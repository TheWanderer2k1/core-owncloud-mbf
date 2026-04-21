<?php

namespace OCA\PackageManager\BackgroundJob;

use OC\BackgroundJob\Job;
use OCP\IUserManager;
use OCP\IConfig;
use OCA\PackageManager\Service\LogService;
use OCA\PackageManager\Db\SubscriptionStatusMapper;
use OCA\PackageManager\Db\SubscriptionStatus;
use OCA\PackageManager\Db\SubscriptionHistoryMapper;
use OCA\PackageManager\Db\SubscriptionHistory;
use OCA\PackageManager\Db\PackageMapper;

class AutoCancelExpiredPackage extends Job {
    private LogService $logger;
    private IConfig $config;
    private IUserManager $userManager;
    private SubscriptionStatusMapper $subscriptionStatusMapper;
    private SubscriptionHistoryMapper $subscriptionHistoryMapper;
    private PackageMapper $packageMapper;

    public function __construct(LogService $logger,
                                IConfig $config,
                                IUserManager $userManager,
                                SubscriptionStatusMapper $subscriptionStatusMapper, 
                                SubscriptionHistoryMapper $subscriptionHistoryMapper,
                                PackageMapper $packageMapper) {
        $this->subscriptionStatusMapper = $subscriptionStatusMapper;
        $this->subscriptionHistoryMapper = $subscriptionHistoryMapper;
        $this->logger = $logger;
        $this->userManager = $userManager;
        $this->packageMapper = $packageMapper;
        $this->config = $config;
    }

    public function run($argument) {
        try {
            $expiredSubscriptions = $this->subscriptionStatusMapper->findExpiredSubscriptions();
            foreach ($expiredSubscriptions as $expiredSubscription) {
                $this->logger->debug("Found expired subscription for user: " . $expiredSubscription->getUserId());
                // Cancel the subscription
                $expiredSubscription->setStatus('expired');
                $this->subscriptionStatusMapper->update($expiredSubscription);
                // Deactivate user's account
                $userId = $expiredSubscription->getUserId();
                $user = $this->userManager->get($userId);
                if ($user) {
                    $defaultQuota = $this->config->getSystemValue('default_user_quota', '1 MB');
                    $user->setQuota($defaultQuota);
                    $usedSpace = $this->getUserUsedSpace($userId);
                    $packageQuotaBytes = \OCP\Util::computerFileSize($defaultQuota);
                    if ($usedSpace === null || $usedSpace > $packageQuotaBytes) {
                        $user->setEnabled(false);
                        $this->logger->info("Deactivated user because used space exceeded default quota: " . $expiredSubscription->getUserId());   
                    }
                } else {
                    $this->logger->error("User not found for subscription: " . $expiredSubscription->getUserId());
                }
                // Get the corresponding package details
                $package = $this->packageMapper->findById($expiredSubscription->getPackageId());
                // Create a history entry for the cancellation
                $history = new SubscriptionHistory(
                    $expiredSubscription->getId(),
                    $expiredSubscription->getUserId(),
                    $expiredSubscription->getPackageId(),
                    'auto_expired',
                    "System cancelled user " . $expiredSubscription->getUserId() . "'s subscribed package " . $expiredSubscription->getPackageId() . " due to expiration",
                    null,
                    $package->getName(),
                    $package->getCode(),
                    $package->getQuota(),
                    $package->getDuration(),
                    $package->getUnit(),
                    $package->getPrice(),
                    $expiredSubscription->getEndAt()
                );
                $this->subscriptionHistoryMapper->insert($history);
            }
        } catch (\Exception $e) {
            $this->logger->error("Error in AutoCancelExpiredPackage background job: " . $e->getMessage());
        }
    }

    private function getUserUsedSpace(string $userId): ?float {
        try {
            if (!$userId) {
                // if there is no userId, query will return the total used space of all users
                return null;
            }
            $sql = 'SELECT SUM(fc.size) as total_size'.
                    ' FROM `*PREFIX*filecache` as fc '.
                    ' JOIN `*PREFIX*storages` as st ON fc.storage = st.numeric_id '.
                    ' WHERE st.id LIKE ? '.
                    ' AND fc.path LIKE ? '.
                    ' AND fc.mimetype != (SELECT id FROM `*PREFIX*mimetypes` WHERE mimetype = ?)';
            $likeUserId = '%'.$userId;
            $likePath = 'files/%';
            $mimetype = 'httpd/unix-directory';
            $dbConnection = \OC::$server->getDatabaseConnection();
            $query = $dbConnection->prepare($sql);
            $query->bindParam(1, $likeUserId, \PDO::PARAM_STR);
            $query->bindParam(2, $likePath, \PDO::PARAM_STR);
            $query->bindParam(3, $mimetype, \PDO::PARAM_STR);
            $query->execute();
            $row = $query->fetch();
            $query->closeCursor();
            if ($row) {
                return (float)reset($row);
            }
            return 0.0;
        } catch (\Throwable $e) {
            $this->logger->error("Get user used space error for user $userId: " . $e->getMessage());
            return null;
        }
    }
}