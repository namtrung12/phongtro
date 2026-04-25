<?php $pageTitle = 'Đăng nhu cầu'; ?>
<style>
  .seek-posts-card {
    overflow: hidden;
  }
  .seek-posts-tabs {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
  .seek-posts-tabs .btn {
    width: 100%;
    min-width: 0;
  }
  #formSeek {
    display: grid;
    grid-template-columns: repeat(12, minmax(0, 1fr));
    gap: 12px;
    align-items: start;
  }
  #formSeek > .col-md-12,
  #formSeek > .col-md-6,
  #formSeek > .col-md-3 {
    min-width: 0;
  }
  #formSeek > .col-md-12 { grid-column: span 12; }
  #formSeek > .col-md-6 { grid-column: span 6; }
  #formSeek > .col-md-3 { grid-column: span 3; }
  .seek-check-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(136px, 1fr));
    gap: 10px 12px;
  }
  .seek-check-grid .form-check {
    min-width: 0;
    align-items: flex-start;
  }
  .seek-check-grid .form-check span {
    line-height: 1.35;
  }
  .seek-actions {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 10px;
  }
  .seek-actions .btn {
    min-width: 0;
  }
  .seek-posts-table {
    min-width: 760px;
  }
  .seek-posts-table td img {
    width: 48px;
    height: 48px;
    max-width: none;
  }
  .seek-posts-mobile {
    display: none;
  }
  .seek-post-card {
    border: 1px solid #f3e2c7;
    border-radius: 14px;
    background: linear-gradient(180deg, #fffdf9, #fff8ef);
    padding: 12px;
    box-shadow: 0 10px 24px rgba(217, 119, 6, 0.08);
  }
  .seek-post-card + .seek-post-card {
    margin-top: 10px;
  }
  .seek-post-card-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
  }
  .seek-post-card-area {
    font-size: 15px;
    font-weight: 800;
    line-height: 1.35;
    color: #111827;
  }
  .seek-post-card-user {
    margin-top: 4px;
    font-size: 12px;
    color: #6b7280;
  }
  .seek-post-card-time {
    flex: 0 0 auto;
    font-size: 12px;
    font-weight: 700;
    color: #92400e;
    white-space: nowrap;
  }
  .seek-post-card-meta {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 8px;
    margin-top: 10px;
  }
  .seek-post-meta-item {
    min-width: 0;
    padding: 8px 10px;
    border-radius: 12px;
    border: 1px solid #f4e4c8;
    background: rgba(255, 255, 255, 0.92);
  }
  .seek-post-meta-label {
    display: block;
    margin-bottom: 3px;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: #9a3412;
  }
  .seek-post-meta-value {
    display: block;
    font-size: 13px;
    font-weight: 700;
    line-height: 1.35;
    color: #1f2937;
    overflow-wrap: anywhere;
  }
  .seek-post-card-images {
    display: flex;
    gap: 8px;
    margin-top: 10px;
    overflow: hidden;
  }
  .seek-post-card-images a {
    flex: 0 0 auto;
    text-decoration: none;
  }
  .seek-post-card-images img {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    object-fit: cover;
    border: 1px solid #f3d7aa;
    display: block;
  }
  .seek-post-card-note {
    margin-top: 10px;
    font-size: 13px;
    line-height: 1.55;
    color: #4b5563;
    white-space: pre-line;
    display: -webkit-box;
    -webkit-line-clamp: 4;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }
  .seek-posts-empty {
    padding: 12px;
    border-radius: 12px;
    background: #fffaf3;
    border: 1px dashed #f3d7aa;
    color: #6b7280;
  }
  @media (max-width: 768px) {
    #formSeek {
      grid-template-columns: minmax(0, 1fr);
      gap: 10px;
    }
    #formSeek > .col-md-12,
    #formSeek > .col-md-6,
    #formSeek > .col-md-3 {
      grid-column: 1 / -1;
      width: 100%;
    }
    .seek-check-grid {
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 10px 8px;
    }
    .seek-check-grid .form-check {
      display: grid;
      grid-template-columns: 18px minmax(0, 1fr);
      gap: 8px;
    }
    .seek-check-grid .form-check-input {
      margin-top: 2px;
    }
    .seek-actions {
      flex-direction: column;
      align-items: stretch;
    }
    .seek-actions .btn {
      width: 100%;
    }
    .seek-posts-list .table-responsive {
      display: none;
    }
    .seek-posts-mobile {
      display: block;
    }
    .seek-post-card {
      padding: 11px;
    }
    .seek-post-card-head {
      gap: 10px;
    }
    .seek-post-card-time {
      font-size: 11px;
    }
    .seek-post-card-meta {
      gap: 7px;
    }
  }
  @media (max-width: 360px) {
    .seek-check-grid {
      grid-template-columns: minmax(0, 1fr);
    }
    .seek-post-card-meta {
      grid-template-columns: minmax(0, 1fr);
    }
    .seek-post-card-head {
      flex-direction: column;
    }
  }
</style>
<div class="card seek-posts-card">
  <div class="card-body">
    <div class="d-flex gap-2 mb-3 seek-posts-tabs">
      <button class="btn btn-primary btn-sm" type="button" id="tabRoommate">Tìm người ở ghép</button>
      <button class="btn btn-outline btn-sm" type="button" id="tabRoom">Đăng tìm phòng</button>
    </div>
    <h1 class="mb-2" style="font-size:20px;" id="formTitle">Đăng nhu cầu tìm người ở ghép</h1>
    <p class="text-muted mb-3" id="formDesc">Không chèn SĐT/Zalo trong nội dung. Chủ trọ mua nhu cầu mới thấy thông tin.</p>
    <?php if (!empty($flashError)): ?><div class="alert alert-danger"><?= htmlspecialchars($flashError) ?></div><?php endif; ?>
    <?php if (!empty($flashSuccess)): ?><div class="alert alert-success"><?= htmlspecialchars($flashSuccess) ?></div><?php endif; ?>
    <form method="post" action="?route=seek-posts" enctype="multipart/form-data" class="row g-3" id="formSeek">
      <input type="hidden" name="post_kind" id="postKind" value="roommate">
      <div class="col-md-6">
        <label class="form-label">Khu vực mong muốn</label>
        <input type="text" name="area" class="form-control" required placeholder="VD: TP Thanh Hóa, Quận 1, ĐH Bách Khoa...">
      </div>
      <div class="col-md-3 kind-room">
        <label class="form-label">Quận/Huyện</label>
        <input type="text" name="district" class="form-control" placeholder="VD: Quận 1, TP Thanh Hóa">
      </div>
      <div class="col-md-3 kind-room">
        <label class="form-label">Phường/Xã</label>
        <input type="text" name="ward" class="form-control" placeholder="VD: P. Điện Biên">
      </div>
      <div class="col-md-3 kind-room">
        <label class="form-label">Gần trường / công ty</label>
        <input type="text" name="near_place" class="form-control" placeholder="VD: Gần ĐH Hồng Đức">
      </div>
      <div class="col-md-3">
        <label class="form-label">Giá tối thiểu (đ)</label>
        <input type="number" name="price_min" class="form-control" placeholder="1000000" required min="1000" step="1000">
      </div>
      <div class="col-md-3">
        <label class="form-label">Giá tối đa (đ)</label>
        <input type="number" name="price_max" class="form-control" placeholder="3000000" required min="1000" step="1000">
      </div>
      <div class="col-md-3">
        <label class="form-label">Số người</label>
        <input type="number" name="people_count" id="fieldPeople" class="form-control" min="1" placeholder="1-4" required>
      </div>
      <div class="col-md-3">
        <label class="form-label">Giới tính</label>
        <select name="gender" id="fieldGender" class="form-control" required>
          <option value="">-- Chọn giới tính --</option>
          <option value="female" <?= ($gender ?? '') === 'female' ? 'selected' : '' ?>>Nữ</option>
          <option value="male" <?= ($gender ?? '') === 'male' ? 'selected' : '' ?>>Nam</option>
          <option value="any" <?= ($gender ?? '') === 'any' ? 'selected' : '' ?>>Không yêu cầu</option>
        </select>
      </div>
      <div class="col-md-3 kind-room">
        <label class="form-label">Loại phòng mong muốn</label>
        <select name="room_type" class="form-control">
          <option value="">-- Chọn loại phòng --</option>
          <option>Phòng trọ</option>
          <option>Chung cư mini</option>
          <option>Studio</option>
          <option>Ký túc xá / Nhà lưu trú</option>
          <option>Ở ghép</option>
        </select>
      </div>
      <div class="col-md-3 kind-room">
        <label class="form-label">Diện tích tối thiểu (m²)</label>
        <input type="number" name="area_min" class="form-control" min="5" step="1" placeholder="15">
      </div>
      <div class="col-md-3">
        <label class="form-label">Đặc điểm phòng</label>
        <div class="seek-check-grid">
          <label class="form-check" style="gap:8px;">
            <input type="checkbox" name="shared_owner" value="1" class="form-check-input">
            <span>Chung chủ</span>
          </label>
          <label class="form-check" style="gap:8px;">
            <input type="checkbox" name="closed_room" value="1" class="form-check-input">
            <span>Khép kín</span>
          </label>
        </div>
      </div>
      <div class="col-md-3 kind-room">
        <label class="form-label">Thời gian chuyển đến</label>
        <input type="text" name="move_in" class="form-control" placeholder="VD: Trong tháng 5, tuần sau...">
      </div>
      <div class="col-md-3 kind-roommate">
        <label class="form-label">Cần thêm (người)</label>
        <input type="number" name="need_people" class="form-control" min="1" placeholder="1" value="1">
      </div>
      <div class="col-md-3 kind-roommate">
        <label class="form-label">Đang ở (người)</label>
        <input type="number" name="current_people" class="form-control" min="1" placeholder="1">
      </div>
      <div class="col-md-3">
        <label class="form-label">Ảnh tham khảo (tùy chọn)</label>
        <input type="file" name="room_image1" id="img1" accept="image/*" class="form-control">
        <small class="text-muted" id="img1Note">Mood/reference nếu cần.</small>
      </div>
      <div class="col-md-3 kind-roommate">
        <label class="form-label">Ảnh phòng 2 (tùy chọn)</label>
        <input type="file" name="room_image2" accept="image/*" class="form-control">
        <small class="text-muted">Thêm góc khác nếu có.</small>
      </div>
      <div class="col-md-3 kind-roommate">
        <label class="form-label">Ảnh phòng 3 (tùy chọn)</label>
        <input type="file" name="room_image3" accept="image/*" class="form-control">
        <small class="text-muted">Thêm góc khác nếu có.</small>
      </div>
      <div class="col-md-12">
        <label class="form-label">Mô tả nhu cầu</label>
        <textarea name="note" id="noteField" class="form-control" rows="4" required placeholder="Thời gian dọn, yêu cầu ở ghép, nội thất cần, ưu tiên gần trường..."></textarea>
      </div>
      <div class="col-md-12 kind-room">
        <label class="form-label">Tiện nghi mong muốn</label>
        <input type="text" name="amenities" class="form-control" placeholder="VD: Điều hòa, máy giặt, thang máy...">
      </div>
      <div class="col-md-12 kind-room">
        <label class="form-label">Yêu cầu nội thất (chọn nhanh)</label>
        <div class="seek-check-grid">
          <?php $amenitiesList = ['Điều hòa','Nóng lạnh','Máy giặt','Wifi','Chỗ để xe','Thang máy','Ban công','Bếp riêng']; ?>
          <?php foreach ($amenitiesList as $am): ?>
            <label class="form-check" style="gap:8px;">
              <input type="checkbox" name="amenities_check[]" value="<?= htmlspecialchars($am) ?>" class="form-check-input">
              <span><?= htmlspecialchars($am) ?></span>
            </label>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="col-md-3 kind-room">
        <label class="form-label">Mức ưu tiên</label>
        <select name="priority" class="form-control">
          <option value="">-- Chọn ưu tiên --</option>
          <option>Ưu tiên gần trường</option>
          <option>Ưu tiên giá rẻ</option>
          <option>Ưu tiên full nội thất</option>
          <option>Ưu tiên gần công ty</option>
        </select>
      </div>
      <div class="col-md-6 kind-roommate">
        <label class="form-label">Địa chỉ phòng đang ở</label>
        <input type="text" name="room_address" class="form-control" placeholder="Số nhà, đường, phường/quận">
      </div>
      <div class="col-md-3 kind-roommate">
        <label class="form-label">Tổng tiền phòng (đ)</label>
        <input type="number" name="total_price" class="form-control" min="1000" step="1000" placeholder="2000000">
      </div>
      <div class="col-md-3 kind-roommate">
        <label class="form-label">Chi phí mỗi người (đ)</label>
        <input type="number" name="share_price" class="form-control" min="0" step="1000" placeholder="1000000">
      </div>
      <div class="col-md-12 kind-roommate">
        <label class="form-label">Tính cách / giờ giấc</label>
        <input type="text" name="schedule" class="form-control" placeholder="Đi làm giờ hành chính, không hút thuốc, về trước 23h...">
      </div>
      <div class="col-md-12 d-flex gap-2 seek-actions">
        <button class="btn btn-primary" type="submit">Đăng bài</button>
        <a class="btn btn-outline" href="?route=rooms">Về trang danh sách</a>
      </div>
    </form>
  </div>
</div>

<script>
(() => {
  const tabRoommate = document.getElementById('tabRoommate');
  const tabRoom = document.getElementById('tabRoom');
  const title = document.getElementById('formTitle');
  const desc = document.getElementById('formDesc');
  const kindInput = document.getElementById('postKind');
  const img1 = document.getElementById('img1');
  const img1Note = document.getElementById('img1Note');
  const gender = document.getElementById('fieldGender');
  const people = document.getElementById('fieldPeople');
  const note = document.getElementById('noteField');
  const kindRoommateEls = document.querySelectorAll('.kind-roommate');
  const kindRoomEls = document.querySelectorAll('.kind-room');

  const setKind = (kind) => {
    kindInput.value = kind;
    if (kind === 'roommate') {
      title.textContent = 'Đăng nhu cầu tìm người ở ghép';
      desc.textContent = 'Không chèn SĐT/Zalo trong nội dung. Chủ trọ mua nhu cầu mới thấy thông tin.';
      img1.required = true;
      img1Note.textContent = 'Ảnh phòng hiện tại của bạn (bắt buộc).';
      gender.required = true;
      people.required = true;
      note.placeholder = 'Thời gian dọn, yêu cầu ở ghép, nội thất cần, ưu tiên gần trường...';
      tabRoommate.className = 'btn btn-primary btn-sm';
      tabRoom.className = 'btn btn-outline btn-sm';
      kindRoommateEls.forEach(el => el.style.display = '');
      kindRoomEls.forEach(el => el.style.display = 'none');
    } else {
      title.textContent = 'Đăng nhu cầu tìm phòng';
      desc.textContent = 'Mô tả phòng bạn muốn thuê, ngân sách và khu vực ưu tiên.';
      img1.required = false;
      img1Note.textContent = 'Ảnh mẫu (tùy chọn).';
      gender.value = 'any';
      gender.required = false;
      people.value = 1;
      people.required = false;
      note.placeholder = 'Ngân sách, khu vực, yêu cầu nội thất, thời gian chuyển đến...';
      tabRoommate.className = 'btn btn-outline btn-sm';
      tabRoom.className = 'btn btn-primary btn-sm';
      kindRoommateEls.forEach(el => el.style.display = 'none');
      kindRoomEls.forEach(el => el.style.display = '');
    }
  };

  tabRoommate?.addEventListener('click', () => setKind('roommate'));
  tabRoom?.addEventListener('click', () => setKind('room'));
  // init
  setKind('roommate');
})();
</script>

<div class="card seek-posts-card seek-posts-list" style="margin-top:16px;">
  <div class="card-body">
    <h2 class="mb-2" style="font-size:18px;">Nhu cầu mới nhất</h2>
    <div class="table-responsive">
      <table class="table align-middle seek-posts-table">
        <thead>
          <tr>
            <th>Khu vực</th><th>Ngân sách</th><th>Người</th><th>Giới tính</th><th>Ảnh phòng</th><th>Người đăng</th><th>Thời gian</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($posts)): ?>
            <tr><td colspan="7">Chưa có bài viết.</td></tr>
          <?php endif; ?>
          <?php foreach ($posts as $p): ?>
            <tr>
              <td style="font-weight:700;"><?= htmlspecialchars($p['area']) ?></td>
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
              <td><?= htmlspecialchars($p['user_name'] ?? 'Ẩn danh') ?></td>
              <td><?= htmlspecialchars($p['created_at']) ?></td>
            </tr>
            <tr>
              <td colspan="7" class="text-muted" style="white-space:pre-wrap;"><?= htmlspecialchars($p['note']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="seek-posts-mobile">
      <?php if (empty($posts)): ?>
        <div class="seek-posts-empty">Chưa có bài viết.</div>
      <?php endif; ?>
      <?php foreach ($posts as $p): ?>
        <?php
          $imgs = array_filter([
            $p['room_image'] ?? '',
            $p['room_image2'] ?? '',
            $p['room_image3'] ?? '',
          ]);
          if ($p['price_min'] || $p['price_max']) {
              $budgetText = ($p['price_min'] ? number_format((int)$p['price_min'], 0, ',', '.') . 'đ' : '—') . ' - ' .
                  ($p['price_max'] ? number_format((int)$p['price_max'], 0, ',', '.') . 'đ' : '—');
          } else {
              $budgetText = '—';
          }
          $genderText = ['male' => 'Nam', 'female' => 'Nữ', 'any' => 'Không yêu cầu'][$p['gender'] ?? 'any'] ?? 'Không yêu cầu';
          $createdAtRaw = (string)($p['created_at'] ?? '');
          $createdAtTs = $createdAtRaw !== '' ? strtotime($createdAtRaw) : false;
          $createdAtCompact = $createdAtTs ? date('d/m H:i', $createdAtTs) : $createdAtRaw;
        ?>
        <article class="seek-post-card">
          <div class="seek-post-card-head">
            <div>
              <div class="seek-post-card-area"><?= htmlspecialchars($p['area']) ?></div>
              <div class="seek-post-card-user">Đăng bởi <?= htmlspecialchars($p['user_name'] ?? 'Ẩn danh') ?></div>
            </div>
            <div class="seek-post-card-time"><?= htmlspecialchars($createdAtCompact) ?></div>
          </div>
          <div class="seek-post-card-meta">
            <div class="seek-post-meta-item">
              <span class="seek-post-meta-label">Ngân sách</span>
              <span class="seek-post-meta-value"><?= htmlspecialchars($budgetText) ?></span>
            </div>
            <div class="seek-post-meta-item">
              <span class="seek-post-meta-label">Người</span>
              <span class="seek-post-meta-value"><?= $p['people_count'] ? (int)$p['people_count'] . ' người' : '—' ?></span>
            </div>
            <div class="seek-post-meta-item">
              <span class="seek-post-meta-label">Giới tính</span>
              <span class="seek-post-meta-value"><?= htmlspecialchars($genderText) ?></span>
            </div>
            <div class="seek-post-meta-item">
              <span class="seek-post-meta-label">Ảnh phòng</span>
              <span class="seek-post-meta-value"><?= !empty($imgs) ? count($imgs) . ' ảnh' : 'Không có' ?></span>
            </div>
          </div>
          <?php if (!empty($imgs)): ?>
            <div class="seek-post-card-images">
              <?php foreach ($imgs as $img): ?>
                <a href="<?= htmlspecialchars(assetUrl($img)) ?>" target="_blank">
                  <img src="<?= htmlspecialchars(assetUrl($img)) ?>" alt="Ảnh phòng">
                </a>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
          <div class="seek-post-card-note"><?= htmlspecialchars($p['note']) ?></div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</div>
