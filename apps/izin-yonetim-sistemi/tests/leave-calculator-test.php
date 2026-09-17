<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/leave-calculator.php';

$passed = 0;
$failed = 0;

function check_case(string $name, callable $test): void
{
    global $passed, $failed;

    try {
        $test();
        $passed++;
        echo "[PASS] {$name}\n";
    } catch (Throwable $e) {
        $failed++;
        echo "[FAIL] {$name}: {$e->getMessage()}\n";
    }
}

function assert_float(float $expected, float $actual): void
{
    if (abs($expected - $actual) > 0.0001) {
        throw new RuntimeException("Expected {$expected}, got {$actual}");
    }
}

function assert_true(bool $value, string $message = 'Assertion failed'): void
{
    if (!$value) {
        throw new RuntimeException($message);
    }
}

check_case('Friday to Monday counts two workdays', function (): void {
    $result = calculate_leave_days_with_holidays(
        '2026-09-18',
        '2026-09-21',
        'full_day',
        null,
        []
    );

    assert_float(2.0, (float) $result['total']);
    assert_true(count($result['days']) === 2);
});

check_case('Full-day public holiday is excluded', function (): void {
    $result = calculate_leave_days_with_holidays(
        '2026-09-18',
        '2026-09-21',
        'full_day',
        null,
        [
            '2026-09-21' => ['is_half_day' => false, 'half_day_period' => null],
        ]
    );

    assert_float(1.0, (float) $result['total']);
});

check_case('Half-day public holiday leaves half a workday', function (): void {
    $result = calculate_leave_days_with_holidays(
        '2026-09-18',
        '2026-09-21',
        'full_day',
        null,
        [
            '2026-09-21' => ['is_half_day' => true, 'half_day_period' => 'afternoon'],
        ]
    );

    assert_float(1.5, (float) $result['total']);
});

check_case('Morning leave on afternoon holiday counts 0.5', function (): void {
    $result = calculate_leave_days_with_holidays(
        '2026-09-21',
        '2026-09-21',
        'half_day',
        'morning',
        [
            '2026-09-21' => ['is_half_day' => true, 'half_day_period' => 'afternoon'],
        ]
    );

    assert_float(0.5, (float) $result['total']);
});

check_case('Leave requested during the holiday half counts zero', function (): void {
    $result = calculate_leave_days_with_holidays(
        '2026-09-21',
        '2026-09-21',
        'half_day',
        'afternoon',
        [
            '2026-09-21' => ['is_half_day' => true, 'half_day_period' => 'afternoon'],
        ]
    );

    assert_float(0.0, (float) $result['total']);
    assert_true($result['days'] === []);
});

check_case('Cross-year range uses actual ledger dates', function (): void {
    $result = calculate_leave_days_with_holidays(
        '2026-12-31',
        '2027-01-01',
        'full_day',
        null,
        []
    );

    assert_float(2.0, (float) $result['total']);
    assert_true($result['days'][0]['date'] === '2026-12-31');
    assert_true($result['days'][1]['date'] === '2027-01-01');
});

check_case('Invalid calendar date is rejected', function (): void {
    try {
        calculate_leave_days_with_holidays('2026-02-31', '2026-02-31', 'full_day', null, []);
    } catch (InvalidArgumentException) {
        return;
    }

    throw new RuntimeException('Invalid date was accepted');
});

check_case('Half-day request cannot span multiple dates', function (): void {
    try {
        calculate_leave_days_with_holidays('2026-09-21', '2026-09-22', 'half_day', 'morning', []);
    } catch (InvalidArgumentException) {
        return;
    }

    throw new RuntimeException('Multi-day half-day request was accepted');
});

echo "\nResult: {$passed} passed, {$failed} failed\n";
exit($failed === 0 ? 0 : 1);
