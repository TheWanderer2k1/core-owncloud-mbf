<?php

namespace OCA\PackageManager\Db;

use OCP\IDBConnection;
use OCP\AppFramework\Db\Mapper;
use OCA\PackageManager\Db\SubscriptionStatus;

class SubscriptionStatusMapper extends Mapper {

    public function __construct(IDBConnection $db) {
        parent::__construct($db, 'packagemanager_subscription_status', 'OCA\PackageManager\Db\SubscriptionStatus');
    }

    public function findByUserId(string $userId): SubscriptionStatus {
        $sql = 'SELECT * FROM ' . $this->getTableName() . ' WHERE user_id = ?';
        return $this->findEntity($sql, [$userId]);
    }
}