<?php

declare(strict_types=1);

function operations_calendar_config(): array
{
    $config = app_config('operations_calendar');
    return is_array($config) ? $config : [];
}

function operations_calendar_enabled(): bool
{
    $config = operations_calendar_config();

    return ($config['enabled'] ?? false) === true
        && trim((string) ($config['endpoint'] ?? '')) !== ''
        && strlen(trim((string) ($config['token'] ?? ''))) >= 32;
}

function operations_calendar_fetch_window(string $startDate, string $endDate): array
{
    if (!operations_calendar_enabled()) {
        return [
            'configured' => false,
            'available' => false,
            'events' => [],
        ];
    }

    $start = parse_leave_date($startDate);
    $end = parse_leave_date($endDate);

    if ($start === null || $end === null || $end < $start) {
        throw new InvalidArgumentException('Geçersiz operasyon takvimi tarih aralığı.');
    }

    $span = (int) $start->diff($end)->format('%a');
    if ($span > 92) {
        throw new InvalidArgumentException('Operasyon takvimi tek sorguda en fazla 93 gün destekler.');
    }

    $config = operations_calendar_config();
    $endpoint = trim((string) ($config['endpoint'] ?? ''));
    $token = trim((string) ($config['token'] ?? ''));

    $parts = parse_url($endpoint);
    if (
        !is_array($parts)
        || strtolower((string) ($parts['scheme'] ?? '')) !== 'https'
        || trim((string) ($parts['host'] ?? '')) === ''
    ) {
        error_log('Operations calendar endpoint must be a valid HTTPS URL.');
        return [
            'configured' => true,
            'available' => false,
            'events' => [],
        ];
    }

    $timezone = new DateTimeZone(date_default_timezone_get());
    $from = new DateTimeImmutable($startDate . ' 00:00:00', $timezone);
    $to = new DateTimeImmutable($endDate . ' 23:59:59', $timezone);

    $separator = str_contains($endpoint, '?') ? '&' : '?';
    $url = $endpoint . $separator . http_build_query([
        'from' => $from->format(DATE_ATOM),
        'to' => $to->format(DATE_ATOM),
    ]);

    if (!function_exists('curl_init')) {
        error_log('Operations calendar requires cURL.');
        return [
            'configured' => true,
            'available' => false,
            'events' => [],
        ];
    }

    $ch = curl_init($url);
    if ($ch === false) {
        return [
            'configured' => true,
            'available' => false,
            'events' => [],
        ];
    }

    curl_setopt_array($ch, [
        CURLOPT_HTTPGET => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_TIMEOUT => 6,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'X-Elahi-Calendar-Token: ' . $token,
        ],
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_MAXREDIRS => 0,
        CURLOPT_USERAGENT => 'Elahimiavagh-Leave-Management/1.0',
    ]);

    $response = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);

    if (!is_string($response) || $status !== 200) {
        error_log('Operations calendar request failed with HTTP ' . $status . '.');
        return [
            'configured' => true,
            'available' => false,
            'events' => [],
        ];
    }

    try {
        $decoded = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    } catch (Throwable $e) {
        error_log('Operations calendar returned invalid JSON.');
        return [
            'configured' => true,
            'available' => false,
            'events' => [],
        ];
    }

    if (!is_array($decoded) || !is_array($decoded['events'] ?? null)) {
        return [
            'configured' => true,
            'available' => false,
            'events' => [],
        ];
    }

    $events = [];
    foreach (array_slice($decoded['events'], 0, 500) as $event) {
        if (!is_array($event)) {
            continue;
        }

        $source = (string) ($event['source_module'] ?? '');
        if (!in_array($source, ['tour', 'umrah'], true)) {
            continue;
        }

        $uid = trim((string) ($event['event_uid'] ?? ''));
        $title = trim((string) ($event['title'] ?? ''));
        $startAt = trim((string) ($event['start_at'] ?? ''));
        $endAt = trim((string) ($event['end_at'] ?? ''));

        if ($uid === '' || $title === '' || $startAt === '' || $endAt === '') {
            continue;
        }

        $events[$uid] = [
            'event_uid' => $uid,
            'source_module' => $source,
            'event_type' => (string) ($event['event_type'] ?? ''),
            'title' => mb_substr($title, 0, 255),
            'start_at' => $startAt,
            'end_at' => $endAt,
            'location' => isset($event['location']) ? mb_substr(trim((string) $event['location']), 0, 255) : null,
            'public_url' => isset($event['public_url']) ? trim((string) $event['public_url']) : null,
        ];
    }

    return [
        'configured' => true,
        'available' => true,
        'events' => array_values($events),
    ];
}

function operations_calendar_context(string $startDate, string $endDate): array
{
    if (!operations_calendar_enabled()) {
        return [
            'configured' => false,
            'available' => false,
            'count' => 0,
            'peak_operations' => 0,
            'busy_days' => 0,
            'by_source' => ['tour' => 0, 'umrah' => 0],
            'events' => [],
        ];
    }

    $start = parse_leave_date($startDate);
    $end = parse_leave_date($endDate);

    if ($start === null || $end === null || $end < $start) {
        throw new InvalidArgumentException('Geçersiz operasyon takvimi tarih aralığı.');
    }

    $eventsByUid = [];
    $cursor = $start;

    while ($cursor <= $end) {
        $windowEnd = $cursor->modify('+92 days');
        if ($windowEnd > $end) {
            $windowEnd = $end;
        }

        $window = operations_calendar_fetch_window(
            $cursor->format('Y-m-d'),
            $windowEnd->format('Y-m-d')
        );

        if (!$window['available']) {
            return [
                'configured' => true,
                'available' => false,
                'count' => 0,
                'peak_operations' => 0,
                'busy_days' => 0,
                'by_source' => ['tour' => 0, 'umrah' => 0],
                'events' => [],
            ];
        }

        foreach ((array) $window['events'] as $event) {
            $eventsByUid[(string) $event['event_uid']] = $event;
        }

        $cursor = $windowEnd->modify('+1 day');
    }

    $timezone = new DateTimeZone(date_default_timezone_get());
    $dailyCounts = [];
    $bySource = ['tour' => 0, 'umrah' => 0];

    foreach ($eventsByUid as $event) {
        $source = (string) $event['source_module'];
        if (isset($bySource[$source])) {
            $bySource[$source]++;
        }

        try {
            $eventStart = new DateTimeImmutable((string) $event['start_at'], new DateTimeZone('UTC'));
            $eventEnd = new DateTimeImmutable((string) $event['end_at'], new DateTimeZone('UTC'));
        } catch (Throwable) {
            continue;
        }

        $localStart = $eventStart->setTimezone($timezone)->setTime(0, 0);
        $localEnd = $eventEnd->setTimezone($timezone)->setTime(0, 0);

        if ($localStart < $start) {
            $localStart = $start;
        }
        if ($localEnd > $end) {
            $localEnd = $end;
        }

        for ($day = $localStart; $day <= $localEnd; $day = $day->modify('+1 day')) {
            $key = $day->format('Y-m-d');
            $dailyCounts[$key] = ($dailyCounts[$key] ?? 0) + 1;
        }
    }

    $events = array_values($eventsByUid);
    usort($events, static function (array $a, array $b): int {
        return strcmp((string) $a['start_at'], (string) $b['start_at']);
    });

    return [
        'configured' => true,
        'available' => true,
        'count' => count($events),
        'peak_operations' => $dailyCounts !== [] ? max($dailyCounts) : 0,
        'busy_days' => count(array_filter($dailyCounts, static fn (int $count): bool => $count > 0)),
        'by_source' => $bySource,
        'events' => array_slice($events, 0, 20),
    ];
}
