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
    public function __construct()
    {
        parent::__construct();

        $this->load->model('User_model');
    }

    public function index() {
        echo 'login';
    }

    public function register_form() {
        $this->load->view('register_view');
    }

    public function register_process() {
        $this->form_validation->set_rules('username', '이름 (닉네임)', 'required|trim');
        $this->form_validation->set_rules('user_id', '아이디', 'required|is_unique[users.user_id]');
        $this->form_validation->set_rules('user_password', '비밀번호', 'required');

        if ($this->form_validation->run() == FALSE) {
            $this->load->view('register_view');
        } else {
            $username = $this->input->post('username');
            $user_id = $this->input->post('user_id');
            $password = $this->input->post('user_password');

            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $this->User_model->create_user($username, $user_id, $hashed_password);

            redirect('auth/login');
        }
    }

    public function login_form() {
        $this->load->view('login_view');
    }

    public function login_process() {
        $this->form_validation->set_rules('user_id', '아이디', 'required|trim');
        $this->form_validation->set_rules('user_password', '비밀번호', 'required');

        if($this->form_validation->run() == FALSE) {
            $this->load->view('login_view');
        } else {
            $user_id = $this->input->post('user_id');
            $password = $this->input->post('user_password');

            $user = $this->User_model->verify_user($user_id, $password);

            if  ($user) {
                $session_data = [
                    'id' => $user->id,
                    'user_id' => $user->user_id,
                    'username' => $user->username,
                    'logged_in' => TRUE
                ];

                $this->session->set_userdata($session_data);

                redirect('/');
            } else {
                $this->session->set_flashdata('error', '회원정보와 일치하지 않습니다.');
                redirect('auth/login');
            }
        }
    }

    public function logout() {
        $this->session->sess_destroy();
        redirect('/');
    }
}