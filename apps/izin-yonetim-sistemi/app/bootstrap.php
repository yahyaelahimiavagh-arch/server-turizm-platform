<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

$config = load_app_config();
$app = $config['app'] ?? [];
$isProduction = ($app['env'] ?? 'production') === 'production';

if ($isProduction) {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
} else {
    ini_set('display_errors', '1');
}

ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.cookie_httponly', '1');

date_default_timezone_set((string) ($app['timezone'] ?? 'Europe/Istanbul'));

$isHttps = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

session_name((string) ($app['session_name'] ?? 'server_turizm_izin'));
session_set_cookie_params([
    'lifetime' => 0,
    'path' => rtrim((string) ($app['base_path'] ?? '/izin'), '/') . '/',
    'domain' => '',
    'secure' => $isHttps,
    'httponly' => true,
    'samesite' => 'Lax',
]);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Permissions-Policy: camera=(), microphone=(), geolocation=()");

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/leave-calculator.php';
require_once __DIR__ . '/repositories/UserRepository.php';
require_once __DIR__ . '/repositories/LeaveRepository.php';
require_once __DIR__ . '/repositories/ReportRepository.php';
