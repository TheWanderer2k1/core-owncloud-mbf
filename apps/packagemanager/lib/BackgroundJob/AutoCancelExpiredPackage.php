<?php

namespace OCA\PackageManager\BackgroundJob;

use OC\BackgroundJob\Job;
use OCP\IUserManager;
use OCA\PackageManager\Service\LogService;
use OCA\PackageManager\Db\SubscriptionStatusMapper;
use OCA\PackageManager\Db\SubscriptionStatus;
use OCA\PackageManager\Db\SubscriptionHistoryMapper;
use OCA\PackageManager\Db\SubscriptionHistory;
use OCA\PackageManager\Db\PackageMapper;

class AutoCancelExpiredPackage extends Job {
    private LogService $logger;
    private IUserManager $userManager;
    private SubscriptionStatusMapper $subscriptionStatusMapper;
    private SubscriptionHistoryMapper $subscriptionHistoryMapper;
    private PackageMapper $packageMapper;

    public function __construct(LogService $logger,
                                IUserManager $userManager,
                                SubscriptionStatusMapper $subscriptionStatusMapper, 
                                SubscriptionHistoryMapper $subscriptionHistoryMapper,
                                PackageMapper $packageMapper) {
        $this->subscriptionStatusMapper = $subscriptionStatusMapper;
        $this->subscriptionHistoryMapper = $subscriptionHistoryMapper;
        $this->logger = $logger;
        $this->userManager = $userManager;
        $this->packageMapper = $packageMapper;
    }

    public function run($argument) {
        try {
            $expiredSubscriptions = $this->subscriptionStatusMapper->findExpiredSubscriptions();
            foreach ($expiredSubscriptions as $expiredSubscription) {
                $this->logger->debug("Found expired subscription for user: " . $expiredSubscription->getUserId());
                // Cancel the subscription and log the event
                $expiredSubscription->setStatus('expired');
                $this->subscriptionStatusMapper->update($expiredSubscription);
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
                // Deactivate user's account
                $user = $this->userManager->get($expiredSubscription->getUserId());
                if ($user) {
                    $user->setEnabled(false);
                    $this->logger->debug("Deactivated user account: " . $expiredSubscription->getUserId());
                } else {
                    $this->logger->error("User not found for subscription: " . $expiredSubscription->getUserId());
                }
            }
        } catch (\Exception $e) {
            $this->logger->error("Error in AutoCancelExpiredPackage background job: " . $e->getMessage());
        }
    }
}