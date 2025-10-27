<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>로그인</title>
    <style>
        .error { color: red; font-size: 0.9em; }
    </style>
</head>
<body>

    <h2>로그인</h2>

    <?php 
    if ($this->session->flashdata('error')): ?>
        <p class="error"><?php echo $this->session->flashdata('error'); ?></p>
    <?php endif; ?>

    <?php 
    echo validation_errors('<div class="error">', '</div>'); 
    ?>

    <?php echo form_open('auth/login'); ?>

        <div>
            <label for="user_id">아이디:</label>
            <input type="text" name="user_id" id="user_id" value="<?php echo set_value('user_id'); ?>">
        </div>
        
        <div>
            <label for="user_password">비밀번호:</label>
            <input type="password" name="user_password" id="user_password" /> 
        </div>
        <div>
            <button type="submit">로그인</button>
            <button type="button" onclick="location.href='/ci-starter/auth/register'">회원가입</button>
        </div>

    </form>

</body>
</html>