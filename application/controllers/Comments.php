<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @property CI_Input $input
 * @property CI_Session $session
 * @property Comment_model $Comment_model
 */
class Comments extends MY_Controller
{

    protected $models = ['Comment_model'];

    public function __construct()
    {
        parent::__construct();
    }

    /** 댓글 작성 (루트/대댓글 공통) : POST */
    public function create()
    {
        $userId = (int)$this->session->userdata('id');
        if (!$userId) {
            $this->session->set_flashdata('error', '로그인이 필요합니다.');
            redirect('/auth/login');
            exit;
        }

        $postId   = (int)$this->input->post('post_id');
        $parentId = $this->input->post('parent_id');
        $body     = trim((string)$this->input->post('body'));

        if (!$postId) {
            $this->session->set_flashdata('error', 'post_id가 없습니다.');
            redirect('/posts');
            exit;
        }
        if ($body === '') {
            $this->session->set_flashdata('error', '내용을 입력하세요.');
            redirect("/posts/view/{$postId}");
            exit;
        }

        $parentId = ($parentId === '' || $parentId === null) ? null : (int)$parentId;

        // 저장
        $row = $this->Comment_model->create_comment($postId, $userId, $body, $parentId);
        if (!$row) {
            $this->session->set_flashdata('error', 'DB 저장 중 오류가 발생했습니다.');
            redirect("/posts/view/{$postId}");
            exit;
        }

        $newId = (int)$row['id'];

        // 전위순서에서 새 댓글이 속한 페이지 계산 (page_size=50 고정)
        $page = $this->Comment_model->calc_page_of_comment($postId, $newId, 50);

        // PRG: 해당 페이지의 댓글 위치로 이동
        redirect("/posts/view/{$postId}?page={$page}#c{$newId}");
        exit;
    }

    /** 댓글 삭제(소프트) : POST */
    public function delete()
    {
        $userId = (int)$this->session->userdata('id');
        if (!$userId) {
            $this->session->set_flashdata('error', '로그인이 필요합니다.');
            redirect('/auth/login');
            exit;
        }

        $commentId = (int)$this->input->post('comment_id');
        $postId    = (int)$this->input->post('post_id');
        $page      = (int)$this->input->post('page'); // 뷰에서 숨김 input으로 보내면 UX 좋음

        if (!$commentId || !$postId) {
            $this->session->set_flashdata('error', '잘못된 요청입니다.');
            redirect('/posts');
            exit;
        }

        $ok = $this->Comment_model->soft_delete_comment($commentId, $userId);
        if (!$ok) {
            $this->session->set_flashdata('error', '삭제할 수 없습니다.');
        } else {
            $this->session->set_flashdata('success', '삭제되었습니다.');
        }

        $dest = "/posts/view/{$postId}";
        if ($page > 0) $dest .= "?page={$page}";
        redirect($dest);
        exit;
    }
}
