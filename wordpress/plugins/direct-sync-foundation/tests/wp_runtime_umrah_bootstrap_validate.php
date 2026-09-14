<?php
/** Prove validate-only can resolve an existing Umrah Program by source identity before sidecar bootstrap. */
if (!defined('ABSPATH') || !defined('WP_CLI')) throw new RuntimeException('Run through WP-CLI only.');
$ok=static function($condition,$message){ if(!$condition) throw new RuntimeException($message); WP_CLI::log('PASS: '.$message); };
wp_set_current_user(1);

$before_seq=(int)get_option('stpi_next_program_number',1);
$before_program_ids=get_posts(array('post_type'=>'stpi_program','post_status'=>'any','posts_per_page'=>-1,'fields'=>'ids'));
$before_event_ids=get_posts(array('post_type'=>'stpi_event','post_status'=>'any','posts_per_page'=>-1,'fields'=>'ids'));
$before_public_master=get_option('stti_v100_public_master',null);

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
$ok((bool)preg_match('/^[a-f0-9]{64}$/D',$current_checksum),'bootstrap fixture has canonical checksum');

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
$ok(get_option('stti_v100_public_master',null)===$before_public_master,'bootstrap validate does not alter Tour Public Master');

wp_delete_post($post_id,true);
$after_event_ids=get_posts(array('post_type'=>'stpi_event','post_status'=>'any','posts_per_page'=>-1,'fields'=>'ids'));
foreach(array_diff($after_event_ids,$before_event_ids) as $event_id) wp_delete_post((int)$event_id,true);
update_option('stpi_next_program_number',$before_seq,false);
$after_program_ids=get_posts(array('post_type'=>'stpi_program','post_status'=>'any','posts_per_page'=>-1,'fields'=>'ids'));
$ok(count($after_program_ids)===count($before_program_ids),'bootstrap runtime cleanup restores Program count');
$ok((int)get_option('stpi_next_program_number',1)===$before_seq,'bootstrap runtime cleanup restores Program sequence');
WP_CLI::success('Umrah source-identity bootstrap validate runtime: PASS');
