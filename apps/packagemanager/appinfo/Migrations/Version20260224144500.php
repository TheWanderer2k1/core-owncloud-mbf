<?php
namespace OCA\PackageManager\Migrations;

use Doctrine\DBAL\Schema\Schema;
use OCP\Migration\ISchemaMigration;

class Version20260224144500 implements ISchemaMigration {
    private $prefix;

	public function changeSchema(Schema $schema, array $options) {
		$this->prefix = $options['tablePrefix'];
        if ($schema->hasTable("{$this->prefix}packagemanager_subscription_history")) {
            $table = $schema->getTable("{$this->prefix}packagemanager_subscription_history");
            
            if (!$table->hasColumn('package_name')) {
                $table->addColumn('package_name', 'text', ['notnull' => false]);
            }
            if (!$table->hasColumn('package_code')) {
                $table->addColumn('package_code', 'text', ['notnull' => false, 'length' => 64]);
            }
            if (!$table->hasColumn('package_quota')) {
                $table->addColumn('package_quota', 'text', ['notnull' => false, 'length' => 16]);
            }
            if (!$table->hasColumn('package_duration')) {
                $table->addColumn('package_duration', 'integer', ['notnull' => false, 'length' => 4]);
            }
            if (!$table->hasColumn('package_unit')) {
                $table->addColumn('package_unit', 'text', ['notnull' => false, 'length' => 16]);
            }
            if (!$table->hasColumn('package_price')) {
                $table->addColumn('package_price', 'decimal', ['notnull' => false, 'scale' => 2, 'precision' => 10]);
            }
            if (!$table->hasColumn('package_expiry_date')) {
                $table->addColumn('package_expiry_date', 'integer', ['notnull' => false]);
            }
        }
    }
}
