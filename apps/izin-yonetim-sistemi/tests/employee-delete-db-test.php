<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

$pdo = db();
$pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_general_ci");

function employee_delete_assert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }

    echo "[PASS] {$message}\n";
}

$pdo->exec('DELETE FROM leave_attachments');
$pdo->exec('DELETE FROM leave_request_days');
$pdo->exec('DELETE FROM leave_requests');
$pdo->exec('DELETE FROM annual_leave_entitlements');
$pdo->exec('DELETE FROM annual_allowances');
$pdo->exec('DELETE FROM audit_log');
$pdo->exec('DELETE FROM users');

$hash = password_hash('runtime-delete-password', PASSWORD_DEFAULT);

$insertAdmin = $pdo->prepare(
    "INSERT INTO users (full_name, email, password_hash, role, is_active)
     VALUES ('Runtime Admin', 'runtime-admin-delete@example.invalid', :password_hash, 'admin', 1)"
);
$insertAdmin->execute(['password_hash' => $hash]);
$adminId = (int) $pdo->lastInsertId();

$insertEmployee = $pdo->prepare(
    "INSERT INTO users (full_name, email, password_hash, role, is_active, hire_date, birth_date)
     VALUES ('Runtime Delete Employee', 'runtime-delete@example.invalid', :password_hash, 'employee', 1, '2025-01-01', '1995-01-01')"
);
$insertEmployee->execute(['password_hash' => $hash]);
$employeeId = (int) $pdo->lastInsertId();

sync_annual_leave_entitlements($pdo, $employeeId, '2026-09-18');

$leaveTypeId = (int) $pdo->query(
    "SELECT id FROM leave_types WHERE code='medical' LIMIT 1"
)->fetchColumn();
employee_delete_assert($leaveTypeId > 0, 'medical leave type fixture exists');

$requestStmt = $pdo->prepare(
    "INSERT INTO leave_requests
     (user_id, leave_type_id, start_date, end_date, duration_type, requested_days, status, employee_comment)
     VALUES (:user_id, :leave_type_id, '2026-09-22', '2026-09-22', 'full_day', 1.00, 'approved', 'delete fixture')"
);
$requestStmt->execute([
    'user_id' => $employeeId,
    'leave_type_id' => $leaveTypeId,
]);
$requestId = (int) $pdo->lastInsertId();

$insertOtherEmployee = $pdo->prepare(
    "INSERT INTO users (full_name, email, password_hash, role, is_active, hire_date, birth_date)
     VALUES ('Runtime Survivor Employee', 'runtime-survivor@example.invalid', :password_hash, 'employee', 1, '2025-01-01', '1994-01-01')"
);
$insertOtherEmployee->execute(['password_hash' => $hash]);
$survivorId = (int) $pdo->lastInsertId();

$survivorRequest = $pdo->prepare(
    "INSERT INTO leave_requests
     (user_id, leave_type_id, start_date, end_date, duration_type, requested_days, status, processed_by, processed_at)
     VALUES (:user_id, :leave_type_id, '2026-10-01', '2026-10-01', 'full_day', 1.00, 'approved', :processed_by, NOW())"
);
$survivorRequest->execute([
    'user_id' => $survivorId,
    'leave_type_id' => $leaveTypeId,
    'processed_by' => $employeeId,
]);
$survivorRequestId = (int) $pdo->lastInsertId();

$pdo->prepare(
    "INSERT INTO leave_request_days (leave_request_id, leave_date, day_value)
     VALUES (:request_id, '2026-09-22', 1.00)"
)->execute(['request_id' => $requestId]);

$storageDir = ensure_attachment_storage();
$storedName = str_repeat('a', 64);
$filePath = $storageDir . DIRECTORY_SEPARATOR . $storedName;
file_put_contents($filePath, 'runtime delete attachment');
@chmod($filePath, 0600);
$sha = hash_file('sha256', $filePath);

$pdo->prepare(
    "INSERT INTO leave_attachments
     (leave_request_id, uploaded_by, original_name, stored_name, mime_type, size_bytes, sha256)
     VALUES (:request_id, :uploaded_by, 'runtime-delete.txt.pdf', :stored_name, 'application/pdf', :size_bytes, :sha256)"
)->execute([
    'request_id' => $requestId,
    'uploaded_by' => $employeeId,
    'stored_name' => $storedName,
    'size_bytes' => filesize($filePath),
    'sha256' => $sha,
]);

audit_log_event(
    $pdo,
    $employeeId,
    'leave_request_created',
    'leave_request',
    $requestId,
    ['fixture' => true]
);

$repo = new UserRepository($pdo);

$wrongConfirmationBlocked = false;
try {
    $repo->deleteEmployeePermanently(
        $employeeId,
        $adminId,
        'wrong@example.invalid'
    );
} catch (DomainException) {
    $wrongConfirmationBlocked = true;
}
employee_delete_assert($wrongConfirmationBlocked, 'typed e-mail confirmation blocks accidental permanent delete');
employee_delete_assert($repo->find($employeeId) !== null, 'employee still exists after rejected delete confirmation');

$result = $repo->deleteEmployeePermanently(
    $employeeId,
    $adminId,
    'runtime-delete@example.invalid'
);

employee_delete_assert($repo->find($employeeId) === null, 'employee row permanently deleted');
employee_delete_assert((int) $pdo->query("SELECT COUNT(*) FROM leave_requests WHERE user_id={$employeeId}")->fetchColumn() === 0, 'employee leave requests purged');
employee_delete_assert((int) $pdo->query("SELECT COUNT(*) FROM annual_leave_entitlements WHERE user_id={$employeeId}")->fetchColumn() === 0, 'employee service-year entitlements purged');
employee_delete_assert((int) $pdo->query("SELECT COUNT(*) FROM leave_attachments WHERE uploaded_by={$employeeId}")->fetchColumn() === 0, 'employee attachment rows purged');
employee_delete_assert(!is_file($filePath), 'employee private attachment file removed from disk');
employee_delete_assert((int) ($result['leave_requests'] ?? -1) === 1, 'delete result reports purged request count');
employee_delete_assert((int) ($result['attachments'] ?? -1) === 1, 'delete result reports purged attachment count');

$survivorStmt = $pdo->prepare(
    'SELECT user_id, processed_by FROM leave_requests WHERE id = :id LIMIT 1'
);
$survivorStmt->execute(['id' => $survivorRequestId]);
$survivorRow = $survivorStmt->fetch();
employee_delete_assert(is_array($survivorRow), 'unrelated leave request survives employee deletion');
employee_delete_assert((int) $survivorRow['user_id'] === $survivorId, 'surviving request still belongs to the other employee');
employee_delete_assert($survivorRow['processed_by'] === null, 'reverse processed_by reference is cleared before user deletion');

$deleteAudit = $pdo->prepare(
    "SELECT actor_user_id, metadata_json
     FROM audit_log
     WHERE event_type='employee_permanently_deleted'
       AND entity_type='employee'
       AND entity_id=:entity_id
     ORDER BY id DESC
     LIMIT 1"
);
$deleteAudit->execute(['entity_id' => (string) $employeeId]);
$auditRow = $deleteAudit->fetch();
employee_delete_assert(is_array($auditRow), 'permanent deletion itself remains audited');
employee_delete_assert((int) $auditRow['actor_user_id'] === $adminId, 'deletion audit records the admin actor');

echo "\nEmployee permanent delete DB test: PASS\n";
