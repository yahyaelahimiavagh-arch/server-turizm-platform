<?php

declare(strict_types=1);

function leave_calendar_export_config(): array
{
    $config = app_config('calendar_export');
    return is_array($config) ? $config : [];
}

function leave_calendar_export_enabled(): bool
{
    $config = leave_calendar_export_config();

    return ($config['enabled'] ?? false) === true
        && strlen(trim((string) ($config['token'] ?? ''))) >= 32;
}

function leave_calendar_export_authorized(): bool
{
    if (!leave_calendar_export_enabled()) {
        return false;
    }

    $configured = trim((string) (leave_calendar_export_config()['token'] ?? ''));
    $provided = trim((string) ($_SERVER['HTTP_X_ELAHI_LEAVE_TOKEN'] ?? ''));

    if ($provided === '') {
        $authorization = trim((string) ($_SERVER['HTTP_AUTHORIZATION'] ?? ''));
        if (preg_match('/^Bearer\s+(.+)$/i', $authorization, $matches)) {
            $provided = trim((string) $matches[1]);
        }
    }

    return $provided !== '' && hash_equals($configured, $provided);
}

function leave_calendar_export_events(string $fromDate, string $toDate): array
{
    $from = parse_leave_date($fromDate);
    $to = parse_leave_date($toDate);

    if ($from === null || $to === null || $to < $from) {
        throw new InvalidArgumentException('Geçersiz takvim dışa aktarım aralığı.');
    }

    if ((int) $from->diff($to)->format('%a') > 92) {
        throw new InvalidArgumentException('Takvim dışa aktarımı tek istekte en fazla 93 gün olabilir.');
    }

    $stmt = db()->prepare(
        "SELECT lr.id AS request_id, lr.duration_type, lr.half_day_period,
                u.full_name, lrd.leave_date, lrd.day_value
         FROM leave_request_days lrd
         INNER JOIN leave_requests lr ON lr.id = lrd.leave_request_id
         INNER JOIN users u ON u.id = lr.user_id
         WHERE lr.status = 'approved'
           AND u.role = 'employee'
           AND u.is_active = 1
           AND lrd.leave_date BETWEEN :from_date AND :to_date
         ORDER BY lrd.leave_date ASC, lr.id ASC"
    );
    $stmt->execute([
        'from_date' => $fromDate,
        'to_date' => $toDate,
    ]);

    $timezone = new DateTimeZone(date_default_timezone_get());
    $events = [];

    foreach ($stmt->fetchAll() as $row) {
        $date = (string) $row['leave_date'];
        $day = parse_leave_date($date);
        if ($day === null) {
            continue;
        }

        $requestId = (int) $row['request_id'];
        $dayValue = (float) $row['day_value'];
        $isHalfDay = $dayValue < 0.999;
        $period = $row['half_day_period'] !== null ? (string) $row['half_day_period'] : null;

        $start = new DateTimeImmutable($date . ' 00:00:00', $timezone);
        $end = new DateTimeImmutable($date . ' 23:59:59', $timezone);

        $events[] = [
            'event_uid' => 'leave:' . $requestId . ':' . $date,
            'source_module' => 'leave',
            'source_entity_id' => (string) $requestId,
            'event_type' => $isHalfDay ? 'employee_half_day_leave' : 'employee_leave',
            'title' => trim((string) $row['full_name']) . ' — ' . ($isHalfDay ? 'Yarım Gün İzinli' : 'İzinli'),
            'start_at' => $start->format(DATE_ATOM),
            'end_at' => $end->format(DATE_ATOM),
            'all_day' => true,
            'status' => 'published',
            'visibility' => 'internal',
            'priority' => 50,
            'location' => null,
            'public_url' => null,
            'source_version' => 'leave-export-v1.0',
            'metadata' => [
                'day_value' => $dayValue,
                'half_day_period' => $period,
            ],
        ];
    }

    return $events;
}
