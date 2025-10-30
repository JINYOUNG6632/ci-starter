<div class="post-container" data-post-id="{post->id}">
    <div class="post-header">
        <h2>{post->title}</h2>
        <div class="post-meta">작성자 : <strong>{post->username}</strong></div>
    </div>

    <div class="post-body">
        {= nl2br(post->body)}
    </div>

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
