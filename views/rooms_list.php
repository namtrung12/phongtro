<?php
$pageTitle = 'Danh sách phòng';
$page = $pagination['page'] ?? 1;
$pages = $pagination['pages'] ?? 1;
$totalRooms = $pagination['total'] ?? count($rooms);
$buildMobileLink = static function(array $params = []): string {
    return '?' . http_build_query(array_merge(['route' => 'search'], $params));
};
$mobileQuickFilters = [
    ['label' => 'Gần trường', 'params' => ['near_school' => 1]],
    ['label' => '< 2.5 triệu', 'params' => ['max_price' => 2500000]],
    ['label' => 'Khép kín', 'params' => ['closed_room' => 1]],
    ['label' => 'Không chung', 'params' => ['shared_owner' => '0']],
    ['label' => 'Điều hòa', 'params' => ['keyword' => 'điều hòa']],
];
$mobileAreas = ['Thanh Hóa', 'Hà Nội', 'Đà Nẵng', 'TP.HCM', 'Vinh', 'Quy Nhơn'];
?>
<style>
  .page-stack { display:flex; flex-direction:column; gap:26px; margin-top:0; }
  .hero.page-hero {
      margin-bottom:0;
      padding:30px 28px;
      display:flex;
      flex-wrap:wrap;
      gap:18px;
      align-items:flex-start;
  }
  .hero-lead { max-width: 620px; }
  .hero-lead h1 { margin:0 0 10px 0; font-size:26px; letter-spacing:-0.2px; }
  .hero-sub { opacity:0.92; line-height:1.5; margin-bottom:10px; }
  .hero-pills { display:flex; gap:10px; flex-wrap:wrap; }
  .hero-pill {
      background: rgba(255,255,255,0.22);
      color:#fff;
      padding:8px 12px;
      border-radius: 999px;
      font-weight:600;
      border: 1px solid rgba(255,255,255,0.25);
  }
  .hero-metrics { display:grid; grid-template-columns:repeat(auto-fit, minmax(150px,1fr)); gap:10px; min-width:200px; }
  .metric-box {
      background: rgba(255,255,255,0.12);
      border: 1px solid rgba(255,255,255,0.18);
      border-radius:12px;
      padding:12px 14px;
      color:#fff;
      box-shadow: inset 0 0 0 1px rgba(255,255,255,0.08);
  }
  .metric-box strong { display:block; font-size:20px; margin-bottom:4px; }
  .slider-shell { border-radius: 18px; overflow:hidden; box-shadow: var(--shadow); }
  .section-header { display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:12px; }
  .section-title { margin:0; font-size:18px; font-weight:700; letter-spacing:-0.2px; }
  .section-note { color: var(--muted); font-size:13px; }
  .rooms-grid { gap:14px; }
  .card-room.room-card {
      border:1px solid #f1f5f9;
      box-shadow: 0 14px 38px rgba(15,23,42,0.10);
      transition: transform .18s ease, box-shadow .18s ease;
      background: linear-gradient(180deg, #fff, #fdfaf2);
  }
  .card-room.room-card:hover { transform: translateY(-4px); box-shadow: 0 18px 40px rgba(15,23,42,0.14); }
  .room-thumb { height:170px; object-fit:cover; border-bottom:1px solid #f1f5f9; border-radius: 12px 12px 0 0; }
  .room-price { font-weight:800; color: var(--primary-dark); margin-bottom:6px; font-size:15px; }
  .room-meta { display:flex; flex-wrap:wrap; gap:6px; margin-bottom:6px; }
  .chip-lite {
      background:#fff8e1;
      color:#7c2d12;
      padding:5px 9px;
      border-radius:999px;
      font-size:11px;
      border:1px solid #f6d470;
      font-weight:700;
  }
  .room-address { color: var(--muted); font-size:12px; margin-bottom:6px; }
  .room-desc { color: #4b5563; font-size:12px; line-height:1.45; }
  .cta { margin-top: auto; padding:11px 12px; font-size:13px; border-radius:12px; }
  .mobile-home { display:none; }
  .mobile-hero-card {
      background: linear-gradient(135deg, #fbbf24, #d97706);
      color: #7c2d12;
      border-radius: 14px;
      padding: 14px 14px 16px;
      box-shadow: 0 12px 28px rgba(217,119,6,0.22);
      border: 1px solid rgba(251,191,36,0.55);
      position: relative;
      overflow: hidden;
  }
  .mobile-hero-card::after {
      content:"";
      position:absolute;
      right:-40px; top:-30px;
      width:140px; height:140px;
      background: radial-gradient(circle at 30% 40%, rgba(255,255,255,0.3), transparent 55%);
      transform: rotate(18deg);
  }
  .mobile-hero-badge {
      display:inline-flex;
      align-items:center;
      gap:6px;
      padding:6px 8px;
      background: rgba(255,255,255,0.28);
      color:#7c2d12;
      border-radius:999px;
      font-weight:800;
      font-size:11px;
      border: 1px solid rgba(255,255,255,0.36);
  }
  .mobile-hero-title { font-size:18px; font-weight:800; margin:8px 0 4px; letter-spacing:-0.2px; }
  .mobile-hero-sub { font-size:13px; color:#7c2d12; opacity:0.9; margin:0 0 8px; line-height:1.5; }
  .mobile-chip-scroll {
      display:flex;
      flex-wrap:nowrap;
      gap:10px;
      overflow-x:auto;
      -webkit-overflow-scrolling:touch;
      scroll-snap-type:x proximity;
      scroll-behavior:smooth;
      padding:8px 10px;
      margin:2px -4px 0;
      border-radius:16px;
      background:rgba(124,45,18,0.08);
      border:1px solid rgba(255,255,255,0.28);
      box-shadow:inset 0 1px 0 rgba(255,255,255,0.18), 0 10px 22px rgba(124,45,18,0.08);
      scrollbar-width:none;
  }
  .mobile-chip-scroll::-webkit-scrollbar { display:none; }
  .mobile-chip-scroll::-webkit-scrollbar-thumb { background: rgba(0,0,0,0.08); border-radius:999px; }
  .mobile-chip-scroll[data-marquee-ready="1"],
  .mobile-area-scroll[data-marquee-ready="1"] {
      scroll-snap-type: none;
  }
  .mobile-chip {
      flex:0 0 auto;
      scroll-snap-align:start;
      padding:9px 13px;
      border-radius:999px;
      background:rgba(255,250,240,0.88);
      color:#7c2d12;
      font-weight:800;
      border:1px solid rgba(255,255,255,0.76);
      text-decoration:none;
      box-shadow:0 8px 18px rgba(124,45,18,0.12);
      opacity:0.72;
      transform:scale(0.96);
      transition:transform .36s ease, opacity .36s ease, box-shadow .36s ease, background .36s ease;
  }
  .mobile-chip.is-active {
      background:#fffdf7;
      border-color:#fff;
      opacity:1;
      transform:scale(1);
      box-shadow:0 12px 26px rgba(124,45,18,0.22);
  }
  .mobile-cta-grid { display:grid; grid-template-columns: 1fr; gap:8px; align-items:stretch; }
  .mobile-cta {
      display:flex;
      align-items:center;
      justify-content:center;
      gap:6px;
      min-height:52px;
      padding:11px 10px;
      border-radius:12px;
      background:linear-gradient(135deg, #fbbf24, #d97706);
      color:#fff;
      font-weight:800;
      text-decoration:none;
      box-shadow: 0 10px 22px rgba(217,119,6,0.20);
      border: 1px solid #d97706;
  }
  .mobile-cta.secondary {
      background:#fffaf0;
      color:#7c2d12;
      border:1px dashed #f59e0b;
      box-shadow: 0 8px 18px rgba(217,119,6,0.08);
  }
  .mobile-area-scroll {
      display:flex;
      flex-wrap:nowrap;
      gap:10px;
      overflow-x:auto;
      -webkit-overflow-scrolling:touch;
      scroll-snap-type:x proximity;
      scroll-behavior:smooth;
      padding:8px 10px;
      margin:0 -4px;
      border-radius:16px;
      background:rgba(255,255,255,0.62);
      border:1px solid rgba(217,119,6,0.12);
      box-shadow:inset 0 1px 0 rgba(255,255,255,0.78), 0 10px 22px rgba(15,23,42,0.06);
      scrollbar-width:none;
  }
  .mobile-area-scroll::-webkit-scrollbar { display:none; }
  .mobile-area {
      flex:0 0 auto;
      scroll-snap-align:start;
      min-width:max-content;
      min-height:44px;
      display:inline-flex;
      align-items:center;
      padding:9px 10px;
      border-radius:999px;
      background:#fff;
      border:1px solid #e2e8f0;
      color:#0f172a;
      font-weight:700;
      text-decoration:none;
      box-shadow:0 8px 18px rgba(15,23,42,0.08);
      opacity:0.74;
      transform:scale(0.96);
      transition:transform .36s ease, opacity .36s ease, box-shadow .36s ease, border-color .36s ease;
  }
  .mobile-area.is-active {
      border-color:#f59e0b;
      color:#7c2d12;
      opacity:1;
      transform:scale(1);
      box-shadow:0 12px 24px rgba(217,119,6,0.16);
  }
  .tenant-posts-mobile { display:none; }
  .tenant-post-card {
      border:1px solid #f3e2c7;
      border-radius:14px;
      background:linear-gradient(180deg, #fffefb, #fff8ef);
      padding:12px;
      box-shadow:0 10px 24px rgba(217,119,6,0.08);
  }
  .tenant-post-card + .tenant-post-card { margin-top:10px; }
  .tenant-post-head {
      display:flex;
      align-items:flex-start;
      justify-content:space-between;
      gap:12px;
  }
  .tenant-post-area {
      font-size:15px;
      font-weight:800;
      line-height:1.35;
      color:#111827;
  }
  .tenant-post-user {
      margin-top:4px;
      font-size:12px;
      color:#6b7280;
  }
  .tenant-post-time {
      flex:0 0 auto;
      font-size:12px;
      font-weight:700;
      color:#92400e;
      white-space:nowrap;
  }
  .tenant-post-meta {
      display:grid;
      grid-template-columns:repeat(2, minmax(0,1fr));
      gap:8px;
      margin-top:10px;
  }
  .tenant-post-meta-item {
      min-width:0;
      padding:8px 10px;
      border-radius:12px;
      border:1px solid #f4e4c8;
      background:rgba(255,255,255,0.94);
  }
  .tenant-post-meta-label {
      display:block;
      margin-bottom:3px;
      font-size:10px;
      font-weight:700;
      letter-spacing:0.04em;
      text-transform:uppercase;
      color:#9a3412;
  }
  .tenant-post-meta-value {
      display:block;
      font-size:13px;
      font-weight:700;
      line-height:1.35;
      color:#1f2937;
      overflow-wrap:anywhere;
  }
  .tenant-post-contact {
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:10px;
      margin-top:10px;
      padding:10px 11px;
      border-radius:12px;
      background:#fff;
      border:1px solid #f4e4c8;
  }
  .tenant-post-phone {
      min-width:0;
      font-size:14px;
      font-weight:800;
      color:#111827;
      overflow-wrap:anywhere;
  }
  .tenant-post-contact .btn {
      flex:0 0 auto;
      white-space:nowrap;
  }
  .tenant-post-note {
      margin-top:10px;
      font-size:13px;
      line-height:1.55;
      color:#4b5563;
      white-space:pre-line;
      display:-webkit-box;
      -webkit-line-clamp:4;
      -webkit-box-orient:vertical;
      overflow:hidden;
  }
  @media (max-width: 768px) {
      .page-stack { gap:18px; margin-top:2px; }
      .hero.page-hero { padding:16px 14px; border-radius:14px; display:none; }
      .hero-lead h1 { font-size:20px; }
      .hero-sub { font-size:13px; margin-bottom:8px; }
      .hero-pills { gap:8px; }
      .hero-pill { font-size:12px; padding:7px 10px; }
      .hero-metrics { grid-template-columns: repeat(3, minmax(0, 1fr)); }
      .section-header { flex-direction: column; align-items: flex-start; gap: 8px; }
      .rooms-grid { display: grid !important; grid-template-columns: 1fr; gap: 12px; }
      .rooms-grid > [class*='col-'] { width: 100%; min-width: 0; }
      .room-desc { display: none; }
      .room-thumb { height: 188px; }
      .room-price { font-size: 14px; color: #b45309; }
      .cta { font-weight: 800; }
      .mobile-home {
          display:flex;
          flex-direction:column;
          gap:14px;
          margin:12px 0 14px;
      }
      .tenant-posts-section .table-responsive { display:none; }
      .tenant-posts-mobile { display:block; }
      .tenant-post-contact { padding:9px 10px; }
      .tenant-post-time { font-size:11px; }
  }
  @media (max-width: 360px) {
      .tenant-post-head {
          flex-direction:column;
      }
      .tenant-post-meta {
          grid-template-columns:minmax(0,1fr);
      }
      .tenant-post-contact {
          flex-direction:column;
          align-items:stretch;
      }
      .tenant-post-contact .btn {
          width:100%;
      }
  }
</style>
<div class="page-stack">
  <div class="mobile-home" aria-label="Giao diện nhanh trên di động">
    <div class="mobile-hero-card">
      <div class="mobile-hero-badge">Mobile · Gợi ý</div>
      <div class="mobile-hero-title">Tìm phòng trong 1 phút</div>
      <p class="mobile-hero-sub">Chọn nhanh tiêu chí, để lại số. Chủ trọ sẽ gọi bạn, bạn không phải gọi từng nơi.</p>
      <div class="mobile-chip-scroll" data-auto-marquee="filters" aria-label="Bộ lọc nhanh">
        <?php foreach ($mobileQuickFilters as $chip): ?>
          <a class="mobile-chip" href="<?= htmlspecialchars($buildMobileLink($chip['params'])) ?>"><?= htmlspecialchars($chip['label']) ?></a>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="mobile-cta-grid">
      <a class="mobile-cta" href="?route=search">🔍 Tìm phòng</a>
    </div>
    <div class="mobile-area-scroll" data-auto-marquee="areas" aria-label="Khu vực phổ biến">
      <?php foreach ($mobileAreas as $area): ?>
        <a class="mobile-area" href="<?= htmlspecialchars($buildMobileLink(['province' => $area])) ?>"><?= htmlspecialchars($area) ?></a>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="hero page-hero">
      <div class="hero-lead">
        <h1>Tìm phòng nhanh, để lại quan tâm</h1>
        <p class="hero-sub">Duyệt phòng, để lại SĐT; chủ trọ trả phí để mở thông tin của bạn. An toàn cho người tìm, minh bạch cho chủ trọ.</p>
        <div class="hero-pills">
            <span class="hero-pill">Không cần gọi điện nhiều</span>
            <span class="hero-pill">Phòng mới cập nhật hằng ngày</span>
            <span class="hero-pill">Ưu tiên phòng gần trường</span>
        </div>
      </div>
      <div class="hero-metrics">
        <div class="metric-box">
            <strong><?= number_format($totalRooms, 0, ',', '.') ?>+</strong>
            <span>Phòng đang mở</span>
        </div>
        <div class="metric-box">
            <strong>10 phút</strong>
            <span>Đăng ký, nhận gọi lại</span>
        </div>
        <div class="metric-box">
            <strong>24/7</strong>
            <span>Hỗ trợ & nhắc lịch</span>
        </div>
      </div>
  </div>

<?php
$recommended = array_slice($rooms, 0, 3);
$bannerRooms = array_slice($rooms, 0, 5);
$fallbacks = [
    'https://images.unsplash.com/photo-1505691938895-1758d7feb511?auto=format&fit=crop&w=1200&q=80',
    'https://images.unsplash.com/photo-1484154218962-a197022b5858?auto=format&fit=crop&w=1200&q=80',
    'https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?auto=format&fit=crop&w=1200&q=80',
];
$firstImage = function(array $room, array $fallbacks) {
    foreach (['thumbnail','image1','image2','image3','image4'] as $k) {
        if (!empty($room[$k])) return $room[$k];
    }
    return $fallbacks[array_rand($fallbacks)];
};

?>
  <div class="card mb-0 slider-shell">
      <div class="card-body" style="padding:0;">
          <div class="slider" data-slider-home>
              <div class="slides">
                  <?php if (!empty($bannerRooms)): ?>
                      <?php foreach ($bannerRooms as $room): $img = $firstImage($room, $fallbacks); ?>
                          <div class="slide">
                              <a href="?route=room&id=<?= (int)$room['id'] ?>" style="display:block;height:100%;">
                                  <img src="<?= htmlspecialchars(assetUrl($img)) ?>" alt="<?= htmlspecialchars($room['title']) ?>">
                                  <div style="position:absolute;left:16px;bottom:16px;background:rgba(0,0,0,0.55);color:#fff;padding:10px 12px;border-radius:10px;font-weight:700;max-width:80%;"><?= htmlspecialchars($room['title']) ?></div>
                              </a>
                          </div>
                      <?php endforeach; ?>
                  <?php else: ?>
                      <?php foreach ($fallbacks as $img): ?>
                          <div class="slide"><img src="<?= htmlspecialchars(assetUrl($img)) ?>" alt="Slide"></div>
                      <?php endforeach; ?>
                  <?php endif; ?>
              </div>
              <div class="slider-dots">
                  <?php $count = !empty($bannerRooms) ? count($bannerRooms) : count($fallbacks); ?>
                  <?php for ($i = 0; $i < $count; $i++): ?>
                      <button type="button" data-slide="<?= $i ?>" class="<?= $i === 0 ? 'active' : '' ?>"></button>
                  <?php endfor; ?>
              </div>
          </div>
      </div>
</div>

<?php if (!empty($recommended)): ?>
<div class="card mb-4">
  <div class="card-body">
        <div class="section-header">
          <div style="display:flex; align-items:center; gap:10px;">
            <div style="width:36px;height:36px;border-radius:12px;background:#fcd34d; display:flex;align-items:center;justify-content:center;color:#7c2d12;font-weight:800;">★</div>
            <h3 class="section-title" style="font-size:19px;">Gợi ý cho bạn</h3>
          </div>
          <span class="badge badge-warning" style="background:#f59e0b; color:#fff;">Ưu tiên phòng phù hợp</span>
        </div>
    <div class="row g-3 rooms-grid">
      <?php foreach ($recommended as $room): ?>
        <?php
          $img = $room['thumbnail'] ?? '';
          if (!$img) {
            foreach (['image1','image2','image3','image4','image5','image6','image7','image8'] as $k) {
              if (!empty($room[$k])) { $img = $room[$k]; break; }
            }
          }
          if (!$img) { $img = 'https://via.placeholder.com/400x200?text=Phong'; }
        ?>
        <div class="col-md-6 col-lg-4">
          <a class="card card-room room-card h-100 card-link" href="?route=room&id=<?= (int)$room['id'] ?>">
            <img src="<?= htmlspecialchars(assetUrl($img)) ?>" class="card-img-top room-thumb" alt="<?= htmlspecialchars($room['title']) ?>">
            <div class="card-body d-flex flex-column">
              <div class="d-flex justify-between align-items-center mb-1">
                <h3 class="mb-0" style="font-size:17px;"><?= htmlspecialchars($room['title']) ?></h3>
                <?php if (!empty($room['vip_tier']) && $room['vip_tier'] !== 'Thường'): ?>
                  <span class="badge badge-warning" style="font-size:11px;"><?= htmlspecialchars($room['vip_tier']) ?></span>
                <?php endif; ?>
              </div>
              <div class="room-meta">
                <span class="chip-lite"><?= htmlspecialchars($room['area']) ?></span>
                <?php if (($room['shared_owner'] ?? null) === '1' || ($room['shared_owner'] ?? 0) == 1): ?>
                  <span class="chip-lite">Chung chủ</span>
                <?php endif; ?>
                <?php if (($room['closed_room'] ?? null) === '1' || ($room['closed_room'] ?? 0) == 1): ?>
                  <span class="chip-lite">Khép kín</span>
                <?php endif; ?>
              </div>
              <p class="room-address"><?= htmlspecialchars(maskAddress($room['address'] ?? '')) ?></p>
              <div class="room-price"><?= number_format((int)$room['price'], 0, ',', '.') ?> đ / tháng</div>
              <p class="room-desc flex-grow-1 mb-3"><?= htmlspecialchars(mb_strimwidth($room['description'] ?? '', 0, 90, '...')) ?></p>
              <span class="btn btn-success w-100 text-center cta">Xem phòng ngay</span>
            </div>
          </a>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<?php endif; ?>


<script>
(() => {
  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (prefersReducedMotion) return;

  const setupAutoMarquee = (selector, intervalMs) => {
    document.querySelectorAll(selector).forEach((track) => {
      if (track.dataset.marqueeReady === '1') return;
      const originalItems = Array.from(track.children);
      if (originalItems.length < 2) return;

      originalItems.forEach((item) => {
        const clone = item.cloneNode(true);
        clone.setAttribute('aria-hidden', 'true');
        clone.tabIndex = -1;
        clone.querySelectorAll('a, button, input, select, textarea').forEach((el) => {
          el.tabIndex = -1;
          el.setAttribute('aria-hidden', 'true');
        });
        track.appendChild(clone);
      });

      track.dataset.marqueeReady = '1';

      let paused = false;
      let timerId = 0;
      let resumeTimer = 0;
      let activeIndex = 0;
      let resetTimer = 0;

      const shouldRun = () => window.innerWidth <= 768;
      const items = () => Array.from(track.children);
      const setActive = (index) => {
        items().forEach((item) => item.classList.remove('is-active'));
        const item = items()[index];
        if (item) item.classList.add('is-active');
      };
      const scrollToIndex = (index, behavior = 'smooth') => {
        const item = items()[index];
        if (!item) return;
        const centeredLeft = item.offsetLeft - ((track.clientWidth - item.offsetWidth) / 2);
        track.scrollTo({ left: Math.max(0, centeredLeft), behavior });
        setActive(index);
      };

      const pauseTemporarily = (delay = 1800) => {
        paused = true;
        window.clearTimeout(resumeTimer);
        resumeTimer = window.setTimeout(() => {
          paused = false;
        }, delay);
      };

      const advance = () => {
        if (!shouldRun() || paused) return;

        window.clearTimeout(resetTimer);
        activeIndex += 1;
        scrollToIndex(activeIndex);

        if (activeIndex >= originalItems.length) {
          resetTimer = window.setTimeout(() => {
            activeIndex = 0;
            scrollToIndex(activeIndex, 'auto');
          }, 720);
        }
      };

      track.addEventListener('pointerdown', () => pauseTemporarily(2200), { passive: true });
      track.addEventListener('touchstart', () => pauseTemporarily(2200), { passive: true });
      track.addEventListener('mouseenter', () => pauseTemporarily(2200));
      track.addEventListener('wheel', () => pauseTemporarily(1800), { passive: true });
      track.addEventListener('scroll', () => {
        if (!shouldRun()) return;
        const currentItems = items();
        const viewportCenter = track.scrollLeft + (track.clientWidth / 2);
        const nearest = currentItems.reduce((best, item, index) => {
          const itemCenter = item.offsetLeft + (item.offsetWidth / 2);
          const distance = Math.abs(itemCenter - viewportCenter);
          return distance < best.distance ? { index, distance } : best;
        }, { index: activeIndex, distance: Number.POSITIVE_INFINITY });
        activeIndex = nearest.index % originalItems.length;
        setActive(nearest.index);
      }, { passive: true });

      scrollToIndex(0, 'auto');
      timerId = window.setInterval(advance, intervalMs);

      document.addEventListener('visibilitychange', () => {
        if (document.hidden && timerId) {
          window.clearInterval(timerId);
          timerId = 0;
          return;
        }
        if (!document.hidden && !timerId) {
          timerId = window.setInterval(advance, intervalMs);
        }
      });
    });
  };

  setupAutoMarquee('[data-auto-marquee="filters"]', 3400);
  setupAutoMarquee('[data-auto-marquee="areas"]', 3800);
})();
</script>
<!-- end page stack -->
</div>

<div class="row g-3 rooms-grid">
    <?php if (empty($rooms)): ?>
        <p>Chưa có phòng nào.</p>
    <?php endif; ?>

    <?php foreach ($rooms as $room): ?>
        <?php
          $img = $room['thumbnail'] ?? '';
          if (!$img) {
            foreach (['image1','image2','image3','image4','image5','image6','image7','image8'] as $k) {
              if (!empty($room[$k])) { $img = $room[$k]; break; }
            }
          }
          if (!$img) { $img = 'https://via.placeholder.com/400x200?text=Phong'; }
        ?>
        <div class="col-md-6 col-lg-4">
            <a class="card card-room room-card h-100 card-link" href="?route=room&id=<?= (int)$room['id'] ?>">
                <img src="<?= htmlspecialchars(assetUrl($img)) ?>" class="card-img-top room-thumb" alt="<?= htmlspecialchars($room['title']) ?>">
                <div class="card-body d-flex flex-column">
                    <h3 class="mb-1" style="font-size:17px;"><?= htmlspecialchars($room['title']) ?></h3>
                    <div class="room-meta">
                        <span class="chip-lite"><?= htmlspecialchars($room['area']) ?></span>
                        <?php if (($room['shared_owner'] ?? null) === '1' || ($room['shared_owner'] ?? 0) == 1): ?>
                            <span class="chip-lite">Chung chủ</span>
                        <?php endif; ?>
                        <?php if (($room['closed_room'] ?? null) === '1' || ($room['closed_room'] ?? 0) == 1): ?>
                            <span class="chip-lite">Khép kín</span>
                        <?php endif; ?>
                        <?php if (!empty($room['vip_tier']) && $room['vip_tier'] !== 'Thường'): ?>
                            <span class="chip-lite"><?= htmlspecialchars($room['vip_tier']) ?></span>
                        <?php endif; ?>
                    </div>
                    <p class="room-address"><?= htmlspecialchars(maskAddress($room['address'])) ?></p>
                    <div class="room-price"><?= number_format((int)$room['price'], 0, ',', '.') ?> đ / tháng</div>
                    <p class="room-desc flex-grow-1 mb-3"><?= htmlspecialchars(mb_strimwidth($room['description'] ?? '', 0, 90, '...')) ?></p>
                    <span class="btn btn-success w-100 text-center cta">Xem phòng ngay</span>
                </div>
            </a>
        </div>
    <?php endforeach; ?>
</div>

<?php if ($pages > 1): ?>
<?php
  $buildPageUrl = function(int $p) {
      $params = $_GET;
      $params['route'] = 'rooms';
      $params['page'] = $p;
      return '?' . http_build_query($params);
  };
?>
<div class="d-flex justify-between align-items-center mt-3">
  <div class="text-muted small">Trang <?= $page ?> / <?= $pages ?> · <?= number_format($totalRooms,0,',','.') ?> phòng</div>
  <div class="d-flex gap-2">
    <a class="btn btn-outline-secondary btn-sm <?= $page <= 1 ? 'disabled' : '' ?>" href="<?= $page <= 1 ? '#' : $buildPageUrl($page-1) ?>">« Trước</a>
    <?php for ($i = max(1, $page-2); $i <= min($pages, $page+2); $i++): ?>
      <a class="btn btn-outline-secondary btn-sm <?= $i === $page ? 'active' : '' ?>" href="<?= $buildPageUrl($i) ?>"><?= $i ?></a>
    <?php endfor; ?>
    <a class="btn btn-outline-secondary btn-sm <?= $page >= $pages ? 'disabled' : '' ?>" href="<?= $page >= $pages ? '#' : $buildPageUrl($page+1) ?>">Sau »</a>
  </div>
</div>
<?php endif; ?>

<?php if (!empty($tenantPosts ?? [])): ?>
<div class="card tenant-posts-section" style="margin-top:40px;">
  <div class="card-body">
    <div class="section-header">
      <h3 class="section-title">Bài tìm trọ / ở ghép</h3>
      <a class="btn btn-outline btn-sm" href="?route=seek-posts">+ Đăng tìm trọ</a>
    </div>
    <p class="text-muted mb-2" style="font-size:13px;">
      Dành cho người đã có phòng và cần tìm bạn ở cùng. Website chỉ hỗ trợ đăng/hiển thị, không thu phí hay làm trung gian.
    </p>
    <div class="table-responsive">
      <table class="table align-middle">
        <thead>
          <tr>
            <th>Khu vực</th>
            <th>Ngân sách</th>
            <th>Người</th>
            <th>Giới tính</th>
            <th>Người đăng</th>
            <th>Liên hệ</th>
            <th>Thời gian</th>
            <th>Ghi chú</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($tenantPosts as $p): ?>
          <tr>
            <td><?= htmlspecialchars($p['area'] ?? '') ?></td>
            <td>
              <?php if (!empty($p['price_min']) || !empty($p['price_max'])): ?>
                <?= $p['price_min'] ? number_format((int)$p['price_min'],0,',','.') . 'đ' : '—' ?> -
                <?= $p['price_max'] ? number_format((int)$p['price_max'],0,',','.') . 'đ' : '—' ?>
              <?php else: ?>—<?php endif; ?>
            </td>
            <td><?= $p['people_count'] ? (int)$p['people_count'] : '—' ?></td>
            <td><?= ['male'=>'Nam','female'=>'Nữ','any'=>'Không yêu cầu'][$p['gender'] ?? 'any'] ?></td>
            <td><?= htmlspecialchars($p['user_name'] ?? 'Ẩn danh') ?></td>
            <td>
              <?php if (!empty($p['user_phone'])): ?>
                <div style="display:flex; align-items:center; gap:8px; white-space:nowrap;">
                  <span style="font-weight:600;"><?= htmlspecialchars($p['user_phone']) ?></span>
                  <a class="btn btn-outline btn-sm" style="padding:4px 10px; line-height:1.2;" href="tel:<?= htmlspecialchars($p['user_phone']) ?>">Gọi</a>
                </div>
              <?php else: ?>
                —
              <?php endif; ?>
            </td>
            <td><?= htmlspecialchars($p['created_at'] ?? '') ?></td>
            <td class="small text-muted" style="max-width:320px; white-space:pre-wrap;"><?= htmlspecialchars($p['note'] ?? '') ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="tenant-posts-mobile">
      <?php foreach ($tenantPosts as $p): ?>
        <?php
          if (!empty($p['price_min']) || !empty($p['price_max'])) {
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
        <article class="tenant-post-card">
          <div class="tenant-post-head">
            <div>
              <div class="tenant-post-area"><?= htmlspecialchars($p['area'] ?? '') ?></div>
              <div class="tenant-post-user">Đăng bởi <?= htmlspecialchars($p['user_name'] ?? 'Ẩn danh') ?></div>
            </div>
            <div class="tenant-post-time"><?= htmlspecialchars($createdAtCompact) ?></div>
          </div>
          <div class="tenant-post-meta">
            <div class="tenant-post-meta-item">
              <span class="tenant-post-meta-label">Ngân sách</span>
              <span class="tenant-post-meta-value"><?= htmlspecialchars($budgetText) ?></span>
            </div>
            <div class="tenant-post-meta-item">
              <span class="tenant-post-meta-label">Người</span>
              <span class="tenant-post-meta-value"><?= $p['people_count'] ? (int)$p['people_count'] . ' người' : '—' ?></span>
            </div>
            <div class="tenant-post-meta-item">
              <span class="tenant-post-meta-label">Giới tính</span>
              <span class="tenant-post-meta-value"><?= htmlspecialchars($genderText) ?></span>
            </div>
            <div class="tenant-post-meta-item">
              <span class="tenant-post-meta-label">Liên hệ</span>
              <span class="tenant-post-meta-value"><?= !empty($p['user_phone']) ? htmlspecialchars($p['user_phone']) : '—' ?></span>
            </div>
          </div>
          <?php if (!empty($p['user_phone'])): ?>
            <div class="tenant-post-contact">
              <div class="tenant-post-phone"><?= htmlspecialchars($p['user_phone']) ?></div>
              <a class="btn btn-outline btn-sm" href="tel:<?= htmlspecialchars($p['user_phone']) ?>">Gọi ngay</a>
            </div>
          <?php endif; ?>
          <div class="tenant-post-note"><?= htmlspecialchars($p['note'] ?? '') ?></div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<?php else: ?>
  <?php if (!empty($_SESSION['user']) && ($_SESSION['user']['role'] ?? '') === 'tenant'): ?>
    <div class="card mt-4" style="margin-top:40px;">
      <div class="card-body d-flex justify-between align-items-center">
        <div>
          <h3 class="mb-1" style="font-size:16px;">Chưa có bài tìm trọ / ở ghép.</h3>
          <p class="text-muted mb-0">Hãy đăng nhu cầu của bạn để chủ trọ liên hệ.</p>
        </div>
        <a class="btn btn-primary btn-sm" href="?route=seek-posts">+ Đăng tìm trọ</a>
      </div>
    </div>
  <?php endif; ?>
<?php endif; ?>



