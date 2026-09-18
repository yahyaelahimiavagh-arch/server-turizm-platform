<?php

declare(strict_types=1);

function calculate_leave_days(
    string $startDate,
    string $endDate,
    string $durationType,
    ?string $halfDayPeriod = null
): array {
    $holidays = load_holidays_between($startDate, $endDate);
    $workingWeekdays = function_exists('configured_working_weekdays')
        ? configured_working_weekdays()
        : [1, 2, 3, 4, 5];

    return calculate_leave_days_with_holidays(
        $startDate,
        $endDate,
        $durationType,
        $halfDayPeriod,
        $holidays,
        $workingWeekdays
    );
}

function calculate_leave_days_with_holidays(
    string $startDate,
    string $endDate,
    string $durationType,
    ?string $halfDayPeriod,
    array $holidays,
    array $workingWeekdays = [1, 2, 3, 4, 5]
): array {
    $start = parse_leave_date($startDate);
    $end = parse_leave_date($endDate);

    if ($start === null || $end === null || $end < $start) {
        throw new InvalidArgumentException('Geçerli bir tarih aralığı seçin.');
    }

    $span = (int) $start->diff($end)->format('%a');
    if ($span > 366) {
        throw new InvalidArgumentException('İzin aralığı 367 günden uzun olamaz.');
    }

    if (!in_array($durationType, ['full_day', 'half_day'], true)) {
        throw new InvalidArgumentException('Geçersiz izin süresi tipi.');
    }

    if ($durationType === 'half_day') {
        if ($startDate !== $endDate) {
            throw new InvalidArgumentException('Yarım gün izin yalnızca tek bir tarih için kullanılabilir.');
        }
        if (!in_array($halfDayPeriod, ['morning', 'afternoon'], true)) {
            throw new InvalidArgumentException('Yarım gün için sabah veya öğleden sonra seçin.');
        }
    } else {
        $halfDayPeriod = null;
    }

    $workingWeekdays = normalize_working_weekdays($workingWeekdays);
    $days = [];
    $total = 0.0;
    $weeklyRestDays = 0;
    $fullHolidayDays = 0;
    $halfHolidayDays = 0;

    for ($date = $start; $date <= $end; $date = $date->modify('+1 day')) {
        $dayOfWeek = (int) $date->format('N');
        if (!in_array($dayOfWeek, $workingWeekdays, true)) {
            $weeklyRestDays++;
            continue;
        }

        $dateKey = $date->format('Y-m-d');
        $holiday = $holidays[$dateKey] ?? null;

        if ($holiday !== null) {
            if (($holiday['is_half_day'] ?? false) === true) {
                $halfHolidayDays++;
            } else {
                $fullHolidayDays++;
            }
        }

        $value = calculate_single_day_value($durationType, $halfDayPeriod, $holiday);

        if ($value <= 0.0) {
            continue;
        }

        $days[] = [
            'date' => $dateKey,
            'value' => $value,
        ];
        $total += $value;
    }

    return [
        'total' => round($total, 2),
        'days' => $days,
        'breakdown' => [
            'calendar_days' => $span + 1,
            'weekly_rest_days' => $weeklyRestDays,
            'full_holiday_days' => $fullHolidayDays,
            'half_holiday_days' => $halfHolidayDays,
            'deducted_days' => round($total, 2),
        ],
        'policy' => [
            'working_weekdays' => $workingWeekdays,
        ],
    ];
}

function normalize_working_weekdays(array $workingWeekdays): array
{
    $normalized = [];

    foreach ($workingWeekdays as $day) {
        $day = filter_var($day, FILTER_VALIDATE_INT);
        if ($day !== false && $day >= 1 && $day <= 7) {
            $normalized[(int) $day] = true;
        }
    }

    $result = array_keys($normalized);
    sort($result);

    if ($result === []) {
        throw new InvalidArgumentException('En az bir çalışma günü tanımlanmalıdır.');
    }

    return $result;
}

function parse_leave_date(string $value): ?DateTimeImmutable
{
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    $errors = DateTimeImmutable::getLastErrors();

    if ($date === false) {
        return null;
    }

    if ($errors !== false && (($errors['warning_count'] ?? 0) > 0 || ($errors['error_count'] ?? 0) > 0)) {
        return null;
    }

    return $date->format('Y-m-d') === $value ? $date : null;
}

function load_holidays_between(string $startDate, string $endDate): array
{
    $stmt = db()->prepare(
        'SELECT holiday_date, is_half_day, half_day_period
         FROM public_holidays
         WHERE holiday_date BETWEEN :start_date AND :end_date'
    );
    $stmt->execute([
        'start_date' => $startDate,
        'end_date' => $endDate,
    ]);

    $holidays = [];
    foreach ($stmt->fetchAll() as $row) {
        $holidays[(string) $row['holiday_date']] = [
            'is_half_day' => (int) $row['is_half_day'] === 1,
            'half_day_period' => $row['half_day_period'] ?: null,
        ];
    }

    return $holidays;
}

function calculate_single_day_value(
    string $durationType,
    ?string $requestedHalfDayPeriod,
    ?array $holiday
): float {
    if ($holiday === null) {
        return $durationType === 'half_day' ? 0.5 : 1.0;
    }

    if (($holiday['is_half_day'] ?? false) !== true) {
        return 0.0;
    }

    $holidayPeriod = $holiday['half_day_period'] ?? null;

    if ($durationType === 'full_day') {
        return 0.5;
    }

    if (!in_array($holidayPeriod, ['morning', 'afternoon'], true)) {
        return 0.0;
    }

    return $requestedHalfDayPeriod === $holidayPeriod ? 0.0 : 0.5;
}
