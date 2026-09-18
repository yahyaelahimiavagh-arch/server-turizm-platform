<?php
/**
 * Plugin Name: Elahimiavagh Operations Calendar Core
 * Description: Shared calendar projection layer for tours, Umrah programs, leave, holidays and future operational modules.
 * Version: 0.1.0
 * Author: elahimiavagh.com
 * Author URI: https://elahimiavagh.com
 * Requires PHP: 8.1
 */

if (!defined('ABSPATH')) {
    exit;
}

define('ELAHI_OPS_CALENDAR_VERSION', '0.1.0');

function elahi_ops_calendar_table(): string
{
    global $wpdb;
    return $wpdb->prefix . 'elahi_calendar_events';
}

function elahi_ops_calendar_install(): void
{
    global $wpdb;

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $table = elahi_ops_calendar_table();
    $charset = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE {$table} (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        event_uid varchar(191) NOT NULL,
        source_module varchar(64) NOT NULL,
        source_entity_id varchar(191) NOT NULL,
        event_type varchar(64) NOT NULL,
        title varchar(255) NOT NULL,
        start_at datetime NOT NULL,
        end_at datetime NOT NULL,
        all_day tinyint(1) NOT NULL DEFAULT 1,
        status varchar(32) NOT NULL DEFAULT 'draft',
        visibility varchar(32) NOT NULL DEFAULT 'internal',
        priority smallint(5) unsigned NOT NULL DEFAULT 50,
        location varchar(255) NULL,
        public_url text NULL,
        metadata_json longtext NULL,
        source_version varchar(64) NULL,
        created_at datetime NOT NULL,
        updated_at datetime NOT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY event_uid (event_uid),
        KEY source_entity (source_module, source_entity_id),
        KEY event_window (start_at, end_at),
        KEY public_window (visibility, status, start_at)
    ) {$charset};";

    dbDelta($sql);
    update_option('elahi_ops_calendar_db_version', ELAHI_OPS_CALENDAR_VERSION, false);

    if (function_exists('elahi_ops_calendar_register_rewrite')) {
        elahi_ops_calendar_register_rewrite();
        flush_rewrite_rules(false);
    }
}

register_activation_hook(__FILE__, 'elahi_ops_calendar_install');

function elahi_ops_calendar_parse_utc(string $value): ?string
{
    try {
        $date = new DateTimeImmutable($value, new DateTimeZone('UTC'));
        return $date->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
    } catch (Throwable) {
        return null;
    }
}

function elahi_ops_calendar_normalize_event(array $event)
{
    $uid = trim((string) ($event['event_uid'] ?? ''));
    $sourceModule = sanitize_key((string) ($event['source_module'] ?? ''));
    $sourceEntityId = trim((string) ($event['source_entity_id'] ?? ''));
    $eventType = sanitize_key((string) ($event['event_type'] ?? ''));
    $title = sanitize_text_field((string) ($event['title'] ?? ''));
    $startAt = elahi_ops_calendar_parse_utc((string) ($event['start_at'] ?? ''));
    $endAt = elahi_ops_calendar_parse_utc((string) ($event['end_at'] ?? ''));

    if ($uid === '' || strlen($uid) > 191 || !preg_match('/^[A-Za-z0-9._:-]+$/', $uid)) {
        return new WP_Error('invalid_event_uid', 'event_uid must be a stable 1-191 character identifier.');
    }
    if ($sourceModule === '' || $sourceEntityId === '' || $eventType === '' || $title === '') {
        return new WP_Error('missing_event_identity', 'source_module, source_entity_id, event_type and title are required.');
    }
    if ($startAt === null || $endAt === null || $endAt < $startAt) {
        return new WP_Error('invalid_event_window', 'start_at/end_at must be valid and ordered.');
    }

    $status = (string) ($event['status'] ?? 'draft');
    $visibility = (string) ($event['visibility'] ?? 'internal');

    if (!in_array($status, ['draft', 'published', 'cancelled', 'archived'], true)) {
        return new WP_Error('invalid_event_status', 'Unsupported calendar event status.');
    }
    if (!in_array($visibility, ['public', 'internal', 'private'], true)) {
        return new WP_Error('invalid_event_visibility', 'Unsupported calendar event visibility.');
    }

    $metadata = $event['metadata'] ?? [];
    if (!is_array($metadata)) {
        return new WP_Error('invalid_event_metadata', 'metadata must be an object/array.');
    }

    return [
        'event_uid' => $uid,
        'source_module' => $sourceModule,
        'source_entity_id' => mb_substr($sourceEntityId, 0, 191),
        'event_type' => $eventType,
        'title' => mb_substr($title, 0, 255),
        'start_at' => $startAt,
        'end_at' => $endAt,
        'all_day' => !empty($event['all_day']) ? 1 : 0,
        'status' => $status,
        'visibility' => $visibility,
        'priority' => max(0, min(100, (int) ($event['priority'] ?? 50))),
        'location' => isset($event['location']) && trim((string) $event['location']) !== ''
            ? mb_substr(sanitize_text_field((string) $event['location']), 0, 255)
            : null,
        'public_url' => isset($event['public_url']) && trim((string) $event['public_url']) !== ''
            ? esc_url_raw((string) $event['public_url'])
            : null,
        'metadata_json' => wp_json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        'source_version' => isset($event['source_version']) && trim((string) $event['source_version']) !== ''
            ? mb_substr(sanitize_text_field((string) $event['source_version']), 0, 64)
            : null,
    ];
}

/**
 * Public integration contract for modules.
 *
 * The source module remains the source of truth. This table is only a calendar
 * projection and can always be rebuilt from source entities.
 */
function elahi_ops_calendar_upsert_event(array $event)
{
    global $wpdb;

    $normalized = elahi_ops_calendar_normalize_event($event);
    if (is_wp_error($normalized)) {
        return $normalized;
    }

    $table = elahi_ops_calendar_table();
    $now = current_time('mysql', true);
    $existingId = $wpdb->get_var(
        $wpdb->prepare("SELECT id FROM {$table} WHERE event_uid = %s LIMIT 1", $normalized['event_uid'])
    );

    $data = $normalized + ['updated_at' => $now];

    if ($existingId) {
        $updated = $wpdb->update($table, $data, ['id' => (int) $existingId]);
        if ($updated === false) {
            return new WP_Error('calendar_update_failed', 'Calendar projection update failed.');
        }
        return (int) $existingId;
    }

    $data['created_at'] = $now;
    $inserted = $wpdb->insert($table, $data);
    if ($inserted === false) {
        return new WP_Error('calendar_insert_failed', 'Calendar projection insert failed.');
    }

    return (int) $wpdb->insert_id;
}

function elahi_ops_calendar_remove_event(string $eventUid): bool
{
    global $wpdb;

    $eventUid = trim($eventUid);
    if ($eventUid === '') {
        return false;
    }

    return $wpdb->delete(elahi_ops_calendar_table(), ['event_uid' => $eventUid], ['%s']) !== false;
}


/**
 * Atomically reconcile one complete source-module projection.
 *
 * Callers must provide the full desired event set for the source module.
 * Empty reconciliation is fail-closed unless allowEmpty is explicitly true.
 */
function elahi_ops_calendar_reconcile_source_events(
    string $sourceModule,
    array $events,
    bool $allowEmpty = false
) {
    global $wpdb;

    $sourceModule = sanitize_key($sourceModule);
    if ($sourceModule === '') {
        return new WP_Error('invalid_source_module', 'A stable source module is required.');
    }

    if ($events === [] && !$allowEmpty) {
        return new WP_Error('empty_projection_blocked', 'Empty source reconciliation requires explicit allowEmpty.');
    }

    $normalizedEvents = [];
    $eventUids = [];

    foreach (array_values($events) as $event) {
        if (!is_array($event)) {
            return new WP_Error('invalid_projection_event', 'Every projection event must be an object/array.');
        }

        if (sanitize_key((string) ($event['source_module'] ?? '')) !== $sourceModule) {
            return new WP_Error('projection_source_mismatch', 'Projection event source_module does not match reconciliation source.');
        }

        $normalized = elahi_ops_calendar_normalize_event($event);
        if (is_wp_error($normalized)) {
            return $normalized;
        }

        $uid = (string) $normalized['event_uid'];
        if (isset($eventUids[$uid])) {
            return new WP_Error('duplicate_projection_uid', 'Duplicate event_uid inside source projection.');
        }

        $eventUids[$uid] = true;
        $normalizedEvents[] = $event;
    }

    $wpdb->query('START TRANSACTION');

    try {
        foreach ($normalizedEvents as $event) {
            $result = elahi_ops_calendar_upsert_event($event);
            if (is_wp_error($result)) {
                throw new RuntimeException($result->get_error_message());
            }
        }

        $table = elahi_ops_calendar_table();
        $deleted = 0;

        if ($eventUids === []) {
            $deleted = $wpdb->delete($table, ['source_module' => $sourceModule], ['%s']);
            if ($deleted === false) {
                throw new RuntimeException('Source projection cleanup failed.');
            }
        } else {
            $uids = array_keys($eventUids);
            $placeholders = implode(', ', array_fill(0, count($uids), '%s'));
            $sql = "DELETE FROM {$table}
                    WHERE source_module = %s
                      AND event_uid NOT IN ({$placeholders})";
            $deleted = $wpdb->query($wpdb->prepare($sql, array_merge([$sourceModule], $uids)));
            if ($deleted === false) {
                throw new RuntimeException('Stale projection cleanup failed.');
            }
        }

        $wpdb->query('COMMIT');

        return [
            'source_module' => $sourceModule,
            'projected' => count($normalizedEvents),
            'removed' => max(0, (int) $deleted),
        ];
    } catch (Throwable $e) {
        $wpdb->query('ROLLBACK');
        error_log('Operations calendar reconcile failed [' . $sourceModule . ']: ' . $e->getMessage());

        return new WP_Error('projection_reconcile_failed', 'Source projection reconciliation failed.');
    }
}

function elahi_ops_calendar_events(array $args = []): array
{
    global $wpdb;

    $defaults = [
        'from' => gmdate('Y-m-d 00:00:00'),
        'to' => gmdate('Y-m-d 23:59:59', strtotime('+12 months')),
        'visibility' => 'public',
        'status' => 'published',
        'source_module' => '',
        'limit' => 500,
    ];
    $args = wp_parse_args($args, $defaults);

    $from = elahi_ops_calendar_parse_utc((string) $args['from']);
    $to = elahi_ops_calendar_parse_utc((string) $args['to']);
    if ($from === null || $to === null || $to < $from) {
        return [];
    }

    $where = ['end_at >= %s', 'start_at <= %s'];
    $params = [$from, $to];

    if ((string) $args['visibility'] !== '') {
        $where[] = 'visibility = %s';
        $params[] = (string) $args['visibility'];
    }
    if ((string) $args['status'] !== '') {
        $where[] = 'status = %s';
        $params[] = (string) $args['status'];
    }
    if ((string) $args['source_module'] !== '') {
        $where[] = 'source_module = %s';
        $params[] = sanitize_key((string) $args['source_module']);
    }

    $limit = max(1, min(2000, (int) $args['limit']));
    $table = elahi_ops_calendar_table();
    $sql = "SELECT event_uid, source_module, source_entity_id, event_type, title,
                   start_at, end_at, all_day, status, visibility, priority,
                   location, public_url, metadata_json, source_version
            FROM {$table}
            WHERE " . implode(' AND ', $where) . "
            ORDER BY start_at ASC, priority DESC, id ASC
            LIMIT {$limit}";

    return $wpdb->get_results($wpdb->prepare($sql, $params), ARRAY_A) ?: [];
}

function elahi_ops_calendar_internal_token(): string
{
    $token = defined('ELAHI_OPS_CALENDAR_INTERNAL_TOKEN')
        ? trim((string) ELAHI_OPS_CALENDAR_INTERNAL_TOKEN)
        : '';

    return trim((string) apply_filters('elahi_ops_calendar_internal_token', $token));
}

function elahi_ops_calendar_machine_permission(WP_REST_Request $request)
{
    if (current_user_can('manage_options')) {
        return true;
    }

    $configured = elahi_ops_calendar_internal_token();

    if (strlen($configured) < 32) {
        return new WP_Error(
            'calendar_machine_auth_unconfigured',
            'Internal calendar API is not configured.',
            ['status' => 503]
        );
    }

    $provided = trim((string) $request->get_header('x-elahi-calendar-token'));

    if ($provided === '') {
        $authorization = trim((string) $request->get_header('authorization'));
        if (preg_match('/^Bearer\s+(.+)$/i', $authorization, $matches)) {
            $provided = trim((string) $matches[1]);
        }
    }

    if ($provided === '' || !hash_equals($configured, $provided)) {
        return new WP_Error(
            'calendar_machine_auth_failed',
            'Internal calendar API authentication failed.',
            ['status' => 401]
        );
    }

    return true;
}

function elahi_ops_calendar_machine_operations(WP_REST_Request $request)
{
    $fromRaw = trim((string) ($request->get_param('from') ?: gmdate(DATE_ATOM)));
    $toRaw = trim((string) ($request->get_param('to') ?: gmdate(DATE_ATOM, strtotime('+31 days'))));

    try {
        $from = new DateTimeImmutable($fromRaw, new DateTimeZone('UTC'));
        $to = new DateTimeImmutable($toRaw, new DateTimeZone('UTC'));
    } catch (Throwable) {
        return new WP_Error('invalid_calendar_window', 'Invalid calendar window.', ['status' => 422]);
    }

    if ($to < $from || $to->getTimestamp() - $from->getTimestamp() > 93 * DAY_IN_SECONDS) {
        return new WP_Error(
            'calendar_window_too_large',
            'Internal calendar window must be ordered and no longer than 93 days.',
            ['status' => 422]
        );
    }

    $requestedSource = sanitize_key((string) $request->get_param('source_module'));
    $allowedSources = ['tour', 'umrah'];

    if ($requestedSource !== '' && !in_array($requestedSource, $allowedSources, true)) {
        return new WP_Error('invalid_calendar_source', 'Unsupported operation source.', ['status' => 422]);
    }

    $sources = $requestedSource !== '' ? [$requestedSource] : $allowedSources;
    $eventsByUid = [];

    foreach ($sources as $sourceModule) {
        foreach (['public', 'internal'] as $visibility) {
            $rows = elahi_ops_calendar_events([
                'from' => $from->setTimezone(new DateTimeZone('UTC'))->format(DATE_ATOM),
                'to' => $to->setTimezone(new DateTimeZone('UTC'))->format(DATE_ATOM),
                'visibility' => $visibility,
                'status' => 'published',
                'source_module' => $sourceModule,
                'limit' => 500,
            ]);

            foreach ($rows as $row) {
                $uid = (string) ($row['event_uid'] ?? '');
                if ($uid === '') {
                    continue;
                }

                $eventsByUid[$uid] = [
                    'event_uid' => $uid,
                    'source_module' => (string) ($row['source_module'] ?? ''),
                    'source_entity_id' => (string) ($row['source_entity_id'] ?? ''),
                    'event_type' => (string) ($row['event_type'] ?? ''),
                    'title' => (string) ($row['title'] ?? ''),
                    'start_at' => (string) ($row['start_at'] ?? ''),
                    'end_at' => (string) ($row['end_at'] ?? ''),
                    'all_day' => (int) ($row['all_day'] ?? 0) === 1,
                    'visibility' => (string) ($row['visibility'] ?? ''),
                    'priority' => (int) ($row['priority'] ?? 0),
                    'location' => $row['location'] !== null ? (string) $row['location'] : null,
                    'public_url' => $row['public_url'] !== null ? (string) $row['public_url'] : null,
                    'source_version' => $row['source_version'] !== null ? (string) $row['source_version'] : null,
                ];
            }
        }
    }

    $events = array_values($eventsByUid);
    usort($events, static function (array $a, array $b): int {
        $startCompare = strcmp((string) $a['start_at'], (string) $b['start_at']);
        if ($startCompare !== 0) {
            return $startCompare;
        }

        $priorityCompare = ((int) $b['priority']) <=> ((int) $a['priority']);
        if ($priorityCompare !== 0) {
            return $priorityCompare;
        }

        return strcmp((string) $a['event_uid'], (string) $b['event_uid']);
    });

    $events = array_slice($events, 0, 500);

    return new WP_REST_Response([
        'schema_version' => '1.0',
        'window' => [
            'from' => $from->setTimezone(new DateTimeZone('UTC'))->format(DATE_ATOM),
            'to' => $to->setTimezone(new DateTimeZone('UTC'))->format(DATE_ATOM),
        ],
        'count' => count($events),
        'events' => $events,
    ]);
}

function elahi_ops_calendar_register_rest(): void
{
    register_rest_route('elahimiavagh/v1', '/calendar/events', [
        'methods' => WP_REST_Server::READABLE,
        'permission_callback' => '__return_true',
        'callback' => static function (WP_REST_Request $request): WP_REST_Response {
            $requestedVisibility = sanitize_key((string) $request->get_param('visibility'));
            $visibility = 'public';

            if (current_user_can('manage_options') && in_array($requestedVisibility, ['public', 'internal', 'private'], true)) {
                $visibility = $requestedVisibility;
            }

            $events = elahi_ops_calendar_events([
                'from' => (string) ($request->get_param('from') ?: gmdate('c')),
                'to' => (string) ($request->get_param('to') ?: gmdate('c', strtotime('+12 months'))),
                'visibility' => $visibility,
                'status' => 'published',
                'source_module' => sanitize_key((string) $request->get_param('source_module')),
                'limit' => min(1000, max(1, (int) ($request->get_param('limit') ?: 500))),
            ]);

            foreach ($events as &$event) {
                $event['metadata'] = json_decode((string) ($event['metadata_json'] ?? ''), true) ?: [];
                unset($event['metadata_json']);
            }
            unset($event);

            return new WP_REST_Response([
                'schema_version' => '1.0',
                'count' => count($events),
                'events' => $events,
            ]);
        },
    ]);

    register_rest_route('elahimiavagh/v1', '/calendar/operations', [
        'methods' => WP_REST_Server::READABLE,
        'permission_callback' => 'elahi_ops_calendar_machine_permission',
        'callback' => 'elahi_ops_calendar_machine_operations',
        'args' => [
            'from' => [
                'type' => 'string',
                'required' => false,
            ],
            'to' => [
                'type' => 'string',
                'required' => false,
            ],
            'source_module' => [
                'type' => 'string',
                'required' => false,
                'enum' => ['tour', 'umrah'],
            ],
        ],
    ]);
}
add_action('rest_api_init', 'elahi_ops_calendar_register_rest');

function elahi_ops_calendar_shortcode(array $atts = []): string
{
    $atts = shortcode_atts([
        'months' => '6',
        'limit' => '12',
    ], $atts, 'elahi_operations_calendar');

    $months = max(1, min(24, (int) $atts['months']));
    $limit = max(1, min(50, (int) $atts['limit']));
    $events = elahi_ops_calendar_events([
        'from' => gmdate('c'),
        'to' => gmdate('c', strtotime("+{$months} months")),
        'visibility' => 'public',
        'status' => 'published',
        'limit' => $limit,
    ]);

    if ($events === []) {
        return '<div class="elahi-ops-calendar-empty">Yaklaşan program bulunmuyor.</div>';
    }

    ob_start();
    ?>
    <div class="elahi-ops-calendar" data-schema-version="1.0">
        <?php foreach ($events as $event): ?>
            <article class="elahi-ops-calendar-event">
                <?php $eventTimestamp = strtotime((string) $event['start_at'] . ' UTC'); ?>
                <time datetime="<?php echo esc_attr($eventTimestamp !== false ? gmdate('c', $eventTimestamp) : ''); ?>">
                    <?php echo esc_html(get_date_from_gmt((string) $event['start_at'], 'd.m.Y')); ?>
                </time>
                <strong><?php echo esc_html((string) $event['title']); ?></strong>
                <?php if (!empty($event['location'])): ?>
                    <span><?php echo esc_html((string) $event['location']); ?></span>
                <?php endif; ?>
                <?php if (!empty($event['public_url'])): ?>
                    <a href="<?php echo esc_url((string) $event['public_url']); ?>">Programı Gör</a>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
        <div class="elahi-ops-calendar-credit">
            <a href="https://elahimiavagh.com" rel="noopener">Powered by elahimiavagh.com</a>
        </div>
    </div>
    <?php
    return (string) ob_get_clean();
}
add_shortcode('elahi_operations_calendar', 'elahi_ops_calendar_shortcode');


function elahi_ops_calendar_enqueue_public_assets(): void
{
    wp_enqueue_style(
        'elahi-ops-calendar-public',
        plugin_dir_url(__FILE__) . 'assets/public-calendar.css',
        [],
        ELAHI_OPS_CALENDAR_VERSION
    );
}

function elahi_ops_calendar_month_shortcode(array $atts = []): string
{
    $atts = shortcode_atts([
        'month' => wp_date('Y-m'),
        'limit_per_day' => '4',
    ], $atts, 'elahi_operations_calendar_month');

    $monthValue = trim((string) $atts['month']);
    if (!preg_match('/^\\d{4}-\\d{2}$/', $monthValue)) {
        $monthValue = wp_date('Y-m');
    }

    $timezone = wp_timezone();
    try {
        $monthStart = new DateTimeImmutable($monthValue . '-01 00:00:00', $timezone);
    } catch (Throwable) {
        $monthStart = new DateTimeImmutable(wp_date('Y-m-01') . ' 00:00:00', $timezone);
    }

    if ($monthStart->format('Y-m') !== $monthValue) {
        $monthStart = new DateTimeImmutable(wp_date('Y-m-01') . ' 00:00:00', $timezone);
    }

    $monthEnd = $monthStart->modify('last day of this month')->setTime(23, 59, 59);
    $gridStart = $monthStart->modify('monday this week');
    $gridEnd = $monthEnd->modify('sunday this week');
    $utc = new DateTimeZone('UTC');

    $events = elahi_ops_calendar_events([
        'from' => $gridStart->setTimezone($utc)->format(DATE_ATOM),
        'to' => $gridEnd->setTimezone($utc)->format(DATE_ATOM),
        'visibility' => 'public',
        'status' => 'published',
        'limit' => 1000,
    ]);

    $eventsByDate = [];
    foreach ($events as $event) {
        try {
            $eventStart = new DateTimeImmutable((string) $event['start_at'], $utc);
            $eventEnd = new DateTimeImmutable((string) $event['end_at'], $utc);
        } catch (Throwable) {
            continue;
        }

        $localStart = $eventStart->setTimezone($timezone)->setTime(0, 0);
        $localEnd = $eventEnd->setTimezone($timezone)->setTime(0, 0);

        if ($localStart < $gridStart) {
            $localStart = $gridStart;
        }
        if ($localEnd > $gridEnd) {
            $localEnd = $gridEnd;
        }

        for ($day = $localStart; $day <= $localEnd; $day = $day->modify('+1 day')) {
            $eventsByDate[$day->format('Y-m-d')][] = $event;
        }
    }

    $limitPerDay = max(1, min(10, (int) $atts['limit_per_day']));
    $monthNames = [
        1 => 'Ocak', 2 => 'Şubat', 3 => 'Mart', 4 => 'Nisan',
        5 => 'Mayıs', 6 => 'Haziran', 7 => 'Temmuz', 8 => 'Ağustos',
        9 => 'Eylül', 10 => 'Ekim', 11 => 'Kasım', 12 => 'Aralık',
    ];
    $weekdayNames = ['Pzt', 'Sal', 'Çar', 'Per', 'Cum', 'Cmt', 'Paz'];

    elahi_ops_calendar_enqueue_public_assets();

    ob_start();
    ?>
    <section class="elahi-ops-month" data-schema-version="1.0" data-month="<?php echo esc_attr($monthStart->format('Y-m')); ?>">
        <header class="elahi-ops-month__header">
            <div>
                <span class="elahi-ops-month__kicker">Tur Takvimi</span>
                <h2><?php echo esc_html($monthNames[(int) $monthStart->format('n')] . ' ' . $monthStart->format('Y')); ?></h2>
            </div>
            <span class="elahi-ops-month__count"><?php echo esc_html((string) count($events)); ?> program</span>
        </header>

        <div class="elahi-ops-month__scroll">
            <div class="elahi-ops-month__grid" role="grid">
                <?php foreach ($weekdayNames as $weekday): ?>
                    <div class="elahi-ops-month__weekday" role="columnheader"><?php echo esc_html($weekday); ?></div>
                <?php endforeach; ?>

                <?php for ($day = $gridStart; $day <= $gridEnd; $day = $day->modify('+1 day')): ?>
                    <?php
                    $dateKey = $day->format('Y-m-d');
                    $dayEvents = $eventsByDate[$dateKey] ?? [];
                    $visibleEvents = array_slice($dayEvents, 0, $limitPerDay);
                    $hiddenCount = max(0, count($dayEvents) - count($visibleEvents));
                    $classes = ['elahi-ops-month__day'];
                    if ($day->format('Y-m') !== $monthStart->format('Y-m')) {
                        $classes[] = 'is-outside';
                    }
                    if ($dateKey === wp_date('Y-m-d')) {
                        $classes[] = 'is-today';
                    }
                    ?>
                    <div class="<?php echo esc_attr(implode(' ', $classes)); ?>" role="gridcell">
                        <div class="elahi-ops-month__date"><?php echo esc_html($day->format('j')); ?></div>

                        <div class="elahi-ops-month__events">
                            <?php foreach ($visibleEvents as $event): ?>
                                <?php if (!empty($event['public_url'])): ?>
                                    <a class="elahi-ops-month__event" href="<?php echo esc_url((string) $event['public_url']); ?>">
                                        <span><?php echo esc_html((string) $event['title']); ?></span>
                                        <?php if (!empty($event['location'])): ?>
                                            <small><?php echo esc_html((string) $event['location']); ?></small>
                                        <?php endif; ?>
                                    </a>
                                <?php else: ?>
                                    <div class="elahi-ops-month__event">
                                        <span><?php echo esc_html((string) $event['title']); ?></span>
                                        <?php if (!empty($event['location'])): ?>
                                            <small><?php echo esc_html((string) $event['location']); ?></small>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>

                            <?php if ($hiddenCount > 0): ?>
                                <span class="elahi-ops-month__more">+<?php echo esc_html((string) $hiddenCount); ?> program</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endfor; ?>
            </div>
        </div>

        <?php if ($events === []): ?>
            <p class="elahi-ops-month__empty">Bu ay için yayınlanmış program bulunmuyor.</p>
        <?php endif; ?>

        <footer class="elahi-ops-month__footer">
            <a href="<?php echo esc_url(elahi_ops_calendar_public_ics_url()); ?>">Takvime Abone Ol (.ics)</a>
            <a href="https://elahimiavagh.com" rel="noopener">Powered by elahimiavagh.com</a>
        </footer>
    </section>
    <?php
    return (string) ob_get_clean();
}
add_shortcode('elahi_operations_calendar_month', 'elahi_ops_calendar_month_shortcode');


function elahi_ops_calendar_register_platform_module(array $modules): array
{
    global $wpdb;

    $table = elahi_ops_calendar_table();
    $tableExists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table));
    $healthy = $tableExists === $table;

    $modules['operations_calendar'] = [
        'name' => 'Operations Calendar Core',
        'version' => ELAHI_OPS_CALENDAR_VERSION,
        'type' => 'projection',
        'health' => $healthy ? 'healthy' : 'error',
        'health_detail' => $healthy ? 'Projection table ready.' : 'Projection table missing.',
        'source_of_truth' => 'Projection only',
        'schema_version' => 'event-v1.0',
        'integration_state' => 'public/internal/private contract ready',
    ];

    return $modules;
}
add_filter('elahi_platform_modules', 'elahi_ops_calendar_register_platform_module');


function elahi_ops_calendar_public_ics_url(): string
{
    return home_url('/operations-calendar.ics');
}

function elahi_ops_calendar_ics_escape(string $value): string
{
    return str_replace(
        ["\\", ";", ",", "\r\n", "\r", "\n"],
        ["\\\\", "\\;", "\\,", "\\n", "\\n", "\\n"],
        $value
    );
}

function elahi_ops_calendar_public_ics_events(): array
{
    $from = gmdate(DATE_ATOM, strtotime('-7 days'));
    $to = gmdate(DATE_ATOM, strtotime('+18 months'));
    $events = [];

    foreach (['tour', 'umrah'] as $sourceModule) {
        foreach (elahi_ops_calendar_events([
            'from' => $from,
            'to' => $to,
            'visibility' => 'public',
            'status' => 'published',
            'source_module' => $sourceModule,
            'limit' => 1000,
        ]) as $event) {
            $uid = (string) ($event['event_uid'] ?? '');
            if ($uid !== '') {
                $events[$uid] = $event;
            }
        }
    }

    $events = array_values($events);
    usort($events, static function (array $a, array $b): int {
        return strcmp((string) ($a['start_at'] ?? ''), (string) ($b['start_at'] ?? ''));
    });

    return $events;
}

function elahi_ops_calendar_build_public_ics(): string
{
    $timezone = wp_timezone();
    $utc = new DateTimeZone('UTC');
    $lines = [
        'BEGIN:VCALENDAR',
        'VERSION:2.0',
        'PRODID:-//elahimiavagh.com//Operations Calendar//TR',
        'CALSCALE:GREGORIAN',
        'METHOD:PUBLISH',
        'X-WR-CALNAME:' . elahi_ops_calendar_ics_escape(get_bloginfo('name') . ' Tur Takvimi'),
        'X-WR-TIMEZONE:' . elahi_ops_calendar_ics_escape($timezone->getName()),
        'REFRESH-INTERVAL;VALUE=DURATION:PT6H',
        'X-PUBLISHED-TTL:PT6H',
    ];

    $dtstamp = gmdate('Ymd\THis\Z');

    foreach (elahi_ops_calendar_public_ics_events() as $event) {
        try {
            $start = new DateTimeImmutable((string) $event['start_at'], $utc);
            $end = new DateTimeImmutable((string) $event['end_at'], $utc);
        } catch (Throwable) {
            continue;
        }

        $localStart = $start->setTimezone($timezone);
        $localEnd = $end->setTimezone($timezone);
        $uid = hash('sha256', (string) $event['event_uid']) . '@elahimiavagh.com';

        $lines[] = 'BEGIN:VEVENT';
        $lines[] = 'UID:' . $uid;
        $lines[] = 'DTSTAMP:' . $dtstamp;

        if ((int) ($event['all_day'] ?? 0) === 1) {
            $startDate = $localStart->format('Ymd');
            $exclusiveEnd = $localEnd->setTime(0, 0)->modify('+1 day')->format('Ymd');
            $lines[] = 'DTSTART;VALUE=DATE:' . $startDate;
            $lines[] = 'DTEND;VALUE=DATE:' . $exclusiveEnd;
        } else {
            $lines[] = 'DTSTART:' . $start->setTimezone($utc)->format('Ymd\THis\Z');
            $lines[] = 'DTEND:' . $end->setTimezone($utc)->format('Ymd\THis\Z');
        }

        $lines[] = 'SUMMARY:' . elahi_ops_calendar_ics_escape((string) ($event['title'] ?? 'Program'));

        if (!empty($event['location'])) {
            $lines[] = 'LOCATION:' . elahi_ops_calendar_ics_escape((string) $event['location']);
        }

        if (!empty($event['public_url'])) {
            $lines[] = 'URL:' . (string) $event['public_url'];
        }

        $source = strtoupper((string) ($event['source_module'] ?? 'PROGRAM'));
        $lines[] = 'CATEGORIES:' . elahi_ops_calendar_ics_escape($source);
        $lines[] = 'STATUS:CONFIRMED';
        $lines[] = 'TRANSP:TRANSPARENT';
        $lines[] = 'END:VEVENT';
    }

    $lines[] = 'END:VCALENDAR';

    return implode("\r\n", $lines) . "\r\n";
}

function elahi_ops_calendar_register_rewrite(): void
{
    add_rewrite_rule(
        '^operations-calendar\.ics$',
        'index.php?elahi_ops_calendar_ics=1',
        'top'
    );
}
add_action('init', 'elahi_ops_calendar_register_rewrite');

function elahi_ops_calendar_query_vars(array $vars): array
{
    $vars[] = 'elahi_ops_calendar_ics';
    return $vars;
}
add_filter('query_vars', 'elahi_ops_calendar_query_vars');

function elahi_ops_calendar_serve_public_ics(): void
{
    if ((string) get_query_var('elahi_ops_calendar_ics') !== '1') {
        return;
    }

    status_header(200);
    nocache_headers();
    header('Content-Type: text/calendar; charset=utf-8');
    header('Content-Disposition: inline; filename="operations-calendar.ics"');
    header('X-Robots-Tag: noindex, nofollow', true);
    header('Referrer-Policy: no-referrer', true);

    echo elahi_ops_calendar_build_public_ics();
    exit;
}
add_action('template_redirect', 'elahi_ops_calendar_serve_public_ics', -120);

function elahi_ops_calendar_deactivate(): void
{
    flush_rewrite_rules(false);
}
register_deactivation_hook(__FILE__, 'elahi_ops_calendar_deactivate');
