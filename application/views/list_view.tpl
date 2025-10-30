<div class="board-container">
  <div class="board-header">
    <h2>{category->name}</h2>
  </div>

  {@ posts}
    <div class="post-item">
        <a class="post-item" href="/ci-starter/posts/view/{posts->id}">
        <h3 class="post-title">{posts->title}</h3>
        <p class="post-meta">작성자 : {posts->username}</p>
        </a>

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
</div>
