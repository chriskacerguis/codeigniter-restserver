<?php

defined('BASEPATH') OR exit('No direct script access allowed');

// This can be removed if you use __autoload() in config.php OR use Modular Extensions
require APPPATH . '/libraries/REST_Controller.php';

/**
 * This is an example of a few basic user interaction methods you could use
 * all done with a hardcoded array
 *
 * @package         CodeIgniter
 * @subpackage      Rest Server
 * @category        Controller
 * @author          Phil Sturgeon, Chris Kacerguis
 * @license         MIT
 * @link            https://github.com/chriskacerguis/codeigniter-restserver
 */
class Example extends REST_Controller {

    function __construct()
    {
        // Construct the parent class
        parent::__construct();

        // Configure limits on our controller methods
        // Ensure you have created the 'limits' table and enabled 'limits' within application/config/rest.php
        $this->methods['user_get']['limit'] = 500; // 500 requests per hour per user/key
        $this->methods['user_post']['limit'] = 100; // 100 requests per hour per user/key
        $this->methods['user_delete']['limit'] = 50; // 50 requests per hour per user/key
    }

    public function user_get()
    {
        if (!$this->get('id'))
        {
            $this->response(NULL, REST_Controller::HTTP_BAD_REQUEST); // BAD_REQUEST (400) being the HTTP response code
        }

        // $user = $this->some_model->getSomething( $this->get('id') );
        $users = [
            1 => ['id' => 1, 'name' => 'John', 'email' => 'john@example.com', 'fact' => 'Loves coding'],
            2 => ['id' => 2, 'name' => 'Jim', 'email' => 'jim@example.com', 'fact' => 'Developed on CodeIgniter'],
            3 => ['id' => 3, 'name' => 'Jane', 'email' => 'jane@example.com', 'fact' => 'Lives in the USA', ['hobbies' => ['guitar', 'cycling']]],
        ];

        // Get the user from the array, by retrieving the id from the GET request
        $user = isset($users[$this->get('id')]) ? $users[$this->get('id')] : NULL;

        if ($user)
        {
            $this->response($user, REST_Controller::HTTP_OK); // OK (200) being the HTTP response code
        }
        else
        {
            $this->response(['error' => 'User could not be found'], REST_Controller::NOT_FOUND); // NOT_FOUND (404) being the HTTP response code
        }
    }

    public function user_post()
    {
        // $this->some_model->update_user($this->get('id'));
        $message = [
            'id' => $this->get('id'),
            'name' => $this->post('name'),
            'email' => $this->post('email'),
            'message' => 'Added a resource'
        ];

        $this->response($message, REST_Controller::HTTP_CREATED); // CREATED (201) being the HTTP response code
    }

    public function user_delete()
    {
        // $this->some_model->delete_something($this->get();
        $message = [
            'id' => $this->get('id'),
            'message' => 'Deleted the resource'
        ];

        $this->response($message, REST_Controller::HTTP_NO_CONTENT); // NO_CONTENT (204) being the HTTP response code
    }

    public function users_get()
    {
        // $users = $this->some_model->get_something($this->get('limit'));
        $users = [
            ['id' => 1, 'name' => 'John', 'email' => 'john@example.com', 'fact' => 'Loves coding'],
            ['id' => 2, 'name' => 'Jim', 'email' => 'jim@example.com', 'fact' => 'Developed on CodeIgniter'],
            3 => ['id' => 3, 'name' => 'Jane', 'email' => 'jane@example.com', 'fact' => 'Lives in the USA', ['hobbies' => ['guitar', 'cycling']]],
        ];

        if ($users)
        {
            $this->response($users, REST_Controller::HTTP_OK); // OK (200) being the HTTP response code
        }

        else
        {
            $this->response(['error' => 'Couldn\'t find any users!'], REST_Controller::NOT_FOUND); // NOT_FOUND (404) being the HTTP response code
        }
    }

    public function send_post()
    {
        var_dump($this->request->body);
    }

    public function send_put()
    {
        var_dump($this->put('foo'));
    }
}
