<?php
/**
 * STTI v1.2.0 — Controlled dynamic Tour detail routes + premium presentation polish.
 *
 * Reuses the accepted Complete Customer Renderer model for individual customer
 * detail pages. Public route exposure remains an explicit per-Tour gate plus a
 * separate detail-route master. This branch deliberately keeps indexation,
 * sitemap, schema and canonical output hard OFF.
 */
if (!defined('ABSPATH')) { exit; }

require_once __DIR__ . '/detail-route-v119-template.php';

function stti_v119_detail_master_option() { return 'stti_v119_detail_master'; }
function stti_v119_detail_master_enabled() { return get_option(stti_v119_detail_master_option(), '0') === '1'; }

function stti_v119_detail_flag($payload) {
    $publication = is_array($payload['publication'] ?? null) ? $payload['publication'] : array();
    return !empty($publication['detail_route']);
}

function stti_v119_detail_path($stable_id) {
    $stable_id = strtolower(trim((string)$stable_id));
    if (!preg_match('/^stt-\d{6}$/', $stable_id)) return '';
    return '/turlar/' . $stable_id . '/';
}

function stti_v119_detail_url($stable_id) {
    $path = stti_v119_detail_path($stable_id);
    return $path === '' ? '' : home_url($path);
}

function stti_v119_detail_readiness($row) {
    $reasons = array();
    if (!is_array($row)) return array('ready'=>false,'reasons'=>array('candidate_missing'),'payload'=>array(),'model'=>array());
    $stable_id = (string)($row['stable_id'] ?? '');
    if (!preg_match('/^STT-\d{6}$/', $stable_id)) $reasons[] = 'stable_id_invalid';
    $payload = stti_v110_payload($row);
    if (!$payload) $reasons[] = 'payload_missing';
    if ((string)($row['editorial'] ?? '') !== 'approved') $reasons[] = 'editorial_not_approved';
    $identity = is_array($payload['identity'] ?? null) ? $payload['identity'] : array();
    if (trim((string)($identity['public_title'] ?? '')) === '') $reasons[] = 'title_missing';
    if (function_exists('stti_v110_record_is_eligible') && !stti_v110_record_is_eligible($row, $payload)) $reasons[] = 'tour_not_hub_eligible';
    $model = function_exists('stti_v080_renderer_model') ? stti_v080_renderer_model($payload, $stable_id, (string)($row['checksum'] ?? '')) : array();
    if (($model['ready'] ?? false) !== true) $reasons[] = 'renderer_not_ready';
    if (count((array)($model['route_stops'] ?? array())) < 1) $reasons[] = 'route_missing';
    return array('ready'=>empty($reasons),'reasons'=>array_values(array_unique($reasons)),'payload'=>$payload,'model'=>$model);
}

function stti_v119_detail_surface($stable_id) {
    $row = stti_get_candidate((string)$stable_id);
    $readiness = stti_v119_detail_readiness($row);
    $payload = $readiness['payload'];
    $enabled = $row && stti_v119_detail_master_enabled() && stti_v119_detail_flag($payload) && $readiness['ready'];
    return array(
        'contract'=>'STTI-DYNAMIC-DETAIL-1.2.0',
        'stable_id'=>(string)$stable_id,
        'public_url'=>stti_v119_detail_url($stable_id),
        'route'=>(bool)$enabled,
        'content_ready'=>(bool)$readiness['ready'],
        'readiness_reasons'=>$readiness['reasons'],
        'row'=>$row,
        'payload'=>$payload,
        'model'=>$readiness['model'],
        'indexation'=>false,
        'sitemap'=>false,
        'schema'=>false,
        'canonical'=>false,
    );
}

function stti_v119_detail_url_if_public($stable_id) {
    $surface = stti_v119_detail_surface($stable_id);
    return !empty($surface['route']) ? (string)$surface['public_url'] : '';
}

function stti_v119_request_stable_id() {
    if (is_admin()) return '';
    $path = function_exists('stti_v110_request_path') ? stti_v110_request_path() : '/';
    if (!preg_match('#^/turlar/(stt-\d{6})/$#i', $path, $m)) return '';
    return strtoupper($m[1]);
}

function stti_v119_robots_filter($robots) {
    $stable_id = stti_v119_request_stable_id();
    if ($stable_id === '' || !stti_v119_detail_surface($stable_id)['route']) return $robots;
    foreach (array('index','noindex','follow','nofollow','noarchive') as $key) unset($robots[$key]);
    $robots['noindex'] = true;
    $robots['follow'] = true;
    $robots['noarchive'] = true;
    return $robots;
}

function stti_v119_prepare_detail_surface() {
    remove_action('wp_head', 'rel_canonical');
    add_filter('wp_robots', 'stti_v119_robots_filter', 999);
    add_filter('wpseo_canonical', '__return_false', 999);
    add_filter('wpseo_json_ld_output', '__return_false', 999);
    add_filter('rank_math/frontend/canonical', '__return_false', 999);
    add_filter('rank_math/json_ld', '__return_empty_array', 999);
    add_filter('body_class', static function($classes){
        $classes = array_values(array_diff((array)$classes, array('error404')));
        $classes[] = 'stti-v119-detail-route';
        // Reuse the accepted v0.5.x/v0.6.5 premium Customer Preview visual skin.
        // Presentation-only: reuse accepted premium Customer Preview visual language.
        // Access/SEO/publication semantics remain governed by the existing hard gates.
        $classes[] = 'stti-customer-preview-mode';
        $classes[] = 'stti-v120-premium-detail';
        return array_values(array_unique($classes));
    }, 999);
}

function stti_v120_enqueue_detail_assets($map) {
    // Keep the accepted v0.8 renderer dependencies, but replace its static
    // canonical map script with the v1.2.0 presentation-only animated renderer.
    stti_v080_enqueue_assets($map);
    wp_dequeue_script('stti-canonical-map-v080');

    // Reuse the accepted v0.6.5 live-theme shell alignment logic. It will not
    // geocode because STTI_CX_MAP is intentionally not localized on this route.
    wp_enqueue_script(
        'stti-v120-premium-shell',
        STTI_URL . 'assets/customer-preview.js',
        array(),
        STTI_RELEASE_VERSION,
        true
    );
    wp_enqueue_style(
        'stti-v120-detail-polish',
        STTI_URL . 'assets/detail-route-v120.css',
        array('stti-customer-renderer-v080'),
        STTI_RELEASE_VERSION
    );
    wp_enqueue_script(
        'stti-v120-detail-map',
        STTI_URL . 'assets/detail-route-v120.js',
        array('stti-leaflet-v080','stti-customer-shell-v080'),
        STTI_RELEASE_VERSION,
        true
    );
    wp_localize_script('stti-v120-detail-map', 'STTI_V120_DETAIL_MAP', is_array($map) ? $map : array());
}

function stti_v119_maybe_render_detail_route() {
    $stable_id = stti_v119_request_stable_id();
    if ($stable_id === '') return;
    $surface = stti_v119_detail_surface($stable_id);
    if (empty($surface['route'])) return;
    global $wp_query;
    if ($wp_query) $wp_query->is_404 = false;
    status_header(200);
    nocache_headers();
    stti_v119_prepare_detail_surface();
    stti_v120_enqueue_detail_assets($surface['model']['map']);
    stti_v119_render_detail_template($surface);
    exit;
}
add_action('template_redirect', 'stti_v119_maybe_render_detail_route', 2);

function stti_v119_hub_records() {
    $records = function_exists('stti_v117_hub_records') ? stti_v117_hub_records() : array();
    foreach ($records as &$card) {
        $detail = stti_v119_detail_url_if_public((string)($card['stable_id'] ?? ''));
        if ($detail !== '') {
            $card['detail_url'] = $detail;
            $card['cta_url'] = $detail;
            $card['cta_label'] = 'Turu İncele';
        }
    }
    unset($card);
    return $records;
}

function stti_v119_hub_html() {
    return function_exists('stti_v110_render_hub') ? stti_v110_render_hub(stti_v119_hub_records()) : '';
}

function stti_v119_hub_admin_screen() {
    if (!is_admin()) return false;
    $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
    return $page === 'stti-tour-hub-v110';
}

function stti_v119_render_detail_controls() {
    if (!stti_v119_hub_admin_screen() || !current_user_can('manage_options')) return;
    $master = stti_v119_detail_master_enabled();
    $rows = array();
    foreach (stti_get_candidates() as $row) {
        $readiness = stti_v119_detail_readiness($row);
        if (!$readiness['ready']) continue;
        $payload = $readiness['payload'];
        $stable_id = (string)($row['stable_id'] ?? '');
        $rows[] = array(
            'stable_id'=>$stable_id,
            'title'=>(string)($row['public_title'] ?? 'Tour'),
            'enabled'=>stti_v119_detail_flag($payload),
            'url'=>stti_v119_detail_url($stable_id),
        );
    }
    ?>
    <div class="notice notice-info" style="padding:14px 16px;margin-top:18px;max-width:920px">
      <h2 style="margin-top:0">STTI v1.2.0 · Premium Dinamik Tur Detay Sayfası</h2>
      <p>Bu katman kabul edilmiş Complete Customer Renderer veri modelini ve v0.6.5 premium Customer Preview görsel dilini kullanır. <strong>Indexation / Sitemap / Schema / Canonical bu sürümde HARD OFF</strong>.</p>
      <p><strong>Detay Master:</strong> <?php echo $master ? 'AÇIK' : 'KAPALI'; ?></p>
      <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin:10px 0 18px">
        <input type="hidden" name="action" value="stti_v119_set_detail_master" />
        <input type="hidden" name="detail_master" value="<?php echo $master ? '0' : '1'; ?>" />
        <?php wp_nonce_field('stti_v119_detail_master'); ?>
        <button type="submit" class="button <?php echo $master ? '' : 'button-primary'; ?>"><?php echo $master ? 'Detay Master’ı Kapat' : 'Detay Master’ı Aç'; ?></button>
      </form>
      <?php if (!$rows): ?>
        <p>Detay renderer için hazır approved tur yok.</p>
      <?php else: ?>
      <table class="widefat striped" style="max-width:900px">
        <thead><tr><th>Stable ID</th><th>Tur</th><th>Detay Rotası</th><th>URL</th><th>İşlem</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $item): ?>
          <tr>
            <td><code><?php echo esc_html($item['stable_id']); ?></code></td>
            <td><?php echo esc_html($item['title']); ?></td>
            <td><strong><?php echo $item['enabled'] ? 'AÇIK' : 'KAPALI'; ?></strong></td>
            <td><code><?php echo esc_html($item['url']); ?></code></td>
            <td><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin:0">
              <input type="hidden" name="action" value="stti_v119_set_detail_route" />
              <input type="hidden" name="stable_id" value="<?php echo esc_attr($item['stable_id']); ?>" />
              <input type="hidden" name="detail_route" value="<?php echo $item['enabled'] ? '0' : '1'; ?>" />
              <?php wp_nonce_field('stti_v119_detail_route_' . $item['stable_id']); ?>
              <button type="submit" class="button <?php echo $item['enabled'] ? '' : 'button-primary'; ?>"><?php echo $item['enabled'] ? 'Detay Rotasını Kapat' : 'Detay Rotasını Aç'; ?></button>
            </form></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>
    <?php
}
add_action('admin_notices', 'stti_v119_render_detail_controls', 60);

function stti_v119_handle_detail_master() {
    if (!current_user_can('manage_options')) wp_die('Yetki yok.');
    check_admin_referer('stti_v119_detail_master');
    $target = isset($_POST['detail_master']) && (string)wp_unslash($_POST['detail_master']) === '1';
    update_option(stti_v119_detail_master_option(), $target ? '1' : '0', false);
    wp_safe_redirect(add_query_arg(array('page'=>'stti-tour-hub-v110','detail_master_updated'=>'1'), admin_url('admin.php')));
    exit;
}
add_action('admin_post_stti_v119_set_detail_master', 'stti_v119_handle_detail_master');

function stti_v119_handle_detail_route() {
    if (!current_user_can('manage_options')) wp_die('Yetki yok.');
    $stable_id = isset($_POST['stable_id']) ? sanitize_text_field(wp_unslash($_POST['stable_id'])) : '';
    if ($stable_id === '') wp_die('Stable ID required.');
    check_admin_referer('stti_v119_detail_route_' . $stable_id);
    $target = isset($_POST['detail_route']) && (string)wp_unslash($_POST['detail_route']) === '1';
    $row = stti_get_candidate($stable_id);
    if (!$row) wp_die('Unknown STT stable ID. Fail closed.');
    $readiness = stti_v119_detail_readiness($row);
    if ($target && !$readiness['ready']) wp_die('Tour detail renderer is not ready. Fail closed.');
    $payload = $readiness['payload'];
    $before = $payload;
    if (!isset($payload['publication']) || !is_array($payload['publication'])) $payload['publication'] = array();
    $payload['publication']['detail_route'] = $target;
    $json = wp_json_encode($payload, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    $checksum = hash('sha256', $json);
    $now = current_time('mysql');
    global $wpdb; $t = stti_tables();
    $updated = $wpdb->update($t['tours'], array('payload'=>$json,'checksum'=>$checksum,'updated_at'=>$now), array('stable_id'=>$stable_id), array('%s','%s','%s'), array('%s'));
    if ($updated === false) wp_die('Detail route write failed.');
    stti_audit_event($stable_id, $target ? 'detail_route_enabled' : 'detail_route_disabled', $before, $payload);
    wp_safe_redirect(add_query_arg(array('page'=>'stti-tour-hub-v110','detail_route_updated'=>'1'), admin_url('admin.php')));
    exit;
}
add_action('admin_post_stti_v119_set_detail_route', 'stti_v119_handle_detail_route');
