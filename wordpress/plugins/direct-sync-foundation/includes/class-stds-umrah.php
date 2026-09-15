<?php
if (!defined('ABSPATH')) { exit; }
final class STDS_Umrah {
    public static function handle($envelope) {
        $loaded=self::ensure_runtime(); if(is_wp_error($loaded)) return $loaded;
        $payload=is_array($envelope['payload']??null)?$envelope['payload']:array();
        $batch=is_array($payload['batch']??null)?$payload['batch']:array();
        $controls=is_array($payload['controls']??null)?$payload['controls']:array();
        $control_map=array();
        foreach($controls as $c){ if(!is_array($c))continue; $row=absint($c['source_row']??0); if($row)$control_map[$row]=$c; }
        $normalized=STPI_Contract::normalize_batch($batch);
        $source=is_array($normalized['source']??null)?$normalized['source']:array();
        $mode=(string)($envelope['mode']??'validate'); $results=array();
        foreach(array_values($normalized['programs']??array()) as $program){
            if(!is_array($program))continue;
            $row=absint($program['provenance']['source_row']??0); $control=$control_map[$row]??array();
            $program_id=strtoupper(trim((string)($control['stable_id']??($program['program_id']??''))));
            $expected=strtolower(trim((string)($control['expected_checksum_sha256']??'')));

            // Stable IDs returned by WordPress are concurrency/identity controls, not
            // Google Sheets source content. Never let a sidecar STP-* alter the source
            // payload hash; source hashing must remain stable across no-change retries.
            $source_program=$program;
            $source_program['program_id']=null;
            $single=$normalized; $single['programs']=array($source_program);

            $validation=STPI_Validator::validate_batch($single);
            if(!empty($validation['errors'])){ $results[]=self::result($row,$program_id,'ERROR','',self::messages($validation['errors'])); continue; }
            $plan=STPI_Store::plan_program($source,$source_program);
            if(($plan['operation']??'')==='CONFLICT'){ $results[]=self::result($row,$program_id,'CONFLICT','',array((string)$plan['reason'])); continue; }

            $target_post=0;
            if($program_id!==''){
                $ids=STPI_Store::find_by_program_id($program_id);
                if(count($ids)!==1){ $results[]=self::result($row,$program_id,'CONFLICT','',array('Stable Program ID did not resolve to exactly one candidate.')); continue; }
                $target_post=(int)$ids[0];
                $planned_post=(int)($plan['post_id']??0);
                $planned_id=(string)($plan['program_id']??'');
                if(!$planned_post || $target_post!==$planned_post || $planned_id!==$program_id){
                    $results[]=self::result($row,$program_id,'CONFLICT','',array('STABLE_ID_SOURCE_MISMATCH')); continue;
                }
                $current=(string)get_post_meta($target_post,'_stpi_payload_hash',true);
                if(!preg_match('/^[a-f0-9]{64}$/D',$expected) || !hash_equals($current,$expected)){ $results[]=self::result($row,$program_id,'CONFLICT',$current,array('CHECKSUM_MISMATCH')); continue; }
            } elseif($expected!=='') { $results[]=self::result($row,'','CONFLICT','',array('New Program must not supply an expected checksum.')); continue; }

            $target_id=$program_id!==''?$program_id:(string)($plan['program_id']??'');
            $impact=$target_id!==''?self::public_impact($target_id,$target_post):null;
            $public_operation=self::public_operation((string)$plan['operation']);

            // v0.1.3 production safety: a canonical UPDATE of an already registered
            // public Program would demote editorial state to needs_review and can make
            // the live Hub card disappear. Surface the impact during validate, but
            // fail closed before any apply mutation. Recovery/re-publication remains a
            // separate explicit Program Intelligence + Publishing Integration action.
            if($mode==='validate'){
                $results[]=self::result($row,$target_id,$public_operation,$target_id?self::checksum($target_id):'',array(),$impact);
                continue;
            }
            if(($plan['operation']??'')==='UPDATE_CANDIDATE' && is_array($impact) && !empty($impact['protected'])){
                $results[]=self::result($row,$target_id,'CONFLICT',$target_id?self::checksum($target_id):'',array('PUBLIC_PROGRAM_UPDATE_REQUIRES_CONTROLLED_REVIEW'),$impact);
                continue;
            }

            $summary=STPI_Store::import_batch($single,gmdate(DATE_W3C),'Google Sheets Direct Sync');
            if(!empty($summary['errors'])){ $results[]=self::result($row,$target_id,'ERROR','',array_map('strval',$summary['errors']),$impact); continue; }
            $new_id=(string)($summary['program_ids'][0]??$target_id); $op=!empty($summary['created'])?'CREATE':(!empty($summary['updated'])?'UPDATE':'UNCHANGED');
            $results[]=self::result($row,$new_id,$op,self::checksum($new_id),array(),$impact);
        }
        foreach((array)($payload['removals']??array()) as $removal){
            if(!is_array($removal))continue; $results[]=self::archive($source,$removal,$mode);
        }
        return array('adapter'=>'umrah','results'=>$results);
    }

    private static function public_impact($program_id,$post_id=0){
        $registry=get_option('stppi_registry',array());
        $cfg=(is_array($registry)&&isset($registry[$program_id])&&is_array($registry[$program_id]))?$registry[$program_id]:array();
        $registry_mode=(string)($cfg['mode']??'');
        $protected=in_array($registry_mode,array('public_noindex','indexable'),true);
        $editorial='';
        if($post_id){
            $current=STPI_Store::get_program($post_id);
            $editorial=(string)($current['workflow']['editorial']??'');
        }
        return array(
            'protected'=>$protected,
            'registry_mode'=>$registry_mode?:null,
            'editorial'=>$editorial?:null,
            'public_master'=>(bool)get_option('stppi_public_master',false),
            'hub_bridge'=>(bool)get_option('stppi_hub_bridge_enabled',false),
            'apply_blocked'=>$protected,
            'reason'=>$protected?'PUBLIC_PROGRAM_UPDATE_REQUIRES_CONTROLLED_REVIEW':null,
        );
    }

    private static function archive($source,$removal,$mode){
        $program_id=strtoupper(trim((string)($removal['stable_id']??''))); $expected=strtolower(trim((string)($removal['expected_checksum_sha256']??''))); $row=absint($removal['source_row']??0);
        $ids=STPI_Store::find_by_program_id($program_id); if(count($ids)!==1)return self::result($row,$program_id,'CONFLICT','',array('Archive target not found.'));
        $post_id=(int)$ids[0]; $current=(string)get_post_meta($post_id,'_stpi_payload_hash',true); if(!preg_match('/^[a-f0-9]{64}$/D',$expected)||!hash_equals($current,$expected))return self::result($row,$program_id,'CONFLICT',$current,array('CHECKSUM_MISMATCH'));
        $impact=self::public_impact($program_id,$post_id);
        if(is_array($impact)&&!empty($impact['protected']))return self::result($row,$program_id,'CONFLICT',$current,array('PUBLIC_PROGRAM_ARCHIVE_REQUIRES_CONTROLLED_REVIEW'),$impact);
        $program=STPI_Store::get_program($post_id); if(($program['workflow']['editorial']??'')==='archived')return self::result($row,$program_id,'UNCHANGED',$current,array(),$impact);
        if($mode==='validate')return self::result($row,$program_id,'ARCHIVE',$current,array(),$impact);
        $source_row=absint($program['provenance']['source_row']??0); if(!$row || $source_row!==$row)return self::result($row,$program_id,'CONFLICT',$current,array('SOURCE_ROW_MISMATCH'),$impact);
        $intent=array('schema'=>'st-direct-sync-removal/v1','marked_at'=>gmdate(DATE_W3C),'source'=>$source,'removal'=>array('program_code'=>(string)($program['program_code']??''),'source_row'=>$row,'source_ref'=>(string)($program['provenance']['source_ref']??''),'reason'=>'programi_kaldir'));
        update_post_meta($post_id,'_stpi_source_removal_intent',$intent); STPI_Audit::log('source_removal_intent_marked',$program_id,array('source_row'=>$row,'reason'=>'programi_kaldir','via'=>'direct_sync'));
        $out=STPI_Store::transition($post_id,'archive'); if(is_wp_error($out)){ delete_post_meta($post_id,'_stpi_source_removal_intent'); return self::result($row,$program_id,'ERROR',$current,array($out->get_error_message()),$impact); }
        return self::result($row,$program_id,'ARCHIVE',self::checksum($program_id),array(),$impact);
    }
    private static function public_operation($operation){
        if($operation==='CREATE_CANDIDATE')return 'CREATE';
        if($operation==='UPDATE_CANDIDATE')return 'UPDATE';
        return in_array($operation,array('UNCHANGED','CONFLICT'),true)?$operation:'ERROR';
    }
    private static function ensure_runtime(){
        if(class_exists('STPI_Contract')&&class_exists('STPI_Validator')&&class_exists('STPI_Store')&&class_exists('STPI_Audit'))return true;
        if(!defined('STPI_DIR'))return new WP_Error('stds_umrah_dependency','Program Intelligence plugin is not active.',array('status'=>503));
        foreach(array('class-stpi-contract.php','class-stpi-hotel-adapter.php','class-stpi-hotel-directory.php','class-stpi-validator.php','class-stpi-audit.php','class-stpi-lifecycle.php','class-stpi-store.php') as $file){ $path=STPI_DIR.'includes/'.$file; if(!is_file($path))return new WP_Error('stds_umrah_dependency','Program Intelligence runtime file is missing.',array('status'=>503)); require_once $path; }
        if(!post_type_exists('stpi_program')) register_post_type('stpi_program',array('public'=>false,'publicly_queryable'=>false,'show_ui'=>false,'show_in_rest'=>false,'exclude_from_search'=>true,'has_archive'=>false,'rewrite'=>false,'supports'=>array('title','revisions')));
        if(!post_type_exists('stpi_event')) register_post_type('stpi_event',array('public'=>false,'publicly_queryable'=>false,'show_ui'=>false,'show_in_rest'=>false,'exclude_from_search'=>true,'has_archive'=>false,'rewrite'=>false,'supports'=>array('title','editor')));
        return true;
    }
    private static function checksum($program_id){ $ids=STPI_Store::find_by_program_id($program_id); return count($ids)===1?(string)get_post_meta((int)$ids[0],'_stpi_payload_hash',true):''; }
    private static function result($row,$id,$op,$checksum,$errors,$public_impact=null){
        $out=array('source_row'=>$row,'stable_id'=>$id?:null,'operation'=>$op,'checksum'=>$checksum?:null,'errors'=>array_values($errors));
        if(is_array($public_impact))$out['public_impact']=$public_impact;
        return $out;
    }
    private static function messages($issues){ $out=array(); foreach((array)$issues as $i)$out[]=is_array($i)?(string)($i['message']??wp_json_encode($i)):(string)$i; return $out; }
}
