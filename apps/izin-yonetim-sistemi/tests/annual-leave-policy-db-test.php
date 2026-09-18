<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

$pdo = db();

function policy_assert(bool $condition, string $message): void
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
$pdo->exec("DELETE FROM users WHERE role='employee'");

$insertUser = $pdo->prepare(
    "INSERT INTO users (full_name, email, password_hash, role, is_active, hire_date, birth_date)
     VALUES (:full_name, :email, :password_hash, 'employee', 1, :hire_date, :birth_date)"
);
$hash = password_hash('runtime-policy-password', PASSWORD_DEFAULT);

$insertUser->execute([
    'full_name' => 'Policy Standard',
    'email' => 'policy-standard@example.invalid',
    'password_hash' => $hash,
    'hire_date' => '2025-09-18',
    'birth_date' => '1999-02-25',
]);
$standardId = (int) $pdo->lastInsertId();

$before = sync_annual_leave_entitlements($pdo, $standardId, '2026-09-17');
policy_assert($before === [], 'no statutory annual leave entitlement before first anniversary');

$first = sync_annual_leave_entitlements($pdo, $standardId, '2026-09-18');
policy_assert(count($first) === 1, 'first entitlement created on first service anniversary');
policy_assert((float) $first[0]['company_policy_days'] === 15.0, 'Server Turizm first-tier company policy is 15 days');
policy_assert((float) $first[0]['legal_minimum_days'] === 14.0, 'first-tier legal floor is 14 days');
policy_assert((float) $first[0]['entitlement_days'] === 15.0, 'effective first entitlement uses higher company policy');

$twoYears = sync_annual_leave_entitlements($pdo, $standardId, '2027-09-18');
policy_assert(count($twoYears) === 2, 'second service-year entitlement accumulates');
$balance = annual_leave_balance($pdo, $standardId, '2027-09-18');
policy_assert((float) $balance['entitlement'] === 30.0, 'unused annual leave carries forward instead of resetting');
policy_assert($balance['carryover_enabled'] === true, 'carryover policy is enabled');
policy_assert($balance['cashout_while_active_allowed'] === false, 'active-employment cashout is disabled');

$leaveTypeId = (int) $pdo->query(
    "SELECT id FROM leave_types WHERE deducts_annual_allowance=1 ORDER BY id LIMIT 1"
)->fetchColumn();
policy_assert($leaveTypeId > 0, 'annual leave type fixture exists');

$requestStmt = $pdo->prepare(
    "INSERT INTO leave_requests
     (user_id, leave_type_id, start_date, end_date, duration_type, requested_days, status)
     VALUES (:user_id, :leave_type_id, :start_date, :end_date, 'full_day', :requested_days, 'approved')"
);
$requestStmt->execute([
    'user_id' => $standardId,
    'leave_type_id' => $leaveTypeId,
    'start_date' => '2026-10-05',
    'end_date' => '2026-10-09',
    'requested_days' => 5.0,
]);
$requestId = (int) $pdo->lastInsertId();

$dayStmt = $pdo->prepare(
    "INSERT INTO leave_request_days (leave_request_id, leave_date, day_value)
     VALUES (:request_id, :leave_date, 1.00)"
);
foreach (['2026-10-05','2026-10-06','2026-10-07','2026-10-08','2026-10-09'] as $date) {
    $dayStmt->execute([
        'request_id' => $requestId,
        'leave_date' => $date,
    ]);
}

$balanceAfterUse = annual_leave_balance($pdo, $standardId, '2027-09-18');
policy_assert((float) $balanceAfterUse['approved'] === 5.0, 'approved annual leave is deducted from cumulative balance');
policy_assert((float) $balanceAfterUse['remaining'] === 25.0, 'carryover balance remains after approved leave');

$insertUser->execute([
    'full_name' => 'Policy Age Fifty',
    'email' => 'policy-age50@example.invalid',
    'password_hash' => $hash,
    'hire_date' => '2025-09-18',
    'birth_date' => '1976-09-18',
]);
$ageId = (int) $pdo->lastInsertId();
$ageEntitlements = sync_annual_leave_entitlements($pdo, $ageId, '2026-09-18');
policy_assert(count($ageEntitlements) === 1, 'age-rule employee earns first entitlement');
policy_assert((float) $ageEntitlements[0]['age_minimum_days'] === 20.0, 'age 50 rule raises legal minimum to 20 days');
policy_assert((float) $ageEntitlements[0]['entitlement_days'] === 20.0, 'age-protected employee receives 20 days despite 15-day company tier');

$insertUser->execute([
    'full_name' => 'Policy Sixth Year',
    'email' => 'policy-six@example.invalid',
    'password_hash' => $hash,
    'hire_date' => '2020-09-18',
    'birth_date' => '1990-01-01',
]);
$sixthId = (int) $pdo->lastInsertId();
$sixthEntitlements = sync_annual_leave_entitlements($pdo, $sixthId, '2026-09-18');
policy_assert(count($sixthEntitlements) === 6, 'six completed service years create six entitlement buckets');
policy_assert((float) $sixthEntitlements[5]['entitlement_days'] === 20.0, 'sixth service-year entitlement uses 20-day tier');

$insertUser->execute([
    'full_name' => 'Policy New Hire',
    'email' => 'policy-new@example.invalid',
    'password_hash' => $hash,
    'hire_date' => '2026-01-01',
    'birth_date' => '2000-01-01',
]);
$newHireId = (int) $pdo->lastInsertId();

$blocked = false;
try {
    annual_leave_assert_request_available(
        $pdo,
        $newHireId,
        [['date' => '2026-12-15', 'value' => 1.0]]
    );
} catch (DomainException $e) {
    $blocked = true;
}
policy_assert($blocked, 'future unearned first-year leave is blocked');

$allowedAfterAnniversary = true;
try {
    annual_leave_assert_request_available(
        $pdo,
        $newHireId,
        [['date' => '2027-01-02', 'value' => 1.0]]
    );
} catch (DomainException $e) {
    $allowedAfterAnniversary = false;
}
policy_assert($allowedAfterAnniversary, 'earned entitlement is available after first anniversary');

$saturday = calculate_leave_days(
    '2026-09-19',
    '2026-09-19',
    'full_day',
    null
);
policy_assert((float) $saturday['total'] === 1.0, 'Saturday half-day work still deducts one full leave day');

$fridayToMonday = calculate_leave_days(
    '2026-09-18',
    '2026-09-21',
    'full_day',
    null
);
policy_assert((float) $fridayToMonday['total'] === 3.0, 'Friday through Monday deducts Friday + Saturday + Monday = 3 days');

echo "\nService-year annual leave policy DB test: PASS\n";
