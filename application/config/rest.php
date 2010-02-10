<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

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
| REST Login usernames
|--------------------------------------------------------------------------
|
| Array of usernames and passwords for login
|
|	array('admin' => '1234')
|
*/
$config['rest_valid_logins'] = array('admin' => '1234');

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