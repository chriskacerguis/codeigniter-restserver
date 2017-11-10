<?php
/**
 * Created by PhpStorm.
 * User: cita-02
 * Date: 11/9/17
 * Time: 6:34 PM
 */

class expresiones extends CI_Controller {


    public function __construct()
    {
        parent::__construct();
        $this->load->model('expresiones_model');
        $this->load->database('default');
    }

    public function index()
    {

        $data['expresiones'] =  $this->expresiones_model->leer();

        $this->load->view('expresiones_vista', $data);

    }

}