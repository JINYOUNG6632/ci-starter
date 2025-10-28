<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Category_model extends MY_Model {
    public function __construct()
    {
        parent::__construct();
    }

    public function get_all_categories()
    {
        $query = $this->db->get('categories');
        return $query->result();
    }

    public function get_category_by_id($id)
    {
        $query = $this->db->get_where('categories', array('id' => $id));
        return $query->row();
    }
}