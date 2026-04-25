<?php $pageTitle = 'Quản lý quan tâm'; ?>
<style>
  .lead-action-cell {
    min-width: 250px;
    white-space: nowrap;
  }
  .lead-action-form {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    flex-wrap: nowrap;
  }
</style>
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
        <h1 class="mb-2" style="font-size:20px;">Quan tâm</h1>
        <?php $leadStatus = ['new' => 'Mới', 'sold' => 'Đã bán', 'used' => 'Đã dùng', 'invalid' => 'Sai']; ?>
        <table class="table align-middle">
          <thead>
            <tr><th>Mã</th><th>Phòng</th><th>Khách</th><th>SĐT</th><th>Trạng thái</th><th>Ngày</th><th>Chủ trọ</th><th></th></tr>
          </thead>
          <tbody>
            <?php foreach ($leads as $l): ?>
            <tr>
              <td>#<?= (int)$l['id'] ?></td>
              <td><?= htmlspecialchars($l['room_title']) ?></td>
              <td><?= htmlspecialchars($l['tenant_name']) ?></td>
              <td><?= htmlspecialchars($l['tenant_phone']) ?></td>
              <td><?= htmlspecialchars($leadStatus[$l['status']] ?? $l['status']) ?></td>
              <td><?= htmlspecialchars($l['created_at']) ?></td>
              <td><?= htmlspecialchars($l['landlord_phone']) ?></td>
              <td class="lead-action-cell">
                <form method="post" action="?route=admin-lead-action" class="lead-action-form">
                  <input type="hidden" name="lead_id" value="<?= (int)$l['id'] ?>">
                  <button class="btn btn-outline btn-sm" name="action" value="invalid" type="submit">Đánh dấu sai</button>
                  <button class="btn btn-outline-secondary btn-sm" name="action" value="used" type="submit">Đánh dấu đã dùng</button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

