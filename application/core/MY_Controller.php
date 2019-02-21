<?php

defined('BASEPATH') OR exit('No direct script access allowed');

use Teapot\StatusCode;
use Spatie\ArrayToXml\ArrayToXml;
use Whitelist\Check;

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

        // Load our config
        $this->load->config($config);

        // Initialise the response, request and rest objects
        $this->request  = new stdClass();
        $this->response = new stdClass();
        $this->rest     = new stdClass();

        // Check if the IP is on our blacklist or whitelist
        $this->_check_ip();

        // Get the query
        $this->_parse_query();

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
     * Checks if we are using the blacklists or
     * whitelists to block IPs
     *
     * @access protected
     */
    protected function _check_ip()
    {
        $checker = new Whitelist\Check();

        // Check whitelist
        if ($this->config->item('rest_ip_whitelist') !== FALSE)
        {
            $checker->whitelist($this->config->item('rest_ip_whitelist'));
            if (!$checker->check($this->input->ip_address())) {
                $this->response(['status' => 'error', 'msg' => $this->lang->line('text_rest_ip_denied')], StatusCode::UNAUTHORIZED);
            }
        }

        // Check blacklist
        if ($this->config->item('rest_ip_blacklist') !== FALSE)
        {
            $checker->whitelist($this->config->item('rest_ip_whitelist'));
            if ($checker->check($this->input->ip_address())) {
                $this->response(['status' => 'error', 'msg' => $this->lang->line('text_rest_ip_denied')], StatusCode::UNAUTHORIZED);
            }
        }
 
    }



    /**
     * Add the request to the log table
     *
     * @access protected
     * @param bool $authorized TRUE the user is authorized; otherwise, FALSE
     * @return bool TRUE the data was inserted; otherwise, FALSE
     */
    protected function _check_auth() 
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
     * Parse the query parameters
     *
     * @access protected
     * @return void
     */
    protected function _parse_query()
    {
        $this->_query_args = $this->input->get();
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

        return isset($this->_get_args[$key]) ? $this->_xss_clean($this->_get_args[$key]) : NULL;
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

        $this->request->body = $this->input->raw_input_stream;
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

        return isset($this->_post_args[$key]) ? $this->_xss_clean($this->_post_args[$key]) : NULL;
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

        return isset($this->_put_args[$key]) ? $this->_xss_clean($this->_put_args[$key]) : NULL;
    }

    /**
     * Parse the OPTIONS request arguments
     *
     * @access protected
     * @return void
     */
    protected function _parse_options()
    {

        // Is this needed?

        // Parse the OPTIONS variables
        parse_str(parse_url($this->input->server('REQUEST_URI'), PHP_URL_QUERY), $options);

        // Merge both the URI segments and OPTIONS params
        $this->_options_args = array_merge($this->_options_args, $options);
    }

    /**
     * Sends all server options.
     *
     * @access public
     * @return NULL
     */
    public function options()
    {
        $headers = implode(',', $this->config->item('allowed_cors_headers'));
        $this->output->set_header("Access-Control-Expose-Headers: {$headers}");
        
        $methods = implode(',', $this->config->item('allowed_cors_methods'));
        $this->output->set_header("Access-Control-Allow-Methods: {$methods}");

        if ($this->config->item('allowed_cors_origins') !== TRUE) {
            $domains = implode(',', $this->config->item('allowed_cors_origins'));
            $this->output->set_header("Access-Control-Allow-Origin: {$domains}");
        }
        else
        {
            $this->output->set_header("Access-Control-Allow-Origin: *");
        }
    }

    /**
     * Parse the PATCH request arguments
     *
     * @access protected
     * @return void
     */
    protected function _parse_patch()
    {
        // It might be a HTTP body
        if ($this->request->format)
        {
            $this->request->body = $this->input->raw_input_stream;
        }
        else if ($this->input->method() === 'patch')
        {
            // If no file type is provided, then there are probably just arguments
            $this->_patch_args = $this->input->input_stream();
        }
    }

    /**
     * Retrieve a value from a PATCH request
     *
     * @access public
     * @param NULL $key Key to retrieve from the PATCH request
     * If NULL an array of arguments is returned
     * @param NULL $xss_clean Whether to apply XSS filtering
     * @return array|string|NULL Value from the PATCH request; otherwise, NULL
     */
    public function patch($key = NULL, $xss_clean = NULL)
    {
        if ($key === NULL)
        {
            return $this->_patch_args;
        }
        return isset($this->_patch_args[$key]) ? $this->_xss_clean($this->_patch_args[$key]) : NULL;
    }

    /**
     * Parse the HEAD request arguments
     *
     * @access protected
     * @return void
     */
    protected function _parse_head()
    {
        // Parse the HEAD variables
        parse_str(parse_url($this->input->server('REQUEST_URI'), PHP_URL_QUERY), $head);

        // Merge both the URI segments and HEAD params
        $this->_head_args = array_merge($this->_head_args, $head);
    }

    /**
     * Retrieve a value from a HEAD request
     *
     * @access public
     * @param NULL $key Key to retrieve from the HEAD request
     * If NULL an array of arguments is returned
     * @param NULL $xss_clean Whether to apply XSS filtering
     * @return array|string|NULL Value from the HEAD request; otherwise, NULL
     */
    public function head($key = NULL, $xss_clean = NULL)
    {
        if ($key === NULL)
        {
            return $this->_head_args;
        }
        return isset($this->_head_args[$key]) ? $this->_xss_clean($this->_head_args[$key]) : NULL;
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
    protected function _xss_clean($value)
    {
        if ($this->config->item('rest_xss_clean') === TRUE) {
            return $this->security->xss_clean($value);
        }

        return $value;
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

        else if ($data !== NULL) {
            // Set out output to the correct format.
            $this->output->set_content_type($this->config->item('rest_format'), strtolower($this->config->item('charset')));

            // Send JSON data
            if ($this->config->item('rest_format') == 'json') 
            {
                if (is_array($data) || is_object($data))
                {
                    $output = json_encode($data);
                }
                else 
                {
                    $output = json_encode([0 => $data]);
                }
                
            }

            // Send XML data
            if ($this->config->item('rest_format') == 'xml') 
            {
                // See: https://github.com/spatie/array-to-xml/issues/88
                $wrapped_data['element'] = $data;
                $output = ArrayToXml::convert($wrapped_data);
            }

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