<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require APPPATH . '/libraries/REST_Controller.php';

class Jwt extends REST_Controller
{
    // Accounts data for login model emulation
    private $accounts = [
        'admin' =>  'password',
        'user' =>  'password2',
        'demo' =>  'password3',
    ];

	public function __construct()
	{
		parent::__construct();
    }

    //User JWT authentication to get the toekn
	public function token_post()
	{
        $this->load->library('form_validation');

        $this->form_validation->set_data([
                'username' => $this->post('username'),
                'password' => $this->post('password'),
        ]);

        $this->form_validation->set_rules('username', 'Username', 'required');
        $this->form_validation->set_rules('password', 'Password', 'required');

		if ($this->form_validation->run() == TRUE)
		{
			if ($this->login($this->post('username'), $this->post('password')))
			{
                $token['username'] = $this->post('username');
                $date = new DateTime();
                $token['iat'] = $date->getTimestamp();
                $token['exp'] = $date->getTimestamp() + $this->config->item('jwt_token_expire');
                $output_data['token'] = $this->jwt_encode($token);
                $this->response($output_data, REST_Controller::HTTP_OK);
			}
			else
			{
                $output_data[$this->config->item('rest_status_field_name')] = "invalid_credentials";
                $output_data[$this->config->item('rest_message_field_name')] = "Invalid username or password!";
                $this->response($output_data, REST_Controller::HTTP_UNAUTHORIZED);
			}
		}
		else
		{
            $output_data[$this->config->item('rest_status_field_name')] = "empty_fields";
            $output_data[$this->config->item('rest_message_field_name')] = $this->form_validation->error_array();

			$this->response($output_data, REST_Controller::HTTP_UNPROCESSABLE_ENTITY);
		}
	}

    // Refresh the token with new expirey time
	public function token_refresh_get()
	{
        try
        {
            $decoded = $this->jwt_decode($this->jwt_token());

            if($this->username_check($decoded['username']) == FALSE)
            {
                $output_data[$this->config->item('rest_status_field_name')] = "invalid_user";
                $output_data[$this->config->item('rest_message_field_name')] = "The token user id is not exist in the system!";
                $this->response($output_data, REST_Controller::HTTP_UNAUTHORIZED);
            }

            $token['username'] = $decoded['username'];
            $date = new DateTime();
            $token['iat'] = $date->getTimestamp();
            $token['exp'] = $date->getTimestamp() + $this->config->item('jwt_token_expire');
            $output_data['token'] = $this->jwt_encode($token);
            $this->response($output_data, REST_Controller::HTTP_OK);
        }
        catch (Exception $e)
        {
            $output_data[$this->config->item('rest_status_field_name')] = "invalid_token";
            $output_data[$this->config->item('rest_message_field_name')] = $e->getMessage();
            $this->response($output_data, REST_Controller::HTTP_UNAUTHORIZED);
        }
	}

    // JWT test endpoint, it shows the token information (need token authorization)
	public function token_info_get()
	{
        try
        {
            $output_data = $this->jwt_decode($this->jwt_token());
            $this->response($output_data, REST_Controller::HTTP_OK);
        }
        catch (Exception $e)
        {
            $output_data[$this->config->item('rest_status_field_name')] = "invalid_token";
            $output_data[$this->config->item('rest_message_field_name')] = $e->getMessage();
            $this->response($output_data, REST_Controller::HTTP_UNAUTHORIZED);
        }
	}

    // Login model emulation, login funcation
    private function login($username, $password)
    {
        if(array_key_exists($username, $this->accounts) AND $this->accounts[$username] === $password)
        {
            return TRUE;
        }
        return FALSE;
    }

    // Login model emulation, check if user exist on database
	private function username_check($username)
	{
        if(array_key_exists($username, $this->accounts))
        {
            return TRUE;
        }
        return FALSE;
	}

}