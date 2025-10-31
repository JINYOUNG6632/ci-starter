{? attachments}
  <section class="post-attachments">
    <h3 class="section-title">첨부</h3>
    <ul class="attach-list">
      {@ attachments}
        <li class="attach-item" style="display:flex;align-items:center;gap:8px;">
          <a class="attach-link"
             href="/ci-starter/files/download/{attachments->id}"
             aria-label="{attachments->original_filename}">
            {attachments->original_filename}
          </a>
          <small class="muted">({attachments->file_size} bytes)</small>
        </li>
      {/}
    </ul>
  </section>
{/}
