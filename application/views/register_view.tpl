<div class="register-container">
    <h2>회원가입</h2>

    {? error}
        <p class="error">{error}</p>
    {/}

    <form action="/ci-starter/auth/register" method="post">
        <div class="form-group">
            <label for="username">이름 (닉네임)</label>
            <input
                type="text"
                id="username"
                name="username"
                required
                placeholder="닉네임을 입력하세요">
        </div>

        <div class="form-group">
            <label for="user_id">아이디</label>
            <input
                type="text"
                id="user_id"
                name="user_id"
                required
                placeholder="아이디를 입력하세요">
        </div>

        <div class="form-group">
            <label for="user_password">비밀번호</label>
            <input
                type="password"
                id="user_password"
                name="user_password"
                required
                placeholder="비밀번호를 입력하세요">
        </div>

        <button type="submit" class="btn-submit">회원가입</button>
    </form>

    <div class="login-link">
        <p>이미 계정이 있으신가요?
            <a href="/ci-starter/auth/login">로그인</a>
        </p>
    </div>
</div>
