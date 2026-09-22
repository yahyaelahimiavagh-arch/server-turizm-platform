<?php
if (!defined('ABSPATH')) { exit; }

final class STCA_Logging {
    private const DB_VERSION = '1.0.0';

    public static function table(): string {
        global $wpdb;
        return $wpdb->prefix . 'stca_events';
    }

    public static function install(): void {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $charset = $wpdb->get_charset_collate();
        $table = self::table();
        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            event_type VARCHAR(40) NOT NULL,
            channel VARCHAR(30) NOT NULL DEFAULT 'instagram',
            message_id VARCHAR(191) NOT NULL DEFAULT '',
            sender_hash CHAR(64) NOT NULL DEFAULT '',
            intent VARCHAR(40) NOT NULL DEFAULT '',
            language VARCHAR(12) NOT NULL DEFAULT '',
            status VARCHAR(40) NOT NULL DEFAULT '',
            message_excerpt TEXT NULL,
            meta_json LONGTEXT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uniq_message_id (message_id),
            KEY idx_created_at (created_at),
            KEY idx_intent (intent),
            KEY idx_status (status)
        ) {$charset};";
        dbDelta($sql);
        update_option('stca_db_version', self::DB_VERSION, false);
    }

    public static function maybe_install(): void {
        if ((string) get_option('stca_db_version', '') !== self::DB_VERSION) {
            self::install();
        }
    }

    public static function event(string $eventType, array $data = array()): void {
        global $wpdb;
        $messageId = sanitize_text_field((string) ($data['message_id'] ?? ''));
        if ($messageId !== '') {
            $exists = $wpdb->get_var($wpdb->prepare('SELECT id FROM ' . self::table() . ' WHERE message_id=%s LIMIT 1', $messageId));
            if ($exists) { return; }
        } else {
            $messageId = 'evt-' . wp_generate_uuid4();
        }
        $sender = (string) ($data['sender_id'] ?? '');
        $excerpt = sanitize_textarea_field(STCA_Intent::slice((string) ($data['message'] ?? ''), 0, 500));
        $wpdb->insert(self::table(), array(
            'event_type' => sanitize_key($eventType),
            'channel' => sanitize_key((string) ($data['channel'] ?? 'instagram')),
            'message_id' => $messageId,
            'sender_hash' => $sender !== '' ? hash_hmac('sha256', $sender, wp_salt('auth')) : '',
            'intent' => sanitize_key((string) ($data['intent'] ?? '')),
            'language' => sanitize_key((string) ($data['language'] ?? '')),
            'status' => sanitize_key((string) ($data['status'] ?? '')),
            'message_excerpt' => $excerpt,
            'meta_json' => wp_json_encode((array) ($data['meta'] ?? array()), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'created_at' => current_time('mysql'),
        ), array('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s'));
    }

    public static function recent(int $limit = 20): array {
        global $wpdb;
        $limit = max(1, min(100, $limit));
        return (array) $wpdb->get_results($wpdb->prepare('SELECT * FROM ' . self::table() . ' ORDER BY id DESC LIMIT %d', $limit), ARRAY_A);
    }
}
