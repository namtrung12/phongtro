<?php $pageTitle = 'Lịch sử quan tâm'; ?>
<div class="card">
  <div class="card-body table-responsive">
    <h1 class="mb-2" style="font-size:20px;">Quan tâm đã gửi</h1>
    <p class="text-muted mb-3">Hiển thị theo số điện thoại tài khoản của bạn.</p>
    <?php $leadStatus = ['new' => 'Chưa mở', 'sold' => 'Đã mua', 'used' => 'Đã xử lý', 'invalid' => 'Sai']; ?>
    <table class="table align-middle">
      <thead>
        <tr><th>Mã</th><th>Phòng</th><th>Địa chỉ</th><th>Trạng thái</th><th>Ngày</th></tr>
      </thead>
      <tbody>
        <?php if (empty($leads)): ?>
          <tr><td colspan="5">Chưa có quan tâm nào.</td></tr>
        <?php endif; ?>
        <?php foreach ($leads as $l): ?>
        <tr>
          <td>#<?= (int)$l['id'] ?></td>
          <td><?= htmlspecialchars($l['room_title']) ?></td>
          <td><?= htmlspecialchars($l['address']) ?></td>
          <td><?= htmlspecialchars($leadStatus[$l['status']] ?? $l['status']) ?></td>
          <td><?= htmlspecialchars($l['created_at']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
