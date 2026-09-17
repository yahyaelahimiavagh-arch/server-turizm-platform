<?php

declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

$user = require_login();
if (($user['role'] ?? '') === 'admin') {
    redirect('admin/dashboard.php');
}

$leaveRepo = new LeaveRepository(db());
$rows = $leaveRepo->userRequests((int) $user['id']);
$success = flash('success');

$pageTitle = 'İzinlerim';
require __DIR__ . '/templates/header.php';
?>
<div class="page-head">
    <div><h1>İzinlerim</h1><p>Tüm izin talepleriniz ve durumları.</p></div>
    <a class="btn btn-gold" href="<?= e(base_path('leave-new.php')) ?>">Yeni İzin Talebi</a>
</div>
<?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
<section class="card">
    <div class="table-wrap">
        <table>
            <thead><tr><th>Tür</th><th>Tarih</th><th>Süre</th><th>Gün</th><th>Durum</th><th>Yönetici Notu</th></tr></thead>
            <tbody>
            <?php if (!$rows): ?><tr><td colspan="6">Henüz izin talebiniz yok.</td></tr><?php endif; ?>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= e($row['leave_type_name']) ?></td>
                    <td><?= e($row['start_date']) ?><?= $row['start_date'] !== $row['end_date'] ? ' — ' . e($row['end_date']) : '' ?></td>
                    <td><?= $row['duration_type'] === 'half_day' ? 'Yarım Gün' : 'Tam Gün' ?></td>
                    <td><?= e(format_days((float) $row['requested_days'])) ?></td>
                    <td><span class="badge <?= e(status_badge_class($row['status'])) ?>"><?= e(status_label($row['status'])) ?></span></td>
                    <td><?= e($row['admin_note'] ?: '—') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require __DIR__ . '/templates/footer.php'; ?>
