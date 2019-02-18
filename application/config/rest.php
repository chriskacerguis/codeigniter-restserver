<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| HTTP protocol
|--------------------------------------------------------------------------
|
| Set to force the use of HTTPS for REST API calls
|
*/
$config['force_https'] = FALSE;

/*
|--------------------------------------------------------------------------
| REST Output Format
|--------------------------------------------------------------------------
|
| The default format of the response
|
| 'json':       Uses json_encode(). Note: If a GET query string
|               called 'callback' is passed, then jsonp will be returned
| 'xml':        Uses simplexml_load_string()
|
*/
$config['rest_format'] = 'json';

/*
|--------------------------------------------------------------------------
| REST Status Field Name
|--------------------------------------------------------------------------
|
| The field name for the status inside the response
|
*/
$config['rest_status_field_name'] = 'status';

/*
|--------------------------------------------------------------------------
| REST Message Field Name
|--------------------------------------------------------------------------
|
| The field name for the message inside the response
|
*/
$config['rest_message_field_name'] = 'error';


/*
|--------------------------------------------------------------------------
| REST Realm
|--------------------------------------------------------------------------
|
| Name of the password protected REST API displayed on login dialogs
|
| e.g: My Secret REST API
|
*/
$config['rest_realm'] = 'REST API';

/*
|--------------------------------------------------------------------------
| REST Login
|--------------------------------------------------------------------------
|
| Set to specify the REST API requires to be logged in
|
| FALSE     No login required
| 'basic'   Unsecured login
| 'session' Check for a PHP session variable. See 'auth_source' to set the
|           authorization key
| 'apikey'  Use API keys as login auth
|
*/
$config['rest_auth'] = FALSE;


/*
|--------------------------------------------------------------------------
| REST Login Usernames
|--------------------------------------------------------------------------
|
| Array of usernames and passwords for basic login
|
*/
$config['rest_valid_logins'] = ['admin' => '1234'];

/*
|--------------------------------------------------------------------------
| REST IP White-list
|--------------------------------------------------------------------------
|
| Limit connections to your REST server.  If this is set, ONLY these 
| IP will be allowed to connect.
|
| See https://github.com/Jalle19/php-whitelist-check for all format
| format options.
| 
| Set to FALSE if you want to allow all IPs
|
| e.g: '123.456.789.0, 987.654.32.1'
|
|
*/
$config['rest_ip_whitelist'] = FALSE;

/*
|--------------------------------------------------------------------------
| REST IP Blacklist
|--------------------------------------------------------------------------
|
| Prevent connections from the following IP addresses
|
| See https://github.com/Jalle19/php-whitelist-check for all format
| format options.
|
| Set to FALSE if you want to allow all IPs
|
*/
$config['rest_ip_blacklist'] = FALSE;

// todo pull from the xbl / blacklist things


/*
|--------------------------------------------------------------------------
| REST API Limits method
|--------------------------------------------------------------------------
|
| Specify the method used to limit the API calls, if FALSE does not check
|
| Available methods are :
| $config['rest_limits_method'] = 'ip'; // Put a limit per ip address
| $config['rest_limits_method'] = 'apikey'; // Put a limit per api key
|
*/
$config['rest_limit_on'] = FALSE;

/*
|--------------------------------------------------------------------------
| REST Enable Logging
|--------------------------------------------------------------------------
|
| When set to TRUE, the REST API will log actions based on the column names 'key', 'date',
| 'time' and 'ip_address'. This is a general rule that can be overridden in the
| $this->method array for each controller
|
| Default table schema:
|   CREATE TABLE `logs` (
|       `id` INT(11) NOT NULL AUTO_INCREMENT,
|       `uri` VARCHAR(255) NOT NULL,
|       `method` VARCHAR(6) NOT NULL,
|       `params` TEXT DEFAULT NULL,
|       `api_key` VARCHAR(40) NOT NULL,
|       `ip_address` VARCHAR(45) NOT NULL,
|       `time` INT(11) NOT NULL,
|       `rtime` FLOAT DEFAULT NULL,
|       `authorized` VARCHAR(1) NOT NULL,
|       `response_code` smallint(3) DEFAULT '0',
|       PRIMARY KEY (`id`)
|   ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
|
*/
$config['rest_enable_logging'] = FALSE;

/*
|--------------------------------------------------------------------------
| REST Language File
|--------------------------------------------------------------------------
|
| Language file to load from the language directory
|
*/
$config['rest_language'] = 'english';

/*
|--------------------------------------------------------------------------
| CORS Check
|--------------------------------------------------------------------------
|
| Set to TRUE to enable Cross-Origin Resource Sharing (CORS). Useful if you
| are hosting your API on a different domain from the application that
| will access it through a browser
|
*/
$config['check_cors'] = FALSE;

/*
|--------------------------------------------------------------------------
| CORS Allowable Headers
|--------------------------------------------------------------------------
|
| If using CORS checks, set the allowable headers here
|
*/
$config['allowed_cors_headers'] = [
  'Origin',
  'X-Requested-With',
  'Content-Type',
  'Accept',
  'Access-Control-Request-Method'
];

/*
|--------------------------------------------------------------------------
| CORS Allowable Methods
|--------------------------------------------------------------------------
|
| If using CORS checks, you can set the methods you want to be allowed
|
*/
$config['allowed_cors_methods'] = [
  'GET',
  'POST',
  'OPTIONS',
  'PUT',
  'PATCH',
  'DELETE'
];

/*
|--------------------------------------------------------------------------
| CORS Allow Any Domain
|--------------------------------------------------------------------------
|
| Set to TRUE to enable Cross-Origin Resource Sharing (CORS) from any
| source domain
|
*/
$config['allow_any_cors_domain'] = FALSE;

/*
|--------------------------------------------------------------------------
| CORS Allowable Domains
|--------------------------------------------------------------------------
|
| Used if $config['check_cors'] is set to TRUE and $config['allow_any_cors_domain']
| is set to FALSE. Set all the allowable domains within the array
|
| e.g. $config['allowed_origins'] = ['http://www.example.com', 'https://spa.example.com']
|
*/
$config['allowed_cors_origins'] = [];

/*
|--------------------------------------------------------------------------
| CORS Forced Headers
|--------------------------------------------------------------------------
|
| If using CORS checks, always include the headers and values specified here 
| in the OPTIONS client preflight.
| Example:
| $config['forced_cors_headers'] = [
|   'Access-Control-Allow-Credentials' => 'true'
| ];
|
| Added because of how Sencha Ext JS framework requires the header
| Access-Control-Allow-Credentials to be set to true to allow the use of
| credentials in the REST Proxy. 
| See documentation here:
| http://docs.sencha.com/extjs/6.5.2/classic/Ext.data.proxy.Rest.html#cfg-withCredentials
|
*/
$config['forced_cors_headers'] = [];

/*
|--------------------------------------------------------------------------
| XSS Cleaning
|--------------------------------------------------------------------------
|
| Enable / disable XSS cleaning
|
*/
$config['rest_xss_clean'] = TRUE;