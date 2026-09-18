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


check_case('Configured Saturday workday is counted', function (): void {
    $result = calculate_leave_days_with_holidays(
        '2026-09-18',
        '2026-09-21',
        'full_day',
        null,
        [],
        [1, 2, 3, 4, 5, 6]
    );

    assert_float(3.0, (float) $result['total']);
    assert_true((int) $result['breakdown']['weekly_rest_days'] === 1);
});


check_case('Server Turizm Friday to Monday counts Saturday as one full leave day', function (): void {
    $result = calculate_leave_days_with_holidays(
        '2026-09-18',
        '2026-09-21',
        'full_day',
        null,
        [],
        [
            1 => 'full_day',
            2 => 'full_day',
            3 => 'full_day',
            4 => 'full_day',
            5 => 'full_day',
            6 => 'morning',
            7 => 'off',
        ]
    );

    assert_float(3.0, (float) $result['total']);
    assert_true((int) $result['breakdown']['weekly_rest_days'] === 1);
    assert_true((int) $result['breakdown']['partial_workdays'] === 1);
    assert_float(1.0, (float) $result['days'][1]['value']);
});

check_case('Saturday morning half-day leave counts 0.5', function (): void {
    $result = calculate_leave_days_with_holidays(
        '2026-09-19',
        '2026-09-19',
        'half_day',
        'morning',
        [],
        [
            1 => 'full_day',
            2 => 'full_day',
            3 => 'full_day',
            4 => 'full_day',
            5 => 'full_day',
            6 => 'morning',
            7 => 'off',
        ]
    );

    assert_float(0.5, (float) $result['total']);
});

check_case('Saturday afternoon leave counts zero when company does not work afternoon', function (): void {
    $result = calculate_leave_days_with_holidays(
        '2026-09-19',
        '2026-09-19',
        'half_day',
        'afternoon',
        [],
        [
            1 => 'full_day',
            2 => 'full_day',
            3 => 'full_day',
            4 => 'full_day',
            5 => 'full_day',
            6 => 'morning',
            7 => 'off',
        ]
    );

    assert_float(0.0, (float) $result['total']);
    assert_true($result['days'] === []);
});

check_case('Saturday morning half-day holiday reduces configured full leave day by 0.5', function (): void {
    $result = calculate_leave_days_with_holidays(
        '2026-09-19',
        '2026-09-19',
        'full_day',
        null,
        [
            '2026-09-19' => ['is_half_day' => true, 'half_day_period' => 'morning'],
        ],
        [
            1 => 'full_day',
            2 => 'full_day',
            3 => 'full_day',
            4 => 'full_day',
            5 => 'full_day',
            6 => 'morning',
            7 => 'off',
        ]
    );

    assert_float(0.5, (float) $result['total']);
});

check_case('Saturday afternoon holiday does not reduce Saturday leave charge', function (): void {
    $result = calculate_leave_days_with_holidays(
        '2026-09-19',
        '2026-09-19',
        'full_day',
        null,
        [
            '2026-09-19' => ['is_half_day' => true, 'half_day_period' => 'afternoon'],
        ],
        [
            1 => 'full_day',
            2 => 'full_day',
            3 => 'full_day',
            4 => 'full_day',
            5 => 'full_day',
            6 => 'morning',
            7 => 'off',
        ]
    );

    assert_float(1.0, (float) $result['total']);
});

check_case('Configured Saturday full-day leave weight can be reduced independently', function (): void {
    $result = calculate_leave_days_with_holidays(
        '2026-09-19',
        '2026-09-19',
        'full_day',
        null,
        [],
        [
            1 => 'full_day',
            2 => 'full_day',
            3 => 'full_day',
            4 => 'full_day',
            5 => 'full_day',
            6 => 'morning',
            7 => 'off',
        ],
        [
            1 => 1,
            2 => 1,
            3 => 1,
            4 => 1,
            5 => 1,
            6 => 0.5,
            7 => 0,
        ]
    );

    assert_float(0.5, (float) $result['total']);
});

check_case('Calculation exposes transparent exclusion breakdown', function (): void {
    $result = calculate_leave_days_with_holidays(
        '2026-09-18',
        '2026-09-21',
        'full_day',
        null,
        [
            '2026-09-21' => ['is_half_day' => false, 'half_day_period' => null],
        ]
    );

    assert_true((int) $result['breakdown']['calendar_days'] === 4);
    assert_true((int) $result['breakdown']['weekly_rest_days'] === 2);
    assert_true((int) $result['breakdown']['full_holiday_days'] === 1);
    assert_float(1.0, (float) $result['breakdown']['deducted_days']);
});

echo "\nResult: {$passed} passed, {$failed} failed\n";
exit($failed === 0 ? 0 : 1);
