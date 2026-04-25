<?php
  $pageTitle = 'Đăng ký';
  $oldName = $_POST['name'] ?? '';
  $oldPhone = $_POST['phone'] ?? '';
  $oldPassword = $_POST['password'] ?? '';
  $oldPasswordConfirm = $_POST['password_confirm'] ?? '';
  $oldIsLandlord = isset($_POST['is_landlord']) && $_POST['is_landlord'];
?>

<style>
  .auth-shell {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: 18px;
    align-items: stretch;
    margin-top: 12px;
  }
  .auth-panel {
    background: var(--card);
    border-radius: 18px;
    border: 1px solid #fde68a;
    box-shadow: var(--shadow);
    overflow: hidden;
    position: relative;
  }
  .auth-hero {
    background: radial-gradient(140% 140% at 15% 10%, rgba(34,197,94,0.22), transparent 50%),
                radial-gradient(110% 130% at 90% 10%, rgba(59,130,246,0.18), transparent 55%),
                linear-gradient(135deg, #fbbf24, #d97706);
    color: #0f172a;
    padding: 22px;
    height: 100%;
    display: flex;
    flex-direction: column;
    gap: 14px;
  }
  .auth-hero h2 { margin: 0; font-size: 22px; letter-spacing: -0.3px; }
  .auth-hero p { margin: 0; color: #111827; opacity: 0.92; }
  .auth-hero .pill {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 14px;
    background: rgba(255,255,255,0.92);
    color: #7c2d12;
    border-radius: 999px;
    font-weight: 800;
    box-shadow: 0 12px 26px rgba(124,45,18,0.18);
    border: 1px solid rgba(255,255,255,0.7);
  }
  .auth-hero ul { padding-left: 18px; margin: 6px 0 0 0; line-height: 1.55; font-weight: 600; color: #0f172a; }
  .auth-hero li { margin-bottom: 6px; }
  .auth-form { padding: 22px; display: flex; flex-direction: column; gap: 14px; }
  .auth-form h1 { font-size: 24px; letter-spacing: -0.2px; margin: 0; }
  .auth-form .subtitle { color: var(--muted); font-size: 14px; margin: -4px 0 6px; }
  .field { display: flex; flex-direction: column; gap: 6px; }
  .field label { font-weight: 700; color: #0f172a; }
  .field .input-wrap {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 12px;
    background: #fff;
    border: 1px solid #d8dce6;
    border-radius: 12px;
    min-width: 0;
    transition: border .15s ease, box-shadow .15s ease, transform .1s ease;
  }
  .field .input-wrap:focus-within {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(217,119,6,0.18);
    transform: translateY(-1px);
  }
  .field .icon {
    width: 34px;
    height: 34px;
    border-radius: 10px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: #fff7e6;
    color: #b45309;
    font-weight: 800;
    font-size: 15px;
    flex-shrink: 0;
  }
  .field input {
    border: none;
    outline: none;
    width: auto;
    min-width: 0;
    flex: 1 1 auto;
    background: transparent;
    font-size: 15px;
    color: #0f172a;
  }
  .field input::placeholder { color: #9ca3af; }
  .toggle-pass {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    color: #0f172a;
    border-radius: 10px;
    padding: 8px 10px;
    cursor: pointer;
    font-weight: 700;
    flex: 0 0 auto;
  }
  .role-note {
    background: #f8fafc;
    border: 1px dashed #cbd5e1;
    padding: 12px;
    border-radius: 12px;
    color: #0f172a;
    font-weight: 600;
  }
  .alt-link { font-weight: 700; color: #b45309; }
  .auth-footer { text-align: center; color: var(--muted); font-size: 13px; }
  @media (max-width: 720px) {
    .auth-shell {
      grid-template-columns: 1fr;
      gap: 14px;
      margin-top: 8px;
    }
    .auth-panel { border-radius: 16px; }
    .auth-hero {
      min-height: auto;
      padding: 18px 16px;
    }
    .auth-form {
      padding: 18px 14px 16px;
    }
    .auth-form h1 { font-size: 20px; }
    .field .input-wrap { padding: 10px; }
    .toggle-pass { padding: 8px 9px; }
    .role-note { padding: 10px; }
  }
</style>

<div class="auth-shell">
  <div class="auth-panel auth-hero">
    <div class="pill">✨ Tạo tài khoản mới</div>
    <h2>Gia nhập cộng đồng PhòngTrọ</h2>
    <p>Đăng ký để đăng phòng, lưu phòng yêu thích và nhận thông báo lịch xem phòng.</p>
    <ul>
      <li>Chủ trọ: đăng phòng, quản lý quan tâm, nhận nhu cầu sớm.</li>
      <li>Người thuê: lưu phòng, nhận nhắc xem phòng, trò chuyện với chủ.</li>
      <li>Bảo mật số điện thoại, quản lý thông báo linh hoạt.</li>
    </ul>
  </div>

  <div class="auth-panel">
    <div class="auth-form">
      <div>
        <h1>Tạo tài khoản</h1>
        <div class="subtitle">Điền thông tin cơ bản để bắt đầu.</div>
      </div>

      <form method="post" action="?route=register" class="d-flex flex-column gap-3">
        <div class="field">
          <label for="regName">Họ và tên (đầy đủ)</label>
          <div class="input-wrap">
            <span class="icon">👤</span>
            <input type="text" id="regName" name="name" required placeholder="Ví dụ: Nguyễn Văn A" autocomplete="name" value="<?= htmlspecialchars($oldName) ?>">
          </div>
        </div>

        <div class="field">
          <label for="regPhone">Số điện thoại</label>
          <div class="input-wrap">
            <span class="icon">📱</span>
            <input type="tel" id="regPhone" name="phone" required pattern="0\d{9}" minlength="10" maxlength="10" placeholder="0912 345 678" autocomplete="tel" value="<?= htmlspecialchars($oldPhone) ?>">
          </div>
          <div class="text-muted small">Số điện thoại dùng để liên hệ gọi điện, vui lòng ghi đúng.</div>
        </div>

        <div class="field">
          <label for="password">Mật khẩu</label>
          <div class="input-wrap">
            <span class="icon">🔒</span>
            <input type="password" id="password" name="password" required minlength="6" autocomplete="new-password" value="<?= htmlspecialchars($oldPassword) ?>">
            <button type="button" class="toggle-pass" data-toggle-pass="password" aria-label="Hiện/ẩn mật khẩu">👁</button>
          </div>
        </div>

        <div class="field">
          <label for="password_confirm">Nhập lại mật khẩu</label>
          <div class="input-wrap">
            <span class="icon">✅</span>
            <input type="password" id="password_confirm" name="password_confirm" required minlength="6" autocomplete="new-password" value="<?= htmlspecialchars($oldPasswordConfirm) ?>">
            <button type="button" class="toggle-pass" data-toggle-pass="password_confirm" aria-label="Hiện/ẩn mật khẩu">👁</button>
          </div>
        </div>

        <div class="role-note">
          <label class="form-check" style="gap:10px;">
            <input class="form-check-input" type="checkbox" value="1" id="is_landlord" name="is_landlord" style="width:18px;height:18px;" <?= $oldIsLandlord ? 'checked' : '' ?>>
            <span>
              <strong>Tôi là chủ trọ</strong><br>
              <small>Nếu chọn, vai trò sẽ là chủ trọ (đăng phòng, mua quan tâm).</small>
            </span>
          </label>
        </div>

        <button class="btn btn-primary w-100" type="submit" style="height:48px; font-size:15px;">Đăng ký</button>
      </form>

      <div class="auth-footer">
        Đã có tài khoản? <a class="alt-link" href="?route=login">Đăng nhập</a>
      </div>
    </div>
  </div>
</div>

<script>
  (() => {
    document.querySelectorAll('[data-toggle-pass]').forEach(btn => {
      btn.addEventListener('click', () => {
        const targetId = btn.getAttribute('data-toggle-pass');
        const input = document.getElementById(targetId);
        if (!input) return;
        const isPass = input.type === 'password';
        input.type = isPass ? 'text' : 'password';
        btn.textContent = isPass ? '🙈' : '👁';
        btn.setAttribute('aria-label', isPass ? 'Ẩn mật khẩu' : 'Hiện mật khẩu');
      });
    });
  })();
</script>
