<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title><?php echo html_escape($category->name ?? '게시글 목록'); ?></title>
    <style>
        body { font-family: sans-serif; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background-color: #f2f2f2; }
        .controls { margin-top: 20px; }
        .nav { margin-bottom: 20px; }
    </style>
</head>
<body>

    <div class="nav">
        <?php if ($this->session->userdata('logged_in')): ?>
            <span>환영합니다, <?php echo html_escape($this->session->userdata('username')); ?>님!</span>
            <a href="/ci-starter/auth/logout">로그아웃</a>
        <?php else: ?>
            <a href="/ci-starter/auth/login">로그인</a>
            <a href="/ci-starter/auth/register">회원가입</a>
        <?php endif; ?>
    </div>

    <h2><?php echo html_escape($category->name ?? '게시글 목록'); ?></h2>

    <table>
        <thead>
            <tr>
                <th style="width:10%;">작성자</th>
                <th>제목</th>
                </tr>
        </thead>
        <tbody>
            <?php if (isset($posts) && !empty($posts)): ?>
                <?php foreach ($posts as $post): ?>
                    <tr>
                        <td><?php echo $post->username; ?></td>
                        <td>
                            <a href="/ci-starter/posts/view/<?php echo $post->id; ?>">
                                <?php echo html_escape($post->title); ?>
                            </a>
                        </td>
                        </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="2">게시글이 없습니다.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="controls">
        <button type="button" onclick="location.href='/ci-starter/posts/write_form'">글쓰기</button>
    </div>

</body>
</html>