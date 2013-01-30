# CodeIgniter Rest Server

A fully RESTful server implementation for CodeIgniter using one library, one
config file and one controller.

## Requirements

1. PHP 5.2 or greater
2. CodeIgniter 2.1.0 to 3.0-dev

_Note: for 1.7.x support download v2.2 from Downloads tab_

## Installation

Drag and drop the **application/libraries/Format.php** and **application/libraries/REST_Controller.php** files into your application's directories. Either autoload the `REST_Controller` class or `require_once` it at the top of your controllers to load it into the scope. Additionally, copy the **rest.php** file from **application/config** in your application's configuration directory.

## Handling Requests

When your controller extends from `REST_Controller`, the method names will be appended with the HTTP method used to access the request. If you're  making an HTTP `GET` call to `/books`, for instance, it would call a `Books#index_get()` method.

This allows you to implement a RESTful interface easily:

	class Books extends REST_Controller
	{
		public function index_get()
		{
			// Display all books
		}

		public function index_post()
		{
			// Create a new book
		}
	}

`REST_Controller` also supports `PUT` and `DELETE` methods, allowing you to support a truly RESTful interface.

Accessing parameters is also easy. Simply use the name of the HTTP verb as a method:

	$this->get('blah'); // GET param
	$this->post('blah'); // POST param
	$this->put('blah'); // PUT param
	$this->delete('blah'); // DELETE param

## Content Types

`REST_Controller` supports a bunch of different request/response formats, including XML, JSON and serialised PHP. By default, the class will check the URL and look for a format either as an extension or as a separate segment.

This means your URLs can look like this:

	http://example.com/books.json
	http://example.com/books?format=json

This can be flaky with URI segments, so the recommend approach is using the HTTP `Accept` header:

	$ curl -H "Accept: application/json" http://example.com

Any responses you make from the class (see [responses](#responses) for more on this) will be serialised in the designated format.

## Responses

The class provides a `response()` method that allows you to return data in the user's requested response format.

Returning any object / array / string / whatever is easy:

	public function index_get()
	{
		$this->response($this->db->get('books')->result());
	}

This will automatically return an `HTTP 200 OK` response. You can specify the status code in the second parameter:

	public function index_post()
	{
		// ...create new book

		$this->response($book, 201); // Send an HTTP 201 Created
	}

If you don't specify a response code, and the data you respond with `== FALSE` (an empty array or string, for instance), the response code will automatically be set to `404 Not Found`:

	$this->response(array()); // HTTP 404 Not Found

## Multilingual Support

If your application uses language files to support multiple locales, `REST_Controller` will automatically parse the HTTP `Accept-Language` header and provide the language(s) in your actions. This information can be found in the `$this->response->lang` object:

	public function __construct()
	{
		parent::__construct();

		if (is_array($this->response->lang))
		{
			$this->load->language('application', $this->response->lang[0]);
		}
		else
		{
			$this->load->language('application', $this->response->lang);
		}
	}

## Authentication

This class also provides rudimentary support for HTTP basic authentication and/or the securer HTTP digest access authentication.

You can enable basic authentication by setting the `$config['rest_auth']` to `'basic'`. The `$config['rest_valid_logins']` directive can then be used to set the usernames and passwords able to log in to your system. The class will automatically send all the correct headers to trigger the authentication dialogue:

	$config['rest_valid_logins'] = array( 'username' => 'password', 'other_person' => 'secure123' );

Enabling digest auth is similarly easy. Configure your desired logins in the config file like above, and set `$config['rest_auth']` to `'digest'`. The class will automatically send out the headers to enable digest auth.

Both methods of authentication can be secured further by using an IP whitelist. If you enable `$config['rest_ip_whitelist_enabled']` in your config file, you can then set a list of allowed IPs.

Any client connecting to your API will be checked against the whitelisted IP array. If they're on the list, they'll be allowed access. If not, sorry, no can do hombre. The whitelist is a comma-separated string:

	$config['rest_ip_whitelist'] = '123.456.789.0, 987.654.32.1';

Your localhost IPs (`127.0.0.1` and `0.0.0.0`) are allowed by default.

## API Keys

In addition to the authentication methods above, the `REST_Controller` class also supports the use of API keys. Enabling API keys is easy. Turn it on in your **config/rest.php** file:

	$config['rest_enable_keys'] = TRUE;

You'll need to create a new database table to store and access the keys. `REST_Controller` will automatically assume you have a table that looks like this:

	CREATE TABLE `keys` (
	  `id` int(11) NOT NULL AUTO_INCREMENT,
	  `key` varchar(40) NOT NULL,
	  `level` int(2) NOT NULL,
	  `ignore_limits` tinyint(1) NOT NULL DEFAULT '0',
	  `date_created` int(11) NOT NULL,
	  PRIMARY KEY (`id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8;

The class will look for an HTTP header with the API key on each request. An invalid or missing API key will result in an `HTTP 403 Forbidden`.

By default, the HTTP will be `X-API-KEY`. This can be configured in **config/rest.php**.

	$ curl -X POST -H "X-API-KEY: some_key_here" http://example.com/books

## Other Documentation / Tutorials

* [NetTuts: Working with RESTful Services in CodeIgniter](http://net.tutsplus.com/tutorials/php/working-with-restful-services-in-codeigniter-2/)

## Change Log

### 2.6.2

* Update CodeIgniter files to 2.1.3
* Fixed issue #165

### 2.6.1

* Update CodeIgniter files to 2.1.2
* Log Table support for IPv6 & NULL parameters
* Abstract out the processes of firing a controller method within _remap() to an separate method
* Moved GET, POST, PUT, and DELETE parsing to separate methods, allowing them to be overridden as needed
* Small bugfix for a PHP 5.3 strlen error
* Fixed some PHP 5.4 warnings
* Fix for bug in Format.php's to_html() which failed to detect if $data was really a multidimensional array.
* Fix for empty node on XML output format, for false = 0, true = 1.

### 2.6.0

* Added loads of PHPDoc comments.
* Response where method doesn't exist is now "HTTP 405 Method Not Allowed", not "HTTP 404 Not Found".
* Compatible with PHP 5.4.
* Added support for gzip compression.
* Fix the apache\_request\_header function with CGI.
* Fixed up correctly .foo extensions to work when get arguments provided.
* Allows method emulation via X-HTTP-Method-Override
* Support for Backbone.emulateHTTP improved.
* Combine both URI segment and GET params instead of using one or the other
* Separate each piece of the WWW-Authenticate header for digest requests with a comma.
* Added IP whitelist option.

### 2.5

* Instead of just seeing item, item, item, the singular version of the basenode will be used if possible. [Example](http://d.pr/RS46).
* Re-factored to use the Format library, which will soon be merged with CodeIgniter.
* Fixed Limit bug (limit of 5 would allow 6 requests).
* Added logging for invalid API key requests.
* Changed serialize to serialized.
* Changed all visibility 'private' to 'protected'.
* MIME's with character encodings on the end will now work.
* Fixed PUT arguments. Once again just sending a body query string works. [Example](http://d.pr/cY0b)
* Fixed up all .foo extensions to work when no get arguments provided, and moved .html to Format library.
* Updated key.php example to use config_item('rest_keys_table') instead of hardcoded 'keys' table name.
* Updated REST_Controller to use config_item('rest_limits_table') instead of hardcoded 'limits'.

### 2.4

* Added support for UTF-8 characters in XML.
* Added JSONP as a return type.
* Loaded the Security lib before use in case it is not loaded in the application.
* Emulate the Request method for MooTools support.
* Upgraded everything to use CodeIgniter Reactor 2.0.0.
* Added the ability to set or override the Auth type per controller / method.
* Adding ability to only accept AJAX requests.

### 2.3

* Upgraded to CodeIgniter 2.0 and stopped supporting CodeIgniter 1.7.2.
* After $this->response() is called the controller will stop processing.

### 2.2

* Added config options to set table names for keys, limits and logs.
* FALSE values were coming out as empty strings in xml or rawxml mode, now they will be 0/1.
* key => FALSE can now be used to override the keys_enabled option for a specific method, and level is now optional. If no level is set it will assume the method has a level of 0.
* Fixed issue where calls to ->get('foo') would error is foo was not set. Reported by  Paul Barto.

## Contributions

This project has been funded and made possible through my clients kindly allowing me to 
open-source the functionality as I build it into their projects. I am no longer actively developing 
features for this as I no longer require it, but I will continue to maintain pull requests and try to 
fix issues as and when they are reported (within a week or two). 

Pull Requests are the best way to fix bugs or add features. I know loads of you use this, so please 
contribute if you have improvements to be made and I'll keep releasing versions over time.
