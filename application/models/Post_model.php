<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Post_model extends MY_Model {
    protected $table = 'posts';

    public function __construct()
    {
        parent::__construct();
        $this->load->model('File_model');
    }

    /* =========================
     * 기본 CRUD
     * ========================= */
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
        $this->db->where('posts.id', (int)$post_id);
        // ✅ 삭제된 글 제외
        $this->db->where('posts.is_deleted', 0);

        $query = $this->db->get();
        return $query->row();
    }

    public function get_posts_by_category($category_id)
    {
        $this->db->select('posts.id, posts.title, posts.created_at, users.username');
        $this->db->from('posts');
        $this->db->join('users', 'users.id = posts.user_id');
        $this->db->where('posts.category_id', (int)$category_id);
        // ✅ 삭제된 글 제외
        $this->db->where('posts.is_deleted', 0);
        $this->db->order_by('posts.id', 'DESC');

        $query = $this->db->get();
        return $query->result();
    }

    public function update_post($post_id, $data)
    {
        $this->db->where('posts.id', (int)$post_id);
        // ✅ 삭제된 글은 업데이트 불가
        $this->db->where('posts.is_deleted', 0);
        return $this->db->update('posts', $data);
    }

    public function delete_post($id)
    {
        $id = (int)$id;

        $this->db->trans_start();

        // ✅ 소프트 삭제
        $this->db->where('id', $id)->update('posts', ['is_deleted' => 1]);
        // 댓글/파일도 화면에서 숨기고 싶다면 같이 소프트 삭제
        $this->db->where('post_id', $id)->update('comments', ['is_deleted' => 1]);
        $this->File_model->soft_delete_by_post($id);

        $this->db->trans_complete();
        return $this->db->trans_status();
    }

    public function count_by_title(string $q = '', ?int $category_id = null): int
    {
        $this->db->from('posts');
        // ✅ 삭제된 글 제외
        $this->db->where('is_deleted', 0);
        if (!empty($category_id)) {
            $this->db->where('category_id', (int)$category_id);
        }
        if ($q !== '') {
            $this->db->like('title', $q, 'both');
        }
        return (int)$this->db->count_all_results();
    }

    public function list_by_title(int $limit, int $offset, string $q = '', int $category_id = 0)
    {
        // 댓글 수 집계 서브쿼리 (삭제되지 않은 댓글만)
        $sub = $this->db
            ->select('post_id, COUNT(*) AS cnt', false)
            ->from('comments')
            ->where('is_deleted', 0)
            ->group_by('post_id')
            ->get_compiled_select();

        $this->db
            ->select([
                'p.id',
                'p.title',
                'p.created_at',
                'p.user_id',
                'u.username AS username',
                'COALESCE(c.cnt, 0) AS comment_count',
            ], false)
            ->from('posts AS p')
            ->join('users AS u', 'u.id = p.user_id', 'left')
            ->join("({$sub}) AS c", 'c.post_id = p.id', 'left');

        // ✅ 삭제된 글 제외 (핵심 한 줄)
        $this->db->where('p.is_deleted', 0);

        if ($category_id > 0) {
            $this->db->where('p.category_id', (int)$category_id);
        }
        if ($q !== '') {
            $this->db->like('p.title', $q);
        }

        return $this->db
            ->order_by('p.id', 'DESC')
            ->limit($limit, $offset)
            ->get()->result();
    }
}
