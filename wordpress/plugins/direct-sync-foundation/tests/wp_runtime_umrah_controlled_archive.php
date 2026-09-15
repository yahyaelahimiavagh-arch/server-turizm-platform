<?php
/** End-to-end controlled public/noindex Umrah archive proof for Direct Sync v0.1.4. */
if (!defined('ABSPATH') || !defined('WP_CLI')) throw new RuntimeException('Run through WP-CLI only.');
$ok=static function($condition,$message){ if(!$condition) throw new RuntimeException($message); WP_CLI::log('PASS: '.$message); };
wp_set_current_user(1);

$before_seq=(int)get_option('stpi_next_program_number',1);
$before_program_ids=get_posts(array('post_type'=>'stpi_program','post_status'=>'any','posts_per_page'=>-1,'fields'=>'ids'));
$before_event_ids=get_posts(array('post_type'=>'stpi_event','post_status'=>'any','posts_per_page'=>-1,'fields'=>'ids'));
$before_registry=get_option('stppi_registry',null);
$before_master=get_option('stppi_public_master',null);
$before_hub=get_option('stppi_hub_bridge_enabled',null);
$before_hotel_links=get_option('stppi_hotel_links_enabled',null);
$hotel_posts=array();

if(!post_type_exists('sthi_hotel')) register_post_type('sthi_hotel',array('public'=>false,'show_ui'=>false,'supports'=>array('title')));
add_filter('sthi_hotel_public_url',static function($url,$post_id){
    $id=(string)get_post_meta($post_id,'_sthi_hotel_id',true);
    return $id ? 'https://stds.test/otel/'.strtolower($id).'/' : '';
},10,2);
foreach(array(
    array('STH-910001','Archive CI Madinah Hotel','Madinah'),
    array('STH-910002','Archive CI Makkah Hotel','Makkah'),
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
$batch['source']['document_ref']='ci-controlled-archive-sheet';
$batch['source']['worksheet']='Home';
$batch['export_id']='ci-controlled-archive-bootstrap';
$p=&$batch['programs'][0];
$p['program_id']=null;
$p['provenance']['source_type']='google_sheets';
$p['provenance']['source_ref']='Home row 20';
$p['provenance']['source_row']=20;
$p['stays'][0]['hotel_id']='STH-910001';
$p['stays'][1]['hotel_id']='STH-910002';
$p['inclusions']=array('CI controlled archive verified service');
unset($p);

$normalized=STPI_Contract::normalize_batch($batch);
$summary=STPI_Store::import_batch($normalized,gmdate(DATE_W3C),'Controlled archive bootstrap');
$ok(empty($summary['errors']) && (int)$summary['created']===1,'archive fixture creates one Program candidate');
$program_id=(string)$summary['program_ids'][0];
$ids=STPI_Store::find_by_program_id($program_id);
$ok(count($ids)===1,'archive fixture has one immutable STP identity');
$post_id=(int)$ids[0];
$approved=STPI_Store::transition($post_id,'approve');
$ok($approved===true,'archive fixture passes real Program Intelligence approval');
$checksum=(string)get_post_meta($post_id,'_stpi_payload_hash',true);
$ok((bool)preg_match('/^[a-f0-9]{64}$/D',$checksum),'approved archive fixture has canonical checksum');

$cfg=array(
    'id'=>$program_id,
    'slug'=>'ci-controlled-archive-program',
    'mode'=>'public_noindex',
    'seo'=>true,
    'post_id'=>$post_id,
    'hash'=>$checksum,
    'hotel_hash'=>'ci-hotel-hash',
    'review_token'=>wp_generate_uuid4(),
);
update_option('stppi_registry',array($program_id=>$cfg),false);
update_option('stppi_public_master',true,false);
update_option('stppi_hub_bridge_enabled',true,false);
update_option('stppi_hotel_links_enabled',false,false);
$master_live=get_option('stppi_public_master',false);
$hub_live=get_option('stppi_hub_bridge_enabled',false);
$hotel_live=get_option('stppi_hotel_links_enabled',false);

$archive_batch=array(
    'schema_version'=>STPI_SCHEMA_VERSION,
    'export_id'=>'ci-controlled-archive-request',
    'generated_at'=>gmdate(DATE_W3C),
    'source'=>array(
        'type'=>'google_sheets',
        'mode'=>'partial',
        'document_ref'=>'ci-controlled-archive-sheet',
        'worksheet'=>'Home',
        'timezone'=>'Europe/Istanbul',
    ),
    'programs'=>array(),
);
$removal=array(
    'source_row'=>20,
    'program_code'=>'219',
    'stable_id'=>$program_id,
    'expected_checksum_sha256'=>$checksum,
);
$validate=array(
    'contract'=>STDS_CONTRACT,
    'request_id'=>'STS-CI-CONTROLLED-ARCHIVE-VALIDATE-001',
    'adapter'=>'umrah',
    'mode'=>'validate',
    'payload'=>array('batch'=>$archive_batch,'controls'=>array(),'removals'=>array($removal)),
);
$validate_result=STDS_Umrah_Gateway::handle($validate);
$validate_row=$validate_result['results'][0]??array();
$ok(($validate_row['operation']??'')==='ARCHIVE','public/noindex KALDIR validates as ARCHIVE');
$ok(empty($validate_row['errors']),'controlled archive preflight has no errors');
$ok(!empty($validate_row['public_impact']['protected']),'preflight recognizes protected public/noindex route');
$ok(!empty($validate_row['public_impact']['controlled_archive_supported']),'preflight explicitly marks controlled archive supported');
$ok(!empty($validate_row['public_impact']['requires_confirmation']),'preflight requires operator confirmation');
$ok((STPI_Store::get_program($post_id)['workflow']['editorial']??'')==='approved','validate does not archive canonical Program');
$ok((get_option('stppi_registry',array())[$program_id]['mode']??'')==='public_noindex','validate does not close public route');

$without_ack=$validate;
$without_ack['request_id']='STS-CI-CONTROLLED-ARCHIVE-NOACK-002';
$without_ack['mode']='apply';
$without_ack_result=STDS_Umrah_Gateway::handle($without_ack);
$without_ack_row=$without_ack_result['results'][0]??array();
$ok(($without_ack_row['operation']??'')==='CONFLICT','protected archive apply without explicit confirmation is blocked');
$ok(in_array('CONTROLLED_ARCHIVE_CONFIRMATION_REQUIRED',$without_ack_row['errors']??array(),true),'missing confirmation returns exact fail-closed error');
$ok((STPI_Store::get_program($post_id)['workflow']['editorial']??'')==='approved','blocked apply does not mutate canonical Program');
$ok((get_option('stppi_registry',array())[$program_id]['mode']??'')==='public_noindex','blocked apply does not mutate route mode');

// Indexable Programs are intentionally excluded from automatic archive because SEO removal needs its own review policy.
$indexable_cfg=$cfg;
$indexable_cfg['mode']='indexable';
update_option('stppi_registry',array($program_id=>$indexable_cfg),false);
$indexable=$validate;
$indexable['request_id']='STS-CI-CONTROLLED-ARCHIVE-INDEXABLE-003';
$indexable_result=STDS_Umrah_Gateway::handle($indexable);
$indexable_row=$indexable_result['results'][0]??array();
$ok(($indexable_row['operation']??'')==='CONFLICT','indexable archive remains fail-closed');
$ok(in_array('INDEXABLE_PROGRAM_ARCHIVE_REQUIRES_SEO_REVIEW',$indexable_row['errors']??array(),true),'indexable route requires separate SEO review');
$ok((STPI_Store::get_program($post_id)['workflow']['editorial']??'')==='approved','indexable SEO block remains no-write');
update_option('stppi_registry',array($program_id=>$cfg),false);

$approved_removal=$removal;
$approved_removal['controlled_archive_approved']=true;
$apply=$validate;
$apply['request_id']='STS-CI-CONTROLLED-ARCHIVE-APPLY-004';
$apply['mode']='apply';
$apply['payload']['removals']=array($approved_removal);
$apply_result=STDS_Umrah_Gateway::handle($apply);
$apply_row=$apply_result['results'][0]??array();
$ok(($apply_row['operation']??'')==='ARCHIVE','confirmed public/noindex archive applies');
$ok(empty($apply_row['errors']),'confirmed controlled archive has no error');
$ok(!empty($apply_row['public_impact']['controlled_archived']),'apply reports public exposure was controlled-archived');
$stored=STPI_Store::get_program($post_id);
$ok(($stored['workflow']['editorial']??'')==='archived','canonical Program transitions to archived');
$new_checksum=(string)get_post_meta($post_id,'_stpi_payload_hash',true);
$ok($new_checksum!==$checksum,'archive advances canonical checksum');
$registry_after=get_option('stppi_registry',array());
$ok(($registry_after[$program_id]['mode']??'')==='prepared','public/noindex route is demoted to PREPARED');
$ok(($registry_after[$program_id]['hash']??'')===$new_checksum,'prepared registry keeps current archived checksum');
$ok(get_option('stppi_public_master',false)===$master_live,'Program Public Master remains unchanged');
$ok(get_option('stppi_hub_bridge_enabled',false)===$hub_live,'Hub bridge remains unchanged');
$ok(get_option('stppi_hotel_links_enabled',false)===$hotel_live,'Hotel relation gate remains unchanged');
$intent=get_post_meta($post_id,'_stpi_source_removal_intent',true);
$ok(is_array($intent)&&($intent['removal']['reason']??'')==='programi_kaldir','source removal intent is preserved as archive evidence');
$ok((int)($intent['removal']['source_row']??0)===20,'archive evidence preserves exact source row');

$retry_removal=$removal;
$retry_removal['expected_checksum_sha256']=$new_checksum;
$retry=$validate;
$retry['request_id']='STS-CI-CONTROLLED-ARCHIVE-RETRY-005';
$retry['payload']['removals']=array($retry_removal);
$retry_result=STDS_Umrah_Gateway::handle($retry);
$retry_row=$retry_result['results'][0]??array();
$ok(($retry_row['operation']??'')==='UNCHANGED','post-archive retry is UNCHANGED');
$ok(($retry_row['checksum']??'')===$new_checksum,'post-archive retry returns archived checksum');

if($before_registry===null) delete_option('stppi_registry'); else update_option('stppi_registry',$before_registry,false);
if($before_master===null) delete_option('stppi_public_master'); else update_option('stppi_public_master',$before_master,false);
if($before_hub===null) delete_option('stppi_hub_bridge_enabled'); else update_option('stppi_hub_bridge_enabled',$before_hub,false);
if($before_hotel_links===null) delete_option('stppi_hotel_links_enabled'); else update_option('stppi_hotel_links_enabled',$before_hotel_links,false);
wp_delete_post($post_id,true);
foreach($hotel_posts as $hotel_post_id) wp_delete_post($hotel_post_id,true);
$after_event_ids=get_posts(array('post_type'=>'stpi_event','post_status'=>'any','posts_per_page'=>-1,'fields'=>'ids'));
foreach(array_diff($after_event_ids,$before_event_ids) as $event_id) wp_delete_post((int)$event_id,true);
update_option('stpi_next_program_number',$before_seq,false);
$after_program_ids=get_posts(array('post_type'=>'stpi_program','post_status'=>'any','posts_per_page'=>-1,'fields'=>'ids'));
$ok(count($after_program_ids)===count($before_program_ids),'runtime cleanup restores Program count');
$ok((int)get_option('stpi_next_program_number',1)===$before_seq,'runtime cleanup restores Program sequence');
WP_CLI::success('Controlled public/noindex Umrah archive runtime: PASS');
