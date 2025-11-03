<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Comments extends MY_Controller
{
    protected $models = ['Comment_model'];

    public function __construct()
    {
        parent::__construct();
    }

    /** 공통 page_size 해석 (세션 우선, 없으면 요청, 그래도 없으면 보수적 기본) */
    private function resolvePageSize(int $postId): int
    {
        $sessKey = 'comments_page_size_'.$postId;
        $sessVal = (int)$this->session->userdata($sessKey);
        if ($sessVal > 0) return $sessVal;

        $req = (int)$this->input->get_post('page_size');
        if ($req > 0 && $req <= 100) { // 상한선만 방어
            // 세션에도 캐시해두면 다음부터는 클라이언트 의존도 ↓
            $this->session->set_userdata($sessKey, $req);
            return $req;
        }
        // 최종 안전 fallback (임의 하드코딩이 아니라 "없을 때만" 쓰는 보호값)
        return 10;
    }

    /** 댓글 작성 */
    public function create()
    {
        $userId = (int)$this->session->userdata('id');
        $isAjax = $this->input->is_ajax_request();
        if (!$userId) {
            if ($isAjax) return $this->jsonFail('로그인이 필요합니다.', 401);
            $this->session->set_flashdata('error','로그인이 필요합니다.');
            redirect('/auth/login'); exit;
        }

        $postId   = (int)$this->input->post('post_id');
        $parentId = $this->input->post('parent_id');
        $body     = trim((string)$this->input->post('body'));
        $parentId = ($parentId === '' || $parentId === null) ? null : (int)$parentId;

        if (!$postId || $body === '') {
            if ($isAjax) return $this->jsonFail('유효하지 않은 요청', 400);
            redirect('/posts'); exit;
        }

        // 삭제된 부모엔 답글 금지
        if ($parentId !== null) {
            $p = $this->db->query("
                SELECT is_deleted FROM comments
                WHERE id = ? AND post_id = ? LIMIT 1
            ", [$parentId, $postId])->row();
            if (!$p || (int)$p->is_deleted === 1) {
                if ($isAjax) return $this->jsonFail('삭제된 댓글에는 답글을 달 수 없습니다.', 400);
                $this->session->set_flashdata('error','삭제된 댓글에는 답글을 달 수 없습니다.');
                redirect("/posts/view/{$postId}"); exit;
            }
        }

        // 저장
        $row = $this->Comment_model->create_comment($postId, $userId, $body, $parentId);
        if (!$row) {
            if ($isAjax) return $this->jsonFail('DB 오류', 500);
            redirect("/posts/view/{$postId}"); exit;
        }

        // ★ 여기서 page_size를 세션/요청에서 "동일 소스"로 해석
        $ps = $this->resolvePageSize($postId);

        // 가시성 규칙 기준 페이지 계산
        $nodeLftRow = $this->db->query("
            SELECT lft FROM comments WHERE id = ? AND post_id = ? LIMIT 1
        ", [(int)$row['id'], $postId])->row();
        $nodeLft = $nodeLftRow ? (int)$nodeLftRow->lft : 0;

        $posRow = $this->db->query("
            SELECT COUNT(*) AS cnt
            FROM comments c
            WHERE c.post_id = ?
              AND c.lft <= ?
              AND (c.is_deleted = 0 OR EXISTS (
                    SELECT 1 FROM comments x
                    WHERE x.parent_id = c.id AND x.is_deleted = 0
                  ))
        ", [$postId, $nodeLft])->row();
        $pos  = (int)$posRow->cnt;
        $page = max(1, (int)ceil($pos / $ps));

        $anchor = $this->db->query("
            SELECT c.id
            FROM comments c
            WHERE c.post_id = ?
              AND c.lft < ?
              AND (c.is_deleted = 0 OR EXISTS (
                    SELECT 1 FROM comments x
                    WHERE x.parent_id = c.id AND x.is_deleted = 0
                  ))
            ORDER BY c.lft DESC LIMIT 1
        ", [$postId, $nodeLft])->row();
        $insertAfterId = $anchor ? (int)$anchor->id : null;

        $totalRow = $this->db->query("
            SELECT COUNT(*) AS cnt
            FROM comments c
            WHERE c.post_id = ?
              AND (c.is_deleted = 0 OR EXISTS (
                    SELECT 1 FROM comments x
                    WHERE x.parent_id = c.id AND x.is_deleted = 0
                  ))
        ", [$postId])->row();
        $totalCount = (int)($totalRow ? $totalRow->cnt : 0);
        $totalPages = (int)ceil(($totalCount ?: 0) / $ps);

        if ($isAjax) {
            return $this->jsonOk([
                'comment'                 => $row,
                'page'                    => $page,
                'insert_after_comment_id' => $insertAfterId,
                'owns'                    => ((int)$row['user_id'] === $userId),
                'total_count'             => $totalCount,
                'total_pages'             => $totalPages,
            ]);
        }

        redirect("/posts/view/{$postId}?page={$page}#c{$row['id']}"); exit;
    }

    /** 댓글 페이지 조각 (AJAX) */
    public function page()
    {
        $this->output->set_content_type('application/json');
        if (!$this->input->is_ajax_request()) return $this->jsonFail('AJAX only', 400);

        $postId = (int)$this->input->get('post_id');
        $page   = max(1, (int)$this->input->get('page'));
        $ps     = $this->resolvePageSize($postId);   // ★ 동일 소스

        $paged = $this->Comment_model->page_by_post($postId, $page, $ps);

        $this->template_->viewAssign([
            'post'                 => (object)['id'=>$postId],
            'comments'             => $paged['rows'],
            'page'                 => $paged['page'],
            'total_pages'          => $paged['total_pages'],
            'total_comment_count'  => $paged['total'],
            'session_user_id'      => (int)$this->session->userdata('id'),
            'reply_to'             => null,
        ]);
        $this->template_->viewDefine('comment_list_stub', 'comment_list_stub.tpl');
        $html = $this->template_->viewFetch('comment_list_stub');

        return $this->jsonOk([
            'list_html'   => $html,
            'page'        => $paged['page'],
            'total_count' => $paged['total'],
            'total_pages' => $paged['total_pages'],
        ]);
    }

    public function delete()
    {
        $userId = (int)$this->session->userdata('id');
        $isAjax = $this->input->is_ajax_request();
        if (!$userId) {
            if ($isAjax) return $this->jsonFail('로그인이 필요합니다.', 401);
            $this->session->set_flashdata('error','로그인이 필요합니다.');
            redirect('/auth/login'); exit;
        }

        $commentId = (int)$this->input->post('comment_id');
        $postId    = (int)$this->input->post('post_id');
        if (!$commentId || !$postId) {
            if ($isAjax) return $this->jsonFail('잘못된 요청', 400);
            $this->session->set_flashdata('error','잘못된 요청입니다.');
            redirect('/posts'); exit;
        }

        $ok = $this->Comment_model->soft_delete_comment($commentId, $userId);

        $childRow = $this->db->query("
            SELECT COUNT(*) AS cnt
            FROM comments
            WHERE parent_id = ? AND is_deleted = 0
        ", [$commentId])->row();
        $childVisibleCount = (int)$childRow->cnt;

        $ps = $this->resolvePageSize($postId);   // ★ 동일 소스
        $totalRow = $this->db->query("
            SELECT COUNT(*) AS cnt
            FROM comments c
            WHERE c.post_id = ?
              AND (c.is_deleted = 0 OR EXISTS (
                    SELECT 1 FROM comments x
                    WHERE x.parent_id = c.id AND x.is_deleted = 0
                  ))
        ", [$postId])->row();
        $totalCount = (int)$totalRow->cnt;
        $totalPages = (int)ceil(($totalCount ?: 0) / $ps);

        if ($isAjax) {
            return $this->jsonOk([
                'deleted'           => (bool)$ok,
                'reply_count_after' => $childVisibleCount,
                'total_count'       => $totalCount,
                'total_pages'       => $totalPages,
            ]);
        }

        $this->session->set_flashdata($ok ? 'success':'error', $ok ? '삭제되었습니다.':'삭제할 수 없습니다.');
        redirect("/posts/view/{$postId}"); exit;
    }

    private function jsonOk($data=[]) {
        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(['ok'=>true,'data'=>$data]));
    }
    private function jsonFail($msg,$code=400) {
        return $this->output->set_status_header($code)
            ->set_content_type('application/json')
            ->set_output(json_encode(['ok'=>false,'msg'=>$msg]));
    }
}
