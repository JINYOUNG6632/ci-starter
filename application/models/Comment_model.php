<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @property CI_DB_query_builder $db
 */
class Comment_model extends MY_Model {
    public function __construct()
    {
        parent::__construct();
    }

    public function get_comments($post_id, $parent_id, $limit, $offset)
    {
        $fetch_limit = $limit + 1;

        $this->db
            ->select('c.id,
                      c.post_id,
                      c.user_id,
                      u.username,
                      c.body,
                      c.created_at,
                      c.parent_id,
                      c.reply_count,
                      c.is_deleted')
            ->from('comments AS c')
            ->join('users AS u', 'u.id = c.user_id', 'left')
            ->where('c.post_id', $post_id)
            ->order_by('c.created_at', 'ASC')
            ->limit($fetch_limit, $offset);
        
        if ($parent_id === null) {
            $this->db->where('c.parent_id IS NULL', null, false);
        } else {
            $this->db->where('c.parent_id', $parent_id);
        }

        $query = $this->db->get();
        $rows = $query->result_array();

                $has_more = false;
        if (count($rows) > $limit) {
            $has_more = true;
            array_pop($rows); // 초과로 가져온 1개는 화면에서는 안 쓸 거라 제거
        }

        $next_offset = $offset + count($rows);

        return [
            'comments'    => $rows,
            'has_more'    => $has_more,
            'next_offset' => $next_offset
        ];
    }

    public function create_comment($post_id, $user_id, $body, $parent_id = null)
    {
        $now = date('Y-m-d H:i:s');

        $this->db->trans_start();

        $insert_data = [
            'post_id'     => $post_id,
            'user_id'     => $user_id,
            'body'        => $body,
            'parent_id'   => $parent_id,
            'reply_count' => 0,
            'created_at'  => $now,
            'updated_at'  => $now,
            'is_deleted'  => 0,
        ];

        $this->db->insert('comments', $insert_data);
        $new_id = $this->db->insert_id();

        if(!is_null($parent_id)) {
            $this->db->set('reply_count', 'reply_count + 1', false)
                ->where('id', $parent_id)
                ->update('comments');
        }

        $this->db->trans_complete();

        if ($this->db->trans_status() === false) {
            return false;
        }

                $this->db
            ->select('c.id,
                      c.post_id,
                      c.user_id,
                      u.username,
                      c.body,
                      c.created_at,
                      c.parent_id,
                      c.reply_count,
                      c.is_deleted')
            ->from('comments AS c')
            ->join('users AS u', 'u.id = c.user_id', 'left')
            ->where('c.id', $new_id);

        $row = $this->db->get()->row_array();
        return $row ?: false;
    }

    public function soft_delete_comment($comment_id, $user_id)
    {
        $this->db->set('is_deleted', 1)
                 ->set('updated_at', date('Y-m-d H:i:s'))
                 ->where('id', $comment_id)
                 ->where('user_id', $user_id)
                 ->update('comments');

        return $this->db->affected_rows() > 0;
    }
}