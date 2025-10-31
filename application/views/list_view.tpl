<div class="board-header">
  <h2>{category->name}</h2>

  <form method="get" class="search-form">
    <input type="text" name="q" value="{q}" placeholder="제목 검색">
    <select name="per_page">
      <option value="10" {? per_page==10}selected{/}>10개</option>
      <option value="20" {? per_page==20}selected{/}>20개</option>
      <option value="50" {? per_page==50}selected{/}>50개</option>
    </select>
    <button type="submit">검색</button>
  </form>
</div>

  {@ posts}
    <div class="post-item" style="position:relative;">
      <h3 class="post-title">
        {posts->title}
        {? posts->comment_count > 0}
          <span class="comment-count">[{posts->comment_count}]</span>
        {/}
      </h3>
      <p class="post-meta">작성자 : {posts->username}</p>

      <a
        class="stretched-link"
        href="/ci-starter/posts/view/{posts->id}"
        aria-label="{posts->title}"
        tabindex="-1"
        aria-hidden="true"></a>
    </div>
  {/}

  {? !posts}
    <div class="no-posts"><p>게시물이 없습니다.</p></div>
  {/}

  <!-- 하단 페이지네이션 -->
  <div class="pagination-bottom">
    {=pagination}
  </div>
</div>
