<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title><?php echo html_escape($post->title ?? '게시글 상세'); ?></title>
    <style>
        body { font-family: sans-serif; }
        .error { color: red; font-size: 0.9em; border: 1px solid red; padding: 10px; margin-bottom: 15px; }
        .post-header { border-bottom: 1px solid #ccc; padding-bottom: 10px; }
        .post-meta { font-size: 0.9em; color: #666; margin-top: 5px; }
        .post-body { margin-top: 20px; padding: 15px; border: 1px solid #eee; min-height: 200px; white-space: pre-wrap; /* 텍스트의 줄바꿈 유지 */ background-color: #fdfdfd; }
        .controls { margin-top: 20px; }
        button { padding: 8px 12px; }
    </style>
</head>
<body>

    <?php 
    // 컨트롤러에서 set_flashdata로 설정한 에러 메시지 표시
    if ($this->session->flashdata('error')): ?>
        <p class="error"><?php echo $this->session->flashdata('error'); ?></p>
    <?php endif; ?>

    <?php if (isset($post) && $post): ?>
        <div class="post-header">
            <h2><?php echo html_escape($post->title); ?></h2>
            <div class="post-meta">
                <span>작성자 : <?php echo $post->username; ?></span>
                </div>
        </div>

        <div class="post-body">
            <?php echo nl2br(html_escape($post->body)); // nl2br() 함수로 PHP 문자열의 줄바꿈(\n)을 HTML <br> 태그로 변환 ?>
        </div>

        <div class="controls">
            <button type="button" onclick="location.href='/ci-starter/posts/index/<?php echo $post->category_id; ?>'">목록</button>

            <?php // Posts.php의 로직에 따라, 세션 ID와 게시글의 user_id가 일치하는지 확인
            if ($post->user_id == $this->session->userdata('id')): ?>
                <button type="button" onclick="location.href='/ci-starter/posts/edit_form/<?php echo $post->id; ?>'">수정</button>
                <button type="button" onclick="confirmDelete()">삭제</button>
            <?php endif; ?>
        </div>

        <script>
        function confirmDelete() {
            // 삭제 확인창
            if (confirm('정말로 이 게시글을 삭제하시겠습니까?')) {
                location.href = '/ci-starter/posts/delete/<?php echo $post->id; ?>';
            }
        }
        </script>

    <?php else: ?>
        <h2>게시글을 찾을 수 없습니다.</h2>
        <p><a href="/ci-starter/posts">목록으로 돌아가기</a></p>
    <?php endif; ?>

</body>
</html>