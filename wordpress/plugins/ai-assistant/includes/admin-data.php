<?php
if (!defined('ABSPATH')) {
    exit;
}

function stai_admin_get_count($event_type, $since = '') {
    stai_maybe_create_log_table();

    global $wpdb;

    $table = stai_get_log_table_name();

    if (!empty($since)) {
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE event_type = %s AND created_at >= %s",
            $event_type,
            $since
        ));
    }

    return (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$table} WHERE event_type = %s",
        $event_type
    ));
}

function stai_admin_get_recent_messages($limit = 30) {
    stai_maybe_create_log_table();

    global $wpdb;

    $table = stai_get_log_table_name();
    $limit = absint($limit);

    return $wpdb->get_results(
        "SELECT created_at, language, message, page_url
         FROM {$table}
         WHERE event_type = 'message'
         ORDER BY id DESC
         LIMIT {$limit}",
        ARRAY_A
    );
}

function stai_admin_get_recent_leads($limit = 30) {
    stai_maybe_create_log_table();

    global $wpdb;

    $table = stai_get_log_table_name();
    $limit = absint($limit);

    return $wpdb->get_results(
        "SELECT created_at, program_no, message, page_url, meta
         FROM {$table}
         WHERE event_type = 'lead'
         ORDER BY id DESC
         LIMIT {$limit}",
        ARRAY_A
    );
}

function stai_admin_get_daily_stats($days = 7) {
    stai_maybe_create_log_table();

    global $wpdb;

    $table = stai_get_log_table_name();
    $days = absint($days);

    if ($days < 1) {
        $days = 7;
    }

    if ($days > 30) {
        $days = 30;
    }

    $since = date('Y-m-d 00:00:00', current_time('timestamp') - ($days - 1) * DAY_IN_SECONDS);

    $rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT DATE(created_at) AS stat_date,
                    event_type,
                    COUNT(*) AS total
             FROM {$table}
             WHERE created_at >= %s
             GROUP BY DATE(created_at), event_type
             ORDER BY stat_date ASC",
            $since
        ),
        ARRAY_A
    );

    $stats = [];

    for ($i = $days - 1; $i >= 0; $i--) {
        $date = date('Y-m-d', current_time('timestamp') - $i * DAY_IN_SECONDS);

        $stats[$date] = [
            'date' => $date,
            'message' => 0,
            'lead' => 0,
            'whatsapp_click' => 0,
            'widget_open' => 0,
        ];
    }

    foreach ($rows as $row) {
        $date = $row['stat_date'];
        $event_type = $row['event_type'];

        if (!isset($stats[$date])) {
            continue;
        }

        if (!array_key_exists($event_type, $stats[$date])) {
            continue;
        }

        $stats[$date][$event_type] = (int) $row['total'];
    }

    return array_values($stats);
}

function stai_admin_get_top_programs($limit = 10) {
    stai_maybe_create_log_table();

    global $wpdb;

    $table = stai_get_log_table_name();
    $limit = absint($limit);

    if ($limit < 1) {
        $limit = 10;
    }

    if ($limit > 50) {
        $limit = 50;
    }

    return $wpdb->get_results(
        "SELECT program_no, COUNT(*) AS total
         FROM {$table}
         WHERE program_no IS NOT NULL
           AND program_no != ''
         GROUP BY program_no
         ORDER BY total DESC
         LIMIT {$limit}",
        ARRAY_A
    );
}
