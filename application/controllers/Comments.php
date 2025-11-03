<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Comments extends MY_Controller
{
    protected $models = ['Comment_model'];

    public function __construct()
    {
        parent::__construct();
    }

    /** page_size 공통 해석 */
    private function resolvePageSize(int $postId): int
    {
        $sessKey = 'comments_page_size_'.$postId;
        $sessVal = (int)$this->session->userdata($sessKey);
        if ($sessVal > 0) return $sessVal;

        $req = (int)$this->input->get_post('page_size');
        if ($req > 0 && $req <= 100) {
            $this->session->set_userdata($sessKey, $req);
            return $req;
        }
        return 10;
    }

    /** 댓글 작성 */
    public function create()
    {
        $userId = (int)$this->session->userdata('id');
        $isAjax = $this->input->is_ajax_request();
        if (!$userId) {
            if ($isAjax) return $this->jsonFail('로그인이 필요합니다.', 401);
            redirect('/auth/login');
        }

        $postId   = (int)$this->input->post('post_id');
        $parentId = $this->input->post('parent_id');
        $body     = trim((string)$this->input->post('body'));
        $parentId = ($parentId === '' || $parentId === null) ? null : (int)$parentId;

        if (!$postId || $body === '') {
            if ($isAjax) return $this->jsonFail('유효하지 않은 요청', 400);
            redirect('/posts');
        }

        // 삭제된 부모에 답글 금지
        if ($parentId !== null) {
            $p = $this->db->query("
                SELECT is_deleted FROM comments
                WHERE id = ? AND post_id = ? LIMIT 1
            ", [$parentId, $postId])->row();
            if (!$p || (int)$p->is_deleted === 1) {
                if ($isAjax) return $this->jsonFail('삭제된 댓글에는 답글을 달 수 없습니다.', 400);
                redirect("/posts/view/{$postId}");
            }
        }

        // 저장
        $row = $this->Comment_model->create_comment($postId, $userId, $body, $parentId);
        if (!$row) return $this->jsonFail('DB 오류', 500);

        $ps = $this->resolvePageSize($postId);

        // 현재 노드 위치(lft)
        $nodeLft = (int)$this->db->query("
            SELECT lft FROM comments WHERE id=? AND post_id=? LIMIT 1
        ", [$row['id'], $postId])->row()->lft;

        // 가시성 기준 몇 번째 댓글인지
        $pos = (int)$this->db->query("
            SELECT COUNT(*) AS cnt
            FROM comments c
            WHERE c.post_id = ?
              AND c.lft <= ?
              AND (c.is_deleted = 0 OR EXISTS (
                  SELECT 1 FROM comments x
                  WHERE x.parent_id = c.id AND x.is_deleted = 0
              ))
        ", [$postId, $nodeLft])->row()->cnt;

        $page = max(1, (int)ceil($pos / $ps));

        // 바로 앞 가시 댓글(anchor)
        $anchorRow = $this->db->query("
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
        $insertAfterId = $anchorRow ? (int)$anchorRow->id : null;

        // 가시성 기준 총 댓글수(페이지네이션 계산용)
        $totalVisible = (int)$this->db->query("
            SELECT COUNT(*) AS cnt
            FROM comments c
            WHERE c.post_id = ?
              AND (c.is_deleted = 0 OR EXISTS (
                    SELECT 1 FROM comments x WHERE x.parent_id=c.id AND x.is_deleted=0
              ))
        ", [$postId])->row()->cnt;

        $totalPages = (int)ceil(($totalVisible ?: 0) / $ps);

        // ✅ 삭제 제외 카운트(헤더 표기용)
        $totalActive = (int)$this->db->query("
            SELECT COUNT(*) AS cnt
            FROM comments WHERE post_id=? AND is_deleted=0
        ", [$postId])->row()->cnt;

        if ($isAjax) {
            return $this->jsonOk([
                'comment'                 => $row,
                'page'                    => $page,
                'insert_after_comment_id' => $insertAfterId,
                'owns'                    => ($row['user_id'] == $userId),
                'total_pages'             => $totalPages,
                'total_count_active'      => $totalActive, // ✅ 헤더용
            ]);
        }

        redirect("/posts/view/{$postId}?page={$page}#c{$row['id']}");
    }

    /** 댓글 페이지 조각 */
    public function page()
    {
        if (!$this->input->is_ajax_request()) return $this->jsonFail('AJAX only', 400);

        $postId = (int)$this->input->get('post_id');
        $page   = max(1, (int)$this->input->get('page'));
        $ps     = $this->resolvePageSize($postId);

        $paged = $this->Comment_model->page_by_post($postId, $page, $ps);

        $reply_to = $this->input->get('reply_to');
        $reply_to = ($reply_to === null || $reply_to === '') ? null : (int)$reply_to;

        $this->template_->viewDefine('comment_list_stub', 'comment_list_stub.tpl');
        $this->template_->viewAssign([
            'post'          => (object)['id'=>$postId],
            'comments'      => $paged['rows'],
            'page'          => $paged['page'],
            'total_pages'   => $paged['total_pages'],
            'session_user_id' => (int)$this->session->userdata('id'),
            'reply_to' => $reply_to,
        ]);
        $html = $this->template_->viewFetch('comment_list_stub');

        // ✅ 삭제 제외 카운트 반환
        $totalActive = (int)$this->db->query("
            SELECT COUNT(*) AS cnt
            FROM comments WHERE post_id=? AND is_deleted=0
        ", [$postId])->row()->cnt;

        return $this->jsonOk([
            'list_html'         => $html,
            'page'              => $paged['page'],
            'total_pages'       => $paged['total_pages'],
            'total_count_active'=> $totalActive, // ✅ 헤더 갱신
        ]);
    }

    /** 댓글 삭제 */
    public function delete()
    {
        $userId = (int)$this->session->userdata('id');
        if (!$userId) return $this->jsonFail('로그인 필요', 401);

        $commentId = (int)$this->input->post('comment_id');
        $postId    = (int)$this->input->post('post_id');
        $ps        = $this->resolvePageSize($postId);

        $ok = $this->Comment_model->soft_delete_comment($commentId, $userId);

        $childVisible = (int)$this->db->query("
            SELECT COUNT(*) AS cnt
            FROM comments WHERE parent_id=? AND is_deleted=0
        ", [$commentId])->row()->cnt;

        // 가시성 기준 count/page
        $totalVisible = (int)$this->db->query("
            SELECT COUNT(*) AS cnt
            FROM comments c
            WHERE c.post_id = ?
              AND (c.is_deleted = 0 OR EXISTS (
                    SELECT 1 FROM comments x WHERE x.parent_id=c.id AND x.is_deleted=0
              ))
        ", [$postId])->row()->cnt;

        $totalPages = (int)ceil(($totalVisible ?: 0) / $ps);

        // ✅ 삭제 제외 댓글 수 반환
        $totalActive = (int)$this->db->query("
            SELECT COUNT(*) AS cnt
            FROM comments WHERE post_id=? AND is_deleted=0
        ", [$postId])->row()->cnt;

        return $this->jsonOk([
            'deleted'           => (bool)$ok,
            'reply_count_after' => $childVisible,
            'total_pages'       => $totalPages,
            'total_count_active'=> $totalActive, // ✅ 헤더용
        ]);
    }

    private function jsonOk($data=[]) {
        return $this->output->set_content_type('application/json')
            ->set_output(json_encode(['ok'=>true,'data'=>$data]));
    }

    private function jsonFail($msg,$code=400) {
        return $this->output->set_status_header($code)
            ->set_content_type('application/json')
            ->set_output(json_encode(['ok'=>false,'msg'=>$msg]));
    }
}
