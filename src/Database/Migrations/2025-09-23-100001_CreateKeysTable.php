<?php

declare(strict_types=1);

namespace chriskacerguis\RestServer\Database\Migrations;

use CodeIgniter\Database\Migration;
use chriskacerguis\RestServer\Config\Rest as RestConfig;

class CreateKeysTable extends Migration
{
    public function up(): void
    {
        $config = new RestConfig();
        $table  = $config->keysTable;

        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'constraint'     => 20,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'key' => [
                'type'       => 'VARCHAR',
                'constraint' => $config->keyLength ?? 40,
            ],
            'level' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
            ],
            'ignore_limits' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'is_private_key' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'ip_addresses' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            $config->keysExpiryColumn => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('key');

        $this->forge->createTable($table, true);
    }

    public function down(): void
    {
        $config = new RestConfig();
        $this->forge->dropTable($config->keysTable, true);
    }
}
