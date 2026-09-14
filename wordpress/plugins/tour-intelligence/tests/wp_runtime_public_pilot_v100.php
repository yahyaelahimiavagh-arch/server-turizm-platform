<?php
/** STTI v1.0.0 Controlled Public Tour Pilot — disposable runtime evidence. */
if (!defined('ABSPATH') || !defined('WP_CLI')) throw new RuntimeException('Run through WP-CLI only.');
$ok = static function($condition, $message) { if (!$condition) throw new RuntimeException($message); WP_CLI::log('PASS: ' . $message); };
wp_set_current_user(1); global $wpdb; $tables=stti_tables(); $cfg=stti_v100_public_config();
$option_names=array_values($cfg['options']);$missing='__STTI_V100_MISSING__';$option_before=array();
foreach($option_names as $name){$option_before[$name]=get_option($name,$missing);delete_option($name);}
$sequence_before=(int)get_option('stti_next_sequence',0);$tours_before=(int)$wpdb->get_var("SELECT COUNT(*) FROM {$tables['tours']}");$audit_before=(int)$wpdb->get_var("SELECT COUNT(*) FROM {$tables['audit']}");
$fixture=json_decode((string)file_get_contents(STTI_DIR.'tests/fixtures/STT-000001-v090-real-tour.json'),true);$payload=is_array($fixture['canonical_payload']??null)?$fixture['canonical_payload']:array();
$ok($payload!==array(),'v0.9 accepted real-tour fixture loads');$payload['lifecycle']['editorial']='approved';
$ok(stti_get_candidate('STT-000001')===null,'disposable runtime starts without public-pilot candidate');
$json=wp_json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);$checksum=hash('sha256',$json);$now=current_time('mysql');
$inserted=$wpdb->insert($tables['tours'],array('stable_id'=>'STT-000001','schema_version'=>STTI_SCHEMA_VERSION,'public_title'=>$payload['identity']['public_title'],'slug'=>'','editorial'=>'approved','schedule_status'=>$payload['lifecycle']['schedule'],'availability'=>$payload['lifecycle']['availability'],'temporal'=>$payload['lifecycle']['temporal'],'source_completeness'=>$payload['provenance']['source_completeness'],'payload'=>$json,'checksum'=>$checksum,'created_by'=>1,'created_at'=>$now,'updated_at'=>$now),array('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%d','%s','%s'));
$ok($inserted===1,'approved pilot candidate persists in disposable runtime');
$ready=stti_v100_candidate_readiness();$ok(($ready['ready']??false)===true,'editorial-approved real Tour passes public content readiness');
$surface=stti_v100_surface_state();$ok(!$surface['route']&&!$surface['indexation']&&!$surface['sitemap']&&!$surface['schema']&&!$surface['canonical'],'all public/SEO surfaces default OFF');
$ok(stti_v100_is_exact_public_request('/turlar/buyuk-iran-kultur-turu/')===true,'exact pilot path matches');$ok(stti_v100_is_exact_public_request('/turlar/buyuk-iran-kultur-turu/extra/')===false,'non-allowlisted path does not match');
$r=stti_v100_update_gates(array('master'=>true),1,true);$ok(!is_wp_error($r)&&$r['master']===true&&$r['route']===false,'Public Master can be armed without exposing route');$ok(stti_v100_surface_state()['route']===false,'master alone cannot expose Tour');
$r=stti_v100_update_gates(array('master'=>true,'route'=>true),1,true);$ok(!is_wp_error($r),'exact route gate can be enabled for ready allowlisted Tour');$surface=stti_v100_surface_state();
$ok($surface['route']===true&&!$surface['indexation']&&!$surface['canonical']&&!$surface['schema']&&!$surface['sitemap'],'route can be public while every SEO exposure remains independently OFF');
$robots=stti_v100_robots_filter(array());$ok(isset($robots['noindex'])&&isset($robots['follow'])&&!isset($robots['index']),'public route stays noindex until indexation gate');
$r=stti_v100_update_gates(array('master'=>true,'route'=>true,'indexation'=>true),1,true);$ok(!is_wp_error($r),'indexation can be enabled independently');$surface=stti_v100_surface_state();
$ok($surface['indexation']===true&&!$surface['canonical']&&!$surface['schema']&&!$surface['sitemap'],'indexation does not auto-enable canonical/schema/sitemap');$robots=stti_v100_robots_filter(array('noindex'=>true));$ok(isset($robots['index'])&&isset($robots['follow'])&&!isset($robots['noindex']),'indexation gate deterministically changes robots');
$r=stti_v100_update_gates(array('master'=>true,'route'=>true,'indexation'=>true,'canonical'=>true,'schema'=>true,'sitemap'=>true),1,true);$ok(!is_wp_error($r),'all individually approved SEO gates can be enabled');$surface=stti_v100_surface_state();
$ok($surface['route']&&$surface['indexation']&&$surface['canonical']&&$surface['schema']&&$surface['sitemap'],'all six effective gates are ON only after explicit request');
ob_start();stti_v100_print_canonical();$canonical=ob_get_clean();$ok(strpos($canonical,stti_v100_public_url())!==false,'canonical output is exact allowlisted URL');
ob_start();stti_v100_print_schema();$schema=ob_get_clean();$ok(strpos($schema,'"@type":"WebPage"')!==false&&strpos($schema,stti_v100_public_url())!==false,'schema output is minimal and exact-route scoped');
if(!function_exists('wp_get_sitemap_providers'))throw new RuntimeException('WordPress sitemap API unavailable.');$providers=wp_get_sitemap_providers();if(!isset($providers['stti-tours'])){stti_v100_register_sitemap_provider();$providers=wp_get_sitemap_providers();}
$ok(isset($providers['stti-tours']),'custom STTI sitemap provider is registered');$urls=$providers['stti-tours']->get_url_list(1);$ok(count($urls)===1&&($urls[0]['loc']??'')===stti_v100_public_url(),'sitemap exposes exactly one allowlisted Tour URL');
$r=stti_v100_update_gates(array('master'=>false,'route'=>true,'indexation'=>true,'canonical'=>true,'schema'=>true,'sitemap'=>true),1,true);$ok(!is_wp_error($r),'fast rollback accepts one master-off action');$surface=stti_v100_surface_state();
$ok(!$surface['route']&&!$surface['indexation']&&!$surface['canonical']&&!$surface['schema']&&!$surface['sitemap'],'Public Master OFF collapses every child gate immediately');$ok($providers['stti-tours']->get_url_list(1)===array(),'sitemap becomes empty immediately after master rollback');
$events=$wpdb->get_results($wpdb->prepare("SELECT event FROM {$tables['audit']} WHERE stable_id=%s",'STT-000001'),ARRAY_A);$event_names=array_map(static fn($row)=>$row['event'],is_array($events)?$events:array());$ok(in_array('v100_public_pilot_gates_updated',$event_names,true),'release gate changes are audit-evidenced');
$wpdb->delete($tables['audit'],array('stable_id'=>'STT-000001'),array('%s'));$wpdb->delete($tables['tours'],array('stable_id'=>'STT-000001'),array('%s'));update_option('stti_next_sequence',$sequence_before,false);
foreach($option_before as $name=>$value){if($value===$missing)delete_option($name);else update_option($name,$value,false);}
$ok((int)$wpdb->get_var("SELECT COUNT(*) FROM {$tables['tours']}")===$tours_before,'Tour table restored exactly after v1.0 runtime');$ok((int)$wpdb->get_var("SELECT COUNT(*) FROM {$tables['audit']}")===$audit_before,'Audit table restored exactly after v1.0 runtime');$ok((int)get_option('stti_next_sequence',0)===$sequence_before,'Stable-ID sequence restored exactly after v1.0 runtime');
foreach($option_before as $name=>$value)$ok(get_option($name,$missing)===$value,'Release option restored: '.$name);
WP_CLI::log('STTI v1.0.0 Controlled Public Tour Pilot runtime: PASS');
