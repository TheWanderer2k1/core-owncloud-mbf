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
    protected string $packageName;
    protected string $packageCode;
    protected string $packageQuota;
    protected int $packageDuration;
    protected string $packageUnit;
    protected float $packagePrice;
    protected int $packageExpiryDate;

    public function __construct(int $subscriptionStatusId = 0, string $userId = '', int $packageId = 0, string $actionType = '', string $description = '', string $createdAt = null, string $packageName = '', string $packageCode = '', string $packageQuota = '', int $packageDuration = 0, string $packageUnit = '', float $packagePrice = 0.0, int $packageExpiryDate = 0) {
        parent::__construct();
        $this->subscriptionStatusId = $subscriptionStatusId;
        $this->userId = $userId;
        $this->packageId = $packageId;
        $this->actionType = $actionType;
        $this->description = $description;
        $this->createdAt = $createdAt ?? (new \DateTime())->format('Y-m-d H:i:s');
        $this->packageName = $packageName;
        $this->packageCode = $packageCode;
        $this->packageQuota = $packageQuota;
        $this->packageDuration = $packageDuration;
        $this->packageUnit = $packageUnit;
        $this->packagePrice = $packagePrice;
        $this->packageExpiryDate = $packageExpiryDate;
        
        // mark field update else cannot insert into DB
        $this->markFieldUpdated('subscriptionStatusId');
        $this->markFieldUpdated('userId');
        $this->markFieldUpdated('packageId');
        $this->markFieldUpdated('actionType');
        $this->markFieldUpdated('description');
        $this->markFieldUpdated('createdAt');
        $this->markFieldUpdated('packageName');
        $this->markFieldUpdated('packageCode');
        $this->markFieldUpdated('packageQuota');
        $this->markFieldUpdated('packageDuration');
        $this->markFieldUpdated('packageUnit');
        $this->markFieldUpdated('packagePrice');
        $this->markFieldUpdated('packageExpiryDate');
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

    public function getPackageName() {
        return $this->getter('packageName');
    }

    public function setPackageName(string $packageName) {
        $this->setter('packageName', [$packageName]);
    }

    public function getPackageCode() {
        return $this->getter('packageCode');
    }

    public function setPackageCode(string $packageCode) {
        $this->setter('packageCode', [$packageCode]);
    }

    public function getPackageQuota() {
        return $this->getter('packageQuota');
    }

    public function setPackageQuota(string $packageQuota) {
        $this->setter('packageQuota', [$packageQuota]);
    }

    public function getPackageDuration() {
        return $this->getter('packageDuration');
    }

    public function setPackageDuration(int $packageDuration) {
        $this->setter('packageDuration', [$packageDuration]);
    }

    public function getPackageUnit() {
        return $this->getter('packageUnit');
    }

    public function setPackageUnit(string $packageUnit) {
        $this->setter('packageUnit', [$packageUnit]);
    }

    public function getPackagePrice() {
        return $this->getter('packagePrice');
    }

    public function setPackagePrice(float $packagePrice) {
        $this->setter('packagePrice', [$packagePrice]);
    }

    public function getPackageExpiryDate() {
        return $this->getter('packageExpiryDate');
    }

    public function setPackageExpiryDate(int $packageExpiryDate) {
        $this->setter('packageExpiryDate', [$packageExpiryDate]);
    }
}