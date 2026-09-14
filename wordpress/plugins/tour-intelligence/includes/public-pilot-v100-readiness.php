<?php
/** Read-only readiness model for STTI v1.0.0. */
if(!defined('ABSPATH')){exit;}
function stti_v100_candidate_readiness($row=null){
    $cfg=stti_v100_public_config();$reasons=array();$row=is_array($row)?$row:stti_get_candidate($cfg['stable_id']);
    if(!$row)return array('ready'=>false,'reasons'=>array('candidate_missing'),'row'=>null,'payload'=>array(),'model'=>array());
    if(($row['stable_id']??'')!==$cfg['stable_id'])$reasons[]='stable_id_mismatch';
    $payload=stti_v070_existing_payload($row);if(!is_array($payload)||!$payload)$reasons[]='payload_missing';
    if(stti_v070_text($row['editorial']??'')!=='approved'||stti_v070_text($payload['lifecycle']['editorial']??'')!=='approved')$reasons[]='editorial_not_approved';
    if(stti_v070_text($payload['identity']['public_title']??'')==='')$reasons[]='title_missing';
    $start=stti_v070_text($payload['date']['start_date']??'');$end=stti_v070_text($payload['date']['end_date']??'');
    if(!preg_match('/^\d{4}-\d{2}-\d{2}$/',$start)||!preg_match('/^\d{4}-\d{2}-\d{2}$/',$end))$reasons[]='exact_dates_required';
    if(stti_v070_text($payload['lifecycle']['temporal']??'')==='completed')$reasons[]='tour_completed';
    $model=stti_v080_renderer_model($payload,$cfg['stable_id'],(string)($row['checksum']??''));
    if(($model['ready']??false)!==true)$reasons[]='renderer_not_ready';
    if(count($model['route_stops']??array())<1)$reasons[]='route_missing';
    $price_type=stti_v070_text($payload['pricing']['type']??'on_request');
    if(in_array($price_type,array('exact','from'),true)){
        if(!is_numeric($payload['pricing']['amount']??null))$reasons[]='price_amount_missing';
        if(stti_v070_text($payload['pricing']['currency']??'')==='')$reasons[]='price_currency_missing';
    }
    return array('ready'=>!$reasons,'reasons'=>array_values(array_unique($reasons)),'row'=>$row,'payload'=>$payload,'model'=>$model);
}
function stti_v100_surface_state($row=null){
    $g=stti_v100_gate_state();$r=stti_v100_candidate_readiness($row);$route=$r['ready']&&$g['master']&&$g['route'];
    return array('contract'=>'STTI-PUBLIC-PILOT-1.0.0','stable_id'=>stti_v100_public_config()['stable_id'],'public_url'=>stti_v100_public_url(),'content_ready'=>(bool)$r['ready'],'readiness_reasons'=>$r['reasons'],'route'=>(bool)$route,'indexation'=>(bool)($route&&$g['indexation']),'canonical'=>(bool)($route&&$g['canonical']),'schema'=>(bool)($route&&$g['schema']),'sitemap'=>(bool)($route&&$g['indexation']&&$g['sitemap']),'gates'=>$g,'row'=>$r['row'],'payload'=>$r['payload'],'model'=>$r['model']);
}
