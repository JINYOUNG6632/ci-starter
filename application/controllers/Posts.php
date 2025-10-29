<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @property CI_Form_validation $form_validation
 * @property CI_Input $input
 * @property CI_Session $session
 * @property CI_DB_query_builder $db
 * @property Post_model $Post_model
 * @property Category_model $Category_model
 */
class Posts extends MY_Controller
{
    public $table = 'posts';

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Post_model');
        $this->load->model('Category_model');
    }

    private function _check_login()
    {
        if (!$this->session->userdata('logged_in')) {
            $this->session->set_flashdata('error', '로그인이 필요합니다.');
            redirect('auth/login');
        }
    }

    public function index($category_id = 1)
    {
        $data['posts']    = $this->Post_model->get_posts_by_category($category_id);
        $data['category'] = $this->Category_model->get_category_by_id($category_id);
        $data['title']    = $data['category']->name;

        $this->template_->viewAssign($data);
        $this->template_->viewDefine('content', 'list_view.tpl');
        $this->template_->viewDefine('layout_common', 'true');
    }

    public function view($post_id)
    {
        $data['post']            = $this->Post_model->get_post_by_id($post_id);
        $data['error']           = $this->session->flashdata('error');
        $data['session_user_id'] = $this->session->userdata('id');
        $data['title']           = $data['post'] ? $data['post']->title : '게시글 상세';

        $this->template_->viewAssign($data);
        $this->template_->viewDefine('content', 'post_detail_view.tpl');
        $this->template_->viewDefine('layout_common', 'true');
    }

    public function write_form()
    {
        $this->_check_login();

        $categories = $this->Category_model->get_all_categories();

        $data = [
            'is_edit'               => false,
            'form_action'           => '/ci-starter/posts/write_process',
            'validation_errors'     => validation_errors(),

            'categories'            => $categories,
            'selected_category_id'  => set_value('category_id', ''),

            'title_value'           => set_value('title', ''),
            'body_value'            => set_value('body', ''),

            'title'                 => '새 게시글 작성'
        ];

        $this->template_->viewAssign($data);
        $this->template_->viewDefine('content', 'post_form_view.tpl');
        $this->template_->viewDefine('layout_common', 'true');
    }

    public function write_process()
    {
        $this->_check_login();

        $this->form_validation->set_rules('category_id', '카테고리', 'required|integer');
        $this->form_validation->set_rules('title', '제목', 'required|trim');
        $this->form_validation->set_rules('body', '내용', 'required');

        if ($this->form_validation->run() == false) {

            $categories = $this->Category_model->get_all_categories();

            $data = [
                'is_edit'               => false,
                'form_action'           => '/ci-starter/posts/write_process',
                'validation_errors'     => validation_errors(),

                'categories'            => $categories,
                'selected_category_id'  => set_value('category_id', ''),

                'title_value'           => set_value('title', ''),
                'body_value'            => set_value('body', ''),

                'title'                 => '새 게시글 작성',
            ];

            $this->template_->viewAssign($data);
            $this->template_->viewDefine('content', 'post_form_view.tpl');
            $this->template_->viewDefine('layout_common', 'true');
            return;
        }

        $insert = [
            'title'       => $this->input->post('title'),
            'body'        => $this->input->post('body'),
            'category_id' => $this->input->post('category_id'),
            'user_id'     => $this->session->userdata('id'),
        ];

        $new_post_id = $this->Post_model->create_post($insert);
        redirect('posts/view/' . $new_post_id);
    }

    public function edit_form($post_id)
    {
        $this->_check_login();

        $post = $this->Post_model->get_post_by_id($post_id);
        if (!$post || $post->user_id != $this->session->userdata('id')) {
            $this->session->set_flashdata('error', '수정권한이 없습니다.');
            redirect('posts/view/' . $post_id);
            return;
        }

        $categories = $this->Category_model->get_all_categories();

        $data = [
            'is_edit'               => true,
            'form_action'           => '/ci-starter/posts/edit_process/' . $post->id,
            'validation_errors'     => validation_errors(),

            'categories'            => $categories,
            'selected_category_id'  => set_value('category_id', $post->category_id),

            'title_value'           => set_value('title', $post->title),
            'body_value'            => set_value('body', $post->body),

            'title'                 => '게시글 수정',
        ];

        $this->template_->viewAssign($data);
        $this->template_->viewDefine('content', 'post_form_view.tpl');
        $this->template_->viewDefine('layout_common', 'true');
    }

    public function edit_process($post_id)
    {
        $this->_check_login();

        $post = $this->Post_model->get_post_by_id($post_id);
        if (!$post || $post->user_id != $this->session->userdata('id')) {
            $this->session->set_flashdata('error', '권한이 없습니다.');
            redirect('posts/view/' . $post_id);
            return;
        }

        $this->form_validation->set_rules('category_id', '카테고리', 'required|integer');
        $this->form_validation->set_rules('title', '제목', 'required|trim');
        $this->form_validation->set_rules('body', '내용', 'required');

        if ($this->form_validation->run() == false) {

            $categories = $this->Category_model->get_all_categories();

            $data = [
                'is_edit'               => true,
                'form_action'           => '/ci-starter/posts/edit_process/' . $post->id,
                'validation_errors'     => validation_errors(),

                'categories'            => $categories,
                'selected_category_id'  => set_value('category_id', $post->category_id),

                'title_value'           => set_value('title', $post->title),
                'body_value'            => set_value('body', $post->body),

                'title'                 => '게시글 수정',
            ];

            $this->template_->viewAssign($data);
            $this->template_->viewDefine('content', 'post_form_view.tpl');
            $this->template_->viewDefine('layout_common', 'true');
            return;
        }

        $update = [
            'title'       => $this->input->post('title'),
            'body'        => $this->input->post('body'),
            'category_id' => $this->input->post('category_id'),
        ];

        $this->Post_model->update_post($post_id, $update);
        redirect('posts/view/' . $post_id);
    }

    public function delete($post_id)
    {
        $this->_check_login();

        $post = $this->Post_model->get_post_by_id($post_id);
        if (!$post || $post->user_id != $this->session->userdata('id')) {
            $this->session->set_flashdata('error', '권한이 없습니다.');
            redirect('posts/view/' . $post_id);
            return;
        }

        $this->Post_model->delete_post($post_id);
        redirect('posts');
    }
}
