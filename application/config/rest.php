<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| REST Login
|--------------------------------------------------------------------------
|
| Is login required and if so, which type of login?
|
|	'' = no login required, 'basic' = unsecure login, 'digest' = more secure login
|
*/
$config['rest_auth'] = '';

/*
|--------------------------------------------------------------------------
| REST Realm
|--------------------------------------------------------------------------
|
| Name for the password protected REST API displayed on login dialogs
|
|	E.g: My Secret REST API
|
*/
$config['rest_realm'] = 'REST API';

/*
|--------------------------------------------------------------------------
| REST Login usernames
|--------------------------------------------------------------------------
|
| Is login required
|
|	'' = no login required, 'basic' = unsecure login, 'digest' = more secure login
|
*/
$config['rest_valid_logins'] = array('admin' => '1234');

/*
|--------------------------------------------------------------------------
| REST Cache
|--------------------------------------------------------------------------
|
| How many MINUTES should output be cached?
|
|	0 = no cache
|
*/
$config['rest_cache'] = 0;

/*
|--------------------------------------------------------------------------
| REST Ignore HTTP Accept
|--------------------------------------------------------------------------
|
| Set to TRUE to ignore the HTTP Accept and speed up each request a little.
| Only do this if you are using the $this->rest_format or /format/xml in URLs 
|
|	FALSE
|
*/
$config['rest_ignore_http_accept'] = FALSE;


/* End of file config.php */
/* Location: ./system/application/config/rest.php */