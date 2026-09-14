<?php
/** Admin-only release controls for STTI v1.0.0. */
if(!defined('ABSPATH')){exit;}
function stti_v100_register_public_pilot_page(){
    add_submenu_page('stti-tour-intelligence','Controlled Public Pilot','Public Pilot','manage_options','stti-v100-public-pilot','stti_v100_render_public_pilot_admin');
}
function stti_v100_render_public_pilot_admin(){
    if(!current_user_can('manage_options'))return;$surface=stti_v100_surface_state();$g=$surface['gates'];
    echo '<div class="wrap"><h1>STTI v1.0 — Controlled Public Pilot</h1>';
    echo '<p><strong>Allowlist:</strong> '.esc_html($surface['stable_id']).' · <code>'.esc_html(stti_v100_public_config()['path']).'</code></p>';
    echo '<p><strong>Content readiness:</strong> '.($surface['content_ready']?'READY':'BLOCKED').'</p>';
    if(!$surface['content_ready'])echo '<p><strong>Blockers:</strong> '.esc_html(implode(', ',$surface['readiness_reasons'])).'</p>';
    echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'">';wp_nonce_field('stti_v100_public_pilot');
    echo '<input type="hidden" name="action" value="stti_v100_save_public_pilot"><table class="form-table"><tbody>';
    $labels=array('master'=>'Public Master','route'=>'Exact public route','indexation'=>'Indexation','canonical'=>'Canonical','schema'=>'Schema','sitemap'=>'Sitemap');
    foreach($labels as $key=>$label)echo '<tr><th scope="row">'.esc_html($label).'</th><td><label><input type="checkbox" name="gates['.esc_attr($key).']" value="1" '.checked(!empty($g[$key]),true,false).'> ON</label></td></tr>';
    echo '</tbody></table>';submit_button('Save controlled gates');echo '</form><p><strong>Fast rollback:</strong> turn Public Master OFF.</p></div>';
}
function stti_v100_handle_save_public_pilot(){
    if(!current_user_can('manage_options'))wp_die('Unauthorized');check_admin_referer('stti_v100_public_pilot');
    $raw=isset($_POST['gates'])&&is_array($_POST['gates'])?wp_unslash($_POST['gates']):array();$requested=array();
    foreach(array('master','route','indexation','sitemap','schema','canonical') as $key)$requested[$key]=!empty($raw[$key]);
    $result=stti_v100_update_gates($requested,get_current_user_id(),true);if(is_wp_error($result))wp_die(esc_html($result->get_error_message()));
    wp_safe_redirect(add_query_arg(array('page'=>'stti-v100-public-pilot'),admin_url('admin.php')));exit;
}
