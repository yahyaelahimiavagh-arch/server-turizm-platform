<?php
if (!defined('ABSPATH')) { exit; }
final class STDS_Tour {
    public static function handle($envelope) {
        if(!function_exists('stti_import_dry_run')||!function_exists('stti_get_candidate')) return new WP_Error('stds_tour_dependency','Tour Intelligence direct-sync runtime is unavailable.',array('status'=>503));
        $payload=is_array($envelope['payload']??null)?$envelope['payload']:array(); $mode=(string)($envelope['mode']??'validate'); $results=array();
        foreach((array)($payload['documents']??array()) as $doc){ if(!is_array($doc))continue; $results[]=self::upsert($doc,$mode); }
        foreach((array)($payload['archives']??array()) as $arc){ if(!is_array($arc))continue; $results[]=self::archive($arc,$mode); }
        return array('adapter'=>'tour','results'=>$results);
    }
    private static function upsert($doc,$mode){
        $target=is_array($doc['target']??null)?$doc['target']:array(); $sid=(string)($target['stable_id']??''); $existing=$sid?stti_get_candidate($sid):null;
        if(!isset($doc['tour'])||!is_array($doc['tour']))$doc['tour']=array(); if(!isset($doc['tour']['lifecycle'])||!is_array($doc['tour']['lifecycle']))$doc['tour']['lifecycle']=array();
        $doc['tour']['lifecycle']['editorial']=$existing?(string)$existing['editorial']:'needs_review';
        $plan=stti_import_dry_run($doc); $class=(string)($plan['classification']??'INVALID');
        if(in_array($class,array('INVALID','CONFLICT'),true))return array('stable_id'=>$sid?:null,'operation'=>$class,'checksum'=>$plan['current_checksum']??null,'errors'=>self::plan_errors($plan));
        if($mode==='validate')return array('stable_id'=>($plan['proposed_stable_id']??$sid),'operation'=>$class,'checksum'=>$plan['proposed_checksum']??null,'errors'=>array());
        if($class==='UNCHANGED'){ stti_audit_event($sid,'direct_sync_unchanged',json_decode((string)$existing['payload'],true),json_decode((string)$existing['payload'],true)); return array('stable_id'=>$sid,'operation'=>'UNCHANGED','checksum'=>$existing['checksum'],'errors'=>array()); }
        $stable_id=$sid;
        if($class==='CREATE'){ $stable_id=stti_allocate_stable_id(); if(is_wp_error($stable_id))return array('stable_id'=>null,'operation'=>'ERROR','checksum'=>null,'errors'=>array($stable_id->get_error_message())); }
        $payload=$plan['canonical_preview']; $payload['stable_id']=$stable_id; if(isset($payload['publication'])&&is_array($payload['publication']))$payload['publication']=array('renderer'=>'private','public_route'=>false,'hub_visible'=>false,'homepage_visible'=>false,'indexable'=>false,'sitemap'=>false);
        $json=wp_json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); $checksum=hash('sha256',$json); global $wpdb; $t=stti_tables(); $now=current_time('mysql');
        $fields=self::row_fields($payload,$json,$checksum,$now);
        if($class==='CREATE'){ $fields['stable_id']=$stable_id; $fields['created_by']=get_current_user_id(); $fields['created_at']=$now; $ok=$wpdb->insert($t['tours'],$fields); if(!$ok)return array('stable_id'=>$stable_id,'operation'=>'ERROR','checksum'=>null,'errors'=>array('Tour insert failed.')); stti_audit_event($stable_id,'direct_sync_created',null,$payload); }
        else { $before=json_decode((string)$existing['payload'],true); $ok=$wpdb->update($t['tours'],$fields,array('stable_id'=>$stable_id)); if($ok===false)return array('stable_id'=>$stable_id,'operation'=>'ERROR','checksum'=>$existing['checksum'],'errors'=>array('Tour update failed.')); stti_audit_event($stable_id,'direct_sync_updated',$before,$payload); }
        return array('stable_id'=>$stable_id,'operation'=>$class,'checksum'=>$checksum,'errors'=>array());
    }
    private static function archive($arc,$mode){
        $sid=strtoupper(trim((string)($arc['stable_id']??''))); $expected=strtolower(trim((string)($arc['expected_checksum_sha256']??''))); $row=stti_get_candidate($sid);
        if(!$row)return array('stable_id'=>$sid,'operation'=>'CONFLICT','checksum'=>null,'errors'=>array('Archive target not found.'));
        if(!preg_match('/^[a-f0-9]{64}$/D',$expected)||!hash_equals((string)$row['checksum'],$expected))return array('stable_id'=>$sid,'operation'=>'CONFLICT','checksum'=>$row['checksum'],'errors'=>array('CHECKSUM_MISMATCH'));
        if($row['editorial']==='archived')return array('stable_id'=>$sid,'operation'=>'UNCHANGED','checksum'=>$row['checksum'],'errors'=>array());
        if($mode==='validate')return array('stable_id'=>$sid,'operation'=>'ARCHIVE','checksum'=>$row['checksum'],'errors'=>array());
        $before=json_decode((string)$row['payload'],true); $p=$before; if(!isset($p['lifecycle'])||!is_array($p['lifecycle']))$p['lifecycle']=array(); $p['lifecycle']['editorial']='archived'; $p['lifecycle']['availability']='closed'; if(isset($p['publication'])&&is_array($p['publication']))$p['publication']=array('renderer'=>'private','public_route'=>false,'hub_visible'=>false,'homepage_visible'=>false,'indexable'=>false,'sitemap'=>false);
        $json=wp_json_encode($p,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); $checksum=hash('sha256',$json); global $wpdb; $t=stti_tables(); $ok=$wpdb->update($t['tours'],array('editorial'=>'archived','availability'=>'closed','payload'=>$json,'checksum'=>$checksum,'updated_at'=>current_time('mysql')),array('stable_id'=>$sid)); if($ok===false)return array('stable_id'=>$sid,'operation'=>'ERROR','checksum'=>$row['checksum'],'errors'=>array('Tour archive update failed.')); stti_audit_event($sid,'direct_sync_archived',$before,$p); return array('stable_id'=>$sid,'operation'=>'ARCHIVE','checksum'=>$checksum,'errors'=>array());
    }
    private static function row_fields($p,$json,$checksum,$now){ $id=is_array($p['identity']??null)?$p['identity']:array(); $life=is_array($p['lifecycle']??null)?$p['lifecycle']:array(); $prov=is_array($p['provenance']??null)?$p['provenance']:array(); return array('schema_version'=>STTI_SCHEMA_VERSION,'public_title'=>(string)($id['public_title']??''),'slug'=>(string)($id['slug']??''),'editorial'=>(string)($life['editorial']??'needs_review'),'schedule_status'=>(string)($life['schedule']??'tentative'),'availability'=>(string)($life['availability']??'on_request'),'temporal'=>(string)($life['temporal']??'undated'),'source_completeness'=>(string)($prov['source_completeness']??'source_minimal'),'payload'=>$json,'checksum'=>$checksum,'updated_at'=>$now); }
    private static function plan_errors($plan){ $out=array(); foreach((array)($plan['validation']['errors']??array()) as $e)$out[]=(string)$e; if(!empty($plan['conflict']))$out[]=(string)$plan['conflict']; return array_values(array_unique($out)); }
}
