<?php
  $pageTitle = 'Đăng nhập';
  $oldLoginPhone = $_POST['phone'] ?? '';
  $oldLoginPassword = $_POST['password'] ?? '';
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
    background: radial-gradient(120% 140% at 20% 20%, rgba(245,158,11,0.24), transparent 50%),
                radial-gradient(100% 120% at 90% 0%, rgba(59,130,246,0.16), transparent 52%),
                linear-gradient(135deg, #fbbf24, #d97706);
    color: #0f172a;
    padding: 22px;
    height: 100%;
    display: flex;
    flex-direction: column;
    gap: 14px;
  }
  .auth-hero h2 { margin: 0; font-size: 22px; letter-spacing: -0.3px; }
  .auth-hero p { margin: 0; color: #111827; opacity: 0.9; }
  .auth-hero ul { padding-left: 18px; margin: 6px 0 0 0; color: #0f172a; line-height: 1.5; font-weight: 600; }
  .auth-hero li { margin-bottom: 6px; }
  .auth-hero .badge-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: rgba(255,255,255,0.9);
    color: #7c2d12;
    padding: 8px 12px;
    border-radius: 999px;
    font-weight: 700;
    font-size: 13px;
    border: 1px solid rgba(255,255,255,0.7);
    box-shadow: 0 10px 22px rgba(124,45,18,0.18);
  }
  .auth-form {
    padding: 22px;
    display: flex;
    flex-direction: column;
    gap: 14px;
  }
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
  .auth-actions { display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap; }
  .alt-link { font-weight: 600; color: #b45309; }
  .auth-footer { text-align: center; color: var(--muted); font-size: 13px; }
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
  .ghost-chip {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
    padding: 10px 12px;
    border-radius: 12px;
    background: #f8fafc;
    border: 1px dashed #cbd5e1;
    color: #0f172a;
    font-weight: 600;
    margin-top: 4px;
  }
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
    .auth-actions {
      justify-content: flex-start !important;
      gap: 8px;
    }
    .alt-link {
      display: inline-flex;
      align-self: flex-start;
    }
    .field .input-wrap { padding: 10px; }
    .toggle-pass { padding: 8px 9px; }
  }
</style>

<div class="auth-shell">
  <div class="auth-panel auth-hero">
    <div class="badge-pill">⚡ Vào nhanh</div>
    <h2>Đăng nhập để tiếp tục</h2>
    <p>Kết nối nhanh với chủ trọ, theo dõi phòng đã lưu và nhận thông báo lịch xem phòng.</p>
    <ul>
      <li>Giữ nguyên quyền lợi theo vai trò bạn đã đăng ký.</li>
      <li>Lưu lại phòng yêu thích, xem lịch sử liên hệ.</li>
      <li>Đăng nhập an toàn bằng số điện thoại.</li>
    </ul>
    <div class="ghost-chip">💬 Zalo hỗ trợ: <strong>0383765225</strong></div>
  </div>

  <div class="auth-panel">
    <div class="auth-form">
      <div>
        <h1>Chào mừng trở lại</h1>
        <div class="subtitle">Nhập thông tin để vào PhòngTrọ.</div>
      </div>

      <form method="post" action="?route=login" class="d-flex flex-column gap-3">
        <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect ?? '') ?>">

        <div class="field">
          <label for="loginPhone">Số điện thoại</label>
          <div class="input-wrap">
            <span class="icon">📱</span>
            <input type="tel" id="loginPhone" name="phone" required placeholder="09xx xxx xxx" autocomplete="tel" value="<?= htmlspecialchars($oldLoginPhone) ?>">
          </div>
        </div>

        <div class="field">
          <label for="loginPassword">Mật khẩu</label>
          <div class="input-wrap">
            <span class="icon">🔒</span>
            <input type="password" id="loginPassword" name="password" required autocomplete="current-password" value="<?= htmlspecialchars($oldLoginPassword) ?>">
            <button type="button" class="toggle-pass" id="toggleLoginPass" aria-label="Hiện/ẩn mật khẩu">👁</button>
          </div>
        </div>

        <div class="auth-actions" style="justify-content:flex-end;">
          <a class="alt-link" href="?route=register">Chưa có tài khoản?</a>
        </div>

        <button class="btn btn-primary w-100" type="submit" style="height:48px; font-size:15px;">Đăng nhập</button>
      </form>

      <div class="auth-footer">
        Khi đăng nhập, bạn đồng ý với Điều khoản & Chính sách bảo mật của PhòngTrọ.
      </div>
    </div>
  </div>
</div>

<script>
  (() => {
    const btn = document.getElementById('toggleLoginPass');
    const input = document.getElementById('loginPassword');
    if (!btn || !input) return;
    btn.addEventListener('click', () => {
      const isPass = input.type === 'password';
      input.type = isPass ? 'text' : 'password';
      btn.textContent = isPass ? '🙈' : '👁';
      btn.setAttribute('aria-label', isPass ? 'Ẩn mật khẩu' : 'Hiện mật khẩu');
    });
  })();
</script>
