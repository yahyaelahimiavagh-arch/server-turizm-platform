<?php
/**
 * Plugin Name: Server Turizm Tour Intelligence
 * Description: STTI v0.6.0 JSON Import Validate + Dry Run Gate: preserves the accepted private customer experience while adding STTI-TOUR-IMPORT-1.0.0 paste/upload validation, normalization and CREATE / UPDATE / UNCHANGED / CONFLICT dry-run classification. Import commit/write remains locked. Public routes, sitemap, indexation, schema output and canonical/robots changes remain hard locked.
 * Version: 0.6.0
 * Author: Server Turizm
 */

if (!defined('ABSPATH')) { exit; }

define('STTI_VERSION', '0.6.0');
define('STTI_SCHEMA_VERSION', '1.1.0');
define('STTI_FILE', __FILE__);
define('STTI_DIR', plugin_dir_path(__FILE__));
define('STTI_URL', plugin_dir_url(__FILE__));

register_activation_hook(__FILE__, 'stti_activate');
add_action('admin_init', 'stti_maybe_upgrade');
add_action('admin_init', 'stti_maybe_redirect_legacy_customer_preview', 20);
add_action('template_redirect', 'stti_maybe_render_customer_theme_preview', 0);
add_action('admin_menu', 'stti_register_admin_menu');
add_action('admin_enqueue_scripts', 'stti_admin_assets');
add_action('admin_head', 'stti_private_preview_noindex');
add_action('admin_post_stti_save_candidate', 'stti_handle_save_candidate');
add_action('admin_post_stti_export_all_json', 'stti_handle_export_all_json');

function stti_tables() {
    global $wpdb;
    return [
        'tours' => $wpdb->prefix . 'stti_tours',
        'audit' => $wpdb->prefix . 'stti_audit',
    ];
}

function stti_maybe_upgrade() {
    if (get_option('stti_db_version') !== STTI_VERSION) {
        stti_activate();
    }
}

function stti_activate() {
    global $wpdb;
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    $t = stti_tables();
    $charset = $wpdb->get_charset_collate();

    $sql_tours = "CREATE TABLE {$t['tours']} (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        stable_id VARCHAR(32) NOT NULL,
        schema_version VARCHAR(16) NOT NULL,
        public_title VARCHAR(255) NOT NULL,
        slug VARCHAR(255) NOT NULL DEFAULT '',
        editorial VARCHAR(32) NOT NULL DEFAULT 'needs_review',
        schedule_status VARCHAR(32) NOT NULL DEFAULT 'tentative',
        availability VARCHAR(32) NOT NULL DEFAULT 'on_request',
        temporal VARCHAR(32) NOT NULL DEFAULT 'undated',
        source_completeness VARCHAR(32) NOT NULL DEFAULT 'source_minimal',
        payload LONGTEXT NOT NULL,
        checksum CHAR(64) NOT NULL,
        created_by BIGINT UNSIGNED NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY stable_id (stable_id),
        KEY editorial (editorial),
        KEY temporal (temporal)
    ) $charset;";

    $sql_audit = "CREATE TABLE {$t['audit']} (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        stable_id VARCHAR(32) NOT NULL,
        event VARCHAR(64) NOT NULL,
        actor_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
        before_json LONGTEXT NULL,
        after_json LONGTEXT NULL,
        created_at DATETIME NOT NULL,
        PRIMARY KEY  (id),
        KEY stable_id (stable_id),
        KEY event (event)
    ) $charset;";

    dbDelta($sql_tours);
    dbDelta($sql_audit);
    if (get_option('stti_next_sequence', null) === null) {
        add_option('stti_next_sequence', 1, '', false);
    }
    update_option('stti_db_version', STTI_VERSION, false);
}

function stti_register_admin_menu() {
    add_menu_page('Tour Intelligence','Tour Intelligence','manage_options','stti-tour-intelligence','stti_render_admin','dashicons-location-alt',31);
}

function stti_admin_assets($hook) {
    if ($hook !== 'toplevel_page_stti-tour-intelligence') { return; }
    wp_enqueue_style('stti-admin', STTI_URL . 'assets/admin.css', [], STTI_VERSION);
    wp_enqueue_script('stti-admin', STTI_URL . 'assets/admin.js', [], STTI_VERSION, true);
}

function stti_fixture_tours() {
    return [
        ['stable_id'=>'STT-PREVIEW-001','title'=>'Büyük İran Turu','destination'=>'İran','route'=>'Tahran → Kaşan → İsfahan → Yezd → Şiraz','date'=>'16–24 Ekim 2026','duration'=>'8 Gece / 9 Gün','price'=>'899 €','price_type'=>'exact','source'=>'Broşür / görsel','completeness'=>'SOURCE COMPLETE','editorial'=>'NEEDS REVIEW','schedule'=>'SCHEDULED','temporal'=>'UPCOMING','availability'=>'OPEN','health'=>86,'class'=>'good'],
        ['stable_id'=>'STT-PREVIEW-002','title'=>'Büyük Balkan Turu','destination'=>'5 Ülke / 17 Şehir','route'=>'Bosna · Makedonya · Arnavutluk · Kosova · Karadağ','date'=>'26 Eylül – 2 Ekim · yıl belirtilmemiş','duration'=>'6 Gece / 7 Gün','price'=>'1100 €’dan başlayan','price_type'=>'from','source'=>'Broşür / görsel','completeness'=>'SOURCE PARTIAL','editorial'=>'NEEDS REVIEW','schedule'=>'TENTATIVE','temporal'=>'UNDATED','availability'=>'OPEN','health'=>73,'class'=>'warn'],
        ['stable_id'=>'STT-PREVIEW-003','title'=>'Özbekistan Kültür Turu','destination'=>'Özbekistan','route'=>'Semerkand · Buhara','date'=>'Tarih yakında','duration'=>'—','price'=>'Talep üzerine','price_type'=>'on_request','source'=>'Homepage mevcut içerik','completeness'=>'SOURCE MINIMAL','editorial'=>'NEEDS REVIEW','schedule'=>'TENTATIVE','temporal'=>'UNDATED','availability'=>'ON REQUEST','health'=>42,'class'=>'bad'],
        ['stable_id'=>'STT-PREVIEW-004','title'=>'Mısır Kültür Turu','destination'=>'Mısır','route'=>'Kahire · Piramitler','date'=>'Tarih yakında','duration'=>'—','price'=>'Talep üzerine','price_type'=>'on_request','source'=>'Homepage mevcut içerik','completeness'=>'SOURCE MINIMAL','editorial'=>'NEEDS REVIEW','schedule'=>'TENTATIVE','temporal'=>'UNDATED','availability'=>'ON REQUEST','health'=>39,'class'=>'bad'],
    ];
}


function stti_get_fixture($fixture_id) {
    foreach (stti_fixture_tours() as $fixture) {
        if (($fixture['stable_id'] ?? '') === $fixture_id) return $fixture;
    }
    return null;
}

function stti_fixture_to_form($fixture_id) {
    $f = stti_default_form();
    $f['origin_fixture_id'] = $fixture_id;
    $f['source_ref'] = 'fixture:' . $fixture_id;
    switch ($fixture_id) {
        case 'STT-PREVIEW-001':
            $f = array_merge($f, [
                'tour_code'=>'IRN-2026-01','public_title'=>'Büyük İran Turu','short_title'=>'İran','slug'=>'buyuk-iran-kultur-turu',
                'primary_country'=>'İran','countries'=>'İran','primary_city'=>'Tahran','cities'=>'Tahran, Kaşan, İsfahan, Yezd, Şiraz',
                'departure_city'=>'','return_city'=>'','date_mode'=>'exact','date_precision'=>'exact','start_date'=>'2026-10-16','end_date'=>'2026-10-24',
                'route'=>'Tahran → Kaşan → İsfahan → Yezd → Şiraz','price_type'=>'exact','price_amount'=>'899','currency'=>'EUR','price_basis'=>'unknown',
                'source_type'=>'brochure','source_completeness'=>'source_complete','editorial'=>'needs_review','schedule_status'=>'scheduled','availability'=>'open','temporal'=>'upcoming',
            ]);
            break;
        case 'STT-PREVIEW-002':
            $f = array_merge($f, [
                'public_title'=>'Büyük Balkan Turu','short_title'=>'Balkanlar','slug'=>'buyuk-balkan-turu',
                'primary_country'=>'','countries'=>'Bosna, Makedonya, Arnavutluk, Kosova, Karadağ','primary_city'=>'','cities'=>'',
                'date_mode'=>'tentative','date_precision'=>'day_month_only','route'=>'Bosna → Makedonya → Arnavutluk → Kosova → Karadağ',
                'price_type'=>'from','price_amount'=>'1100','currency'=>'EUR','price_basis'=>'unknown',
                'source_type'=>'brochure','source_completeness'=>'source_partial','editorial'=>'needs_review','schedule_status'=>'tentative','availability'=>'open','temporal'=>'undated',
            ]);
            break;
        case 'STT-PREVIEW-003':
            $f = array_merge($f, [
                'public_title'=>'Özbekistan Kültür Turu','short_title'=>'Özbekistan','slug'=>'ozbekistan-kultur-turu',
                'primary_country'=>'Özbekistan','countries'=>'Özbekistan','primary_city'=>'','cities'=>'Semerkand, Buhara',
                'date_mode'=>'coming_soon','date_precision'=>'unknown','route'=>'Semerkand → Buhara','price_type'=>'on_request','price_amount'=>'','currency'=>'EUR','price_basis'=>'unknown',
                'source_type'=>'legacy_wordpress','source_completeness'=>'source_minimal','editorial'=>'needs_review','schedule_status'=>'tentative','availability'=>'on_request','temporal'=>'undated',
            ]);
            break;
        case 'STT-PREVIEW-004':
            $f = array_merge($f, [
                'public_title'=>'Mısır Kültür Turu','short_title'=>'Mısır','slug'=>'misir-kultur-turu',
                'primary_country'=>'Mısır','countries'=>'Mısır','primary_city'=>'Kahire','cities'=>'Kahire',
                'date_mode'=>'coming_soon','date_precision'=>'unknown','route'=>'Kahire · Piramitler','price_type'=>'on_request','price_amount'=>'','currency'=>'EUR','price_basis'=>'unknown',
                'source_type'=>'legacy_wordpress','source_completeness'=>'source_minimal','editorial'=>'needs_review','schedule_status'=>'tentative','availability'=>'on_request','temporal'=>'undated',
            ]);
            break;
    }
    stti_derive_date_facts($f);
    return $f;
}

function stti_find_candidate_by_origin_fixture($fixture_id) {
    if (!$fixture_id) return null;
    foreach (stti_get_candidates() as $row) {
        $payload = json_decode($row['payload'], true);
        if (is_array($payload) && (($payload['provenance']['origin_fixture_id'] ?? '') === $fixture_id)) return $row;
    }
    return null;
}

function stti_get_candidates() {
    global $wpdb; $t = stti_tables();
    $rows = $wpdb->get_results("SELECT * FROM {$t['tours']} ORDER BY updated_at DESC, id DESC", ARRAY_A);
    return is_array($rows) ? $rows : [];
}

function stti_get_candidate($stable_id) {
    global $wpdb; $t = stti_tables();
    return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$t['tours']} WHERE stable_id=%s LIMIT 1", $stable_id), ARRAY_A);
}

function stti_get_audit($stable_id, $limit = 20) {
    global $wpdb; $t = stti_tables();
    return $wpdb->get_results($wpdb->prepare("SELECT * FROM {$t['audit']} WHERE stable_id=%s ORDER BY id DESC LIMIT %d", $stable_id, $limit), ARRAY_A);
}


function stti_get_all_audit($stable_id) {
    global $wpdb; $t = stti_tables();
    $rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$t['audit']} WHERE stable_id=%s ORDER BY id ASC", $stable_id), ARRAY_A);
    return is_array($rows) ? $rows : [];
}


function stti_private_preview_url($stable_id) {
    return add_query_arg([
        'page'=>'stti-tour-intelligence',
        'view'=>'preview',
        'tour'=>$stable_id,
    ], admin_url('admin.php'));
}

function stti_customer_preview_url($stable_id) {
    $stable_id = sanitize_text_field((string)$stable_id);
    $url = add_query_arg([
        'stti_customer_preview'=>'1',
        'tour'=>$stable_id,
        'stti_preview_nonce'=>wp_create_nonce('stti_customer_preview_' . $stable_id),
    ], home_url('/'));
    return $url;
}

function stti_customer_preview_fingerprint($row) {
    $stable_id = (string)($row['stable_id'] ?? '');
    $checksum = (string)($row['checksum'] ?? '');
    return hash('sha256', 'STTI-CUSTOMER-PREVIEW-0.3.4|' . $stable_id . '|' . $checksum . '|dom-measured-live-header-hero-bleed-restored-route');
}

function stti_customer_preview_evidence($row, $payload) {
    $payload = is_array($payload) ? $payload : [];
    return [
        'contract'=>'STTI-CUSTOMER-PREVIEW-0.3.4',
        'status'=>'pilot_active',
        'stable_id'=>(string)($row['stable_id'] ?? ''),
        'access'=>'front_end_private/manage_options+nonce',
        'surface'=>'wordpress_theme_shell_admin_only',
        'header'=>'live_theme_get_header',
        'footer'=>'live_theme_get_footer',
        'source'=>'canonical_payload_direct_read',
        'data_duplication'=>false,
        'writes'=>0,
        'public_route'=>false,
        'indexable'=>false,
        'sitemap'=>false,
        'schema_output_by_stti'=>false,
        'canonical_changes_by_stti'=>false,
        'cta_status'=>'working_links',
        'media_policy'=>'canonical_media_then_verified_site_destination_fallback',
        'missing_fact_policy'=>'hide_or_neutral_fallback_never_invent',
        'layout_mode'=>'full_bleed_viewport',
        'header_overlap_guard'=>'actual_live_header_selector_dom_measurement',
        'header_glass_compatibility'=>true,
        'summary_bar'=>'large_color_coded_cards',
        'header_seam_strategy'=>'hero_background_bleed_to_live_header_top_dynamic',
        'route_map_engine'=>'leaflet_global_route',
        'route_map_tiles'=>'openstreetmap',
        'route_geocoder'=>'nominatim_client_pilot',
        'route_coordinate_cache'=>'browser_localstorage_pilot',
        'route_unresolved_policy'=>'never_invent_admin_warning',
        'route_auto_fit_bounds'=>true,
        'route_animation'=>'segment_by_segment_restored_v054',
        'route_animation_trigger'=>'viewport_after_geocode',
        'route_marker_reveal'=>'progressive_restored_v054',
        'route_timeline_sync'=>true,
        'route_visual_skin'=>'v0.5.4_restored',
        'hero_artwork_lift_px'=>'dom_measured_dynamic',
        'route_reduced_motion'=>'instant_final_state',
        'source_checksum_sha256'=>(string)($row['checksum'] ?? ''),
        'render_fingerprint_sha256'=>stti_customer_preview_fingerprint($row),
        'preview_url'=>stti_customer_preview_url((string)($row['stable_id'] ?? '')),
    ];
}

function stti_private_renderer_fingerprint($row) {
    $stable_id = (string)($row['stable_id'] ?? '');
    $checksum = (string)($row['checksum'] ?? '');
    return hash('sha256', 'STTI-PRIVATE-RENDERER-0.1.0|' . $stable_id . '|' . $checksum);
}

function stti_private_renderer_evidence($row, $payload) {
    $payload = is_array($payload) ? $payload : [];
    return [
        'contract'=>'STTI-PRIVATE-RENDERER-0.1.0',
        'status'=>'runtime_accepted',
        'stable_id'=>(string)($row['stable_id'] ?? ''),
        'access'=>'wp-admin/manage_options',
        'surface'=>'admin_only',
        'source'=>'canonical_payload_direct_read',
        'data_duplication'=>false,
        'public_route'=>false,
        'indexable'=>false,
        'sitemap'=>false,
        'schema_output'=>false,
        'canonical_changes'=>false,
        'source_checksum_sha256'=>(string)($row['checksum'] ?? ''),
        'render_fingerprint_sha256'=>stti_private_renderer_fingerprint($row),
        'section_counts'=>[
            'route_stops'=>count(is_array($payload['route']['stops'] ?? null)?$payload['route']['stops']:[]),
            'itinerary_days'=>count(is_array($payload['itinerary'] ?? null)?$payload['itinerary']:[]),
            'hotel_relations'=>count(is_array($payload['stays']['hotels'] ?? null)?$payload['stays']['hotels']:[]),
            'transport_segments'=>count(is_array($payload['transport']['segments'] ?? null)?$payload['transport']['segments']:[]),
            'pricing_items'=>count(is_array($payload['pricing']['items'] ?? null)?$payload['pricing']['items']:[]),
            'included_services'=>count(is_array($payload['services']['included'] ?? null)?$payload['services']['included']:[]),
            'excluded_services'=>count(is_array($payload['services']['excluded'] ?? null)?$payload['services']['excluded']:[]),
            'requirements'=>count(is_array($payload['requirements']['items'] ?? null)?$payload['requirements']['items']:[]),
        ],
        'preview_url'=>stti_private_preview_url((string)($row['stable_id'] ?? '')),
    ];
}

function stti_private_preview_noindex() {
    if (!current_user_can('manage_options')) return;
    if (!isset($_GET['page'], $_GET['view'])) return;
    if (sanitize_key(wp_unslash($_GET['page'])) !== 'stti-tour-intelligence') return;
    if (sanitize_key(wp_unslash($_GET['view'])) !== 'preview') return;
    echo "\n<meta name=\"robots\" content=\"noindex,nofollow,noarchive,nosnippet,noimageindex\">\n";
}

function stti_export_all_url() {
    return wp_nonce_url(admin_url('admin-post.php?action=stti_export_all_json'), 'stti_export_all_json');
}

function stti_collect_full_export() {
    global $wp_version;
    $candidates = [];
    foreach (stti_get_candidates() as $row) {
        $payload = json_decode($row['payload'], true);
        if (!is_array($payload)) { $payload = []; }
        $audit = [];
        foreach (stti_get_all_audit($row['stable_id']) as $event) {
            $audit[] = [
                'id' => (int) $event['id'],
                'event' => $event['event'],
                'actor_id' => (int) $event['actor_id'],
                'created_at' => $event['created_at'],
                'before' => $event['before_json'] ? json_decode($event['before_json'], true) : null,
                'after' => $event['after_json'] ? json_decode($event['after_json'], true) : null,
            ];
        }
        $form = stti_form_from_row($row);
        $validation = stti_validate_form($form);
        $candidates[] = [
            'record' => [
                'stable_id' => $row['stable_id'],
                'schema_version' => $row['schema_version'],
                'public_title' => $row['public_title'],
                'slug' => $row['slug'],
                'editorial' => $row['editorial'],
                'schedule_status' => $row['schedule_status'],
                'availability' => $row['availability'],
                'temporal' => $row['temporal'],
                'source_completeness' => $row['source_completeness'],
                'checksum_sha256' => $row['checksum'],
                'created_by' => (int) $row['created_by'],
                'created_at' => $row['created_at'],
                'updated_at' => $row['updated_at'],
            ],
            'canonical_payload' => $payload,
            'structured_counts' => [
                'route_stops' => count(is_array($payload['route']['stops'] ?? null) ? $payload['route']['stops'] : []),
                'itinerary_days' => count(is_array($payload['itinerary'] ?? null) ? $payload['itinerary'] : []),
                'hotel_relations' => count(is_array($payload['stays']['hotels'] ?? null) ? $payload['stays']['hotels'] : []),
                'transport_segments' => count(is_array($payload['transport']['segments'] ?? null) ? $payload['transport']['segments'] : []),
                'pricing_items' => count(is_array($payload['pricing']['items'] ?? null) ? $payload['pricing']['items'] : []),
                'included_services' => count(is_array($payload['services']['included'] ?? null) ? $payload['services']['included'] : []),
                'excluded_services' => count(is_array($payload['services']['excluded'] ?? null) ? $payload['services']['excluded'] : []),
                'requirements' => count(is_array($payload['requirements']['items'] ?? null) ? $payload['requirements']['items'] : []),
            ],
            'validation' => $validation,
            'private_renderer_evidence' => stti_private_renderer_evidence($row, $payload),
            'customer_preview_evidence' => stti_customer_preview_evidence($row, $payload),
            'audit_log' => $audit,
        ];
    }

    return [
        'export_contract' => 'STTI-EVIDENCE-1.0.0',
        'generated_at' => current_time('c'),
        'site' => [
            'url' => home_url('/'),
            'wordpress_version' => $wp_version,
            'php_version' => PHP_VERSION,
        ],
        'stti' => [
            'plugin_version' => STTI_VERSION,
            'tour_schema_version' => STTI_SCHEMA_VERSION,
            'mode' => 'private_json_import_validate_dry_run_pilot',
            'public_master' => false,
            'frontend_writes' => 0,
            'json_import' => [
                'contract' => 'STTI-TOUR-IMPORT-1.0.0',
                'status' => 'validate_dry_run_active',
                'input' => 'paste_or_json_upload',
                'actions' => ['validate','normalize','dry_run'],
                'classifications' => ['CREATE','UPDATE','UNCHANGED','CONFLICT','INVALID'],
                'canonical_write' => false,
                'audit_write' => false,
                'sequence_write' => false,
                'publication_request_allowed' => false,
            ],
            'next_stable_id_sequence' => (int) get_option('stti_next_sequence', 1),
            'release_locks' => [
                'public_renderer' => false,
                'public_routes' => false,
                'sitemap' => false,
                'indexation' => false,
                'homepage_adapter' => false,
                'schema_output' => false,
                'canonical_robots_changes' => false,
            ],
            'private_renderer' => [
                'contract' => 'STTI-PRIVATE-RENDERER-0.1.0',
                'status' => 'runtime_accepted',
                'access' => 'wp-admin/manage_options',
                'source' => 'canonical_payload_direct_read',
                'data_duplication' => false,
                'public_route' => false,
                'indexable' => false,
                'sitemap' => false,
                'schema_output' => false,
            ],
            'customer_experience' => [
                'contract' => 'STTI-CUSTOMER-PREVIEW-0.3.4',
                'status' => 'pilot_active',
                'access' => 'front_end_private/manage_options+nonce',
                'surface' => 'wordpress_theme_shell_admin_only',
                'header' => 'live_theme_get_header',
                'footer' => 'live_theme_get_footer',
                'source' => 'canonical_payload_direct_read',
                'data_duplication' => false,
                'writes' => 0,
                'public_route' => false,
                'indexable' => false,
                'sitemap' => false,
                'schema_output_by_stti' => false,
                'canonical_changes_by_stti' => false,
                'cta_status' => 'working_links',
                'layout_mode' => 'full_bleed_viewport',
                'header_overlap_guard' => 'actual_live_header_selector_dom_measurement',
                'header_glass_compatibility' => true,
                'summary_bar' => 'large_color_coded_cards',
                'header_seam_strategy' => 'hero_background_bleed_to_live_header_top_dynamic',
                'route_map_engine' => 'leaflet_global_route',
                'route_map_tiles' => 'openstreetmap',
                'route_geocoder' => 'nominatim_client_pilot',
                'route_coordinate_cache' => 'browser_localstorage_pilot',
                'route_unresolved_policy' => 'never_invent_admin_warning',
                'route_auto_fit_bounds' => true,
                'route_animation' => 'segment_by_segment_restored_v054',
                'route_animation_trigger' => 'viewport_after_geocode',
                'route_marker_reveal' => 'progressive_restored_v054',
                'route_timeline_sync' => true,
                'route_visual_skin' => 'v0.5.4_restored',
                'hero_artwork_lift_px' => 'dom_measured_dynamic',
                'route_reduced_motion' => 'instant_final_state',
            ],
        ],
        'summary' => [
            'candidate_count' => count($candidates),
            'fixture_count' => count(stti_fixture_tours()),
            'needs_review_count' => count(array_filter($candidates, static function ($item) {
                return (($item['record']['editorial'] ?? '') === 'needs_review');
            })),
        ],
        'preview_fixtures' => stti_fixture_tours(),
        'private_candidates' => $candidates,
    ];
}

function stti_handle_export_all_json() {
    if (!current_user_can('manage_options')) { wp_die('Unauthorized'); }
    check_admin_referer('stti_export_all_json');
    $payload = stti_collect_full_export();
    $filename = 'server-turizm-tour-intelligence-full-evidence-' . current_time('Ymd-His') . '.json';
    nocache_headers();
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . sanitize_file_name($filename) . '"');
    header('X-Content-Type-Options: nosniff');
    echo wp_json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function stti_allocate_stable_id() {
    $seq = (int) get_option('stti_next_sequence', 1);
    for ($i=0; $i<100000; $i++) {
        $candidate = 'STT-' . str_pad((string)$seq, 6, '0', STR_PAD_LEFT);
        if (!stti_get_candidate($candidate)) {
            update_option('stti_next_sequence', $seq + 1, false);
            return $candidate;
        }
        $seq++;
    }
    return new WP_Error('stti_id_exhausted', 'Stable ID allocation failed.');
}

function stti_current_view() {
    $allowed=['dashboard','tours','editor','preview','customer-preview','review','archive','import','settings'];
    $view=isset($_GET['view'])?sanitize_key(wp_unslash($_GET['view'])):'dashboard';
    return in_array($view,$allowed,true)?$view:'dashboard';
}

function stti_view_url($view, $extra = []) {
    return esc_url(add_query_arg(array_merge(['page'=>'stti-tour-intelligence','view'=>$view],$extra), admin_url('admin.php')));
}

function stti_badge($text,$tone='neutral') { return '<span class="stti-badge stti-badge-'.esc_attr($tone).'">'.esc_html($text).'</span>'; }


function stti_default_form() {
    return [
        'stable_id'=>'','tour_code'=>'','public_title'=>'','short_title'=>'','slug'=>'','language'=>'tr-TR',
        'primary_country'=>'','countries'=>'','primary_city'=>'','cities'=>'','departure_city'=>'','return_city'=>'',
        'date_mode'=>'undated','date_precision'=>'unknown','start_date'=>'','end_date'=>'','month'=>'','duration_days'=>'','duration_nights'=>'',
        'route'=>'','route_stops_json'=>'[]','itinerary_json'=>'[]','hotel_relations_json'=>'[]','transport_segments_json'=>'[]',
        'price_type'=>'on_request','price_amount'=>'','currency'=>'EUR','price_basis'=>'unknown','pricing_items_json'=>'[]',
        'included_services_json'=>'[]','excluded_services_json'=>'[]',
        'visa_status'=>'unknown','visa_notes'=>'','requirements_items_json'=>'[]',
        'short_description'=>'','source_type'=>'manual','source_ref'=>'','origin_fixture_id'=>'','source_completeness'=>'source_minimal',
        'editorial'=>'needs_review','schedule_status'=>'tentative','availability'=>'on_request','temporal'=>'undated',
    ];
}


function stti_clean_json_value($value) {
    if (is_array($value)) {
        $clean = [];
        foreach ($value as $k=>$v) {
            $clean[sanitize_key((string)$k)] = stti_clean_json_value($v);
        }
        return $clean;
    }
    if (is_bool($value) || is_int($value) || is_float($value) || $value === null) return $value;
    return sanitize_textarea_field((string)$value);
}

function stti_sanitize_json_array_string($value) {
    $decoded = json_decode(wp_unslash((string)$value), true);
    if (!is_array($decoded)) return '[]';
    $clean = [];
    foreach ($decoded as $item) {
        if (!is_array($item)) continue;
        $clean[] = stti_clean_json_value($item);
    }
    return wp_json_encode($clean, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
}

function stti_decode_array_field($json) {
    $decoded = json_decode((string)$json, true);
    return is_array($decoded) ? array_values($decoded) : [];
}

function stti_route_summary_from_stops($stops) {
    $labels = [];
    foreach ($stops as $stop) {
        if (!is_array($stop)) continue;
        $label = trim((string)($stop['city'] ?? ''));
        if ($label === '') $label = trim((string)($stop['label'] ?? ''));
        if ($label !== '') $labels[] = $label;
    }
    return implode(' → ', $labels);
}

function stti_bootstrap_route_stops_from_summary($summary) {
    $summary = trim((string)$summary);
    if ($summary === '') return [];
    $parts = preg_split('/\s*(?:→|›|>|·)\s*/u', $summary);
    $out = [];
    $i = 1;
    foreach ($parts as $part) {
        $part = trim($part);
        if ($part === '') continue;
        $out[] = ['stop_id'=>'R'.$i,'type'=>'stop','country'=>'','city'=>$part,'label'=>'','note'=>''];
        $i++;
    }
    return $out;
}

function stti_normalize_structured_dates(&$f) {
    $hotels = stti_decode_array_field($f['hotel_relations_json'] ?? '[]');
    foreach ($hotels as &$hotel) {
        if (!is_array($hotel)) continue;
        foreach (['check_in','check_out'] as $key) {
            if (!empty($hotel[$key])) $hotel[$key] = stti_normalize_date_input($hotel[$key]);
        }
        $hotel['nights'] = null;
        if (!empty($hotel['check_in']) && !empty($hotel['check_out']) &&
            preg_match('/^\d{4}-\d{2}-\d{2}$/',$hotel['check_in']) &&
            preg_match('/^\d{4}-\d{2}-\d{2}$/',$hotel['check_out'])) {
            $a = new DateTimeImmutable($hotel['check_in']);
            $b = new DateTimeImmutable($hotel['check_out']);
            if ($b >= $a) $hotel['nights'] = (int)$a->diff($b)->days;
        }
    }
    unset($hotel);
    $f['hotel_relations_json'] = wp_json_encode($hotels,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
}

function stti_sync_itinerary(&$f) {
    $items = stti_decode_array_field($f['itinerary_json'] ?? '[]');
    $exact = ($f['date_mode'] === 'exact' && preg_match('/^\d{4}-\d{2}-\d{2}$/',$f['start_date']) && $f['duration_days'] !== '');
    $duration = $exact ? max(0,(int)$f['duration_days']) : 0;
    if ($exact) {
        $start = new DateTimeImmutable($f['start_date']);
        for ($i=0; $i<count($items); $i++) {
            if (!is_array($items[$i])) $items[$i] = [];
            $day = isset($items[$i]['day_number']) ? max(1,(int)$items[$i]['day_number']) : ($i+1);
            $items[$i]['day_number'] = $day;
            $items[$i]['date'] = $start->modify('+'.($day-1).' days')->format('Y-m-d');
        }
        if (count($items) < $duration) {
            for ($day=count($items)+1; $day<=$duration; $day++) {
                $items[] = [
                    'day_number'=>$day,
                    'date'=>$start->modify('+'.($day-1).' days')->format('Y-m-d'),
                    'title'=>'','city'=>'','summary'=>'','activities'=>'','meals'=>'',
                    'transport_ref'=>'','hotel_ref'=>'','media_refs'=>''
                ];
            }
        }
    }
    $f['itinerary_json'] = wp_json_encode(array_values($items),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
}


function stti_form_from_row($row) {
    $f = stti_default_form();
    if (!$row) return $f;
    $payload = json_decode($row['payload'], true); if (!is_array($payload)) $payload=[];
    $f['stable_id']=$row['stable_id'];
    $f['public_title']=$row['public_title'];
    $f['slug']=$row['slug'];
    $f['editorial']=$row['editorial'];
    $f['schedule_status']=$row['schedule_status'];
    $f['availability']=$row['availability'];
    $f['temporal']=$row['temporal'];
    $f['source_completeness']=$row['source_completeness'];
    $map = [
        'tour_code'=>['identity','tour_code'],'short_title'=>['identity','short_title'],'language'=>['identity','language'],
        'primary_country'=>['destinations','primary_country'],'countries'=>['destinations','countries_csv'],'primary_city'=>['destinations','primary_city'],'cities'=>['destinations','cities_csv'],'departure_city'=>['destinations','departure_city'],'return_city'=>['destinations','return_city'],
        'date_mode'=>['date','mode'],'date_precision'=>['date','precision'],'start_date'=>['date','start_date'],'end_date'=>['date','end_date'],'month'=>['date','month'],'duration_days'=>['date','duration_days'],'duration_nights'=>['date','duration_nights'],
        'route'=>['route','summary'],'price_type'=>['pricing','type'],'price_amount'=>['pricing','amount'],'currency'=>['pricing','currency'],'price_basis'=>['pricing','basis'],
        'short_description'=>['content','short_description'],'source_type'=>['provenance','source_type'],'source_ref'=>['provenance','source_ref'],'origin_fixture_id'=>['provenance','origin_fixture_id'],
        'visa_status'=>['requirements','visa_status'],'visa_notes'=>['requirements','visa_notes'],
    ];
    foreach ($map as $key=>$path) {
        if (isset($payload[$path[0]][$path[1]]) || array_key_exists($path[1],$payload[$path[0]] ?? [])) {
            $v = $payload[$path[0]][$path[1]];
            $f[$key] = $v === null ? '' : (string)$v;
        }
    }
    $route_stops = $payload['route']['stops'] ?? [];
    if (!is_array($route_stops) || !$route_stops) $route_stops = stti_bootstrap_route_stops_from_summary($f['route']);
    $f['route_stops_json'] = wp_json_encode(array_values($route_stops),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    $f['itinerary_json'] = wp_json_encode(array_values(is_array($payload['itinerary'] ?? null)?$payload['itinerary']:[]),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    $f['hotel_relations_json'] = wp_json_encode(array_values(is_array($payload['stays']['hotels'] ?? null)?$payload['stays']['hotels']:[]),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    $f['transport_segments_json'] = wp_json_encode(array_values(is_array($payload['transport']['segments'] ?? null)?$payload['transport']['segments']:[]),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    $f['pricing_items_json'] = wp_json_encode(array_values(is_array($payload['pricing']['items'] ?? null)?$payload['pricing']['items']:[]),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    $f['included_services_json'] = wp_json_encode(array_values(is_array($payload['services']['included'] ?? null)?$payload['services']['included']:[]),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    $f['excluded_services_json'] = wp_json_encode(array_values(is_array($payload['services']['excluded'] ?? null)?$payload['services']['excluded']:[]),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    $f['requirements_items_json'] = wp_json_encode(array_values(is_array($payload['requirements']['items'] ?? null)?$payload['requirements']['items']:[]),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    return $f;
}


function stti_normalize_date_input($value) {
    $value = trim((string)$value);
    if ($value === '') return '';
    $formats = ['Y-m-d','d/m/Y','d.m.Y','d-m-Y'];
    foreach ($formats as $format) {
        $dt = DateTime::createFromFormat('!'.$format, $value);
        $errors = DateTime::getLastErrors();
        if ($dt && ($errors === false || ($errors['warning_count']===0 && $errors['error_count']===0))) {
            return $dt->format('Y-m-d');
        }
    }
    return $value;
}

function stti_display_date($value) {
    $value = trim((string)$value);
    if ($value === '') return '';
    $dt = DateTime::createFromFormat('!Y-m-d', $value);
    return $dt ? $dt->format('d/m/Y') : $value;
}

function stti_derive_date_facts(&$f) {
    $f['start_date'] = stti_normalize_date_input($f['start_date']);
    $f['end_date']   = stti_normalize_date_input($f['end_date']);
    if ($f['date_mode'] === 'exact' && preg_match('/^\d{4}-\d{2}-\d{2}$/',$f['start_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/',$f['end_date'])) {
        $start = new DateTimeImmutable($f['start_date']);
        $end   = new DateTimeImmutable($f['end_date']);
        if ($end >= $start) {
            $nights = (int)$start->diff($end)->days;
            $f['duration_nights'] = (string)$nights;
            $f['duration_days']   = (string)($nights + 1);
            $today = new DateTimeImmutable(current_time('Y-m-d'));
            if ($today < $start) $f['temporal'] = 'upcoming';
            elseif ($today > $end) $f['temporal'] = 'completed';
            else $f['temporal'] = 'in_progress';
            return;
        }
    }
    $f['duration_days']='';
    $f['duration_nights']='';
    if ($f['date_mode'] !== 'exact') $f['temporal']='undated';
}

function stti_hybrid_date_field($label,$name,$value) {
    $display = stti_display_date($value);
    echo '<label class="stti-field stti-date-field"><span>'.esc_html($label).'</span><div class="stti-date-control">';
    echo '<input type="text" name="'.esc_attr($name).'" value="'.esc_attr($display).'" placeholder="dd/mm/yyyy" inputmode="numeric" autocomplete="off" data-stti-date-text="'.esc_attr($name).'">';
    echo '<button type="button" class="stti-calendar-btn" aria-label="'.esc_attr($label).' takvimini aç" data-stti-calendar-for="'.esc_attr($name).'"><span class="dashicons dashicons-calendar-alt"></span></button>';
    echo '<input type="date" class="stti-native-picker" value="'.esc_attr($value).'" tabindex="-1" aria-hidden="true" data-stti-date-picker="'.esc_attr($name).'">';
    echo '</div><small class="stti-field-help">Yazın veya takvimden seçin · çift tıklama takvimi açar.</small></label>';
}


function stti_sanitize_form($raw) {
    $out = stti_default_form();
    $json_keys = ['route_stops_json','itinerary_json','hotel_relations_json','transport_segments_json','pricing_items_json','included_services_json','excluded_services_json','requirements_items_json'];
    foreach ($out as $key=>$default) {
        if (!array_key_exists($key,$raw)) continue;
        $val = wp_unslash($raw[$key]);
        if (in_array($key,$json_keys,true)) $out[$key]=stti_sanitize_json_array_string($raw[$key]);
        elseif (in_array($key,['short_description','route','visa_notes'],true)) $out[$key]=sanitize_textarea_field($val);
        elseif (in_array($key,['price_amount'],true)) $out[$key]=preg_replace('/[^0-9.,]/','',(string)$val);
        elseif (in_array($key,['duration_days','duration_nights'],true)) $out[$key]=(string)absint($val);
        else $out[$key]=sanitize_text_field($val);
    }
    if (isset($raw['stable_id'])) $out['stable_id']=sanitize_text_field(wp_unslash($raw['stable_id']));
    stti_derive_date_facts($out);
    stti_normalize_structured_dates($out);
    stti_sync_itinerary($out);
    $stops = stti_decode_array_field($out['route_stops_json']);
    if ($stops) $out['route'] = stti_route_summary_from_stops($stops);
    return $out;
}



function stti_validate_form($f) {
    $errors=[]; $warnings=[];
    if ($f['public_title']==='') $errors[]='Public Başlık zorunludur.';
    if ($f['language']==='') $errors[]='Dil zorunludur.';
    if ($f['date_mode']==='exact' && ($f['start_date']==='' || $f['end_date']==='')) $errors[]='Exact tarih modunda başlangıç ve bitiş tarihi zorunludur.';
    if ($f['date_mode']==='exact' && ((!preg_match('/^\d{4}-\d{2}-\d{2}$/',$f['start_date'])) || (!preg_match('/^\d{4}-\d{2}-\d{2}$/',$f['end_date'])))) $errors[]='Tarih formatı geçersiz. gg/aa/yyyy kullanın veya takvimden seçin.';
    if ($f['start_date'] && $f['end_date'] && $f['end_date'] < $f['start_date']) $errors[]='Bitiş tarihi başlangıç tarihinden önce olamaz.';
    if (in_array($f['price_type'],['exact','from'],true) && $f['price_amount']==='') $errors[]='Exact / From fiyat tipinde tutar zorunludur.';
    if (in_array($f['price_type'],['exact','from'],true) && $f['price_basis']==='unknown') $warnings[]='Fiyat basis kaynakta belirtilmemiş; UNKNOWN olarak korunuyor.';
    if ($f['primary_country']==='') $warnings[]='Primary country eksik.';
    if ($f['route']==='') $warnings[]='Rota özeti eksik.';
    if ($f['source_ref']==='') $warnings[]='Source reference eksik.';
    if ($f['source_completeness']!=='source_complete') $warnings[]='Kaynak tam değil; human review gerekir.';

    $stops = stti_decode_array_field($f['route_stops_json']);
    foreach ($stops as $i=>$stop) {
        if (trim((string)($stop['city'] ?? ''))==='' && trim((string)($stop['label'] ?? ''))==='') $warnings[]='Rota stop #'.($i+1).' için city/label eksik.';
    }
    $itinerary = stti_decode_array_field($f['itinerary_json']);
    if ($f['duration_days']!=='' && $itinerary && count($itinerary)!==(int)$f['duration_days']) {
        $warnings[]='Gün gün program sayısı ('.count($itinerary).') süre ile ('.(int)$f['duration_days'].') eşleşmiyor.';
    }
    $hotels = stti_decode_array_field($f['hotel_relations_json']);
    foreach ($hotels as $i=>$hotel) {
        $mode = (string)($hotel['mode'] ?? 'unresolved');
        if ($mode==='hotel_intelligence' && trim((string)($hotel['hotel_stable_id'] ?? ''))==='') $warnings[]='Otel relation #'.($i+1).' için Hotel Intelligence Stable ID eksik.';
        if ($mode==='unresolved' && trim((string)($hotel['unresolved_name'] ?? ''))==='') $warnings[]='Unresolved otel relation #'.($i+1).' için geçici otel adı eksik.';
        if (!empty($hotel['check_in']) && !empty($hotel['check_out']) && $hotel['check_out'] < $hotel['check_in']) $errors[]='Otel relation #'.($i+1).' check-out, check-in tarihinden önce olamaz.';
    }
    $prices = stti_decode_array_field($f['pricing_items_json']);
    foreach ($prices as $i=>$price) {
        $type = (string)($price['type'] ?? 'on_request');
        $amount = trim((string)($price['amount'] ?? ''));
        if (in_array($type,['exact','from'],true) && $amount==='') $errors[]='Fiyat satırı #'.($i+1).' için tutar zorunludur.';
    }
    return ['errors'=>$errors,'warnings'=>array_values(array_unique($warnings))];
}



function stti_build_payload($f, $stable_id) {
    $countries = array_values(array_filter(array_map('trim', explode(',', $f['countries']))));
    $cities = array_values(array_filter(array_map('trim', explode(',', $f['cities']))));
    $amount = $f['price_amount'] === '' ? null : (float)str_replace(',','.',$f['price_amount']);
    return [
        'schema'=>'STTI-TOUR-'.STTI_SCHEMA_VERSION,
        'stable_id'=>$stable_id,
        'identity'=>['tour_code'=>$f['tour_code'],'public_title'=>$f['public_title'],'short_title'=>$f['short_title'],'slug'=>$f['slug'],'language'=>$f['language']],
        'destinations'=>['primary_country'=>$f['primary_country'],'countries'=>$countries,'countries_csv'=>$f['countries'],'primary_city'=>$f['primary_city'],'cities'=>$cities,'cities_csv'=>$f['cities'],'departure_city'=>$f['departure_city'],'return_city'=>$f['return_city']],
        'date'=>['mode'=>$f['date_mode'],'precision'=>$f['date_precision'],'start_date'=>$f['start_date']?:null,'end_date'=>$f['end_date']?:null,'month'=>$f['month']?:null,'duration_days'=>$f['duration_days']===''?null:(int)$f['duration_days'],'duration_nights'=>$f['duration_nights']===''?null:(int)$f['duration_nights']],
        'route'=>['summary'=>$f['route'],'stops'=>stti_decode_array_field($f['route_stops_json'])],
        'itinerary'=>stti_decode_array_field($f['itinerary_json']),
        'stays'=>['hotels'=>stti_decode_array_field($f['hotel_relations_json'])],
        'transport'=>['segments'=>stti_decode_array_field($f['transport_segments_json'])],
        'pricing'=>['type'=>$f['price_type'],'amount'=>$amount,'currency'=>$f['currency'],'basis'=>$f['price_basis'],'items'=>stti_decode_array_field($f['pricing_items_json'])],
        'services'=>['included'=>stti_decode_array_field($f['included_services_json']),'excluded'=>stti_decode_array_field($f['excluded_services_json'])],
        'requirements'=>['visa_status'=>$f['visa_status'],'visa_notes'=>$f['visa_notes'],'items'=>stti_decode_array_field($f['requirements_items_json'])],
        'media'=>['items'=>[]],
        'content'=>['short_description'=>$f['short_description']],
        'lifecycle'=>['editorial'=>$f['editorial'],'schedule'=>$f['schedule_status'],'availability'=>$f['availability'],'temporal'=>$f['temporal']],
        'publication'=>['renderer'=>'private','public_route'=>false,'hub_visible'=>false,'homepage_visible'=>false,'indexable'=>false,'sitemap'=>false],
        'provenance'=>['source_type'=>$f['source_type'],'source_ref'=>$f['source_ref'],'origin_fixture_id'=>$f['origin_fixture_id'],'source_completeness'=>$f['source_completeness']],
    ];
}


function stti_audit_event($stable_id,$event,$before,$after) {
    global $wpdb; $t=stti_tables();
    $wpdb->insert($t['audit'],[
        'stable_id'=>$stable_id,'event'=>$event,'actor_id'=>get_current_user_id(),
        'before_json'=>$before===null?null:wp_json_encode($before,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
        'after_json'=>$after===null?null:wp_json_encode($after,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
        'created_at'=>current_time('mysql'),
    ],['%s','%s','%d','%s','%s','%s']);
}

function stti_handle_save_candidate() {
    if (!current_user_can('manage_options')) wp_die('Unauthorized');
    check_admin_referer('stti_save_candidate');
    $f=stti_sanitize_form($_POST);
    $validation=stti_validate_form($f);
    if ($validation['errors']) {
        $url=add_query_arg(['page'=>'stti-tour-intelligence','view'=>'editor','stti_error'=>rawurlencode(implode(' | ',$validation['errors']))],admin_url('admin.php'));
        wp_safe_redirect($url); exit;
    }
    global $wpdb; $t=stti_tables();
    $is_new = $f['stable_id']==='';
    if ($is_new && !empty($f['origin_fixture_id'])) {
        $already = stti_find_candidate_by_origin_fixture($f['origin_fixture_id']);
        if ($already) {
            $url=add_query_arg(['page'=>'stti-tour-intelligence','view'=>'editor','tour'=>$already['stable_id'],'stti_saved'=>'fixture_exists'],admin_url('admin.php'));
            wp_safe_redirect($url); exit;
        }
    }
    $stable_id = $is_new ? stti_allocate_stable_id() : $f['stable_id'];
    if (is_wp_error($stable_id)) wp_die(esc_html($stable_id->get_error_message()));
    $existing = $is_new ? null : stti_get_candidate($stable_id);
    if (!$is_new && !$existing) wp_die('Unknown STT stable ID. Fail closed.');

    $payload=stti_build_payload($f,$stable_id);
    $json=wp_json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    $checksum=hash('sha256',$json);
    $now=current_time('mysql');
    if ($is_new) {
        $wpdb->insert($t['tours'],[
            'stable_id'=>$stable_id,'schema_version'=>STTI_SCHEMA_VERSION,'public_title'=>$f['public_title'],'slug'=>$f['slug'],
            'editorial'=>$f['editorial'],'schedule_status'=>$f['schedule_status'],'availability'=>$f['availability'],'temporal'=>$f['temporal'],
            'source_completeness'=>$f['source_completeness'],'payload'=>$json,'checksum'=>$checksum,'created_by'=>get_current_user_id(),'created_at'=>$now,'updated_at'=>$now,
        ],['%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%d','%s','%s']);
        if (!$wpdb->insert_id) wp_die('Private candidate insert failed.');
        stti_audit_event($stable_id,'candidate_created',null,$payload);
        $event='created';
    } else {
        $before=json_decode($existing['payload'],true);
        if (hash_equals((string)$existing['checksum'],$checksum)) {
            stti_audit_event($stable_id,'candidate_unchanged',$before,$payload);
            $event='unchanged';
        } else {
            $wpdb->update($t['tours'],[
                'schema_version'=>STTI_SCHEMA_VERSION,'public_title'=>$f['public_title'],'slug'=>$f['slug'],'editorial'=>$f['editorial'],'schedule_status'=>$f['schedule_status'],
                'availability'=>$f['availability'],'temporal'=>$f['temporal'],'source_completeness'=>$f['source_completeness'],'payload'=>$json,'checksum'=>$checksum,'updated_at'=>$now,
            ],['stable_id'=>$stable_id],['%s','%s','%s','%s','%s','%s','%s','%s','%s','%s'],['%s']);
            stti_audit_event($stable_id,'candidate_updated',$before,$payload);
            $event='updated';
        }
    }
    $url=add_query_arg(['page'=>'stti-tour-intelligence','view'=>'editor','tour'=>$stable_id,'stti_saved'=>$event],admin_url('admin.php'));
    wp_safe_redirect($url); exit;
}

function stti_render_admin() {
    if (!current_user_can('manage_options')) return;
    $view=stti_current_view(); $fixtures=stti_fixture_tours(); $candidates=stti_get_candidates();
    echo '<div class="wrap stti-wrap"><div class="stti-shell">';
    stti_render_sidebar($view);
    echo '<main class="stti-main">'; stti_render_topbar($view, count($candidates));
    switch($view){
        case 'tours': stti_render_tours($fixtures,$candidates); break;
        case 'editor': stti_render_editor(); break;
        case 'preview': stti_render_private_preview(); break;
        case 'review': stti_render_review($fixtures,$candidates); break;
        case 'archive': stti_render_archive($candidates); break;
        case 'import': stti_render_import(); break;
        case 'settings': stti_render_settings($candidates); break;
        default: stti_render_dashboard($fixtures,$candidates); break;
    }
    echo '</main></div></div>';
}

function stti_render_sidebar($view){
    $items=['dashboard'=>['dashicons-chart-area','Dashboard'],'tours'=>['dashicons-list-view','Tours'],'editor'=>['dashicons-edit-page','Create / Edit Tour'],'review'=>['dashicons-visibility','Review Queue'],'archive'=>['dashicons-archive','Archive'],'import'=>['dashicons-database-import','Import / Export'],'settings'=>['dashicons-admin-generic','Settings']];
    ?>
    <aside class="stti-sidebar">
      <div class="stti-brand"><div class="stti-brand-mark">ST</div><div><strong>Tour Intelligence</strong><span>Server Turizm</span></div></div>
      <div class="stti-mode-card"><span>STTI v<?php echo esc_html(STTI_VERSION); ?></span><strong>PRIVATE CUSTOMER EXPERIENCE</strong><small>Public Master: HARD OFF</small></div>
      <nav class="stti-nav" aria-label="Tour Intelligence admin navigation">
      <?php foreach($items as $key=>$item){$active=$view===$key?' is-active':''; echo '<a class="stti-nav-item'.esc_attr($active).'" href="'.stti_view_url($key).'"><span class="dashicons '.esc_attr($item[0]).'"></span><span>'.esc_html($item[1]).'</span></a>'; } ?>
      </nav>
      <div class="stti-locks"><strong>Release Locks</strong><span>Public routes <b>OFF</b></span><span>Sitemap <b>OFF</b></span><span>Indexation <b>OFF</b></span><span>Homepage adapter <b>OFF</b></span></div>
    </aside>
    <?php
}

function stti_view_title($view){$m=['dashboard'=>'Tour Operations Dashboard','tours'=>'Tour Registry','editor'=>'Tour Editor','preview'=>'Technical Tour Preview','customer-preview'=>'Customer Experience Preview','review'=>'Review Queue','archive'=>'Archive & Lifecycle','import'=>'Import / Export','settings'=>'System Settings'];return $m[$view]??'Tour Intelligence';}
function stti_view_description($view){$m=['dashboard'=>'Runtime-accepted technical renderer plus the Private Customer Experience Pilot. Public release remains hard locked.','tours'=>'Preview fixtures and real private STT candidates remain separated. Customer Preview and Technical Preview both read canonical records directly.','editor'=>'Create/update canonical private data. Both preview layers are read-only and do not duplicate Tour facts.','preview'=>'Accepted technical renderer. Admin-only direct canonical read with no public exposure.','customer-preview'=>'Legacy admin entry redirects to the private front-end theme-shell preview. The live site header/footer are used; access remains admin-only and noindex.','review'=>'Human review first. Incomplete sources remain valid without invented facts.','archive'=>'No delete path. Archive/restore remains locked for a later lifecycle gate.','import'=>'STTI-TOUR-IMPORT-1.0.0 JSON can be pasted or uploaded for server-side validation, normalization and dry run. Commit remains locked; this gate performs no canonical, audit or sequence writes.','settings'=>'Ownership, storage state, accepted technical renderer, customer experience pilot and hard public locks.'];return $m[$view]??'';}

function stti_render_topbar($view,$count){ ?>
<header class="stti-topbar"><div><span class="stti-kicker">STTI · PRIVATE CUSTOMER EXPERIENCE v<?php echo esc_html(STTI_VERSION); ?></span><h1><?php echo esc_html(stti_view_title($view)); ?></h1><p><?php echo esc_html(stti_view_description($view)); ?></p></div><div class="stti-top-actions"><span class="stti-state-dot"></span><span>Private candidates: <?php echo (int)$count; ?></span><button type="button" class="button button-primary" disabled>Public Aç</button></div></header>
<?php }

function stti_metric($value,$label,$hint,$tone){echo '<article class="stti-metric stti-metric-'.esc_attr($tone).'"><span>'.esc_html($label).'</span><strong>'.esc_html($value).'</strong><small>'.esc_html($hint).'</small></article>';}
function stti_gate($label,$state,$tone){echo '<div class="stti-gate"><span>'.esc_html($label).'</span><b class="stti-gate-'.esc_attr($tone).'">'.esc_html($state).'</b></div>';}

function stti_render_dashboard($fixtures,$candidates){
    $needs_review=count(array_filter($candidates,fn($r)=>$r['editorial']==='needs_review'));
    ?>
    <section class="stti-metrics">
      <?php stti_metric((string)count($candidates),'Private Candidates','Canonical DB records','navy'); ?>
      <?php stti_metric((string)$needs_review,'Needs Review','Human approval required','gold'); ?>
      <?php stti_metric('4','Preview Fixtures','T1.1 evidence only','orange'); ?>
      <?php stti_metric('0','Frontend Writes','Hard invariant','slate'); ?>
    </section>
    <section class="stti-grid stti-grid-2">
      <article class="stti-panel"><div class="stti-panel-head"><div><span class="stti-kicker">PRIVATE STORE</span><h2>Candidate registry</h2></div><a href="<?php echo stti_view_url('tours'); ?>">Registry →</a></div>
      <?php if(!$candidates): ?><p class="stti-muted">Store is empty. Create one controlled candidate in Tour Editor. Activation never seeds business data automatically.</p><?php else: ?><div class="stti-health-list"><?php foreach(array_slice($candidates,0,6) as $r): ?><div class="stti-health-row"><div><strong><?php echo esc_html($r['public_title']); ?></strong><span><?php echo esc_html($r['stable_id']); ?> · <?php echo esc_html(strtoupper($r['source_completeness'])); ?></span></div><div class="stti-health-meter"><i style="width:70%"></i></div><b>PRIVATE</b></div><?php endforeach; ?></div><?php endif; ?>
      </article>
      <article class="stti-panel"><div class="stti-panel-head"><div><span class="stti-kicker">RELEASE CONTROL</span><h2>Current branch locks</h2></div></div><div class="stti-gate-list">
      <?php stti_gate('Canonical Tour Model','DEFINED','pass'); stti_gate('Fixture Validation','PASS','pass'); stti_gate('Private Store Core','RUNTIME ACCEPTED','pass'); stti_gate('Structured Authoring','RUNTIME ACCEPTED','pass'); stti_gate('Private Renderer','RUNTIME ACCEPTED','pass'); stti_gate('Customer Experience','PILOT ACTIVE','active'); stti_gate('Public Renderer','LOCKED','off'); stti_gate('Sitemap / Indexation','LOCKED','off'); ?>
      </div></article>
    </section>
    <section class="stti-panel stti-flow-panel"><div class="stti-panel-head"><div><span class="stti-kicker">CURRENT CONTROLLED GATE</span><h2>Customer-facing private experience validation</h2></div></div><div class="stti-flow"><?php foreach(['Canonical direct read','Standalone customer UI','No invented facts','Responsive layout','No-write refresh','Access isolation'] as $i=>$step): ?><div class="stti-flow-step <?php echo $i===0?'is-ready':''; ?>"><span><?php echo esc_html(str_pad((string)($i+1),2,'0',STR_PAD_LEFT)); ?></span><strong><?php echo esc_html($step); ?></strong></div><?php if($i<5): ?><i>→</i><?php endif; ?><?php endforeach; ?></div></section>
    <?php
}

function stti_render_tours($fixtures,$candidates){ ?>
<section class="stti-toolbar"><div class="stti-search"><span class="dashicons dashicons-search"></span><input type="search" placeholder="Tour, STT ID, ülke, şehir ara…" disabled></div><div class="stti-toolbar-actions"><a class="button button-primary" href="<?php echo stti_view_url('editor'); ?>">+ Yeni Private Candidate</a></div></section>
<?php if($candidates): ?><section class="stti-panel stti-table-panel"><div class="stti-panel-head" style="padding:16px"><div><span class="stti-kicker">CANONICAL PRIVATE STORE</span><h2><?php echo count($candidates); ?> candidate</h2></div></div><div class="stti-table-wrap"><table class="stti-table"><thead><tr><th>Stable ID / Tour</th><th>Lifecycle</th><th>Source</th><th>Checksum</th><th>Updated</th><th></th></tr></thead><tbody><?php foreach($candidates as $r): ?><tr><td><span class="stti-id"><?php echo esc_html($r['stable_id']); ?></span><strong><?php echo esc_html($r['public_title']); ?></strong><small><?php echo esc_html($r['slug']); ?></small></td><td><?php echo stti_badge(strtoupper($r['editorial']),'gold').stti_badge(strtoupper($r['schedule_status']),'neutral').stti_badge(strtoupper($r['availability']),'blue'); ?></td><td><strong><?php echo esc_html(strtoupper($r['source_completeness'])); ?></strong></td><td><small><?php echo esc_html(substr($r['checksum'],0,12)); ?>…</small></td><td><small><?php echo esc_html($r['updated_at']); ?></small></td><td><div class="stti-row-action-stack"><a class="button button-small button-primary" href="<?php echo esc_url(stti_customer_preview_url($r['stable_id'])); ?>">Customer Preview</a><a class="button button-small" href="<?php echo esc_url(stti_private_preview_url($r['stable_id'])); ?>">Technical Preview</a><a class="button button-small" href="<?php echo stti_view_url('editor',['tour'=>$r['stable_id']]); ?>">Düzenle</a></div></td></tr><?php endforeach; ?></tbody></table></div></section><?php endif; ?>
<section class="stti-panel stti-table-panel" style="margin-top:16px"><div class="stti-panel-head" style="padding:16px"><div><span class="stti-kicker">T1.1 EVIDENCE FIXTURES</span><h2>Read-only evidence · editable candidate oluştur</h2><p class="stti-muted">Fixture değişmez. “Adaya Dönüştür” doğrulanmış alanları yeni Private Candidate formuna taşır; Stable ID sadece ilk kayıtta atanır.</p></div></div><div class="stti-table-wrap"><table class="stti-table"><thead><tr><th>Fixture / Tour</th><th>Destination / Route</th><th>Date</th><th>Price</th><th>Source</th><th>Health</th><th></th></tr></thead><tbody><?php foreach($fixtures as $tour): $existing_fixture_candidate=stti_find_candidate_by_origin_fixture($tour['stable_id']); ?><tr><td><span class="stti-id"><?php echo esc_html($tour['stable_id']); ?></span><strong><?php echo esc_html($tour['title']); ?></strong><small><?php echo esc_html($tour['completeness']); ?></small></td><td><strong><?php echo esc_html($tour['destination']); ?></strong><small><?php echo esc_html($tour['route']); ?></small></td><td><strong><?php echo esc_html($tour['date']); ?></strong><small><?php echo esc_html($tour['duration']); ?></small></td><td><strong><?php echo esc_html($tour['price']); ?></strong></td><td><?php echo esc_html($tour['source']); ?></td><td><div class="stti-score stti-score-<?php echo esc_attr($tour['class']); ?>"><b><?php echo (int)$tour['health']; ?></b><span>/100</span></div></td><td><?php if($existing_fixture_candidate): ?><a class="button button-small" href="<?php echo stti_view_url('editor',['tour'=>$existing_fixture_candidate['stable_id']]); ?>">Adayı Düzenle</a><?php else: ?><a class="button button-small" href="<?php echo stti_view_url('editor',['fixture'=>$tour['stable_id']]); ?>">Adaya Dönüştür</a><?php endif; ?></td></tr><?php endforeach; ?></tbody></table></div></section>
<?php }

function stti_field($label,$name,$value,$type='text',$readonly=false){echo '<label class="stti-field"><span>'.esc_html($label).'</span><input type="'.esc_attr($type).'" name="'.esc_attr($name).'" value="'.esc_attr($value).'" '.($readonly?'readonly':'').'></label>';}
function stti_textarea($label,$name,$value){echo '<label class="stti-field stti-field-full"><span>'.esc_html($label).'</span><textarea name="'.esc_attr($name).'" rows="5">'.esc_textarea($value).'</textarea></label>';}
function stti_select($label,$name,$value,$options){echo '<label class="stti-field"><span>'.esc_html($label).'</span><select name="'.esc_attr($name).'">';foreach($options as $k=>$v)echo '<option value="'.esc_attr($k).'" '.selected($value,$k,false).'>'.esc_html($v).'</option>';echo '</select></label>';}

function stti_editor_pane_title($num,$title,$desc){echo '<div class="stti-section-title"><span class="stti-kicker">'.esc_html(str_pad((string)$num,2,'0',STR_PAD_LEFT)).' · '.esc_html(mb_strtoupper($title)).'</span><h3>'.esc_html($title).'</h3><p>'.esc_html($desc).'</p></div>';}

function stti_hidden_json_field($name,$value) {
    echo '<input type="hidden" name="'.esc_attr($name).'" id="'.esc_attr($name).'" value="'.esc_attr($value).'" data-stti-json-field="'.esc_attr($name).'">';
}
function stti_builder_header($title,$hint,$button_id,$button_label) {
    echo '<div class="stti-builder-head"><div><strong>'.esc_html($title).'</strong><span>'.esc_html($hint).'</span></div><button type="button" class="button" id="'.esc_attr($button_id).'">+ '.esc_html($button_label).'</button></div>';
}


function stti_render_editor(){
    $stable=isset($_GET['tour'])?sanitize_text_field(wp_unslash($_GET['tour'])):'';
    $fixture_id=isset($_GET['fixture'])?sanitize_text_field(wp_unslash($_GET['fixture'])):'';
    $row=$stable?stti_get_candidate($stable):null;
    if (!$row && $fixture_id && stti_get_fixture($fixture_id)) { $f=stti_fixture_to_form($fixture_id); } else { $f=stti_form_from_row($row); }
    if ($f['route_stops_json']==='[]' && $f['route']!=='') $f['route_stops_json']=wp_json_encode(stti_bootstrap_route_stops_from_summary($f['route']),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    $audit=$stable?stti_get_audit($stable):[];
    $tabs=['Kimlik','Destinasyonlar','Tarihler','Rota','Gün Gün Program','Oteller','Ulaşım','Fiyatlar','Dahil / Hariç','Vize','Medya','İçerik','SEO','Social / Brochure','Preview / Publish','Revision / Audit'];
    if(isset($_GET['stti_saved'])) echo '<div class="notice notice-success stti-own-notice"><p>Private candidate '.esc_html(sanitize_text_field(wp_unslash($_GET['stti_saved']))).'. Public output remains OFF.</p></div>';
    if(isset($_GET['stti_error'])) echo '<div class="notice notice-error stti-own-notice"><p>'.esc_html(rawurldecode(sanitize_text_field(wp_unslash($_GET['stti_error'])))).'</p></div>';
    ?>
    <section class="stti-editor-head"><div><span class="stti-id"><?php echo esc_html($f['stable_id']?:($f['origin_fixture_id']?'FIXTURE → PRIVATE CANDIDATE':'NEW PRIVATE CANDIDATE')); ?></span><h2><?php echo esc_html($f['public_title']?:'Yeni Kültür Turu'); ?></h2><p>Structured authoring accepted · Private Renderer admin-only/read-only · public route / sitemap / indexation hard OFF</p></div><div class="stti-editor-status"><?php echo stti_badge('PRIVATE RENDERER v0.4','blue').stti_badge(strtoupper($f['editorial']),'gold').stti_badge('PUBLIC OFF','red'); ?></div></section>
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" id="stti-editor-form">
    <input type="hidden" name="action" value="stti_save_candidate"><input type="hidden" name="stable_id" value="<?php echo esc_attr($f['stable_id']); ?>"><input type="hidden" name="origin_fixture_id" value="<?php echo esc_attr($f['origin_fixture_id']); ?>"><?php wp_nonce_field('stti_save_candidate'); ?>
    <?php stti_hidden_json_field('route_stops_json',$f['route_stops_json']); stti_hidden_json_field('itinerary_json',$f['itinerary_json']); stti_hidden_json_field('hotel_relations_json',$f['hotel_relations_json']); stti_hidden_json_field('transport_segments_json',$f['transport_segments_json']); stti_hidden_json_field('pricing_items_json',$f['pricing_items_json']); stti_hidden_json_field('included_services_json',$f['included_services_json']); stti_hidden_json_field('excluded_services_json',$f['excluded_services_json']); stti_hidden_json_field('requirements_items_json',$f['requirements_items_json']); ?>
    <div class="stti-editor-layout"><aside class="stti-editor-tabs"><?php foreach($tabs as $i=>$tab): ?><button type="button" class="stti-editor-tab <?php echo $i===0?'is-active':''; ?>" data-tab="<?php echo (int)$i; ?>"><span><?php echo esc_html(str_pad((string)($i+1),2,'0',STR_PAD_LEFT)); ?></span><?php echo esc_html($tab); ?></button><?php endforeach; ?></aside>
    <section class="stti-editor-canvas">
      <div class="stti-editor-pane is-active" data-pane="0"><?php stti_editor_pane_title(1,'Kimlik','Stable ID server tarafından atanır ve asla yeniden kullanılmaz.'); ?><div class="stti-form-grid"><?php stti_field('Stable Tour ID','stable_id_display',$f['stable_id']?:'Save sonrası atanır','text',true); stti_field('Tour Kodu','tour_code',$f['tour_code']); stti_field('Public Başlık','public_title',$f['public_title']); stti_field('Kısa Başlık','short_title',$f['short_title']); stti_field('Slug','slug',$f['slug']); stti_field('Dil','language',$f['language']); ?></div><div class="stti-inline-note"><strong>Identity lock</strong><span>Title / slug / dates may evolve. Stable ID is immutable.</span></div></div>

      <div class="stti-editor-pane" data-pane="1"><?php stti_editor_pane_title(2,'Destinasyonlar','Multi-country / multi-city canonical model. Unknown is allowed; fabricated facts are not.'); ?><div class="stti-form-grid"><?php stti_field('Primary Country','primary_country',$f['primary_country']); stti_field('Countries','countries',$f['countries']); stti_field('Primary City','primary_city',$f['primary_city']); stti_field('Cities','cities',$f['cities']); stti_field('Departure City','departure_city',$f['departure_city']); stti_field('Return City','return_city',$f['return_city']); ?></div></div>

      <div class="stti-editor-pane" data-pane="2"><?php stti_editor_pane_title(3,'Tarihler','Direct typing + calendar picker. Duration and temporal state are derived from exact dates.'); ?><div class="stti-form-grid"><?php stti_select('Date Mode','date_mode',$f['date_mode'],['exact'=>'Exact','month_only'=>'Month only','tentative'=>'Tentative','coming_soon'=>'Coming soon','on_request'=>'On request','undated'=>'Undated']); stti_select('Date Precision','date_precision',$f['date_precision'],['exact'=>'Exact','day_month_only'=>'Day + month only','month_only'=>'Month only','season_only'=>'Season only','unknown'=>'Unknown']); stti_hybrid_date_field('Start Date','start_date',$f['start_date']); stti_hybrid_date_field('End Date','end_date',$f['end_date']); stti_field('Month','month',$f['month'],'month'); stti_field('Duration Days · AUTO','duration_days',$f['duration_days'],'number',true); stti_field('Duration Nights · AUTO','duration_nights',$f['duration_nights'],'number',true); ?></div><div class="stti-inline-note"><strong>Derived facts</strong><span>Exact dates control Duration Days/Nights and itinerary day dates. End &lt; Start blocks save.</span></div></div>

      <div class="stti-editor-pane" data-pane="3"><?php stti_editor_pane_title(4,'Rota','Structured stops own the route. Summary is generated automatically and stored for compatibility.'); stti_builder_header('Route Builder','Add/reorder stops. City or label is enough; unknown fields may stay empty.','stti-add-route-stop','Stop Ekle'); ?><div id="stti-route-builder" class="stti-builder" data-empty-text="Henüz structured stop yok."></div><?php stti_textarea('Rota Özeti · AUTO','route',$f['route']); ?><div class="stti-inline-note"><strong>No invention</strong><span>Departure/return is not inferred unless the source or operator confirms it.</span></div></div>

      <div class="stti-editor-pane" data-pane="4"><?php stti_editor_pane_title(5,'Gün Gün Program','Exact dates create day shells automatically. Empty content remains unknown and is not fabricated.'); ?><div class="stti-builder-head"><div><strong>Itinerary Builder</strong><span>Day number/date are derived; editorial fields remain human-entered.</span></div><div class="stti-builder-actions"><button type="button" class="button" id="stti-sync-itinerary">Tarihlerle Eşle</button><button type="button" class="button" id="stti-add-itinerary-day">+ Gün Ekle</button></div></div><div id="stti-itinerary-builder" class="stti-builder stti-day-builder"></div><div class="stti-inline-note"><strong>Safe sync</strong><span>Date sync adds missing days and recalculates dates; it never silently deletes extra day content.</span></div></div>

      <div class="stti-editor-pane" data-pane="5"><?php stti_editor_pane_title(6,'Oteller','Relations only. Hotel facts stay owned by Hotel Intelligence; unresolved source names are allowed.'); stti_builder_header('Hotel Relations','Use Hotel Intelligence Stable ID when known; otherwise keep an unresolved source name.','stti-add-hotel','Otel Relation Ekle'); ?><div id="stti-hotel-builder" class="stti-builder"></div><div class="stti-inline-note"><strong>Ownership boundary</strong><span>STTI stores relation + stay context, never duplicates Hotel Intelligence name/gallery/map/facts.</span></div></div>

      <div class="stti-editor-pane" data-pane="6"><?php stti_editor_pane_title(7,'Ulaşım','Structured transport segments. Provider/reference are optional and remain source-backed.'); stti_builder_header('Transport Segments','Flight, coach, train, ferry and other movement segments.','stti-add-transport','Segment Ekle'); ?><div id="stti-transport-builder" class="stti-builder"></div></div>

      <div class="stti-editor-pane" data-pane="7"><?php stti_editor_pane_title(8,'Fiyatlar','Summary semantics remain for compatibility; structured price rows support multiple occupancies/packages.'); ?><div class="stti-form-grid"><?php stti_select('Primary Price Type','price_type',$f['price_type'],['exact'=>'Exact','from'=>'From','on_request'=>'On request']); stti_field('Primary Amount','price_amount',$f['price_amount']); stti_select('Currency','currency',$f['currency'],['EUR'=>'EUR','USD'=>'USD','TRY'=>'TRY','GBP'=>'GBP']); stti_select('Basis','price_basis',$f['price_basis'],['unknown'=>'Unknown / source not specified','per_person'=>'Per person','per_room'=>'Per room','package'=>'Package']); ?></div><?php stti_builder_header('Structured Pricing','Multiple room/occupancy/package prices; “from” stays distinct from exact.','stti-add-price','Fiyat Satırı Ekle'); ?><div id="stti-pricing-builder" class="stti-builder"></div></div>

      <div class="stti-editor-pane" data-pane="8"><?php stti_editor_pane_title(9,'Dahil / Hariç','Only source-confirmed inclusions/exclusions should be entered. Unknown remains blank.'); ?><div class="stti-grid stti-grid-2"><div class="stti-builder-subpanel"><?php stti_builder_header('Dahil Olanlar','Confirmed included services only.','stti-add-included','Dahil Ekle'); ?><div id="stti-included-builder" class="stti-builder stti-builder-compact"></div></div><div class="stti-builder-subpanel"><?php stti_builder_header('Hariç Olanlar','Confirmed excluded services only.','stti-add-excluded','Hariç Ekle'); ?><div id="stti-excluded-builder" class="stti-builder stti-builder-compact"></div></div></div></div>

      <div class="stti-editor-pane" data-pane="9"><?php stti_editor_pane_title(10,'Vize & Gereksinimler','Visa state and requirements are explicit; unknown is a valid value.'); ?><div class="stti-form-grid"><?php stti_select('Visa Status','visa_status',$f['visa_status'],['unknown'=>'Unknown','required'=>'Required','not_required'=>'Not required','conditional'=>'Conditional / nationality dependent']); stti_textarea('Visa Notes','visa_notes',$f['visa_notes']); ?></div><?php stti_builder_header('Requirements','Passport, age, health, documents or other source-confirmed requirements.','stti-add-requirement','Gereksinim Ekle'); ?><div id="stti-requirements-builder" class="stti-builder stti-builder-compact"></div></div>

      <div class="stti-editor-pane" data-pane="10"><?php stti_editor_pane_title(11,'Medya','Media relation model remains locked for the next gate. No URL identity or duplicated attachment facts.'); ?><div class="stti-placeholder-grid"><div><span class="dashicons dashicons-format-image"></span><strong>Attachment relations</strong><p>Future: WordPress attachment IDs / references.</p></div><div><span class="dashicons dashicons-lock"></span><strong>Private only</strong><p>No public gallery output.</p></div><div><span class="dashicons dashicons-shield-alt"></span><strong>No URL identity</strong><p>Media URL is not the stable entity identity.</p></div></div></div>

      <div class="stti-editor-pane" data-pane="11"><?php stti_editor_pane_title(12,'İçerik','Editorial summary. Long-form content engine remains a later gate.'); ?><?php stti_textarea('Short Description','short_description',$f['short_description']); ?></div>

      <div class="stti-editor-pane" data-pane="12"><?php stti_editor_pane_title(13,'SEO','Public SEO remains hard locked in v0.5.7. Both Technical Preview and Customer Preview are admin-only/noindex and make no frontend canonical/sitemap/schema changes.'); ?><div class="stti-rule-stack"><div><b>INDEX</b><span>OFF</span></div><div><b>SITEMAP</b><span>OFF</span></div><div><b>CANONICAL</b><span>UNTOUCHED</span></div><div><b>SCHEMA</b><span>OFF</span></div></div></div>

      <div class="stti-editor-pane" data-pane="13"><?php stti_editor_pane_title(14,'Social / Brochure','Output generation remains locked. Structured data is being prepared for one-source / many-output use later.'); ?><div class="stti-placeholder-grid"><div><strong>1080×1350</strong><p>Future feed output.</p></div><div><strong>1080×1920</strong><p>Future story output.</p></div><div><strong>PDF / WhatsApp</strong><p>Future generated outputs.</p></div></div></div>

      <div class="stti-editor-pane" data-pane="14"><?php stti_editor_pane_title(15,'Preview / Publish','Lifecycle can be saved privately. Public publication remains impossible.'); ?><div class="stti-form-grid"><?php stti_select('Editorial','editorial',$f['editorial'],['draft'=>'Draft','needs_review'=>'Needs review','approved'=>'Approved']); stti_select('Schedule','schedule_status',$f['schedule_status'],['scheduled'=>'Scheduled','tentative'=>'Tentative','postponed'=>'Postponed','rescheduled'=>'Rescheduled','cancelled'=>'Cancelled']); stti_select('Availability','availability',$f['availability'],['open'=>'Open','limited'=>'Limited','sold_out'=>'Sold out','on_request'=>'On request','closed'=>'Closed']); stti_select('Temporal · AUTO for exact dates','temporal',$f['temporal'],['upcoming'=>'Upcoming','in_progress'=>'In progress','completed'=>'Completed','undated'=>'Undated']); stti_select('Source Type','source_type',$f['source_type'],['manual'=>'Manual','brochure'=>'Brochure','google_sheet'=>'Google Sheet','legacy_wordpress'=>'Legacy WordPress','json_import'=>'JSON Import']); stti_field('Source Ref','source_ref',$f['source_ref']); stti_select('Source Completeness','source_completeness',$f['source_completeness'],['source_complete'=>'SOURCE COMPLETE','source_partial'=>'SOURCE PARTIAL','source_minimal'=>'SOURCE MINIMAL']); ?></div><div class="stti-inline-note"><strong>Publication invariant</strong><span>Approved ≠ Published ≠ Indexed. Private renderer/customer preview remain admin-only/read-only; public renderer/routes/sitemap/indexation remain impossible in v0.6.0.</span></div></div>

      <div class="stti-editor-pane" data-pane="15"><?php stti_editor_pane_title(16,'Revision / Audit','Every create/update/unchanged save is auditable. Existing v0.2.3/v0.3.0 audit history is preserved. Opening either preview creates no audit/write event.'); ?><?php if(!$audit): ?><p class="stti-muted">No audit events yet.</p><?php else: ?><div class="stti-rule-stack"><?php foreach($audit as $e): ?><div><b><?php echo esc_html(strtoupper($e['event'])); ?></b><span><?php echo esc_html($e['created_at']); ?> · actor #<?php echo (int)$e['actor_id']; ?></span></div><?php endforeach; ?></div><?php endif; ?></div>
    </section>

    <aside class="stti-inspector"><div class="stti-inspector-card"><span class="stti-kicker">PRIVATE CUSTOMER EXPERIENCE</span><div class="stti-big-score"><strong><?php echo $row?'1':'0'; ?></strong><span>record</span></div><?php stti_readiness('Private Store','ACCEPTED','pass'); stti_readiness('Stable ID',$f['stable_id']?'READY':'ON SAVE',$f['stable_id']?'pass':'warn'); stti_readiness('Route Builder','ACTIVE','pass'); stti_readiness('Itinerary Builder','ACTIVE','pass'); stti_readiness('Hotel Relations','ACTIVE','pass'); stti_readiness('Private Renderer',$f['stable_id']?'READY':'ON SAVE',$f['stable_id']?'pass':'warn'); stti_readiness('Public Renderer','LOCKED','off'); stti_readiness('SEO','LOCKED','off'); ?></div><div class="stti-inspector-card"><span class="stti-kicker">SOURCE</span><strong><?php echo esc_html(strtoupper($f['source_type'])); ?></strong><p><?php echo esc_html(strtoupper($f['source_completeness'])); ?></p></div></aside></div>

    <footer class="stti-editor-footer"><div><span class="stti-state-dot"></span> Structured authoring · accepted technical renderer · customer preview read-only · frontend writes 0</div><div><?php if($row): ?><a class="button button-primary" href="<?php echo esc_url(stti_customer_preview_url($f['stable_id'])); ?>">Customer Preview</a><a class="button" href="<?php echo esc_url(stti_private_preview_url($f['stable_id'])); ?>">Technical Preview</a><?php endif; ?><button class="button" type="button" id="stti-json-preview">JSON Önizleme</button><button class="button" type="button" id="stti-json-download-draft">Taslak JSON İndir</button><button class="button button-primary" type="submit">Private Kaydet</button></div></footer>
    </form><dialog id="stti-json-dialog" class="stti-json-dialog"><button type="button" id="stti-json-close" class="button">Kapat</button><h2>Client-side JSON Preview</h2><pre id="stti-json-output"></pre></dialog>
    <?php
}


function stti_renderer_scalar($value, $fallback='—') {
    if ($value === null) return $fallback;
    $value = trim((string)$value);
    return $value === '' ? $fallback : $value;
}

function stti_renderer_date_label($date) {
    $date = trim((string)$date);
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) return '—';
    try { return (new DateTimeImmutable($date))->format('d.m.Y'); }
    catch (Exception $e) { return '—'; }
}


function stti_customer_logo_url() {
    $custom_logo_id = (int) get_theme_mod('custom_logo');
    if ($custom_logo_id > 0) {
        $url = wp_get_attachment_image_url($custom_logo_id, 'full');
        if (is_string($url) && $url !== '') return $url;
    }
    return '';
}

function stti_customer_date_long($date) {
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$date)) return '—';
    try {
        $dt = new DateTimeImmutable($date);
        $months = [1=>'Ocak',2=>'Şubat',3=>'Mart',4=>'Nisan',5=>'Mayıs',6=>'Haziran',7=>'Temmuz',8=>'Ağustos',9=>'Eylül',10=>'Ekim',11=>'Kasım',12=>'Aralık'];
        return $dt->format('j') . ' ' . ($months[(int)$dt->format('n')] ?? $dt->format('m')) . ' ' . $dt->format('Y');
    } catch (Exception $e) { return '—'; }
}

function stti_customer_currency_symbol($currency) {
    $currency = strtoupper(trim((string)$currency));
    return ['EUR'=>'€','USD'=>'$','TRY'=>'₺','GBP'=>'£'][$currency] ?? $currency;
}

function stti_customer_price($pricing) {
    $pricing = is_array($pricing) ? $pricing : [];
    $type = (string)($pricing['type'] ?? 'on_request');
    if ($type === 'on_request') return ['main'=>'Talep üzerine','prefix'=>'','suffix'=>''];
    $amount = $pricing['amount'] ?? null;
    if ($amount === null || $amount === '') return ['main'=>'Fiyat bilgisi bekleniyor','prefix'=>'','suffix'=>''];
    $num = rtrim(rtrim(number_format((float)$amount, 2, ',', '.'), '0'), ',');
    return ['main'=>$num . ' ' . stti_customer_currency_symbol($pricing['currency'] ?? ''),'prefix'=>$type==='from'?'Başlangıç fiyatı':'','suffix'=>''];
}

function stti_customer_render_items($items) {
    if (!is_array($items)) return;
    foreach ($items as $item) {
        $label = is_array($item) ? trim((string)($item['label'] ?? ($item['name'] ?? ''))) : trim((string)$item);
        if ($label === '') continue;
        echo '<li><span aria-hidden="true">✓</span><b>' . esc_html($label) . '</b></li>';
    }
}

function stti_customer_whatsapp_url($title = '') {
    $message = 'Merhaba, Server Turizm';
    if (trim((string)$title) !== '') $message .= ' "' . trim((string)$title) . '"';
    $message .= ' hakkında bilgi almak istiyorum.';
    return 'https://wa.me/905302015284?text=' . rawurlencode($message);
}

function stti_customer_media_url($media, $country = '') {
    if (is_array($media)) {
        foreach ($media as $item) {
            if (!is_array($item)) continue;
            $attachment_id = (int)($item['attachment_id'] ?? 0);
            if ($attachment_id > 0) {
                $url = wp_get_attachment_image_url($attachment_id, 'full');
                if (is_string($url) && $url !== '') return $url;
            }
            $url = esc_url_raw((string)($item['url'] ?? ''));
            if ($url !== '') return $url;
        }
    }
    $country_key = sanitize_title((string)$country);
    if ($country_key === 'iran') {
        return content_url('/uploads/2026/02/iran.jpg');
    }
    return '';
}

function stti_customer_preview_is_request() {
    return isset($_GET['stti_customer_preview']) && sanitize_text_field(wp_unslash($_GET['stti_customer_preview'])) === '1';
}

function stti_customer_preview_fail_closed() {
    global $wp_query;
    if ($wp_query) $wp_query->set_404();
    status_header(404);
    nocache_headers();
    $template = get_404_template();
    if ($template && is_readable($template)) include $template;
    else wp_die('Not found', '404', ['response'=>404]);
    exit;
}

function stti_maybe_redirect_legacy_customer_preview() {
    if (!isset($_GET['page'], $_GET['view'])) return;
    if (sanitize_key(wp_unslash($_GET['page'])) !== 'stti-tour-intelligence') return;
    if (sanitize_key(wp_unslash($_GET['view'])) !== 'customer-preview') return;
    if (!current_user_can('manage_options')) { stti_customer_preview_fail_closed(); }
    $stable_id = isset($_GET['tour']) ? sanitize_text_field(wp_unslash($_GET['tour'])) : '';
    if ($stable_id === '' || !stti_get_candidate($stable_id)) wp_die('Unknown STT stable ID. Customer preview fail closed.');
    wp_safe_redirect(stti_customer_preview_url($stable_id));
    exit;
}

function stti_customer_preview_body_class($classes) {
    $classes[] = 'stti-customer-preview-mode';
    return $classes;
}


function stti_customer_map_config($payload, $stable_id, $checksum) {
    $payload = is_array($payload) ? $payload : [];
    $dest = is_array($payload['destinations'] ?? null) ? $payload['destinations'] : [];
    $route = is_array($payload['route'] ?? null) ? $payload['route'] : [];
    $stops = is_array($route['stops'] ?? null) ? $route['stops'] : [];

    $countries = [];
    if (is_array($dest['countries'] ?? null)) {
        foreach ($dest['countries'] as $country) {
            $country = trim((string)$country);
            if ($country !== '') $countries[] = $country;
        }
    }
    $countries = array_values(array_unique($countries));
    $single_country = count($countries) === 1 ? $countries[0] : '';
    if ($single_country === '') {
        $primary = trim((string)($dest['primary_country'] ?? ''));
        if ($primary !== '' && count($countries) <= 1) $single_country = $primary;
    }

    $map_stops = [];
    foreach ($stops as $i => $stop) {
        if (!is_array($stop)) continue;
        $city = trim((string)($stop['city'] ?? ''));
        $label = trim((string)($stop['label'] ?? ''));
        $country = trim((string)($stop['country'] ?? ''));
        $name = $city !== '' ? $city : $label;
        if ($name === '') continue;
        if ($country === '' && $single_country !== '') $country = $single_country;
        $map_stops[] = [
            'order' => $i + 1,
            'stop_id' => (string)($stop['stop_id'] ?? ('R'.($i+1))),
            'name' => $name,
            'city' => $city,
            'country' => $country,
            'queryable' => $country !== '',
            'query' => $country !== '' ? ($name . ', ' . $country) : '',
        ];
    }

    return [
        'stableId' => (string)$stable_id,
        'checksum' => (string)$checksum,
        'stops' => $map_stops,
        'tiles' => 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
        'tileAttribution' => '&copy; OpenStreetMap contributors',
        'geocoder' => 'https://nominatim.openstreetmap.org/search',
        'geocodeDelayMs' => 1100,
        'cacheNamespace' => 'stti_route_geo_v1',
        'maxZoom' => 13,
        'minZoom' => 2,
        'segmentDurationMs' => 1050,
        'markerRevealMs' => 220,
        'animationStartDelayMs' => 260,
    ];
}

function stti_customer_preview_robots($robots) {
    if (!is_array($robots)) $robots = [];
    $robots['noindex'] = true;
    $robots['nofollow'] = true;
    $robots['noarchive'] = true;
    return $robots;
}

function stti_maybe_render_customer_theme_preview() {
    if (!stti_customer_preview_is_request()) return;

    $stable_id = isset($_GET['tour']) ? sanitize_text_field(wp_unslash($_GET['tour'])) : '';
    $nonce = isset($_GET['stti_preview_nonce']) ? sanitize_text_field(wp_unslash($_GET['stti_preview_nonce'])) : '';
    if (!is_user_logged_in() || !current_user_can('manage_options') || $stable_id === '' || !wp_verify_nonce($nonce, 'stti_customer_preview_' . $stable_id)) {
        stti_customer_preview_fail_closed();
    }

    $row = stti_get_candidate($stable_id);
    if (!$row) stti_customer_preview_fail_closed();
    $payload = json_decode($row['payload'], true);
    if (!is_array($payload)) wp_die('Canonical payload unreadable. Customer preview fail closed.');

    nocache_headers();
    header('X-Robots-Tag: noindex, nofollow, noarchive, nosnippet, noimageindex', true);
    status_header(200);

    // Keep the live theme shell, but fail closed for SEO exposure on this private request.
    remove_action('wp_head', 'rel_canonical');
    remove_action('wp_head', 'wp_shortlink_wp_head', 10);
    add_filter('wp_robots', 'stti_customer_preview_robots', 999);
    add_filter('wpseo_canonical', '__return_false', 999);
    add_filter('wpseo_json_ld_output', '__return_false', 999);
    add_filter('rank_math/frontend/canonical', '__return_false', 999);
    add_filter('rank_math/json_ld', '__return_empty_array', 999);
    add_filter('body_class', 'stti_customer_preview_body_class', 999);
    show_admin_bar(false);

    wp_enqueue_style('stti-leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', [], '1.9.4');
    wp_enqueue_style('stti-customer-preview', STTI_URL . 'assets/customer-preview.css', ['stti-leaflet'], STTI_VERSION);
    wp_enqueue_script('stti-leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', [], '1.9.4', true);
    wp_enqueue_script('stti-customer-preview', STTI_URL . 'assets/customer-preview.js', ['stti-leaflet'], STTI_VERSION, true);
    wp_localize_script('stti-customer-preview', 'STTI_CX_MAP', stti_customer_map_config($payload, $stable_id, (string)($row['checksum'] ?? '')));
    stti_render_customer_preview_theme_shell($row, $payload);
    exit;
}

function stti_render_customer_preview_theme_shell($row, $p) {
    $identity = is_array($p['identity'] ?? null) ? $p['identity'] : [];
    $dest = is_array($p['destinations'] ?? null) ? $p['destinations'] : [];
    $date = is_array($p['date'] ?? null) ? $p['date'] : [];
    $route = is_array($p['route'] ?? null) ? $p['route'] : [];
    $stops = is_array($route['stops'] ?? null) ? $route['stops'] : [];
    $itinerary = is_array($p['itinerary'] ?? null) ? $p['itinerary'] : [];
    $hotels = is_array($p['stays']['hotels'] ?? null) ? $p['stays']['hotels'] : [];
    $transport = is_array($p['transport']['segments'] ?? null) ? $p['transport']['segments'] : [];
    $pricing = is_array($p['pricing'] ?? null) ? $p['pricing'] : [];
    $services = is_array($p['services'] ?? null) ? $p['services'] : [];
    $included = is_array($services['included'] ?? null) ? $services['included'] : [];
    $excluded = is_array($services['excluded'] ?? null) ? $services['excluded'] : [];
    $requirements = is_array($p['requirements'] ?? null) ? $p['requirements'] : [];
    $requirement_items = is_array($requirements['items'] ?? null) ? $requirements['items'] : [];
    $media = is_array($p['media']['items'] ?? null) ? $p['media']['items'] : [];
    $content = is_array($p['content'] ?? null) ? $p['content'] : [];
    $price = stti_customer_price($pricing);
    $title = stti_renderer_scalar($identity['public_title'] ?? $row['public_title']);
    $country = stti_renderer_scalar($dest['primary_country'] ?? '');
    $days = $date['duration_days'] ?? null;
    $nights = $date['duration_nights'] ?? null;
    $route_summary = stti_renderer_scalar($route['summary'] ?? '');
    $basis = strtolower(trim((string)($pricing['basis'] ?? 'unknown')));
    $start_label = stti_customer_date_long($date['start_date'] ?? '');
    $end_label = stti_customer_date_long($date['end_date'] ?? '');
    $hero_image = stti_customer_media_url($media, $country);
    $whatsapp = stti_customer_whatsapp_url($title);
    $edit_url = stti_view_url('editor',['tour'=>$row['stable_id']]);
    $registry_url = stti_view_url('tours');
    $tech_url = stti_private_preview_url($row['stable_id']);
    $has_itinerary_detail = false;
    foreach ($itinerary as $day) { if (stti_renderer_has_itinerary_detail($day)) { $has_itinerary_detail = true; break; } }
    $visa_has_fact = trim((string)($requirements['visa_notes'] ?? '')) !== '' || !empty($requirement_items) || !in_array(strtolower((string)($requirements['visa_status'] ?? 'unknown')), ['', 'unknown'], true);

    add_filter('pre_get_document_title', function() use ($title) { return $title . ' · Server Turizm'; }, 999);
    get_header();
    ?>
    <div class="stti-cx">
      <div class="stti-cx-privatebar">
        <div><strong>ÖZEL ÖNİZLEME</strong><span>Yalnızca yönetici · noindex · canonical STTI verisi</span></div>
        <nav><a href="<?php echo esc_url($edit_url); ?>">Editör</a><a href="<?php echo esc_url($tech_url); ?>">Teknik Önizleme</a><a href="<?php echo esc_url($registry_url); ?>">Registry</a></nav>
      </div>

      <section class="stti-cx-hero<?php echo $hero_image ? ' has-image' : ''; ?>"<?php if($hero_image): ?> style="--stti-hero:url('<?php echo esc_url($hero_image); ?>')"<?php endif; ?>>
        <div class="stti-cx-hero-overlay"></div>
        <div class="stti-cx-wrap stti-cx-hero-inner">
          <div class="stti-cx-hero-copy">
            <span class="stti-cx-eyebrow">SERVER TURİZM · KÜLTÜR TURLARI</span>
            <h1><?php echo esc_html($title); ?></h1>
            <?php if($route_summary): ?><p class="stti-cx-route-summary"><?php echo esc_html($route_summary); ?></p><?php endif; ?>
            <div class="stti-cx-facts">
              <?php if($start_label !== '—' && $end_label !== '—'): ?><span><?php echo esc_html($start_label); ?> — <?php echo esc_html($end_label); ?></span><?php endif; ?>
              <?php if($days !== null && $nights !== null): ?><span><?php echo (int)$days; ?> Gün · <?php echo (int)$nights; ?> Gece</span><?php endif; ?>
              <?php if($country): ?><span><?php echo esc_html($country); ?></span><?php endif; ?>
            </div>
            <div class="stti-cx-price"><small><?php echo esc_html($price['prefix'] ?: 'Tur fiyatı'); ?></small><strong><?php echo esc_html($price['main']); ?></strong><?php if($basis === 'unknown'): ?><em>Fiyat baz bilgisi kaynakta belirtilmemiştir.</em><?php endif; ?></div>
            <div class="stti-cx-actions">
              <a class="stti-cx-btn stti-cx-btn-gold" href="<?php echo esc_url($whatsapp); ?>" target="_blank" rel="noopener noreferrer">WhatsApp'tan Bilgi Al <span>→</span></a>
              <a class="stti-cx-btn stti-cx-btn-line" href="#stti-program">Programı İncele <span>↓</span></a>
            </div>
          </div>
        </div>
      </section>

      <section class="stti-cx-summary">
        <div class="stti-cx-wrap stti-cx-summary-grid">
          <article class="stti-cx-summary-card destination"><span>DESTİNASYON</span><b><?php echo esc_html($country ?: '—'); ?></b><i>Keşif rotası</i></article>
          <article class="stti-cx-summary-card dates"><span>TARİH</span><b><?php echo esc_html($start_label); ?><small><?php echo esc_html($end_label); ?></small></b><i>Gidiş · dönüş</i></article>
          <article class="stti-cx-summary-card duration"><span>SÜRE</span><b><?php echo $days===null?'—':(int)$days.' Gün'; ?><small><?php echo $nights===null?'—':(int)$nights.' Gece'; ?></small></b><i>Toplam program</i></article>
          <article class="stti-cx-summary-card price"><span>FİYAT</span><b><?php echo esc_html($price['main']); ?></b><i><?php echo $basis === 'unknown' ? 'Baz bilgisi kaynakta yok' : 'Doğrulanmış fiyat'; ?></i></article>
        </div>
      </section>

      <section class="stti-cx-section" id="stti-program">
        <div class="stti-cx-wrap">
          <header class="stti-cx-section-head"><span>01 · ROTA</span><h2><?php echo count($stops); ?> durak, tek yolculuk.</h2><p>Doğrulanmış tur durakları dünya haritasında otomatik konumlandırılır; rota sıraya göre çizilir.</p></header>
          <?php if($stops): ?>
            <div class="stti-cx-map-shell">
              <div class="stti-cx-map-topline"><div><span>DÜNYA ROTA HARİTASI</span><b><?php echo count($stops); ?> durak · otomatik yakınlaştırma</b></div><div class="stti-cx-map-legend"><i></i> Server Turizm rotası</div></div>
              <div id="stti-cx-route-map" class="stti-cx-route-map" role="region" aria-label="<?php echo esc_attr($title); ?> dünya rota haritası"></div>
              <div id="stti-cx-map-status" class="stti-cx-map-status">Rota konumları doğrulanıyor…</div>
            </div>
            <div class="stti-cx-route-line"><?php foreach($stops as $i=>$stop): $label=trim((string)($stop['city']??'')) ?: trim((string)($stop['label']??'')); ?><article><b><?php echo str_pad((string)($i+1),2,'0',STR_PAD_LEFT); ?></b><span><?php echo esc_html($label ?: 'Durak'); ?></span></article><?php endforeach; ?></div>
          <?php endif; ?>
        </div>
      </section>

      <section class="stti-cx-section stti-cx-section-soft">
        <div class="stti-cx-wrap">
          <header class="stti-cx-section-head"><span>02 · TUR PROGRAMI</span><h2>Yolculuğun akışı.</h2></header>
          <?php if($has_itinerary_detail): ?>
            <div class="stti-cx-days"><?php foreach($itinerary as $day): ?><details class="stti-cx-day"><summary><b><?php echo str_pad((string)((int)($day['day_number']??0)),2,'0',STR_PAD_LEFT); ?></b><span><small><?php echo esc_html(stti_customer_date_long($day['date']??'')); ?></small><strong><?php echo esc_html(trim((string)($day['title']??'')) ?: ('Gün '.(int)($day['day_number']??0))); ?></strong></span><i>+</i></summary><div><?php if(trim((string)($day['city']??''))!==''): ?><p><b>Şehir</b><?php echo esc_html($day['city']); ?></p><?php endif; ?><?php if(trim((string)($day['summary']??''))!==''): ?><p><?php echo esc_html($day['summary']); ?></p><?php endif; ?><?php if(trim((string)($day['activities']??''))!==''): ?><p><b>Aktiviteler</b><?php echo esc_html($day['activities']); ?></p><?php endif; ?><?php if(trim((string)($day['meals']??''))!==''): ?><p><b>Öğünler</b><?php echo esc_html($day['meals']); ?></p><?php endif; ?></div></details><?php endforeach; ?></div>
          <?php else: ?>
            <div class="stti-cx-program-pending"><div><span>PROGRAM DETAYI</span><h3>Gün gün program hazırlanıyor.</h3><p>Kaynakta doğrulanmış günlük program bulunmadığı için boş içerik veya tahmini bilgi göstermiyoruz.</p></div><div class="stti-cx-date-stack"><b><?php echo esc_html($start_label); ?></b><i>→</i><b><?php echo esc_html($end_label); ?></b></div></div>
          <?php endif; ?>
        </div>
      </section>

      <?php if($hotels || $transport || $included || $excluded || $visa_has_fact): ?>
      <section class="stti-cx-section stti-cx-deep">
        <div class="stti-cx-wrap">
          <header class="stti-cx-section-head light"><span>03 · SEYAHAT DETAYLARI</span><h2>Doğrulanmış detaylar.</h2></header>
          <div class="stti-cx-detail-grid">
            <?php if($hotels): ?><article><span>KONAKLAMA</span><h3><?php echo count($hotels); ?> otel</h3><ul><?php foreach($hotels as $hotel): ?><li><?php echo esc_html(stti_renderer_scalar($hotel['hotel_stable_id']??($hotel['unresolved_name']??''),'Otel')); ?></li><?php endforeach; ?></ul></article><?php endif; ?>
            <?php if($transport): ?><article><span>ULAŞIM</span><h3><?php echo count($transport); ?> segment</h3><ul><?php foreach($transport as $seg): ?><li><?php echo esc_html(strtoupper(stti_renderer_scalar($seg['type']??''))); ?> · <?php echo esc_html(stti_renderer_scalar($seg['from']??'')); ?> → <?php echo esc_html(stti_renderer_scalar($seg['to']??'')); ?></li><?php endforeach; ?></ul></article><?php endif; ?>
            <?php if($visa_has_fact): ?><article><span>VİZE</span><h3><?php echo esc_html(strtoupper(stti_renderer_scalar($requirements['visa_status']??''))); ?></h3><?php if(trim((string)($requirements['visa_notes']??''))!==''): ?><p><?php echo esc_html($requirements['visa_notes']); ?></p><?php elseif($requirement_items): ?><ul><?php stti_customer_render_items($requirement_items); ?></ul><?php endif; ?></article><?php endif; ?>
          </div>
          <?php if($included || $excluded): ?><div class="stti-cx-service-grid"><?php if($included): ?><article><span>FİYATA DAHİL</span><ul><?php stti_customer_render_items($included); ?></ul></article><?php endif; ?><?php if($excluded): ?><article><span>FİYATA DAHİL DEĞİL</span><ul><?php stti_customer_render_items($excluded); ?></ul></article><?php endif; ?></div><?php endif; ?>
        </div>
      </section>
      <?php endif; ?>

      <section class="stti-cx-cta">
        <div class="stti-cx-wrap stti-cx-cta-inner"><div><span>BİR SONRAKİ YOLCULUĞUNUZ</span><h2>Güvenle başlayın.</h2><p>Tur detayları ve uygunluk için seyahat danışmanımızla görüşün.</p></div><a class="stti-cx-btn stti-cx-btn-gold" href="<?php echo esc_url($whatsapp); ?>" target="_blank" rel="noopener noreferrer">WhatsApp'tan Bilgi Al <span>→</span></a></div>
      </section>

      <div class="stti-cx-adminnote"><div class="stti-cx-wrap"><b>Private preview:</b> Eksik canonical alanlar müşteri yüzünde gizlenir; veri uydurulmaz.</div></div>
    </div>
    <?php
    get_footer();
}

function stti_renderer_price_label($pricing) {
    $pricing = is_array($pricing) ? $pricing : [];
    $type = (string)($pricing['type'] ?? 'on_request');
    if ($type === 'on_request') return 'Talep üzerine';
    $amount = $pricing['amount'] ?? null;
    if ($amount === null || $amount === '') return '—';
    $currency = (string)($pricing['currency'] ?? '');
    $prefix = $type === 'from' ? 'Başlangıç ' : '';
    return $prefix . rtrim(rtrim(number_format((float)$amount, 2, '.', ''), '0'), '.') . ' ' . $currency;
}

function stti_renderer_has_itinerary_detail($day) {
    if (!is_array($day)) return false;
    foreach (['title','city','summary','activities','meals','transport_ref','hotel_ref','media_refs'] as $key) {
        if (trim((string)($day[$key] ?? '')) !== '') return true;
    }
    return false;
}

function stti_render_private_preview() {
    if (!current_user_can('manage_options')) { wp_die('Unauthorized'); }
    $stable_id = isset($_GET['tour']) ? sanitize_text_field(wp_unslash($_GET['tour'])) : '';
    if ($stable_id === '') { stti_empty_state('Private Preview için tour seçilmedi','Registry üzerinden bir canonical STT candidate seçin.'); return; }
    $row = stti_get_candidate($stable_id);
    if (!$row) { wp_die('Unknown STT stable ID. Private renderer fail closed.'); }
    $p = json_decode($row['payload'], true);
    if (!is_array($p)) { wp_die('Canonical payload unreadable. Private renderer fail closed.'); }

    $identity = is_array($p['identity'] ?? null) ? $p['identity'] : [];
    $dest = is_array($p['destinations'] ?? null) ? $p['destinations'] : [];
    $date = is_array($p['date'] ?? null) ? $p['date'] : [];
    $route = is_array($p['route'] ?? null) ? $p['route'] : [];
    $stops = is_array($route['stops'] ?? null) ? $route['stops'] : [];
    $itinerary = is_array($p['itinerary'] ?? null) ? $p['itinerary'] : [];
    $hotels = is_array($p['stays']['hotels'] ?? null) ? $p['stays']['hotels'] : [];
    $transport = is_array($p['transport']['segments'] ?? null) ? $p['transport']['segments'] : [];
    $pricing = is_array($p['pricing'] ?? null) ? $p['pricing'] : [];
    $prices = is_array($pricing['items'] ?? null) ? $pricing['items'] : [];
    $services = is_array($p['services'] ?? null) ? $p['services'] : [];
    $included = is_array($services['included'] ?? null) ? $services['included'] : [];
    $excluded = is_array($services['excluded'] ?? null) ? $services['excluded'] : [];
    $requirements = is_array($p['requirements'] ?? null) ? $p['requirements'] : [];
    $requirement_items = is_array($requirements['items'] ?? null) ? $requirements['items'] : [];
    $lifecycle = is_array($p['lifecycle'] ?? null) ? $p['lifecycle'] : [];
    $prov = is_array($p['provenance'] ?? null) ? $p['provenance'] : [];
    $content = is_array($p['content'] ?? null) ? $p['content'] : [];
    $fingerprint = stti_private_renderer_fingerprint($row);
    $duration_days = $date['duration_days'] ?? null;
    $duration_nights = $date['duration_nights'] ?? null;
    ?>
    <section class="stti-private-lockbar">
      <div><span class="dashicons dashicons-lock"></span><strong>ADMIN-ONLY PRIVATE RENDERER</strong><span>Canonical direct read · NO public route · NOINDEX · NON-SITEMAP · NO schema output</span></div>
      <div><a class="button button-primary" href="<?php echo esc_url(stti_customer_preview_url($stable_id)); ?>">Customer Preview</a><a class="button" href="<?php echo stti_view_url('editor',['tour'=>$stable_id]); ?>">← Editöre Dön</a><a class="button" href="<?php echo stti_view_url('tours'); ?>">Registry</a></div>
    </section>

    <article class="stti-render-shell">
      <header class="stti-render-hero">
        <div class="stti-render-hero-main">
          <span class="stti-kicker">PRIVATE TOUR DETAIL PREVIEW · <?php echo esc_html($stable_id); ?></span>
          <h2><?php echo esc_html(stti_renderer_scalar($identity['public_title'] ?? $row['public_title'])); ?></h2>
          <p class="stti-render-route"><?php echo esc_html(stti_renderer_scalar($route['summary'] ?? '')); ?></p>
          <?php if (trim((string)($content['short_description'] ?? '')) !== ''): ?><p class="stti-render-description"><?php echo esc_html($content['short_description']); ?></p><?php endif; ?>
          <div class="stti-render-badges">
            <?php echo stti_badge(strtoupper(stti_renderer_scalar($lifecycle['editorial'] ?? $row['editorial'])),'gold'); ?>
            <?php echo stti_badge(strtoupper(stti_renderer_scalar($lifecycle['schedule'] ?? $row['schedule_status'])),'neutral'); ?>
            <?php echo stti_badge(strtoupper(stti_renderer_scalar($lifecycle['availability'] ?? $row['availability'])),'blue'); ?>
            <?php echo stti_badge('PRIVATE','red'); ?>
          </div>
        </div>
        <div class="stti-render-facts">
          <div><span>Tarih</span><strong><?php echo esc_html(stti_renderer_date_label($date['start_date'] ?? '')); ?> – <?php echo esc_html(stti_renderer_date_label($date['end_date'] ?? '')); ?></strong></div>
          <div><span>Süre</span><strong><?php echo $duration_days===null?'—':(int)$duration_days.' Gün'; ?> / <?php echo $duration_nights===null?'—':(int)$duration_nights.' Gece'; ?></strong></div>
          <div><span>Fiyat</span><strong><?php echo esc_html(stti_renderer_price_label($pricing)); ?></strong><small>Basis: <?php echo esc_html(stti_renderer_scalar($pricing['basis'] ?? 'unknown')); ?></small></div>
          <div><span>Kaynak</span><strong><?php echo esc_html(strtoupper(stti_renderer_scalar($prov['source_completeness'] ?? $row['source_completeness']))); ?></strong><small><?php echo esc_html(stti_renderer_scalar($prov['source_type'] ?? '')); ?></small></div>
        </div>
      </header>

      <section class="stti-render-section">
        <div class="stti-render-section-head"><div><span class="stti-kicker">ROTA</span><h3><?php echo count($stops); ?> canonical stop</h3></div><span>Summary, structured stops'tan okunur.</span></div>
        <?php if (!$stops): ?><div class="stti-render-empty">Canonical route stop bulunmuyor.</div><?php else: ?><div class="stti-render-route-line"><?php foreach($stops as $i=>$stop): $label=trim((string)($stop['city'] ?? '')) ?: trim((string)($stop['label'] ?? '')); ?><div class="stti-render-stop"><b><?php echo (int)($i+1); ?></b><strong><?php echo esc_html($label ?: 'Unnamed stop'); ?></strong><?php if(trim((string)($stop['note'] ?? ''))!==''): ?><small><?php echo esc_html($stop['note']); ?></small><?php endif; ?></div><?php endforeach; ?></div><?php endif; ?>
      </section>

      <section class="stti-render-section">
        <div class="stti-render-section-head"><div><span class="stti-kicker">GÜN GÜN PROGRAM</span><h3><?php echo count($itinerary); ?> gün</h3></div><span>Boş shell'ler kaynakta olmayan içerikle doldurulmaz.</span></div>
        <?php if (!$itinerary): ?><div class="stti-render-empty">Canonical itinerary bulunmuyor.</div><?php else: ?><div class="stti-render-days"><?php foreach($itinerary as $day): $has=stti_renderer_has_itinerary_detail($day); ?><article class="stti-render-day"><div class="stti-render-day-num"><span>GÜN</span><b><?php echo (int)($day['day_number'] ?? 0); ?></b></div><div><small><?php echo esc_html(stti_renderer_date_label($day['date'] ?? '')); ?></small><?php if($has): ?><h4><?php echo esc_html(stti_renderer_scalar($day['title'] ?? '', 'Program')); ?></h4><?php if(trim((string)($day['city'] ?? ''))!==''): ?><p><b>Şehir:</b> <?php echo esc_html($day['city']); ?></p><?php endif; ?><?php if(trim((string)($day['summary'] ?? ''))!==''): ?><p><?php echo esc_html($day['summary']); ?></p><?php endif; ?><?php if(trim((string)($day['activities'] ?? ''))!==''): ?><p><b>Aktiviteler:</b> <?php echo esc_html($day['activities']); ?></p><?php endif; ?><?php if(trim((string)($day['meals'] ?? ''))!==''): ?><p><b>Öğün:</b> <?php echo esc_html($day['meals']); ?></p><?php endif; ?><?php else: ?><p class="stti-render-muted">Program detayı henüz canonical kaynakla doğrulanmadı.</p><?php endif; ?></div></article><?php endforeach; ?></div><?php endif; ?>
      </section>

      <div class="stti-render-grid-2">
        <section class="stti-render-section">
          <div class="stti-render-section-head"><div><span class="stti-kicker">OTELLER</span><h3><?php echo count($hotels); ?> relation</h3></div></div>
          <?php if(!$hotels): ?><div class="stti-render-empty">Canonical payload içinde otel relation yok.</div><?php else: ?><div class="stti-render-list"><?php foreach($hotels as $hotel): ?><div><strong><?php echo esc_html(stti_renderer_scalar($hotel['hotel_stable_id'] ?? ($hotel['unresolved_name'] ?? ''), 'Unresolved hotel')); ?></strong><span><?php echo esc_html(stti_renderer_scalar($hotel['city'] ?? '')); ?> · <?php echo esc_html(stti_renderer_scalar($hotel['mode'] ?? 'unresolved')); ?></span></div><?php endforeach; ?></div><?php endif; ?>
        </section>
        <section class="stti-render-section">
          <div class="stti-render-section-head"><div><span class="stti-kicker">ULAŞIM</span><h3><?php echo count($transport); ?> segment</h3></div></div>
          <?php if(!$transport): ?><div class="stti-render-empty">Canonical payload içinde transport segment yok.</div><?php else: ?><div class="stti-render-list"><?php foreach($transport as $segment): ?><div><strong><?php echo esc_html(strtoupper(stti_renderer_scalar($segment['type'] ?? ''))); ?></strong><span><?php echo esc_html(stti_renderer_scalar($segment['from'] ?? '')); ?> → <?php echo esc_html(stti_renderer_scalar($segment['to'] ?? '')); ?></span></div><?php endforeach; ?></div><?php endif; ?>
        </section>
      </div>

      <section class="stti-render-section">
        <div class="stti-render-section-head"><div><span class="stti-kicker">FİYATLANDIRMA</span><h3><?php echo count($prices); ?> structured price</h3></div><span>Exact / From / On request semantiği korunur.</span></div>
        <?php if(!$prices): ?><div class="stti-render-empty">Structured pricing satırı yok.</div><?php else: ?><div class="stti-render-price-grid"><?php foreach($prices as $price): ?><article><span><?php echo esc_html(stti_renderer_scalar($price['label'] ?? '', 'Fiyat')); ?></span><strong><?php $pt=(string)($price['type']??'on_request'); if($pt==='on_request') echo 'Talep üzerine'; else { $pv=$price['amount']??''; echo esc_html(($pt==='from'?'Başlangıç ':'').stti_renderer_scalar($pv).' '.stti_renderer_scalar($price['currency']??'')); } ?></strong><small>Basis: <?php echo esc_html(stti_renderer_scalar($price['basis'] ?? 'unknown')); ?><?php if(trim((string)($price['occupancy'] ?? ''))!=='') echo ' · '.esc_html($price['occupancy']); ?></small><?php if(trim((string)($price['note'] ?? ''))!==''): ?><p><?php echo esc_html($price['note']); ?></p><?php endif; ?></article><?php endforeach; ?></div><?php endif; ?>
      </section>

      <div class="stti-render-grid-3">
        <section class="stti-render-section"><div class="stti-render-section-head"><div><span class="stti-kicker">DAHİL</span><h3><?php echo count($included); ?></h3></div></div><?php if(!$included): ?><div class="stti-render-empty">Kaynakla doğrulanmış dahil hizmet girilmedi.</div><?php else: ?><ul class="stti-render-ul"><?php foreach($included as $item): ?><li><?php echo esc_html(stti_renderer_scalar($item['label'] ?? ($item['name'] ?? ''))); ?></li><?php endforeach; ?></ul><?php endif; ?></section>
        <section class="stti-render-section"><div class="stti-render-section-head"><div><span class="stti-kicker">HARİÇ</span><h3><?php echo count($excluded); ?></h3></div></div><?php if(!$excluded): ?><div class="stti-render-empty">Kaynakla doğrulanmış hariç hizmet girilmedi.</div><?php else: ?><ul class="stti-render-ul"><?php foreach($excluded as $item): ?><li><?php echo esc_html(stti_renderer_scalar($item['label'] ?? ($item['name'] ?? ''))); ?></li><?php endforeach; ?></ul><?php endif; ?></section>
        <section class="stti-render-section"><div class="stti-render-section-head"><div><span class="stti-kicker">VİZE / GEREKSİNİM</span><h3><?php echo esc_html(strtoupper(stti_renderer_scalar($requirements['visa_status'] ?? 'unknown'))); ?></h3></div></div><?php if(trim((string)($requirements['visa_notes'] ?? ''))!==''): ?><p><?php echo esc_html($requirements['visa_notes']); ?></p><?php endif; ?><?php if(!$requirement_items): ?><div class="stti-render-empty">Ek gereksinim girilmedi.</div><?php else: ?><ul class="stti-render-ul"><?php foreach($requirement_items as $item): ?><li><?php echo esc_html(stti_renderer_scalar($item['label'] ?? ($item['name'] ?? ''))); ?></li><?php endforeach; ?></ul><?php endif; ?></section>
      </div>

      <footer class="stti-render-evidence">
        <div><span>Canonical checksum</span><code><?php echo esc_html($row['checksum']); ?></code></div>
        <div><span>Render fingerprint</span><code><?php echo esc_html($fingerprint); ?></code></div>
        <div><span>Source ref</span><code><?php echo esc_html(stti_renderer_scalar($prov['source_ref'] ?? '')); ?></code></div>
        <div><span>Contract</span><code>STTI-PRIVATE-RENDERER-0.1.0</code></div>
      </footer>
    </article>
    <?php
}


function stti_readiness($label,$state,$tone){echo '<div class="stti-readiness"><span>'.esc_html($label).'</span><b class="stti-r-'.esc_attr($tone).'">'.esc_html($state).'</b></div>';}

function stti_render_review($fixtures,$candidates){$review=array_filter($candidates,fn($r)=>$r['editorial']==='needs_review'); ?>
<section class="stti-grid stti-grid-2"><article class="stti-panel"><div class="stti-panel-head"><div><span class="stti-kicker">PRIVATE HUMAN REVIEW</span><h2><?php echo count($review); ?> stored candidates require review</h2></div></div><?php if(!$review): ?><p class="stti-muted">No stored candidates yet. The four T1.1 fixtures remain read-only evidence.</p><?php else: ?><div class="stti-review-list"><?php foreach($review as $r): ?><div class="stti-review-item"><div><strong><?php echo esc_html($r['public_title']); ?></strong><span><?php echo esc_html($r['stable_id']); ?> · <?php echo esc_html(strtoupper($r['source_completeness'])); ?></span></div><a class="button button-small" href="<?php echo stti_view_url('editor',['tour'=>$r['stable_id']]); ?>">Aç</a></div><?php endforeach; ?></div><?php endif; ?></article><article class="stti-panel"><div class="stti-panel-head"><div><span class="stti-kicker">REVIEW RULE</span><h2>No invented facts</h2></div></div><div class="stti-rule-stack"><div><b>ERROR</b><span>Invalid/contradictory data: save blocked.</span></div><div><b>WARNING</b><span>Incomplete source: private save allowed, human review required.</span></div><div><b>PUBLIC</b><span>Always locked publicly in v0.6.0; both preview surfaces are admin-only/read-only.</span></div></div></article></section>
<?php }

function stti_render_archive($candidates){$completed=array_filter($candidates,fn($r)=>$r['temporal']==='completed'); if(!$completed){stti_empty_state('Archive gate is intentionally empty','Completed candidates are preserved, but archive/restore actions are not implemented in v0.6.0.');return;} ?><section class="stti-panel"><div class="stti-panel-head"><div><span class="stti-kicker">ARCHIVE CANDIDATES</span><h2><?php echo count($completed); ?> completed private candidates</h2></div></div><p class="stti-muted">Read-only. No archive write exists yet.</p></section><?php }


/**
 * v0.6.0 JSON Import Contract validation + normalization + DRY RUN ONLY.
 * No canonical candidate, audit, sequence, publication or frontend write exists in this gate.
 */
function stti_import_is_assoc($value) {
    return is_array($value) && ($value === [] || array_keys($value) !== range(0, count($value) - 1));
}

function stti_import_allowed_keys($obj, $allowed, $path, &$errors) {
    if (!is_array($obj)) return;
    foreach (array_keys($obj) as $key) {
        if (!in_array((string)$key, $allowed, true)) $errors[] = $path . ': desteklenmeyen alan: ' . $key;
    }
}

function stti_import_contains_publication_request($value, $path='root') {
    if (!is_array($value)) return null;
    foreach ($value as $key=>$item) {
        $p = $path . '.' . $key;
        if (in_array((string)$key, ['publication','public_route','hub_visible','homepage_visible','indexable','sitemap','schema_output','canonical','robots'], true)) return $p;
        $found = stti_import_contains_publication_request($item, $p);
        if ($found) return $found;
    }
    return null;
}

function stti_import_validate_contract($doc) {
    $errors=[]; $warnings=[];
    if (!is_array($doc) || !stti_import_is_assoc($doc)) return ['errors'=>['Root JSON object olmalıdır.'],'warnings'=>[]];
    stti_import_allowed_keys($doc, ['import_contract','mode','policy','producer','target','sources','tour'], 'root', $errors);
    foreach (['import_contract','mode','policy','target','sources','tour'] as $key) if (!array_key_exists($key,$doc)) $errors[]='Zorunlu alan eksik: '.$key;
    if (($doc['import_contract'] ?? null) !== 'STTI-TOUR-IMPORT-1.0.0') $errors[]='import_contract STTI-TOUR-IMPORT-1.0.0 olmalıdır.';
    if (!in_array(($doc['mode'] ?? null), ['partial','full'], true)) $errors[]='mode partial veya full olmalıdır.';

    $policy=$doc['policy'] ?? null;
    if (!is_array($policy)) $errors[]='policy object olmalıdır.';
    else {
        stti_import_allowed_keys($policy,['facts','missing_values','publication'],'policy',$errors);
        if (($policy['facts'] ?? null)!=='source_only_no_invention') $errors[]='policy.facts source_only_no_invention olmalıdır.';
        if (($policy['missing_values'] ?? null)!=='omit_or_null_or_explicit_unknown') $errors[]='policy.missing_values geçersiz.';
        if (($policy['publication'] ?? null)!=='force_private') $errors[]='policy.publication force_private olmalıdır.';
    }

    if (isset($doc['producer']) && !is_array($doc['producer'])) $errors[]='producer object olmalıdır.';
    $target=$doc['target'] ?? null;
    if (!is_array($target)) $errors[]='target object olmalıdır.';
    else {
        stti_import_allowed_keys($target,['stable_id','expected_checksum_sha256'],'target',$errors);
        if (!array_key_exists('stable_id',$target) || !array_key_exists('expected_checksum_sha256',$target)) $errors[]='target.stable_id ve expected_checksum_sha256 birlikte bulunmalıdır.';
        $sid=$target['stable_id'] ?? null; $expected=$target['expected_checksum_sha256'] ?? null;
        if ($sid!==null && (!is_string($sid) || !preg_match('/^STT-[0-9]{6}$/',$sid))) $errors[]='target.stable_id formatı geçersiz.';
        if ($sid===null && $expected!==null) $errors[]='Yeni kayıt target.expected_checksum_sha256=null olmalıdır.';
        if ($sid!==null && (!is_string($expected) || !preg_match('/^[a-f0-9]{64}$/',$expected))) $errors[]='Update için 64 haneli expected_checksum_sha256 zorunludur.';
    }

    $sources=$doc['sources'] ?? null;
    if (!is_array($sources) || !$sources) $errors[]='sources en az 1 kaynak içermelidir.';
    else {
        $ids=[];
        foreach ($sources as $i=>$source) {
            if (!is_array($source)) { $errors[]='sources['.$i.'] object olmalıdır.'; continue; }
            $sid=trim((string)($source['source_id'] ?? ''));
            if ($sid==='') $errors[]='sources['.$i.'].source_id zorunludur.';
            if ($sid!=='' && in_array($sid,$ids,true)) $errors[]='Duplicate source_id: '.$sid;
            if ($sid!=='') $ids[]=$sid;
            if (trim((string)($source['type'] ?? ''))==='') $errors[]='sources['.$i.'].type zorunludur.';
            if (!array_key_exists('ref',$source) || trim((string)($source['ref'] ?? ''))==='') $warnings[]='sources['.$i.'].ref boş; provenance review gerekir.';
        }
    }

    $tour=$doc['tour'] ?? null;
    if (!is_array($tour)) $errors[]='tour object olmalıdır.';
    else {
        $allowed=['identity','destinations','date','route','itinerary','stays','transport','pricing','services','requirements','media','content','lifecycle','provenance'];
        stti_import_allowed_keys($tour,$allowed,'tour',$errors);
        if (!is_array($tour['identity'] ?? null)) $errors[]='tour.identity zorunlu object olmalıdır.';
        else {
            if (trim((string)($tour['identity']['public_title'] ?? ''))==='') $errors[]='tour.identity.public_title zorunludur.';
            if (trim((string)($tour['identity']['language'] ?? ''))==='') $errors[]='tour.identity.language zorunludur.';
        }
        if (($doc['mode'] ?? '')==='full') {
            foreach (['identity','destinations','date','route','itinerary','stays','transport','pricing','services','requirements','media','content','lifecycle','provenance'] as $section) {
                if (!array_key_exists($section,$tour)) $errors[]='FULL modunda bölüm eksik: tour.'.$section;
            }
        }
        $date=$tour['date'] ?? [];
        if (is_array($date) && (($date['mode'] ?? '')==='exact')) {
            foreach (['start_date','end_date'] as $k) if (empty($date[$k]) || !preg_match('/^\d{4}-\d{2}-\d{2}$/',(string)$date[$k])) $errors[]='Exact tarihte tour.date.'.$k.' YYYY-MM-DD zorunludur.';
            if (!empty($date['start_date']) && !empty($date['end_date']) && $date['end_date'] < $date['start_date']) $errors[]='tour.date.end_date start_date öncesi olamaz.';
        }
        $pricing=$tour['pricing'] ?? [];
        if (is_array($pricing) && in_array(($pricing['type'] ?? ''),['exact','from'],true) && (!isset($pricing['amount']) || $pricing['amount']==='')) $errors[]='Exact/from pricing için tour.pricing.amount zorunludur.';
        $hotels=$tour['stays']['hotels'] ?? [];
        if (is_array($hotels)) foreach ($hotels as $i=>$hotel) if (is_array($hotel) && (($hotel['mode'] ?? '')==='unresolved') && trim((string)($hotel['unresolved_name'] ?? ''))==='') $errors[]='Unresolved hotel #'.($i+1).' için unresolved_name zorunludur.';
    }
    $publication = stti_import_contains_publication_request($doc['tour'] ?? []);
    if ($publication) $errors[]='External publication/indexation alanı yasak: '.$publication;
    return ['errors'=>array_values(array_unique($errors)),'warnings'=>array_values(array_unique($warnings))];
}

function stti_import_primary_source($doc) {
    $sources=is_array($doc['sources'] ?? null)?$doc['sources']:[];
    $primary_id=(string)($doc['tour']['provenance']['primary_source_id'] ?? '');
    foreach ($sources as $source) if (is_array($source) && $primary_id!=='' && (($source['source_id'] ?? '')===$primary_id)) return $source;
    return $sources && is_array($sources[0]) ? $sources[0] : [];
}

function stti_import_clean_scalar($value) {
    if ($value===null) return '';
    if (is_bool($value)) return $value ? '1' : '0';
    if (is_scalar($value)) return (string)$value;
    return '';
}

function stti_import_document_to_form($doc, $existing_row=null) {
    $f=$existing_row ? stti_form_from_row($existing_row) : stti_default_form();
    $tour=is_array($doc['tour'] ?? null)?$doc['tour']:[];
    $identity=is_array($tour['identity'] ?? null)?$tour['identity']:[];
    $dest=is_array($tour['destinations'] ?? null)?$tour['destinations']:[];
    $date=is_array($tour['date'] ?? null)?$tour['date']:[];
    $route=is_array($tour['route'] ?? null)?$tour['route']:[];
    $pricing=is_array($tour['pricing'] ?? null)?$tour['pricing']:[];
    $requirements=is_array($tour['requirements'] ?? null)?$tour['requirements']:[];
    $content=is_array($tour['content'] ?? null)?$tour['content']:[];
    $lifecycle=is_array($tour['lifecycle'] ?? null)?$tour['lifecycle']:[];
    $prov=is_array($tour['provenance'] ?? null)?$tour['provenance']:[];
    $map=[
        'tour_code'=>[$identity,'tour_code'],'public_title'=>[$identity,'public_title'],'short_title'=>[$identity,'short_title'],'slug'=>[$identity,'slug'],'language'=>[$identity,'language'],
        'primary_country'=>[$dest,'primary_country'],'primary_city'=>[$dest,'primary_city'],'departure_city'=>[$dest,'departure_city'],'return_city'=>[$dest,'return_city'],
        'date_mode'=>[$date,'mode'],'date_precision'=>[$date,'precision'],'start_date'=>[$date,'start_date'],'end_date'=>[$date,'end_date'],'month'=>[$date,'month'],
        'route'=>[$route,'summary'],'price_type'=>[$pricing,'type'],'price_amount'=>[$pricing,'amount'],'currency'=>[$pricing,'currency'],'price_basis'=>[$pricing,'basis'],
        'visa_status'=>[$requirements,'visa_status'],'visa_notes'=>[$requirements,'visa_notes'],'short_description'=>[$content,'short_description'],
        'editorial'=>[$lifecycle,'editorial'],'schedule_status'=>[$lifecycle,'schedule'],'availability'=>[$lifecycle,'availability'],
        'source_completeness'=>[$prov,'source_completeness'],
    ];
    foreach ($map as $key=>$pair) if (array_key_exists($pair[1],$pair[0]) && $pair[0][$pair[1]]!==null) $f[$key]=stti_import_clean_scalar($pair[0][$pair[1]]);
    if (array_key_exists('countries',$dest) && is_array($dest['countries'])) $f['countries']=implode(', ',array_map('strval',$dest['countries']));
    if (array_key_exists('cities',$dest) && is_array($dest['cities'])) $f['cities']=implode(', ',array_map('strval',$dest['cities']));
    $json_map=[
        'route_stops_json'=>$route['stops'] ?? null,
        'itinerary_json'=>$tour['itinerary'] ?? null,
        'hotel_relations_json'=>$tour['stays']['hotels'] ?? null,
        'transport_segments_json'=>$tour['transport']['segments'] ?? null,
        'pricing_items_json'=>$pricing['items'] ?? null,
        'included_services_json'=>$tour['services']['included'] ?? null,
        'excluded_services_json'=>$tour['services']['excluded'] ?? null,
        'requirements_items_json'=>$requirements['items'] ?? null,
    ];
    foreach ($json_map as $key=>$value) if (is_array($value)) $f[$key]=wp_json_encode(array_values($value),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    $src=stti_import_primary_source($doc);
    if ($src) {
        if (!empty($src['type'])) $f['source_type']=sanitize_key((string)$src['type']);
        if (!empty($src['ref'])) $f['source_ref']=sanitize_text_field((string)$src['ref']);
        if (empty($f['source_completeness']) && !empty($src['completeness'])) $f['source_completeness']=sanitize_key((string)$src['completeness']);
        if (strpos((string)($src['ref'] ?? ''),'fixture:STT-PREVIEW-')===0) $f['origin_fixture_id']=substr((string)$src['ref'],8);
    }
    if (!empty($doc['target']['stable_id'])) $f['stable_id']=(string)$doc['target']['stable_id'];
    stti_derive_date_facts($f);
    stti_normalize_structured_dates($f);
    stti_sync_itinerary($f);
    $stops=stti_decode_array_field($f['route_stops_json']);
    if ($stops) $f['route']=stti_route_summary_from_stops($stops);
    return $f;
}

function stti_import_dry_run($doc) {
    $validation=stti_import_validate_contract($doc);
    if ($validation['errors']) return ['classification'=>'INVALID','validation'=>$validation,'write_performed'=>false];
    $target=$doc['target'] ?? [];
    $stable_id=$target['stable_id'] ?? null;
    $existing=$stable_id ? stti_get_candidate($stable_id) : null;
    if ($stable_id && !$existing) return ['classification'=>'CONFLICT','validation'=>$validation,'conflict'=>'TARGET_NOT_FOUND','target_stable_id'=>$stable_id,'write_performed'=>false];
    if ($stable_id && !hash_equals((string)$existing['checksum'],(string)($target['expected_checksum_sha256'] ?? ''))) return ['classification'=>'CONFLICT','validation'=>$validation,'conflict'=>'CHECKSUM_MISMATCH','target_stable_id'=>$stable_id,'expected_checksum'=>$target['expected_checksum_sha256'] ?? null,'current_checksum'=>$existing['checksum'],'write_performed'=>false];
    $form=stti_import_document_to_form($doc,$existing);
    $form_validation=stti_validate_form($form);
    $validation['warnings']=array_values(array_unique(array_merge($validation['warnings'],$form_validation['warnings'])));
    if ($form_validation['errors']) {
        $validation['errors']=array_values(array_unique(array_merge($validation['errors'],$form_validation['errors'])));
        return ['classification'=>'INVALID','validation'=>$validation,'write_performed'=>false];
    }
    $proposed_id=$stable_id ?: 'STT-' . str_pad((string)((int)get_option('stti_next_sequence',1)),6,'0',STR_PAD_LEFT);
    $payload=stti_build_payload($form,$proposed_id);
    // Contract media is parsed and surfaced but current canonical write path is intentionally not extended in this dry-run gate.
    $media=is_array($doc['tour']['media']['items'] ?? null)?array_values($doc['tour']['media']['items']):[];
    if ($media) {
        $payload['media']['items']=$media;
        $validation['warnings'][]='Media items dry run payload içinde gösterilir; commit/write v0.6.0 içinde kilitlidir.';
    }
    $json=wp_json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    $checksum=hash('sha256',$json);
    $classification=$existing ? (hash_equals((string)$existing['checksum'],$checksum)?'UNCHANGED':'UPDATE') : 'CREATE';
    return [
        'classification'=>$classification,
        'validation'=>$validation,
        'mode'=>$doc['mode'],
        'target_stable_id'=>$stable_id,
        'proposed_stable_id'=>$proposed_id,
        'expected_checksum'=>$target['expected_checksum_sha256'] ?? null,
        'current_checksum'=>$existing['checksum'] ?? null,
        'proposed_checksum'=>$checksum,
        'canonical_preview'=>$payload,
        'write_performed'=>false,
        'audit_write'=>false,
        'sequence_write'=>false,
        'publication_forced_private'=>true,
    ];
}

function stti_import_read_request_json(&$input_label) {
    $input_label='';
    $text=isset($_POST['stti_json_text']) ? trim(wp_unslash((string)$_POST['stti_json_text'])) : '';
    if ($text!=='') { $input_label='Paste JSON'; return $text; }
    if (!empty($_FILES['stti_json_file']['tmp_name']) && is_uploaded_file($_FILES['stti_json_file']['tmp_name'])) {
        $name=sanitize_file_name((string)($_FILES['stti_json_file']['name'] ?? 'tour.json'));
        $ext=strtolower(pathinfo($name,PATHINFO_EXTENSION));
        if ($ext!=='json') return new WP_Error('stti_import_filetype','Sadece .json dosyası kabul edilir.');
        if ((int)($_FILES['stti_json_file']['size'] ?? 0) > 2*1024*1024) return new WP_Error('stti_import_filesize','JSON dosyası 2 MB sınırını aşıyor.');
        $raw=file_get_contents($_FILES['stti_json_file']['tmp_name']);
        if ($raw===false) return new WP_Error('stti_import_read','JSON dosyası okunamadı.');
        $input_label=$name;
        return $raw;
    }
    return new WP_Error('stti_import_empty','JSON yapıştırın veya .json dosyası seçin.');
}

function stti_import_process_request() {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET')!=='POST' || !isset($_POST['stti_json_dry_run'])) return null;
    if (!current_user_can('manage_options')) return ['classification'=>'INVALID','validation'=>['errors'=>['Unauthorized'],'warnings'=>[]],'write_performed'=>false];
    check_admin_referer('stti_json_dry_run');
    $label=''; $raw=stti_import_read_request_json($label);
    if (is_wp_error($raw)) return ['classification'=>'INVALID','validation'=>['errors'=>[$raw->get_error_message()],'warnings'=>[]],'input_label'=>$label,'write_performed'=>false];
    $doc=json_decode($raw,true);
    if (!is_array($doc)) return ['classification'=>'INVALID','validation'=>['errors'=>['JSON parse hatası: '.json_last_error_msg()],'warnings'=>[]],'input_label'=>$label,'raw_excerpt'=>substr($raw,0,1000),'write_performed'=>false];
    $result=stti_import_dry_run($doc); $result['input_label']=$label; return $result;
}

function stti_import_tone($class) {
    return ['CREATE'=>'blue','UPDATE'=>'gold','UNCHANGED'=>'green','CONFLICT'=>'red','INVALID'=>'red'][$class] ?? 'neutral';
}

function stti_render_import_result($result) {
    if (!$result) return;
    $class=$result['classification'] ?? 'INVALID'; $v=$result['validation'] ?? ['errors'=>[],'warnings'=>[]];
    echo '<section class="stti-panel stti-import-result"><div class="stti-panel-head"><div><span class="stti-kicker">SERVER-SIDE DRY RUN</span><h2>'.esc_html($class).'</h2><p>'.esc_html((string)($result['input_label'] ?? '')).'</p></div><span class="stti-badge stti-badge-'.esc_attr(stti_import_tone($class)).'">NO WRITE</span></div>';
    if (!empty($result['conflict'])) echo '<div class="notice notice-error inline"><p><strong>Conflict:</strong> '.esc_html($result['conflict']).'</p></div>';
    if (!empty($v['errors'])) { echo '<div class="stti-import-messages stti-import-errors"><strong>Errors</strong><ul>'; foreach($v['errors'] as $m) echo '<li>'.esc_html($m).'</li>'; echo '</ul></div>'; }
    if (!empty($v['warnings'])) { echo '<div class="stti-import-messages stti-import-warnings"><strong>Warnings</strong><ul>'; foreach($v['warnings'] as $m) echo '<li>'.esc_html($m).'</li>'; echo '</ul></div>'; }
    echo '<div class="stti-grid stti-grid-3">';
    foreach ([
        'Target'=>($result['target_stable_id'] ?? null) ?: 'NEW',
        'Proposed ID'=>$result['proposed_stable_id'] ?? '—',
        'Mode'=>strtoupper((string)($result['mode'] ?? '—')),
    ] as $label=>$value) echo '<article class="stti-mini-card"><span>'.esc_html($label).'</span><strong>'.esc_html((string)$value).'</strong></article>';
    echo '</div>';
    if (isset($result['current_checksum']) || isset($result['proposed_checksum'])) echo '<div class="stti-import-checksums"><p><span>Current checksum</span><code>'.esc_html((string)($result['current_checksum'] ?? '—')).'</code></p><p><span>Proposed checksum</span><code>'.esc_html((string)($result['proposed_checksum'] ?? '—')).'</code></p></div>';
    if (!empty($result['canonical_preview'])) echo '<details class="stti-json-details"><summary>Normalized Canonical Preview</summary><pre>'.esc_html(wp_json_encode($result['canonical_preview'],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)).'</pre></details>';
    echo '<div class="stti-no-write"><strong>Gate safety</strong><p>Bu sonuç yalnızca validate + normalize + dry run. Candidate, audit, stable ID sequence, public route, sitemap, indexation veya canonical/robots write yapılmadı.</p></div></section>';
}


function stti_render_import(){
    $result=stti_import_process_request();
    ?>
    <section class="stti-grid stti-grid-3">
      <?php stti_import_card('JSON Paste / Upload','ACTIVE · DRY RUN','STTI-TOUR-IMPORT-1.0.0 server-side validate + normalize + classification. Commit locked.'); ?>
      <?php stti_import_card('Google Sheets','FUTURE GATE','Sheet + Apps Script comes after importer contract/runtime acceptance.'); ?>
      <?php stti_import_card('Excel','FUTURE GATE','XLSX → Partial JSON generator is the next separate artifact gate.'); ?>
    </section>
    <section class="stti-panel stti-import-workbench">
      <div class="stti-panel-head"><div><span class="stti-kicker">v0.6.0-B · JSON IMPORT WORKBENCH</span><h2>Validate + Normalize + Dry Run</h2><p>JSON yapıştırın veya .json yükleyin. Bu gate hiçbir Tour verisini kaydetmez.</p></div><span class="stti-badge stti-badge-gold">COMMIT LOCKED</span></div>
      <form method="post" enctype="multipart/form-data" action="<?php echo esc_url(stti_view_url('import')); ?>">
        <?php wp_nonce_field('stti_json_dry_run'); ?>
        <input type="hidden" name="page" value="stti-tour-intelligence"><input type="hidden" name="view" value="import"><input type="hidden" name="stti_json_dry_run" value="1">
        <label class="stti-field"><span>JSON Paste</span><textarea name="stti_json_text" rows="16" spellcheck="false" placeholder='{"import_contract":"STTI-TOUR-IMPORT-1.0.0", ...}'></textarea><small class="stti-field-help">Paste önceliklidir. Boş bırakırsanız alttaki .json dosyası okunur.</small></label>
        <label class="stti-field"><span>veya .json Upload</span><input type="file" name="stti_json_file" accept="application/json,.json"><small class="stti-field-help">Max 2 MB · JSON only</small></label>
        <div class="stti-import-actions"><button type="submit" class="button button-primary button-hero">Validate + Dry Run</button><span>CREATE / UPDATE / UNCHANGED / CONFLICT / INVALID</span></div>
      </form>
    </section>
    <?php stti_render_import_result($result); ?>
    <section class="stti-panel stti-no-write"><div><span class="stti-kicker">READ-ONLY EVIDENCE EXPORT</span><h2>Tüm STTI verisini tek JSON olarak indir</h2><p>Existing evidence export remains read-only. v0.6.0 adds importer gate evidence but does not add an import commit action.</p></div><a class="button button-primary" href="<?php echo esc_url(stti_export_all_url()); ?>">Tüm JSON Evidence İndir</a></section>
    <section class="stti-panel stti-no-write"><strong>Transport safety</strong><p>Import commit/delete/public route generation does not exist in v0.6.0. Dry run does not allocate Stable IDs or write audit events.</p></section>
    <?php
}

function stti_import_card($title,$eyebrow,$text){$active=(strpos($eyebrow,'ACTIVE')!==false);echo '<article class="stti-panel stti-import-card"><span class="stti-kicker">'.esc_html($eyebrow).'</span><h2>'.esc_html($title).'</h2><p>'.esc_html($text).'</p><button class="button" disabled>'.($active?'Active · no commit':'Locked').'</button></article>';}
function stti_render_settings($candidates){$t=stti_tables(); ?><section class="stti-grid stti-grid-2"><article class="stti-panel"><div class="stti-panel-head"><div><span class="stti-kicker">OWNERSHIP</span><h2>System boundaries</h2></div></div><div class="stti-gate-list"><?php stti_gate('Tour canonical data','STTI STRUCTURED PRIVATE','pass'); stti_gate('Hotel facts','HOTEL INTELLIGENCE','pass'); stti_gate('Homepage Culture cards','CURRENT OWNER PRESERVED','active'); stti_gate('Canonical / Robots / Sitemap','UNTOUCHED','pass'); ?></div></article><article class="stti-panel"><div class="stti-panel-head"><div><span class="stti-kicker">PRIVATE STORAGE</span><h2>Runtime state</h2></div></div><div class="stti-gate-list"><?php stti_gate('Candidate rows',(string)count($candidates),'active'); stti_gate('Tour table',$t['tours'],'pass'); stti_gate('Audit table',$t['audit'],'pass'); stti_gate('Private Renderer','RUNTIME ACCEPTED','pass'); stti_gate('Customer Experience','ADMIN-ONLY PILOT','active'); stti_gate('Public Renderer','OFF','off'); stti_gate('Sitemap','OFF','off'); stti_gate('Indexation','OFF','off'); ?></div></article></section><?php }
function stti_empty_state($title,$text){echo '<section class="stti-panel stti-empty"><span class="dashicons dashicons-archive"></span><h2>'.esc_html($title).'</h2><p>'.esc_html($text).'</p></section>';}
