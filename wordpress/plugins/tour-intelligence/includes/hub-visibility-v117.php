<?php
/**
 * STTI v1.1.7 — Explicit per-Tour Hub visibility gate.
 *
 * Editorial approval is not publication. A Tour may appear in the Culture Tours
 * Hub only when it is editorially eligible AND publication.hub_visible === true.
 */
if (!defined('ABSPATH')) { exit; }

function stti_v117_hub_visible($payload) {
    $publication = is_array($payload['publication'] ?? null) ? $payload['publication'] : array();
    return !empty($publication['hub_visible']);
}

function stti_v117_hub_records($today=null) {
    $out = array();
    foreach (stti_get_candidates() as $row) {
        $payload = stti_v110_payload($row);
        if (!stti_v110_record_is_eligible($row, $payload, $today)) continue;
        if (!stti_v117_hub_visible($payload)) continue;
        $out[] = stti_v110_card_model($row, $payload);
    }
    usort($out, static function($a,$b){
        if ($a['dated'] !== $b['dated']) return $a['dated'] ? -1 : 1;
        if ($a['dated'] && $a['start_date'] !== $b['start_date']) return strcmp($a['start_date'], $b['start_date']);
        return strcmp((string)$b['updated_at'], (string)$a['updated_at']);
    });
    return $out;
}

function stti_v117_replace_hub_content($content) {
    if (is_admin() || !stti_v110_hub_enabled() || !stti_v110_is_hub_request()) return $content;
    if (function_exists('is_main_query') && !is_main_query()) return $content;
    if (function_exists('in_the_loop') && !in_the_loop()) return $content;
    return stti_v110_render_hub(stti_v117_hub_records());
}
add_filter('the_content', 'stti_v117_replace_hub_content', 100);

function stti_v117_is_hub_admin_screen() {
    if (!is_admin()) return false;
    $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
    return $page === 'stti-tour-hub-v110';
}

function stti_v117_render_hub_visibility_panel() {
    if (!stti_v117_is_hub_admin_screen() || !current_user_can('manage_options')) return;

    $approved = array();
    foreach (stti_get_candidates() as $row) {
        $payload = stti_v110_payload($row);
        if (!stti_v110_record_is_eligible($row, $payload)) continue;
        $approved[] = array(
            'stable_id'=>(string)($row['stable_id'] ?? ''),
            'title'=>(string)($row['public_title'] ?? 'Tour'),
            'visible'=>stti_v117_hub_visible($payload),
        );
    }
    $visible_count = count(stti_v117_hub_records());
    ?>
    <div class="notice notice-info" style="padding:14px 16px;margin-top:18px;max-width:920px">
      <h2 style="margin-top:0">STTI v1.1.7 · Hub görünürlüğü</h2>
      <p><strong>Gerçekte Hub'da gösterilecek tur:</strong> <?php echo esc_html((string)$visible_count); ?></p>
      <p>Editorial <code>approved</code> olmak tek başına yayın değildir. Her tur ayrıca açıkça <code>Hub görünürlüğü = AÇIK</code> yapılmalıdır.</p>
      <?php if (!$approved): ?>
        <p>Şu anda editorial olarak uygun tur yok.</p>
      <?php else: ?>
        <table class="widefat striped" style="max-width:900px">
          <thead><tr><th>Stable ID</th><th>Tur</th><th>Hub görünürlüğü</th><th>İşlem</th></tr></thead>
          <tbody>
          <?php foreach ($approved as $item): ?>
            <tr>
              <td><code><?php echo esc_html($item['stable_id']); ?></code></td>
              <td><?php echo esc_html($item['title']); ?></td>
              <td><strong><?php echo $item['visible'] ? 'AÇIK' : 'KAPALI'; ?></strong></td>
              <td>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin:0">
                  <input type="hidden" name="action" value="stti_v117_set_hub_visibility" />
                  <input type="hidden" name="stable_id" value="<?php echo esc_attr($item['stable_id']); ?>" />
                  <input type="hidden" name="hub_visible" value="<?php echo $item['visible'] ? '0' : '1'; ?>" />
                  <?php wp_nonce_field('stti_v117_hub_visibility_' . $item['stable_id']); ?>
                  <button class="button <?php echo $item['visible'] ? '' : 'button-primary'; ?>" type="submit">
                    <?php echo $item['visible'] ? 'Hub’dan Gizle' : 'Hub’da Göster'; ?>
                  </button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
    <?php
}
add_action('admin_notices', 'stti_v117_render_hub_visibility_panel', 50);

function stti_v117_handle_set_hub_visibility() {
    if (!current_user_can('manage_options')) wp_die('Yetki yok.');
    $stable_id = isset($_POST['stable_id']) ? sanitize_text_field(wp_unslash($_POST['stable_id'])) : '';
    if ($stable_id === '') wp_die('Stable ID required.');
    check_admin_referer('stti_v117_hub_visibility_' . $stable_id);

    $target = isset($_POST['hub_visible']) && (string)wp_unslash($_POST['hub_visible']) === '1';
    $row = stti_get_candidate($stable_id);
    if (!$row) wp_die('Unknown STT stable ID. Fail closed.');

    $payload = json_decode((string)($row['payload'] ?? ''), true);
    if (!is_array($payload)) wp_die('Canonical payload invalid. Fail closed.');

    if ($target && !stti_v110_record_is_eligible($row, $payload)) {
        wp_die('Tour is not editorially eligible for Hub visibility.');
    }

    $before = $payload;
    if (!isset($payload['publication']) || !is_array($payload['publication'])) $payload['publication'] = array();
    $payload['publication']['hub_visible'] = $target;

    $json = wp_json_encode($payload, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    $checksum = hash('sha256', $json);
    $now = current_time('mysql');
    global $wpdb; $t = stti_tables();
    $updated = $wpdb->update(
        $t['tours'],
        array('payload'=>$json,'checksum'=>$checksum,'updated_at'=>$now),
        array('stable_id'=>$stable_id),
        array('%s','%s','%s'),
        array('%s')
    );
    if ($updated === false) wp_die('Hub visibility write failed.');

    stti_audit_event($stable_id, $target ? 'hub_visibility_enabled' : 'hub_visibility_disabled', $before, $payload);
    wp_safe_redirect(add_query_arg(array('page'=>'stti-tour-hub-v110','hub_visibility_updated'=>'1'),admin_url('admin.php')));
    exit;
}
add_action('admin_post_stti_v117_set_hub_visibility', 'stti_v117_handle_set_hub_visibility');
