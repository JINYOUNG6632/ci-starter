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

    /**
     * 단 한 번의 쿼리로:
     * - 해당 글의 전체 트리를 전위순서(path)로 펼친 뒤
     * - rn(ROW_NUMBER)로 페이지네이션 (기본 50개)
     * - total_count(전체 댓글 수)까지 함께 반환
     *
     * 반환:
     *   [
     *     'rows'        => stdClass[] (댓글 리스트: depth/path/rn 포함),
     *     'total'       => int,        // 전체 댓글 수
     *     'total_pages' => int,
     *     'page'        => int,
     *     'page_size'   => int,
     *   ]
     */
    public function page_by_post($postId, $page = 1, $pageSize = 50)
    {
        $postId   = (int)$postId;
        $page     = max(1, (int)$page);
        $pageSize = max(1, (int)$pageSize);

        $start = ($page - 1) * $pageSize + 1;
        $end   = $page * $pageSize;

        $sql = "
            WITH RECURSIVE cte AS (
                SELECT
                    c.id, c.parent_id, c.post_id, c.user_id, u.username,
                    c.body, c.is_deleted, c.created_at,
                    0 AS depth,
                    LPAD(CAST(c.id AS CHAR), 10, '0') AS path
                FROM comments c
                LEFT JOIN users u ON u.id = c.user_id
                WHERE c.post_id = ? AND c.parent_id IS NULL

                UNION ALL

                SELECT
                    ch.id, ch.parent_id, ch.post_id, ch.user_id, u.username,
                    ch.body, ch.is_deleted, ch.created_at,
                    cte.depth + 1,
                    CONCAT(cte.path, '/', LPAD(CAST(ch.id AS CHAR), 10, '0')) AS path
                FROM comments ch
                LEFT JOIN users u ON u.id = ch.user_id
                JOIN cte ON ch.parent_id = cte.id
                WHERE ch.post_id = ?
            ),
            t AS (
                SELECT
                    cte.*,
                    (SELECT COUNT(*) FROM comments x WHERE x.parent_id = cte.id) AS reply_count,
                    ROW_NUMBER() OVER (ORDER BY cte.path) AS rn,
                    COUNT(*)    OVER () AS total_count
                FROM cte
            )
            SELECT *
            FROM t
            WHERE t.rn BETWEEN ? AND ?
            ORDER BY t.rn
        ";

        // ✅ 객체 리스트로 받기
        $rows = $this->db->query($sql, [$postId, $postId, $start, $end])->result();

        // total 계산 (객체 접근으로 통일)
        if (!empty($rows)) {
            $total = (int)$rows[0]->total_count;
        } else {
            // 댓글 0개인 경우
            $total = (int)$this->db
                ->query("SELECT COUNT(*) AS cnt FROM comments WHERE post_id = ?", [$postId])
                ->row()->cnt;
        }

        $totalPages = (int)ceil(($total ?: 0) / $pageSize);

        return [
            'rows'        => $rows,     // stdClass[] (템플릿에서 {comments->id} 접근)
            'total'       => $total,
            'total_pages' => $totalPages,
            'page'        => $page,
            'page_size'   => $pageSize,
        ];
    }

    /**
     * 새 댓글이 트리 전위순서에서 몇 번째(rn)인지 구해 페이지 계산.
     * PRG 리다이렉트 시 사용.
     */
    public function calc_page_of_comment($postId, $commentId, $pageSize = 50)
    {
        $postId   = (int)$postId;
        $commentId= (int)$commentId;
        $pageSize = max(1, (int)$pageSize);

        $sql = "
            WITH RECURSIVE cte AS (
                SELECT id, parent_id, post_id,
                       LPAD(CAST(id AS CHAR), 10, '0') AS path
                FROM comments
                WHERE post_id = ? AND parent_id IS NULL

                UNION ALL

                SELECT ch.id, ch.parent_id, ch.post_id,
                       CONCAT(cte.path, '/', LPAD(CAST(ch.id AS CHAR), 10, '0')) AS path
                FROM comments ch
                JOIN cte ON ch.parent_id = cte.id
                WHERE ch.post_id = ?
            ),
            ranked AS (
                SELECT id, ROW_NUMBER() OVER (ORDER BY path) AS rn
                FROM cte
            )
            SELECT rn FROM ranked WHERE id = ?
        ";

        $row = $this->db->query($sql, [$postId, $postId, $commentId])->row();
        $rn  = $row ? (int)$row->rn : 1;

        return (int)ceil($rn / $pageSize);
    }

    /** 댓글 생성 (루트/대댓글 공통) */
    public function create_comment($postId, $userId, $body, $parentId = null)
    {
        $now = date('Y-m-d H:i:s');

        $this->db->trans_start();

        $this->db->insert('comments', [
            'post_id'     => (int)$postId,
            'user_id'     => (int)$userId,
            'body'        => $body,
            'parent_id'   => $parentId !== null ? (int)$parentId : null,
            'reply_count' => 0,
            'created_at'  => $now,
            'updated_at'  => $now,
            'is_deleted'  => 0,
        ]);
        $newId = (int)$this->db->insert_id();

        if ($parentId !== null) {
            $this->db->set('reply_count', 'reply_count + 1', false)
                     ->where('id', (int)$parentId)
                     ->update('comments');
        }

        $this->db->trans_complete();
        if ($this->db->trans_status() === false) {
            return false;
        }

        // 컨트롤러에서 배열 접근을 하므로 row_array() 유지
        return $this->find_one_with_user($newId);
    }

    /** 소프트 삭제 (본인만) */
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

    /** 단일 댓글 + 유저명 조회 (배열 반환) */
    public function find_one_with_user($commentId)
    {
        return $this->db
            ->select('c.id, c.post_id, c.user_id, u.username, c.body, c.created_at, c.parent_id, c.reply_count, c.is_deleted')
            ->from('comments AS c')
            ->join('users AS u', 'u.id = c.user_id', 'left')
            ->where('c.id', (int)$commentId)
            ->get()->row_array();
    }
}
