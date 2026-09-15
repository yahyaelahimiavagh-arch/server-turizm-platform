<?php
if (!defined('ABSPATH')) { exit; }
final class STDS_REST {
    public static function register_routes(){ register_rest_route('server-turizm/v1','/direct-sync',array('methods'=>'POST','callback'=>array(__CLASS__,'handle'),'permission_callback'=>'__return_true')); }
    public static function handle(WP_REST_Request $request){
        $raw=(string)$request->get_body(); if($raw===''||strlen($raw)>2097152)return new WP_Error('stds_body','Sync body is empty or exceeds 2 MB.',array('status'=>400));
        $doc=json_decode($raw,true); if(!is_array($doc))return new WP_Error('stds_json','Invalid JSON body.',array('status'=>400));
        $contract=(string)($doc['contract']??''); $request_id=trim((string)($doc['request_id']??'')); $adapter=(string)($doc['adapter']??''); $mode=(string)($doc['mode']??'validate');
        if($contract!==STDS_CONTRACT)return new WP_Error('stds_contract','Unsupported Direct Sync contract.',array('status'=>400));
        if(!preg_match('/^[A-Za-z0-9._:-]{12,96}$/D',$request_id))return new WP_Error('stds_request_id','Invalid request_id.',array('status'=>400));
        if(!in_array($adapter,array('umrah','tour'),true)||!in_array($mode,array('validate','apply'),true))return new WP_Error('stds_adapter','Invalid adapter or mode.',array('status'=>400));
        $auth=STDS_Auth::verify($request,$raw); if(is_wp_error($auth))return $auth;
        $existing=STDS_Store::get($request_id);
        if($existing){
            if(!hash_equals((string)$existing['body_hash'],$auth['body_hash']))return new WP_Error('stds_idempotency_conflict','request_id was already used with different content.',array('status'=>409));
            if($existing['state']==='completed'&&$existing['response_json']){ $response=json_decode((string)$existing['response_json'],true); if(is_array($response)){ $response['idempotent_replay']=true; return new WP_REST_Response($response,200); } }
            return new WP_Error('stds_processing','The same request is already processing.',array('status'=>409));
        }
        $reserved=STDS_Store::reserve($request_id,$auth['nonce_hash'],$auth['body_hash'],$adapter); if(is_wp_error($reserved))return $reserved;
        $result=$adapter==='umrah'?STDS_Umrah_Gateway::handle($doc):STDS_Tour::handle($doc);
        if(is_wp_error($result)){ $error=array('contract'=>STDS_CONTRACT,'request_id'=>$request_id,'adapter'=>$adapter,'ok'=>false,'errors'=>array($result->get_error_message())); STDS_Store::complete($request_id,$error,'failed'); return $result; }
        $results=(array)($result['results']??array()); $has_errors=false; $public_exposure_changed=false;
        foreach($results as $r){
            if(in_array((string)($r['operation']??''),array('ERROR','CONFLICT','INVALID'),true))$has_errors=true;
            if(!empty($r['public_impact']['controlled_archived']))$public_exposure_changed=true;
        }
        $response=array('contract'=>STDS_CONTRACT,'request_id'=>$request_id,'adapter'=>$adapter,'mode'=>$mode,'ok'=>!$has_errors,'results'=>$results,'idempotent_replay'=>false,'public_exposure_changed'=>$public_exposure_changed);
        STDS_Store::complete($request_id,$response,'completed'); return new WP_REST_Response($response,$has_errors?207:200);
    }
}
