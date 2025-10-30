<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @property CI_Input $input
 * @property CI_Session $session
 * @property Comment_model $Comment_model
 */
class Comments extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Comment_model');
        $this->load->library('session');
        $this->output->set_content_type('application/json');
    }

    /* ---------------------------------------
     * 내부 유틸: JSON 응답 헬퍼
     * ------------------------------------- */

    private function _json($arr, $status_code = 200)
    {
        $this->output->set_status_header($status_code);
        echo json_encode($arr);
    }

    private function _json_error($msg, $status_code = 400)
    {
        $this->_json(['ok' => false, 'message' => $msg], $status_code);
    }

    private function _json_success($data = [])
    {
        $data['ok'] = true;
        $this->_json($data);
    }

    /* ---------------------------------------
     * 댓글 목록 조회 (루트 댓글 or 특정 댓글의 대댓글)
     * GET /comments/list?post_id=...&parent_id=...&offset=...&limit=...
     *
     * - post_id: 필수
     * - parent_id: 없으면(또는 빈 문자열이면) 루트 댓글만
     * - offset: 기본 0
     * - limit: 기본 20
     *
     * 응답:
     * {
     *   ok: true,
     *   comments: [
     *     {
     *       id, post_id, user_id, username, body,
     *       created_at, parent_id, reply_count, is_deleted,
     *       can_delete
     *     }, ...
     *   ],
     *   has_more: true/false,
     *   next_offset: number
     * }
     * ------------------------------------- */
    public function list()
    {
        // 1. 인풋 파라미터
        $post_id   = (int)$this->input->get('post_id');
        $parent_id = $this->input->get('parent_id'); // null or 숫자 문자열
        $offset    = (int)$this->input->get('offset');
        $limit     = (int)$this->input->get('limit');

        if (!$post_id) {
            return $this->_json_error('post_id가 필요합니다.', 400);
        }

        if ($limit <= 0) {
            $limit = 20;
        }
        if ($offset < 0) {
            $offset = 0;
        }

        // parent_id 정규화
        if ($parent_id === '' || $parent_id === null) {
            $parent_id = null;
        } else {
            $parent_id = (int)$parent_id;
        }

        // 2. 모델에서 가져오기
        $result = $this->Comment_model->get_comments($post_id, $parent_id, $limit, $offset);

        $rows        = $result['comments'];
        $has_more    = $result['has_more'];
        $next_offset = $result['next_offset'];

        // 3. 현재 로그인 유저 id
        $current_user_id = (int)$this->session->userdata('id');

        // 4. 각 댓글마다 can_delete 붙여주기
        foreach ($rows as &$row) {
            // row는 result_array()에서 온 associative array
            $row['can_delete'] = (
                $current_user_id &&
                isset($row['user_id']) &&
                (int)$row['user_id'] === $current_user_id
            ) ? 1 : 0;
        }
        unset($row);

        // 5. 응답
        return $this->_json_success([
            'comments'    => $rows,
            'has_more'    => $has_more,
            'next_offset' => $next_offset,
        ]);
    }

    /* ---------------------------------------
     * 댓글 작성
     * POST /comments/create
     * form-data:
     *   post_id
     *   parent_id (optional)
     *   body
     *
     * 응답:
     * {
     *   ok: true,
     *   comment: { ... 새로 생성된 댓글 한 개 ... }
     * }
     * ------------------------------------- */
    public function create()
    {
        $user_id = (int)$this->session->userdata('id');
        if (!$user_id) {
            return $this->_json_error('로그인이 필요합니다.', 401);
        }

        $post_id   = (int)$this->input->post('post_id');
        $parent_id = $this->input->post('parent_id');
        $body      = trim((string)$this->input->post('body'));

        if (!$post_id) {
            return $this->_json_error('post_id가 없습니다.', 400);
        }
        if ($body === '') {
            return $this->_json_error('내용을 입력하세요.', 400);
        }

        if ($parent_id === '' || $parent_id === null) {
            $parent_id = null;
        } else {
            $parent_id = (int)$parent_id;
        }

        // parent 검증 생략 가능하지만 원래는 여기서 post_id 일치 여부 체크해줬지.

        $new_comment = $this->Comment_model->create_comment($post_id, $user_id, $body, $parent_id);
        if (!$new_comment) {
            return $this->_json_error('DB 저장 중 오류가 발생했습니다.', 500);
        }

        // ✅ 방금 쓴 건 내 댓글이니까 삭제 권한 O
        $new_comment['can_delete'] = 1;

        return $this->_json_success([
            'comment' => $new_comment,
        ]);
    }

    /* ---------------------------------------
     * 댓글 삭제 (soft delete)
     * POST /comments/delete
     * form-data:
     *   comment_id
     *
     * 본인만 삭제 가능.
     * ------------------------------------- */
    public function delete()
    {
        // 1. 로그인 체크
        $user_id = (int)$this->session->userdata('id');
        if (!$user_id) {
            return $this->_json_error('로그인이 필요합니다.', 401);
        }

        // 2. 파라미터 받기
        $comment_id = (int)$this->input->post('comment_id');
        if (!$comment_id) {
            return $this->_json_error('comment_id가 없습니다.', 400);
        }

        // 3. soft delete 시도
        $ok = $this->Comment_model->soft_delete_comment($comment_id, $user_id);

        if (!$ok) {
            // 실패:
            //  - 없는 댓글 or
            //  - 현재 유저가 작성자가 아님 → update 안 됨
            return $this->_json_error('삭제할 수 없습니다.', 403);
        }

        // 4. 성공 응답
        return $this->_json_success([
            'comment_id' => $comment_id
        ]);
    }
}
