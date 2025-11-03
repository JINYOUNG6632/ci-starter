<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @property File_model   $File_model
 * @property CI_Session   $session
 * @property CI_Input     $input
 */
class Files extends MY_Controller
{

    protected $models        = ['File_model'];

    public function __construct()
    {
        parent::__construct();
    }

    /** /files/download/{id} : 첨부 다운로드 */
    public function download($id)
    {
        $id = (int)$id;

        $file = $this->File_model->get_for_download($id);
        if (!$file) { show_404(); return; }

        $abs  = $file['_abs_path'];
        $name = $file['original_filename'];     
        $mime = $file['content_type'] ?: 'application/octet-stream';
        $size = (int)$file['file_size'];         

        // 안전 헤더
        header('Content-Description: File Transfer');
        header('Content-Type: '.$mime);
        header('Content-Length: '.$size);
        header('Content-Disposition: attachment; filename="'.rawurlencode($name).'"');
        header('Content-Transfer-Encoding: binary');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, must-revalidate');
        header('Pragma: public');

        // 버퍼 정리 후 전송 (환경에 따라 유용)
        if (function_exists('ob_get_level')) {
            while (ob_get_level() > 0) { @ob_end_clean(); }
        }

        readfile($abs);
        exit;
    }

    /** POST /files/delete/{id} : 첨부 개별 소프트 삭제 */
    public function delete($id)
    {
        $id = (int)$id;

        // 메서드 강제: POST만 허용
        if (strtoupper($this->input->method(TRUE)) !== 'POST') {
            show_error('Method Not Allowed', 405);
            return;
        }

        $this->_check_login();

        // 파일 + 소유자 조회 (File_model에 get_one_with_owner 필요)
        $f = $this->File_model->get_one_with_owner($id);
        if (!$f) {
            $this->session->set_flashdata('error', '파일을 찾을 수 없습니다.');
            redirect($_SERVER['HTTP_REFERER'] ?? '/ci-starter/posts');
            exit;
        }

        // 소유자 검증: 파일이 속한 게시글의 작성자와 현재 사용자 일치?
        $current_user_id = (int)$this->session->userdata('id');
        if ((int)$f->post_owner_id !== $current_user_id) {
            $this->session->set_flashdata('error', '첨부를 삭제할 권한이 없습니다.');
            redirect($_SERVER['HTTP_REFERER'] ?? '/ci-starter/posts/view/'.$f->post_id);
            exit;;
        }

        // 소프트 삭제
        $ok = $this->File_model->soft_delete_one($id);
        $this->session->set_flashdata($ok ? 'success' : 'error', $ok ? '첨부를 삭제했습니다.' : '삭제에 실패했습니다.');

        // 원래 페이지로 리다이렉트
        $back = $_SERVER['HTTP_REFERER'] ?? '/ci-starter/posts/view/'.$f->post_id;
        redirect($back);
        exit;
    }
}
