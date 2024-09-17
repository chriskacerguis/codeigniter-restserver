# CodeIgniter RestServer

[![StyleCI](https://github.styleci.io/repos/230589/shield?branch=master)](https://github.styleci.io/repos/230589)

A fully RESTful server implementation for CodeIgniter using one library, one config file and one controller.

## Important!!

CodeIgniter 4 includes REST support out of the box and therefore does not require the RestServer.

See the documentation here: [RESTful Resource Handling](https://codeigniter4.github.io/userguide/incoming/restful.html)

## Requirements

- PHP 7.2 or greater
- CodeIgniter 3.1.11+

## Installation

```sh
composer require chriskacerguis/codeigniter-restserver
```

## Usage

CodeIgniter Rest Server is available on [Packagist](https://packagist.org/packages/chriskacerguis/codeigniter-restserver) (using semantic versioning), and installation via composer is the recommended way to install Codeigniter Rest Server. Just add this line to your `composer.json` file:

```json
"chriskacerguis/codeigniter-restserver": "^3.1"
```

or run

```sh
composer require chriskacerguis/codeigniter-restserver
```

Note that you will need to copy `rest.php` to your `config` directory (e.g. `application/config`)

Step 1: Add this to your controller (should be before any of your code)

```php
use chriskacerguis\RestServer\RestController;
```

Step 2: Extend your controller

```php
class Example extends RestController
```

## Basic GET example

Here is a basic example. This controller, which should be saved as `Api.php`, can be called in two ways:

* `http://domain/api/users/` will return the list of all users
* `http://domain/api/users/id/1` will only return information about the user with id = 1

```php
<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use chriskacerguis\RestServer\RestController;

class Api extends RestController {

    function __construct()
    {
        // Construct the parent class
        parent::__construct();
    }

    public function users_get()
    {
        // Users from a data store e.g. database
        $users = [
            ['id' => 0, 'name' => 'John', 'email' => 'john@example.com'],
            ['id' => 1, 'name' => 'Jim', 'email' => 'jim@example.com'],
        ];

        $id = $this->get( 'id' );

        if ( $id === null )
        {
            // Check if the users data store contains users
            if ( $users )
            {
                // Set the response and exit
                $this->response( $users, 200 );
            }
            else
            {
                // Set the response and exit
                $this->response( [
                    'status' => false,
                    'message' => 'No users were found'
                ], 404 );
            }
        }
        else
        {
            if ( array_key_exists( $id, $users ) )
            {
                $this->response( $users[$id], 200 );
            }
            else
            {
                $this->response( [
                    'status' => false,
                    'message' => 'No such user found'
                ], 404 );
            }
        }
    }
}
```

## Extending supported formats

If you need to be able to support more formats for replies, you can extend the
`Format` class to add the required `to_...` methods

1. Extend the `RestController` class (in `libraries/MY_REST_Controller.php`)
```php
<?php

use chriskacerguis\RestServer\RestController;

class MY_REST_Controller extends RestController
{
    public function __construct()
    {
        parent::__construct();
        // This can be the library's chriskacerguis\RestServer\Format
        // or your own custom overloaded Format class (see bellow)
        $this->format = new Format();
    }
}
```

2. Extend the `Format` class (can be created as a CodeIgniter library in `libraries/Format.php`).
Following is an example to add support for PDF output

```php
<?php

use chriskacerguis\RestServer\Format as RestServerFormat;

class Format extends RestServerFormat
{
    public function to_pdf($data = null)
    {
        if ($data === null && func_num_args() === 0) {
            $data = $this->_data;
        }

        if (is_array($data) || substr($data, 0, 4) != '%PDF') {
            $html = $this->to_html($data);

            // Use your PDF lib of choice. For example mpdf
            $mpdf = new \Mpdf\Mpdf();
            $mpdf->WriteHTML($html);
            return $mpdf->Output('', 'S');
        }

        return $data;
    }
}
```
