<?php
/**
 * Created by PhpStorm.
 * User: cita-02
 * Date: 11/9/17
 * Time: 6:40 PM
 */

class Expresiones_model extends CI_Model
{

    public function leer()
    {
        $this->db->select('*');
        $this->db->from('expresiones');
        $query = $this->db->get();
        return $query->result();

    }

}