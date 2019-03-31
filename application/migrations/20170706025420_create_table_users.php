<?php
/**
 * @author   Natan Felles <natanfelles@gmail.com>
 */
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Class Migration_create_table_users
 *
 * @property CI_DB_forge         $dbforge
 * @property CI_DB_query_builder $db
 */
class Migration_create_table_users extends CI_Migration {


	protected $table = 'users';


	public function up()
	{
		$fields = array(
			'id'         => [
				'type'           => 'INT(11)',
				'auto_increment' => true,
				'unsigned'       => true,
			],
			'email'      => [
				'type'   => 'VARCHAR(255)',
				'unique' => true,
			],
			'password'   => [
				'type' => 'VARCHAR(64)',
			],
			'firstname'  => [
				'type' => 'VARCHAR(32)',
			],
			'lastname'   => [
				'type' => 'VARCHAR(32)',
			],
			'created_at' => [
				'type' => 'DATETIME',
			],
		);
		$this->dbforge->add_field($fields);
		$this->dbforge->add_key('id', true);
		$this->dbforge->create_table($this->table, true);

		/*for ($i = 1; $i <= 100; $i++)
		{
			$this->db->insert($this->table, [
				'email'      => "user-{$i}@mail.com",
				'password'   => password_hash('codeigniter', PASSWORD_DEFAULT),
				'firstname'  => "Firstname {$i}",
				'lastname'   => "Lastname {$i}",
				'created_at' => date('Y-' . rand(1, 12) . '-' . rand(1, 28) . ' H:i:s'),
			]);
		}*/
	}


	public function down()
	{
		if ($this->db->table_exists($this->table))
		{
			$this->dbforge->drop_table($this->table);
		}
	}

}
