<div class="post-container" data-post-id="{post->id}">
    <div class="post-header">
        <h2>{post->title}</h2>
        <div class="post-meta">작성자 : <strong>{post->username}</strong></div>
    </div>

    <div class="post-body">
        {= nl2br(post->body)}
    </div>

    {? attachments}
    <div class="post-attachments">
        <h3>첨부파일</h3>
        <ul class="attach-list">
            {@ attachments}
                <li class="attach-item" style="display:flex;align-items:center;gap:8px;">
                    <a class="attach-link"
                       href="/ci-starter/files/download/{attachments->id}"
                       aria-label="{attachments->original_filename}">
                        {attachments->original_filename}
                    </a>
                    <small class="muted">({attachments->file_size} bytes)</small>

                    {? post->user_id == session_user_id}
                        <!-- 첨부 개별 삭제 (POST /files/delete/{id}) -->
                        <form action="/ci-starter/files/delete/{attachments->id}" method="post"
                              onsubmit="return confirm('이 첨부를 삭제할까요?');" style="margin:0;">
                            <button type="submit" class="btn btn-delete btn-sm">첨부 삭제</button>
                        </form>
                    {/}
                </li>
            {/}
        </ul>
    </div>
    {/}

    <div class="controls">
        <button type="button" class="btn btn-list"
                onclick="location.href='/ci-starter/posts/index/{post->category_id}'">목록</button>

        {? post->user_id == session_user_id}
            <button type="button" class="btn btn-edit"
                    onclick="location.href='/ci-starter/posts/edit_form/{post->id}'">수정</button>
            <form action="/ci-starter/posts/delete/{post->id}" method="post"
                  onsubmit="return confirm('이 게시글을 삭제하시겠습니까?');"
                  style="display:inline;">
                <button type="submit" class="btn btn-delete">삭제</button>
            </form>
        {/}
    </div>

    <div class="comments-wrapper">
        {# comment_section }
    </div>
</div>
