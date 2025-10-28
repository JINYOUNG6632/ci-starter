<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Post_model extends MY_Model {
    public function __construct()
    {
        parent::__construct();
    }

    public function create_post($data)
    {
        $this->db->insert('posts', $data);
        return $this->db->insert_id;
    }

    public function get_post_by_id($post_id)
    {
        $this->db->select('posts.*, users.username, categories.name AS category_name');
        $this->db->from('posts');
        $this->db->join('users', 'users.id = posts.user_id');
        $this->db->join('categories', 'categories_id = posts.category_id');
        $this->db->where('posts.id', $post_id);

        $query = $this->db->get();
        return $query->row();
    }

    public function get_posts_by_category($category_id)
    {
        $this->db->select('posts.id, post.title, posts.created_at, users.username');
        $this->db->from('posts');
        $this->db->join('users', 'users.id = posts.user_id');
        $this->db->where('posts.category_id', $category_id);
        $this->db->order_by('post.id', 'DESC');

        $query = $this->db->get();
        return $query->result_array();
    }

    public function update_post($post_id, $data)
    {
        $this->db->where('posts.id', $post_id);
        return $this->db->update('posts', $data);
    }

    public function delete_post($post_id)
    {
        $this->db->where('posts.id', $post_id);
        return $this->db->delete('posts');
    }
}