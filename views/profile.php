<?php
$pageTitle = 'Tài khoản';

$roleLabels = [
    'tenant' => 'Người thuê',
    'landlord' => 'Chủ trọ',
    'staff' => 'Nhân sự',
    'admin' => 'Quản trị',
];
$roleLabel = $roleLabels[$user['role']] ?? ucfirst((string)($user['role'] ?? 'Tài khoản'));
$phoneVerified = !empty($user['phone_verified']);
$avatarUrl = assetUrl($user['avatar'] ?? 'avt.jpg');
$fallbackAvatarUrl = assetUrl('avt.jpg');
$messagesHref = (($user['role'] ?? '') === 'admin') ? '?route=admin-messages' : '?route=messages';
$rolePermissions = $roles[$user['role']] ?? [];

$roleAccentClass = 'is-tenant';
if (($user['role'] ?? '') === 'landlord' || ($user['role'] ?? '') === 'staff') {
    $roleAccentClass = 'is-landlord';
} elseif (($user['role'] ?? '') === 'admin') {
    $roleAccentClass = 'is-admin';
}

$summaryItems = [
    [
        'label' => 'Số điện thoại',
        'value' => $user['phone'] ?? '—',
        'hint' => $phoneVerified ? 'Đã xác minh và dùng để đăng nhập.' : 'Chưa xác minh, có thể chỉnh sửa.',
    ],
    [
        'label' => 'Ngày sinh',
        'value' => !empty($user['birthdate']) ? $user['birthdate'] : 'Chưa cập nhật',
        'hint' => 'Thông tin cá nhân cơ bản.',
    ],
    [
        'label' => 'Quê quán',
        'value' => !empty($user['hometown']) ? $user['hometown'] : 'Chưa cập nhật',
        'hint' => 'Giúp hồ sơ rõ ràng hơn.',
    ],
];

$quickLinks = [];
if (($user['role'] ?? '') === 'tenant') {
    $quickLinks = [
        ['href' => '?route=my-stay', 'title' => 'Chỗ ở tôi', 'desc' => 'Xem phòng đang thuê, hóa đơn và thông báo.', 'icon' => '🏠'],
        ['href' => '?route=seek-posts', 'title' => 'Đăng tìm phòng', 'desc' => 'Tạo hoặc cập nhật nhu cầu thuê.', 'icon' => '📝'],
        ['href' => $messagesHref, 'title' => 'Tin nhắn', 'desc' => 'Tiếp tục các cuộc trò chuyện đang mở.', 'icon' => '💬'],
    ];
} elseif (($user['role'] ?? '') === 'landlord') {
    $quickLinks = [
        ['href' => '?route=dashboard', 'title' => 'Bảng điều khiển', 'desc' => 'Xem vận hành trọ, việc cần xử lý và nhịp nhu cầu.', 'icon' => '📊'],
        ['href' => '?route=my-rooms', 'title' => 'Vận hành trọ', 'desc' => 'Quản lý phòng theo người thuê, hợp đồng và hoá đơn.', 'icon' => '🏘'],
        ['href' => '?route=payment-history', 'title' => 'Thanh toán', 'desc' => 'Theo dõi lịch sử nạp tiền và giao dịch.', 'icon' => '💳'],
    ];
} elseif (($user['role'] ?? '') === 'admin') {
    $quickLinks = [
        ['href' => '?route=admin', 'title' => 'Trang quản trị', 'desc' => 'Đi vào khu tổng quan và phê duyệt.', 'icon' => '🛠'],
        ['href' => '?route=admin-messages', 'title' => 'Tin nhắn người dùng', 'desc' => 'Trả lời hội thoại và hỗ trợ nhanh.', 'icon' => '💬'],
        ['href' => '?route=admin-theme', 'title' => 'Giao diện', 'desc' => 'Điều chỉnh nền, nút kêu gọi hành động và phần hiển thị.', 'icon' => '🎨'],
    ];
} else {
    $quickLinks = [
        ['href' => '?route=rooms', 'title' => 'Trang chủ', 'desc' => 'Quay lại danh sách phòng mới nhất.', 'icon' => '🏠'],
        ['href' => $messagesHref, 'title' => 'Tin nhắn', 'desc' => 'Mở khu vực trò chuyện.', 'icon' => '💬'],
    ];
}
?>

<style>
  .profile-shell {
    display: flex;
    flex-direction: column;
    gap: 18px;
  }
  .profile-hero {
    position: relative;
    overflow: hidden;
    border-radius: 22px;
    padding: 22px;
    background:
      radial-gradient(circle at 18% 18%, rgba(255,255,255,0.34), transparent 30%),
      radial-gradient(circle at 85% 12%, rgba(255,244,214,0.34), transparent 24%),
      linear-gradient(135deg, #fff8e8 0%, #fff3cf 44%, #ffe1a2 100%);
    border: 1px solid rgba(245, 158, 11, 0.26);
    box-shadow: 0 24px 42px rgba(180, 83, 9, 0.14);
  }
  .profile-hero::after {
    content: "";
    position: absolute;
    right: -42px;
    bottom: -64px;
    width: 220px;
    height: 220px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(217,119,6,0.10), transparent 64%);
    pointer-events: none;
  }
  .profile-hero-row {
    position: relative;
    z-index: 1;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 18px;
  }
  .profile-identity {
    display: flex;
    align-items: center;
    gap: 16px;
    min-width: 0;
    flex: 1 1 auto;
  }
  .profile-avatar {
    width: 92px;
    height: 92px;
    border-radius: 28px;
    overflow: hidden;
    flex: 0 0 92px;
    border: 2px solid rgba(255,255,255,0.68);
    box-shadow: 0 16px 28px rgba(146, 64, 14, 0.18);
    background: #fff;
  }
  .profile-avatar img {
    width: 100%;
    height: 100%;
    display: block;
    object-fit: cover;
  }
  .profile-eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 7px 11px;
    border-radius: 999px;
    background: rgba(255,255,255,0.72);
    border: 1px solid rgba(255,255,255,0.56);
    color: #9a3412;
    font-size: 12px;
    font-weight: 800;
  }
  .profile-name {
    margin: 10px 0 4px;
    font-size: clamp(24px, 3vw, 30px);
    line-height: 1.12;
    letter-spacing: -0.4px;
    color: #431407;
    word-break: break-word;
  }
  .profile-subline {
    margin: 0;
    color: #7c2d12;
    font-size: 14px;
    font-weight: 700;
    word-break: break-word;
  }
  .profile-status-row {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 12px;
  }
  .profile-pill {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    min-height: 34px;
    padding: 8px 12px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 800;
    border: 1px solid transparent;
  }
  .profile-pill.role {
    color: #7c2d12;
    background: rgba(255,255,255,0.72);
    border-color: rgba(251,191,36,0.42);
  }
  .profile-pill.verify.ok {
    color: #166534;
    background: rgba(220,252,231,0.94);
    border-color: rgba(34,197,94,0.24);
  }
  .profile-pill.verify.pending {
    color: #92400e;
    background: rgba(255,247,237,0.94);
    border-color: rgba(245,158,11,0.22);
  }
  .profile-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    justify-content: flex-end;
    flex: 0 0 auto;
  }
  .profile-actions .btn {
    min-height: 42px;
    padding-inline: 15px;
  }
  .profile-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.25fr) minmax(300px, 0.95fr);
    gap: 18px;
    align-items: start;
  }
  .profile-main-col,
  .profile-side-col {
    display: flex;
    flex-direction: column;
    gap: 18px;
    min-width: 0;
  }
  .profile-card {
    background: linear-gradient(180deg, rgba(255,255,255,0.98), rgba(255,251,241,0.98));
    border: 1px solid rgba(251,191,36,0.22);
    border-radius: 20px;
    box-shadow: 0 18px 36px rgba(15, 23, 42, 0.08);
    padding: 18px;
  }
  .profile-card-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    margin-bottom: 14px;
  }
  .profile-card-title {
    margin: 0;
    font-size: 18px;
    line-height: 1.2;
    letter-spacing: -0.25px;
    color: #111827;
  }
  .profile-card-note {
    margin: 4px 0 0;
    color: #6b7280;
    font-size: 13px;
  }
  .profile-summary-list {
    display: grid;
    gap: 10px;
  }
  .profile-summary-item {
    display: grid;
    grid-template-columns: minmax(112px, 140px) minmax(0, 1fr);
    gap: 12px;
    padding: 12px 0;
    border-bottom: 1px solid rgba(226, 232, 240, 0.78);
  }
  .profile-summary-item:last-child {
    border-bottom: none;
    padding-bottom: 0;
  }
  .profile-summary-label {
    color: #6b7280;
    font-size: 13px;
    font-weight: 700;
  }
  .profile-summary-value {
    font-size: 15px;
    font-weight: 800;
    color: #111827;
    line-height: 1.45;
    word-break: break-word;
  }
  .profile-summary-hint {
    margin-top: 4px;
    color: #6b7280;
    font-size: 12px;
    line-height: 1.45;
  }
  .profile-quick-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 12px;
  }
  .profile-quick-link {
    display: flex;
    flex-direction: column;
    gap: 10px;
    min-height: 148px;
    padding: 16px;
    border-radius: 18px;
    border: 1px solid rgba(251,191,36,0.18);
    background:
      linear-gradient(180deg, rgba(255,255,255,0.94), rgba(255,250,240,0.94)),
      #fff;
    box-shadow: 0 14px 28px rgba(217, 119, 6, 0.08);
    color: inherit;
    text-decoration: none;
    transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
  }
  .profile-quick-link:hover {
    transform: translateY(-2px);
    box-shadow: 0 20px 32px rgba(217, 119, 6, 0.14);
    border-color: rgba(245,158,11,0.36);
  }
  .profile-quick-icon {
    width: 42px;
    height: 42px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 14px;
    background: linear-gradient(135deg, #fff4d6, #ffe4a8);
    font-size: 20px;
    box-shadow: inset 0 1px 0 rgba(255,255,255,0.8);
  }
  .profile-quick-title {
    font-size: 15px;
    font-weight: 800;
    color: #111827;
    line-height: 1.35;
  }
  .profile-quick-desc {
    font-size: 13px;
    color: #6b7280;
    line-height: 1.5;
  }
  .profile-perm-list {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
  }
  .profile-perm-chip {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 10px 12px;
    border-radius: 14px;
    background: #fff7ed;
    color: #9a3412;
    font-size: 13px;
    font-weight: 800;
    border: 1px solid rgba(245,158,11,0.18);
  }
  .profile-side-stack {
    display: flex;
    flex-direction: column;
    gap: 14px;
  }
  .profile-side-highlight {
    padding: 16px;
    border-radius: 18px;
    background:
      radial-gradient(circle at 88% 14%, rgba(255,255,255,0.28), transparent 26%),
      linear-gradient(135deg, #fbbf24, #d97706);
    color: #431407;
    border: 1px solid rgba(245,158,11,0.2);
    box-shadow: 0 20px 32px rgba(180, 83, 9, 0.18);
  }
  .profile-side-highlight p {
    margin: 0;
    color: rgba(67,20,7,0.88);
  }
  .profile-side-highlight strong {
    display: block;
    margin-bottom: 6px;
    font-size: 17px;
  }
  .profile-inline-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 12px;
  }
  .profile-install-card {
    margin: 0;
    padding: 16px;
    border-radius: 18px;
    border: 1px solid rgba(245, 158, 11, 0.3);
    background: linear-gradient(180deg, #fffaf0, #fff6dd);
  }
  .profile-install-card[hidden] {
    display: none !important;
  }
  .profile-install-title {
    margin: 0;
    font-size: 16px;
    font-weight: 800;
    color: #92400e;
  }
  .profile-install-desc {
    margin: 6px 0 0;
    font-size: 13px;
    color: #4b5563;
    line-height: 1.5;
  }
  .profile-install-actions {
    margin-top: 12px;
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
  }
  .profile-install-actions .btn {
    min-height: 36px;
    font-size: 12px;
  }
  .profile-logout-wrap .btn {
    width: 100%;
    min-height: 44px;
  }
  .profile-edit-layout {
    display: grid;
    grid-template-columns: minmax(0, 1.2fr) minmax(280px, 0.8fr);
    gap: 18px;
    align-items: start;
  }
  .profile-form {
    display: grid;
    gap: 14px;
  }
  .profile-form-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14px;
  }
  .profile-form .form-field {
    display: flex;
    flex-direction: column;
    gap: 6px;
  }
  .profile-form .form-label {
    font-size: 13px;
    font-weight: 800;
    color: #374151;
  }
  .profile-form .form-help {
    margin: 0;
    color: #6b7280;
    font-size: 12px;
    line-height: 1.45;
  }
  .profile-file-input {
    padding: 10px 12px !important;
  }
  .profile-form-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    padding-top: 4px;
  }
  .profile-preview-card {
    position: sticky;
    top: 94px;
  }
  .profile-preview-avatar {
    width: 88px;
    height: 88px;
    border-radius: 24px;
    overflow: hidden;
    background: #fff7ed;
    margin-bottom: 12px;
    border: 1px solid rgba(245,158,11,0.18);
  }
  .profile-preview-avatar img {
    width: 100%;
    height: 100%;
    display: block;
    object-fit: cover;
  }
  .profile-preview-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-top: 12px;
  }
  .profile-preview-item {
    padding: 10px 12px;
    border-radius: 14px;
    background: #fffaf0;
    border: 1px solid rgba(245,158,11,0.14);
  }
  .profile-preview-item small {
    display: block;
    color: #6b7280;
    margin-bottom: 4px;
  }
  .profile-preview-item strong {
    display: block;
    color: #111827;
    word-break: break-word;
  }
  .profile-mobile-logout-wrap {
    display: none;
  }
  html.browser-dark .profile-hero {
    background:
      radial-gradient(circle at 18% 18%, rgba(255,255,255,0.10), transparent 28%),
      linear-gradient(135deg, #2f210f 0%, #24190d 52%, #1a130d 100%);
    border-color: rgba(245,158,11,0.18);
    box-shadow: 0 28px 46px rgba(0,0,0,0.38);
  }
  html.browser-dark .profile-name,
  html.browser-dark .profile-card-title,
  html.browser-dark .profile-summary-value,
  html.browser-dark .profile-quick-title,
  html.browser-dark .profile-preview-item strong {
    color: #fff6e7;
  }
  html.browser-dark .profile-subline,
  html.browser-dark .profile-eyebrow,
  html.browser-dark .profile-side-highlight,
  html.browser-dark .profile-side-highlight p,
  html.browser-dark .profile-side-highlight strong {
    color: #f5ddbc;
  }
  html.browser-dark .profile-card,
  html.browser-dark .profile-preview-item,
  html.browser-dark .profile-quick-link,
  html.browser-dark .profile-install-card {
    background: linear-gradient(180deg, #1d1813, #17120d);
    border-color: #473625;
    box-shadow: 0 18px 36px rgba(0,0,0,0.36);
  }
  html.browser-dark .profile-summary-item {
    border-bottom-color: rgba(71,54,37,0.85);
  }
  html.browser-dark .profile-summary-label,
  html.browser-dark .profile-summary-hint,
  html.browser-dark .profile-card-note,
  html.browser-dark .profile-quick-desc,
  html.browser-dark .profile-form .form-help,
  html.browser-dark .profile-preview-item small {
    color: #cdbb9f;
  }
  html.browser-dark .profile-pill.role {
    color: #ffe4bf;
    background: rgba(255,244,223,0.08);
    border-color: rgba(245,158,11,0.22);
  }
  html.browser-dark .profile-perm-chip {
    background: rgba(245,158,11,0.12);
    color: #ffd99a;
    border-color: rgba(245,158,11,0.24);
  }
  html.browser-dark .profile-quick-icon {
    background: linear-gradient(135deg, #352414, #4a2d15);
  }
  @media (max-width: 960px) {
    .profile-grid,
    .profile-edit-layout {
      grid-template-columns: 1fr;
    }
    .profile-preview-card {
      position: static;
    }
  }
  @media (max-width: 768px) {
    .profile-shell {
      gap: 14px;
    }
    .profile-hero {
      padding: 18px 16px;
      border-radius: 18px;
    }
    .profile-hero-row {
      flex-direction: column;
      align-items: stretch;
    }
    .profile-identity {
      align-items: flex-start;
    }
    .profile-avatar {
      width: 78px;
      height: 78px;
      border-radius: 22px;
      flex-basis: 78px;
    }
    .profile-name {
      font-size: 24px;
      margin-top: 8px;
    }
    .profile-actions {
      justify-content: stretch;
      width: 100%;
    }
    .profile-actions .btn {
      flex: 1 1 0;
      justify-content: center;
    }
    .profile-card {
      padding: 16px 14px;
      border-radius: 16px;
    }
    .profile-summary-item {
      grid-template-columns: 1fr;
      gap: 5px;
    }
    .profile-quick-grid,
    .profile-form-grid {
      grid-template-columns: 1fr;
    }
    .profile-quick-link {
      min-height: 0;
      padding: 14px;
    }
    .profile-install-actions .btn {
      flex: 1 1 auto;
    }
    .profile-mobile-logout-wrap {
      display: block;
    }
  }
</style>

<div class="profile-shell">
  <section class="profile-hero <?= htmlspecialchars($roleAccentClass) ?>">
    <div class="profile-hero-row">
      <div class="profile-identity">
        <div class="profile-avatar">
          <img
            src="<?= htmlspecialchars($avatarUrl) ?>"
            alt="Ảnh đại diện"
            onerror="this.src='<?= htmlspecialchars($fallbackAvatarUrl) ?>';"
          >
        </div>
        <div style="min-width:0;">
          <span class="profile-eyebrow">Hồ sơ tài khoản</span>
          <h1 class="profile-name"><?= htmlspecialchars($user['name']) ?></h1>
          <p class="profile-subline"><?= htmlspecialchars($user['phone']) ?></p>
          <div class="profile-status-row">
            <span class="profile-pill role"><?= htmlspecialchars($roleLabel) ?></span>
            <span class="profile-pill verify <?= $phoneVerified ? 'ok' : 'pending' ?>">
              <?= $phoneVerified ? 'Đã xác minh số điện thoại' : 'Chưa xác minh số điện thoại' ?>
            </span>
          </div>
        </div>
      </div>
      <div class="profile-actions">
        <a class="btn btn-primary" href="<?= $isEdit ? '?route=profile' : '?route=profile&edit=1' ?>">
          <?= $isEdit ? 'Xem hồ sơ' : 'Chỉnh sửa hồ sơ' ?>
        </a>
        <a class="btn btn-outline" href="<?= htmlspecialchars($messagesHref) ?>">Tin nhắn</a>
      </div>
    </div>
  </section>

  <?php if ($isEdit): ?>
    <section class="profile-edit-layout">
      <div class="profile-card">
        <div class="profile-card-head">
          <div>
            <h2 class="profile-card-title">Cập nhật thông tin</h2>
            <p class="profile-card-note">Chỉ giữ lại những trường thực sự cần thiết để chỉnh nhanh.</p>
          </div>
        </div>

        <form method="post" enctype="multipart/form-data" id="profileEditForm" class="profile-form">
          <input type="hidden" name="update_profile" value="1">
          <input type="hidden" name="ajax" value="1">

          <div class="profile-form-grid">
            <div class="form-field">
              <label class="form-label" for="profileName">Họ tên</label>
              <input id="profileName" type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" required>
            </div>

            <div class="form-field">
              <label class="form-label" for="profilePhone">Số điện thoại</label>
              <input
                id="profilePhone"
                type="tel"
                name="phone"
                class="form-control"
                value="<?= htmlspecialchars($user['phone']) ?>"
                <?= $phoneVerified ? 'readonly' : 'required pattern="0\\d{9}" minlength="10" maxlength="10"' ?>
              >
              <p class="form-help">
                <?= $phoneVerified
                  ? 'Số điện thoại đã xác minh nên không chỉnh sửa được.'
                  : 'Nếu đổi số, đây sẽ là tài khoản đăng nhập mới của bạn.' ?>
              </p>
            </div>

            <div class="form-field">
              <label class="form-label" for="profileBirthdate">Ngày sinh</label>
              <input id="profileBirthdate" type="date" name="birthdate" class="form-control" value="<?= htmlspecialchars($user['birthdate'] ?? '') ?>">
            </div>

            <div class="form-field">
              <label class="form-label" for="profileHometown">Quê quán</label>
              <input id="profileHometown" type="text" name="hometown" class="form-control" value="<?= htmlspecialchars($user['hometown'] ?? '') ?>" placeholder="Ví dụ: Thanh Hóa">
            </div>
          </div>

          <div class="form-field">
            <label class="form-label" for="profileAvatarFile">Ảnh đại diện</label>
            <input id="profileAvatarFile" type="file" name="avatar_file" accept="image/*" class="form-control profile-file-input">
            <p class="form-help">Ảnh vuông, rõ mặt hoặc logo cá nhân sẽ hiển thị đẹp nhất.</p>
          </div>

          <div class="profile-form-actions">
            <button class="btn btn-primary" type="submit" id="profileSaveBtn">Lưu thay đổi</button>
            <a class="btn btn-outline" href="?route=profile">Huỷ</a>
          </div>
        </form>
      </div>

      <aside class="profile-card profile-preview-card">
        <h2 class="profile-card-title">Xem nhanh trước khi lưu</h2>
        <p class="profile-card-note">Phần này giúp bạn kiểm tra nội dung ngay tại chỗ.</p>

        <div class="profile-preview-avatar">
          <img
            src="<?= htmlspecialchars($avatarUrl) ?>"
            alt="Avatar xem trước"
            id="profileAvatarPreview"
            onerror="this.src='<?= htmlspecialchars($fallbackAvatarUrl) ?>';"
          >
        </div>

        <div class="profile-preview-list">
          <div class="profile-preview-item">
            <small>Họ tên</small>
            <strong id="profilePreviewName"><?= htmlspecialchars($user['name']) ?></strong>
          </div>
          <div class="profile-preview-item">
            <small>Số điện thoại</small>
            <strong id="profilePreviewPhone"><?= htmlspecialchars($user['phone']) ?></strong>
          </div>
          <div class="profile-preview-item">
            <small>Thông tin thêm</small>
            <strong id="profilePreviewExtra">
              <?= htmlspecialchars(($user['birthdate'] ?? 'Chưa có ngày sinh') . ' · ' . ($user['hometown'] ?? 'Chưa có quê quán')) ?>
            </strong>
          </div>
        </div>
      </aside>
    </section>
  <?php else: ?>
    <section class="profile-grid">
      <div class="profile-main-col">
        <div class="profile-card">
          <div class="profile-card-head">
            <div>
              <h2 class="profile-card-title">Thông tin cơ bản</h2>
              <p class="profile-card-note">Những gì cần xem nhanh được gom lại ở một chỗ.</p>
            </div>
          </div>

          <div class="profile-summary-list">
            <?php foreach ($summaryItems as $item): ?>
              <div class="profile-summary-item">
                <div class="profile-summary-label"><?= htmlspecialchars($item['label']) ?></div>
                <div>
                  <div class="profile-summary-value"><?= htmlspecialchars($item['value']) ?></div>
                  <div class="profile-summary-hint"><?= htmlspecialchars($item['hint']) ?></div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="profile-card">
          <div class="profile-card-head">
            <div>
              <h2 class="profile-card-title">Lối tắt thường dùng</h2>
              <p class="profile-card-note">Ưu tiên đúng việc bạn cần làm tiếp theo.</p>
            </div>
          </div>

          <div class="profile-quick-grid">
            <?php foreach ($quickLinks as $link): ?>
              <a class="profile-quick-link" href="<?= htmlspecialchars($link['href']) ?>">
                <span class="profile-quick-icon"><?= htmlspecialchars($link['icon']) ?></span>
                <div class="profile-quick-title"><?= htmlspecialchars($link['title']) ?></div>
                <div class="profile-quick-desc"><?= htmlspecialchars($link['desc']) ?></div>
              </a>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <aside class="profile-side-col">
        <div class="profile-side-highlight">
          <strong><?= htmlspecialchars($roleLabel) ?></strong>
          <p>
            <?= $phoneVerified
              ? 'Tài khoản đang ở trạng thái ổn định, có thể dùng ngay cho các thao tác chính.'
              : 'Nên hoàn thiện thêm hồ sơ để dùng tài khoản thuận tiện hơn.' ?>
          </p>
          <div class="profile-inline-actions">
            <?php if (($user['role'] ?? '') === 'admin'): ?>
              <a class="btn btn-outline btn-sm" href="?route=admin">Mở quản trị</a>
            <?php elseif (($user['role'] ?? '') === 'landlord'): ?>
              <a class="btn btn-outline btn-sm" href="?route=dashboard">Mở bảng điều khiển</a>
            <?php elseif (($user['role'] ?? '') === 'tenant'): ?>
              <a class="btn btn-outline btn-sm" href="?route=my-stay">Mở chỗ ở tôi</a>
            <?php endif; ?>
            <a class="btn btn-outline btn-sm" href="?route=logout">Đăng xuất</a>
          </div>
        </div>

        <div class="profile-card">
          <div class="profile-card-head">
            <div>
              <h2 class="profile-card-title">Quyền của tài khoản</h2>
              <p class="profile-card-note">Rút gọn theo dạng thẻ để dễ quét mắt hơn.</p>
            </div>
          </div>

          <div class="profile-perm-list">
            <?php foreach ($rolePermissions as $perm): ?>
              <span class="profile-perm-chip">• <?= htmlspecialchars($perm) ?></span>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="profile-install-card" id="profileInstallCard" hidden>
          <p class="profile-install-title">Cài ứng dụng PhòngTrọ</p>
          <p class="profile-install-desc">Thêm ứng dụng ra màn hình chính để mở nhanh hơn khi cần xem tin, quản lý phòng hoặc trả lời tin nhắn.</p>
          <div class="profile-install-actions">
            <button type="button" class="btn btn-primary btn-sm" id="profileInstallBtn">Cài ngay</button>
            <button type="button" class="btn btn-outline-secondary btn-sm" id="profileInstallLaterBtn">Để sau</button>
          </div>
        </div>

        <div class="profile-mobile-logout-wrap profile-logout-wrap">
          <a class="btn btn-danger" href="?route=logout">Đăng xuất</a>
        </div>
      </aside>
    </section>
  <?php endif; ?>
</div>

<?php if ($isEdit): ?>
<script>
  (() => {
    const form = document.getElementById('profileEditForm');
    if (!form) return;

    const saveBtn = document.getElementById('profileSaveBtn');
    const nameInput = document.getElementById('profileName');
    const phoneInput = document.getElementById('profilePhone');
    const birthInput = document.getElementById('profileBirthdate');
    const hometownInput = document.getElementById('profileHometown');
    const avatarInput = document.getElementById('profileAvatarFile');
    const previewName = document.getElementById('profilePreviewName');
    const previewPhone = document.getElementById('profilePreviewPhone');
    const previewExtra = document.getElementById('profilePreviewExtra');
    const previewAvatar = document.getElementById('profileAvatarPreview');
    const initialAvatar = '<?= htmlspecialchars($avatarUrl, ENT_QUOTES) ?>';

    const syncPreview = () => {
      if (previewName && nameInput) {
        previewName.textContent = nameInput.value.trim() || 'Chưa nhập họ tên';
      }
      if (previewPhone && phoneInput) {
        previewPhone.textContent = phoneInput.value.trim() || 'Chưa nhập số điện thoại';
      }
      if (previewExtra) {
        const birth = birthInput?.value?.trim() || 'Chưa có ngày sinh';
        const home = hometownInput?.value?.trim() || 'Chưa có quê quán';
        previewExtra.textContent = `${birth} · ${home}`;
      }
    };

    [nameInput, phoneInput, birthInput, hometownInput].forEach((input) => {
      input?.addEventListener('input', syncPreview);
      input?.addEventListener('change', syncPreview);
    });

    avatarInput?.addEventListener('change', () => {
      const file = avatarInput.files && avatarInput.files[0];
      if (!previewAvatar) return;
      if (!file) {
        previewAvatar.src = initialAvatar;
        return;
      }
      const objectUrl = URL.createObjectURL(file);
      previewAvatar.src = objectUrl;
    });

    syncPreview();

    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      const fd = new FormData(form);
      const originalLabel = saveBtn ? saveBtn.textContent : '';

      if (saveBtn) {
        saveBtn.disabled = true;
        saveBtn.textContent = 'Đang lưu...';
      }

      try {
        const res = await fetch('?route=profile', { method: 'POST', body: fd });
        const json = await res.json();
        if (!json.ok) {
          alert(json.error || 'Không lưu được thông tin.');
          return;
        }
        window.location.href = '?route=profile';
      } catch (err) {
        console.error(err);
        alert('Không lưu được thông tin.');
      } finally {
        if (saveBtn) {
          saveBtn.disabled = false;
          saveBtn.textContent = originalLabel;
        }
      }
    });
  })();
</script>
<?php endif; ?>

<script>
  (() => {
    const card = document.getElementById('profileInstallCard');
    const installBtn = document.getElementById('profileInstallBtn');
    const laterBtn = document.getElementById('profileInstallLaterBtn');
    if (!card || !installBtn || !laterBtn) return;

    const dismissKey = 'pwa_install_profile_dismissed_at_v1';
    const installedKey = 'pwa_install_profile_installed_v1';
    const dismissCooldownMs = 5 * 60 * 1000;

    const isMobile = window.matchMedia('(max-width: 992px)').matches;
    const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;

    const readNumber = (key) => {
      try {
        return Number(localStorage.getItem(key) || '0');
      } catch (e) {
        return 0;
      }
    };

    const setValue = (key, value) => {
      try {
        localStorage.setItem(key, value);
      } catch (e) {
        // ignore storage errors
      }
    };

    const isDismissedRecently = () => {
      const ts = readNumber(dismissKey);
      return ts > 0 && (Date.now() - ts) < dismissCooldownMs;
    };

    const isInstalled = () => {
      if (isStandalone || window.__pwaInstalled === true) return true;
      try {
        return localStorage.getItem(installedKey) === '1';
      } catch (e) {
        return false;
      }
    };

    const updateCardVisibility = () => {
      const canInstall = !!window.__deferredInstallPrompt;
      card.hidden = !(isMobile && !isInstalled() && !isDismissedRecently() && canInstall);
    };

    installBtn.addEventListener('click', async () => {
      const deferredPrompt = window.__deferredInstallPrompt;
      if (!deferredPrompt) return;
      try {
        deferredPrompt.prompt();
        const choice = await deferredPrompt.userChoice;
        if (choice?.outcome === 'accepted') {
          setValue(installedKey, '1');
          window.__deferredInstallPrompt = null;
          card.hidden = true;
        }
      } catch (e) {
        // ignore
      }
    });

    laterBtn.addEventListener('click', () => {
      setValue(dismissKey, String(Date.now()));
      card.hidden = true;
    });

    window.addEventListener('phongtro-install-available', updateCardVisibility);
    window.addEventListener('phongtro-app-installed', () => {
      setValue(installedKey, '1');
      card.hidden = true;
    });

    updateCardVisibility();
    window.setTimeout(updateCardVisibility, 1200);
    window.setInterval(updateCardVisibility, 30 * 1000);
  })();
</script>
