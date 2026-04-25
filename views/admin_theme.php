<?php
  $pageTitle = 'Giao diện';
  $opacityPercent = (int)round(((float)($currentOpacity ?? 0.045)) * 100);
?>
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
      <div class="card-body">
        <h2 class="mb-2">Ảnh nền hoa văn</h2>
        <p class="text-muted mb-3">Chọn URL hoặc tải ảnh (png/jpg/webp). Ưu tiên ảnh từ upload. Để trống sẽ dùng mặc định.</p>
        <form method="post" action="?route=admin-theme-save" enctype="multipart/form-data" class="d-flex flex-column gap-2">
          <label class="form-label">Link ảnh (URL hoặc đường dẫn tương đối)</label>
          <input type="text" name="bg_url" class="form-control" placeholder="https://... hoặc storage/uploads/xxx.png" value="<?= htmlspecialchars($currentBg ?? '') ?>">
          <label class="form-label">Hoặc tải ảnh lên</label>
          <input type="file" name="bg_file" class="form-control" accept="image/*">
          <label class="form-label" for="bgOpacity">Độ đậm ảnh nền: <span id="opacityValue"><?= (int)$opacityPercent ?></span>%</label>
          <input id="bgOpacity" type="range" name="bg_opacity" min="0" max="25" step="1" value="<?= (int)$opacityPercent ?>" class="form-control">
          <small class="text-muted">0% là ẩn hoa văn, 5% là nhẹ, 15-25% là đậm hơn.</small>
          <button class="btn btn-primary mt-2" type="submit">Lưu ảnh nền</button>
        </form>
        <div class="mt-3">
          <div class="text-muted mb-1">Đang dùng:</div>
          <div class="card" style="padding:8px; max-width:400px;">
            <img id="bgPreview" src="<?= htmlspecialchars(assetUrl($currentBg ?? '')) ?>" alt="Background preview" style="width:100%; border-radius:10px; object-fit:contain; background:#f8fafc; opacity:<?= htmlspecialchars((string)($currentOpacity ?? 0.045)) ?>;">
            <small class="text-muted"><?= htmlspecialchars($currentBg ?? '') ?></small>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<script>
  (() => {
    const range = document.getElementById('bgOpacity');
    const label = document.getElementById('opacityValue');
    const preview = document.getElementById('bgPreview');
    range?.addEventListener('input', () => {
      const percent = Number(range.value || 0);
      if (label) label.textContent = String(percent);
      if (preview) preview.style.opacity = String(percent / 100);
    });
  })();
</script>

