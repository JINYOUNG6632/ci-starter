<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        {? title}
            {title}
        {:}
            CI-Starter
        {/}
    </title>

    {BASE_CSS}
    {CSS}
</head>

<body>

    <header class="site-header">
        <h1 class="site-title">
            <a href="/ci-starter/">📋 계층형 게시판</a>
        </h1>

        <nav class="category-nav">
            {@ header_categories}
                <a href="/ci-starter/posts/index/{header_categories->id}">
                    {header_categories->name}
                </a>
            {/}
        </nav>

        <div class="user-nav">
            {? is_logged_in}
                <span>환영합니다, <strong>{session_username}</strong>님!</span>
                <a href="/ci-starter/auth/logout">로그아웃</a>
            {:}
                <a href="/ci-starter/auth/login">로그인</a>
                <a href="/ci-starter/auth/register">회원가입</a>
            {/}
        </div>
    </header>

    <div class="post-button-container">
        {? is_logged_in}
            <a href="/ci-starter/posts/write" class="post-button write">
                📝 게시글 작성하기
            </a>
        {:}
            <a href="/ci-starter/auth/login" class="post-button login">
                🔐 로그인 후 글쓰기
            </a>
        {/}
    </div>

    <main class="main-card">
        {? this->viewDefined('content')}
            {# content}
        {/}
    </main>

    <footer class="site-footer">
        <p>&copy; 2025 My Project. All rights reserved.</p>
    </footer>

    {JS}
</body>
</html>
