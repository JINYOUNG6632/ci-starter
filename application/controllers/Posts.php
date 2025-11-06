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
    protected $models        = ['Post_model', 'Category_model', 'File_model', 'Comment_model'];
    protected $commonModules = ['pagination_module'];

    public function __construct()
    {
        parent::__construct();
    }

    public function index($category_id = 1)
    {
        $q = trim((string)$this->input->get('q', TRUE));
        $category = $this->Category_model->get_category_by_id($category_id);

        $pg = $this->pagination_module->for_posts($category_id, $q, 10);
        $posts = $this->Post_model->list_by_title($pg['limit'], $pg['offset'], $q, $category_id);

        $this->css('list_view.css', time());

        $this->render('list_view.tpl', [
            'title'      => $category ? $category->name : '게시글 목록',
            'category'   => $category,
            'posts'      => $posts,
            'total'      => $pg['total'],
            'page'       => $pg['page'],
            'per_page'   => $pg['limit'],
            'q'          => $q,
            'pagination' => $pg['links'],
        ]);
    }


    /** 상세 + 댓글 서버렌더 페이지네이션(10) */
    public function view($post_id)
    {
        $postId = (int)$post_id;
        $post   = $this->Post_model->get_post_by_id($postId);

        // 페이지 전용 CSS
        $this->css('post_detail.css', time());

        // 첨부 목록
        $attachments = $post ? $this->File_model->list_by_post($postId) : [];

        // 댓글 페이지네이션 (서버렌더)
        $page     = max(1, (int)$this->input->get('page'));
        $reply_to = $this->input->get('reply_to'); // 해당 댓글 아래에 대댓글 폼 보이기
        $reply_to = ($reply_to === null || $reply_to === '') ? null : (int)$reply_to;

        $commentPage = $this->Comment_model->page_by_post($postId, $page, 10);

        // 총 댓글 수 (기존 로직 유지)
        $this->db->from('comments');
        $this->db->where('post_id', $postId);
        $this->db->where('is_deleted', 0);
        $total_comment_count = (int)$this->db->count_all_results();

        // 부분 템플릿(파셜) 매핑
        $this->template_->viewDefine('comment_section',   'comment_section.tpl');
        $this->template_->viewDefine('comment_form',      'comment_form.tpl');
        $this->template_->viewDefine('comment_list_stub', 'comment_list_stub.tpl');
        $this->template_->viewDefine('file_view',         'file_view.tpl');

        $this->js('comments.js', time());

        // 화면 렌더
        $this->render('post_detail_view.tpl', [
            'post'                => $post,
            'post_id'             => $post ? $post->id : null,
            'session_user_id'     => $this->session->userdata('id'),
            'title'               => $post ? $post->title : '게시글 상세',
            'attachments'         => $attachments,
            'comments'            => $commentPage['rows'],
            'page'                => $commentPage['page'],
            'total_pages'         => $commentPage['total_pages'],
            'page_size'           => $commentPage['page_size'],
            'reply_to'            => $reply_to,
            'total_comment_count' => $total_comment_count,
        ]);
    }

    /** 작성 폼 */
    public function write_form()
    {
        $this->_check_login();

        $categories = $this->Category_model->get_all_categories();

        // 페이지 전용 CSS
        $this->css('post_form_view.css', time());

        $this->render('post_form_view.tpl', [
            'is_edit'              => false,
            'form_action'          => '/ci-starter/posts/write_process',
            'validation_errors'    => validation_errors(),
            'categories'           => $categories,
            'selected_category_id' => set_value('category_id', ''),
            'title_value'          => set_value('title', ''),
            'body_value'           => set_value('body', ''),
            'title'                => '새 게시글 작성',
            'attachments'          => [],
        ]);
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
        exit;
    }

    /** 수정 폼 */
    public function edit_form($post_id)
    {
        $this->_check_login();

        $post = $this->Post_model->get_post_by_id($post_id);
        if (!$post || $post->user_id != $this->session->userdata('id')) {
            $this->session->set_flashdata('error', '수정권한이 없습니다.');
            redirect('posts/view/' . $post_id);
            exit;
        }

        $categories  = $this->Category_model->get_all_categories();
        $attachments = $this->File_model->list_by_post($post_id);

        $this->css('post_form_view.css', time());

        $this->render('post_form_view.tpl', [
            'is_edit'              => true,
            'form_action'          => '/ci-starter/posts/edit_process/' . $post->id,
            'validation_errors'    => validation_errors(),
            'categories'           => $categories,
            'selected_category_id' => set_value('category_id', $post->category_id),
            'title_value'          => set_value('title', $post->title),
            'body_value'           => set_value('body', $post->body),
            'title'                => '게시글 수정',
            'attachments'          => $attachments ?: [],
        ]);
    }

    /** 수정 처리 */
    public function edit_process($post_id)
    {
        $this->_check_login();

        $post = $this->Post_model->get_post_by_id($post_id);
        if (!$post || $post->user_id != $this->session->userdata('id')) {
            $this->session->set_flashdata('error', '권한이 없습니다.');
            redirect('posts/view/' . $post_id);
            exit;
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
        exit;
    }

    /** 삭제 */
    public function delete($post_id)
    {
        $this->_check_login();

        $post = $this->Post_model->get_post_by_id($post_id);
        if (!$post || $post->user_id != $this->session->userdata('id')) {
            $this->session->set_flashdata('error', '권한이 없습니다.');
            redirect('posts/view/' . $post_id);
            exit;
        }

        // 게시글 삭제
        $this->Post_model->delete_post($post_id);

        redirect('posts');
        exit;
    }
}
