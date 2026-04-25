<?php $pageTitle = 'Tin nhắn hệ thống'; ?>
<style>
  .chat-admin-shell {
    display: grid;
    grid-template-columns: 300px 1fr;
    gap: 16px;
    align-items: stretch;
    min-height: 640px;
  }
  .chat-list {
    background: linear-gradient(180deg, #fbbf24, #f59e0b);
    color:#0f172a;
    border-radius:16px;
    overflow:hidden;
    box-shadow:0 14px 30px rgba(217,119,6,0.22);
    display:flex;
    flex-direction:column;
    height:clamp(560px, 76vh, 760px);
    min-height:0;
  }
  .chat-list .head { padding:12px 14px; font-weight:800; font-size:16px; color:#7c2d12; }
  .chat-search { padding:0 12px 12px; flex:0 0 auto; }
  .chat-search input { width:100%; border-radius:24px; border:1px solid rgba(255,255,255,0.6); padding:10px 12px; background:rgba(255,255,255,0.8); color:#0f172a; }
  .chat-item { display:flex; gap:10px; padding:10px 12px; cursor:pointer; align-items:center; transition:background .15s; }
  .chat-item:hover { background:rgba(255,255,255,0.35); }
  .chat-item.active { background:#f59e0b; color:#0f172a; }
  .chat-item .avatar { width:38px; height:38px; border-radius:50%; background:#fff; display:flex; align-items:center; justify-content:center; color:#b45309; font-weight:700; }
  .chat-item .meta { flex:1; }
  .chat-item .name { font-weight:700; }
  .chat-item .preview { font-size:12px; color:#7c2d12; opacity:0.8; }
  .chat-item .time { font-size:11px; color:#7c2d12; opacity:0.72; margin-top:2px; }
  #chatListItems { flex:1 1 auto; min-height:0; }
  .chat-pane {
    background:#fff;
    border-radius:16px;
    border:1px solid #fcd34d;
    height:clamp(560px, 76vh, 760px);
    min-height:0;
    display:flex;
    flex-direction:column;
    overflow:hidden;
    box-shadow:0 18px 36px rgba(15, 23, 42, 0.08);
  }
  .chat-pane header { padding:12px 14px; border-bottom:1px solid #fcd34d; display:flex; justify-content:space-between; align-items:center; color:#b45309; font-weight:700; }
  .chat-thread { flex:1 1 auto; padding:14px; overflow:auto; display:flex; flex-direction:column; gap:10px; background:#fff8e6; }
  .chat-msg-row { display:flex; width:100%; }
  .chat-msg-row.mine { justify-content:flex-end; }
  .chat-msg-row.theirs { justify-content:flex-start; }
  .bubble {
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
  .bubble.mine { margin-left:auto; background:#d97706; color:#fff; }
  .bubble.theirs { margin-right:auto; background:#fff; border:1px solid #fcd34d; }
  .chat-input { padding:12px; border-top:1px solid #fcd34d; display:flex; gap:10px; background:#fff; }
  .chat-input input { flex:1 1 auto; min-width:0; }
  @media (max-width: 768px) {
    .chat-admin-shell { grid-template-columns: 1fr; gap: 12px; min-height: auto; }
    .chat-list { border-radius: 12px; }
    #chatListItems { max-height: 260px; }
    .chat-pane {
      height: min(72dvh, 560px);
      border-radius: 12px;
    }
    .chat-thread {
      min-height: 0;
      max-height: none;
      scroll-padding-bottom: calc(110px + var(--chat-keyboard-offset, 0px));
    }
    .chat-input {
      position: sticky;
      bottom: env(safe-area-inset-bottom, 0px);
      transform: translateY(calc(var(--chat-keyboard-offset, 0px) * -1));
      z-index: 4;
      box-shadow: 0 -10px 24px rgba(15,23,42,0.08);
      transition: transform .18s ease-out;
    }
  }
</style>
<div class="admin-shell">
  <aside class="admin-menu">
    <a href="?route=admin" class="<?= ($activeMenu ?? '') === 'home' ? 'active' : '' ?>">Bảng điều khiển</a>
    <a href="?route=admin-users" class="<?= ($activeMenu ?? '') === 'users' ? 'active' : '' ?>">Người dùng</a>
    <a href="?route=admin-leads" class="<?= ($activeMenu ?? '') === 'leads' ? 'active' : '' ?>">Nhu cầu</a>
    <a href="?route=admin-payments" class="<?= ($activeMenu ?? '') === 'payments' ? 'active' : '' ?>">Giao dịch</a>
    <a href="?route=admin-reports" class="<?= ($activeMenu ?? '') === 'reports' ? 'active' : '' ?>">Báo cáo</a>
    <a href="?route=admin-settings" class="<?= ($activeMenu ?? '') === 'theme' ? 'active' : '' ?>">Cài đặt hệ thống</a>
    <a href="?route=admin-audit-logs" class="<?= ($activeMenu ?? '') === 'audit' ? 'active' : '' ?>">Nhật ký kiểm tra</a>
  </aside>
  <div>
    <?php
      // loại bỏ chính admin khỏi danh sách
      $messageUsers = array_values(array_filter($messageUsers, function ($u) use ($user) {
          return (int)$u['id'] !== (int)$user['id'] && (($u['role'] ?? '') !== 'admin');
      }));
      $activeId = $filterUserId ?? null;
      if ($activeId === null && !empty($messageUsers)) {
          $activeId = (int)$messageUsers[0]['id'];
      }
      $activeName = '';
      foreach ($messageUsers as $u) {
        if ((int)$u['id'] === (int)$activeId) { $activeName = $u['name']; break; }
      }
    ?>
    <div class="chat-admin-shell">
      <div class="chat-list">
        <div class="head">Đoạn trò chuyện</div>
        <div class="chat-search"><input type="text" id="chatFilter" placeholder="Tìm kiếm..."></div>
        <div id="chatListItems">
          <?php foreach ($messageUsers as $u): ?>
            <?php $avatarUrl = assetUrl($u['avatar'] ?? 'avt.jpg'); ?>
            <a href="?route=admin-messages&user_id=<?= (int)$u['id'] ?>" class="chat-item <?= ($activeId===(int)$u['id'])?'active':'' ?>" data-user-id="<?= (int)$u['id'] ?>" data-name="<?= strtolower(htmlspecialchars($u['name'])) ?>">
              <div class="avatar" style="overflow:hidden;">
                <img src="<?= htmlspecialchars($avatarUrl) ?>" alt="avt" style="width:100%;height:100%;object-fit:cover;" onerror="this.style.display='none'">
              </div>
              <div class="meta">
                <div class="name"><?= htmlspecialchars($u['name']) ?></div>
                <div class="preview"><?= htmlspecialchars(mb_strimwidth((string)($u['latest_message'] ?? ('#' . (int)$u['id'])), 0, 42, '...')) ?></div>
                <?php if (!empty($u['latest_at'])): ?><div class="time"><?= htmlspecialchars($u['latest_at']) ?></div><?php endif; ?>
              </div>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="chat-pane">
        <header>
          <div id="adminChatTitle" style="font-weight:700;">
            <?= $activeId ? ('Trò chuyện với '.htmlspecialchars($activeName).' (#'.$activeId.')') : 'Chọn người để trò chuyện' ?>
          </div>
        </header>
        <div class="chat-thread" id="adminChatThread">
          <?php if (!$activeId): ?>
            <div class="text-muted">Chưa có cuộc trò chuyện. Hãy chọn người ở danh sách bên trái.</div>
          <?php elseif (empty($messages)): ?>
            <div class="text-muted">Chưa có tin nhắn.</div>
          <?php endif; ?>
          <?php foreach (array_reverse($messages) as $m): ?>
            <?php
              $isFromActiveUser = $activeId ? ((int)$m['sender_id'] === (int)$activeId) : false;
              $mine = $isFromActiveUser ? 'theirs' : 'mine';
            ?>
            <div class="chat-msg-row <?= $mine ?>">
              <div class="bubble <?= $mine ?>">
                <div style="white-space:pre-wrap;"><?= htmlspecialchars($m['content_effective'] ?? $m['content'] ?? $m['message'] ?? '') ?></div>
                <div style="font-size:11px;opacity:0.7;margin-top:4px;" class="msg-time" data-time="<?= htmlspecialchars($m['created_at']) ?>">✓ <?= htmlspecialchars($m['created_at']) ?></div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
        <form method="post" class="chat-input" id="adminChatForm">
          <input type="text" name="content" class="form-control" placeholder="Nhập tin nhắn..." <?= $activeId ? '' : 'disabled' ?> required>
          <input type="hidden" name="target_user_id" id="adminTargetUser" value="<?= $activeId ?? '' ?>">
          <input type="hidden" name="ajax" value="1">
          <button class="btn btn-primary" type="submit" <?= $activeId ? '' : 'disabled' ?>>Gửi</button>
        </form>
      </div>
    </div>
  </div>
</div>
<script>
  (() => {
    const filterInput = document.getElementById('chatFilter');
    const listBox = document.getElementById('chatListItems');
    const title = document.getElementById('adminChatTitle');
    const thread = document.getElementById('adminChatThread');
    const form = document.getElementById('adminChatForm');
    const targetInput = document.getElementById('adminTargetUser');
    const composerInput = form?.querySelector('input[name="content"]');
    let activeId = <?= $activeId ? (int)$activeId : 'null' ?>;
    let activeName = <?= json_encode($activeName, JSON_UNESCAPED_UNICODE) ?>;
    let lastMessagesSignature = '';
    let lastUsersSignature = '';
    let loadingMessages = false;
    let loadingUsers = false;
    let composerLiftTimer = null;

    const isMobileChat = () => window.matchMedia('(max-width: 768px)').matches;
    const relTime = (iso) => {
      const t = new Date(String(iso).replace(' ','T'));
      const diff = (Date.now() - t.getTime())/1000;
      if (!Number.isFinite(diff)) return iso;
      if (diff < 60) return 'vừa xong';
      if (diff < 3600) return `Đã gửi ${Math.floor(diff/60)} phút trước`;
      if (diff < 86400) return `Đã gửi ${Math.floor(diff/3600)} giờ trước`;
      return iso;
    };
    const escapeHtml = (str) => String(str).replace(/[&<>"']/g, (c) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const shortText = (str, len = 42) => {
      str = String(str || '').replace(/\s+/g, ' ').trim();
      return str.length > len ? str.slice(0, len - 3) + '...' : str;
    };
    const assetUrl = (path) => {
      path = String(path || '').trim();
      if (!path) return <?= json_encode(assetUrl('avt.jpg')) ?>;
      if (/^(https?:)?\/\//i.test(path) || /^(data|blob):/i.test(path)) return path;
      if (path.startsWith('/')) return path;
      return <?= json_encode(baseUrl()) ?> + path.replace(/^\/+/, '');
    };
    const applyFilter = () => {
      const q = (filterInput?.value || '').trim().toLowerCase();
      Array.from(document.querySelectorAll('#chatListItems .chat-item')).forEach(i => {
        const n = i.getAttribute('data-name') || '';
        i.style.display = n.includes(q) ? '' : 'none';
      });
    };
    filterInput?.addEventListener('input', applyFilter);
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
        thread?.scrollTo({ top: thread.scrollHeight, behavior: resolvedBehavior });
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

    const renderMsgs = (msgs=[]) => {
      if (!thread) return;
      const signature = msgs.map(m => `${m.id}:${m.created_at}`).join('|');
      if (signature === lastMessagesSignature) {
        updateTimes();
        return;
      }
      lastMessagesSignature = signature;
      thread.innerHTML = '';
      if (!msgs.length) {
        thread.innerHTML = '<div class="text-muted">Chưa có tin nhắn.</div>';
        return;
      }
      msgs.slice().reverse().forEach(m => {
        const isFromActiveUser = activeId ? Number(m.sender_id) === Number(activeId) : false;
        const mine = !isFromActiveUser;
        const bubble = document.createElement('div');
        bubble.className = `chat-msg-row ${mine ? 'mine' : 'theirs'}`;
        bubble.innerHTML = `
          <div class="bubble ${mine ? 'mine':'theirs'}">
            <div style="white-space:pre-wrap;">${escapeHtml(m.content_effective || m.content || m.message || '')}</div>
            <div class="msg-time" data-time="${escapeHtml(m.created_at || '')}" style="font-size:11px;opacity:0.7;margin-top:4px;">✓ ${escapeHtml(m.created_at || '')}</div>
          </div>`;
        thread.appendChild(bubble);
      });
      thread.scrollTop = thread.scrollHeight;
      updateTimes();
      syncComposerLift('instant');
    };
    const updateTimes = () => {
      document.querySelectorAll('.msg-time').forEach(el => {
        const t = el.getAttribute('data-time');
        if (t) {
          el.textContent = '✓ ' + relTime(t);
          el.title = t;
        }
      });
    };
    const selectUser = (id, name, pushUrl = true) => {
      activeId = Number(id) || null;
      activeName = name || '';
      lastMessagesSignature = '';
      document.querySelectorAll('#chatListItems .chat-item').forEach(i => {
        i.classList.toggle('active', Number(i.dataset.userId) === activeId);
      });
      if (targetInput) targetInput.value = activeId || '';
      if (title) title.textContent = activeId ? `Trò chuyện với ${activeName} (#${activeId})` : 'Chọn người để trò chuyện';
      const contentInput = form?.querySelector('input[name="content"]');
      const submitBtn = form?.querySelector('button[type="submit"]');
      if (contentInput) contentInput.disabled = !activeId;
      if (submitBtn) submitBtn.disabled = !activeId;
      if (pushUrl && activeId) {
        history.replaceState(null, '', `?route=admin-messages&user_id=${activeId}`);
      }
      loadMsgs();
      if (activeId) {
        window.setTimeout(() => syncComposerLift('instant'), 50);
      }
    };
    const bindUserClicks = () => {
      document.querySelectorAll('#chatListItems .chat-item').forEach(item => {
        item.addEventListener('click', (e) => {
          e.preventDefault();
          selectUser(item.dataset.userId, item.querySelector('.name')?.textContent || '');
        });
      });
    };
    const renderUsers = (users = []) => {
      if (!listBox) return;
      users = users.filter(u => String(u.role || '') !== 'admin');
      const signature = users.map(u => `${u.id}:${u.latest_at || ''}:${u.latest_message || ''}`).join('|');
      if (signature === lastUsersSignature) {
        applyFilter();
        return;
      }
      lastUsersSignature = signature;
      listBox.innerHTML = '';
      if (!users.length) {
        listBox.innerHTML = '<div class="text-muted" style="padding:12px;">Chưa có người nhắn.</div>';
        selectUser(null, '', false);
        return;
      }
      users.forEach(u => {
        const id = Number(u.id);
        const avatar = assetUrl(u.avatar || 'avt.jpg');
        const item = document.createElement('a');
        item.href = `?route=admin-messages&user_id=${id}`;
        item.className = `chat-item ${id === activeId ? 'active' : ''}`;
        item.dataset.userId = String(id);
        item.dataset.name = String(u.name || '').toLowerCase();
        item.innerHTML = `
          <div class="avatar" style="overflow:hidden;">
            <img src="${escapeHtml(avatar)}" alt="avt" style="width:100%;height:100%;object-fit:cover;" onerror="this.style.display='none'">
          </div>
          <div class="meta">
            <div class="name">${escapeHtml(u.name || 'Người dùng')}</div>
            <div class="preview">${escapeHtml(shortText(u.latest_message || ('#' + id)))}</div>
            ${u.latest_at ? `<div class="time">${escapeHtml(relTime(u.latest_at))}</div>` : ''}
          </div>`;
        listBox.appendChild(item);
      });
      bindUserClicks();
      applyFilter();
      if (!activeId && users[0]) {
        selectUser(users[0].id, users[0].name || '', false);
      }
    };
    const loadMsgs = async () => {
      if (!activeId || loadingMessages) return;
      loadingMessages = true;
      try {
        const res = await fetch(`?route=admin-messages&user_id=${activeId}&ajax=1`, { cache: 'no-store' });
        const json = await res.json();
        if (json.ok) renderMsgs(json.messages || []);
      } catch (err) {
        console.error(err);
      } finally {
        loadingMessages = false;
      }
    };
    const loadUsers = async () => {
      if (loadingUsers) return;
      loadingUsers = true;
      try {
        const res = await fetch('?route=admin-messages&ajax=users', { cache: 'no-store' });
        const json = await res.json();
        if (json.ok) renderUsers(json.users || []);
      } catch (err) {
        console.error(err);
      } finally {
        loadingUsers = false;
      }
    };
    form?.addEventListener('submit', async (e) => {
      e.preventDefault();
      const fd = new FormData(form);
      const content = (fd.get('content')||'').toString().trim();
      if (!content) return;
      const ajaxInput = form.querySelector('input[name="ajax"]');
      const btn = form.querySelector('button[type=\"submit\"]');
      btn.disabled = true;
      try {
        const res = await fetch('?route=admin-messages', { method: 'POST', body: fd });
        const raw = await res.text();
        const json = JSON.parse(raw);
        if (!res.ok || !json.ok) {
          throw new Error(json.error || 'Gửi tin nhắn thất bại.');
        }
        if (json.ok) {
          const contentInput = form.querySelector('input[name="content"]');
          if (contentInput) contentInput.value = '';
          if (targetInput) targetInput.value = activeId || '';
          if (Array.isArray(json.users)) {
            renderUsers(json.users || []);
          } else {
            loadUsers();
          }
          if (Array.isArray(json.messages)) {
            renderMsgs(json.messages || []);
          } else {
            loadMsgs();
          }
        }
      } catch (err) {
        console.error(err);
        if (ajaxInput) ajaxInput.value = '0';
        form.submit();
      } finally {
        btn.disabled = false;
        composerInput?.focus();
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
    bindUserClicks();
    updateTimes();
    loadUsers();
    loadMsgs();
    setInterval(loadUsers, 5000);
    setInterval(loadMsgs, 4000);
  })();
</script>

