<?php

declare(strict_types=1);

namespace chriskacerguis\RestServer\Database\Migrations;

use chriskacerguis\RestServer\Config\Rest as RestConfig;
use CodeIgniter\Database\Migration;

class CreateAccessTable extends Migration
{
    public function up(): void
    {
        $config = new RestConfig();
        $table = $config->accessTable;

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
            'controller' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
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
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('key');
        $this->forge->addKey('controller');
        $this->forge->addKey('method');

        $this->forge->createTable($table, true);
    }

    public function down(): void
    {
        $config = new RestConfig();
        $this->forge->dropTable($config->accessTable, true);
    }
}
