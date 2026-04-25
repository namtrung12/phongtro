<?php
$pageTitle = 'Không gian thuê trọ của tôi';

$staySpace = $staySpace ?? [];
$room = $staySpace['room'] ?? null;
$profile = $staySpace['operation_profile'] ?? null;
$landlord = $staySpace['landlord'] ?? [];
$invoices = $staySpace['invoices'] ?? [];
$currentInvoice = $staySpace['current_invoice'] ?? null;
$notices = $staySpace['notices'] ?? [];
$issues = $staySpace['issues'] ?? [];
$stayHistory = $staySpace['stay_history'] ?? [];
$activeStay = $staySpace['active_stay'] ?? null;
$meterLogs = $staySpace['meter_logs'] ?? [];
$handoverRecords = $staySpace['handover_records'] ?? [];
$unpaidInvoiceCount = (int)($staySpace['unpaid_invoice_count'] ?? 0);
$contractDaysLeft = $staySpace['contract_days_left'] ?? null;
$linkedRoomCount = (int)($staySpace['linked_room_count'] ?? 0);

$occupancyOptions = roomOperationStatusOptions();
$conditionOptions = roomConditionOptions();
$noticeTypeOptions = $noticeTypeOptions ?? roomNoticeTypeOptions();
$issuePriorityOptions = $issuePriorityOptions ?? tenantIssuePriorityOptions();
$issueStatusOptions = $issueStatusOptions ?? tenantIssueStatusOptions();
$handoverTypeOptions = $handoverTypeOptions ?? roomHandoverTypeOptions();
$invoiceStatusLabels = [
    'draft' => 'Bản nháp',
    'issued' => 'Đã phát hành',
    'paid' => 'Đã thanh toán',
    'unpaid' => 'Chưa thanh toán',
    'partially_paid' => 'Thanh toán một phần',
    'overdue' => 'Quá hạn',
    'cancelled' => 'Đã huỷ',
];
$invoiceReminderLabels = [
    'draft' => 'Bản nháp',
    'issued' => 'Đã phát hành',
    'paid' => 'Đã thanh toán',
    'unpaid' => 'Chưa đến hạn',
    'due_soon' => 'Gần đến hạn',
    'overdue' => 'Quá hạn',
    'cancelled' => 'Đã huỷ',
];
$invoiceStatusClassMap = [
    'draft' => 'cancelled',
    'issued' => 'due_soon',
    'unpaid' => 'unpaid',
    'partially_paid' => 'due_soon',
    'paid' => 'paid',
    'overdue' => 'overdue',
    'cancelled' => 'cancelled',
];
$issueStatusClassMap = [
    'open' => 'overdue',
    'in_progress' => 'unpaid',
    'waiting_parts' => 'due_soon',
    'resolved' => 'paid',
    'closed' => 'cancelled',
];

$occupancyStatus = (string)($profile['occupancy_status'] ?? 'vacant');
$roomCondition = (string)($profile['room_condition'] ?? 'ready');
$occupancyLabel = $occupancyOptions[$occupancyStatus] ?? 'Phòng trống';
$conditionLabel = $conditionOptions[$roomCondition] ?? 'Ổn định';
$contractStart = trim((string)($profile['contract_start'] ?? ''));
$contractEnd = trim((string)($profile['contract_end'] ?? ''));
$monthlyRent = (int)($profile['monthly_rent'] ?? $room['price'] ?? 0);
$depositAmount = (int)($profile['deposit_amount'] ?? 0);
$serviceFee = (int)($profile['service_fee'] ?? 0);
$landlordAvatar = assetUrl((string)($landlord['avatar'] ?? 'avt.jpg'));
$dueSoonInvoiceCount = 0;
$overdueInvoiceCount = 0;
$latestSettlement = null;
foreach ($invoices as $invoiceRow) {
    $reminderState = (string)($invoiceRow['reminder_state'] ?? 'unpaid');
    if ($reminderState === 'due_soon') {
        $dueSoonInvoiceCount++;
    } elseif ($reminderState === 'overdue') {
        $overdueInvoiceCount++;
    }
}
foreach ($stayHistory as $stayRow) {
    if (
        !empty($stayRow['settled_at'])
        || (int)($stayRow['deposit_deduction_amount'] ?? 0) > 0
        || (int)($stayRow['deposit_refund_amount'] ?? 0) > 0
    ) {
        $latestSettlement = $stayRow;
        break;
    }
}

$contractStatusText = 'Chưa có thông tin hợp đồng';
if ($contractDaysLeft !== null) {
    if ($contractDaysLeft < 0) {
        $contractStatusText = 'Hợp đồng đã hết hạn ' . abs((int)$contractDaysLeft) . ' ngày';
    } elseif ($contractDaysLeft <= 30) {
        $contractStatusText = 'Hợp đồng còn ' . (int)$contractDaysLeft . ' ngày';
    } else {
        $contractStatusText = 'Hợp đồng còn hiệu lực';
    }
}
$hasContract = $contractStart !== '' || $contractEnd !== '';
$hasInvoices = !empty($invoices);
$hasMoveOutRecord = false;
foreach ($handoverRecords as $handoverRow) {
    if (($handoverRow['handover_type'] ?? '') === 'move_out') {
        $hasMoveOutRecord = true;
        break;
    }
}
$currentInvoiceStatus = (string)($currentInvoice['display_status'] ?? 'unpaid');
$currentInvoiceReminder = (string)($currentInvoice['reminder_state'] ?? $currentInvoiceStatus);
$stayLifecycle = [
    [
        'label' => 'Đang ở',
        'value' => $room ? ($occupancyStatus === 'occupied' ? 'Phòng đã có người thuê' : $occupancyLabel) : 'Chưa liên kết phòng',
        'state' => $room ? ($occupancyStatus === 'occupied' ? 'active' : 'done') : 'pending',
    ],
    [
        'label' => 'Hợp đồng',
        'value' => $hasContract ? $contractStatusText : 'Chờ chủ trọ cập nhật',
        'state' => !$hasContract ? 'pending' : (($contractDaysLeft !== null && $contractDaysLeft <= 30) ? 'warning' : 'done'),
    ],
    [
        'label' => 'Hóa đơn',
        'value' => $currentInvoice ? ('Kỳ ' . (string)($currentInvoice['billing_month'] ?? '') . ' đang theo dõi') : 'Chưa có hóa đơn tháng',
        'state' => !$currentInvoice ? 'pending' : (($currentInvoiceReminder === 'overdue') ? 'warning' : (($currentInvoiceStatus === 'paid') ? 'done' : 'active')),
    ],
    [
        'label' => 'Thanh toán',
        'value' => !$hasInvoices ? 'Chưa phát sinh' : ($unpaidInvoiceCount > 0 ? ($unpaidInvoiceCount . ' kỳ cần xử lý') : 'Đang đúng hạn'),
        'state' => !$hasInvoices ? 'pending' : ($unpaidInvoiceCount > 0 ? ($overdueInvoiceCount > 0 ? 'warning' : 'active') : 'done'),
    ],
    [
        'label' => 'Trả phòng',
        'value' => $latestSettlement ? 'Đã chốt hoàn cọc' : ($hasMoveOutRecord ? 'Đã có biên bản trả phòng' : 'Chưa phát sinh'),
        'state' => ($latestSettlement || $hasMoveOutRecord) ? 'done' : 'pending',
    ],
];
?>

<style>
  .stay-shell {
    display: flex;
    flex-direction: column;
    gap: 18px;
  }
  .stay-hero {
    position: relative;
    overflow: hidden;
    border-radius: 24px;
    padding: 22px;
    background:
      radial-gradient(circle at 16% 18%, rgba(255,255,255,0.34), transparent 30%),
      linear-gradient(135deg, #fff8e5 0%, #ffe9b5 44%, #ffd585 100%);
    border: 1px solid rgba(245,158,11,0.22);
    box-shadow: 0 24px 44px rgba(180,83,9,0.14);
  }
  .stay-hero::after {
    content: "";
    position: absolute;
    right: -48px;
    bottom: -72px;
    width: 220px;
    height: 220px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(217,119,6,0.12), transparent 64%);
  }
  .stay-hero-row {
    position: relative;
    z-index: 1;
    display: flex;
    justify-content: space-between;
    gap: 18px;
    align-items: flex-start;
  }
  .stay-eyebrow {
    display: inline-flex;
    align-items: center;
    min-height: 32px;
    padding: 7px 12px;
    border-radius: 999px;
    background: rgba(255,255,255,0.72);
    color: #9a3412;
    font-size: 12px;
    font-weight: 800;
  }
  .stay-hero h1 {
    margin: 10px 0 6px;
    color: #431407;
    font-size: clamp(24px, 3vw, 32px);
    line-height: 1.12;
    letter-spacing: -0.03em;
  }
  .stay-hero p {
    margin: 0;
    color: #7c2d12;
    font-size: 14px;
    line-height: 1.6;
    max-width: 760px;
  }
  .stay-hero-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    justify-content: flex-end;
  }
  .stay-hero-actions .btn {
    min-height: 40px;
  }
  .stay-chip-row {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 14px;
  }
  .stay-chip {
    display: inline-flex;
    align-items: center;
    min-height: 30px;
    padding: 6px 10px;
    border-radius: 999px;
    background: rgba(255,255,255,0.72);
    border: 1px solid rgba(251,191,36,0.28);
    color: #92400e;
    font-size: 12px;
    font-weight: 800;
  }
  .stay-vòng đời {
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    gap: 10px;
  }
  .stay-step {
    padding: 14px;
    border-radius: 18px;
    border: 1px solid rgba(226,232,240,0.92);
    background: linear-gradient(180deg, rgba(255,255,255,0.98), rgba(255,250,239,0.98));
    box-shadow: 0 16px 32px rgba(15,23,42,0.06);
  }
  .stay-step-label {
    color: #6b7280;
    font-size: 12px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.04em;
  }
  .stay-step-value {
    margin-top: 8px;
    color: #111827;
    font-size: 15px;
    font-weight: 800;
    line-height: 1.45;
  }
  .stay-step.active {
    border-color: rgba(245,158,11,0.28);
    background: linear-gradient(180deg, #fff9e8, #fff1cd);
  }
  .stay-step.done {
    border-color: rgba(34,197,94,0.24);
    background: linear-gradient(180deg, #f0fdf4, #dcfce7);
  }
  .stay-step.warning {
    border-color: rgba(244,63,94,0.22);
    background: linear-gradient(180deg, #fff7ed, #ffe4e6);
  }
  .stay-step.pending {
    border-style: dashed;
    background: linear-gradient(180deg, #fff, #fff8ec);
  }
  .stay-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.15fr) minmax(300px, 0.85fr);
    gap: 18px;
    align-items: start;
  }
  .stay-main,
  .stay-side {
    display: flex;
    flex-direction: column;
    gap: 18px;
    min-width: 0;
  }
  .stay-card {
    background: linear-gradient(180deg, rgba(255,255,255,0.98), rgba(255,251,241,0.98));
    border: 1px solid rgba(251,191,36,0.18);
    border-radius: 20px;
    box-shadow: 0 18px 36px rgba(15,23,42,0.08);
    padding: 18px;
  }
  .stay-section {
    scroll-margin-top: 96px;
  }
  .stay-card-head {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 12px;
    flex-wrap: wrap;
    margin-bottom: 14px;
  }
  .stay-card-title {
    margin: 0;
    color: #111827;
    font-size: 18px;
    line-height: 1.2;
  }
  .stay-card-note {
    margin: 4px 0 0;
    color: #6b7280;
    font-size: 13px;
    line-height: 1.45;
  }
  .stay-highlight {
    border-radius: 18px;
    padding: 16px;
    background: linear-gradient(135deg, #fbbf24, #d97706);
    color: #431407;
    box-shadow: 0 18px 32px rgba(180,83,9,0.16);
  }
  .stay-highlight strong {
    display: block;
    margin-bottom: 6px;
    font-size: 18px;
  }
  .stay-highlight p {
    margin: 0;
    color: rgba(67,20,7,0.92);
    line-height: 1.55;
  }
  .stay-kpi-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 10px;
  }
  .stay-kpi {
    padding: 14px;
    border-radius: 16px;
    border: 1px solid rgba(251,191,36,0.16);
    background: linear-gradient(180deg, #fffdf8, #fff7e7);
  }
  .stay-kpi-label {
    color: #6b7280;
    font-size: 12px;
    font-weight: 700;
  }
  .stay-kpi-value {
    margin-top: 6px;
    color: #111827;
    font-size: 24px;
    font-weight: 800;
    line-height: 1.1;
  }
  .stay-info-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 10px;
  }
  .stay-info-item {
    padding: 12px 14px;
    border-radius: 16px;
    border: 1px solid rgba(226,232,240,0.92);
    background: #fff;
  }
  .stay-info-item small {
    display: block;
    color: #6b7280;
    margin-bottom: 4px;
  }
  .stay-info-item strong {
    display: block;
    color: #111827;
    font-size: 15px;
    line-height: 1.5;
    word-break: break-word;
  }
  .stay-landlord {
    display: flex;
    gap: 12px;
    align-items: center;
    padding: 12px 14px;
    border-radius: 18px;
    border: 1px solid rgba(226,232,240,0.92);
    background: #fff;
  }
  .stay-landlord-avatar {
    width: 52px;
    height: 52px;
    border-radius: 18px;
    overflow: hidden;
    flex: 0 0 52px;
    border: 1px solid rgba(226,232,240,0.92);
    background: #fffaf0;
  }
  .stay-landlord-avatar img {
    width: 100%;
    height: 100%;
    display: block;
    object-fit: cover;
  }
  .stay-invoice-highlight {
    padding: 16px;
    border-radius: 18px;
    border: 1px solid rgba(251,191,36,0.18);
    background: linear-gradient(180deg, #fffdf8, #fff4d8);
    margin-bottom: 14px;
  }
  .stay-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
  }
  .stay-item {
    border: 1px solid rgba(226,232,240,0.92);
    border-radius: 18px;
    padding: 14px;
    background: #fff;
  }
  .stay-item-top {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 12px;
    flex-wrap: wrap;
  }
  .stay-item-title {
    color: #111827;
    font-size: 15px;
    font-weight: 800;
    line-height: 1.35;
  }
  .stay-badge {
    display: inline-flex;
    align-items: center;
    padding: 6px 10px;
    border-radius: 999px;
    border: 1px solid transparent;
    font-size: 12px;
    font-weight: 800;
  }
  .stay-badge.paid {
    color: #166534;
    background: #dcfce7;
    border-color: rgba(34,197,94,0.18);
  }
  .stay-badge.unpaid {
    color: #92400e;
    background: #fff7ed;
    border-color: rgba(245,158,11,0.18);
  }
  .stay-badge.overdue {
    color: #9f1239;
    background: #fff1f2;
    border-color: rgba(244,63,94,0.18);
  }
  .stay-badge.due_soon {
    color: #9a3412;
    background: #fef3c7;
    border-color: rgba(245,158,11,0.22);
  }
  .stay-badge.cancelled {
    color: #475569;
    background: #f1f5f9;
    border-color: rgba(148,163,184,0.18);
  }
  .stay-inline-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 10px;
    margin-top: 12px;
  }
  .stay-inline-cell {
    padding: 10px 12px;
    border-radius: 14px;
    background: #fffaf0;
    border: 1px solid rgba(251,191,36,0.16);
  }
  .stay-inline-cell small {
    display: block;
    color: #6b7280;
    margin-bottom: 4px;
  }
  .stay-inline-cell strong {
    display: block;
    color: #111827;
    word-break: break-word;
  }
  .stay-copy {
    margin-top: 10px;
    color: #374151;
    font-size: 14px;
    line-height: 1.6;
    white-space: pre-line;
  }
  .stay-badge-row {
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-end;
    gap: 8px;
  }
  .stay-timeline {
    display: flex;
    flex-direction: column;
    gap: 12px;
  }
  .stay-timeline-item {
    position: relative;
    border: 1px solid rgba(226,232,240,0.92);
    border-radius: 18px;
    padding: 14px 14px 14px 18px;
    background: #fff;
  }
  .stay-timeline-item::before {
    content: "";
    position: absolute;
    left: 0;
    top: 14px;
    bottom: 14px;
    width: 4px;
    border-radius: 999px;
    background: linear-gradient(180deg, #f59e0b, #f97316);
  }
  .stay-alert-row {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 12px;
  }
  .stay-alert-chip {
    display: inline-flex;
    align-items: center;
    min-height: 28px;
    padding: 6px 10px;
    border-radius: 999px;
    border: 1px solid rgba(244,63,94,0.18);
    background: #fff1f2;
    color: #9f1239;
    font-size: 12px;
    font-weight: 800;
  }
  .stay-photo-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 10px;
    margin-top: 12px;
  }
  .stay-photo-card {
    display: block;
    overflow: hidden;
    border-radius: 16px;
    border: 1px solid rgba(226,232,240,0.92);
    background: #fffaf0;
    text-decoration: none;
  }
  .stay-photo-card img {
    display: block;
    width: 100%;
    height: 124px;
    object-fit: cover;
  }
  .stay-photo-label {
    display: block;
    padding: 9px 10px;
    color: #374151;
    font-size: 12px;
    font-weight: 800;
    line-height: 1.35;
  }
  .stay-form-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
  }
  .stay-field {
    display: flex;
    flex-direction: column;
    gap: 6px;
  }
  .stay-field.full {
    grid-column: 1 / -1;
  }
  .stay-label {
    color: #374151;
    font-size: 13px;
    font-weight: 800;
  }
  .stay-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
  }
  .stay-proof {
    display: inline-flex;
    margin-top: 10px;
    border-radius: 14px;
    overflow: hidden;
    border: 1px solid rgba(226,232,240,0.92);
    background: #fffaf0;
  }
  .stay-proof img {
    display: block;
    width: 120px;
    height: 120px;
    object-fit: cover;
  }
  .stay-empty {
    padding: 16px;
    border-radius: 18px;
    border: 1px dashed rgba(148,163,184,0.42);
    background: #f8fafc;
    color: #6b7280;
    font-size: 14px;
    line-height: 1.6;
  }
  html.browser-dark .stay-hero {
    background:
      radial-gradient(circle at 16% 18%, rgba(255,255,255,0.08), transparent 30%),
      linear-gradient(135deg, #2b2117 0%, #21180f 44%, #17120d 100%);
    border-color: rgba(245,158,11,0.16);
    box-shadow: 0 24px 42px rgba(0,0,0,0.38);
  }
  html.browser-dark .stay-hero h1,
  html.browser-dark .stay-card-title,
  html.browser-dark .stay-kpi-value,
  html.browser-dark .stay-info-item strong,
  html.browser-dark .stay-item-title,
  html.browser-dark .stay-inline-cell strong {
    color: #fff6e7;
  }
  html.browser-dark .stay-hero p,
  html.browser-dark .stay-eyebrow,
  html.browser-dark .stay-chip,
  html.browser-dark .stay-highlight,
  html.browser-dark .stay-highlight p,
  html.browser-dark .stay-card-note,
  html.browser-dark .stay-kpi-label,
  html.browser-dark .stay-info-item small,
  html.browser-dark .stay-inline-cell small,
  html.browser-dark .stay-copy,
  html.browser-dark .stay-empty {
    color: #cdbb9f;
  }
  html.browser-dark .stay-card,
  html.browser-dark .stay-kpi,
  html.browser-dark .stay-info-item,
  html.browser-dark .stay-landlord,
  html.browser-dark .stay-invoice-highlight,
  html.browser-dark .stay-item,
  html.browser-dark .stay-inline-cell,
  html.browser-dark .stay-proof,
  html.browser-dark .stay-timeline-item,
  html.browser-dark .stay-photo-card {
    background: linear-gradient(180deg, #1d1813, #17120d);
    border-color: #473625;
    box-shadow: 0 16px 32px rgba(0,0,0,0.34);
  }
  html.browser-dark .stay-photo-label,
  html.browser-dark .stay-alert-chip {
    color: #f5d8c3;
  }
  html.browser-dark .stay-landlord-avatar {
    background: linear-gradient(180deg, #1d1813, #17120d);
    border-color: #473625;
  }
  @media (max-width: 960px) {
    .stay-vòng đời {
      grid-template-columns: repeat(2, minmax(0, 1fr));
    }
    .stay-grid {
      grid-template-columns: 1fr;
    }
  }
  @media (max-width: 768px) {
    .stay-hero {
      padding: 16px 14px;
      border-radius: 18px;
    }
    .stay-hero-row {
      flex-direction: column;
      align-items: stretch;
    }
    .stay-hero-actions {
      justify-content: stretch;
    }
    .stay-hero-actions .btn {
      flex: 1 1 auto;
    }
    .stay-card {
      padding: 16px 14px;
      border-radius: 18px;
    }
    .stay-kpi-grid,
    .stay-info-grid,
    .stay-inline-grid,
    .stay-form-grid,
    .stay-photo-grid {
      grid-template-columns: 1fr;
    }
  }
</style>

<div class="stay-shell">
  <section class="stay-hero">
    <div class="stay-hero-row">
      <div style="min-width:0;">
        <span class="stay-eyebrow">Không gian thuê trọ của tôi</span>
        <?php if ($room): ?>
          <h1>#<?= (int)$room['id'] ?> · <?= htmlspecialchars((string)($room['title'] ?? 'Phòng đang thuê')) ?></h1>
          <p><?= htmlspecialchars((string)($room['address'] ?? '')) ?></p>
          <div class="stay-chip-row">
            <span class="stay-chip"><?= htmlspecialchars($occupancyLabel) ?></span>
            <span class="stay-chip"><?= htmlspecialchars($conditionLabel) ?></span>
            <span class="stay-chip"><?= $unpaidInvoiceCount > 0 ? ($unpaidInvoiceCount . ' hóa đơn cần xử lý') : 'Không có hóa đơn tồn' ?></span>
            <?php if ($dueSoonInvoiceCount > 0): ?>
              <span class="stay-chip"><?= $dueSoonInvoiceCount ?> hóa đơn gần đến hạn</span>
            <?php endif; ?>
            <?php if ($overdueInvoiceCount > 0): ?>
              <span class="stay-chip"><?= $overdueInvoiceCount ?> hóa đơn quá hạn</span>
            <?php endif; ?>
            <?php if ($linkedRoomCount > 1): ?>
              <span class="stay-chip">Có <?= $linkedRoomCount ?> phòng đang liên kết với số này</span>
            <?php endif; ?>
          </div>
        <?php else: ?>
          <h1><?= !empty($stayHistory) ? 'Hiện không còn phòng thuê đang hoạt động' : 'Chưa có phòng nào được liên kết với tài khoản' ?></h1>
          <p>
            <?= !empty($stayHistory)
              ? 'Bạn đã checkout khỏi phòng gần nhất. Hệ thống vẫn giữ lịch sử thuê, hoàn cọc và các kỳ trước ở phần bên dưới.'
              : 'Chủ trọ cần gắn đúng số điện thoại của bạn trong hồ sơ vận hành phòng. Khi đã liên kết, bạn sẽ thấy hợp đồng, hóa đơn, thông báo và khu báo sự cố tại đây.' ?>
          </p>
        <?php endif; ?>
      </div>
      <div class="stay-hero-actions">
        <?php if ($room): ?>
          <a class="btn btn-outline" href="<?= htmlspecialchars(routeUrl('my-stay', ['section' => 'invoices'])) ?>#my-invoices">Hóa đơn của tôi</a>
          <a class="btn btn-outline" href="<?= htmlspecialchars(routeUrl('my-stay', ['section' => 'notices'])) ?>#my-notices">Thông báo</a>
          <a class="btn btn-primary" href="<?= htmlspecialchars(routeUrl('my-stay', ['section' => 'issues'])) ?>#my-issues">Báo sự cố</a>
        <?php else: ?>
          <a class="btn btn-outline" href="?route=seek-posts">Đăng tìm phòng</a>
          <a class="btn btn-primary" href="?route=rooms">Tìm thêm phòng</a>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <section class="stay-vòng đời">
    <?php foreach ($stayLifecycle as $step): ?>
      <article class="stay-step <?= htmlspecialchars((string)$step['state']) ?>">
        <div class="stay-step-label"><?= htmlspecialchars((string)$step['label']) ?></div>
        <div class="stay-step-value"><?= htmlspecialchars((string)$step['value']) ?></div>
      </article>
    <?php endforeach; ?>
  </section>

  <?php if (!$room || !$profile): ?>
    <div class="stay-card stay-section" id="my-room">
      <div class="stay-card-head">
        <div>
          <h2 class="stay-card-title"><?= !empty($stayHistory) ? 'Không còn kỳ thuê đang hoạt động' : 'Cần gì để mở khu này?' ?></h2>
          <p class="stay-card-note"><?= !empty($stayHistory) ? 'Kỳ thuê hiện tại đã kết thúc. Nếu thuê mới, chủ trọ chỉ cần gắn lại đúng số điện thoại của bạn là khu này sẽ mở lại ngay.' : 'Chỉ cần chủ trọ cập nhật số điện thoại của bạn tại hồ sơ vận hành phòng.' ?></p>
        </div>
      </div>
      <div class="stay-empty">
        <?= !empty($stayHistory)
          ? 'Bạn vẫn xem được lịch sử các kỳ đã thuê, hoàn cọc và ghi chú checkout ở bên dưới. Khi phát sinh kỳ thuê mới, phần phòng hiện tại và hóa đơn mới sẽ xuất hiện lại tự động.'
          : 'Khi chủ trọ nhập đúng số điện thoại tài khoản của bạn vào mục người thuê, trang này sẽ tự hiện thông tin phòng đang thuê, hóa đơn tháng này, lịch sử các tháng trước, thông báo và biểu mẫu báo sự cố.' ?>
      </div>
    </div>
    <?php if (!empty($stayHistory)): ?>
      <section class="stay-card stay-section" id="my-history">
        <div class="stay-card-head">
          <div>
            <h2 class="stay-card-title">Lịch sử thuê đã lưu</h2>
            <p class="stay-card-note">Bạn hiện không còn phòng active, nhưng hệ thống vẫn giữ lại các kỳ đã thuê, hoàn cọc và khấu trừ trước đó.</p>
          </div>
        </div>

        <div class="stay-list">
          <?php foreach ($stayHistory as $stay): ?>
            <?php
              $refundAmount = (int)($stay['deposit_refund_amount'] ?? 0);
              $deductionAmount = (int)($stay['deposit_deduction_amount'] ?? 0);
              $stayStatus = (string)($stay['status'] ?? 'closed');
            ?>
            <article class="stay-item">
              <div class="stay-item-top">
                <div>
                  <div class="stay-item-title"><?= htmlspecialchars((string)($stay['room_title'] ?? 'Kỳ thuê')) ?></div>
                  <div class="stay-card-note"><?= htmlspecialchars((string)($stay['started_at'] ?? '')) ?> → <?= !empty($stay['ended_at']) ? htmlspecialchars((string)$stay['ended_at']) : 'đang ở' ?></div>
                </div>
                <span class="stay-badge <?= htmlspecialchars($stayStatus === 'active' ? 'due_soon' : 'paid') ?>">
                  <?= htmlspecialchars($stayStatus === 'active' ? 'Đang thuê' : 'Đã checkout') ?>
                </span>
              </div>
              <div class="stay-inline-grid">
                <div class="stay-inline-cell">
                  <small>Người thuê</small>
                  <strong><?= htmlspecialchars((string)($stay['tenant_name'] ?? 'Bạn')) ?></strong>
                </div>
                <div class="stay-inline-cell">
                  <small>Tiền thuê</small>
                  <strong><?= number_format((int)($stay['rent_amount'] ?? 0), 0, ',', '.') ?> đ</strong>
                </div>
                <div class="stay-inline-cell">
                  <small>Tiền cọc</small>
                  <strong><?= number_format((int)($stay['deposit_amount'] ?? 0), 0, ',', '.') ?> đ</strong>
                </div>
                <div class="stay-inline-cell">
                  <small>Hoàn cọc / khấu trừ</small>
                  <strong><?= number_format($refundAmount, 0, ',', '.') ?> đ / <?= number_format($deductionAmount, 0, ',', '.') ?> đ</strong>
                </div>
                <div class="stay-inline-cell">
                  <small>Nhịp thanh toán</small>
                  <strong><?= htmlspecialchars((string)($stay['payment_regularity_label'] ?? 'Chưa có dữ liệu')) ?></strong>
                </div>
                <div class="stay-inline-cell">
                  <small>Chốt cọc</small>
                  <strong><?= !empty($stay['settled_at']) ? htmlspecialchars((string)$stay['settled_at']) : 'Chưa chốt' ?></strong>
                </div>
              </div>
              <?php if (!empty($stay['settlement_note'])): ?>
                <div class="stay-copy"><?= nl2br(htmlspecialchars((string)$stay['settlement_note'])) ?></div>
              <?php endif; ?>
            </article>
          <?php endforeach; ?>
        </div>
      </section>
    <?php endif; ?>
  <?php else: ?>
    <div class="stay-grid">
      <div class="stay-main">
        <section class="stay-card stay-section" id="my-room">
          <div class="stay-card-head">
            <div>
              <h2 class="stay-card-title">Phòng tôi đang thuê</h2>
              <p class="stay-card-note">Tóm tắt hợp đồng, giá thuê và thông tin phòng để bạn mở ứng dụng là thấy ngay.</p>
            </div>
          </div>

          <div class="stay-kpi-grid" style="margin-bottom:14px;">
            <div class="stay-kpi">
              <div class="stay-kpi-label">Giá thuê</div>
              <div class="stay-kpi-value"><?= number_format($monthlyRent, 0, ',', '.') ?>đ</div>
            </div>
            <div class="stay-kpi">
              <div class="stay-kpi-label">Tiền cọc</div>
              <div class="stay-kpi-value"><?= number_format($depositAmount, 0, ',', '.') ?>đ</div>
            </div>
            <div class="stay-kpi">
              <div class="stay-kpi-label">Phí dịch vụ</div>
              <div class="stay-kpi-value"><?= number_format($serviceFee, 0, ',', '.') ?>đ</div>
            </div>
            <div class="stay-kpi">
              <div class="stay-kpi-label">Hợp đồng</div>
              <div class="stay-kpi-value"><?= $contractDaysLeft === null ? '—' : ((int)$contractDaysLeft . ' ngày') ?></div>
            </div>
            <div class="stay-kpi">
              <div class="stay-kpi-label">Hóa đơn tồn</div>
              <div class="stay-kpi-value"><?= $unpaidInvoiceCount ?></div>
            </div>
          </div>

          <div class="stay-info-grid">
            <div class="stay-info-item">
              <small>Số phòng</small>
              <strong>#<?= (int)$room['id'] ?> · <?= htmlspecialchars((string)($room['title'] ?? '')) ?></strong>
            </div>
            <div class="stay-info-item">
              <small>Địa chỉ</small>
              <strong><?= htmlspecialchars((string)($room['address'] ?? '')) ?></strong>
            </div>
            <div class="stay-info-item">
              <small>Ngày bắt đầu thuê</small>
              <strong><?= $contractStart !== '' ? htmlspecialchars($contractStart) : 'Chưa cập nhật' ?></strong>
            </div>
            <div class="stay-info-item">
              <small>Trạng thái hợp đồng</small>
              <strong><?= htmlspecialchars($contractStatusText) ?></strong>
            </div>
            <div class="stay-info-item">
              <small>Điện / nước</small>
              <strong>
                Điện: <?= !empty($room['electric_price']) ? number_format((int)$room['electric_price'], 0, ',', '.') . ' đ/kWh' : 'chưa cập nhật' ?><br>
                Nước: <?= !empty($room['water_price']) ? number_format((int)$room['water_price'], 0, ',', '.') . ' đ/m³' : 'chưa cập nhật' ?>
              </strong>
            </div>
            <div class="stay-info-item">
              <small>Tình trạng phòng</small>
              <strong><?= htmlspecialchars($conditionLabel) ?></strong>
            </div>
            <div class="stay-info-item">
              <small>Công tơ gần nhất</small>
              <strong>
                Điện: <?= isset($profile['electric_meter_reading']) && $profile['electric_meter_reading'] !== null ? number_format((int)$profile['electric_meter_reading'], 0, ',', '.') : 'chưa cập nhật' ?><br>
                Nước: <?= isset($profile['water_meter_reading']) && $profile['water_meter_reading'] !== null ? number_format((int)$profile['water_meter_reading'], 0, ',', '.') : 'chưa cập nhật' ?>
              </strong>
            </div>
          </div>

          <div class="stay-landlord" style="margin-top:14px;">
            <div class="stay-landlord-avatar">
              <img src="<?= htmlspecialchars($landlordAvatar) ?>" alt="Chủ trọ" onerror="this.src='<?= htmlspecialchars(assetUrl('avt.jpg')) ?>'">
            </div>
            <div style="min-width:0;">
              <div class="stay-card-note">Chủ trọ phụ trách</div>
              <div class="stay-item-title"><?= !empty($landlord['name']) ? htmlspecialchars((string)$landlord['name']) : 'Chưa cập nhật' ?></div>
              <div class="stay-card-note"><?= !empty($landlord['phone']) ? htmlspecialchars((string)$landlord['phone']) : 'Chưa có số liên hệ' ?></div>
            </div>
          </div>
        </section>

        <section class="stay-card stay-section">
          <div class="stay-card-head">
            <div>
              <h2 class="stay-card-title">Lịch sử thuê và tiền cọc</h2>
              <p class="stay-card-note">Theo dõi bạn đã ở từ khi nào, từng ở phòng nào và chủ trọ đã chốt hoàn cọc ra sao theo từng kỳ thuê.</p>
            </div>
          </div>

          <?php if ($activeStay): ?>
            <div class="stay-invoice-highlight">
              <div class="stay-item-top">
                <div>
                  <div class="stay-item-title">Kỳ thuê hiện tại đang hoạt động</div>
                  <div class="stay-card-note">Bản ghi này lấy trực tiếp từ hồ sơ vận hành của chủ trọ.</div>
                </div>
                <span class="stay-badge unpaid"><?= htmlspecialchars((string)($activeStay['payment_regularity_label'] ?? 'Đang theo dõi')) ?></span>
              </div>
              <div class="stay-inline-grid">
                <div class="stay-inline-cell">
                  <small>Bắt đầu thuê</small>
                  <strong><?= !empty($activeStay['started_at']) ? htmlspecialchars((string)$activeStay['started_at']) : 'Chưa cập nhật' ?></strong>
                </div>
                <div class="stay-inline-cell">
                  <small>Đặt cọc ban đầu</small>
                  <strong><?= number_format((int)($activeStay['deposit_amount'] ?? $depositAmount), 0, ',', '.') ?> đ</strong>
                </div>
                <div class="stay-inline-cell">
                  <small>Nhịp thanh toán</small>
                  <strong><?= htmlspecialchars((string)($activeStay['payment_regularity_label'] ?? 'Chưa có dữ liệu')) ?></strong>
                </div>
              </div>
            </div>
          <?php endif; ?>

          <div class="stay-timeline">
            <?php if (empty($stayHistory)): ?>
              <div class="stay-empty">Chưa có lịch sử thuê nào được ghi nhận cho số điện thoại này.</div>
            <?php endif; ?>
            <?php foreach ($stayHistory as $stay): ?>
              <?php
                $stayStatus = (string)($stay['status'] ?? 'active');
                $stayStatusClass = $stayStatus === 'active' ? 'unpaid' : 'paid';
                $stayStatusLabel = $stayStatus === 'active' ? 'Đang thuê' : 'Đã kết thúc';
                $deductionAmount = (int)($stay['deposit_deduction_amount'] ?? 0);
                $refundAmount = (int)($stay['deposit_refund_amount'] ?? 0);
              ?>
              <article class="stay-timeline-item">
                <div class="stay-item-top">
                  <div>
                    <div class="stay-item-title">
                      <?= !empty($stay['room_title']) ? htmlspecialchars((string)$stay['room_title']) : ('Phòng #' . (int)($stay['room_id'] ?? 0)) ?>
                    </div>
                    <div class="stay-card-note">
                      <?= !empty($stay['room_address']) ? htmlspecialchars((string)$stay['room_address']) : 'Hồ sơ thuê theo phòng đang liên kết' ?>
                    </div>
                  </div>
                  <span class="stay-badge <?= htmlspecialchars($stayStatusClass) ?>"><?= htmlspecialchars($stayStatusLabel) ?></span>
                </div>
                <div class="stay-inline-grid">
                  <div class="stay-inline-cell">
                    <small>Từ ngày</small>
                    <strong><?= !empty($stay['started_at']) ? htmlspecialchars((string)$stay['started_at']) : 'Chưa cập nhật' ?></strong>
                  </div>
                  <div class="stay-inline-cell">
                    <small>Đến ngày</small>
                    <strong><?= !empty($stay['ended_at']) ? htmlspecialchars((string)$stay['ended_at']) : ($stayStatus === 'active' ? 'Đang ở' : 'Chưa chốt') ?></strong>
                  </div>
                  <div class="stay-inline-cell">
                    <small>Tiền thuê / cọc</small>
                    <strong><?= number_format((int)($stay['rent_amount'] ?? $monthlyRent), 0, ',', '.') ?> đ / <?= number_format((int)($stay['deposit_amount'] ?? 0), 0, ',', '.') ?> đ</strong>
                  </div>
                  <div class="stay-inline-cell">
                    <small>Đã thanh toán</small>
                    <strong><?= (int)($stay['payment_paid_count'] ?? 0) ?> kỳ</strong>
                  </div>
                  <div class="stay-inline-cell">
                    <small>Chưa thanh toán</small>
                    <strong><?= (int)($stay['payment_unpaid_count'] ?? 0) ?> kỳ</strong>
                  </div>
                  <div class="stay-inline-cell">
                    <small>Hoàn cọc / khấu trừ</small>
                    <strong><?= number_format($refundAmount, 0, ',', '.') ?> đ / <?= number_format($deductionAmount, 0, ',', '.') ?> đ</strong>
                  </div>
                </div>
                <?php if (!empty($stay['settlement_note'])): ?>
                  <div class="stay-copy"><?= nl2br(htmlspecialchars((string)$stay['settlement_note'])) ?></div>
                <?php endif; ?>
              </article>
            <?php endforeach; ?>
          </div>
        </section>

        <section class="stay-card stay-section" id="my-invoices">
          <div class="stay-card-head">
            <div>
              <h2 class="stay-card-title">Hóa đơn của tôi</h2>
              <p class="stay-card-note">Xem tháng này trước, kéo xuống là thấy các tháng trước và trạng thái đã thanh toán hay chưa.</p>
            </div>
          </div>

          <?php if ($currentInvoice): ?>
            <?php
              $currentDisplayStatus = (string)($currentInvoice['display_status'] ?? 'unpaid');
              $currentReminderState = (string)($currentInvoice['reminder_state'] ?? $currentDisplayStatus);
              $currentStatusClass = $invoiceStatusClassMap[$currentDisplayStatus] ?? 'unpaid';
            ?>
            <div class="stay-invoice-highlight">
              <div class="stay-item-top">
                <div>
                  <div class="stay-item-title">Hóa đơn trọng tâm: <?= htmlspecialchars((string)($currentInvoice['billing_month'] ?? '')) ?></div>
                  <div class="stay-card-note">Tổng tiền cần theo dõi trong kỳ hiện tại.</div>
                </div>
                <div class="stay-badge-row">
                  <span class="stay-badge <?= htmlspecialchars($currentStatusClass) ?>"><?= htmlspecialchars($invoiceStatusLabels[$currentDisplayStatus] ?? $currentDisplayStatus) ?></span>
                  <?php if ($currentReminderState === 'due_soon'): ?>
                    <span class="stay-badge due_soon"><?= htmlspecialchars($invoiceReminderLabels[$currentReminderState]) ?></span>
                  <?php endif; ?>
                </div>
              </div>
              <div class="stay-inline-grid">
                <div class="stay-inline-cell">
                  <small>Tổng tiền</small>
                  <strong><?= number_format((int)($currentInvoice['total_amount'] ?? 0), 0, ',', '.') ?> đ</strong>
                </div>
                <div class="stay-inline-cell">
                  <small>Đã thanh toán / còn thiếu</small>
                  <strong><?= number_format((int)($currentInvoice['amount_paid'] ?? 0), 0, ',', '.') ?> đ / <?= number_format((int)($currentInvoice['amount_due'] ?? 0), 0, ',', '.') ?> đ</strong>
                </div>
                <div class="stay-inline-cell">
                  <small>Hạn thanh toán</small>
                  <strong><?= !empty($currentInvoice['due_date']) ? htmlspecialchars((string)$currentInvoice['due_date']) : 'Chưa đặt hạn' ?></strong>
                </div>
                <div class="stay-inline-cell">
                  <small>Ngày thanh toán</small>
                  <strong><?= !empty($currentInvoice['paid_date']) ? htmlspecialchars((string)$currentInvoice['paid_date']) : 'Chưa thanh toán' ?></strong>
                </div>
                <div class="stay-inline-cell">
                  <small>Tiền phòng / dịch vụ</small>
                  <strong><?= number_format((int)($currentInvoice['rent_amount'] ?? 0), 0, ',', '.') ?> đ / <?= number_format((int)($currentInvoice['service_amount'] ?? 0), 0, ',', '.') ?> đ</strong>
                </div>
                <div class="stay-inline-cell">
                  <small>Điện / nước</small>
                  <strong><?= number_format((int)($currentInvoice['electric_amount'] ?? 0), 0, ',', '.') ?> đ / <?= number_format((int)($currentInvoice['water_amount'] ?? 0), 0, ',', '.') ?> đ</strong>
                </div>
                <div class="stay-inline-cell">
                  <small>Phát sinh thêm</small>
                  <strong><?= number_format((int)($currentInvoice['other_amount'] ?? 0), 0, ',', '.') ?> đ</strong>
                </div>
              </div>
            </div>
          <?php endif; ?>

          <div class="stay-list">
            <?php if (empty($invoices)): ?>
              <div class="stay-empty">Chưa có hóa đơn nào được chủ trọ tạo cho phòng này.</div>
            <?php endif; ?>
            <?php foreach ($invoices as $invoice): ?>
              <?php
                $displayStatus = (string)($invoice['display_status'] ?? 'unpaid');
                $reminderState = (string)($invoice['reminder_state'] ?? $displayStatus);
                $statusClass = $invoiceStatusClassMap[$displayStatus] ?? 'unpaid';
              ?>
              <article class="stay-item">
                <div class="stay-item-top">
                  <div>
                    <div class="stay-item-title">Hóa đơn <?= htmlspecialchars((string)($invoice['billing_month'] ?? '')) ?></div>
                    <div class="stay-card-note">Tạo lúc <?= htmlspecialchars((string)($invoice['created_at'] ?? '')) ?></div>
                  </div>
                  <div class="stay-badge-row">
                    <span class="stay-badge <?= htmlspecialchars($statusClass) ?>"><?= htmlspecialchars($invoiceStatusLabels[$displayStatus] ?? $displayStatus) ?></span>
                    <?php if ($reminderState === 'due_soon'): ?>
                      <span class="stay-badge due_soon"><?= htmlspecialchars($invoiceReminderLabels[$reminderState]) ?></span>
                    <?php endif; ?>
                  </div>
                </div>
                <div class="stay-inline-grid">
                  <div class="stay-inline-cell">
                    <small>Tổng tiền</small>
                    <strong><?= number_format((int)($invoice['total_amount'] ?? 0), 0, ',', '.') ?> đ</strong>
                  </div>
                  <div class="stay-inline-cell">
                    <small>Đã thanh toán / còn thiếu</small>
                    <strong><?= number_format((int)($invoice['amount_paid'] ?? 0), 0, ',', '.') ?> đ / <?= number_format((int)($invoice['amount_due'] ?? 0), 0, ',', '.') ?> đ</strong>
                  </div>
                  <div class="stay-inline-cell">
                    <small>Tiền thuê</small>
                    <strong><?= number_format((int)($invoice['rent_amount'] ?? 0), 0, ',', '.') ?> đ</strong>
                  </div>
                  <div class="stay-inline-cell">
                    <small>Phí dịch vụ</small>
                    <strong><?= number_format((int)($invoice['service_amount'] ?? 0), 0, ',', '.') ?> đ</strong>
                  </div>
                  <div class="stay-inline-cell">
                    <small>Tiền điện</small>
                    <strong><?= number_format((int)($invoice['electric_amount'] ?? 0), 0, ',', '.') ?> đ</strong>
                  </div>
                  <div class="stay-inline-cell">
                    <small>Tiền nước</small>
                    <strong><?= number_format((int)($invoice['water_amount'] ?? 0), 0, ',', '.') ?> đ</strong>
                  </div>
                  <div class="stay-inline-cell">
                    <small>Hạn thanh toán</small>
                    <strong><?= !empty($invoice['due_date']) ? htmlspecialchars((string)$invoice['due_date']) : 'Chưa cập nhật' ?></strong>
                  </div>
                </div>
                <?php if (!empty($invoice['note'])): ?>
                  <div class="stay-copy"><?= nl2br(htmlspecialchars((string)($invoice['note'] ?? ''))) ?></div>
                <?php endif; ?>
              </article>
            <?php endforeach; ?>
          </div>
        </section>

        <section class="stay-card stay-section">
          <div class="stay-card-head">
            <div>
              <h2 class="stay-card-title">Nhật ký chỉ số điện nước</h2>
              <p class="stay-card-note">Theo dõi từng kỳ tiêu thụ để thấy tháng nào tăng bất thường và đối chiếu trực tiếp với hóa đơn.</p>
            </div>
          </div>

          <div class="stay-list">
            <?php if (empty($meterLogs)): ?>
              <div class="stay-empty">Chưa có kỳ điện nước nào được ghi nhận cho phòng này.</div>
            <?php endif; ?>
            <?php foreach ($meterLogs as $meterLog): ?>
              <article class="stay-item">
                <div class="stay-item-top">
                  <div>
                    <div class="stay-item-title">Kỳ <?= htmlspecialchars((string)($meterLog['billing_month'] ?? '')) ?></div>
                    <div class="stay-card-note">Ghi nhận lúc <?= htmlspecialchars((string)($meterLog['created_at'] ?? '')) ?></div>
                  </div>
                  <span class="stay-badge unpaid">Theo kỳ</span>
                </div>
                <div class="stay-inline-grid">
                  <div class="stay-inline-cell">
                    <small>Điện cũ / mới</small>
                    <strong><?= $meterLog['electric_reading_old'] !== null ? number_format((int)$meterLog['electric_reading_old'], 0, ',', '.') : '—' ?> / <?= $meterLog['electric_reading_new'] !== null ? number_format((int)$meterLog['electric_reading_new'], 0, ',', '.') : '—' ?></strong>
                  </div>
                  <div class="stay-inline-cell">
                    <small>Tiêu thụ điện</small>
                    <strong><?= $meterLog['electric_units'] !== null ? number_format((int)$meterLog['electric_units'], 0, ',', '.') . ' kWh' : 'Chưa có' ?></strong>
                  </div>
                  <div class="stay-inline-cell">
                    <small>Nước cũ / mới</small>
                    <strong><?= $meterLog['water_reading_old'] !== null ? number_format((int)$meterLog['water_reading_old'], 0, ',', '.') : '—' ?> / <?= $meterLog['water_reading_new'] !== null ? number_format((int)$meterLog['water_reading_new'], 0, ',', '.') : '—' ?></strong>
                  </div>
                  <div class="stay-inline-cell">
                    <small>Tiêu thụ nước</small>
                    <strong><?= $meterLog['water_units'] !== null ? number_format((int)$meterLog['water_units'], 0, ',', '.') . ' m³' : 'Chưa có' ?></strong>
                  </div>
                  <div class="stay-inline-cell">
                    <small>Đối chiếu công tơ</small>
                    <strong><?= !empty($meterLog['invoice_id']) ? ('Gắn với hóa đơn #' . (int)$meterLog['invoice_id']) : 'Chưa gắn hóa đơn' ?></strong>
                  </div>
                  <div class="stay-inline-cell">
                    <small>Xu hướng</small>
                    <strong><?= empty($meterLog['usage_alerts']) ? 'Ổn định' : 'Cần xem lại' ?></strong>
                  </div>
                </div>
                <?php if (!empty($meterLog['usage_alerts'])): ?>
                  <div class="stay-alert-row">
                    <?php foreach ($meterLog['usage_alerts'] as $usageAlert): ?>
                      <span class="stay-alert-chip"><?= htmlspecialchars((string)$usageAlert) ?></span>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>
              </article>
            <?php endforeach; ?>
          </div>
        </section>

        <section class="stay-card stay-section">
          <div class="stay-card-head">
            <div>
              <h2 class="stay-card-title">Bàn giao và tình trạng phòng</h2>
              <p class="stay-card-note">Ảnh lúc nhận hoặc trả phòng giúp đối chiếu nhanh tình trạng tường, giường và thiết bị khi phát sinh tranh chấp.</p>
            </div>
          </div>

          <div class="stay-list">
            <?php if (empty($handoverRecords)): ?>
              <div class="stay-empty">Chưa có ảnh bàn giao nào được lưu cho kỳ thuê hiện tại.</div>
            <?php endif; ?>
            <?php foreach ($handoverRecords as $handover): ?>
              <?php
                $handoverType = (string)($handover['handover_type'] ?? 'move_in');
                $handoverLabel = $handoverTypeOptions[$handoverType] ?? 'Bàn giao';
              ?>
              <article class="stay-item">
                <div class="stay-item-top">
                  <div>
                    <div class="stay-item-title"><?= htmlspecialchars($handoverLabel) ?></div>
                    <div class="stay-card-note">Lưu lúc <?= htmlspecialchars((string)($handover['created_at'] ?? '')) ?></div>
                  </div>
                  <span class="stay-badge <?= htmlspecialchars($handoverType === 'move_out' ? 'due_soon' : 'paid') ?>"><?= htmlspecialchars($handoverLabel) ?></span>
                </div>
                <?php if (!empty($handover['note'])): ?>
                  <div class="stay-copy"><?= nl2br(htmlspecialchars((string)$handover['note'])) ?></div>
                <?php endif; ?>
                <div class="stay-photo-grid">
                  <?php if (!empty($handover['wall_image'])): ?>
                    <a class="stay-photo-card" href="<?= htmlspecialchars(assetUrl((string)$handover['wall_image'])) ?>" target="_blank" rel="noopener">
                      <img src="<?= htmlspecialchars(assetUrl((string)$handover['wall_image'])) ?>" alt="Ảnh tường">
                      <span class="stay-photo-label">Tường</span>
                    </a>
                  <?php endif; ?>
                  <?php if (!empty($handover['bed_image'])): ?>
                    <a class="stay-photo-card" href="<?= htmlspecialchars(assetUrl((string)$handover['bed_image'])) ?>" target="_blank" rel="noopener">
                      <img src="<?= htmlspecialchars(assetUrl((string)$handover['bed_image'])) ?>" alt="Ảnh giường">
                      <span class="stay-photo-label">Giường</span>
                    </a>
                  <?php endif; ?>
                  <?php if (!empty($handover['equipment_image'])): ?>
                    <a class="stay-photo-card" href="<?= htmlspecialchars(assetUrl((string)$handover['equipment_image'])) ?>" target="_blank" rel="noopener">
                      <img src="<?= htmlspecialchars(assetUrl((string)$handover['equipment_image'])) ?>" alt="Ảnh thiết bị">
                      <span class="stay-photo-label">Thiết bị</span>
                    </a>
                  <?php endif; ?>
                </div>
              </article>
            <?php endforeach; ?>
          </div>
        </section>

        <section class="stay-card stay-section" id="my-issues">
          <div class="stay-card-head">
            <div>
              <h2 class="stay-card-title">Báo sự cố</h2>
              <p class="stay-card-note">Gửi nội dung, ảnh minh chứng và mức độ ưu tiên để chủ trọ xử lý nhanh hơn.</p>
            </div>
          </div>

          <form method="post" action="?route=my-stay" enctype="multipart/form-data" class="stay-form-grid">
            <input type="hidden" name="action" value="create-tenant-issue">
            <input type="hidden" name="room_id" value="<?= (int)$room['id'] ?>">

            <div class="stay-field">
              <label class="stay-label" for="stayIssuePriority">Mức độ ưu tiên</label>
              <select id="stayIssuePriority" name="priority" class="form-control">
                <?php foreach ($issuePriorityOptions as $value => $label): ?>
                  <option value="<?= htmlspecialchars($value) ?>"><?= htmlspecialchars($label) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="stay-field">
              <label class="stay-label" for="stayIssueImage">Ảnh minh chứng</label>
              <input id="stayIssueImage" type="file" name="issue_image" class="form-control" accept="image/*">
            </div>

            <div class="stay-field full">
              <label class="stay-label" for="stayIssueContent">Nội dung sự cố</label>
              <textarea id="stayIssueContent" name="content" class="form-control" rows="4" placeholder="Ví dụ: Máy lạnh không mát từ tối qua, nước chảy nhỏ, ổ điện khu bếp có mùi khét."></textarea>
            </div>

            <div class="stay-field full">
              <div class="stay-actions">
                <button class="btn btn-primary" type="submit">Gửi báo sự cố</button>
              </div>
            </div>
          </form>

          <div class="stay-list" style="margin-top:16px;">
            <?php if (empty($issues)): ?>
              <div class="stay-empty">Bạn chưa gửi báo sự cố nào cho phòng này.</div>
            <?php endif; ?>
            <?php foreach ($issues as $issue): ?>
              <?php
                $issuePriorityLabel = $issuePriorityOptions[$issue['priority'] ?? 'normal'] ?? ($issue['priority'] ?? 'Bình thường');
                $issueStatus = (string)($issue['status'] ?? 'open');
                $issueStatusLabel = $issueStatusOptions[$issueStatus] ?? $issueStatus;
                $issueStatusClass = $issueStatusClassMap[$issueStatus] ?? 'overdue';
              ?>
              <article class="stay-item">
                <div class="stay-item-top">
                  <div>
                    <div class="stay-item-title">Sự cố đã gửi</div>
                    <div class="stay-card-note"><?= htmlspecialchars((string)($issue['created_at'] ?? '')) ?></div>
                  </div>
                  <span class="stay-badge <?= htmlspecialchars($issueStatusClass) ?>">
                    <?= htmlspecialchars((string)$issueStatusLabel) ?>
                  </span>
                </div>
                <div class="stay-inline-grid">
                  <div class="stay-inline-cell">
                    <small>Ưu tiên</small>
                    <strong><?= htmlspecialchars((string)$issuePriorityLabel) ?></strong>
                  </div>
                  <div class="stay-inline-cell">
                    <small>Trạng thái</small>
                    <strong><?= htmlspecialchars((string)$issueStatusLabel) ?></strong>
                  </div>
                  <div class="stay-inline-cell">
                    <small>Phòng</small>
                    <strong>#<?= (int)$room['id'] ?></strong>
                  </div>
                  <div class="stay-inline-cell">
                    <small>Chi phí sửa chữa</small>
                    <strong><?= number_format((int)($issue['repair_cost'] ?? 0), 0, ',', '.') ?> đ</strong>
                  </div>
                </div>
                <div class="stay-copy"><?= nl2br(htmlspecialchars((string)($issue['content'] ?? ''))) ?></div>
                <?php if (!empty($issue['landlord_note'])): ?>
                  <div class="stay-copy" style="margin-top:10px;"><strong>Phản hồi từ chủ trọ:</strong><br><?= nl2br(htmlspecialchars((string)$issue['landlord_note'])) ?></div>
                <?php endif; ?>
                <?php if (!empty($issue['image_path'])): ?>
                  <a class="stay-proof" href="<?= htmlspecialchars(assetUrl((string)$issue['image_path'])) ?>" target="_blank" rel="noopener">
                    <img src="<?= htmlspecialchars(assetUrl((string)$issue['image_path'])) ?>" alt="Minh chứng sự cố">
                  </a>
                <?php endif; ?>
              </article>
            <?php endforeach; ?>
          </div>
        </section>
      </div>

      <aside class="stay-side">
        <section class="stay-highlight">
          <strong>
            <?= $overdueInvoiceCount > 0
              ? 'Có khoản đang quá hạn'
              : ($dueSoonInvoiceCount > 0 ? 'Sắp đến hạn thanh toán' : ($unpaidInvoiceCount > 0 ? 'Có việc cần xử lý' : 'Mọi thứ đang ổn')) ?>
          </strong>
          <p>
            <?= $overdueInvoiceCount > 0
              ? ('Hiện có ' . $overdueInvoiceCount . ' hóa đơn đã quá hạn. Nên ưu tiên xem lại hạn thanh toán và các thông báo từ chủ trọ để tránh phát sinh tranh chấp.')
              : ($dueSoonInvoiceCount > 0
                ? ('Có ' . $dueSoonInvoiceCount . ' hóa đơn gần đến hạn. Hệ thống đang nhắc sớm để bạn chủ động xử lý trước khi chuyển quá hạn.')
                : ($unpaidInvoiceCount > 0
                  ? ('Hiện có ' . $unpaidInvoiceCount . ' hóa đơn chưa thanh toán. Nên kiểm tra hạn thanh toán và thông báo mới nhất từ chủ trọ.')
                  : 'Hiện không có hóa đơn tồn. Bạn có thể theo dõi hợp đồng, thông báo và gửi sự cố nếu phòng phát sinh vấn đề.')) ?>
          </p>
        </section>

        <section class="stay-card">
          <div class="stay-card-head">
            <div>
              <h2 class="stay-card-title">Tóm tắt kỳ thuê</h2>
              <p class="stay-card-note">Những thứ bạn thường phải hỏi lại chủ trọ được gom về một chỗ.</p>
            </div>
          </div>
          <div class="stay-inline-grid" style="margin-top:0;">
            <div class="stay-inline-cell">
              <small>Số kỳ đã ở</small>
              <strong><?= count($stayHistory) ?></strong>
            </div>
            <div class="stay-inline-cell">
              <small>Nhịp thanh toán</small>
              <strong><?= htmlspecialchars((string)($activeStay['payment_regularity_label'] ?? 'Chưa có dữ liệu')) ?></strong>
            </div>
            <div class="stay-inline-cell">
              <small>Cọc hiện tại</small>
              <strong><?= number_format($depositAmount, 0, ',', '.') ?> đ</strong>
            </div>
            <div class="stay-inline-cell">
              <small>Hoàn cọc gần nhất</small>
              <strong><?= $latestSettlement ? number_format((int)($latestSettlement['deposit_refund_amount'] ?? 0), 0, ',', '.') . ' đ' : 'Chưa chốt' ?></strong>
            </div>
            <div class="stay-inline-cell">
              <small>Khấu trừ gần nhất</small>
              <strong><?= $latestSettlement ? number_format((int)($latestSettlement['deposit_deduction_amount'] ?? 0), 0, ',', '.') . ' đ' : '0 đ' ?></strong>
            </div>
            <div class="stay-inline-cell">
              <small>Ảnh bàn giao</small>
              <strong><?= count($handoverRecords) ?> bộ</strong>
            </div>
          </div>
        </section>

        <section class="stay-card stay-section" id="my-notices">
          <div class="stay-card-head">
            <div>
              <h2 class="stay-card-title">Thông báo từ chủ trọ</h2>
              <p class="stay-card-note">Nhắc thanh toán, điều chỉnh phí, lịch điện nước và nội quy sẽ nằm ở đây.</p>
            </div>
          </div>

          <div class="stay-list">
            <?php if (empty($notices)): ?>
              <div class="stay-empty">Chưa có thông báo nào được gửi xuống cho phòng này.</div>
            <?php endif; ?>
            <?php foreach ($notices as $notice): ?>
              <?php $noticeTypeLabel = $noticeTypeOptions[$notice['notice_type'] ?? 'general'] ?? ($notice['notice_type'] ?? 'Thông báo'); ?>
              <article class="stay-item">
                <div class="stay-item-top">
                  <div>
                    <div class="stay-item-title"><?= htmlspecialchars((string)($notice['title'] ?? 'Thông báo')) ?></div>
                    <div class="stay-card-note">Gửi lúc <?= htmlspecialchars((string)($notice['created_at'] ?? '')) ?></div>
                  </div>
                  <span class="stay-badge unpaid"><?= htmlspecialchars((string)$noticeTypeLabel) ?></span>
                </div>
                <?php if (!empty($notice['effective_date'])): ?>
                  <div class="stay-card-note" style="margin-top:10px;">Hiệu lực từ <?= htmlspecialchars((string)$notice['effective_date']) ?></div>
                <?php endif; ?>
                <div class="stay-copy"><?= nl2br(htmlspecialchars((string)($notice['content'] ?? ''))) ?></div>
              </article>
            <?php endforeach; ?>
          </div>
        </section>
      </aside>
    </div>
  <?php endif; ?>
</div>
