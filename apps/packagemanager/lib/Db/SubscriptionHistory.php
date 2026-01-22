<?php

namespace OCA\PackageManager\Db;

use OCP\AppFramework\Db\Entity;

class SubscriptionHistory extends Entity {
    protected int $subscriptionStatusId;
    protected string $userId;
    protected int $packageId;
    protected string $actionType;
    protected string $description;
    protected string $createdAt;

    public function __construct(int $subscriptionStatusId = 0, string $userId = '', int $packageId = 0, string $actionType = '', string $description = '', string $createdAt = null) {
        parent::__construct();
        $this->subscriptionStatusId = $subscriptionStatusId;
        $this->userId = $userId;
        $this->packageId = $packageId;
        $this->actionType = $actionType;
        $this->description = $description;
        $this->createdAt = $createdAt ?? (new \DateTime())->format('Y-m-d H:i:s');
        // mark field update else cannot insert into DB
        $this->markFieldUpdated('subscriptionStatusId');
        $this->markFieldUpdated('userId');
        $this->markFieldUpdated('packageId');
        $this->markFieldUpdated('actionType');
        $this->markFieldUpdated('description');
        $this->markFieldUpdated('createdAt');
    }

    public function getSubscriptionStatusId() {
        return $this->getter('subscriptionStatusId');
    }

    public function setSubscriptionStatusId(int $subscriptionStatusId) {
        $this->setter('subscriptionStatusId', [$subscriptionStatusId]);
    }

    public function getUserId() {
        return $this->getter('userId');
    }

    public function setUserId(string $userId) {
        $this->setter('userId', [$userId]);
    }

    public function getPackageId() {
        return $this->getter('packageId');
    }

    public function setPackageId(int $packageId) {
        $this->setter('packageId', [$packageId]);
    }

    public function getActionType() {
        return $this->getter('actionType');
    }

    public function setActionType(string $actionType) {
        $this->setter('actionType', [$actionType]);
    }

    public function getDescription() {
        return $this->getter('description');
    }

    public function setDescription(string $description) {
        $this->setter('description', [$description]);
    }

    public function getCreatedAt() {
        return $this->getter('createdAt');
    }

    public function setCreatedAt(string $createdAt) {
        $this->setter('createdAt', [$createdAt]);
    }
}