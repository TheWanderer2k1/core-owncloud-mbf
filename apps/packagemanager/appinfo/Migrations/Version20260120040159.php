<?php
namespace OCA\packagemanager\Migrations;

use Doctrine\DBAL\Schema\Schema;
use OCP\Migration\ISchemaMigration;

/**
 * Auto-generated migration step: Please modify to your needs!
 */
class Version20260120040159 implements ISchemaMigration {
    private $prefix;

	public function changeSchema(Schema $schema, array $options) {
		$this->prefix = $options['tablePrefix'];

        // Create table 'packages'
		if (!$schema->hasTable("{$this->prefix}packagemanager_packages")) {
			$packagesTable = $schema->createTable("{$this->prefix}packagemanager_packages");
			$packagesTable->addColumn('id', 'integer', [
                'autoincrement' => true,
                'unsigned' => true,
                'notnull' => true,
            ]);
			$packagesTable->addColumn('name', 'text', [
                'notnull' => true,
            ]);
            $packagesTable->addColumn('code', 'text', [
                'notnull' => true,
                'length' => 64,
            ]);
            $packagesTable->addColumn('price', 'decimal', [
                'notnull' => true,
                'default' => '0.00',
            ]);
            $packagesTable->addColumn('quota', 'text', [
                'notnull' => true,
                'length' => 16,
                'default' => '15 GB',
            ]);
            $packagesTable->addColumn('duration', 'integer', [
                'notnull' => true,
                'default' => 1,
                'length' => 4,
            ]);
            $packagesTable->addColumn('unit', 'text', [
                'notnull' => true, 
                'length' => 16,
                'default' => 'month',
            ]);
            $packagesTable->setPrimaryKey(['id']);
        }

        // Create table 'subscription_status'
        if (!$schema->hasTable("{$this->prefix}packagemanager_subscription_status")) {
            $subscriptionStatusTable = $schema->createTable("{$this->prefix}packagemanager_subscription_status");
            $subscriptionStatusTable->addColumn('id', 'integer', [
                'autoincrement' => true,
                'unsigned' => true,
                'notnull' => true,
            ]);
            $subscriptionStatusTable->addColumn('user_id', 'text', [
                'notnull' => true,
            ]);
            $subscriptionStatusTable->addColumn('package_id', 'integer', [
                'notnull' => true,
            ]);
            // unix timestamp
            $subscriptionStatusTable->addColumn('start_at', 'integer', [
                'notnull' => true,
            ]);
            // unix timestamp
            $subscriptionStatusTable->addColumn('end_at', 'integer', [
                'notnull' => true,
            ]);
            $subscriptionStatusTable->addColumn('status', 'text', [
                'notnull' => true,
                'length' => 16,
                'default' => 'active',
            ]);
            $subscriptionStatusTable->setPrimaryKey(['id']);
            $subscriptionStatusTable->addForeignKeyConstraint(
                "{$this->prefix}packagemanager_packages",
                ['package_id'],
                ['id'],
                ['onDelete' => 'CASCADE']
            );
            $subscriptionStatusTable->addForeignKeyConstraint(
                "oc_users",
                ['user_id'],
                ['uid'],
                ['onDelete' => 'CASCADE']
            );
        }

        // Create table 'subscription_history'
        if (!$schema->hasTable("{$this->prefix}packagemanager_subscription_history")) {
            $subscriptionHistoryTable = $schema->createTable("{$this->prefix}packagemanager_subscription_history");
            $subscriptionHistoryTable->addColumn('id', 'integer', [
                'autoincrement' => true,
                'unsigned' => true,
                'notnull' => true,
            ]);
            $subscriptionHistoryTable->addColumn('subscription_status_id', 'integer', [
                'notnull' => true,
            ]);
            $subscriptionHistoryTable->addColumn('user_id', 'text', [
                'notnull' => true,
            ]);
            $subscriptionHistoryTable->addColumn('package_id', 'integer', [
                'notnull' => true,
            ]);
            $subscriptionHistoryTable->addColumn('action_type', 'text', [
                'notnull' => true,
                'length' => 32,
            ]);
            $subscriptionHistoryTable->addColumn('description', 'text', [
                'notnull' => true,
            ]);
            $subscriptionHistoryTable->addColumn('created_at', 'datetime', [
                'notnull' => true,
            ]);
            $subscriptionHistoryTable->setPrimaryKey(['id']);
            $subscriptionHistoryTable->addForeignKeyConstraint(
                "{$this->prefix}packagemanager_subscription_status",
                ['subscription_status_id'],
                ['id'],
            );
            $subscriptionHistoryTable->addForeignKeyConstraint(
                "{$this->prefix}packagemanager_packages",
                ['package_id'],
                ['id'],
            );
            $subscriptionHistoryTable->addForeignKeyConstraint(
                "oc_users",
                ['user_id'],
                ['uid'],
            );
        }
    }
}