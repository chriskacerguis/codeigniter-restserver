<?php
/**
 * @author   Natan Felles <natanfelles@gmail.com>
 */
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Class Migration_create_table_api_keys
 *
 * @property CI_DB_forge         $dbforge
 * @property CI_DB_query_builder $db
 */
class Migration_create_table_api_keys extends CI_Migration {


	public function up()
	{
		$this->config->load('rest');
		$table = config_item('rest_keys_table');
		$fields = array(
			'id'                           => [
				'type'           => 'INT(11)',
				'auto_increment' => true,
				'unsigned'       => true,
			],
			'user_id'                      => [
				'type'     => 'INT(11)',
				'unsigned' => true,
			],
			config_item('rest_key_column') => [
				'type'   => 'VARCHAR(' . config_item('rest_key_length') . ')',
				'unique' => true,
			],
			'level'                        => [
				'type' => 'INT(2)',
			],
			'ignore_limits'                => [
				'type'    => 'TINYINT(1)',
				'default' => 0,
			],
			'is_private_key'               => [
				'type'    => 'TINYINT(1)',
				'default' => 0,
			],
			'ip_addresses'                 => [
				'type' => 'TEXT',
				'null' => true,
			],
			'date_created'                 => [
				'type' => 'INT(11)',
			],
		);
		$this->dbforge->add_field($fields);
		$this->dbforge->add_key('id', true);
		$this->dbforge->create_table($table);
		$this->db->query(add_foreign_key($table, 'user_id', 'users(id)', 'CASCADE', 'CASCADE'));
	}


	public function down()
	{
		$table = config_item('rest_key_column');
		if ($this->db->table_exists($table))
		{
			$this->db->query(drop_foreign_key($table, 'user_id'));
			$this->dbforge->drop_table($table);
		}
	}

}
