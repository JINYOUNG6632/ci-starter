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
class Posts extends Member_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Post_model');
        $this->load->model('Category_model');
    }

    public function index($category_id = 1) 
    {
        $data['posts'] = $this->Post_model->get_post_by_category($category_id);
        $data['category'] = $this->Category_model->get_category_by_id($category_id);

        $this->load->view('list_view', $data);
    }

    public function view($post_id)
    {
        $data['post'] = $this->Post_model->get_post_by_id($post_id);
        $this->load->view('post_detail_view', $data);
    }

    public function write_form()
    {
        // 추후에 카테고리를 셀렉할 수 있도록 변경
        // 추후 파일도 업로드 가능하도록 변경
        $data['categories'] = $this->Category_model->get_all_categories();
        $data['post'] = null;

        $this->load->view('post_form_view', $data);
    }

    public function write_process()
    {
        // 추후에 writeform에서 카테고리를 셀렉할 수 있도록 로딩할 때 변경
        $this->form_validation->set_rules('category_id', '카테고리', 'required|integer');
        $this->form_validation->set_rules('title', '제목', 'required|trim');
        $this->form_validation->set_rules('body', '내용', 'required');

        if ($this->form_validation->run() == false) {
            // 바로 입력창으로 다시 보내면 내용이 날아갈 수 있으니 방법 생각
            $this->write_form();
        } else {
            $data = array(
                'title' => $this->input->post('title'),
                'body' => $this->input->post('body'),
                'category_id' => $this->input->post('category_id'),
                'user_id' => $this->session->userdata('id')
            );
        };

        $new_post_id = $this->Post_model->create_post($data);
        redirect('posts/view/' . $new_post_id);
    }

    public function edit_form($post_id)
    {
        $post = $this->Post_model->get_post_by_id($post_id);

        if (!$post || $post->user_id != $this->session->userdata('id')) {
            $this->session->set_flashdata('error', '수정권한이 없습니다.');
            redirect('posts/view/' . $post_id);
            return;
        }

        // 카테고리를 다 불러서 데이터에 넣어준다? 이상함
        $data['categories'] = $this->Category_model->get_all_categories();
        $data['post'] = $post;

        $this->load->view('post_form_view', $data);
    }

    public function edit_process($post_id)
    {
        $post = $this->Post_model->get_post_by_id($post_id);

        if (!$post || $post->user_id != $this->session->userdata('id')) {
            $this->session->set_flashdata('error', '권한이 없습니다.');
            redirect('posts/view/' . $post_id);
            return;
        }

        // 마찬가지로 추후 파일추가시 변경
        $this->form_validation->set_rules('category_id', '카테고리', 'required|integer');
        $this->form_validation->set_rules('title', '제목', 'required|trim');
        $this->form_validation->set_rules('body', '내용', 'required');

        if($this->form_validation->run() == false) {
            $this->edit_form($post_id);
        } else {
            $data = array(
                'title' => $this->input->post('title'),
                'body' => $this->input->post('body'),
                'category_id' => $this->input->post('category_id')
            );

            $this->Post_model->update_post($post_id, $data);
            redirect('posts/view/' . $post_id);
        }
    }

    public function delete($post_id)
    {
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