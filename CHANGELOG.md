Changelog:
===========

### UNRELEASED
* Added support for CodeIgniter controller's index methods (index_GET, index_POST...)
* Added exceptions handling when the method could not be found

### 2.7.2

* Added $this->query() in which query parameters can now be obtained regardless of whether a GET request is sent or not
* Added doc comments added to functions
* Added HTTP status constants e.g. REST_Controller::HTTP_OK
* Added new CSV formatting function
* Fixed numerous bug fixes
* Updated API calls limit can be based on API key, routed url or method name
* Updated documentation
* Updated examples (thanks @ivantcholakov and @lagaisse)
* Updated many functions by re-writing (thanks @softwarespot)
* Updated performance increase

### 2.7.0

* Added Blacklist IP option
* Added controller based access controls
* Added support for OPTIONS, PATCH, and HEAD (from boh1996)
* Added logging of the time it takes for a request (rtime column in DB)
* Changed DB schemas to use InnoDB, not MyISAM
* Updated Readme to reflect new developer (Chris Kacerguis)

### 2.6.2

* Update CodeIgniter files to 2.1.3
* Fixed issue #165

### 2.6.1

* Update CodeIgniter files to 2.1.2
* Log Table support for IPv6 & NULL parameters
* Abstract out the processes of firing a controller method within _remap() to an separate method
* Moved GET, POST, PUT, and DELETE parsing to separate methods, allowing them to be overridden as needed
* Small bug-fix for a PHP 5.3 strlen error
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
* Added IP white-list option.

### 2.5

* Instead of just seeing item, item, item, the singular version of the base-node will be used if possible. [Example](http://d.pr/RS46).
* Re-factored to use the Format library, which will soon be merged with CodeIgniter.
* Fixed Limit bug (limit of 5 would allow 6 requests).
* Added logging for invalid API key requests.
* Changed serialize to serialized.
