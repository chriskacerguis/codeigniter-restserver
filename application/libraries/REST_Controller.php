<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class REST_Controller extends Controller
{
    // Set this in a controller to use a default format
    protected $rest_format = NULL;
    
    private $_method;
    private $_format;
    
    private $_get_args = array();
    private $_put_args = array();
    private $_delete_args = array();
    private $_args = array();
    
    // List all supported methods, the first will be the default format
    private $_supported_formats = array(
		'xml' 		=> 'application/xml',
		'rawxml' 	=> 'application/xml',
		'json' 		=> 'application/json',
		'serialize' => 'application/vnd.php.serialized',
		'php' 		=> 'text/plain',
		'html' 		=> 'text/html',
		'csv' 		=> 'application/csv'
	);
    
    // Constructor function
    function __construct()
    {
        parent::Controller();
        
	    // How is this request being made? POST, DELETE, GET, PUT?
	    $this->_method = $this->_detect_method();
	    
        // Lets grab the config and get ready to party
        $this->load->config('rest');
	    
        if($this->config->item('rest_auth') == 'basic')
        {
        	$this->_prepare_basic_auth();
        }
        
        elseif($this->config->item('rest_auth') == 'digest')
        {
        	$this->_prepare_digest_auth();
        }
        
        switch($this->_method)
        {
        	case 'put':
		    	// Set up out PUT variables
		    	parse_str(file_get_contents('php://input'), $this->_put_args);
    		break;
    		
        	case 'delete':
		    	// Set up out PUT variables
		    	parse_str(file_get_contents('php://input'), $this->_delete_args);
    		break;
        }
        
        // Set up our GET variables
    	$this->_get_args = $this->uri->ruri_to_assoc();
    	
    	// Merge both for one mega-args variable
    	$this->_args = array_merge($this->_get_args, $this->_put_args, $this->_delete_args);
    	
    	// Which format should the data be returned in?
	    $this->_format = $this->_detect_format();
    }
    
    /* 
     * Remap
     * 
     * Requests are not made to methods directly The request will be for an "object".
     * this simply maps the object and method to the correct Controller method.
     */
    function _remap($object_called)
    {
    	$controller_method = $object_called.'_'.$this->_method;
		
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
    function response($data = array(), $http_code = 200)
    {
   		if(empty($data))
    	{
    		$this->output->set_status_header(404);
    		return;
    	}
	    
    	$this->output->set_status_header($http_code);
        
        // If the format method exists, call and return the output in that format
        if(method_exists($this, '_format_'.$this->_format))
        {
	    	// Set the correct format header
	    	$this->output->set_header('Content-type: '.$this->_supported_formats[$this->_format]);
    	
        	$formatted_data = $this->{'_format_'.$this->_format}($data);
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
		$pattern = '/\.(' . implode( '|', array_keys($this->_supported_formats) ) . ')$/';

		// Check if a file extension is used
		if(preg_match($pattern, end($this->_get_args), $matches))
        {
			// The key of the last argument
			$last_key = end( array_keys($this->_get_args));

			// Remove the extension from arguments too
			$this->_get_args[$last_key] = preg_replace($pattern, '', $this->_get_args[$last_key]);
			$this->_args[$last_key] = preg_replace($pattern, '', $this->_args[$last_key]);

			return $matches[1];
        }
		
    	// A format has been passed as an argument in the URL and it is supported
    	if(isset($this->_args['format']) && isset($this->_supported_formats))
    	{
    		return $this->_args['format'];
    	}
    	
    	// Otherwise, check the HTTP_ACCEPT (if it exists and we are allowed)
	    if($this->config->item('rest_ignore_http_accept') === FALSE && $this->input->server('HTTP_ACCEPT'))
	    {
	    	// Check all formats against the HTTP_ACCEPT header
	    	foreach(array_keys($this->_supported_formats) as $format)
	    	{
		    	// Has this format been requested?
		    	if(strpos($this->input->server('HTTP_ACCEPT'), $format) !== FALSE)
		    	{
		    		// If not HTML or XML assume its right and send it on its way
		    		if($format != 'html' && $format != 'xml')
		    		{
		    			
		    			return $format;		    			
		    		}
		    		
		    		// HTML or XML have shown up as a match
		    		else
		    		{
		    			// If it is truely HTML, it wont want any XML
		    			if($format == 'html' && strpos($this->input->server('HTTP_ACCEPT'), 'xml') === FALSE)
		    			{
		    				return $format;
		    			}
		    			// If it is truely XML, it wont want any HTML
		    			elseif($format == 'xml' && strpos($this->input->server('HTTP_ACCEPT'), 'html') === FALSE)
		    			{
		    				return $format;
		    			}
		    		}
		    	}
	    	}
	    	
	    } // End HTTP_ACCEPT checking
	    	
		// Well, none of that has worked! Let's see if the controller has a default
		if($this->rest_format != NULL)
		{
			return $this->rest_format;
		}	    	

		// Just use whatever the first supported type is, nothing else is working!
		list($default)=array_keys($this->_supported_formats);
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
    
    public function get($key = NULL, $xss_clean = TRUE)
    {
		if($key === NULL)
		{
			return $this->_get_args;
		}
		
    	return array_key_exists($key, $this->_get_args) ? $this->_xss_clean( $this->_get_args[$key], $xss_clean ) : $this->input->get($key, $xss_clean) ;
    }
    
    public function post($key = NULL, $xss_clean = TRUE)
    {
		if($key === NULL)
		{
			return $this->_post_args;
		}
		
    	return $this->input->post($key = NULL, $xss_clean);
    }
    
    public function put($key = NULL, $xss_clean = TRUE)
    {
		if($key === NULL)
		{
			return $this->_put_args;
		}

    	return array_key_exists($key, $this->_put_args) ? $this->_xss_clean( $this->_put_args[$key], $xss_clean ) : FALSE ;
    }
    
    public function delete($key = NULL, $xss_clean = TRUE)
    {
		if($key === NULL)
		{
			return $this->_delete_args;
		}

    	return array_key_exists($key, $this->_delete_args) ? $this->_xss_clean( $this->_delete_args[$key], $xss_clean ) : FALSE ;
    }
    
    private function _xss_clean($val, $bool)
    {
    	return $bool ? $this->input->xss_clean($val) : $val;
    }
    
    // SECURITY FUNCTIONS ---------------------------------------------------------
    
    private function _check_login($username = '', $password = NULL)
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
    
    private function _prepare_basic_auth()
    {
    	$username = NULL;
    	$password = NULL;
    	
    	// mod_php
		if ($this->input->server('PHP_AUTH_USER')) 
		{
		    $username = $this->input->server('PHP_AUTH_USER');
		    $password = $this->input->server('PHP_AUTH_PW');
		}
		
		// most other servers
		elseif ( $this->input->server('HTTP_AUTHENTICATION') )
		{
			if (strpos(strtolower($this->input->server('HTTP_AUTHENTICATION')),'basic') === 0)
			{
				list($username,$password) = explode(':',base64_decode(substr($this->input->server('HTTP_AUTHORIZATION'), 6)));
			}  
		}
		
		if ( !$this->_check_login($username, $password) )
		{
		    $this->_force_login();
		}
		
    }
    
    private function _prepare_digest_auth()
    {
    	$uniqid = uniqid(""); // Empty argument for backward compatibility
	   
	    // We need to test which server authentication variable to use
	    // because the PHP ISAPI module in IIS acts different from CGI
	    if($this->input->server('PHP_AUTH_DIGEST'))
	    {
	        $digest_string = $this->input->server('PHP_AUTH_DIGEST');
	    }
	    
	    elseif($this->input->server('HTTP_AUTHORIZATION'))
	    {
	        $digest_string = $this->input->server('HTTP_AUTHORIZATION');
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
	        $this->_force_login($uniqid);
	    }

	    // We need to retrieve authentication informations from the $auth_data variable
		preg_match_all('@(username|nonce|uri|nc|cnonce|qop|response)=[\'"]?([^\'",]+)@', $digest_string, $matches);
		$digest = array_combine($matches[1], $matches[2]); 

		if ( !array_key_exists('username', $digest) || !$this->_check_login($digest['username']) )
		{
			$this->_force_login($uniqid);
        }
		
		$valid_logins =& $this->config->item('rest_valid_logins');
		$valid_pass = $valid_logins[$digest['username']];
		
        // This is the valid response expected
		$A1 = md5($digest['username'] . ':' . $this->config->item('rest_realm') . ':' . $valid_pass);
		$A2 = md5(strtoupper($this->_method).':'.$digest['uri']);
		$valid_response = md5($A1.':'.$digest['nonce'].':'.$digest['nc'].':'.$digest['cnonce'].':'.$digest['qop'].':'.$A2);
            
		if ($digest['response'] != $valid_response)
		{
	    	header('HTTP/1.0 401 Unauthorized');
	    	header('HTTP/1.1 401 Unauthorized');
            exit;
		}

    }
    
    
    private function _force_login($nonce = '')
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

    // Force it into an array
    private function _force_loopable($data)
    {
    	// Force it to be something useful
		if(!is_array($data) && !is_object($data))
		{
			$data = (array) $data;
		}
		
		return $data;
    }
    // FORMATING FUNCTIONS ---------------------------------------------------------
    
    // Format XML for output
    private function _format_xml($data = array(), $structure = NULL, $basenode = 'xml')
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
		$data = $this->_force_loopable($data);
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
			$key = preg_replace('/[^a-z]/i', '', $key);

			// if there is another array found recrusively call this function
			if (is_array($value) || is_object($value))
			{
				$node = $structure->addChild($key);
				// recrusive call.
				$this-> _format_xml($value, $node, $basenode);
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
    
    
    // Format Raw XML for output
    private function _format_rawxml($data = array(), $structure = NULL, $basenode = 'xml')
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
		$data = $this->_force_loopable($data);
		foreach( $data as $key => $value)
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
			if (is_array($value) || is_object($value))
			{
				$node = $structure->addChild($key);
				// recrusive call.
				$this->_format_rawxml($value, $node, $basenode);
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
    private function _format_html($data = array())
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
    private function _format_csv($data = array())
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
    private function _format_json($data = array())
    {
    	return json_encode($data);
    }
    
    // Encode as Serialized array
    private function _format_serialize($data = array())
    {
    	return serialize($data);
    }
    
    // Encode raw PHP
    private function _format_php($data = array())
    {
    	return var_export($data, TRUE);
    }
    
}
?>