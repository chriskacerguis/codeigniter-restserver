<?php
/**
 * Created by PhpStorm.
 * User: yamilelias
 * Date: 11/9/17
 * Time: 7:15 PM
 */

defined('BASEPATH') OR exit('No direct script access allowed');

/** @noinspection PhpIncludeInspection */
require APPPATH . 'libraries/REST_Controller.php';

/**
 * Class Heatmap. This class handles all the functionality to save, load and send data from the database
 * in a web service, so it can be accessed by any application.
 *
 * @package     CodeIgniter
 * @subpackage  Rest_Server
 * @category    Controller
 * @author      yamilelias <yamileliassoto@gmail.com>
 */
class Heatmap extends REST_Controller {

    function __construct($config = 'rest')
    {
        parent::__construct($config);

        // Load model to work with
        $this->load->model('heatmap_model');
        $this->load->database('default');

        /*
         * TODO: Configure limits into the controller methods if necessary
         * Note: Ensure you have created the 'limits' table and enabled 'limits' within application/config/rest.php
         *
         * This can be done this way:
         * $this->methods['data_get']['limit'] = 500;
         * It means that 500 requests can be made per hour per user/key
         */
        // $this->methods['data_get']['limit'] = 1000;
    }

    /**
     * GET method.
     */
    public function data_get() {

        $raw_id = $this->uri->segment(5);

        print_r("ID found! it is: ". $raw_id);

        $id = str_replace('%20', ' ', $raw_id); // So we can get the space from the timestamp

//        $this->response([
//            'id' => $this->get('id'),
//        ], REST_Controller::HTTP_OK);

        // If no ID was provided, then return all the records
        if ($id == NULL)
        {
            // Get all the records
            $heatmap = $this->heatmap_model->get_all();

            // Check if the users data store contains users (in case the database result returns NULL)
            if ($heatmap)
            {
                // Set the response and exit
                $this->response($heatmap, REST_Controller::HTTP_OK); // OK (200) being the HTTP response code
            }
            else
            {
                // Set the response and exit
                $this->response([
                    'status' => FALSE,
                    'message' => 'No records were found in database'
                ], REST_Controller::HTTP_NOT_FOUND); // NOT_FOUND (404) being the HTTP response code
            }
        }

        // Validate the id.
        if ($id <= 0) {
            // Invalid id, set the response and exit.
            $this->response([
                'status' => FALSE,
                'message' => 'Please provide a valid ID to get the information'
            ], REST_Controller::HTTP_BAD_REQUEST);
        }

        // Get records from database
        $heatmap = $this->heatmap_model->retrieve_entry($raw_id);

        // If a record matches the id provided, then return it
        if (!empty($heatmap))
        {
            $this->set_response($heatmap, REST_Controller::HTTP_OK); // OK (200) being the HTTP response code
        }
        else
        {
            $this->set_response([
                'status' => FALSE,
                'message' => "No record with id = \"{$id}\" was found"
            ], REST_Controller::HTTP_NOT_FOUND); // NOT_FOUND (404) being the HTTP response code
        }
    }

    /**
     * POST Method.
     */
    public function data_post() {

        $entry = array(
            'id' => $this->get('id'),
            'longitude' => $this->post('longitude'),
            'latitude' => $this->post('latitude'),
            'sensor_value' => $this->post('sensor_value'),
        );

        $id = '';

        if($entry['id']) {
            $this->heatmap_model->update_entry($entry);
        } else {
            $id = $this->heatmap_model->insert_entry($entry);
        }

        $message = [
            'id' => empty($id) ? $this->get('id') : $id, // Return same ID if update, return generated if was insert
            'longitude' => $this->get('longitude'),
            'latitude' => $this->get('latitude'),
            'sensor_value' => $this->get('sensor_value'),
            'message' => 'Added a record to the database'
        ];

        $this->set_response($message, REST_Controller::HTTP_CREATED); // CREATED (201) being the HTTP response code
    }
}