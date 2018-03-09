<?php defined('BASEPATH') or exit('No direct script access allowed');

class endpoint_model extends CI_Model
{

    public function __construct()
    {
        parent::__construct();
    }

    public function list_table($table);
    {
        $total = $this->db->get_where($table, $where)->get();

        $list = ['rows' => $query->result(), 'num_rows' => $total->result()->num_rows()];

        return $list;

    }

    public function page($table, $where = [], $offset = null, $limit = null);
    {

        $rows = $this->db->get_where($table, $where, $limit, $offset)->get();

        $total = $this->db->get_where($table, $where)->get();

        $page = ['rows' => $rows->result(), 'num_rows' => $total->result()->num_rows()];

        return $page;

    }

    public function row('table', $where)
    {
        $result = [];

        $this->db->select('*');
        $this->db->from('table');
        if (is_array($where)) {
            //$array = array('name !=' => $name, 'id <' => $id, 'date >' => $date);
            $this->db->where($array);
        } else if (
            is_string($where)) {
            //$where = "name='Joe' AND status='boss' OR status='active'";
            $this->db->where($where);
        }

        $query = $this->db->get();

        if (!$query) {
            $result = $this->db->error(); // Has keys 'code' and 'message'
        } elseif ($this->db->affected_rows() > 0) {

            $result = ['code' => 0, 'row' => $this->db->row()];

        }
        return $result;

    }

    public function insert($table, $data)
    {
        $result = [];

        if (!$this->db->insert($table, $data)) {
            $result = $this->db->error(); // Has keys 'code' and 'message'
        } elseif ($this->db->affected_rows() > 0) {
            $result = ['code' => 0, 'insert_id' => $this->db->insert_id()];
        }

        return $result;
    }

    public function update($table, $where, $data)
    {
        $result = [];

        $this->db->set($data);
        $this->db->where($where);

        if (!$this->db->update($table)) {
            $result = $this->db->error(); // Has keys 'code' and 'message'
        } elseif ($this->db->affected_rows() > 0) {
            $result = ['code' => 0];
        } elseif ($this->db->affected_rows() == 0) {
            $result = ['code' => 3004];
        }

        return $result;
    }

    public function delete($table, $where)
    {
        $result = [];

        if (!$this->db->delete('mytable', array('id' => $id))) {
            $result = $this->db->error(); // Has keys 'code' and 'message'
        } else {
            $result = ['code' => 0, 'message' => ''];
        }

        return $result;

    }
}

//$error = $this->db->error(); // Has keys 'code' and 'message'
