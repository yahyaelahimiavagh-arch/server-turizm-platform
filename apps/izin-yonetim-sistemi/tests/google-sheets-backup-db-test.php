<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

$pdo = db();

function sheets_backup_assert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
    echo "[PASS] {$message}\n";
}

$pdo->exec('DELETE FROM google_sheet_sync_queue');
$pdo->exec('DELETE FROM google_sheet_backup_registry');
$pdo->exec('DELETE FROM leave_attachments');
$pdo->exec('DELETE FROM leave_request_days');
$pdo->exec('DELETE FROM leave_requests');
$pdo->exec('DELETE FROM annual_leave_entitlements');
$pdo->exec('DELETE FROM annual_allowances');
$pdo->exec('DELETE FROM audit_log');
$pdo->exec('DELETE FROM users');

$hash = password_hash('sheet-backup-test-password', PASSWORD_DEFAULT);
$insert = $pdo->prepare(
    "INSERT INTO users
     (full_name, email, password_hash, role, is_active, hire_date, birth_date)
     VALUES
     ('Sheets Test Employee', 'sheets-test@example.invalid', :password_hash, 'employee', 1, '2025-02-25', '1999-02-25')"
);
$insert->execute(['password_hash' => $hash]);
$userId = (int) $pdo->lastInsertId();

sync_annual_leave_entitlements($pdo, $userId, '2026-09-18');

$snapshot = google_sheets_backup_build_employee_snapshot($pdo, $userId);
sheets_backup_assert(is_array($snapshot), 'employee backup snapshot created');
sheets_backup_assert(($snapshot['employee_ref'] ?? '') === google_sheets_backup_employee_ref($userId), 'stable employee backup ref created');

$payload = json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
sheets_backup_assert(!str_contains($payload, 'password_hash'), 'password hash excluded from Google Sheets payload');
sheets_backup_assert(!str_contains($payload, 'sheet-backup-test-password'), 'password excluded from Google Sheets payload');
sheets_backup_assert(!array_key_exists('employee_comment', ($snapshot['leave_requests'][0] ?? [])), 'free-text employee notes excluded from snapshot');

$eventUuid = google_sheets_backup_queue_payload($pdo, $snapshot, 'full_backup');
sheets_backup_assert(strlen($eventUuid) === 36, 'backup queue event UUID created');

$writes = [];
$writer = static function (PDO $pdo, array $row, array $snapshot) use (&$writes): array {
    $writes[] = [
        'event_uuid' => (string) $row['event_uuid'],
        'employee_ref' => (string) $snapshot['employee_ref'],
        'deleted' => (($snapshot['profile']['backup_status'] ?? '') === 'deleted'),
    ];

    return [
        'sheet_id' => 123456,
        'title' => (($snapshot['profile']['backup_status'] ?? '') === 'deleted' ? 'DELETED - ' : '')
            . (string) $snapshot['employee_ref']
            . ' - '
            . (string) $snapshot['profile']['full_name'],
    ];
};

$result = google_sheets_backup_process_queue($pdo, 20, $writer);
sheets_backup_assert((int) $result['succeeded'] === 1, 'queued employee snapshot processed through pluggable writer');
sheets_backup_assert(count($writes) === 1, 'writer invoked exactly once');

$registry = $pdo->query('SELECT * FROM google_sheet_backup_registry LIMIT 1')->fetch();
sheets_backup_assert(is_array($registry), 'backup registry row created');
sheets_backup_assert((string) $registry['status'] === 'active', 'active employee registry status stored');
sheets_backup_assert((int) $registry['sheet_id'] === 123456, 'sheet id stored in backup registry');

$deletedSnapshot = google_sheets_backup_build_employee_snapshot($pdo, $userId);
$deletedSnapshot['profile']['backup_status'] = 'deleted';
$deletedSnapshot['profile']['deleted_at'] = '2026-09-18T12:00:00+03:00';
google_sheets_backup_queue_payload($pdo, $deletedSnapshot, 'employee_deleted');

$pdo->prepare('DELETE FROM annual_leave_entitlements WHERE user_id=:id')->execute(['id' => $userId]);
$pdo->prepare('DELETE FROM annual_allowances WHERE user_id=:id')->execute(['id' => $userId]);
$pdo->prepare('DELETE FROM users WHERE id=:id')->execute(['id' => $userId]);

$result = google_sheets_backup_process_queue($pdo, 20, $writer);
sheets_backup_assert((int) $result['succeeded'] === 1, 'deleted employee archive event survives source-user deletion');

$registry = $pdo->query('SELECT * FROM google_sheet_backup_registry LIMIT 1')->fetch();
sheets_backup_assert((string) $registry['status'] === 'deleted', 'deleted employee remains archived in backup registry');
sheets_backup_assert(str_starts_with((string) $registry['sheet_title'], 'DELETED - '), 'deleted employee sheet is retained with archive title');

$failed = (int) $pdo->query("SELECT COUNT(*) FROM google_sheet_sync_queue WHERE status='failed'")->fetchColumn();
sheets_backup_assert($failed === 0, 'Google Sheets backup queue regression has no failed rows');

echo "\nGoogle Sheets backup DB regression: PASS\n";
