<div class="board-container">

    <div class="board-header">
        <h2>{category->name}</h2>
    </div>

    {@ posts}
        <div class="post-item">
            <h3>
                <a href="/ci-starter/posts/view/{posts->id}">
                    {posts->title}
                </a>
            </h3>
            <p>작성자 : {posts->username}</p>
        </div>
    {/}

    {? !posts}
        <div class="no-posts">
            <p>게시물이 없습니다.</p>
        </div>
    {/}
</div>
