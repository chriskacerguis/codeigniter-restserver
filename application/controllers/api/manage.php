<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Manage Controller
 *
 * This is a basic Key Management REST controller to make and delete keys.
 *
 * @package		CodeIgniter
 * @subpackage	Rest Server
 * @category	Controller
 * @author		Phil Sturgeon
 * @link		http://philsturgeon.co.uk/code/
*/

// This can be removed if you use __autoload() in config.php
require(APPPATH.'/libraries/REST_Controller.php');

class Manage extends REST_Controller
{
	protected $rest_permissions = array(
		'key_post' => 10,
		'key_delete' => 10,
	);

	/**
	 * Key Delete
	 *
	 * Remove a key from the database to stop it working.
	 *
	 * @access	public
	 * @return	void
	 */
	function key_put()
    {
		$this->load->helper('security');

		do
		{
			$salt = dohash(time().mt_rand());
			$key = substr($salt, 0, config_item('rest_key_length'));
		}

		// Already in the DB? Fail. Try again
		while (self::_key_exists($key));

		// If no key level provided, give them a rubbish one
		$level = $this->put('level') ? $this->put('level') : 1;

		// Insert the new key
		if (self::_insert_key($key, $level))
		{
			$this->response(array('key' => $key), 201); // 201 = Created
		}

		else
		{
			$this->response(array('error' => 'Could not save the key.'), 500); // 500 = Internal Server Error
		}
    }

	// --------------------------------------------------------------------

	/**
	 * Key Delete
	 *
	 * Remove a key from the database to stop it working.
	 *
	 * @access	public
	 * @return	void
	 */
	function key_delete()
    {
		$key = $this->delete('key');

		// Does this key even exist?
		if ( ! self::_key_exists($key))
		{
			// NOOOOOOOOO!
			$this->response(array('error' => 'Invalid API Key.'), 400);
		}

		// Kill it
		self::_delete_key($key);

		// Tell em we killed it
		$this->response(array('success' => 'API Key was deleted.'), 200);
    }

	private function _key_exists($key)
	{
		return $this->rest->db->where('key', $key)->count_all_results('keys') > 0;
	}

	private function _insert_key($key, $level)
	{
		return $this->rest->db->set(array(
			'key' => $key,
			'level' => $level,
			'date_created' => function_exists('now') ? now() : time()
		))->insert('keys');
	}

	private function _delete_key($key)
	{
		return $this->rest->db->where('key', $key)->delete('keys');
	}
}