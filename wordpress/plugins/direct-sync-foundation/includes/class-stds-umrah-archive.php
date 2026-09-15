<?php
if (!defined('ABSPATH')) { exit; }

/**
 * Direct Sync v0.1.4 archive gateway.
 *
 * Existing STDS_Umrah remains authoritative for CREATE/UPDATE/UNCHANGED.
 * This gateway isolates explicit Sheet KALDIR removals so an already-live
 * public/noindex Program can be archived only after validate + operator
 * confirmation, while preserving Stable ID, canonical audit history and the
 * existing global Public Master / Hub state.
 */
final class STDS_Umrah_Gateway {
    public static function handle($envelope) {
        $payload=is_array($envelope['payload']??null)?$envelope['payload']:array();
        $removals=is_array($payload['removals']??null)?$payload['removals']:array();
        if(!$removals) return STDS_Umrah::handle($envelope);

        $base=$envelope;
        if(!isset($base['payload'])||!is_array($base['payload']))$base['payload']=array();
        $base['payload']['removals']=array();
        $result=STDS_Umrah::handle($base);
        if(is_wp_error($result))return $result;

        // STDS_Umrah::handle() has already loaded the accepted Program Intelligence runtime.
        $batch=is_array($payload['batch']??null)?$payload['batch']:array();
        $normalized=STPI_Contract::normalize_batch($batch);
        $source=is_array($normalized['source']??null)?$normalized['source']:array();
        $mode=(string)($envelope['mode']??'validate');
        $results=is_array($result['results']??null)?$result['results']:array();
        foreach($removals as $removal){
            if(!is_array($removal))continue;
            $results[]=STDS_Umrah_Controlled_Archive::handle($source,$removal,$mode);
        }
        return array('adapter'=>'umrah','results'=>$results);
    }
}

final class STDS_Umrah_Controlled_Archive {
    public static function handle($source,$removal,$mode) {
        $program_id=strtoupper(trim((string)($removal['stable_id']??'')));
        $expected=strtolower(trim((string)($removal['expected_checksum_sha256']??'')));
        $row=absint($removal['source_row']??0);
        $approved=!empty($removal['controlled_archive_approved']);

        if(!preg_match('/^STP-[0-9]{6}$/D',$program_id))return self::result($row,$program_id,'CONFLICT','',array('INVALID_STABLE_PROGRAM_ID'));
        $ids=STPI_Store::find_by_program_id($program_id);
        if(count($ids)!==1)return self::result($row,$program_id,'CONFLICT','',array('Archive target not found.'));
        $post_id=(int)$ids[0];
        $current=(string)get_post_meta($post_id,'_stpi_payload_hash',true);
        if(!preg_match('/^[a-f0-9]{64}$/D',$expected)||!hash_equals($current,$expected))return self::result($row,$program_id,'CONFLICT',$current,array('CHECKSUM_MISMATCH'));

        $program=STPI_Store::get_program($post_id);
        $source_row=absint($program['provenance']['source_row']??0);
        if(!$row || $source_row!==$row)return self::result($row,$program_id,'CONFLICT',$current,array('SOURCE_ROW_MISMATCH'));

        $impact=self::public_impact($program_id,$post_id);
        if(($program['workflow']['editorial']??'')==='archived'){
            if(!empty($impact['protected']))return self::result($row,$program_id,'CONFLICT',$current,array('ARCHIVED_PROGRAM_PUBLIC_ROUTE_REQUIRES_MANUAL_REPAIR'),$impact);
            return self::result($row,$program_id,'UNCHANGED',$current,array(),$impact);
        }

        if(!empty($impact['protected'])){
            if(($impact['registry_mode']??'')==='indexable'){
                $impact['controlled_archive_supported']=false;
                $impact['reason']='INDEXABLE_ARCHIVE_REQUIRES_SEO_REVIEW';
                return self::result($row,$program_id,'CONFLICT',$current,array('INDEXABLE_PROGRAM_ARCHIVE_REQUIRES_SEO_REVIEW'),$impact);
            }
            $runtime=self::ensure_publishing_runtime();
            if(is_wp_error($runtime))return self::result($row,$program_id,'ERROR',$current,array($runtime->get_error_message()),$impact);
            $impact['controlled_archive_supported']=true;
            $impact['requires_confirmation']=true;
            $impact['reason']='CONTROLLED_PUBLIC_NOINDEX_ARCHIVE_READY';
            if($mode==='validate')return self::result($row,$program_id,'ARCHIVE',$current,array(),$impact);
            if(!$approved)return self::result($row,$program_id,'CONFLICT',$current,array('CONTROLLED_ARCHIVE_CONFIRMATION_REQUIRED'),$impact);
            return self::apply_public_noindex($source,$removal,$program,$post_id,$program_id,$row,$current,$impact);
        }

        if($mode==='validate')return self::result($row,$program_id,'ARCHIVE',$current,array(),$impact);
        return self::apply_private($source,$program,$post_id,$program_id,$row,$current,$impact);
    }

    private static function apply_private($source,$program,$post_id,$program_id,$row,$current,$impact){
        self::mark_intent($source,$program,$post_id,$program_id,$row);
        $out=STPI_Store::transition($post_id,'archive');
        if(is_wp_error($out)){
            delete_post_meta($post_id,'_stpi_source_removal_intent');
            return self::result($row,$program_id,'ERROR',$current,array($out->get_error_message()),$impact);
        }
        STPI_Audit::log('direct_sync_archive',$program_id,array('source_row'=>$row,'reason'=>'programi_kaldir','public_route'=>false));
        return self::result($row,$program_id,'ARCHIVE',self::checksum($program_id),array(),$impact);
    }

    private static function apply_public_noindex($source,$removal,$program,$post_id,$program_id,$row,$current,$impact){
        if(!method_exists('STPI_Store','identity_repair_snapshot')||!method_exists('STPI_Store','identity_repair_restore_snapshot')){
            return self::result($row,$program_id,'ERROR',$current,array('CONTROLLED_ARCHIVE_SNAPSHOT_RUNTIME_MISSING'),$impact);
        }
        $snapshot=STPI_Store::identity_repair_snapshot($post_id);
        if(is_wp_error($snapshot)||!is_array($snapshot))return self::result($row,$program_id,'ERROR',$current,array('CONTROLLED_ARCHIVE_SNAPSHOT_FAILED'),$impact);

        $registry_before=STPPI_Repository::registry();
        if(!is_array($registry_before)||empty($registry_before[$program_id])||!is_array($registry_before[$program_id])){
            return self::result($row,$program_id,'CONFLICT',$current,array('CONTROLLED_ARCHIVE_REGISTRY_MISSING'),$impact);
        }
        if((string)($registry_before[$program_id]['mode']??'')!=='public_noindex'){
            return self::result($row,$program_id,'CONFLICT',$current,array('CONTROLLED_ARCHIVE_ROUTE_MODE_CHANGED'),$impact);
        }

        $master_before=get_option('stppi_public_master',false);
        $hub_before=get_option('stppi_hub_bridge_enabled',false);
        $hotel_before=get_option('stppi_hotel_links_enabled',false);
        self::mark_intent($source,$program,$post_id,$program_id,$row);
        $out=STPI_Store::transition($post_id,'archive');
        if(is_wp_error($out)){
            delete_post_meta($post_id,'_stpi_source_removal_intent');
            return self::result($row,$program_id,'ERROR',$current,array('CONTROLLED_ARCHIVE_TRANSITION_FAILED: '.$out->get_error_message()),$impact);
        }

        $registry_after=$registry_before;
        $registry_after[$program_id]['mode']='prepared';
        $registry_after[$program_id]['hash']=self::checksum($program_id);
        $registry_after[$program_id]['review_token']=wp_generate_uuid4();
        $registry_after[$program_id]['controlled_archive_at']=gmdate('c');
        $registry_after[$program_id]['controlled_archive_reason']='programi_kaldir';
        STPPI_Repository::save_registry($registry_after);

        $drift=get_option('stppi_public_master',false)!==$master_before
            || get_option('stppi_hub_bridge_enabled',false)!==$hub_before
            || get_option('stppi_hotel_links_enabled',false)!==$hotel_before;
        $stored=STPI_Store::get_program($post_id);
        $saved_registry=STPPI_Repository::registry();
        $post_ok=(($stored['workflow']['editorial']??'')==='archived');
        $route_ok=(isset($saved_registry[$program_id])&&is_array($saved_registry[$program_id])&&($saved_registry[$program_id]['mode']??'')==='prepared');
        if($drift||!$post_ok||!$route_ok){
            self::rollback($snapshot,$registry_before,$post_id,$program_id,$master_before,$hub_before,$hotel_before,$drift?'public_gate_drift':'postcondition_failed');
            return self::result($row,$program_id,'ERROR',self::checksum($program_id),array($drift?'CONTROLLED_ARCHIVE_PUBLIC_GATE_DRIFT':'CONTROLLED_ARCHIVE_POSTCONDITION_FAILED'),$impact);
        }

        STPI_Audit::log('direct_sync_controlled_archive',$program_id,array(
            'source_row'=>$row,
            'reason'=>'programi_kaldir',
            'prior_route_mode'=>'public_noindex',
            'route_mode_after'=>'prepared',
            'public_master_unchanged'=>true,
            'hub_bridge_unchanged'=>true,
        ));
        $impact['controlled_archive_supported']=true;
        $impact['controlled_archived']=true;
        $impact['requires_confirmation']=false;
        $impact['apply_blocked']=false;
        $impact['registry_mode_after']='prepared';
        $impact['reason']='CONTROLLED_PUBLIC_NOINDEX_ARCHIVED';
        return self::result($row,$program_id,'ARCHIVE',self::checksum($program_id),array(),$impact);
    }

    private static function rollback($snapshot,$registry_before,$post_id,$program_id,$master_before,$hub_before,$hotel_before,$reason){
        STPI_Store::identity_repair_restore_snapshot($snapshot);
        STPPI_Repository::save_registry($registry_before);
        delete_post_meta($post_id,'_stpi_source_removal_intent');
        update_option('stppi_public_master',$master_before,false);
        update_option('stppi_hub_bridge_enabled',$hub_before,false);
        update_option('stppi_hotel_links_enabled',$hotel_before,false);
        STPI_Audit::log('direct_sync_controlled_archive_rolled_back',$program_id,array('reason'=>(string)$reason));
    }

    private static function mark_intent($source,$program,$post_id,$program_id,$row){
        $intent=array(
            'schema'=>'st-direct-sync-removal/v1',
            'marked_at'=>gmdate(DATE_W3C),
            'source'=>$source,
            'removal'=>array(
                'program_code'=>(string)($program['program_code']??''),
                'source_row'=>$row,
                'source_ref'=>(string)($program['provenance']['source_ref']??''),
                'reason'=>'programi_kaldir',
            ),
        );
        update_post_meta($post_id,'_stpi_source_removal_intent',$intent);
        STPI_Audit::log('source_removal_intent_marked',$program_id,array('source_row'=>$row,'reason'=>'programi_kaldir','via'=>'direct_sync_controlled_archive'));
    }

    private static function public_impact($program_id,$post_id){
        $registry=get_option('stppi_registry',array());
        $cfg=(is_array($registry)&&isset($registry[$program_id])&&is_array($registry[$program_id]))?$registry[$program_id]:array();
        $registry_mode=(string)($cfg['mode']??'');
        return array(
            'protected'=>in_array($registry_mode,array('public_noindex','indexable'),true),
            'registry_mode'=>$registry_mode?:null,
            'editorial'=>(string)(STPI_Store::get_program($post_id)['workflow']['editorial']??''),
            'public_master'=>(bool)get_option('stppi_public_master',false),
            'hub_bridge'=>(bool)get_option('stppi_hub_bridge_enabled',false),
            'apply_blocked'=>false,
            'controlled_archive_supported'=>false,
            'reason'=>null,
        );
    }

    private static function ensure_publishing_runtime(){
        if(class_exists('STPPI_Repository'))return true;
        if(!defined('STPPI_DIR'))return new WP_Error('stds_publishing_dependency','Program Publishing Integration plugin is not active.');
        $path=STPPI_DIR.'includes/class-stppi-repository.php';
        if(!is_file($path))return new WP_Error('stds_publishing_dependency','Program Publishing Integration repository runtime is missing.');
        require_once $path;
        return class_exists('STPPI_Repository')?true:new WP_Error('stds_publishing_dependency','Program Publishing Integration repository runtime could not be loaded.');
    }

    private static function checksum($program_id){
        $ids=STPI_Store::find_by_program_id($program_id);
        return count($ids)===1?(string)get_post_meta((int)$ids[0],'_stpi_payload_hash',true):'';
    }

    private static function result($row,$id,$op,$checksum,$errors,$public_impact=null){
        $out=array('source_row'=>$row,'stable_id'=>$id?:null,'operation'=>$op,'checksum'=>$checksum?:null,'errors'=>array_values($errors));
        if(is_array($public_impact))$out['public_impact']=$public_impact;
        return $out;
    }
}
