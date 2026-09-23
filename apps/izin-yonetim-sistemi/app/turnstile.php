<?php

declare(strict_types=1);

function turnstile_config(): array
{
    $config = app_config('turnstile');
    return is_array($config) ? $config : [];
}

function turnstile_enabled(): bool
{
    $config = turnstile_config();

    return ($config['enabled'] ?? false) === true
        && trim((string) ($config['site_key'] ?? '')) !== ''
        && trim((string) ($config['secret_key'] ?? '')) !== '';
}

function turnstile_site_key(): string
{
    return trim((string) (turnstile_config()['site_key'] ?? ''));
}

function turnstile_widget_html(): string
{
    if (!turnstile_enabled()) {
        return '';
    }

    return '<div class="cf-turnstile" data-sitekey="' . e(turnstile_site_key()) . '"></div>';
}

function verify_turnstile_response(?string $token = null): bool
{
    if (!turnstile_enabled()) {
        return true;
    }

    $token = $token ?? (string) ($_POST['cf-turnstile-response'] ?? '');
    $token = trim($token);

    if ($token === '') {
        return false;
    }

    $secret = trim((string) (turnstile_config()['secret_key'] ?? ''));
    $payload = http_build_query([
        'secret' => $secret,
        'response' => $token,
        'remoteip' => (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
    ]);

    $body = null;

    if (function_exists('curl_init')) {
        $ch = curl_init('https://challenges.cloudflare.com/turnstile/v0/siteverify');
        if ($ch !== false) {
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 4,
                CURLOPT_TIMEOUT => 8,
                CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
            ]);
            $response = curl_exec($ch);
            $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            curl_close($ch);

            if (is_string($response) && $status >= 200 && $status < 300) {
                $body = $response;
            }
        }
    }

    if ($body === null && filter_var(ini_get('allow_url_fopen'), FILTER_VALIDATE_BOOL)) {
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => $payload,
                'timeout' => 8,
                'ignore_errors' => true,
            ],
        ]);

        $response = @file_get_contents(
            'https://challenges.cloudflare.com/turnstile/v0/siteverify',
            false,
            $context
        );

        if (is_string($response)) {
            $body = $response;
        }
    }

    if ($body === null) {
        error_log('Turnstile verification transport failed.');
        return false;
    }

    $decoded = json_decode($body, true);

    return is_array($decoded) && ($decoded['success'] ?? false) === true;
}
