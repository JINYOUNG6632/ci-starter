{? !post}
  <div class="empty-post">존재하지 않는 게시글입니다.</div>
{:}

<div class="post-container" data-post-id="{post->id}">

  <!-- 게시글 상단 -->
  <article class="post">
    <div class="post-header">
      <h2 class="post-title">{post->title}</h2>
      <div class="post-meta">
        <span class="post-author">작성자 #{post->user_id}</span>
        <span class="post-date">{post->created_at}</span>
      </div>
    </div>

    <div class="post-body">
      {= nl2br(post->body) }
    </div>

    {# file_view}

    <div class="controls" style="margin-top:12px; display:flex; gap:8px;">
      <a class="btn btn-list"
         href="/ci-starter/posts/index/{post->category_id}">목록</a>

      {? post->user_id == session_user_id}
        <a class="btn btn-edit"
           href="/ci-starter/posts/edit/{post->id}">수정</a>

        <form action="/ci-starter/posts/delete/{post->id}" method="post"
              onsubmit="return confirm('이 게시글을 삭제하시겠습니까?');"
              style="display:inline;">
          <button type="submit" class="btn btn-delete">삭제</button>
        </form>
      {/}
    </div>
  </article>

  <hr>

  <!-- 댓글 섹션: 조각 포함 -->
  {# comment_section }

</div>

{/}
