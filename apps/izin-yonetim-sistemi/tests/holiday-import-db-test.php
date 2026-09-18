<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

$pdo = db();

function holiday_import_assert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
    echo "[PASS] {$message}\n";
}

$pdo->exec('DELETE FROM public_holidays');

$first = import_public_holiday_rows($pdo, [
    [
        'holiday_date' => '2027-01-01',
        'name' => 'Yılbaşı',
        'is_half_day' => 0,
        'half_day_period' => null,
        'source_uid' => 'TR-2027-001',
    ],
    [
        'holiday_date' => '2027-04-23',
        'name' => 'Ulusal Egemenlik ve Çocuk Bayramı',
        'is_half_day' => 0,
        'half_day_period' => null,
        'source_uid' => 'TR-2027-002',
    ],
], null, 'ci');

holiday_import_assert((int) $first['created'] === 2, 'first import detects two new holidays');
holiday_import_assert((int) $first['updated'] === 0, 'first import has no updates');

$second = import_public_holiday_rows($pdo, [
    [
        'holiday_date' => '2027-01-01',
        'name' => 'Yılbaşı',
        'is_half_day' => 0,
        'half_day_period' => null,
        'source_uid' => 'TR-2027-001',
    ],
    [
        'holiday_date' => '2027-04-23',
        'name' => '23 Nisan Ulusal Egemenlik ve Çocuk Bayramı',
        'is_half_day' => 0,
        'half_day_period' => null,
        'source_uid' => 'TR-2027-002',
    ],
    [
        'holiday_date' => '2027-05-01',
        'name' => 'Emek ve Dayanışma Günü',
        'is_half_day' => 0,
        'half_day_period' => null,
        'source_uid' => 'TR-2027-003',
    ],
], null, 'ci');

holiday_import_assert((int) $second['created'] === 1, 'second import detects newly added holiday');
holiday_import_assert((int) $second['updated'] === 1, 'second import detects changed holiday');
holiday_import_assert((int) $second['unchanged'] === 1, 'second import detects unchanged holiday');

$count = (int) $pdo->query('SELECT COUNT(*) FROM public_holidays')->fetchColumn();
holiday_import_assert($count === 3, 'import is idempotent and does not duplicate dates');

$sourceCount = (int) $pdo->query("SELECT COUNT(*) FROM public_holidays WHERE source_type='ci'")->fetchColumn();
holiday_import_assert($sourceCount === 3, 'import source metadata stored');

echo "\nPublic holiday import regression: PASS\n";
