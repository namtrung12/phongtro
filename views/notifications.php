<?php $pageTitle = 'Thông báo'; ?>
<?php
  $currentUserId = (int)(currentUser()['id'] ?? 0);
  $highlightNotificationId = (int)($highlightNotificationId ?? 0);
  $unreadCount = (int)($unreadCount ?? 0);
  $notifications = array_values($notifications ?? []);
  $notificationTypeLabels = [
    'lead_interest' => 'Nhu cầu mới',
    'invoice_created' => 'Hóa đơn mới',
    'invoice_status' => 'Cập nhật hóa đơn',
    'support_ticket' => 'Phiếu hỗ trợ',
    'contract_update' => 'Hợp đồng',
    'contract_expiring' => 'Sắp hết hạn',
    'room_status' => 'Trạng thái phòng',
    'general' => 'Thông báo',
  ];
?>
<style>
  .notify-shell { display:flex; flex-direction:column; gap:12px; margin-top:8px; }
  .notify-card { border-radius:14px; border:1px solid #f3f4f6; background:#fff; box-shadow:0 12px 24px rgba(15,23,42,0.08); }
  .notify-head { padding:14px; border-bottom:1px solid #f3f4f6; display:flex; justify-content:space-between; align-items:center; gap:10px; }
  .notify-head h1 { margin:0; font-size:20px; }
  .notify-sub { margin:4px 0 0; color:#6b7280; font-size:13px; }
  .notify-head-actions { display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
  .notify-count { display:inline-flex; align-items:center; gap:6px; padding:6px 10px; border-radius:999px; background:#fff7ed; color:#9a3412; border:1px solid #fdba74; font-size:12px; font-weight:800; }
  .notify-list { list-style:none; margin:0; padding:0; }
  .notify-item { padding:12px 14px; border-top:1px solid #f3f4f6; }
  .notify-item:hover { background:#fffbeb; }
  .notify-item.is-highlight { background:#fff7ed; border-left:4px solid #f59e0b; }
  .notify-item.is-unread { background:linear-gradient(90deg, rgba(255,247,237,0.95), rgba(255,255,255,0.98)); }
  .notify-main { display:block; text-decoration:none; color:inherit; }
  .notify-top { display:flex; justify-content:space-between; align-items:flex-start; gap:10px; }
  .notify-title { margin:0; font-size:15px; font-weight:800; color:#92400e; }
  .notify-title-wrap { display:flex; align-items:center; gap:8px; min-width:0; }
  .notify-dot { width:10px; height:10px; border-radius:999px; background:#f97316; box-shadow:0 0 0 4px rgba(249,115,22,0.15); flex:0 0 auto; }
  .notify-meta { margin-top:4px; color:#4b5563; font-size:13px; display:flex; flex-wrap:wrap; gap:8px; }
  .notify-type { display:inline-flex; align-items:center; border:1px solid #f59e0b; color:#92400e; background:#fff7ed; border-radius:999px; padding:2px 8px; font-size:12px; font-weight:700; }
  .notify-read-state { display:inline-flex; align-items:center; gap:6px; padding:4px 8px; border-radius:999px; border:1px solid #e5e7eb; background:#fff; color:#475569; font-size:12px; font-weight:800; white-space:nowrap; }
  .notify-item.is-unread .notify-read-state { border-color:#fdba74; background:#fff7ed; color:#c2410c; }
  .notify-body { margin-top:10px; color:#475569; font-size:14px; line-height:1.6; }
  .notify-actions { margin-top:10px; display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
  .notify-actions .btn { min-height:34px; }
  .notify-empty { padding:16px 14px; color:#6b7280; }
  @media (max-width: 768px) {
    .notify-head { padding:12px; }
    .notify-item { padding:11px 12px; }
    .notify-top { flex-direction:column; }
    .notify-head-actions { width:100%; justify-content:flex-start; }
  }
</style>

<div class="notify-shell">
  <div class="notify-card">
    <div class="notify-head">
      <div>
        <h1>Thông báo</h1>
        <p class="notify-sub">Trung tâm sự kiện cho nhu cầu, hóa đơn, hợp đồng, phiếu hỗ trợ và vận hành thuê trọ. <?= $unreadCount > 0 ? 'Bạn còn ' . $unreadCount . ' chưa đọc.' : 'Tất cả đã được đọc.' ?></p>
      </div>
      <div class="notify-head-actions">
        <span class="notify-count" id="notifyUnreadCount">
          <span>🔔</span>
          <span><?= $unreadCount ?> chưa đọc</span>
        </span>
        <button type="button" class="btn btn-outline btn-sm" id="notifyMarkAllBtn" <?= $unreadCount > 0 ? '' : 'disabled' ?>>Đánh dấu tất cả đã đọc</button>
      </div>
    </div>

    <?php if (empty($notifications)): ?>
      <div class="notify-empty">Chưa có thông báo nào. Khi có nhu cầu mới, hóa đơn mới, phiếu hoặc thay đổi trạng thái quan trọng, bạn sẽ thấy tại đây.</div>
    <?php else: ?>
      <ul class="notify-list">
        <?php foreach ($notifications as $item): ?>
          <?php
            $notificationId = (int)($item['id'] ?? 0);
            $isHighlight = $highlightNotificationId > 0 && $notificationId === $highlightNotificationId;
            $isUnread = empty($item['is_read']) && empty($item['read_at']);
            $type = trim((string)($item['notification_type'] ?? 'general'));
            $typeLabel = $notificationTypeLabels[$type] ?? ucfirst(str_replace('_', ' ', $type));
            $linkUrl = trim((string)($item['link_url'] ?? ''));
            $mainHref = $linkUrl !== '' ? $linkUrl : routeUrl('notifications', ['notification_id' => $notificationId]);
          ?>
          <li>
            <div class="notify-item <?= $isHighlight ? 'is-highlight' : '' ?> <?= $isUnread ? 'is-unread' : 'is-read' ?>" data-notification-id="<?= $notificationId ?>" data-is-read="<?= $isUnread ? '0' : '1' ?>">
              <div class="notify-top">
                <a class="notify-main" href="<?= htmlspecialchars($mainHref) ?>">
                  <div class="notify-title-wrap">
                    <span class="notify-dot" <?= $isUnread ? '' : 'hidden' ?>></span>
                    <p class="notify-title"><?= htmlspecialchars((string)($item['title'] ?? 'Thông báo hệ thống')) ?></p>
                  </div>
                  <div class="notify-meta">
                    <span>#<?= $notificationId ?></span>
                    <span><?= htmlspecialchars((string)($item['created_at'] ?? '')) ?></span>
                    <span class="notify-type"><?= htmlspecialchars($typeLabel) ?></span>
                  </div>
                </a>
                <span class="notify-read-state"><?= $isUnread ? 'Chưa đọc' : 'Đã đọc' ?></span>
              </div>
              <?php if (!empty($item['body'])): ?>
                <div class="notify-body"><?= nl2br(htmlspecialchars((string)$item['body'])) ?></div>
              <?php endif; ?>
              <div class="notify-actions">
                <?php if ($linkUrl !== ''): ?>
                  <a class="btn btn-outline btn-sm notify-open-link" href="<?= htmlspecialchars($linkUrl) ?>">Mở chi tiết</a>
                <?php endif; ?>
                <button type="button" class="btn btn-outline btn-sm notify-toggle-read"><?= $isUnread ? 'Đánh dấu đã đọc' : 'Đánh dấu chưa đọc' ?></button>
              </div>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
</div>

<?php if ($currentUserId > 0): ?>
<script>
  (() => {
    const unreadBadge = document.getElementById('notifyUnreadCount');
    const markAllBtn = document.getElementById('notifyMarkAllBtn');
    const markRoute = <?= json_encode(routeUrl('notifications-mark'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

    const updateUnreadBadge = (count) => {
      if (!unreadBadge) return;
      unreadBadge.innerHTML = `<span>🔔</span><span>${count} chưa đọc</span>`;
      if (markAllBtn) {
        markAllBtn.disabled = count <= 0;
      }
    };

    const applyItemState = (item, isRead) => {
      if (!item) return;
      item.dataset.isRead = isRead ? '1' : '0';
      item.classList.toggle('is-unread', !isRead);
      item.classList.toggle('is-read', isRead);
      const dot = item.querySelector('.notify-dot');
      if (dot) {
        dot.hidden = isRead;
      }
      const state = item.querySelector('.notify-read-state');
      if (state) {
        state.textContent = isRead ? 'Đã đọc' : 'Chưa đọc';
      }
      const btn = item.querySelector('.notify-toggle-read');
      if (btn) {
        btn.textContent = isRead ? 'Đánh dấu chưa đọc' : 'Đánh dấu đã đọc';
      }
    };

    const postMark = async (payload) => {
      const res = await fetch(markRoute, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify(payload),
      });
      return res.json().catch(() => ({}));
    };

    document.querySelectorAll('.notify-toggle-read').forEach((btn) => {
      btn.addEventListener('click', async () => {
        const item = btn.closest('.notify-item');
        const notificationId = Number(item?.dataset.notificationId || '0');
        if (!item || !notificationId) return;
        btn.disabled = true;
        try {
          const nextIsRead = item.dataset.isRead !== '1';
          const json = await postMark({ notification_id: notificationId, is_read: nextIsRead ? 1 : 0 });
          if (!json.ok) return;
          applyItemState(item, !!json.is_read);
          updateUnreadBadge(Number(json.unread_count || 0));
        } finally {
          btn.disabled = false;
        }
      });
    });

    document.querySelectorAll('.notify-main, .notify-open-link').forEach((link) => {
      link.addEventListener('click', () => {
        const item = link.closest('.notify-item');
        const notificationId = Number(item?.dataset.notificationId || '0');
        if (!item || !notificationId || item.dataset.isRead === '1') return;
        fetch(markRoute, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
          },
          body: JSON.stringify({ notification_id: notificationId, is_read: 1 }),
        }).then((res) => res.json()).then((json) => {
          if (!json.ok) return;
          applyItemState(item, true);
          updateUnreadBadge(Number(json.unread_count || 0));
        }).catch(() => {});
      });
    });

    markAllBtn?.addEventListener('click', async () => {
      markAllBtn.disabled = true;
      let shouldRestoreButton = true;
      try {
        const json = await postMark({ mark_all: 1 });
        if (!json.ok) return;
        document.querySelectorAll('.notify-item').forEach((item) => applyItemState(item, true));
        updateUnreadBadge(Number(json.unread_count || 0));
        shouldRestoreButton = false;
      } finally {
        if (shouldRestoreButton) {
          markAllBtn.disabled = false;
        }
      }
    });
  })();
</script>
<?php endif; ?>
