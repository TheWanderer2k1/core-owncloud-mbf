<?php

namespace OCA\FAQ\Db;

use OCP\IDBConnection;
use OCP\AppFramework\Db\Mapper;
use OCA\FAQ\Db\Faq;

class FaqMapper extends Mapper {

    public function __construct(IDBConnection $db) {
        parent::__construct($db, 'faq', 'OCA\FAQ\Db\Faq');
    }

    public function findById(int $id): Faq {
        $sql = 'SELECT * FROM ' . $this->getTableName() . ' WHERE id = ?';
        return $this->findEntity($sql, [$id]);
    }

    public function findAll(): array {
        $sql = 'SELECT * FROM ' . $this->getTableName() . ' ORDER BY id DESC';
        return $this->findEntities($sql);
    }

    public function findActive(): array {
        $sql = 'SELECT * FROM ' . $this->getTableName() . ' WHERE status = ? ORDER BY id DESC';
        return $this->findEntities($sql, [1]);
    }
}
