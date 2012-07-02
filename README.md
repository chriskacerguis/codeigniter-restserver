# CodeIgniter Rest Server

A fully RESTful server implementation for CodeIgniter using one library, one
config file and one controller.

## Sponsored by: Coding Futures

## Requirements

1. PHP 5.2+
2. CodeIgniter 2.1.0 to 3.0-dev

_Note: for 1.7.x support download v2.2 from Downloads tab_

## Installation

Drag and drop the **application/libraries/Format.php** and **application/libraries/REST_Controller.php** files into your application's directories. Either autoload the `REST_Controller` class or `require_once` it at the top of your controllers to load it into the scope.

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

Alternatively (and recommend) is using the HTTP `Accept` header, which is built for this purpose:

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

## Other Documentation / Tutorials

* [NetTuts: Working with RESTful Services in CodeIgniter](http://net.tutsplus.com/tutorials/php/working-with-restful-services-in-codeigniter-2/)

## Change Log

### 2.6.0

* Added loads of PHPDoc comments.
* Response where method doesn't exist is now "HTTP 405 Method Not Allowed", not "HTTP 404 Not Found".
* Compatable with PHP 5.4.
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

## Donations

If my REST Server has helped you out, or you'd like me to do some custom work on it, [please sponsor me](http://pledgie.com/campaigns/8328)
so I can keep working on this and other CodeIgniter projects for you all.