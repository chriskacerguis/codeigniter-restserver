# CodeIgniter Rest Server

A fully RESTful server implementation for CodeIgniter using one library, one
config file and one controller.

## Sponsored by: Coding Futures

## Requirements

1. PHP 5.2+
2. CodeIgniter 2.1.0 to 3.0-dev

Note: for 1.7.x support download v2.2 from Downloads tab

## Usage

Coming soon. Take a look at application/controllers/api/example.php for
hints until the default controller demo is built and ready.

I haven't got around to writing any documentation specifically for this project
but you can read my NetTuts article which covers it's usage along with the REST Client lib.

[NetTuts: Working with RESTful Services in CodeIgniter](http://net.tutsplus.com/tutorials/php/working-with-restful-services-in-codeigniter-2/)

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