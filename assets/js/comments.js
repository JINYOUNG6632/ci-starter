document.addEventListener('DOMContentLoaded', () => {
  const section = document.querySelector('.comment-section');
  if (!section) return;

  const rootForm = document.querySelector('#comment-create-root form');

  /* -------------------------- Util -------------------------- */
  const escapeHtml = (s) =>
    s == null ? '' : String(s)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;');

  const getPage = () => {
    const u = new URL(location.href);
    return parseInt(u.searchParams.get('page') || '1', 10);
  };

  const getPostId = () =>
    section.dataset.postId ||
    document.querySelector('.post-container')?.dataset.postId;

  const ajax = async (url, fd) => {
    const res = await fetch(url, {
      method: 'POST',
      body: fd,
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    });
    return res.json();
  };

  /* ---------------------- UI Update ---------------------- */

  const updateHeader = (cnt) => {
    const t = section.querySelector('.section-title');
    if (t) t.textContent = `댓글 (${cnt})`;
  };

  const buildPageHref = (pageNum) => {
    const url = new URL(location.href);
    url.searchParams.set('page', String(pageNum));
    return `${url.pathname}${url.search}`;
  };

  const updatePagination = (cur, total) => {
    let nav = section.querySelector('.pagination');

    // 네비 없으면 생성
    if (!nav) {
      nav = document.createElement('nav');
      nav.className = 'pagination';
      nav.style = 'margin:18px 0;display:flex;gap:8px;align-items:center;';
      section.appendChild(nav);
    }

    // href를 실제로 채워넣음 (기본 동작 보장 + JS가로채기)
    const prevHref = cur > 1 ? buildPageHref(cur - 1) : '';
    const nextHref = cur < total ? buildPageHref(cur + 1) : '';

    nav.innerHTML = `
      <a class="page-link prev" ${prevHref ? `href="${prevHref}"` : 'style="display:none"'}>이전</a>
      <span class="page-counter">${cur} / ${total}</span>
      <a class="page-link next" ${nextHref ? `href="${nextHref}"` : 'style="display:none"'}>다음</a>
    `;
  };

  const renderComment = (c, canDelete) => {
    const d = document.createElement('div');
    d.id = `c${c.id}`;
    d.className = 'comment-item';
    d.style.marginLeft = (c.depth * 24) + 'px';

    // 삭제된 댓글 즉시 렌더용
    if (c.is_deleted == 1) {
      d.innerHTML = `<div class="comment-deleted">삭제된 댓글입니다.</div>`;
      return d;
    }

    d.innerHTML = `
      <div class="comment-header">
        <span class="comment-user">${escapeHtml(c.username)}</span>
        <div class="comment-meta-right">
          <span class="comment-time">${escapeHtml(c.created_at)}</span>
          <div class="comment-actions">
            <a class="reply-btn" href="${buildPageHref(getPage())}&reply_to=${c.id}#c${c.id}">댓글 달기</a>
            ${canDelete ? `
              <form class="comment-delete-form" style="display:inline;">
                <input type="hidden" name="post_id" value="${c.post_id}">
                <input type="hidden" name="comment_id" value="${c.id}">
                <button type="submit" class="btn btn-delete">삭제</button>
              </form>` : ''}
          </div>
        </div>
      </div>
      <div class="comment-body">${escapeHtml(c.body)}</div>
    `;
    return d;
  };

  /* ---------------------- Page Loader ---------------------- */

  const loadPage = async (p) => {
    const res = await fetch(
      `/ci-starter/comments/page?post_id=${getPostId()}&page=${p}`,
      { headers: { 'X-Requested-With': 'XMLHttpRequest' } }
    );
    const json = await res.json();
    if (!json.ok) {
      alert(json.msg || '댓글 페이지 로드 실패');
      return;
    }

    // 리스트 교체
    document.querySelector('#comment-list').innerHTML = json.data.list_html;

    // 비었으면 no-comments 유지, 아니면 제거(서버 조각에 따라 다를 수 있으니 방어적으로 처리)
    const listHasItems = !!document.querySelector('#comment-list .comment-item');
    const emptyNotice = section.querySelector('.no-comments');
    if (listHasItems && emptyNotice) emptyNotice.remove();

    // 페이지네이션 갱신
    updatePagination(json.data.page, json.data.total_pages);

    // 주소창 sync
    const u = new URL(location.href);
    u.searchParams.set('page', json.data.page);
    history.replaceState(null, '', u);
  };

  /* ---------------------- Create Logic ---------------------- */

  // ✅ 등록 직후엔 항상 서버 조각으로 재동기화(정렬/페이지 수/자리표시자 모두 서버 기준으로)
  const handleCreate = async (form) => {
    const fd = new FormData(form);
    const json = await ajax('/ci-starter/comments/create', fd);
    if (!json.ok) return alert(json.msg);

    const d = json.data;

    // 헤더 카운트 갱신
    updateHeader(d.total_count);

    // 서버가 계산한 '진짜' 페이지로 조각 재로드
    await loadPage(d.page);

    // 새 댓글로 포커스
    setTimeout(() => {
      document.getElementById(`c${d.comment.id}`)?.scrollIntoView({ behavior: 'smooth' });
    }, 100);

    form.reset();
  };

  /* ---------------------- Event Bindings ---------------------- */

  // 루트 댓글 작성
  rootForm?.addEventListener('submit', (e) => {
    e.preventDefault();
    handleCreate(rootForm);
  });

  // 페이지네이션: a[href]를 이벤트 위임으로 가로채서 AJAX 이동 (먹통 방지 + 새 탭 등 기본 기능 보장)
  section.addEventListener('click', (e) => {
    const a = e.target.closest('.pagination .page-link[href]');
    if (!a) return;
    const href = a.getAttribute('href');
    if (!href) return;

    // 같은 오리진만 AJAX 처리
    const url = new URL(href, location.origin);
    const target = parseInt(url.searchParams.get('page') || '1', 10);
    if (!Number.isFinite(target) || target < 1) return;

    e.preventDefault();
    loadPage(target);
  });

  // 대댓글 + 삭제 이벤트 위임
  section.addEventListener('submit', async (e) => {
    const form = e.target;

    // 대댓글
    if (form.matches('.comment-form-reply')) {
      e.preventDefault();
      handleCreate(form);
      return;
    }

    // 삭제
    if (form.matches('.comment-delete-form')) {
      e.preventDefault();
      if (!confirm('이 댓글을 삭제하시겠습니까?')) return;

      const fd = new FormData(form);
      const json = await ajax('/ci-starter/comments/delete', fd);
      if (!json.ok) return alert(json.msg || '삭제 실패');

      // 헤더 카운트 갱신
      updateHeader(json.data.total_count);

      // 현재 페이지가 총 페이지보다 커졌다면 보정
      const cur = getPage();
      const totalPages = json.data.total_pages || 1;
      const targetPage = Math.min(cur, totalPages);

      // ✅ 삭제 후에도 항상 서버 조각 재로드(1/1로 틀어지는 문제 방지)
      await loadPage(targetPage);
    }
  });
});
