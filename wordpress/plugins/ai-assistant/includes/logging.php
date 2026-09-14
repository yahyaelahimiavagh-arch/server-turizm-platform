<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Logging and analytics database.
 */
function stai_get_log_table_name() {
    global $wpdb;
    return $wpdb->prefix . 'stai_logs';
}

function stai_maybe_create_log_table() {
    global $wpdb;

    $table = stai_get_log_table_name();
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE {$table} (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        event_type VARCHAR(50) NOT NULL,
        message TEXT NULL,
        program_no VARCHAR(50) NULL,
        language VARCHAR(20) NULL,
        page_url TEXT NULL,
        ip_hash VARCHAR(128) NULL,
        user_agent TEXT NULL,
        meta LONGTEXT NULL,
        created_at DATETIME NOT NULL,
        PRIMARY KEY  (id),
        KEY event_type (event_type),
        KEY program_no (program_no),
        KEY created_at (created_at)
    ) {$charset_collate};";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}

function stai_detect_message_language($message) {
    return stai_is_persian_message($message) ? 'fa' : 'tr';
}

function stai_get_user_agent() {
    if (empty($_SERVER['HTTP_USER_AGENT'])) {
        return '';
    }

    return sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT']));
}

function stai_log_event($event_type, $data = []) {
    stai_maybe_create_log_table();

    global $wpdb;

    $table = stai_get_log_table_name();

    $event_type = sanitize_key($event_type);

    $message = isset($data['message']) ? sanitize_textarea_field($data['message']) : '';
    $program_no = isset($data['program_no']) ? sanitize_text_field($data['program_no']) : '';
    $language = isset($data['language']) ? sanitize_text_field($data['language']) : '';
    $page_url = isset($data['page_url']) ? esc_url_raw($data['page_url']) : '';

    $meta = isset($data['meta']) && is_array($data['meta']) ? $data['meta'] : [];

    $ip = stai_get_client_ip();
    $ip_hash = '';

    if (!empty($ip) && $ip !== 'unknown') {
        $ip_hash = hash_hmac('sha256', $ip, wp_salt('auth'));
    }

    $wpdb->insert(
        $table,
        [
            'event_type' => $event_type,
            'message'    => $message,
            'program_no' => $program_no,
            'language'   => $language,
            'page_url'   => $page_url,
            'ip_hash'    => $ip_hash,
            'user_agent' => stai_get_user_agent(),
            'meta'       => wp_json_encode($meta, JSON_UNESCAPED_UNICODE),
            'created_at' => current_time('mysql'),
        ],
        [
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
        ]
    );

    return (int) $wpdb->insert_id;
}
