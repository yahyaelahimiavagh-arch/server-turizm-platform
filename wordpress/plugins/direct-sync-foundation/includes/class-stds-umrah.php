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
            if($program_id!=='')$program['program_id']=$program_id;
            $single=$normalized; $single['programs']=array($program);
            $validation=STPI_Validator::validate_batch($single);
            if(!empty($validation['errors'])){ $results[]=self::result($row,$program_id,'ERROR','',self::messages($validation['errors'])); continue; }
            $plan=STPI_Store::plan_program($source,$program);
            if(($plan['operation']??'')==='CONFLICT'){ $results[]=self::result($row,$program_id,'CONFLICT','',array((string)$plan['reason'])); continue; }
            if($program_id!==''){
                $ids=STPI_Store::find_by_program_id($program_id);
                if(count($ids)!==1){ $results[]=self::result($row,$program_id,'CONFLICT','',array('Stable Program ID did not resolve to exactly one candidate.')); continue; }
                $current=(string)get_post_meta((int)$ids[0],'_stpi_payload_hash',true);
                if(!preg_match('/^[a-f0-9]{64}$/D',$expected) || !hash_equals($current,$expected)){ $results[]=self::result($row,$program_id,'CONFLICT',$current,array('CHECKSUM_MISMATCH')); continue; }
            } elseif($expected!=='') { $results[]=self::result($row,'','CONFLICT','',array('New Program must not supply an expected checksum.')); continue; }
            if($mode==='validate'){ $results[]=self::result($row,$program_id,(string)$plan['operation'],$program_id?self::checksum($program_id):'',array()); continue; }
            $summary=STPI_Store::import_batch($single,gmdate(DATE_W3C),'Google Sheets Direct Sync');
            if(!empty($summary['errors'])){ $results[]=self::result($row,$program_id,'ERROR','',array_map('strval',$summary['errors'])); continue; }
            $new_id=(string)($summary['program_ids'][0]??$program_id); $op=!empty($summary['created'])?'CREATE':(!empty($summary['updated'])?'UPDATE':'UNCHANGED');
            $results[]=self::result($row,$new_id,$op,self::checksum($new_id),array());
        }
        foreach((array)($payload['removals']??array()) as $removal){
            if(!is_array($removal))continue; $results[]=self::archive($source,$removal,$mode);
        }
        return array('adapter'=>'umrah','results'=>$results);
    }
    private static function archive($source,$removal,$mode){
        $program_id=strtoupper(trim((string)($removal['stable_id']??''))); $expected=strtolower(trim((string)($removal['expected_checksum_sha256']??''))); $row=absint($removal['source_row']??0);
        $ids=STPI_Store::find_by_program_id($program_id); if(count($ids)!==1)return self::result($row,$program_id,'CONFLICT','',array('Archive target not found.'));
        $post_id=(int)$ids[0]; $current=(string)get_post_meta($post_id,'_stpi_payload_hash',true); if(!preg_match('/^[a-f0-9]{64}$/D',$expected)||!hash_equals($current,$expected))return self::result($row,$program_id,'CONFLICT',$current,array('CHECKSUM_MISMATCH'));
        $program=STPI_Store::get_program($post_id); if(($program['workflow']['editorial']??'')==='archived')return self::result($row,$program_id,'UNCHANGED',$current,array());
        if($mode==='validate')return self::result($row,$program_id,'ARCHIVE',$current,array());
        $source_row=absint($program['provenance']['source_row']??0); if(!$row || $source_row!==$row)return self::result($row,$program_id,'CONFLICT',$current,array('SOURCE_ROW_MISMATCH'));
        $intent=array('schema'=>'st-direct-sync-removal/v1','marked_at'=>gmdate(DATE_W3C),'source'=>$source,'removal'=>array('program_code'=>(string)($program['program_code']??''),'source_row'=>$row,'source_ref'=>(string)($program['provenance']['source_ref']??''),'reason'=>'programi_kaldir'));
        update_post_meta($post_id,'_stpi_source_removal_intent',$intent); STPI_Audit::log('source_removal_intent_marked',$program_id,array('source_row'=>$row,'reason'=>'programi_kaldir','via'=>'direct_sync'));
        $out=STPI_Store::transition($post_id,'archive'); if(is_wp_error($out)){ delete_post_meta($post_id,'_stpi_source_removal_intent'); return self::result($row,$program_id,'ERROR',$current,array($out->get_error_message())); }
        return self::result($row,$program_id,'ARCHIVE',self::checksum($program_id),array());
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
    private static function result($row,$id,$op,$checksum,$errors){ return array('source_row'=>$row,'stable_id'=>$id?:null,'operation'=>$op,'checksum'=>$checksum?:null,'errors'=>array_values($errors)); }
    private static function messages($issues){ $out=array(); foreach((array)$issues as $i)$out[]=is_array($i)?(string)($i['message']??wp_json_encode($i)):(string)$i; return $out; }
}
