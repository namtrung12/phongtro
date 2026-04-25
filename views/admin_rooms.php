<?php $pageTitle = 'Duyệt phòng'; ?>
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
    <div class="d-flex justify-between align-items-center mb-3">
      <div>
        <h1 class="mb-1" style="font-size:20px;">Duyệt phòng</h1>
        <p class="text-muted mb-0">Quản trị duyệt hoặc từ chối các bài đăng phòng.</p>
      </div>
      <div class="d-flex gap-2">
        <a class="btn btn-outline btn-sm" href="?route=admin-rooms&status=pending">Đang chờ</a>
        <a class="btn btn-outline btn-sm" href="?route=admin-rooms&status=active">Đã duyệt</a>
        <a class="btn btn-outline btn-sm" href="?route=admin-rooms&status=rejected">Đã từ chối</a>
      </div>
    </div>

    <div class="card">
      <div class="card-body table-responsive">
        <style>
          .card-body.table-responsive { overflow: visible; }
          .room-action-btn { padding:4px 8px; font-size:12px; display:inline-flex; align-items:center; gap:4px; border-radius:8px; position: relative; }
          .room-actions { display:flex; gap:6px; align-items:center; }
          .room-table th, .room-table td { padding:10px 8px; font-size:13px; }
          .room-action-btn[data-tip]::after {
            content: attr(data-tip);
            position: absolute;
            left: 50%;
            top: -30px;
            transform: translate(-50%, -4px);
            background: rgba(17,24,39,0.9);
            color: #fff;
            padding: 6px 8px;
            border-radius: 6px;
            font-size: 11px;
            white-space: nowrap;
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            transition: opacity .15s ease, transform .15s ease;
            z-index: 10;
          }
          .room-action-btn[data-tip]:hover::after {
            opacity: 1;
            visibility: visible;
            transform: translate(-50%, -8px);
          }
        </style>
        <?php $roomStatus = ['pending' => 'Đang chờ', 'active' => 'Đã duyệt', 'rejected' => 'Đã từ chối']; ?>
        <table class="table align-middle room-table">
          <thead>
            <tr>
              <th>Mã</th><th>Tiêu đề</th><th>Chủ trọ</th><th>Giá</th><th>Giá nhu cầu</th><th>Khu vực</th><th>Trạng thái</th><th></th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($rooms)): ?>
              <tr><td colspan="8">Không có phòng.</td></tr>
            <?php endif; ?>
            <?php foreach ($rooms as $r): ?>
              <tr>
                <td>#<?= (int)$r['id'] ?></td>
                <td><?= htmlspecialchars($r['title']) ?></td>
                <td><?= htmlspecialchars($r['landlord_name']) ?> (<?= htmlspecialchars($r['landlord_phone']) ?>)</td>
                <td><?= number_format((int)$r['price'], 0, ',', '.') ?> đ / tháng</td>
                <?php
                  $leadPriceBase = effectiveLeadPriceFromRow($r);
                  $suggest = !empty($r['lead_price_suggest']) ? normalizeLeadPrice((int)$r['lead_price_suggest']) : null;
                  $expect = !empty($r['lead_price_expect']) ? normalizeLeadPrice((int)$r['lead_price_expect']) : null;
                ?>
                <td>
                  <div><strong><?= number_format((int)$leadPriceBase, 0, ',', '.') ?> đ</strong></div>
                  <div class="text-muted small">Đề xuất: <?= $expect ? number_format((int)$expect,0,',','.') . 'đ' : '—' ?> · Gợi ý: <?= $suggest ? number_format((int)$suggest,0,',','.') . 'đ' : '—' ?></div>
                </td>
                <td><?= htmlspecialchars($r['area']) ?></td>
                <td><?= htmlspecialchars($roomStatus[$r['status']] ?? $r['status']) ?></td>
                <td>
                  <?php if ($r['status'] === 'pending'): ?>
                  <form method="post" action="?route=admin-room-action" class="d-inline">
                    <input type="hidden" name="room_id" value="<?= (int)$r['id'] ?>">
                    <div class="room-actions">
                      <input type="number" name="lead_price_final" class="form-control form-control-sm" style="width:120px;" min="3000" step="1000" value="<?= (int)$leadPriceBase ?>" placeholder="Giá nhu cầu">
                      <a class="btn btn-outline room-action-btn" href="?route=room&id=<?= (int)$r['id'] ?>" target="_blank" rel="noopener" data-tip="Xem trước" title="Xem trước">🔍</a>
                      <button class="btn btn-primary room-action-btn" name="action" value="approve" type="submit" data-tip="Duyệt" title="Duyệt">✔</button>
                      <button class="btn btn-outline-secondary room-action-btn" name="action" value="reject" type="submit" data-tip="Từ chối" title="Từ chối">✖</button>
                    </div>
                  </form>
                  <?php else: ?>
                    <a class="btn btn-outline room-action-btn" href="?route=room&id=<?= (int)$r['id'] ?>" target="_blank" rel="noopener" data-tip="Xem" title="Xem">🔍</a>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

