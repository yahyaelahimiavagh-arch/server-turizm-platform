<?php

declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: private, no-store, max-age=0');
header('X-Robots-Tag: noindex, nofollow', true);
header('X-Content-Type-Options: nosniff');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    http_response_code(405);
    header('Allow: GET');
    echo json_encode(['ok' => false, 'message' => 'Method not allowed.']);
    exit;
}

if (!leave_calendar_export_enabled()) {
    http_response_code(503);
    echo json_encode(['ok' => false, 'message' => 'Calendar export is not configured.']);
    exit;
}

if (!leave_calendar_export_authorized()) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => 'Unauthorized.']);
    exit;
}

$fromDate = trim((string) ($_GET['from'] ?? date('Y-m-d')));
$toDate = trim((string) ($_GET['to'] ?? date('Y-m-d', strtotime('+31 days'))));

try {
    $events = leave_calendar_export_events($fromDate, $toDate);

    echo json_encode([
        'ok' => true,
        'schema_version' => '1.0',
        'window' => [
            'from' => $fromDate,
            'to' => $toDate,
        ],
        'count' => count($events),
        'events' => $events,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (InvalidArgumentException $e) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Calendar export failed.']);
}
