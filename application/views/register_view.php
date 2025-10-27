<!DOCTYPE html>
<html>
<head>
    <title>회원가입</title>
</head>
<body>
    <h2>회원가입</h2>

    <div style="color: red;">
        <?php echo validation_errors(); ?>
    </div>

    <?php echo form_open('auth/register_process'); ?>

        <label for="user_id">아이디:</label>
        <input type="text" name="user_id" value="<?php echo set_value('username'); ?>" />
        
        <br>

        <label for="user_password">비밀번호:</label>
        <input type="password" name="user_password" />
        
        <br>

        <input type="submit" value="가입하기" />
        
    </form>
</body>
</html>