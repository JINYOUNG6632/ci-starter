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

    <style>
        body {
            font-family: 'Noto Sans KR', sans-serif;
            background-color: #f9f9f9;
            margin: 0;
            padding: 0;
            color: #333;
        }

        header {
            background-color: #fff;
            border-bottom: 2px solid #4CAF50;
            padding: 20px;
            text-align: center;
        }

        header h1 {
            margin: 0;
            font-size: 26px;
        }

        header h1 a {
            text-decoration: none;
            color: #333;
        }

        nav {
            margin-top: 10px;
        }

        nav a {
            margin: 0 8px;
            color: #555;
            text-decoration: none;
            font-weight: 500;
        }

        nav a:hover {
            color: #4CAF50;
        }

        .nav {
            padding: 10px 20px;
            text-align: right;
            border-top: 1px solid #eee;
            border-bottom: 1px solid #eee;
            background-color: #fafafa;
            font-size: 14px;
        }

        .nav a {
            margin-left: 10px;
            color: #4CAF50;
            text-decoration: none;
            font-weight: bold;
        }

        .nav a:hover {
            text-decoration: underline;
        }

        /* 게시글 작성 버튼 영역 */
        .post-button-container {
            text-align: center;
            margin: 25px 0;
        }

        .post-button {
            display: inline-block;
            padding: 12px 25px;
            font-size: 15px;
            border-radius: 6px;
            text-decoration: none;
            transition: background-color 0.2s;
        }

        .post-button.write {
            background-color: #4CAF50;
            color: white;
        }

        .post-button.write:hover {
            background-color: #45a049;
        }

        .post-button.login {
            background-color: #888;
            color: white;
        }

        .post-button.login:hover {
            background-color: #666;
        }

        main {
            width: 80%;
            max-width: 900px;
            margin: 0 auto 50px;
            background-color: #fff;
            padding: 20px;
            border-radius: 6px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        footer {
            text-align: center;
            padding: 15px;
            color: #999;
            font-size: 13px;
            border-top: 1px solid #ddd;
            background-color: #fafafa;
        }
    </style>
</head>

<body>

    <header>
        <h1><a href="/ci-starter/">📋 계층형 게시판</a></h1>
        <nav>
            {@ header_categories}
                <a href="/ci-starter/posts/index/{header_categories->id}">{header_categories->name}</a>
            {/}
        </nav>

        <div class="nav">
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
            <a href="/ci-starter/posts/write" class="post-button write">📝 게시글 작성하기</a>
        {:}
            <a href="/ci-starter/auth/login" class="post-button login">🔐 로그인 후 글쓰기</a>
        {/}
    </div>

    <main>
        {? this->viewDefined('content')}
            {# content}
        {/}
    </main>

    <footer>
        <p>&copy; 2025 My Project. All rights reserved.</p>
    </footer>

</body>
</html>
