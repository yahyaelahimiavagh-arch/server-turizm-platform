<?php
/** Shared Direct Sync disposable WordPress runtime evidence. */
if (!defined('ABSPATH') || !defined('WP_CLI')) throw new RuntimeException('Run through WP-CLI only.');
if (!defined('ST_DIRECT_SYNC_SECRET')) define('ST_DIRECT_SYNC_SECRET','ci-direct-sync-secret-v1');
if (!defined('ST_DIRECT_SYNC_KEY_ID')) define('ST_DIRECT_SYNC_KEY_ID','ci-sheets-key');
$ok=static function($condition,$message){ if(!$condition) throw new RuntimeException($message); WP_CLI::log('PASS: '.$message); };
wp_set_current_user(1);
global $wpdb;
$tours=stti_tables();
$before_event_ids=get_posts(array('post_type'=>'stpi_event','post_status'=>'any','posts_per_page'=>-1,'fields'=>'ids'));
$before=array(
 'tour_count'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM {$tours['tours']}"),
 'tour_audit'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM {$tours['audit']}"),
 'tour_seq'=>(int)get_option('stti_next_sequence',1),
 'program_seq'=>(int)get_option('stpi_next_program_number',1),
 'program_count'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type='stpi_program'"),
 'event_count'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type='stpi_event'"),
 'public_master'=>get_option('stti_v100_public_master',null),
);
$sync_table=STDS_Store::table();
$before['sync_count']=(int)$wpdb->get_var("SELECT COUNT(*) FROM {$sync_table}");

$call=static function($doc,$nonce,$timestamp=null,$secret=null,$key_id=null) {
    $raw=wp_json_encode($doc,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    $timestamp=(string)($timestamp===null?time():$timestamp); $body_hash=hash('sha256',$raw);
    $canonical="ST-DIRECT-SYNC-1\n{$timestamp}\n{$nonce}\n{$body_hash}";
    $signature=hash_hmac('sha256',$canonical,$secret===null?ST_DIRECT_SYNC_SECRET:$secret);
    $req=new WP_REST_Request('POST','/server-turizm/v1/direct-sync');
    $req->set_body($raw);
    $req->set_header('content-type','application/json');
    $req->set_header('x-st-sync-timestamp',$timestamp);
    $req->set_header('x-st-sync-nonce',$nonce);
    $req->set_header('x-st-sync-key-id',$key_id===null?ST_DIRECT_SYNC_KEY_ID:$key_id);
    $req->set_header('x-st-sync-signature',$signature);
    return rest_do_request($req);
};

$ok(defined('STDS_CONTRACT') && STDS_CONTRACT==='ST-DIRECT-SYNC-1.0.0','shared Direct Sync contract is loaded');
$ok(rest_get_server() instanceof WP_REST_Server,'WordPress REST server is available');

$tour_doc=array(
 'import_contract'=>'STTI-TOUR-IMPORT-1.0.0','mode'=>'partial',
 'policy'=>array('facts'=>'source_only_no_invention','missing_values'=>'omit_or_null_or_explicit_unknown','publication'=>'force_private'),
 'producer'=>array('type'=>'google_sheet','name'=>'CI Tour Sheet','version'=>'direct-sync-test','generated_at'=>gmdate(DATE_W3C)),
 'target'=>array('stable_id'=>null,'expected_checksum_sha256'=>null),
 'sources'=>array(array('source_id'=>'SRC-CI-TOUR','type'=>'google_sheet','ref'=>'ci://tour/row/3','label'=>'CI Tour','language'=>'tr-TR','completeness'=>'source_partial','notes'=>'Direct Sync runtime fixture')),
 'tour'=>array(
  'identity'=>array('tour_code'=>'CI-TOUR-001','public_title'=>'CI Direct Sync Tour','language'=>'tr-TR'),
  'destinations'=>array('primary_country'=>'Türkiye','countries'=>array('Türkiye'),'cities'=>array('İstanbul')),
  'date'=>array('mode'=>'exact','precision'=>'exact','start_date'=>'2027-06-01','end_date'=>'2027-06-03'),
  'pricing'=>array('type'=>'on_request','amount'=>null,'currency'=>'EUR','basis'=>'unknown'),
  'lifecycle'=>array('editorial'=>'needs_review','schedule'=>'scheduled','availability'=>'open'),
  'provenance'=>array('primary_source_id'=>'SRC-CI-TOUR','source_completeness'=>'source_partial')
 )
);
$tour_validate=array('contract'=>STDS_CONTRACT,'request_id'=>'STS-CI-TOUR-VALIDATE-001','adapter'=>'tour','mode'=>'validate','payload'=>array('documents'=>array($tour_doc),'archives'=>array()));
$r=$call($tour_validate,'nonce-tour-validate-0001'); $data=$r->get_data();
$ok($r->get_status()===200 && ($data['results'][0]['operation']??'')==='CREATE','Tour validate plans CREATE');
$ok((int)$wpdb->get_var("SELECT COUNT(*) FROM {$tours['tours']}")===$before['tour_count'] && (int)get_option('stti_next_sequence',1)===$before['tour_seq'],'Tour validate performs no canonical or sequence write');

$expired=$call(array('contract'=>STDS_CONTRACT,'request_id'=>'STS-CI-AUTH-OLD-001','adapter'=>'tour','mode'=>'validate','payload'=>array('documents'=>array($tour_doc),'archives'=>array())),'nonce-auth-old-00000001',time()-1000);
$ok($expired->get_status()===401,'Expired timestamp is rejected before write');
$bad_sig=$call(array('contract'=>STDS_CONTRACT,'request_id'=>'STS-CI-AUTH-SIG-001','adapter'=>'tour','mode'=>'validate','payload'=>array('documents'=>array($tour_doc),'archives'=>array())),'nonce-auth-sig-00000001',null,'wrong-ci-secret');
$ok($bad_sig->get_status()===401,'Invalid HMAC signature is rejected before write');

$tour_create=array('contract'=>STDS_CONTRACT,'request_id'=>'STS-CI-TOUR-CREATE-001','adapter'=>'tour','mode'=>'apply','payload'=>array('documents'=>array($tour_doc),'archives'=>array()));
$r=$call($tour_create,'nonce-tour-create-000001'); $data=$r->get_data();
$ok($r->get_status()===200 && !empty($data['ok']),'Tour CREATE request succeeds');
$tr=$data['results'][0]; $tour_id=$tr['stable_id']; $tour_checksum=$tr['checksum'];
$ok(($tr['operation']??'')==='CREATE' && preg_match('/^STT-\d{6}$/',$tour_id),'Tour CREATE allocates immutable STT ID');
$ok(stti_get_candidate($tour_id)!==null,'Tour candidate exists after Direct Sync CREATE');
$replay=$call($tour_create,'nonce-tour-create-000001')->get_data();
$ok(!empty($replay['idempotent_replay']) && (int)$wpdb->get_var("SELECT COUNT(*) FROM {$tours['tours']}")===$before['tour_count']+1,'Tour exact request replay is idempotent');
$changed_request=$tour_create; $changed_request['payload']['documents'][0]['tour']['identity']['public_title']='Changed under same request ID';
$changed=$call($changed_request,'nonce-tour-body-change-01');
$ok($changed->get_status()===409,'Same request ID with changed body is rejected');
$nonce_reuse=$tour_create; $nonce_reuse['request_id']='STS-CI-TOUR-NONCE-REUSE-001';
$nonce_response=$call($nonce_reuse,'nonce-tour-create-000001');
$ok($nonce_response->get_status()===409,'Nonce replay under a different request ID is rejected');

$tour_bad=$tour_doc; $tour_bad['target']=array('stable_id'=>$tour_id,'expected_checksum_sha256'=>str_repeat('0',64)); $tour_bad['tour']['identity']['public_title']='Blocked title';
$r=$call(array('contract'=>STDS_CONTRACT,'request_id'=>'STS-CI-TOUR-CONFLICT-001','adapter'=>'tour','mode'=>'apply','payload'=>array('documents'=>array($tour_bad),'archives'=>array())),'nonce-tour-conflict-0001');
$ok($r->get_status()===207 && ($r->get_data()['results'][0]['operation']??'')==='CONFLICT','Tour checksum conflict fails closed');

$tour_update=$tour_doc; $tour_update['target']=array('stable_id'=>$tour_id,'expected_checksum_sha256'=>$tour_checksum); $tour_update['tour']['identity']['public_title']='CI Direct Sync Tour Updated';
$r=$call(array('contract'=>STDS_CONTRACT,'request_id'=>'STS-CI-TOUR-UPDATE-001','adapter'=>'tour','mode'=>'apply','payload'=>array('documents'=>array($tour_update),'archives'=>array())),'nonce-tour-update-000001'); $data=$r->get_data();
$ok($r->get_status()===200 && ($data['results'][0]['operation']??'')==='UPDATE','Tour UPDATE succeeds with expected checksum');
$tour_checksum=$data['results'][0]['checksum'];
$r=$call(array('contract'=>STDS_CONTRACT,'request_id'=>'STS-CI-TOUR-ARCHIVE-001','adapter'=>'tour','mode'=>'apply','payload'=>array('documents'=>array(),'archives'=>array(array('stable_id'=>$tour_id,'expected_checksum_sha256'=>$tour_checksum)))),'nonce-tour-archive-00001'); $data=$r->get_data();
$ok($r->get_status()===200 && ($data['results'][0]['operation']??'')==='ARCHIVE','Tour archive is state transition, not delete');
$ok((stti_get_candidate($tour_id)['editorial']??'')==='archived','Archived Tour entity remains stored');

$umrah=json_decode(file_get_contents(WP_PLUGIN_DIR.'/program-intelligence/examples/umrah-219.json'),true);
$umrah['source']['type']='google_sheets'; $umrah['source']['document_ref']='ci-direct-sync-sheet'; $umrah['source']['worksheet']='Home'; $umrah['export_id']='ci-direct-sync-umrah-create';
$umrah['programs'][0]['program_id']=null; $umrah['programs'][0]['provenance']['source_type']='google_sheets'; $umrah['programs'][0]['provenance']['source_ref']='Home row 16'; $umrah['programs'][0]['provenance']['source_row']=16;
$umrah_validate=array('contract'=>STDS_CONTRACT,'request_id'=>'STS-CI-UMRAH-VALIDATE-001','adapter'=>'umrah','mode'=>'validate','payload'=>array('batch'=>$umrah,'controls'=>array(array('source_row'=>16,'stable_id'=>null,'expected_checksum_sha256'=>null)),'removals'=>array()));
$r=$call($umrah_validate,'nonce-umrah-validate-001'); $data=$r->get_data();
$ok($r->get_status()===200 && ($data['results'][0]['operation']??'')==='CREATE','Umrah validate plans CREATE');
$ok((int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type='stpi_program'")===$before['program_count'] && (int)get_option('stpi_next_program_number',1)===$before['program_seq'],'Umrah validate performs no Program or sequence write');

$umrah_create=array('contract'=>STDS_CONTRACT,'request_id'=>'STS-CI-UMRAH-CREATE-001','adapter'=>'umrah','mode'=>'apply','payload'=>array('batch'=>$umrah,'controls'=>array(array('source_row'=>16,'stable_id'=>null,'expected_checksum_sha256'=>null)),'removals'=>array()));
$r=$call($umrah_create,'nonce-umrah-create-00001'); $data=$r->get_data();
$ok($r->get_status()===200 && !empty($data['ok']),'Umrah CREATE request succeeds');
$ur=$data['results'][0]; $program_id=$ur['stable_id']; $program_checksum=$ur['checksum'];
$ok(($ur['operation']??'')==='CREATE' && preg_match('/^STP-\d{6}$/',$program_id),'Umrah CREATE allocates immutable STP ID');
$program_ids=STPI_Store::find_by_program_id($program_id); $ok(count($program_ids)===1,'Umrah candidate exists after Direct Sync CREATE');

$umrah_bad=$umrah; $umrah_bad['programs'][0]['program_id']=$program_id;
$r=$call(array('contract'=>STDS_CONTRACT,'request_id'=>'STS-CI-UMRAH-CONFLICT-001','adapter'=>'umrah','mode'=>'apply','payload'=>array('batch'=>$umrah_bad,'controls'=>array(array('source_row'=>16,'stable_id'=>$program_id,'expected_checksum_sha256'=>str_repeat('0',64))),'removals'=>array())),'nonce-umrah-conflict-001');
$ok($r->get_status()===207 && ($r->get_data()['results'][0]['operation']??'')==='CONFLICT','Umrah checksum conflict fails closed');

$umrah_update=$umrah; $umrah_update['programs'][0]['program_id']=$program_id; $umrah_update['programs'][0]['pricing']['entries'][0]['amount']=2410; $umrah_update['export_id']='ci-direct-sync-umrah-update';
$r=$call(array('contract'=>STDS_CONTRACT,'request_id'=>'STS-CI-UMRAH-UPDATE-001','adapter'=>'umrah','mode'=>'apply','payload'=>array('batch'=>$umrah_update,'controls'=>array(array('source_row'=>16,'stable_id'=>$program_id,'expected_checksum_sha256'=>$program_checksum)),'removals'=>array())),'nonce-umrah-update-00001'); $data=$r->get_data();
$ok($r->get_status()===200 && ($data['results'][0]['operation']??'')==='UPDATE','Umrah UPDATE succeeds with expected checksum');
$program_checksum=$data['results'][0]['checksum'];
$r=$call(array('contract'=>STDS_CONTRACT,'request_id'=>'STS-CI-UMRAH-ARCHIVE-001','adapter'=>'umrah','mode'=>'apply','payload'=>array('batch'=>array('schema_version'=>'1.0.0','export_id'=>'archive-only','generated_at'=>gmdate(DATE_W3C),'source'=>$umrah['source'],'programs'=>array()),'controls'=>array(),'removals'=>array(array('source_row'=>16,'stable_id'=>$program_id,'expected_checksum_sha256'=>$program_checksum)))),'nonce-umrah-archive-0001'); $data=$r->get_data();
$ok($r->get_status()===200 && ($data['results'][0]['operation']??'')==='ARCHIVE','Umrah explicit removal archives without delete');
$program_post=(int)STPI_Store::find_by_program_id($program_id)[0]; $program=STPI_Store::get_program($program_post);
$ok(($program['workflow']['editorial']??'')==='archived' && get_post($program_post)!==null,'Archived Umrah Program remains stored with snapshot lifecycle');
$ok(get_option('stti_v100_public_master',null)===$before['public_master'],'Direct Sync never changes Tour Public Master');

$wpdb->delete($tours['audit'],array('stable_id'=>$tour_id),array('%s')); $wpdb->delete($tours['tours'],array('stable_id'=>$tour_id),array('%s')); update_option('stti_next_sequence',$before['tour_seq'],false);
wp_delete_post($program_post,true);
$event_ids_after=get_posts(array('post_type'=>'stpi_event','post_status'=>'any','posts_per_page'=>-1,'fields'=>'ids'));
foreach(array_diff($event_ids_after,$before_event_ids) as $event_id) wp_delete_post($event_id,true);
update_option('stpi_next_program_number',$before['program_seq'],false);
$wpdb->query($wpdb->prepare("DELETE FROM {$sync_table} WHERE request_id LIKE %s",'STS-CI-%'));
$after=array(
 'tour_count'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM {$tours['tours']}"),
 'tour_audit'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM {$tours['audit']}"),
 'tour_seq'=>(int)get_option('stti_next_sequence',1),
 'program_seq'=>(int)get_option('stpi_next_program_number',1),
 'program_count'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type='stpi_program'"),
 'event_count'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type='stpi_event'"),
 'sync_count'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM {$sync_table}"),
);
$ok($after['tour_count']===$before['tour_count'] && $after['tour_audit']===$before['tour_audit'] && $after['tour_seq']===$before['tour_seq'],'Tour runtime cleanup restores tables and sequence');
$ok($after['program_count']===$before['program_count'] && $after['program_seq']===$before['program_seq'] && $after['event_count']===$before['event_count'],'Umrah runtime cleanup restores Programs, audit events and sequence');
$ok($after['sync_count']===$before['sync_count'],'Direct Sync idempotency table cleanup restores baseline');
WP_CLI::success('Unified Google Sheets Direct Sync runtime: PASS');
