<?php

declare(strict_types=1);

namespace chriskacerguis\RestServer\Database\Migrations;

use CodeIgniter\Database\Migration;
use chriskacerguis\RestServer\Config\Rest as RestConfig;

class CreateLimitsTable extends Migration
{
    public function up(): void
    {
        $config = new RestConfig();
        $table  = $config->limitsTable;

        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'constraint'     => 20,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'uri' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'class' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'method' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'api_key' => [
                'type'       => 'VARCHAR',
                'constraint' => $config->keyLength ?? 40,
                'null'       => true,
            ],
            'ip_address' => [
                'type'       => 'VARCHAR',
                'constraint' => 45,
                'null'       => true,
            ],
            'count' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
            ],
            'limit' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],
            'reset_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey(['uri', 'method']);
        $this->forge->addKey('api_key');
        $this->forge->addKey('ip_address');

        $this->forge->createTable($table, true);
    }

    public function down(): void
    {
        $config = new RestConfig();
        $this->forge->dropTable($config->limitsTable, true);
    }
}
