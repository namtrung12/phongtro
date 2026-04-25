<?php $pageTitle = 'Đăng phòng mới'; ?>

<style>
  .page-hero {
    background: linear-gradient(135deg, #f59e0b, #d97706);
    border-radius: 18px;
    padding: 20px;
    color: #fff;
    box-shadow: 0 20px 50px rgba(217, 119, 6, 0.28);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 18px;
  }
  .page-hero h1 { margin: 0; font-size: 22px; }
  .page-hero p { margin: 4px 0 0; color: rgba(255,255,255,0.85); }
  .layout-shell {
    display: grid;
    grid-template-columns: 240px 1fr;
    gap: 18px;
    align-items: start;
    margin-top: 16px;
  }
  @media (max-width: 992px) {
    .layout-shell { grid-template-columns: 1fr; }
  }
  .side-menu {
    background: #fff;
    border-radius: 14px;
    border: 1px solid #e5e7eb;
    padding: 12px;
    box-shadow: 0 14px 30px rgba(15, 23, 42, 0.12);
    display: flex;
    flex-direction: column;
    gap: 8px;
  }
  .side-menu a {
    padding: 10px 12px;
    border-radius: 10px;
    border: 1px solid #e5e7eb;
    font-weight: 600;
    color: #0f172a;
    text-decoration: none;
    transition: all .15s ease;
  }
  .side-menu a.active {
    background: #fff7ed;
    color: #b45309;
    border-color: #fdba74;
  }
  .side-menu a:hover { border-color: #f59e0b; color: #b45309; background: #fffaf0; }
  .card-surface {
    background: #fff;
    border-radius: 16px;
    border: 1px solid #e5e7eb;
    box-shadow: 0 18px 36px rgba(15, 23, 42, 0.08);
    overflow: hidden;
  }
  .card-body {
    padding: 18px;
  }
  .section-title {
    margin: 0 0 10px;
    font-size: 16px;
    display: flex;
    align-items: center;
    gap: 8px;
  }
  .section-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 12px;
  }
  .field {
    display: flex;
    flex-direction: column;
    gap: 6px;
  }
  .field small { color: #6b7280; }
  .field label { font-weight: 700; }
  .field input, .field textarea, .field select {
    padding: 11px 12px;
    border-radius: 10px;
    border: 1px solid #dfe3ea;
    background: #f9fafb;
    transition: border .15s ease, box-shadow .15s ease;
  }
  .field input:focus, .field textarea:focus, .field select:focus {
    border-color: #d97706;
    outline: none;
    box-shadow: 0 0 0 3px rgba(217,119,6,0.14);
  }
  .pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 10px;
    border-radius: 999px;
    background: #fff4d6;
    color: #92400e;
    font-weight: 700;
    font-size: 12px;
  }
  .media-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: 12px;
    align-items: start;
  }
  .muted { color: #6b7280; font-size: 13px; }
  .btn-row { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
  .btn-primary {
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: #fff;
    border: none;
    padding: 12px 16px;
    border-radius: 12px;
    font-weight: 700;
    box-shadow: 0 14px 30px rgba(217, 119, 6, 0.30);
    cursor: pointer;
    transition: transform .12s ease, box-shadow .12s ease;
  }
  .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 18px 36px rgba(180,83,9,0.34); }
  .btn-outline {
    background: transparent;
    color: #111827;
    border: 1px solid #d1d5db;
    padding: 11px 14px;
    border-radius: 12px;
    font-weight: 700;
    cursor: pointer;
  }
  .checkbox-row {
    display: flex;
    gap: 14px;
    flex-wrap: wrap;
    align-items: center;
  }
  .checkbox-row label { display: flex; align-items: center; gap: 8px; font-weight: 600; }
  .badge-tip {
    background: #fff7ed;
    color: #b45309;
    border: 1px dashed #f59e0b;
    padding: 10px 12px;
    border-radius: 12px;
    font-weight: 600;
  }
</style>

<div class="page-hero">
  <div>
    <h1>Đăng phòng mới</h1>
    <p>Hoàn tất thông tin, người thuê sẽ gửi quan tâm ngay khi duyệt.</p>
  </div>
  <div class="pill">⚡ Duyệt nhanh • Bảo mật liên hệ</div>
</div>

<div class="layout-shell">
  <aside class="side-menu">
    <a href="?route=dashboard" class="<?= ($activeMenu ?? '') === 'home' ? 'active' : '' ?>">Tổng quan</a>
    <a href="?route=my-rooms" class="<?= ($activeMenu ?? '') === 'rooms' ? 'active' : '' ?>">Vận hành trọ</a>
    <a href="?route=dashboard&tab=lead#lead" class="<?= ($activeMenu ?? '') === 'leads' ? 'active' : '' ?>">Quan tâm</a>
    <a href="?route=payment-history" class="<?= ($activeMenu ?? '') === 'payments' ? 'active' : '' ?>">Thanh toán</a>
  </aside>

  <div class="card-surface">
    <div class="card-body">
      <form method="post" action="?route=room-create" enctype="multipart/form-data" class="form-shell">
        <div class="section">
          <div class="section-title">📄 Thông tin chính</div>
          <div class="section-grid">
            <div class="field">
              <label for="title">Tiêu đề / mã phòng</label>
              <input id="title" type="text" name="title" required placeholder="VD: Mã P203 - Phòng khép kín 25m2, gần trường">
              <small>Khuyến khích ghi mã phòng ở đầu tiêu đề để dễ quản lý, ví dụ: P203, A12, 3B. Không ghi tên trọ / thương hiệu.</small>
            </div>
            <div class="field">
              <label for="price">Giá (đ/tháng)</label>
              <input id="price" type="number" name="price" min="1000" step="1000" required placeholder="3500000">
              <small>Nhập bội của 1.000đ.</small>
            </div>
            <div class="field">
              <label for="lead_price_expect">Giá nhu cầu mong muốn (đ)</label>
              <input id="lead_price_expect" type="number" name="lead_price_expect" min="3000" step="1000" placeholder="Ví dụ 20000">
              <small>Tối thiểu 3.000đ, nhập bội của 1.000đ. Gợi ý: &lt;1tr≈7k · 1-2tr≈10k · 2-3tr≈17k.</small>
            </div>
          </div>
        </div>

        <?php
        $locationPath = __DIR__ . '/../app/vn_locations.json';
        $locationData = json_decode(is_file($locationPath) ? file_get_contents($locationPath) : '[]', true) ?: [];
        $provinceOptions = array_map(static function ($p) {
            return $p['Name'] ?? '';
        }, $locationData);
        ?>
        <input type="hidden" name="address" id="address_full_create">

        <div class="section" style="margin-top:14px;">
          <div class="section-title">📍 Địa chỉ hiển thị</div>
          <div class="section-grid">
            <div class="field">
              <label for="addr_street_create">Số nhà / đường</label>
              <input type="text" id="addr_street_create" placeholder="Số nhà + Tên đường" required>
              <small>Địa chỉ sẽ tự ghép đầy đủ.</small>
            </div>
            <div class="field">
              <label for="addr_ward_create">Phường/Xã</label>
              <select id="addr_ward_create" name="ward" required>
                <option value="">Chọn xã/phường</option>
              </select>
            </div>
            <div class="field">
              <label for="addr_district_create">Quận/Huyện</label>
              <select id="addr_district_create" name="area" required>
                <option value="">Chọn quận/huyện</option>
              </select>
            </div>
            <div class="field">
              <label for="addr_province_create">Tỉnh/TP</label>
              <select id="addr_province_create" name="province" required>
                <option value="">Chọn Tỉnh/TP</option>
                <?php foreach ($provinceOptions as $opt): ?>
                  <option value="<?= htmlspecialchars($opt) ?>"><?= htmlspecialchars($opt) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="badge-tip" style="margin-top:10px;">Địa chỉ lưu: Số nhà, Phường, Quận/Huyện, Tỉnh.</div>
        </div>

        <div class="section" style="margin-top:14px;">
          <div class="section-title">⚙️ Chi phí tiện ích</div>
          <div class="section-grid">
            <div class="field">
              <label for="electric_price">Giá điện (đ/kWh)</label>
              <input id="electric_price" type="number" name="electric_price" min="0" step="1000" placeholder="4000">
            </div>
            <div class="field">
              <label for="water_price">Giá nước (đ/m³)</label>
              <input id="water_price" type="number" name="water_price" min="0" step="1000" placeholder="20000">
            </div>
          </div>
        </div>

        <div class="section" style="margin-top:14px;">
          <div class="section-title">📝 Mô tả</div>
          <div class="field">
            <textarea name="description" rows="4" placeholder="Nội thất, diện tích, tiện ích..."></textarea>
          </div>
        </div>

        <div class="section" style="margin-top:14px;">
          <div class="section-title">🖼️ Hình ảnh & Video</div>
          <div class="media-grid">
            <div class="field">
              <label>Ảnh chính (URL hoặc upload)</label>
              <input type="text" name="thumbnail" placeholder="https://...">
              <input type="file" name="thumbnail_file" accept="image/*">
            </div>
            <div class="field">
              <label>Ảnh phụ (4-8 ảnh, URL hoặc upload)</label>
              <textarea name="gallery_urls" rows="3" placeholder="Mỗi ảnh 1 dòng, tối đa 8"></textarea>
              <div id="galleryFilesWrap" class="d-flex flex-column gap-2 mt-2">
                <div class="d-flex gap-2 align-items-center single-file-row">
                  <input type="file" name="gallery_files[]" class="form-control" accept="image/*">
                  <button type="button" class="btn btn-outline btn-sm remove-file" aria-label="Xóa">×</button>
                </div>
              </div>
              <button type="button" id="addGalleryFile" class="btn btn-outline btn-sm mt-1">+ Thêm ảnh upload</button>
              <small class="muted">Chọn hoặc dán 4-8 ảnh rõ nét.</small>
            </div>
          </div>
          <div class="section-grid" style="margin-top:12px;">
            <div class="field">
              <label>Video (URL hoặc upload)</label>
              <input type="text" name="video_url" class="form-control mb-2" placeholder="https://youtube.com/watch?v=...">
              <input type="file" name="video_file" class="form-control" accept="video/*">
              <small class="muted">Video 1-3 phút, quay ngang, rõ nét.</small>
            </div>
          </div>
        </div>

        <div class="section" style="margin-top:14px;">
          <div class="section-title">🔒 Tùy chọn</div>
          <div class="checkbox-row">
            <label><input type="checkbox" name="shared_owner" value="1"> Chung chủ</label>
            <label><input type="checkbox" name="closed_room" value="1"> Phòng khép kín</label>
          </div>
        </div>

        <div class="btn-row" style="margin-top:18px;">
          <button class="btn-primary" type="submit">Đăng phòng</button>
          <a class="btn-outline" href="?route=my-rooms">Hủy</a>
          <span class="muted">Sau khi gửi, quản trị viên sẽ duyệt trước khi hiển thị.</span>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  (() => {
    const ids = ['addr_street_create','addr_ward_create','addr_district_create','addr_province_create'];
    const output = document.getElementById('address_full_create');
    const sync = () => {
      const parts = ids.map(id => document.getElementById(id)?.value.trim()).filter(Boolean);
      if (output) output.value = parts.join(', ');
    };
    const form = document.querySelector('form[action="?route=room-create"]');
    const ensureMedia = () => {
      if (!form) return false;
      const urls = (form.gallery_urls?.value || '').split(/\n/).map(s => s.trim()).filter(Boolean);
      const fileInputs = Array.from(form.querySelectorAll('input[name="gallery_files[]"]'));
      const filesCount = fileInputs.reduce((sum, inp) => sum + (inp?.files?.length || 0), 0);
      const total = urls.length + filesCount;
      const hasVideo = (() => {
        const url = form.video_url?.value.trim();
        const files = form.video_file?.files?.length || 0;
        return !!url || files > 0;
      })();
      let msg = '';
      if (total < 4 || total > 8) {
        msg += 'Cần 4-8 ảnh phụ (URL hoặc upload).';
      }
      if (!hasVideo) {
        msg += (msg ? '\n' : '') + 'Cần ít nhất 1 video (khuyên 1-3 phút).';
      }
      if (msg) {
        alert(msg);
        return false;
      }
      return true;
    };
    const locationData = <?= json_encode($locationData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    const provinceEl = document.getElementById('addr_province_create');
    const districtEl = document.getElementById('addr_district_create');
    const wardEl = document.getElementById('addr_ward_create');

    const fillOptions = (el, list, placeholder, currentValue = '') => {
      if (!el) return;
      el.innerHTML = '';
      const first = document.createElement('option');
      first.value = '';
      first.textContent = placeholder;
      el.appendChild(first);
      (list || []).forEach(item => {
        const opt = document.createElement('option');
        opt.value = item;
        opt.textContent = item;
        if (currentValue && currentValue === item) opt.selected = true;
        el.appendChild(opt);
      });
    };

    const onProvinceChange = (keep = false) => {
      const province = provinceEl?.value || '';
      const provinceObj = locationData.find(p => p.Name === province);
      const districts = provinceObj?.Districts?.map(d => d.Name) || [];
      const currentDistrict = keep ? districtEl?.value || '' : '';
      fillOptions(districtEl, districts, 'Chọn quận/huyện', currentDistrict && districts.includes(currentDistrict) ? currentDistrict : '');
      fillOptions(wardEl, [], 'Chọn xã/phường');
      sync();
    };

    const onDistrictChange = (keep = false) => {
      const province = provinceEl?.value || '';
      const district = districtEl?.value || '';
      const provinceObj = locationData.find(p => p.Name === province);
      const districtObj = provinceObj?.Districts?.find(d => d.Name === district);
      const wards = districtObj?.Wards?.map(w => w.Name) || [];
      const currentWard = keep ? wardEl?.value || '' : '';
      fillOptions(wardEl, wards, 'Chọn xã/phường', currentWard && wards.includes(currentWard) ? currentWard : '');
      sync();
    };

    provinceEl?.addEventListener('change', () => onProvinceChange(false));
    districtEl?.addEventListener('change', () => onDistrictChange(false));
    wardEl?.addEventListener('change', sync);

    // dynamic gallery file inputs
    const wrap = document.getElementById('galleryFilesWrap');
    const addBtn = document.getElementById('addGalleryFile');
    const createRow = () => {
      const row = document.createElement('div');
      row.className = 'd-flex gap-2 align-items-center single-file-row';
      const input = document.createElement('input');
      input.type = 'file';
      input.name = 'gallery_files[]';
      input.accept = 'image/*';
      input.className = 'form-control';
      const rm = document.createElement('button');
      rm.type = 'button';
      rm.className = 'btn btn-outline-secondary btn-sm remove-file';
      rm.textContent = '×';
      rm.addEventListener('click', () => {
        if (wrap && wrap.children.length > 1) {
          row.remove();
        }
      });
      row.appendChild(input);
      row.appendChild(rm);
      return row;
    };
    addBtn?.addEventListener('click', () => {
      if (!wrap) return;
      wrap.appendChild(createRow());
    });
    wrap?.querySelectorAll('.remove-file').forEach(btn => {
      btn.addEventListener('click', () => {
        const row = btn.closest('.single-file-row');
        if (wrap.children.length > 1 && row) row.remove();
      });
    });

    // init
    fillOptions(provinceEl, locationData.map(p => p.Name), 'Chọn Tỉnh/TP');
    onProvinceChange(true);
    onDistrictChange(true);

    ids.forEach(id => {
      const el = document.getElementById(id);
      if (el) ['input','change','blur'].forEach(evt => el.addEventListener(evt, sync));
    });
    form?.addEventListener('submit', (e) => {
      sync();
      if (!ensureMedia()) {
        e.preventDefault();
      }
    });
    sync();
  })();
</script>
