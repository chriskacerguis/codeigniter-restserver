<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Example
 *
 * This is a test for the wildcard .
 *
 * @package		CodeIgniter
 * @subpackage	Rest Server
 * @category	Controller
 * @author		Allen Taylor
 * @link		http://philsturgeon.co.uk/code/
*/

// This can be removed if you use __autoload() in config.php OR use Modular Extensions

/*
In order for this test to work you will need to change the auth_source option in the rest.php config file to '' and uncomment this line $config['auth_override_class_method']['wildcard_test_cases']['*'] = 'basic'; in the file as well. Once these are uncommented the tests will work.
*/
require APPPATH.'/libraries/REST_Controller.php'; 
class Wildcard_test_cases extends REST_Controller{
	function __construct(){
		parent::__construct();
		//set config for test
		$this->config->load('rest');
		$this->config->set_item('rest_auth', 'none');//turn on rest auth
		$this->config->set_item('auth_source', '');//use config array for authentication
		$this->config->set_item('auth_override_class_method', array('wildcard_test_cases' => array('*' => 'basic')));
		$this->load->helper('url');
	}


	function digest_get(){
		$this->response("welcome", 200);
	}
}
?>