<?php
$pageTitle = 'Trang quản trị';

$roleStats = array_merge([
    'tenant' => 0,
    'landlord' => 0,
    'staff' => 0,
    'admin' => 0,
], $roleStats ?? []);

$opsStats = array_merge([
    'vacant_rooms' => 0,
    'occupied_rooms' => 0,
    'reserved_rooms' => 0,
    'maintenance_rooms' => 0,
    'expiring_contracts' => 0,
    'active_stays' => 0,
    'unpaid_invoices' => 0,
    'due_soon_invoices' => 0,
    'overdue_invoices' => 0,
    'open_issues' => 0,
    'pending_rooms' => 0,
    'pending_seek_posts' => 0,
], $opsStats ?? []);

$roleCards = [
    [
        'label' => 'Quản trị',
        'value' => (int)$roleStats['admin'],
        'note' => 'Điều phối hệ thống.',
    ],
    [
        'label' => 'Chủ trọ',
        'value' => (int)$roleStats['landlord'],
        'note' => 'Nhóm vận hành chính.',
    ],
    [
        'label' => 'Nhân sự',
        'value' => (int)$roleStats['staff'],
        'note' => 'Hỗ trợ theo phạm vi.',
    ],
    [
        'label' => 'Người thuê',
        'value' => (int)$roleStats['tenant'],
        'note' => 'Nhóm sử dụng dịch vụ.',
    ],
];

$lifecycleCards = [
    ['label' => 'Phòng chờ duyệt', 'value' => (int)$opsStats['pending_rooms'], 'note' => 'Tin mới cần duyệt.'],
    ['label' => 'Đang có người thuê', 'value' => (int)$opsStats['occupied_rooms'], 'note' => 'Phòng đang vận hành.'],
    ['label' => 'Hợp đồng sắp hết hạn', 'value' => (int)$opsStats['expiring_contracts'], 'note' => 'Dưới 30 ngày.'],
    ['label' => 'Hoá đơn cần thu', 'value' => (int)$opsStats['unpaid_invoices'], 'note' => 'Bao gồm gần hạn và quá hạn.'],
    ['label' => 'Hoá đơn quá hạn', 'value' => (int)$opsStats['overdue_invoices'], 'note' => 'Cần theo dõi gấp.'],
    ['label' => 'Sự cố mở', 'value' => (int)$opsStats['open_issues'], 'note' => 'Chưa xử lý xong.'],
];

$actionCards = [
    ['href' => '?route=admin-rooms&status=pending', 'title' => 'Duyệt phòng', 'desc' => 'Ưu tiên xử lý các tin mới trước khi hiển thị ra ngoài.', 'badge' => (int)$opsStats['pending_rooms'] . ' chờ duyệt'],
    ['href' => '?route=admin-users', 'title' => 'Người dùng', 'desc' => 'Kiểm tra rõ 3 vai trò: quản trị, chủ trọ và người thuê.', 'badge' => (int)$stats['users'] . ' tài khoản'],
    ['href' => '?route=admin-payments', 'title' => 'Thanh toán', 'desc' => 'Theo dõi giao dịch và đối soát doanh thu toàn hệ thống.', 'badge' => number_format((int)$stats['revenue'], 0, ',', '.') . ' đ'],
    ['href' => '?route=admin-seek-posts', 'title' => 'Bài tìm phòng', 'desc' => 'Duyệt các nhu cầu thuê để phía người thuê cũng có vòng đời riêng.', 'badge' => (int)$opsStats['pending_seek_posts'] . ' chờ duyệt'],
];
?>
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
    <style>
      .admin-home-stack { display:flex; flex-direction:column; gap:18px; }
      .admin-home-hero {
        position: relative;
        overflow: hidden;
        border-radius: 16px;
        padding: 14px 16px;
        background:
          radial-gradient(circle at 14% 18%, rgba(255,255,255,0.34), transparent 28%),
          linear-gradient(135deg, #fff7e2 0%, #ffe3ad 48%, #ffc768 100%);
        border: 1px solid rgba(245,158,11,0.26);
        box-shadow: 0 12px 24px rgba(180,83,9,0.12);
      }
      .admin-home-hero-row {
        position: relative;
        z-index: 1;
        display: flex;
        justify-content: space-between;
        gap: 16px;
        align-items: flex-start;
      }
      .admin-home-eyebrow {
        display: inline-flex;
        align-items: center;
        min-height: 32px;
        padding: 7px 12px;
        border-radius: 999px;
        background: rgba(255,255,255,0.74);
        color: #9a3412;
        font-size: 12px;
        font-weight: 800;
      }
      .admin-home-hero h1 {
        margin: 8px 0 6px;
        color: #431407;
        font-size: clamp(22px, 2.3vw, 30px);
        line-height: 1.1;
        letter-spacing: -0.03em;
      }
      .admin-home-hero p {
        margin: 0;
        max-width: 680px;
        color: #7c2d12;
        line-height: 1.45;
        font-size: 14px;
      }
      .admin-home-hero-actions {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        justify-content: flex-end;
      }
      .admin-home-hero-actions .btn { min-height: 36px; }
      .admin-home-chip-row {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 10px;
      }
      .admin-home-chip {
        display: inline-flex;
        align-items: center;
        min-height: 30px;
        padding: 6px 10px;
        border-radius: 999px;
        background: rgba(255,255,255,0.74);
        color: #92400e;
        font-size: 12px;
        font-weight: 800;
        border: 1px solid rgba(251,191,36,0.28);
      }
      .admin-home-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.15fr) minmax(300px, 0.85fr);
        gap: 12px;
        align-items: start;
      }
      .admin-home-main,
      .admin-home-side {
        display: flex;
        flex-direction: column;
        gap: 12px;
      }
      .admin-home-card {
        padding: 14px;
        border-radius: 14px;
        border: 1px solid rgba(251,191,36,0.18);
        background: linear-gradient(180deg, rgba(255,255,255,0.98), rgba(255,251,241,0.98));
        box-shadow: 0 10px 22px rgba(15,23,42,0.08);
      }
      .admin-home-head {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        align-items: flex-start;
        margin-bottom: 10px;
      }
      .admin-home-head h2 {
        margin: 0;
        color: #111827;
        font-size: 17px;
        line-height: 1.2;
      }
      .admin-home-head p {
        margin: 3px 0 0;
        color: #6b7280;
        font-size: 12px;
        line-height: 1.4;
      }
      .admin-home-role-grid,
      .admin-home-lifecycle-grid,
      .admin-home-action-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 9px;
      }
      .admin-home-role-card,
      .admin-home-lifecycle-card,
      .admin-home-action-card {
        padding: 12px;
        border-radius: 12px;
        border: 1px solid rgba(226,232,240,0.92);
        background: #fff;
      }
      .admin-home-role-label,
      .admin-home-lifecycle-label {
        color: #6b7280;
        font-size: 12px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.04em;
      }
      .admin-home-role-value,
      .admin-home-lifecycle-value {
        margin-top: 6px;
        color: #111827;
        font-size: 28px;
        font-weight: 800;
        line-height: 1;
      }
      .admin-home-role-note,
      .admin-home-lifecycle-note,
      .admin-home-action-desc,
      .admin-home-metric-note {
        margin-top: 6px;
        color: #6b7280;
        font-size: 12px;
        line-height: 1.4;
      }
      .admin-home-role-note,
      .admin-home-lifecycle-note {
        display: none;
      }
      .admin-home-action-card {
        display: block;
        color: inherit;
        transition: border-color .18s ease, transform .18s ease, box-shadow .18s ease;
      }
      .admin-home-action-card:hover {
        border-color: rgba(245,158,11,0.38);
        transform: translateY(-1px);
        box-shadow: 0 18px 28px rgba(245,158,11,0.12);
      }
      .admin-home-action-title {
        color: #111827;
        font-size: 15px;
        font-weight: 800;
      }
      .admin-home-badge {
        display: inline-flex;
        align-items: center;
        min-height: 28px;
        padding: 6px 10px;
        border-radius: 999px;
        background: #fff7ed;
        color: #92400e;
        border: 1px solid rgba(245,158,11,0.24);
        font-size: 12px;
        font-weight: 800;
      }
      .admin-home-metric-list {
        display: grid;
        gap: 10px;
      }
      .admin-home-metric {
        padding: 12px;
        border-radius: 12px;
        border: 1px solid rgba(226,232,240,0.92);
        background: #fff;
      }
      .admin-home-metric-label {
        color: #6b7280;
        font-size: 12px;
        font-weight: 700;
      }
      .admin-home-metric-value {
        margin-top: 4px;
        color: #111827;
        font-size: 22px;
        font-weight: 800;
      }
      @media (max-width: 960px) {
        .admin-home-grid { grid-template-columns: 1fr; }
        .admin-home-hero-row { flex-direction: column; }
        .admin-home-hero-actions { width: 100%; }
        .admin-home-hero-actions .btn { flex: 1 1 calc(50% - 5px); }
      }
    </style>

    <div class="admin-home-stack">
      <section class="admin-home-hero">
        <div class="admin-home-hero-row">
          <div>
            <span class="admin-home-eyebrow">Không gian quản trị</span>
            <h1>Bảng điều khiển quản trị tổng quan</h1>
            <p>Theo dõi nhanh tài khoản, phòng, nhu cầu và điểm nghẽn vận hành cần xử lý trong ngày.</p>
            <div class="admin-home-chip-row">
              <span class="admin-home-chip"><?= (int)$stats['users'] ?> tài khoản</span>
              <span class="admin-home-chip"><?= (int)$stats['rooms'] ?> phòng</span>
              <span class="admin-home-chip"><?= (int)$opsStats['pending_rooms'] ?> phòng chờ duyệt</span>
              <span class="admin-home-chip"><?= (int)$opsStats['unpaid_invoices'] ?> hóa đơn cần thu</span>
              <span class="admin-home-chip"><?= (int)$opsStats['open_issues'] ?> sự cố mở</span>
            </div>
          </div>
          <div class="admin-home-hero-actions">
            <a class="btn btn-primary btn-sm" href="?route=admin-rooms&status=pending">Duyệt phòng mới</a>
            <a class="btn btn-outline btn-sm" href="?route=admin-users">Mở người dùng</a>
          </div>
        </div>
      </section>

      <div class="admin-home-grid">
        <div class="admin-home-main">
          <section class="admin-home-card">
            <div class="admin-home-head">
              <div>
                <h2>Vai trò hệ thống</h2>
                <p>Vai trò không chỉ là cờ `role` trong cơ sở dữ liệu mà là 3 không gian khác nhau với nhiệm vụ rõ ràng.</p>
              </div>
            </div>
            <div class="admin-home-role-grid">
              <?php foreach ($roleCards as $roleCard): ?>
                <article class="admin-home-role-card">
                  <div class="admin-home-role-label"><?= htmlspecialchars((string)$roleCard['label']) ?></div>
                  <div class="admin-home-role-value"><?= (int)$roleCard['value'] ?></div>
                  <div class="admin-home-role-note"><?= htmlspecialchars((string)$roleCard['note']) ?></div>
                </article>
              <?php endforeach; ?>
            </div>
          </section>

          <section class="admin-home-card">
            <div class="admin-home-head">
              <div>
                <h2>Chu trình thuê trọ</h2>
                <p>Các điểm trong vòng đời thuê đang nằm ở đâu để biết hệ thống có “thật” hay chỉ dừng ở đăng tin.</p>
              </div>
            </div>
            <div class="admin-home-lifecycle-grid">
              <?php foreach ($lifecycleCards as $card): ?>
                <article class="admin-home-lifecycle-card">
                  <div class="admin-home-lifecycle-label"><?= htmlspecialchars((string)$card['label']) ?></div>
                  <div class="admin-home-lifecycle-value"><?= (int)$card['value'] ?></div>
                  <div class="admin-home-lifecycle-note"><?= htmlspecialchars((string)$card['note']) ?></div>
                </article>
              <?php endforeach; ?>
            </div>
          </section>

          <section class="admin-home-card">
            <div class="admin-home-head">
              <div>
                <h2>Điều phối nhanh</h2>
                <p>Những điểm vào chính để quản trị giữ hệ thống đi đúng luồng theo ngữ cảnh từng vai trò.</p>
              </div>
            </div>
            <div class="admin-home-action-grid">
              <?php foreach ($actionCards as $actionCard): ?>
                <a class="admin-home-action-card" href="<?= htmlspecialchars((string)$actionCard['href']) ?>">
                  <div class="admin-home-head" style="margin-bottom:0;">
                    <div>
                      <div class="admin-home-action-title"><?= htmlspecialchars((string)$actionCard['title']) ?></div>
                      <div class="admin-home-action-desc"><?= htmlspecialchars((string)$actionCard['desc']) ?></div>
                    </div>
                    <span class="admin-home-badge"><?= htmlspecialchars((string)$actionCard['badge']) ?></span>
                  </div>
                </a>
              <?php endforeach; ?>
            </div>
          </section>
        </div>

        <aside class="admin-home-side">
          <section class="admin-home-card">
            <div class="admin-home-head">
              <div>
                <h2>Sức khỏe vận hành</h2>
                <p>Những con số quản trị nên nhìn mỗi ngày để tránh hệ thống chỉ đẹp ở đầu vào mà yếu ở hậu kiểm.</p>
              </div>
            </div>
            <div class="admin-home-metric-list">
              <article class="admin-home-metric">
                <div class="admin-home-metric-label">Doanh thu đã thu</div>
                <div class="admin-home-metric-value"><?= number_format((int)$stats['revenue'], 0, ',', '.') ?> đ</div>
                <div class="admin-home-metric-note">Tính trên các thanh toán đã xác nhận.</div>
              </article>
              <article class="admin-home-metric">
                <div class="admin-home-metric-label">Gần đến hạn</div>
                <div class="admin-home-metric-value"><?= (int)$opsStats['due_soon_invoices'] ?></div>
                <div class="admin-home-metric-note">Kỳ cần nhắc sớm trước khi chuyển sang quá hạn.</div>
              </article>
              <article class="admin-home-metric">
                <div class="admin-home-metric-label">Sự cố đang mở</div>
                <div class="admin-home-metric-value"><?= (int)$opsStats['open_issues'] ?></div>
                <div class="admin-home-metric-note">Báo sự cố người thuê gửi lên nhưng chưa xử lý xong.</div>
              </article>
              <article class="admin-home-metric">
                <div class="admin-home-metric-label">Phòng bảo trì / giữ chỗ</div>
                <div class="admin-home-metric-value"><?= (int)$opsStats['maintenance_rooms'] + (int)$opsStats['reserved_rooms'] ?></div>
                <div class="admin-home-metric-note">Các phòng chưa vận hành bình thường, cần theo dõi riêng.</div>
              </article>
            </div>
          </section>
        </aside>
      </div>
    </div>
  </div>
</div>

