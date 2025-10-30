<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @property CI_Form_validation $form_validation
 * @property CI_Input $input
 * @property CI_Session $session
 * @property CI_DB_query_builder $db
 * @property Post_model $Post_model
 * @property Category_model $Category_model
 * @property File_model $File_model
 */
class Posts extends MY_Controller
{
    public $table = 'posts';

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Post_model');
        $this->load->model('Category_model');
        $this->load->model('File_model');
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
        $posts    = $this->Post_model->get_posts_by_category($category_id);
        $category = $this->Category_model->get_category_by_id($category_id);

        // 공통 CSS는 BASE_CSS에 직접 링크
        $baseCss = '<link rel="stylesheet" href="/ci-starter/assets/css/layout_common.css">';

        // 화면 전용 CSS/JS는 Optimizer 사용
        $this->css('list_view.css', '20251030'); // 캐시 무력화 버전
        // $this->js('list_view.js', '20251030');  // 필요 시

        $tags = $this->optimizer->makeOptimizerScriptTag();

        $data = [
            'posts'    => $posts,
            'category' => $category,
            'title'    => $category ? $category->name : '게시글 목록',

            'BASE_CSS' => $baseCss,                // 공통
            'CSS'      => $tags['css_optimizer'],  // 화면 전용
            'JS'       => $tags['js_optimizer'],   // 화면 전용
        ];

        $this->template_->viewAssign($data);
        $this->template_->viewDefine('content', 'list_view.tpl');
        $this->template_->viewDefine('layout_common', 'true');
    }

    public function view($post_id)
    {
        $post = $this->Post_model->get_post_by_id($post_id);

        // 공통 CSS
        $baseCss = '<link rel="stylesheet" href="/ci-starter/assets/css/layout_common.css">';

        // 화면 전용
        $this->css('post_detail.css', '20251030');
        $this->js('post_detail.js', '20251030');

        $tags = $this->optimizer->makeOptimizerScriptTag();

        // ▼ 첨부 목록 내려주기
        $attachments = $post ? $this->File_model->list_by_post($post_id) : [];

        $data = [
            'post'             => $post,
            'post_id'          => $post ? $post->id : null,
            'session_user_id'  => $this->session->userdata('id'),
            'title'            => $post ? $post->title : '게시글 상세',

            // ▼ 상세 뷰에서 사용할 첨부 목록
            'attachments'      => $attachments,

            'BASE_CSS'         => $baseCss,               // 공통
            'CSS'              => $tags['css_optimizer'], // 화면 전용
            'JS'               => $tags['js_optimizer'],  // 화면 전용
        ];

        $this->template_->viewDefine('comment_form', 'comment_form.tpl');
        $this->template_->viewDefine('comment_list_stub', 'comment_list_stub.tpl');
        $this->template_->viewDefine('comment_section', 'comment_section.tpl');

        $this->template_->viewAssign($data);
        $this->template_->viewDefine('content', 'post_detail_view.tpl');
        $this->template_->viewDefine('layout_common', 'true');
    }

    public function write_form()
    {
        $this->_check_login();

        $categories = $this->Category_model->get_all_categories();

        // 공통 CSS
        $baseCss = '<link rel="stylesheet" href="/ci-starter/assets/css/layout_common.css">';

        // 화면 전용
        $this->css('post_form_view.css', '20251030');

        $tags = $this->optimizer->makeOptimizerScriptTag();

        $data = [
            'is_edit'               => false,
            'form_action'           => '/ci-starter/posts/write_process',
            'validation_errors'     => validation_errors(),

            'categories'            => $categories,
            'selected_category_id'  => set_value('category_id', ''),
            'title_value'           => set_value('title', ''),
            'body_value'            => set_value('body', ''),
            'title'                 => '새 게시글 작성',

            'attachments'           => [],

            'BASE_CSS' => $baseCss,
            'CSS'      => $tags['css_optimizer'],
            'JS'       => $tags['js_optimizer'],
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

            // 공통 CSS
            $baseCss = '<link rel="stylesheet" href="/ci-starter/assets/css/layout_common.css">';

            // 화면 전용
            $this->css('post_form_view.css', '20251030');
            $tags = $this->optimizer->makeOptimizerScriptTag();

            $data = [
                'is_edit'               => false,
                'form_action'           => '/ci-starter/posts/write_process',
                'validation_errors'     => validation_errors(),

                'categories'            => $categories,
                'selected_category_id'  => set_value('category_id', ''),

                'title_value'           => set_value('title', ''),
                'body_value'            => set_value('body', ''),

                'title'                 => '새 게시글 작성',

                'attachments'           => [],

                'BASE_CSS' => $baseCss,
                'CSS'      => $tags['css_optimizer'],
                'JS'       => $tags['js_optimizer'],
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

        // 1) 게시글 생성
        $new_post_id = $this->Post_model->create_post($insert);

        // 2) ▼ 파일 첨부 처리 (폼의 input name="attachments[]" 기준)
        //    post_form_view.tpl 은 enctype="multipart/form-data" 여야 함
        $this->File_model->upload_and_attach($new_post_id, 'attachments');

        // 3) 상세로 이동
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

        // 공통 CSS
        $baseCss = '<link rel="stylesheet" href="/ci-starter/assets/css/layout_common.css">';

        // 화면 전용
        $this->css('post_form_view.css', '20251030');
        $tags = $this->optimizer->makeOptimizerScriptTag();

        $data = [
            'is_edit'               => true,
            'form_action'           => '/ci-starter/posts/edit_process/' . $post->id,
            'validation_errors'     => validation_errors(),

            'categories'            => $categories,
            'selected_category_id'  => set_value('category_id', $post->category_id),

            'title_value'           => set_value('title', $post->title),
            'body_value'            => set_value('body', $post->body),

            'title'                 => '게시글 수정',

            'attachments'           => [],

            'BASE_CSS' => $baseCss,
            'CSS'      => $tags['css_optimizer'],
            'JS'       => $tags['js_optimizer'],
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
            // (생략) 기존 네 코드 그대로 + 항상 attachments 내려주기
            $attachments = $this->File_model->list_by_post($post_id);
            $data = [ /* ... */ 'attachments' => $attachments ?: [] ];
            // (생략) 렌더
            return;
        }

        // 1) 게시글 업데이트
        $update = [
            'title'       => $this->input->post('title'),
            'body'        => $this->input->post('body'),
            'category_id' => $this->input->post('category_id'),
        ];
        $this->Post_model->update_post($post_id, $update);

        // 2) ✅ 체크한 첨부들 일괄 소프트삭제
        $delete_ids = (array)$this->input->post('delete_attachments');
        if (!empty($delete_ids)) {
            foreach ($delete_ids as $fid) {
                $fid = (int)$fid;
                // 소유자 검증: 내 게시글의 파일만 지움
                $f = $this->File_model->get_one_with_owner($fid);
                if ($f && (int)$f->post_owner_id === (int)$this->session->userdata('id')) {
                    $this->File_model->soft_delete_one($fid);
                }
            }
        }

        // 3) 신규 첨부 업로드도 병행
        $this->File_model->upload_and_attach($post_id, 'attachments');

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

        // 게시글 삭제
        $this->Post_model->delete_post($post_id);

        redirect('posts');
    }
}
