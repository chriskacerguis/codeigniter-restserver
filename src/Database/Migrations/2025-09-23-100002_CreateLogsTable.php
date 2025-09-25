<?php

declare(strict_types=1);

namespace chriskacerguis\RestServer\Database\Migrations;

use chriskacerguis\RestServer\Config\Rest as RestConfig;
use CodeIgniter\Database\Migration;

class CreateLogsTable extends Migration
{
    public function up(): void
    {
        $config = new RestConfig();
        $table = $config->logsTable;

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
                'null'       => true,
            ],
            'uri' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'method' => [
                'type'       => 'VARCHAR',
                'constraint' => 6,
            ],
            'params' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'api_version' => [
                'type'       => 'VARCHAR',
                'constraint' => 10,
                'null'       => true,
            ],
            'ip_address' => [
                'type'       => 'VARCHAR',
                'constraint' => 45,
                'null'       => true,
            ],
            'authorized' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
            ],
            'response_code' => [
                'type'       => 'SMALLINT',
                'constraint' => 3,
                'null'       => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey(['uri', 'method']);
        $this->forge->addKey('key');

        $this->forge->createTable($table, true);
    }

    public function down(): void
    {
        $config = new RestConfig();
        $this->forge->dropTable($config->logsTable, true);
    }
}
