<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Simple rate limit helpers for Gemini fallback.
 */
function stai_rate_key($prefix, $ip) {
    return $prefix . md5((string) $ip);
}

function stai_rate_get($prefix, $ip) {
    return (int) get_transient(stai_rate_key($prefix, $ip));
}

function stai_rate_increment($prefix, $ip, $ttl) {
    $key = stai_rate_key($prefix, $ip);
    $count = (int) get_transient($key);
    $count++;

    set_transient($key, $count, $ttl);

    return $count;
}

/**
 * Anti-spam and rate limit helpers.
 */
function stai_antispam_client_key($scope = 'chat') {
    $ip = stai_get_client_ip();
    $ua = stai_get_user_agent();

    return 'stai_antispam_' . sanitize_key($scope) . '_' . hash_hmac(
        'sha256',
        $ip . '|' . $ua,
        wp_salt('auth')
    );
}

function stai_antispam_get_window($scope) {
    $key = stai_antispam_client_key($scope);
    $data = get_transient($key);

    if (!is_array($data)) {
        return [
            'count' => 0,
            'reset' => 0,
        ];
    }

    return [
        'count' => isset($data['count']) ? (int) $data['count'] : 0,
        'reset' => isset($data['reset']) ? (int) $data['reset'] : 0,
    ];
}

function stai_antispam_increment_window($scope, $window_seconds) {
    $window_seconds = max(60, (int) $window_seconds);

    $key = stai_antispam_client_key($scope);
    $data = get_transient($key);

    if (!is_array($data) || empty($data['reset']) || (int) $data['reset'] <= time()) {
        $data = [
            'count' => 0,
            'reset' => time() + $window_seconds,
        ];
    }

    $data['count'] = (int) $data['count'] + 1;

    set_transient($key, $data, max(60, $data['reset'] - time()));

    return $data;
}

function stai_antispam_remaining_seconds($scope) {
    $data = stai_antispam_get_window($scope);

    if (empty($data['reset']) || (int) $data['reset'] <= time()) {
        return 0;
    }

    return (int) $data['reset'] - time();
}

function stai_antispam_check_min_interval($scope, $min_seconds) {
    $min_seconds = (int) $min_seconds;

    if ($min_seconds <= 0) {
        return [
            'allowed' => true,
            'retry_after' => 0,
        ];
    }

    $key = stai_antispam_client_key($scope . '_last');
    $last = (int) get_transient($key);

    if ($last > 0 && (time() - $last) < $min_seconds) {
        return [
            'allowed' => false,
            'retry_after' => $min_seconds - (time() - $last),
        ];
    }

    set_transient($key, time(), $min_seconds + 30);

    return [
        'allowed' => true,
        'retry_after' => 0,
    ];
}

function stai_antispam_check_duplicate_message($message, $seconds = 15) {
    $seconds = max(3, (int) $seconds);

    $normalized = stai_normalize_text_for_match($message);

    if ($normalized === '') {
        return [
            'allowed' => true,
            'retry_after' => 0,
        ];
    }

    $key = stai_antispam_client_key('duplicate_' . md5($normalized));
    $exists = get_transient($key);

    if (!empty($exists)) {
        return [
            'allowed' => false,
            'retry_after' => $seconds,
        ];
    }

    set_transient($key, '1', $seconds);

    return [
        'allowed' => true,
        'retry_after' => 0,
    ];
}

function stai_antispam_response_message($message, $retry_after = 0) {
    $retry_after = (int) $retry_after;

    if (stai_is_persian_message($message)) {
        if ($retry_after > 0) {
            return "برای جلوگیری از ارسال پشت‌سرهم، لطفاً {$retry_after} ثانیه صبر کنید و دوباره پیام بفرستید.";
        }

        return "تعداد پیام‌های شما زیاد شده است. لطفاً کمی بعد دوباره تلاش کنید.";
    }

    if ($retry_after > 0) {
        return "Arka arkaya mesaj göndermemek için lütfen {$retry_after} saniye bekleyip tekrar deneyin.";
    }

    return "Çok fazla mesaj gönderdiniz. Lütfen biraz sonra tekrar deneyin.";
}

function stai_check_chat_antispam($message, $params = []) {
    if (!stai_get_bool_setting('chat_rate_limit_enabled', true)) {
        return [
            'allowed' => true,
            'status' => 200,
            'message' => '',
            'retry_after' => 0,
        ];
    }

    $min_interval = stai_get_int_setting('chat_min_interval_seconds', 4, 0, 60);
    $max_messages = stai_get_int_setting('chat_max_messages', 8, 1, 100);
    $window_minutes = stai_get_int_setting('chat_window_minutes', 10, 1, 1440);
    $duplicate_seconds = stai_get_int_setting('duplicate_block_seconds', 12, 0, 300);

    $interval = stai_antispam_check_min_interval('chat_interval', $min_interval);

    if (!$interval['allowed']) {
        return [
            'allowed' => false,
            'status' => 429,
            'message' => stai_antispam_response_message($message, $interval['retry_after']),
            'retry_after' => $interval['retry_after'],
        ];
    }

    if ($duplicate_seconds > 0) {
        $duplicate = stai_antispam_check_duplicate_message($message, $duplicate_seconds);

        if (!$duplicate['allowed']) {
            return [
                'allowed' => false,
                'status' => 429,
                'message' => stai_is_persian_message($message)
                    ? "این پیام تکراری است. لطفاً چند ثانیه بعد دوباره تلاش کنید."
                    : "Bu mesaj tekrarlandı. Lütfen birkaç saniye sonra tekrar deneyin.",
                'retry_after' => $duplicate['retry_after'],
            ];
        }
    }

    $window_seconds = $window_minutes * MINUTE_IN_SECONDS;
    $window = stai_antispam_get_window('chat_window');

    if ($window['count'] >= $max_messages && $window['reset'] > time()) {
        $remaining = stai_antispam_remaining_seconds('chat_window');

        return [
            'allowed' => false,
            'status' => 429,
            'message' => stai_is_persian_message($message)
                ? "به محدودیت پیام رسیدید. لطفاً حدود {$remaining} ثانیه بعد دوباره تلاش کنید."
                : "Mesaj limitine ulaştınız. Lütfen yaklaşık {$remaining} saniye sonra tekrar deneyin.",
            'retry_after' => $remaining,
        ];
    }

    stai_antispam_increment_window('chat_window', $window_seconds);

    return [
        'allowed' => true,
        'status' => 200,
        'message' => '',
        'retry_after' => 0,
    ];
}

function stai_check_lead_antispam($params = []) {
    if (!is_array($params)) {
        $params = [];
    }

    /*
     * Honeypot field.
     * Real users will never fill this field.
     */
    if (!empty($params['website'])) {
        return [
            'allowed' => false,
            'status' => 400,
            'message' => 'Invalid request.',
            'retry_after' => 0,
        ];
    }

    if (!stai_get_bool_setting('lead_rate_limit_enabled', true)) {
        return [
            'allowed' => true,
            'status' => 200,
            'message' => '',
            'retry_after' => 0,
        ];
    }

    $max_leads = stai_get_int_setting('lead_max_submissions', 2, 1, 20);
    $window_minutes = stai_get_int_setting('lead_window_minutes', 60, 1, 1440);
    $window_seconds = $window_minutes * MINUTE_IN_SECONDS;

    $window = stai_antispam_get_window('lead_window');

    if ($window['count'] >= $max_leads && $window['reset'] > time()) {
        $remaining = stai_antispam_remaining_seconds('lead_window');

        return [
            'allowed' => false,
            'status' => 429,
            'message' => "Çok fazla form gönderdiniz. Lütfen {$remaining} saniye sonra tekrar deneyin.",
            'retry_after' => $remaining,
        ];
    }

    stai_antispam_increment_window('lead_window', $window_seconds);

    return [
        'allowed' => true,
        'status' => 200,
        'message' => '',
        'retry_after' => 0,
    ];
}

/**
 * Gemini quota cooldown helpers.
 * If Gemini returns 429, we pause Gemini calls for a while.
 */
function stai_gemini_cooldown_key() {
    return 'stai_gemini_cooldown_until';
}

function stai_gemini_get_cooldown_remaining() {
    $until = (int) get_transient(stai_gemini_cooldown_key());

    if ($until <= time()) {
        return 0;
    }

    return $until - time();
}

function stai_gemini_is_in_cooldown() {
    return stai_gemini_get_cooldown_remaining() > 0;
}

function stai_gemini_set_cooldown($seconds = 1800) {
    $seconds = (int) $seconds;

    if ($seconds < 300) {
        $seconds = 300;
    }

    if ($seconds > 3600) {
        $seconds = 3600;
    }

    set_transient(
        stai_gemini_cooldown_key(),
        time() + $seconds,
        $seconds
    );
}

function stai_gemini_extract_retry_seconds($body) {
    $default = 1800;

    if (!is_array($body)) {
        return $default;
    }

    if (!empty($body['error']['details']) && is_array($body['error']['details'])) {
        foreach ($body['error']['details'] as $detail) {
            if (!empty($detail['retryDelay']) && preg_match('/(\d+)/', $detail['retryDelay'], $m)) {
                return max(300, (int) $m[1]);
            }
        }
    }

    if (!empty($body['error']['message']) && preg_match('/retry in ([0-9.]+)s/i', $body['error']['message'], $m)) {
        return max(300, (int) ceil((float) $m[1]));
    }

    return $default;
}

function stai_get_safe_fallback_answer($message) {
    $custom_message = stai_get_setting('gemini_quota_message', '');

    if (!empty($custom_message)) {
        return $custom_message;
    }

    if (stai_is_persian_message($message)) {
        return "در حال حاضر پاسخ هوش مصنوعی به دلیل محدودیت مصرف Gemini موقتاً در دسترس نیست.\n\nاما می‌توانید از بخش‌های اصلی استفاده کنید:\n• برنامه‌ها را نشان بده\n• ارزان‌ترین برنامه کدام است؟\n• قیمت کودک برنامه 210\n• برنامه‌های ماه ژوئن\n• واتساپ\n\nبرای اطلاعات نهایی و رزرو، لطفاً با تیم فروش Server Turizm تماس بگیرید.";
    }

    return "AI cevabı şu anda Gemini kullanım kotası nedeniyle geçici olarak kullanılamıyor.\n\nAncak aşağıdaki konularda yardımcı olabilirim:\n• Mevcut programları göster\n• En uygun program hangisi?\n• Program 210 çocuk fiyatı\n• Haziran programları\n• WhatsApp\n\nSon bilgi ve rezervasyon için lütfen Server Turizm satış ekibiyle iletişime geçin.";
}
