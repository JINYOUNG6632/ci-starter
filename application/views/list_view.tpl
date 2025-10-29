<style>
.board-container {
    max-width: 900px;
    margin: 40px auto;
    background-color: #fff;
    border: 1px solid #ddd;
    border-radius: 6px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    padding: 20px 30px;
    font-family: 'Noto Sans KR', sans-serif;
}

.board-header {
    text-align: left;
    border-bottom: 2px solid #4CAF50;
    padding-bottom: 10px;
    margin-bottom: 25px;
}

.board-header h2 {
    font-size: 22px;
    font-weight: 700;
    color: #333;
    margin: 0;
}

.post-item {
    border-bottom: 1px solid #eee;
    padding: 15px 0;
    transition: background-color 0.2s;
}

.post-item:last-child {
    border-bottom: none;
}

.post-item:hover {
    background-color: #f9f9f9;
}

.post-item h3 {
    margin: 0 0 5px;
    font-size: 18px;
    font-weight: 600;
}

.post-item h3 a {
    text-decoration: none;
    color: #2c3e50;
    transition: color 0.2s;
}

.post-item h3 a:hover {
    color: #4CAF50;
}

.post-item p {
    margin: 0;
    color: #777;
    font-size: 14px;
}

.no-posts {
    text-align: center;
    color: #aaa;
    font-size: 15px;
    padding: 40px 0;
}
</style>

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
