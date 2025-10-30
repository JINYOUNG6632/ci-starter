(function () {

    // --- 유틸 -------------------------------------------------

    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    // 현재 게시글 id 가져오기 (post_detail_view.tpl 최상단 div에 data-post-id 있어야 함)
    const postContainer = document.querySelector('.post-container');
    const POST_ID = postContainer ? postContainer.dataset.postId : null;

    if (!POST_ID) {
        console.warn('POST_ID를 찾을 수 없습니다. .post-container[data-post-id] 확인하세요.');
        return;
    }

    const rootCommentListEl = document.getElementById('comment-list');
    if (!rootCommentListEl) {
        console.warn('#comment-list 요소를 찾을 수 없습니다.');
        return;
    }

    // --- 댓글 DOM 빌더 ---------------------------------------

    function renderCommentItem(c) {
        const wrapper = document.createElement('div');
        wrapper.className = 'comment-item';
        wrapper.setAttribute('data-comment-id', c.id);

        // soft delete 된 댓글이면 본문 숨김
        if (c.is_deleted == 1) {
            wrapper.innerHTML = '<div class="comment-deleted">삭제된 댓글입니다.</div>';
            return wrapper;
        }

        // 삭제 버튼은 내가 쓴 댓글일 때만
        let deleteBtnHtml = '';
        if (c.can_delete === 1 || c.can_delete === true) {
            deleteBtnHtml = `
                <button class="btn-comment-delete" data-comment-id="${c.id}">
                    삭제
                </button>
            `;
        }

        const headerHtml = `
            <div class="comment-header">
                <span class="comment-username">${escapeHtml(c.username || '익명')}</span>
                <span class="comment-created">${escapeHtml(c.created_at)}</span>
                ${deleteBtnHtml}
            </div>
        `;

        const bodyHtml = `
            <div class="comment-body-text">${escapeHtml(c.body)}</div>
        `;

        // 댓글 작성 UI
        const replyUiHtml = `
            <div class="comment-actions">
                <button class="btn-reply-toggle" data-target="${c.id}">댓글 달기</button>
            </div>

            <div class="reply-form comment-form" id="reply-form-${c.id}" style="display:none;">
                <textarea class="reply-textarea comment-body-input" id="reply-textarea-${c.id}" placeholder="댓글을 입력하세요..."></textarea>
                <button class="btn-reply-submit comment-submit-btn" data-parent="${c.id}">등록</button>
            </div>
        `;

        // 하위 댓글(대댓글) 영역
        let childrenHtml = '';

        if (c.reply_count > 0) {
            childrenHtml += `
                <button class="comment-replies-toggle"
                        data-parent-id="${c.id}"
                        data-loaded="0"
                        data-offset="0"
                        data-reply-count="${c.reply_count}"
                        data-open="0">
                댓글 ${c.reply_count}개
                </button>
            `;
        }

        childrenHtml += `
            <div class="comment-children" data-parent="${c.id}"></div>
        `;

        wrapper.innerHTML = headerHtml + bodyHtml + replyUiHtml + childrenHtml;
        return wrapper;
    }

    // --- 초기 루트 댓글 불러오기 -----------------------------

    function loadRootComments(offset = 0, limit = 20) {
        const url = `/ci-starter/comments/list?post_id=${encodeURIComponent(POST_ID)}&offset=${encodeURIComponent(offset)}&limit=${encodeURIComponent(limit)}`;

        return fetch(url, {
            method: 'GET',
            credentials: 'same-origin'
        })
        .then(res => res.json())
        .then(json => {
            if (!json.ok) {
                console.error('댓글 로드 실패:', json.message);
                return;
            }

            const comments = json.comments || [];
            comments.forEach(function (c) {
                const node = renderCommentItem(c);
                rootCommentListEl.appendChild(node);
            });

            // has_more / next_offset 로직은 필요시 여기에 처리
        })
        .catch(err => {
            console.error(err);
        });
    }

    // --- 대댓글(자식 댓글) 로딩 ------------------------------

    function loadChildComments(parentId, btnEl) {
        if (!parentId) return;

        // 버튼 상태 읽기
        const alreadyLoaded = btnEl.getAttribute('data-loaded'); // "0" 또는 "1"
        let offset = parseInt(btnEl.getAttribute('data-offset'), 10);
        if (isNaN(offset)) offset = 0;

        // 이미 전부 불러왔고 토글만 하고 싶으면 여기서 토글만 처리하는 방식도 가능.
        // 일단은 서버 계속 호출해서 pagination 지원.
        const url = `/ci-starter/comments/list?post_id=${encodeURIComponent(POST_ID)}&parent_id=${encodeURIComponent(parentId)}&offset=${encodeURIComponent(offset)}&limit=20`;

        fetch(url, {
            method: 'GET',
            credentials: 'same-origin'
        })
        .then(res => res.json())
        .then(json => {
            if (!json.ok) {
                alert(json.message || '댓글 로딩 실패');
                return;
            }

            const list = json.comments || [];
            const childWrap = document.querySelector('.comment-children[data-parent="' + parentId + '"]');
            if (!childWrap) {
                console.warn('자식 댓글 컨테이너를 찾을 수 없음:', parentId);
                return;
            }

            list.forEach(function (childComment) {
                const childNode = renderCommentItem(childComment);
                childNode.classList.add('comment-reply');
                childWrap.appendChild(childNode);
            });

            if (json.has_more) {
                const nextOffset = json.next_offset;
                btnEl.setAttribute('data-offset', String(nextOffset));
                btnEl.setAttribute('data-loaded', '0');
                btnEl.textContent = '더보기';
            } else {
                // 더 이상 불러올 게 없으면 loaded=1로 바꿔서
                btnEl.setAttribute('data-loaded', '1');
                btnEl.setAttribute('data-open', '1');
                btnEl.textContent = '닫기';

                // (선택) 이미 로드한 상태에서 다시 누르면 토글만 할 수도 있음
                // -> 그 로직은 click handler 쪽에서 처리
            }
        })
        .catch(err => {
            console.error(err);
        });
    }

    // --- 댓글 작성 (루트 댓글) -------------------------------

    const writeBtn = document.getElementById('btn-comment-write');
    if (writeBtn) {
        writeBtn.addEventListener('click', function() {
            const textarea = document.getElementById('comment-body');
            if (!textarea) return;

            const body = textarea.value.trim();
            if (!body) {
                alert('댓글 내용을 입력하세요.');
                return;
            }

            const formData = new FormData();
            formData.append('post_id', POST_ID);
            formData.append('body', body);

            fetch('/ci-starter/comments/create', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(res => res.json())
            .then(json => {
                if (!json.ok) {
                    alert(json.message || '등록 실패');
                    return;
                }

                const newComment = json.comment;
                const node = renderCommentItem(newComment);
                rootCommentListEl.appendChild(node);
                textarea.value = '';
            })
            .catch(err => {
                console.error(err);
            });
        });
    }

    // --- 전역 클릭 핸들러: 댓글 토글 / 댓글 등록 / 삭제 / 대댓글 로드 ----

    document.addEventListener('click', function(e) {

        // 1) "댓글" 버튼 눌러서 reply form 열고 닫기
        if (e.target.classList.contains('btn-reply-toggle')) {
            const targetId = e.target.getAttribute('data-target'); // 부모 댓글 id
            const formEl = document.getElementById('reply-form-' + targetId);
            if (formEl) {
                formEl.style.display = (formEl.style.display === 'none' ? 'block' : 'none');
            }
        }

        // 2) "등록"(대댓글 작성)
        if (e.target.classList.contains('btn-reply-submit')) {
            const parentId = e.target.getAttribute('data-parent');
            const textarea = document.getElementById('reply-textarea-' + parentId);
            if (!textarea) return;

            const body = textarea.value.trim();
            if (!body) {
                alert('댓글 내용을 입력하세요.');
                return;
            }

            const formData = new FormData();
            formData.append('post_id', POST_ID);
            formData.append('parent_id', parentId);
            formData.append('body', body);

            fetch('/ci-starter/comments/create', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(res => res.json())
            .then(json => {
                if (!json.ok) {
                    alert(json.message || '등록 실패');
                    return;
                }

                // 방금 등록한 대댓글
                const newReply = json.comment;
                const childWrap = document.querySelector('.comment-children[data-parent="' + parentId + '"]');
                if (childWrap) {
                    const node = renderCommentItem(newReply);
                    node.classList.add('comment-reply');
                    childWrap.appendChild(node);
                }

                textarea.value = '';

                // 부모 댓글의 "댓글 보기" 버튼이 있다면 reply_count 증가/텍스트 갱신은 선택적으로 가능
            })
            .catch(err => {
                console.error(err);
            });
        }

        // 3) "삭제" 버튼 눌렀을 때
        if (e.target.classList.contains('btn-comment-delete')) {
            const commentId = e.target.getAttribute('data-comment-id');
            if (!commentId) return;

            if (!confirm('정말 이 댓글을 삭제하시겠습니까?')) {
                return;
            }

            const formData = new FormData();
            formData.append('comment_id', commentId);

            fetch('/ci-starter/comments/delete', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(res => res.json())
            .then(json => {
                if (!json.ok) {
                    alert(json.message || '삭제 실패');
                    return;
                }

                const item = document.querySelector('.comment-item[data-comment-id="' + commentId + '"]');
                if (item) {
                    item.innerHTML = '<div class="comment-deleted">삭제된 댓글입니다.</div>';
                }
            })
            .catch(err => {
                console.error(err);
            });
        }

        // 4) 🔥 "댓글 N개 보기" / "댓글 더보기" 버튼 눌렀을 때
        if (e.target.classList.contains('comment-replies-toggle')) {
        const btnEl    = e.target;
        const parentId = btnEl.getAttribute('data-parent-id');
        const loaded   = btnEl.getAttribute('data-loaded'); // "0" or "1"

        if (loaded === '1') {
            const childWrap = document.querySelector('.comment-children[data-parent="' + parentId + '"]');
            if (childWrap) {
            const isOpen = btnEl.getAttribute('data-open') === '1';
            if (isOpen) {
                // 닫기 → 숨김 + n개
                childWrap.style.display = 'none';
                const cnt = btnEl.getAttribute('data-reply-count') || '0';
                btnEl.textContent = `${cnt}개`;
                btnEl.setAttribute('data-open', '0');
            } else {
                // 열기 → 보임 + 닫기
                childWrap.style.display = 'block';
                btnEl.textContent = '닫기';
                btnEl.setAttribute('data-open', '1');
            }
            }
            return;
        }

        // 아직 다 안 불러왔으면 서버 호출
        loadChildComments(parentId, btnEl);
        }
    });

    // --- 초기 실행: 루트 댓글 첫 페이지 로드 ----------------
    loadRootComments(0, 20);

})();
