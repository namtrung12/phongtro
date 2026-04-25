<?php $pageTitle = 'Bảng điều khiển chủ trọ'; ?>
<div class="admin-shell">
  <aside class="admin-menu">
    <a href="?route=dashboard" class="<?= ($activeMenu ?? '') === 'home' ? 'active' : '' ?>">Bảng điều khiển</a>
    <a href="?route=dashboard&tab=lead#lead" class="<?= ($activeMenu ?? '') === 'leads' ? 'active' : '' ?>">Nhu cầu</a>
    <a href="?route=my-rooms" class="<?= ($activeMenu ?? '') === 'rooms' && (($_GET['focus'] ?? '') === '') ? 'active' : '' ?>">Phòng trọ</a>
    <a href="?route=my-rooms&focus=tenants" class="<?= (($_GET['focus'] ?? '') === 'tenants') ? 'active' : '' ?>">Khách thuê</a>
    <a href="?route=my-rooms&focus=contracts" class="<?= (($_GET['focus'] ?? '') === 'contracts') ? 'active' : '' ?>">Hợp đồng</a>
    <a href="?route=my-rooms&focus=invoices" class="<?= (($_GET['focus'] ?? '') === 'invoices') ? 'active' : '' ?>">Hóa đơn</a>
    <a href="?route=payment-history" class="<?= ($activeMenu ?? '') === 'payments' ? 'active' : '' ?>">Thanh toán</a>
    <a href="?route=my-rooms&focus=issues" class="<?= (($_GET['focus'] ?? '') === 'issues') ? 'active' : '' ?>">Sự cố</a>
    <a href="?route=dashboard" class="<?= ($activeMenu ?? '') === 'home' ? 'active' : '' ?>">Báo cáo</a>
  </aside>
  <div>
    <?php
      $closedStatuses = leadClosedStatuses();
      $blockedStatuses = ['invalid'];
      $today = date('Y-m-d');
      $leads = $leads ?? [];
      $leadMarketplace = $leadMarketplace ?? [];
      $marketAvailablePosts = array_values($leadMarketplace['available_posts'] ?? []);
      $marketAvailableCount = (int)($leadMarketplace['available_count'] ?? count($marketAvailablePosts));
      $marketFreshCount = (int)($leadMarketplace['fresh_count'] ?? 0);
      $isLeadPurchasedRow = function($lead) {
          return leadHasUnlockedContact(is_array($lead) ? $lead : []);
      };
      $leadToday = array_values(array_filter($leads, function($lead) use ($today) {
          return strpos((string)($lead['created_at'] ?? ''), $today) === 0;
      }));
      $leadPurchased = array_values(array_filter($leads, function($lead) use ($isLeadPurchasedRow) {
          return $isLeadPurchasedRow($lead);
      }));
      $leadUnpaid = array_values(array_filter($leads, function($lead) use ($isLeadPurchasedRow, $blockedStatuses) {
          $status = (string)($lead['status'] ?? 'new');
          return !$isLeadPurchasedRow($lead) && !in_array($status, $blockedStatuses, true);
      }));
      $leadNew = (int)($leadStats['new'] ?? 0);
      $leadPurchasedCount = count($leadPurchased);
      $leadUnpaidCount = count($leadUnpaid);
      $leadTodayCount = count($leadToday);
      $leadCalled = (int)($leadStats['contacted'] ?? 0);
      $leadNegotiating = (int)($leadStats['negotiating'] ?? 0);
      $leadInvalid = (int)($leadStats['invalid'] ?? 0);
      $leadClosedCount = (int)($leadStats['closed'] ?? 0) + (int)($leadStats['used'] ?? 0);
      $hotHour = $insights['hot_hours'][0] ?? null;
      $hotArea = $insights['hot_areas'][0] ?? null;
      $ps = $insights['price_stats'] ?? null;
      $opsDashboard = $opsDashboard ?? [];
      $opsRooms = array_values($opsDashboard['rooms'] ?? []);
      $opsAttentionRooms = array_values($opsDashboard['attention_rooms'] ?? []);
      $opsTotalRooms = (int)($opsDashboard['total_rooms'] ?? 0);
      $opsOccupiedRooms = (int)($opsDashboard['occupied_rooms'] ?? 0);
      $opsVacantRooms = (int)($opsDashboard['vacant_rooms'] ?? 0);
      $opsMaintenanceRooms = (int)($opsDashboard['maintenance_rooms'] ?? 0);
      $opsUnpaidInvoices = (int)($opsDashboard['unpaid_invoices'] ?? 0);
      $opsDueSoonInvoices = (int)($opsDashboard['due_soon_invoices'] ?? 0);
      $opsOverdueInvoices = (int)($opsDashboard['overdue_invoices'] ?? 0);
      $opsExpiringContracts = (int)($opsDashboard['expiring_contracts'] ?? 0);
      $opsOpenIssues = (int)($opsDashboard['open_issues'] ?? 0);
      $opsRevenueMonth = (int)($opsDashboard['revenue_month'] ?? 0);
      $occupancyRate = $opsTotalRooms > 0 ? (int)round(($opsOccupiedRooms / $opsTotalRooms) * 100) : 0;
      $leadCloseRate = $leadPurchasedCount > 0 ? (int)round(($leadClosedCount / $leadPurchasedCount) * 100) : 0;
      $opsAttentionTotal = 0;
      foreach ($opsRooms as $opsRoom) {
          if (!empty($opsRoom['ops_attention'])) {
              $opsAttentionTotal++;
          }
      }
      $opsLifecycle = [
          ['label' => 'Phòng trống', 'value' => $opsVacantRooms, 'note' => 'Các phòng sẵn sàng đón người thuê mới.'],
          ['label' => 'Đã có người thuê', 'value' => $opsOccupiedRooms, 'note' => 'Phòng đã đi vào vận hành thực tế.'],
          ['label' => 'Đang bảo trì', 'value' => $opsMaintenanceRooms, 'note' => 'Các phòng tạm dừng khai thác để sửa chữa hoặc xử lý sự cố.'],
          ['label' => 'Hợp đồng sắp hết', 'value' => $opsExpiringContracts, 'note' => 'Cần gia hạn hoặc chuẩn bị phương án tiếp theo.'],
          ['label' => 'Hóa đơn cần thu', 'value' => $opsUnpaidInvoices, 'note' => 'Bao gồm các kỳ gần hạn và chưa thanh toán.'],
          ['label' => 'Phiếu sự cố đang mở', 'value' => $opsOpenIssues, 'note' => 'Các yêu cầu hỗ trợ và sự cố chưa được xử lý xong.'],
      ];
      $statusMap = [
          'new' => 'Mới tạo',
          'opened' => 'Mới mua',
          'contacted' => 'Đã liên hệ',
          'negotiating' => 'Đang thương lượng',
          'closed' => 'Chốt thành công',
          'invalid' => 'Thất bại',
          'sold' => 'Mới mua',
          'used' => 'Chốt thành công',
          'paid' => 'Mới mua',
      ];
      $leadStageOptions = [
          'contacted' => 'Đã liên hệ',
          'negotiating' => 'Đang thương lượng',
          'closed' => 'Chốt thành công',
          'invalid' => 'Thất bại',
      ];

      $renderLeadHistory = function(array $lead): string {
          $history = array_slice($lead['interaction_history_preview'] ?? [], 0, 3);
          ob_start();
          ?>
          <div class="lead-history-list">
            <?php if (empty($history)): ?>
              <div class="lead-history-empty">Chưa có tương tác nào.</div>
            <?php endif; ?>
            <?php foreach ($history as $event): ?>
              <div class="lead-history-item">
                <strong><?= htmlspecialchars((string)($event['label'] ?? 'Cập nhật')) ?></strong>
                <span><?= htmlspecialchars((string)($event['created_at'] ?? '')) ?></span>
                <?php if (!empty($event['note'])): ?>
                  <small><?= htmlspecialchars((string)$event['note']) ?></small>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>
          <?php
          return ob_get_clean();
      };

      $renderLeadActions = function(array $lead, bool $isPurchased, bool $isClosed, bool $isBlocked, int $leadPrice, string $historyAt) use ($leadStageOptions): string {
          ob_start();
          $source = (string)($lead['source'] ?? '');
          $isMarketplaceLead = $source === 'marketplace' && !empty($lead['tenant_post_id']);
          ?>
          <?php if (!$isPurchased && !$isBlocked): ?>
            <form method="post" action="?route=<?= $isMarketplaceLead ? 'open-marketplace-lead' : 'open-lead' ?>" class="d-inline">
              <input type="hidden" name="<?= $isMarketplaceLead ? 'tenant_post_id' : 'lead_id' ?>" value="<?= (int)($isMarketplaceLead ? ($lead['tenant_post_id'] ?? 0) : ($lead['id'] ?? 0)) ?>">
              <button class="btn btn-sm btn-primary" type="submit"><?= $isMarketplaceLead ? 'Mua từ sàn' : 'Mua nhu cầu' ?> - <?= number_format((int)$leadPrice, 0, ',', '.') ?>đ</button>
            </form>
          <?php elseif ($isPurchased && !$isClosed && !$isBlocked): ?>
            <form method="post" action="?route=update-lead-stage" class="lead-stage-form">
              <input type="hidden" name="lead_id" value="<?= (int)$lead['id'] ?>">
              <select name="stage" class="form-control">
                <?php foreach ($leadStageOptions as $value => $label): ?>
                  <option value="<?= htmlspecialchars($value) ?>" <?= ((string)($lead['status'] ?? '') === $value) ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                <?php endforeach; ?>
              </select>
              <button class="btn btn-sm btn-success" type="submit">Cập nhật</button>
            </form>
            <?php if ((int)($lead['room_id'] ?? 0) > 0): ?>
              <a href="?route=room-ops&id=<?= (int)$lead['room_id'] ?>#ops-before-rent" class="btn btn-sm btn-outline">Hồ sơ phòng</a>
            <?php endif; ?>
          <?php elseif ($isPurchased): ?>
            <span class="text-muted small lead-static-note"><?= $historyAt !== '' ? ('Đã mua lúc ' . htmlspecialchars($historyAt)) : 'Đã mua' ?></span>
            <?php if ((int)($lead['room_id'] ?? 0) > 0): ?>
              <a href="?route=room-ops&id=<?= (int)$lead['room_id'] ?>" class="btn btn-sm btn-outline">Mở hồ sơ phòng</a>
            <?php endif; ?>
          <?php else: ?>
            <span class="text-muted small lead-static-note">Nhu cầu đã dừng ở trạng thái thất bại</span>
          <?php endif; ?>
          <?php
          return ob_get_clean();
      };

      $renderLeadTable = function(array $rows, string $emptyText) use ($isLeadPurchasedRow, $closedStatuses, $blockedStatuses, $statusMap, $renderLeadActions, $renderLeadHistory) {
          ob_start();
          ?>
          <div class="lead-table-desktop">
            <div class="table-responsive">
              <table class="table align-middle lead-table">
                <thead>
                  <tr>
                    <th>Mã</th>
                    <th>Nhu cầu</th>
                    <th>Khách</th>
                    <th>Phù hợp</th>
                    <th>Trạng thái</th>
                    <th>Lịch sử</th>
                    <th>Hành động</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($rows)): ?>
                    <tr><td colspan="7"><?= htmlspecialchars($emptyText) ?></td></tr>
                  <?php endif; ?>
                  <?php foreach ($rows as $lead): ?>
                    <?php
                      $status = (string)($lead['status'] ?? 'new');
                      $isPurchased = $isLeadPurchasedRow($lead);
                      $isClosed = in_array($status, $closedStatuses, true);
                      $isBlocked = in_array($status, $blockedStatuses, true);
                      $statusLabel = ($isPurchased && $isBlocked) ? 'Đã mua - lỗi' : ($statusMap[$status] ?? $status);
                      $badgeClass = $isBlocked ? 'badge-outline' : ($isClosed ? 'badge-success' : ($status === 'negotiating' ? 'badge-warning' : ($isPurchased ? 'badge-success' : 'badge-warning')));
                      $leadPrice = effectiveLeadPriceFromRow($lead);
                      $phoneVerified = !empty($lead['phone_verified']);
                      $tenantName = (string)($lead['tenant_name'] ?? '');
                      $tenantPhone = (string)($lead['tenant_phone'] ?? '');
                      $openedAt = trim((string)($lead['opened_at'] ?? ''));
                      $purchasedAt = trim((string)($lead['purchased_at'] ?? ''));
                      $historyAt = $openedAt !== '' ? $openedAt : $purchasedAt;
                      $displayTenantName = $isPurchased ? $tenantName : maskName($tenantName);
                      $displayTenantPhone = $isPurchased ? $tenantPhone : maskPhone($tenantPhone);
                    ?>
                    <tr>
                      <td>#<?= (int)$lead['id'] ?></td>
                      <td>
                        <div class="lead-cell-title"><?= htmlspecialchars((string)($lead['room_title'] ?? '')) ?></div>
                        <div class="lead-pill-row">
                          <span class="lead-source-pill"><?= htmlspecialchars((string)($lead['lead_source_label'] ?? 'Nhu cầu')) ?></span>
                          <span class="lead-source-pill alt"><?= htmlspecialchars((string)($lead['lead_freshness_label'] ?? '')) ?></span>
                        </div>
                        <div class="lead-subline">Khu vực: <?= htmlspecialchars((string)($lead['lead_area_label'] ?? 'Chưa rõ khu vực')) ?></div>
                        <div class="lead-subline">Ngân sách: <?= htmlspecialchars((string)($lead['lead_budget_label'] ?? 'Chưa chốt ngân sách')) ?> · <?= htmlspecialchars((string)($lead['lead_room_type_label'] ?? 'Chưa rõ loại phòng')) ?></div>
                        <div class="lead-subline">Cần thuê: <?= htmlspecialchars((string)($lead['lead_move_in_label'] ?? 'Chưa chốt thời gian')) ?></div>
                        <?php if (!empty($lead['lead_amenities_preview'])): ?>
                          <div class="lead-subline">Tiện nghi: <?= htmlspecialchars((string)$lead['lead_amenities_preview']) ?></div>
                        <?php endif; ?>
                      </td>
                      <td>
                        <div class="lead-cell-title"><?= htmlspecialchars($displayTenantName) ?></div>
                        <div class="lead-subline">SĐT: <?= htmlspecialchars($displayTenantPhone) ?></div>
                        <?php if (!empty($lead['lead_summary_note'])): ?>
                          <div class="lead-note-preview"><?= htmlspecialchars((string)$lead['lead_summary_note']) ?></div>
                        <?php endif; ?>
                      </td>
                      <td>
                        <div class="lead-fit-value"><?= htmlspecialchars((string)($lead['match_label'] ?? '0%')) ?></div>
                        <div class="lead-subline"><?= htmlspecialchars((string)($lead['match_suggestion'] ?? 'Chưa có gợi ý')) ?></div>
                        <?php if (!$isPurchased && !$isBlocked): ?>
                          <div class="lead-subline">Giá mở: <?= number_format((int)$leadPrice, 0, ',', '.') ?>đ</div>
                        <?php endif; ?>
                      </td>
                      <td>
                        <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($statusLabel) ?></span>
                        <?php if (!empty($lead['lead_priority_label'])): ?>
                          <span class="lead-source-pill alt"><?= htmlspecialchars((string)$lead['lead_priority_label']) ?></span>
                        <?php endif; ?>
                        <?php if ($phoneVerified): ?>
                          <span class="badge badge-success">SĐT đã được xác minh</span>
                        <?php endif; ?>
                        <?php if ($historyAt !== ''): ?>
                          <div class="lead-subline">Mở lúc: <?= htmlspecialchars($historyAt) ?></div>
                        <?php endif; ?>
                      </td>
                      <td><?= $renderLeadHistory($lead) ?></td>
                      <td class="lead-actions">
                        <?= $renderLeadActions($lead, $isPurchased, $isClosed, $isBlocked, $leadPrice, $historyAt) ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
          <div class="lead-table-mobile">
            <?php if (empty($rows)): ?>
              <div class="text-muted small"><?= htmlspecialchars($emptyText) ?></div>
            <?php endif; ?>
            <?php foreach ($rows as $lead): ?>
              <?php
                $status = (string)($lead['status'] ?? 'new');
                $isPurchased = $isLeadPurchasedRow($lead);
                $isClosed = in_array($status, $closedStatuses, true);
                $isBlocked = in_array($status, $blockedStatuses, true);
                $statusLabel = ($isPurchased && $isBlocked) ? 'Đã mua - lỗi' : ($statusMap[$status] ?? $status);
                $badgeClass = $isBlocked ? 'badge-outline' : ($isClosed ? 'badge-success' : ($status === 'negotiating' ? 'badge-warning' : ($isPurchased ? 'badge-success' : 'badge-warning')));
                $leadPrice = effectiveLeadPriceFromRow($lead);
                $tenantName = (string)($lead['tenant_name'] ?? '');
                $tenantPhone = (string)($lead['tenant_phone'] ?? '');
                $openedAt = trim((string)($lead['opened_at'] ?? ''));
                $purchasedAt = trim((string)($lead['purchased_at'] ?? ''));
                $historyAt = $openedAt !== '' ? $openedAt : $purchasedAt;
                $displayTenantName = $isPurchased ? $tenantName : maskName($tenantName);
                $displayTenantPhone = $isPurchased ? $tenantPhone : maskPhone($tenantPhone);
              ?>
              <article class="lead-mobile-item">
                <div class="lead-mobile-title">#<?= (int)$lead['id'] ?> · <?= htmlspecialchars((string)($lead['room_title'] ?? '')) ?></div>
                <div class="lead-pill-row" style="margin-bottom:6px;">
                  <span class="lead-source-pill"><?= htmlspecialchars((string)($lead['lead_source_label'] ?? 'Nhu cầu')) ?></span>
                  <span class="lead-source-pill alt"><?= htmlspecialchars((string)($lead['lead_freshness_label'] ?? '')) ?></span>
                </div>
                <div class="lead-mobile-contact">Khách: <?= htmlspecialchars($displayTenantName) ?></div>
                <div class="lead-mobile-contact">SĐT: <?= htmlspecialchars($displayTenantPhone) ?></div>
                <div class="lead-mobile-contact">Ngân sách: <?= htmlspecialchars((string)($lead['lead_budget_label'] ?? 'Chưa chốt')) ?></div>
                <div class="lead-mobile-contact">Khu vực: <?= htmlspecialchars((string)($lead['lead_area_label'] ?? 'Chưa rõ khu vực')) ?></div>
                <div class="lead-mobile-actions">
                  <?= $renderLeadActions($lead, $isPurchased, $isClosed, $isBlocked, $leadPrice, $historyAt) ?>
                </div>
                <details class="lead-mobile-details">
                  <summary>Xem chi tiết</summary>
                  <div class="lead-mobile-extra">
                    <div><strong>Phù hợp:</strong> <?= htmlspecialchars((string)($lead['match_label'] ?? '0%')) ?></div>
                    <div><strong>Gợi ý:</strong> <?= htmlspecialchars((string)($lead['match_suggestion'] ?? 'Chưa có gợi ý')) ?></div>
                    <div><strong>Trạng thái:</strong> <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($statusLabel) ?></span></div>
                    <?php if (!empty($lead['phone_verified'])): ?>
                      <div><span class="badge badge-success">SĐT đã được xác minh</span></div>
                    <?php endif; ?>
                    <?php if (!$isPurchased && !$isBlocked): ?>
                      <div><strong>Giá mở nhu cầu:</strong> <?= number_format((int)$leadPrice, 0, ',', '.') ?>đ</div>
                    <?php endif; ?>
                    <div><strong>Cần thuê:</strong> <?= htmlspecialchars((string)($lead['lead_move_in_label'] ?? 'Chưa chốt thời gian')) ?></div>
                    <?php if (!empty($lead['lead_summary_note'])): ?>
                      <div><strong>Mô tả:</strong> <?= htmlspecialchars((string)$lead['lead_summary_note']) ?></div>
                    <?php endif; ?>
                    <div><strong>Lịch sử:</strong></div>
                    <?= $renderLeadHistory($lead) ?>
                  </div>
                </details>
              </article>
            <?php endforeach; ?>
          </div>
          <?php
          return ob_get_clean();
      };
    ?>
    <style>
      .opsdash-stack { display:flex; flex-direction:column; gap:16px; margin-bottom:18px; }
      .opsdash-hero {
        position: relative;
        overflow: hidden;
        border-radius: 20px;
        padding: 20px;
        border: 1px solid rgba(251,191,36,0.24);
        background:
          radial-gradient(circle at top left, rgba(255,255,255,0.24), transparent 28%),
          linear-gradient(135deg, #fff7e0 0%, #ffe2a9 50%, #ffca74 100%);
        box-shadow: 0 18px 42px rgba(217,119,6,0.14);
      }
      .opsdash-hero::after {
        content:"";
        position:absolute;
        right:-48px;
        bottom:-76px;
        width:220px;
        height:220px;
        border-radius:50%;
        background: radial-gradient(circle, rgba(180,83,9,0.16), transparent 64%);
      }
      .opsdash-hero-row {
        position: relative;
        z-index: 1;
        display:flex;
        justify-content:space-between;
        gap:16px;
        align-items:flex-start;
      }
      .opsdash-eyebrow {
        display:inline-flex;
        align-items:center;
        min-height:30px;
        padding:6px 12px;
        border-radius:999px;
        background:rgba(255,255,255,0.72);
        color:#9a3412;
        font-size:12px;
        font-weight:800;
        letter-spacing:.02em;
      }
      .opsdash-hero h1 {
        margin:10px 0 8px;
        color:#431407;
        font-size:clamp(24px, 3vw, 32px);
        line-height:1.1;
        letter-spacing:-0.03em;
      }
      .opsdash-hero p {
        margin:0;
        max-width:720px;
        color:#7c2d12;
        font-size:14px;
        line-height:1.55;
      }
      .opsdash-hero-actions {
        display:flex;
        gap:8px;
        flex-wrap:wrap;
        justify-content:flex-end;
      }
      .opsdash-hero-actions .btn { min-height:40px; }
      .opsdash-stat-grid {
        display:grid;
        grid-template-columns:repeat(auto-fit,minmax(165px,1fr));
        gap:12px;
      }
      .opsdash-flow-grid {
        display:grid;
        grid-template-columns:repeat(auto-fit,minmax(180px,1fr));
        gap:12px;
      }
      .opsdash-flow-card {
        padding:16px;
        border-radius:18px;
        border:1px solid rgba(251,191,36,0.18);
        background:linear-gradient(180deg, #fffdf8 0%, #fff6df 100%);
        box-shadow:0 14px 28px rgba(15,23,42,0.05);
      }
      .opsdash-flow-label {
        color:#6b7280;
        font-size:12px;
        font-weight:800;
        text-transform:uppercase;
        letter-spacing:.04em;
      }
      .opsdash-flow-value {
        margin-top:8px;
        color:#111827;
        font-size:28px;
        font-weight:800;
        line-height:1.1;
      }
      .opsdash-flow-note {
        margin-top:8px;
        color:#6b7280;
        font-size:13px;
        line-height:1.5;
      }
      .opsdash-stat-card {
        padding:16px;
        border-radius:16px;
        border:1px solid #e8edf5;
        background:linear-gradient(180deg, #ffffff 0%, #fffaf1 100%);
        box-shadow:0 12px 28px rgba(15,23,42,0.05);
      }
      .opsdash-stat-label {
        color:#6b7280;
        font-size:12px;
        font-weight:700;
        text-transform:uppercase;
        letter-spacing:.04em;
      }
      .opsdash-stat-value {
        margin-top:8px;
        color:#111827;
        font-size:28px;
        font-weight:800;
        line-height:1.1;
      }
      .opsdash-stat-note {
        margin-top:6px;
        color:#6b7280;
        font-size:13px;
        line-height:1.45;
      }
      .opsdash-main-grid {
        display:grid;
        grid-template-columns:minmax(0, 1.1fr) minmax(300px, 0.9fr);
        gap:14px;
        align-items:start;
      }
      .opsdash-panel {
        border:1px solid #e8edf5;
        border-radius:18px;
        padding:16px;
        background:#fff;
        box-shadow:0 16px 32px rgba(15,23,42,0.06);
      }
      .opsdash-panel-head {
        display:flex;
        justify-content:space-between;
        align-items:flex-start;
        gap:12px;
        flex-wrap:wrap;
        margin-bottom:14px;
      }
      .opsdash-panel-title {
        margin:0;
        color:#111827;
        font-size:18px;
        line-height:1.2;
      }
      .opsdash-panel-note {
        margin:4px 0 0;
        color:#6b7280;
        font-size:13px;
        line-height:1.45;
      }
      .opsdash-panel-link {
        color:#b45309;
        font-weight:800;
        text-decoration:none;
      }
      .opsdash-panel-link:hover { color:#92400e; }
      .opsdash-attention-list {
        display:flex;
        flex-direction:column;
        gap:10px;
      }
      .opsdash-attention-item {
        display:block;
        padding:14px;
        border:1px solid #f6d899;
        border-radius:14px;
        background:linear-gradient(180deg, #fffdf8 0%, #fff6e5 100%);
        text-decoration:none;
      }
      .opsdash-attention-item:hover { border-color:#f59e0b; }
      .opsdash-room-top {
        display:flex;
        justify-content:space-between;
        align-items:flex-start;
        gap:10px;
        flex-wrap:wrap;
      }
      .opsdash-room-title {
        color:#111827;
        font-size:15px;
        font-weight:800;
        line-height:1.35;
      }
      .opsdash-room-note {
        margin-top:4px;
        color:#6b7280;
        font-size:13px;
        line-height:1.45;
      }
      .opsdash-tags {
        display:flex;
        flex-wrap:wrap;
        gap:6px;
        margin-top:10px;
      }
      .opsdash-tag {
        display:inline-flex;
        align-items:center;
        min-height:28px;
        padding:6px 10px;
        border-radius:999px;
        background:#fff;
        border:1px solid #fed7aa;
        color:#9a3412;
        font-size:12px;
        font-weight:700;
      }
      .opsdash-inline-stats {
        display:grid;
        grid-template-columns:repeat(3, minmax(0, 1fr));
        gap:10px;
        margin-bottom:12px;
      }
      .opsdash-inline-stat {
        padding:12px;
        border-radius:14px;
        border:1px solid #e5e7eb;
        background:#f8fafc;
      }
      .opsdash-inline-stat strong {
        display:block;
        color:#111827;
        font-size:24px;
        line-height:1.05;
      }
      .opsdash-inline-stat span {
        display:block;
        margin-top:6px;
        color:#6b7280;
        font-size:12px;
        font-weight:700;
      }
      .opsdash-room-grid {
        display:grid;
        grid-template-columns:repeat(2, minmax(0, 1fr));
        gap:10px;
      }
      .opsdash-room-card {
        display:block;
        padding:14px;
        border-radius:14px;
        border:1px solid #e5e7eb;
        background:#fcfcfd;
        text-decoration:none;
      }
      .opsdash-room-card:hover { border-color:#cbd5e1; background:#fff; }
      .opsdash-empty {
        padding:14px;
        border-radius:14px;
        border:1px dashed #d1d5db;
        background:#f9fafb;
        color:#6b7280;
        font-size:14px;
        line-height:1.5;
      }
      .chip-row { display:flex; gap:8px; flex-wrap:wrap; margin:0 0 12px; min-width:0; }
      .chip { padding:8px 10px; border-radius:8px; background:#fff7e6; color:#92400e; font-weight:700; border:1px solid #fcd34d; font-size:13px; }
      .kpi-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(190px,1fr)); gap:12px; margin-bottom:12px; }
      .kpi-card { border:1px solid #e6e8f0; box-shadow:none; }
      .kpi-value { font-size:26px; font-weight:800; }
      .muted { color:#6b7280; }
      .lead-section-stack { display:flex; flex-direction:column; gap:18px; margin-top:16px; min-width:0; }
      .lead-section { border:1px solid #e6e8f0; border-radius:12px; padding:14px; background:#fff; min-width:0; }
      .lead-section-head { display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:10px; flex-wrap:wrap; min-width:0; }
      .lead-section-title { margin:0; font-size:17px; }
      .lead-section-note { margin:4px 0 0; color:#6b7280; font-size:13px; }
      .lead-count { display:inline-flex; align-items:center; justify-content:center; min-width:42px; padding:8px 12px; border-radius:999px; background:#fff7e6; color:#b45309; border:1px solid #f59e0b; font-weight:800; }
      .lead-actions { display:flex; gap:8px; flex-wrap:wrap; align-items:center; min-width:260px; }
      .lead-actions form { margin:0; }
      .lead-actions .btn { white-space:nowrap; }
      .lead-stage-form { display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
      .lead-stage-form .form-control { min-width:180px; max-width:220px; }
      .lead-static-note { display:inline-flex; align-items:center; min-height:32px; }
      .lead-cell-title { color:#111827; font-size:14px; font-weight:800; line-height:1.35; }
      .lead-subline { margin-top:4px; color:#6b7280; font-size:12px; line-height:1.5; }
      .lead-note-preview { margin-top:6px; color:#4b5563; font-size:12px; line-height:1.55; }
      .lead-pill-row { display:flex; gap:6px; flex-wrap:wrap; margin-top:6px; }
      .lead-source-pill {
        display:inline-flex;
        align-items:center;
        min-height:24px;
        padding:4px 9px;
        border-radius:999px;
        background:#fff7e6;
        border:1px solid #f59e0b;
        color:#9a3412;
        font-size:11px;
        font-weight:800;
      }
      .lead-source-pill.alt {
        background:#f8fafc;
        border-color:#dbeafe;
        color:#1d4ed8;
      }
      .lead-fit-value { color:#111827; font-size:24px; font-weight:800; line-height:1.05; }
      .lead-history-list { display:flex; flex-direction:column; gap:8px; min-width:0; }
      .lead-history-item {
        padding:9px 10px;
        border-radius:10px;
        background:#f8fafc;
        border:1px solid #e5e7eb;
      }
      .lead-history-item strong,
      .lead-history-item span,
      .lead-history-item small { display:block; }
      .lead-history-item strong { color:#111827; font-size:12px; line-height:1.35; }
      .lead-history-item span { margin-top:3px; color:#6b7280; font-size:11px; }
      .lead-history-item small { margin-top:4px; color:#475569; font-size:11px; line-height:1.45; }
      .lead-history-empty {
        padding:10px;
        border-radius:10px;
        border:1px dashed #d1d5db;
        color:#6b7280;
        font-size:12px;
        background:#f9fafb;
      }
      .market-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(250px,1fr)); gap:12px; }
      .market-card {
        padding:14px;
        border-radius:16px;
        border:1px solid #f3e2c7;
        background:linear-gradient(180deg, #fffefb 0%, #fff8ef 100%);
        box-shadow:0 12px 28px rgba(217,119,6,0.08);
      }
      .market-card-head {
        display:flex;
        justify-content:space-between;
        align-items:flex-start;
        gap:10px;
      }
      .market-card-title { color:#111827; font-size:15px; font-weight:800; line-height:1.35; }
      .market-card-note { margin-top:8px; color:#4b5563; font-size:13px; line-height:1.55; }
      .market-card-footer { display:flex; justify-content:space-between; align-items:center; gap:10px; margin-top:12px; flex-wrap:wrap; }
      .market-price { color:#9a3412; font-size:14px; font-weight:800; }
      .lead-table th:last-child, .lead-table td:last-child { width:300px; }
      .lead-section .table-responsive { overflow-x:auto; -webkit-overflow-scrolling:touch; width:100%; max-width:100%; }
      .lead-table-mobile { display:none; }
      .chip-row .chip,
      .lead-section .lead-section-head > *,
      .lead-actions { min-width:0; }
      .chip-row .chip { max-width:100%; white-space: nowrap; }
      @media (max-width: 960px) {
        .opsdash-main-grid,
        .opsdash-room-grid { grid-template-columns:1fr; }
      }
      @media (max-width: 720px) {
        .opsdash-hero { padding:16px; }
        .opsdash-hero-row { flex-direction:column; }
        .opsdash-hero-actions { width:100%; }
        .opsdash-hero-actions .btn { flex:1 1 calc(50% - 4px); }
        .opsdash-stat-grid,
        .opsdash-inline-stats,
        .kpi-grid { grid-template-columns:1fr; }
        .lead-section { padding:12px; }
        .lead-section-head { align-items:flex-start; }
        .lead-table-desktop { display:none; }
        .lead-table-mobile { display:flex; flex-direction:column; gap:10px; }
        .lead-mobile-item {
          border:1px solid #e5e7eb;
          border-radius:10px;
          padding:10px;
          background:#fff;
        }
        .lead-mobile-title {
          font-weight:700;
          color:#111827;
          line-height:1.35;
          margin-bottom:4px;
          overflow-wrap: break-word;
        }
        .lead-mobile-contact {
          font-size:13px;
          color:#374151;
          overflow-wrap: break-word;
        }
        .lead-mobile-actions {
          margin-top:8px;
          display:flex;
          gap:6px;
          flex-direction:column;
        }
        .lead-mobile-actions form { margin:0; width:100%; }
        .lead-mobile-actions .btn {
          width:100%;
          white-space:nowrap;
          font-size:12px;
          line-height:1.35;
          padding:8px 9px;
        }
        .lead-stage-form .form-control { width:100%; max-width:none; }
        .lead-mobile-details { margin-top:8px; }
        .lead-mobile-details summary {
          cursor:pointer;
          color:#1d4ed8;
          font-weight:700;
          font-size:13px;
        }
        .lead-mobile-extra {
          margin-top:8px;
          padding-top:8px;
          border-top:1px dashed #e5e7eb;
          display:flex;
          flex-direction:column;
          gap:6px;
          font-size:12px;
          color:#374151;
          overflow-wrap: break-word;
        }
        .market-grid { grid-template-columns:1fr; }
        .chip-row { gap:6px; }
      }
    </style>
    <?php if (($activeMenu ?? '') === 'home'): ?>
      <div class="opsdash-stack">
        <section class="opsdash-hero">
          <div class="opsdash-hero-row">
            <div>
              <span class="opsdash-eyebrow">Bảng điều khiển vận hành trọ</span>
              <h1>Mở ứng dụng là thấy ngay việc cần xử lý</h1>
              <p>Phòng giờ không chỉ là bài đăng. Mỗi phòng là một hồ sơ vận hành với người thuê, hợp đồng, công tơ và lịch sử hoá đơn.</p>
              <div class="chip-row" style="margin-top:14px; margin-bottom:0;">
                <div class="chip">Tổng số phòng: <?= $opsTotalRooms ?></div>
                <div class="chip">Đã thuê: <?= $opsOccupiedRooms ?></div>
                <div class="chip">Phòng trống: <?= $opsVacantRooms ?></div>
                <div class="chip">Bảo trì: <?= $opsMaintenanceRooms ?></div>
                <div class="chip">Gần hạn: <?= $opsDueSoonInvoices ?></div>
                <div class="chip">Phiếu mở: <?= $opsOpenIssues ?></div>
                <div class="chip"><?= $opsAttentionTotal ?> phòng cần chú ý</div>
              </div>
            </div>
            <div class="opsdash-hero-actions">
              <a href="?route=my-rooms" class="btn btn-primary btn-sm">Vào vận hành trọ</a>
              <a href="?route=room-create" class="btn btn-outline btn-sm">Thêm phòng mới</a>
            </div>
          </div>
        </section>

        <section class="opsdash-panel">
          <div class="opsdash-panel-head">
            <div>
              <h2 class="opsdash-panel-title">Chu trình thuê trọ đang chạy tới đâu</h2>
              <p class="opsdash-panel-note">Tách rõ các chặng vận hành để ứng dụng nhìn như một hệ quản lý thuê ở thật, không chỉ là nơi đăng bài.</p>
            </div>
            <a href="?route=my-rooms" class="opsdash-panel-link">Mở hồ sơ phòng</a>
          </div>
          <div class="opsdash-flow-grid">
            <?php foreach ($opsLifecycle as $flowCard): ?>
              <article class="opsdash-flow-card">
                <div class="opsdash-flow-label"><?= htmlspecialchars((string)$flowCard['label']) ?></div>
                <div class="opsdash-flow-value"><?= (int)$flowCard['value'] ?></div>
                <div class="opsdash-flow-note"><?= htmlspecialchars((string)$flowCard['note']) ?></div>
              </article>
            <?php endforeach; ?>
          </div>
        </section>

        <div class="opsdash-stat-grid">
          <article class="opsdash-stat-card">
            <div class="opsdash-stat-label">Tổng số phòng</div>
            <div class="opsdash-stat-value"><?= $opsTotalRooms ?></div>
            <div class="opsdash-stat-note">Toàn bộ hồ sơ phòng đang theo dõi.</div>
          </article>
          <article class="opsdash-stat-card">
            <div class="opsdash-stat-label">Đã thuê</div>
            <div class="opsdash-stat-value"><?= $opsOccupiedRooms ?></div>
            <div class="opsdash-stat-note">Phòng đang có người thuê thực tế.</div>
          </article>
          <article class="opsdash-stat-card">
            <div class="opsdash-stat-label">Phòng trống</div>
            <div class="opsdash-stat-value"><?= $opsVacantRooms ?></div>
            <div class="opsdash-stat-note">Cần lấp đầy hoặc kiểm tra tình trạng.</div>
          </article>
          <article class="opsdash-stat-card">
            <div class="opsdash-stat-label">Hoá đơn chưa thanh toán</div>
            <div class="opsdash-stat-value"><?= $opsUnpaidInvoices ?></div>
            <div class="opsdash-stat-note">Bao gồm cả hoá đơn quá hạn.</div>
          </article>
          <article class="opsdash-stat-card">
            <div class="opsdash-stat-label">Sắp đến hạn</div>
            <div class="opsdash-stat-value"><?= $opsDueSoonInvoices ?></div>
            <div class="opsdash-stat-note">Các kỳ cần nhắc nhẹ trước hạn thanh toán.</div>
          </article>
          <article class="opsdash-stat-card">
            <div class="opsdash-stat-label">Đang quá hạn</div>
            <div class="opsdash-stat-value"><?= $opsOverdueInvoices ?></div>
            <div class="opsdash-stat-note">Cần ưu tiên xử lý và theo dõi công nợ.</div>
          </article>
          <article class="opsdash-stat-card">
            <div class="opsdash-stat-label">Đang bảo trì</div>
            <div class="opsdash-stat-value"><?= $opsMaintenanceRooms ?></div>
            <div class="opsdash-stat-note">Các phòng đang tạm khóa để sửa chữa hoặc bảo trì.</div>
          </article>
          <article class="opsdash-stat-card">
            <div class="opsdash-stat-label">Phiếu sự cố đang mở</div>
            <div class="opsdash-stat-value"><?= $opsOpenIssues ?></div>
            <div class="opsdash-stat-note">Yêu cầu hỗ trợ và sự cố chưa đóng.</div>
          </article>
          <article class="opsdash-stat-card">
            <div class="opsdash-stat-label">Hợp đồng sắp hết hạn</div>
            <div class="opsdash-stat-value"><?= $opsExpiringContracts ?></div>
            <div class="opsdash-stat-note">Các hợp đồng còn dưới 30 ngày.</div>
          </article>
          <article class="opsdash-stat-card">
            <div class="opsdash-stat-label">Doanh thu tháng này</div>
            <div class="opsdash-stat-value"><?= number_format($opsRevenueMonth, 0, ',', '.') ?>đ</div>
            <div class="opsdash-stat-note">Tính từ các hoá đơn đã thanh toán.</div>
          </article>
        </div>

        <div class="opsdash-main-grid">
          <section class="opsdash-panel">
            <div class="opsdash-panel-head">
              <div>
                <h2 class="opsdash-panel-title">Phòng cần xử lý ngay</h2>
                <p class="opsdash-panel-note">Ưu tiên hoá đơn tồn, hợp đồng sắp hết và các phòng đang có sự cố.</p>
              </div>
              <a href="?route=my-rooms" class="opsdash-panel-link">Xem toàn bộ</a>
            </div>
            <div class="opsdash-attention-list">
              <?php if (empty($opsAttentionRooms)): ?>
                <div class="opsdash-empty">Chưa có phòng nào đang báo động. Bạn có thể kiểm tra danh sách phòng để cập nhật công tơ hoặc hợp đồng mới.</div>
              <?php endif; ?>
              <?php foreach ($opsAttentionRooms as $attentionRoom): ?>
                <a href="?route=room-ops&id=<?= (int)$attentionRoom['room_id'] ?>" class="opsdash-attention-item">
                  <div class="opsdash-room-top">
                    <div>
                      <div class="opsdash-room-title">#<?= (int)$attentionRoom['room_id'] ?> · <?= htmlspecialchars((string)($attentionRoom['title'] ?? '')) ?></div>
                      <div class="opsdash-room-note"><?= htmlspecialchars((string)($attentionRoom['status_label'] ?? '')) ?></div>
                    </div>
                    <span class="badge badge-warning"><?= count($attentionRoom['attention'] ?? []) ?> việc</span>
                  </div>
                  <div class="opsdash-tags">
                    <?php foreach (array_slice($attentionRoom['attention'] ?? [], 0, 4) as $alert): ?>
                      <span class="opsdash-tag"><?= htmlspecialchars((string)$alert) ?></span>
                    <?php endforeach; ?>
                  </div>
                </a>
              <?php endforeach; ?>
            </div>
          </section>

          <section class="opsdash-panel">
            <div class="opsdash-panel-head">
              <div>
                <h2 class="opsdash-panel-title">Nhịp khách thuê</h2>
                <p class="opsdash-panel-note">Nhu cầu vẫn có ích, nhưng được tách riêng để không lẫn với vận hành phòng.</p>
              </div>
              <a href="?route=dashboard&tab=lead#lead" class="opsdash-panel-link">Vào nhu cầu</a>
            </div>
            <div class="opsdash-inline-stats">
              <div class="opsdash-inline-stat">
                <strong><?= $leadTodayCount ?></strong>
                <span>Nhu cầu mới hôm nay</span>
              </div>
              <div class="opsdash-inline-stat">
                <strong><?= $leadUnpaidCount ?></strong>
                <span>Nhu cầu chưa mua</span>
              </div>
              <div class="opsdash-inline-stat">
                <strong><?= $leadPurchasedCount ?></strong>
                <span>Nhu cầu đã mở</span>
              </div>
            </div>
            <div class="chip-row" style="margin-bottom:0;">
              <div class="chip"><?= $hotArea ? ('Khu nổi bật: ' . htmlspecialchars((string)$hotArea['area']) . ' (' . (int)$hotArea['c'] . ' nhu cầu)') : 'Khu nổi bật: chưa đủ dữ liệu' ?></div>
              <div class="chip"><?= $hotHour ? ('Khung giờ: ' . str_pad((int)$hotHour['h'], 2, '0', STR_PAD_LEFT) . 'h (' . (int)$hotHour['c'] . ' nhu cầu)') : 'Khung giờ: chưa đủ dữ liệu' ?></div>
              <div class="chip"><?= ($ps && ($ps['avg_price'] ?? 0) > 0) ? ('Giá chốt trung bình: ' . number_format((int)$ps['avg_price'], 0, ',', '.') . ' đ') : 'Giá chốt trung bình: chưa có' ?></div>
              <div class="chip"><?= ($ps && ($ps['max_price'] ?? 0) > 0) ? ('Giá chốt cao nhất: ' . number_format((int)$ps['max_price'], 0, ',', '.') . ' đ') : 'Giá chốt cao nhất: chưa có' ?></div>
            </div>
          </section>
        </div>

        <section class="opsdash-panel">
          <div class="opsdash-panel-head">
            <div>
              <h2 class="opsdash-panel-title">Ảnh chụp nhanh từng phòng</h2>
              <p class="opsdash-panel-note">Đi thẳng vào hồ sơ vận hành của từng phòng thay vì lướt một danh sách dài.</p>
            </div>
            <a href="?route=my-rooms" class="opsdash-panel-link">Mở danh sách phòng</a>
          </div>
          <div class="opsdash-room-grid">
            <?php if (empty($opsRooms)): ?>
              <div class="opsdash-empty">Bạn chưa có phòng nào trong hệ vận hành. Hãy thêm phòng đầu tiên để bắt đầu theo dõi người thuê, công tơ và hoá đơn.</div>
            <?php endif; ?>
            <?php foreach (array_slice($opsRooms, 0, 4) as $opsRoom): ?>
              <?php
                $opsStatus = (string)($opsRoom['ops_profile']['occupancy_status'] ?? 'vacant');
                $contractDaysLeft = $opsRoom['ops_contract_days_left'] ?? null;
              ?>
              <a href="?route=room-ops&id=<?= (int)$opsRoom['id'] ?>" class="opsdash-room-card">
                <div class="opsdash-room-top">
                  <span class="badge <?= $opsStatus === 'occupied' ? 'badge-success' : 'badge-warning' ?>"><?= htmlspecialchars((string)($opsRoom['ops_status_label'] ?? 'Phòng trống')) ?></span>
                  <?php if ((int)($opsRoom['ops_unpaid_invoice_count'] ?? 0) > 0): ?>
                    <span class="badge badge-warning"><?= (int)$opsRoom['ops_unpaid_invoice_count'] ?> hoá đơn tồn</span>
                  <?php endif; ?>
                </div>
                <div class="opsdash-room-title">#<?= (int)$opsRoom['id'] ?> · <?= htmlspecialchars((string)($opsRoom['title'] ?? '')) ?></div>
                <div class="opsdash-room-note">
                  <?= !empty($opsRoom['ops_tenant_name']) ? ('Người thuê: ' . htmlspecialchars((string)$opsRoom['ops_tenant_name'])) : 'Chưa gắn người thuê' ?>
                </div>
                <div class="opsdash-room-note">Thu thực tế: <?= number_format((int)($opsRoom['ops_monthly_rent'] ?? $opsRoom['price'] ?? 0), 0, ',', '.') ?> đ/tháng</div>
                <?php if ($contractDaysLeft !== null): ?>
                  <div class="opsdash-room-note">Hợp đồng còn <?= (int)$contractDaysLeft ?> ngày</div>
                <?php endif; ?>
                <?php if (!empty($opsRoom['ops_attention'])): ?>
                  <div class="opsdash-tags">
                    <?php foreach (array_slice($opsRoom['ops_attention'], 0, 2) as $opsAlert): ?>
                      <span class="opsdash-tag"><?= htmlspecialchars((string)$opsAlert) ?></span>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>
              </a>
            <?php endforeach; ?>
          </div>
        </section>
      </div>
    <?php endif; ?>

    <div class="d-flex justify-between align-items-center mb-2">
      <div>
        <h1 class="mb-1" style="font-size:20px;">Sàn nhu cầu + quan tâm trực tiếp</h1>
        <p class="text-muted mb-0">Một nơi để mua nhu cầu thuê, theo dõi thương lượng và chốt thành hợp đồng thật. Hiện có <?= (int)$leadCount ?> nhu cầu đã vào luồng xử lý và <?= $marketAvailableCount ?> nhu cầu đang mở bán.</p>
      </div>
    </div>

    <div class="card mb-3">
      <div class="card-body">
        <div class="chip-row" style="margin-bottom:0;">
          <div class="chip">Nhu cầu đã vào luồng xử lý: <?= (int)$leadCount ?></div>
          <div class="chip">Sàn đang mở: <?= $marketAvailableCount ?></div>
          <div class="chip">Nhu cầu mới 24h: <?= $marketFreshCount ?></div>
          <div class="chip">Nhu cầu mới hôm nay: <?= $leadTodayCount ?></div>
          <div class="chip">Nhu cầu chưa mua: <?= $leadUnpaidCount ?></div>
          <div class="chip">Nhu cầu đã mở: <?= $leadPurchasedCount ?></div>
          <div class="chip">Đang thương lượng: <?= $leadNegotiating ?></div>
          <div class="chip">Tỷ lệ chốt nhu cầu: <?= $leadCloseRate ?>%</div>
          <div class="chip">Tỷ lệ lấp đầy: <?= $occupancyRate ?>%</div>
          <div class="chip" style="background:#fff1f2; color:#9f1239; border-color:#fecdd3;">Nhu cầu thất bại: <?= $leadInvalid ?></div>
        </div>
      </div>
    </div>

    <div class="card" id="lead">
      <div class="card-body">
        <div class="d-flex justify-between align-items-center mb-1">
          <div>
            <h2 class="mb-1" style="font-size:18px;">Không gian xử lý nhu cầu</h2>
            <p class="text-muted mb-0">Xem trước trước khi mua, mở đủ liên hệ sau khi mua, và có đủ giai đoạn từ mới mua tới thương lượng, chốt hoặc thất bại.</p>
          </div>
        </div>
        <div class="lead-section-stack">
          <section class="lead-section">
            <div class="lead-section-head">
              <div>
                <h3 class="lead-section-title">Nhu cầu sàn đang mở bán</h3>
                <p class="lead-section-note">Chủ trọ chỉ thấy bản xem trước. Thông tin liên hệ đầy đủ chỉ mở sau khi mua nhu cầu.</p>
              </div>
              <span class="lead-count"><?= $marketAvailableCount ?></span>
            </div>
            <div class="market-grid">
              <?php if (empty($marketAvailablePosts)): ?>
                <div class="opsdash-empty">Hiện chưa có nhu cầu thuê mới phù hợp. Khi người thuê đăng nhu cầu, hệ thống sẽ gợi ý phòng khớp nhất ngay tại đây.</div>
              <?php endif; ?>
              <?php foreach (array_slice($marketAvailablePosts, 0, 8) as $post): ?>
                <?php
                  $marketNote = trim((string)preg_replace('/\s+/u', ' ', (string)($post['note'] ?? '')));
                  $marketPreview = $marketNote !== '' ? mb_substr($marketNote, 0, 140, 'UTF-8') : 'Nhu cầu thuê mới từ người thuê.';
                ?>
                <article class="market-card">
                  <div class="market-card-head">
                    <div>
                      <div class="market-card-title"><?= htmlspecialchars((string)($post['preview_name'] ?? 'Khách***')) ?> · <?= htmlspecialchars((string)($post['preview_phone'] ?? '***')) ?></div>
                      <div class="lead-pill-row">
                        <span class="lead-source-pill"><?= htmlspecialchars((string)($post['freshness_label'] ?? 'Nhu cầu mới')) ?></span>
                        <span class="lead-source-pill alt"><?= htmlspecialchars((string)($post['match_label'] ?? '0%')) ?></span>
                      </div>
                    </div>
                    <span class="badge badge-warning"><?= htmlspecialchars((string)($post['post_kind'] ?? 'room')) ?></span>
                  </div>
                  <div class="lead-subline">Khu vực: <?= htmlspecialchars((string)($post['area_label'] ?? 'Chưa rõ khu vực')) ?></div>
                  <div class="lead-subline">Ngân sách: <?= htmlspecialchars((string)($post['budget_label'] ?? 'Chưa chốt ngân sách')) ?></div>
                  <div class="lead-subline">Loại phòng: <?= htmlspecialchars((string)($post['room_type'] ?: 'Chưa rõ loại phòng')) ?></div>
                  <div class="lead-subline">Cần thuê: <?= htmlspecialchars((string)($post['move_in_time'] ?: 'Chưa chốt thời gian')) ?></div>
                  <div class="lead-subline"><?= htmlspecialchars((string)($post['match_suggestion'] ?? 'Chưa có gợi ý')) ?></div>
                  <div class="market-card-note"><?= htmlspecialchars($marketPreview) ?></div>
                  <div class="market-card-footer">
                    <div class="market-price"><?= !empty($post['buy_price']) ? ('Mở nhu cầu: ' . number_format((int)$post['buy_price'], 0, ',', '.') . 'đ') : 'Chưa có phòng khớp để mua' ?></div>
                    <form method="post" action="?route=open-marketplace-lead">
                      <input type="hidden" name="tenant_post_id" value="<?= (int)$post['id'] ?>">
                      <button class="btn btn-sm btn-primary" type="submit" <?= !empty($post['buy_disabled']) ? 'disabled' : '' ?>>Mua nhu cầu này</button>
                    </form>
                  </div>
                </article>
              <?php endforeach; ?>
            </div>
          </section>

          <section class="lead-section">
            <div class="lead-section-head">
              <div>
                <h3 class="lead-section-title">Nhu cầu mới hôm nay</h3>
                <p class="lead-section-note">Nhu cầu phát sinh trong ngày, ưu tiên xử lý trước.</p>
              </div>
              <span class="lead-count"><?= $leadTodayCount ?></span>
            </div>
            <?= $renderLeadTable($leadToday, 'Hôm nay chưa có nhu cầu mới.') ?>
          </section>

          <section class="lead-section">
            <div class="lead-section-head">
              <div>
                <h3 class="lead-section-title">Nhu cầu chưa mua</h3>
                <p class="lead-section-note">Danh sách nhu cầu đang chờ mở. Mục này có nút mua.</p>
              </div>
              <span class="lead-count"><?= $leadUnpaidCount ?></span>
            </div>
            <?= $renderLeadTable($leadUnpaid, 'Không còn nhu cầu chưa mua.') ?>
          </section>

          <section class="lead-section">
            <div class="lead-section-head">
              <div>
                <h3 class="lead-section-title">Nhu cầu đã mua / lịch sử</h3>
                <p class="lead-section-note">Nhu cầu đã thanh toán hoặc đã mở, không hiện nút mua lại.</p>
              </div>
              <span class="lead-count"><?= $leadPurchasedCount ?></span>
            </div>
            <?= $renderLeadTable($leadPurchased, 'Chưa có lịch sử nhu cầu đã mua.') ?>
          </section>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  (() => {
    const root = document.getElementById('lead');
    if (!root) return;

    if (window.location.hash === '#lead') {
      root.scrollIntoView({ behavior: 'smooth' });
    }
  })();
</script>
