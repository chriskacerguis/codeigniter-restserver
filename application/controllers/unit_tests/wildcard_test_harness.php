<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Example
 *
 * This is a test for the wildcard. Wildcard allows you to specify an authentication type rule for an entire controller. Example would be $config['auth_override_class_method']['wildcard_test_cases']['*'] = 'basic'; This sets the authentication method for the Wildcard_test_harness controller to basic.
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
class Wildcard_test_harness extends CI_Controller
{
	function __construct(){
		parent::__construct();
		$this->load->library('unit_test');
		$this->load->helper('url');
	}

	//curl interface functions
	private function makeRequest($url, $cred = '', $curlopts = array()){
		$ch = curl_init($url);
		$items = array(
		    CURLOPT_URL => $url,
		    CURLOPT_USERPWD => $cred
		);
		foreach($curlopts as $opt => $value)
			$items[$opt] = $value;
		curl_setopt_array($ch, $items);
		ob_start();
		$response = curl_exec($ch);
		$contents = ob_get_contents();
		ob_end_clean();
		$info = curl_getinfo($ch);

		$errno = curl_errno($ch);
		$error = curl_error($ch);
		curl_close($ch);
		return array('response' => $response, 'contents' => $contents, 'errno' => $errno, 'error' => $error, 'info' => $info);//return 
	}

	/*
	These two test cases will test if the authentication is working for the wildcard method. The curl requests may not work if you do not have an .htaccess file with mod rewrite in the same directory as your index.php file. If you don't have that file you can add it or change the url below to the one that includes index.php.
	*/
	function index(){

		//not authorized
		//no htaccess: $test = $this->makeRequest(base_url() . 'index.php/unit_tests/wildcard_test_cases/digest', '');
		$test = $this->makeRequest(base_url() . 'unit_tests/wildcard_test_cases/digest', '');
		// print_r($test);
		$this->unit->run($test['info']['http_code'], '401', 'Not Authorized test (No credentials provided)');
		//no htaccess: $test = $this->makeRequest(base_url() . 'index.php/unit_tests/wildcard_test_cases/digest', 'admin:1234');
		$test = $this->makeRequest(base_url() . 'unit_tests/wildcard_test_cases/digest', 'admin:1234');
		//print_r($test);
		$this->unit->run($test['info']['http_code'], '200', 'Authorized, credentials given');
		echo $this->unit->report();
	}
}
?>