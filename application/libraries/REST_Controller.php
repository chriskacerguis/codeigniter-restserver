<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * CodeIgniter Rest Controller
 * A fully RESTful server implementation for CodeIgniter using one library, one config file and one controller.
 *
 * @package         CodeIgniter
 * @subpackage      Libraries
 * @category        Libraries
 * @author          Phil Sturgeon, Chris Kacerguis
 * @license         MIT
 * @link            https://github.com/chriskacerguis/codeigniter-restserver
 * @version         3.0.0
 */
abstract class REST_Controller extends CI_Controller {
    /**
     * This defines the rest format.
     * Must be overridden it in a controller so that it is set.
     *
     * @var string|NULL
     */
    protected $rest_format = NULL;

    /**
     * Defines the list of method properties such as limit, log and level
     *
     * @var array
     */
    protected $methods = [];

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
    protected $request = NULL;

    /**
     * What is gonna happen in output?
     *
     * @var object
     */
    protected $response = NULL;

    /**
     * Stores DB, keys, key level, etc
     *
     * @var object
     */
    protected $rest = NULL;

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
     * The insert_id of the log entry (if we have one)
     *
     * @var string
     */
    protected $_insert_id = '';

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
     * The arguments from GET, POST, PUT, DELETE request methods combined.
     *
     * @var array
     */
    protected $_args = [];

    /**
     * If the request is allowed based on the API key provided.
     *
     * @var bool
     */
    protected $_allow = TRUE;

    /**
     * Determines if output compression is enabled
     *
     * @var bool
     */
    protected $_zlib_oc = FALSE;

    /**
     * The LDAP Distinguished Name of the User post authentication
     *
     * @var string
     */
    protected $_user_ldap_dn = '';

    /**
     * The start of the response time from the server
     *
     * @var string
     */
    protected $_start_rtime = '';

    /**
     * The end of the response time from the server
     *
     * @var string
     */
    protected $_end_rtime = '';

    /**
     * List all supported methods, the first will be the default format
     *
     * @var array
     */
    protected $_supported_formats = [
            'json' => 'application/json',
            'array' => 'application/json',
            'csv' => 'application/csv',
            'html' => 'text/html',
            'jsonp' => 'application/javascript',
            'php' => 'text/plain',
            'serialized' => 'application/vnd.php.serialized',
            'xml' => 'application/xml'
        ];

    /**
     * Information about the current API user
     *
     * @var object
     */
    protected $_apiuser;

    /**
     * Enable XSS flag
     * Determines whether the XSS filter is always active when
     * GET, OPTIONS, HEAD, POST, PUT, DELETE and PATCH data is encountered.
     * Set automatically based on config setting.
     *
     * @var bool
     */
    protected $_enable_xss = FALSE;

    /**
     * Extend this function to apply additional checking early on in the process
     *
     * @access protected
     */
    protected function early_checks()
    {
    }

    /**
     * Constructor for the REST API
     *
     * @access public
     *
     * @param string $config Configuration filename minus the file extension
     * e.g: my_rest.php is passed as 'my_rest'
     */
    public function __construct($config = 'rest')
    {
        parent::__construct();

        // Disable XML Entity (security vulnerability)
        libxml_disable_entity_loader(TRUE);

        // Check to see if PHP is equal to or greater than 5.4.x
        if (is_php('5.4') === FALSE)
        {
            // CodeIgniter 3 is recommended for v5.4 or above
            exit('Using PHP v' . PHP_VERSION . ', though PHP v5.4 or greater is required');
        }

        // Check to see if this is CI 3.x
        if (explode('.', CI_VERSION, 2)[0] < 3)
        {
            exit('REST Server requires CodeIgniter 3.x');
        }

        // Set the default value of global xss filtering. Same approach as CodeIgniter 3
        $this->_enable_xss = (config_item('global_xss_filtering') === TRUE);

        // Start the timer for how long the request takes
        $this->_start_rtime = microtime(TRUE);

        // Load the rest.php configuration file
        $this->load->config($config);

        // At present the library is bundled with REST_Controller 2.5+, but will eventually be part of CodeIgniter (no citation)
        $this->load->library('format');

        // Initialise the response, request and rest objects
        $this->request = new stdClass();
        $this->response = new stdClass();
        $this->rest = new stdClass();

        $this->_zlib_oc = @ini_get('zlib.output_compression');

        // Check to see if the current IP address is blacklisted
        if ($this->config->item('rest_ip_blacklist_enabled') === TRUE)
        {
            $this->_check_blacklist_auth();
        }

        // Determine whether the connection is HTTPS
        $this->request->ssl = is_https();

        // How is this request being made? GET, POST, PATCH, DELETE, INSERT, PUT, HEAD or OPTIONS
        $this->request->method = $this->_detect_method();

        // Create an argument container if it doesn't exist e.g. _get_args
        if (!isset($this->{'_' . $this->request->method . '_args'}))
        {
            $this->{'_' . $this->request->method . '_args'} = [];
        }

        // Set up the GET variables
        $this->_get_args = array_merge($this->_get_args, $this->uri->ruri_to_assoc());

        // Try to find a format for the request (means we have a request body)
        $this->request->format = $this->_detect_input_format();

        // Not all methods have a body attached with them
        $this->request->body = NULL;

        $this->{'_parse_' . $this->request->method}();

        // Now we know all about our request, let's try and parse the body if it exists
        if ($this->request->format && $this->request->body)
        {
            $this->request->body = $this->format->factory($this->request->body, $this->request->format)->to_array();
            // Assign payload arguments to proper method container
            $this->{'_' . $this->request->method . '_args'} = $this->request->body;
        }

        // Merge both for one mega-args variable
        $this->_args = array_merge(
            $this->_get_args,
            $this->_options_args,
            $this->_patch_args,
            $this->_head_args,
            $this->_put_args,
            $this->_post_args,
            $this->_delete_args,
            $this->{'_' . $this->request->method . '_args'}
        );

        // Which format should the data be returned in?
        $this->response->format = $this->_detect_output_format();

        // Which format should the data be returned in?
        $this->response->lang = $this->_detect_lang();

        // Extend this function to apply additional checking early on in the process
        $this->early_checks();

        // Load DB if its enabled
        if (config_item('rest_database_group') && (config_item('rest_enable_keys') || config_item('rest_enable_logging')))
        {
            $this->rest->db = $this->load->database(config_item('rest_database_group'), TRUE);
        }

        // Use whatever database is in use (isset returns FALSE)
        elseif (property_exists($this, 'db'))
        {
            $this->rest->db = $this->db;
        }

        // Check if there is a specific auth type for the current class/method
        // _auth_override_check could exit so we need $this->rest->db initialized before
        $this->auth_override = $this->_auth_override_check();

        // Checking for keys? GET TO WorK!
        // Skip keys test for $config['auth_override_class_method']['class'['method'] = 'none'
        if (config_item('rest_enable_keys') && $this->auth_override !== TRUE)
        {
            $this->_allow = $this->_detect_api_key();
        }

        // Only allow ajax requests
        if ($this->input->is_ajax_request() === FALSE && config_item('rest_ajax_only'))
        {
            // Display an error response
            $this->response(
                [
                    config_item('rest_status_field_name') => FALSE,
                    config_item('rest_message_field_name') => 'Only AJAX requests are acceptable'
                ], 406); // Set status to 406 NOT ACCEPTABLE
        }

        // When there is no specific override for the current class/method, use the default auth value set in the config
        if ($this->auth_override === FALSE && !(config_item('rest_enable_keys') && $this->_allow === TRUE))
        {
            $rest_auth = strtolower($this->config->item('rest_auth'));
            switch ($rest_auth)
            {
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
            if ($this->config->item('rest_ip_whitelist_enabled') === TRUE)
            {
                $this->_check_whitelist_auth();
            }
        }
    }

    /**
     * Deconstructor
     *
     * @author Chris Kacerguis
     * @access public
     */
    public function __destruct()
    {
        // Get the current timestamp
        $this->_end_rtime = microtime(TRUE);

        // Log the loading time to the log table
        if (config_item('rest_enable_logging') === TRUE)
        {
            $this->_log_access_time();
        }
    }

    /**
     * Requests are not made to methods directly, the request will be for
     * an "object". This simply maps the object and method to the correct
     * Controller method.
     *
     * @access public
     *
     * @param  string $object_called
     * @param  array $arguments The arguments passed to the controller method.
     */
    public function _remap($object_called, $arguments)
    {
        // Should we answer if not over SSL?
        if (config_item('force_https') && $this->request->ssl === FALSE)
        {
            $this->response([config_item('rest_status_field_name') => FALSE, config_item('rest_message_field_name') => 'Unsupported protocol'], 403);
        }

        // Remove the supported format from the function name e.g. index.json => index
        $object_called = preg_replace('/^(.*)\.(?:' . implode('|', array_keys($this->_supported_formats)) . ')$/', '$1', $object_called);

        $controller_method = $object_called . '_' . $this->request->method;

        // Do we want to log this method (if allowed by config)?
        $log_method = !(isset($this->methods[$controller_method]['log']) && $this->methods[$controller_method]['log'] == FALSE);

        // Use keys for this method?
        $use_key = !(isset($this->methods[$controller_method]['key']) && $this->methods[$controller_method]['key'] == FALSE);

        // They provided a key, but it wasn't valid, so get them out of here.
        if (config_item('rest_enable_keys') && $use_key && $this->_allow === FALSE)
        {
            if (config_item('rest_enable_logging') && $log_method)
            {
                $this->_log_request();
            }

            $this->response([config_item('rest_status_field_name') => FALSE, config_item('rest_message_field_name') => 'Invalid API Key ' . $this->rest->key], 403);
        }

        // Check to see if this key has access to the requested controller.
        if (config_item('rest_enable_keys') && $use_key && !empty($this->rest->key) && !$this->_check_access())
        {
            if (config_item('rest_enable_logging') && $log_method)
            {
                $this->_log_request();
            }

            $this->response([config_item('rest_status_field_name') => FALSE, config_item('rest_message_field_name') => 'This API key does not have access to the requested controller.'], 401);
        }

        // Sure it exists, but can they do anything with it?
        if (!method_exists($this, $controller_method))
        {
            $this->response([config_item('rest_status_field_name') => FALSE, config_item('rest_message_field_name') => 'Unknown method.'], 404);
        }

        // Doing key related stuff? Can only do it if they have a key right?
        if (config_item('rest_enable_keys') && !empty($this->rest->key))
        {
            // Check the limit
            if (config_item('rest_enable_limits') && !$this->_check_limit($controller_method))
            {
                $response = [config_item('rest_status_field_name') => FALSE, config_item('rest_message_field_name') => 'This API key has reached the hourly limit for this method.'];
                $this->response($response, 401);
            }

            // If no level is set use 0, they probably aren't using permissions
            $level = isset($this->methods[$controller_method]['level']) ? $this->methods[$controller_method]['level'] : 0;

            // If no level is set, or it is lower than/equal to the key's level
            $authorized = $level <= $this->rest->level;

            // IM TELLIN!
            if (config_item('rest_enable_logging') && $log_method)
            {
                $this->_log_request($authorized);
            }

            // They don't have good enough perms
            $response = [config_item('rest_status_field_name') => FALSE, config_item('rest_message_field_name') => 'This API key does not have enough permissions.'];
            $authorized || $this->response($response, 401);
        }

        // No key stuff, but record that stuff is happening
        elseif (config_item('rest_enable_logging') && $log_method)
        {
            $this->_log_request($authorized = TRUE);
        }

        // Call the controller method and passed arguments
        try
        {
            call_user_func_array([$this, $controller_method], $arguments);
        }
        catch (Exception $ex)
        {
            // If the method doesn't exist, then the error will be caught and an error response shown
            $this->response(
                [
                    config_item('rest_status_field_name') => FALSE,
                    config_item('rest_message_field_name') => [
                        'classname' => get_class($ex),
                        'message' => $ex->getMessage()
                    ]
                ], 500);
        }
    }

    /**
     * Takes mixed data and optionally a status code, then creates the response
     *
     * @access public
     *
     * @param array|NULL $data Data to output to the user
     * @param int|NULL $http_code HTTP status code
     * @param bool $continue TRUE to flush the response to the client and continue
     * running the script; otherwise, exit
     */
    public function response($data = NULL, $http_code = NULL, $continue = FALSE)
    {
        // If the HTTP status is not NULL, then cast as an integer
        if ($http_code !== NULL)
        {
            // So as to be safe later on in the process
            $http_code = (int) $http_code;
        }

        // Set the output as NULL by default
        $output = NULL;

        // If data is NULL and no HTTP status code provided, then display, error and exit
        if ($data === NULL && $http_code === NULL)
        {
            $http_code = 404;
        }

        // If data is not NULL and a HTTP status code provided, then continue
        elseif ($data !== NULL)
        {
            // Is compression enabled and available?
            if ($this->config->item('compress_output') === TRUE && $this->_zlib_oc == FALSE)
            {
                if (extension_loaded('zlib'))
                {
                    $http_encoding = $this->input->server('HTTP_ACCEPT_ENCODING');
                    if ($http_encoding !== NULL && strpos($http_encoding, 'gzip') !== FALSE)
                    {
                        ob_start('ob_gzhandler');
                    }
                }
            }

            // If the format method exists, call and return the output in that format
            if (method_exists($this->format, 'to_' . $this->response->format))
            {
                // Set the format header
                header('Content-Type: ' . $this->_supported_formats[$this->response->format]
                       . '; charset=' . strtolower($this->config->item('charset')));

                $output = $this->format->factory($data)->{'to_' . $this->response->format}();

                // An array must be parsed as a string, so as not to cause an array to string error.
                // Json is the most appropriate form for such a datatype
                if ($this->response->format === 'array')
                {
                    $output = $this->format->factory($output)->{'to_json'}();
                }
            }
            else
            {
                // If an array or object, then parse as a json, so as to be a 'string'
                if (is_array($data) || is_object($data))
                {
                    $data = $this->format->factory($data)->{'to_json'}();
                }

                // Format is not supported, so output the raw data as a string
                $output = $data;
            }
        }

        // If not greater than zero, then set the HTTP status code as 200 by default
        // Though perhaps 500 should be set instead, for the developer not passing a
        // correct HTTP status code
        $http_code > 0 || $http_code = 200;

        set_status_header($http_code);

        // JC: Log response code only if rest logging enabled
        if (config_item('rest_enable_logging') === TRUE)
        {
            $this->_log_response_code($http_code);
        }

        // If zlib.output_compression is enabled it will compress the output,
        // but it will not modify the content-length header to compensate for
        // the reduction, causing the browser to hang waiting for more data.
        // We'll just skip content-length in those cases
        if (!$this->_zlib_oc && !$this->config->item('compress_output'))
        {
            header('Content-Length: ' . strlen($output));
        }

        if ($continue === FALSE)
        {
            exit($output);
        }

        echo($output);
        ob_end_flush();
        ob_flush();
        flush();
    }

    /**
     * Detect which format the HTTP Body is provided in
     *
     * @access protected
     */
    protected function _detect_input_format()
    {
        if ($this->input->server('CONTENT_TYPE'))
        {
            // Check all formats against the HTTP_ACCEPT header
            foreach ($this->_supported_formats as $format => $mime)
            {
                if (strpos($match = $this->input->server('CONTENT_TYPE'), ';'))
                {
                    $match = current(explode(';', $match));
                }

                if ($match == $mime)
                {
                    return $format;
                }
            }
        }

        return NULL;
    }

    /**
     * Detect which format should be used to output the data
     *
     * @access protected
     * @return string The output format.
     */
    protected function _detect_output_format()
    {
        $pattern = '/\.(' . implode('|', array_keys($this->_supported_formats)) . ')$/';

        // Check if a file extension is used when no get arguments provided
        $matches = [];
        if (!$this->_get_args && preg_match($pattern, $this->uri->uri_string(), $matches))
        {
            return $matches[1];
        }

        // Check if a file extension is used
        elseif ($this->_get_args && !is_array(end($this->_get_args)) && preg_match($pattern, end($this->_get_args), $matches))
        {
            //elseif ($this->_get_args and !is_array(end($this->_get_args)) and preg_match($pattern, end(array_keys($this->_get_args)), $matches)) {
            // The key of the last argument
            $arg_keys = array_keys($this->_get_args);
            $last_key = end($arg_keys);

            // Remove the extension from arguments too
            $this->_get_args[$last_key] = preg_replace($pattern, '', $this->_get_args[$last_key]);
            $this->_args[$last_key] = preg_replace($pattern, '', $this->_args[$last_key]);

            return $matches[1];
        }

        // A format has been passed as an argument in the URL and it is supported
        if (isset($this->_get_args['format']) && array_key_exists($this->_get_args['format'], $this->_supported_formats))
        {
            return $this->_get_args['format'];
        }

        // Otherwise, check the HTTP_ACCEPT (if it exists and we are allowed)
        if ($this->config->item('rest_ignore_http_accept') === FALSE && $this->input->server('HTTP_ACCEPT'))
        {
            // Check all formats against the HTTP_ACCEPT header
            foreach (array_keys($this->_supported_formats) as $format)
            {
                // Has this format been requested?
                if (strpos($this->input->server('HTTP_ACCEPT'), $format) !== FALSE)
                {
                    // If not HTML or XML assume its right and send it on its way
                    if ($format != 'html' && $format != 'xml')
                    {
                        return $format;
                    }

                    // HTML or XML have shown up as a match
                    else
                    {
                        // If it is truly HTML, it wont want any XML
                        if ($format == 'html' && strpos($this->input->server('HTTP_ACCEPT'), 'xml') === FALSE)
                        {
                            return $format;
                        }

                        // If it is truly XML, it wont want any HTML
                        elseif ($format == 'xml' && strpos($this->input->server('HTTP_ACCEPT'), 'html') === FALSE)
                        {
                            return $format;
                        }
                    }
                }
            }
        } // End HTTP_ACCEPT checking

        // Well, none of that has worked! Let's see if the controller has a default
        if (!empty($this->rest_format))
        {
            return $this->rest_format;
        }

        // Just use the default format
        return config_item('rest_default_format');
    }

    /**
     * Get the HTTP request string e.g. get or post
     *
     * @return string|NULL Supported request method as a lowercase string; otherwise, NULL if not supported
     */
    protected function _detect_method()
    {
        // Declare a variable to store the method
        $method = NULL;

        // Determine whether the 'enable_emulate_request' setting is enabled
        if ($this->config->item('enable_emulate_request') === TRUE)
        {
            $method = $this->input->post('_method');
            if ($method === NULL)
            {
                $method = $this->input->server('HTTP_X_HTTP_METHOD_OVERRIDE');
            }

            $method = strtolower($method);
        }

        if (empty($method))
        {
            // Get the request method as a lowercase string.
            $method = $this->input->method();
        }

        return in_array($method, $this->allowed_http_methods) && method_exists($this, '_parse_' . $method) ? $method : 'get';
    }

    /**
     * See if the user has provided an API key
     *
     * @access protected
     * @return bool
     */
    protected function _detect_api_key()
    {
        // Get the api key name variable set in the rest config file
        $api_key_variable = config_item('rest_key_name');

        // Work out the name of the SERVER entry based on config
        $key_name = 'HTTP_' . strtoupper(str_replace('-', '_', $api_key_variable));

        $this->rest->key = NULL;
        $this->rest->level = NULL;
        $this->rest->user_id = NULL;
        $this->rest->ignore_limits = FALSE;

        // Find the key from server or arguments
        if (($key = isset($this->_args[$api_key_variable]) ? $this->_args[$api_key_variable] : $this->input->server($key_name)))
        {
            if (!($row = $this->rest->db->where(config_item('rest_key_column'), $key)->get(config_item('rest_keys_table'))->row()))
            {
                return FALSE;
            }

            $this->rest->key = $row->{config_item('rest_key_column')};

            isset($row->user_id) && $this->rest->user_id = $row->user_id;
            isset($row->level) && $this->rest->level = $row->level;
            isset($row->ignore_limits) && $this->rest->ignore_limits = $row->ignore_limits;

            $this->_apiuser = $row;

            /*
             * If "is private key" is enabled, compare the ip address with the list
             * of valid ip addresses stored in the database.
             */
            if (!empty($row->is_private_key))
            {
                // Check for a list of valid ip addresses
                if (isset($row->ip_addresses))
                {
                    // multiple ip addresses must be separated using a comma, explode and loop
                    $list_ip_addresses = explode(',', $row->ip_addresses);
                    $found_address = FALSE;

                    foreach ($list_ip_addresses as $ip_address)
                    {
                        if ($this->input->ip_address() == trim($ip_address))
                        {
                            // there is a match, set the the value to TRUE and break out of the loop
                            $found_address = TRUE;
                            break;
                        }
                    }

                    return $found_address;
                }
                else
                {
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
     * What language do they want it in?
     *
     * @access protected
     * @return NULL|string The language code.
     */
    protected function _detect_lang()
    {
        if (!$lang = $this->input->server('HTTP_ACCEPT_LANGUAGE'))
        {
            return NULL;
        }

        // They might have sent a few, make it an array
        if (strpos($lang, ',') !== FALSE)
        {
            $langs = explode(',', $lang);

            $return_langs = [];
            foreach ($langs as $lang)
            {
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
     * Add the request to the log table
     *
     * @access protected
     *
     * @param bool $authorized TRUE the user is authorized; otherwise, FALSE
     *
     * @return bool TRUE the data was inserted; otherwise, FALSE
     */
    protected function _log_request($authorized = FALSE)
    {
        // Insert the request into the log table
        $isInserted = $this->rest->db
            ->insert(
                config_item('rest_logs_table'), [
                'uri' => $this->uri->uri_string(),
                'method' => $this->request->method,
                'params' => $this->_args ? (config_item('rest_logs_json_params') === TRUE ? json_encode($this->_args) : serialize($this->_args)) : NULL,
                'api_key' => isset($this->rest->key) ? $this->rest->key : '',
                'ip_address' => $this->input->ip_address(),
                'time' => now(), // Used to be: function_exists('now') ? now() : time()
                'authorized' => $authorized
            ]);

        // Get the last insert id to update at a later stage of the request
        $this->_insert_id = $this->rest->db->insert_id();

        return $isInserted;
    }

    /**
     * Check if the requests to a controller method exceed a limit
     *
     * @access protected
     *
     * @param  string $controller_method The method being called
     *
     * @return bool TRUE the call limit is below the threshold; otherwise, FALSE
     */
    protected function _check_limit($controller_method)
    {
        // They are special, or it might not even have a limit
        if (!empty($this->rest->ignore_limits) || !isset($this->methods[$controller_method]['limit']))
        {
            // Everything is fine
            return TRUE;
        }

        // How many times can you get to this method in an hour?
        $limit = $this->methods[$controller_method]['limit'];

        // Get data about a keys' usage and limit to one row
        $result = $this->rest->db
            ->where('uri', $this->uri->uri_string())
            ->where('api_key', $this->rest->key)
            ->get(config_item('rest_limits_table'))
            ->row();

        // No calls have been made for this key
        if (!$result)
        {
            // Create a new row for the following key
            $this->rest->db->insert(config_item('rest_limits_table'), [
                'uri' => $this->uri->uri_string(),
                'api_key' => isset($this->rest->key) ? $this->rest->key : '',
                'count' => 1,
                'hour_started' => time()
            ]);
        }

        // Been an hour since they called
        elseif ($result->hour_started < (time() - 3600))
        {
            // Reset the started period and count
            $this->rest->db
                ->where('uri', $this->uri->uri_string())
                ->where('api_key', isset($this->rest->key) ? $this->rest->key : '')
                ->set('hour_started', time())
                ->set('count', 1)
                ->update(config_item('rest_limits_table'));
        }

        // They have called within the hour, so lets update
        else
        {
            // The limit has been exceeded
            if ($result->count >= $limit)
            {
                return FALSE;
            }

            // Increase the count by one
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
     * Check if there is a specific auth type set for the current class/method
     * being called.
     *
     * @access protected
     * @return bool
     */
    protected function _auth_override_check()
    {
        // Assign the class/method auth type override array from the config
        $this->overrides_array = $this->config->item('auth_override_class_method');

        // Check to see if the override array is even populated, otherwise return FALSE
        if (empty($this->overrides_array))
        {
            return FALSE;
        }

        // check for wildcard flag for rules for classes
        if (!empty($this->overrides_array[$this->router->class]['*'])) // Check for class overrides
        {
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
        if (empty($this->overrides_array[$this->router->class][$this->router->method]))
        {
            return FALSE;
        }

        // None auth override found, prepare nothing but send back a TRUE override flag
        if ($this->overrides_array[$this->router->class][$this->router->method] == 'none')
        {
            return TRUE;
        }

        // Basic auth override found, prepare basic
        if ($this->overrides_array[$this->router->class][$this->router->method] == 'basic')
        {
            $this->_prepare_basic_auth();

            return TRUE;
        }

        // Digest auth override found, prepare digest
        if ($this->overrides_array[$this->router->class][$this->router->method] == 'digest')
        {
            $this->_prepare_digest_auth();

            return TRUE;
        }

        // Whitelist auth override found, check client's ip against config whitelist
        if ($this->overrides_array[$this->router->class][$this->router->method] == 'whitelist')
        {
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
        if (is_cli())
        {
            $args = $_SERVER['argv'];
            unset($args[0]);
            $_SERVER['QUERY_STRING'] = $_SERVER['PATH_INFO'] = $_SERVER['REQUEST_URI'] = '/' . implode('/', $args) . '/';
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
        if ($this->request->format)
        {
            $this->request->body = file_get_contents('php://input');
        }

        // If no file type is provided, this is probably just arguments
        else
        {
            if ($this->input->method() === 'put')
            {
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
        if ($this->request->format)
        {
            $this->request->body = file_get_contents('php://input');
        }

        // If no file type is provided, this is probably just arguments
        else
        {
            if ($this->input->method() === 'patch')
            {
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
        if ($this->input->method() === 'delete')
        {
            $this->_delete_args = $this->input->input_stream();
        }
    }

    // INPUT FUNCTION --------------------------------------------------------------

    /**
     * Retrieve a value from a GET request
     *
     * @access public
     *
     * @param NULL $key Key to retrieve from the GET request
     * If NULL an array of arguments is returned
     * @param NULL $xss_clean Whether to apply XSS filtering
     *
     * @return array|string|FALSE Value from the GET request; otherwise, FALSE
     */
    public function get($key = NULL, $xss_clean = NULL)
    {
        if ($key === NULL)
        {
            return $this->_get_args;
        }

        return array_key_exists($key, $this->_get_args) ? $this->_xss_clean($this->_get_args[$key], $xss_clean) : FALSE;
    }

    /**
     * Retrieve a value from a OPTIONS request
     *
     * @access public
     *
     * @param NULL $key Key to retrieve from the OPTIONS request.
     * If NULL an array of arguments is returned
     * @param NULL $xss_clean Whether to apply XSS filtering
     *
     * @return array|string|FALSE Value from the OPTIONS request; otherwise, FALSE
     */
    public function options($key = NULL, $xss_clean = NULL)
    {
        if ($key === NULL)
        {
            return $this->_options_args;
        }

        return array_key_exists($key, $this->_options_args) ? $this->_xss_clean($this->_options_args[$key], $xss_clean) : FALSE;
    }

    /**
     * Retrieve a value from a HEAD request
     *
     * @access public
     *
     * @param NULL $key Key to retrieve from the HEAD request
     * If NULL an array of arguments is returned
     * @param NULL $xss_clean Whether to apply XSS filtering
     *
     * @return array|string|FALSE Value from the HEAD request; otherwise, FALSE
     */
    public function head($key = NULL, $xss_clean = NULL)
    {
        if ($key === NULL)
        {
            return $this->head_args;
        }

        return array_key_exists($key, $this->head_args) ? $this->_xss_clean($this->head_args[$key], $xss_clean) : FALSE;
    }

    /**
     * Retrieve a value from a POST request
     *
     * @access public
     *
     * @param NULL $key Key to retrieve from the POST request
     * If NULL an array of arguments is returned
     * @param NULL $xss_clean Whether to apply XSS filtering
     *
     * @return array|string|FALSE Value from the POST request; otherwise, FALSE
     */
    public function post($key = NULL, $xss_clean = NULL)
    {
        if ($key === NULL)
        {
            return $this->_post_args;
        }

        return array_key_exists($key, $this->_post_args) ? $this->_xss_clean($this->_post_args[$key], $xss_clean) : FALSE;
    }

    /**
     * Retrieve a value from a PUT request
     *
     * @access public
     *
     * @param NULL $key Key to retrieve from the PUT request
     * If NULL an array of arguments is returned
     * @param NULL $xss_clean Whether to apply XSS filtering
     *
     * @return array|string|FALSE Value from the PUT request; otherwise, FALSE
     */
    public function put($key = NULL, $xss_clean = NULL)
    {
        if ($key === NULL)
        {
            return $this->_put_args;
        }

        return array_key_exists($key, $this->_put_args) ? $this->_xss_clean($this->_put_args[$key], $xss_clean) : FALSE;
    }

    /**
     * Retrieve a value from a DELETE request
     *
     * @access public
     *
     * @param NULL $key Key to retrieve from the DELETE request
     * If NULL an array of arguments is returned
     * @param NULL $xss_clean Whether to apply XSS filtering
     *
     * @return array|string|FALSE Value from the DELETE request; otherwise, FALSE
     */
    public function delete($key = NULL, $xss_clean = NULL)
    {
        if ($key === NULL)
        {
            return $this->_delete_args;
        }

        return array_key_exists($key, $this->_delete_args) ? $this->_xss_clean($this->_delete_args[$key], $xss_clean) : FALSE;
    }

    /**
     * Retrieve a value from a PATCH request
     *
     * @access public
     *
     * @param NULL $key Key to retrieve from the PATCH request
     * If NULL an array of arguments is returned
     * @param NULL $xss_clean Whether to apply XSS filtering
     *
     * @return array|string|FALSE Value from the PATCH request; otherwise, FALSE
     */
    public function patch($key = NULL, $xss_clean = NULL)
    {
        if ($key === NULL)
        {
            return $this->_patch_args;
        }

        return array_key_exists($key, $this->_patch_args) ? $this->_xss_clean($this->_patch_args[$key], $xss_clean) : FALSE;
    }

    /**
     * Sanitizes data so that Cross Site Scripting Hacks can be
     * prevented.
     *
     * @access protected
     *
     * @param  string $value Input data
     * @param  bool $xss_clean Whether to apply XSS filtering
     *
     * @return string
     */
    protected function _xss_clean($value, $xss_clean)
    {
        is_bool($xss_clean) OR $xss_clean = $this->_enable_xss;

        return $xss_clean === TRUE ? $this->security->xss_clean($value) : $value;
    }

    /**
     * Retrieve the validation errors
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
     *
     * @param  string $username The username to validate
     * @param  string $password The password to validate
     *
     * @return bool
     */
    protected function _perform_ldap_auth($username = '', $password = NULL)
    {
        if (empty($username))
        {
            log_message('debug', 'LDAP Auth: failure, empty username');

            return FALSE;
        }

        log_message('debug', 'LDAP Auth: Loading Config');

        $this->config->load('ldap.php', TRUE);

        $ldap = [
            'timeout' => $this->config->item('timeout', 'ldap'),
            'host' => $this->config->item('server', 'ldap'),
            'port' => $this->config->item('port', 'ldap'),
            'rdn' => $this->config->item('binduser', 'ldap'),
            'pass' => $this->config->item('bindpw', 'ldap'),
            'basedn' => $this->config->item('basedn', 'ldap'),
        ];

        log_message('debug', 'LDAP Auth: Connect to ' . (isset($ldaphost) ? $ldaphost : '[ldap not configured]'));

        // Appears to be unused
        $ldapconfig['authrealm'] = $this->config->item('domain', 'ldap');

        // connect to ldap server
        $ldapconn = ldap_connect($ldap['host'], $ldap['port']);

        if ($ldapconn)
        {

            log_message('debug', 'Setting timeout to ' . $ldap['timeout'] . ' seconds');

            ldap_set_option($ldapconn, LDAP_OPT_NETWORK_TIMEOUT, $ldap['timeout']);

            log_message('debug', 'LDAP Auth: Binding to ' . $ldap['host'] . ' with dn ' . $ldap['rdn']);

            // binding to ldap server
            $ldapbind = ldap_bind($ldapconn, $ldap['rdn'], $ldap['pass']);

            // verify binding
            if ($ldapbind)
            {
                log_message('debug', 'LDAP Auth: bind successful');
            }
            else
            {
                log_message('error', 'LDAP Auth: bind unsuccessful');

                return FALSE;
            }

        }

        // search for user
        if (($res_id = ldap_search($ldapconn, $ldap['basedn'], "uid=$username")) == FALSE)
        {
            log_message('error', 'LDAP Auth: User ' . $username . ' not found in search');

            return FALSE;
        }

        if (ldap_count_entries($ldapconn, $res_id) != 1)
        {
            log_message('error', 'LDAP Auth: failure, username ' . $username . 'found more than once');

            return FALSE;
        }

        if (($entry_id = ldap_first_entry($ldapconn, $res_id)) == FALSE)
        {
            log_message('error', 'LDAP Auth: failure, entry of searchresult could not be fetched');

            return FALSE;
        }

        if (($user_dn = ldap_get_dn($ldapconn, $entry_id)) == FALSE)
        {
            log_message('error', 'LDAP Auth: failure, user-dn could not be fetched');

            return FALSE;
        }

        // User found, could not authenticate as user
        if (($link_id = ldap_bind($ldapconn, $user_dn, $password)) == FALSE)
        {
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
     *
     * @param  string $username The username to validate
     * @param  string $password The password to validate
     *
     * @return bool
     */
    protected function _perform_library_auth($username = '', $password = NULL)
    {
        if (empty($username))
        {
            log_message('error', 'Library Auth: failure, empty username');

            return FALSE;
        }

        $auth_library_class = strtolower($this->config->item('auth_library_class'));
        $auth_library_function = strtolower($this->config->item('auth_library_function'));

        if (empty($auth_library_class))
        {
            log_message('debug', 'Library Auth: failure, empty auth_library_class');

            return FALSE;
        }

        if (empty($auth_library_function))
        {
            log_message('debug', 'Library Auth: failure, empty auth_library_function');

            return FALSE;
        }

        if (!is_callable([$auth_library_class, $auth_library_function]))
        {
            $this->load->library($auth_library_class);
        }

        return $this->{$auth_library_class}->$auth_library_function($username, $password);
    }

    /**
     * Check if the user is logged in
     *
     * @access protected
     *
     * @param  string $username The user's name
     * @param  bool|string $password The user's password
     *
     * @return bool
     */
    protected function _check_login($username = NULL, $password = FALSE)
    {
        if (empty($username))
        {
            return FALSE;
        }

        $auth_source = strtolower($this->config->item('auth_source'));
        $rest_auth = strtolower($this->config->item('rest_auth'));
        $valid_logins = $this->config->item('rest_valid_logins');

        if (!$this->config->item('auth_source') && $rest_auth === 'digest')
        {
            // For digest we do not have a password passed as argument
            return md5($username . ':' . $this->config->item('rest_realm') . ':' . (isset($valid_logins[$username]) ? $valid_logins[$username] : ''));
        }

        if ($password === FALSE)
        {
            return FALSE;
        }

        if ($auth_source === 'ldap')
        {
            log_message('debug', "Performing LDAP authentication for $username");

            return $this->_perform_ldap_auth($username, $password);
        }

        if ($auth_source === 'library')
        {
            log_message('debug', "Performing Library authentication for $username");

            return $this->_perform_library_auth($username, $password);
        }

        if (array_key_exists($username, $valid_logins) === FALSE)
        {
            return FALSE;
        }

        if ($valid_logins[$username] != $password)
        {
            return FALSE;
        }

        return TRUE;
    }

    /**
     * Check to see if the user is logged in with a PHP session key
     *
     * @access protected
     */
    protected function _check_php_session()
    {
        // Get the auth_source config item
        $key = $this->config->item('auth_source');

        // If falsy, then the user isn't logged in
        if (!$this->session->userdata($key))
        {
            // Display an error response
            $this->response(
                [
                    config_item('rest_status_field_name') => FALSE,
                    config_item('rest_message_field_name') => 'Not Authorized'
                ], 401);
        }
    }

    /**
     * Prepares for basic authentication
     *
     * @access protected
     */
    protected function _prepare_basic_auth()
    {
        // If whitelist is enabled it has the first chance to kick them out
        if (config_item('rest_ip_whitelist_enabled'))
        {
            $this->_check_whitelist_auth();
        }

        // Returns NULL if the SERVER variables PHP_AUTH_USER and HTTP_AUTHENTICATION don't exist
        $username = $this->input->server('PHP_AUTH_USER');
        $http_auth = $this->input->server('HTTP_AUTHENTICATION');

        $password = NULL;
        if ($username !== NULL)
        {
            $password = $this->input->server('PHP_AUTH_PW');
        }
        elseif ($http_auth !== NULL)
        {
            // If the authentication header is set as basic, then extract the username and password from
            // HTTP_AUTHORIZATION e.g. my_username:my_password. This is passed in the .htaccess file
            if (strpos(strtolower($http_auth), 'basic') === 0)
            {
                // Search online for HTTP_AUTHORIZATION workaround to explain what this is doing
                list($username, $password) = explode(':', base64_decode(substr($this->input->server('HTTP_AUTHORIZATION'), 6)));
            }
        }

        // Check if the user is logged into the system
        if ($this->_check_login($username, $password) === FALSE)
        {
            $this->_force_login();
        }
    }

    /**
     * Prepares for digest authentication
     *
     * @access protected
     */
    protected function _prepare_digest_auth()
    {
        // If whitelist is enabled it has the first chance to kick them out
        if (config_item('rest_ip_whitelist_enabled'))
        {
            $this->_check_whitelist_auth();
        }

        // We need to test which server authentication variable to use,
        // because the PHP ISAPI module in IIS acts different from CGI
        $digest_string = '';
        if ($this->input->server('PHP_AUTH_DIGEST'))
        {
            $digest_string = $this->input->server('PHP_AUTH_DIGEST');
        }
        elseif ($this->input->server('HTTP_AUTHORIZATION'))
        {
            $digest_string = $this->input->server('HTTP_AUTHORIZATION');
        }

        $uniqueId = uniqid();

        // The $_SESSION['error_prompted'] variable is used to ask the password
        // again if none given or if the user enters wrong auth information
        if (empty($digest_string))
        {
            $this->_force_login($uniqueId);
        }

        // We need to retrieve authentication data from the $digest_string variable
        $matches = [];
        preg_match_all('@(username|nonce|uri|nc|cnonce|qop|response)=[\'"]?([^\'",]+)@', $digest_string, $matches);
        $digest = (empty($matches[1]) || empty($matches[2])) ? [] : array_combine($matches[1], $matches[2]);

        // For digest authentication the library function should return already stored md5(username:restrealm:password) for that username @see rest.php::auth_library_function config
        $A1 = $this->_check_login($digest['username'], TRUE);
        if (array_key_exists('username', $digest) === FALSE || $A1 === FALSE)
        {
            $this->_force_login($uniqueId);
        }

        $A2 = md5(strtoupper($this->request->method) . ':' . $digest['uri']);
        $valid_response = md5($A1 . ':' . $digest['nonce'] . ':' . $digest['nc'] . ':' . $digest['cnonce'] . ':' . $digest['qop'] . ':' . $A2);

        // Check if the string don't compare (case-insensitive)
        if (strcasecmp($digest['response'], $valid_response) !== 0)
        {
            // Display an error response
            $this->response(
                [
                    config_item('rest_status_field_name') => 0,
                    config_item('rest_message_field_name') => 'Invalid credentials'
                ], 401);
        }
    }

    /**
     * Checks if the client's ip is in the 'rest_ip_blacklist' config and generates a 401 response
     *
     * @access protected
     */
    protected function _check_blacklist_auth()
    {
        // Match an ip address in a blacklist e.g. 127.0.0.0, 0.0.0.0
        $pattern = sprintf('/(?:,\s*|^)\Q%s\E(?=,\s*|$)/m', $this->input->ip_address());

        // Returns 1, 0 or FALSE (on error only). Therefore implicitly convert 1 to TRUE
        if (preg_match($pattern, config_item('rest_ip_blacklist')))
        {
            // Display an error response
            $this->response(
                [
                    'status' => FALSE,
                    'error' => 'IP Denied'
                ],
                401);
        }
    }

    /**
     * Check if the client's ip is in the 'rest_ip_whitelist' config and generates a 401 response
     *
     * @access protected
     */
    protected function _check_whitelist_auth()
    {
        $whitelist = explode(',', config_item('rest_ip_whitelist'));

        array_push($whitelist, '127.0.0.1', '0.0.0.0');

        foreach ($whitelist AS &$ip)
        {
            $ip = trim($ip);
        }

        if (!in_array($this->input->ip_address(), $whitelist))
        {
            $this->response([config_item('rest_status_field_name') => FALSE, config_item('rest_message_field_name') => 'IP not authorized'], 401);
        }
    }

    /**
     * Force logging in by setting the WWW-Authenticate header
     *
     * @access protected
     *
     * @param string $nonce A server-specified data string which should be uniquely generated
     * each time
     */
    protected function _force_login($nonce = '')
    {
        $restAuth = $this->config->item('rest_auth');
        $restRealm = $this->config->item('rest_realm');
        if (strtolower($restAuth) === 'basic')
        {
            // See http://tools.ietf.org/html/rfc2617#page-5
            header('WWW-Authenticate: Basic realm="' . $restRealm . '"');
        }
        elseif (strtolower($restAuth) === 'digest')
        {
            // See http://tools.ietf.org/html/rfc2617#page-18
            header(
                'WWW-Authenticate: Digest realm="' . $restRealm
                . '", qop="auth", nonce="' . $nonce
                . '", opaque="' . md5($restRealm) . '"');
        }

        // Display an error response
        $this->response(
            [
                config_item('rest_status_field_name') => FALSE,
                config_item('rest_message_field_name') => 'Not authorized'
            ], 401);
    }

    /**
     * Updates the log table with the total access time
     *
     * @access protected
     * @author Chris Kacerguis
     *
     * @return bool TRUE log table updated; otherwise, FALSE
     */
    protected function _log_access_time()
    {
        $payload['rtime'] = $this->_end_rtime - $this->_start_rtime;

        return $this->rest->db
            ->update(
                config_item('rest_logs_table'), $payload, [
                'id' => $this->_insert_id
            ]);
    }

    /**
     * Updates the log table with HTTP response code
     *
     * @access protected
     * @author Justin Chen
     *
     * @param $http_code int HTTP status code
     *
     * @return bool TRUE log table updated; otherwise, FALSE
     */
    protected function _log_response_code($http_code)
    {
        $payload['response_code'] = $http_code;

        return $this->rest->db->update(
            config_item('rest_logs_table'), $payload, [
            'id' => $this->_insert_id
        ]);
    }

    /**
     * Check to see if the API key has access to the controller and methods
     *
     * @access protected
     * @return bool TRUE the API key has access; otherwise, FALSE
     */
    protected function _check_access()
    {
        // If we don't want to check access, just return TRUE
        if (config_item('rest_enable_access') === FALSE)
        {
            return TRUE;
        }

        // Fetch controller based on path and controller name
        $controller = implode(
            '/', [
            $this->router->directory,
            $this->router->class
        ]);

        // Remove any double slashes for safety
        $controller = str_replace('//', '/', $controller);

        // Query the access table and get the number of results
        return $this->rest->db
                   ->where('key', $this->rest->key)
                   ->where('controller', $controller)
                   ->get(config_item('rest_access_table'))
                   ->num_rows() > 0;
    }

}
