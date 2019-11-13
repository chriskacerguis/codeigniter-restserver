<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Example GET controller
|--------------------------------------------------------------------------
|
| Place this file in the application/controllers/api directory
|
| Add this to your routes.php config file:
| $route['api/get/users/(:num)'] = 'api/example/get/id/$1';
|
*/

class Example extends REST_Controller
{
    public function __construct()
    {
        // Construct the parent class
        parent::__construct();
    }

    public function users_get()
    {
        // Users from a data store e.g. database
        $users = [
            ['id' => 1, 'name' => 'John', 'email' => 'john@example.com'],
            ['id' => 2, 'name' => 'Jim', 'email' => 'jim@example.com'],
        ];

        $id = $this->get('id');

        if ($id === null) {
            // Check if the users data store contains users
            if ($users) {
                // Set the response and exit
                $this->response($users, 200);
            } else {
                // Set the response and exit
                $this->response([
                    'status'  => false,
                    'message' => 'No users were found',
                ], 404);
            }
        }
    }
}
