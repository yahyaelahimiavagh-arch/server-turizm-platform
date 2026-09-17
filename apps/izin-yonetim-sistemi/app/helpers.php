<?php

declare(strict_types=1);

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function base_path(string $path = ''): string
{
    $app = app_config('app');
    $base = rtrim((string) ($app['base_path'] ?? '/izin'), '/');
    $path = ltrim($path, '/');

    return $path === '' ? $base . '/' : $base . '/' . $path;
}

function redirect(string $path): never
{
    header('Location: ' . base_path($path), true, 302);
    exit;
}

function is_post(): bool
{
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function flash(string $key, ?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['_flash'][$key] = $message;
        return null;
    }

    if (!isset($_SESSION['_flash'][$key])) {
        return null;
    }

    $value = (string) $_SESSION['_flash'][$key];
    unset($_SESSION['_flash'][$key]);

    return $value;
}

function old(string $key, string $default = ''): string
{
    return e($_POST[$key] ?? $default);
}

function format_days(float $value): string
{
    $formatted = number_format($value, 2, '.', '');
    return rtrim(rtrim($formatted, '0'), '.');
}

function status_label(string $status): string
{
    return match ($status) {
        'approved' => 'Onaylandı',
        'rejected' => 'Reddedildi',
        default => 'Bekliyor',
    };
}

function status_badge_class(string $status): string
{
    return match ($status) {
        'approved' => 'badge-approved',
        'rejected' => 'badge-rejected',
        default => 'badge-pending',
    };
}
