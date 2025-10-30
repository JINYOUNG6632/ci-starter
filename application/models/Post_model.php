<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Post_model extends MY_Model {
    public function __construct()
    {
        parent::__construct();
        $this->load->model('File_model');
    }

    public function create_post($data)
    {
        $this->db->insert('posts', $data);
        return $this->db->insert_id();
    }

    public function get_post_by_id($post_id)
    {
        $this->db->select('posts.*, users.username, categories.name AS category_name');
        $this->db->from('posts');
        $this->db->join('users', 'users.id = posts.user_id');
        $this->db->join('categories', 'categories.id = posts.category_id');
        $this->db->where('posts.id', $post_id);
        $this->db->where('posts.is_deleted', 0);

        $query = $this->db->get();
        return $query->row();
    }

    public function get_posts_by_category($category_id)
    {
        $this->db->select('posts.id, posts.title, posts.created_at, users.username');
        $this->db->from('posts');
        $this->db->join('users', 'users.id = posts.user_id');
        $this->db->where('posts.category_id', $category_id);
        $this->db->where('posts.is_deleted', 0);
        $this->db->order_by('posts.id', 'DESC');

        $query = $this->db->get();
        return $query->result();
    }

    public function update_post($post_id, $data)
    {
        $this->db->where('posts.id', $post_id);
        $this->db->where('posts.is_deleted', 0);
        return $this->db->update('posts', $data);
    }

    public function delete_post($id)
    {
        $id = (int)$id;

        $this->db->trans_start();

        $this->db->where('id', $id)->update('posts', ['is_deleted' => 1]);

        $this->db->where('post_id', $id)->update('comments', ['is_deleted' => 1]);

        $this->File_model->soft_delete_by_post($id);

        $this->db->trans_complete();
        return $this->db->trans_status();
    }

}