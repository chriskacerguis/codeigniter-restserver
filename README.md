# CodeIgniter RestServer

[![StyleCI](https://github.styleci.io/repos/230589/shield?branch=master)](https://github.styleci.io/repos/230589)

A fully RESTful server implementation for CodeIgniter using one library, one config file and one controller.

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
                $this->response( $users, RestController::HTTP_OK );
            }
            else
            {
                // Set the response and exit
                $this->response( [
                    'status' => false,
                    'message' => 'No users were found'
                ], RestController::HTTP_NOT_FOUND );
            }
        }
        else
        {
            if ( array_key_exists( $id, $users ) )
            {
                $this->response( $users[$id], RestController::HTTP_OK );
            }
            else
            {
                $this->response( [
                    'status' => false,
                    'message' => 'No such user found'
                ], RestController::HTTP_NOT_FOUND );
            }
        }
    }
}
```

## Basic POST example

Here is a basic post example. This controller, which should be saved as `Api.php`, or the `user_post` added to your existing `Api.php` from the `GET` example above.

* `http://domain/api/user` will add a new user to your data store

Note that for POST requests, your values are passed in the BODY of your request, unlike using GET.

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

    public function user_post()
    {
        $name = $this->post( 'name' );
        $department = $this->post( 'department' );

        if ( empty( $name ) || empty( $department ) )
        {
            // Return a bad response.
            $this->response( [
                'status' => false,
                'message' => 'Validation failed'
            ], RestController::HTTP_BAD_REQUEST );
        }

        // Save your data here.
        // $this->load->model('api_model');
        // $insert_id = $this->api_model->save_user($name, $department)
        // if ( $insert_id ) {
        //     $this->response( [
        //         'status' => true,
        //         'message' => 'User created'
        //     ], RestController::HTTP_CREATED );
        // }
    }
}
```

## Some notes:

* For POST requests, add your values in the BODY of your request, including authentication (e.g., X-API-KEY).
* For GET requests, add your values to the PARAMS, and your authentication in the header.
* Method names are automatically derived by RestServer based on the HTTP verb, for example, a GET function, called in this example, as `users` is defined as `users_get`, and the POST function, as per the example, is called as `user` and defined as `user_post`. You must not call the function with the `_post` or `_get` suffix.
