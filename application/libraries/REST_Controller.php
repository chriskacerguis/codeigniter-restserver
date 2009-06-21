<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class REST_Controller extends Controller {
    
    private $method;
    private $format;
    
    private $get_args;
    private $put_args;
    private $args;
    
    // List all supported methods, the first will be the default format
    private $supported_formats = array(
		'xml' 		=> 'application/xml',
		'json' 		=> 'application/json',
		'serialize' => 'text/plain',
		'php' 		=> 'text/plain',
		'html' 		=> 'text/html',
		'csv' 		=> 'application/csv'
	);
    
    // Constructor function
    function __construct()
    {
        parent::Controller();
        
	    // How is this request being made? POST, DELETE, GET, PUT?
	    $this->method = $this->_detect_method();
	    
        // Lets grab the config and get ready to party
        $this->load->config('rest');
	    
        if($this->config->item('rest_auth') == 'basic')
        {
        	$this->_prepareBasicAuth();
        }
        
        elseif($this->config->item('rest_auth') == 'digest')
        {
        	$this->_prepareDigestAuth();
        }
        
        // Set caching based on the REST cache config item
        $this->output->cache( $this->config->item('rest_cache') );
        
        // Set up our GET variables
    	$this->get_args = $this->uri->ruri_to_assoc();
    	
    	// Set up out PUT variables
    	parse_str(file_get_contents('php://input'), $this->put_args);
    	
    	// Merge both for one mega-args variable
    	$this->args = array_merge($this->get_args, $this->put_args);
    	
    	// Which format should the data be returned in?
	    $this->format = $this->_detect_format();
    }
    
    /* 
     * Remap
     * 
     * Requests are not made to methods directly The request will be for an "object".
     * this simply maps the object and method to the correct Controller method.
     */
    function _remap($object_called)
    {
    	$controller_method = $object_called.'_'.$this->method;
		
		if(method_exists($this, $controller_method))
		{
			$this->$controller_method();
		}
		
		else
		{
			show_404();
		}
    }
    
    /* 
     * response
     * 
     * Takes pure data and optionally a status code, then creates the response
     */
    function response($data = '', $http_code = 200)
    {
   		if(empty($data))
    	{
    		$this->output->set_status_header(404);
    		return;
    	}
	    
    	$this->output->set_status_header($http_code);
        
        // If the format method exists, call and return the output in that format
        if(method_exists($this, '_'.$this->format))
        {
	    	// Set a XML header
	    	$this->output->set_header('Content-type: '.$this->supported_formats[$this->format]);
    	
        	$formatted_data = $this->{'_'.$this->format}($data);
        	$this->output->set_output( $formatted_data );
        }
        
        // Format not supported, output directly
        else
		{
        	$this->output->set_output( $data );
        }
    }

    
    /* 
     * Detect format
     * 
     * Detect which format should be used to output the data
     */
    private function _detect_format()
    {
    	if(array_key_exists('format', $this->args) && array_key_exists($this->args['format'], $this->supported_formats))
    	{
    		return $this->args['format'];
    	}
    	
    	// If a HTTP_ACCEPT header is present...
	    if($this->input->server('HTTP_ACCEPT'))
	    {
	    	// Check to see if it matches a supported format
	    	foreach(array_keys($this->supported_formats) as $format)
	    	{
		    	if(strpos($this->input->server('HTTP_ACCEPT'), $format) !== FALSE)
		    	{
		    		return $format;
		    	}
	    	}
	    }
	    
	    // If it doesnt match any or no HTTP_ACCEPT header exists, uses the first (default) supported format
	    list($default)=array_keys($this->supported_formats);
	    return $default;
    }
    
    
    /* 
     * Detect method
     * 
     * Detect which method (POST, PUT, GET, DELETE) is being used
     */
    private function _detect_method()
    {
    	$method = strtolower($this->input->server('REQUEST_METHOD'));
    	if(in_array($method, array('get', 'delete', 'post', 'put')))
    	{
	    	return $method;
    	}

    	return 'get';
    }
    
    
    // INPUT FUNCTION --------------------------------------------------------------
    
    public function get($key)
    {
    	return array_key_exists($key, $this->get_args) ? $this->input->xss_clean( $this->get_args[$key] ) : $this->input->get($key) ;
    }
    
    public function post($key)
    {
    	return $this->input->post($key);
    }
    
    public function put($key)
    {
    	return array_key_exists($key, $this->put_args) ? $this->input->xss_clean( $this->put_args[$key] ) : FALSE ;
    }
    
    // SECURITY FUNCTIONS ---------------------------------------------------------
    
    private function _checkLogin($username = '', $password = NULL)
    {
		if(empty($username))
		{
			return FALSE;
		}
		
		$valid_logins =& $this->config->item('rest_valid_logins');
		
		if(!array_key_exists($username, $valid_logins))
		{
			return FALSE;
		}
		
		// If actually NULL (not empty string) then do not check it
		if($password !== NULL)
		{
			if($valid_logins[$username] != $password)
			{
				return FALSE;
			}
		}
		
		return TRUE;
    }
    
    private function _prepareBasicAuth()
    {
    	$username = NULL;
    	$password = NULL;
    	
    	// mod_php
		if (isset($_SERVER['PHP_AUTH_USER'])) 
		{
		    $username = $_SERVER['PHP_AUTH_USER'];
		    $password = $_SERVER['PHP_AUTH_PW'];
		}
		
		// most other servers
		elseif (isset($_SERVER['HTTP_AUTHENTICATION']))
		{
			if (strpos(strtolower($_SERVER['HTTP_AUTHENTICATION']),'basic')===0)
			{
				list($username,$password) = explode(':',base64_decode(substr($_SERVER['HTTP_AUTHORIZATION'], 6)));
			}  
		}
		
		if ( !$this->_checkLogin($username, $password) )
		{
		    $this->_forceLogin();
		}
		
    }
    
    private function _prepareDigestAuth()
    {
    	$uniqid = uniqid(""); // Empty argument for backward compatibility
	   
	    // We need to test which server authentication variable to use
	    // because the PHP ISAPI module in IIS acts different from CGI
	    if(isset($_SERVER['PHP_AUTH_DIGEST']))
	    {
	        $digest_string = $_SERVER['PHP_AUTH_DIGEST'];
	    }
	    elseif(isset($_SERVER['HTTP_AUTHORIZATION']))
	    {
	        $digest_string = $_SERVER['HTTP_AUTHORIZATION'];
	    }
	    else
	    {
	    	$digest_string = "";
	    }
	    
	    /* The $_SESSION['error_prompted'] variabile is used to ask
	       the password again if none given or if the user enters
	       a wrong auth. informations. */
	    if ( empty($digest_string) )
	    {
	        $this->_forceLogin($uniqid);
	    }

	    // We need to retrieve authentication informations from the $auth_data variable
		preg_match_all('@(username|nonce|uri|nc|cnonce|qop|response)=[\'"]?([^\'",]+)@', $digest_string, $matches);
		$digest = array_combine($matches[1], $matches[2]); 

		if ( !array_key_exists('username', $digest) || !$this->_checkLogin($digest['username']) )
		{
			$this->_forceLogin($uniqid);
        }
		
		$valid_logins =& $this->config->item('rest_valid_logins');
		$valid_pass = $valid_logins[$digest['username']];
		
        // This is the valid response expected
		$A1 = md5($digest['username'] . ':' . $this->config->item('rest_realm') . ':' . $valid_pass);
		$A2 = md5(strtoupper($this->method).':'.$digest['uri']);
		$valid_response = md5($A1.':'.$digest['nonce'].':'.$digest['nc'].':'.$digest['cnonce'].':'.$digest['qop'].':'.$A2);
            
		if ($digest['response'] != $valid_response)
		{
            $this->response(NULL, 401);
            exit;
		}

    }
    
    
    private function _forceLogin($nonce = '')
    {
	    header('HTTP/1.0 401 Unauthorized');
	    header('HTTP/1.1 401 Unauthorized');
	    
    	if($this->config->item('rest_auth') == 'basic')
        {
        	header('WWW-Authenticate: Basic realm="'.$this->config->item('rest_realm').'"');
        }
        
        elseif($this->config->item('rest_auth') == 'digest')
        {
        	header('WWW-Authenticate: Digest realm="'.$this->config->item('rest_realm'). '" qop="auth" nonce="'.$nonce.'" opaque="'.md5($this->config->item('rest_realm')).'"');
        }
    	
	    echo 'Text to send if user hits Cancel button';
	    die();
    }
    
    // FORMATING FUNCTIONS ---------------------------------------------------------
    
    // Format XML for output
    private function _xml($data = array(), $structure = NULL, $basenode = 'xml')
    {
    	// turn off compatibility mode as simple xml throws a wobbly if you don't.
		if (ini_get('zend.ze1_compatibility_mode') == 1)
		{
			ini_set ('zend.ze1_compatibility_mode', 0);
		}

		if ($structure == NULL)
		{
			$structure = simplexml_load_string("<?xml version='1.0' encoding='utf-8'?><$basenode />");
		}

		// loop through the data passed in.
		foreach($data as $key => $value)
		{
			// no numeric keys in our xml please!
			if (is_numeric($key))
			{
				// make string key...
				//$key = "item_". (string) $key;
				$key = "item";
			}

			// replace anything not alpha numeric
			$key = preg_replace('/[^a-z0-9_-]/i', '', $key);

			// if there is another array found recrusively call this function
			if (is_array($value))
			{
				$node = $structure->addChild($key);
				// recrusive call.
				$this->_xml($value, $node, $basenode);
			}
			else
			{
				// add single node.

				$value = htmlentities($value, ENT_NOQUOTES, "UTF-8");

				$UsedKeys[] = $key;

				$structure->addChild($key, $value);
			}

		}
    	
		// pass back as string. or simple xml object if you want!
		return $structure->asXML();
    }
    
    // Format HTML for output
    private function _html($data = array())
    {
		// Multi-dimentional array
		if(isset($data[0]))
		{
			$headings = array_keys($data[0]);
		}
		
		// Single array
		else
		{
			$headings = array_keys($data);
			$data = array($data);
		}
		
		$this->load->library('table');
		
		$this->table->set_heading($headings);
		
		foreach($data as &$row)
		{
			$this->table->add_row($row);
		}
		
		return $this->table->generate();
    }
    
    // Format HTML for output
    private function _csv($data = array())
    {
    	// Multi-dimentional array
		if(isset($data[0]))
		{
			$headings = array_keys($data[0]);
		}
		
		// Single array
		else
		{
			$headings = array_keys($data);
			$data = array($data);
		}
		
		$output = implode(',', $headings)."\r\n";
		foreach($data as &$row)
		{
			$output .= '"'.implode('","',$row)."\"\r\n";
		}
		
		return $output;
    }
    
    // Encode as JSON
    private function _json($data = array())
    {
    	return json_encode($data);
    }
    
    // Encode as Serialized array
    private function _serialize($data = array())
    {
    	return serialize($data);
    }
    
    // Encode raw PHP
    private function _php($data = array())
    {
    	return var_export($data, TRUE);
    }
    
    
}
?>