<?php

namespace OCA\PackageManager\Db;

use OCP\IDBConnection;
use OCP\AppFramework\Db\Mapper;
use OCA\PackageManager\Db\SubscriptionHistory;

class SubscriptionHistoryMapper extends Mapper {

    public function __construct(IDBConnection $db) {
        parent::__construct($db, 'packagemanager_subscription_history', 'OCA\PackageManager\Db\SubscriptionHistory');
    }
}