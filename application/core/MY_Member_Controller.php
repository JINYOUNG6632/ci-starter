<?php
defined('BASEPATH') OR exit('No direct script access allowed');


// 회원 보안 전용 컨트롤러
class MY_Member_Controller extends MY_Controller {

    public function __construct()
    {
        parent::__construct();

        if (! $this->session->userdata('logged_in'))
        {
            $this->session->set_flashdata('error', '로그인이 필요합니다.');
            redirect('auth/login');
        }
    }
}