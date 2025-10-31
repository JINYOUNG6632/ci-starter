<div class="comment-section" data-post-id="{post->id}">
  <h3 class="section-title">댓글 ({total_comment_count})</h3>

  {# comment_form }

  {? !comments}
    <div class="no-comments">첫 댓글을 남겨보세요.</div>
  {:}
    {# comment_list_stub }
  {/}

  {? total_pages && total_pages > 1}
    <nav class="pagination" style="margin:18px 0; display:flex; gap:8px; align-items:center;">
      {? page > 1}
        <a class="page-link" href="?page={= page - 1}">이전</a>
      {/}
      <span class="page-counter">{page} / {total_pages}</span>
      {? page < total_pages}
        <a class="page-link" href="?page={= page + 1}">다음</a>
      {/}
    </nav>
  {/}
</div>
