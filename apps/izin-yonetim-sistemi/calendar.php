<?php

declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

$user = require_login();
$isAdmin = ($user['role'] ?? '') === 'admin';

$requestedMonth = trim((string) ($_GET['month'] ?? date('Y-m')));
$monthStart = DateTimeImmutable::createFromFormat('!Y-m-d', $requestedMonth . '-01');
$monthErrors = DateTimeImmutable::getLastErrors();

if (
    $monthStart === false
    || ($monthErrors !== false && (($monthErrors['warning_count'] ?? 0) > 0 || ($monthErrors['error_count'] ?? 0) > 0))
    || $monthStart->format('Y-m') !== $requestedMonth
    || (int) $monthStart->format('Y') < 2000
    || (int) $monthStart->format('Y') > 2100
) {
    $monthStart = new DateTimeImmutable(date('Y-m-01'));
}

$monthEnd = $monthStart->modify('last day of this month');
$gridStart = $monthStart->modify('monday this week');
$gridEnd = $monthEnd->modify('sunday this week');

$prevMonth = $monthStart->modify('-1 month')->format('Y-m');
$nextMonth = $monthStart->modify('+1 month')->format('Y-m');
$todayMonth = date('Y-m');
$today = date('Y-m-d');

$monthNames = [
    1 => 'Ocak',
    2 => 'Şubat',
    3 => 'Mart',
    4 => 'Nisan',
    5 => 'Mayıs',
    6 => 'Haziran',
    7 => 'Temmuz',
    8 => 'Ağustos',
    9 => 'Eylül',
    10 => 'Ekim',
    11 => 'Kasım',
    12 => 'Aralık',
];

$weekdayNames = ['Pzt', 'Sal', 'Çar', 'Per', 'Cum', 'Cmt', 'Paz'];
$workingWeekdays = configured_working_weekdays();

$leaveRepo = new LeaveRepository(db());
$events = $leaveRepo->calendarEventsBetween(
    $gridStart->format('Y-m-d'),
    $gridEnd->format('Y-m-d'),
    $isAdmin ? null : (int) $user['id'],
    true
);

$eventsByDate = [];
foreach ($events as $event) {
    $eventsByDate[(string) $event['leave_date']][] = $event;
}

$holidayStmt = db()->prepare(
    'SELECT holiday_date, name, is_half_day, half_day_period
     FROM public_holidays
     WHERE holiday_date BETWEEN :from_date AND :to_date
     ORDER BY holiday_date ASC'
);
$holidayStmt->execute([
    'from_date' => $gridStart->format('Y-m-d'),
    'to_date' => $gridEnd->format('Y-m-d'),
]);

$holidaysByDate = [];
foreach ($holidayStmt->fetchAll() as $holiday) {
    $holidaysByDate[(string) $holiday['holiday_date']][] = $holiday;
}

$pageTitle = 'Takvim — ' . application_name();
require __DIR__ . '/templates/header.php';
?>
<div class="page-head calendar-page-head">
    <div>
        <h1>İzin Takvimi</h1>
        <p>
            <?= $isAdmin ? 'Tüm çalışanların onaylı izinleri' : 'Onaylanan izinleriniz' ?>.
            Çalışma günü olmayan günler ve resmî tatiller ayrıca gösterilir.
        </p>
    </div>
</div>

<section class="card calendar-card">
    <div class="calendar-toolbar">
        <div class="calendar-nav">
            <a class="btn btn-light" href="<?= e(base_path('calendar.php?month=' . $prevMonth)) ?>" aria-label="Önceki ay">← Önceki</a>
            <a class="btn btn-light" href="<?= e(base_path('calendar.php?month=' . $todayMonth)) ?>">Bugün</a>
            <a class="btn btn-light" href="<?= e(base_path('calendar.php?month=' . $nextMonth)) ?>" aria-label="Sonraki ay">Sonraki →</a>
        </div>
        <h2 class="calendar-month-title">
            <?= e($monthNames[(int) $monthStart->format('n')] . ' ' . $monthStart->format('Y')) ?>
        </h2>
        <div class="calendar-policy-summary">
            Çalışma günleri: <?= e(working_weekdays_text()) ?>
        </div>
    </div>

    <div class="calendar-legend" aria-label="Takvim açıklamaları">
        <span><i class="calendar-legend-dot legend-leave"></i> Onaylı izin</span>
        <span><i class="calendar-legend-dot legend-holiday"></i> Resmî tatil</span>
        <span><i class="calendar-legend-dot legend-nonwork"></i> Çalışma günü değil</span>
    </div>

    <div class="calendar-scroll">
        <div class="calendar-grid" role="grid" aria-label="<?= e($monthNames[(int) $monthStart->format('n')] . ' ' . $monthStart->format('Y')) ?>">
            <?php foreach ($weekdayNames as $weekday): ?>
                <div class="calendar-weekday" role="columnheader"><?= e($weekday) ?></div>
            <?php endforeach; ?>

            <?php for ($date = $gridStart; $date <= $gridEnd; $date = $date->modify('+1 day')): ?>
                <?php
                $dateKey = $date->format('Y-m-d');
                $dayEvents = $eventsByDate[$dateKey] ?? [];
                $dayHolidays = $holidaysByDate[$dateKey] ?? [];
                $isOutside = $date->format('Y-m') !== $monthStart->format('Y-m');
                $isToday = $dateKey === $today;
                $isWorkingDay = in_array((int) $date->format('N'), $workingWeekdays, true);

                $classes = ['calendar-day'];
                if ($isOutside) {
                    $classes[] = 'is-outside';
                }
                if ($isToday) {
                    $classes[] = 'is-today';
                }
                if (!$isWorkingDay) {
                    $classes[] = 'is-nonwork';
                }
                ?>
                <div class="<?= e(implode(' ', $classes)) ?>" role="gridcell" aria-label="<?= e($date->format('d.m.Y')) ?>">
                    <div class="calendar-date-row">
                        <span class="calendar-date"><?= e($date->format('j')) ?></span>
                        <?php if (!$isWorkingDay): ?>
                            <span class="calendar-day-note">Çalışma dışı</span>
                        <?php endif; ?>
                    </div>

                    <div class="calendar-day-events">
                        <?php foreach ($dayHolidays as $holiday): ?>
                            <div class="calendar-holiday" title="<?= e((string) $holiday['name']) ?>">
                                <span class="calendar-event-label">Tatil</span>
                                <span><?= e((string) $holiday['name']) ?></span>
                                <?php if ((int) $holiday['is_half_day'] === 1): ?>
                                    <small>Yarım gün<?= $holiday['half_day_period'] ? ' · ' . e((string) $holiday['half_day_period']) : '' ?></small>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>

                        <?php foreach ($dayEvents as $event): ?>
                            <div class="calendar-event" style="--event-color: <?= e((string) $event['color_hex']) ?>">
                                <span class="calendar-event-label"><?= e((string) $event['leave_type_name']) ?></span>
                                <?php if ($isAdmin): ?>
                                    <strong><?= e((string) $event['full_name']) ?></strong>
                                <?php endif; ?>
                                <small><?= e(format_days((float) $event['day_value'])) ?> gün</small>
                            </div>
                        <?php endforeach; ?>

                        <?php if ($dayEvents === [] && $dayHolidays === []): ?>
                            <span class="calendar-empty-day" aria-hidden="true"></span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endfor; ?>
        </div>
    </div>
</section>

<section class="card mt-24 calendar-help">
    <h2 class="section-title">Hesaplama Şeffaflığı</h2>
    <p>
        Bu takvimde çalışma günü olarak tanımlanmayan günler ve sistemde kayıtlı resmî tatiller
        izin hesabına dahil edilmez.
        <?php if ($isAdmin): ?>
            Çalışma günleri <a href="<?= e(base_path('admin/settings.php')) ?>">Şirket Politikaları</a> üzerinden yönetilir.
        <?php else: ?>
            Çalışma günleri şirket yöneticisinin belirlediği politikaya göre uygulanır.
        <?php endif; ?>
    </p>
</section>

<?php require __DIR__ . '/templates/footer.php'; ?>
