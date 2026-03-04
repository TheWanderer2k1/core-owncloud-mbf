<?php
namespace OCA\Files\Migrations;

use Doctrine\DBAL\Schema\Schema;
use OCP\Migration\ISchemaMigration;

/**
 * Creates the oc_recent_files table to track actual user operations.
 */
class Version20260225120000 implements ISchemaMigration {
	public function changeSchema(Schema $schema, array $options) {
		$prefix = $options['tablePrefix'];
		if (!$schema->hasTable("{$prefix}recent_files")) {
			$table = $schema->createTable("{$prefix}recent_files");
			$table->addColumn('id', 'bigint', [
				'autoincrement' => true,
				'unsigned' => true,
				'notnull' => true,
				'length' => 20,
			]);
			$table->addColumn('uid', 'string', [
				'notnull' => true,
				'length' => 64,
			]);
			$table->addColumn('fileid', 'bigint', [
				'unsigned' => true,
				'notnull' => true,
				'length' => 20,
			]);
			$table->addColumn('timestamp', 'integer', [
				'notnull' => true,
				'unsigned' => true,
				'length' => 11,
			]);
			$table->addColumn('action', 'string', [
				'notnull' => true,
				'length' => 32,
			]);
			$table->setPrimaryKey(['id']);
			$table->addIndex(['uid', 'timestamp'], 'recent_uid_ts_idx');
			$table->addIndex(['uid', 'fileid'], 'recent_uid_fileid_idx');
		}
	}
}

