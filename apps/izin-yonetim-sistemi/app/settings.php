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

function work_schedule_modes(): array
{
    return [
        'off' => [
            'label' => 'Çalışma Yok',
            'value' => 0.0,
            'periods' => [],
        ],
        'morning' => [
            'label' => 'Yarım Gün — Sabah',
            'value' => 0.5,
            'periods' => ['morning'],
        ],
        'afternoon' => [
            'label' => 'Yarım Gün — Öğleden Sonra',
            'value' => 0.5,
            'periods' => ['afternoon'],
        ],
        'full_day' => [
            'label' => 'Tam Gün',
            'value' => 1.0,
            'periods' => ['morning', 'afternoon'],
        ],
    ];
}

function configured_work_schedule(): array
{
    $raw = trim((string) app_setting('work_schedule_json', ''));
    $modes = work_schedule_modes();
    $schedule = [];

    if ($raw !== '') {
        try {
            $decoded = json_decode($raw, true, 32, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            $decoded = null;
        }

        if (is_array($decoded)) {
            for ($day = 1; $day <= 7; $day++) {
                $mode = (string) ($decoded[(string) $day] ?? $decoded[$day] ?? 'off');
                $schedule[$day] = array_key_exists($mode, $modes) ? $mode : 'off';
            }
        }
    }

    if ($schedule !== []) {
        return $schedule;
    }

    // Backward-compatible fallback for V1/V2 installations that only have
    // working_weekdays. Legacy selected days are treated as full workdays.
    $rawWeekdays = (string) app_setting('working_weekdays', '1,2,3,4,5');
    $legacy = [];

    foreach (explode(',', $rawWeekdays) as $part) {
        $day = filter_var(trim($part), FILTER_VALIDATE_INT);
        if ($day !== false && $day >= 1 && $day <= 7) {
            $legacy[(int) $day] = true;
        }
    }

    for ($day = 1; $day <= 7; $day++) {
        $schedule[$day] = isset($legacy[$day]) ? 'full_day' : 'off';
    }

    return $schedule;
}

function work_schedule_day_policy(int $weekday): array
{
    $schedule = configured_work_schedule();
    $mode = (string) ($schedule[$weekday] ?? 'off');
    $modes = work_schedule_modes();

    return $modes[$mode] ?? $modes['off'];
}

function configured_leave_full_day_weights(): array
{
    $raw = trim((string) app_setting('leave_full_day_weights_json', ''));
    $weights = [];

    if ($raw !== '') {
        try {
            $decoded = json_decode($raw, true, 32, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            $decoded = null;
        }

        if (is_array($decoded)) {
            for ($day = 1; $day <= 7; $day++) {
                $value = $decoded[(string) $day] ?? $decoded[$day] ?? null;
                if (is_numeric($value)) {
                    $number = (float) $value;
                    if (in_array($number, [0.0, 0.5, 1.0], true)) {
                        $weights[$day] = $number;
                    }
                }
            }
        }
    }

    if (count($weights) === 7) {
        ksort($weights);
        return $weights;
    }

    $schedule = configured_work_schedule();
    for ($day = 1; $day <= 7; $day++) {
        $weights[$day] = (($schedule[$day] ?? 'off') === 'off') ? 0.0 : 1.0;
    }

    return $weights;
}

function leave_full_day_weight(int $weekday): float
{
    $weights = configured_leave_full_day_weights();
    return (float) ($weights[$weekday] ?? 0.0);
}

function configured_working_weekdays(): array
{
    $days = [];

    foreach (configured_work_schedule() as $day => $mode) {
        if ($mode !== 'off') {
            $days[] = (int) $day;
        }
    }

    sort($days);
    return $days;
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

function leave_full_day_weights_text(): string
{
    $labels = weekday_labels();
    $weights = configured_leave_full_day_weights();
    $parts = [];

    foreach ($weights as $day => $weight) {
        $label = $labels[$day] ?? (string) $day;
        $parts[] = $label . ': ' . format_days((float) $weight) . ' gün';
    }

    return implode(' · ', $parts);
}

function working_weekdays_text(): string
{
    $labels = weekday_labels();
    $modes = work_schedule_modes();
    $names = [];

    foreach (configured_work_schedule() as $day => $mode) {
        if ($mode === 'off') {
            continue;
        }

        $label = $labels[$day] ?? (string) $day;

        if ($mode === 'morning') {
            $label .= ' (yarım gün sabah)';
        } elseif ($mode === 'afternoon') {
            $label .= ' (yarım gün öğleden sonra)';
        }

        $names[] = $label;
    }

    return implode(', ', $names);
}
