<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Helpers.
 */
function stai_is_persian_message($message) {
    return preg_match('/[\x{0600}-\x{06FF}]/u', (string) $message);
}

function stai_get_client_ip() {
    $keys = [
        'HTTP_CF_CONNECTING_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_REAL_IP',
        'REMOTE_ADDR',
    ];

    foreach ($keys as $key) {
        if (empty($_SERVER[$key])) {
            continue;
        }

        $value = sanitize_text_field(wp_unslash($_SERVER[$key]));

        if ($key === 'HTTP_X_FORWARDED_FOR') {
            $parts = explode(',', $value);
            $value = trim($parts[0]);
        }

        if (filter_var($value, FILTER_VALIDATE_IP)) {
            return $value;
        }
    }

    return 'unknown';
}

function stai_get_whatsapp_url($text = '') {
    $number_source = stai_get_setting(
        'whatsapp_number',
        defined('STAI_WHATSAPP_NUMBER') ? STAI_WHATSAPP_NUMBER : ''
    );

    if (empty($number_source)) {
        return '';
    }

    $number = preg_replace('/\D+/', '', $number_source);

    if (empty($number)) {
        return '';
    }

    if (empty($text)) {
        $text = 'Merhaba, umre programları hakkında bilgi almak istiyorum.';
    }

    return 'https://wa.me/' . $number . '?text=' . rawurlencode($text);
}
