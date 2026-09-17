<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

$admin = require_admin();
$leaveRepo = new LeaveRepository(db());
$counts = $leaveRepo->dashboardCounts();
$pending = array_slice($leaveRepo->pendingRequests(), 0, 8);
$success = flash('success');
$error = flash('error');

$pageTitle = 'Yönetim Dashboard';
require dirname(__DIR__) . '/templates/header.php';
?>
<div class="page-head">
    <div><h1>Yönetim Dashboard</h1><p>Server Turizm izin yönetimi genel görünüm.</p></div>
    <a class="btn btn-light" href="<?= e(base_path('calendar.php')) ?>">Takvimi Aç</a>
</div>

<?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

<div class="grid grid-4">
    <div class="card stat-card"><div class="label">Toplam Çalışan</div><div class="value"><?= e((string) $counts['employees']) ?></div></div>
    <div class="card stat-card"><div class="label">Bekleyen Talepler</div><div class="value"><?= e((string) $counts['pending']) ?></div></div>
    <div class="card stat-card"><div class="label">Bugün İzinli</div><div class="value"><?= e((string) $counts['today']) ?></div></div>
    <div class="card stat-card"><div class="label">Bu Ay Kullanılan İzin</div><div class="value"><?= e(format_days((float) $counts['month_used'])) ?> Gün</div></div>
</div>

<section class="card mt-24">
    <div class="page-head">
        <div><h2 class="section-title">Bekleyen İzin Talepleri</h2></div>
        <a class="btn btn-light" href="<?= e(base_path('admin/requests.php')) ?>">Tümünü Gör</a>
    </div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Çalışan</th><th>Tür</th><th>Tarih</th><th>Gün</th><th>İşlem</th></tr></thead>
            <tbody>
            <?php if (!$pending): ?><tr><td colspan="5">Bekleyen talep yok.</td></tr><?php endif; ?>
            <?php foreach ($pending as $row): ?>
                <tr>
                    <td><?= e($row['full_name']) ?></td>
                    <td><?= e($row['leave_type_name']) ?></td>
                    <td><?= e($row['start_date']) ?><?= $row['start_date'] !== $row['end_date'] ? ' — ' . e($row['end_date']) : '' ?></td>
                    <td><?= e(format_days((float) $row['requested_days'])) ?></td>
                    <td><a class="btn btn-light" href="<?= e(base_path('admin/requests.php#request-' . $row['id'])) ?>">İncele</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require dirname(__DIR__) . '/templates/footer.php'; ?>
