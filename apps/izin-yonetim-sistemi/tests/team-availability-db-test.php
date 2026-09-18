<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

$pdo = db();

function team_assert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }

    echo "[PASS] {$message}\n";
}

$pdo->exec("DELETE FROM leave_attachments");
$pdo->exec("DELETE FROM leave_request_days");
$pdo->exec("DELETE FROM leave_requests");
$pdo->exec("DELETE FROM annual_leave_entitlements");
$pdo->exec("DELETE FROM annual_allowances");
$pdo->exec("DELETE FROM users");

$passwordHash = password_hash('CI-runtime-only-password', PASSWORD_DEFAULT);
$userStmt = $pdo->prepare(
    "INSERT INTO users (full_name, email, password_hash, role, is_active)
     VALUES (:full_name, :email, :password_hash, 'employee', 1)"
);

$userIds = [];
foreach (['A', 'B', 'C', 'D'] as $name) {
    $userStmt->execute([
        'full_name' => 'Runtime ' . $name,
        'email' => 'runtime-' . strtolower($name) . '@example.invalid',
        'password_hash' => $passwordHash,
    ]);
    $userIds[$name] = (int) $pdo->lastInsertId();
}

$leaveTypeId = (int) $pdo->query(
    "SELECT id FROM leave_types WHERE code = 'annual' LIMIT 1"
)->fetchColumn();
team_assert($leaveTypeId > 0, 'annual leave type fixture available');

$requestStmt = $pdo->prepare(
    "INSERT INTO leave_requests
     (user_id, leave_type_id, start_date, end_date, duration_type, requested_days, status)
     VALUES
     (:user_id, :leave_type_id, :start_date, :end_date, 'full_day', 1.00, :status)"
);
$dayStmt = $pdo->prepare(
    "INSERT INTO leave_request_days (leave_request_id, leave_date, day_value)
     VALUES (:request_id, :leave_date, 1.00)"
);

foreach ([
    ['user' => 'B', 'status' => 'approved'],
    ['user' => 'C', 'status' => 'pending'],
] as $fixture) {
    $requestStmt->execute([
        'user_id' => $userIds[$fixture['user']],
        'leave_type_id' => $leaveTypeId,
        'start_date' => '2027-01-11',
        'end_date' => '2027-01-11',
        'status' => $fixture['status'],
    ]);
    $requestId = (int) $pdo->lastInsertId();
    $dayStmt->execute([
        'request_id' => $requestId,
        'leave_date' => '2027-01-11',
    ]);
}

$repo = new LeaveRepository($pdo);
$context = $repo->teamAvailabilityContext(
    [
        ['date' => '2027-01-11', 'value' => 1.0],
        ['date' => '2027-01-12', 'value' => 1.0],
    ],
    $userIds['A']
);

team_assert((int) $context['active_employees'] === 4, 'active employee count is aggregate-only and correct');
team_assert((int) $context['max_approved_other'] === 1, 'approved overlap count detected');
team_assert((int) $context['max_pending_other'] === 1, 'pending overlap count detected');
team_assert((int) $context['max_potential_leave'] === 3, 'requester plus overlapping leave produces potential concurrent count');
team_assert((int) $context['min_potential_on_duty'] === 1, 'minimum on-duty staffing calculated');
team_assert($context['warning'] === true, 'configured concurrent leave threshold raises warning');
team_assert(count((array) $context['dates']) === 2, 'team context preserves requested working-day coverage');

foreach ((array) $context['dates'] as $row) {
    team_assert(!array_key_exists('full_name', $row), 'employee preview does not expose coworker names');
    team_assert(!array_key_exists('user_id', $row), 'employee preview does not expose coworker ids');
}

echo "\nTeam availability DB test: PASS\n";
