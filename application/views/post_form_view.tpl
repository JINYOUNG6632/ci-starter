<div class="post-form-wrapper">
    <div class="post-form-header">
        {? is_edit}
            <h2>게시글 수정</h2>
        {:}
            <h2>새 게시글 작성</h2>
        {/}
    </div>

    {? validation_errors}
        <div class="post-error-block">
            {validation_errors}
        </div>
    {/}

    <form action="{form_action}" method="post" enctype="multipart/form-data">
        <div class="form-group">
            <label for="category_id">카테고리</label>
            <select name="category_id" id="category_id">
                <option value="">카테고리를 선택하세요</option>

                {@ categories}
                    {? categories->id == selected_category_id}
                        <option value="{categories->id}" selected>
                            {categories->name}
                        </option>
                    {:}
                        <option value="{categories->id}">
                            {categories->name}
                        </option>
                    {/}
                {/}
            </select>
        </div>

        <div class="form-group">
            <label for="title">제목</label>
            <input
                type="text"
                id="title"
                name="title"
                value="{title_value}"
                placeholder="제목을 입력하세요">
        </div>

        <div class="form-group">
            <label for="body">내용</label>
            <textarea
                id="body"
                name="body"
                placeholder="내용을 입력하세요">{body_value}</textarea>
        </div>

        <!-- ★ 파일 첨부 블록 (이름/다중/accept 중요) ★ -->
        <div class="form-group">
            <label for="attachments">파일 첨부</label>
            <input
                type="file"
                id="attachments"
                name="attachments[]"
                multiple
                accept=".jpg,.jpeg,.png,.gif,.pdf,.txt,.zip"
                aria-describedby="attachments_help">
            <small id="attachments_help" class="help-text">
                이미지(JPG/PNG/GIF), PDF, TXT, ZIP 업로드 가능. 여러 개 선택 가능.
            </small>
            <div id="selected-files" class="selected-files" aria-live="polite" aria-atomic="true"></div>
        </div>

        <!-- 수정 모드일 때 기존 첨부 목록 노출 + 삭제 기능 -->
        {? is_edit && attachments}
        <div class="form-group">
            <label>기존 첨부파일</label>

            <!-- (옵션) 전체선택 토글 -->
            <div style="margin-bottom:6px;">
                <label style="cursor:pointer;">
                    <input type="checkbox" id="delete-all-toggle" onclick="
                        var cbs=document.querySelectorAll('.attach-del-cb');
                        for(var i=0;i<cbs.length;i++){cbs[i].checked=this.checked;}
                    ">
                    전체 선택/해제
                </label>
            </div>

            <ul class="attach-list">
                {@ attachments}
                    <li class="attach-item" style="display:flex;align-items:center;gap:8px;">
                        <!-- ✅ 일괄삭제 체크박스: Posts::edit_process에서 delete_attachments[] 처리 -->
                        <input type="checkbox"
                               class="attach-del-cb"
                               name="delete_attachments[]"
                               value="{attachments->id}"
                               id="del-{attachments->id}">

                        <label for="del-{attachments->id}" style="margin:0;cursor:pointer;flex:1;">
                            <a href="/ci-starter/files/download/{attachments->id}"
                               aria-label="{attachments->original_filename}"
                               style="text-decoration:underline;">
                                {attachments->original_filename}
                            </a>
                            <small class="muted">({attachments->file_size} bytes)</small>
                        </label>

                        <!-- ✅ 개별 즉시 삭제(POST /files/delete/{id}) -->
                        <form action="/ci-starter/files/delete/{attachments->id}" method="post"
                              onsubmit="return confirm('이 첨부를 삭제할까요?');" style="margin:0;">
                            <button type="submit" class="btn btn-delete btn-sm">삭제</button>
                        </form>
                    </li>
                {/}
            </ul>

            <small class="help-text">체크 후 “수정하기”를 누르면 선택한 첨부가 삭제됩니다.</small>
        </div>
        {/}

        <div class="form-controls">
            {? is_edit}
                <button type="submit" class="btn-primary">수정하기</button>
            {:}
                <button type="submit" class="btn-primary">작성하기</button>
            {/}
            <button type="button" class="btn-secondary" onclick="history.back()">취소</button>
        </div>
    </form>
</div>

<!-- (선택) 파일 선택 시 선택 목록 표시 UX -->
<script>
(function(){
  var input = document.getElementById('attachments');
  var box   = document.getElementById('selected-files');
  if (!input || !box) return;
  input.addEventListener('change', function(){
    if (!this.files || !this.files.length) { box.textContent = ''; return; }
    var names = [];
    for (var i=0;i<this.files.length;i++){ names.push(this.files[i].name); }
    box.textContent = '선택된 파일: ' + names.join(', ');
  });
})();
</script>
