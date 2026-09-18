<?php
/**
 * Plugin Name: Elahimiavagh Operations Calendar Adapters
 * Description: Rebuildable calendar projections for Umrah Program Intelligence and Tour Intelligence.
 * Version: 0.1.0
 * Author: elahimiavagh.com
 * Author URI: https://elahimiavagh.com
 * Requires PHP: 8.1
 */

if (!defined('ABSPATH')) {
    exit;
}

define('ELAHI_OPS_ADAPTERS_VERSION', '0.1.0');

function elahi_ops_adapters_date_bounds(string $startDate, string $endDate): ?array
{
    if (
        !preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate)
        || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)
    ) {
        return null;
    }

    $timezone = new DateTimeZone('Europe/Istanbul');

    try {
        $start = new DateTimeImmutable($startDate . ' 00:00:00', $timezone);
        $end = new DateTimeImmutable($endDate . ' 23:59:59', $timezone);
    } catch (Throwable) {
        return null;
    }

    if ($start->format('Y-m-d') !== $startDate || $end->format('Y-m-d') !== $endDate || $end < $start) {
        return null;
    }

    return [
        'start_at' => $start->format(DATE_ATOM),
        'end_at' => $end->format(DATE_ATOM),
    ];
}

function elahi_ops_adapters_program_location(array $program): string
{
    $labels = [];

    foreach ((array) ($program['destinations'] ?? []) as $destination) {
        if (!is_array($destination)) {
            continue;
        }

        $label = trim((string) ($destination['city'] ?? $destination['name'] ?? ''));
        if ($label !== '') {
            $labels[$label] = true;
        }
    }

    return implode(' · ', array_slice(array_keys($labels), 0, 4));
}

function elahi_ops_adapters_prepare_program_dependencies()
{
    if (!defined('STPPI_DIR') || !defined('STPI_DIR')) {
        return new WP_Error('program_plugins_missing', 'Program Intelligence / Publishing Integration is not active.');
    }

    if (!class_exists('STPPI_Repository')) {
        require_once STPPI_DIR . 'includes/class-stppi-repository.php';
    }

    if (!class_exists('STPPI_Renderer')) {
        require_once STPPI_DIR . 'includes/class-stppi-renderer.php';
    }

    if (!class_exists('STPPI_Integrations')) {
        require_once STPPI_DIR . 'includes/class-stppi-integrations.php';
    }

    if (!class_exists('STPPI_Repository') || !class_exists('STPPI_Integrations')) {
        return new WP_Error('program_repository_missing', 'Program repository integration is unavailable.');
    }

    $dependencies = STPPI_Repository::dependencies();
    return is_wp_error($dependencies) ? $dependencies : true;
}

function elahi_ops_adapters_sync_umrah()
{
    if (!function_exists('elahi_ops_calendar_reconcile_source_events')) {
        return new WP_Error('calendar_core_missing', 'Operations Calendar Core is not active.');
    }

    $dependencies = elahi_ops_adapters_prepare_program_dependencies();
    if (is_wp_error($dependencies)) {
        return $dependencies;
    }

    $all = STPPI_Repository::all_rows();
    if (is_wp_error($all)) {
        return $all;
    }

    $publicById = [];
    foreach ((array) STPPI_Integrations::eligible_public_noindex_programs() as $item) {
        if (!is_array($item)) {
            continue;
        }

        $id = strtoupper(trim((string) ($item['id'] ?? '')));
        if (!preg_match('/^STP-[0-9]{6}$/D', $id)) {
            continue;
        }

        $config = is_array($item['config'] ?? null) ? $item['config'] : [];
        $publicUrl = '';

        if ($config !== [] && class_exists('STPPI_Renderer')) {
            $publicUrl = (string) STPPI_Renderer::url($config);
        }

        $publicById[$id] = [
            'url' => $publicUrl,
        ];
    }

    $events = [];

    foreach ((array) ($all['rows'] ?? []) as $programId => $row) {
        $programId = strtoupper(trim((string) $programId));

        if (
            !preg_match('/^STP-[0-9]{6}$/D', $programId)
            || in_array($programId, ['STP-000036', 'STP-000037'], true)
            || !is_array($row)
        ) {
            continue;
        }

        $program = is_array($row['program'] ?? null) ? $row['program'] : [];
        if (($program['service_type'] ?? '') !== 'umrah') {
            continue;
        }

        $workflow = is_array($program['workflow'] ?? null) ? $program['workflow'] : [];
        if (($workflow['editorial'] ?? '') !== 'approved' || ($workflow['schedule'] ?? '') !== 'scheduled') {
            continue;
        }

        $temporal = STPPI_Repository::temporal($program);
        if (!in_array($temporal, ['upcoming', 'in_progress'], true)) {
            continue;
        }

        $schedule = is_array($program['schedule'] ?? null) ? $program['schedule'] : [];
        $startDate = trim((string) ($schedule['start_date'] ?? ''));
        $endDate = trim((string) ($schedule['end_date'] ?? ''));
        $bounds = elahi_ops_adapters_date_bounds($startDate, $endDate);

        if ($bounds === null) {
            continue;
        }

        $title = trim(wp_strip_all_tags((string) ($program['title'] ?? '')));
        if ($title === '') {
            $title = 'Umre Programı';
        }

        $isPublic = isset($publicById[$programId]);
        $publicUrl = $isPublic ? trim((string) ($publicById[$programId]['url'] ?? '')) : '';

        $events[] = [
            'event_uid' => 'umrah:' . $programId . ':schedule',
            'source_module' => 'umrah',
            'source_entity_id' => $programId,
            'event_type' => 'umrah_program',
            'title' => $title,
            'start_at' => $bounds['start_at'],
            'end_at' => $bounds['end_at'],
            'all_day' => true,
            'status' => 'published',
            'visibility' => $isPublic ? 'public' : 'internal',
            'priority' => 80,
            'location' => elahi_ops_adapters_program_location($program) ?: null,
            'public_url' => $publicUrl !== '' ? $publicUrl : null,
            'source_version' => defined('STPI_VERSION') ? (string) STPI_VERSION : null,
            'metadata' => [
                'availability' => (string) ($workflow['availability'] ?? ''),
                'duration_days' => isset($schedule['duration_days']) ? (int) $schedule['duration_days'] : null,
                'duration_nights' => isset($schedule['duration_nights']) ? (int) $schedule['duration_nights'] : null,
            ],
        ];
    }

    return elahi_ops_calendar_reconcile_source_events('umrah', $events, true);
}

function elahi_ops_adapters_prepare_tour_dependencies()
{
    $required = [
        'stti_get_candidates',
        'stti_v110_payload',
        'stti_v110_record_is_eligible',
        'stti_v110_card_model',
    ];

    foreach ($required as $functionName) {
        if (!function_exists($functionName)) {
            return new WP_Error('tour_plugin_missing', 'Tour Intelligence calendar API is unavailable.');
        }
    }

    return true;
}

function elahi_ops_adapters_sync_tours()
{
    if (!function_exists('elahi_ops_calendar_reconcile_source_events')) {
        return new WP_Error('calendar_core_missing', 'Operations Calendar Core is not active.');
    }

    $dependencies = elahi_ops_adapters_prepare_tour_dependencies();
    if (is_wp_error($dependencies)) {
        return $dependencies;
    }

    $hubMaster = function_exists('stti_v110_hub_enabled') && stti_v110_hub_enabled();
    $events = [];

    foreach ((array) stti_get_candidates() as $row) {
        if (!is_array($row)) {
            continue;
        }

        $payload = stti_v110_payload($row);
        if (!stti_v110_record_is_eligible($row, $payload)) {
            continue;
        }

        $date = is_array($payload['date'] ?? null) ? $payload['date'] : [];
        $startDate = trim((string) ($date['start_date'] ?? ''));
        $endDate = trim((string) ($date['end_date'] ?? ''));

        if ($endDate === '' && $startDate !== '') {
            $endDate = $startDate;
        }

        $bounds = elahi_ops_adapters_date_bounds($startDate, $endDate);
        if ($bounds === null) {
            continue;
        }

        $card = stti_v110_card_model($row, $payload);
        $stableId = trim((string) ($card['stable_id'] ?? $row['stable_id'] ?? ''));
        if ($stableId === '') {
            continue;
        }

        $detailUrl = trim((string) ($card['detail_url'] ?? ''));
        $hubVisible = function_exists('stti_v117_hub_visible')
            && stti_v117_hub_visible($payload);

        $isPublic = $detailUrl !== '' || ($hubMaster && $hubVisible);
        $publicUrl = $detailUrl;

        if ($publicUrl === '' && $hubMaster && $hubVisible) {
            $publicUrl = home_url('/kultur-turlari/');
        }

        $lifecycle = is_array($payload['lifecycle'] ?? null) ? $payload['lifecycle'] : [];

        $events[] = [
            'event_uid' => 'tour:' . $stableId . ':schedule',
            'source_module' => 'tour',
            'source_entity_id' => $stableId,
            'event_type' => 'culture_tour',
            'title' => trim((string) ($card['title'] ?? 'Tur')) ?: 'Tur',
            'start_at' => $bounds['start_at'],
            'end_at' => $bounds['end_at'],
            'all_day' => true,
            'status' => 'published',
            'visibility' => $isPublic ? 'public' : 'internal',
            'priority' => 70,
            'location' => trim((string) ($card['destination'] ?? '')) ?: null,
            'public_url' => $publicUrl !== '' ? $publicUrl : null,
            'source_version' => defined('STTI_RELEASE_VERSION')
                ? (string) STTI_RELEASE_VERSION
                : (defined('STTI_VERSION') ? (string) STTI_VERSION : null),
            'metadata' => [
                'availability' => (string) ($lifecycle['availability'] ?? $row['availability'] ?? ''),
                'hub_visible' => $hubVisible,
                'hub_master' => $hubMaster,
            ],
        ];
    }

    return elahi_ops_calendar_reconcile_source_events('tour', $events, true);
}

function elahi_ops_adapters_sync_all()
{
    $results = [];
    $errors = [];

    $sources = [
        'umrah' => 'elahi_ops_adapters_sync_umrah',
        'tour' => 'elahi_ops_adapters_sync_tours',
    ];

    foreach ($sources as $source => $callback) {
        $result = $callback();

        if (is_wp_error($result)) {
            $errors[$source] = $result->get_error_code() . ': ' . $result->get_error_message();
            continue;
        }

        $results[$source] = $result;
    }

    $state = [
        'version' => ELAHI_OPS_ADAPTERS_VERSION,
        'synced_at_utc' => gmdate(DATE_ATOM),
        'results' => $results,
        'errors' => $errors,
    ];

    update_option('elahi_ops_adapters_last_sync', $state, false);

    if ($results === []) {
        return new WP_Error('calendar_sources_unavailable', 'No calendar source could be synchronized.');
    }

    return $state;
}

function elahi_ops_adapters_job_handler($result, array $payload, int $jobId)
{
    $sync = elahi_ops_adapters_sync_all();

    if (is_wp_error($sync)) {
        return $sync;
    }

    return true;
}

function elahi_ops_adapters_cron_schedules(array $schedules): array
{
    if (!isset($schedules['elahi_every_five_minutes'])) {
        $schedules['elahi_every_five_minutes'] = [
            'interval' => 5 * MINUTE_IN_SECONDS,
            'display' => 'Every Five Minutes — Operations Calendar',
        ];
    }

    return $schedules;
}
add_filter('cron_schedules', 'elahi_ops_adapters_cron_schedules');

function elahi_ops_adapters_enqueue_sync()
{
    if (!function_exists('elahi_platform_enqueue_unique_job')) {
        return new WP_Error('platform_core_missing', 'Elahimiavagh Platform Core is not active.');
    }

    return elahi_platform_enqueue_unique_job(
        'calendar_sync_all',
        ['requested_by' => 'schedule'],
        ['max_attempts' => 3]
    );
}

function elahi_ops_adapters_schedule(): void
{
    if (!wp_next_scheduled('elahi_ops_adapters_schedule_sync')) {
        wp_schedule_event(time() + 60, 'elahi_every_five_minutes', 'elahi_ops_adapters_schedule_sync');
    }
}
add_action('init', 'elahi_ops_adapters_schedule');
add_action('elahi_ops_adapters_schedule_sync', 'elahi_ops_adapters_enqueue_sync');

function elahi_ops_adapters_activate(): void
{
    elahi_ops_adapters_schedule();
}
register_activation_hook(__FILE__, 'elahi_ops_adapters_activate');

function elahi_ops_adapters_deactivate(): void
{
    wp_clear_scheduled_hook('elahi_ops_adapters_schedule_sync');
}
register_deactivation_hook(__FILE__, 'elahi_ops_adapters_deactivate');

function elahi_ops_adapters_register_runtime(): void
{
    add_filter('elahi_platform_job_calendar_sync_all', 'elahi_ops_adapters_job_handler', 10, 3);
}
add_action('plugins_loaded', 'elahi_ops_adapters_register_runtime', 50);

function elahi_ops_adapters_register_platform_module(array $modules): array
{
    $lastSync = get_option('elahi_ops_adapters_last_sync', []);
    $lastSync = is_array($lastSync) ? $lastSync : [];
    $errors = is_array($lastSync['errors'] ?? null) ? $lastSync['errors'] : [];

    $calendarReady = function_exists('elahi_ops_calendar_reconcile_source_events');
    $queueReady = function_exists('elahi_platform_enqueue_unique_job');

    $health = ($calendarReady && $queueReady) ? 'healthy' : 'warning';
    if ($errors !== []) {
        $health = 'warning';
    }

    $detail = $calendarReady && $queueReady
        ? 'Projection adapters loaded.'
        : 'Calendar Core or Platform Core is unavailable.';

    if (!empty($lastSync['synced_at_utc'])) {
        $detail .= ' Last sync: ' . (string) $lastSync['synced_at_utc'] . '.';
    }

    if ($errors !== []) {
        $detail .= ' Source warning: ' . implode(' | ', array_keys($errors)) . '.';
    }

    $modules['operations_calendar_adapters'] = [
        'name' => 'Operations Calendar Adapters',
        'version' => ELAHI_OPS_ADAPTERS_VERSION,
        'type' => 'integration',
        'health' => $health,
        'health_detail' => $detail,
        'source_of_truth' => 'Program Intelligence + Tour Intelligence',
        'schema_version' => 'event-v1.0',
        'integration_state' => '5-minute projection sync',
    ];

    return $modules;
}
add_filter('elahi_platform_modules', 'elahi_ops_adapters_register_platform_module');

function elahi_ops_adapters_admin_post_sync(): void
{
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }

    check_admin_referer('elahi_ops_adapters_sync_now');

    $result = elahi_ops_adapters_enqueue_sync();
    $status = is_wp_error($result) ? 'error' : 'queued';

    wp_safe_redirect(
        add_query_arg(
            [
                'page' => 'elahi-platform',
                'calendar_sync' => $status,
            ],
            admin_url('admin.php')
        )
    );
    exit;
}
add_action('admin_post_elahi_ops_adapters_sync_now', 'elahi_ops_adapters_admin_post_sync');

function elahi_ops_adapters_admin_notice(): void
{
    if (!is_admin() || !current_user_can('manage_options')) {
        return;
    }

    $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
    if ($page !== 'elahi-platform') {
        return;
    }

    $state = get_option('elahi_ops_adapters_last_sync', []);
    $state = is_array($state) ? $state : [];

    ?>
    <div class="notice notice-info inline" style="padding:12px 14px;margin:18px 0">
        <p>
            <strong>Operations Calendar Sync</strong>
            <?php if (!empty($state['synced_at_utc'])): ?>
                · Son senkronizasyon: <code><?php echo esc_html((string) $state['synced_at_utc']); ?></code>
            <?php else: ?>
                · Henüz senkronizasyon kaydı yok.
            <?php endif; ?>
        </p>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin:0 0 4px">
            <input type="hidden" name="action" value="elahi_ops_adapters_sync_now">
            <?php wp_nonce_field('elahi_ops_adapters_sync_now'); ?>
            <button type="submit" class="button button-primary">Takvim Senkronizasyonunu Kuyruğa Al</button>
        </form>
    </div>
    <?php
}
add_action('admin_notices', 'elahi_ops_adapters_admin_notice', 20);

if (defined('WP_CLI') && WP_CLI && class_exists('WP_CLI')) {
    WP_CLI::add_command('elahi calendar sync', static function (): void {
        $result = elahi_ops_adapters_sync_all();

        if (is_wp_error($result)) {
            WP_CLI::error($result->get_error_message());
        }

        WP_CLI::success('Operations Calendar projections synchronized.');
    });
}
