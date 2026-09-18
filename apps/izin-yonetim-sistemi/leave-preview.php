<?php

declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

$user = require_login();
if (($user['role'] ?? '') === 'admin') {
    http_response_code(403);
    echo json_encode(['ok' => false, 'message' => 'Bu işlem çalışan hesabı içindir.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!is_post()) {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Geçersiz istek yöntemi.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$submitted = $_POST['_csrf'] ?? '';
$stored = $_SESSION['_csrf_token'] ?? '';
if (!is_string($submitted) || !is_string($stored) || $stored === '' || !hash_equals($stored, $submitted)) {
    http_response_code(419);
    echo json_encode(['ok' => false, 'message' => 'Oturum doğrulaması başarısız.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$leaveTypeId = filter_var($_POST['leave_type_id'] ?? null, FILTER_VALIDATE_INT);
$startDate = trim((string) ($_POST['start_date'] ?? ''));
$endDate = trim((string) ($_POST['end_date'] ?? ''));
$durationType = (string) ($_POST['duration_type'] ?? 'full_day');
$halfDayPeriod = $durationType === 'half_day' ? (string) ($_POST['half_day_period'] ?? '') : null;

if (!$leaveTypeId || $startDate === '' || $endDate === '') {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'İzin türü ve tarihleri seçin.'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $repo = new LeaveRepository(db());
    $leaveType = $repo->findLeaveType((int) $leaveTypeId);

    if (!$leaveType || (int) $leaveType['is_active'] !== 1) {
        throw new DomainException('Seçilen izin türü kullanılamıyor.');
    }

    $calculation = calculate_leave_days($startDate, $endDate, $durationType, $halfDayPeriod);
    $calculation['deducts_annual_allowance'] = (int) $leaveType['deducts_annual_allowance'] === 1;
    $calculation['requires_attachment'] = (int) ($leaveType['requires_attachment'] ?? 0) === 1;
    $calculation['leave_type_name'] = (string) $leaveType['name'];
    $calculation['working_weekdays_text'] = working_weekdays_text();

    $teamContext = $repo->teamAvailabilityContext(
        is_array($calculation['days'] ?? null) ? $calculation['days'] : [],
        (int) $user['id']
    );

    $operationsContext = operations_calendar_context($startDate, $endDate);

    echo json_encode([
        'ok' => true,
        'calculation' => $calculation,
        'team_context' => $teamContext,
        'operations_context' => $operationsContext,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (InvalidArgumentException|DomainException $e) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Hesaplama sırasında bir hata oluştu.'], JSON_UNESCAPED_UNICODE);
}
