<?php
/**
 * Expedited, opt-in-by-source archival for an EXPLICIT Programı Kaldır flag.
 * It never handles physically absent rows (which retain the slower 2.6 safety path).
 * Requires two independently authenticated snapshots with different generated_at values,
 * with a minimum 60-second period since the first observation and exact Program identity.
 * Never renders output or alters STPPI's public templates.
 */
if (!defined('ABSPATH')) exit;
final class ST2L_Fast_Archive {
 const HOLD_SECONDS = 60;
 const MAX_FRESH_SECONDS = 180;
 const PENDING_OPT = 'st2l_fast_archive_pending';
 const REPORT_OPT = 'st2l_fast_archive_report';

 public static function pending() {
   $p=get_option(self::PENDING_OPT,array());
   return is_array($p)?$p:array();
 }
 public static function count(){return count(self::pending());}

 // Deliberately ignore only the removed flag. ALL other fields, UUIDs, IDs and row
 // ordering must be identical for a removal-only snapshot to bypass a full scan.
 public static function only_flags_changed($old,$current) {
   if(!is_array($old)||!is_array($current)||!isset($old['programs'],$old['hotels'])||!isset($current['programs'],$current['hotels']))return false;
   if(!is_array($old['programs'])||!is_array($current['programs'])||!is_array($old['hotels'])||!is_array($current['hotels']))return false;
   if(count($old['programs'])!==count($current['programs']) || count($old['hotels'])!==count($current['hotels']))return false;
   $a=$old['programs'];$b=$current['programs'];$changed=false;
   foreach($a as $i=>$row){
     if(!isset($b[$i])||!is_array($row)||!is_array($b[$i]))return false;
     if((bool)($row['removed']??false)!==(bool)($b[$i]['removed']??false))$changed=true;
     unset($a[$i]['removed'],$b[$i]['removed']);
   }
   return $changed && wp_json_encode($a)===wp_json_encode($b) && wp_json_encode($old['hotels'])===wp_json_encode($current['hotels']);
 }

 private static function flag_rows($snapshot){
   $rows=array();
   if(!is_array($snapshot)||!is_array($snapshot['programs']??null))return $rows;
   foreach($snapshot['programs'] as $e){
     if(!is_array($e)||empty($e['removed'])||!ST2L_Core::valid_uuid($e['uuid']??null))continue;
     $id=trim((string)($e['stable_id']??''));$code=trim((string)($e['fields']['code']??''));
     if(!preg_match('/^STP-\d{6}$/D',$id)||$code==='')continue;
     $rows[(string)$e['uuid']]=$e;
   }
   return $rows;
 }

 public static function observe($snapshot){
   if(!get_option('st2l_write_enabled',false))return;
   if(!is_array($snapshot)||!empty($snapshot['_identity_issues']))return;
   $token=trim((string)($snapshot['generated_at']??''));
   if($token===''||strlen($token)>80)return; // 2 independent source samples required
   $now=time();$flagged=self::flag_rows($snapshot);$reg=ST2L_Core::reg('programs');
   $pending=self::pending();$changed=false;
   // A removed checkbox may have been UNCHECKED. Cancel the intent immediately.
   foreach($pending as $uuid=>$state){
     if(!isset($flagged[$uuid]) || (string)($state['stable_id']??'')!==(string)$flagged[$uuid]['stable_id'] || (string)($state['code']??'')!==trim((string)$flagged[$uuid]['fields']['code'])){
       unset($pending[$uuid]);$changed=true;
     }
   }
   foreach($flagged as $uuid=>$entry){
     // Do not stage a record until the original full sync has bound it.
     $r=$reg[$uuid]??null;
     if(!is_array($r)||empty($r['post_id'])||!empty($r['archived'])||($r['stable_id']??'')!==$entry['stable_id'])continue;
     if(!isset($pending[$uuid])){
       $pending[$uuid]=array('stable_id'=>(string)$entry['stable_id'],'code'=>trim((string)$entry['fields']['code']),
         'first_seen'=>$now,'first_token'=>$token,'last_token'=>$token,'last_received'=>$now,'confirmations'=>1);
       $changed=true;
     }elseif((string)($pending[$uuid]['last_token']??'')!==$token){
       // A second distinct snapshot must be received *after* the 60-second
       // hold; receiving two rapid-fire snapshots may never trigger archive.
       if($now-(int)($pending[$uuid]['first_seen']??$now)>=self::HOLD_SECONDS){
         $pending[$uuid]['confirmations']=(int)($pending[$uuid]['confirmations']??1)+1;
       }
       $pending[$uuid]['last_token']=$token;$pending[$uuid]['last_received']=$now;$changed=true;
     }
   }
   if($changed){
     update_option(self::PENDING_OPT,$pending,false);
     if(!$pending){$report=get_option(self::REPORT_OPT,array());$report=is_array($report)?$report:array();unset($report['last_error'],$report['error_key']);update_option(self::REPORT_OPT,$report,false);}
   }
   if($pending)self::schedule();
   self::update_fast_stats();
 }

 public static function schedule(){
   $pending=self::pending();if(!$pending || !get_option('st2l_write_enabled',false))return;
   // Wake at the EARLIEST eligible timestamp. A fresh signed second
   // sample can also finalize on its own REST request.
   $due=null;
   foreach($pending as $state){$candidate=max(time()+1,(int)($state['first_seen']??time())+self::HOLD_SECONDS);
     $due=$due===null?$candidate:min($due,$candidate);
   }
   if($due!==null && !wp_next_scheduled('st2l_fast_archive_job'))wp_schedule_single_event($due,'st2l_fast_archive_job');
 }

 public static function run_due(){
   if(!get_option('st2l_write_enabled',false))return;
   // Share the SAME mutex as regular ST2L_Core::run_queue to avoid stale-registry writes.
   if(!add_option('st2l_running',time(),'','no')){
     $since=(int)get_option('st2l_running',0);
     if(time()-$since<180){self::schedule_after(15);return;}
     delete_option('st2l_running');
     if(!add_option('st2l_running',time(),'','no'))return;
   }
   try{self::apply_due_locked();}
   catch(Throwable $e){ST2L_Core::log('fast-archive','',$e->getMessage());}
   finally{delete_option('st2l_running');}
 }
 private static function schedule_after($s){if(!wp_next_scheduled('st2l_fast_archive_job'))wp_schedule_single_event(time()+$s,'st2l_fast_archive_job');}

 private static function apply_due_locked(){
   $target=get_option('st2l_target',array());
   if(!is_array($target)||!empty($target['_identity_issues']))return;
   $pending=self::pending();if(!$pending)return;
   $flagged=self::flag_rows($target);$reg=ST2L_Core::reg('programs');$now=time();$modified=false;
   $report=get_option(self::REPORT_OPT,array());$report=is_array($report)?$report:array();
   foreach($pending as $uuid=>$p){
     if(!isset($flagged[$uuid])|| (string)$flagged[$uuid]['stable_id']!==(string)($p['stable_id']??'') || trim((string)$flagged[$uuid]['fields']['code'])!==(string)($p['code']??'')){
       unset($pending[$uuid]);$modified=true;continue;
     }
     if(empty($reg[$uuid])||!is_array($reg[$uuid])||!empty($reg[$uuid]['archived'])){
       unset($pending[$uuid]);$modified=true;continue;
     }
     if(($now-(int)($p['first_seen']??0))<self::HOLD_SECONDS || (int)($p['confirmations']??0)<2)continue;
     if($now-(int)($p['last_received']??0)>self::MAX_FRESH_SECONDS)continue;
     // Each archival operation checks UUID, Stable ID AND current website code.
     $bound=ST2L_Programs::bind_removed($flagged[$uuid],$reg[$uuid]);
     if(is_wp_error($bound)){
       self::report_error($report,$p,$uuid,$bound->get_error_message());continue;
     }
     if(!is_array($bound)){
       self::report_error($report,$p,$uuid,'No unique bound Program found');continue;
     }
     $result=ST2L_Programs::archive($bound);
     if(is_wp_error($result)){
       self::report_error($report,$p,$uuid,$result->get_error_message());continue;
     }
     $reg[$uuid]['archived']=true;$reg[$uuid]['archived_at']=$now;
     $reg[$uuid]['missing_since']=0;$reg[$uuid]['missing_scans']=0;
     unset($pending[$uuid]);$modified=true;
     // Invalidate only the Umrah hub and affected program cache on LiteSpeed.
     do_action('litespeed_purge_post',(int)$reg[$uuid]['post_id']);
     do_action('litespeed_purge_url','/umre-1/');
     $report['last_archived']=array('at'=>gmdate('c'),'uuid'=>$uuid,'stable_id'=>$p['stable_id']);
     unset($report['last_error']);
   }
   if($modified){ST2L_Core::save_reg('programs',$reg);update_option(self::PENDING_OPT,$pending,false);}
   update_option(self::REPORT_OPT,$report,false);
   self::update_fast_stats();
   // Do not keep polling the complete registry. The NEXT authenticated Sheet
   // snapshot supplies any missing second confirmation; WP cron is opportunistic.
   if($pending){
     foreach($pending as $p){if((int)($p['confirmations']??0)>=2 && ($now-(int)($p['first_seen']??0))<self::HOLD_SECONDS){self::schedule_after(self::HOLD_SECONDS);break;}}
   }
 }

 private static function report_error(&$report,$state,$uuid,$msg){
   $key=hash('sha256',(string)$uuid.'|'.(string)$msg);
   if(($report['error_key']??'')!==$key){ST2L_Core::log('fast-archive',$uuid,$msg);$report['error_key']=$key;}
   $report['last_error']=array('at'=>gmdate('c'),'uuid'=>$uuid,'stable_id'=>$state['stable_id'],'message'=>$msg);
 }

 public static function update_fast_stats(){
   $stats=get_option('st2l_last_stats',array());
   $target=get_option('st2l_target',array());
   if(!is_array($stats)||!is_array($target)||!isset($target['programs']))return;
   if((string)($stats['version']??'')!==(string)get_option('st2l_target_hash',''))return;
   $queue=get_option('st2l_queue',array());
   // A full active batch owns the authoritative stats. Avoid marking it
   // complete merely because the explicit fast-archive flag was finalized.
   if(is_array($queue)&&!empty($queue['phase']))return;
   $present=array();
   foreach((array)$target['programs'] as $e){
     if(!is_array($e)||!ST2L_Core::valid_uuid($e['uuid']??null))continue;
     if(empty($e['removed']))$present[(string)$e['uuid']]=true;
   }
   $missing=0;
   foreach(ST2L_Core::reg('programs') as $uuid=>$r){
     if(empty($r['archived']) && empty($present[$uuid]))$missing++;
   }
   $report=get_option(self::REPORT_OPT,array());$hasError=!empty($report['last_error']);
   $stats['pending_program_archives']=$missing;
   $stats['complete']=($missing===0 && (int)($stats['pending_hotel_archives']??0)===0
     && (int)($stats['identity_issues']??0)===0 && !$hasError && (int)($stats['errors']??0)===0);
   if($hasError)$stats['errors']=max(1,(int)($stats['errors']??0));
   $stats['at']=gmdate('c');
   update_option('st2l_last_stats',$stats,false);
   if($stats['complete'])update_option('st2l_last_success',gmdate('c'),false);
 }

}
