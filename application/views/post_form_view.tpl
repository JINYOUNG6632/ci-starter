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
