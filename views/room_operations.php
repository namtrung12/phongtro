<?php
$pageTitle = 'Vận hành phòng';

$profile = $operationProfile ?? [];
$occupancyStatus = (string)($profile['occupancy_status'] ?? 'vacant');
$roomCondition = (string)($profile['room_condition'] ?? 'ready');
$occupancyLabel = $occupancyOptions[$occupancyStatus] ?? 'Phòng trống';
$conditionLabel = $conditionOptions[$roomCondition] ?? 'Ổn định';
$contractEnd = trim((string)($profile['contract_end'] ?? ''));
$contractStart = trim((string)($profile['contract_start'] ?? ''));
$contractDaysLeft = $contractEnd !== '' ? daysUntilDate($contractEnd) : null;
$unpaidInvoices = array_values(array_filter($roomInvoices ?? [], static function (array $invoice): bool {
    $display = (string)($invoice['display_status'] ?? '');
    return $display !== 'paid' && $display !== 'cancelled';
}));
$latestInvoice = $roomInvoices[0] ?? null;
$roomNotices = $roomNotices ?? [];
$roomIssues = $roomIssues ?? [];
$stayHistory = $stayHistory ?? [];
$meterLogs = $meterLogs ?? [];
$handoverRecords = $handoverRecords ?? [];
$roomLeads = array_values($roomLeads ?? []);
$noticeTypeOptions = $noticeTypeOptions ?? roomNoticeTypeOptions();
$issuePriorityOptions = $issuePriorityOptions ?? tenantIssuePriorityOptions();
$issueStatusOptions = $issueStatusOptions ?? tenantIssueStatusOptions();
$handoverTypeOptions = $handoverTypeOptions ?? roomHandoverTypeOptions();
$invoiceStatusOptions = roomInvoiceStatusOptions();
$serviceFee = (int)($profile['service_fee'] ?? 0);
$dueSoonInvoices = array_values(array_filter($roomInvoices ?? [], static function (array $invoice): bool {
    return (string)($invoice['reminder_state'] ?? '') === 'due_soon';
}));
$overdueInvoices = array_values(array_filter($roomInvoices ?? [], static function (array $invoice): bool {
    return (string)($invoice['reminder_state'] ?? '') === 'overdue';
}));
$activeStay = null;
foreach ($stayHistory as $stayRow) {
    if ((string)($stayRow['status'] ?? '') === 'active') {
        $activeStay = $stayRow;
        break;
    }
}
$nextNoticeDate = date('Y-m-d');
$nextBillingMonth = date('Y-m');
$nextDueDate = date('Y-m-d', strtotime('+5 days'));
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
$leadClosedStatuses = leadClosedStatuses();
$leadTotalCount = count($roomLeads);
$leadPurchasedCount = 0;
$leadContactedCount = 0;
$leadClosedCount = 0;
$leadInvalidCount = 0;
foreach ($roomLeads as $leadRow) {
    $leadStatus = (string)($leadRow['status'] ?? 'new');
    $leadPurchased = leadHasUnlockedContact($leadRow);
    if ($leadPurchased) {
        $leadPurchasedCount++;
    }
    if (in_array($leadStatus, ['contacted', 'closed', 'used'], true)) {
        $leadContactedCount++;
    }
    if (in_array($leadStatus, $leadClosedStatuses, true)) {
        $leadClosedCount++;
    }
    if ($leadStatus === 'invalid') {
        $leadInvalidCount++;
    }
}
$hasCurrentTenant = $occupancyStatus === 'occupied' && trim((string)($profile['tenant_phone'] ?? '')) !== '';
$hasContract = $contractStart !== '' || $contractEnd !== '';
$hasBilling = !empty($roomInvoices);
$openIssuesCount = 0;
$resolvedIssuesCount = 0;
foreach ($roomIssues as $issueRow) {
    if ((string)($issueRow['status'] ?? 'new') === 'resolved') {
        $resolvedIssuesCount++;
    } else {
        $openIssuesCount++;
    }
}
$hasMoveOutRecord = false;
foreach ($handoverRecords as $handoverRow) {
    if ((string)($handoverRow['handover_type'] ?? '') === 'move_out') {
        $hasMoveOutRecord = true;
        break;
    }
}
$closedStayCount = 0;
$latestSettlement = null;
foreach ($stayHistory as $stayRow) {
    if ((string)($stayRow['status'] ?? '') === 'closed') {
        $closedStayCount++;
    }
    if (
        $latestSettlement === null
        && (
            !empty($stayRow['settled_at'])
            || (int)($stayRow['deposit_deduction_amount'] ?? 0) > 0
            || (int)($stayRow['deposit_refund_amount'] ?? 0) > 0
        )
    ) {
        $latestSettlement = $stayRow;
    }
}
$opsLifecycleGroups = [
    [
        'title' => 'Trước khi thuê',
        'note' => 'Từ nhu cầu thuê tới bước chốt khách vào phòng.',
        'href' => '#ops-before-rent',
        'link_label' => 'Mở khu nhu cầu',
        'steps' => [
            [
                'label' => 'Người thuê đăng nhu cầu',
                'detail' => $leadTotalCount > 0 ? ($leadTotalCount . ' nhu cầu đã vào phòng này') : 'Chưa có nhu cầu nào cho phòng này',
                'state' => $leadTotalCount > 0 ? 'done' : 'pending',
            ],
            [
                'label' => 'Mua nhu cầu và liên hệ',
                'detail' => $leadPurchasedCount > 0
                    ? ($leadContactedCount > 0 ? ('Đã mua ' . $leadPurchasedCount . ' nhu cầu, đã liên hệ ' . $leadContactedCount) : ('Đã mua ' . $leadPurchasedCount . ' nhu cầu, chờ gọi khách'))
                    : ($leadTotalCount > 0 ? 'Có nhu cầu mới nhưng chưa mở' : 'Chưa có nhu cầu để xử lý'),
                'state' => $leadPurchasedCount > 0 ? ($leadContactedCount > 0 ? 'done' : 'active') : ($leadTotalCount > 0 ? 'active' : 'pending'),
            ],
            [
                'label' => 'Chốt thuê',
                'detail' => ($leadClosedCount > 0 || $hasCurrentTenant)
                    ? 'Đã có nhu cầu chuyển thành kỳ thuê thực tế'
                    : ($leadPurchasedCount > 0 ? 'Có thể chốt nhu cầu vào hồ sơ phòng ngay' : 'Chưa có nhu cầu đủ điều kiện để chốt'),
                'state' => ($leadClosedCount > 0 || $hasCurrentTenant) ? 'done' : ($leadPurchasedCount > 0 ? 'active' : 'pending'),
            ],
        ],
    ],
    [
        'title' => 'Trong lúc thuê',
        'note' => 'Hồ sơ phòng, hợp đồng, hóa đơn, thanh toán và xử lý sự cố.',
        'href' => '#ops-profile',
        'link_label' => 'Mở vận hành',
        'steps' => [
            [
                'label' => 'Gắn người thuê vào phòng',
                'detail' => $hasCurrentTenant ? ('Đang gắn ' . trim((string)($profile['tenant_name'] ?? 'Người thuê'))) : 'Chưa gắn người thuê vào hồ sơ',
                'state' => $hasCurrentTenant ? 'done' : 'pending',
            ],
            [
                'label' => 'Tạo hợp đồng',
                'detail' => $hasContract ? $contractStatusText : 'Chưa nhập mốc hợp đồng',
                'state' => !$hasContract ? 'pending' : (($contractDaysLeft !== null && $contractDaysLeft <= 30) ? 'warning' : 'done'),
            ],
            [
                'label' => 'Tạo hóa đơn định kỳ',
                'detail' => $hasBilling ? ('Đã có ' . count($roomInvoices) . ' hóa đơn, kỳ gần nhất ' . (string)($latestInvoice['billing_month'] ?? '')) : 'Chưa có kỳ hóa đơn nào',
                'state' => $hasBilling ? 'done' : 'pending',
            ],
            [
                'label' => 'Người thuê thanh toán',
                'detail' => !$hasBilling
                    ? 'Chưa phát sinh kỳ cần thanh toán'
                    : (count($overdueInvoices) > 0
                        ? ('Có ' . count($overdueInvoices) . ' hóa đơn quá hạn')
                        : (count($unpaidInvoices) > 0 ? ('Còn ' . count($unpaidInvoices) . ' hóa đơn chưa thanh toán') : 'Các hóa đơn hiện đã xử lý xong')),
                'state' => !$hasBilling ? 'pending' : (count($overdueInvoices) > 0 ? 'warning' : (count($unpaidInvoices) > 0 ? 'active' : 'done')),
            ],
            [
                'label' => 'Báo sự cố và xử lý',
                'detail' => $openIssuesCount > 0
                    ? ('Có ' . $openIssuesCount . ' sự cố đang mở')
                    : ($resolvedIssuesCount > 0 ? ('Đã xử lý ' . $resolvedIssuesCount . ' sự cố') : 'Chưa phát sinh sự cố nào'),
                'state' => $openIssuesCount > 0 ? 'active' : ($resolvedIssuesCount > 0 ? 'done' : 'pending'),
            ],
        ],
    ],
    [
        'title' => 'Kết thúc thuê',
        'note' => 'Trả phòng, đối soát cọc, bồi thường và lưu lịch sử thuê.',
        'href' => '#ops-checkout',
        'link_label' => 'Mở trả phòng',
        'steps' => [
            [
                'label' => 'Trả phòng',
                'detail' => ($hasMoveOutRecord || (!$activeStay && $closedStayCount > 0))
                    ? 'Đã có dấu vết trả phòng trong hệ thống'
                    : ($activeStay ? 'Phòng vẫn đang có kỳ thuê hoạt động' : 'Chưa phát sinh trả phòng'),
                'state' => ($hasMoveOutRecord || (!$activeStay && $closedStayCount > 0)) ? 'done' : ($activeStay ? 'active' : 'pending'),
            ],
            [
                'label' => 'Đối soát cọc / bồi thường',
                'detail' => $latestSettlement
                    ? ('Đã chốt hoàn ' . number_format((int)($latestSettlement['deposit_refund_amount'] ?? 0), 0, ',', '.') . ' đ, khấu trừ ' . number_format((int)($latestSettlement['deposit_deduction_amount'] ?? 0), 0, ',', '.') . ' đ')
                    : ($activeStay ? 'Chưa chốt cọc cho kỳ thuê hiện tại' : 'Chưa có dữ liệu hoàn cọc'),
                'state' => $latestSettlement ? 'done' : ($activeStay ? 'active' : 'pending'),
            ],
            [
                'label' => 'Lưu lịch sử thuê',
                'detail' => $closedStayCount > 0 ? ('Đã lưu ' . $closedStayCount . ' kỳ thuê đã kết thúc') : 'Chưa có kỳ thuê đã đóng',
                'state' => $closedStayCount > 0 ? 'done' : 'pending',
            ],
        ],
    ],
];
?>

<div class="admin-shell">
  <aside class="admin-menu">
    <a href="?route=dashboard" class="<?= ($activeMenu ?? '') === 'home' ? 'active' : '' ?>">Tổng quan</a>
    <a href="?route=my-rooms" class="<?= ($activeMenu ?? '') === 'rooms' ? 'active' : '' ?>">Vận hành trọ</a>
    <a href="?route=dashboard&tab=lead#lead" class="<?= ($activeMenu ?? '') === 'leads' ? 'active' : '' ?>">Quan tâm</a>
    <a href="?route=payment-history" class="<?= ($activeMenu ?? '') === 'payments' ? 'active' : '' ?>">Thanh toán</a>
  </aside>

  <div>
    <style>
      .ops-shell {
        display: flex;
        flex-direction: column;
        gap: 18px;
      }
      .ops-hero {
        position: relative;
        overflow: hidden;
        border-radius: 22px;
        padding: 20px;
        background:
          radial-gradient(circle at 12% 18%, rgba(255,255,255,0.26), transparent 24%),
          linear-gradient(135deg, #fff6e0 0%, #ffe8ba 48%, #ffd890 100%);
        border: 1px solid rgba(245,158,11,0.24);
        box-shadow: 0 20px 40px rgba(217,119,6,0.12);
      }
      .ops-hero::after {
        content: "";
        position: absolute;
        right: -44px;
        bottom: -72px;
        width: 210px;
        height: 210px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(217,119,6,0.12), transparent 64%);
      }
      .ops-hero-row {
        position: relative;
        z-index: 1;
        display: flex;
        justify-content: space-between;
        gap: 16px;
        align-items: flex-start;
      }
      .ops-hero h1 {
        margin: 8px 0 6px;
        font-size: clamp(24px, 3vw, 30px);
        line-height: 1.15;
        color: #431407;
        letter-spacing: -0.35px;
      }
      .ops-hero p {
        margin: 0;
        color: #7c2d12;
      }
      .ops-mini-chip {
        display: inline-flex;
        align-items: center;
        min-height: 34px;
        padding: 8px 12px;
        border-radius: 999px;
        background: rgba(255,255,255,0.76);
        border: 1px solid rgba(255,255,255,0.6);
        color: #9a3412;
        font-size: 12px;
        font-weight: 800;
      }
      .ops-hero-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 12px;
      }
      .ops-hero-actions {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        justify-content: flex-end;
      }
      .ops-hero-actions .btn {
        min-height: 42px;
      }
      .ops-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.2fr) minmax(300px, 0.8fr);
        gap: 18px;
        align-items: start;
      }
      .ops-lifecycle-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 14px;
      }
      .ops-lifecycle-group {
        border: 1px solid rgba(251,191,36,0.22);
        border-radius: 20px;
        padding: 16px;
        background: linear-gradient(180deg, rgba(255,255,255,0.98), rgba(255,248,232,0.98));
        box-shadow: 0 16px 32px rgba(15,23,42,0.06);
      }
      .ops-lifecycle-head {
        display: flex;
        justify-content: space-between;
        gap: 10px;
        align-items: flex-start;
        margin-bottom: 12px;
      }
      .ops-lifecycle-head h2 {
        margin: 0;
        font-size: 18px;
        line-height: 1.2;
      }
      .ops-lifecycle-link {
        color: #b45309;
        font-size: 13px;
        font-weight: 800;
        white-space: nowrap;
      }
      .ops-lifecycle-link:hover {
        color: #92400e;
      }
      .ops-lifecycle-note {
        margin: 4px 0 0;
        color: #6b7280;
        font-size: 13px;
        line-height: 1.5;
      }
      .ops-lifecycle-steps {
        display: grid;
        gap: 10px;
      }
      .ops-lifecycle-step {
        padding: 14px;
        border-radius: 16px;
        border: 1px solid rgba(226,232,240,0.92);
        background: #fff;
      }
      .ops-lifecycle-step.done {
        border-color: rgba(34,197,94,0.22);
        background: linear-gradient(180deg, #f0fdf4, #dcfce7);
      }
      .ops-lifecycle-step.active {
        border-color: rgba(245,158,11,0.26);
        background: linear-gradient(180deg, #fff8e8, #fff1cd);
      }
      .ops-lifecycle-step.warning {
        border-color: rgba(244,63,94,0.22);
        background: linear-gradient(180deg, #fff7ed, #ffe4e6);
      }
      .ops-lifecycle-step.pending {
        border-style: dashed;
        background: linear-gradient(180deg, #fff, #fff8ef);
      }
      .ops-lifecycle-label {
        color: #6b7280;
        font-size: 12px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.04em;
      }
      .ops-lifecycle-value {
        margin-top: 7px;
        color: #111827;
        font-size: 15px;
        font-weight: 800;
        line-height: 1.45;
      }
      .ops-lifecycle-detail {
        margin-top: 6px;
        color: #6b7280;
        font-size: 13px;
        line-height: 1.5;
      }
      .ops-main,
      .ops-side {
        display: flex;
        flex-direction: column;
        gap: 18px;
        min-width: 0;
      }
      .ops-anchor-section {
        scroll-margin-top: 96px;
      }
      .ops-card {
        background: linear-gradient(180deg, rgba(255,255,255,0.98), rgba(255,250,239,0.98));
        border: 1px solid rgba(251,191,36,0.22);
        border-radius: 20px;
        padding: 18px;
        box-shadow: 0 16px 32px rgba(15, 23, 42, 0.08);
      }
      .ops-card-head {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        align-items: flex-start;
        margin-bottom: 14px;
      }
      .ops-card-head h2 {
        margin: 0;
        font-size: 18px;
        line-height: 1.2;
      }
      .ops-card-note {
        margin: 4px 0 0;
        color: #6b7280;
        font-size: 13px;
      }
      .ops-room-list {
        display: grid;
        gap: 10px;
      }
      .ops-room-item {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        padding: 12px 0;
        border-bottom: 1px solid rgba(226,232,240,0.86);
      }
      .ops-room-item:last-child {
        border-bottom: none;
        padding-bottom: 0;
      }
      .ops-room-item strong {
        color: #111827;
        font-size: 14px;
      }
      .ops-room-item span {
        color: #6b7280;
        font-size: 13px;
      }
      .ops-kpi-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
      }
      .ops-kpi {
        padding: 14px;
        border-radius: 16px;
        border: 1px solid rgba(251,191,36,0.16);
        background: linear-gradient(180deg, #fffdf8, #fff7e7);
      }
      .ops-kpi-label {
        color: #6b7280;
        font-size: 12px;
        font-weight: 700;
      }
      .ops-kpi-value {
        margin-top: 5px;
        font-size: 24px;
        font-weight: 800;
        color: #111827;
      }
      .ops-form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px;
      }
      .ops-field {
        display: flex;
        flex-direction: column;
        gap: 6px;
      }
      .ops-field.full {
        grid-column: 1 / -1;
      }
      .ops-label {
        color: #374151;
        font-size: 13px;
        font-weight: 800;
      }
      .ops-help {
        margin: 0;
        color: #6b7280;
        font-size: 12px;
        line-height: 1.45;
      }
      .ops-form-actions {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        margin-top: 4px;
      }
      .ops-side-highlight {
        border-radius: 18px;
        padding: 16px;
        background: linear-gradient(135deg, #fbbf24, #d97706);
        color: #431407;
        box-shadow: 0 18px 32px rgba(180,83,9,0.16);
      }
      .ops-side-highlight strong {
        display: block;
        margin-bottom: 6px;
        font-size: 18px;
      }
      .ops-side-highlight p {
        margin: 0;
        color: rgba(67,20,7,0.9);
      }
      .ops-history-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
      }
      .ops-history-item {
        border: 1px solid rgba(226,232,240,0.92);
        border-radius: 18px;
        padding: 14px;
        background: #fff;
      }
      .ops-history-top {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
        align-items: center;
      }
      .ops-history-title {
        font-size: 16px;
        font-weight: 800;
        color: #111827;
      }
      .ops-status {
        display: inline-flex;
        align-items: center;
        padding: 6px 10px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 800;
        border: 1px solid transparent;
      }
      .ops-status.paid {
        color: #166534;
        background: #dcfce7;
        border-color: rgba(34,197,94,0.18);
      }
      .ops-status.unpaid {
        color: #92400e;
        background: #fff7ed;
        border-color: rgba(245,158,11,0.18);
      }
      .ops-status.overdue {
        color: #9f1239;
        background: #fff1f2;
        border-color: rgba(244,63,94,0.18);
      }
      .ops-status.due_soon {
        color: #92400e;
        background: #fef3c7;
        border-color: rgba(245,158,11,0.24);
      }
      .ops-status.cancelled {
        color: #475569;
        background: #f1f5f9;
        border-color: rgba(148,163,184,0.18);
      }
      .ops-history-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 10px;
        margin-top: 12px;
      }
      .ops-history-cell {
        padding: 10px 12px;
        border-radius: 14px;
        background: #fffaf0;
        border: 1px solid rgba(251,191,36,0.16);
      }
      .ops-history-cell small {
        display: block;
        margin-bottom: 4px;
        color: #6b7280;
      }
      .ops-history-cell strong {
        display: block;
        color: #111827;
        word-break: break-word;
      }
      .ops-history-actions {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        margin-top: 12px;
      }
      .ops-history-actions form {
        margin: 0;
      }
      .ops-item-form-grid {
        margin-top: 12px;
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 10px;
      }
      .ops-item-form-grid .full {
        grid-column: 1 / -1;
      }
      .ops-item-form-grid .btn {
        min-height: 42px;
      }
      .ops-lead-list {
        display: grid;
        gap: 12px;
      }
      .ops-lead-item {
        border: 1px solid rgba(226,232,240,0.92);
        border-radius: 18px;
        padding: 14px;
        background: #fff;
      }
      .ops-lead-top {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
        align-items: flex-start;
      }
      .ops-lead-title {
        font-size: 16px;
        font-weight: 800;
        color: #111827;
      }
      .ops-lead-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 10px;
        margin-top: 12px;
      }
      .ops-lead-cell {
        padding: 10px 12px;
        border-radius: 14px;
        background: #fffaf0;
        border: 1px solid rgba(251,191,36,0.16);
      }
      .ops-lead-cell small {
        display: block;
        margin-bottom: 4px;
        color: #6b7280;
      }
      .ops-lead-cell strong {
        display: block;
        color: #111827;
        word-break: break-word;
      }
      .ops-notice-list,
      .ops-issue-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
      }
      .ops-notice-item,
      .ops-issue-item {
        border: 1px solid rgba(226,232,240,0.92);
        border-radius: 18px;
        padding: 14px;
        background: #fff;
      }
      .ops-notice-top,
      .ops-issue-top {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 12px;
        flex-wrap: wrap;
      }
      .ops-notice-title,
      .ops-issue-title {
        font-size: 15px;
        font-weight: 800;
        color: #111827;
        line-height: 1.35;
      }
      .ops-note-copy {
        margin-top: 10px;
        color: #374151;
        font-size: 14px;
        line-height: 1.6;
        white-space: pre-line;
      }
      .ops-inline-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 10px;
      }
      .ops-inline-pill {
        display: inline-flex;
        align-items: center;
        min-height: 28px;
        padding: 6px 10px;
        border-radius: 999px;
        background: #fff7ed;
        border: 1px solid rgba(251,191,36,0.24);
        color: #9a3412;
        font-size: 12px;
        font-weight: 700;
      }
      .ops-proof-thumb {
        display: inline-flex;
        margin-top: 10px;
        border-radius: 14px;
        overflow: hidden;
        border: 1px solid rgba(226,232,240,0.92);
        background: #fffaf0;
      }
      .ops-proof-thumb img {
        display: block;
        width: 120px;
        height: 120px;
        object-fit: cover;
      }
      .ops-inline-list {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
      }
      .ops-photo-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 10px;
        margin-top: 12px;
      }
      .ops-photo-card {
        border-radius: 16px;
        overflow: hidden;
        border: 1px solid rgba(226,232,240,0.92);
        background: #fffaf0;
      }
      .ops-photo-card img {
        display: block;
        width: 100%;
        aspect-ratio: 1 / 1;
        object-fit: cover;
      }
      .ops-photo-card span {
        display: block;
        padding: 8px 10px;
        color: #6b7280;
        font-size: 12px;
        font-weight: 700;
      }
      .ops-empty {
        color: #6b7280;
        font-size: 14px;
      }
      html.browser-dark .ops-hero {
        background:
          radial-gradient(circle at 12% 18%, rgba(255,255,255,0.08), transparent 22%),
          linear-gradient(135deg, #2b2117 0%, #21180f 48%, #17120d 100%);
        border-color: rgba(245,158,11,0.16);
        box-shadow: 0 24px 42px rgba(0,0,0,0.38);
      }
      html.browser-dark .ops-hero h1,
      html.browser-dark .ops-lifecycle-head h2,
      html.browser-dark .ops-lifecycle-value,
      html.browser-dark .ops-card-head h2,
      html.browser-dark .ops-room-item strong,
      html.browser-dark .ops-kpi-value,
      html.browser-dark .ops-lead-title,
      html.browser-dark .ops-lead-cell strong,
      html.browser-dark .ops-history-title,
      html.browser-dark .ops-history-cell strong,
      html.browser-dark .ops-notice-title,
      html.browser-dark .ops-issue-title {
        color: #fff6e7;
      }
      html.browser-dark .ops-hero p,
      html.browser-dark .ops-mini-chip,
      html.browser-dark .ops-side-highlight,
      html.browser-dark .ops-side-highlight p {
        color: #f5ddbc;
      }
      html.browser-dark .ops-card,
      html.browser-dark .ops-lifecycle-group,
      html.browser-dark .ops-lifecycle-step,
      html.browser-dark .ops-lead-item,
      html.browser-dark .ops-lead-cell,
      html.browser-dark .ops-history-item,
      html.browser-dark .ops-notice-item,
      html.browser-dark .ops-issue-item,
      html.browser-dark .ops-history-cell,
      html.browser-dark .ops-kpi {
        background: linear-gradient(180deg, #1d1813, #17120d);
        border-color: #473625;
        box-shadow: 0 16px 32px rgba(0,0,0,0.34);
      }
      html.browser-dark .ops-room-item {
        border-bottom-color: rgba(71,54,37,0.86);
      }
      html.browser-dark .ops-card-note,
      html.browser-dark .ops-help,
      html.browser-dark .ops-lifecycle-note,
      html.browser-dark .ops-lifecycle-label,
      html.browser-dark .ops-lifecycle-detail,
      html.browser-dark .ops-room-item span,
      html.browser-dark .ops-kpi-label,
      html.browser-dark .ops-lead-cell small,
      html.browser-dark .ops-history-cell small,
      html.browser-dark .ops-note-copy,
      html.browser-dark .ops-inline-pill,
      html.browser-dark .ops-empty {
        color: #cdbb9f;
      }
      html.browser-dark .ops-lifecycle-link {
        color: #f7c97a;
      }
      html.browser-dark .ops-proof-thumb {
        border-color: #473625;
        background: linear-gradient(180deg, #1d1813, #17120d);
      }
      html.browser-dark .ops-photo-card {
        border-color: #473625;
        background: linear-gradient(180deg, #1d1813, #17120d);
      }
      html.browser-dark .ops-photo-card span {
        color: #cdbb9f;
      }
      @media (max-width: 960px) {
        .ops-lifecycle-grid,
        .ops-grid {
          grid-template-columns: 1fr;
        }
      }
      @media (max-width: 768px) {
        .ops-hero {
          padding: 16px 14px;
          border-radius: 18px;
        }
        .ops-hero-row {
          flex-direction: column;
          align-items: stretch;
        }
        .ops-lifecycle-head {
          flex-direction: column;
        }
        .ops-hero-actions {
          justify-content: stretch;
        }
        .ops-hero-actions .btn {
          flex: 1 1 auto;
          justify-content: center;
        }
        .ops-card {
          padding: 16px 14px;
          border-radius: 16px;
        }
        .ops-form-grid,
        .ops-kpi-grid,
        .ops-inline-list,
        .ops-lead-grid,
        .ops-photo-grid,
        .ops-history-grid,
        .ops-item-form-grid {
          grid-template-columns: 1fr;
        }
        .ops-room-item {
          flex-direction: column;
        }
      }
    </style>

    <div class="ops-shell">
      <section class="ops-hero">
        <div class="ops-hero-row">
          <div style="min-width:0;">
            <span class="ops-mini-chip">Hồ sơ vận hành phòng</span>
            <h1>#<?= (int)$room['id'] ?> · <?= htmlspecialchars((string)$room['title']) ?></h1>
            <p><?= htmlspecialchars((string)($room['address'] ?? '')) ?></p>
            <div class="ops-hero-meta">
              <span class="ops-mini-chip"><?= htmlspecialchars($occupancyLabel) ?></span>
              <span class="ops-mini-chip"><?= htmlspecialchars($conditionLabel) ?></span>
              <span class="ops-mini-chip"><?= !empty($profile['tenant_name']) ? ('Người thuê: ' . htmlspecialchars((string)$profile['tenant_name'])) : 'Chưa gắn người thuê' ?></span>
            </div>
          </div>
          <div class="ops-hero-actions">
            <a class="btn btn-outline" href="?route=room-edit&id=<?= (int)$room['id'] ?>">Sửa bài đăng</a>
            <a class="btn btn-outline" href="?route=room&id=<?= (int)$room['id'] ?>" target="_blank" rel="noopener">Xem tin công khai</a>
          </div>
        </div>
      </section>

      <section class="ops-lifecycle-grid">
        <?php foreach ($opsLifecycleGroups as $group): ?>
          <article class="ops-lifecycle-group">
            <div class="ops-lifecycle-head">
              <div>
                <h2><?= htmlspecialchars((string)$group['title']) ?></h2>
                <p class="ops-lifecycle-note"><?= htmlspecialchars((string)$group['note']) ?></p>
              </div>
              <a class="ops-lifecycle-link" href="<?= htmlspecialchars((string)$group['href']) ?>"><?= htmlspecialchars((string)$group['link_label']) ?></a>
            </div>
            <div class="ops-lifecycle-steps">
              <?php foreach ($group['steps'] as $step): ?>
                <article class="ops-lifecycle-step <?= htmlspecialchars((string)$step['state']) ?>">
                  <div class="ops-lifecycle-label"><?= htmlspecialchars((string)$step['label']) ?></div>
                  <div class="ops-lifecycle-value"><?= htmlspecialchars((string)$step['detail']) ?></div>
                </article>
              <?php endforeach; ?>
            </div>
          </article>
        <?php endforeach; ?>
      </section>

      <div class="ops-grid">
        <div class="ops-main">
          <section class="ops-card ops-anchor-section" id="ops-before-rent">
            <div class="ops-card-head">
              <div>
                <h2>Nhu cầu và bước chốt thuê</h2>
                <p class="ops-card-note">Đây là đoạn nối giữa “người thuê đăng nhu cầu” và “gắn người thuê vào phòng”. Chốt nhu cầu ở đây sẽ kéo luôn người thuê vào hồ sơ vận hành.</p>
              </div>
            </div>

            <div class="ops-inline-meta" style="margin-top:0;">
              <span class="ops-inline-pill"><?= $leadTotalCount ?> nhu cầu cho phòng này</span>
              <span class="ops-inline-pill"><?= $leadPurchasedCount ?> nhu cầu đã mua</span>
              <span class="ops-inline-pill"><?= $leadClosedCount ?> nhu cầu đã chốt</span>
              <?php if ($leadInvalidCount > 0): ?>
                <span class="ops-inline-pill"><?= $leadInvalidCount ?> nhu cầu lỗi</span>
              <?php endif; ?>
            </div>

            <div class="ops-lead-list" style="margin-top:14px;">
              <?php if (empty($roomLeads)): ?>
                <div class="ops-empty">Chưa có nhu cầu nào đi vào phòng này. Khi người thuê đăng nhu cầu và quan tâm phòng, bước trước thuê sẽ hiện ở đây.</div>
              <?php endif; ?>
              <?php foreach (array_slice($roomLeads, 0, 5) as $lead): ?>
                <?php
                  $leadStatus = (string)($lead['status'] ?? 'new');
                  $leadStatusLabel = [
                      'new' => 'Nhu cầu mới',
                      'opened' => 'Đã mua',
                      'contacted' => 'Đã liên hệ',
                      'closed' => 'Đã chốt thuê',
                      'invalid' => 'Nhu cầu lỗi',
                      'used' => 'Đã dùng',
                      'paid' => 'Đã mua',
                  ][$leadStatus] ?? $leadStatus;
                  $leadPurchased = leadHasUnlockedContact($lead);
                  $leadClosed = in_array($leadStatus, $leadClosedStatuses, true);
                  $displayName = $leadPurchased ? (string)($lead['tenant_name'] ?? '') : maskName((string)($lead['tenant_name'] ?? 'Khách'));
                  $displayPhone = $leadPurchased ? (string)($lead['tenant_phone'] ?? '') : maskPhone((string)($lead['tenant_phone'] ?? ''));
                ?>
                <article class="ops-lead-item">
                  <div class="ops-lead-top">
                    <div>
                      <div class="ops-lead-title"><?= htmlspecialchars($displayName !== '' ? $displayName : 'Khách quan tâm') ?></div>
                      <div class="ops-card-note">Nhu cầu #<?= (int)$lead['id'] ?> · Gửi lúc <?= htmlspecialchars((string)($lead['created_at'] ?? '')) ?></div>
                    </div>
                    <span class="ops-status <?= htmlspecialchars($leadClosed ? 'paid' : ($leadPurchased ? 'due_soon' : ($leadStatus === 'invalid' ? 'overdue' : 'unpaid'))) ?>">
                      <?= htmlspecialchars($leadStatusLabel) ?>
                    </span>
                  </div>

                  <div class="ops-lead-grid">
                    <div class="ops-lead-cell">
                      <small>Số điện thoại</small>
                      <strong><?= htmlspecialchars($displayPhone !== '' ? $displayPhone : 'Chưa có') ?></strong>
                    </div>
                    <div class="ops-lead-cell">
                      <small>Mức độ phù hợp</small>
                      <strong><?= htmlspecialchars((string)($lead['match_label'] ?? '—')) ?></strong>
                    </div>
                    <div class="ops-lead-cell">
                      <small>Giá mở nhu cầu</small>
                      <strong><?= number_format((int)effectiveLeadPriceFromRow($lead), 0, ',', '.') ?> đ</strong>
                    </div>
                  </div>

                  <div class="ops-history-actions">
                    <?php if (!$leadPurchased && $leadStatus !== 'invalid'): ?>
                      <a class="btn btn-outline btn-sm" href="?route=dashboard&tab=lead#lead">Mua nhu cầu ở khu nhu cầu</a>
                    <?php elseif ($leadPurchased && !$leadClosed): ?>
                      <form method="post" action="?route=room-ops">
                        <input type="hidden" name="room_id" value="<?= (int)$room['id'] ?>">
                        <input type="hidden" name="action" value="convert-room-lead">
                        <input type="hidden" name="lead_id" value="<?= (int)$lead['id'] ?>">
                        <button class="btn btn-primary btn-sm" type="submit">Chốt thuê từ nhu cầu này</button>
                      </form>
                    <?php elseif ($leadClosed): ?>
                      <span class="ops-inline-pill">Nhu cầu này đã được chốt vào hồ sơ thuê</span>
                    <?php else: ?>
                      <span class="ops-inline-pill">Nhu cầu đã bị đánh dấu lỗi</span>
                    <?php endif; ?>
                  </div>
                </article>
              <?php endforeach; ?>
            </div>
          </section>

          <section class="ops-card ops-anchor-section" id="ops-profile">
            <div class="ops-card-head">
              <div>
                <h2>Hồ sơ vận hành</h2>
                <p class="ops-card-note">Từ đây phòng không chỉ là bài đăng, mà là hồ sơ quản lý thực tế.</p>
              </div>
            </div>

            <form method="post" action="?route=room-ops" class="ops-form-grid">
              <input type="hidden" name="room_id" value="<?= (int)$room['id'] ?>">
              <input type="hidden" name="action" value="save-room-ops">

              <div class="ops-field">
                <label class="ops-label" for="opsOccupancyStatus">Tình trạng phòng</label>
                <select id="opsOccupancyStatus" name="occupancy_status" class="form-control">
                  <?php foreach ($occupancyOptions as $value => $label): ?>
                    <option value="<?= htmlspecialchars($value) ?>" <?= $occupancyStatus === $value ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="ops-field">
                <label class="ops-label" for="opsRoomCondition">Tình trạng kỹ thuật</label>
                <select id="opsRoomCondition" name="room_condition" class="form-control">
                  <?php foreach ($conditionOptions as $value => $label): ?>
                    <option value="<?= htmlspecialchars($value) ?>" <?= $roomCondition === $value ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="ops-field">
                <label class="ops-label" for="opsTenantName">Người đang thuê</label>
                <input id="opsTenantName" type="text" name="tenant_name" class="form-control" value="<?= htmlspecialchars((string)($profile['tenant_name'] ?? '')) ?>" placeholder="Ví dụ: Nguyễn Văn A">
              </div>

              <div class="ops-field">
                <label class="ops-label" for="opsTenantPhone">SĐT người thuê</label>
                <input id="opsTenantPhone" type="tel" name="tenant_phone" class="form-control" value="<?= htmlspecialchars((string)($profile['tenant_phone'] ?? '')) ?>" placeholder="0912345678">
                <p class="ops-help">Người thuê đăng nhập bằng đúng số này sẽ thấy phòng, hóa đơn, thông báo và khu báo sự cố của họ.</p>
              </div>

              <div class="ops-field">
                <label class="ops-label" for="opsMonthlyRent">Tiền thuê thực thu</label>
                <input id="opsMonthlyRent" type="number" name="monthly_rent" class="form-control" min="0" step="1000" value="<?= htmlspecialchars((string)($profile['monthly_rent'] ?? $room['price'] ?? '')) ?>">
              </div>

              <div class="ops-field">
                <label class="ops-label" for="opsDepositAmount">Tiền cọc</label>
                <input id="opsDepositAmount" type="number" name="deposit_amount" class="form-control" min="0" step="1000" value="<?= htmlspecialchars((string)($profile['deposit_amount'] ?? '')) ?>">
              </div>

              <div class="ops-field">
                <label class="ops-label" for="opsServiceFee">Phí dịch vụ cố định</label>
                <input id="opsServiceFee" type="number" name="service_fee" class="form-control" min="0" step="1000" value="<?= htmlspecialchars((string)($profile['service_fee'] ?? 0)) ?>">
              </div>

              <div class="ops-field">
                <label class="ops-label" for="opsContractStart">Ngày bắt đầu hợp đồng</label>
                <input id="opsContractStart" type="date" name="contract_start" class="form-control" value="<?= htmlspecialchars((string)($profile['contract_start'] ?? '')) ?>">
              </div>

              <div class="ops-field">
                <label class="ops-label" for="opsContractEnd">Ngày hết hạn hợp đồng</label>
                <input id="opsContractEnd" type="date" name="contract_end" class="form-control" value="<?= htmlspecialchars((string)($profile['contract_end'] ?? '')) ?>">
              </div>

              <div class="ops-field">
                <label class="ops-label" for="opsElectricMeter">Công tơ điện hiện tại</label>
                <input id="opsElectricMeter" type="number" name="electric_meter_reading" class="form-control" min="0" step="1" value="<?= htmlspecialchars((string)($profile['electric_meter_reading'] ?? '')) ?>">
              </div>

              <div class="ops-field">
                <label class="ops-label" for="opsWaterMeter">Công tơ nước hiện tại</label>
                <input id="opsWaterMeter" type="number" name="water_meter_reading" class="form-control" min="0" step="1" value="<?= htmlspecialchars((string)($profile['water_meter_reading'] ?? '')) ?>">
              </div>

              <div class="ops-field full">
                <label class="ops-label" for="opsIssueNote">Ghi chú sự cố</label>
                <textarea id="opsIssueNote" name="issue_note" class="form-control" rows="3" placeholder="Ví dụ: Máy lạnh yếu, cần kiểm tra đường ống nước."><?= htmlspecialchars((string)($profile['issue_note'] ?? '')) ?></textarea>
              </div>

              <div class="ops-field full">
                <label class="ops-label" for="opsOperationNote">Ghi chú vận hành</label>
                <textarea id="opsOperationNote" name="operation_note" class="form-control" rows="3" placeholder="Các lưu ý nội bộ về phòng, lịch hẹn, tình trạng thanh toán..."><?= htmlspecialchars((string)($profile['operation_note'] ?? '')) ?></textarea>
                <p class="ops-help">Chỗ này dùng như sổ tay vận hành của riêng chủ trọ.</p>
              </div>

              <div class="ops-field full">
                <div class="ops-form-actions">
                  <button class="btn btn-primary" type="submit">Lưu hồ sơ vận hành</button>
                  <a class="btn btn-outline" href="?route=my-rooms">Quay lại danh sách phòng</a>
                </div>
              </div>
            </form>
          </section>

          <section class="ops-card ops-anchor-section" id="ops-billing">
            <div class="ops-card-head">
              <div>
                <h2>Tạo hoá đơn tháng</h2>
                <p class="ops-card-note">Chỉ cần nhập số điện, số nước mới. Tiền phòng và phí dịch vụ sẽ tự lấy từ hồ sơ vận hành.</p>
              </div>
            </div>

            <form method="post" action="?route=room-ops" class="ops-form-grid">
              <input type="hidden" name="room_id" value="<?= (int)$room['id'] ?>">
              <input type="hidden" name="action" value="create-room-invoice">
              <input type="hidden" name="rent_amount" value="<?= htmlspecialchars((string)($profile['monthly_rent'] ?? $room['price'] ?? 0)) ?>">
              <input type="hidden" name="service_amount" value="<?= htmlspecialchars((string)$serviceFee) ?>">

              <div class="ops-field">
                <label class="ops-label" for="invoiceBillingMonth">Tháng hoá đơn</label>
                <input id="invoiceBillingMonth" type="month" name="billing_month" class="form-control" value="<?= htmlspecialchars($nextBillingMonth) ?>" required>
              </div>

              <div class="ops-field full">
                <label class="ops-label">Khoản cố định sẽ tự tính</label>
                <div class="ops-inline-list">
                  <div class="ops-history-cell">
                    <small>Tiền phòng</small>
                    <strong><?= number_format((int)($profile['monthly_rent'] ?? $room['price'] ?? 0), 0, ',', '.') ?> đ</strong>
                  </div>
                  <div class="ops-history-cell">
                    <small>Phí dịch vụ</small>
                    <strong><?= number_format($serviceFee, 0, ',', '.') ?> đ</strong>
                  </div>
                </div>
              </div>

              <div class="ops-field">
                <label class="ops-label" for="invoiceElectricNew">Chỉ số điện mới</label>
                <input id="invoiceElectricNew" type="number" name="electric_reading_new" class="form-control" min="<?= htmlspecialchars((string)($profile['electric_meter_reading'] ?? 0)) ?>" step="1" value="">
                <p class="ops-help">Chỉ số cũ: <?= $profile['electric_meter_reading'] !== null ? (int)$profile['electric_meter_reading'] : 'chưa có' ?> · Giá điện: <?= !empty($room['electric_price']) ? number_format((int)$room['electric_price'], 0, ',', '.') . ' đ/kWh' : 'chưa khai báo' ?></p>
              </div>

              <div class="ops-field">
                <label class="ops-label" for="invoiceWaterNew">Chỉ số nước mới</label>
                <input id="invoiceWaterNew" type="number" name="water_reading_new" class="form-control" min="<?= htmlspecialchars((string)($profile['water_meter_reading'] ?? 0)) ?>" step="1" value="">
                <p class="ops-help">Chỉ số cũ: <?= $profile['water_meter_reading'] !== null ? (int)$profile['water_meter_reading'] : 'chưa có' ?> · Giá nước: <?= !empty($room['water_price']) ? number_format((int)$room['water_price'], 0, ',', '.') . ' đ/m³' : 'chưa khai báo' ?></p>
              </div>

              <div class="ops-field">
                <label class="ops-label" for="invoiceOtherAmount">Phát sinh thêm</label>
                <input id="invoiceOtherAmount" type="number" name="other_amount" class="form-control" min="0" step="1000" value="0">
              </div>

              <div class="ops-field">
                <label class="ops-label" for="invoiceDueDate">Hạn thanh toán</label>
                <input id="invoiceDueDate" type="date" name="due_date" class="form-control" value="<?= htmlspecialchars($nextDueDate) ?>">
              </div>

              <div class="ops-field full">
                <label class="ops-label" for="invoiceNote">Ghi chú hoá đơn</label>
                <textarea id="invoiceNote" name="invoice_note" class="form-control" rows="3" placeholder="Ví dụ: Tháng này phát sinh thay vòi nước 150.000đ."></textarea>
              </div>

              <div class="ops-field full">
                <div class="ops-form-actions">
                  <button class="btn btn-primary" type="submit">Tạo hoá đơn</button>
                </div>
              </div>
            </form>
          </section>

          <section class="ops-card ops-anchor-section" id="ops-notices">
            <div class="ops-card-head">
              <div>
                <h2>Thông báo cho người thuê</h2>
                <p class="ops-card-note">Đưa nhắc thanh toán, điều chỉnh phí, lịch cắt điện nước hoặc nội quy vào khu “Không gian thuê trọ của tôi”.</p>
              </div>
            </div>

            <form method="post" action="?route=room-ops" class="ops-form-grid">
              <input type="hidden" name="room_id" value="<?= (int)$room['id'] ?>">
              <input type="hidden" name="action" value="create-room-notice">

              <div class="ops-field">
                <label class="ops-label" for="noticeType">Loại thông báo</label>
                <select id="noticeType" name="notice_type" class="form-control">
                  <?php foreach ($noticeTypeOptions as $value => $label): ?>
                    <option value="<?= htmlspecialchars($value) ?>"><?= htmlspecialchars($label) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="ops-field">
                <label class="ops-label" for="noticeEffectiveDate">Ngày hiệu lực</label>
                <input id="noticeEffectiveDate" type="date" name="notice_effective_date" class="form-control" value="<?= htmlspecialchars($nextNoticeDate) ?>">
              </div>

              <div class="ops-field full">
                <label class="ops-label" for="noticeTitle">Tiêu đề</label>
                <input id="noticeTitle" type="text" name="notice_title" class="form-control" placeholder="Ví dụ: Hóa đơn tháng này cần thanh toán trước ngày 05.">
              </div>

              <div class="ops-field full">
                <label class="ops-label" for="noticeContent">Nội dung</label>
                <textarea id="noticeContent" name="notice_content" class="form-control" rows="4" placeholder="Viết rõ việc cần làm, mốc thời gian, khoản phí điều chỉnh hoặc lưu ý điện nước."></textarea>
              </div>

              <div class="ops-field full">
                <div class="ops-form-actions">
                  <button class="btn btn-primary" type="submit">Gửi thông báo</button>
                </div>
              </div>
            </form>

            <div class="ops-notice-list" style="margin-top:16px;">
              <?php if (empty($roomNotices)): ?>
                <div class="ops-empty">Chưa có thông báo nào được gửi xuống cho người thuê.</div>
              <?php endif; ?>
              <?php foreach ($roomNotices as $notice): ?>
                <?php $noticeTypeLabel = $noticeTypeOptions[$notice['notice_type'] ?? 'general'] ?? ($notice['notice_type'] ?? 'Thông báo'); ?>
                <article class="ops-notice-item">
                  <div class="ops-notice-top">
                    <div>
                      <div class="ops-notice-title"><?= htmlspecialchars((string)($notice['title'] ?? 'Thông báo')) ?></div>
                      <div class="ops-card-note">Đăng lúc <?= htmlspecialchars((string)($notice['created_at'] ?? '')) ?></div>
                    </div>
                    <span class="ops-status unpaid"><?= htmlspecialchars((string)$noticeTypeLabel) ?></span>
                  </div>
                  <div class="ops-note-copy"><?= nl2br(htmlspecialchars((string)($notice['content'] ?? ''))) ?></div>
                  <div class="ops-inline-meta">
                    <?php if (!empty($notice['effective_date'])): ?>
                      <span class="ops-inline-pill">Hiệu lực: <?= htmlspecialchars((string)$notice['effective_date']) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($notice['landlord_name'])): ?>
                      <span class="ops-inline-pill">Từ: <?= htmlspecialchars((string)$notice['landlord_name']) ?></span>
                    <?php endif; ?>
                  </div>
                </article>
              <?php endforeach; ?>
            </div>
          </section>

          <section class="ops-card ops-anchor-section" id="ops-issues">
            <div class="ops-card-head">
              <div>
                <h2>Sự cố người thuê đã gửi</h2>
                <p class="ops-card-note">Theo dõi báo hỏng, ảnh minh chứng và đánh dấu tiến độ xử lý ngay tại đây.</p>
              </div>
            </div>

            <div class="ops-issue-list">
              <?php if (empty($roomIssues)): ?>
                <div class="ops-empty">Chưa có báo sự cố nào từ người thuê.</div>
              <?php endif; ?>
              <?php foreach ($roomIssues as $issue): ?>
                <?php
                  $issuePriorityLabel = $issuePriorityOptions[$issue['priority'] ?? 'normal'] ?? ($issue['priority'] ?? 'Bình thường');
                  $issueStatus = (string)($issue['status'] ?? 'open');
                  $issueStatusLabel = $issueStatusOptions[$issueStatus] ?? $issueStatus;
                  $issueStatusClass = $issueStatusClassMap[$issueStatus] ?? 'overdue';
                ?>
                <article class="ops-issue-item">
                  <div class="ops-issue-top">
                    <div>
                      <div class="ops-issue-title"><?= htmlspecialchars((string)($issue['tenant_name'] ?? 'Người thuê')) ?> báo sự cố</div>
                      <div class="ops-card-note">Gửi lúc <?= htmlspecialchars((string)($issue['created_at'] ?? '')) ?></div>
                    </div>
                    <span class="ops-status <?= htmlspecialchars($issueStatusClass) ?>">
                      <?= htmlspecialchars((string)$issueStatusLabel) ?>
                    </span>
                  </div>

                  <div class="ops-inline-meta">
                    <span class="ops-inline-pill">Ưu tiên: <?= htmlspecialchars((string)$issuePriorityLabel) ?></span>
                    <span class="ops-inline-pill">SĐT: <?= htmlspecialchars((string)($issue['tenant_phone'] ?? '')) ?></span>
                    <?php if ((int)($issue['repair_cost'] ?? 0) > 0): ?>
                      <span class="ops-inline-pill">Chi phí sửa: <?= number_format((int)($issue['repair_cost'] ?? 0), 0, ',', '.') ?> đ</span>
                    <?php endif; ?>
                  </div>

                  <div class="ops-note-copy"><?= nl2br(htmlspecialchars((string)($issue['content'] ?? ''))) ?></div>

                  <?php if (!empty($issue['image_path'])): ?>
                    <a class="ops-proof-thumb" href="<?= htmlspecialchars(assetUrl((string)$issue['image_path'])) ?>" target="_blank" rel="noopener">
                      <img src="<?= htmlspecialchars(assetUrl((string)$issue['image_path'])) ?>" alt="Minh chứng sự cố">
                    </a>
                  <?php endif; ?>

                  <?php if (!empty($issue['landlord_note'])): ?>
                    <div class="ops-card-note" style="margin-top:10px;">Ghi chú xử lý: <?= nl2br(htmlspecialchars((string)$issue['landlord_note'])) ?></div>
                  <?php endif; ?>

                  <form method="post" action="?route=room-ops" class="ops-item-form-grid">
                    <input type="hidden" name="room_id" value="<?= (int)$room['id'] ?>">
                    <input type="hidden" name="action" value="update-tenant-issue">
                    <input type="hidden" name="issue_id" value="<?= (int)$issue['id'] ?>">

                    <div class="ops-field">
                      <label class="ops-label">Trạng thái phiếu</label>
                      <select name="issue_status" class="form-control">
                        <?php foreach ($issueStatusOptions as $value => $label): ?>
                          <option value="<?= htmlspecialchars($value) ?>" <?= $issueStatus === $value ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>

                    <div class="ops-field">
                      <label class="ops-label">Chi phí sửa chữa</label>
                      <input type="number" name="repair_cost" class="form-control" min="0" step="1000" value="<?= htmlspecialchars((string)($issue['repair_cost'] ?? 0)) ?>">
                    </div>

                    <div class="ops-field full">
                      <label class="ops-label">Ghi chú nội bộ / phản hồi</label>
                      <textarea name="landlord_note" class="form-control" rows="3" placeholder="Ví dụ: Đã gọi thợ, chờ thay linh kiện vào chiều mai."><?= htmlspecialchars((string)($issue['landlord_note'] ?? '')) ?></textarea>
                    </div>

                    <div class="ops-field full">
                      <div class="ops-form-actions">
                        <button class="btn btn-primary btn-sm" type="submit">Cập nhật phiếu</button>
                      </div>
                    </div>
                  </form>
                </article>
              <?php endforeach; ?>
            </div>
          </section>
        </div>

        <aside class="ops-side">
          <section class="ops-side-highlight">
            <strong>Việc cần xử lý</strong>
            <p>
              <?=
                count($overdueInvoices) > 0
                  ? ('Có ' . count($overdueInvoices) . ' hoá đơn đã quá hạn và ' . count($dueSoonInvoices) . ' hoá đơn gần đến hạn.')
                  : (count($dueSoonInvoices) > 0
                      ? ('Có ' . count($dueSoonInvoices) . ' hoá đơn gần đến hạn, nên nhắc nhẹ người thuê.')
                      : (count($unpaidInvoices) > 0
                          ? ('Hiện có ' . count($unpaidInvoices) . ' hoá đơn chưa thanh toán.')
                          : 'Chưa có hoá đơn tồn, có thể tiếp tục theo dõi hợp đồng và công tơ.'))
              ?>
            </p>
          </section>

          <section class="ops-card">
            <div class="ops-card-head">
              <div>
                <h2>Tóm tắt vận hành</h2>
                <p class="ops-card-note">Các chỉ số cần nhìn nhanh trước khi gọi khách hoặc chốt thu tiền.</p>
              </div>
            </div>

            <div class="ops-kpi-grid">
              <div class="ops-kpi">
                <div class="ops-kpi-label">Tiền thuê tháng</div>
                <div class="ops-kpi-value"><?= number_format((int)($profile['monthly_rent'] ?? $room['price'] ?? 0), 0, ',', '.') ?>đ</div>
              </div>
              <div class="ops-kpi">
                <div class="ops-kpi-label">Tiền cọc</div>
                <div class="ops-kpi-value"><?= number_format((int)($profile['deposit_amount'] ?? 0), 0, ',', '.') ?>đ</div>
              </div>
              <div class="ops-kpi">
                <div class="ops-kpi-label">Phí dịch vụ</div>
                <div class="ops-kpi-value"><?= number_format($serviceFee, 0, ',', '.') ?>đ</div>
              </div>
              <div class="ops-kpi">
                <div class="ops-kpi-label">Hoá đơn chưa thanh toán</div>
                <div class="ops-kpi-value">
                  <?= count($unpaidInvoices) ?>
                </div>
              </div>
              <div class="ops-kpi">
                <div class="ops-kpi-label">Hợp đồng còn lại</div>
                <div class="ops-kpi-value">
                  <?php if ($contractDaysLeft === null): ?>
                    —
                  <?php else: ?>
                    <?= $contractDaysLeft ?> ngày
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </section>

          <section class="ops-card">
            <div class="ops-card-head">
              <div>
                <h2>Thông tin bài đăng</h2>
                <p class="ops-card-note">Liên kết giữa hồ sơ vận hành và tin công khai.</p>
              </div>
            </div>

            <div class="ops-room-list">
              <div class="ops-room-item">
                <div>
                  <strong>Giá niêm yết</strong>
                  <span><?= number_format((int)($room['price'] ?? 0), 0, ',', '.') ?> đ/tháng</span>
                </div>
              </div>
              <div class="ops-room-item">
                <div>
                  <strong>Điện / nước</strong>
                  <span>
                    Điện: <?= !empty($room['electric_price']) ? number_format((int)$room['electric_price'], 0, ',', '.') . ' đ/kWh' : 'chưa có' ?><br>
                    Nước: <?= !empty($room['water_price']) ? number_format((int)$room['water_price'], 0, ',', '.') . ' đ/m³' : 'chưa có' ?>
                  </span>
                </div>
              </div>
              <div class="ops-room-item">
                <div>
                  <strong>Phí dịch vụ cố định</strong>
                  <span><?= number_format($serviceFee, 0, ',', '.') ?> đ/tháng</span>
                </div>
              </div>
              <div class="ops-room-item">
                <div>
                  <strong>Nhu cầu 24h gần nhất</strong>
                  <span><?= countRoomLeadsRecent((int)$room['id']) ?> người quan tâm</span>
                </div>
              </div>
              <div class="ops-room-item">
                <div>
                  <strong>Bài đăng hiện tại</strong>
                  <span><?= htmlspecialchars((string)($room['status'] ?? 'pending')) ?></span>
                </div>
              </div>
            </div>
          </section>
        </aside>
      </div>

      <section class="ops-card ops-anchor-section" id="ops-invoice-history">
        <div class="ops-card-head">
          <div>
            <h2>Lịch sử hoá đơn</h2>
            <p class="ops-card-note">Theo dõi từng tháng, chỉ số điện nước và trạng thái thanh toán.</p>
          </div>
        </div>

        <?php if (empty($roomInvoices)): ?>
          <div class="ops-empty">Chưa có hóa đơn nào cho phòng này.</div>
        <?php else: ?>
          <div class="ops-history-list">
            <?php foreach ($roomInvoices as $invoice): ?>
              <?php
                $displayStatus = (string)($invoice['display_status'] ?? 'unpaid');
                $invoiceStatusLabel = $invoiceStatusLabels[$displayStatus] ?? $displayStatus;
                $reminderState = (string)($invoice['reminder_state'] ?? 'unpaid');
                $statusClass = $invoiceStatusClassMap[$displayStatus] ?? 'unpaid';
              ?>
              <article class="ops-history-item">
                <div class="ops-history-top">
                  <div>
                    <div class="ops-history-title">Hoá đơn <?= htmlspecialchars((string)$invoice['billing_month']) ?></div>
                    <div class="ops-card-note">Tạo lúc <?= htmlspecialchars((string)($invoice['created_at'] ?? '')) ?></div>
                  </div>
                  <div style="display:flex; gap:8px; flex-wrap:wrap;">
                    <span class="ops-status <?= htmlspecialchars($statusClass) ?>"><?= htmlspecialchars($invoiceStatusLabel) ?></span>
                    <?php if ($reminderState === 'due_soon' || $reminderState === 'overdue'): ?>
                      <span class="ops-status <?= htmlspecialchars($reminderState) ?>"><?= htmlspecialchars($invoiceReminderLabels[$reminderState] ?? $reminderState) ?></span>
                    <?php endif; ?>
                  </div>
                </div>

                <div class="ops-history-grid">
                  <div class="ops-history-cell">
                    <small>Tổng tiền</small>
                    <strong><?= number_format((int)($invoice['total_amount'] ?? 0), 0, ',', '.') ?> đ</strong>
                  </div>
                  <div class="ops-history-cell">
                    <small>Tiền thuê</small>
                    <strong><?= number_format((int)($invoice['rent_amount'] ?? 0), 0, ',', '.') ?> đ</strong>
                  </div>
                  <div class="ops-history-cell">
                    <small>Phí dịch vụ</small>
                    <strong><?= number_format((int)($invoice['service_amount'] ?? 0), 0, ',', '.') ?> đ</strong>
                  </div>
                  <div class="ops-history-cell">
                    <small>Điện</small>
                    <strong>
                      <?= ($invoice['electric_units'] ?? null) !== null ? ((int)$invoice['electric_units'] . ' số · ' . number_format((int)($invoice['electric_amount'] ?? 0), 0, ',', '.') . ' đ') : '—' ?>
                    </strong>
                  </div>
                  <div class="ops-history-cell">
                    <small>Nước</small>
                    <strong>
                      <?= ($invoice['water_units'] ?? null) !== null ? ((int)$invoice['water_units'] . ' số · ' . number_format((int)($invoice['water_amount'] ?? 0), 0, ',', '.') . ' đ') : '—' ?>
                    </strong>
                  </div>
                  <div class="ops-history-cell">
                    <small>Hạn thanh toán</small>
                    <strong><?= !empty($invoice['due_date']) ? htmlspecialchars((string)$invoice['due_date']) : '—' ?></strong>
                  </div>
                  <div class="ops-history-cell">
                    <small>Đã thu / còn thiếu</small>
                    <strong><?= number_format((int)($invoice['amount_paid'] ?? 0), 0, ',', '.') ?> đ / <?= number_format((int)($invoice['amount_due'] ?? 0), 0, ',', '.') ?> đ</strong>
                  </div>
                  <div class="ops-history-cell">
                    <small>Ngày thanh toán</small>
                    <strong><?= !empty($invoice['paid_date']) ? htmlspecialchars((string)$invoice['paid_date']) : '—' ?></strong>
                  </div>
                  <div class="ops-history-cell">
                    <small>Phương thức thu</small>
                    <strong><?= !empty($invoice['payment_method']) ? htmlspecialchars((string)$invoice['payment_method']) : '—' ?></strong>
                  </div>
                  <div class="ops-history-cell">
                    <small>Điện cũ → mới</small>
                    <strong><?= ($invoice['electric_reading_old'] ?? null) !== null || ($invoice['electric_reading_new'] ?? null) !== null ? ((string)($invoice['electric_reading_old'] ?? '—') . ' → ' . (string)($invoice['electric_reading_new'] ?? '—')) : '—' ?></strong>
                  </div>
                  <div class="ops-history-cell">
                    <small>Nước cũ → mới</small>
                    <strong><?= ($invoice['water_reading_old'] ?? null) !== null || ($invoice['water_reading_new'] ?? null) !== null ? ((string)($invoice['water_reading_old'] ?? '—') . ' → ' . (string)($invoice['water_reading_new'] ?? '—')) : '—' ?></strong>
                  </div>
                </div>

                <?php if (!empty($invoice['note'])): ?>
                  <div class="ops-card-note" style="margin-top:10px;"><?= nl2br(htmlspecialchars((string)$invoice['note'])) ?></div>
                <?php endif; ?>

                <form method="post" action="?route=room-ops" class="ops-item-form-grid">
                  <input type="hidden" name="room_id" value="<?= (int)$room['id'] ?>">
                  <input type="hidden" name="action" value="mark-room-invoice">
                  <input type="hidden" name="invoice_id" value="<?= (int)$invoice['id'] ?>">

                  <div class="ops-field">
                    <label class="ops-label">Trạng thái hóa đơn</label>
                    <select name="invoice_status" class="form-control">
                      <?php foreach ($invoiceStatusOptions as $value => $label): ?>
                        <option value="<?= htmlspecialchars($value) ?>" <?= $displayStatus === $value ? 'selected' : '' ?>><?= htmlspecialchars($invoiceStatusLabels[$value] ?? $label) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>

                  <div class="ops-field">
                    <label class="ops-label">Số tiền đã thu</label>
                    <input type="number" name="amount_paid" class="form-control" min="0" step="1000" value="<?= htmlspecialchars((string)($invoice['amount_paid'] ?? 0)) ?>">
                  </div>

                  <div class="ops-field">
                    <label class="ops-label">Phương thức thu</label>
                    <input type="text" name="payment_method" class="form-control" value="<?= htmlspecialchars((string)($invoice['payment_method'] ?? '')) ?>" placeholder="Ví dụ: Chuyển khoản, tiền mặt">
                  </div>

                  <div class="ops-field full">
                    <div class="ops-form-actions">
                      <button class="btn btn-primary btn-sm" type="submit">Cập nhật hóa đơn</button>
                    </div>
                  </div>
                </form>
              </article>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </section>

      <section class="ops-card ops-anchor-section" id="ops-checkout">
        <div class="ops-card-head">
          <div>
            <h2>Lịch sử thuê và hoàn cọc</h2>
            <p class="ops-card-note">Biết người thuê đã ở từ khi nào, từng ở phòng nào và đã chốt hoàn cọc ra sao.</p>
          </div>
        </div>

        <?php if ($activeStay): ?>
          <form method="post" action="?route=room-ops" class="ops-form-grid" style="margin-bottom:16px;">
            <input type="hidden" name="room_id" value="<?= (int)$room['id'] ?>">
            <input type="hidden" name="action" value="settle-room-deposit">
            <input type="hidden" name="stay_id" value="<?= (int)$activeStay['id'] ?>">

            <div class="ops-field">
              <label class="ops-label">Tiền cọc ban đầu</label>
              <div class="ops-history-cell"><strong><?= number_format((int)($activeStay['deposit_amount'] ?? 0), 0, ',', '.') ?> đ</strong></div>
            </div>

            <div class="ops-field">
              <label class="ops-label" for="depositDeductionAmount">Khấu trừ hư hỏng</label>
              <input id="depositDeductionAmount" type="number" name="deposit_deduction_amount" class="form-control" min="0" step="1000" value="<?= htmlspecialchars((string)($activeStay['deposit_deduction_amount'] ?? 0)) ?>">
            </div>

            <div class="ops-field">
              <label class="ops-label" for="depositRefundAmount">Số tiền hoàn lại</label>
              <input id="depositRefundAmount" type="number" name="deposit_refund_amount" class="form-control" min="0" step="1000" value="<?= htmlspecialchars((string)($activeStay['deposit_refund_amount'] ?? max(0, (int)($activeStay['deposit_amount'] ?? 0) - (int)($activeStay['deposit_deduction_amount'] ?? 0)))) ?>">
            </div>

            <div class="ops-field">
              <label class="ops-label" for="depositSettledAt">Ngày chốt cọc</label>
              <input id="depositSettledAt" type="date" name="settled_at" class="form-control" value="<?= htmlspecialchars((string)($activeStay['settled_at'] ?? date('Y-m-d'))) ?>">
            </div>

            <div class="ops-field">
              <label class="ops-label" for="stayEndedAt">Ngày kết thúc thuê</label>
              <input id="stayEndedAt" type="date" name="ended_at" class="form-control" value="<?= htmlspecialchars((string)($activeStay['ended_at'] ?? date('Y-m-d'))) ?>">
            </div>

            <div class="ops-field full">
              <label class="ops-label" for="settlementNote">Ghi chú hoàn cọc</label>
              <textarea id="settlementNote" name="settlement_note" class="form-control" rows="3" placeholder="Ví dụ: Khấu trừ 300.000đ do thay khoá và sơn lại tường."><?= htmlspecialchars((string)($activeStay['settlement_note'] ?? '')) ?></textarea>
            </div>

            <div class="ops-field full">
              <div class="ops-form-actions">
                <button class="btn btn-primary" type="submit">Chốt hoàn cọc</button>
              </div>
            </div>
          </form>
        <?php endif; ?>

        <?php if (empty($stayHistory)): ?>
          <div class="ops-empty">Chưa có lịch sử thuê nào cho phòng này.</div>
        <?php else: ?>
          <div class="ops-history-list">
            <?php foreach ($stayHistory as $stay): ?>
              <article class="ops-history-item">
                <div class="ops-history-top">
                  <div>
                    <div class="ops-history-title"><?= htmlspecialchars((string)($stay['tenant_name'] ?? 'Người thuê')) ?></div>
                    <div class="ops-card-note"><?= htmlspecialchars((string)($stay['started_at'] ?? '')) ?> → <?= !empty($stay['ended_at']) ? htmlspecialchars((string)$stay['ended_at']) : 'đang ở' ?></div>
                  </div>
                  <span class="ops-status <?= htmlspecialchars((string)($stay['status'] ?? 'active') === 'active' ? 'due_soon' : 'paid') ?>">
                    <?= htmlspecialchars((string)($stay['status'] ?? 'active') === 'active' ? 'Đang thuê' : 'Đã rời phòng') ?>
                  </span>
                </div>
                <div class="ops-history-grid">
                  <div class="ops-history-cell">
                    <small>SĐT</small>
                    <strong><?= htmlspecialchars((string)($stay['tenant_phone'] ?? '')) ?></strong>
                  </div>
                  <div class="ops-history-cell">
                    <small>Tiền thuê</small>
                    <strong><?= number_format((int)($stay['rent_amount'] ?? 0), 0, ',', '.') ?> đ</strong>
                  </div>
                  <div class="ops-history-cell">
                    <small>Tiền cọc</small>
                    <strong><?= number_format((int)($stay['deposit_amount'] ?? 0), 0, ',', '.') ?> đ</strong>
                  </div>
                  <div class="ops-history-cell">
                    <small>Thói quen thanh toán</small>
                    <strong><?= htmlspecialchars((string)($stay['payment_regularity_label'] ?? 'Chưa có dữ liệu')) ?></strong>
                  </div>
                  <div class="ops-history-cell">
                    <small>Kỳ đã trả</small>
                    <strong><?= (int)($stay['payment_paid_count'] ?? 0) ?></strong>
                  </div>
                  <div class="ops-history-cell">
                    <small>Kỳ còn tồn</small>
                    <strong><?= (int)($stay['payment_unpaid_count'] ?? 0) ?></strong>
                  </div>
                  <div class="ops-history-cell">
                    <small>Khấu trừ</small>
                    <strong><?= number_format((int)($stay['deposit_deduction_amount'] ?? 0), 0, ',', '.') ?> đ</strong>
                  </div>
                  <div class="ops-history-cell">
                    <small>Hoàn lại</small>
                    <strong><?= number_format((int)($stay['deposit_refund_amount'] ?? 0), 0, ',', '.') ?> đ</strong>
                  </div>
                </div>
                <?php if (!empty($stay['settlement_note'])): ?>
                  <div class="ops-note-copy"><?= nl2br(htmlspecialchars((string)$stay['settlement_note'])) ?></div>
                <?php endif; ?>
              </article>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </section>

      <section class="ops-card ops-anchor-section" id="ops-meters">
        <div class="ops-card-head">
          <div>
            <h2>Nhật ký chỉ số điện nước</h2>
            <p class="ops-card-note">Lưu theo từng kỳ để nhìn ra tháng nào tăng bất thường.</p>
          </div>
        </div>

        <?php if (empty($meterLogs)): ?>
          <div class="ops-empty">Chưa có log công tơ nào. Tạo hóa đơn tháng sẽ tự sinh log theo kỳ.</div>
        <?php else: ?>
          <div class="ops-history-list">
            <?php foreach ($meterLogs as $log): ?>
              <article class="ops-history-item">
                <div class="ops-history-top">
                  <div>
                    <div class="ops-history-title">Kỳ <?= htmlspecialchars((string)($log['billing_month'] ?? '')) ?></div>
                    <div class="ops-card-note">Ghi lúc <?= htmlspecialchars((string)($log['created_at'] ?? '')) ?></div>
                  </div>
                  <?php if (!empty($log['usage_alerts'])): ?>
                    <div style="display:flex; gap:8px; flex-wrap:wrap;">
                      <?php foreach ($log['usage_alerts'] as $alert): ?>
                        <span class="ops-status overdue"><?= htmlspecialchars((string)$alert) ?></span>
                      <?php endforeach; ?>
                    </div>
                  <?php endif; ?>
                </div>
                <div class="ops-history-grid">
                  <div class="ops-history-cell">
                    <small>Điện cũ → mới</small>
                    <strong><?= ($log['electric_reading_old'] ?? null) !== null || ($log['electric_reading_new'] ?? null) !== null ? ((string)($log['electric_reading_old'] ?? '—') . ' → ' . (string)($log['electric_reading_new'] ?? '—')) : '—' ?></strong>
                  </div>
                  <div class="ops-history-cell">
                    <small>Số điện dùng</small>
                    <strong><?= ($log['electric_units'] ?? null) !== null ? (int)$log['electric_units'] . ' số' : '—' ?></strong>
                  </div>
                  <div class="ops-history-cell">
                    <small>Nước cũ → mới</small>
                    <strong><?= ($log['water_reading_old'] ?? null) !== null || ($log['water_reading_new'] ?? null) !== null ? ((string)($log['water_reading_old'] ?? '—') . ' → ' . (string)($log['water_reading_new'] ?? '—')) : '—' ?></strong>
                  </div>
                  <div class="ops-history-cell">
                    <small>Số nước dùng</small>
                    <strong><?= ($log['water_units'] ?? null) !== null ? (int)$log['water_units'] . ' số' : '—' ?></strong>
                  </div>
                </div>
              </article>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </section>

      <section class="ops-card ops-anchor-section" id="ops-handover">
        <div class="ops-card-head">
          <div>
            <h2>Ảnh bàn giao và tình trạng phòng</h2>
            <p class="ops-card-note">Lưu ảnh tường, giường, thiết bị lúc nhận hoặc trả phòng để đối chiếu nhanh.</p>
          </div>
        </div>

        <form method="post" action="?route=room-ops" enctype="multipart/form-data" class="ops-form-grid">
          <input type="hidden" name="room_id" value="<?= (int)$room['id'] ?>">
          <input type="hidden" name="action" value="create-room-handover">

          <div class="ops-field">
            <label class="ops-label" for="handoverType">Kiểu bàn giao</label>
            <select id="handoverType" name="handover_type" class="form-control">
              <?php foreach ($handoverTypeOptions as $value => $label): ?>
                <option value="<?= htmlspecialchars($value) ?>"><?= htmlspecialchars($label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="ops-field">
            <label class="ops-label" for="handoverWallImage">Ảnh tường</label>
            <input id="handoverWallImage" type="file" name="handover_wall_image" class="form-control" accept="image/*">
          </div>

          <div class="ops-field">
            <label class="ops-label" for="handoverBedImage">Ảnh giường</label>
            <input id="handoverBedImage" type="file" name="handover_bed_image" class="form-control" accept="image/*">
          </div>

          <div class="ops-field">
            <label class="ops-label" for="handoverEquipmentImage">Ảnh thiết bị</label>
            <input id="handoverEquipmentImage" type="file" name="handover_equipment_image" class="form-control" accept="image/*">
          </div>

          <div class="ops-field full">
            <label class="ops-label" for="handoverNote">Ghi chú tình trạng</label>
            <textarea id="handoverNote" name="handover_note" class="form-control" rows="3" placeholder="Ví dụ: Tường góc phải có vết xước nhẹ, quạt trần hoạt động bình thường, nệm còn tốt."></textarea>
          </div>

          <div class="ops-field full">
            <div class="ops-form-actions">
              <button class="btn btn-primary" type="submit">Lưu biên bản bàn giao</button>
            </div>
          </div>
        </form>

        <div class="ops-history-list" style="margin-top:16px;">
          <?php if (empty($handoverRecords)): ?>
            <div class="ops-empty">Chưa có biên bản bàn giao nào cho phòng này.</div>
          <?php endif; ?>
          <?php foreach ($handoverRecords as $handover): ?>
            <?php $handoverTypeLabel = $handoverTypeOptions[$handover['handover_type'] ?? 'move_in'] ?? ($handover['handover_type'] ?? 'Bàn giao'); ?>
            <article class="ops-history-item">
              <div class="ops-history-top">
                <div>
                  <div class="ops-history-title"><?= htmlspecialchars((string)$handoverTypeLabel) ?></div>
                  <div class="ops-card-note">Ghi lúc <?= htmlspecialchars((string)($handover['created_at'] ?? '')) ?></div>
                </div>
                <span class="ops-status due_soon"><?= htmlspecialchars((string)$handoverTypeLabel) ?></span>
              </div>
              <?php if (!empty($handover['note'])): ?>
                <div class="ops-note-copy"><?= nl2br(htmlspecialchars((string)$handover['note'])) ?></div>
              <?php endif; ?>
              <div class="ops-photo-grid">
                <?php foreach ([
                  'wall_image' => 'Ảnh tường',
                  'bed_image' => 'Ảnh giường',
                  'equipment_image' => 'Ảnh thiết bị',
                ] as $field => $label): ?>
                  <?php if (!empty($handover[$field])): ?>
                    <a class="ops-photo-card" href="<?= htmlspecialchars(assetUrl((string)$handover[$field])) ?>" target="_blank" rel="noopener">
                      <img src="<?= htmlspecialchars(assetUrl((string)$handover[$field])) ?>" alt="<?= htmlspecialchars($label) ?>">
                      <span><?= htmlspecialchars($label) ?></span>
                    </a>
                  <?php endif; ?>
                <?php endforeach; ?>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      </section>
    </div>
  </div>
</div>
GET['focus'] ?? '') === 'issues') ? 'active' : '' ?>">Sự cố</a>
    <a href="?route=dashboard" class="<?= ($activeMenu ?? '') === 'home' ? 'active' : '' ?>">Báo cáo</a>
  </aside>

  <div>
    <style>
      .ops-shell {
        display: flex;
        flex-direction: column;
        gap: 18px;
      }
      .ops-hero {
        position: relative;
        overflow: hidden;
        border-radius: 22px;
        padding: 20px;
        background:
          radial-gradient(circle at 12% 18%, rgba(255,255,255,0.26), transparent 24%),
          linear-gradient(135deg, #fff6e0 0%, #ffe8ba 48%, #ffd890 100%);
        border: 1px solid rgba(245,158,11,0.24);
        box-shadow: 0 20px 40px rgba(217,119,6,0.12);
      }
      .ops-hero::after {
        content: "";
        position: absolute;
        right: -44px;
        bottom: -72px;
        width: 210px;
        height: 210px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(217,119,6,0.12), transparent 64%);
      }
      .ops-hero-row {
        position: relative;
        z-index: 1;
        display: flex;
        justify-content: space-between;
        gap: 16px;
        align-items: flex-start;
      }
      .ops-hero h1 {
        margin: 8px 0 6px;
        font-size: clamp(24px, 3vw, 30px);
        line-height: 1.15;
        color: #431407;
        letter-spacing: -0.35px;
      }
      .ops-hero p {
        margin: 0;
        color: #7c2d12;
      }
      .ops-mini-chip {
        display: inline-flex;
        align-items: center;
        min-height: 34px;
        padding: 8px 12px;
        border-radius: 999px;
        background: rgba(255,255,255,0.76);
        border: 1px solid rgba(255,255,255,0.6);
        color: #9a3412;
        font-size: 12px;
        font-weight: 800;
      }
      .ops-hero-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 12px;
      }
      .ops-hero-actions {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        justify-content: flex-end;
      }
      .ops-hero-actions .btn {
        min-height: 42px;
      }
      .ops-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.2fr) minmax(300px, 0.8fr);
        gap: 18px;
        align-items: start;
      }
      .ops-lifecycle-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 14px;
      }
      .ops-lifecycle-group {
        border: 1px solid rgba(251,191,36,0.22);
        border-radius: 20px;
        padding: 16px;
        background: linear-gradient(180deg, rgba(255,255,255,0.98), rgba(255,248,232,0.98));
        box-shadow: 0 16px 32px rgba(15,23,42,0.06);
      }
      .ops-lifecycle-head {
        display: flex;
        justify-content: space-between;
        gap: 10px;
        align-items: flex-start;
        margin-bottom: 12px;
      }
      .ops-lifecycle-head h2 {
        margin: 0;
        font-size: 18px;
        line-height: 1.2;
      }
      .ops-lifecycle-link {
        color: #b45309;
        font-size: 13px;
        font-weight: 800;
        white-space: nowrap;
      }
      .ops-lifecycle-link:hover {
        color: #92400e;
      }
      .ops-lifecycle-note {
        margin: 4px 0 0;
        color: #6b7280;
        font-size: 13px;
        line-height: 1.5;
      }
      .ops-lifecycle-steps {
        display: grid;
        gap: 10px;
      }
      .ops-lifecycle-step {
        padding: 14px;
        border-radius: 16px;
        border: 1px solid rgba(226,232,240,0.92);
        background: #fff;
      }
      .ops-lifecycle-step.done {
        border-color: rgba(34,197,94,0.22);
        background: linear-gradient(180deg, #f0fdf4, #dcfce7);
      }
      .ops-lifecycle-step.active {
        border-color: rgba(245,158,11,0.26);
        background: linear-gradient(180deg, #fff8e8, #fff1cd);
      }
      .ops-lifecycle-step.warning {
        border-color: rgba(244,63,94,0.22);
        background: linear-gradient(180deg, #fff7ed, #ffe4e6);
      }
      .ops-lifecycle-step.pending {
        border-style: dashed;
        background: linear-gradient(180deg, #fff, #fff8ef);
      }
      .ops-lifecycle-label {
        color: #6b7280;
        font-size: 12px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.04em;
      }
      .ops-lifecycle-value {
        margin-top: 7px;
        color: #111827;
        font-size: 15px;
        font-weight: 800;
        line-height: 1.45;
      }
      .ops-lifecycle-detail {
        margin-top: 6px;
        color: #6b7280;
        font-size: 13px;
        line-height: 1.5;
      }
      .ops-main,
      .ops-side {
        display: flex;
        flex-direction: column;
        gap: 18px;
        min-width: 0;
      }
      .ops-anchor-section {
        scroll-margin-top: 96px;
      }
      .ops-card {
        background: linear-gradient(180deg, rgba(255,255,255,0.98), rgba(255,250,239,0.98));
        border: 1px solid rgba(251,191,36,0.22);
        border-radius: 20px;
        padding: 18px;
        box-shadow: 0 16px 32px rgba(15, 23, 42, 0.08);
      }
      .ops-card-head {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        align-items: flex-start;
        margin-bottom: 14px;
      }
      .ops-card-head h2 {
        margin: 0;
        font-size: 18px;
        line-height: 1.2;
      }
      .ops-card-note {
        margin: 4px 0 0;
        color: #6b7280;
        font-size: 13px;
      }
      .ops-room-list {
        display: grid;
        gap: 10px;
      }
      .ops-room-item {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        padding: 12px 0;
        border-bottom: 1px solid rgba(226,232,240,0.86);
      }
      .ops-room-item:last-child {
        border-bottom: none;
        padding-bottom: 0;
      }
      .ops-room-item strong {
        color: #111827;
        font-size: 14px;
      }
      .ops-room-item span {
        color: #6b7280;
        font-size: 13px;
      }
      .ops-kpi-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
      }
      .ops-kpi {
        padding: 14px;
        border-radius: 16px;
        border: 1px solid rgba(251,191,36,0.16);
        background: linear-gradient(180deg, #fffdf8, #fff7e7);
      }
      .ops-kpi-label {
        color: #6b7280;
        font-size: 12px;
        font-weight: 700;
      }
      .ops-kpi-value {
        margin-top: 5px;
        font-size: 24px;
        font-weight: 800;
        color: #111827;
      }
      .ops-form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px;
      }
      .ops-field {
        display: flex;
        flex-direction: column;
        gap: 6px;
      }
      .ops-field.full {
        grid-column: 1 / -1;
      }
      .ops-label {
        color: #374151;
        font-size: 13px;
        font-weight: 800;
      }
      .ops-help {
        margin: 0;
        color: #6b7280;
        font-size: 12px;
        line-height: 1.45;
      }
      .ops-form-actions {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        margin-top: 4px;
      }
      .ops-side-highlight {
        border-radius: 18px;
        padding: 16px;
        background: linear-gradient(135deg, #fbbf24, #d97706);
        color: #431407;
        box-shadow: 0 18px 32px rgba(180,83,9,0.16);
      }
      .ops-side-highlight strong {
        display: block;
        margin-bottom: 6px;
        font-size: 18px;
      }
      .ops-side-highlight p {
        margin: 0;
        color: rgba(67,20,7,0.9);
      }
      .ops-history-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
      }
      .ops-history-item {
        border: 1px solid rgba(226,232,240,0.92);
        border-radius: 18px;
        padding: 14px;
        background: #fff;
      }
      .ops-history-top {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
        align-items: center;
      }
      .ops-history-title {
        font-size: 16px;
        font-weight: 800;
        color: #111827;
      }
      .ops-status {
        display: inline-flex;
        align-items: center;
        padding: 6px 10px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 800;
        border: 1px solid transparent;
      }
      .ops-status.paid {
        color: #166534;
        background: #dcfce7;
        border-color: rgba(34,197,94,0.18);
      }
      .ops-status.unpaid {
        color: #92400e;
        background: #fff7ed;
        border-color: rgba(245,158,11,0.18);
      }
      .ops-status.overdue {
        color: #9f1239;
        background: #fff1f2;
        border-color: rgba(244,63,94,0.18);
      }
      .ops-status.due_soon {
        color: #92400e;
        background: #fef3c7;
        border-color: rgba(245,158,11,0.24);
      }
      .ops-status.cancelled {
        color: #475569;
        background: #f1f5f9;
        border-color: rgba(148,163,184,0.18);
      }
      .ops-history-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 10px;
        margin-top: 12px;
      }
      .ops-history-cell {
        padding: 10px 12px;
        border-radius: 14px;
        background: #fffaf0;
        border: 1px solid rgba(251,191,36,0.16);
      }
      .ops-history-cell small {
        display: block;
        margin-bottom: 4px;
        color: #6b7280;
      }
      .ops-history-cell strong {
        display: block;
        color: #111827;
        word-break: break-word;
      }
      .ops-history-actions {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        margin-top: 12px;
      }
      .ops-history-actions form {
        margin: 0;
      }
      .ops-item-form-grid {
        margin-top: 12px;
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 10px;
      }
      .ops-item-form-grid .full {
        grid-column: 1 / -1;
      }
      .ops-item-form-grid .btn {
        min-height: 42px;
      }
      .ops-lead-list {
        display: grid;
        gap: 12px;
      }
      .ops-lead-item {
        border: 1px solid rgba(226,232,240,0.92);
        border-radius: 18px;
        padding: 14px;
        background: #fff;
      }
      .ops-lead-top {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
        align-items: flex-start;
      }
      .ops-lead-title {
        font-size: 16px;
        font-weight: 800;
        color: #111827;
      }
      .ops-lead-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 10px;
        margin-top: 12px;
      }
      .ops-lead-cell {
        padding: 10px 12px;
        border-radius: 14px;
        background: #fffaf0;
        border: 1px solid rgba(251,191,36,0.16);
      }
      .ops-lead-cell small {
        display: block;
        margin-bottom: 4px;
        color: #6b7280;
      }
      .ops-lead-cell strong {
        display: block;
        color: #111827;
        word-break: break-word;
      }
      .ops-notice-list,
      .ops-issue-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
      }
      .ops-notice-item,
      .ops-issue-item {
        border: 1px solid rgba(226,232,240,0.92);
        border-radius: 18px;
        padding: 14px;
        background: #fff;
      }
      .ops-notice-top,
      .ops-issue-top {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 12px;
        flex-wrap: wrap;
      }
      .ops-notice-title,
      .ops-issue-title {
        font-size: 15px;
        font-weight: 800;
        color: #111827;
        line-height: 1.35;
      }
      .ops-note-copy {
        margin-top: 10px;
        color: #374151;
        font-size: 14px;
        line-height: 1.6;
        white-space: pre-line;
      }
      .ops-inline-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 10px;
      }
      .ops-inline-pill {
        display: inline-flex;
        align-items: center;
        min-height: 28px;
        padding: 6px 10px;
        border-radius: 999px;
        background: #fff7ed;
        border: 1px solid rgba(251,191,36,0.24);
        color: #9a3412;
        font-size: 12px;
        font-weight: 700;
      }
      .ops-proof-thumb {
        display: inline-flex;
        margin-top: 10px;
        border-radius: 14px;
        overflow: hidden;
        border: 1px solid rgba(226,232,240,0.92);
        background: #fffaf0;
      }
      .ops-proof-thumb img {
        display: block;
        width: 120px;
        height: 120px;
        object-fit: cover;
      }
      .ops-inline-list {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
      }
      .ops-photo-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 10px;
        margin-top: 12px;
      }
      .ops-photo-card {
        border-radius: 16px;
        overflow: hidden;
        border: 1px solid rgba(226,232,240,0.92);
        background: #fffaf0;
      }
      .ops-photo-card img {
        display: block;
        width: 100%;
        aspect-ratio: 1 / 1;
        object-fit: cover;
      }
      .ops-photo-card span {
        display: block;
        padding: 8px 10px;
        color: #6b7280;
        font-size: 12px;
        font-weight: 700;
      }
      .ops-empty {
        color: #6b7280;
        font-size: 14px;
      }
      html.browser-dark .ops-hero {
        background:
          radial-gradient(circle at 12% 18%, rgba(255,255,255,0.08), transparent 22%),
          linear-gradient(135deg, #2b2117 0%, #21180f 48%, #17120d 100%);
        border-color: rgba(245,158,11,0.16);
        box-shadow: 0 24px 42px rgba(0,0,0,0.38);
      }
      html.browser-dark .ops-hero h1,
      html.browser-dark .ops-lifecycle-head h2,
      html.browser-dark .ops-lifecycle-value,
      html.browser-dark .ops-card-head h2,
      html.browser-dark .ops-room-item strong,
      html.browser-dark .ops-kpi-value,
      html.browser-dark .ops-lead-title,
      html.browser-dark .ops-lead-cell strong,
      html.browser-dark .ops-history-title,
      html.browser-dark .ops-history-cell strong,
      html.browser-dark .ops-notice-title,
      html.browser-dark .ops-issue-title {
        color: #fff6e7;
      }
      html.browser-dark .ops-hero p,
      html.browser-dark .ops-mini-chip,
      html.browser-dark .ops-side-highlight,
      html.browser-dark .ops-side-highlight p {
        color: #f5ddbc;
      }
      html.browser-dark .ops-card,
      html.browser-dark .ops-lifecycle-group,
      html.browser-dark .ops-lifecycle-step,
      html.browser-dark .ops-lead-item,
      html.browser-dark .ops-lead-cell,
      html.browser-dark .ops-history-item,
      html.browser-dark .ops-notice-item,
      html.browser-dark .ops-issue-item,
      html.browser-dark .ops-history-cell,
      html.browser-dark .ops-kpi {
        background: linear-gradient(180deg, #1d1813, #17120d);
        border-color: #473625;
        box-shadow: 0 16px 32px rgba(0,0,0,0.34);
      }
      html.browser-dark .ops-room-item {
        border-bottom-color: rgba(71,54,37,0.86);
      }
      html.browser-dark .ops-card-note,
      html.browser-dark .ops-help,
      html.browser-dark .ops-lifecycle-note,
      html.browser-dark .ops-lifecycle-label,
      html.browser-dark .ops-lifecycle-detail,
      html.browser-dark .ops-room-item span,
      html.browser-dark .ops-kpi-label,
      html.browser-dark .ops-lead-cell small,
      html.browser-dark .ops-history-cell small,
      html.browser-dark .ops-note-copy,
      html.browser-dark .ops-inline-pill,
      html.browser-dark .ops-empty {
        color: #cdbb9f;
      }
      html.browser-dark .ops-lifecycle-link {
        color: #f7c97a;
      }
      html.browser-dark .ops-proof-thumb {
        border-color: #473625;
        background: linear-gradient(180deg, #1d1813, #17120d);
      }
      html.browser-dark .ops-photo-card {
        border-color: #473625;
        background: linear-gradient(180deg, #1d1813, #17120d);
      }
      html.browser-dark .ops-photo-card span {
        color: #cdbb9f;
      }
      @media (max-width: 960px) {
        .ops-lifecycle-grid,
        .ops-grid {
          grid-template-columns: 1fr;
        }
      }
      @media (max-width: 768px) {
        .ops-hero {
          padding: 16px 14px;
          border-radius: 18px;
        }
        .ops-hero-row {
          flex-direction: column;
          align-items: stretch;
        }
        .ops-lifecycle-head {
          flex-direction: column;
        }
        .ops-hero-actions {
          justify-content: stretch;
        }
        .ops-hero-actions .btn {
          flex: 1 1 auto;
          justify-content: center;
        }
        .ops-card {
          padding: 16px 14px;
          border-radius: 16px;
        }
        .ops-form-grid,
        .ops-kpi-grid,
        .ops-inline-list,
        .ops-lead-grid,
        .ops-photo-grid,
        .ops-history-grid,
        .ops-item-form-grid {
          grid-template-columns: 1fr;
        }
        .ops-room-item {
          flex-direction: column;
        }
      }
    </style>

    <div class="ops-shell">
      <section class="ops-hero">
        <div class="ops-hero-row">
          <div style="min-width:0;">
            <span class="ops-mini-chip">Hồ sơ vận hành phòng</span>
            <h1>#<?= (int)$room['id'] ?> · <?= htmlspecialchars((string)$room['title']) ?></h1>
            <p><?= htmlspecialchars((string)($room['address'] ?? '')) ?></p>
            <div class="ops-hero-meta">
              <span class="ops-mini-chip"><?= htmlspecialchars($occupancyLabel) ?></span>
              <span class="ops-mini-chip"><?= htmlspecialchars($conditionLabel) ?></span>
              <span class="ops-mini-chip"><?= !empty($profile['tenant_name']) ? ('Người thuê: ' . htmlspecialchars((string)$profile['tenant_name'])) : 'Chưa gắn người thuê' ?></span>
            </div>
          </div>
          <div class="ops-hero-actions">
            <a class="btn btn-outline" href="?route=room-edit&id=<?= (int)$room['id'] ?>">Sửa bài đăng</a>
            <a class="btn btn-outline" href="?route=room&id=<?= (int)$room['id'] ?>" target="_blank" rel="noopener">Xem tin công khai</a>
          </div>
        </div>
      </section>

      <section class="ops-lifecycle-grid">
        <?php foreach ($opsLifecycleGroups as $group): ?>
          <article class="ops-lifecycle-group">
            <div class="ops-lifecycle-head">
              <div>
                <h2><?= htmlspecialchars((string)$group['title']) ?></h2>
                <p class="ops-lifecycle-note"><?= htmlspecialchars((string)$group['note']) ?></p>
              </div>
              <a class="ops-lifecycle-link" href="<?= htmlspecialchars((string)$group['href']) ?>"><?= htmlspecialchars((string)$group['link_label']) ?></a>
            </div>
            <div class="ops-lifecycle-steps">
              <?php foreach ($group['steps'] as $step): ?>
                <article class="ops-lifecycle-step <?= htmlspecialchars((string)$step['state']) ?>">
                  <div class="ops-lifecycle-label"><?= htmlspecialchars((string)$step['label']) ?></div>
                  <div class="ops-lifecycle-value"><?= htmlspecialchars((string)$step['detail']) ?></div>
                </article>
              <?php endforeach; ?>
            </div>
          </article>
        <?php endforeach; ?>
      </section>

      <div class="ops-grid">
        <div class="ops-main">
          <section class="ops-card ops-anchor-section" id="ops-before-rent">
            <div class="ops-card-head">
              <div>
                <h2>Nhu cầu và bước chốt thuê</h2>
                <p class="ops-card-note">Đây là đoạn nối giữa “người thuê đăng nhu cầu” và “gắn người thuê vào phòng”. Chốt nhu cầu ở đây sẽ kéo luôn người thuê vào hồ sơ vận hành.</p>
              </div>
            </div>

            <div class="ops-inline-meta" style="margin-top:0;">
              <span class="ops-inline-pill"><?= $leadTotalCount ?> nhu cầu cho phòng này</span>
              <span class="ops-inline-pill"><?= $leadPurchasedCount ?> nhu cầu đã mua</span>
              <span class="ops-inline-pill"><?= $leadClosedCount ?> nhu cầu đã chốt</span>
              <?php if ($leadInvalidCount > 0): ?>
                <span class="ops-inline-pill"><?= $leadInvalidCount ?> nhu cầu lỗi</span>
              <?php endif; ?>
            </div>

            <div class="ops-lead-list" style="margin-top:14px;">
              <?php if (empty($roomLeads)): ?>
                <div class="ops-empty">Chưa có nhu cầu nào đi vào phòng này. Khi người thuê đăng nhu cầu và quan tâm phòng, bước trước thuê sẽ hiện ở đây.</div>
              <?php endif; ?>
              <?php foreach (array_slice($roomLeads, 0, 5) as $lead): ?>
                <?php
                  $leadStatus = (string)($lead['status'] ?? 'new');
                  $leadStatusLabel = [
                      'new' => 'Nhu cầu mới',
                      'opened' => 'Đã mua',
                      'contacted' => 'Đã liên hệ',
                      'closed' => 'Đã chốt thuê',
                      'invalid' => 'Nhu cầu lỗi',
                      'used' => 'Đã dùng',
                      'paid' => 'Đã mua',
                  ][$leadStatus] ?? $leadStatus;
                  $leadPurchased = leadHasUnlockedContact($lead);
                  $leadClosed = in_array($leadStatus, $leadClosedStatuses, true);
                  $displayName = $leadPurchased ? (string)($lead['tenant_name'] ?? '') : maskName((string)($lead['tenant_name'] ?? 'Khách'));
                  $displayPhone = $leadPurchased ? (string)($lead['tenant_phone'] ?? '') : maskPhone((string)($lead['tenant_phone'] ?? ''));
                ?>
                <article class="ops-lead-item">
                  <div class="ops-lead-top">
                    <div>
                      <div class="ops-lead-title"><?= htmlspecialchars($displayName !== '' ? $displayName : 'Khách quan tâm') ?></div>
                      <div class="ops-card-note">Nhu cầu #<?= (int)$lead['id'] ?> · Gửi lúc <?= htmlspecialchars((string)($lead['created_at'] ?? '')) ?></div>
                    </div>
                    <span class="ops-status <?= htmlspecialchars($leadClosed ? 'paid' : ($leadPurchased ? 'due_soon' : ($leadStatus === 'invalid' ? 'overdue' : 'unpaid'))) ?>">
                      <?= htmlspecialchars($leadStatusLabel) ?>
                    </span>
                  </div>

                  <div class="ops-lead-grid">
                    <div class="ops-lead-cell">
                      <small>Số điện thoại</small>
                      <strong><?= htmlspecialchars($displayPhone !== '' ? $displayPhone : 'Chưa có') ?></strong>
                    </div>
                    <div class="ops-lead-cell">
                      <small>Mức độ phù hợp</small>
                      <strong><?= htmlspecialchars((string)($lead['match_label'] ?? '—')) ?></strong>
                    </div>
                    <div class="ops-lead-cell">
                      <small>Giá mở nhu cầu</small>
                      <strong><?= number_format((int)effectiveLeadPriceFromRow($lead), 0, ',', '.') ?> đ</strong>
                    </div>
                  </div>

                  <div class="ops-history-actions">
                    <?php if (!$leadPurchased && $leadStatus !== 'invalid'): ?>
                      <a class="btn btn-outline btn-sm" href="?route=dashboard&tab=lead#lead">Mua nhu cầu ở khu nhu cầu</a>
                    <?php elseif ($leadPurchased && !$leadClosed): ?>
                      <form method="post" action="?route=room-ops">
                        <input type="hidden" name="room_id" value="<?= (int)$room['id'] ?>">
                        <input type="hidden" name="action" value="convert-room-lead">
                        <input type="hidden" name="lead_id" value="<?= (int)$lead['id'] ?>">
                        <button class="btn btn-primary btn-sm" type="submit">Chốt thuê từ nhu cầu này</button>
                      </form>
                    <?php elseif ($leadClosed): ?>
                      <span class="ops-inline-pill">Nhu cầu này đã được chốt vào hồ sơ thuê</span>
                    <?php else: ?>
                      <span class="ops-inline-pill">Nhu cầu đã bị đánh dấu lỗi</span>
                    <?php endif; ?>
                  </div>
                </article>
              <?php endforeach; ?>
            </div>
          </section>

          <section class="ops-card ops-anchor-section" id="ops-profile">
            <div class="ops-card-head">
              <div>
                <h2>Hồ sơ vận hành</h2>
                <p class="ops-card-note">Từ đây phòng không chỉ là bài đăng, mà là hồ sơ quản lý thực tế.</p>
              </div>
            </div>

            <form method="post" action="?route=room-ops" class="ops-form-grid">
              <input type="hidden" name="room_id" value="<?= (int)$room['id'] ?>">
              <input type="hidden" name="action" value="save-room-ops">

              <div class="ops-field">
                <label class="ops-label" for="opsOccupancyStatus">Tình trạng phòng</label>
                <select id="opsOccupancyStatus" name="occupancy_status" class="form-control">
                  <?php foreach ($occupancyOptions as $value => $label): ?>
                    <option value="<?= htmlspecialchars($value) ?>" <?= $occupancyStatus === $value ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="ops-field">
                <label class="ops-label" for="opsRoomCondition">Tình trạng kỹ thuật</label>
                <select id="opsRoomCondition" name="room_condition" class="form-control">
                  <?php foreach ($conditionOptions as $value => $label): ?>
                    <option value="<?= htmlspecialchars($value) ?>" <?= $roomCondition === $value ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="ops-field">
                <label class="ops-label" for="opsTenantName">Người đang thuê</label>
                <input id="opsTenantName" type="text" name="tenant_name" class="form-control" value="<?= htmlspecialchars((string)($profile['tenant_name'] ?? '')) ?>" placeholder="Ví dụ: Nguyễn Văn A">
              </div>

              <div class="ops-field">
                <label class="ops-label" for="opsTenantPhone">SĐT người thuê</label>
                <input id="opsTenantPhone" type="tel" name="tenant_phone" class="form-control" value="<?= htmlspecialchars((string)($profile['tenant_phone'] ?? '')) ?>" placeholder="0912345678">
                <p class="ops-help">Người thuê đăng nhập bằng đúng số này sẽ thấy phòng, hóa đơn, thông báo và khu báo sự cố của họ.</p>
              </div>

              <div class="ops-field">
                <label class="ops-label" for="opsMonthlyRent">Tiền thuê thực thu</label>
                <input id="opsMonthlyRent" type="number" name="monthly_rent" class="form-control" min="0" step="1000" value="<?= htmlspecialchars((string)($profile['monthly_rent'] ?? $room['price'] ?? '')) ?>">
              </div>

              <div class="ops-field">
                <label class="ops-label" for="opsDepositAmount">Tiền cọc</label>
                <input id="opsDepositAmount" type="number" name="deposit_amount" class="form-control" min="0" step="1000" value="<?= htmlspecialchars((string)($profile['deposit_amount'] ?? '')) ?>">
              </div>

              <div class="ops-field">
                <label class="ops-label" for="opsServiceFee">Phí dịch vụ cố định</label>
                <input id="opsServiceFee" type="number" name="service_fee" class="form-control" min="0" step="1000" value="<?= htmlspecialchars((string)($profile['service_fee'] ?? 0)) ?>">
              </div>

              <div class="ops-field">
                <label class="ops-label" for="opsContractStart">Ngày bắt đầu hợp đồng</label>
                <input id="opsContractStart" type="date" name="contract_start" class="form-control" value="<?= htmlspecialchars((string)($profile['contract_start'] ?? '')) ?>">
              </div>

              <div class="ops-field">
                <label class="ops-label" for="opsContractEnd">Ngày hết hạn hợp đồng</label>
                <input id="opsContractEnd" type="date" name="contract_end" class="form-control" value="<?= htmlspecialchars((string)($profile['contract_end'] ?? '')) ?>">
              </div>

              <div class="ops-field">
                <label class="ops-label" for="opsElectricMeter">Công tơ điện hiện tại</label>
                <input id="opsElectricMeter" type="number" name="electric_meter_reading" class="form-control" min="0" step="1" value="<?= htmlspecialchars((string)($profile['electric_meter_reading'] ?? '')) ?>">
              </div>

              <div class="ops-field">
                <label class="ops-label" for="opsWaterMeter">Công tơ nước hiện tại</label>
                <input id="opsWaterMeter" type="number" name="water_meter_reading" class="form-control" min="0" step="1" value="<?= htmlspecialchars((string)($profile['water_meter_reading'] ?? '')) ?>">
              </div>

              <div class="ops-field full">
                <label class="ops-label" for="opsIssueNote">Ghi chú sự cố</label>
                <textarea id="opsIssueNote" name="issue_note" class="form-control" rows="3" placeholder="Ví dụ: Máy lạnh yếu, cần kiểm tra đường ống nước."><?= htmlspecialchars((string)($profile['issue_note'] ?? '')) ?></textarea>
              </div>

              <div class="ops-field full">
                <label class="ops-label" for="opsOperationNote">Ghi chú vận hành</label>
                <textarea id="opsOperationNote" name="operation_note" class="form-control" rows="3" placeholder="Các lưu ý nội bộ về phòng, lịch hẹn, tình trạng thanh toán..."><?= htmlspecialchars((string)($profile['operation_note'] ?? '')) ?></textarea>
                <p class="ops-help">Chỗ này dùng như sổ tay vận hành của riêng chủ trọ.</p>
              </div>

              <div class="ops-field full">
                <div class="ops-form-actions">
                  <button class="btn btn-primary" type="submit">Lưu hồ sơ vận hành</button>
                  <a class="btn btn-outline" href="?route=my-rooms">Quay lại danh sách phòng</a>
                </div>
              </div>
            </form>
          </section>

          <section class="ops-card ops-anchor-section" id="ops-billing">
            <div class="ops-card-head">
              <div>
                <h2>Tạo hoá đơn tháng</h2>
                <p class="ops-card-note">Chỉ cần nhập số điện, số nước mới. Tiền phòng và phí dịch vụ sẽ tự lấy từ hồ sơ vận hành.</p>
              </div>
            </div>

            <form method="post" action="?route=room-ops" class="ops-form-grid">
              <input type="hidden" name="room_id" value="<?= (int)$room['id'] ?>">
              <input type="hidden" name="action" value="create-room-invoice">
              <input type="hidden" name="rent_amount" value="<?= htmlspecialchars((string)($profile['monthly_rent'] ?? $room['price'] ?? 0)) ?>">
              <input type="hidden" name="service_amount" value="<?= htmlspecialchars((string)$serviceFee) ?>">

              <div class="ops-field">
                <label class="ops-label" for="invoiceBillingMonth">Tháng hoá đơn</label>
                <input id="invoiceBillingMonth" type="month" name="billing_month" class="form-control" value="<?= htmlspecialchars($nextBillingMonth) ?>" required>
              </div>

              <div class="ops-field full">
                <label class="ops-label">Khoản cố định sẽ tự tính</label>
                <div class="ops-inline-list">
                  <div class="ops-history-cell">
                    <small>Tiền phòng</small>
                    <strong><?= number_format((int)($profile['monthly_rent'] ?? $room['price'] ?? 0), 0, ',', '.') ?> đ</strong>
                  </div>
                  <div class="ops-history-cell">
                    <small>Phí dịch vụ</small>
                    <strong><?= number_format($serviceFee, 0, ',', '.') ?> đ</strong>
                  </div>
                </div>
              </div>

              <div class="ops-field">
                <label class="ops-label" for="invoiceElectricNew">Chỉ số điện mới</label>
                <input id="invoiceElectricNew" type="number" name="electric_reading_new" class="form-control" min="<?= htmlspecialchars((string)($profile['electric_meter_reading'] ?? 0)) ?>" step="1" value="">
                <p class="ops-help">Chỉ số cũ: <?= $profile['electric_meter_reading'] !== null ? (int)$profile['electric_meter_reading'] : 'chưa có' ?> · Giá điện: <?= !empty($room['electric_price']) ? number_format((int)$room['electric_price'], 0, ',', '.') . ' đ/kWh' : 'chưa khai báo' ?></p>
              </div>

              <div class="ops-field">
                <label class="ops-label" for="invoiceWaterNew">Chỉ số nước mới</label>
                <input id="invoiceWaterNew" type="number" name="water_reading_new" class="form-control" min="<?= htmlspecialchars((string)($profile['water_meter_reading'] ?? 0)) ?>" step="1" value="">
                <p class="ops-help">Chỉ số cũ: <?= $profile['water_meter_reading'] !== null ? (int)$profile['water_meter_reading'] : 'chưa có' ?> · Giá nước: <?= !empty($room['water_price']) ? number_format((int)$room['water_price'], 0, ',', '.') . ' đ/m³' : 'chưa khai báo' ?></p>
              </div>

              <div class="ops-field">
                <label class="ops-label" for="invoiceOtherAmount">Phát sinh thêm</label>
                <input id="invoiceOtherAmount" type="number" name="other_amount" class="form-control" min="0" step="1000" value="0">
              </div>

              <div class="ops-field">
                <label class="ops-label" for="invoiceDueDate">Hạn thanh toán</label>
                <input id="invoiceDueDate" type="date" name="due_date" class="form-control" value="<?= htmlspecialchars($nextDueDate) ?>">
              </div>

              <div class="ops-field full">
                <label class="ops-label" for="invoiceNote">Ghi chú hoá đơn</label>
                <textarea id="invoiceNote" name="invoice_note" class="form-control" rows="3" placeholder="Ví dụ: Tháng này phát sinh thay vòi nước 150.000đ."></textarea>
              </div>

              <div class="ops-field full">
                <div class="ops-form-actions">
                  <button class="btn btn-primary" type="submit">Tạo hoá đơn</button>
                </div>
              </div>
            </form>
          </section>

          <section class="ops-card ops-anchor-section" id="ops-notices">
            <div class="ops-card-head">
              <div>
                <h2>Thông báo cho người thuê</h2>
                <p class="ops-card-note">Đưa nhắc thanh toán, điều chỉnh phí, lịch cắt điện nước hoặc nội quy vào khu “Không gian thuê trọ của tôi”.</p>
              </div>
            </div>

            <form method="post" action="?route=room-ops" class="ops-form-grid">
              <input type="hidden" name="room_id" value="<?= (int)$room['id'] ?>">
              <input type="hidden" name="action" value="create-room-notice">

              <div class="ops-field">
                <label class="ops-label" for="noticeType">Loại thông báo</label>
                <select id="noticeType" name="notice_type" class="form-control">
                  <?php foreach ($noticeTypeOptions as $value => $label): ?>
                    <option value="<?= htmlspecialchars($value) ?>"><?= htmlspecialchars($label) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="ops-field">
                <label class="ops-label" for="noticeEffectiveDate">Ngày hiệu lực</label>
                <input id="noticeEffectiveDate" type="date" name="notice_effective_date" class="form-control" value="<?= htmlspecialchars($nextNoticeDate) ?>">
              </div>

              <div class="ops-field full">
                <label class="ops-label" for="noticeTitle">Tiêu đề</label>
                <input id="noticeTitle" type="text" name="notice_title" class="form-control" placeholder="Ví dụ: Hóa đơn tháng này cần thanh toán trước ngày 05.">
              </div>

              <div class="ops-field full">
                <label class="ops-label" for="noticeContent">Nội dung</label>
                <textarea id="noticeContent" name="notice_content" class="form-control" rows="4" placeholder="Viết rõ việc cần làm, mốc thời gian, khoản phí điều chỉnh hoặc lưu ý điện nước."></textarea>
              </div>

              <div class="ops-field full">
                <div class="ops-form-actions">
                  <button class="btn btn-primary" type="submit">Gửi thông báo</button>
                </div>
              </div>
            </form>

            <div class="ops-notice-list" style="margin-top:16px;">
              <?php if (empty($roomNotices)): ?>
                <div class="ops-empty">Chưa có thông báo nào được gửi xuống cho người thuê.</div>
              <?php endif; ?>
              <?php foreach ($roomNotices as $notice): ?>
                <?php $noticeTypeLabel = $noticeTypeOptions[$notice['notice_type'] ?? 'general'] ?? ($notice['notice_type'] ?? 'Thông báo'); ?>
                <article class="ops-notice-item">
                  <div class="ops-notice-top">
                    <div>
                      <div class="ops-notice-title"><?= htmlspecialchars((string)($notice['title'] ?? 'Thông báo')) ?></div>
                      <div class="ops-card-note">Đăng lúc <?= htmlspecialchars((string)($notice['created_at'] ?? '')) ?></div>
                    </div>
                    <span class="ops-status unpaid"><?= htmlspecialchars((string)$noticeTypeLabel) ?></span>
                  </div>
                  <div class="ops-note-copy"><?= nl2br(htmlspecialchars((string)($notice['content'] ?? ''))) ?></div>
                  <div class="ops-inline-meta">
                    <?php if (!empty($notice['effective_date'])): ?>
                      <span class="ops-inline-pill">Hiệu lực: <?= htmlspecialchars((string)$notice['effective_date']) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($notice['landlord_name'])): ?>
                      <span class="ops-inline-pill">Từ: <?= htmlspecialchars((string)$notice['landlord_name']) ?></span>
                    <?php endif; ?>
                  </div>
                </article>
              <?php endforeach; ?>
            </div>
          </section>

          <section class="ops-card ops-anchor-section" id="ops-issues">
            <div class="ops-card-head">
              <div>
                <h2>Sự cố người thuê đã gửi</h2>
                <p class="ops-card-note">Theo dõi báo hỏng, ảnh minh chứng và đánh dấu tiến độ xử lý ngay tại đây.</p>
              </div>
            </div>

            <div class="ops-issue-list">
              <?php if (empty($roomIssues)): ?>
                <div class="ops-empty">Chưa có báo sự cố nào từ người thuê.</div>
              <?php endif; ?>
              <?php foreach ($roomIssues as $issue): ?>
                <?php
                  $issuePriorityLabel = $issuePriorityOptions[$issue['priority'] ?? 'normal'] ?? ($issue['priority'] ?? 'Bình thường');
                  $issueStatus = (string)($issue['status'] ?? 'open');
                  $issueStatusLabel = $issueStatusOptions[$issueStatus] ?? $issueStatus;
                  $issueStatusClass = $issueStatusClassMap[$issueStatus] ?? 'overdue';
                ?>
                <article class="ops-issue-item">
                  <div class="ops-issue-top">
                    <div>
                      <div class="ops-issue-title"><?= htmlspecialchars((string)($issue['tenant_name'] ?? 'Người thuê')) ?> báo sự cố</div>
                      <div class="ops-card-note">Gửi lúc <?= htmlspecialchars((string)($issue['created_at'] ?? '')) ?></div>
                    </div>
                    <span class="ops-status <?= htmlspecialchars($issueStatusClass) ?>">
                      <?= htmlspecialchars((string)$issueStatusLabel) ?>
                    </span>
                  </div>

                  <div class="ops-inline-meta">
                    <span class="ops-inline-pill">Ưu tiên: <?= htmlspecialchars((string)$issuePriorityLabel) ?></span>
                    <span class="ops-inline-pill">SĐT: <?= htmlspecialchars((string)($issue['tenant_phone'] ?? '')) ?></span>
                    <?php if ((int)($issue['repair_cost'] ?? 0) > 0): ?>
                      <span class="ops-inline-pill">Chi phí sửa: <?= number_format((int)($issue['repair_cost'] ?? 0), 0, ',', '.') ?> đ</span>
                    <?php endif; ?>
                  </div>

                  <div class="ops-note-copy"><?= nl2br(htmlspecialchars((string)($issue['content'] ?? ''))) ?></div>

                  <?php if (!empty($issue['image_path'])): ?>
                    <a class="ops-proof-thumb" href="<?= htmlspecialchars(assetUrl((string)$issue['image_path'])) ?>" target="_blank" rel="noopener">
                      <img src="<?= htmlspecialchars(assetUrl((string)$issue['image_path'])) ?>" alt="Minh chứng sự cố">
                    </a>
                  <?php endif; ?>

                  <?php if (!empty($issue['landlord_note'])): ?>
                    <div class="ops-card-note" style="margin-top:10px;">Ghi chú xử lý: <?= nl2br(htmlspecialchars((string)$issue['landlord_note'])) ?></div>
                  <?php endif; ?>

                  <form method="post" action="?route=room-ops" class="ops-item-form-grid">
                    <input type="hidden" name="room_id" value="<?= (int)$room['id'] ?>">
                    <input type="hidden" name="action" value="update-tenant-issue">
                    <input type="hidden" name="issue_id" value="<?= (int)$issue['id'] ?>">

                    <div class="ops-field">
                      <label class="ops-label">Trạng thái phiếu</label>
                      <select name="issue_status" class="form-control">
                        <?php foreach ($issueStatusOptions as $value => $label): ?>
                          <option value="<?= htmlspecialchars($value) ?>" <?= $issueStatus === $value ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>

                    <div class="ops-field">
                      <label class="ops-label">Chi phí sửa chữa</label>
                      <input type="number" name="repair_cost" class="form-control" min="0" step="1000" value="<?= htmlspecialchars((string)($issue['repair_cost'] ?? 0)) ?>">
                    </div>

                    <div class="ops-field full">
                      <label class="ops-label">Ghi chú nội bộ / phản hồi</label>
                      <textarea name="landlord_note" class="form-control" rows="3" placeholder="Ví dụ: Đã gọi thợ, chờ thay linh kiện vào chiều mai."><?= htmlspecialchars((string)($issue['landlord_note'] ?? '')) ?></textarea>
                    </div>

                    <div class="ops-field full">
                      <div class="ops-form-actions">
                        <button class="btn btn-primary btn-sm" type="submit">Cập nhật phiếu</button>
                      </div>
                    </div>
                  </form>
                </article>
              <?php endforeach; ?>
            </div>
          </section>
        </div>

        <aside class="ops-side">
          <section class="ops-side-highlight">
            <strong>Việc cần xử lý</strong>
            <p>
              <?=
                count($overdueInvoices) > 0
                  ? ('Có ' . count($overdueInvoices) . ' hoá đơn đã quá hạn và ' . count($dueSoonInvoices) . ' hoá đơn gần đến hạn.')
                  : (count($dueSoonInvoices) > 0
                      ? ('Có ' . count($dueSoonInvoices) . ' hoá đơn gần đến hạn, nên nhắc nhẹ người thuê.')
                      : (count($unpaidInvoices) > 0
                          ? ('Hiện có ' . count($unpaidInvoices) . ' hoá đơn chưa thanh toán.')
                          : 'Chưa có hoá đơn tồn, có thể tiếp tục theo dõi hợp đồng và công tơ.'))
              ?>
            </p>
          </section>

          <section class="ops-card">
            <div class="ops-card-head">
              <div>
                <h2>Tóm tắt vận hành</h2>
                <p class="ops-card-note">Các chỉ số cần nhìn nhanh trước khi gọi khách hoặc chốt thu tiền.</p>
              </div>
            </div>

            <div class="ops-kpi-grid">
              <div class="ops-kpi">
                <div class="ops-kpi-label">Tiền thuê tháng</div>
                <div class="ops-kpi-value"><?= number_format((int)($profile['monthly_rent'] ?? $room['price'] ?? 0), 0, ',', '.') ?>đ</div>
              </div>
              <div class="ops-kpi">
                <div class="ops-kpi-label">Tiền cọc</div>
                <div class="ops-kpi-value"><?= number_format((int)($profile['deposit_amount'] ?? 0), 0, ',', '.') ?>đ</div>
              </div>
              <div class="ops-kpi">
                <div class="ops-kpi-label">Phí dịch vụ</div>
                <div class="ops-kpi-value"><?= number_format($serviceFee, 0, ',', '.') ?>đ</div>
              </div>
              <div class="ops-kpi">
                <div class="ops-kpi-label">Hoá đơn chưa thanh toán</div>
                <div class="ops-kpi-value">
                  <?= count($unpaidInvoices) ?>
                </div>
              </div>
              <div class="ops-kpi">
                <div class="ops-kpi-label">Hợp đồng còn lại</div>
                <div class="ops-kpi-value">
                  <?php if ($contractDaysLeft === null): ?>
                    —
                  <?php else: ?>
                    <?= $contractDaysLeft ?> ngày
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </section>

          <section class="ops-card">
            <div class="ops-card-head">
              <div>
                <h2>Thông tin bài đăng</h2>
                <p class="ops-card-note">Liên kết giữa hồ sơ vận hành và tin công khai.</p>
              </div>
            </div>

            <div class="ops-room-list">
              <div class="ops-room-item">
                <div>
                  <strong>Giá niêm yết</strong>
                  <span><?= number_format((int)($room['price'] ?? 0), 0, ',', '.') ?> đ/tháng</span>
                </div>
              </div>
              <div class="ops-room-item">
                <div>
                  <strong>Điện / nước</strong>
                  <span>
                    Điện: <?= !empty($room['electric_price']) ? number_format((int)$room['electric_price'], 0, ',', '.') . ' đ/kWh' : 'chưa có' ?><br>
                    Nước: <?= !empty($room['water_price']) ? number_format((int)$room['water_price'], 0, ',', '.') . ' đ/m³' : 'chưa có' ?>
                  </span>
                </div>
              </div>
              <div class="ops-room-item">
                <div>
                  <strong>Phí dịch vụ cố định</strong>
                  <span><?= number_format($serviceFee, 0, ',', '.') ?> đ/tháng</span>
                </div>
              </div>
              <div class="ops-room-item">
                <div>
                  <strong>Nhu cầu 24h gần nhất</strong>
                  <span><?= countRoomLeadsRecent((int)$room['id']) ?> người quan tâm</span>
                </div>
              </div>
              <div class="ops-room-item">
                <div>
                  <strong>Bài đăng hiện tại</strong>
                  <span><?= htmlspecialchars((string)($room['status'] ?? 'pending')) ?></span>
                </div>
              </div>
            </div>
          </section>
        </aside>
      </div>

      <section class="ops-card ops-anchor-section" id="ops-invoice-history">
        <div class="ops-card-head">
          <div>
            <h2>Lịch sử hoá đơn</h2>
            <p class="ops-card-note">Theo dõi từng tháng, chỉ số điện nước và trạng thái thanh toán.</p>
          </div>
        </div>

        <?php if (empty($roomInvoices)): ?>
          <div class="ops-empty">Chưa có hóa đơn nào cho phòng này.</div>
        <?php else: ?>
          <div class="ops-history-list">
            <?php foreach ($roomInvoices as $invoice): ?>
              <?php
                $displayStatus = (string)($invoice['display_status'] ?? 'unpaid');
                $invoiceStatusLabel = $invoiceStatusLabels[$displayStatus] ?? $displayStatus;
                $reminderState = (string)($invoice['reminder_state'] ?? 'unpaid');
                $statusClass = $invoiceStatusClassMap[$displayStatus] ?? 'unpaid';
              ?>
              <article class="ops-history-item">
                <div class="ops-history-top">
                  <div>
                    <div class="ops-history-title">Hoá đơn <?= htmlspecialchars((string)$invoice['billing_month']) ?></div>
                    <div class="ops-card-note">Tạo lúc <?= htmlspecialchars((string)($invoice['created_at'] ?? '')) ?></div>
                  </div>
                  <div style="display:flex; gap:8px; flex-wrap:wrap;">
                    <span class="ops-status <?= htmlspecialchars($statusClass) ?>"><?= htmlspecialchars($invoiceStatusLabel) ?></span>
                    <?php if ($reminderState === 'due_soon' || $reminderState === 'overdue'): ?>
                      <span class="ops-status <?= htmlspecialchars($reminderState) ?>"><?= htmlspecialchars($invoiceReminderLabels[$reminderState] ?? $reminderState) ?></span>
                    <?php endif; ?>
                  </div>
                </div>

                <div class="ops-history-grid">
                  <div class="ops-history-cell">
                    <small>Tổng tiền</small>
                    <strong><?= number_format((int)($invoice['total_amount'] ?? 0), 0, ',', '.') ?> đ</strong>
                  </div>
                  <div class="ops-history-cell">
                    <small>Tiền thuê</small>
                    <strong><?= number_format((int)($invoice['rent_amount'] ?? 0), 0, ',', '.') ?> đ</strong>
                  </div>
                  <div class="ops-history-cell">
                    <small>Phí dịch vụ</small>
                    <strong><?= number_format((int)($invoice['service_amount'] ?? 0), 0, ',', '.') ?> đ</strong>
                  </div>
                  <div class="ops-history-cell">
                    <small>Điện</small>
                    <strong>
                      <?= ($invoice['electric_units'] ?? null) !== null ? ((int)$invoice['electric_units'] . ' số · ' . number_format((int)($invoice['electric_amount'] ?? 0), 0, ',', '.') . ' đ') : '—' ?>
                    </strong>
                  </div>
                  <div class="ops-history-cell">
                    <small>Nước</small>
                    <strong>
                      <?= ($invoice['water_units'] ?? null) !== null ? ((int)$invoice['water_units'] . ' số · ' . number_format((int)($invoice['water_amount'] ?? 0), 0, ',', '.') . ' đ') : '—' ?>
                    </strong>
                  </div>
                  <div class="ops-history-cell">
                    <small>Hạn thanh toán</small>
                    <strong><?= !empty($invoice['due_date']) ? htmlspecialchars((string)$invoice['due_date']) : '—' ?></strong>
                  </div>
                  <div class="ops-history-cell">
                    <small>Đã thu / còn thiếu</small>
                    <strong><?= number_format((int)($invoice['amount_paid'] ?? 0), 0, ',', '.') ?> đ / <?= number_format((int)($invoice['amount_due'] ?? 0), 0, ',', '.') ?> đ</strong>
                  </div>
                  <div class="ops-history-cell">
                    <small>Ngày thanh toán</small>
                    <strong><?= !empty($invoice['paid_date']) ? htmlspecialchars((string)$invoice['paid_date']) : '—' ?></strong>
                  </div>
                  <div class="ops-history-cell">
                    <small>Phương thức thu</small>
                    <strong><?= !empty($invoice['payment_method']) ? htmlspecialchars((string)$invoice['payment_method']) : '—' ?></strong>
                  </div>
                  <div class="ops-history-cell">
                    <small>Điện cũ → mới</small>
                    <strong><?= ($invoice['electric_reading_old'] ?? null) !== null || ($invoice['electric_reading_new'] ?? null) !== null ? ((string)($invoice['electric_reading_old'] ?? '—') . ' → ' . (string)($invoice['electric_reading_new'] ?? '—')) : '—' ?></strong>
                  </div>
                  <div class="ops-history-cell">
                    <small>Nước cũ → mới</small>
                    <strong><?= ($invoice['water_reading_old'] ?? null) !== null || ($invoice['water_reading_new'] ?? null) !== null ? ((string)($invoice['water_reading_old'] ?? '—') . ' → ' . (string)($invoice['water_reading_new'] ?? '—')) : '—' ?></strong>
                  </div>
                </div>

                <?php if (!empty($invoice['note'])): ?>
                  <div class="ops-card-note" style="margin-top:10px;"><?= nl2br(htmlspecialchars((string)$invoice['note'])) ?></div>
                <?php endif; ?>

                <form method="post" action="?route=room-ops" class="ops-item-form-grid">
                  <input type="hidden" name="room_id" value="<?= (int)$room['id'] ?>">
                  <input type="hidden" name="action" value="mark-room-invoice">
                  <input type="hidden" name="invoice_id" value="<?= (int)$invoice['id'] ?>">

                  <div class="ops-field">
                    <label class="ops-label">Trạng thái hóa đơn</label>
                    <select name="invoice_status" class="form-control">
                      <?php foreach ($invoiceStatusOptions as $value => $label): ?>
                        <option value="<?= htmlspecialchars($value) ?>" <?= $displayStatus === $value ? 'selected' : '' ?>><?= htmlspecialchars($invoiceStatusLabels[$value] ?? $label) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>

                  <div class="ops-field">
                    <label class="ops-label">Số tiền đã thu</label>
                    <input type="number" name="amount_paid" class="form-control" min="0" step="1000" value="<?= htmlspecialchars((string)($invoice['amount_paid'] ?? 0)) ?>">
                  </div>

                  <div class="ops-field">
                    <label class="ops-label">Phương thức thu</label>
                    <input type="text" name="payment_method" class="form-control" value="<?= htmlspecialchars((string)($invoice['payment_method'] ?? '')) ?>" placeholder="Ví dụ: Chuyển khoản, tiền mặt">
                  </div>

                  <div class="ops-field full">
                    <div class="ops-form-actions">
                      <button class="btn btn-primary btn-sm" type="submit">Cập nhật hóa đơn</button>
                    </div>
                  </div>
                </form>
              </article>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </section>

      <section class="ops-card ops-anchor-section" id="ops-checkout">
        <div class="ops-card-head">
          <div>
            <h2>Lịch sử thuê và hoàn cọc</h2>
            <p class="ops-card-note">Biết người thuê đã ở từ khi nào, từng ở phòng nào và đã chốt hoàn cọc ra sao.</p>
          </div>
        </div>

        <?php if ($activeStay): ?>
          <form method="post" action="?route=room-ops" class="ops-form-grid" style="margin-bottom:16px;">
            <input type="hidden" name="room_id" value="<?= (int)$room['id'] ?>">
            <input type="hidden" name="action" value="settle-room-deposit">
            <input type="hidden" name="stay_id" value="<?= (int)$activeStay['id'] ?>">

            <div class="ops-field">
              <label class="ops-label">Tiền cọc ban đầu</label>
              <div class="ops-history-cell"><strong><?= number_format((int)($activeStay['deposit_amount'] ?? 0), 0, ',', '.') ?> đ</strong></div>
            </div>

            <div class="ops-field">
              <label class="ops-label" for="depositDeductionAmount">Khấu trừ hư hỏng</label>
              <input id="depositDeductionAmount" type="number" name="deposit_deduction_amount" class="form-control" min="0" step="1000" value="<?= htmlspecialchars((string)($activeStay['deposit_deduction_amount'] ?? 0)) ?>">
            </div>

            <div class="ops-field">
              <label class="ops-label" for="depositRefundAmount">Số tiền hoàn lại</label>
              <input id="depositRefundAmount" type="number" name="deposit_refund_amount" class="form-control" min="0" step="1000" value="<?= htmlspecialchars((string)($activeStay['deposit_refund_amount'] ?? max(0, (int)($activeStay['deposit_amount'] ?? 0) - (int)($activeStay['deposit_deduction_amount'] ?? 0)))) ?>">
            </div>

            <div class="ops-field">
              <label class="ops-label" for="depositSettledAt">Ngày chốt cọc</label>
              <input id="depositSettledAt" type="date" name="settled_at" class="form-control" value="<?= htmlspecialchars((string)($activeStay['settled_at'] ?? date('Y-m-d'))) ?>">
            </div>

            <div class="ops-field">
              <label class="ops-label" for="stayEndedAt">Ngày kết thúc thuê</label>
              <input id="stayEndedAt" type="date" name="ended_at" class="form-control" value="<?= htmlspecialchars((string)($activeStay['ended_at'] ?? date('Y-m-d'))) ?>">
            </div>

            <div class="ops-field full">
              <label class="ops-label" for="settlementNote">Ghi chú hoàn cọc</label>
              <textarea id="settlementNote" name="settlement_note" class="form-control" rows="3" placeholder="Ví dụ: Khấu trừ 300.000đ do thay khoá và sơn lại tường."><?= htmlspecialchars((string)($activeStay['settlement_note'] ?? '')) ?></textarea>
            </div>

            <div class="ops-field full">
              <div class="ops-form-actions">
                <button class="btn btn-primary" type="submit">Chốt hoàn cọc</button>
              </div>
            </div>
          </form>
        <?php endif; ?>

        <?php if (empty($stayHistory)): ?>
          <div class="ops-empty">Chưa có lịch sử thuê nào cho phòng này.</div>
        <?php else: ?>
          <div class="ops-history-list">
            <?php foreach ($stayHistory as $stay): ?>
              <article class="ops-history-item">
                <div class="ops-history-top">
                  <div>
                    <div class="ops-history-title"><?= htmlspecialchars((string)($stay['tenant_name'] ?? 'Người thuê')) ?></div>
                    <div class="ops-card-note"><?= htmlspecialchars((string)($stay['started_at'] ?? '')) ?> → <?= !empty($stay['ended_at']) ? htmlspecialchars((string)$stay['ended_at']) : 'đang ở' ?></div>
                  </div>
                  <span class="ops-status <?= htmlspecialchars((string)($stay['status'] ?? 'active') === 'active' ? 'due_soon' : 'paid') ?>">
                    <?= htmlspecialchars((string)($stay['status'] ?? 'active') === 'active' ? 'Đang thuê' : 'Đã rời phòng') ?>
                  </span>
                </div>
                <div class="ops-history-grid">
                  <div class="ops-history-cell">
                    <small>SĐT</small>
                    <strong><?= htmlspecialchars((string)($stay['tenant_phone'] ?? '')) ?></strong>
                  </div>
                  <div class="ops-history-cell">
                    <small>Tiền thuê</small>
                    <strong><?= number_format((int)($stay['rent_amount'] ?? 0), 0, ',', '.') ?> đ</strong>
                  </div>
                  <div class="ops-history-cell">
                    <small>Tiền cọc</small>
                    <strong><?= number_format((int)($stay['deposit_amount'] ?? 0), 0, ',', '.') ?> đ</strong>
                  </div>
                  <div class="ops-history-cell">
                    <small>Thói quen thanh toán</small>
                    <strong><?= htmlspecialchars((string)($stay['payment_regularity_label'] ?? 'Chưa có dữ liệu')) ?></strong>
                  </div>
                  <div class="ops-history-cell">
                    <small>Kỳ đã trả</small>
                    <strong><?= (int)($stay['payment_paid_count'] ?? 0) ?></strong>
                  </div>
                  <div class="ops-history-cell">
                    <small>Kỳ còn tồn</small>
                    <strong><?= (int)($stay['payment_unpaid_count'] ?? 0) ?></strong>
                  </div>
                  <div class="ops-history-cell">
                    <small>Khấu trừ</small>
                    <strong><?= number_format((int)($stay['deposit_deduction_amount'] ?? 0), 0, ',', '.') ?> đ</strong>
                  </div>
                  <div class="ops-history-cell">
                    <small>Hoàn lại</small>
                    <strong><?= number_format((int)($stay['deposit_refund_amount'] ?? 0), 0, ',', '.') ?> đ</strong>
                  </div>
                </div>
                <?php if (!empty($stay['settlement_note'])): ?>
                  <div class="ops-note-copy"><?= nl2br(htmlspecialchars((string)$stay['settlement_note'])) ?></div>
                <?php endif; ?>
              </article>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </section>

      <section class="ops-card ops-anchor-section" id="ops-meters">
        <div class="ops-card-head">
          <div>
            <h2>Nhật ký chỉ số điện nước</h2>
            <p class="ops-card-note">Lưu theo từng kỳ để nhìn ra tháng nào tăng bất thường.</p>
          </div>
        </div>

        <?php if (empty($meterLogs)): ?>
          <div class="ops-empty">Chưa có log công tơ nào. Tạo hóa đơn tháng sẽ tự sinh log theo kỳ.</div>
        <?php else: ?>
          <div class="ops-history-list">
            <?php foreach ($meterLogs as $log): ?>
              <article class="ops-history-item">
                <div class="ops-history-top">
                  <div>
                    <div class="ops-history-title">Kỳ <?= htmlspecialchars((string)($log['billing_month'] ?? '')) ?></div>
                    <div class="ops-card-note">Ghi lúc <?= htmlspecialchars((string)($log['created_at'] ?? '')) ?></div>
                  </div>
                  <?php if (!empty($log['usage_alerts'])): ?>
                    <div style="display:flex; gap:8px; flex-wrap:wrap;">
                      <?php foreach ($log['usage_alerts'] as $alert): ?>
                        <span class="ops-status overdue"><?= htmlspecialchars((string)$alert) ?></span>
                      <?php endforeach; ?>
                    </div>
                  <?php endif; ?>
                </div>
                <div class="ops-history-grid">
                  <div class="ops-history-cell">
                    <small>Điện cũ → mới</small>
                    <strong><?= ($log['electric_reading_old'] ?? null) !== null || ($log['electric_reading_new'] ?? null) !== null ? ((string)($log['electric_reading_old'] ?? '—') . ' → ' . (string)($log['electric_reading_new'] ?? '—')) : '—' ?></strong>
                  </div>
                  <div class="ops-history-cell">
                    <small>Số điện dùng</small>
                    <strong><?= ($log['electric_units'] ?? null) !== null ? (int)$log['electric_units'] . ' số' : '—' ?></strong>
                  </div>
                  <div class="ops-history-cell">
                    <small>Nước cũ → mới</small>
                    <strong><?= ($log['water_reading_old'] ?? null) !== null || ($log['water_reading_new'] ?? null) !== null ? ((string)($log['water_reading_old'] ?? '—') . ' → ' . (string)($log['water_reading_new'] ?? '—')) : '—' ?></strong>
                  </div>
                  <div class="ops-history-cell">
                    <small>Số nước dùng</small>
                    <strong><?= ($log['water_units'] ?? null) !== null ? (int)$log['water_units'] . ' số' : '—' ?></strong>
                  </div>
                </div>
              </article>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </section>

      <section class="ops-card ops-anchor-section" id="ops-handover">
        <div class="ops-card-head">
          <div>
            <h2>Ảnh bàn giao và tình trạng phòng</h2>
            <p class="ops-card-note">Lưu ảnh tường, giường, thiết bị lúc nhận hoặc trả phòng để đối chiếu nhanh.</p>
          </div>
        </div>

        <form method="post" action="?route=room-ops" enctype="multipart/form-data" class="ops-form-grid">
          <input type="hidden" name="room_id" value="<?= (int)$room['id'] ?>">
          <input type="hidden" name="action" value="create-room-handover">

          <div class="ops-field">
            <label class="ops-label" for="handoverType">Kiểu bàn giao</label>
            <select id="handoverType" name="handover_type" class="form-control">
              <?php foreach ($handoverTypeOptions as $value => $label): ?>
                <option value="<?= htmlspecialchars($value) ?>"><?= htmlspecialchars($label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="ops-field">
            <label class="ops-label" for="handoverWallImage">Ảnh tường</label>
            <input id="handoverWallImage" type="file" name="handover_wall_image" class="form-control" accept="image/*">
          </div>

          <div class="ops-field">
            <label class="ops-label" for="handoverBedImage">Ảnh giường</label>
            <input id="handoverBedImage" type="file" name="handover_bed_image" class="form-control" accept="image/*">
          </div>

          <div class="ops-field">
            <label class="ops-label" for="handoverEquipmentImage">Ảnh thiết bị</label>
            <input id="handoverEquipmentImage" type="file" name="handover_equipment_image" class="form-control" accept="image/*">
          </div>

          <div class="ops-field full">
            <label class="ops-label" for="handoverNote">Ghi chú tình trạng</label>
            <textarea id="handoverNote" name="handover_note" class="form-control" rows="3" placeholder="Ví dụ: Tường góc phải có vết xước nhẹ, quạt trần hoạt động bình thường, nệm còn tốt."></textarea>
          </div>

          <div class="ops-field full">
            <div class="ops-form-actions">
              <button class="btn btn-primary" type="submit">Lưu biên bản bàn giao</button>
            </div>
          </div>
        </form>

        <div class="ops-history-list" style="margin-top:16px;">
          <?php if (empty($handoverRecords)): ?>
            <div class="ops-empty">Chưa có biên bản bàn giao nào cho phòng này.</div>
          <?php endif; ?>
          <?php foreach ($handoverRecords as $handover): ?>
            <?php $handoverTypeLabel = $handoverTypeOptions[$handover['handover_type'] ?? 'move_in'] ?? ($handover['handover_type'] ?? 'Bàn giao'); ?>
            <article class="ops-history-item">
              <div class="ops-history-top">
                <div>
                  <div class="ops-history-title"><?= htmlspecialchars((string)$handoverTypeLabel) ?></div>
                  <div class="ops-card-note">Ghi lúc <?= htmlspecialchars((string)($handover['created_at'] ?? '')) ?></div>
                </div>
                <span class="ops-status due_soon"><?= htmlspecialchars((string)$handoverTypeLabel) ?></span>
              </div>
              <?php if (!empty($handover['note'])): ?>
                <div class="ops-note-copy"><?= nl2br(htmlspecialchars((string)$handover['note'])) ?></div>
              <?php endif; ?>
              <div class="ops-photo-grid">
                <?php foreach ([
                  'wall_image' => 'Ảnh tường',
                  'bed_image' => 'Ảnh giường',
                  'equipment_image' => 'Ảnh thiết bị',
                ] as $field => $label): ?>
                  <?php if (!empty($handover[$field])): ?>
                    <a class="ops-photo-card" href="<?= htmlspecialchars(assetUrl((string)$handover[$field])) ?>" target="_blank" rel="noopener">
                      <img src="<?= htmlspecialchars(assetUrl((string)$handover[$field])) ?>" alt="<?= htmlspecialchars($label) ?>">
                      <span><?= htmlspecialchars($label) ?></span>
                    </a>
                  <?php endif; ?>
                <?php endforeach; ?>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      </section>
    </div>
  </div>
</div>

