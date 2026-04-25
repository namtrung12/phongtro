<?php
$pageTitle = 'Cổng người thuê';
$activeSection = (string)($activeSection ?? 'dashboard');
$portalStats = array_merge([
    'linked_rooms' => 0,
    'unpaid_invoices' => 0,
    'open_issues' => 0,
    'notices' => 0,
    'contract_days_left' => null,
], $portalStats ?? []);

$contractLabel = 'Chưa có hợp đồng';
$contractTone = 'is-neutral';
if ($portalStats['contract_days_left'] !== null) {
    $days = (int)$portalStats['contract_days_left'];
    if ($days < 0) {
        $contractLabel = 'Đã hết hạn ' . abs($days) . ' ngày';
        $contractTone = 'is-danger';
    } elseif ($days <= 14) {
        $contractLabel = 'Cần gia hạn trong ' . $days . ' ngày';
        $contractTone = 'is-danger';
    } elseif ($days <= 30) {
        $contractLabel = 'Còn ' . $days . ' ngày';
        $contractTone = 'is-warn';
    } else {
        $contractLabel = 'Còn hiệu lực';
        $contractTone = 'is-ok';
    }
}

$moduleGroups = [
    'rental' => ['title' => 'Kỳ thuê & chỗ ở', 'note' => 'Theo dõi phòng đang ở, hợp đồng và thông tin kỳ thuê.'],
    'finance' => ['title' => 'Tài chính', 'note' => 'Kiểm soát hóa đơn, lịch thanh toán và các khoản còn thiếu.'],
    'support' => ['title' => 'Hỗ trợ & tài khoản', 'note' => 'Gửi yêu cầu sửa chữa, nhận thông báo và quản lý hồ sơ.'],
];

$modules = [
    [
        'key' => 'dashboard',
        'label' => 'Bảng điều khiển',
        'desc' => 'Tổng quan kỳ thuê và các việc cần xử lý gần nhất.',
        'href' => routeUrl('my-stay'),
        'icon' => '📊',
        'group' => 'rental',
        'sections' => ['dashboard'],
    ],
    [
        'key' => 'room',
        'label' => 'Phòng của tôi',
        'desc' => 'Thông tin phòng, chủ trọ, tiền cọc và trạng thái hiện tại.',
        'href' => routeUrl('my-stay', ['section' => 'room']) . '#my-room',
        'icon' => '🏠',
        'group' => 'rental',
        'sections' => ['dashboard', 'room'],
    ],
    [
        'key' => 'contract',
        'label' => 'Hợp đồng của tôi',
        'desc' => 'Theo dõi kỳ hạn hợp đồng và mốc gia hạn.',
        'href' => routeUrl('my-stay', ['section' => 'room']) . '#my-contract',
        'icon' => '📝',
        'group' => 'rental',
        'sections' => ['contract'],
    ],
    [
        'key' => 'invoices',
        'label' => 'Hóa đơn',
        'desc' => 'Xem từng kỳ hóa đơn và số dư cần thanh toán.',
        'href' => routeUrl('my-stay', ['section' => 'invoices']) . '#my-invoices',
        'icon' => '🧾',
        'group' => 'finance',
        'sections' => ['invoices', 'payments'],
    ],
    [
        'key' => 'payments',
        'label' => 'Thanh toán',
        'desc' => 'Theo dõi trạng thái thanh toán theo kỳ.',
        'href' => routeUrl('my-stay', ['section' => 'invoices']) . '#my-invoices',
        'icon' => '💳',
        'group' => 'finance',
        'sections' => ['payments', 'invoices'],
    ],
    [
        'key' => 'maintenance',
        'label' => 'Yêu cầu sửa chữa',
        'desc' => 'Tạo phiếu sự cố và theo dõi phản hồi.',
        'href' => routeUrl('my-stay', ['section' => 'issues']) . '#my-issues',
        'icon' => '🛠',
        'group' => 'support',
        'sections' => ['dashboard', 'issues'],
    ],
    [
        'key' => 'notifications',
        'label' => 'Thông báo',
        'desc' => 'Nhắc hạn, cập nhật vận hành và sự kiện quan trọng.',
        'href' => routeUrl('notifications'),
        'icon' => '🔔',
        'group' => 'support',
        'sections' => ['notifications'],
    ],
    [
        'key' => 'profile',
        'label' => 'Hồ sơ',
        'desc' => 'Cập nhật thông tin cá nhân và tài khoản.',
        'href' => routeUrl('profile'),
        'icon' => '👤',
        'group' => 'support',
        'sections' => ['profile'],
    ],
];

$groupedModules = ['rental' => [], 'finance' => [], 'support' => []];
foreach ($modules as $module) {
    $groupKey = (string)($module['group'] ?? 'support');
    if (!isset($groupedModules[$groupKey])) {
        $groupedModules[$groupKey] = [];
    }
    $groupedModules[$groupKey][] = $module;
}

$quickActionKeys = ['room', 'invoices', 'payments', 'maintenance'];
$quickActions = [];
foreach ($quickActionKeys as $quickKey) {
    foreach ($modules as $module) {
        if (($module['key'] ?? '') === $quickKey) {
            $quickActions[] = $module;
            break;
        }
    }
}

$priorityItems = [];
if ((int)$portalStats['unpaid_invoices'] > 0) {
    $priorityItems[] = [
        'title' => 'Hóa đơn cần xử lý',
        'value' => (int)$portalStats['unpaid_invoices'],
        'desc' => 'Kiểm tra kỳ hóa đơn chưa hoàn tất để tránh trễ hạn.',
        'href' => routeUrl('my-stay', ['section' => 'invoices']) . '#my-invoices',
        'cta' => 'Mở hóa đơn',
        'tone' => 'is-warn',
    ];
}
if ((int)$portalStats['open_issues'] > 0) {
    $priorityItems[] = [
        'title' => 'Yêu cầu sửa chữa đang mở',
        'value' => (int)$portalStats['open_issues'],
        'desc' => 'Theo dõi tiến độ xử lý hoặc cập nhật thêm mô tả.',
        'href' => routeUrl('my-stay', ['section' => 'issues']) . '#my-issues',
        'cta' => 'Xem sự cố',
        'tone' => 'is-alert',
    ];
}
if ((int)$portalStats['notices'] > 0) {
    $priorityItems[] = [
        'title' => 'Thông báo mới',
        'value' => (int)$portalStats['notices'],
        'desc' => 'Có cập nhật vận hành hoặc nhắc hạn quan trọng.',
        'href' => routeUrl('notifications'),
        'cta' => 'Đọc thông báo',
        'tone' => 'is-info',
    ];
}
if ($contractTone === 'is-danger' || $contractTone === 'is-warn') {
    $priorityItems[] = [
        'title' => 'Trạng thái hợp đồng',
        'value' => $contractLabel,
        'desc' => 'Kiểm tra điều khoản gia hạn để chủ động kế hoạch ở.',
        'href' => routeUrl('my-stay', ['section' => 'room']) . '#my-contract',
        'cta' => 'Xem hợp đồng',
        'tone' => $contractTone === 'is-danger' ? 'is-alert' : 'is-warn',
    ];
}
?>

<style>
  .tenant-portal-shell {
      display: flex;
      flex-direction: column;
      gap: 18px;
  }
  .tenant-hero {
      display: grid;
      grid-template-columns: minmax(320px, 1.4fr) minmax(260px, 1fr);
      gap: 14px;
      border-radius: 18px;
      padding: 18px;
      border: 1px solid #f3cc79;
      background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 40%, #ffffff 100%);
      box-shadow: 0 18px 36px rgba(217, 119, 6, 0.12);
  }
  .tenant-hero-eyebrow {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 6px 10px;
      border-radius: 999px;
      font-size: 12px;
      font-weight: 800;
      color: #7c2d12;
      border: 1px solid rgba(217, 119, 6, 0.26);
      background: rgba(255, 255, 255, 0.78);
      margin-bottom: 8px;
  }
  .tenant-hero h1 {
      margin: 0 0 8px;
      font-size: 30px;
      line-height: 1.15;
      letter-spacing: -0.4px;
      color: #7c2d12;
  }
  .tenant-hero p {
      margin: 0 0 12px;
      color: #6b7280;
      max-width: 62ch;
      line-height: 1.5;
  }
  .tenant-contract-pill {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 9px 12px;
      border-radius: 999px;
      border: 1px solid #fed7aa;
      background: #fff;
      color: #7c2d12;
      font-weight: 700;
      font-size: 13px;
  }
  .tenant-contract-pill b { font-size: 13px; }
  .tenant-contract-pill.is-ok {
      border-color: rgba(34, 197, 94, 0.32);
      color: #166534;
      background: #f0fdf4;
  }
  .tenant-contract-pill.is-warn {
      border-color: rgba(234, 88, 12, 0.35);
      color: #9a3412;
      background: #fff7ed;
  }
  .tenant-contract-pill.is-danger {
      border-color: rgba(239, 68, 68, 0.36);
      color: #b91c1c;
      background: #fef2f2;
  }
  .tenant-kpi-grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 10px;
      align-content: start;
  }
  .tenant-kpi {
      border-radius: 14px;
      border: 1px solid #f3e2c7;
      background: #fff;
      padding: 12px;
  }
  .tenant-kpi-value {
      font-size: 24px;
      font-weight: 800;
      line-height: 1.1;
      color: #111827;
      margin-bottom: 4px;
  }
  .tenant-kpi-label {
      margin: 0;
      font-size: 12px;
      color: #6b7280;
      line-height: 1.3;
  }
  .tenant-priority-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
      gap: 10px;
  }
  .tenant-priority-card {
      border-radius: 14px;
      border: 1px solid #f3e2c7;
      background: #fff;
      padding: 12px;
      display: flex;
      flex-direction: column;
      gap: 8px;
      min-height: 132px;
  }
  .tenant-priority-card.is-warn {
      border-color: rgba(234, 88, 12, 0.35);
      background: linear-gradient(180deg, #fffaf0, #ffffff);
  }
  .tenant-priority-card.is-alert {
      border-color: rgba(239, 68, 68, 0.34);
      background: linear-gradient(180deg, #fff5f5, #ffffff);
  }
  .tenant-priority-card.is-info {
      border-color: rgba(59, 130, 246, 0.28);
      background: linear-gradient(180deg, #eff6ff, #ffffff);
  }
  .tenant-priority-head {
      display: flex;
      align-items: baseline;
      justify-content: space-between;
      gap: 8px;
  }
  .tenant-priority-title {
      margin: 0;
      font-size: 14px;
      font-weight: 800;
      color: #111827;
  }
  .tenant-priority-value {
      font-size: 20px;
      font-weight: 800;
      color: #7c2d12;
      text-align: right;
      line-height: 1.1;
  }
  .tenant-priority-card.is-alert .tenant-priority-value { color: #b91c1c; }
  .tenant-priority-card.is-info .tenant-priority-value { color: #1d4ed8; }
  .tenant-priority-desc {
      margin: 0;
      color: #6b7280;
      font-size: 13px;
      line-height: 1.45;
      flex: 1 1 auto;
  }
  .tenant-quick-grid {
      display: grid;
      grid-template-columns: repeat(4, minmax(0, 1fr));
      gap: 10px;
  }
  .tenant-quick-card {
      border-radius: 14px;
      border: 1px solid #f3e2c7;
      background: #fff;
      padding: 12px;
      text-decoration: none;
      color: inherit;
      transition: transform .16s ease, box-shadow .16s ease, border-color .16s ease;
      min-height: 120px;
      display: flex;
      flex-direction: column;
      gap: 6px;
  }
  .tenant-quick-card:hover {
      transform: translateY(-2px);
      border-color: #f59e0b;
      box-shadow: 0 14px 28px rgba(217, 119, 6, 0.13);
  }
  .tenant-quick-icon {
      width: 34px;
      height: 34px;
      border-radius: 10px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      background: #fff7ed;
      color: #7c2d12;
      font-size: 18px;
      border: 1px solid rgba(245, 158, 11, 0.22);
      margin-bottom: 4px;
  }
  .tenant-quick-title {
      margin: 0;
      font-size: 15px;
      font-weight: 800;
      color: #111827;
      line-height: 1.25;
  }
  .tenant-quick-desc {
      margin: 0;
      color: #6b7280;
      font-size: 12px;
      line-height: 1.4;
  }
  .tenant-group-grid {
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 10px;
  }
  .tenant-group-card {
      border-radius: 14px;
      border: 1px solid #f3e2c7;
      background: #fff;
      padding: 12px;
      display: flex;
      flex-direction: column;
      gap: 10px;
  }
  .tenant-group-card h2 {
      margin: 0;
      font-size: 16px;
      color: #111827;
      letter-spacing: -0.2px;
  }
  .tenant-group-note {
      margin: 0;
      font-size: 12px;
      color: #6b7280;
      line-height: 1.4;
  }
  .tenant-module-list {
      display: flex;
      flex-direction: column;
      gap: 7px;
  }
  .tenant-module-link {
      border-radius: 12px;
      border: 1px solid #edf0f6;
      background: #f9fafb;
      padding: 9px 10px;
      text-decoration: none;
      display: flex;
      gap: 8px;
      align-items: flex-start;
      transition: border-color .15s ease, background .15s ease, box-shadow .15s ease;
  }
  .tenant-module-link:hover {
      border-color: #f59e0b;
      background: #fff7ed;
  }
  .tenant-module-icon {
      width: 26px;
      height: 26px;
      border-radius: 8px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      background: #fff;
      border: 1px solid #fde68a;
      flex: 0 0 auto;
  }
  .tenant-module-content {
      min-width: 0;
      flex: 1 1 auto;
  }
  .tenant-module-name {
      margin: 0 0 2px;
      font-size: 13px;
      font-weight: 800;
      color: #111827;
      line-height: 1.3;
  }
  .tenant-module-desc {
      margin: 0;
      font-size: 12px;
      color: #6b7280;
      line-height: 1.35;
  }
  .tenant-module-arrow {
      color: #9ca3af;
      flex: 0 0 auto;
      padding-top: 2px;
  }
  @media (max-width: 1180px) {
      .tenant-quick-grid {
          grid-template-columns: repeat(2, minmax(0, 1fr));
      }
      .tenant-group-grid {
          grid-template-columns: 1fr;
      }
  }
  @media (max-width: 860px) {
      .tenant-hero {
          grid-template-columns: 1fr;
      }
      .tenant-kpi-grid {
          grid-template-columns: repeat(2, minmax(0, 1fr));
      }
  }
  @media (max-width: 560px) {
      .tenant-hero {
          padding: 14px;
          border-radius: 14px;
      }
      .tenant-hero h1 {
          font-size: 24px;
      }
      .tenant-kpi-grid,
      .tenant-quick-grid {
          grid-template-columns: 1fr;
      }
      .tenant-priority-grid {
          grid-template-columns: 1fr;
      }
  }
</style>

<div class="tenant-portal-shell">
  <section class="tenant-hero">
    <div>
      <span class="tenant-hero-eyebrow">🏡 Cổng người thuê</span>
      <h1>Quản lý kỳ thuê rõ ràng, thao tác nhanh</h1>
      <p>Xem ngay các việc cần xử lý, truy cập nhanh hóa đơn và hỗ trợ sửa chữa mà không phải dò nhiều màn hình.</p>
      <span class="tenant-contract-pill <?= htmlspecialchars($contractTone) ?>">
        <span>Hợp đồng</span>
        <b><?= htmlspecialchars($contractLabel) ?></b>
      </span>
    </div>
    <div class="tenant-kpi-grid">
      <article class="tenant-kpi">
        <div class="tenant-kpi-value"><?= (int)$portalStats['linked_rooms'] ?></div>
        <p class="tenant-kpi-label">Phòng liên kết</p>
      </article>
      <article class="tenant-kpi">
        <div class="tenant-kpi-value"><?= (int)$portalStats['unpaid_invoices'] ?></div>
        <p class="tenant-kpi-label">Hóa đơn cần xử lý</p>
      </article>
      <article class="tenant-kpi">
        <div class="tenant-kpi-value"><?= (int)$portalStats['open_issues'] ?></div>
        <p class="tenant-kpi-label">Sự cố đang mở</p>
      </article>
      <article class="tenant-kpi">
        <div class="tenant-kpi-value"><?= (int)$portalStats['notices'] ?></div>
        <p class="tenant-kpi-label">Thông báo mới</p>
      </article>
    </div>
  </section>

  <?php if (!empty($priorityItems)): ?>
    <section class="tenant-priority-grid">
      <?php foreach ($priorityItems as $item): ?>
        <article class="tenant-priority-card <?= htmlspecialchars((string)($item['tone'] ?? '')) ?>">
          <div class="tenant-priority-head">
            <h2 class="tenant-priority-title"><?= htmlspecialchars((string)$item['title']) ?></h2>
            <span class="tenant-priority-value"><?= htmlspecialchars((string)$item['value']) ?></span>
          </div>
          <p class="tenant-priority-desc"><?= htmlspecialchars((string)$item['desc']) ?></p>
          <a class="btn btn-outline btn-sm" href="<?= htmlspecialchars((string)$item['href']) ?>"><?= htmlspecialchars((string)$item['cta']) ?></a>
        </article>
      <?php endforeach; ?>
    </section>
  <?php endif; ?>

  <section class="tenant-quick-grid">
    <?php foreach ($quickActions as $quick): ?>
      <a class="tenant-quick-card" href="<?= htmlspecialchars((string)$quick['href']) ?>">
        <span class="tenant-quick-icon"><?= htmlspecialchars((string)$quick['icon']) ?></span>
        <h2 class="tenant-quick-title"><?= htmlspecialchars((string)$quick['label']) ?></h2>
        <p class="tenant-quick-desc"><?= htmlspecialchars((string)$quick['desc']) ?></p>
      </a>
    <?php endforeach; ?>
  </section>

  <section class="tenant-group-grid">
    <?php foreach ($moduleGroups as $groupKey => $groupMeta): ?>
      <article class="tenant-group-card">
        <h2><?= htmlspecialchars((string)$groupMeta['title']) ?></h2>
        <p class="tenant-group-note"><?= htmlspecialchars((string)$groupMeta['note']) ?></p>
        <div class="tenant-module-list">
          <?php foreach ($groupedModules[$groupKey] as $module): ?>
            <a class="tenant-module-link" href="<?= htmlspecialchars((string)$module['href']) ?>">
              <span class="tenant-module-icon"><?= htmlspecialchars((string)$module['icon']) ?></span>
              <span class="tenant-module-content">
                <p class="tenant-module-name"><?= htmlspecialchars((string)$module['label']) ?></p>
                <p class="tenant-module-desc"><?= htmlspecialchars((string)$module['desc']) ?></p>
              </span>
              <span class="tenant-module-arrow">›</span>
            </a>
          <?php endforeach; ?>
        </div>
      </article>
    <?php endforeach; ?>
  </section>
</div>
