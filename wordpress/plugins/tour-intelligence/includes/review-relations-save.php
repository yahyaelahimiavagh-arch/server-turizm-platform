<?php
/** v0.7 private manual-save handler. AI Completion/import dry-run handlers are untouched. */
function stti_v070_handle_save_candidate() {
    if (!function_exists('current_user_can') || !current_user_can('manage_options')) wp_die('Unauthorized');
    check_admin_referer('stti_save_candidate');
    $f = stti_sanitize_form($_POST);
    $validation = stti_validate_form($f);

    global $wpdb;
    $t = stti_tables();
    $is_new = $f['stable_id'] === '';
    if ($is_new && !empty($f['origin_fixture_id'])) {
        $already = stti_find_candidate_by_origin_fixture($f['origin_fixture_id']);
        if ($already) {
            $url = add_query_arg(array('page'=>'stti-tour-intelligence','view'=>'editor','tour'=>$already['stable_id'],'stti_saved'=>'fixture_exists'), admin_url('admin.php'));
            wp_safe_redirect($url); exit;
        }
    }
    $existing = $is_new ? null : stti_get_candidate($f['stable_id']);
    if (!$is_new && !$existing) wp_die('Unknown STT stable ID. Fail closed.');
    $existing_payload = stti_v070_existing_payload($existing);

    // Validate relation graph before stable-ID allocation so an invalid new row causes no sequence write.
    $preview_id = $is_new ? 'STT-000000' : $f['stable_id'];
    $payload = stti_build_payload($f, $preview_id);
    $raw_variants = array_key_exists('route_variants_json', $_POST) ? $_POST['route_variants_json'] : null;
    $payload = stti_v070_enrich_payload($payload, $existing_payload, $raw_variants);
    $relation_review = stti_v070_relation_review($payload);
    $validation['errors'] = array_values(array_unique(array_merge($validation['errors'], $relation_review['errors'])));
    $validation['warnings'] = array_values(array_unique(array_merge($validation['warnings'], $relation_review['warnings'], $relation_review['blockers'])));

    if ($validation['errors']) {
        $url = add_query_arg(array('page'=>'stti-tour-intelligence','view'=>'editor','tour'=>$f['stable_id'],'stti_error'=>rawurlencode(implode(' | ', $validation['errors']))), admin_url('admin.php'));
        wp_safe_redirect($url); exit;
    }

    $stable_id = $is_new ? stti_allocate_stable_id() : $f['stable_id'];
    if (is_wp_error($stable_id)) wp_die(esc_html($stable_id->get_error_message()));
    $payload['stable_id'] = $stable_id;
    $json = wp_json_encode($payload, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    $checksum = hash('sha256', $json);
    $now = current_time('mysql');

    if ($is_new) {
        $wpdb->insert($t['tours'], array(
            'stable_id'=>$stable_id,'schema_version'=>STTI_SCHEMA_VERSION,'public_title'=>$f['public_title'],'slug'=>$f['slug'],
            'editorial'=>$f['editorial'],'schedule_status'=>$f['schedule_status'],'availability'=>$f['availability'],'temporal'=>$f['temporal'],
            'source_completeness'=>$f['source_completeness'],'payload'=>$json,'checksum'=>$checksum,'created_by'=>get_current_user_id(),'created_at'=>$now,'updated_at'=>$now,
        ), array('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%d','%s','%s'));
        if (!$wpdb->insert_id) wp_die('Private candidate insert failed.');
        stti_audit_event($stable_id, 'candidate_created', null, $payload);
        $event = 'created';
    } else {
        $before = $existing_payload;
        if (hash_equals((string)$existing['checksum'], $checksum)) {
            stti_audit_event($stable_id, 'candidate_unchanged', $before, $payload);
            $event = 'unchanged';
        } else {
            $wpdb->update($t['tours'], array(
                'schema_version'=>STTI_SCHEMA_VERSION,'public_title'=>$f['public_title'],'slug'=>$f['slug'],'editorial'=>$f['editorial'],'schedule_status'=>$f['schedule_status'],
                'availability'=>$f['availability'],'temporal'=>$f['temporal'],'source_completeness'=>$f['source_completeness'],'payload'=>$json,'checksum'=>$checksum,'updated_at'=>$now,
            ), array('stable_id'=>$stable_id), array('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s'), array('%s'));
            stti_audit_event($stable_id, 'candidate_updated', $before, $payload);
            $event = 'updated';
        }
    }
    $url = add_query_arg(array('page'=>'stti-tour-intelligence','view'=>'editor','tour'=>$stable_id,'stti_saved'=>$event), admin_url('admin.php'));
    wp_safe_redirect($url); exit;
}
