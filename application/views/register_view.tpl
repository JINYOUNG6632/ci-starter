<style>
.register-container {
    max-width: 400px;
    margin: 50px auto;
    padding: 30px;
    border: 1px solid #ddd;
    border-radius: 8px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    background-color: #fff;
}
.register-container h2 {
    text-align: center;
    margin-bottom: 25px;
    color: #333;
}
.form-group { margin-bottom: 15px; }
.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
    color: #555;
}
.form-group input {
    width: 100%;
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 4px;
}
.error {
    color: #e74c3c;
    font-size: 0.9em;
    margin-top: 10px;
    text-align: center;
}
.btn-submit {
    width: 100%;
    padding: 10px;
    background-color: #27ae60;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 1.1em;
    margin-top: 10px;
    transition: background-color 0.3s;
}
.btn-submit:hover { background-color: #229954; }
.login-link {
    text-align: center;
    margin-top: 20px;
}
.login-link a {
    color: #27ae60;
    text-decoration: none;
}
</style>

<div class="register-container">
    <h2>회원가입</h2>

    {? error}
        <p class="error">{error}</p>
    {/}

    <form action="/ci-starter/auth/register" method="post">
        <div class="form-group">
            <label for="username">이름 (닉네임)</label>
            <input type="text" id="username" name="username" required placeholder="닉네임을 입력하세요">
        </div>

        <div class="form-group">
            <label for="user_id">아이디</label>
            <input type="text" id="user_id" name="user_id" required placeholder="아이디를 입력하세요">
        </div>

        <div class="form-group">
            <label for="user_password">비밀번호</label>
            <input type="password" id="user_password" name="user_password" required placeholder="비밀번호를 입력하세요">
        </div>

        <button type="submit" class="btn-submit">회원가입</button>
    </form>

    <div class="login-link">
        <p>이미 계정이 있으신가요? <a href="/auth/login">로그인</a></p>
    </div>
</div>
