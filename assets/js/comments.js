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

    const prevHref = cur > 1 ? buildPageHref(cur - 1) : '';
    const nextHref = cur < total ? buildPageHref(cur + 1) : '';

    nav.innerHTML = `
      <a class="page-link prev" ${prevHref ? `href="${prevHref}"` : 'style="display:none"'}>이전</a>
      <span class="page-counter">${cur} / ${total}</span>
      <a class="page-link next" ${nextHref ? `href="${nextHref}"` : 'style="display:none"'}>다음</a>
    `;
  };

  /* ---------------------- Page Loader ---------------------- */

  // 기존 loadPage 전체를 이걸로 교체
  const loadPage = async (p, replyTo = '') => {
    const res = await fetch(
      `/ci-starter/comments/page?post_id=${getPostId()}&page=${p}` + (replyTo ? `&reply_to=${replyTo}` : ''),
      { headers: { 'X-Requested-With': 'XMLHttpRequest' } }
    );
    const json = await res.json();
    if (!json.ok) {
      alert(json.msg || '댓글 페이지 로드 실패');
      return;
    }

    // 1) 리스트 컨테이너 보장: 없으면 생성해서 pagination 앞(또는 맨 끝)에 끼워넣기
    let listEl = document.querySelector('#comment-list');
    if (!listEl) {
      listEl = document.createElement('section');
      listEl.id = 'comment-list';

      const pag = section.querySelector('.pagination');
      if (pag && pag.parentNode === section) {
        section.insertBefore(listEl, pag);  // pagination 위에 위치
      } else {
        section.appendChild(listEl);
      }
    }

    // 2) 리스트 교체
    listEl.innerHTML = json.data.list_html;

    // 3) "첫 댓글을 남겨보세요" 제거 조건
    //    서버가 active 카운트를 내려줄 경우 그걸로, 없으면 DOM에 아이템 존재 여부로 판단
    const emptyNotice = section.querySelector('.no-comments');
    const activeCount = json.data.total_count_active;
    const hasItems = !!listEl.querySelector('.comment-item');

    if ((typeof activeCount === 'number' && activeCount > 0) || hasItems) {
      emptyNotice && emptyNotice.remove();
    }

    // 4) 페이지네이션 갱신
    updatePagination(json.data.page, json.data.total_pages);

    // 5) 헤더 카운트(가능하면 active 기준으로)
    if (typeof activeCount === 'number') {
      updateHeader(activeCount);
    } else if (typeof json.data.total_count === 'number') {
      updateHeader(json.data.total_count);
    }

    // 6) 주소창 동기화 (reply_to 유지, 해시는 넣지 않음 → 깜빡임 방지)
    const u = new URL(location.href);
    u.searchParams.set('page', json.data.page);
    if (replyTo) u.searchParams.set('reply_to', replyTo);
    else u.searchParams.delete('reply_to');
    history.replaceState(null, '', `${u.pathname}${u.search}`);
  };

  /* ---------------------- Create Logic ---------------------- */

  const handleCreate = async (form) => {
    const fd = new FormData(form);
    const json = await ajax('/ci-starter/comments/create', fd);
    if (!json.ok) return alert(json.msg);

    const d = json.data;

    // 헤더 갱신
    updateHeader(d.total_count_active ?? d.total_count ?? 0);
    await loadPage(d.page);

    // 서버가 계산한 실제 페이지로 조각 reload
    const replyTo = new URL(location.href).searchParams.get('reply_to') || '';
    await loadPage(d.page, replyTo);

    // 포커스 이동
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

  // 페이지네이션 링크 인터셉트하여 AJAX 로드
  section.addEventListener('click', (e) => {
    const a = e.target.closest('.pagination .page-link[href]');
    if (!a) return;
    const href = a.getAttribute('href');
    if (!href) return;

    const url = new URL(href, location.origin);
    const target = parseInt(url.searchParams.get('page') || '1', 10);
    if (!Number.isFinite(target) || target < 1) return;

    e.preventDefault();
    const replyTo = new URL(location.href).searchParams.get('reply_to') || '';
    loadPage(target, replyTo);
  });

  // 대댓글 + 삭제
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

      // 헤더 갱신
      updateHeader(json.data.total_count_active);

      // 페이지 재조정 후 reload
      const cur = getPage();
      const totalPages = json.data.total_pages || 1;
      const p = Math.min(cur, totalPages);
      await loadPage(p);
    }
  });
});
