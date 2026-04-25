<?php $pageTitle = 'Nhật ký kiểm tra'; ?>
<div class="admin-shell">
  <aside class="admin-menu">
    <a href="?route=admin" class="<?= ($activeMenu ?? '') === 'home' ? 'active' : '' ?>">Bảng điều khiển</a>
    <a href="?route=admin-users" class="<?= ($activeMenu ?? '') === 'users' ? 'active' : '' ?>">Người dùng</a>
    <a href="?route=admin-leads" class="<?= ($activeMenu ?? '') === 'leads' ? 'active' : '' ?>">Nhu cầu</a>
    <a href="?route=admin-payments" class="<?= ($activeMenu ?? '') === 'payments' ? 'active' : '' ?>">Giao dịch</a>
    <a href="?route=admin-reports">Báo cáo</a>
    <a href="?route=admin-settings">Cài đặt hệ thống</a>
    <a href="?route=admin-audit-logs" class="<?= ($activeMenu ?? '') === 'audit' ? 'active' : '' ?>">Nhật ký kiểm tra</a>
  </aside>

  <div>
    <div class="card">
      <div class="card-body table-responsive">
        <div class="d-flex justify-between align-items-center mb-2">
          <div>
            <h1 class="mb-1" style="font-size:20px;">Nhật ký kiểm tra</h1>
            <p class="text-muted mb-0">Truy vết thao tác hệ thống gần nhất, bao gồm actor, hành động và đối tượng bị tác động.</p>
          </div>
          <a class="btn btn-outline btn-sm" href="?route=admin-audit-logs&limit=500">Xem 500 bản ghi</a>
        </div>

        <table class="table align-middle">
          <thead>
            <tr>
              <th>#</th>
              <th>Thời gian</th>
              <th>Actor</th>
              <th>Action</th>
              <th>Entity</th>
              <th>Route / IP</th>
              <th>Metadata</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($logs)): ?>
              <tr>
                <td colspan="7">Chưa có dữ liệu nhật ký kiểm tra.</td>
              </tr>
            <?php endif; ?>
            <?php foreach ($logs as $log): ?>
              <?php
                $metadata = trim((string)($log['metadata_json'] ?? ''));
                if ($metadata !== '' && strlen($metadata) > 220) {
                    $metadata = substr($metadata, 0, 220) . '...';
                }
                $actor = trim((string)($log['actor_name'] ?? ''));
                $actorPhone = trim((string)($log['actor_phone'] ?? ''));
                $actorRole = trim((string)($log['actor_role'] ?? ''));
                $entityType = trim((string)($log['entity_type'] ?? ''));
                $entityId = trim((string)($log['entity_id'] ?? ''));
                $route = trim((string)($log['route'] ?? ''));
                $ip = trim((string)($log['ip_address'] ?? ''));
              ?>
              <tr>
                <td>#<?= (int)($log['id'] ?? 0) ?></td>
                <td><?= htmlspecialchars((string)($log['created_at'] ?? '')) ?></td>
                <td>
                  <div><strong><?= htmlspecialchars($actor !== '' ? $actor : 'Hệ thống') ?></strong></div>
                  <div class="text-muted small">
                    <?= htmlspecialchars($actorRole !== '' ? $actorRole : 'n/a') ?>
                    <?php if ($actorPhone !== ''): ?> · <?= htmlspecialchars($actorPhone) ?><?php endif; ?>
                  </div>
                </td>
                <td><?= htmlspecialchars((string)($log['action'] ?? '')) ?></td>
                <td>
                  <div><?= htmlspecialchars($entityType !== '' ? $entityType : 'n/a') ?></div>
                  <div class="text-muted small"><?= htmlspecialchars($entityId !== '' ? $entityId : '-') ?></div>
                </td>
                <td>
                  <div class="small"><?= htmlspecialchars($route !== '' ? $route : '-') ?></div>
                  <div class="text-muted small"><?= htmlspecialchars($ip !== '' ? $ip : '-') ?></div>
                </td>
                <td class="small"><?= htmlspecialchars($metadata !== '' ? $metadata : '-') ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
