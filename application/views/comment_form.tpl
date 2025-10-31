<section id="comment-create-root" class="comment-form-root">
  <h3 class="section-title" style="display:none;">댓글 쓰기</h3>
  <form action="/ci-starter/comments/create" method="post">
    <input type="hidden" name="post_id" value="{post->id}">
    <textarea name="body" required rows="4" placeholder="댓글을 입력하세요"></textarea>
    <div class="form-actions">
      <button type="submit">등록</button>
    </div>
  </form>
</section>
