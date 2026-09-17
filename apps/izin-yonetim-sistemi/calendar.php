<?php

declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

$user = require_login();
$year = filter_input(INPUT_GET, 'year', FILTER_VALIDATE_INT) ?: (int) date('Y');
if ($year < 2000 || $year > 2100) {
    $year = (int) date('Y');
}

$leaveRepo = new LeaveRepository(db());
$isAdmin = ($user['role'] ?? '') === 'admin';
$events = $leaveRepo->calendarEvents($isAdmin ? null : (int) $user['id'], true);
$events = array_values(array_filter($events, static fn(array $event): bool => (int) substr((string) $event['leave_date'], 0, 4) === $year));

$pageTitle = 'Takvim';
require __DIR__ . '/templates/header.php';
?>
<div class="page-head">
    <div>
        <h1>İzin Takvimi</h1>
        <p><?= $isAdmin ? 'Tüm onaylı izinler' : 'Onaylanan izinleriniz' ?> — <?= e((string) $year) ?></p>
    </div>
</div>
<section class="card">
    <div class="table-wrap">
        <table>
            <thead><tr><th>Tarih</th><?php if ($isAdmin): ?><th>Çalışan</th><?php endif; ?><th>İzin Türü</th><th>Gün</th></tr></thead>
            <tbody>
            <?php if (!$events): ?><tr><td colspan="4">Bu yıl için onaylı izin bulunmuyor.</td></tr><?php endif; ?>
            <?php foreach ($events as $event): ?>
                <tr>
                    <td><?= e($event['leave_date']) ?></td>
                    <?php if ($isAdmin): ?><td><?= e($event['full_name']) ?></td><?php endif; ?>
                    <td><span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:<?= e($event['color_hex']) ?>;margin-right:7px"></span><?= e($event['leave_type_name']) ?></td>
                    <td><?= e(format_days((float) $event['day_value'])) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require __DIR__ . '/templates/footer.php'; ?>
