<?php
    defined('BASEPATH') OR exit('No direct script access allowed');

    // This can be removed if you use __autoload() in config.php OR use Modular Extensions
    /** @noinspection PhpIncludeInspection */
    require_once(APPPATH . 'third_party/rest_server/libraries/REST_Controller.php');

    class MY_Controller extends REST_Controller {
        public function __construct(){
            parent::__construct();
        }
    }