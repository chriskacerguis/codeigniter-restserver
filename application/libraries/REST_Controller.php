<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * CodeIgniter Rest Controller
 *
 * A fully RESTful server implementation for CodeIgniter using one library, one config file and one controller.
 *
 * @package        	CodeIgniter
 * @subpackage    	Libraries
 * @category    	Libraries
 * @author        	Phil Sturgeon, Chris Kacerguis
 * @license         MIT
 * @link			https://github.com/chriskacerguis/codeigniter-restserver
 * @version         3.0.0-pre
 */
abstract class REST_Controller extends CI_Controller
{
    /**
     * This defines the rest format.
     *
     * Must be overridden it in a controller so that it is set.
     *
     * @var string|NULL
     */
    protected $rest_format          = NULL;

    /**
     * Defines the list of method properties such as limit, log and level
     *
     * @var array
     */
    protected $methods              = [];

    /**
     * List of allowed HTTP methods
     *
     * @var array
     */
    protected $allowed_http_methods = ['get', 'delete', 'post', 'put', 'options', 'patch', 'head'];

    /**
     * General request data and information.
     * Stores accept, language, body, headers, etc.
     *
     * @var object
     */
    protected $request              = NULL;

    /**
     * What is gonna happen in output?
     *
     * @var object
     */
    protected $response             = NULL;

    /**
     * Stores DB, keys, key level, etc
     *
     * @var object
     */
    protected $rest                 = NULL;

    /**
     * The arguments for the GET request method
     *
     * @var array
     */
    protected $_get_args            = [];

    /**
     * The arguments for the POST request method
     *
     * @var array
     */
    protected $_post_args           = [];

    /**
     * The insert_id of the log entry (if we have one)
     *
     * @var string
    */
    protected $_insert_id           = '';

    /**
     * The arguments for the PUT request method
     *
     * @var array
     */
    protected $_put_args            = [];

    /**
     * The arguments for the DELETE request method
     *
     * @var array
     */
    protected $_delete_args         = [];

    /**
     * The arguments for the PATCH request method
     *
     * @var array
     */
    protected $_patch_args          = [];

    /**
     * The arguments for the HEAD request method
     *
     * @var array
     */
    protected $_head_args           = [];

    /**
     * The arguments for the OPTIONS request method
     *
     * @var array
     */
    protected $_options_args        = [];

    /**
     * The arguments from GET, POST, PUT, DELETE request methods combined.
     *
     * @var array
     */
    protected $_args                = [];

    /**
     * If the request is allowed based on the API key provided.
     *
     * @var boolean
     */
    protected $_allow               = TRUE;

    /**
     * Determines if output compression is enabled
     *
     * @var boolean
     */
    protected $_zlib_oc             = FALSE;

    /**
     * The LDAP Distinguished Name of the User post authentication
     *
     * @var string
    */
    protected $_user_ldap_dn        = '';

    /**
     * The start of the response time from the server
     *
     * @var string
    */
    protected $_start_rtime         = '';

    /**
     * The end of the response time from the server
     *
     * @var string
    */
    protected $_end_rtime           = '';

    /**
     * List all supported methods, the first will be the default format
     *
     * @var array
     */
    protected $_supported_formats   = [
        'xml'           => 'application/xml',
        'json'          => 'application/json',
        'jsonp'         => 'application/javascript',
        'serialized'    => 'application/vnd.php.serialized',
        'php'           => 'text/plain',
        'html'          => 'text/html',
        'csv'           => 'application/csv'
    ];

    /**
     * Information about the current API user
     *
     * @var object
     */
    protected $_apiuser;

    /**
     * Developers can extend this class and add a check in here.
     */
    protected function early_checks()
    {

    }

    /**
     * Constructor function
     * @todo   Document more please.
     * @access public
     */
    public function __construct($config = 'rest')
    {
        parent::__construct();

        // Start the timer for how long the request takes
        $this->_start_rtime = microtime(TRUE);

        // Lets grab the config and get ready to party
        $this->load->config($config);

        // This library is bundled with REST_Controller 2.5+, but will eventually be part of CodeIgniter itself
        $this->load->library('format');

        // init objects
        $this->response     = new stdClass();
        $this->rest         = new stdClass();

        $this->_zlib_oc     = @ini_get('zlib.output_compression');

        // let's learn about the request
        $this->request      = new stdClass();

        // Check to see if this IP is Blacklisted
        if ($this->config->item('rest_ip_blacklist_enabled')) {
            $this->_check_blacklist_auth();
        }

        // Is it over SSL?
        $this->request->ssl     = $this->_detect_ssl();

        // How is this request being made? POST, DELETE, GET, PUT?
        $this->request->method  = $this->_detect_method();

        // Create argument container, if nonexistent
        if (!isset($this->{'_'.$this->request->method.'_args'})) {
            $this->{'_'.$this->request->method.'_args'} = [];
        }

        // Set up our GET variables
        $this->_get_args        = array_merge($this->_get_args, $this->uri->ruri_to_assoc());

        // Try to find a format for the request (means we have a request body)
        $this->request->format  = $this->_detect_input_format();

        // Some Methods cant have a body
        $this->request->body    = NULL;

        $this->{'_parse_' . $this->request->method}();

        // Now we know all about our request, let's try and parse the body if it exists
        if ($this->request->format && $this->request->body) {
            $this->request->body = $this->format->factory($this->request->body, $this->request->format)->to_array();
            // Assign payload arguments to proper method container
            $this->{'_'.$this->request->method.'_args'} = $this->request->body;
        }

        // Merge both for one mega-args variable
        $this->_args = array_merge($this->_get_args,
            $this->_options_args,
            $this->_patch_args,
            $this->_head_args ,
            $this->_put_args,
            $this->_post_args,
            $this->_delete_args,
            $this->{'_'.$this->request->method.'_args'}
        );

        // Which format should the data be returned in?
        $this->response         = new stdClass();
        $this->response->format = $this->_detect_output_format();

        // Which format should the data be returned in?
        $this->response->lang   = $this->_detect_lang();

        // Developers can extend this class and add a check in here
        $this->early_checks();

        $this->rest             = new StdClass();

        // Load DB if its enabled
        if (config_item('rest_database_group') && (config_item('rest_enable_keys') || config_item('rest_enable_logging'))) {
            $this->rest->db     = $this->load->database(config_item('rest_database_group'), TRUE);
        }

        // Use whatever database is in use (isset returns FALSE)
        elseif (property_exists($this, "db")) {
            $this->rest->db     = $this->db;
        }

        // Check if there is a specific auth type for the current class/method
        // _auth_override_check could exit so we need $this->rest->db initialized before
        $this->auth_override    = $this->_auth_override_check();

        // Checking for keys? GET TO WorK!
	      // Skip keys test for $config['auth_override_class_method']['class'['method'] = 'none'
        if (config_item('rest_enable_keys') && $this->auth_override !== TRUE) {
            $this->_allow = $this->_detect_api_key();
        }

        // only allow ajax requests
        if (!$this->input->is_ajax_request() && config_item('rest_ajax_only')) {
            $response = [config_item('rest_status_field_name') => FALSE, config_item('rest_message_field_name') => 'Only AJAX requests are accepted.'];
            $this->response($response, 406); // Set status to 406 NOT ACCEPTABLE
        }

        // When there is no specific override for the current class/method, use the default auth value set in the config
        if ($this->auth_override !== TRUE && !(config_item('rest_enable_keys') && $this->_allow === TRUE)) {
            $rest_auth = strtolower($this->config->item('rest_auth'));
            switch ($rest_auth) {
                case 'basic':
                    $this->_prepare_basic_auth();
                    break;
                case 'digest':
                    $this->_prepare_digest_auth();
                    break;
                case 'session':
                    $this->_check_php_session();
                    break;
            }
            if ($this->config->item('rest_ip_whitelist_enabled')) {
                $this->_check_whitelist_auth();
            }
        }
    }

    /**
     * Destructor function
     * @author Chris Kacerguis
     *
     * @access public
     */
    public function __destruct()
    {
        // Record the "stop" time of the request
        $this->_end_rtime = microtime(TRUE);
        // CK: if, we are logging, log the access time here, as we are done!
        if (config_item('rest_enable_logging')) {
            $this->_log_access_time();
        }

    }

    /**
     * Remap
     *
     * Requests are not made to methods directly, the request will be for
     * an "object". This simply maps the object and method to the correct
     * Controller method.
     *
     * @access public
     * @param  string $object_called
     * @param  array  $arguments     The arguments passed to the controller method.
     */
    public function _remap($object_called, $arguments)
    {
        // Should we answer if not over SSL?
        if (config_item('force_https') && !$this->_detect_ssl()) {
            $this->response([config_item('rest_status_field_name') => FALSE, config_item('rest_message_field_name') => 'Unsupported protocol'], 403);
        }

        $pattern = '/^(.*)\.('.implode('|', array_keys($this->_supported_formats)).')$/';
        $matches = [];
        if (preg_match($pattern, $object_called, $matches)) {
            $object_called = $matches[1];
        }

        $controller_method = $object_called.'_'.$this->request->method;

        // Do we want to log this method (if allowed by config)?
        $log_method = !(isset($this->methods[$controller_method]['log']) && $this->methods[$controller_method]['log'] == FALSE);

        // Use keys for this method?
        $use_key = !(isset($this->methods[$controller_method]['key']) && $this->methods[$controller_method]['key'] == FALSE);

        // They provided a key, but it wasn't valid, so get them out of here.
        if (config_item('rest_enable_keys') && $use_key && $this->_allow === FALSE) {
            if (config_item('rest_enable_logging') && $log_method) {
                $this->_log_request();
            }

            $this->response([config_item('rest_status_field_name') => FALSE, config_item('rest_message_field_name') => 'Invalid API Key '.$this->rest->key], 403);
        }

        // Check to see if this key has access to the requested controller.
        if (config_item('rest_enable_keys') && $use_key && !empty($this->rest->key) && !$this->_check_access()) {
            if (config_item('rest_enable_logging') && $log_method) {
                $this->_log_request();
            }

            $this->response([config_item('rest_status_field_name') => FALSE, config_item('rest_message_field_name') => 'This API key does not have access to the requested controller.'], 401);
        }

        // Sure it exists, but can they do anything with it?
        if ( ! method_exists($this, $controller_method)) {
            $this->response([config_item('rest_status_field_name') => FALSE, config_item('rest_message_field_name') => 'Unknown method.'], 404);
        }

        // Doing key related stuff? Can only do it if they have a key right?
        if (config_item('rest_enable_keys') && !empty($this->rest->key)) {
            // Check the limit
            if (config_item('rest_enable_limits') && !$this->_check_limit($controller_method)) {
                $response = [config_item('rest_status_field_name') => FALSE, config_item('rest_message_field_name') => 'This API key has reached the hourly limit for this method.'];
                $this->response($response, 401);
            }

            // If no level is set use 0, they probably aren't using permissions
            $level = isset($this->methods[$controller_method]['level']) ? $this->methods[$controller_method]['level'] : 0;

            // If no level is set, or it is lower than/equal to the key's level
            $authorized = $level <= $this->rest->level;

            // IM TELLIN!
            if (config_item('rest_enable_logging') && $log_method) {
                $this->_log_request($authorized);
            }

            // They don't have good enough perms
            $response = [config_item('rest_status_field_name') => FALSE, config_item('rest_message_field_name') => 'This API key does not have enough permissions.'];
            $authorized || $this->response($response, 401);
        }

        // No key stuff, but record that stuff is happening
        elseif (config_item('rest_enable_logging') && $log_method) {
            $this->_log_request($authorized = TRUE);
        }

        // and...... GO!
        try {
            $this->_fire_method([$this, $controller_method], $arguments);
        }
        catch(Exception $ex) {
            $this->_server_error_response($ex);
        }

        // should not get here.
    }

    /**
     * Fire Method
     *
     * Fires the designated controller method with the given arguments.
     *
     * @access protected
     * @param  array $method The controller method to fire
     * @param  array $args   The arguments to pass to the controller method
     */
    protected function _fire_method($method, $args)
    {
        call_user_func_array($method, $args);
    }

    /**
     * Response
     *
     * Takes pure data and optionally a status code, then creates the response.
     * Set $continue to TRUE to flush the response to the client and continue running the script.
     *
     * @access public
     * @param  array    $data
     * @param  NULL|int $http_code
     * @param  bool $continue
     */
    public function response($data = NULL, $http_code = NULL, $continue = FALSE)
    {
        // If data is NULL and not code provide, error and bail
        if ($data === NULL && $http_code === NULL) {
            $http_code = 404;

            // create the output variable here in the case of $this->response(array());
            $output = NULL;
        }

        // If data is NULL but http code provided, keep the output empty
        elseif ($data === NULL && is_numeric($http_code)) {
            $output = NULL;
        }

        // Otherwise (if no data but 200 provided) or some data, carry on camping!
        else {
            // Is compression requested?
            if ($this->config->item('compress_output') === TRUE && $this->_zlib_oc == FALSE) {
                if (extension_loaded('zlib')) {
                    if (isset($_SERVER['HTTP_ACCEPT_ENCODING']) && strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== FALSE) {
                        ob_start('ob_gzhandler');
                    }
                }
            }

            is_numeric($http_code) || $http_code = 200;

            // @deprecated the following statement can be deleted.
            // If the format method exists, call and return the output in that format
            if (method_exists($this, '_format_'.$this->response->format)) {
                // Set the correct format header
                header('Content-Type: '.$this->_supported_formats[$this->response->format] . '; charset=' . strtolower($this->config->item('charset')));

                $output = $this->{'_format_'.$this->response->format}($data);
            }

            // If the format method exists, call and return the output in that format
            elseif (method_exists($this->format, 'to_'.$this->response->format)) {
                // Set the correct format header
                header('Content-Type: '.$this->_supported_formats[$this->response->format] . '; charset=' . strtolower($this->config->item('charset')));

                $output = $this->format->factory($data)->{'to_'.$this->response->format}();
            }

            // Format not supported, output directly
            else {
                $output = $data;
            }
        }

        set_status_header($http_code);

        // JC: Log response code only if rest logging enabled
        if (config_item('rest_enable_logging')) {
            $this->_log_response_code($http_code);
        }

        // If zlib.output_compression is enabled it will compress the output,
        // but it will not modify the content-length header to compensate for
        // the reduction, causing the browser to hang waiting for more data.
        // We'll just skip content-length in those cases.
        if ( ! $this->_zlib_oc && ! $this->config->item('compress_output')) {
            header('Content-Length: ' . strlen($output));
        }

        if($continue){
            echo($output);
            ob_end_flush();
            ob_flush();
            flush();
        }else{
            exit($output);
        }
    }

    /**
     * Detect SSL use
     *
     * Detect whether SSL is being used or not.
     *
     * @access protected
     */
    protected function _detect_ssl()
    {
    	    // $_SERVER['HTTPS'] (http://php.net/manual/en/reserved.variables.server.php)
    	    // Set to a non-empty value if the script was queried through the HTTPS protocol
            return (isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']));
    }

    /**
     * Return server response
     *
     * Method to send a response to the client in the event of a server error.
     *
     * @access public
     * @param  object $ex
     */
    protected function _server_error_response($ex)
    {
        $response = [
                config_item('rest_status_field_name') => FALSE,
                config_item('rest_message_field_name') => [
                        'classname' => get_class($ex), 'message' => $ex->getMessage()
                ]
        ];
        $this->response($response, 500);
    }

    /**
     * Detect input format
     *
     * Detect which format the HTTP Body is provided in
     *
     * @access protected
     */
    protected function _detect_input_format()
    {
        if ($this->input->server('CONTENT_TYPE')) {
            // Check all formats against the HTTP_ACCEPT header
            foreach ($this->_supported_formats as $format => $mime) {
                if (strpos($match = $this->input->server('CONTENT_TYPE'), ';')) {
                    $match = current(explode(';', $match));
                }

                if ($match == $mime) {
                    return $format;
                }
            }
        }

        return NULL;
    }

    /**
     * Detect format
     *
     * Detect which format should be used to output the data.
     *
     * @access protected
     * @return string The output format.
     */
    protected function _detect_output_format()
    {
        $pattern = '/\.('.implode('|', array_keys($this->_supported_formats)).')$/';

        // Check if a file extension is used when no get arguments provided
        $matches = [];
        if (!$this->_get_args && preg_match($pattern, $this->uri->uri_string(), $matches)) {
            return $matches[1];
        }

        // Check if a file extension is used
        elseif ($this->_get_args && !is_array(end($this->_get_args)) && preg_match($pattern, end($this->_get_args), $matches)) {
        //elseif ($this->_get_args and !is_array(end($this->_get_args)) and preg_match($pattern, end(array_keys($this->_get_args)), $matches)) {
            // The key of the last argument
            $arg_keys = array_keys($this->_get_args);
            $last_key = end($arg_keys);

            // Remove the extension from arguments too
            $this->_get_args[$last_key] = preg_replace($pattern, '', $this->_get_args[$last_key]);
            $this->_args[$last_key]     = preg_replace($pattern, '', $this->_args[$last_key]);

            return $matches[1];
        }

        // A format has been passed as an argument in the URL and it is supported
        if (isset($this->_get_args['format']) && array_key_exists($this->_get_args['format'], $this->_supported_formats)) {
            return $this->_get_args['format'];
        }

        // Otherwise, check the HTTP_ACCEPT (if it exists and we are allowed)
        if ($this->config->item('rest_ignore_http_accept') === FALSE && $this->input->server('HTTP_ACCEPT')) {
            // Check all formats against the HTTP_ACCEPT header
            foreach (array_keys($this->_supported_formats) as $format) {
                // Has this format been requested?
                if (strpos($this->input->server('HTTP_ACCEPT'), $format) !== FALSE) {
                    // If not HTML or XML assume its right and send it on its way
                    if ($format != 'html' && $format != 'xml') {
                        return $format;
                    }

                    // HTML or XML have shown up as a match
                    else {
                        // If it is truly HTML, it wont want any XML
                        if ($format == 'html' && strpos($this->input->server('HTTP_ACCEPT'), 'xml') === FALSE) {
                            return $format;
                        }

                        // If it is truly XML, it wont want any HTML
                        elseif ($format == 'xml' && strpos($this->input->server('HTTP_ACCEPT'), 'html') === FALSE) {
                            return $format;
                        }
                    }
                }
            }
        } // End HTTP_ACCEPT checking

        // Well, none of that has worked! Let's see if the controller has a default
        if ( ! empty($this->rest_format)) {
            return $this->rest_format;
        }

        // Just use the default format
        return config_item('rest_default_format');
    }

    /**
     * Detect method
     *
     * Detect which HTTP method is being used
     *
     * @access protected
     * @return string
     */
    protected function _detect_method()
    {
        $method = strtolower($this->input->server('REQUEST_METHOD'));

        if ($this->config->item('enable_emulate_request')) {
            if ($this->input->post('_method')) {
                $method = strtolower($this->input->post('_method'));
            } elseif ($this->input->server('HTTP_X_HTTP_METHOD_OVERRIDE')) {
                $method = strtolower($this->input->server('HTTP_X_HTTP_METHOD_OVERRIDE'));
            }
        }

        if (in_array($method, $this->allowed_http_methods) && method_exists($this, '_parse_' . $method)) {
            return $method;
        }

        return 'get';
    }

    /**
     * Detect API Key
     *
     * See if the user has provided an API key
     *
     * @access protected
     * @return boolean
     */
    protected function _detect_api_key()
    {
        // Get the api key name variable set in the rest config file
        $api_key_variable = config_item('rest_key_name');

        // Work out the name of the SERVER entry based on config
        $key_name = 'HTTP_'.strtoupper(str_replace('-', '_', $api_key_variable));

        $this->rest->key = NULL;
        $this->rest->level = NULL;
        $this->rest->user_id = NULL;
        $this->rest->ignore_limits = FALSE;

        // Find the key from server or arguments
        if (($key = isset($this->_args[$api_key_variable]) ? $this->_args[$api_key_variable] : $this->input->server($key_name))) {
            if ( ! ($row = $this->rest->db->where(config_item('rest_key_column'), $key)->get(config_item('rest_keys_table'))->row())) {
                return FALSE;
            }

            $this->rest->key = $row->{config_item('rest_key_column')};

            isset($row->user_id) && $this->rest->user_id = $row->user_id;
            isset($row->level) && $this->rest->level = $row->level;
            isset($row->ignore_limits) && $this->rest->ignore_limits = $row->ignore_limits;

            $this->_apiuser =  $row;

            /*
             * If "is private key" is enabled, compare the ip address with the list
             * of valid ip addresses stored in the database.
             */
            if (!empty($row->is_private_key)) {
                // Check for a list of valid ip addresses
                if (isset($row->ip_addresses)) {
                    // multiple ip addresses must be separated using a comma, explode and loop
                    $list_ip_addresses = explode(",", $row->ip_addresses);
                    $found_address = FALSE;

                    foreach ($list_ip_addresses as $ip_address) {
                        if ($this->input->ip_address() == trim($ip_address)) {
                            // there is a match, set the the value to TRUE and break out of the loop
                            $found_address = TRUE;
                            break;
                        }
                    }

                    return $found_address;
                } else {
                    // There should be at least one IP address for this private key.
                    return FALSE;
                }
            }

            return $row;
        }

        // No key has been sent
        return FALSE;
    }

    /**
     * Detect language(s)
     *
     * What language do they want it in?
     *
     * @access protected
     * @return NULL|string The language code.
     */
    protected function _detect_lang()
    {
        if ( ! $lang = $this->input->server('HTTP_ACCEPT_LANGUAGE')) {
            return NULL;
        }

        // They might have sent a few, make it an array
        if (strpos($lang, ',') !== FALSE) {
            $langs = explode(',', $lang);

            $return_langs = [];
            foreach ($langs as $lang) {
                // Remove weight and strip space
                list($lang) = explode(';', $lang);
                $return_langs[] = trim($lang);
            }

            return $return_langs;
        }

        // Nope, just return the string
        return $lang;
    }

    /**
     * Log request
     *
     * Record the entry for awesomeness purposes
     *
     * @access protected
     * @param  boolean $authorized
     * @return object
     */
    protected function _log_request($authorized = FALSE)
    {
        $status = $this->rest->db->insert(config_item('rest_logs_table'), [
                    'uri' => $this->uri->uri_string(),
                    'method' => $this->request->method,
                    'params' => $this->_args ? (config_item('rest_logs_json_params') ? json_encode($this->_args) : serialize($this->_args)) : NULL,
                    'api_key' => isset($this->rest->key) ? $this->rest->key : '',
                    'ip_address' => $this->input->ip_address(),
                    'time' => function_exists('now') ? now() : time(),
                    'authorized' => $authorized
                ]);

        $this->_insert_id = $this->rest->db->insert_id();

        return $status;
    }

    /**
     * Limiting requests
     *
     * Check if the requests are coming in a tad too fast.
     *
     * @access protected
     * @param  string  $controller_method The method being called.
     * @return boolean
     */
    protected function _check_limit($controller_method)
    {
        // They are special, or it might not even have a limit
        if ( ! empty($this->rest->ignore_limits) || !isset($this->methods[$controller_method]['limit'])) {
            // On your way sonny-jim.
            return TRUE;
        }

        // How many times can you get to this method an hour?
        $limit = $this->methods[$controller_method]['limit'];

        // Get data on a keys usage
        $result = $this->rest->db
                ->where('uri', $this->uri->uri_string())
                ->where('api_key', $this->rest->key)
                ->get(config_item('rest_limits_table'))
                ->row();

        // No calls yet for this key
        if ( ! $result ) {
            // Right, set one up from scratch
            $this->rest->db->insert(config_item('rest_limits_table'), [
                'uri' => $this->uri->uri_string(),
                'api_key' => isset($this->rest->key) ? $this->rest->key : '',
                'count' => 1,
                'hour_started' => time()
            ]);
        }

        // Been an hour since they called
        elseif ($result->hour_started < time() - (60 * 60)) {
            // Reset the started period
            $this->rest->db
                    ->where('uri', $this->uri->uri_string())
                    ->where('api_key', isset($this->rest->key) ? $this->rest->key : '')
                    ->set('hour_started', time())
                    ->set('count', 1)
                    ->update(config_item('rest_limits_table'));
        }

        // They have called within the hour, so lets update
        else {
            // Your luck is out, you've called too many times!
            if ($result->count >= $limit) {
                return FALSE;
            }

            $this->rest->db
                    ->where('uri', $this->uri->uri_string())
                    ->where('api_key', $this->rest->key)
                    ->set('count', 'count + 1', FALSE)
                    ->update(config_item('rest_limits_table'));
        }

        return TRUE;
    }

    /**
     * Auth override check
     *
     * Check if there is a specific auth type set for the current class/method
     * being called.
     *
     * @access protected
     * @return boolean
     */
    protected function _auth_override_check()
    {

        // Assign the class/method auth type override array from the config
        $this->overrides_array = $this->config->item('auth_override_class_method');

        // Check to see if the override array is even populated, otherwise return FALSE
        if (empty($this->overrides_array)) {
            return FALSE;
        }

        // check for wildcard flag for rules for classes
        if(!empty($this->overrides_array[$this->router->class]['*'])){//check for class overides
            // None auth override found, prepare nothing but send back a TRUE override flag
            if ($this->overrides_array[$this->router->class]['*'] == 'none')
            {
                return TRUE;
            }

            // Basic auth override found, prepare basic
            if ($this->overrides_array[$this->router->class]['*'] == 'basic')
            {
                $this->_prepare_basic_auth();
                return TRUE;
            }

            // Digest auth override found, prepare digest
            if ($this->overrides_array[$this->router->class]['*'] == 'digest')
            {
                $this->_prepare_digest_auth();
                return TRUE;
            }

            // Whitelist auth override found, check client's ip against config whitelist
            if ($this->overrides_array[$this->router->class]['*'] == 'whitelist')
            {
                $this->_check_whitelist_auth();
                return TRUE;
            }
        }

        // Check to see if there's an override value set for the current class/method being called
        if (empty($this->overrides_array[$this->router->class][$this->router->method])) {
            return FALSE;
        }

        // None auth override found, prepare nothing but send back a TRUE override flag
        if ($this->overrides_array[$this->router->class][$this->router->method] == 'none') {
            return TRUE;
        }

        // Basic auth override found, prepare basic
        if ($this->overrides_array[$this->router->class][$this->router->method] == 'basic') {
            $this->_prepare_basic_auth();

            return TRUE;
        }

        // Digest auth override found, prepare digest
        if ($this->overrides_array[$this->router->class][$this->router->method] == 'digest') {
            $this->_prepare_digest_auth();

            return TRUE;
        }

        // Whitelist auth override found, check client's ip against config whitelist
        if ($this->overrides_array[$this->router->class][$this->router->method] == 'whitelist') {
            $this->_check_whitelist_auth();

            return TRUE;
        }

        // Return FALSE when there is an override value set but it does not match
        // 'basic', 'digest', or 'none'. (the value was misspelled)
        return FALSE;
    }

    /**
     * Parse GET
     *
     * @access protected
     */
    protected function _parse_get()
    {
        // Fix for Issue #247
        if (is_cli()) {
            $args = $_SERVER['argv'];
            unset($args[0]);
            $_SERVER['QUERY_STRING'] =  $_SERVER['PATH_INFO'] = $_SERVER['REQUEST_URI'] = '/' . implode('/', $args) . '/';
        }

        // Grab proper GET variables
        parse_str(parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY), $get);

        // Merge both the URI segments and GET params
        $this->_get_args = array_merge($this->_get_args, $get);
    }

    /**
     * Parse POST
     *
     * @access protected
     */
    protected function _parse_post()
    {
        $this->_post_args = $_POST;

        $this->request->format && $this->request->body = file_get_contents('php://input');
    }

    /**
     * Parse PUT
     *
     * @access protected
     */
    protected function _parse_put()
    {
        // It might be a HTTP body
        if ($this->request->format) {
            $this->request->body = file_get_contents('php://input');
        }

        // If no file type is provided, this is probably just arguments
        else {
            if ($this->input->method() == 'put') {
                $this->_put_args = $this->input->input_stream();
            }
        }

    }

    /**
     * Parse HEAD
     *
     * @access protected
     */
    protected function _parse_head()
    {
        // Grab proper HEAD variables
        parse_str(parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY), $head);

        // Merge both the URI segments and HEAD params
        $this->_head_args = array_merge($this->_head_args, $head);
    }

    /**
     * Parse OPTIONS
     *
     * @access protected
     */
    protected function _parse_options()
    {
        // Grab proper OPTIONS variables
        parse_str(parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY), $options);

        // Merge both the URI segments and OPTIONS params
        $this->_options_args = array_merge($this->_options_args, $options);
    }

    /**
     * Parse PATCH
     *
     * @access protected
     */
    protected function _parse_patch()
    {
        // It might be a HTTP body
        if ($this->request->format) {
            $this->request->body = file_get_contents('php://input');
        }

        // If no file type is provided, this is probably just arguments
        else {
            if ($this->input->method() == 'patch') {
                $this->_patch_args = $this->input->input_stream();
            }
        }
    }

    /**
     * Parse DELETE
     *
     * @access protected
     */
    protected function _parse_delete()
    {
        // Set up out DELETE variables (which shouldn't really exist, but sssh!)
        if ($this->input->method() == 'delete') {
            $this->_delete_args = $this->input->input_stream();
        }
    }

    // INPUT FUNCTION --------------------------------------------------------------

    /**
     * Retrieve a value from the GET request arguments.
     *
     * @access public
     * @param  string  $key       The key for the GET request argument to retrieve
     * @param  boolean $xss_clean Whether the value should be XSS cleaned or not.
     * @return string  The GET argument value.
     */
    public function get($key = NULL, $xss_clean = TRUE)
    {
        if ($key === NULL) {
            return $this->_get_args;
        }

        return array_key_exists($key, $this->_get_args) ? $this->_xss_clean($this->_get_args[$key], $xss_clean) : FALSE;
    }

    /**
     * This function retrieves a values from the OPTIONS request arguments
     *
     * @access public
     * @param  string  $key       The OPTIONS/GET argument key
     * @param  boolean $xss_clean Whether the value should be XSS cleaned or not
     * @return string  The OPTIONS/GET argument value
     */
    public function options($key = NULL, $xss_clean = TRUE)
    {
        if ($key === NULL) {
            return $this->_options_args;
        }

        return array_key_exists($key, $this->_options_args) ? $this->_xss_clean($this->_options_args[$key], $xss_clean) : FALSE;
    }

    /**
     * This function retrieves a values from the HEAD request arguments
     *
     * @access public
     * @param  string  $key       The HEAD/GET argument key
     * @param  boolean $xss_clean Whether the value should be XSS cleaned or not
     * @return string  The HEAD/GET argument value
     */
    public function head($key = NULL, $xss_clean = TRUE)
    {
        if ($key === NULL) {
            return $this->head_args;
        }

        return array_key_exists($key, $this->head_args) ? $this->_xss_clean($this->head_args[$key], $xss_clean) : FALSE;
    }

    /**
     * Retrieve a value from the POST request arguments.
     *
     * @access public
     * @param  string  $key       The key for the POST request argument to retrieve
     * @param  boolean $xss_clean Whether the value should be XSS cleaned or not.
     * @return string  The POST argument value.
     */
    public function post($key = NULL, $xss_clean = TRUE)
    {
        if ($key === NULL) {
            return $this->_post_args;
        }

        return array_key_exists($key, $this->_post_args) ? $this->_xss_clean($this->_post_args[$key], $xss_clean) : FALSE;
    }

    /**
     * Retrieve a value from the PUT request arguments.
     *
     * @access public
     * @param  string  $key       The key for the PUT request argument to retrieve
     * @param  boolean $xss_clean Whether the value should be XSS cleaned or not.
     * @return string  The PUT argument value.
     */
    public function put($key = NULL, $xss_clean = TRUE)
    {
        if ($key === NULL) {
            return $this->_put_args;
        }

        return array_key_exists($key, $this->_put_args) ? $this->_xss_clean($this->_put_args[$key], $xss_clean) : FALSE;
    }

    /**
     * Retrieve a value from the DELETE request arguments.
     *
     * @access public
     * @param  string  $key       The key for the DELETE request argument to retrieve
     * @param  boolean $xss_clean Whether the value should be XSS cleaned or not.
     * @return string  The DELETE argument value.
     */
    public function delete($key = NULL, $xss_clean = TRUE)
    {
        if ($key === NULL) {
            return $this->_delete_args;
        }

        return array_key_exists($key, $this->_delete_args) ? $this->_xss_clean($this->_delete_args[$key], $xss_clean) : FALSE;
    }

    /**
     * Retrieve a value from the PATCH request arguments.
     *
     * @access public
     * @param  string  $key       The key for the PATCH request argument to retrieve
     * @param  boolean $xss_clean Whether the value should be XSS cleaned or not.
     * @return string  The PATCH argument value.
     */
    public function patch($key = NULL, $xss_clean = TRUE)
    {
        if ($key === NULL) {
            return $this->_patch_args;
        }

        return array_key_exists($key, $this->_patch_args) ? $this->_xss_clean($this->_patch_args[$key], $xss_clean) : FALSE;
    }

    /**
     * Process to protect from XSS attacks.
     *
     * @access protected
     * @param  string  $val     The input.
     * @param  boolean $process Do clean or note the input.
     * @return string
     */
    protected function _xss_clean($val, $process)
    {
        if (CI_VERSION < 2) {
            return $process ? $this->input->xss_clean($val) : $val;
        }

        return $process ? $this->security->xss_clean($val) : $val;
    }

    /**
     * Retrieve the validation errors.
     *
     * @access public
     * @return array
     */
    public function validation_errors()
    {
        $string = strip_tags($this->form_validation->error_string());

        return explode("\n", trim($string, "\n"));
    }

    // SECURITY FUNCTIONS ---------------------------------------------------------

    /**
     * Perform LDAP Authentication
     *
     * @access protected
     * @param  string  $username The username to validate
     * @param  string  $password The password to validate
     * @return boolean
     */
    protected function _perform_ldap_auth($username = '', $password = NULL)
    {
        if (empty($username)) {
            log_message('debug', 'LDAP Auth: failure, empty username');

            return FALSE;
        }

        log_message('debug', 'LDAP Auth: Loading Config');

        $this->config->load('ldap.php', TRUE);

        $ldap = [
            'timeout' => $this->config->item('timeout', 'ldap'),
            'host'    => $this->config->item('server', 'ldap'),
            'port'    => $this->config->item('port', 'ldap'),
            'rdn'     => $this->config->item('binduser', 'ldap'),
            'pass'    => $this->config->item('bindpw', 'ldap'),
            'basedn'  => $this->config->item('basedn', 'ldap'),
          ];

        log_message('debug', 'LDAP Auth: Connect to ' . $ldaphost);

        $ldapconfig['authrealm'] = $this->config->item('domain', 'ldap');

        // connect to ldap server
        $ldapconn = ldap_connect($ldap['host'], $ldap['port']);

        if ($ldapconn) {

            log_message('debug', 'Setting timeout to ' . $ldap['timeout'] . ' seconds');

            ldap_set_option($ldapconn, LDAP_OPT_NETWORK_TIMEOUT, $ldap['timeout']);

            log_message('debug', 'LDAP Auth: Binding to ' . $ldap['host'] . ' with dn ' . $ldap['rdn']);

            // binding to ldap server
            $ldapbind = ldap_bind($ldapconn, $ldap['rdn'], $ldap['pass']);

            // verify binding
            if ($ldapbind) {
                log_message('debug', 'LDAP Auth: bind successful');
            } else {
                log_message('error', 'LDAP Auth: bind unsuccessful');

                return FALSE;
            }

        }

        // search for user
        if (($res_id = ldap_search( $ldapconn, $ldap['basedn'], "uid=$username")) == FALSE) {
            log_message('error', 'LDAP Auth: User ' . $username . ' not found in search');

            return FALSE;
        }

        if (ldap_count_entries($ldapconn, $res_id) != 1) {
            log_message('error', 'LDAP Auth: failure, username ' . $username . 'found more than once');

            return FALSE;
        }

        if (( $entry_id = ldap_first_entry($ldapconn, $res_id))== FALSE) {
            log_message('error', 'LDAP Auth: failure, entry of searchresult could not be fetched');

            return FALSE;
        }

        if (( $user_dn = ldap_get_dn($ldapconn, $entry_id)) == FALSE) {
            log_message('error', 'LDAP Auth: failure, user-dn could not be fetched');

            return FALSE;
        }

        // User found, could not authenticate as user
        if (($link_id = ldap_bind($ldapconn, $user_dn, $password)) == FALSE) {
            log_message('error', 'LDAP Auth: failure, username/password did not match: ' . $user_dn);

            return FALSE;
        }

        log_message('debug', 'LDAP Auth: Success ' . $user_dn . ' authenticated successfully');

        $this->_user_ldap_dn = $user_dn;
        ldap_close($ldapconn);

        return TRUE;
    }

    /**
     * Perform Library Authentication - Override this function to change the way the library is called
     *
     * @access protected
     * @param  string  $username The username to validate
     * @param  string  $password The password to validate
     * @return boolean
     */
    protected function _perform_library_auth($username = '', $password = NULL)
    {
        if (empty($username)) {
            log_message('error', 'Library Auth: failure, empty username');
            return FALSE;
        }

        $auth_library_class     = strtolower($this->config->item('auth_library_class'));
        $auth_library_function  = strtolower($this->config->item('auth_library_function'));

        if (empty($auth_library_class)) {
            log_message('debug', 'Library Auth: failure, empty auth_library_class');
            return FALSE;
        }

        if (empty($auth_library_function)) {
            log_message('debug', 'Library Auth: failure, empty auth_library_function');
            return FALSE;
        }

        if (!is_callable([$auth_library_class, $auth_library_function])) {
            $this->load->library($auth_library_class);
        }

        return $this->{$auth_library_class}->$auth_library_function($username, $password);
    }

    /**
     * Check if the user is logged in.
     *
     * @access protected
     * @param  string  $username The user's name
     * @param  string  $password The user's password
     * @return boolean
     */
    protected function _check_login($username = '', $password = FALSE)
    {
        if (empty($username)) {
            return FALSE;
        }

        $auth_source = strtolower($this->config->item('auth_source'));
        $rest_auth = strtolower($this->config->item('rest_auth'));
        $valid_logins = $this->config->item('rest_valid_logins');

        if (!$this->config->item('auth_source') && $rest_auth == 'digest') { // for digest we do not have a password passed as argument
            return md5($username.':'.$this->config->item('rest_realm').':'.(isset($valid_logins[$username])?$valid_logins[$username]:''));
        }

        if ($password === FALSE) {
            return FALSE;
        }

        if ($auth_source == 'ldap') {
            log_message('debug', 'performing LDAP authentication for $username');

            return $this->_perform_ldap_auth($username, $password);
        }

        if ($auth_source == 'library') {
            log_message('debug', 'performing Library authentication for '.$username);

            return $this->_perform_library_auth($username, $password);
        }

        if (!array_key_exists($username, $valid_logins)) {
            return FALSE;
        }

        if ($valid_logins[$username] != $password) {
            return FALSE;
        }

        return TRUE;
    }

    /**
     * Check to see if the user is logged into the web app with a php session key.
     *
     * @access protected
     */
    protected function _check_php_session()
    {
        $key = $this->config->item('auth_source');
        if (!$this->session->userdata($key)) {
            $this->response([config_item('rest_status_field_name') => FALSE, config_item('rest_message_field_name') => 'Not Authorized'], 401);
        }
    }

    /**
     * @todo document this.
     *
     * @access protected
     */
    protected function _prepare_basic_auth()
    {
        // If whitelist is enabled it has the first chance to kick them out
        if (config_item('rest_ip_whitelist_enabled')) {
            $this->_check_whitelist_auth();
        }

        $username = NULL;
        $password = NULL;

        // mod_php
        if ($this->input->server('PHP_AUTH_USER')) {
            $username = $this->input->server('PHP_AUTH_USER');
            $password = $this->input->server('PHP_AUTH_PW');
        }

        // most other servers
        elseif ($this->input->server('HTTP_AUTHENTICATION')) {
            if (strpos(strtolower($this->input->server('HTTP_AUTHENTICATION')), 'basic') === 0) {
                list($username, $password) = explode(':', base64_decode(substr($this->input->server('HTTP_AUTHORIZATION'), 6)));
            }
        }

        if ( ! $this->_check_login($username, $password)) {
            $this->_force_login();
        }
    }

    /**
     * @todo Document this.
     *
     * @access protected
     */
    protected function _prepare_digest_auth()
    {
        // If whitelist is enabled it has the first chance to kick them out
        if (config_item('rest_ip_whitelist_enabled')) {
            $this->_check_whitelist_auth();
        }

        $uniqid = uniqid(""); // Empty argument for backward compatibility
        // We need to test which server authentication variable to use
        // because the PHP ISAPI module in IIS acts different from CGI
        if ($this->input->server('PHP_AUTH_DIGEST')) {
            $digest_string = $this->input->server('PHP_AUTH_DIGEST');
        } elseif ($this->input->server('HTTP_AUTHORIZATION')) {
            $digest_string = $this->input->server('HTTP_AUTHORIZATION');
        } else {
            $digest_string = "";
        }

        // The $_SESSION['error_prompted'] variable is used to ask the password
        // again if none given or if the user enters wrong auth information.
        if (empty($digest_string)) {
            $this->_force_login($uniqid);
        }

        // We need to retrieve authentication informations from the $auth_data variable
        $matches = [];
        preg_match_all('@(username|nonce|uri|nc|cnonce|qop|response)=[\'"]?([^\'",]+)@', $digest_string, $matches);
        $digest = (empty($matches[1]) || empty($matches[2])) ? [] : array_combine($matches[1], $matches[2]);

        // For digest authentication the library function should return already stored md5(username:restrealm:password) for that username @see rest.php::auth_library_function config
        $A1 = $this->_check_login($digest['username'], TRUE);
        if ( ! array_key_exists('username', $digest) || ! $A1 ) {
            $this->_force_login($uniqid);
        }

        $A2 = md5(strtoupper($this->request->method).':'.$digest['uri']);
        $valid_response = md5($A1.':'.$digest['nonce'].':'.$digest['nc'].':'.$digest['cnonce'].':'.$digest['qop'].':'.$A2);

        if ($digest['response'] != $valid_response) {
            $this->response([config_item('rest_status_field_name') => 0, config_item('rest_message_field_name') => 'Invalid credentials'], 401);
            exit;
        }
    }

    /**
     * Check if the client's ip is in the 'rest_ip_blacklist' config
     *
     * @access protected
     */
    protected function _check_blacklist_auth()
    {
        $blacklist = explode(',', config_item('rest_ip_blacklist'));

        foreach ($blacklist AS &$ip) {
            $ip = trim($ip);
        }

        if (in_array($this->input->ip_address(), $blacklist)) {
            $this->response(['status' => FALSE, 'error' => 'IP Denied'], 401);
        }
    }

    /**
     * Check if the client's ip is in the 'rest_ip_whitelist' config
     *
     * @access protected
     */
    protected function _check_whitelist_auth()
    {
        $whitelist = explode(',', config_item('rest_ip_whitelist'));

        array_push($whitelist, '127.0.0.1', '0.0.0.0');

        foreach ($whitelist AS &$ip) {
            $ip = trim($ip);
        }

        if ( ! in_array($this->input->ip_address(), $whitelist)) {
            $this->response([config_item('rest_status_field_name') => FALSE, config_item('rest_message_field_name') => 'IP not authorized'], 401);
        }
    }

    /**
     * @todo Document this.
     *
     * @access protected
     * @param  string $nonce
     */
    protected function _force_login($nonce = '')
    {
        if (strtolower( $this->config->item('rest_auth') ) == 'basic') {
            header('WWW-Authenticate: Basic realm="'.$this->config->item('rest_realm').'"');
        } elseif (strtolower( $this->config->item('rest_auth') ) == 'digest') {
            header('WWW-Authenticate: Digest realm="'.$this->config->item('rest_realm').'", qop="auth", nonce="'.$nonce.'", opaque="'.md5($this->config->item('rest_realm')).'"');
        }

        $this->response([config_item('rest_status_field_name') => FALSE, config_item('rest_message_field_name') => 'Not authorized'], 401);
    }

    /**
     * Force it into an array
     *
     * @access  protected
     * @param   object|array $data
     * @return  array
     */
    protected function _force_loopable($data)
    {
        // Force it to be something useful
        if ( ! is_array($data) && !is_object($data)) {
            $data = (array) $data;
        }

        return $data;
    }

    /**
     * updates the log with the access time
     *
     * @access protected
     * @author Chris Kacerguis
     * @return boolean
     */

    protected function _log_access_time()
    {
        $payload['rtime'] = $this->_end_rtime - $this->_start_rtime;

        return $this->rest->db->update(config_item('rest_logs_table'), $payload, ['id' => $this->_insert_id]);
    }

    /**
     * updates the log with response code result
     *
     * @author Justin Chen
     * @return boolean
     */

    protected function _log_response_code($http_code)
    {
        $payload['response_code'] = $http_code;
        return $this->rest->db->update(config_item('rest_logs_table'), $payload, ['id' => $this->_insert_id]);
    }

    /**
     * Check to see if the API key has access to the controller and methods
     *
     * @access protected
     * @return boolean
     */
    protected function _check_access()
    {
        // if we don't want to check acccess, just return TRUE
        if (config_item('rest_enable_access') === FALSE) {
            return TRUE;
        }

        // Fetch controller based on path and controller name
        $controller = implode( '/', [$this->router->directory, $this->router->class] );

        // Remove any double slashes for safety
        $controller = str_replace('//', '/', $controller);

        // Build access table query
        $this->rest->db->select();
        $this->rest->db->where('key', $this->rest->key);
        $this->rest->db->where('controller', $controller);

        $query = $this->rest->db->get(config_item('rest_access_table'));

        if ($query->num_rows() > 0) {
            return TRUE;
        }

        return FALSE;
    }

}
