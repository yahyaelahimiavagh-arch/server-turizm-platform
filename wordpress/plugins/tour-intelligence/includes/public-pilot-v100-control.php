<?php
/** Gate transitions for STTI v1.0.0. */
if(!defined('ABSPATH')){exit;}
function stti_v100_validate_gate_request($requested,$row=null){
    $keys=array('master','route','indexation','sitemap','schema','canonical');$next=array();
    foreach($keys as $key)$next[$key]=!empty($requested[$key]);
    if(!$next['master'])foreach(array('route','indexation','sitemap','schema','canonical') as $key)$next[$key]=false;
    elseif(!$next['route'])foreach(array('indexation','sitemap','schema','canonical') as $key)$next[$key]=false;
    elseif(!$next['indexation'])$next['sitemap']=false;
    $errors=array();
    if($next['master']){$ready=stti_v100_candidate_readiness($row);if(!$ready['ready'])$errors[]='Candidate is not public-ready: '.implode(', ',$ready['reasons']);}
    return array('valid'=>!$errors,'gates'=>$next,'errors'=>$errors);
}
function stti_v100_update_gates($requested,$actor_id=0,$write_audit=true){
    $validation=stti_v100_validate_gate_request($requested);if(!$validation['valid'])return new WP_Error('stti_v100_gate_invalid',implode(' | ',$validation['errors']));
    $cfg=stti_v100_public_config();$before=stti_v100_gate_state();
    foreach($validation['gates'] as $key=>$enabled)update_option($cfg['options'][$key],$enabled?'1':'0',false);
    $after=stti_v100_gate_state();
    if($write_audit&&function_exists('stti_audit_event'))stti_audit_event($cfg['stable_id'],'v100_public_pilot_gates_updated',array('gates'=>$before),array('gates'=>$after,'actor_id'=>(int)$actor_id));
    return $after;
}
