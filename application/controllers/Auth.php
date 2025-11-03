<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @property CI_Form_validation $form_validation
 * @property CI_Input $input
 * @property CI_Session $session
 * @property CI_DB_query_builder $db
 * @property User_model $User_model
 */
class Auth extends MY_Controller
{
    protected $models = ['User_model'];

    public function __construct()
    {
        parent::__construct();
    }

    /** 기본 엔드포인트 -> 로그인 폼으로 */
    public function index()
    {
        redirect('/auth/login');
        exit;
    }

    /** 회원가입 폼 */
    public function register_form()
    {
        // 페이지 전용 CSS만 등록
        $this->css('register_view.css', time());

        // 공통 값(BASE_CSS/CSS/JS/is_logged_in 등)은 renderEmpty()가 자동 주입
        $this->renderEmpty('register_view.tpl', [
            'title' => '회원가입',
            'error' => $this->session->flashdata('error'),
        ]);
    }

    /** 회원가입 처리 */
    public function register_process()
    {
        $this->form_validation->set_rules('username', '이름 (닉네임)', 'required|trim');
        $this->form_validation->set_rules('user_id', '아이디', 'required|is_unique[users.user_id]');
        $this->form_validation->set_rules('user_password', '비밀번호', 'required');

        if ($this->form_validation->run() == FALSE) {
            // 검증 실패 시 폼 재표시
            return $this->register_form();
        }

        $username = $this->input->post('username');
        $user_id  = $this->input->post('user_id');
        $password = $this->input->post('user_password');

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $this->User_model->create_user($username, $user_id, $hashed_password);

        redirect('auth/login');
        exit;
    }

    /** 로그인 폼 */
    public function login_form()
    {
        $this->css('login_view.css', time());

        $this->renderEmpty('login_view.tpl', [
            'title'   => '로그인',
            'error'   => $this->session->flashdata('error'),
            'user_id' => $this->input->post('user_id'), // 실패 후 유지용
        ]);
    }

    /** 로그인 처리 */
    public function login_process()
    {
        $this->form_validation->set_rules('user_id', '아이디', 'required|trim');
        $this->form_validation->set_rules('user_password', '비밀번호', 'required');

        if ($this->form_validation->run() == FALSE) {
            return $this->login_form();
        }

        $user_id  = $this->input->post('user_id');
        $password = $this->input->post('user_password');

        $user = $this->User_model->verify_user($user_id, $password);

        if ($user) {
            $session_data = [
                'id'        => $user->id,
                'user_id'   => $user_id,
                'username'  => $user->username,
                'logged_in' => TRUE,
            ];
            $this->session->set_userdata($session_data);

            redirect('/posts');
            exit;
        }

        $this->session->set_flashdata('error', '회원정보와 일치하지 않습니다.');
        redirect('/auth/login');
        exit;
    }

    /** 로그아웃 */
    public function logout()
    {
        $this->session->sess_destroy();
        redirect('/');
        exit;
    }
}
