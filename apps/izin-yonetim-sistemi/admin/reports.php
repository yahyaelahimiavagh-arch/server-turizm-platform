<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';
require_admin();

$userRepo = new UserRepository(db());
$reportRepo = new ReportRepository(db());
$employees = $userRepo->listEmployees();
$year = filter_input(INPUT_GET, 'year', FILTER_VALIDATE_INT) ?: (int) date('Y');
$employeeId = filter_input(INPUT_GET, 'employee_id', FILTER_VALIDATE_INT) ?: 0;

if ($year < 2000 || $year > 2100) {
    $year = (int) date('Y');
}

$months = [1 => 'Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran', 'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık'];
$singleEmployee = null;
$singleReport = null;
$allReports = [];

if ($employeeId > 0) {
    $singleEmployee = $userRepo->find($employeeId);
    if (!$singleEmployee || $singleEmployee['role'] !== 'employee') {
        http_response_code(404);
        exit('Çalışan bulunamadı.');
    }
    $singleReport = $reportRepo->employeeYear($employeeId, $year);
} else {
    $allReports = $reportRepo->allEmployeesYear($year);
}

$pageTitle = 'İzin Raporları';
require dirname(__DIR__) . '/templates/header.php';
?>
<div class="page-head"><div><h1>İzin Raporları</h1><p>Aylık ve yıllık kullanım görünümü.</p></div></div>

<section class="card" style="margin-bottom:24px">
    <form method="get" class="grid grid-2">
        <div class="form-group"><label for="year">Yıl</label><input id="year" name="year" type="number" min="2000" max="2100" value="<?= e((string) $year) ?>"></div>
        <div class="form-group"><label for="employee_id">Çalışan</label><select id="employee_id" name="employee_id"><option value="0">Tüm Çalışanlar</option><?php foreach ($employees as $employee): ?><option value="<?= e($employee['id']) ?>" <?= (int) $employee['id'] === $employeeId ? 'selected' : '' ?>><?= e($employee['full_name']) ?></option><?php endforeach; ?></select></div>
        <div><button class="btn btn-primary" type="submit">Raporu Göster</button></div>
    </form>
</section>

<?php if ($singleEmployee && $singleReport): ?>
    <?php $summary = $singleReport['summary']; ?>
    <div class="page-head"><div><h2><?= e($singleEmployee['full_name']) ?> — <?= e((string) $year) ?></h2></div></div>
    <div class="grid grid-4">
        <div class="card stat-card"><div class="label">Yıllık Hak</div><div class="value"><?= e(format_days((float) $summary['entitlement'])) ?></div></div>
        <div class="card stat-card"><div class="label">Kullanılan</div><div class="value"><?= e(format_days((float) $summary['approved'])) ?></div></div>
        <div class="card stat-card"><div class="label">Bekleyen</div><div class="value"><?= e(format_days((float) $summary['pending'])) ?></div></div>
        <div class="card stat-card"><div class="label">Kalan</div><div class="value"><?= e(format_days((float) $summary['remaining'])) ?></div></div>
    </div>
    <div class="grid grid-2 mt-24">
        <section class="card"><h3 class="section-title">Aylık Kullanım</h3><div class="table-wrap"><table><thead><tr><th>Ay</th><th>Gün</th></tr></thead><tbody><?php foreach ($months as $monthNo => $monthName): ?><tr><td><?= e($monthName) ?></td><td><?= e(format_days((float) $singleReport['monthly'][$monthNo])) ?></td></tr><?php endforeach; ?></tbody></table></div></section>
        <section class="card"><h3 class="section-title">İzin Türü Dağılımı</h3><div class="table-wrap"><table><thead><tr><th>Tür</th><th>Gün</th></tr></thead><tbody><?php foreach ($singleReport['breakdown'] as $row): ?><tr><td><?= e($row['name']) ?></td><td><?= e(format_days((float) $row['total'])) ?></td></tr><?php endforeach; ?></tbody></table></div></section>
    </div>
<?php else: ?>
    <section class="card">
        <h2 class="section-title">Tüm Çalışanlar — <?= e((string) $year) ?></h2>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Çalışan</th><?php foreach ($months as $monthName): ?><th><?= e($monthName) ?></th><?php endforeach; ?><th>Hak</th><th>Kullanılan</th><th>Bekleyen</th><th>Kalan</th></tr></thead>
                <tbody>
                <?php if (!$allReports): ?><tr><td>Aktif çalışan yok.</td></tr><?php endif; ?>
                <?php foreach ($allReports as $row): ?>
                    <?php $report = $row['report']; $summary = $report['summary']; ?>
                    <tr>
                        <td><a href="<?= e(base_path('admin/reports.php?year=' . $year . '&employee_id=' . $row['employee']['id'])) ?>"><?= e($row['employee']['full_name']) ?></a></td>
                        <?php foreach ($months as $monthNo => $monthName): ?><td><?= e(format_days((float) $report['monthly'][$monthNo])) ?></td><?php endforeach; ?>
                        <td><?= e(format_days((float) $summary['entitlement'])) ?></td>
                        <td><?= e(format_days((float) $summary['approved'])) ?></td>
                        <td><?= e(format_days((float) $summary['pending'])) ?></td>
                        <td><?= e(format_days((float) $summary['remaining'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php endif; ?>
<?php require dirname(__DIR__) . '/templates/footer.php'; ?>
