<?php
$pageTitle = 'Tìm kiếm';
$locationPath = __DIR__ . '/../app/vn_locations.json';
$locationData = json_decode(is_file($locationPath) ? file_get_contents($locationPath) : '[]', true) ?: [];
$provinces = array_map(static function ($p) {
    return $p['Name'] ?? '';
}, $locationData);
$buildMobileLink = static function(array $params = []): string {
    return '?' . http_build_query(array_merge(['route' => 'search'], $params));
};
$mobileQuickFilters = [
    ['label' => 'Gần trường', 'params' => ['near_school' => 1]],
    ['label' => 'Khép kín', 'params' => ['closed_room' => 1]],
    ['label' => '< 3 triệu', 'params' => ['max_price' => 3000000]],
    ['label' => 'Không chung', 'params' => ['shared_owner' => '0']],
];
?>
<style>
  .search-shell { display:flex; flex-direction:column; gap:18px; margin-top:10px; }
  .search-card { border-radius: var(--radius); background:#fff; box-shadow: var(--shadow); padding:16px; }
  .search-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(260px,1fr)); gap:12px; }
  .room-card { border:1px solid #f1f5f9; border-radius:14px; overflow:hidden; box-shadow:0 10px 24px rgba(15,23,42,0.08); background:#fff; }
  .room-thumb { width:100%; height:150px; object-fit:cover; }
  .room-body { padding:12px; display:flex; flex-direction:column; gap:6px; }
  .chip { background:#fff8e1; border:1px solid #f6d470; color:#7c2d12; padding:4px 8px; border-radius:999px; font-size:11px; font-weight:700; }
  .room-price { color: var(--primary-dark); font-weight:800; }
  .filter-inline { display:flex; gap:10px; flex-wrap:wrap; }
  .mobile-filter-bar { display:none; }
  .mobile-chip-scroll { display:flex; gap:8px; overflow-x:auto; padding:4px 2px 6px; margin:0 -2px; }
  .mobile-chip-scroll::-webkit-scrollbar { height:6px; }
  .mobile-chip-scroll::-webkit-scrollbar-thumb { background: rgba(0,0,0,0.08); border-radius:999px; }
  .mobile-chip {
      flex:0 0 auto;
      padding:8px 10px;
      border-radius:10px;
      background:#fff7ed;
      color:#7c2d12;
      font-weight:800;
      border:1px solid #fbbf24;
      text-decoration:none;
      box-shadow: 0 8px 16px rgba(217,119,6,0.16);
  }
  .mobile-note { font-size:12px; color: var(--muted); }
  @media (max-width: 768px) {
    .search-shell { gap:12px; }
    .search-card { padding:12px; }
    .search-grid { grid-template-columns: 1fr; gap:10px; }
    .filter-inline { flex-wrap: wrap; }
    .room-thumb { height: 180px; }
    .results-grid { grid-template-columns: 1fr; gap:12px; }
    .mobile-filter-bar { display:flex; flex-direction:column; gap:8px; margin-bottom:10px; }
  }
</style>

<div class="search-shell">
  <div class="search-card">
    <div class="mobile-filter-bar" aria-label="Lọc nhanh trên di động">
      <div class="mobile-chip-scroll">
        <?php foreach ($mobileQuickFilters as $chip): ?>
          <a class="mobile-chip" href="<?= htmlspecialchars($buildMobileLink($chip['params'])) ?>"><?= htmlspecialchars($chip['label']) ?></a>
        <?php endforeach; ?>
      </div>
      <div class="mobile-note">Chạm để áp dụng ngay · Kéo xuống xem kết quả</div>
    </div>
    <form method="get" action="?">
      <input type="hidden" name="route" value="search">
      <div class="search-grid">
        <div>
          <label class="form-label">Từ khóa</label>
          <input class="form-control" type="text" name="keyword" value="<?= htmlspecialchars($filters['keyword'] ?? '') ?>" placeholder="Tên phòng, địa chỉ, khu vực...">
        </div>
        <div>
          <label class="form-label">Tỉnh/TP</label>
          <select class="form-control" name="province" id="s_province">
            <option value="">-- Tất cả --</option>
            <?php foreach ($provinces as $p): ?>
              <option value="<?= htmlspecialchars($p) ?>" <?= ($filters['province'] ?? '') === $p ? 'selected' : '' ?>><?= htmlspecialchars($p) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="form-label">Quận/Huyện</label>
          <select class="form-control" name="district" id="s_district">
            <option value="<?= htmlspecialchars($filters['district'] ?? '') ?>"><?= htmlspecialchars($filters['district'] ?? '-- Tất cả --') ?></option>
          </select>
        </div>
        <div>
          <label class="form-label">Xã/Phường</label>
          <select class="form-control" name="ward" id="s_ward">
            <option value="<?= htmlspecialchars($filters['ward'] ?? '') ?>"><?= htmlspecialchars($filters['ward'] ?? '-- Tất cả --') ?></option>
          </select>
        </div>
        <div>
          <label class="form-label">Khoảng giá (đ/tháng)</label>
          <div class="filter-inline">
            <input class="form-control" style="max-width:120px" type="number" name="min_price" min="0" step="1000" placeholder="Từ" value="<?= htmlspecialchars($filters['min_price'] ?? '') ?>">
            <input class="form-control" style="max-width:120px" type="number" name="max_price" min="0" step="1000" placeholder="Đến" value="<?= htmlspecialchars($filters['max_price'] ?? '') ?>">
          </div>
        </div>
        <div>
          <label class="form-label">Giá điện (đ/kWh)</label>
          <div class="filter-inline">
            <input class="form-control" style="max-width:120px" type="number" name="min_electric_price" min="0" step="1000" placeholder="Từ" value="<?= htmlspecialchars($filters['min_electric_price'] ?? '') ?>">
            <input class="form-control" style="max-width:120px" type="number" name="max_electric_price" min="0" step="1000" placeholder="Đến" value="<?= htmlspecialchars($filters['max_electric_price'] ?? '') ?>">
          </div>
        </div>
        <div>
          <label class="form-label">Giá nước (đ/m³)</label>
          <div class="filter-inline">
            <input class="form-control" style="max-width:120px" type="number" name="min_water_price" min="0" step="1000" placeholder="Từ" value="<?= htmlspecialchars($filters['min_water_price'] ?? '') ?>">
            <input class="form-control" style="max-width:120px" type="number" name="max_water_price" min="0" step="1000" placeholder="Đến" value="<?= htmlspecialchars($filters['max_water_price'] ?? '') ?>">
          </div>
        </div>
        <div>
          <label class="form-label">Sắp xếp</label>
          <select class="form-control" name="sort">
            <option value="vip" <?= ($sort ?? '') === 'vip' ? 'selected' : '' ?>>Ưu tiên</option>
            <option value="newest" <?= ($sort ?? '') === 'newest' ? 'selected' : '' ?>>Mới nhất</option>
            <option value="price_asc" <?= ($sort ?? '') === 'price_asc' ? 'selected' : '' ?>>Giá tăng dần</option>
            <option value="price_desc" <?= ($sort ?? '') === 'price_desc' ? 'selected' : '' ?>>Giá giảm dần</option>
          </select>
        </div>
      </div>

      <div class="search-grid" style="margin-top:10px;">
        <div>
          <label class="form-label d-block">Chung chủ?</label>
          <div class="filter-inline">
            <label class="form-check"><input type="radio" name="shared_owner" value="" <?= ($filters['shared_owner'] ?? '') === '' ? 'checked' : '' ?>> <span>Tất cả</span></label>
            <label class="form-check"><input type="radio" name="shared_owner" value="1" <?= ($filters['shared_owner'] ?? '') === '1' ? 'checked' : '' ?>> <span>Chung chủ</span></label>
            <label class="form-check"><input type="radio" name="shared_owner" value="0" <?= ($filters['shared_owner'] ?? '') === '0' ? 'checked' : '' ?>> <span>Không chung</span></label>
          </div>
        </div>
        <div>
          <label class="form-label d-block">Khép kín?</label>
          <div class="filter-inline">
            <label class="form-check"><input type="radio" name="closed_room" value="" <?= ($filters['closed_room'] ?? '') === '' ? 'checked' : '' ?>> <span>Tất cả</span></label>
            <label class="form-check"><input type="radio" name="closed_room" value="1" <?= ($filters['closed_room'] ?? '') === '1' ? 'checked' : '' ?>> <span>Khép kín</span></label>
            <label class="form-check"><input type="radio" name="closed_room" value="0" <?= ($filters['closed_room'] ?? '') === '0' ? 'checked' : '' ?>> <span>Không khép kín</span></label>
          </div>
        </div>
        <div>
          <label class="form-label d-block">Ưu tiên gần trường</label>
          <label class="form-check"><input type="checkbox" name="near_school" value="1" <?= ($filters['near_school'] ?? '') !== '' ? 'checked' : '' ?>> <span>Có nhắc tới trường/đại học</span></label>
        </div>
      </div>

      <div class="filter-inline" style="margin-top:12px;">
        <button class="btn btn-primary" type="submit">Tìm kiếm</button>
        <a class="btn btn-outline" href="?route=search">Xóa lọc</a>
      </div>
    </form>
  </div>

  <div class="search-card">
    <h3 class="section-title">Kết quả (<?= count($rooms) ?>)</h3>
    <?php if (empty($rooms)): ?>
      <p class="text-muted">Không tìm thấy phòng phù hợp.</p>
    <?php else: ?>
      <div class="search-grid results-grid">
        <?php foreach ($rooms as $room): ?>
          <?php
            $img = $room['thumbnail'] ?? '';
            if (!$img) {
              foreach (['image1','image2','image3','image4'] as $k) { if (!empty($room[$k])) { $img = $room[$k]; break; } }
            }
            if (!$img) $img = 'https://via.placeholder.com/400x200?text=Phong';
          ?>
          <div class="room-card">
            <img class="room-thumb" src="<?= htmlspecialchars(assetUrl($img)) ?>" alt="<?= htmlspecialchars($room['title']) ?>">
            <div class="room-body">
              <strong><?= htmlspecialchars($room['title']) ?></strong>
              <div class="filter-inline">
                <span class="chip"><?= htmlspecialchars($room['area']) ?></span>
                <?php if (!empty($room['shared_owner'])): ?><span class="chip">Chung chủ</span><?php endif; ?>
                <?php if (!empty($room['closed_room'])): ?><span class="chip">Khép kín</span><?php endif; ?>
              </div>
              <div class="room-price"><?= number_format((int)$room['price'], 0, ',', '.') ?> đ/tháng</div>
              <a class="btn btn-success btn-sm" href="?route=room&id=<?= (int)$room['id'] ?>">Xem phòng</a>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<script>
(function() {
  const data = <?= json_encode($locationData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
  const provinceEl = document.getElementById('s_province');
  const districtEl = document.getElementById('s_district');
  const wardEl = document.getElementById('s_ward');
  const currentDistrict = <?= json_encode($filters['district'] ?? '') ?>;
  const currentWard = <?= json_encode($filters['ward'] ?? '') ?>;

  const fill = (el, items, placeholder, current) => {
    if (!el) return;
    el.innerHTML = '';
    const opt0 = document.createElement('option');
    opt0.value = '';
    opt0.textContent = placeholder;
    el.appendChild(opt0);
    items.forEach(v => {
      const o = document.createElement('option');
      o.value = v;
      o.textContent = v;
      if (current && current === v) o.selected = true;
      el.appendChild(o);
    });
  };

  const onProvince = (keep = false) => {
    const p = provinceEl?.value || '';
    const pObj = data.find(x => x.Name === p);
    const districts = (pObj?.Districts || []).map(d => d.Name);
    const dist = keep ? currentDistrict : '';
    fill(districtEl, districts, '-- Tất cả --', dist && districts.includes(dist) ? dist : '');
    onDistrict(keep);
  };

  const onDistrict = (keep = false) => {
    const p = provinceEl?.value || '';
    const d = districtEl?.value || '';
    const pObj = data.find(x => x.Name === p);
    const dObj = pObj?.Districts?.find(x => x.Name === d);
    const wards = (dObj?.Wards || []).map(w => w.Name);
    const ward = keep ? currentWard : '';
    fill(wardEl, wards, '-- Tất cả --', ward && wards.includes(ward) ? ward : '');
  };

  provinceEl?.addEventListener('change', () => onProvince(false));
  districtEl?.addEventListener('change', () => onDistrict(false));

  if (provinceEl && provinceEl.value) {
    onProvince(true);
    if (currentDistrict) districtEl.value = currentDistrict;
    if (currentWard) wardEl.value = currentWard;
  } else {
    onProvince(false);
  }
})();
</script>
