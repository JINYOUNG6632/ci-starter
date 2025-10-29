<style>
.post-form-wrapper {
    max-width: 800px;
    margin: 30px auto 60px;
    background-color: #fff;
    border-radius: 6px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.08);
    padding: 24px 28px;
    border: 1px solid #eee;
    font-family: 'Noto Sans KR', sans-serif;
}

.post-form-header {
    border-bottom: 1px solid #e5e5e5;
    margin-bottom: 20px;
    padding-bottom: 10px;
}

.post-form-header h2 {
    margin: 0;
    font-size: 20px;
    font-weight: 600;
    color: #333;
}

.post-error-block {
    background-color: #fff5f5;
    color: #d63031;
    border: 1px solid #ff7675;
    border-radius: 4px;
    padding: 10px 12px;
    font-size: 0.9em;
    margin-bottom: 20px;
    line-height: 1.4;
    white-space: pre-line;
}

.form-group {
    margin-bottom: 18px;
}

.form-group label {
    display: block;
    margin-bottom: 6px;
    font-weight: 600;
    font-size: 14px;
    color: #444;
}

.form-group select,
.form-group input[type="text"],
.form-group textarea {
    width: 100%;
    padding: 10px 12px;
    box-sizing: border-box;
    border: 1px solid #ccc;
    border-radius: 4px;
    font-family: inherit;
    font-size: 14px;
    line-height: 1.4;
    background-color: #fff;
}

.form-group select:focus,
.form-group input[type="text"]:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #4CAF50;
    box-shadow: 0 0 0 2px rgba(76,175,80,0.15);
}

.form-group textarea {
    min-height: 250px;
    resize: vertical;
}

.form-controls {
    display: flex;
    gap: 10px;
    margin-top: 25px;
}

.btn-primary {
    background-color: #4CAF50;
    color: #fff;
    border: 0;
    border-radius: 4px;
    padding: 10px 16px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    line-height: 1.4;
}
.btn-primary:hover {
    background-color: #45a049;
}

.btn-secondary {
    background-color: #888;
    color: #fff;
    border: 0;
    border-radius: 4px;
    padding: 10px 16px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    line-height: 1.4;
}
.btn-secondary:hover {
    background-color: #666;
}
</style>

<div class="post-form-wrapper">
    <div class="post-form-header">
        {? is_edit}
            <h2>게시글 수정</h2>
        {:}
            <h2>새 게시글 작성</h2>
        {/}
    </div>

    {? validation_errors}
        <div class="post-error-block">
            {validation_errors}
        </div>
    {/}

    <form action="{form_action}" method="post">
        <div class="form-group">
            <label for="category_id">카테고리</label>
            <select name="category_id" id="category_id">
                <option value="">카테고리를 선택하세요</option>

                {@ categories}
                    {? categories->id == selected_category_id}
                        <option value="{categories->id}" selected>
                            {categories->name}
                        </option>
                    {:}
                        <option value="{categories->id}">
                            {categories->name}
                        </option>
                    {/}
                {/}
            </select>
        </div>

        <div class="form-group">
            <label for="title">제목</label>
            <input
                type="text"
                id="title"
                name="title"
                value="{title_value}"
                placeholder="제목을 입력하세요">
        </div>

        <div class="form-group">
            <label for="body">내용</label>
            <textarea
                id="body"
                name="body"
                placeholder="내용을 입력하세요">{body_value}</textarea>
        </div>

        <div class="form-controls">
            {? is_edit}
                <button type="submit" class="btn-primary">수정하기</button>
            {:}
                <button type="submit" class="btn-primary">작성하기</button>
            {/}
            <button type="button" class="btn-secondary" onclick="history.back()">취소</button>
        </div>
    </form>
</div>
