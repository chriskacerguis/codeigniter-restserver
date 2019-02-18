<?php
defined('BASEPATH') OR exit('No direct script access allowed');



class Test extends MY_Controller {

	public function index_get()
	{
		$users = [
            ['id' => 1, 'name' => 'Luke', 'email' => 'luke.skywalker@rebelalliance.org', 'fact' => 'Jedi Knight and friend to Han Solo.'],
            ['id' => 2, 'name' => 'Chewie', 'email' => 'chewbacca@scruffynerfherder.com', 'fact' => 'It\'s not wise to upset a Wookiee.'],
            ['id' => 3, 'name' => 'Han', 'email' => 'han.solo@scruffynerfherder.com', 'fact' => 'made the Kessel Run in less than twelve parsecs.']
        ];
		$this->response($users);
	}

	public function index_post()
	{
		$this->response($this->post());
	}

	public function index_put()
	{
		$this->response($this->put());
	}
}