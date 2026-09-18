<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$root = dirname(__DIR__);
$failures = 0;

function check_item(string $label, bool $ok, string $detail = ''): void
{
    global $failures;
    $prefix = $ok ? '[PASS]' : '[FAIL]';
    echo $prefix . ' ' . $label;
    if ($detail !== '') {
        echo ' — ' . $detail;
    }
    echo PHP_EOL;
    if (!$ok) {
        $failures++;
    }
}

function safe_error(Throwable $e): string
{
    if ($e instanceof PDOException) {
        return 'Database operation failed; credentials/details are intentionally hidden.';
    }
    return $e->getMessage();
}

echo "Leave Management System — Deployment Preflight\n";
echo str_repeat('=', 58) . PHP_EOL;

check_item('PHP >= 8.1', PHP_VERSION_ID >= 80100, PHP_VERSION);

$requiredExtensions = ['pdo', 'pdo_mysql', 'mbstring', 'fileinfo'];
foreach ($requiredExtensions as $extension) {
    check_item('PHP extension: ' . $extension, extension_loaded($extension));
}

$configFile = getenv('IZIN_CONFIG_FILE') ?: null;
$documentRoot = $_SERVER['DOCUMENT_ROOT'] ?? dirname($root);
$defaultConfig = dirname(rtrim((string) $documentRoot, '/\\')) . '/izin-private/config.php';
$configPath = $configFile ?: $defaultConfig;
check_item('Private config readable', is_file($configPath) && is_readable($configPath), $configPath);

if ($failures === 0) {
    try {
        require_once $root . '/app/config.php';
        require_once $root . '/app/db.php';

        $config = load_app_config();
        $app = $config['app'] ?? [];
        $dbConfig = $config['db'] ?? [];

        $appOk = isset($app['base_path'], $app['timezone'], $app['session_name'])
            && (string) $app['base_path'] === '/izin'
            && (string) $app['timezone'] === 'Europe/Istanbul';
        check_item('Application config', $appOk, 'base_path/timezone/session');

        $setupKey = (string) ($app['setup_key'] ?? '');
        check_item(
            'Strong setup_key present',
            strlen($setupKey) >= 32 && !str_starts_with($setupKey, 'CHANGE_'),
            'minimum 32 characters'
        );

        $dbFieldsOk = true;
        foreach (['host', 'name', 'user', 'password'] as $field) {
            if (!isset($dbConfig[$field]) || trim((string) $dbConfig[$field]) === '' || str_contains((string) $dbConfig[$field], 'CHANGE_ME')) {
                $dbFieldsOk = false;
            }
        }
        check_item('Database config populated', $dbFieldsOk);

        $pdo = db();
        check_item('MySQL connection', true);

        $charset = (string) $pdo->query("SELECT @@character_set_connection")->fetchColumn();
        check_item('Connection charset utf8mb4', strtolower($charset) === 'utf8mb4', $charset);

        $expectedTables = [
            'users',
            'leave_types',
            'annual_allowances',
            'public_holidays',
            'leave_requests',
            'leave_request_days',
            'leave_attachments',
            'audit_log',
            'app_settings',
            'login_failures',
        ];

        $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
        foreach ($expectedTables as $table) {
            check_item('DB table: ' . $table, in_array($table, $tables, true));
        }

        $stmt = $pdo->prepare("SELECT setting_value FROM app_settings WHERE setting_key = 'default_annual_allowance_days' LIMIT 1");
        $stmt->execute();
        $defaultAllowance = $stmt->fetchColumn();
        check_item(
            'Default annual allowance seeded',
            $defaultAllowance !== false && is_numeric($defaultAllowance),
            $defaultAllowance === false ? 'missing' : (string) $defaultAllowance . ' days'
        );

        $leaveTypes = (int) $pdo->query('SELECT COUNT(*) FROM leave_types')->fetchColumn();
        check_item('Leave types seeded', $leaveTypes >= 4, (string) $leaveTypes . ' rows');

        $annualTypes = (int) $pdo->query('SELECT COUNT(*) FROM leave_types WHERE deducts_annual_allowance = 1')->fetchColumn();
        check_item('At least one allowance-deducting type', $annualTypes >= 1, (string) $annualTypes . ' rows');

        $requiresAttachmentColumn = $pdo->query("SHOW COLUMNS FROM leave_types LIKE 'requires_attachment'")->fetch();
        check_item('Leave attachment policy column', $requiresAttachmentColumn !== false);

        $workingWeekdaysStmt = $pdo->prepare("SELECT setting_value FROM app_settings WHERE setting_key = 'working_weekdays' LIMIT 1");
        $workingWeekdaysStmt->execute();
        $workingWeekdays = $workingWeekdaysStmt->fetchColumn();
        check_item('Working-week policy seeded', $workingWeekdays !== false && trim((string) $workingWeekdays) !== '');

        $attachmentLimitStmt = $pdo->prepare("SELECT setting_value FROM app_settings WHERE setting_key = 'attachment_max_mb' LIMIT 1");
        $attachmentLimitStmt->execute();
        $attachmentLimit = $attachmentLimitStmt->fetchColumn();
        check_item('Attachment size policy seeded', $attachmentLimit !== false && is_numeric($attachmentLimit));

        $staffingStmt = $pdo->prepare("SELECT setting_value FROM app_settings WHERE setting_key = 'max_concurrent_leave_employees' LIMIT 1");
        $staffingStmt->execute();
        $staffingLimit = $staffingStmt->fetchColumn();
        check_item(
            'Staffing overlap policy seeded',
            $staffingLimit !== false && is_numeric($staffingLimit) && (int) $staffingLimit >= 0
        );

        $calendarExport = $config['calendar_export'] ?? [];
        $calendarExport = is_array($calendarExport) ? $calendarExport : [];
        $calendarExportEnabled = ($calendarExport['enabled'] ?? false) === true;
        $calendarExportToken = trim((string) ($calendarExport['token'] ?? ''));
        check_item(
            'Approved-leave calendar export config',
            !$calendarExportEnabled || strlen($calendarExportToken) >= 32,
            $calendarExportEnabled ? 'enabled; token length checked' : 'disabled'
        );

        $operationsCalendar = $config['operations_calendar'] ?? [];
        $operationsCalendar = is_array($operationsCalendar) ? $operationsCalendar : [];
        $operationsCalendarEnabled = ($operationsCalendar['enabled'] ?? false) === true;
        $operationsEndpoint = trim((string) ($operationsCalendar['endpoint'] ?? ''));
        $operationsToken = trim((string) ($operationsCalendar['token'] ?? ''));
        $operationsEndpointValid = !$operationsCalendarEnabled
            || (
                filter_var($operationsEndpoint, FILTER_VALIDATE_URL) !== false
                && str_starts_with(strtolower($operationsEndpoint), 'https://')
                && strlen($operationsToken) >= 32
            );
        check_item(
            'Operations calendar client config',
            $operationsEndpointValid,
            $operationsCalendarEnabled ? 'enabled; HTTPS endpoint/token checked' : 'disabled'
        );
    } catch (Throwable $e) {
        check_item('Runtime/database preflight', false, safe_error($e));
    }
}

echo str_repeat('-', 58) . PHP_EOL;
if ($failures === 0) {
    echo "PREFLIGHT PASS\n";
    exit(0);
}

echo 'PREFLIGHT FAIL — ' . $failures . " check(s) failed.\n";
exit(1);
