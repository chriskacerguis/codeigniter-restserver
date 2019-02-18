<?php

defined('BASEPATH') OR exit('No direct script access allowed');

use Teapot\StatusCode;

/**
 * CodeIgniter Rest Controller
 * A fully RESTful server implementation for CodeIgniter using one library, one config file and one controller.
 *
 * @package         CodeIgniter REST Server
 * @author          Chris Kacerguis
 * @license         MIT
 * @link            https://github.com/chriskacerguis/codeigniter-restserver
 * @version         4.0.0
 */

class MY_Controller extends CI_Controller {

    /**
     * Contains details about the request
     * Fields: body, format, method, ssl
     * Note: This is a dynamic object (stdClass)
     *
     * @var object
     */
    protected $request = NULL;

    /**
     * Contains details about the response
     * Fields: format, lang
     * Note: This is a dynamic object (stdClass)
     *
     * @var object
     */
    protected $response = NULL;

   /**
     * The arguments for the GET request method
     *
     * @var array
     */
    protected $_get_args = [];

    /**
     * The arguments for the POST request method
     *
     * @var array
     */
    protected $_post_args = [];

    /**
     * The arguments for the PUT request method
     *
     * @var array
     */
    protected $_put_args = [];

    /**
     * The arguments for the DELETE request method
     *
     * @var array
     */
    protected $_delete_args = [];

    /**
     * The arguments for the PATCH request method
     *
     * @var array
     */
    protected $_patch_args = [];

    /**
     * The arguments for the HEAD request method
     *
     * @var array
     */
    protected $_head_args = [];

    /**
     * The arguments for the OPTIONS request method
     *
     * @var array
     */
    protected $_options_args = [];

    /**
     * The arguments for the query parameters
     *
     * @var array
     */
    protected $_query_args = [];

    /**
     * The arguments from GET, POST, PUT, DELETE, PATCH, HEAD and OPTIONS request methods combined
     *
     * @var array
     */
    protected $_args = [];

    /**
     * List all supported methods, the first will be the default format
     *
     * @var array
     */
    protected $_mime_types = [
        'json'  => 'application/json',
        'jsonp' => 'application/javascript',
        'xml'   => 'application/xml'
    ];

    /**
     * Constructor for the REST API
     *
     * @access public
     * @param string $config Configuration filename minus the file extension
     * e.g: my_rest.php is passed as 'my_rest'
     */
    public function __construct($config = 'rest')
    {
        parent::__construct();

        // Initialise the response, request and rest objects
        $this->request  = new stdClass();
        $this->response = new stdClass();
        $this->rest     = new stdClass();

        // Check whitelist
        $this->_check_whitelist();

        // Check blacklist
        $this->_check_blacklist();

        // Do auth
        $this->_check_auth();

        // what HTTP method are they calling
        $this->request->method = $this->input->method();

        // Not all methods have a body attached with them
        $this->request->body = NULL;

        // get header vars
        $this->_head_args = $this->input->request_headers();

        // Set up the GET variables
        $this->_get_args = array_merge($this->_get_args, $this->uri->ruri_to_assoc());

        $this->request->format = $this->_detect_input_format();

        // Parse things
        $this->{'_parse_' . $this->request->method}();

        // Merge both for one mega-args variable
        $this->_args = array_merge(
            $this->_get_args,
            $this->_options_args,
            $this->_patch_args,
            $this->_head_args,
            $this->_put_args,
            $this->_post_args,
            $this->_delete_args,
            $this->{'_' . $this->request->method.'_args'}
        );

    }

    /**
     * Checks if we are using the whitelist
     *
     * @access private
     */
    private function _check_whitelist() 
    {

    }

    /**
     * Checks if we are using the blacklists
     *
     * @access private
     */
    private function _check_blacklist() 
    {
        // Match an ip address in a blacklist e.g. 127.0.0.0, 0.0.0.0
        $pattern = sprintf('/(?:,\s*|^)\Q%s\E(?=,\s*|$)/m', $this->input->ip_address());
        // Returns 1, 0 or FALSE (on error only). Therefore implicitly convert 1 to TRUE
        if (preg_match($pattern, $this->config->item('rest_ip_blacklist')))
        {
            // Display an error response
            $this->response([
                'status' => FALSE,
                'msg' => $this->lang->line('text_rest_ip_denied')
            ], self::HTTP_UNAUTHORIZED);
        }
    }

    /**
     * Add the request to the log table
     *
     * @access protected
     * @param bool $authorized TRUE the user is authorized; otherwise, FALSE
     * @return bool TRUE the data was inserted; otherwise, FALSE
     */
    private function _check_auth() 
    {

    }

    /**
     * Add the request to the log table
     *
     * @access protected
     * @param bool $authorized TRUE the user is authorized; otherwise, FALSE
     * @return bool TRUE the data was inserted; otherwise, FALSE
     */
    private function _log_request()
    {

    }

    /**
     * Check to see if they have hit their hourly limit
     *
     * @access private
     * @param bool $authorized TRUE the user is authorized; otherwise, FALSE
     * @return bool TRUE the data was inserted; otherwise, FALSE
     */
    private function _check_limit()
    {

    }

    /**
     * Get the input format e.g. json or xml
     *
     * @access protected
     * @return string|NULL Supported input format; otherwise, NULL
     */
    protected function _detect_input_format()
    {
        // Get the CONTENT-TYPE value from the SERVER variable
        $content_type = $this->input->server('CONTENT_TYPE');

        if (empty($content_type) === FALSE)
        {
            // If a semi-colon exists in the string, then explode by ; and get the value of where
            // the current array pointer resides. This will generally be the first element of the array
            $content_type = (strpos($content_type, ';') !== FALSE ? current(explode(';', $content_type)) : $content_type);

            // Check all formats against the CONTENT-TYPE header
            foreach ($this->_mime_types as $type => $mime)
            {
                // $type = format e.g. csv
                // $mime = mime type e.g. application/csv

                // If both the mime types match, then return the format
                if ($content_type === $mime)
                {
                    return $type;
                }
            }
        }
        return NULL;
    }

    /**
     * Convert array to json
     *
     * @access private
     * @param bool $authorized TRUE the user is authorized; otherwise, FALSE
     * @return bool TRUE the data was inserted; otherwise, FALSE
     */
    private function _to_json($data)
    {
        return json_encode($data);
    }

    /**
     * Convert array to XML
     *
     * @access private
     * @param array $data 
     * @return string the xml data
     */
    private function _to_xml(array $data)
    {
        $xml    = new SimpleXMLElement('<response/>');
        $object = new SimpleXMLElement();
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $new_object = $object->addChild($key);
                $this->_to_xml($new_object, $value);
            } else {
                // if the key is an integer, it needs text with it to actually work.
                if ($key == (int) $key) {
                    $key = "key_$key";
                }
    
                $object->addChild($key, $value);
            }   
        }  
        return $xml->asXML();
    }

   /**
     * Requests are not made to methods directly, the request will be for
     * an "object". This simply maps the object and method to the correct
     * Controller method
     *
     * @access public
     * @param string $object_called
     * @param array $arguments The arguments passed to the controller method
     * @throws Exception
     */
    public function _remap($object_called, $arguments = [])
    {
        // Remove the supported format from the function name e.g. index.json => index
        $object_called = preg_replace('/^(.*)\.(?:'.implode('|', array_keys($this->_mime_types)).')$/', '$1', $object_called);

        $controller_method = $object_called.'_'.$this->request->method;
        // Does this method exist? If not, try executing an index method
        if (!method_exists($this, $controller_method)) {
            $controller_method = "index_" . $this->request->method;
            array_unshift($arguments, $object_called);
        }

        // TODO: need to call exception here
        call_user_func_array([$this, $controller_method], $arguments);
    }

    /**
     * Parse the GET request arguments
     *
     * @access protected
     * @return void
     */
    protected function _parse_get()
    {
        // Merge both the URI segments and query parameters
        $this->_get_args = array_merge($this->_get_args, $this->_query_args);
    }

    /**
     * Retrieve a value from a GET request
     *
     * @access public
     * @param NULL $key Key to retrieve from the GET request
     * If NULL an array of arguments is returned
     * @return array|string|NULL Value from the GET request; otherwise, NULL
     */
    public function get($key = NULL)
    {
        if ($key === NULL)
        {
            return $this->_get_args;
        }

        return isset($this->_get_args[$key]) ? $this->_xss_clean($this->_get_args[$key], $xss_clean) : NULL;
    }

    /**
     * Parse the POST request arguments
     *
     * @access protected
     * @return void
     */
    protected function _parse_post()
    {
        $this->_post_args = $_POST;

        if ($this->request->format)
        {
            $this->request->body = $this->input->raw_input_stream;
        }
    }

    /**
     * Retrieve a value from a POST request
     *
     * @access public
     * @param NULL $key Key to retrieve from the POST request
     * If NULL an array of arguments is returned
     * @return array|string|NULL Value from the POST request; otherwise, NULL
     */
    public function post($key = NULL)
    {
        if ($key === NULL)
        {
            return $this->_post_args;
        }

        if (isset($this->_post_args[$key]))
        {
            return $this->_post_args[$key];
        }

        return isset($this->_post_args[$key]) ? $this->_xss_clean($this->_post_args[$key], $xss_clean) : NULL;
    }

    /**
     * Parse the PUT request arguments
     *
     * @access protected
     * @return void
     */
    protected function _parse_put()
    {
        if ($this->request->format)
        {
            $this->request->body = $this->input->raw_input_stream;
            if ($this->request->format === 'json')
            {
                $this->_put_args = json_decode($this->input->raw_input_stream);
            }
        }
        else if ($this->input->method() === 'put')
        {
            // If no file type is provided, then there are probably just arguments
            $this->_put_args = $this->input->input_stream();
        }
    }

    /**
     * Retrieve a value from a PUT request
     *
     * @access public
     * @param NULL $key Key to retrieve from the PUT request
     * If NULL an array of arguments is returned
     * @param NULL $xss_clean Whether to apply XSS filtering
     * @return array|string|NULL Value from the PUT request; otherwise, NULL
     */
    public function put($key = NULL)
    {
        if ($key === NULL)
        {
            return $this->_put_args;
        }

        if (isset($this->_put_args[$key]))
        {
            return $this->_put_args[$key];
        }

        return isset($this->_put_args[$key]) ? $this->_xss_clean($this->_put_args[$key], $xss_clean) : NULL;
    }

    /**
     * Sanitizes data so that Cross Site Scripting Hacks can be
     * prevented
     *
     * @access protected
     * @param string $value Input data
     * @param bool $xss_clean Whether to apply XSS filtering
     * @return string
     */
    protected function _xss_clean($value, $xss_clean)
    {
        is_bool($xss_clean) || $xss_clean = $this->_enable_xss;

        return $xss_clean === TRUE ? $this->security->xss_clean($value) : $value;
    }

    /**
     * Takes mixed data and optionally a status code, then creates the response
     *
     * @access public
     * @param array|NULL $data Data to output to the user
     * @param int|NULL $http_code HTTP status code
     * @param bool $continue TRUE to flush the response to the client and continue
     * running the script; otherwise, exit
     */
    public function response($data = NULL, $http_code = NULL, $continue = FALSE)
    {
        ob_start();

        // If the HTTP status is not NULL, then cast as an integer
        if ($http_code !== NULL)
        {
            $http_code = (int) $http_code;
        }

        $output = NULL;

        // If data is NULL and no HTTP status code provided, then display, error and exit
        if ($data === NULL && $http_code === NULL)
        {
            $http_code = StatusCode::NOT_FOUND;
        }

        elseif ($data !== NULL) {
            // Set out output to the correct format.
            $this->output->set_content_type($this->config->item('rest_format'), strtolower($this->config->item('charset')));

            // If an array or object, then parse as a json, so as to be a 'string'
            if (is_array($data) || is_object($data))
            {
                $data = json_encode($data);
            }

            // Format is not supported, so output the raw data as a string
            $output = $data;
        }

        else {
            die('oops');
        }

        	// If not greater than zero, then set the HTTP status code as 200 by default
        	// Though perhaps 500 should be set instead, for the developer not passing a
        	// correct HTTP status code
        	$http_code > 0 || $http_code = StatusCode::OK;

            $this->output->set_status_header($http_code);
            
            $this->output->set_output($output);

            $this->output->_display();
            exit;
    }    
}