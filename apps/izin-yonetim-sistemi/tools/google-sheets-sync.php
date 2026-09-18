<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__) . '/app/bootstrap.php';

$limit = 20;
foreach ($argv as $arg) {
    if (preg_match('/^--limit=(\d+)$/', (string) $arg, $matches)) {
        $limit = max(1, min(100, (int) $matches[1]));
    }
}

try {
    $result = google_sheets_backup_process_queue(db(), $limit);
    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . PHP_EOL;
    exit(((int) ($result['failed'] ?? 0)) > 0 ? 2 : 0);
} catch (Throwable $e) {
    fwrite(STDERR, google_sheets_backup_safe_error($e) . PHP_EOL);
    exit(1);
}
