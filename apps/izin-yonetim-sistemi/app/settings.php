<?php

declare(strict_types=1);

/**
 * Database-backed application settings.
 *
 * Business rules belong in app_settings rather than PHP constants. Values are
 * loaded once per request and may be changed by authorized administrators.
 */
function app_settings_all(): array
{
    static $settings = null;

    if (is_array($settings)) {
        return $settings;
    }

    $settings = [];
    $stmt = db()->query('SELECT setting_key, setting_value FROM app_settings');
    foreach ($stmt->fetchAll() as $row) {
        $settings[(string) $row['setting_key']] = (string) $row['setting_value'];
    }

    return $settings;
}

function app_setting(string $key, mixed $default = null): mixed
{
    $settings = app_settings_all();
    return array_key_exists($key, $settings) ? $settings[$key] : $default;
}

function app_setting_bool(string $key, bool $default = false): bool
{
    $value = app_setting($key, $default ? '1' : '0');

    if (is_bool($value)) {
        return $value;
    }

    return in_array(mb_strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on'], true);
}

function configured_working_weekdays(): array
{
    $raw = (string) app_setting('working_weekdays', '1,2,3,4,5');
    $days = [];

    foreach (explode(',', $raw) as $part) {
        $day = filter_var(trim($part), FILTER_VALIDATE_INT);
        if ($day !== false && $day >= 1 && $day <= 7) {
            $days[(int) $day] = true;
        }
    }

    $result = array_keys($days);
    sort($result);

    return $result !== [] ? $result : [1, 2, 3, 4, 5];
}

function company_name(): string
{
    return trim((string) app_setting('company_name', 'Şirket')) ?: 'Şirket';
}

function application_name(): string
{
    $fallback = company_name() . ' İzin Yönetim Sistemi';
    return trim((string) app_setting('app_name', $fallback)) ?: $fallback;
}

function developer_name(): string
{
    return trim((string) app_setting('developer_name', 'elahimiavagh.com')) ?: 'elahimiavagh.com';
}

function developer_url(): string
{
    $url = trim((string) app_setting('developer_url', 'https://elahimiavagh.com'));
    return filter_var($url, FILTER_VALIDATE_URL) ? $url : 'https://elahimiavagh.com';
}

function weekday_labels(): array
{
    return [
        1 => 'Pazartesi',
        2 => 'Salı',
        3 => 'Çarşamba',
        4 => 'Perşembe',
        5 => 'Cuma',
        6 => 'Cumartesi',
        7 => 'Pazar',
    ];
}

function working_weekdays_text(): string
{
    $labels = weekday_labels();
    $names = [];

    foreach (configured_working_weekdays() as $day) {
        $names[] = $labels[$day] ?? (string) $day;
    }

    return implode(', ', $names);
}
