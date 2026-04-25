<?php $pageTitle = 'Quản lý người dùng'; ?>
<div class="admin-shell">
  <aside class="admin-menu">
    <a href="?route=admin" class="<?= ($activeMenu ?? '') === 'home' ? 'active' : '' ?>">Bảng điều khiển</a>
    <a href="?route=admin-users" class="<?= ($activeMenu ?? '') === 'users' ? 'active' : '' ?>">Người dùng</a>
    <a href="?route=admin-leads" class="<?= ($activeMenu ?? '') === 'leads' ? 'active' : '' ?>">Nhu cầu</a>
    <a href="?route=admin-payments" class="<?= ($activeMenu ?? '') === 'payments' ? 'active' : '' ?>">Giao dịch</a>
    <a href="?route=admin-reports" class="<?= ($activeMenu ?? '') === 'reports' ? 'active' : '' ?>">Báo cáo</a>
    <a href="?route=admin-settings" class="<?= ($activeMenu ?? '') === 'theme' ? 'active' : '' ?>">Cài đặt hệ thống</a>
    <a href="?route=admin-audit-logs" class="<?= ($activeMenu ?? '') === 'audit' ? 'active' : '' ?>">Nhật ký kiểm tra</a>
  </aside>
  <div>
    <div class="card">
      <div class="card-body table-responsive">
        <style>
          .admin-users-table th,
          .admin-users-table td {
            vertical-align: top;
          }
          .admin-users-table .col-actions {
            min-width: 280px;
          }
          .admin-user-main-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: nowrap;
          }
          .admin-user-main-actions .btn {
            min-height: 34px;
            white-space: nowrap;
          }
          .admin-user-expand-row[hidden] { display: none; }
          .admin-user-expand-row > td {
            padding: 0;
            border-top: none;
            background: #fffdf7;
          }
          .admin-user-expand {
            display: grid;
            grid-template-columns: minmax(260px, 1fr) minmax(360px, 1.6fr);
            gap: 12px;
            padding: 12px;
            border: 1px solid #f3e2c7;
            border-radius: 12px;
            margin: 8px 0 12px;
          }
          .admin-user-panel {
            border: 1px solid #e8ecf3;
            border-radius: 12px;
            background: #fff;
            padding: 10px;
          }
          .admin-user-panel.is-focus {
            border-color: #f59e0b;
            box-shadow: 0 0 0 1px rgba(245, 158, 11, 0.2);
          }
          .admin-user-panel h3 {
            margin: 0 0 8px;
            font-size: 14px;
          }
          .admin-user-detail-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 6px 10px;
            font-size: 13px;
          }
          .admin-user-perms {
            margin-top: 8px;
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
          }
          .admin-user-perms .badge {
            font-size: 11px;
          }
          .admin-user-manage-form {
            display: flex;
            flex-direction: column;
            gap: 10px;
          }
          .admin-user-manage-row {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px;
          }
          .admin-user-manage-row label {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 12px;
          }
          .admin-user-manage-row .form-control {
            max-width: 130px;
          }
          .admin-user-manage-note {
            font-size: 12px;
            color: #6b7280;
            margin: 0;
          }
          @media (max-width: 1100px) {
            .admin-user-expand {
              grid-template-columns: 1fr;
            }
          }
        </style>
        <h1 class="mb-2" style="font-size:20px;">Người dùng</h1>
        <?php
          $roleLabels = ['tenant' => 'Người thuê', 'landlord' => 'Chủ trọ', 'staff' => 'Nhân sự', 'admin' => 'Quản trị'];
          $statusLabels = ['active' => 'Hoạt động', 'locked' => 'Đã khóa'];
          $phoneVerifyLabels = [1 => 'Đã xác minh', 0 => 'Chưa xác minh'];
          $permissionLabels = [
              'lead_view' => 'Xem lead',
              'lead_manage' => 'Xử lý lead',
              'room_manage' => 'Vận hành phòng',
              'invoice_manage' => 'Hóa đơn',
              'deposit_manage' => 'Cọc',
          ];
        ?>
        <table class="table align-middle admin-users-table">
          <thead>
            <tr><th>Mã</th><th>Tên</th><th>SĐT</th><th>Vai trò</th><th>Trạng thái</th><th>Xác minh SĐT</th><th>Ngày</th><th class="col-actions">Thao tác</th></tr>
          </thead>
          <tbody>
            <?php foreach ($users as $u): ?>
            <?php $isPhoneVerified = !empty($u['phone_verified']); ?>
            <?php
              $scopePermissions = json_decode((string)($u['scope_permissions_json'] ?? ''), true);
              if (!is_array($scopePermissions)) {
                  $scopePermissions = staffPermissionDefaults();
              }
              $scopeLandlordId = (int)($u['scope_landlord_id'] ?? 0);
              $expandId = 'user-expand-' . (int)$u['id'];
              $enabledPermissions = [];
              foreach ($permissionLabels as $permKey => $permLabel) {
                  if (!empty($scopePermissions[$permKey])) {
                      $enabledPermissions[] = $permLabel;
                  }
              }
            ?>
            <tr>
              <td>#<?= (int)$u['id'] ?></td>
              <td><?= htmlspecialchars($u['name']) ?></td>
              <td><?= htmlspecialchars($u['phone']) ?></td>
              <td>
                <?= htmlspecialchars($roleLabels[$u['role']] ?? $u['role']) ?>
                <?php if (($u['role'] ?? '') === 'staff'): ?>
                  <div class="text-muted" style="font-size:12px; margin-top:4px;">Phạm vi chủ trọ: <?= $scopeLandlordId > 0 ? ('#' . $scopeLandlordId) : 'Chưa gán' ?></div>
                <?php endif; ?>
              </td>
              <td><?= htmlspecialchars($statusLabels[$u['status']] ?? $u['status']) ?></td>
              <td>
                <span class="badge <?= $isPhoneVerified ? 'badge-success' : 'badge-outline' ?>">
                  <?= htmlspecialchars($phoneVerifyLabels[$isPhoneVerified ? 1 : 0]) ?>
                </span>
              </td>
              <td><?= htmlspecialchars($u['created_at']) ?></td>
              <td>
                <div class="admin-user-main-actions">
                  <button class="btn btn-outline btn-sm js-user-expand-toggle" type="button" data-target="<?= htmlspecialchars($expandId) ?>" data-focus="detail" aria-expanded="false">Xem chi tiết</button>
                  <button class="btn btn-primary btn-sm js-user-expand-toggle" type="button" data-target="<?= htmlspecialchars($expandId) ?>" data-focus="manage" aria-expanded="false">Quản lý chỉnh sửa</button>
                </div>
              </td>
            </tr>
            <tr class="admin-user-expand-row" id="<?= htmlspecialchars($expandId) ?>" hidden>
              <td colspan="8">
                <div class="admin-user-expand">
                  <section class="admin-user-panel admin-user-detail-card" data-focus-panel="detail">
                    <h3>Chi tiết người dùng</h3>
                    <div class="admin-user-detail-grid">
                      <div><strong>Mã:</strong> #<?= (int)$u['id'] ?></div>
                      <div><strong>Ngày tạo:</strong> <?= htmlspecialchars((string)$u['created_at']) ?></div>
                      <div><strong>Vai trò:</strong> <?= htmlspecialchars($roleLabels[$u['role']] ?? (string)$u['role']) ?></div>
                      <div><strong>Trạng thái:</strong> <?= htmlspecialchars($statusLabels[$u['status']] ?? (string)$u['status']) ?></div>
                      <div><strong>Xác minh SĐT:</strong> <?= htmlspecialchars($phoneVerifyLabels[$isPhoneVerified ? 1 : 0]) ?></div>
                      <div><strong>Phạm vi chủ trọ:</strong> <?= $scopeLandlordId > 0 ? ('#' . $scopeLandlordId) : 'Chưa gán' ?></div>
                    </div>
                    <div class="admin-user-perms">
                      <?php if (empty($enabledPermissions)): ?>
                        <span class="badge badge-outline">Chưa cấp quyền vận hành</span>
                      <?php else: ?>
                        <?php foreach ($enabledPermissions as $permLabel): ?>
                          <span class="badge badge-success"><?= htmlspecialchars($permLabel) ?></span>
                        <?php endforeach; ?>
                      <?php endif; ?>
                    </div>
                  </section>
                  <section class="admin-user-panel admin-user-manage-card" data-focus-panel="manage">
                    <h3>Quản lý chỉnh sửa</h3>
                    <form method="post" action="?route=admin-user-action" class="admin-user-manage-form">
                      <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                      <input type="hidden" name="permissions_present" value="1">
                      <div class="admin-user-manage-row">
                        <button class="btn btn-outline-secondary btn-sm" name="action" value="lock" type="submit">Khóa tài khoản</button>
                        <button class="btn btn-outline btn-sm" name="action" value="unlock" type="submit">Mở khóa</button>
                        <?php if ($isPhoneVerified): ?>
                          <button class="btn btn-outline btn-sm" name="action" value="unverify-phone" type="submit">Bỏ xác minh SĐT</button>
                        <?php else: ?>
                          <button class="btn btn-outline btn-sm" name="action" value="verify-phone" type="submit">Xác minh SĐT</button>
                        <?php endif; ?>
                      </div>
                      <div class="admin-user-manage-row">
                        <button class="btn <?= ($u['role'] ?? '') === 'tenant' ? 'btn-primary' : 'btn-outline' ?> btn-sm" name="action" value="tenant" type="submit">Người thuê</button>
                        <button class="btn <?= ($u['role'] ?? '') === 'landlord' ? 'btn-primary' : 'btn-outline' ?> btn-sm" name="action" value="landlord" type="submit">Chủ trọ</button>
                        <button class="btn <?= ($u['role'] ?? '') === 'staff' ? 'btn-primary' : 'btn-outline' ?> btn-sm" name="action" value="staff" type="submit">Nhân sự</button>
                      </div>
                      <div class="admin-user-manage-row">
                        <input type="number" name="scope_landlord_id" class="form-control" min="1" step="1" value="<?= $scopeLandlordId > 0 ? $scopeLandlordId : '' ?>" placeholder="Mã chủ trọ">
                        <label><input type="checkbox" name="perm_lead_view" value="1" <?= !empty($scopePermissions['lead_view']) ? 'checked' : '' ?>>Xem lead</label>
                        <label><input type="checkbox" name="perm_lead_manage" value="1" <?= !empty($scopePermissions['lead_manage']) ? 'checked' : '' ?>>Xử lý lead</label>
                        <label><input type="checkbox" name="perm_room_manage" value="1" <?= !empty($scopePermissions['room_manage']) ? 'checked' : '' ?>>Vận hành phòng</label>
                        <label><input type="checkbox" name="perm_invoice_manage" value="1" <?= !empty($scopePermissions['invoice_manage']) ? 'checked' : '' ?>>Hóa đơn</label>
                        <label><input type="checkbox" name="perm_deposit_manage" value="1" <?= !empty($scopePermissions['deposit_manage']) ? 'checked' : '' ?>>Cọc</label>
                      </div>
                      <p class="admin-user-manage-note">Cập nhật phạm vi/quyền cho nhân sự rồi bấm lưu.</p>
                      <div class="admin-user-manage-row">
                        <button class="btn btn-outline-secondary btn-sm" name="action" value="update-staff-scope" type="submit">Lưu phạm vi & quyền</button>
                      </div>
                    </form>
                  </section>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <script>
          (() => {
            const toggleButtons = Array.from(document.querySelectorAll('.js-user-expand-toggle'));
            if (!toggleButtons.length) return;
            const rows = Array.from(document.querySelectorAll('.admin-user-expand-row'));

            const setRowOpen = (row, open) => {
              row.hidden = !open;
              const rowId = row.id;
              toggleButtons
                .filter((btn) => btn.dataset.target === rowId)
                .forEach((btn) => btn.setAttribute('aria-expanded', open ? 'true' : 'false'));
              if (!open) {
                row.dataset.focus = '';
                row.querySelectorAll('[data-focus-panel]').forEach((panel) => panel.classList.remove('is-focus'));
              }
            };

            const setFocusPanel = (row, focus) => {
              row.dataset.focus = focus;
              row.querySelectorAll('[data-focus-panel]').forEach((panel) => {
                panel.classList.toggle('is-focus', panel.dataset.focusPanel === focus);
              });
            };

            toggleButtons.forEach((button) => {
              button.addEventListener('click', () => {
                const rowId = button.dataset.target || '';
                const focus = button.dataset.focus || 'detail';
                const row = document.getElementById(rowId);
                if (!row) return;

                const alreadyOpen = !row.hidden;
                const sameFocus = row.dataset.focus === focus;

                rows.forEach((otherRow) => {
                  if (otherRow !== row) setRowOpen(otherRow, false);
                });

                if (alreadyOpen && sameFocus) {
                  setRowOpen(row, false);
                  return;
                }

                setRowOpen(row, true);
                setFocusPanel(row, focus);
              });
            });
          })();
        </script>
      </div>
    </div>
  </div>
</div>

