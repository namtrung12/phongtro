<?php $pageTitle = 'Tin nhắn với quản trị viên'; ?>
<style>
  .chat-shell {
    display:grid;
    grid-template-columns:280px 1fr;
    gap:12px;
    align-items:start;
  }
  .user-chat-row { display:flex; width:100%; }
  .user-chat-row.mine { justify-content:flex-end; }
  .user-chat-row.theirs { justify-content:flex-start; }
  .user-chat-bubble {
    width:min(var(--chat-bubble-width, 320px), calc(100% - 8px));
    min-height:var(--chat-bubble-min-height, 68px);
    padding:10px 12px;
    border-radius:12px;
    display:flex;
    flex-direction:column;
    justify-content:space-between;
    gap:6px;
    flex:0 0 auto;
    overflow-wrap:anywhere;
  }
  .user-chat-panel {
    height:clamp(420px, 62vh, 640px);
    display:flex;
    flex-direction:column;
    min-width:0;
    min-height:0;
  }
  .user-chat-thread {
    overflow:auto;
    flex:1 1 auto;
    display:flex;
    flex-direction:column;
    gap:10px;
    min-height:0;
    scroll-padding-bottom:88px;
  }
  .user-chat-input {
    padding:12px;
    border-top:1px solid #e5e7eb;
    background:#fff;
  }
  .user-chat-input input {
    flex:1 1 auto;
    min-width:0;
  }
  .user-chat-row.mine .user-chat-bubble {
    background:#d97706;
    color:#fff;
    border:1px solid #b45309;
    margin-left:auto;
  }
  .user-chat-row.theirs .user-chat-bubble {
    background:#f3f4f6;
    border:1px solid #e5e7eb;
    margin-right:auto;
  }
  @media (max-width: 768px) {
    .chat-shell { grid-template-columns:1fr; }
    .user-chat-panel {
      height: min(68dvh, 520px);
      border-radius:12px;
    }
    .user-chat-thread {
      scroll-padding-bottom: calc(110px + var(--chat-keyboard-offset, 0px));
    }
    .user-chat-input {
      position: sticky;
      bottom: env(safe-area-inset-bottom, 0px);
      transform: translateY(calc(var(--chat-keyboard-offset, 0px) * -1));
      z-index: 4;
      box-shadow: 0 -10px 24px rgba(15,23,42,0.08);
      transition: transform .18s ease-out;
    }
  }
</style>
<div class="card">
  <div class="card-body">
    <h1 class="mb-3" style="font-size:20px;">Tin nhắn với quản trị viên</h1>
    <?php if (!empty($flashSuccess)): ?><div class="alert alert-success"><?= htmlspecialchars($flashSuccess) ?></div><?php endif; ?>
    <?php if (!empty($flashError)): ?><div class="alert alert-danger"><?= htmlspecialchars($flashError) ?></div><?php endif; ?>
    <div class="chat-shell">
      <div class="card" style="box-shadow:none;border:1px solid #e5e7eb;">
        <div class="card-body" style="padding:12px;">
          <div style="font-weight:700;margin-bottom:8px;">Quản trị viên</div>
          <div class="d-flex align-items-center gap-2">
            <div style="width:38px;height:38px;border-radius:50%;background:#d97706;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;overflow:hidden;">
              <img src="<?= htmlspecialchars(assetUrl($user['avatar'] ?? 'avt.jpg')) ?>" alt="avt" style="width:100%;height:100%;object-fit:cover;" onerror="this.style.display='none'">
            </div>
            <div>
              <div style="font-weight:600;">Quản trị viên</div>
              <small class="text-muted">Hỗ trợ & thông báo</small>
            </div>
          </div>
        </div>
      </div>
      <div class="card user-chat-panel">
        <div id="chatMessages" class="card-body user-chat-thread">
          <?php if (empty($messages)): ?>
            <div class="text-muted">Chưa có tin nhắn.</div>
          <?php endif; ?>
          <?php foreach (array_reverse($messages) as $m): ?>
            <?php $mine = ((int)$m['sender_id'] === (int)$user['id']) ? 'mine' : 'theirs'; ?>
            <div class="user-chat-row <?= $mine ?>">
              <div class="user-chat-bubble">
                <div style="white-space:pre-wrap;"><?= htmlspecialchars($m['content_effective'] ?? $m['content'] ?? $m['message'] ?? '') ?></div>
                <div class="msg-time" data-time="<?= htmlspecialchars($m['created_at']) ?>" style="font-size:11px;opacity:0.7;margin-top:4px;">✓ <?= htmlspecialchars($m['created_at']) ?></div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
        <form method="post" id="userChatForm" class="d-flex gap-2 user-chat-input">
          <input type="text" name="content" class="form-control" placeholder="Nhập tin nhắn..." required>
          <input type="hidden" name="ajax" value="1">
          <button class="btn btn-primary" type="submit">Gửi</button>
        </form>
      </div>
    </div>
  </div>
</div>
<script>
  (() => {
    const currentUserId = <?= (int)$user['id'] ?>;
    const box = document.getElementById('chatMessages');
    const form = document.getElementById('userChatForm');
    const composerInput = form?.querySelector('input[name="content"]');
    let loading = false;
    let lastSignature = '';
    let composerLiftTimer = null;

    const escapeHtml = (str) => String(str).replace(/[&<>"']/g, (c) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const isMobileChat = () => window.matchMedia('(max-width: 768px)').matches;
    const relTime = (iso) => {
      const t = new Date(String(iso).replace(' ', 'T'));
      const diff = (Date.now() - t.getTime()) / 1000;
      if (!Number.isFinite(diff)) return iso;
      if (diff < 60) return 'vừa xong';
      if (diff < 3600) return `Đã gửi ${Math.floor(diff / 60)} phút trước`;
      if (diff < 86400) return `Đã gửi ${Math.floor(diff / 3600)} giờ trước`;
      return iso;
    };
    const updateTimes = () => {
      document.querySelectorAll('#chatMessages .msg-time').forEach(el => {
        const t = el.getAttribute('data-time');
        if (t) {
          el.textContent = '✓ ' + relTime(t);
          el.title = t;
        }
      });
    };
    const getKeyboardInset = () => {
      if (!window.visualViewport) return 0;
      return Math.max(0, window.innerHeight - window.visualViewport.height - window.visualViewport.offsetTop);
    };
    const syncComposerLift = (behavior = 'smooth') => {
      if (!form || !isMobileChat()) return;
      const keyboardInset = getKeyboardInset();
      const resolvedBehavior = behavior === 'instant' ? 'auto' : 'smooth';
      document.documentElement.style.setProperty('--chat-keyboard-offset', `${keyboardInset}px`);

      if (!composerInput || document.activeElement !== composerInput) return;

      if (composerLiftTimer) {
        clearTimeout(composerLiftTimer);
      }
      composerLiftTimer = window.setTimeout(() => {
        box?.scrollTo({ top: box.scrollHeight, behavior: resolvedBehavior });
        form.scrollIntoView({ block: 'end', inline: 'nearest', behavior: resolvedBehavior });
        const viewportHeight = window.visualViewport ? window.visualViewport.height : window.innerHeight;
        const rect = form.getBoundingClientRect();
        const overlap = rect.bottom - viewportHeight + 14;
        if (overlap > 0) {
          window.scrollBy({ top: overlap, left: 0, behavior: resolvedBehavior });
        }
      }, 70);
    };
    const resetComposerLift = () => {
      document.documentElement.style.setProperty('--chat-keyboard-offset', '0px');
      if (composerLiftTimer) {
        clearTimeout(composerLiftTimer);
        composerLiftTimer = null;
      }
    };
    const renderMessages = (messages = []) => {
      if (!box) return;
      const signature = messages.map(m => `${m.id}:${m.created_at}`).join('|');
      if (signature === lastSignature) {
        updateTimes();
        return;
      }
      lastSignature = signature;
      box.innerHTML = '';
      if (!messages.length) {
        box.innerHTML = '<div class="text-muted">Chưa có tin nhắn.</div>';
        return;
      }
      messages.slice().reverse().forEach(m => {
        const mine = Number(m.sender_id) === currentUserId;
        const row = document.createElement('div');
        row.className = `user-chat-row ${mine ? 'mine' : 'theirs'}`;
        row.innerHTML = `
          <div class="user-chat-bubble">
            <div style="white-space:pre-wrap;">${escapeHtml(m.content_effective || m.content || m.message || '')}</div>
            <div class="msg-time" data-time="${escapeHtml(m.created_at || '')}" style="font-size:11px;opacity:0.7;margin-top:4px;">✓ ${escapeHtml(m.created_at || '')}</div>
          </div>`;
        box.appendChild(row);
      });
      box.scrollTop = box.scrollHeight;
      updateTimes();
      syncComposerLift('instant');
    };
    const loadMessages = async () => {
      if (loading) return;
      loading = true;
      try {
        const res = await fetch('?route=messages&ajax=1', { cache: 'no-store' });
        const json = await res.json();
        if (json.ok) renderMessages(json.messages || []);
      } catch (err) {
        console.error(err);
      } finally {
        loading = false;
      }
    };

    form?.addEventListener('submit', async (e) => {
      e.preventDefault();
      const input = form.querySelector('input[name="content"]');
      const ajaxInput = form.querySelector('input[name="ajax"]');
      const btn = form.querySelector('button[type="submit"]');
      if (!input || !input.value.trim()) return;
      btn.disabled = true;
      try {
        const fd = new FormData(form);
        const res = await fetch('?route=messages', { method: 'POST', body: fd });
        const raw = await res.text();
        const json = JSON.parse(raw);
        if (!res.ok || !json.ok) {
          throw new Error(json.error || 'Gửi tin nhắn thất bại.');
        }
        if (json.ok) {
          input.value = '';
          renderMessages(json.messages || []);
        }
      } catch (err) {
        console.error(err);
        if (ajaxInput) ajaxInput.value = '0';
        form.submit();
      } finally {
        btn.disabled = false;
        input.focus();
        syncComposerLift();
      }
    });

    composerInput?.addEventListener('focus', () => syncComposerLift());
    composerInput?.addEventListener('click', () => syncComposerLift());
    window.visualViewport?.addEventListener('resize', () => syncComposerLift('instant'));
    window.visualViewport?.addEventListener('scroll', () => syncComposerLift('instant'));
    window.addEventListener('orientationchange', () => window.setTimeout(() => syncComposerLift('instant'), 160));
    document.addEventListener('focusout', () => {
      window.setTimeout(() => {
        if (document.activeElement !== composerInput) {
          resetComposerLift();
        }
      }, 120);
    });

    updateTimes();
    loadMessages();
    setInterval(loadMessages, 4000);
  })();
</script>
