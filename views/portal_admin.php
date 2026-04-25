<?php
$pageTitle = 'Cổng quản trị';
$activeSection = (string)($activeSection ?? 'dashboard');
$stats = array_merge([
    'users' => 0,
    'rooms' => 0,
    'leads' => 0,
    'payments' => 0,
    'revenue' => 0,
], $stats ?? []);
$opsStats = array_merge([
    'pending_rooms' => 0,
    'pending_seek_posts' => 0,
    'overdue_invoices' => 0,
    'open_issues' => 0,
], $opsStats ?? []);

$modules = [
    ['key' => 'dashboard', 'label' => 'Bảng điều khiển', 'desc' => 'Tổng quan vai trò, vận hành và sức khỏe hệ thống.', 'href' => routeUrl('admin')],
    ['key' => 'users', 'label' => 'Người dùng', 'desc' => 'Quản trị tài khoản, vai trò và trạng thái hoạt động.', 'href' => routeUrl('admin-users')],
    ['key' => 'leads', 'label' => 'Nhu cầu', 'desc' => 'Kiểm soát chất lượng nhu cầu và các trạng thái nghiệp vụ.', 'href' => routeUrl('admin-leads')],
    ['key' => 'transactions', 'label' => 'Giao dịch', 'desc' => 'Theo dõi giao dịch thanh toán và đối soát trạng thái.', 'href' => routeUrl('admin-payments')],
    ['key' => 'reports', 'label' => 'Báo cáo', 'desc' => 'Báo cáo tổng hợp vận hành và chỉ số hiệu suất toàn nền tảng.', 'href' => routeUrl('admin-reports')],
    ['key' => 'settings', 'label' => 'Cài đặt hệ thống', 'desc' => 'Cấu hình hệ thống, giao diện và tham số vận hành.', 'href' => routeUrl('admin-settings')],
    ['key' => 'audit', 'label' => 'Nhật ký kiểm tra', 'desc' => 'Truy vết ai đã làm gì, khi nào và trên đối tượng nào.', 'href' => routeUrl('admin-audit-logs')],
];
?>

<div class="card">
  <div class="card-body">
    <div class="d-flex justify-between align-items-center mb-3 gap-3">
      <div>
        <h1 class="mb-1" style="font-size:24px;">Cổng quản trị</h1>
        <p class="text-muted mb-0">Không gian quản trị tách riêng: người dùng, nhu cầu, giao dịch, báo cáo, cài đặt và nhật ký kiểm tra.</p>
      </div>
      <div class="chip-row mb-0">
        <span class="chip">Người dùng: <?= (int)$stats['users'] ?></span>
        <span class="chip">Phòng: <?= (int)$stats['rooms'] ?></span>
        <span class="chip">Nhu cầu: <?= (int)$stats['leads'] ?></span>
        <span class="chip">Giao dịch: <?= (int)$stats['payments'] ?></span>
        <span class="chip">Doanh thu: <?= number_format((int)$stats['revenue'], 0, ',', '.') ?>đ</span>
        <span class="chip">Phòng chờ duyệt: <?= (int)$opsStats['pending_rooms'] ?></span>
        <span class="chip">Bài tìm chờ duyệt: <?= (int)$opsStats['pending_seek_posts'] ?></span>
        <span class="chip">Hóa đơn quá hạn: <?= (int)$opsStats['overdue_invoices'] ?></span>
        <span class="chip">Sự cố mở: <?= (int)$opsStats['open_issues'] ?></span>
      </div>
    </div>

    <div class="rooms-grid">
      <?php foreach ($modules as $module): ?>
        <?php $isActive = $activeSection === (string)$module['key']; ?>
        <article class="card card-room" style="<?= $isActive ? 'border:2px solid #d97706;' : '' ?>">
          <div class="card-body">
            <div class="d-flex justify-between align-items-center mb-2">
              <a href="<?= htmlspecialchars((string)$module['href']) ?>" style="color:inherit;text-decoration:none;">
                <strong><?= htmlspecialchars((string)$module['label']) ?></strong>
              </a>
            </div>
            <p class="text-muted mb-0"><?= htmlspecialchars((string)$module['desc']) ?></p>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</div>
