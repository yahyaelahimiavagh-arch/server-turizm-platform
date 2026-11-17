<?php
if (!defined('ABSPATH')) exit;
final class ST2L_Core {
 const CONTRACT='ST-SNAPSHOT-2.1';
 const SOURCE='1PLHeqb9ZD4z8c7kWQ1-6zDdQ38KEeaNYTf9R8DA1DVg';
 const BATCH=15;
 const MAX_BODY=8388608;
 public static function register(){
   register_rest_route('server-turizm/v2','/live',array('methods'=>'POST','callback'=>array(__CLASS__,'receive'),'permission_callback'=>'__return_true'));
 }
 public static function valid_uuid($s){return is_string($s)&&preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}$/D',$s);}
 public static function reg($group){$r=get_option('st2l_'.$group.'_registry',array());return is_array($r)?$r:array();}
 public static function save_reg($group,$registry){update_option('st2l_'.$group.'_registry',$registry,false);}
 public static function receive(WP_REST_Request $request){
   if(!class_exists('STDS_Auth'))return new WP_Error('st2_missing_auth','Direct Sync Foundation must remain active.',array('status'=>503));
   $raw=(string)$request->get_body();
   if($raw===''||strlen($raw)>self::MAX_BODY)return new WP_Error('st2_size','Invalid snapshot size',array('status'=>413));
   $auth=STDS_Auth::verify($request,$raw);if(is_wp_error($auth))return $auth;
   $nonce='st2l_nonce_'.substr((string)$auth['nonce_hash'],0,40);
   if(!add_option($nonce,time(),'','no'))return new WP_Error('st2_replay','Replay rejected.',array('status'=>409));
   // Restrict reuse without creating a high-cardinality persistent option table.
   wp_schedule_single_event(time()+600,'st2l_expire_nonce',array($nonce));
   $data=json_decode($raw,true,128);
   if(!is_array($data)||($data['contract']??'')!==self::CONTRACT||($data['sheet_id']??'')!==self::SOURCE||($data['complete']??false)!==true||!is_array($data['programs']??null)||!is_array($data['hotels']??null))return new WP_Error('st2_shape','Invalid or unauthorized snapshot.',array('status'=>400));
   if(count($data['programs'])>1500||count($data['hotels'])>1500)return new WP_Error('st2_limit','Too many records.',array('status'=>413));
   $issues=[];
   foreach(array('programs','hotels') as $group){$seen=[];$ids=[];
     foreach($data[$group] as $entry){
       if(!is_array($entry)||!self::valid_uuid($entry['uuid']??null)||!is_array($entry['fields']??null)){ $issues[]=$group.':invalid row';continue; }
       $uuid=$entry['uuid'];if(isset($seen[$uuid]))$issues[]=$group.':duplicate uuid '.$uuid;
       $seen[$uuid]=1;
       if($group==='programs'&&!empty($entry['stable_id'])){ $id=(string)$entry['stable_id'];if(!preg_match('/^STP-\d{6}$/D',$id)||isset($ids[$id]))$issues[]='Invalid/duplicate Program ID '.$id; $ids[$id]=1; }
     }
   }
   // Never archive on identity damage. The good rows are processed independently.
   $data['_identity_issues']=$issues;
   $data['_received_at']=time();
   // The source includes a different generated_at on every poll. Use only material data
   // to decide whether a running multi-batch reconciliation must restart.
   $semantic=hash('sha256',wp_json_encode(array($data['programs'],$data['hotels'],$issues)));
   $prior_hash=(string)get_option('st2l_target_hash','');
   $prior_target=get_option('st2l_target',array());
   $current_queue=get_option('st2l_queue',array());
   $last=get_option('st2l_last_stats',array());
   // Only a change to explicit removed checkboxes, after a CLEAN complete
   // baseline, may bypass the slow 15-item Hotel/Program reconciliation.
   $fast_only=($semantic!==$prior_hash) && is_array($last) && !empty($last['complete'])
      && (int)($last['errors']??0)===0 && (string)($last['version']??'')===$prior_hash
      && (empty($current_queue)||empty($current_queue['phase']))
      && empty($issues) && ST2L_Fast_Archive::only_flags_changed($prior_target,$data);
   if($semantic!==$prior_hash){
     update_option('st2l_target',$data,false);
     update_option('st2l_target_hash',$semantic,false);
     if($fast_only){
       update_option('st2l_queue',array(),false);
       $last['version']=$semantic;$last['fast_archive_only']=true;
       $last['complete']=false;$last['pending_program_archives']=0;$last['errors']=0;
       $last['at']=gmdate('c');
       update_option('st2l_last_stats',$last,false);
     } else {
       update_option('st2l_queue',array('phase'=>'hotels','cursor'=>0,'version'=>$semantic,'at'=>time(),'errors'=>0),false);
     }
   } elseif(!is_array($current_queue)||empty($current_queue['phase'])) {
     // A pending explicit checkbox is NOT a reason to rescan all 64 programs.
     // Fast archive receives its own signed follow-up snapshots independently.
     $fastPending=ST2L_Fast_Archive::count()>0;
     if(!(is_array($last)&&!empty($last['fast_archive_only'])&&$fastPending)) {
       $retry=!is_array($last)||empty($last['complete']);
       // Previously failed row updates need full replay; a clean pending
       // archive needs only the final archive phase, never 64 upserts.
       $phase=$retry && (int)($last['errors']??0)>0?'hotels':'archive';
       update_option('st2l_queue',array('phase'=>$phase,'cursor'=>0,'version'=>$semantic,'at'=>time(),'errors'=>0),false);
     }
   }
   update_option('st2l_last_received',gmdate('c'),false);
   if(!get_option('st2l_write_enabled',false))
     return new WP_REST_Response(array('ok'=>true,'queued'=>false,'paused'=>true,'issues'=>count($issues),'receipts'=>array(),'fast_archive_pending'=>0),200);
   // First register this independently authenticated snapshot for the fast
   // explicit-removal confirmation (does not delete anything during staging).
   ST2L_Fast_Archive::observe($data);
   ST2L_Fast_Archive::run_due();
   if(is_array(get_option('st2l_queue',array())) && !empty(get_option('st2l_queue',array())['phase'])){
     self::run_queue();self::schedule(5);
   }
   $reg=self::reg('programs');$receipts=[];
   foreach($data['programs'] as $e){$uuid=$e['uuid']??'';if(isset($reg[$uuid]['stable_id']))$receipts[]=array('uuid'=>$uuid,'stable_id'=>$reg[$uuid]['stable_id']);}
   return new WP_REST_Response(array('ok'=>true,'queued'=>true,'issues'=>count($issues),'receipts'=>$receipts,'fast_archive_pending'=>ST2L_Fast_Archive::count()),200);
 }
 public static function schedule($seconds=5){if(!wp_next_scheduled('st2l_process_job'))wp_schedule_single_event(time()+$seconds,'st2l_process_job');}
 public static function run_queue(){
   if(!get_option('st2l_write_enabled',false))return;
   if(!add_option('st2l_running',time(),'','no')){
     $since=(int)get_option('st2l_running',0);
     if(time()-$since<180){self::schedule(20);return;}
     delete_option('st2l_running');
     if(!add_option('st2l_running',time(),'','no'))return;
   }
   try{self::process();}catch(Throwable $e){self::log('system','', $e->getMessage());self::schedule(60);}finally{delete_option('st2l_running');}
 }
 private static function process(){
   $target=get_option('st2l_target',array());$queue=get_option('st2l_queue',array());
   if(!is_array($target)||!isset($target['programs'],$target['hotels'])||!is_array($queue))return;
   if(!empty($queue['version'])&&(string)$queue['version']!==(string)get_option('st2l_target_hash',''))return;
   $phase=(string)($queue['phase']??'hotels');$cursor=(int)($queue['cursor']??0);
   if($phase==='hotels'||$phase==='programs'){
     $items=(array)$target[$phase];$limit=min(count($items),$cursor+self::BATCH);$reg=self::reg($phase);
     for($i=$cursor;$i<$limit;$i++){
       $entry=$items[$i];if(!is_array($entry))continue;$uuid=(string)($entry['uuid']??'');
       if(!self::valid_uuid($uuid))continue;
       // If identity duplicates, quarantine both; no silent overwrite.
       $total=0;foreach($items as $candidate)if(is_array($candidate)&&($candidate['uuid']??'')===$uuid)$total++;
       if($total!==1){self::log($phase,$uuid,'Duplicate UUID: record skipped');$queue['errors']=(int)($queue['errors']??0)+1;continue;}
       if($phase==='programs' && !empty($entry['removed'])){
         $removed=ST2L_Programs::bind_removed($entry,$reg[$uuid]??null);
         if(is_wp_error($removed)){self::log('removal',$uuid,$removed->get_error_message());$queue['errors']=(int)($queue['errors']??0)+1;}
         elseif(is_array($removed)&&!isset($reg[$uuid]))$reg[$uuid]=$removed;
         continue;
       }
       try{$result=$phase==='hotels'?ST2L_Hotels::upsert($entry,$reg[$uuid]??null):ST2L_Programs::upsert($entry,$reg[$uuid]??null);
         if(is_wp_error($result)){self::log($phase,$uuid,$result->get_error_message());$queue['errors']=(int)($queue['errors']??0)+1;continue;}
         $result['last_seen']=time();$result['missing_since']=0;$result['missing_scans']=0;
         $reg[$uuid]=$result;
       }catch(Throwable $e){self::log($phase,$uuid,$e->getMessage());$queue['errors']=(int)($queue['errors']??0)+1;}
     }
     self::save_reg($phase,$reg);
     $queue['cursor']=$limit;
     if($limit>=count($items)){$queue['phase']=$phase==='hotels'?'programs':'archive';$queue['cursor']=0;}
     if((string)($queue['version']??'')===(string)get_option('st2l_target_hash',''))update_option('st2l_queue',$queue,false);
     self::schedule(8);return;
   }
   if($phase==='archive'){
     if((string)($queue['version']??'')!==(string)get_option('st2l_target_hash',''))return;
     // Confirm *absence* in two separate fully valid source snapshots at least 5 min apart.
     // A first ever scan never archives anything, and large drops require 20 minutes.
     $reg=self::reg('programs');$present=[];$explicitRemoved=[];
     foreach($target['programs'] as $entry){if(self::valid_uuid($entry['uuid']??null)){
       $present[$entry['uuid']]=empty($entry['removed']);
       if(!empty($entry['removed']))$explicitRemoved[$entry['uuid']]=true;
     }}
     $missing=0;$active=0;
     foreach($reg as $uuid=>$r){if(!empty($r['archived']))continue;$active++;if(empty($present[$uuid]))$missing++;}
     $bulk=$active>=4&&$missing/$active>0.30;$safe=empty($target['_identity_issues']) && (int)($queue['errors']??0)===0;
     $pendingProgramArchives=0;
     foreach($reg as $uuid=>$r){
       if(!empty($present[$uuid])||!empty($r['archived']))continue;
       if(!$safe)continue;
       $since=(int)($r['missing_since']??0);
       if(!$since){$r['missing_since']=time();$r['missing_scans']=1;}
       else {$r['missing_scans']=(int)($r['missing_scans']??0)+1;}
       // Explicit removed flags belong to the fast, signed-two-snapshot
       // pathway, not to the slower absence-based deletion pathway.
       if(isset($explicitRemoved[$uuid])){ $pendingProgramArchives++;$reg[$uuid]=$r;continue; }
       $time_needed=$bulk?1200:300;
       if(time()-(int)$r['missing_since']>=$time_needed&&$r['missing_scans']>=2){
         $result=ST2L_Programs::archive($r);
         if(is_wp_error($result)){self::log('archive',$uuid,$result->get_error_message());$queue['errors']=(int)($queue['errors']??0)+1;$safe=false;}
         else {$r['archived']=true;$r['archived_at']=time();}
       }
       if(empty($r['archived']))$pendingProgramArchives++;
       $reg[$uuid]=$r;
     }
     self::save_reg('programs',$reg);
     // Hotel removals are reversible. Existing manually managed Hotel Intelligence
     // entities are never unpublished solely because their Sheet row disappears.
     $hotelReg=self::reg('hotels');$hotelPresent=[];
     foreach($target['hotels'] as $hotel){if(self::valid_uuid($hotel['uuid']??null))$hotelPresent[$hotel['uuid']]=true;}
     $pendingHotelArchives=0;
     foreach($hotelReg as $uuid=>$h){
       if(isset($hotelPresent[$uuid])){ $h['missing_since']=0;$h['missing_scans']=0;$h['archived']=false;
         $hotelReg[$uuid]=$h;continue;
       }
       if(!$safe)continue;
       if(empty($h['missing_since'])){$h['missing_since']=time();$h['missing_scans']=1;}
       else $h['missing_scans']=(int)($h['missing_scans']??0)+1;
       if(time()-(int)$h['missing_since']>=300&&$h['missing_scans']>=2){
         if(!empty($h['created_by_sync'])&&get_post_type((int)$h['post_id'])==='sthi_hotel'){
           $res=wp_update_post(array('ID'=>(int)$h['post_id'],'post_status'=>'draft'),true);
           if(is_wp_error($res)){self::log('hotels',$uuid,$res->get_error_message());$hotelReg[$uuid]=$h;continue;}
         }
         $h['archived']=true;
       }
       if(empty($h['archived']))$pendingHotelArchives++;
       $hotelReg[$uuid]=$h;
     }
     self::save_reg('hotels',$hotelReg);
     $safe=$safe && (int)($queue['errors']??0)===0 && !$pendingProgramArchives && !$pendingHotelArchives;
     if($safe){
       update_option('st2l_last_success',gmdate('c'),false);
       // Use the existing STPPI renderer for eligible new records; do not replace visual markup.
       try{ST2L_Legacy_Publisher::reconcile();}
       catch(Throwable $e){self::log('native-publishing','','Original renderer sync skipped: '.$e->getMessage());}
     }
     update_option('st2l_last_stats',array('programs'=>count($target['programs']),'hotels'=>count($target['hotels']),'identity_issues'=>count((array)$target['_identity_issues']),'bulk_deletion_delay'=>$bulk,'pending_program_archives'=>$pendingProgramArchives,'pending_hotel_archives'=>$pendingHotelArchives,'errors'=>(int)($queue['errors']??0),'complete'=>$safe,'version'=>(string)get_option('st2l_target_hash',''),'at'=>gmdate('c')),false);
     update_option('st2l_queue',array(),false);
   }
 }
 public static function log($type,$id,$message){
   $items=get_option('st2l_recent_issues',array());$items=is_array($items)?$items:array();
   array_unshift($items,array('at'=>gmdate('c'),'type'=>$type,'id'=>$id,'message'=>sanitize_text_field((string)$message)));
   update_option('st2l_recent_issues',array_slice($items,0,40),false);
 }
 public static function menu(){add_management_page('Server Turizm Auto Sync','Server Auto Sync','manage_options','st2l-status',array(__CLASS__,'status'));}
 public static function status(){
   if(!current_user_can('manage_options'))wp_die('Forbidden');
   // A completed, clean previous reconciliation also qualifies for native
   // enrollment immediately after upgrading from v2.4 (no new full scan needed).
   $previous=get_option('st2l_last_stats',array());
   if(is_array($previous)&&!empty($previous['complete']) && (int)($previous['errors']??0)===0 &&
      (string)($previous['version']??'')===(string)get_option('st2l_target_hash','') &&
      empty(get_option('st2l_queue',array()))){
       try{ST2L_Legacy_Publisher::reconcile();}
       catch(Throwable $e){self::log('native-publishing','', $e->getMessage());}
   }
   if(isset($_POST['st2l_write'])&&check_admin_referer('st2l_write','st2l_write_nonce')){
      $activate=(string)wp_unslash($_POST['st2l_write'])==='1';
      if($activate && is_array(get_option('st2l_target',null))){
        $checkpoint=ST2L_Checkpoint::create();
        if(is_wp_error($checkpoint)){self::log('safety','',$checkpoint->get_error_message());}
        else {update_option('st2l_write_enabled',true,false);
        // Resuming a clean finished queue does not replay 64 Programs.
        // Keep a queued full scan if a new snapshot arrived while paused.
        $queue=get_option('st2l_queue',array());$previous=get_option('st2l_last_stats',array());
        if(!is_array($queue)||empty($queue['phase'])){
          $cleanBaseline=is_array($previous) && !empty($previous['complete'])
            && (int)($previous['errors']??0)===0
            && (string)($previous['version']??'')===(string)get_option('st2l_target_hash','');
          if(!$cleanBaseline){
            $phase=(is_array($previous) && (int)($previous['errors']??0)===0
              && (string)($previous['version']??'')===(string)get_option('st2l_target_hash',''))?'archive':'hotels';
            update_option('st2l_queue',array('phase'=>$phase,'cursor'=>0,
              'version'=>(string)get_option('st2l_target_hash',''),'at'=>time(),'errors'=>0),false);
          }
        }
        $queue=get_option('st2l_queue',array());
        if(is_array($queue)&&!empty($queue['phase'])){self::run_queue();self::schedule(8);}
        }
      }elseif(!$activate){update_option('st2l_write_enabled',false,false);}
   }
   // Confirmation of a real existing hotel is explicit, never inferred from a Hotel Directory name.
   ST2L_R2_Confirmed::handle_post();
   // Legacy UI switch permanently removed: only STPPI can render the website.
   if(get_option('st2l_public_enabled',false))update_option('st2l_public_enabled',false,false);
   if(isset($_POST['st2l_restore'])&&check_admin_referer('st2l_restore','st2l_restore_nonce')){
     $phrase=trim((string)wp_unslash($_POST['st2l_phrase']??''));
     if($phrase==='RESTORE SYNC 2'){$res=ST2L_Checkpoint::restore();self::log('safety','',is_wp_error($res)?$res->get_error_message():'Original managed WordPress records restored; sync paused.');}
   }
   if(isset($_POST['st2l_run'])&&check_admin_referer('st2l_run','st2l_nonce'))self::run_queue();
   $stats=get_option('st2l_last_stats',array());$queue=get_option('st2l_queue',array());$issues=get_option('st2l_recent_issues',array());
   echo '<div class="wrap"><h1>Server Turizm Auto Sync 2.8 — Original Design</h1><p><strong>Source:</strong> <code>'.esc_html(self::SOURCE).'</code></p>';
   echo '<p><strong>Last received:</strong> '.esc_html(get_option('st2l_last_received','Never')).' · <strong>Last completed:</strong> '.esc_html(get_option('st2l_last_success','Never')).'</p>';
   echo '<p><strong>Queue:</strong> <code>'.esc_html(wp_json_encode($queue)).'</code></p><p><strong>Stats:</strong> <code>'.esc_html(wp_json_encode($stats)).'</code></p>';
   echo '<form method="post">';wp_nonce_field('st2l_run','st2l_nonce');echo '<button class="button button-primary" name="st2l_run" value="1">Process next batch now</button></form>';
   $writing=(bool)get_option('st2l_write_enabled',false);
   echo '<h2>Automatic database updates</h2><p>Snapshot capture is always active, but changes are applied only after explicit activation. State: <strong>'.($writing?'RUNNING':'PAUSED — no database updates').'</strong></p>';
   echo '<form method="post">';wp_nonce_field('st2l_write','st2l_write_nonce');echo '<button name="st2l_write" value="'.($writing?'0':'1').'" class="button button-primary">'.($writing?'Pause database updates':'Start automatic updates (save checkpoint first)').'</button></form>';
   $fast=ST2L_Fast_Archive::pending();$fastReport=get_option('st2l_fast_archive_report',array());
   echo '<h2>Fast archive (explicit Programı Kaldır)</h2><p>Waiting for 2 signed snapshots and at least 60 seconds. <strong>Pending: '.count($fast).'</strong>. Does not reprocess the Hotel/Program lists.</p>';
   foreach($fast as $u=>$p){echo '<p><code>'.esc_html($p['stable_id']??$u).'</code>: '.esc_html($p['code']??'').' &middot; confirmed snapshots: '.(int)($p['confirmations']??1).' &middot; first seen: '.esc_html(gmdate('c',(int)($p['first_seen']??time()))).'</p>';}
   if(!empty($fastReport['last_archived']))echo '<p><strong>Last quick archive:</strong> '.esc_html($fastReport['last_archived']['stable_id']).' at '.esc_html($fastReport['last_archived']['at']).'</p>';
   if(!empty($fastReport['last_error']))echo '<p><strong>Last quick archive warning:</strong> '.esc_html($fastReport['last_error']['message']).'</p>';
   ST2L_R2_Confirmed::render_section();
   echo '<h2>Public design</h2><p><strong>ORIGINAL STPPI DESIGN — LOCKED.</strong> Auto Sync updates the data only. The original <code>[stppi_umre_programs]</code> and original Hotel Intelligence templates remain in charge; no visual replacement button exists.</p>';
   $native=get_option('st2l_original_renderer_report',array());
   if(is_array($native)&&$native){
     echo '<h3>Original renderer registration</h3><p><strong>Last checked:</strong> '.esc_html($native['at']??'—').' · <strong>Newly connected:</strong> '.esc_html(implode(', ',(array)($native['published']??array()))?:'none').'</p>';
     if(!empty($native['already_live']))echo '<p><strong>Already connected with original design:</strong> '.esc_html(implode(', ',(array)$native['already_live'])).'</p>';
     if(!empty($native['notice']))echo '<p>'.esc_html($native['notice']).'</p>';
     foreach(array('pending'=>'Pending approval / details','errors'=>'Original publishing warnings') as $key=>$heading){
       if(empty($native[$key])||!is_array($native[$key]))continue;
       echo '<h4>'.esc_html($heading).'</h4><ul>';
       foreach($native[$key] as $id=>$detail)echo '<li><code>'.esc_html((string)$id).'</code>: '.esc_html((string)$detail).'</li>';
       echo '</ul>';
     }
   }
   $checkpoint=ST2L_Checkpoint::info();
   echo '<h2>Automatic recovery checkpoint</h2>';
   echo $checkpoint?'<p>Captured: '.esc_html($checkpoint['created_at']).' · Managed records: '.(int)$checkpoint['posts'].' · Stored bytes: '.(int)$checkpoint['size'].'.</p>':'<p>Not captured yet. Created automatically before enabling database writes.</p>';
   if($checkpoint){ echo '<p><strong>Emergency restore</strong> restores tracked Program/Hotel posts and integration options to their original state. It does not restore the entire site, media, or unrelated plugins. Changes made in WordPress since capture can be lost.</p>';
     echo '<form method="post">';wp_nonce_field('st2l_restore','st2l_restore_nonce');echo '<input type="text" name="st2l_phrase" placeholder="Type RESTORE SYNC 2" autocomplete="off"> <button name="st2l_restore" value="1" class="button" onclick="return confirm(\"Restore the initial Sync checkpoint? Changes to managed Program and Hotel entities since capture will be lost.\")">Restore checkpoint and pause sync</button></form>';
   }
   echo '<h2>Recent notices</h2><table class="widefat striped"><tr><th>Time</th><th>Group</th><th>ID</th><th>Detail</th></tr>';
   foreach((array)$issues as $i)echo '<tr><td>'.esc_html($i['at']).'</td><td>'.esc_html($i['type']).'</td><td>'.esc_html($i['id']).'</td><td>'.esc_html($i['message']).'</td></tr>';
   echo '</table></div>';
 }
}
add_action('st2l_expire_nonce', function($nonce){if(is_string($nonce)&&strpos($nonce,'st2l_nonce_')===0)delete_option($nonce);});
