<?php
/** End-to-end READY live Umrah auto-refresh proof for Direct Sync v0.1.3. */
if (!defined('ABSPATH') || !defined('WP_CLI')) throw new RuntimeException('Run through WP-CLI only.');
$ok=static function($condition,$message){ if(!$condition) throw new RuntimeException($message); WP_CLI::log('PASS: '.$message); };
wp_set_current_user(1);

$before_seq=(int)get_option('stpi_next_program_number',1);
$before_program_ids=get_posts(array('post_type'=>'stpi_program','post_status'=>'any','posts_per_page'=>-1,'fields'=>'ids'));
$before_event_ids=get_posts(array('post_type'=>'stpi_event','post_status'=>'any','posts_per_page'=>-1,'fields'=>'ids'));
$before_tour_master=get_option('stti_v100_public_master',null);
$before_registry=get_option('stppi_registry',null);
$before_program_master=get_option('stppi_public_master',null);
$before_hub=get_option('stppi_hub_bridge_enabled',null);
$hotel_posts=array();

if(!post_type_exists('sthi_hotel')) register_post_type('sthi_hotel',array('public'=>false,'show_ui'=>false,'supports'=>array('title')));
add_filter('sthi_hotel_public_url',static function($url,$post_id){
    $id=(string)get_post_meta($post_id,'_sthi_hotel_id',true);
    return $id ? 'https://stds.test/otel/'.strtolower($id).'/' : '';
},10,2);
foreach(array(
    array('STH-900001','CI Madinah Hotel','Madinah'),
    array('STH-900002','CI Makkah Hotel','Makkah'),
) as $hotel){
    $pid=wp_insert_post(array('post_type'=>'sthi_hotel','post_status'=>'publish','post_title'=>$hotel[1]),true);
    if(is_wp_error($pid)) throw new RuntimeException($pid->get_error_message());
    $hotel_posts[]=(int)$pid;
    update_post_meta($pid,'_sthi_hotel_id',$hotel[0]);
    update_post_meta($pid,'_sthi_official_name',$hotel[1]);
    update_post_meta($pid,'_sthi_city',$hotel[2]);
    update_post_meta($pid,'_sthi_verification_status','verified');
    update_post_meta($pid,'_sthi_workflow_status','published');
}

$batch=json_decode(file_get_contents(WP_PLUGIN_DIR.'/program-intelligence/examples/umrah-219.json'),true);
$batch['source']['type']='google_sheets';
$batch['source']['document_ref']='ci-live-refresh-sheet';
$batch['source']['worksheet']='Home';
$batch['export_id']='ci-live-refresh-bootstrap';
$p=&$batch['programs'][0];
$p['program_id']=null;
$p['provenance']['source_type']='google_sheets';
$p['provenance']['source_ref']='Home row 16';
$p['provenance']['source_row']=16;
$p['stays'][0]['hotel_id']='STH-900001';
$p['stays'][1]['hotel_id']='STH-900002';
$p['inclusions']=array('CI verified package service');
unset($p);

$normalized=STPI_Contract::normalize_batch($batch);
$summary=STPI_Store::import_batch($normalized,gmdate(DATE_W3C),'Direct Sync live refresh bootstrap');
$ok(empty($summary['errors']) && (int)$summary['created']===1,'READY fixture creates one Program candidate');
$program_id=(string)$summary['program_ids'][0];
$ids=STPI_Store::find_by_program_id($program_id);
$ok(count($ids)===1,'fixture has one immutable STP identity');
$post_id=(int)$ids[0];
$preapproval_checksum=(string)get_post_meta($post_id,'_stpi_payload_hash',true);
$source_hash_before=(string)get_post_meta($post_id,'_stpi_source_payload_hash',true);

// Exact v0.1.2 sidecar behavior remains idempotent before approval.
$sidecar_batch=$batch;
$sidecar_batch['programs'][0]['program_id']=$program_id;
$sidecar_control=array('source_row'=>16,'stable_id'=>$program_id,'expected_checksum_sha256'=>$preapproval_checksum);
$unchanged=array(
 'contract'=>STDS_CONTRACT,
 'request_id'=>'STS-CI-AUTOREFRESH-UNCHANGED-001',
 'adapter'=>'umrah',
 'mode'=>'validate',
 'payload'=>array('batch'=>$sidecar_batch,'controls'=>array($sidecar_control),'removals'=>array()),
);
$unchanged_result=STDS_Umrah::handle($unchanged);
$unchanged_row=$unchanged_result['results'][0]??array();
$ok(($unchanged_row['operation']??'')==='UNCHANGED','sidecar Stable ID keeps unchanged source UNCHANGED');
$ok(($unchanged_row['checksum']??'')===$preapproval_checksum,'unchanged pre-approval checksum is returned');

// Reproduce yesterday's manual recovery state.
$approved=STPI_Store::transition($post_id,'approve');
$ok($approved===true,'real Program Intelligence approval gate accepts READY fixture');
$approved_program=STPI_Store::get_program($post_id);
$ok(($approved_program['workflow']['editorial']??'')==='approved','Program is approved after recovery');
$approved_checksum=(string)get_post_meta($post_id,'_stpi_payload_hash',true);
$ok($approved_checksum!==$preapproval_checksum,'manual approval changes canonical checksum');

if(!class_exists('STPPI_Repository')) require_once STPPI_DIR.'includes/class-stppi-repository.php';
if(!class_exists('STPPI_Renderer')) require_once STPPI_DIR.'includes/class-stppi-renderer.php';
$cfg=array('id'=>$program_id,'slug'=>'ci-live-program','mode'=>'public_noindex','seo'=>true,'post_id'=>$post_id,'hash'=>'','hotel_hash'=>'');
$model=STPPI_Renderer::model($cfg,false);
$ok(!is_wp_error($model),'real Publishing renderer accepts approved READY fixture');
$cfg['hash']=$model['hash'];
$cfg['hotel_hash']=$model['hotel_hash'];
$cfg['review_token']=wp_generate_uuid4();
update_option('stppi_registry',array($program_id=>$cfg),false);
update_option('stppi_public_master',true,false);
update_option('stppi_hub_bridge_enabled',true,false);
$mode_before=(string)$cfg['mode'];
$master_before=get_option('stppi_public_master',false);
$hub_before_live=get_option('stppi_hub_bridge_enabled',false);

// Sidecar intentionally still has the old pre-approval checksum. Introduce a real phone change.
$changed_batch=$sidecar_batch;
$changed_batch['programs'][0]['contact']['phone']='+905302015299';
$changed_batch['programs'][0]['contact']['whatsapp']='+905302015299';
$changed_validate=array(
 'contract'=>STDS_CONTRACT,
 'request_id'=>'STS-CI-AUTOREFRESH-VALIDATE-002',
 'adapter'=>'umrah',
 'mode'=>'validate',
 'payload'=>array('batch'=>$changed_batch,'controls'=>array($sidecar_control),'removals'=>array()),
);
$validate_result=STDS_Umrah::handle($changed_validate);
$validate_row=$validate_result['results'][0]??array();
$ok(($validate_row['operation']??'')==='UPDATE','real business change validates as UPDATE');
$ok(empty($validate_row['errors']),'live UPDATE preflight passes');
$ok(!empty($validate_row['public_impact']['protected']),'preflight recognizes existing live route');
$ok(!empty($validate_row['public_impact']['auto_refresh_supported']),'READY live update is eligible for automatic refresh');
$ok(!empty($validate_row['public_impact']['sidecar_checksum_reconciled']),'old pre-approval sidecar checksum is reconciled as lifecycle-only drift');
$ok((STPI_Store::get_program($post_id)['contact']['phone']??'')!=='+905302015299','validate remains no-write');

$apply=$changed_validate;
$apply['request_id']='STS-CI-AUTOREFRESH-APPLY-003';
$apply['mode']='apply';
$apply_result=STDS_Umrah::handle($apply);
$apply_row=$apply_result['results'][0]??array();
$ok(($apply_row['operation']??'')==='UPDATE','live UPDATE applies successfully');
$ok(empty($apply_row['errors']),'live UPDATE apply has no error');
$ok(!empty($apply_row['public_impact']['auto_refreshed']),'apply reports automatic live refresh');
$stored=STPI_Store::get_program($post_id);
$ok(($stored['workflow']['editorial']??'')==='approved','automatic refresh preserves approved editorial state');
$ok(($stored['contact']['phone']??'')==='+905302015299','new phone reaches canonical Program');
$ok(($stored['contact']['whatsapp']??'')==='+905302015299','new WhatsApp reaches canonical Program');
$new_checksum=(string)get_post_meta($post_id,'_stpi_payload_hash',true);
$new_source_hash=(string)get_post_meta($post_id,'_stpi_source_payload_hash',true);
$ok($new_checksum!==$approved_checksum,'canonical checksum advances after live update');
$ok($new_source_hash!==$source_hash_before,'source checksum advances after live update');
$registry_after=get_option('stppi_registry',array());
$ok(($registry_after[$program_id]['hash']??'')===$new_checksum,'Publishing registry hash is refreshed to canonical checksum');
$ok(($registry_after[$program_id]['mode']??'')===$mode_before,'existing public route mode is preserved');
$ok(!empty($registry_after[$program_id]['hotel_hash']),'Publishing hotel hash remains populated');
$ok(get_option('stppi_public_master',false)===$master_before,'Program Public Master remains unchanged');
$ok(get_option('stppi_hub_bridge_enabled',false)===$hub_before_live,'Hub bridge remains unchanged');
$ok(get_option('stti_v100_public_master',null)===$before_tour_master,'Tour Public Master remains unchanged');

// Returned checksum becomes the next sidecar token and immediately returns UNCHANGED.
$retry=array(
 'contract'=>STDS_CONTRACT,
 'request_id'=>'STS-CI-AUTOREFRESH-RETRY-004',
 'adapter'=>'umrah',
 'mode'=>'validate',
 'payload'=>array('batch'=>$changed_batch,'controls'=>array(array('source_row'=>16,'stable_id'=>$program_id,'expected_checksum_sha256'=>$new_checksum)),'removals'=>array()),
);
$retry_result=STDS_Umrah::handle($retry);
$retry_row=$retry_result['results'][0]??array();
$ok(($retry_row['operation']??'')==='UNCHANGED','post-refresh retry is UNCHANGED');
$ok(($retry_row['checksum']??'')===$new_checksum,'post-refresh retry returns new checksum');

$program_ids_now=get_posts(array('post_type'=>'stpi_program','post_status'=>'any','posts_per_page'=>-1,'fields'=>'ids'));
$ok(count($program_ids_now)===count($before_program_ids)+1,'automatic live refresh creates no duplicate Program');

if($before_registry===null) delete_option('stppi_registry'); else update_option('stppi_registry',$before_registry,false);
if($before_program_master===null) delete_option('stppi_public_master'); else update_option('stppi_public_master',$before_program_master,false);
if($before_hub===null) delete_option('stppi_hub_bridge_enabled'); else update_option('stppi_hub_bridge_enabled',$before_hub,false);
wp_delete_post($post_id,true);
foreach($hotel_posts as $hotel_post_id) wp_delete_post($hotel_post_id,true);
$after_event_ids=get_posts(array('post_type'=>'stpi_event','post_status'=>'any','posts_per_page'=>-1,'fields'=>'ids'));
foreach(array_diff($after_event_ids,$before_event_ids) as $event_id) wp_delete_post((int)$event_id,true);
update_option('stpi_next_program_number',$before_seq,false);
$after_program_ids=get_posts(array('post_type'=>'stpi_program','post_status'=>'any','posts_per_page'=>-1,'fields'=>'ids'));
$ok(count($after_program_ids)===count($before_program_ids),'runtime cleanup restores Program count');
$ok((int)get_option('stpi_next_program_number',1)===$before_seq,'runtime cleanup restores Program sequence');
WP_CLI::success('Automatic live Umrah refresh runtime: PASS');
