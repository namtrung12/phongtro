<?php
$pageTitle = 'Cổng chủ trọ';
$activeSection = (string)($activeSection ?? 'dashboard');
$portalStats = array_merge([
    'rooms' => 0,
    'leads' => 0,
    'open_issues' => 0,
    'unpaid_invoices' => 0,
    'paid_payments' => 0,
    'revenue_month' => 0,
], $portalStats ?? []);
$permissions = array_merge([
    'lead_view' => true,
    'room_manage' => true,
    'invoice_manage' => true,
], $permissions ?? []);

$modules = [
    ['key' => 'dashboard', 'label' => 'Bảng điều khiển', 'desc' => 'Tổng quan vận hành phòng, nhu cầu và dòng tiền theo tháng.', 'href' => routeUrl('dashboard'), 'icon' => '📊'],
    ['key' => 'leads', 'label' => 'Nhu cầu', 'desc' => 'Luồng nhu cầu từ lúc mới mua tới khi chốt hoặc thất bại.', 'href' => routeUrl('dashboard', ['tab' => 'lead']) . '#lead', 'enabled' => (bool)$permissions['lead_view'], 'icon' => '🎯'],
    ['key' => 'rooms', 'label' => 'Phòng trọ', 'desc' => 'Danh sách phòng, trạng thái khai thác và hồ sơ vận hành.', 'href' => routeUrl('my-rooms'), 'icon' => '🏘'],
    ['key' => 'tenants', 'label' => 'Khách thuê', 'desc' => 'Theo dõi người thuê theo từng phòng và kỳ thuê.', 'href' => routeUrl('my-rooms', ['focus' => 'tenants']), 'icon' => '👥'],
    ['key' => 'contracts', 'label' => 'Hợp đồng', 'desc' => 'Theo dõi kỳ hạn hợp đồng, gia hạn và chuyển trạng thái.', 'href' => routeUrl('my-rooms', ['focus' => 'contracts']), 'icon' => '📝'],
    ['key' => 'invoices', 'label' => 'Hóa đơn', 'desc' => 'Quản lý các kỳ thu tiền thuê, điện, nước và phụ phí.', 'href' => routeUrl('my-rooms', ['focus' => 'invoices']), 'enabled' => (bool)$permissions['invoice_manage'], 'icon' => '🧾'],
    ['key' => 'payments', 'label' => 'Thanh toán', 'desc' => 'Lịch sử giao dịch và trạng thái thanh toán theo phòng.', 'href' => routeUrl('payment-history'), 'icon' => '💳'],
    ['key' => 'incidents', 'label' => 'Sự cố', 'desc' => 'Danh sách phiếu, mức độ ưu tiên và tiến độ xử lý.', 'href' => routeUrl('my-rooms', ['focus' => 'issues']), 'icon' => '🛠'],
    ['key' => 'reports', 'label' => 'Báo cáo', 'desc' => 'Chỉ số lấp đầy, tỷ lệ chốt nhu cầu và doanh thu vận hành.', 'href' => routeUrl('dashboard'), 'icon' => '📈'],
];

foreach ($modules as &$module) {
    if (!isset($module['enabled'])) {
        $module['enabled'] = true;
    }
}
unset($module);

$visibleModules = array_values(array_filter($modules, static function (array $module): bool {
    return !empty($module['enabled']);
}));

$primaryModuleKeys = ['dashboard', 'rooms', 'leads', 'payments'];
$primaryModules = [];
$secondaryModules = [];
foreach ($visibleModules as $module) {
    if (in_array((string)$module['key'], $primaryModuleKeys, true)) {
        $primaryModules[] = $module;
    } else {
        $secondaryModules[] = $module;
    }
}
?>

<style>
  .landlord-portal-shell {
      display: flex;
      flex-direction: column;
      gap: 16px;
  }
  .landlord-hero {
      display: grid;
      grid-template-columns: minmax(340px, 1.45fr) minmax(260px, 1fr);
      gap: 14px;
      border-radius: 18px;
      padding: 18px;
      border: 1px solid #f3cc79;
      background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 44%, #ffffff 100%);
      box-shadow: 0 16px 34px rgba(217, 119, 6, 0.12);
  }
  .landlord-eyebrow {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 6px 10px;
      border-radius: 999px;
      font-size: 12px;
      font-weight: 800;
      color: #7c2d12;
      border: 1px solid rgba(217, 119, 6, 0.25);
      background: rgba(255, 255, 255, 0.82);
      margin-bottom: 8px;
  }
  .landlord-hero h1 {
      margin: 0 0 8px;
      font-size: 30px;
      line-height: 1.12;
      letter-spacing: -0.4px;
      color: #7c2d12;
  }
  .landlord-hero p {
      margin: 0;
      max-width: 62ch;
      color: #6b7280;
      line-height: 1.5;
  }
  .landlord-hero-meta {
      margin-top: 10px;
      font-size: 13px;
      color: #7c2d12;
      font-weight: 700;
  }
  .landlord-kpi-grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 10px;
      align-content: start;
  }
  .landlord-kpi {
      border-radius: 14px;
      border: 1px solid #f3e2c7;
      background: #fff;
      padding: 11px;
  }
  .landlord-kpi-label {
      margin: 0 0 6px;
      font-size: 12px;
      color: #6b7280;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.04em;
  }
  .landlord-kpi-value {
      margin: 0;
      font-size: 24px;
      line-height: 1.1;
      font-weight: 900;
      color: #7c2d12;
  }
  .landlord-section-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 10px;
      margin-bottom: 10px;
  }
  .landlord-section-head h2 {
      margin: 0;
      font-size: 20px;
      color: #111827;
  }
  .landlord-section-note {
      margin: 0;
      font-size: 13px;
      color: #6b7280;
      font-weight: 600;
  }
  .landlord-main-grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 12px;
  }
  .landlord-module-card {
      border-radius: 14px;
      border: 1px solid #f3e2c7;
      background: #fff;
      transition: border-color .15s ease, box-shadow .15s ease, transform .15s ease;
  }
  .landlord-module-card:hover,
  .landlord-module-card:focus-within {
      border-color: #f59e0b;
      box-shadow: 0 12px 26px rgba(217, 119, 6, 0.14);
      transform: translateY(-1px);
  }
  .landlord-module-link {
      display: flex;
      flex-direction: column;
      gap: 8px;
      padding: 14px;
      color: inherit;
      text-decoration: none;
  }
  .landlord-module-top {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 8px;
  }
  .landlord-module-title {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      font-size: 18px;
      font-weight: 800;
      color: #111827;
      line-height: 1.2;
  }
  .landlord-module-arrow {
      color: #9ca3af;
      font-size: 16px;
      line-height: 1;
      font-weight: 900;
  }
  .landlord-module-desc {
      margin: 0;
      font-size: 14px;
      line-height: 1.45;
      color: #6b7280;
  }
  .landlord-more-modules {
      border-radius: 14px;
      border: 1px dashed #f3cc79;
      background: #fffdf7;
      padding: 10px 12px;
  }
  .landlord-more-modules summary {
      list-style: none;
      cursor: pointer;
      font-weight: 800;
      color: #7c2d12;
      user-select: none;
      display: inline-flex;
      align-items: center;
      gap: 8px;
  }
  .landlord-more-modules summary::-webkit-details-marker { display: none; }
  .landlord-more-modules summary::before {
      content: "▸";
      font-size: 12px;
      line-height: 1;
      transition: transform .15s ease;
  }
  .landlord-more-modules[open] summary::before {
      transform: rotate(90deg);
  }
  .landlord-secondary-grid {
      margin-top: 10px;
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 10px;
  }
  .landlord-secondary-item {
      display: block;
      text-decoration: none;
      color: inherit;
      padding: 10px 12px;
      border-radius: 12px;
      border: 1px solid #f3e2c7;
      background: #fff;
      transition: border-color .15s ease, box-shadow .15s ease;
  }
  .landlord-secondary-item:hover,
  .landlord-secondary-item:focus-visible {
      border-color: #f59e0b;
      box-shadow: 0 8px 18px rgba(217, 119, 6, 0.12);
  }
  .landlord-secondary-title {
      display: inline-flex;
      align-items: center;
      gap: 7px;
      font-weight: 800;
      color: #111827;
      margin-bottom: 4px;
  }
  .landlord-secondary-note {
      margin: 0;
      font-size: 13px;
      color: #6b7280;
      line-height: 1.35;
  }
  @media (max-width: 992px) {
      .landlord-hero {
          grid-template-columns: 1fr;
      }
      .landlord-main-grid,
      .landlord-secondary-grid {
          grid-template-columns: 1fr;
      }
  }
  @media (max-width: 768px) {
      .landlord-hero {
          border-radius: 14px;
          padding: 14px;
          gap: 12px;
      }
      .landlord-hero h1 {
          font-size: 24px;
      }
      .landlord-module-title {
          font-size: 16px;
      }
      .landlord-kpi-value {
          font-size: 21px;
      }
      .landlord-kpi-grid {
          grid-template-columns: 1fr 1fr;
      }
  }
</style>

<div class="landlord-portal-shell">
  <section class="landlord-hero">
    <div>
      <span class="landlord-eyebrow">Không gian vận hành</span>
      <h1>Cổng chủ trọ</h1>
      <p>Giữ các thao tác quan trọng ở tuyến đầu để vào việc nhanh hơn, phần còn lại gom vào mục nâng cao để đỡ rối.</p>
      <p class="landlord-hero-meta">
        Thanh toán thành công: <?= (int)$portalStats['paid_payments'] ?>
        · Doanh thu tháng: <?= number_format((int)$portalStats['revenue_month'], 0, ',', '.') ?>đ
      </p>
    </div>
    <div class="landlord-kpi-grid">
      <article class="landlord-kpi">
        <p class="landlord-kpi-label">Phòng đang quản lý</p>
        <p class="landlord-kpi-value"><?= (int)$portalStats['rooms'] ?></p>
      </article>
      <article class="landlord-kpi">
        <p class="landlord-kpi-label">Nhu cầu</p>
        <p class="landlord-kpi-value"><?= (int)$portalStats['leads'] ?></p>
      </article>
      <article class="landlord-kpi">
        <p class="landlord-kpi-label">Hóa đơn tồn</p>
        <p class="landlord-kpi-value"><?= (int)$portalStats['unpaid_invoices'] ?></p>
      </article>
      <article class="landlord-kpi">
        <p class="landlord-kpi-label">Sự cố mở</p>
        <p class="landlord-kpi-value"><?= (int)$portalStats['open_issues'] ?></p>
      </article>
    </div>
  </section>

  <section>
    <div class="landlord-section-head">
      <h2>Lối tắt chính</h2>
      <p class="landlord-section-note">Hiển thị trước các mục dùng nhiều nhất trong vận hành hằng ngày.</p>
    </div>
    <div class="landlord-main-grid">
      <?php foreach ($primaryModules as $module): ?>
        <?php $isCurrent = $activeSection === (string)$module['key']; ?>
        <article class="landlord-module-card">
          <a class="landlord-module-link" href="<?= htmlspecialchars((string)$module['href']) ?>" <?= $isCurrent ? 'aria-current="page"' : '' ?>>
            <div class="landlord-module-top">
              <span class="landlord-module-title">
                <span><?= htmlspecialchars((string)($module['icon'] ?? '•')) ?></span>
                <span><?= htmlspecialchars((string)$module['label']) ?></span>
              </span>
              <span class="landlord-module-arrow">›</span>
            </div>
            <p class="landlord-module-desc"><?= htmlspecialchars((string)$module['desc']) ?></p>
          </a>
        </article>
      <?php endforeach; ?>
    </div>
  </section>

  <?php if (!empty($secondaryModules)): ?>
    <details class="landlord-more-modules">
      <summary>Nâng cao (<?= count($secondaryModules) ?> mục)</summary>
      <div class="landlord-secondary-grid">
        <?php foreach ($secondaryModules as $module): ?>
          <a class="landlord-secondary-item" href="<?= htmlspecialchars((string)$module['href']) ?>">
            <div class="landlord-secondary-title">
              <span><?= htmlspecialchars((string)($module['icon'] ?? '•')) ?></span>
              <span><?= htmlspecialchars((string)$module['label']) ?></span>
            </div>
            <p class="landlord-secondary-note"><?= htmlspecialchars((string)$module['desc']) ?></p>
          </a>
        <?php endforeach; ?>
      </div>
    </details>
  <?php endif; ?>
</div>

