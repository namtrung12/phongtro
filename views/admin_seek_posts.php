<?php $pageTitle = 'Duyệt bài tìm phòng'; ?>
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
    <div class="card mb-3">
      <div class="card-body d-flex gap-2 align-items-center flex-wrap">
        <a class="btn btn-outline btn-sm" href="?route=admin-seek-posts&status=pending">Đang chờ</a>
        <a class="btn btn-outline btn-sm" href="?route=admin-seek-posts&status=active">Đã duyệt</a>
        <a class="btn btn-outline btn-sm" href="?route=admin-seek-posts&status=hidden">Ẩn/Bị ẩn</a>
      </div>
    </div>
    <div class="card">
      <div class="card-body table-responsive">
        <h1 class="mb-2" style="font-size:20px;">Bài tìm trọ / ở ghép (<?= htmlspecialchars($status ?? '') ?>)</h1>
        <table class="table align-middle">
          <thead>
            <tr><th>Mã</th><th>Khu vực</th><th>Ngân sách</th><th>Người</th><th>Giới tính</th><th>Ảnh phòng</th><th>Người đăng</th><th>Ngày</th><th></th></tr>
          </thead>
          <tbody>
            <?php if (empty($posts)): ?>
              <tr><td colspan="9">Không có bài.</td></tr>
            <?php endif; ?>
            <?php foreach ($posts as $p): ?>
              <tr>
                <td>#<?= (int)$p['id'] ?></td>
                <td><?= htmlspecialchars($p['area']) ?></td>
                <td>
                  <?php if ($p['price_min'] || $p['price_max']): ?>
                      <?= $p['price_min'] ? number_format((int)$p['price_min'],0,',','.') . 'đ' : '—' ?> -
                      <?= $p['price_max'] ? number_format((int)$p['price_max'],0,',','.') . 'đ' : '—' ?>
                  <?php else: ?>—<?php endif; ?>
                </td>
                <td><?= $p['people_count'] ? (int)$p['people_count'] : '—' ?></td>
                <td><?= ['male'=>'Nam','female'=>'Nữ','any'=>'Không yêu cầu'][$p['gender'] ?? 'any'] ?></td>
                <td>
                  <?php
                    $imgs = array_filter([
                      $p['room_image'] ?? '',
                      $p['room_image2'] ?? '',
                      $p['room_image3'] ?? '',
                    ]);
                  ?>
                  <?php if (!empty($imgs)): ?>
                    <?php foreach ($imgs as $img): ?>
                      <a href="<?= htmlspecialchars(assetUrl($img)) ?>" target="_blank"><img src="<?= htmlspecialchars(assetUrl($img)) ?>" alt="Ảnh phòng" style="height:48px;border-radius:8px;object-fit:cover;margin-right:6px;"></a>
                    <?php endforeach; ?>
                  <?php else: ?>—<?php endif; ?>
                </td>
                <td><?= htmlspecialchars($p['user_name']) ?></td>
                <td><?= htmlspecialchars($p['created_at']) ?></td>
                <td class="d-flex gap-2">
                  <?php if (($status ?? '') === 'pending'): ?>
                    <form method="post" action="?route=admin-seek-action" class="d-inline">
                      <input type="hidden" name="post_id" value="<?= (int)$p['id'] ?>">
                      <button class="btn btn-primary btn-sm" name="action" value="active" type="submit">Duyệt</button>
                      <button class="btn btn-outline-secondary btn-sm" name="action" value="hidden" type="submit">Ẩn</button>
                    </form>
                  <?php elseif (($status ?? '') === 'active'): ?>
                    <form method="post" action="?route=admin-seek-action" class="d-inline">
                      <input type="hidden" name="post_id" value="<?= (int)$p['id'] ?>">
                      <button class="btn btn-outline-secondary btn-sm" name="action" value="hidden" type="submit">Ẩn</button>
                    </form>
                  <?php else: ?>
                    <form method="post" action="?route=admin-seek-action" class="d-inline">
                      <input type="hidden" name="post_id" value="<?= (int)$p['id'] ?>">
                      <button class="btn btn-outline btn-sm" name="action" value="active" type="submit">Hiển thị lại</button>
                    </form>
                  <?php endif; ?>
                </td>
              </tr>
              <tr><td colspan="9" class="text-muted" style="white-space:pre-wrap;"><?= htmlspecialchars($p['note']) ?></td></tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

