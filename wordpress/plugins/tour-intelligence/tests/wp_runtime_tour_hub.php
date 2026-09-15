<?php
if (!defined('ABSPATH')) { exit(1); }

function stti_hub_rt_assert($condition,$label){
    if(!$condition){fwrite(STDERR,"FAIL: {$label}\n");exit(1);} echo "PASS: {$label}\n";
}

stti_hub_rt_assert(function_exists('stti_v110_hub_records'),'hub runtime loaded');
stti_hub_rt_assert(function_exists('stti_v110_render_hub'),'hub renderer loaded');

$tables=stti_tables();
$table=$tables['tours'];
global $wpdb;
$ids=array('STT-900001','STT-900002','STT-900003','STT-900004','STT-900005');
foreach($ids as $id){$wpdb->delete($table,array('stable_id'=>$id),array('%s'));}

$v100_options=array('stti_v100_public_master','stti_v100_public_route','stti_v100_indexation','stti_v100_sitemap','stti_v100_schema','stti_v100_canonical');
$v100_before=array();foreach($v100_options as $name){$v100_before[$name]=get_option($name,null);}
$hub_before=get_option('stti_v110_hub_master',null);
delete_option('stti_v110_hub_master');
stti_hub_rt_assert(stti_v110_hub_enabled()===false,'hub master defaults OFF');

function stti_hub_rt_insert($id,$title,$editorial,$temporal,$start,$end,$updated,$availability='open'){
    global $wpdb;$table=stti_tables()['tours'];
    $payload=array(
        'identity'=>array('public_title'=>$title,'language'=>'tr-TR'),
        'lifecycle'=>array('editorial'=>$editorial,'schedule'=>$start!==''?'scheduled':'tentative','availability'=>$availability),
        'destinations'=>array('primary_country'=>'Testland','countries'=>array('Testland'),'cities'=>array('Alpha','Beta')),
        'route'=>array('summary'=>'Alpha → Beta','stops'=>array(array('stop_id'=>'R1','city'=>'Alpha'),array('stop_id'=>'R2','city'=>'Beta'))),
        'pricing'=>array('type'=>'exact','amount'=>999,'currency'=>'EUR','basis'=>'unknown'),
    );
    if($start!=='')$payload['date']=array('mode'=>'exact','precision'=>'exact','start_date'=>$start,'end_date'=>$end,'duration_days'=>5,'duration_nights'=>4);
    $json=wp_json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    $wpdb->insert($table,array(
        'stable_id'=>$id,'schema_version'=>'1.1.0','public_title'=>$title,'slug'=>strtolower($id),
        'editorial'=>$editorial,'schedule_status'=>$start!==''?'scheduled':'tentative','availability'=>$availability,
        'temporal'=>$temporal,'source_completeness'=>'source_partial','payload'=>$json,'checksum'=>hash('sha256',$json),
        'created_by'=>0,'created_at'=>$updated,'updated_at'=>$updated,
    ),array('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%d','%s','%s'));
}

stti_hub_rt_insert('STT-900001','Future Alpha','approved','upcoming','2099-02-01','2099-02-05','2098-10-01 10:00:00');
stti_hub_rt_insert('STT-900002','Past Tour','approved','past','2098-01-01','2098-01-05','2098-10-02 10:00:00');
stti_hub_rt_insert('STT-900003','Needs Review','needs_review','upcoming','2099-03-01','2099-03-05','2098-10-03 10:00:00');
stti_hub_rt_insert('STT-900004','Undated Approved','approved','undated','','','2098-10-05 10:00:00','on_request');
stti_hub_rt_insert('STT-900005','Future Sold Out','approved','upcoming','2099-04-01','2099-04-05','2098-10-04 10:00:00','sold_out');

$records=stti_v110_hub_records('2099-01-01');
$test_records=array_values(array_filter($records,static function($r){return strpos((string)($r['stable_id']??''),'STT-900')===0;}));
$got=array_map(static function($r){return $r['stable_id'];},$test_records);
stti_hub_rt_assert($got===array('STT-900001','STT-900005','STT-900004'),'eligible future tours sorted dated-first');
stti_hub_rt_assert(!in_array('STT-900002',$got,true),'past tour excluded');
stti_hub_rt_assert(!in_array('STT-900003',$got,true),'needs-review tour excluded');
stti_hub_rt_assert(($test_records[1]['availability']??'')==='Kontenjan dolu','sold-out state remains visible');
stti_hub_rt_assert(($test_records[2]['date']??'')==='Tarih yakında','approved undated tour retained after dated tours');

$html=stti_v110_render_hub($test_records);
stti_hub_rt_assert(strpos($html,'Future Alpha')!==false,'eligible dated tour rendered');
stti_hub_rt_assert(strpos($html,'Undated Approved')!==false,'eligible undated tour rendered');
stti_hub_rt_assert(strpos($html,'Past Tour')===false,'past tour absent from renderer');
stti_hub_rt_assert(strpos($html,'Needs Review')===false,'unapproved tour absent from renderer');

update_option('stti_v110_hub_master','1',false);
stti_hub_rt_assert(stti_v110_hub_enabled()===true,'hub master can be explicitly enabled');
foreach($v100_options as $name){stti_hub_rt_assert(get_option($name,null)===$v100_before[$name],$name.' unchanged by hub runtime');}

foreach($ids as $id){$wpdb->delete($table,array('stable_id'=>$id),array('%s'));}
if($hub_before===null)delete_option('stti_v110_hub_master');else update_option('stti_v110_hub_master',$hub_before,false);

echo "Tour Hub v1.1 runtime: PASS\n";
