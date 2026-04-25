<?php $pageTitle = 'Lịch sử thanh toán'; ?>
<style>
  .payment-detail-row { display: none; }
  .payment-detail-row.open { display: table-row; }
  .payment-guide {
    display: grid;
    grid-template-columns: minmax(260px, 1fr) minmax(220px, 320px);
    gap: 16px;
    padding: 14px;
    border: 1px dashed #f59e0b;
    border-radius: 12px;
    background: #fffaf0;
  }
  .payment-guide h3 {
    margin: 0 0 10px;
    font-size: 15px;
    color: #92400e;
  }
  .payment-live-notice {
    display: none;
    margin: 0 0 14px;
    padding: 12px 14px;
    border-radius: 10px;
    border: 1px solid #86efac;
    background: #f0fdf4;
    color: #166534;
    font-weight: 700;
  }
  .payment-live-notice.show { display: block; }
  .payment-info {
    display: grid;
    grid-template-columns: 120px 1fr;
    gap: 8px 12px;
    align-items: center;
  }
  .payment-info .label { color: #6b7280; font-size: 13px; }
  .payment-copy {
    display: inline-flex;
    align-items: center;
    min-height: 32px;
    padding: 6px 10px;
    border-radius: 8px;
    background: #fff;
    border: 1px solid #fde68a;
    font-weight: 800;
    color: #111827;
  }
  .qr-panel {
    text-align: center;
  }
  .qr-panel img {
    width: 180px;
    max-width: 100%;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    background: #fff;
  }
  .payment-state-box {
    padding: 14px;
    border-radius: 12px;
    border: 1px solid #d1fae5;
    background: #f0fdf4;
    color: #166534;
    font-weight: 700;
  }
  .payment-state-box.is-failed {
    border-color: #fde68a;
    background: #fffbeb;
    color: #92400e;
  }
  .payment-history-table th,
  .payment-history-table td {
    word-break: keep-all;
    overflow-wrap: normal;
    white-space: nowrap;
  }
  .payment-history-table .payment-cell-room {
    max-width: 220px;
    overflow: hidden;
    text-overflow: ellipsis;
  }
  @media (max-width: 768px) {
    .payment-guide { grid-template-columns: 1fr; }
    .payment-info { grid-template-columns: 1fr; }
    .payment-history-table thead th,
    .payment-history-table tbody tr[data-payment-id] > td {
      font-size: 13px;
      padding: 8px 6px;
    }
    .payment-history-table thead th:nth-child(2),
    .payment-history-table thead th:nth-child(3),
    .payment-history-table thead th:nth-child(5),
    .payment-history-table thead th:nth-child(6),
    .payment-history-table thead th:nth-child(7),
    .payment-history-table thead th:nth-child(9),
    .payment-history-table thead th:nth-child(10),
    .payment-history-table tbody tr[data-payment-id] > td:nth-child(2),
    .payment-history-table tbody tr[data-payment-id] > td:nth-child(3),
    .payment-history-table tbody tr[data-payment-id] > td:nth-child(5),
    .payment-history-table tbody tr[data-payment-id] > td:nth-child(6),
    .payment-history-table tbody tr[data-payment-id] > td:nth-child(7),
    .payment-history-table tbody tr[data-payment-id] > td:nth-child(9),
    .payment-history-table tbody tr[data-payment-id] > td:nth-child(10) {
      display: none;
    }
    .payment-history-table thead th:nth-child(1),
    .payment-history-table tbody tr[data-payment-id] > td:nth-child(1) { width: 16%; }
    .payment-history-table thead th:nth-child(4),
    .payment-history-table tbody tr[data-payment-id] > td:nth-child(4) { width: 30%; }
    .payment-history-table thead th:nth-child(8),
    .payment-history-table tbody tr[data-payment-id] > td:nth-child(8) { width: 24%; }
    .payment-history-table thead th:nth-child(11),
    .payment-history-table tbody tr[data-payment-id] > td:nth-child(11) { width: 30%; }
    .payment-history-table tbody tr.payment-detail-row > td {
      white-space: normal;
      word-break: normal;
      overflow-wrap: break-word;
    }
  }
</style>
<div class="admin-shell">
  <aside class="admin-menu">
    <a href="?route=dashboard" class="<?= ($activeMenu ?? '') === 'home' ? 'active' : '' ?>">Tổng quan</a>
    <a href="?route=my-rooms" class="<?= ($activeMenu ?? '') === 'rooms' ? 'active' : '' ?>">Vận hành trọ</a>
    <a href="?route=dashboard&tab=lead#lead" class="<?= ($activeMenu ?? '') === 'leads' ? 'active' : '' ?>">Quan tâm</a>
    <a href="?route=payment-history" class="<?= ($activeMenu ?? '') === 'payments' ? 'active' : '' ?>">Thanh toán</a>
  </aside>
  <div>
    <div class="card">
      <div class="card-body table-responsive">
        <h1 class="mb-2" style="font-size:20px;">Lịch sử thanh toán</h1>
        <div id="paymentLiveNotice" class="payment-live-notice"></div>
        <?php $bankInfo = sepayBankInfo(); ?>
        <?php $focusPaymentId = (int)($focusPaymentId ?? 0); ?>
        <p class="text-muted mb-3">
          Chuyển khoản đúng số tiền và nội dung để SePay tự xác nhận.
          TK nhận: <?= htmlspecialchars($bankInfo['bank']) ?> - <?= htmlspecialchars($bankInfo['account']) ?> - <?= htmlspecialchars($bankInfo['name']) ?>.
        </p>
        <?php
          $payStatus = ['paid' => 'Thành công', 'failed' => 'Chưa thành công', 'pending' => 'Chưa thành công'];
          $payType = ['lead' => 'Mở quan tâm', 'package' => 'Gói dịch vụ'];
        ?>
        <table class="table align-middle payment-history-table">
          <thead>
            <tr>
              <th>Mã</th>
              <th>Quan tâm</th>
              <th>Phòng</th>
              <th>Số tiền</th>
              <th>Nội dung CK</th>
              <th>Loại</th>
              <th>Cổng</th>
              <th>Trạng thái</th>
              <th>Ngày</th>
              <th>Hạn QR</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($payments)): ?>
              <tr><td colspan="11">Chưa có giao dịch.</td></tr>
            <?php endif; ?>
            <?php foreach ($payments as $p): ?>
            <?php
              $paymentCode = (string)($p['payment_code'] ?? '');
              $expiresAt = trim((string)($p['expires_at'] ?? ''));
              $expiresTs = $expiresAt !== '' ? strtotime($expiresAt) : false;
              $isExpired = ($p['status'] ?? '') !== 'paid' && $expiresTs !== false && $expiresTs <= time();
              $isPending = ($p['status'] ?? '') === 'pending' && !$isExpired && $paymentCode !== '';
              $qrUrl = $isPending ? sepayQrUrl((int)$p['amount'], $paymentCode) : '';
              $detailId = 'payment-detail-' . (int)$p['id'];
              $isFocused = $focusPaymentId > 0 && (int)$p['id'] === $focusPaymentId && $qrUrl !== '';
              $statusLabel = $isExpired ? 'Hết hạn' : ($payStatus[$p['status']] ?? $p['status']);
            ?>
            <tr id="payment-row-<?= (int)$p['id'] ?>" data-payment-id="<?= (int)$p['id'] ?>" data-payment-state="<?= htmlspecialchars((string)($p['status'] ?? '')) ?>">
              <td>#<?= (int)$p['id'] ?></td>
              <td><?= $p['lead_id'] ? '#'.(int)$p['lead_id'] : '-' ?></td>
              <td class="payment-cell-room"><?= htmlspecialchars($p['room_title'] ?? '-') ?></td>
              <td><?= number_format((int)$p['amount'], 0, ',', '.') ?> đ</td>
              <td><?= $paymentCode !== '' ? '<strong>' . htmlspecialchars($paymentCode) . '</strong>' : '-' ?></td>
              <td><?= htmlspecialchars($payType[$p['type']] ?? $p['type']) ?></td>
              <td data-role="payment-provider"><?= htmlspecialchars($p['provider']) ?></td>
              <td data-role="payment-status"><?= htmlspecialchars($statusLabel) ?></td>
              <td><?= htmlspecialchars($p['created_at']) ?></td>
              <td data-role="payment-expires"><?= $expiresAt !== '' ? htmlspecialchars($expiresAt) : '-' ?></td>
              <td data-role="payment-action">
                <?php if ($qrUrl !== ''): ?>
                  <button type="button" class="btn btn-outline btn-sm payment-detail-toggle" data-target="<?= htmlspecialchars($detailId) ?>"><?= $isFocused ? 'Thu gọn' : 'Chi tiết' ?></button>
                <?php elseif ($isExpired): ?>
                  <span class="text-muted small">Hết hạn</span>
                <?php else: ?>
                  <span class="text-muted small">-</span>
                <?php endif; ?>
              </td>
            </tr>
            <?php if ($qrUrl !== ''): ?>
            <tr id="<?= htmlspecialchars($detailId) ?>" class="payment-detail-row<?= $isFocused ? ' open' : '' ?>" data-detail-for="<?= (int)$p['id'] ?>">
              <td colspan="11">
                <div class="payment-guide" data-role="payment-detail-body">
                  <div>
                    <h3>Cách 1: Chuyển khoản thủ công</h3>
                    <div class="payment-info">
                      <div class="label">Ngân hàng</div>
                      <div class="payment-copy"><?= htmlspecialchars($bankInfo['bank']) ?></div>
                      <div class="label">Số tài khoản</div>
                      <div class="payment-copy"><?= htmlspecialchars($bankInfo['account']) ?></div>
                      <div class="label">Tên tài khoản</div>
                      <div class="payment-copy"><?= htmlspecialchars($bankInfo['name']) ?></div>
                      <div class="label">Số tiền</div>
                      <div class="payment-copy"><?= number_format((int)$p['amount'], 0, ',', '.') ?> đ</div>
                      <div class="label">Nội dung</div>
                      <div class="payment-copy"><?= htmlspecialchars($paymentCode) ?></div>
                      <div class="label">Hiệu lực đến</div>
                      <div class="payment-copy"><?= htmlspecialchars($expiresAt) ?></div>
                    </div>
                    <p class="text-muted small" style="margin:10px 0 0;">Nhập đúng số tiền và đúng nội dung. QR hết hạn sau 15 phút.</p>
                    <p class="text-muted small payment-countdown" data-expires-at="<?= htmlspecialchars($expiresAt) ?>" style="margin:6px 0 0;"></p>
                  </div>
                  <div class="qr-panel">
                    <h3>Cách 2: Quét mã QR</h3>
                    <img src="<?= htmlspecialchars($qrUrl) ?>" alt="QR thanh toán <?= htmlspecialchars($paymentCode) ?>">
                    <p class="text-muted small" style="margin:10px 0 0;">Quét QR rồi thanh toán, không sửa nội dung chuyển khoản.</p>
                  </div>
                </div>
              </td>
            </tr>
            <?php endif; ?>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<script>
  const paymentLiveNotice = document.getElementById('paymentLiveNotice');
  const showPaymentNotice = (message) => {
    if (!paymentLiveNotice || !message) return;
    paymentLiveNotice.textContent = message;
    paymentLiveNotice.classList.add('show');
  };

  document.querySelectorAll('.payment-detail-toggle').forEach((btn) => {
    btn.addEventListener('click', () => {
      const row = document.getElementById(btn.dataset.target || '');
      if (!row) return;
      const isOpen = row.classList.toggle('open');
      btn.textContent = isOpen ? 'Thu gọn' : 'Chi tiết';
    });
  });

  const updateCountdowns = () => {
    document.querySelectorAll('.payment-countdown[data-expires-at]').forEach((el) => {
      const raw = el.dataset.expiresAt || '';
      const expires = Date.parse(raw.replace(' ', 'T'));
      if (!expires) return;
      const left = Math.max(0, Math.floor((expires - Date.now()) / 1000));
      if (left <= 0) {
        el.textContent = 'Mã QR đã hết hạn. Tạo lại yêu cầu mua nhu cầu để lấy nội dung mới.';
        return;
      }
      const minutes = Math.floor(left / 60);
      const seconds = left % 60;
      el.textContent = `Còn hiệu lực ${minutes}:${String(seconds).padStart(2, '0')}.`;
    });
  };
  updateCountdowns();
  setInterval(updateCountdowns, 1000);

  const focusedPayment = document.querySelector('.payment-detail-row.open');
  if (focusedPayment && focusedPayment.previousElementSibling) {
    focusedPayment.previousElementSibling.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }

  const renderDetailState = (paymentId, status, statusLabel) => {
    const detailRow = document.querySelector(`.payment-detail-row[data-detail-for="${paymentId}"]`);
    if (!detailRow) return;
    const body = detailRow.querySelector('[data-role="payment-detail-body"]');
    if (!body) return;
    const isPaid = status === 'paid';
    body.innerHTML = `
      <div class="payment-state-box${isPaid ? '' : ' is-failed'}">
        ${isPaid ? 'Thanh toán thành công. SePay đã xác nhận giao dịch và nhu cầu sẽ được mở tự động.' : `Giao dịch ${statusLabel.toLowerCase()}. QR này không còn dùng được nữa.`}
      </div>
    `;
    detailRow.classList.add('open');
  };

  const pollPaymentStatuses = async () => {
    const rows = Array.from(document.querySelectorAll('tr[data-payment-id][data-payment-state="pending"]'));
    if (!rows.length) return;
    const ids = rows.map((row) => row.dataset.paymentId).filter(Boolean);
    if (!ids.length) return;

    try {
      const res = await fetch(`?route=payment-history&ajax=status&ids=${encodeURIComponent(ids.join(','))}`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      });
      const data = await res.json();
      if (!data || !data.ok || !Array.isArray(data.payments)) return;

      data.payments.forEach((payment) => {
        const row = document.querySelector(`tr[data-payment-id="${payment.id}"]`);
        if (!row) return;

        const prevState = row.dataset.paymentState || '';
        row.dataset.paymentState = payment.status || '';

        const statusCell = row.querySelector('[data-role="payment-status"]');
        if (statusCell) statusCell.textContent = payment.status_label || payment.status || '';

        const providerCell = row.querySelector('[data-role="payment-provider"]');
        if (providerCell) providerCell.textContent = payment.provider || '';

        const expiresCell = row.querySelector('[data-role="payment-expires"]');
        if (expiresCell) expiresCell.textContent = payment.expires_at || '-';

        const actionCell = row.querySelector('[data-role="payment-action"]');
        if (actionCell && !payment.can_show_qr) {
          if (payment.status === 'paid') {
            actionCell.innerHTML = '<span class="text-success small">Đã xác nhận</span>';
          } else if ((payment.status_label || '') === 'Hết hạn') {
            actionCell.innerHTML = '<span class="text-muted small">Hết hạn</span>';
          } else {
            actionCell.innerHTML = '<span class="text-muted small">-</span>';
          }
        }

        if (!payment.can_show_qr) {
          renderDetailState(payment.id, payment.status || '', payment.status_label || '');
        }

        if (prevState !== 'paid' && payment.status === 'paid') {
          showPaymentNotice(`Thanh toán ${payment.payment_code || ('#' + payment.id)} đã thành công. SĐT nhu cầu sẽ được mở tự động.`);
        }
      });
    } catch (error) {
      // Keep polling silent; user can still refresh manually if network hiccups.
    }
  };

  pollPaymentStatuses();
  setInterval(pollPaymentStatuses, 5000);
</script>
GET['focus'] ?? '') === 'issues') ? 'active' : '' ?>">Sự cố</a>
    <a href="?route=dashboard" class="<?= ($activeMenu ?? '') === 'home' ? 'active' : '' ?>">Báo cáo</a>
  </aside>
  <div>
    <div class="card">
      <div class="card-body table-responsive">
        <h1 class="mb-2" style="font-size:20px;">Lịch sử thanh toán</h1>
        <div id="paymentLiveNotice" class="payment-live-notice"></div>
        <?php $bankInfo = sepayBankInfo(); ?>
        <?php $focusPaymentId = (int)($focusPaymentId ?? 0); ?>
        <p class="text-muted mb-3">
          Chuyển khoản đúng số tiền và nội dung để SePay tự xác nhận.
          TK nhận: <?= htmlspecialchars($bankInfo['bank']) ?> - <?= htmlspecialchars($bankInfo['account']) ?> - <?= htmlspecialchars($bankInfo['name']) ?>.
        </p>
        <?php
          $payStatus = ['paid' => 'Thành công', 'failed' => 'Chưa thành công', 'pending' => 'Chưa thành công'];
          $payType = ['lead' => 'Mở quan tâm', 'package' => 'Gói dịch vụ'];
        ?>
        <table class="table align-middle payment-history-table">
          <thead>
            <tr>
              <th>Mã</th>
              <th>Quan tâm</th>
              <th>Phòng</th>
              <th>Số tiền</th>
              <th>Nội dung CK</th>
              <th>Loại</th>
              <th>Cổng</th>
              <th>Trạng thái</th>
              <th>Ngày</th>
              <th>Hạn QR</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($payments)): ?>
              <tr><td colspan="11">Chưa có giao dịch.</td></tr>
            <?php endif; ?>
            <?php foreach ($payments as $p): ?>
            <?php
              $paymentCode = (string)($p['payment_code'] ?? '');
              $expiresAt = trim((string)($p['expires_at'] ?? ''));
              $expiresTs = $expiresAt !== '' ? strtotime($expiresAt) : false;
              $isExpired = ($p['status'] ?? '') !== 'paid' && $expiresTs !== false && $expiresTs <= time();
              $isPending = ($p['status'] ?? '') === 'pending' && !$isExpired && $paymentCode !== '';
              $qrUrl = $isPending ? sepayQrUrl((int)$p['amount'], $paymentCode) : '';
              $detailId = 'payment-detail-' . (int)$p['id'];
              $isFocused = $focusPaymentId > 0 && (int)$p['id'] === $focusPaymentId && $qrUrl !== '';
              $statusLabel = $isExpired ? 'Hết hạn' : ($payStatus[$p['status']] ?? $p['status']);
            ?>
            <tr id="payment-row-<?= (int)$p['id'] ?>" data-payment-id="<?= (int)$p['id'] ?>" data-payment-state="<?= htmlspecialchars((string)($p['status'] ?? '')) ?>">
              <td>#<?= (int)$p['id'] ?></td>
              <td><?= $p['lead_id'] ? '#'.(int)$p['lead_id'] : '-' ?></td>
              <td class="payment-cell-room"><?= htmlspecialchars($p['room_title'] ?? '-') ?></td>
              <td><?= number_format((int)$p['amount'], 0, ',', '.') ?> đ</td>
              <td><?= $paymentCode !== '' ? '<strong>' . htmlspecialchars($paymentCode) . '</strong>' : '-' ?></td>
              <td><?= htmlspecialchars($payType[$p['type']] ?? $p['type']) ?></td>
              <td data-role="payment-provider"><?= htmlspecialchars($p['provider']) ?></td>
              <td data-role="payment-status"><?= htmlspecialchars($statusLabel) ?></td>
              <td><?= htmlspecialchars($p['created_at']) ?></td>
              <td data-role="payment-expires"><?= $expiresAt !== '' ? htmlspecialchars($expiresAt) : '-' ?></td>
              <td data-role="payment-action">
                <?php if ($qrUrl !== ''): ?>
                  <button type="button" class="btn btn-outline btn-sm payment-detail-toggle" data-target="<?= htmlspecialchars($detailId) ?>"><?= $isFocused ? 'Thu gọn' : 'Chi tiết' ?></button>
                <?php elseif ($isExpired): ?>
                  <span class="text-muted small">Hết hạn</span>
                <?php else: ?>
                  <span class="text-muted small">-</span>
                <?php endif; ?>
              </td>
            </tr>
            <?php if ($qrUrl !== ''): ?>
            <tr id="<?= htmlspecialchars($detailId) ?>" class="payment-detail-row<?= $isFocused ? ' open' : '' ?>" data-detail-for="<?= (int)$p['id'] ?>">
              <td colspan="11">
                <div class="payment-guide" data-role="payment-detail-body">
                  <div>
                    <h3>Cách 1: Chuyển khoản thủ công</h3>
                    <div class="payment-info">
                      <div class="label">Ngân hàng</div>
                      <div class="payment-copy"><?= htmlspecialchars($bankInfo['bank']) ?></div>
                      <div class="label">Số tài khoản</div>
                      <div class="payment-copy"><?= htmlspecialchars($bankInfo['account']) ?></div>
                      <div class="label">Tên tài khoản</div>
                      <div class="payment-copy"><?= htmlspecialchars($bankInfo['name']) ?></div>
                      <div class="label">Số tiền</div>
                      <div class="payment-copy"><?= number_format((int)$p['amount'], 0, ',', '.') ?> đ</div>
                      <div class="label">Nội dung</div>
                      <div class="payment-copy"><?= htmlspecialchars($paymentCode) ?></div>
                      <div class="label">Hiệu lực đến</div>
                      <div class="payment-copy"><?= htmlspecialchars($expiresAt) ?></div>
                    </div>
                    <p class="text-muted small" style="margin:10px 0 0;">Nhập đúng số tiền và đúng nội dung. QR hết hạn sau 15 phút.</p>
                    <p class="text-muted small payment-countdown" data-expires-at="<?= htmlspecialchars($expiresAt) ?>" style="margin:6px 0 0;"></p>
                  </div>
                  <div class="qr-panel">
                    <h3>Cách 2: Quét mã QR</h3>
                    <img src="<?= htmlspecialchars($qrUrl) ?>" alt="QR thanh toán <?= htmlspecialchars($paymentCode) ?>">
                    <p class="text-muted small" style="margin:10px 0 0;">Quét QR rồi thanh toán, không sửa nội dung chuyển khoản.</p>
                  </div>
                </div>
              </td>
            </tr>
            <?php endif; ?>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<script>
  const paymentLiveNotice = document.getElementById('paymentLiveNotice');
  const showPaymentNotice = (message) => {
    if (!paymentLiveNotice || !message) return;
    paymentLiveNotice.textContent = message;
    paymentLiveNotice.classList.add('show');
  };

  document.querySelectorAll('.payment-detail-toggle').forEach((btn) => {
    btn.addEventListener('click', () => {
      const row = document.getElementById(btn.dataset.target || '');
      if (!row) return;
      const isOpen = row.classList.toggle('open');
      btn.textContent = isOpen ? 'Thu gọn' : 'Chi tiết';
    });
  });

  const updateCountdowns = () => {
    document.querySelectorAll('.payment-countdown[data-expires-at]').forEach((el) => {
      const raw = el.dataset.expiresAt || '';
      const expires = Date.parse(raw.replace(' ', 'T'));
      if (!expires) return;
      const left = Math.max(0, Math.floor((expires - Date.now()) / 1000));
      if (left <= 0) {
        el.textContent = 'Mã QR đã hết hạn. Tạo lại yêu cầu mua nhu cầu để lấy nội dung mới.';
        return;
      }
      const minutes = Math.floor(left / 60);
      const seconds = left % 60;
      el.textContent = `Còn hiệu lực ${minutes}:${String(seconds).padStart(2, '0')}.`;
    });
  };
  updateCountdowns();
  setInterval(updateCountdowns, 1000);

  const focusedPayment = document.querySelector('.payment-detail-row.open');
  if (focusedPayment && focusedPayment.previousElementSibling) {
    focusedPayment.previousElementSibling.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }

  const renderDetailState = (paymentId, status, statusLabel) => {
    const detailRow = document.querySelector(`.payment-detail-row[data-detail-for="${paymentId}"]`);
    if (!detailRow) return;
    const body = detailRow.querySelector('[data-role="payment-detail-body"]');
    if (!body) return;
    const isPaid = status === 'paid';
    body.innerHTML = `
      <div class="payment-state-box${isPaid ? '' : ' is-failed'}">
        ${isPaid ? 'Thanh toán thành công. SePay đã xác nhận giao dịch và nhu cầu sẽ được mở tự động.' : `Giao dịch ${statusLabel.toLowerCase()}. QR này không còn dùng được nữa.`}
      </div>
    `;
    detailRow.classList.add('open');
  };

  const pollPaymentStatuses = async () => {
    const rows = Array.from(document.querySelectorAll('tr[data-payment-id][data-payment-state="pending"]'));
    if (!rows.length) return;
    const ids = rows.map((row) => row.dataset.paymentId).filter(Boolean);
    if (!ids.length) return;

    try {
      const res = await fetch(`?route=payment-history&ajax=status&ids=${encodeURIComponent(ids.join(','))}`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      });
      const data = await res.json();
      if (!data || !data.ok || !Array.isArray(data.payments)) return;

      data.payments.forEach((payment) => {
        const row = document.querySelector(`tr[data-payment-id="${payment.id}"]`);
        if (!row) return;

        const prevState = row.dataset.paymentState || '';
        row.dataset.paymentState = payment.status || '';

        const statusCell = row.querySelector('[data-role="payment-status"]');
        if (statusCell) statusCell.textContent = payment.status_label || payment.status || '';

        const providerCell = row.querySelector('[data-role="payment-provider"]');
        if (providerCell) providerCell.textContent = payment.provider || '';

        const expiresCell = row.querySelector('[data-role="payment-expires"]');
        if (expiresCell) expiresCell.textContent = payment.expires_at || '-';

        const actionCell = row.querySelector('[data-role="payment-action"]');
        if (actionCell && !payment.can_show_qr) {
          if (payment.status === 'paid') {
            actionCell.innerHTML = '<span class="text-success small">Đã xác nhận</span>';
          } else if ((payment.status_label || '') === 'Hết hạn') {
            actionCell.innerHTML = '<span class="text-muted small">Hết hạn</span>';
          } else {
            actionCell.innerHTML = '<span class="text-muted small">-</span>';
          }
        }

        if (!payment.can_show_qr) {
          renderDetailState(payment.id, payment.status || '', payment.status_label || '');
        }

        if (prevState !== 'paid' && payment.status === 'paid') {
          showPaymentNotice(`Thanh toán ${payment.payment_code || ('#' + payment.id)} đã thành công. SĐT nhu cầu sẽ được mở tự động.`);
        }
      });
    } catch (error) {
      // Keep polling silent; user can still refresh manually if network hiccups.
    }
  };

  pollPaymentStatuses();
  setInterval(pollPaymentStatuses, 5000);
</script>

