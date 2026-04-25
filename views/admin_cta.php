<?php $pageTitle = 'Tin nhắn tư vấn'; ?>
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
    <div class="card">
      <div class="card-body table-responsive">
        <h1 class="mb-2" style="font-size:20px;">Tin nhắn tư vấn (nút kêu gọi hành động)</h1>
        <table class="table align-middle">
          <thead>
            <tr><th>#</th><th>Tên</th><th>SĐT</th><th>Email</th><th>Khu vực</th><th>Nội dung</th><th>Thời gian</th></tr>
          </thead>
          <tbody id="ctaMessagesBody">
            <?php if (empty($messages)): ?>
              <tr><td colspan="7">Chưa có tin nhắn.</td></tr>
            <?php endif; ?>
            <?php foreach ($messages as $m): ?>
              <tr>
                <td><?= (int)$m['id'] ?></td>
                <td><?= htmlspecialchars($m['name']) ?></td>
                <td><?= htmlspecialchars($m['phone']) ?></td>
                <td><?= htmlspecialchars($m['email']) ?></td>
                <td><?= htmlspecialchars($m['province']) ?></td>
                <td style="white-space:pre-wrap;"><?= htmlspecialchars($m['message']) ?></td>
                <td><?= htmlspecialchars($m['created_at']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<script>
  (() => {
    const body = document.getElementById('ctaMessagesBody');
    let lastSignature = '';
    let loading = false;
    const escapeHtml = (str) => String(str ?? '').replace(/[&<>"']/g, (c) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const render = (messages = []) => {
      if (!body) return;
      const signature = messages.map(m => `${m.id}:${m.created_at}`).join('|');
      if (signature === lastSignature) return;
      lastSignature = signature;
      if (!messages.length) {
        body.innerHTML = '<tr><td colspan="7">Chưa có tin nhắn.</td></tr>';
        return;
      }
      body.innerHTML = messages.map(m => `
        <tr>
          <td>${Number(m.id) || 0}</td>
          <td>${escapeHtml(m.name)}</td>
          <td>${escapeHtml(m.phone)}</td>
          <td>${escapeHtml(m.email)}</td>
          <td>${escapeHtml(m.province)}</td>
          <td style="white-space:pre-wrap;">${escapeHtml(m.message)}</td>
          <td>${escapeHtml(m.created_at)}</td>
        </tr>
      `).join('');
    };
    const load = async () => {
      if (loading) return;
      loading = true;
      try {
        const res = await fetch('?route=admin-cta&ajax=1', { cache: 'no-store' });
        const json = await res.json();
        if (json.ok) render(json.messages || []);
      } catch (err) {
        console.error(err);
      } finally {
        loading = false;
      }
    };
    load();
    setInterval(load, 5000);
  })();
</script>

