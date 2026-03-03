<?php

namespace OCA\PackageManager\BackgroundJob;

use OC\BackgroundJob\Job;
use OCP\IUserManager;
use OCA\PackageManager\Service\LogService;
use OCA\PackageManager\Db\SubscriptionStatusMapper;
use OCA\PackageManager\Db\SubscriptionStatus;
use OCA\PackageManager\Db\SubscriptionHistoryMapper;
use OCA\PackageManager\Db\SubscriptionHistory;

class AutoCancelExpiredPackage extends Job {
    private LogService $logger;
    private IUserManager $userManager;
    private SubscriptionStatusMapper $subscriptionStatusMapper;
    private SubscriptionHistoryMapper $subscriptionHistoryMapper;

    public function __construct(LogService $logger,
                                IUserManager $userManager,
                                SubscriptionStatusMapper $subscriptionStatusMapper, 
                                SubscriptionHistoryMapper $subscriptionHistoryMapper) {
        $this->subscriptionStatusMapper = $subscriptionStatusMapper;
        $this->subscriptionHistoryMapper = $subscriptionHistoryMapper;
        $this->logger = $logger;
        $this->userManager = $userManager;
    }

    public function run($argument) {
        try {
            $expiredSubscriptions = $this->subscriptionStatusMapper->findExpiredSubscriptions();
            foreach ($expiredSubscriptions as $subscription) {
                $this->logger->debug("Found expired subscription for user: " . $subscription->getUserId());
                // Cancel the subscription and log the event
                $subscription->setStatus('expired');
                $this->subscriptionStatusMapper->update($subscription);
                // Create a history entry for the cancellation
                $history = new SubscriptionHistory(
                    $subscription->getId(),
                    $subscription->getUserId(),
                    $subscription->getPackageId(),
                    'auto_expired',
                    "System cancelled user " . $subscription->getUserId() . "'s subscribed package " . $subscription->getPackageId() . " due to expiration"
                );
                $this->subscriptionHistoryMapper->insert($history);
                // Deactivate user's account
                $user = $this->userManager->get($subscription->getUserId());
                if ($user) {
                    $user->setEnabled(false);
                    $this->logger->debug("Deactivated user account: " . $subscription->getUserId());
                } else {
                    $this->logger->error("User not found for subscription: " . $subscription->getUserId());
                }
            }
        } catch (\Exception $e) {
            $this->logger->error("Error in AutoCancelExpiredPackage background job: " . $e->getMessage());
        }
    }
}