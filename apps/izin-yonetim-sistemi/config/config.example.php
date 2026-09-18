<?php

declare(strict_types=1);

return [
    'app' => [
        'env' => 'production',
        'base_path' => '/izin',
        'timezone' => 'Europe/Istanbul',
        'session_name' => 'leave_management',
        'setup_key' => 'CHANGE_TO_A_LONG_RANDOM_VALUE_BEFORE_FIRST_SETUP',
    ],
    'db' => [
        'host' => 'localhost',
        'port' => 3306,
        'name' => 'CHANGE_ME',
        'user' => 'CHANGE_ME',
        'password' => 'CHANGE_ME',
        'charset' => 'utf8mb4',
    ],
    'turnstile' => [
        'enabled' => false,
        'site_key' => '',
        'secret_key' => '',
    ],
    'storage' => [
        // Keep this outside public_html. Leave empty to use the secure default:
        // /home/<cpanel-user>/izin-private/attachments
        'attachments_dir' => '',
    ],
    'operations_calendar' => [
        // Optional machine-to-machine integration with the WordPress Operations Calendar.
        // Keep the token private and use the same 32+ character value in WordPress
        // constant ELAHI_OPS_CALENDAR_INTERNAL_TOKEN.
        'enabled' => false,
        'endpoint' => '',
        'token' => '',
    ],
    'calendar_export' => [
        // Optional approved-leave projection feed for the WordPress Operations Calendar.
        // Keep this token private and configure the same 32+ character value in the
        // WordPress leave-calendar adapter connector.
        'enabled' => false,
        'token' => '',
    ],
];
