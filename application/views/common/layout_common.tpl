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

        <!-- 사이트 제목 (맨 위 중앙) -->
        <div class="site-title-wrap">
            <h1 class="site-title">
                <a href="/ci-starter/">📋 계층형 게시판</a>
            </h1>
        </div>

        <!-- 아래: 사용자 / 카테고리 / 글쓰기 -->
        <div class="header-bar">

            <!-- Left: 환영/로그인 -->
            <div class="header-left">
                {? is_logged_in}
                    <span class="welcome">환영합니다, <strong>{session_username}</strong>님!</span>
                    <a class="auth-link" href="/ci-starter/auth/logout">로그아웃</a>
                {:}
                    <a class="auth-link" href="/ci-starter/auth/login">로그인</a>
                    <a class="auth-link" href="/ci-starter/auth/register">회원가입</a>
                {/}
            </div>

            <!-- Center: 카테고리 -->
            <div class="header-center">
                <nav class="category-nav">
                    {@ header_categories}
                        <a href="/ci-starter/posts/index/{header_categories->id}">
                            {header_categories->name}
                        </a>
                    {/}
                </nav>
            </div>

            <!-- Right: 글쓰기 -->
            <div class="header-right">
                {? is_logged_in}
                    <a href="/ci-starter/posts/write" class="post-button write">📝 글쓰기</a>
                {:}
                    <a href="/ci-starter/auth/login" class="post-button login">로그인 후 글쓰기</a>
                {/}
            </div>

        </div>
    </header>


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
