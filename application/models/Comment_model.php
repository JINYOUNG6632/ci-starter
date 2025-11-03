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

    /* =========================
     *  Nested Set 기반 목록/페이지
     * ========================= */

    public function page_by_post($postId, $page = 1, $pageSize = 50)
    {
        $postId   = (int)$postId;
        $page     = max(1, (int)$page);
        $pageSize = max(1, (int)$pageSize);

        $offset = ($page - 1) * $pageSize;

        /** ✅ 삭제였더라도 자식이 있으면 보여야 함 → reply_count 사용 */
        $totalRow = $this->db->query("
            SELECT COUNT(*) AS cnt
            FROM comments c
            WHERE c.post_id = ?
            AND (c.is_deleted = 0 OR c.reply_count > 0)
        ", [$postId])->row();
        $total = (int)$totalRow->cnt;
        $totalPages = (int)ceil(($total ?: 0) / $pageSize);

        /** ✅ 목록 조회 최적화: 서브쿼리 제거, reply_count 직접 사용 */
        $rows = $this->db->query("
            SELECT 
                c.id, c.post_id, c.user_id, c.parent_id,
                c.body, c.is_deleted, c.created_at, c.updated_at,
                c.lft, c.rgt, c.depth,
                u.username,
                c.reply_count
            FROM comments c
            LEFT JOIN users u ON u.id = c.user_id
            WHERE c.post_id = ?
            AND (c.is_deleted = 0 OR c.reply_count > 0)
            ORDER BY c.lft ASC
            LIMIT ? OFFSET ?
        ", [$postId, $pageSize, $offset])->result();

        return [
            'rows'        => $rows,
            'total'       => $total,
            'total_pages' => $totalPages,
            'page'        => $page,
            'page_size'   => $pageSize,
        ];
    }

    public function calc_page_of_comment($postId, $commentId, $pageSize = 50)
    {
        $postId    = (int)$postId;
        $commentId = (int)$commentId;
        $pageSize  = max(1, (int)$pageSize);

        $node = $this->db->query("
            SELECT lft
            FROM comments
            WHERE id = ? AND post_id = ?
        ", [$commentId, $postId])->row();

        if (!$node) return 1;

        /** ✅ 가시 댓글 수 계산 (reply_count 활용) */
        $cntRow = $this->db->query("
            SELECT COUNT(*) AS cnt
            FROM comments c
            WHERE c.post_id = ?
              AND c.lft <= ?
              AND (c.is_deleted = 0 OR c.reply_count > 0)
        ", [$postId, (int)$node->lft])->row();

        $pos = (int)$cntRow->cnt;
        return max(1, (int)ceil($pos / $pageSize));
    }

    /* =========================
     *  Nested Set 기반 삽입/삭제
     * ========================= */

    public function create_comment($postId, $userId, $body, $parentId = null)
    {
        $postId   = (int)$postId;
        $userId   = (int)$userId;
        $parentId = $parentId !== null ? (int)$parentId : null;
        $now      = date('Y-m-d H:i:s');

        $this->db->trans_start();

        if ($parentId === null) {
            $maxRgt = $this->db->select('COALESCE(MAX(rgt), 0) AS mr', false)
                               ->from('comments')
                               ->where('post_id', $postId)
                               ->get()->row()->mr;
            $lft   = $maxRgt + 1;
            $rgt   = $lft + 1;
            $depth = 0;
        } else {
            $parent = $this->db->query("
                SELECT id, lft, rgt, depth
                FROM comments
                WHERE id = ? AND post_id = ?
                FOR UPDATE
            ", [$parentId, $postId])->row();

            if (!$parent) {
                $this->db->trans_complete();
                return false;
            }

            $this->db->query("
                UPDATE comments
                SET rgt = rgt + 2
                WHERE post_id = ? AND rgt >= ?
            ", [$postId, $parent->rgt]);

            $this->db->query("
                UPDATE comments
                SET lft = lft + 2
                WHERE post_id = ? AND lft > ?
            ", [$postId, $parent->rgt]);

            $lft   = (int)$parent->rgt;
            $rgt   = $lft + 1;
            $depth = (int)$parent->depth + 1;
        }

        $this->db->insert('comments', [
            'post_id'    => $postId,
            'user_id'    => $userId,
            'parent_id'  => $parentId,
            'body'       => $body,
            'is_deleted' => 0,
            'created_at' => $now,
            'updated_at' => $now,
            'lft'        => $lft,
            'rgt'        => $rgt,
            'depth'      => $depth,
            'reply_count'=> 0,
        ]);
        $newId = (int)$this->db->insert_id();

        if ($parentId !== null) {
            $this->db->set('reply_count', 'reply_count + 1', false)
                     ->where('id', $parentId)
                     ->update('comments');
        }

        $this->db->trans_complete();
        if ($this->db->trans_status() === false) return false;

        return $this->find_one_with_user($newId);
    }

    public function soft_delete_comment($commentId, $userId)
    {
        $this->db->set('is_deleted', 1)
                 ->set('body', null)
                 ->set('updated_at', date('Y-m-d H:i:s'))
                 ->where('id', (int)$commentId)
                 ->where('user_id', (int)$userId)
                 ->update('comments');

        return $this->db->affected_rows() > 0;
    }

    public function find_one_with_user($commentId)
    {
        return $this->db
            ->select('c.id, c.post_id, c.user_id, u.username, c.body, c.created_at, c.updated_at, c.parent_id, c.reply_count, c.is_deleted, c.lft, c.rgt, c.depth')
            ->from('comments AS c')
            ->join('users AS u', 'u.id = c.user_id', 'left')
            ->where('c.id', (int)$commentId)
            ->get()->row_array();
    }

    public function get_node_by_id($commentId)
    {
        return $this->db->select('id, post_id, parent_id, lft, rgt, depth')
                        ->from('comments')
                        ->where('id', (int)$commentId)
                        ->get()->row();
    }
}
