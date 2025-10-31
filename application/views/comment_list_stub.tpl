<section id="comment-list">
  {@ comments}
    <div id="c{comments->id}" class="comment-item" style="margin-left:{= comments->depth * 24 }px">
      {? comments->is_deleted == 1}
        <div class="comment-deleted">삭제된 댓글입니다.</div>
      {:}
        <div class="comment-header">
          <span class="comment-user">
            {comments->username}
          </span>
          <span class="comment-time">{comments->created_at}</span>
        </div>
        <div class="comment-body">{= nl2br(comments->body) }</div>

        <div class="comment-actions" style="display:flex;gap:10px;align-items:center;margin-top:6px;">
          <!-- 대댓글 폼 토글: JS 없이 GET 파라미터 -->
          <a href="?page={page}&reply_to={comments->id}#c{comments->id}">댓글 달기</a>

          {? session_user_id && comments->user_id == session_user_id}
            <form action="/ci-starter/comments/delete" method="post" style="display:inline">
              <input type="hidden" name="post_id" value="{post->id}">
              <input type="hidden" name="comment_id" value="{comments->id}">
              <input type="hidden" name="page" value="{page}">
              <button type="submit" onclick="return confirm('삭제하시겠습니까?')">삭제</button>
            </form>
          {/}
        </div>
      {/}

      <!-- 대댓글 작성 폼: reply_to가 이 댓글이면 노출 -->
      {? reply_to && reply_to == comments->id}
        <form action="/ci-starter/comments/create" method="post" class="comment-form-reply" style="margin-top:8px;">
          <input type="hidden" name="post_id" value="{post->id}">
          <input type="hidden" name="parent_id" value="{comments->id}">
          <textarea name="body" required rows="3" placeholder="대댓글을 입력하세요"></textarea>
          <div class="form-actions" style="display:flex;gap:10px;margin-top:6px;">
            <button type="submit">등록</button>
            <a class="btn-cancel" href="?page={page}#c{comments->id}">취소</a>
          </div>
        </form>
      {/}
    </div>
  {/}
</section>
