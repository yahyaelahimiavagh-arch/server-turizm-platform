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
}

register_activation_hook(__FILE__, 'elahi_ops_calendar_install');

function elahi_ops_calendar_parse_utc(string $value): ?string
{
    try {
        $date = new DateTimeImmutable($value);
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
            <a href="https://elahimiavagh.com" rel="noopener">Powered by elahimiavagh.com</a>
        </footer>
    </section>
    <?php
    return (string) ob_get_clean();
}
add_shortcode('elahi_operations_calendar_month', 'elahi_ops_calendar_month_shortcode');
