<?php $pageTitle = 'Lịch sử thanh toán'; ?>
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
        <h1 class="mb-2" style="font-size:20px;">Lịch sử thanh toán</h1>
        <p class="text-muted mb-3">SePay tự động cập nhật giao dịch. Trang này chỉ dùng để theo dõi thành công hoặc chưa thành công.</p>
        <table class="table align-middle">
          <thead>
            <tr><th>Mã</th><th>Chủ trọ</th><th>Quan tâm</th><th>Phòng</th><th>Số tiền</th><th>Mã CK</th><th>Cổng</th><th>Trạng thái</th><th>Ngày</th></tr>
          </thead>
          <tbody>
            <?php
              $payStatus = ['paid' => 'Thành công', 'failed' => 'Chưa thành công', 'pending' => 'Chưa thành công'];
              $payBadge = ['paid' => 'badge-success', 'failed' => 'badge-warning', 'pending' => 'badge-warning'];
            ?>
            <?php if (empty($payments)): ?>
              <tr><td colspan="9">Chưa có giao dịch.</td></tr>
            <?php endif; ?>
            <?php foreach ($payments as $p): ?>
            <tr>
              <td>#<?= (int)$p['id'] ?></td>
              <td><?= htmlspecialchars($p['landlord_name']) ?> (#<?= (int)$p['landlord_id'] ?>)</td>
              <td><?= $p['lead_id'] ? '#'.(int)$p['lead_id'] : '-' ?></td>
              <td><?= htmlspecialchars($p['room_title'] ?? '-') ?></td>
              <td><?= number_format((int)$p['amount'], 0, ',', '.') ?> đ</td>
              <td>
                <?= !empty($p['payment_code']) ? htmlspecialchars($p['payment_code']) : '-' ?>
                <?php if (!empty($p['provider_ref'])): ?>
                  <div class="text-muted small"><?= htmlspecialchars($p['provider_ref']) ?></div>
                <?php endif; ?>
              </td>
              <td><?= htmlspecialchars($p['provider']) ?></td>
              <td><span class="badge <?= htmlspecialchars($payBadge[$p['status']] ?? 'badge-warning') ?>"><?= htmlspecialchars($payStatus[$p['status']] ?? $p['status']) ?></span></td>
              <td><?= htmlspecialchars($p['created_at']) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

