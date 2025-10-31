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

    /**
     * 전위순(lft ASC) 페이지네이션
     * 반환:
     *  [
     *    'rows'        => stdClass[],
     *    'total'       => int,
     *    'total_pages' => int,
     *    'page'        => int,
     *    'page_size'   => int,
     *  ]
     */
    public function page_by_post($postId, $page = 1, $pageSize = 50)
    {
        $postId   = (int)$postId;
        $page     = max(1, (int)$page);
        $pageSize = max(1, (int)$pageSize);

        $offset = ($page - 1) * $pageSize;

        // 표시 대상 총 개수 (소프트삭제 제외)
        $totalRow = $this->db->query("
            SELECT COUNT(*) AS cnt
            FROM comments
            WHERE post_id = ? AND is_deleted = 0
        ", [$postId])->row();
        $total = (int)$totalRow->cnt;
        $totalPages = (int)ceil(($total ?: 0) / $pageSize);

        // 전위순 정렬: lft ASC
        $rows = $this->db->query("
            SELECT c.id, c.post_id, c.user_id, c.parent_id,
                   c.body, c.is_deleted, c.created_at, c.updated_at,
                   c.lft, c.rgt, c.depth,
                   u.username,
                   /* 필요하면 캐시된 reply_count 대신 즉시 계산도 가능 */
                   (SELECT COUNT(*) FROM comments x WHERE x.parent_id = c.id AND x.is_deleted = 0) AS reply_count
            FROM comments c
            LEFT JOIN users u ON u.id = c.user_id
            WHERE c.post_id = ? AND c.is_deleted = 0
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

    /**
     * 특정 댓글이 전위순에서 몇 번째인지(lft 기반) → 페이지 계산
     */
    public function calc_page_of_comment($postId, $commentId, $pageSize = 50)
    {
        $postId    = (int)$postId;
        $commentId = (int)$commentId;
        $pageSize  = max(1, (int)$pageSize);

        // 대상 노드의 lft
        $node = $this->db->query("
            SELECT lft
            FROM comments
            WHERE id = ? AND post_id = ?
        ", [$commentId, $postId])->row();

        if (!$node) return 1;

        // 나보다 앞(같은 포함)인 표시 가능한 댓글 수
        $cntRow = $this->db->query("
            SELECT COUNT(*) AS cnt
            FROM comments
            WHERE post_id = ? AND is_deleted = 0 AND lft <= ?
        ", [$postId, (int)$node->lft])->row();

        $pos = (int)$cntRow->cnt;
        return max(1, (int)ceil($pos / $pageSize));
    }

    /* =========================
     *  Nested Set 기반 삽입/삭제
     * ========================= */

    /**
     * 댓글 생성 (루트/대댓글 공통) - Nested Set
     * return: false | row_array (find_one_with_user 결과)
     */
    public function create_comment($postId, $userId, $body, $parentId = null)
    {
        $postId   = (int)$postId;
        $userId   = (int)$userId;
        $parentId = $parentId !== null ? (int)$parentId : null;
        $now      = date('Y-m-d H:i:s');

        $this->db->trans_start();

        if ($parentId === null) {
            // 루트: post 내 가장 오른쪽(rgt)의 다음에 추가
            $maxRgt = $this->db->select('COALESCE(MAX(rgt), 0) AS mr', false)
                               ->from('comments')
                               ->where('post_id', $postId)
                               ->get()->row()->mr;
            $lft   = $maxRgt + 1;
            $rgt   = $lft + 1;
            $depth = 0;
        } else {
            // 부모 잠금 (동시성)
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

            // 부모 rgt 기준으로 공간 2칸 벌리기 (부등호 주의!)
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

        // 삽입
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
        ]);
        $newId = (int)$this->db->insert_id();

        // (선택) 부모 reply_count 캐시 증가
        if ($parentId !== null) {
            $this->db->set('reply_count', 'reply_count + 1', false)
                     ->where('id', $parentId)
                     ->update('comments');
        }

        $this->db->trans_complete();
        if ($this->db->trans_status() === false) return false;

        return $this->find_one_with_user($newId);
    }

    /**
     * 소프트 삭제 (본문 가림 / 트리 구조 유지)
     */
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

    /* =========================
     *  조회 유틸
     * ========================= */

    /** 단일 댓글 + 유저명 (배열) */
    public function find_one_with_user($commentId)
    {
        return $this->db
            ->select('c.id, c.post_id, c.user_id, u.username, c.body, c.created_at, c.updated_at, c.parent_id, c.reply_count, c.is_deleted, c.lft, c.rgt, c.depth')
            ->from('comments AS c')
            ->join('users AS u', 'u.id = c.user_id', 'left')
            ->where('c.id', (int)$commentId)
            ->get()->row_array();
    }

    /** (보조) 노드 가져오기 */
    public function get_node_by_id($commentId)
    {
        return $this->db->select('id, post_id, parent_id, lft, rgt, depth')
                        ->from('comments')
                        ->where('id', (int)$commentId)
                        ->get()->row();
    }
}
