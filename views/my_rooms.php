<?php $pageTitle = 'Vận hành trọ'; ?>
<div class="admin-shell">
  <aside class="admin-menu">
    <a href="?route=dashboard" class="<?= ($activeMenu ?? '') === 'home' ? 'active' : '' ?>">Tổng quan</a>
    <a href="?route=my-rooms" class="<?= ($activeMenu ?? '') === 'rooms' ? 'active' : '' ?>">Vận hành trọ</a>
    <a href="?route=dashboard&tab=lead#lead" class="<?= ($activeMenu ?? '') === 'leads' ? 'active' : '' ?>">Quan tâm</a>
    <a href="?route=payment-history" class="<?= ($activeMenu ?? '') === 'payments' ? 'active' : '' ?>">Thanh toán</a>
  </aside>
  <div>
    <div class="card mb-3">
      <div class="card-body">
        <strong>Boost phòng</strong>
        <p class="mb-1 text-muted small">Mỗi lượt boost có hiệu lực 12 giờ, giới hạn theo gói hiện có.</p>
        <span class="badge badge-success">Còn <?= max(0, (int)($boostQuota ?? 0) - (int)($boostUsed ?? 0)) ?>/<?= (int)($boostQuota ?? 0) ?> lượt hôm nay</span>
      </div>
    </div>

    <style>
      .my-rooms-top {
        display:flex;
        justify-content:space-between;
        align-items:flex-end;
        gap:12px;
        flex-wrap:wrap;
      }
      .my-rooms-top-actions {
        display:flex;
        align-items:center;
        gap:8px;
        flex-wrap:wrap;
      }
      .my-rooms-search-form {
        display:flex;
        gap:8px;
        margin:0;
      }
      .my-rooms-search-form .form-control {
        width:260px;
        padding:8px 10px;
        border-radius:10px;
      }
      .my-rooms-list {
        display:flex;
        flex-direction:column;
        gap:10px;
      }
      .my-room-item {
        border:1px solid #e5e7eb;
        border-radius:12px;
        padding:10px;
        background:#fff;
        display:flex;
        align-items:flex-start;
        gap:10px;
      }
      .my-room-head {
        display:flex;
        align-items:flex-start;
        gap:10px;
        min-width:0;
        flex:1 1 auto;
      }
      .my-room-thumb {
        width:56px;
        height:56px;
        border-radius:10px;
        border:1px solid #f1f5f9;
        object-fit:cover;
        flex:0 0 56px;
        background:#f8fafc;
      }
      .my-room-title {
        margin:0 0 5px;
        color:#111827;
        font-size:14px;
        line-height:1.35;
        font-weight:700;
        overflow-wrap:anywhere;
      }
      .my-room-status {
        display:inline-flex;
      }
      .my-room-meta {
        display:flex;
        flex-wrap:wrap;
        gap:6px;
        margin-top:8px;
      }
      .my-room-note {
        margin-top:8px;
        font-size:12px;
        color:#6b7280;
        line-height:1.45;
      }
      .my-room-actions {
        display:flex;
        gap:6px;
        flex-wrap:wrap;
        justify-content:flex-end;
      }
      .my-room-actions form { margin:0; }
      .my-room-actions .btn {
        white-space:nowrap;
        min-height:32px;
      }
      @media (max-width: 768px) {
        .my-rooms-top {
          flex-direction: column;
          align-items:stretch;
        }
        .my-rooms-top > div:first-child { width:100%; }
        .my-rooms-top-actions {
          width:100%;
          flex-direction:column;
          align-items:stretch;
        }
        .my-rooms-search-form {
          width:100%;
        }
        .my-rooms-search-form .form-control {
          width:100%;
        }
        .my-room-item {
          flex-direction:column;
        }
        .my-room-head {
          width:100%;
        }
        .my-room-actions {
          width:100%;
        }
        .my-room-actions .btn,
        .my-room-actions form {
          flex:1 1 calc(50% - 6px);
        }
        .my-room-actions .btn {
          width:100%;
          white-space:nowrap;
        }
      }
    </style>

    <div class="my-rooms-top mb-3">
      <div>
        <h1 class="mb-1" style="font-size:20px;">Quản lý vận hành trọ</h1>
        <p class="text-muted mb-0">Mỗi phòng là một hồ sơ vận hành: người thuê, công tơ, hợp đồng và hóa đơn.</p>
      </div>
      <div class="my-rooms-top-actions">
        <form method="get" action="" class="my-rooms-search-form">
          <input type="hidden" name="route" value="my-rooms">
          <input type="text" name="q" class="form-control" value="<?= htmlspecialchars($keyword ?? '') ?>" placeholder="Tìm theo mã hoặc tên phòng">
          <button class="btn btn-outline btn-sm" type="submit">Tìm</button>
        </form>
        <a class="btn btn-primary btn-sm" href="?route=room-create">+ Đăng phòng</a>
      </div>
    </div>

    <div class="card">
      <div class="card-body">
        <?php $statusLabels = ['pending' => 'Đang duyệt', 'active' => 'Đang hiển thị', 'rejected' => 'Bị từ chối']; ?>
        <div class="my-rooms-list">
          <?php if (empty($rooms)): ?>
            <div class="text-muted">Chưa có phòng. Nhấn “Đăng phòng”.</div>
          <?php endif; ?>

          <?php foreach ($rooms as $r): ?>
            <?php
              $thumb = '';
              foreach (['thumbnail', 'image1', 'image2', 'image3', 'image4'] as $imgKey) {
                  $imgVal = trim((string)($r[$imgKey] ?? ''));
                  if ($imgVal !== '') {
                      $thumb = $imgVal;
                      break;
                  }
              }
              if ($thumb === '') {
                  $thumb = 'favicon.png';
              }
            ?>
            <article class="my-room-item">
              <div class="my-room-head">
                <img src="<?= htmlspecialchars(assetUrl($thumb)) ?>" alt="<?= htmlspecialchars((string)$r['title']) ?>" class="my-room-thumb" onerror="this.src='<?= htmlspecialchars(assetUrl('favicon.png')) ?>'">
                <div style="min-width:0;">
                  <h3 class="my-room-title">#<?= (int)$r['id'] ?> · <?= htmlspecialchars((string)$r['title']) ?></h3>
                  <div class="my-room-meta">
                    <span class="badge badge-outline my-room-status"><?= htmlspecialchars($statusLabels[$r['status']] ?? (string)$r['status']) ?></span>
                    <span class="badge <?= (($r['ops_profile']['occupancy_status'] ?? 'vacant') === 'occupied') ? 'badge-success' : 'badge-warning' ?>">
                      <?= htmlspecialchars((string)($r['ops_status_label'] ?? 'Phòng trống')) ?>
                    </span>
                    <?php if ((int)($r['ops_unpaid_invoice_count'] ?? 0) > 0): ?>
                      <span class="badge badge-warning"><?= (int)$r['ops_unpaid_invoice_count'] ?> hóa đơn tồn</span>
                    <?php endif; ?>
                  </div>
                  <div class="my-room-note">
                    <?= !empty($r['ops_tenant_name']) ? ('Người thuê: ' . htmlspecialchars((string)$r['ops_tenant_name'])) : 'Chưa gắn người thuê' ?>
                    · Thu thực tế: <?= number_format((int)($r['ops_monthly_rent'] ?? $r['price'] ?? 0), 0, ',', '.') ?> đ
                    <?php if (($r['ops_contract_days_left'] ?? null) !== null): ?>
                      · Hợp đồng còn <?= (int)$r['ops_contract_days_left'] ?> ngày
                    <?php endif; ?>
                  </div>
                </div>
              </div>

              <div class="my-room-actions">
                <a class="btn btn-primary btn-sm" href="?route=room-ops&id=<?= (int)$r['id'] ?>">Vận hành</a>
                <a class="btn btn-outline btn-sm" href="?route=room&id=<?= (int)$r['id'] ?>" target="_blank" rel="noopener">Xem chi tiết</a>
                <a class="btn btn-outline btn-sm" href="?route=room-edit&id=<?= (int)$r['id'] ?>">Sửa tin</a>
                <form method="post" action="?route=room-delete" onsubmit="return confirm('Xóa phòng này?');">
                  <input type="hidden" name="room_id" value="<?= (int)$r['id'] ?>">
                  <button class="btn btn-outline-secondary btn-sm" type="submit">Xóa</button>
                </form>
                <?php if (($boostQuota ?? 0) > ($boostUsed ?? 0)): ?>
                  <form method="post" action="?route=room-boost">
                    <input type="hidden" name="room_id" value="<?= (int)$r['id'] ?>">
                    <button class="btn btn-success btn-sm" type="submit">Boost</button>
                  </form>
                <?php else: ?>
                  <button class="btn btn-outline-secondary btn-sm" type="button" disabled>Hết boost</button>
                <?php endif; ?>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</div>
GET['focus'] ?? '') === 'issues') ? 'active' : '' ?>">Sự cố</a>
    <a href="?route=dashboard" class="<?= ($activeMenu ?? '') === 'home' ? 'active' : '' ?>">Báo cáo</a>
  </aside>
  <div>
    <div class="card mb-3">
      <div class="card-body">
        <strong>Boost phòng</strong>
        <p class="mb-1 text-muted small">Mỗi lượt boost có hiệu lực 12 giờ, giới hạn theo gói hiện có.</p>
        <span class="badge badge-success">Còn <?= max(0, (int)($boostQuota ?? 0) - (int)($boostUsed ?? 0)) ?>/<?= (int)($boostQuota ?? 0) ?> lượt hôm nay</span>
      </div>
    </div>

    <style>
      .my-rooms-top {
        display:flex;
        justify-content:space-between;
        align-items:flex-end;
        gap:12px;
        flex-wrap:wrap;
      }
      .my-rooms-top-actions {
        display:flex;
        align-items:center;
        gap:8px;
        flex-wrap:wrap;
      }
      .my-rooms-search-form {
        display:flex;
        gap:8px;
        margin:0;
      }
      .my-rooms-search-form .form-control {
        width:260px;
        padding:8px 10px;
        border-radius:10px;
      }
      .my-rooms-list {
        display:flex;
        flex-direction:column;
        gap:10px;
      }
      .my-room-item {
        border:1px solid #e5e7eb;
        border-radius:12px;
        padding:10px;
        background:#fff;
        display:flex;
        align-items:flex-start;
        gap:10px;
      }
      .my-room-head {
        display:flex;
        align-items:flex-start;
        gap:10px;
        min-width:0;
        flex:1 1 auto;
      }
      .my-room-thumb {
        width:56px;
        height:56px;
        border-radius:10px;
        border:1px solid #f1f5f9;
        object-fit:cover;
        flex:0 0 56px;
        background:#f8fafc;
      }
      .my-room-title {
        margin:0 0 5px;
        color:#111827;
        font-size:14px;
        line-height:1.35;
        font-weight:700;
        overflow-wrap:anywhere;
      }
      .my-room-status {
        display:inline-flex;
      }
      .my-room-meta {
        display:flex;
        flex-wrap:wrap;
        gap:6px;
        margin-top:8px;
      }
      .my-room-note {
        margin-top:8px;
        font-size:12px;
        color:#6b7280;
        line-height:1.45;
      }
      .my-room-actions {
        display:flex;
        gap:6px;
        flex-wrap:wrap;
        justify-content:flex-end;
      }
      .my-room-actions form { margin:0; }
      .my-room-actions .btn {
        white-space:nowrap;
        min-height:32px;
      }
      @media (max-width: 768px) {
        .my-rooms-top {
          flex-direction: column;
          align-items:stretch;
        }
        .my-rooms-top > div:first-child { width:100%; }
        .my-rooms-top-actions {
          width:100%;
          flex-direction:column;
          align-items:stretch;
        }
        .my-rooms-search-form {
          width:100%;
        }
        .my-rooms-search-form .form-control {
          width:100%;
        }
        .my-room-item {
          flex-direction:column;
        }
        .my-room-head {
          width:100%;
        }
        .my-room-actions {
          width:100%;
        }
        .my-room-actions .btn,
        .my-room-actions form {
          flex:1 1 calc(50% - 6px);
        }
        .my-room-actions .btn {
          width:100%;
          white-space:nowrap;
        }
      }
    </style>

    <div class="my-rooms-top mb-3">
      <div>
        <h1 class="mb-1" style="font-size:20px;">Quản lý vận hành trọ</h1>
        <p class="text-muted mb-0">Mỗi phòng là một hồ sơ vận hành: người thuê, công tơ, hợp đồng và hóa đơn.</p>
      </div>
      <div class="my-rooms-top-actions">
        <form method="get" action="" class="my-rooms-search-form">
          <input type="hidden" name="route" value="my-rooms">
          <input type="text" name="q" class="form-control" value="<?= htmlspecialchars($keyword ?? '') ?>" placeholder="Tìm theo mã hoặc tên phòng">
          <button class="btn btn-outline btn-sm" type="submit">Tìm</button>
        </form>
        <a class="btn btn-primary btn-sm" href="?route=room-create">+ Đăng phòng</a>
      </div>
    </div>

    <div class="card">
      <div class="card-body">
        <?php $statusLabels = ['pending' => 'Đang duyệt', 'active' => 'Đang hiển thị', 'rejected' => 'Bị từ chối']; ?>
        <div class="my-rooms-list">
          <?php if (empty($rooms)): ?>
            <div class="text-muted">Chưa có phòng. Nhấn “Đăng phòng”.</div>
          <?php endif; ?>

          <?php foreach ($rooms as $r): ?>
            <?php
              $thumb = '';
              foreach (['thumbnail', 'image1', 'image2', 'image3', 'image4'] as $imgKey) {
                  $imgVal = trim((string)($r[$imgKey] ?? ''));
                  if ($imgVal !== '') {
                      $thumb = $imgVal;
                      break;
                  }
              }
              if ($thumb === '') {
                  $thumb = 'favicon.png';
              }
            ?>
            <article class="my-room-item">
              <div class="my-room-head">
                <img src="<?= htmlspecialchars(assetUrl($thumb)) ?>" alt="<?= htmlspecialchars((string)$r['title']) ?>" class="my-room-thumb" onerror="this.src='<?= htmlspecialchars(assetUrl('favicon.png')) ?>'">
                <div style="min-width:0;">
                  <h3 class="my-room-title">#<?= (int)$r['id'] ?> · <?= htmlspecialchars((string)$r['title']) ?></h3>
                  <div class="my-room-meta">
                    <span class="badge badge-outline my-room-status"><?= htmlspecialchars($statusLabels[$r['status']] ?? (string)$r['status']) ?></span>
                    <span class="badge <?= (($r['ops_profile']['occupancy_status'] ?? 'vacant') === 'occupied') ? 'badge-success' : 'badge-warning' ?>">
                      <?= htmlspecialchars((string)($r['ops_status_label'] ?? 'Phòng trống')) ?>
                    </span>
                    <?php if ((int)($r['ops_unpaid_invoice_count'] ?? 0) > 0): ?>
                      <span class="badge badge-warning"><?= (int)$r['ops_unpaid_invoice_count'] ?> hóa đơn tồn</span>
                    <?php endif; ?>
                  </div>
                  <div class="my-room-note">
                    <?= !empty($r['ops_tenant_name']) ? ('Người thuê: ' . htmlspecialchars((string)$r['ops_tenant_name'])) : 'Chưa gắn người thuê' ?>
                    · Thu thực tế: <?= number_format((int)($r['ops_monthly_rent'] ?? $r['price'] ?? 0), 0, ',', '.') ?> đ
                    <?php if (($r['ops_contract_days_left'] ?? null) !== null): ?>
                      · Hợp đồng còn <?= (int)$r['ops_contract_days_left'] ?> ngày
                    <?php endif; ?>
                  </div>
                </div>
              </div>

              <div class="my-room-actions">
                <a class="btn btn-primary btn-sm" href="?route=room-ops&id=<?= (int)$r['id'] ?>">Vận hành</a>
                <a class="btn btn-outline btn-sm" href="?route=room&id=<?= (int)$r['id'] ?>" target="_blank" rel="noopener">Xem chi tiết</a>
                <a class="btn btn-outline btn-sm" href="?route=room-edit&id=<?= (int)$r['id'] ?>">Sửa tin</a>
                <form method="post" action="?route=room-delete" onsubmit="return confirm('Xóa phòng này?');">
                  <input type="hidden" name="room_id" value="<?= (int)$r['id'] ?>">
                  <button class="btn btn-outline-secondary btn-sm" type="submit">Xóa</button>
                </form>
                <?php if (($boostQuota ?? 0) > ($boostUsed ?? 0)): ?>
                  <form method="post" action="?route=room-boost">
                    <input type="hidden" name="room_id" value="<?= (int)$r['id'] ?>">
                    <button class="btn btn-success btn-sm" type="submit">Boost</button>
                  </form>
                <?php else: ?>
                  <button class="btn btn-outline-secondary btn-sm" type="button" disabled>Hết boost</button>
                <?php endif; ?>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</div>

