<?php

/**
 * post_controller_constructor
 *
 * 컨트롤러 메소드가 실행되기전 필요한 처리(컨트롤러 인스턴스화 직후)
 */
class post_controller_constructor
{

    private $ci = NULL;

    public function init()
    {
        $this->ci =& get_instance();

        define('_CONTROLLERS', $this->ci->router->fetch_class());
        define('_METHOD', $this->ci->router->fetch_method());

        $this->ci->load->library("user_agent");
        define('_IS_MOBILE', $this->ci->agent->is_mobile());

        $this->ci->load->model('Category_model');
        $all_categories = $this->ci->Category_model->get_all_categories();

        $common_data = array(
            'header_categories' => $all_categories,
            'is_logged_in' => $this->ci->session->userdata('logged_in'),
            'session_username' => $this->ci->session->userdata('username')
        );

        $this->ci->template_->viewAssign($common_data);
    }
}