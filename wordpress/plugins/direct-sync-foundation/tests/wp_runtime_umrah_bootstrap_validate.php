<?php
/** Prove source bootstrap, sidecar idempotency, stale-approval checksum reconciliation and automatic live-route refresh. */
if (!defined('ABSPATH') || !defined('WP_CLI')) throw new RuntimeException('Run through WP-CLI only.');
$ok=static function($condition,$message){ if(!$condition) throw new RuntimeException($message); WP_CLI::log('PASS: '.$message); };
wp_set_current_user(1);

$before_seq=(int)get_option('stpi_next_program_number',1);
$before_program_ids=get_posts(array('post_type'=>'stpi_program','post_status'=>'any','posts_per_page'=>-1,'fields'=>'ids'));
$before_event_ids=get_posts(array('post_type'=>'stpi_event','post_status'=>'any','posts_per_page'=>-1,'fields'=>'ids'));
$before_public_master=get_option('stti_v100_public_master',null);
$before_stppi_registry=get_option('stppi_registry',null);
$before_stppi_public_master=get_option('stppi_public_master',null);
$before_stppi_hub=get_option('stppi_hub_bridge_enabled',null);
$created_hotel_posts=array();

if(!post_type_exists('sthi_hotel')) register_post_type('sthi_hotel',array('public'=>false,'show_ui'=>false,'supports'=>array('title')));
add_filter('sthi_hotel_public_url',static function($url,$post_id){
    $id=(string)get_post_meta($post_id,'_sthi_hotel_id',true);
    return $id ? 'https://stds.test/otel/'.strtolower($id).'/' : '';
},10,2);
foreach(array(
    array('STH-900001','CI Madinah Hotel','Madinah'),
    array('STH-900002','CI Makkah Hotel','Makkah'),
) as $h){
    $pid=wp_insert_post(array('post_type'=>'sthi_hotel','post_status'=>'publish','post_title'=>$h[1]),true);
    if(is_wp_error($pid)) throw new RuntimeException($pid->get_error_message());
    $created_hotel_posts[]=(int)$pid;
    update_post_meta($pid,'_sthi_hotel_id',$h[0]);
    update_post_meta($pid,'_sthi_official_name',$h[1]);
    update_post_meta($pid,'_sthi_city',$h[2]);
    update_post_meta($pid,'_sthi_verification_status','verified');
    update_post_meta($pid,'_sthi_workflow_status','published');
}

$batch=json_decode(file_get_contents(WP_PLUGIN_DIR.'/program-intelligence/examples/umrah-219.json'),true);
$batch['source']['type']='google_sheets';
$batch['source']['document_ref']='ci-bootstrap-source-sheet';
$batch['source']['worksheet']='Home';
$batch['export_id']='ci-bootstrap-existing-program';
$batch['programs'][0]['program_id']=null;
$batch['programs'][0]['provenance']['source_type']='google_sheets';
$batch['programs'][0]['provenance']['source_ref']='Home row 16';
$batch['programs'][0]['provenance']['source_row']=16;
$batch['programs'][0]['stays'][0]['hotel_id']='STH-900001';
$batch['programs'][0]['stays'][1]['hotel_id']='STH-900002';

$normalized=STPI_Contract::normalize_batch($batch);
$summary=STPI_Store::import_batch($normalized,gmdate(DATE_W3C),'Direct Sync bootstrap runtime');
$ok(empty($summary['errors']) && (int)$summary['created']===1,'bootstrap fixture creates one existing Program');
$program_id=(string)$summary['program_ids'][0];
$ids=STPI_Store::find_by_program_id($program_id);
$ok(count($ids)===1,'bootstrap fixture has one immutable STP identity');
$post_id=(int)$ids[0];
$current_checksum=(string)get_post_meta($post_id,'_stpi_payload_hash',true);
$current_source_hash=(string)get_post_meta($post_id,'_stpi_source_payload_hash',true);
$current_content=(string)get_post_field('post_content',$post_id);
$ok((bool)preg_match('/^[a-f0-9]{64}$/D',$current_checksum),'bootstrap fixture has canonical checksum');
$ok((bool)preg_match('/^[a-f0-9]{64}$/D',$current_source_hash),'bootstrap fixture has source checksum');

$envelope=array(
 'contract'=>STDS_CONTRACT,
 'request_id'=>'STS-CI-BOOTSTRAP-VALIDATE-001',
 'adapter'=>'umrah',
 'mode'=>'validate',
 'payload'=>array(
  'batch'=>$batch,
  'controls'=>array(array('source_row'=>16,'stable_id'=>null,'expected_checksum_sha256'=>null)),
  'removals'=>array(),
 ),
);
$result=STDS_Umrah::handle($envelope);
$ok(is_array($result) && !empty($result['results'][0]),'validate-only returns one row result');
$row=$result['results'][0];
$ok(($row['operation']??'')==='UNCHANGED','source identity resolves existing Program without proposing CREATE');
$ok(($row['stable_id']??'')===$program_id,'validate-only exposes resolved existing STP Stable ID');
$ok(($row['checksum']??'')===$current_checksum,'validate-only exposes current canonical checksum for sidecar bootstrap');
$ok(empty($row['errors']),'bootstrap validate has no row error');

$sidecar_batch=$batch;
$sidecar_batch['programs'][0]['program_id']=$program_id;
$sidecar_control=array('source_row'=>16,'stable_id'=>$program_id,'expected_checksum_sha256'=>$current_checksum);
$sidecar_validate=array(
 'contract'=>STDS_CONTRACT,
 'request_id'=>'STS-CI-SIDECAR-VALIDATE-002',
 'adapter'=>'umrah',
 'mode'=>'validate',
 'payload'=>array('batch'=>$sidecar_batch,'controls'=>array($sidecar_control),'removals'=>array()),
);
$sidecar_result=STDS_Umrah::handle($sidecar_validate);
$sidecar_row=$sidecar_result['results'][0]??array();
$ok(($sidecar_row['operation']??'')==='UNCHANGED','sidecar Stable ID does not turn unchanged source into UPDATE');
$ok(($sidecar_row['stable_id']??'')===$program_id,'sidecar validate preserves immutable STP Stable ID');
$ok(($sidecar_row['checksum']??'')===$current_checksum,'sidecar validate preserves canonical checksum');
$ok(empty($sidecar_row['errors']),'sidecar validate has no row error');

$sidecar_apply=$sidecar_validate;
$sidecar_apply['request_id']='STS-CI-SIDECAR-APPLY-003';
$sidecar_apply['mode']='apply';
$apply_result=STDS_Umrah::handle($sidecar_apply);
$apply_row=$apply_result['results'][0]??array();
$ok(($apply_row['operation']??'')==='UNCHANGED','sidecar no-change apply remains UNCHANGED');
$ok((string)get_post_meta($post_id,'_stpi_source_payload_hash',true)===$current_source_hash,'sidecar no-change apply preserves source payload hash');
$ok((string)get_post_meta($post_id,'_stpi_payload_hash',true)===$current_checksum,'sidecar no-change apply preserves canonical payload hash');
$ok((string)get_post_field('post_content',$post_id)===$current_content,'sidecar no-change apply does not rewrite canonical Program content');

// Simulate yesterday's manual recovery: approved Program + refreshed live Publishing registry,
// while the hidden Sheet sidecar still holds the pre-approval canonical checksum.
$approved=STPI_Store::transition($post_id,'approve');
$ok($approved===true,'fixture can be approved through the real Program Intelligence gate');
$approved_program=STPI_Store::get_program($post_id);
$ok(($approved_program['workflow']['editorial']??'')==='approved','manual recovery leaves canonical Program approved');
$approved_checksum=(string)get_post_meta($post_id,'_stpi_payload_hash',true);
$ok($approved_checksum!==$current_checksum,'approval changes canonical checksum exactly as production recovery did');

if(!class_exists('STPPI_Repository')) require_once STPPI_DIR.'includes/class-stppi-repository.php';
if(!class_exists('STPPI_Renderer')) require_once STPPI_DIR.'includes/class-stppi-renderer.php';
$cfg=array('id'=>$program_id,'slug'=>'ci-live-program','mode'=>'public_noindex','seo'=>true,'post_id'=>$post_id,'hash'=>'','hotel_hash'=>'');
$model=STPPI_Renderer::model($cfg,false);
$ok(!is_wp_error($model),'real Publishing renderer accepts the approved live fixture');
$cfg['hash']=$model['hash'];
$cfg['hotel_hash']=$model['hotel_hash'];
$cfg['review_token']=wp_generate_uuid4();
update_option('stppi_registry',array($program_id=>$cfg),false);
update_option('stppi_public_master',true,false);
update_option('stppi_hub_bridge_enabled',true,false);
$registry_before_live=get_option('stppi_registry',array());
$mode_before=(string)$registry_before_live[$program_id]['mode'];
$master_before_live=get_option('stppi_public_master',false);
$hub_before_live=get_option('stppi_hub_bridge_enabled',false);

// Use the stale pre-approval checksum deliberately and introduce a real business-data change.
// v0.1.3 must recognize that the stale checksum differs only by the prior manual approval,
// then perform the new update while preserving approval and refreshing the live route hashes.
$changed_batch=$sidecar_batch;
$changed_batch['programs'][0]['contact']['phone']='+905302015299';
$changed_batch['programs'][0]['contact']['whatsapp']='+905302015299';
$stale_sidecar_control=array('source_row'=>16,'stable_id'=>$program_id,'expected_checksum_sha256'=>$current_checksum);
$changed_validate=array(
 'contract'=>STDS_CONTRACT,
 'request_id'=>'STS-CI-LIVE-VALIDATE-004',
 'adapter'=>'umrah',
 'mode'=>'validate',
 'payload'=>array('batch'=>$changed_batch,'controls'=>array($stale_sidecar_control),'removals'=>array()),
);
$changed_validate_result=STDS_Umrah::handle($changed_validate);
$changed_validate_row=$changed_validate_result['results'][0]??array();
$ok(($changed_validate_row['operation']??'')==='UPDATE','live Program validate reports the real source UPDATE');
$ok(!empty($changed_validate_row['public_impact']['protected']),'live Program validate detects the existing public route');
$ok(!empty($changed_validate_row['public_impact']['auto_refresh_supported']),'live Program validate authorizes automatic refresh only after READY gate');
$ok(!empty($changed_validate_row['public_impact']['sidecar_checksum_reconciled']),'stale pre-approval sidecar checksum is recognized as lifecycle-only drift');
$ok(empty($changed_validate_row['errors']),'live Program validate remains read-only and passes');
$ok((STPI_Store::get_program($post_id)['contact']['phone']??'')!=='+905302015299','validate writes no business data');

$changed_apply=$changed_validate;
$changed_apply['request_id']='STS-CI-LIVE-APPLY-005';
$changed_apply['mode']='apply';
$changed_apply_result=STDS_Umrah::handle($changed_apply);
$changed_apply_row=$changed_apply_result['results'][0]??array();
$ok(($changed_apply_row['operation']??'')==='UPDATE','live Program apply completes UPDATE instead of requiring manual recovery');
$ok(empty($changed_apply_row['errors']),'live Program auto-refresh apply has no row error');
$ok(!empty($changed_apply_row['public_impact']['auto_refreshed']),'live Program apply reports automatic approval + route refresh');
$after_live=STPI_Store::get_program($post_id);
$ok(($after_live['workflow']['editorial']??'')==='approved','live Program stays approved after Direct Sync update');
$ok(($after_live['contact']['phone']??'')==='+905302015299','live Program stores the changed phone');
$ok(($after_live['contact']['whatsapp']??'')==='+905302015299','live Program stores the changed WhatsApp');
$new_checksum=(string)get_post_meta($post_id,'_stpi_payload_hash',true);
$new_source_hash=(string)get_post_meta($post_id,'_stpi_source_payload_hash',true);
$ok($new_checksum!==$approved_checksum,'live Program canonical checksum advances after real update');
$ok($new_source_hash!==$current_source_hash,'live Program source checksum advances after real update');
$registry_after_live=get_option('stppi_registry',array());
$ok(($registry_after_live[$program_id]['hash']??'')===$new_checksum,'Publishing registry hash refreshes to the new canonical checksum');
$ok(($registry_after_live[$program_id]['mode']??'')===$mode_before,'Publishing route mode is preserved');
$ok(!empty($registry_after_live[$program_id]['hotel_hash']),'Publishing hotel hash remains populated');
$ok(get_option('stppi_public_master',false)===$master_before_live,'Public Master is preserved exactly');
$ok(get_option('stppi_hub_bridge_enabled',false)===$hub_before_live,'Hub bridge is preserved exactly');
$ok(get_option('stti_v100_public_master',null)===$before_public_master,'Tour Public Master remains untouched');

$program_ids_after_live=get_posts(array('post_type'=>'stpi_program','post_status'=>'any','posts_per_page'=>-1,'fields'=>'ids'));
$ok(count($program_ids_after_live)===count($before_program_ids)+1,'live update creates no duplicate Program');

// New checksum returned by the auto-refresh becomes the next sidecar concurrency token.
$final_batch=$changed_batch;
$final_control=array('source_row'=>16,'stable_id'=>$program_id,'expected_checksum_sha256'=>$new_checksum);
$final_validate=array(
 'contract'=>STDS_CONTRACT,
 'request_id'=>'STS-CI-LIVE-VALIDATE-006',
 'adapter'=>'umrah',
 'mode'=>'validate',
 'payload'=>array('batch'=>$final_batch,'controls'=>array($final_control),'removals'=>array()),
);
$final_result=STDS_Umrah::handle($final_validate);
$final_row=$final_result['results'][0]??array();
$ok(($final_row['operation']??'')==='UNCHANGED','post-refresh retry returns UNCHANGED');
$ok(($final_row['checksum']??'')===$new_checksum,'post-refresh retry returns the new canonical checksum');

if($before_stppi_registry===null) delete_option('stppi_registry'); else update_option('stppi_registry',$before_stppi_registry,false);
if($before_stppi_public_master===null) delete_option('stppi_public_master'); else update_option('stppi_public_master',$before_stppi_public_master,false);
if($before_stppi_hub===null) delete_option('stppi_hub_bridge_enabled'); else update_option('stppi_hub_bridge_enabled',$before_stppi_hub,false);

wp_delete_post($post_id,true);
foreach($created_hotel_posts as $hotel_post_id) wp_delete_post($hotel_post_id,true);
$after_event_ids=get_posts(array('post_type'=>'stpi_event','post_status'=>'any','posts_per_page'=>-1,'fields'=>'ids'));
foreach(array_diff($after_event_ids,$before_event_ids) as $event_id) wp_delete_post((int)$event_id,true);
update_option('stpi_next_program_number',$before_seq,false);
$after_program_ids=get_posts(array('post_type'=>'stpi_program','post_status'=>'any','posts_per_page'=>-1,'fields'=>'ids'));
$ok(count($after_program_ids)===count($before_program_ids),'runtime cleanup restores Program count');
$ok((int)get_option('stpi_next_program_number',1)===$before_seq,'runtime cleanup restores Program sequence');
WP_CLI::success('Umrah sidecar + automatic live-route refresh runtime: PASS');
