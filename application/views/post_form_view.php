<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title><?php echo isset($post) ? '게시글 수정' : '새 게시글 작성'; ?></title>
    <style>
        body { font-family: sans-serif; }
        .error { color: red; font-size: 0.9em; margin-bottom: 10px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"], select, textarea { 
            width: 100%; 
            padding: 10px; 
            box-sizing: border-box;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        textarea { height: 250px; }
        button { padding: 10px 15px; }
    </style>
</head>
<body>

    <h2><?php echo isset($post) ? '게시글 수정' : '새 게시글 작성'; ?></h2>

    <?php 
    echo validation_errors('<div class="error">', '</div>'); 
    ?>

    <?php 
    $form_action = isset($post) ? 'posts/edit_process/' . $post->id : 'posts/write_process';
    
    echo form_open($form_action); 
    ?>

        <div class="form-group">
            <label for="category_id">카테고리:</label>
            <select name="category_id" id="category_id">
                <option value="">카테고리를 선택하세요</option>
                <?php if (isset($categories)): ?>
                    <?php foreach ($categories as $category): ?>
                        <?php
                        $default_category = $post->category_id ?? null;
                        $is_selected = set_value('category_id', $default_category) == $category->id;
                        ?>
                        <option value="<?php echo $category->id; ?>" <?php echo $is_selected ? 'selected' : ''; ?>>
                            <?php echo html_escape($category->name); ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="title">제목:</label>
            <input type="text" name="title" id="title" value="<?php echo set_value('title', $post->title ?? ''); ?>">
        </div>
        
        <div class="form-group">
            <label for="body">내용:</label>
            <textarea name="body" id="body"><?php echo set_value('body', $post->body ?? ''); ?></textarea>
        </div>
        
        <div class="controls">
            <button type="submit"><?php echo isset($post) ? '수정하기' : '작성하기'; ?></button>
            <button type="button" onclick="history.back()">취소</button>
        </div>

    </form>

</body>
</html>