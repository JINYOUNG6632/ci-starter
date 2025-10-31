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
 * @property Comment_model $Comment_model
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
        $this->load->model('Comment_model'); // ✅ 댓글 서버렌더 전환용
        $this->load->library('pagination');
        $this->load->library('session');
        $this->load->helper(['url', 'form']);
    }

    private function _check_login()
    {
        if (!$this->session->userdata('logged_in')) {
            $this->session->set_flashdata('error', '로그인이 필요합니다.');
            redirect('auth/login');
        }
    }

    /** 목록 */
    public function index($category_id = 1)
    {
        // --- 검색어 & 페이지 파라미터 -------------------------
        $q       = trim((string)$this->input->get('q', TRUE));
        $perPage = (int)($this->input->get('per_page', TRUE) ?: 10);
        $page    = (int)($this->input->get('page', TRUE) ?: 1);
        if ($perPage <= 0) $perPage = 10;
        if ($page <= 0)    $page    = 1;
        $offset  = ($page - 1) * $perPage;

        // --- 카테고리 정보 ------------------------------------
        $category = $this->Category_model->get_category_by_id($category_id);

        // --- 총 개수(제목 검색 + 카테고리 반영) ----------------
        // Post_model에 count_by_title($q, $category_id)가 있어야 합니다.
        $total = $this->Post_model->count_by_title($q, $category_id);

        // --- 페이지네이션 설정 (쿼리스트링 유지) ---------------
        $config = [
            'base_url'             => site_url('posts/index/' . $category_id),
            'total_rows'           => $total,
            'per_page'             => $perPage,
            'page_query_string'    => TRUE,
            'query_string_segment' => 'page',
            'reuse_query_string'   => TRUE,
            'use_page_numbers'     => TRUE,
            'num_links'            => 2,

            'full_tag_open'  => '<ul class="pagination">',
            'full_tag_close' => '</ul>',
            'first_tag_open' => '<li class="page-item"><span class="page-link">',
            'first_tag_close'=> '</span></li>',
            'last_tag_open'  => '<li class="page-item"><span class="page-link">',
            'last_tag_close' => '</span></li>',
            'next_tag_open'  => '<li class="page-item"><span class="page-link">',
            'next_tag_close' => '</span></li>',
            'prev_tag_open'  => '<li class="page-item"><span class="page-link">',
            'prev_tag_close' => '</span></li>',
            'cur_tag_open'   => '<li class="page-item active"><span class="page-link">',
            'cur_tag_close'  => '</span></li>',
            'num_tag_open'   => '<li class="page-item"><span class="page-link">',
            'num_tag_close'  => '</span></li>',
        ];
        $this->pagination->initialize($config);

        // --- 목록 데이터 ---------------------------------------
        // Post_model에 list_by_title($limit, $offset, $q, $category_id)가 있어야 합니다.
        $posts = $this->Post_model->list_by_title($perPage, $offset, $q, $category_id);

        // 공통 CSS
        $baseCss = '<link rel="stylesheet" href="/ci-starter/assets/css/layout_common.css">';

        // 화면 전용 CSS/JS는 Optimizer 사용
        $this->css('list_view.css', time());
        $tags = $this->optimizer->makeOptimizerScriptTag();

        $data = [
            'title'      => $category ? $category->name : '게시글 목록',
            'category'   => $category,
            'posts'      => $posts,
            'total'      => $total,
            'page'       => $page,
            'per_page'   => $perPage,
            'q'          => $q,
            'pagination' => $this->pagination->create_links(),
            'BASE_CSS'   => $baseCss,
            'CSS'        => $tags['css_optimizer'],
            'JS'         => $tags['js_optimizer'],
        ];

        $this->template_->viewAssign($data);
        $this->template_->viewDefine('content', 'list_view.tpl');
        $this->template_->viewDefine('layout_common', 'true');
    }

    /** 상세 + 댓글 서버렌더 페이지네이션(50) */
    public function view($post_id)
    {
        $postId = (int)$post_id;
        $post   = $this->Post_model->get_post_by_id($postId);

        // 공통 CSS
        $baseCss = '<link rel="stylesheet" href="/ci-starter/assets/css/layout_common.css">';

        $this->css('post_detail.css', time());

        $tags = $this->optimizer->makeOptimizerScriptTag();

        // 첨부 목록
        $attachments = $post ? $this->File_model->list_by_post($postId) : [];

        // 댓글 페이지네이션 (서버렌더)
        $page     = max(1, (int)$this->input->get('page'));
        $reply_to = $this->input->get('reply_to'); // 해당 댓글 아래에 대댓글 폼 보이기
        $reply_to = ($reply_to === null || $reply_to === '') ? null : (int)$reply_to;

        $commentPage = $this->Comment_model->page_by_post($postId, $page, 10);

            $this->db->from('comments');
            $this->db->where('post_id', $postId);
            $this->db->where('is_deleted', 0);
            $total_comment_count = (int) $this->db->count_all_results();

        $data = [
            'post'             => $post,
            'post_id'          => $post ? $post->id : null,
            'session_user_id'  => $this->session->userdata('id'),
            'title'            => $post ? $post->title : '게시글 상세',

            'attachments'      => $attachments,

            // 댓글 데이터 (서버 렌더)
            'comments'         => $commentPage['rows'],
            'page'             => $commentPage['page'],
            'total_pages'      => $commentPage['total_pages'],
            'page_size'        => $commentPage['page_size'],
            'reply_to'         => $reply_to,

            'total_comment_count' => $total_comment_count,

            'BASE_CSS'         => $baseCss,
            'CSS'              => $tags['css_optimizer'],
            'JS'               => $tags['js_optimizer'],
        ];

        $this->template_->viewAssign($data);
        $this->template_->viewDefine('content', 'post_detail_view.tpl');
        $this->template_->viewDefine('comment_section',    'comment_section.tpl');
        $this->template_->viewDefine('comment_form',       'comment_form.tpl');
        $this->template_->viewDefine('comment_list_stub',  'comment_list_stub.tpl');
        $this->template_->viewDefine('file_view', 'file_view.tpl');

        $this->template_->viewDefine('layout_common', 'true');
    }

    /** 작성 폼 */
    public function write_form()
    {
        $this->_check_login();

        $categories = $this->Category_model->get_all_categories();

        // 공통 CSS
        $baseCss = '<link rel="stylesheet" href="/ci-starter/assets/css/layout_common.css">';
        // 화면 전용
        $this->css('post_form_view.css', time());
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
            'BASE_CSS'              => $baseCss,
            'CSS'                   => $tags['css_optimizer'],
            'JS'                   => $tags['js_optimizer'],
        ];

        $this->template_->viewAssign($data);
        $this->template_->viewDefine('content', 'post_form_view.tpl');
        $this->template_->viewDefine('layout_common', 'true');
    }

    /** 작성 처리 */
    public function write_process()
    {
        $this->_check_login();

        $this->form_validation->set_rules('category_id', '카테고리', 'required|integer');
        $this->form_validation->set_rules('title', '제목', 'required|trim');
        $this->form_validation->set_rules('body', '내용', 'required');

        if ($this->form_validation->run() == false) {
            return $this->write_form(); // 검증 실패 시 폼 다시
        }

        $insert = [
            'title'       => $this->input->post('title'),
            'body'        => $this->input->post('body'),
            'category_id' => $this->input->post('category_id'),
            'user_id'     => $this->session->userdata('id'),
        ];

        // 1) 게시글 생성
        $new_post_id = $this->Post_model->create_post($insert);

        // 2) 첨부 업로드 & 연결
        $this->File_model->upload_and_attach($new_post_id, 'attachments');

        // 3) 상세로 이동
        redirect('posts/view/' . $new_post_id);
    }

    /** 수정 폼 */
    public function edit_form($post_id)
    {
        $this->_check_login();

        $post = $this->Post_model->get_post_by_id($post_id);
        if (!$post || $post->user_id != $this->session->userdata('id')) {
            $this->session->set_flashdata('error', '수정권한이 없습니다.');
            redirect('posts/view/' . $post_id);
            return;
        }

        $categories  = $this->Category_model->get_all_categories();
        $attachments = $this->File_model->list_by_post($post_id);

        // 공통 CSS
        $baseCss = '<link rel="stylesheet" href="/ci-starter/assets/css/layout_common.css">';
        $this->css('post_form_view.css', time());
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
            'attachments'           => $attachments ?: [],
            'BASE_CSS'              => $baseCss,
            'CSS'                   => $tags['css_optimizer'],
            'JS'                    => $tags['js_optimizer'],
        ];

        $this->template_->viewAssign($data);
        $this->template_->viewDefine('content', 'post_form_view.tpl');
        $this->template_->viewDefine('layout_common', 'true');
    }

    /** 수정 처리 */
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
            return $this->edit_form($post_id);
        }

        // 1) 게시글 업데이트
        $update = [
            'title'       => $this->input->post('title'),
            'body'        => $this->input->post('body'),
            'category_id' => $this->input->post('category_id'),
        ];
        $this->Post_model->update_post($post_id, $update);

        // 2) 첨부 일괄 소프트삭제
        $delete_ids = (array)$this->input->post('delete_attachments');
        if (!empty($delete_ids)) {
            foreach ($delete_ids as $fid) {
                $fid = (int)$fid;
                $f = $this->File_model->get_one_with_owner($fid);
                if ($f && (int)$f->post_owner_id === (int)$this->session->userdata('id')) {
                    $this->File_model->soft_delete_one($fid);
                }
            }
        }

        // 3) 신규 첨부 업로드
        $this->File_model->upload_and_attach($post_id, 'attachments');

        redirect('posts/view/' . $post_id);
    }

    /** 삭제 */
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
