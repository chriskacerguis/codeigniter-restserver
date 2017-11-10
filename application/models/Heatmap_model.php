<?php
/**
 * Created by PhpStorm.
 * User: cita-02
 * Date: 11/9/17
 * Time: 7:30 PM
 */

/**
 * Class Heatmap_model. Model to access values in the heat table.
 *
 * @category    Model
 * @author      yamilelias <yamileliassoto@gmail.com>
 */
class Heatmap_model extends CI_Model {

    /**
     * Latitude.
     *
     * @var
     */
    public $latitude;

    /**
     * Longitude.
     *
     * @var
     */
    public $longitude;

    /**
     * Sensor Value.
     * @var
     */
    public $sensor_value;

    /**
     * Update an specific entry from the database.
     *
     * @param array $input
     */
    public function insert_entry($input = array()) {
        if(!empty($input)) {
            $this->latitude = $input['latitude'];
            $this->longitude = $input['longitude'];
            $this->sensor_value = $input['sensor_value'];

            $this->db->insert('map', $this);

            // Return new item id
            $insert_id = $this->db->insert_id();

            return  $insert_id;
        }
    }

    /**
     * Update an specific entry from the database.
     *
     * @param array $input
     */
    public function update_entry($input = array()) {
        if(!empty($input)) {
            $this->latitude = $input['latitude'];
            $this->longitude = $input['longitude'];
            $this->sensor_value = $input['sensor_value'];

            $this->db->update('map', $this, array('id' => $input['id']));
        }
    }

    /**
     * Get an entry from a provided value.
     *
     * @param string $id
     */
    public function retrieve_entry($id = '') {
//        $query = $this->db->get('map', ['id' => $id]);

        $this->db->select('*');
        $this->db-> from('map');
        $this->db-> where('id', $id);

        $query = $this->db->get();

        return $query->row();
    }

    /**
     * Return everything from the map table. Equal to SELECT * FROM 'map';
     *
     * @return mixed Result Query.
     */
    public function get_all() {
        $this->db->select('*');
        $this->db->from('map');
        $query = $this->db->get();

        return $query->result();
    }
}