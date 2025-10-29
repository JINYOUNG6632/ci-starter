<style>
.post-container {
    max-width: 850px;
    margin: 40px auto;
    background-color: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
    padding: 30px;
    font-family: 'Noto Sans KR', sans-serif;
}

.post-header {
    border-bottom: 2px solid #4CAF50;
    padding-bottom: 15px;
    margin-bottom: 20px;
}

.post-header h2 {
    font-size: 22px;
    color: #333;
    margin: 0;
    line-height: 1.4;
}

.post-meta {
    margin-top: 8px;
    color: #777;
    font-size: 14px;
}

.post-body {
    padding: 20px;
    background-color: #fafafa;
    border: 1px solid #eee;
    border-radius: 5px;
    font-size: 15px;
    color: #333;
    line-height: 1.6;
    white-space: pre-wrap;
    min-height: 200px;
}

.controls {
    margin-top: 25px;
    display: flex;
    gap: 10px;
}

.btn {
    padding: 10px 18px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-weight: 600;
    transition: background-color 0.2s;
}

.btn-list {
    background-color: #4CAF50;
    color: #fff;
}

.btn-list:hover {
    background-color: #43a047;
}

.btn-edit {
    background-color: #3498db;
    color: #fff;
}

.btn-edit:hover {
    background-color: #2980b9;
}

.btn-delete {
    background-color: #e74c3c;
    color: #fff;
}

.btn-delete:hover {
    background-color: #c0392b;
}

.error {
    background-color: #fff5f5;
    color: #d63031;
    border: 1px solid #ff7675;
    border-radius: 5px;
    padding: 10px 12px;
    font-size: 0.9em;
    margin-bottom: 15px;
}
</style>

{? error}
<p class="error">{error}</p>
{/}

{? post}
<div class="post-container">
    <div class="post-header">
        <h2>{post->title}</h2>
        <div class="post-meta">
            작성자 : <strong>{post->username}</strong>
        </div>
    </div>

    <div class="post-body">
        {= nl2br(post->body)}
    </div>

    <div class="controls">
        <button type="button" class="btn btn-list" onclick="location.href='/ci-starter/posts/index/{post->category_id}'">목록</button>

        {? post->user_id == session_user_id}
            <button type="button" class="btn btn-edit" onclick="location.href='/ci-starter/posts/edit_form/{post->id}'">수정</button>
            <button type="button" class="btn btn-delete" onclick="confirmDelete()">삭제</button>
        {/}
    </div>
</div>

<script>
function confirmDelete() {
    if (confirm('정말로 이 게시글을 삭제하시겠습니까?')) {
        location.href = '/ci-starter/posts/delete/{post->id}';
    }
}
</script>

{:}
<div class="post-container">
    <h2>게시글을 찾을 수 없습니다.</h2>
    <p><a href="/ci-starter/posts" style="color: #4CAF50; text-decoration: none;">목록으로 돌아가기</a></p>
</div>
{/}
