<div class="login-container">
    <h2>로그인</h2>

    {? error}
        <p class="error">{error}</p>
    {/}

    <form action="/ci-starter/auth/login" method="post">
        <div class="form-group">
            <label for="user_id">아이디</label>
            <input
                type="text"
                id="user_id"
                name="user_id"
                required
                value="{? user_id}{user_id}{/}"
                placeholder="아이디를 입력하세요"
            >
        </div>

        <div class="form-group">
            <label for="user_password">비밀번호</label>
            <input
                type="password"
                id="user_password"
                name="user_password"
                required
                placeholder="비밀번호를 입력하세요"
            >
        </div>

        <button type="submit" class="btn-submit">로그인</button>
    </form>

    <div class="register-link">
        <p>계정이 없으신가요?
            <a href="/ci-starter/auth/register">회원가입</a>
        </p>
    </div>
</div>
