<?php

declare(strict_types=1);

function calculate_leave_days(
    string $startDate,
    string $endDate,
    string $durationType,
    ?string $halfDayPeriod = null
): array {
    $holidays = load_holidays_between($startDate, $endDate);
    $workSchedule = function_exists('configured_work_schedule')
        ? configured_work_schedule()
        : [1, 2, 3, 4, 5];
    $leaveWeights = function_exists('configured_leave_full_day_weights')
        ? configured_leave_full_day_weights()
        : [];

    return calculate_leave_days_with_holidays(
        $startDate,
        $endDate,
        $durationType,
        $halfDayPeriod,
        $holidays,
        $workSchedule,
        $leaveWeights
    );
}

function calculate_leave_days_with_holidays(
    string $startDate,
    string $endDate,
    string $durationType,
    ?string $halfDayPeriod,
    array $holidays,
    array $workSchedule = [1, 2, 3, 4, 5],
    array $leaveFullDayWeights = []
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

    $normalizedSchedule = normalize_work_schedule_policy($workSchedule);
    $normalizedLeaveWeights = normalize_leave_full_day_weights($leaveFullDayWeights, $normalizedSchedule);
    $days = [];
    $total = 0.0;
    $weeklyRestDays = 0;
    $partialWorkdays = 0;
    $fullHolidayDays = 0;
    $halfHolidayDays = 0;

    for ($date = $start; $date <= $end; $date = $date->modify('+1 day')) {
        $dayOfWeek = (int) $date->format('N');
        $workMode = (string) ($normalizedSchedule[$dayOfWeek] ?? 'off');

        if ($workMode === 'off') {
            $weeklyRestDays++;
            continue;
        }

        if (in_array($workMode, ['morning', 'afternoon'], true)) {
            $partialWorkdays++;
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

        $value = calculate_single_day_value(
            $durationType,
            $halfDayPeriod,
            $holiday,
            $workMode,
            (float) ($normalizedLeaveWeights[$dayOfWeek] ?? 0.0)
        );

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
            'partial_workdays' => $partialWorkdays,
            'full_holiday_days' => $fullHolidayDays,
            'half_holiday_days' => $halfHolidayDays,
            'deducted_days' => round($total, 2),
        ],
        'policy' => [
            'work_schedule' => $normalizedSchedule,
            'leave_full_day_weights' => $normalizedLeaveWeights,
            'working_weekdays' => array_values(array_map(
                'intval',
                array_keys(array_filter(
                    $normalizedSchedule,
                    static fn (string $mode): bool => $mode !== 'off'
                ))
            )),
        ],
    ];
}

/**
 * Normalizes both the legacy weekday-list policy:
 *   [1,2,3,4,5]
 * and the V2 schedule policy:
 *   [1=>'full_day', ..., 6=>'morning', 7=>'off']
 */
function normalize_work_schedule_policy(array $schedule): array
{
    $validModes = ['off', 'morning', 'afternoon', 'full_day'];
    $normalized = array_fill(1, 7, 'off');

    $looksLikeModeMap = false;
    foreach ($schedule as $value) {
        if (is_string($value) && in_array($value, $validModes, true)) {
            $looksLikeModeMap = true;
            break;
        }
    }

    if ($looksLikeModeMap) {
        foreach ($schedule as $day => $mode) {
            $day = filter_var($day, FILTER_VALIDATE_INT);
            $mode = (string) $mode;

            if (
                $day !== false
                && $day >= 1
                && $day <= 7
                && in_array($mode, $validModes, true)
            ) {
                $normalized[(int) $day] = $mode;
            }
        }
    } else {
        foreach ($schedule as $day) {
            $day = filter_var($day, FILTER_VALIDATE_INT);
            if ($day !== false && $day >= 1 && $day <= 7) {
                $normalized[(int) $day] = 'full_day';
            }
        }
    }

    if (count(array_filter(
        $normalized,
        static fn (string $mode): bool => $mode !== 'off'
    )) === 0) {
        throw new InvalidArgumentException('En az bir çalışma günü tanımlanmalıdır.');
    }

    ksort($normalized);
    return $normalized;
}

function normalize_leave_full_day_weights(array $weights, array $schedule): array
{
    $normalized = [];

    for ($day = 1; $day <= 7; $day++) {
        $mode = (string) ($schedule[$day] ?? 'off');
        $fallback = $mode === 'off' ? 0.0 : 1.0;
        $value = $weights[$day] ?? $weights[(string) $day] ?? $fallback;

        if (!is_numeric($value)) {
            $value = $fallback;
        }

        $number = (float) $value;
        if (!in_array($number, [0.0, 0.5, 1.0], true)) {
            $number = $fallback;
        }

        if ($mode === 'off') {
            $number = 0.0;
        }

        $normalized[$day] = $number;
    }

    return $normalized;
}

function work_mode_periods(string $workMode): array
{
    return match ($workMode) {
        'morning' => ['morning'],
        'afternoon' => ['afternoon'],
        'full_day' => ['morning', 'afternoon'],
        default => [],
    };
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
    ?array $holiday,
    string $workMode = 'full_day',
    float $fullDayLeaveWeight = 1.0
): float {
    $scheduledPeriods = work_mode_periods($workMode);

    if ($scheduledPeriods === []) {
        return 0.0;
    }

    if ($durationType === 'full_day') {
        if ($holiday === null) {
            return $fullDayLeaveWeight;
        }

        if (($holiday['is_half_day'] ?? false) !== true) {
            return 0.0;
        }

        $holidayPeriod = $holiday['half_day_period'] ?? null;
        if (!in_array($holidayPeriod, ['morning', 'afternoon'], true)) {
            return max(0.0, $fullDayLeaveWeight - 0.5);
        }

        if (in_array($holidayPeriod, $scheduledPeriods, true)) {
            return max(0.0, $fullDayLeaveWeight - 0.5);
        }

        // A half-day holiday outside the employee's scheduled work period does
        // not reduce the company's configured full-day leave charge.
        return $fullDayLeaveWeight;
    }

    if (!in_array($requestedHalfDayPeriod, ['morning', 'afternoon'], true)) {
        return 0.0;
    }

    if (!in_array($requestedHalfDayPeriod, $scheduledPeriods, true)) {
        return 0.0;
    }

    if ($holiday === null) {
        return 0.5;
    }

    if (($holiday['is_half_day'] ?? false) !== true) {
        return 0.0;
    }

    $holidayPeriod = $holiday['half_day_period'] ?? null;
    if (!in_array($holidayPeriod, ['morning', 'afternoon'], true)) {
        return 0.0;
    }

    return $requestedHalfDayPeriod === $holidayPeriod ? 0.0 : 0.5;
}

