<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Keys Controller
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

class Keys extends REST_Controller
{
	protected $rest_permissions = array(
		'index_put' => 10,
		'index_delete' => 10,
		'level_post' => 10,
		'regenerate_post' => 10,
	);

	/**
	 * Key Delete
	 *
	 * Remove a key from the database to stop it working.
	 *
	 * @access	public
	 * @return	void
	 */
	public function index_put()
    {
		// Build a new key
		$key = self::_generate_key();

		// If no key level provided, give them a rubbish one
		$level = $this->put('level') ? $this->put('level') : 1;

		// Insert the new key
		if (self::_insert_key($key, $level))
		{
			$this->response(array('status' => 1, 'key' => $key), 201); // 201 = Created
		}

		else
		{
			$this->response(array('status' => 0, 'error' => 'Could not save the key.'), 500); // 500 = Internal Server Error
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
	public function index_delete()
    {
		$key = $this->delete('key');

		// Does this key even exist?
		if ( ! self::_key_exists($key))
		{
			// NOOOOOOOOO!
			$this->response(array('status' => 0, 'error' => 'Invalid API Key.'), 400);
		}

		// Kill it
		self::_delete_key($key);

		// Tell em we killed it
		$this->response(array('status' => 1, 'success' => 'API Key was deleted.'), 200);
    }

	// --------------------------------------------------------------------

	/**
	 * Update Key
	 *
	 * Change the level
	 *
	 * @access	public
	 * @return	void
	 */
	public function level_post()
    {
		$key = $this->post('key');
		$new_level = $this->post('level');
		
		// Does this key even exist?
		if ( ! self::_key_exists($key))
		{
			// NOOOOOOOOO!
			$this->response(array('error' => 'Invalid API Key.'), 400);
		}

		// Update the key level
		if (self::_update_key($key, array('level' => $new_level)))
		{
			$this->response(array('status' => 1, 'success' => 'Key was updated.'), 200); // 201 = Created
		}

		else
		{
			$this->response(array('status' => 0, 'error' => 'Could not update the key level.'), 500); // 500 = Internal Server Error
		}
    }

	// --------------------------------------------------------------------

	/**
	 * Regenerate Key
	 *
	 * Remove a key from the database to stop it working.
	 *
	 * @access	public
	 * @return	void
	 */
	public function regenerate_post()
    {
		$old_key = $this->post('key');
		$key_details = self::_get_key($old_key);

		// The key wasnt found
		if ( ! $key_details)
		{
			// NOOOOOOOOO!
			$this->response(array('status' => 0, 'error' => 'Invalid API Key.'), 400);
		}

		// Build a new key
		$new_key = self::_generate_key();

		// Insert the new key
		if (self::_insert_key($new_key, $key_details->level))
		{
			// Suspend old key
			self::_update_key($old_key, array('level' => 0));

			$this->response(array('status' => 1, 'key' => $new_key), 201); // 201 = Created
		}

		else
		{
			$this->response(array('status' => 0, 'error' => 'Could not save the key.'), 500); // 500 = Internal Server Error
		}
    }

	// --------------------------------------------------------------------

	/* Helper Methods */
	
	private function _generate_key()
	{
		$this->load->helper('security');
		
		do
		{
			$salt = dohash(time().mt_rand());
			$new_key = substr($salt, 0, config_item('rest_key_length'));
		}

		// Already in the DB? Fail. Try again
		while (self::_key_exists($new_key));

		return $new_key;
	}

	// --------------------------------------------------------------------

	/* Private Data Methods */

	private function _get_key($key)
	{
		return $this->rest->db->where('key', $key)->get('keys')->row();
	}

	// --------------------------------------------------------------------

	private function _key_exists($key)
	{
		return $this->rest->db->where('key', $key)->count_all_results('keys') > 0;
	}

	// --------------------------------------------------------------------

	private function _insert_key($key, $level)
	{
		return $this->rest->db->set(array(
			'key' => $key,
			'level' => $level,
			'date_created' => function_exists('now') ? now() : time()
		))->insert('keys');
	}

	// --------------------------------------------------------------------

	private function _update_key($key, $data)
	{
		return $this->rest->db->where('key', $key)->update('keys', $data);
	}

	// --------------------------------------------------------------------

	private function _delete_key($key)
	{
		return $this->rest->db->where('key', $key)->delete('keys');
	}
}