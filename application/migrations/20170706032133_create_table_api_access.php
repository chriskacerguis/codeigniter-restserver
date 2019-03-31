<?php
/**
 * @author   Natan Felles <natanfelles@gmail.com>
 */
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Class Migration_create_table_api_access
 *
 * @property CI_DB_forge         $dbforge
 * @property CI_DB_query_builder $db
 */
class Migration_create_table_api_access extends CI_Migration {


	public function up()
	{
		$this->config->load('rest');
		$table = config_item('rest_access_table');
		$fields = array(
			'id'            => [
				'type'           => 'INT(11)',
				'auto_increment' => true,
				'unsigned'       => true,
			],
			'key'           => [
				'type' => 'VARCHAR(' . config_item('rest_key_length') . ')',
			],
			'all_access'    => [
				'type'    => 'TINYINT(1)',
				'default' => 0,
			],
			'controller'    => [
				'type' => 'VARCHAR(50)',
			],
			'date_created'  => [
				'type' => 'DATETIME',
				'null' => true,
			],
			'date_modified' => [
				'type' => 'TIMESTAMP',
			],
		);
		$this->dbforge->add_field($fields);
		$this->dbforge->add_key('id', true);
		$this->dbforge->add_key('controller');
		$this->dbforge->create_table($table);
		$this->db->query(add_foreign_key($table, 'key',
			config_item('rest_keys_table') . '(' . config_item('rest_key_column') . ')', 'CASCADE', 'CASCADE'));
	}


	public function down()
	{
		$table = config_item('rest_access_table');
		if ($this->db->table_exists($table))
		{
			$this->db->query(drop_foreign_key($table, 'key'));
			$this->dbforge->drop_table($table);
		}
	}

}
