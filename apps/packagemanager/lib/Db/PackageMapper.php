<?php

namespace OCA\PackageManager\Db;

use OCP\IDBConnection;
use OCP\AppFramework\Db\Mapper;
use OCA\PackageManager\Db\Package;

class PackageMapper extends Mapper {

    public function __construct(IDBConnection $db) {
        parent::__construct($db, 'packagemanager_packages', 'OCA\PackageManager\Db\Package');
    }

    public function findByCode(string $code): Package {
        $sql = 'SELECT * FROM ' . $this->getTableName() . ' WHERE code = ?';
        return $this->findEntity($sql, [$code]);
    }
}