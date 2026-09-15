<?php
/** Prove source-identity bootstrap, sidecar idempotency and live-public impact fail-closed safety. */
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

$batch=json_decode(file_get_contents(WP_PLUGIN_DIR.'/program-intelligence/examples/umrah-219.json'),true);
$batch['source']['type']='google_sheets';
$batch['source']['document_ref']='ci-bootstrap-source-sheet';
$batch['source']['worksheet']='Home';
$batch['export_id']='ci-bootstrap-existing-program';
$batch['programs'][0]['program_id']=null;
$batch['programs'][0]['provenance']['source_type']='google_sheets';
$batch['programs'][0]['provenance']['source_ref']='Home row 16';
$batch['programs'][0]['provenance']['source_row']=16;

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

// Simulate the exact next production request after Apps Script saves Stable ID/checksum in the hidden sidecar.
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
$ok(is_array($sidecar_result) && !empty($sidecar_result['results'][0]),'sidecar validate returns one row result');
$sidecar_row=$sidecar_result['results'][0];
$ok(($sidecar_row['operation']??'')==='UNCHANGED','sidecar Stable ID does not turn unchanged source into UPDATE');
$ok(($sidecar_row['stable_id']??'')===$program_id,'sidecar validate preserves immutable STP Stable ID');
$ok(($sidecar_row['checksum']??'')===$current_checksum,'sidecar validate preserves canonical checksum');
$ok(empty($sidecar_row['errors']),'sidecar validate has no row error');

$sidecar_apply=$sidecar_validate;
$sidecar_apply['request_id']='STS-CI-SIDECAR-APPLY-003';
$sidecar_apply['mode']='apply';
$apply_result=STDS_Umrah::handle($sidecar_apply);
$ok(is_array($apply_result) && !empty($apply_result['results'][0]),'sidecar apply returns one row result');
$apply_row=$apply_result['results'][0];
$ok(($apply_row['operation']??'')==='UNCHANGED','sidecar no-change apply remains UNCHANGED');
$ok(($apply_row['stable_id']??'')===$program_id,'sidecar no-change apply preserves immutable Stable ID');
$ok((string)get_post_meta($post_id,'_stpi_source_payload_hash',true)===$current_source_hash,'sidecar no-change apply preserves source payload hash');
$ok((string)get_post_meta($post_id,'_stpi_payload_hash',true)===$current_checksum,'sidecar no-change apply preserves canonical payload hash');
$ok((string)get_post_field('post_content',$post_id)===$current_content,'sidecar no-change apply does not rewrite canonical Program content');
$program_ids_now=get_posts(array('post_type'=>'stpi_program','post_status'=>'any','posts_per_page'=>-1,'fields'=>'ids'));
$ok(count($program_ids_now)===count($before_program_ids)+1,'sidecar retry creates no duplicate Program');
$ok(get_option('stti_v100_public_master',null)===$before_public_master,'bootstrap and sidecar requests do not alter Tour Public Master');

// Reproduce the production-impact condition: this exact Stable ID already owns an accepted public route.
update_option('stppi_registry',array($program_id=>array(
 'id'=>$program_id,
 'slug'=>'ci-live-program',
 'mode'=>'public_noindex',
 'seo'=>true,
 'post_id'=>$post_id,
 'hash'=>$current_checksum,
 'hotel_hash'=>str_repeat('a',64),
)),false);
update_option('stppi_public_master',true,false);
update_option('stppi_hub_bridge_enabled',true,false);
$registry_before_guard=get_option('stppi_registry',array());
$content_before_guard=(string)get_post_field('post_content',$post_id);
$payload_hash_before_guard=(string)get_post_meta($post_id,'_stpi_payload_hash',true);
$source_hash_before_guard=(string)get_post_meta($post_id,'_stpi_source_payload_hash',true);
$editorial_before_guard=(string)(STPI_Store::get_program($post_id)['workflow']['editorial']??'');

$changed_batch=$sidecar_batch;
$changed_batch['programs'][0]['contact']['phone']='+905302015299';
$changed_batch['programs'][0]['contact']['whatsapp']='+905302015299';
$changed_validate=array(
 'contract'=>STDS_CONTRACT,
 'request_id'=>'STS-CI-LIVE-VALIDATE-004',
 'adapter'=>'umrah',
 'mode'=>'validate',
 'payload'=>array('batch'=>$changed_batch,'controls'=>array($sidecar_control),'removals'=>array()),
);
$changed_validate_result=STDS_Umrah::handle($changed_validate);
$changed_validate_row=$changed_validate_result['results'][0]??array();
$ok(($changed_validate_row['operation']??'')==='UPDATE','live Program validate still reports the real source UPDATE');
$ok(!empty($changed_validate_row['public_impact']['protected']),'live Program validate explicitly reports protected public impact');
$ok(!empty($changed_validate_row['public_impact']['apply_blocked']),'live Program validate tells operator apply is blocked');
$ok(empty($changed_validate_row['errors']),'live Program validate itself remains read-only and non-error');
$ok((string)get_post_field('post_content',$post_id)===$content_before_guard,'live Program validate writes no canonical content');

$changed_apply=$changed_validate;
$changed_apply['request_id']='STS-CI-LIVE-APPLY-005';
$changed_apply['mode']='apply';
$changed_apply_result=STDS_Umrah::handle($changed_apply);
$changed_apply_row=$changed_apply_result['results'][0]??array();
$ok(($changed_apply_row['operation']??'')==='CONFLICT','live Program apply fails closed before mutation');
$ok(in_array('PUBLIC_PROGRAM_UPDATE_REQUIRES_CONTROLLED_REVIEW',(array)($changed_apply_row['errors']??array()),true),'live Program apply returns explicit controlled-review reason');
$ok(!empty($changed_apply_row['public_impact']['protected']),'live Program apply retains public-impact evidence');
$ok((string)get_post_field('post_content',$post_id)===$content_before_guard,'blocked live apply preserves canonical Program content byte-for-byte');
$ok((string)get_post_meta($post_id,'_stpi_payload_hash',true)===$payload_hash_before_guard,'blocked live apply preserves canonical payload hash');
$ok((string)get_post_meta($post_id,'_stpi_source_payload_hash',true)===$source_hash_before_guard,'blocked live apply preserves source payload hash');
$ok((string)(STPI_Store::get_program($post_id)['workflow']['editorial']??'')===$editorial_before_guard,'blocked live apply preserves editorial state');
$ok(get_option('stppi_registry',array())===$registry_before_guard,'blocked live apply preserves Publishing registry exactly');
$program_ids_after_guard=get_posts(array('post_type'=>'stpi_program','post_status'=>'any','posts_per_page'=>-1,'fields'=>'ids'));
$ok(count($program_ids_after_guard)===count($before_program_ids)+1,'blocked live apply creates no duplicate Program');

// Restore disposable Publishing Integration options exactly.
if($before_stppi_registry===null) delete_option('stppi_registry'); else update_option('stppi_registry',$before_stppi_registry,false);
if($before_stppi_public_master===null) delete_option('stppi_public_master'); else update_option('stppi_public_master',$before_stppi_public_master,false);
if($before_stppi_hub===null) delete_option('stppi_hub_bridge_enabled'); else update_option('stppi_hub_bridge_enabled',$before_stppi_hub,false);

wp_delete_post($post_id,true);
$after_event_ids=get_posts(array('post_type'=>'stpi_event','post_status'=>'any','posts_per_page'=>-1,'fields'=>'ids'));
foreach(array_diff($after_event_ids,$before_event_ids) as $event_id) wp_delete_post((int)$event_id,true);
update_option('stpi_next_program_number',$before_seq,false);
$after_program_ids=get_posts(array('post_type'=>'stpi_program','post_status'=>'any','posts_per_page'=>-1,'fields'=>'ids'));
$ok(count($after_program_ids)===count($before_program_ids),'bootstrap runtime cleanup restores Program count');
$ok((int)get_option('stpi_next_program_number',1)===$before_seq,'bootstrap runtime cleanup restores Program sequence');
WP_CLI::success('Umrah source identity + sidecar idempotency + public-impact safety runtime: PASS');
