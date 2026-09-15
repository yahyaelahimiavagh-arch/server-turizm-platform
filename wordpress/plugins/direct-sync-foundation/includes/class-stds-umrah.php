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

            if($mode==='validate'){
                if(($plan['operation']??'')==='UPDATE_CANDIDATE' && is_array($impact) && !empty($impact['protected'])){
                    $prospective=self::prospective_live_gate($source_program,$target_id);
                    if(is_wp_error($prospective)){
                        $impact['auto_refresh_supported']=false;
                        $impact['reason']='LIVE_UPDATE_VALIDATION_BLOCKED';
                        $results[]=self::result($row,$target_id,'CONFLICT',$target_id?self::checksum($target_id):'',array($prospective->get_error_message()),$impact);
                        continue;
                    }
                    $impact['auto_refresh_supported']=true;
                    $impact['reason']='LIVE_UPDATE_AUTO_REFRESH';
                }
                $results[]=self::result($row,$target_id,$public_operation,$target_id?self::checksum($target_id):'',array(),$impact);
                continue;
            }

            if(($plan['operation']??'')==='UPDATE_CANDIDATE' && is_array($impact) && !empty($impact['protected'])){
                $results[]=self::apply_live_update($single,$source_program,$plan,$target_id,$target_post,$row,$impact);
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

    private static function prospective_live_gate($source_program,$program_id){
        $candidate=$source_program;
        $candidate['program_id']=$program_id;
        if(!isset($candidate['workflow'])||!is_array($candidate['workflow']))$candidate['workflow']=array();
        $candidate['workflow']['editorial']='approved';
        if(!isset($candidate['provenance'])||!is_array($candidate['provenance']))$candidate['provenance']=array();
        $candidate['provenance']['verified_at']=gmdate(DATE_W3C);
        $candidate['provenance']['verified_by']='Google Sheets Direct Sync';
        $report=STPI_Validator::validate_batch(array(
            'schema_version'=>STPI_SCHEMA_VERSION,
            'source'=>array('type'=>'wordpress','mode'=>'partial'),
            'programs'=>array($candidate),
        ));
        if(!empty($report['errors']) || ($report['programs'][0]['publish_gate']??'')!=='READY'){
            $messages=self::messages($report['errors']??array());
            return new WP_Error('stds_live_update_not_ready',$messages?implode(' | ',$messages):'Updated live Program is not READY for automatic approval.');
        }
        return true;
    }

    private static function apply_live_update($single,$source_program,$plan,$program_id,$post_id,$row,$impact){
        if(!$post_id || !$program_id)return self::result($row,$program_id,'CONFLICT','',array('LIVE_UPDATE_TARGET_MISSING'),$impact);
        $current=STPI_Store::get_program($post_id);
        if(($current['workflow']['editorial']??'')!=='approved'){
            $impact['auto_refresh_supported']=false;
            $impact['reason']='LIVE_UPDATE_REQUIRES_EXISTING_APPROVAL';
            return self::result($row,$program_id,'CONFLICT',self::checksum($program_id),array('LIVE_UPDATE_REQUIRES_EXISTING_APPROVAL'),$impact);
        }
        $gate=self::prospective_live_gate($source_program,$program_id);
        if(is_wp_error($gate)){
            $impact['auto_refresh_supported']=false;
            $impact['reason']='LIVE_UPDATE_VALIDATION_BLOCKED';
            return self::result($row,$program_id,'CONFLICT',self::checksum($program_id),array($gate->get_error_message()),$impact);
        }

        $snapshot=method_exists('STPI_Store','identity_repair_snapshot')?STPI_Store::identity_repair_snapshot($post_id):null;
        if(is_wp_error($snapshot)||!is_array($snapshot))return self::result($row,$program_id,'ERROR',self::checksum($program_id),array('LIVE_UPDATE_SNAPSHOT_FAILED'),$impact);
        $registry_before=get_option('stppi_registry',array());
        if(!is_array($registry_before) || empty($registry_before[$program_id]) || !is_array($registry_before[$program_id])){
            return self::result($row,$program_id,'CONFLICT',self::checksum($program_id),array('LIVE_UPDATE_REGISTRY_MISSING'),$impact);
        }
        $cfg_before=$registry_before[$program_id];
        if(!in_array((string)($cfg_before['mode']??''),array('public_noindex','indexable'),true)){
            return self::result($row,$program_id,'CONFLICT',self::checksum($program_id),array('LIVE_UPDATE_REGISTRY_MODE_CHANGED'),$impact);
        }
        $master_before=get_option('stppi_public_master',false);
        $hub_before=get_option('stppi_hub_bridge_enabled',false);

        $summary=STPI_Store::import_batch($single,gmdate(DATE_W3C),'Google Sheets Direct Sync');
        if(!empty($summary['errors']) || (int)($summary['updated']??0)!==1){
            self::rollback_live_update($snapshot,$registry_before,$program_id,'canonical_import_failed');
            return self::result($row,$program_id,'ERROR',self::checksum($program_id),array_merge(array('LIVE_UPDATE_IMPORT_FAILED'),array_map('strval',$summary['errors']??array())),$impact);
        }

        $approved=STPI_Store::transition($post_id,'approve');
        if(is_wp_error($approved)){
            self::rollback_live_update($snapshot,$registry_before,$program_id,'automatic_reapproval_failed');
            return self::result($row,$program_id,'ERROR',self::checksum($program_id),array('LIVE_UPDATE_AUTO_APPROVAL_FAILED: '.$approved->get_error_message()),$impact);
        }

        $ppi=self::ensure_publishing_runtime();
        if(is_wp_error($ppi)){
            self::rollback_live_update($snapshot,$registry_before,$program_id,'publishing_runtime_missing');
            return self::result($row,$program_id,'ERROR',self::checksum($program_id),array($ppi->get_error_message()),$impact);
        }

        $model=STPPI_Renderer::model($cfg_before,false);
        if(is_wp_error($model)){
            self::rollback_live_update($snapshot,$registry_before,$program_id,'renderer_revalidation_failed');
            return self::result($row,$program_id,'ERROR',self::checksum($program_id),array('LIVE_UPDATE_RENDERER_BLOCKED: '.$model->get_error_message()),$impact);
        }

        $registry_after=$registry_before;
        $registry_after[$program_id]['post_id']=(int)$model['post_id'];
        $registry_after[$program_id]['hash']=(string)$model['hash'];
        $registry_after[$program_id]['hotel_hash']=(string)$model['hotel_hash'];
        $registry_after[$program_id]['mode']=(string)$cfg_before['mode'];
        $registry_after[$program_id]['seo']=array_key_exists('seo',$cfg_before)?(bool)$cfg_before['seo']:true;
        $registry_after[$program_id]['review_token']=wp_generate_uuid4();
        $registry_after[$program_id]['prepared_at']=gmdate('c');
        $registry_after[$program_id]['direct_sync_refreshed_at']=gmdate('c');
        update_option('stppi_registry',$registry_after,false);

        if(get_option('stppi_public_master',false)!==$master_before || get_option('stppi_hub_bridge_enabled',false)!==$hub_before){
            self::rollback_live_update($snapshot,$registry_before,$program_id,'public_gate_drift');
            update_option('stppi_public_master',$master_before,false);
            update_option('stppi_hub_bridge_enabled',$hub_before,false);
            return self::result($row,$program_id,'ERROR',self::checksum($program_id),array('LIVE_UPDATE_PUBLIC_GATE_DRIFT'),$impact);
        }

        STPI_Audit::log('direct_sync_live_refresh',$program_id,array(
            'source_row'=>$row,
            'registry_mode'=>(string)$cfg_before['mode'],
            'canonical_hash'=>(string)$model['hash'],
            'hotel_hash'=>(string)$model['hotel_hash'],
        ));
        $impact['auto_refresh_supported']=true;
        $impact['auto_refreshed']=true;
        $impact['apply_blocked']=false;
        $impact['reason']='LIVE_UPDATE_AUTO_REFRESHED';
        $impact['registry_hash_refreshed']=true;
        return self::result($row,$program_id,'UPDATE',self::checksum($program_id),array(),$impact);
    }

    private static function rollback_live_update($snapshot,$registry_before,$program_id,$reason){
        if(is_array($snapshot) && method_exists('STPI_Store','identity_repair_restore_snapshot'))STPI_Store::identity_repair_restore_snapshot($snapshot);
        if(is_array($registry_before))update_option('stppi_registry',$registry_before,false);
        STPI_Audit::log('direct_sync_live_refresh_rolled_back',$program_id,array('reason'=>(string)$reason));
    }

    private static function ensure_publishing_runtime(){
        if(class_exists('STPPI_Repository')&&class_exists('STPPI_Renderer'))return true;
        if(!defined('STPPI_DIR'))return new WP_Error('stds_publishing_dependency','Program Publishing Integration plugin is not active.');
        foreach(array('class-stppi-repository.php','class-stppi-renderer.php') as $file){
            $path=STPPI_DIR.'includes/'.$file;
            if(!is_file($path))return new WP_Error('stds_publishing_dependency','Program Publishing Integration runtime file is missing: '.$file);
            require_once $path;
        }
        return (class_exists('STPPI_Repository')&&class_exists('STPPI_Renderer'))?true:new WP_Error('stds_publishing_dependency','Program Publishing Integration runtime could not be loaded.');
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
            'apply_blocked'=>false,
            'auto_refresh_supported'=>$protected&&$editorial==='approved',
            'reason'=>$protected?'LIVE_UPDATE_AUTO_REFRESH':null,
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
