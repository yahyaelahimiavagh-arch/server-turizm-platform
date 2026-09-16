<?php
/**
 * STTI v1.1.6 — Guarded editorial approval step.
 *
 * Approval is an explicit server-side transition from needs_review to approved.
 * It re-validates canonical readiness and only changes editorial lifecycle state.
 * Public route, Hub Master, indexation, sitemap and publication flags are preserved.
 */
if (!defined('ABSPATH')) { exit; }

function stti_v115_approval_url($stable_id) {
    return add_query_arg(array(
        'page'=>'stti-tour-approval',
        'tour'=>sanitize_text_field((string)$stable_id),
    ), admin_url('admin.php'));
}

function stti_v115_review_queue_url() {
    return add_query_arg(array(
        'page'=>'stti-tour-intelligence',
        'view'=>'review',
    ), admin_url('admin.php'));
}

function stti_v115_register_approval_page() {
    // Keep the submenu registered. WordPress uses the registered plugin page hook
    // for authorization/routing; removing it during admin_menu can make direct
    // admin.php?page=stti-tour-approval requests fail before our callback runs.
    add_submenu_page(
        'stti-tour-intelligence',
        'Tur Onayı',
        'Tur Onayı',
        'manage_options',
        'stti-tour-approval',
        'stti_v115_render_approval_page'
    );
}
add_action('admin_menu', 'stti_v115_register_approval_page', 30);

function stti_v116_hide_approval_menu_link() {
    // Presentation-only hiding: keep the WordPress page registration intact so
    // direct approval URLs remain authorized and routable.
    echo '<style>#toplevel_page_stti-tour-intelligence .wp-submenu a[href*="page=stti-tour-approval"]{display:none!important}#toplevel_page_stti-tour-intelligence .wp-submenu li:has(a[href*="page=stti-tour-approval"]){display:none!important}</style>';
}
add_action('admin_head', 'stti_v116_hide_approval_menu_link', 999);

function stti_v115_approval_is_screen() {
    if (!is_admin()) return false;
    $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
    return $page === 'stti-tour-approval';
}

function stti_v115_approval_assets() {
    if (!stti_v115_approval_is_screen()) return;
    wp_enqueue_style('stti-admin', STTI_URL . 'assets/admin.css', array(), STTI_VERSION);
    wp_enqueue_style('stti-approval-v115', STTI_URL . 'assets/approval-v115.css', array('stti-admin'), STTI_RELEASE_VERSION);
}
add_action('admin_enqueue_scripts', 'stti_v115_approval_assets', 150);

function stti_v115_publication_state($payload) {
    $publication = is_array($payload['publication'] ?? null) ? $payload['publication'] : array();
    return array(
        'public_route'=>!empty($publication['public_route']),
        'hub_visible'=>!empty($publication['hub_visible']),
        'indexable'=>!empty($publication['indexable']),
        'sitemap'=>!empty($publication['sitemap']),
        'homepage_visible'=>!empty($publication['homepage_visible']),
        'hub_master'=>function_exists('stti_v110_hub_enabled') ? stti_v110_hub_enabled() : false,
    );
}

function stti_v115_render_state_chip($label, $enabled) {
    echo '<div class="stti-v115-state"><span>' . esc_html($label) . '</span><b class="' . ($enabled ? 'is-on' : 'is-off') . '">' . ($enabled ? 'AÇIK' : 'KAPALI') . '</b></div>';
}

function stti_v115_render_approval_page() {
    if (!current_user_can('manage_options')) wp_die('Unauthorized');

    $stable_id = isset($_GET['tour']) ? sanitize_text_field(wp_unslash($_GET['tour'])) : '';
    $row = $stable_id !== '' ? stti_get_candidate($stable_id) : null;
    if (!$row) wp_die('Unknown STT stable ID. Fail closed.');

    $payload = json_decode((string)($row['payload'] ?? ''), true);
    if (!is_array($payload)) $payload = array();
    $summary = function_exists('stti_v113_review_queue_summary') ? stti_v113_review_queue_summary($row) : array('approvalReady'=>false,'blockers'=>array('Review summary unavailable.'));
    $publication = stti_v115_publication_state($payload);
    $already_approved = (($row['editorial'] ?? '') === 'approved');
    $approved_notice = isset($_GET['approved']) ? sanitize_key(wp_unslash($_GET['approved'])) : '';
    $error = isset($_GET['approval_error']) ? sanitize_key(wp_unslash($_GET['approval_error'])) : '';

    echo '<div class="wrap stti-v115-wrap">';
    echo '<div class="stti-v115-header"><div><span>STTI · EDITORIAL GATE</span><h1>Tur Onayı</h1><p>Bu adım yalnız editorial durumu değiştirir. Public / SEO / Hub kilitleri ayrı kalır.</p></div><a class="button" href="' . esc_url(stti_v115_review_queue_url()) . '">Review Queue’ya Dön</a></div>';

    if ($approved_notice !== '') echo '<div class="notice notice-success inline"><p><strong>Editorial approval kaydedildi.</strong> Public / SEO / Hub durumu değiştirilmedi.</p></div>';
    if ($error === 'not_ready') echo '<div class="notice notice-error inline"><p>Tur artık onaya hazır değil. Readiness yeniden kontrol edildi ve işlem fail-closed durduruldu.</p></div>';
    if ($error === 'confirm') echo '<div class="notice notice-error inline"><p>Editorial approval için açık onay kutusu zorunludur.</p></div>';
    if ($error === 'state') echo '<div class="notice notice-error inline"><p>Bu lifecycle durumundan approval yapılamaz.</p></div>';

    echo '<section class="stti-v115-card">';
    echo '<div class="stti-v115-title"><div><span>' . esc_html($stable_id) . '</span><h2>' . esc_html((string)($row['public_title'] ?? 'Tour')) . '</h2></div><b class="' . ($already_approved ? 'is-approved' : (!empty($summary['approvalReady']) ? 'is-ready' : 'is-blocked')) . '">' . ($already_approved ? 'ONAYLI' : (!empty($summary['approvalReady']) ? 'ONAYA HAZIR' : 'EKSİKLER VAR')) . '</b></div>';

    echo '<div class="stti-v115-grid">';
    echo '<div><span>Editorial</span><strong>' . esc_html(strtoupper((string)($row['editorial'] ?? 'needs_review'))) . '</strong></div>';
    echo '<div><span>Schedule</span><strong>' . esc_html(strtoupper((string)($row['schedule_status'] ?? ''))) . '</strong></div>';
    echo '<div><span>Availability</span><strong>' . esc_html(strtoupper((string)($row['availability'] ?? ''))) . '</strong></div>';
    echo '<div><span>Hard blocker</span><strong>' . esc_html((string)($summary['hardBlockerCount'] ?? 0)) . '</strong></div>';
    echo '</div>';

    echo '<h3>Publication kilitleri</h3><div class="stti-v115-states">';
    stti_v115_render_state_chip('Public route', $publication['public_route']);
    stti_v115_render_state_chip('Hub görünürlüğü', $publication['hub_visible']);
    stti_v115_render_state_chip('Indexation', $publication['indexable']);
    stti_v115_render_state_chip('Sitemap', $publication['sitemap']);
    stti_v115_render_state_chip('Homepage', $publication['homepage_visible']);
    stti_v115_render_state_chip('Hub Master', $publication['hub_master']);
    echo '</div>';

    if (!empty($summary['blockers'])) {
        echo '<div class="stti-v115-blockers"><h3>Onay blockerları</h3><ul>';
        foreach ((array)$summary['blockers'] as $blocker) echo '<li>' . esc_html((string)$blocker) . '</li>';
        echo '</ul></div>';
    }

    if ($already_approved) {
        echo '<div class="stti-v115-success"><strong>Bu tur editorial olarak onaylı.</strong><span>Approval tekrar çalıştırılmaz; publication kilitleri bu ekrandan değiştirilemez.</span></div>';
    } elseif (!empty($summary['approvalReady']) && (($row['editorial'] ?? '') === 'needs_review')) {
        echo '<form class="stti-v115-form" method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        echo '<input type="hidden" name="action" value="stti_v115_approve_candidate">';
        echo '<input type="hidden" name="stable_id" value="' . esc_attr($stable_id) . '">';
        wp_nonce_field('stti_v115_approve_candidate_' . $stable_id);
        echo '<label><input type="checkbox" name="confirm_editorial" value="1" required> <span><b>Editorial approval’ı onaylıyorum.</b><small>Bu işlem yalnız NEEDS_REVIEW → APPROVED geçişini yapar; Public route, Hub Master, Indexation ve Sitemap değişmez.</small></span></label>';
        echo '<button type="submit" class="button button-primary button-hero">Tur’u Onayla</button>';
        echo '</form>';
    } else {
        echo '<div class="stti-v115-blocked"><strong>Approval kapalı.</strong><span>Readiness blockerları kapanmadan lifecycle değiştirilemez.</span></div>';
    }

    echo '<div class="stti-v115-actions"><a class="button" href="' . esc_url(stti_view_url('editor', array('tour'=>$stable_id))) . '">Tur Bilgilerini Düzenle</a>';
    if (function_exists('stti_customer_preview_url')) echo '<a class="button" href="' . esc_url(stti_customer_preview_url($stable_id)) . '">Müşteri Önizleme</a>';
    echo '</div></section></div>';
}

function stti_v115_handle_approve_candidate() {
    if (!current_user_can('manage_options')) wp_die('Unauthorized');

    $stable_id = isset($_POST['stable_id']) ? sanitize_text_field(wp_unslash($_POST['stable_id'])) : '';
    if ($stable_id === '') wp_die('Stable ID required.');
    check_admin_referer('stti_v115_approve_candidate_' . $stable_id);

    $redirect = stti_v115_approval_url($stable_id);
    if (!isset($_POST['confirm_editorial']) || (string)wp_unslash($_POST['confirm_editorial']) !== '1') {
        wp_safe_redirect(add_query_arg('approval_error', 'confirm', $redirect)); exit;
    }

    $row = stti_get_candidate($stable_id);
    if (!$row) wp_die('Unknown STT stable ID. Fail closed.');
    if (($row['editorial'] ?? '') === 'approved') {
        wp_safe_redirect(add_query_arg('approved', 'already', $redirect)); exit;
    }
    if (($row['editorial'] ?? '') !== 'needs_review') {
        wp_safe_redirect(add_query_arg('approval_error', 'state', $redirect)); exit;
    }

    $summary = function_exists('stti_v113_review_queue_summary') ? stti_v113_review_queue_summary($row) : array('approvalReady'=>false);
    if (empty($summary['approvalReady'])) {
        wp_safe_redirect(add_query_arg('approval_error', 'not_ready', $redirect)); exit;
    }

    $payload = json_decode((string)($row['payload'] ?? ''), true);
    if (!is_array($payload)) wp_die('Canonical payload is invalid. Fail closed.');
    $before = $payload;
    $publication_before = is_array($payload['publication'] ?? null) ? $payload['publication'] : array();

    if (!isset($payload['lifecycle']) || !is_array($payload['lifecycle'])) $payload['lifecycle'] = array();
    $payload['lifecycle']['editorial'] = 'approved';

    // Approval is not publication. Preserve the publication envelope byte-for-byte at array level.
    $publication_after = is_array($payload['publication'] ?? null) ? $payload['publication'] : array();
    if ($publication_after !== $publication_before) wp_die('Publication state mutation detected. Fail closed.');

    $json = wp_json_encode($payload, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    $checksum = hash('sha256', $json);
    $now = current_time('mysql');
    global $wpdb; $t = stti_tables();
    $updated = $wpdb->update(
        $t['tours'],
        array('editorial'=>'approved','payload'=>$json,'checksum'=>$checksum,'updated_at'=>$now),
        array('stable_id'=>$stable_id),
        array('%s','%s','%s','%s'),
        array('%s')
    );
    if ($updated === false) wp_die('Editorial approval write failed.');

    stti_audit_event($stable_id, 'candidate_approved', $before, $payload);
    wp_safe_redirect(add_query_arg('approved', '1', $redirect)); exit;
}
add_action('admin_post_stti_v115_approve_candidate', 'stti_v115_handle_approve_candidate');
