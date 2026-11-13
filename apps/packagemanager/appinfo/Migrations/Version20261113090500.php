<?php
namespace OCA\packagemanager\Migrations;

use Doctrine\DBAL\Schema\Schema;
use OCP\Migration\ISchemaMigration;

class Version20261113090500 implements ISchemaMigration {
    public function changeSchema(Schema $schema, array $options) {
        $prefix = $options['tablePrefix'];
        $tableName = "{$prefix}groups";

        if ($schema->hasTable($tableName)) {
            $table = $schema->getTable($tableName);

            if (!$table->hasColumn('quota')) {
                $table->addColumn('quota', 'string', [
                    'length' => 64,
                    'notnull' => true,
                    'default' => '',
                ]);
            }
        }
    }
}
