<?php
$pageTitle = 'Sửa phòng';
$addrParts = array_map('trim', explode(',', $room['address'] ?? ''));
$addrStreet = $addrParts[0] ?? '';
$addrWard = $addrParts[1] ?? '';
$addrDistrict = $addrParts[2] ?? '';
$addrProvince = $addrParts[3] ?? '';
?>
<div class="admin-shell">
  <aside class="admin-menu">
    <a href="?route=dashboard" class="<?= ($activeMenu ?? '') === 'home' ? 'active' : '' ?>">Tổng quan</a>
    <a href="?route=my-rooms" class="<?= ($activeMenu ?? '') === 'rooms' ? 'active' : '' ?>">Vận hành trọ</a>
    <a href="?route=dashboard&tab=lead#lead" class="<?= ($activeMenu ?? '') === 'leads' ? 'active' : '' ?>">Quan tâm</a>
    <a href="?route=payment-history" class="<?= ($activeMenu ?? '') === 'payments' ? 'active' : '' ?>">Thanh toán</a>
  </aside>
  <div>
    <div class="card">
      <div class="card-body">
        <style>
          .media-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 12px; align-items: start; }
          .media-block { display: flex; flex-direction: column; gap: 8px; }
        </style>
        <h1 class="mb-2" style="font-size:20px;">Sửa phòng</h1>
        <form method="post" action="?route=room-edit" class="row g-3" enctype="multipart/form-data">
          <input type="hidden" name="room_id" value="<?= (int)$room['id'] ?>">
          <div class="col-md-6">
            <label class="form-label">Tiêu đề / mã phòng</label>
            <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($room['title']) ?>" placeholder="VD: Mã P203 - Phòng khép kín 25m2, gần trường" required>
            <small class="text-muted">Khuyến khích ghi mã phòng ở đầu tiêu đề để dễ quản lý, ví dụ: P203, A12, 3B.</small>
          </div>
          <div class="col-md-3">
            <label class="form-label">Giá (đ/tháng)</label>
            <input type="number" name="price" class="form-control" min="1000" step="1000" value="<?= (int)$room['price'] ?>" required>
            <small class="text-muted">Nhập bội của 1.000đ.</small>
          </div>
          <div class="col-md-3">
            <label class="form-label">Khu vực</label>
            <input type="text" name="area" class="form-control" value="<?= htmlspecialchars($room['area']) ?>" required>
          </div>
          <input type="hidden" name="address" id="address_full_edit" value="<?= htmlspecialchars($room['address']) ?>">
          <div class="col-md-12">
            <label class="form-label">Địa chỉ</label>
            <div class="row g-2">
              <div class="col-md-6">
                <input type="text" id="addr_street_edit" class="form-control" placeholder="Số nhà, đường" value="<?= htmlspecialchars($addrStreet) ?>" required>
              </div>
              <div class="col-md-6">
                <input type="text" id="addr_ward_edit" class="form-control" placeholder="Xã/Phường" value="<?= htmlspecialchars($addrWard) ?>" required>
              </div>
              <div class="col-md-6">
                <input type="text" id="addr_district_edit" class="form-control" placeholder="Quận/Huyện" value="<?= htmlspecialchars($addrDistrict) ?>" required>
              </div>
              <div class="col-md-6">
                <input type="text" id="addr_province_edit" class="form-control" placeholder="Tỉnh/TP" value="<?= htmlspecialchars($addrProvince) ?>" required>
              </div>
            </div>
            <small class="text-muted">Địa chỉ sẽ tự ghép: Số nhà, Phường, Quận/Huyện, Tỉnh.</small>
          </div>
          <div class="col-md-6">
            <label class="form-label">Giá điện (đ/kWh)</label>
            <input type="number" name="electric_price" class="form-control" min="0" step="1000" value="<?= htmlspecialchars($room['electric_price']) ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label">Giá nước (đ/m³)</label>
            <input type="number" name="water_price" class="form-control" min="0" step="1000" value="<?= htmlspecialchars($room['water_price']) ?>">
          </div>
          <div class="col-md-12">
            <label class="form-label">Mô tả</label>
            <textarea name="description" class="form-control" rows="3" placeholder="Nội thất, diện tích, tiện ích..."><?= htmlspecialchars($room['description']) ?></textarea>
          </div>
          <div class="media-row">
            <div class="media-block">
              <label class="form-label mb-1">Ảnh chính (URL hoặc upload)</label>
              <input type="text" name="thumbnail" class="form-control" value="<?= htmlspecialchars($room['thumbnail']) ?>">
              <input type="file" name="thumbnail_file" class="form-control">
            </div>
            <div class="media-block">
              <label class="form-label mb-1">Ảnh phụ (4-8 ảnh)</label>
              <textarea name="gallery_urls" class="form-control" rows="2" placeholder="Dán mỗi ảnh 1 dòng, tối đa 8 ảnh"><?php
                $galleryExisting = [];
                foreach (['image1','image2','image3','image4','image5','image6','image7','image8'] as $k) {
                  if (!empty($room[$k])) $galleryExisting[] = $room[$k];
                }
                echo htmlspecialchars(implode("\n", $galleryExisting));
              ?></textarea>
              <input type="file" name="gallery_files[]" class="form-control" accept="image/*" multiple>
              <small class="text-muted">Chọn hoặc dán 4-8 ảnh, chất lượng rõ nét.</small>
            </div>
          </div>
          <div class="col-md-6">
            <label class="form-label">Video (URL hoặc upload)</label>
            <input type="text" name="video_url" class="form-control mb-2" value="<?= htmlspecialchars($room['video_url'] ?? '') ?>">
            <input type="file" name="video_file" class="form-control" accept="video/*">
          </div>
          <div class="col-md-12 d-flex gap-2 align-items-center">
            <label class="form-check">
              <input class="form-check-input" type="checkbox" name="shared_owner" value="1" <?= !empty($room['shared_owner']) ? 'checked' : '' ?>>
              <span>Chung chủ</span>
            </label>
            <label class="form-check">
              <input class="form-check-input" type="checkbox" name="closed_room" value="1" <?= !empty($room['closed_room']) ? 'checked' : '' ?>>
              <span>Phòng khép kín</span>
            </label>
          </div>
          <div class="col-md-12 d-flex gap-2">
            <button class="btn btn-primary" type="submit">Lưu</button>
            <a class="btn btn-outline" href="?route=my-rooms">Hủy</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
  (() => {
    const ids = ['addr_street_edit','addr_ward_edit','addr_district_edit','addr_province_edit'];
    const output = document.getElementById('address_full_edit');
    const sync = () => {
      const parts = ids.map(id => document.getElementById(id)?.value.trim()).filter(Boolean);
      if (output) output.value = parts.join(', ');
    };
    ids.forEach(id => {
      const el = document.getElementById(id);
      if (el) ['input','change','blur'].forEach(evt => el.addEventListener(evt, sync));
    });
    const form = document.querySelector('form[action="?route=room-edit"]');
    form?.addEventListener('submit', sync);
    sync();
  })();
</script>
