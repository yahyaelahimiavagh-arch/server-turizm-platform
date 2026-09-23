<?php

declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

$user = require_login();
if (($user['role'] ?? '') === 'admin') {
    redirect('admin/dashboard.php');
}

$year = filter_input(INPUT_GET, 'year', FILTER_VALIDATE_INT) ?: (int) date('Y');
if ($year < 2000 || $year > 2100) {
    $year = (int) date('Y');
}

$leaveRepo = new LeaveRepository(db());
$summary = $leaveRepo->allowanceSummary((int) $user['id'], $year);
$nextEntitlement = annual_leave_next_entitlement(db(), (int) $user['id']);
$breakdown = $leaveRepo->approvedBreakdown((int) $user['id'], $year);
$recent = $leaveRepo->recentRequests((int) $user['id'], 5);

$pageTitle = 'Dashboard — İzin Yönetimi';
require __DIR__ . '/templates/header.php';
?>
<div class="page-head">
    <div>
        <h1>Merhaba <?= e($user['full_name']) ?></h1>
        <p>Yıllık izin hakkınız hizmet yılına göre birikir; kullanılmayan haklar devreder. <?= e((string) $year) ?> kullanım özeti.</p>
    </div>
    <a class="btn btn-gold" href="<?= e(base_path('leave-new.php')) ?>">Yeni İzin Talebi</a>
</div>

<div class="grid grid-4">
    <div class="card stat-card"><div class="label">Hak Edilmiş Toplam</div><div class="value"><?= e(format_days((float) $summary['entitlement'])) ?> Gün</div><div class="sub">Önceki hizmet yıllarından kalan haklar dahildir.</div></div>
    <div class="card stat-card"><div class="label">Onaylanan / Ayrılan</div><div class="value"><?= e(format_days((float) $summary['approved'])) ?> Gün</div></div>
    <div class="card stat-card"><div class="label">Bekleyen</div><div class="value"><?= e(format_days((float) $summary['pending'])) ?> Gün</div></div>
    <div class="card stat-card"><div class="label">Kullanılabilir</div><div class="value"><?= e(format_days((float) $summary['available_after_pending'])) ?> Gün</div><div class="sub">Onaylı ve bekleyen talepler düşülmüştür.</div></div>
</div>

<?php if ($nextEntitlement): ?>
    <div class="alert alert-info mt-24">
        Sonraki hizmet yılı hak edişiniz:
        <strong><?= e((string) $nextEntitlement['earned_on']) ?></strong>
        · <?= e(format_days((float) $nextEntitlement['days'])) ?> gün.
        Bu hak, hak ediş tarihinden önce kullanılamaz.
    </div>
<?php endif; ?>

<div class="grid grid-2 mt-24">
    <section class="card">
        <h2 class="section-title">İzin Türleri</h2>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Tür</th><th>Onaylanan</th></tr></thead>
                <tbody>
                <?php foreach ($breakdown as $row): ?>
                    <tr><td><?= e($row['name']) ?></td><td><?= e(format_days((float) $row['total'])) ?> gün</td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="card">
        <h2 class="section-title">Son Talepler</h2>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Tür</th><th>Tarih</th><th>Gün</th><th>Durum</th></tr></thead>
                <tbody>
                <?php if (!$recent): ?>
                    <tr><td colspan="4">Henüz izin talebiniz yok.</td></tr>
                <?php endif; ?>
                <?php foreach ($recent as $row): ?>
                    <tr>
                        <td><?= e($row['leave_type_name']) ?></td>
                        <td><?= e($row['start_date']) ?><?= $row['start_date'] !== $row['end_date'] ? ' — ' . e($row['end_date']) : '' ?></td>
                        <td><?= e(format_days((float) $row['requested_days'])) ?></td>
                        <td><span class="badge <?= e(status_badge_class($row['status'])) ?>"><?= e(status_label($row['status'])) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
<?php require __DIR__ . '/templates/footer.php'; ?>
