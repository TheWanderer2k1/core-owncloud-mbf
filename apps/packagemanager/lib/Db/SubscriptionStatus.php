<?php

namespace OCA\PackageManager\Db;

use OCP\AppFramework\Db\Entity;

class SubscriptionStatus extends Entity {
    protected string $userId;
    protected int $packageId;
    protected int $startAt;
    protected int $endAt;
    protected string $status;

    public function __construct(string $userId = '', int $packageId = 0, int $startAt = 0, int $endAt = 0, string $status = '') {
        parent::__construct();
        $this->userId = $userId;
        $this->packageId = $packageId;
        $this->startAt = $startAt;
        $this->endAt = $endAt;
        $this->status = $status;
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

    public function getStartAt() {
        return $this->getter('startAt');
    }

    public function setStartAt(int $startAt) {
        $this->setter('startAt', [$startAt]);
    }

    public function getEndAt() {
        return $this->getter('endAt');
    }

    public function setEndAt(int $endAt) {
        $this->setter('endAt', [$endAt]);
    }

    public function getStatus() {
        return $this->getter('status');
    }

    public function setStatus(string $status) {
        $this->setter('status', [$status]);
    }
}