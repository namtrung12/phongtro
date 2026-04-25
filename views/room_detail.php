<?php
$pageTitle = $room['title'];
$user = currentUser();
$prefillPhone = $user['phone'] ?? ($_SESSION['user']['phone'] ?? ($_SESSION['phone'] ?? ''));
$prefillName  = $user['name'] ?? ($_SESSION['user']['name'] ?? '');
$isLoggedTenant = !empty($user) && (($user['role'] ?? '') === 'tenant');
$isLoggedIn = !empty($user);
$leadPrice = effectiveLeadPriceFromRow($room);
$slotsLeft = $room['slots_left'] ?? null;
$slotsTotal = $room['slots_total'] ?? null;
$loginRedirectUrl = routeUrl('login', ['redirect' => '?route=room&id=' . (int)$room['id']]);
$fallbacks = [
    'https://images.unsplash.com/photo-1505691938895-1758d7feb511?auto=format&fit=crop&w=1200&q=80',
    'https://images.unsplash.com/photo-1505691938895-1758d7feb511?auto=format&fit=crop&w=1200&q=80&sat=-20',
    'https://images.unsplash.com/photo-1484154218962-a197022b5858?auto=format&fit=crop&w=1200&q=80',
];
    $images = [
        $room['thumbnail'] ?? null,
        $room['image1'] ?? null, $room['image2'] ?? null, $room['image3'] ?? null, $room['image4'] ?? null,
        $room['image5'] ?? null, $room['image6'] ?? null, $room['image7'] ?? null, $room['image8'] ?? null
    ];
    $slides = array_values(array_filter(array_unique(array_merge($images, $fallbacks))));
    $thumbs = array_slice($slides, 0, 8);
?>
<style>
  .room-detail-shell { display:grid; grid-template-columns: minmax(0,1.05fr) minmax(320px,0.95fr); gap:22px; align-items:start; max-width:1200px; margin:0 auto 32px; }
  .room-detail-shell .card { border: none; box-shadow: 0 10px 30px rgba(17,24,39,0.08); border-radius:16px; }
  .room-hero { border-radius:16px 16px 0 0; overflow:hidden; }
  .room-hero img { width:100%; height:380px; object-fit:cover; display:block; }
  .slider { position:relative; }
  .slides { display:flex; transition:transform .4s ease; }
  .slide { min-width:100%; }
  .slider-dots { position:absolute; bottom:12px; left:50%; transform:translateX(-50%); display:flex; gap:8px; }
  .slider-dots button { width:8px; height:8px; border-radius:999px; border:none; background:rgba(255,255,255,0.65); padding:0; transition:all .2s ease; }
  .slider-dots button.active { width:18px; background:var(--primary, #2563eb); }
  .chip-row { display:flex; flex-wrap:wrap; gap:8px; margin:12px 0; }
  .chip { background:#f1f5f9; border-radius:999px; padding:6px 10px; font-size:12px; color:#0f172a; border:1px solid #e2e8f0; }
  .room-meta { display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:10px; }
  .meta-item { padding:10px 12px; background:#f8fafc; border-radius:12px; font-size:13px; }
  .lead-card { position:sticky; top:88px; }
  .lead-pane {
    background: linear-gradient(145deg, #fffaf5 0%, #fff5e8 50%, #ffffff 100%);
    border: 1px solid #ffe5c7;
    border-radius: 16px;
    padding: 16px;
    box-shadow: 0 16px 32px rgba(217,119,6,0.08);
    position: relative;
    overflow: hidden;
  }
  .lead-pane::after {
    content: "";
    position: absolute;
    inset: -20%;
    background: radial-gradient(circle at 20% 20%, rgba(245,158,11,0.12), transparent 42%),
                radial-gradient(circle at 80% 10%, rgba(239,68,68,0.12), transparent 35%);
    z-index: 0;
  }
  .lead-pane > * { position: relative; z-index: 1; }
  .lead-chip {
    display:inline-flex;
    align-items:center;
    gap:8px;
    padding:8px 12px;
    border-radius:12px;
    background:#fff4e6;
    color:#b45309;
    font-weight:700;
    border:1px solid #fcd9b6;
  }
  .cta-wrap { position:sticky; bottom:12px; background:transparent; padding-top:6px; }
  .trust-list { list-style:none; padding-left:0; margin:12px 0 0; font-size:13px; color:#475569; }
  .trust-list li { display:flex; gap:6px; align-items:flex-start; }
  .badge-fomo { display:inline-flex; align-items:center; gap:6px; padding:8px 10px; background:#fff4f0; color:#b42318; border:1px solid #ffe0d9; border-radius:10px; font-size:12px; font-weight:600; }
  .badge-fomo + .badge-fomo { margin-left:8px; }
  .subtle { color:#64748b; font-size:13px; }
  .toggle-more { color:var(--primary,#2563eb); font-size:13px; cursor:pointer; }
  .social-proof { font-size:12px; color:#0f172a; background:#e0f2fe; padding:8px 10px; border-radius:10px; display:inline-flex; gap:6px; align-items:center; }
  @media (max-width: 992px){ .room-detail-shell { grid-template-columns:1fr; } .lead-card { position:static; } .room-hero img{height:280px;} }
  @media (max-width: 768px){
    .room-detail-shell { display:flex; flex-direction:column; gap:12px; }
    .room-hero img { height:240px; border-radius:12px 12px 0 0; }
    .lead-pane { padding:13px; border-radius:12px; }
    .lead-card { position: static; top: auto; }
    .room-meta { grid-template-columns: repeat(2, minmax(0,1fr)); }
    .meta-item { font-size:12px; padding:9px 10px; }
    .thumb-strip-wrap { padding:6px 28px; }
    .thumb-strip img { width:72px; height:50px; }
    .cta-wrap { position: sticky; bottom: 8px; }
    h1 { font-size: 20px; }
    .lead-pane .lead-chip { padding:7px 10px; font-size:12px; }
    .badge-fomo { font-size:11px; padding:7px 9px; }
  }
  .thumb-strip-wrap { position:relative; padding:10px 38px; }
  .thumb-strip { display:flex; gap:8px; padding:6px 0; overflow-x:auto; scroll-behavior:smooth; }
  .thumb-strip::-webkit-scrollbar { height:8px; }
  .thumb-strip::-webkit-scrollbar-thumb { background:#cbd5e1; border-radius:999px; }
  .thumb-strip img { width:90px; height:60px; object-fit:cover; border-radius:10px; cursor:pointer; border:2px solid transparent; flex:0 0 auto; }
  .thumb-strip img.active { border-color: var(--primary, #2563eb); }
  .thumb-nav {
    position:absolute;
    top:50%;
    transform:translateY(-50%);
    width:28px;
    height:44px;
    border:none;
    border-radius:10px;
    background:rgba(15,23,42,0.68);
    color:#fff;
    cursor:pointer;
    display:flex;
    align-items:center;
    justify-content:center;
    box-shadow:0 8px 18px rgba(0,0,0,0.25);
    transition:background .15s ease;
  }
  .thumb-prev { left:8px; }
  .thumb-next { right:8px; }
  .thumb-nav:hover { background:rgba(37,99,235,0.9); }
  .video-wrap { margin:14px 0; border-radius:14px; overflow:hidden; background:#000; }
  .video-wrap iframe, .video-wrap video { width:100%; height:360px; display:block; }
  @media(max-width:768px){ .video-wrap iframe, .video-wrap video { height:220px; } }
</style>

<div class="room-detail-shell">
  <div class="card room-card">
    <div class="room-hero slider" data-slider>
      <div class="slides">
        <?php foreach ($slides as $src): ?>
          <div class="slide">
            <img src="<?= htmlspecialchars(assetUrl($src)) ?>" alt="<?= htmlspecialchars($room['title']) ?>">
          </div>
        <?php endforeach; ?>
      </div>
      <div class="slider-dots">
        <?php foreach ($slides as $index => $src): ?>
          <button type="button" data-slide="<?= $index ?>" class="<?= $index === 0 ? 'active' : '' ?>"></button>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="thumb-strip-wrap">
      <button type="button" class="thumb-nav thumb-prev" aria-label="Ảnh trước">‹</button>
      <div class="thumb-strip" id="thumbStrip">
        <?php foreach ($thumbs as $idx => $src): ?>
          <img src="<?= htmlspecialchars(assetUrl($src)) ?>" data-slide="<?= $idx ?>" alt="thumb" class="<?= $idx === 0 ? 'active' : '' ?>">
        <?php endforeach; ?>
      </div>
      <button type="button" class="thumb-nav thumb-next" aria-label="Ảnh kế">›</button>
    </div>
    <div class="card-body">
      <h1 class="mb-1" style="font-size:21px;"><?= htmlspecialchars($room['title']) ?></h1>
      <div class="mb-2" style="font-weight:700; color: var(--primary); font-size:17px;"><?= number_format((int)$room['price'], 0, ',', '.') ?> đ / tháng</div>
      <p class="text-muted mb-2">Khu vực: <?= htmlspecialchars($room['area']) ?></p>
      <p class="text-muted mb-2">Địa chỉ hiển thị: <?= htmlspecialchars(maskAddress($room['address'])) ?> (chủ trọ phải mở thông tin để xem đầy đủ)</p>
      <div class="chip-row">
        <span class="chip"><?= !empty($room['shared_owner']) ? 'Chung chủ' : 'Không chung chủ' ?></span>
        <span class="chip"><?= !empty($room['closed_room']) ? 'Phòng khép kín' : 'Không khép kín' ?></span>
        <span class="chip">Điện: <?= $room['electric_price'] ? number_format((int)$room['electric_price'], 0, ',', '.') . ' đ/kWh' : 'Liên hệ' ?></span>
        <span class="chip">Nước: <?= $room['water_price'] ? number_format((int)$room['water_price'], 0, ',', '.') . ' đ/m³' : 'Liên hệ' ?></span>
      </div>
      <div class="room-meta mb-3">
        <div class="meta-item">📍 Khu vực: <?= htmlspecialchars($room['area']) ?></div>
        <div class="meta-item">⚡ Điện: <?= $room['electric_price'] ? number_format((int)$room['electric_price'], 0, ',', '.') . ' đ/kWh' : 'Liên hệ' ?></div>
        <div class="meta-item">💧 Nước: <?= $room['water_price'] ? number_format((int)$room['water_price'], 0, ',', '.') . ' đ/m³' : 'Liên hệ' ?></div>
        <div class="meta-item">✅ Đã kiểm duyệt · Chủ trọ đã xác minh</div>
      </div>
      <p class="mb-2">Địa chỉ hiển thị: Gần <?= htmlspecialchars(maskAddress($room['address'])) ?> (ẩn số nhà, mở sau khi chủ trọ trả phí)</p>
      <p class="mb-0"><?= nl2br(htmlspecialchars($room['description'] ?? '')) ?></p>
      <div class="chip-row" style="margin-top:12px;">
        <span class="chip">📍 <?= htmlspecialchars($room['area']) ?></span>
        <span class="chip"><?= !empty($room['shared_owner']) ? '👥 Chung chủ' : '👤 Không chung chủ' ?></span>
        <span class="chip"><?= !empty($room['closed_room']) ? '🚿 Khép kín' : '🛏 Phòng thường' ?></span>
      </div>
      <div class="social-proof mt-2">💬 Hơn 1.200 người thuê đã tìm được phòng qua PhongTro</div>

      <?php if (!empty($room['video_url'])): ?>
        <div class="video-wrap mt-3">
          <?php if (preg_match('~(youtube\\.com/watch\\?v=|youtu\\.be/)~i', $room['video_url'])): ?>
            <?php
              $ytId = '';
              if (preg_match('~(?:v=|/)([A-Za-z0-9_-]{6,})~', $room['video_url'], $m)) { $ytId = $m[1]; }
            ?>
            <iframe src="https://www.youtube.com/embed/<?= htmlspecialchars($ytId) ?>" frameborder="0" allowfullscreen></iframe>
          <?php else: ?>
            <video controls preload="metadata" poster="<?= htmlspecialchars(assetUrl($room['thumbnail'] ?? $slides[0] ?? '')) ?>">
              <source src="<?= htmlspecialchars(assetUrl($room['video_url'])) ?>" type="video/mp4">
              Trình duyệt không hỗ trợ video.
            </video>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <div class="card mt-3" style="border:1px dashed #e2e8f0;">
        <div class="card-body">
          <div class="d-flex justify-between align-items-center mb-1">
            <strong>Thông tin chủ trọ</strong>
            <?php if ($slotsLeft !== null): ?>
              <span class="badge-fomo">Còn <?= (int)$slotsLeft ?>/<?= (int)$slotsTotal ?> lượt mở SĐT hôm nay</span>
            <?php endif; ?>
          </div>
          <p class="mb-1 small text-muted">Tên: <?= htmlspecialchars(maskName($room['landlord_name'] ?? 'Chủ trọ')) ?></p>
          <p class="mb-1 small text-muted">SĐT (ẩn): <?= htmlspecialchars(maskPhone($room['landlord_phone'] ?? '')) ?></p>
          <?php if ($leadPrice): ?><p class="mb-0 small text-muted">Giá mở liên hệ: ~<?= number_format((int)$leadPrice, 0, ',', '.') ?> đ</p><?php endif; ?>
        </div>
      </div>

    </div>
  </div>

  <div class="card lead-card">
    <div class="lead-pane">
      <div class="d-flex align-items-center gap-2 mb-2">
        <span class="lead-chip">🔥 <?= max(0, (int)($room['leads_recent'] ?? 0)) ?> người đang quan tâm</span>
      </div>
      <h2 class="mb-1" style="font-size:19px;"><?= !empty($prefillPhone) ? 'Sẵn sàng gửi SĐT' : 'Nhận liên hệ từ chủ trọ ngay' ?></h2>
      <p class="small text-muted mb-3">Chủ trọ sẽ chủ động gọi bạn sau khi mở SĐT.</p>
      <form method="post" action="?route=lead" id="leadForm">
        <input type="hidden" name="room_id" value="<?= (int)$room['id'] ?>">
        <?php $hasUserPhone = $isLoggedIn && !empty($prefillPhone); ?>
        <div class="mb-3">
          <label class="form-label">Số điện thoại</label>
          <?php if ($hasUserPhone): ?>
            <div class="d-flex align-items-center gap-2" style="padding:10px 12px; border:1px dashed #e2e8f0; border-radius:10px; background:#f8fafc; font-weight:700;">
              📱 <?= htmlspecialchars($prefillPhone) ?>
              <span class="badge" style="background:#e9f7ef; color:#166534; border:1px solid #c2e7c7;">Dùng SĐT tài khoản</span>
            </div>
          <?php endif; ?>
          <input type="hidden" name="phone" value="<?= htmlspecialchars($prefillPhone) ?>">
        </div>
        <div class="cta-wrap">
          <div class="d-flex flex-column mb-2" style="gap:4px;">
            <span class="subtle">✔ Chủ trọ sẽ chủ động liên hệ · ✔ Ưu tiên người đăng ký sớm</span>
            <span class="subtle">✔ Không thu phí người thuê · ✔ Đã kiểm duyệt · Hỗ trợ miễn phí</span>
          </div>
          <?php if ($slotsLeft !== null && (int)$slotsLeft <= 0): ?>
            <button class="btn btn-danger w-100" type="button" disabled>Chủ trọ tạm hết lượt hôm nay</button>
          <?php elseif (!$isLoggedIn): ?>
            <a class="btn btn-danger w-100 text-center" href="<?= htmlspecialchars($loginRedirectUrl) ?>">🔥 Nhận liên hệ ngay</a>
          <?php else: ?>
            <button class="btn btn-danger w-100" type="submit" id="ctaSubmit">
              <?= '🔥 ' . ($isLoggedTenant ? 'Quan tâm ngay' : 'Nhận liên hệ ngay') ?>
            </button>
          <?php endif; ?>
        </div>
      </form>
      <ul class="trust-list">
        <li>✔ Thông tin được ẩn cho đến khi chủ trọ trả phí.</li>
        <li>✔ Chỉ 1 lượt quan tâm/24h cho cùng phòng + số điện thoại.</li>
        <li>✔ Bạn đang tìm phòng khu này? Đăng nhu cầu → chủ trọ liên hệ.</li>
      </ul>
    </div>
  </div>
</div>

<script>
(function() {
    const slider = document.querySelector('[data-slider]');
    if (!slider) return;
    const slides = slider.querySelector('.slides');
    const dots = slider.querySelectorAll('.slider-dots button');
    const thumbStrip = document.getElementById('thumbStrip');
    const thumbImgs = thumbStrip ? Array.from(thumbStrip.querySelectorAll('img')) : [];
    let index = 0;
    const total = dots.length;
    const go = (i) => {
        index = (i + total) % total;
        slides.style.transform = `translateX(-${index * 100}%)`;
        dots.forEach((d, idx) => d.classList.toggle('active', idx === index));
        thumbImgs.forEach((img, idx) => {
          img.classList.toggle('active', idx === index);
          if (idx === index) {
            img.scrollIntoView({behavior:'smooth', block:'nearest', inline:'center'});
          }
        });
    };
    dots.forEach((btn, idx) => btn.addEventListener('click', () => go(idx)));
    thumbImgs.forEach((img) => {
      img.addEventListener('click', () => {
        const i = parseInt(img.dataset.slide || '0', 10);
        go(i);
      });
    });
    setInterval(() => go(index + 1), 5000);

    // thumb strip slider
    const btnPrev = document.querySelector('.thumb-prev');
    const btnNext = document.querySelector('.thumb-next');
    const scrollThumbs = (dir) => {
      if (!thumbStrip) return;
      const step = 90 * 3 + 16; // 3 thumbnails width + gaps
      thumbStrip.scrollBy({ left: dir * step, behavior: 'smooth' });
    };
    btnPrev?.addEventListener('click', () => scrollThumbs(-1));
    btnNext?.addEventListener('click', () => scrollThumbs(1));
    let thumbTimer;
    const startThumbAuto = () => { if (!thumbStrip) return; thumbTimer = setInterval(() => scrollThumbs(1), 4000); };
    const stopThumbAuto = () => { if (thumbTimer) clearInterval(thumbTimer); };
    thumbStrip?.addEventListener('mouseenter', stopThumbAuto);
    thumbStrip?.addEventListener('mouseleave', startThumbAuto);
    if (thumbStrip) startThumbAuto();

})();
</script>

<?php if (!empty($similarRooms ?? [])): ?>
<div class="card mt-4">
  <div class="card-body">
        <div class="d-flex justify-between align-items-center mb-2">
          <h3 class="mb-0" style="font-size:18px;">Phòng tương tự</h3>
          <a href="?route=rooms&area=<?= urlencode($room['area']) ?>" class="btn btn-outline btn-sm">Xem thêm</a>
        </div>
    <div class="row g-3">
      <?php foreach ($similarRooms as $s): ?>
        <?php
          $img = $s['thumbnail'] ?? '';
          if (!$img) {
            foreach (['image1','image2','image3','image4'] as $k) { if (!empty($s[$k])) { $img = $s[$k]; break; } }
          }
          if (!$img) { $img = $slides[0] ?? $fallbacks[0]; }
        ?>
        <div class="col-md-4">
          <a class="card card-room room-card h-100 card-link" href="?route=room&id=<?= (int)$s['id'] ?>">
            <img src="<?= htmlspecialchars(assetUrl($img)) ?>" class="card-img-top room-thumb" alt="<?= htmlspecialchars($s['title']) ?>">
            <div class="card-body d-flex flex-column">
              <h3 class="mb-1" style="font-size:16px;"><?= htmlspecialchars($s['title']) ?></h3>
              <div class="room-price mb-1"><?= number_format((int)$s['price'], 0, ',', '.') ?> đ / tháng</div>
              <p class="room-address small mb-2"><?= htmlspecialchars(maskAddress($s['address'] ?? '')) ?></p>
              <span class="btn btn-success btn-sm w-100 text-center mt-auto">Xem phòng</span>
            </div>
          </a>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<?php endif; ?>
