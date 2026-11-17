<?php
/**
 * Pre-write checkpoint for the entities managed by Server Turizm Auto Sync.
 * This is NOT a full website or media backup. It snapshots the managed post
 * entities, their metadata/relationships, and affected integration options.
 * Creation must succeed before write mode can be enabled.
 */
if (!defined('ABSPATH')) exit;
final class ST2L_Checkpoint {
 const KEY = 'st2l_checkpoint_prewrite_223';
 const MAX_BYTES = 33554432; // 32 MiB compressed/encoded data
 private static function tracked_options() {
   global $wpdb;
   $names = array('stppi_registry','stppi_public_master');
   // Include plugin state, counters, and publishing integration settings.
   $rows = $wpdb->get_results("SELECT option_name, option_value, autoload FROM {$wpdb->options} WHERE option_name LIKE 'st2l\\_%' OR option_name LIKE 'stpi\\_%' OR option_name LIKE 'stppi\\_%'", ARRAY_A);
   return is_array($rows)?array_values(array_filter($rows,function($row){return ($row['option_name']??'')!==self::KEY && strpos((string)($row['option_name']??''),'st2l_nonce_')!==0;})):array();
 }
 public static function create() {
   global $wpdb;
   if ($existing=get_option(self::KEY, false)) {
     if(is_array($existing)&&!empty($existing['data'])&&!empty($existing['sha256'])&&hash_equals((string)$existing['sha256'],hash('sha256',(string)$existing['data'])))return true;
     return new WP_Error('st2_checkpoint_corrupt','Existing recovery checkpoint is corrupted; updates blocked.');
   }
   if (!function_exists('gzencode')) return new WP_Error('st2_no_gzip','Server PHP zlib is required for automatic checkpoint.');
   $posts = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->posts} WHERE post_type IN (%s,%s)",'stpi_program','sthi_hotel'),ARRAY_A);
   if(!is_array($posts))return new WP_Error('st2_checkpoint_posts','Cannot read existing programs and hotels.');
   $ids=array_map('intval',array_column($posts,'ID'));
   $meta=array();$rels=array();
   if($ids){
     $in=implode(',',array_map('absint',$ids));
     $meta=$wpdb->get_results("SELECT * FROM {$wpdb->postmeta} WHERE post_id IN ($in)",ARRAY_A);
     $rels=$wpdb->get_results("SELECT * FROM {$wpdb->term_relationships} WHERE object_id IN ($in)",ARRAY_A);
     if(!is_array($meta)||!is_array($rels))return new WP_Error('st2_checkpoint_meta','Cannot read program and hotel relationships.');
   }
   $payload=array('created_at'=>gmdate('c'),'site'=>home_url('/'),'posts'=>$posts,'meta'=>$meta,'rels'=>$rels,'options'=>self::tracked_options());
   $json=wp_json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
   if(!is_string($json))return new WP_Error('st2_checkpoint_json','Cannot serialize checkpoint.');
   $packed=base64_encode(gzencode($json,6));
   if(strlen($packed)>self::MAX_BYTES)return new WP_Error('st2_checkpoint_size','Automatic checkpoint too large; write mode blocked.');
   $record=array('created_at'=>$payload['created_at'],'sha256'=>hash('sha256',$packed),'data'=>$packed,'size'=>strlen($packed),'posts'=>count($posts));
   if(!add_option(self::KEY,$record,'','no'))return new WP_Error('st2_checkpoint_write','Could not persist checkpoint; write mode blocked.');
   $verify=get_option(self::KEY, false);
   return is_array($verify)&&hash_equals($record['sha256'],(string)($verify['sha256']??''))?true:new WP_Error('st2_checkpoint_verify','Checkpoint verification failed; write mode blocked.');
 }
 public static function info(){ $v=get_option(self::KEY, false);return is_array($v)?array('created_at'=>$v['created_at'],'posts'=>$v['posts'],'size'=>$v['size']):null; }
 public static function restore(){
   global $wpdb;
   $record=get_option(self::KEY,false);
   if(!is_array($record)||!isset($record['data'],$record['sha256'])||!hash_equals((string)$record['sha256'],hash('sha256',(string)$record['data'])))return new WP_Error('st2_checkpoint_missing','Checkpoint missing or corrupted.');
   $raw=gzdecode(base64_decode($record['data'],true));$data=json_decode((string)$raw,true);
   if(!is_array($data)||($data['site']??'')!==home_url('/')||!isset($data['posts'],$data['meta'],$data['rels'],$data['options']))return new WP_Error('st2_checkpoint_invalid','Checkpoint is not for this site.');
   // Block all workers first. Restoring while another worker is active is unsafe.
   update_option('st2l_write_enabled',false,false);
   wp_clear_scheduled_hook('st2l_process_job');
   if(get_option('st2l_running',false))return new WP_Error('st2_checkpoint_busy','Sync worker still active. Retry restore when idle.');
   $originalIds=array_fill_keys(array_map('intval',array_column($data['posts'],'ID')),true);
   $now=$wpdb->get_results($wpdb->prepare("SELECT ID FROM {$wpdb->posts} WHERE post_type IN (%s,%s)",'stpi_program','sthi_hotel'),ARRAY_A);
   if(!is_array($now))return new WP_Error('st2_checkpoint_read','Could not enumerate current entities.');
   $wpdb->query('START TRANSACTION');
   try{
     // Remove only new Sync-owned entities; never delete a manually created entity.
     foreach($now as $row){$id=(int)$row['ID'];if(isset($originalIds[$id]))continue;
       if(!get_post_meta($id,'_st2_source_uuid',true)&&!get_post_meta($id,'_stpi_source_uuid',true))continue;
       $wpdb->delete($wpdb->postmeta,array('post_id'=>$id),array('%d'));
       $wpdb->delete($wpdb->term_relationships,array('object_id'=>$id),array('%d'));
       $wpdb->delete($wpdb->posts,array('ID'=>$id),array('%d'));
     }
     // Restore original posts and complete postmeta/relationships at their original IDs.
     foreach($data['posts'] as $row){if($wpdb->replace($wpdb->posts,$row)===false)throw new Exception('Post restore failed');}
     $ids=array_keys($originalIds);
     if($ids){$in=implode(',',array_map('absint',$ids));
       if($wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE post_id IN ($in)")===false)throw new Exception('Meta deletion failed');
       if($wpdb->query("DELETE FROM {$wpdb->term_relationships} WHERE object_id IN ($in)")===false)throw new Exception('Relationships deletion failed');
     }
     foreach($data['meta'] as $row){if($wpdb->insert($wpdb->postmeta,$row)===false)throw new Exception('Meta restore failed');}
     foreach($data['rels'] as $row){if($wpdb->insert($wpdb->term_relationships,$row)===false)throw new Exception('Relationship restore failed');}
     $wpdb->query('COMMIT');
   }catch(Throwable $e){$wpdb->query('ROLLBACK');return new WP_Error('st2_checkpoint_restore',$e->getMessage());}
   // Restore state options using the WordPress option API to maintain cache consistency.
   $before=array();foreach($data['options'] as $row)$before[$row['option_name']]=$row;
   foreach(self::tracked_options() as $row){$key=$row['option_name'];if(!isset($before[$key]))delete_option($key);}
   foreach($before as $key=>$row){update_option($key,maybe_unserialize($row['option_value']),false);}
   update_option('st2l_write_enabled',false,false);
   update_option('st2l_public_enabled',false,false);
   wp_clear_scheduled_hook('st2l_process_job');
   if(function_exists('wp_cache_flush'))wp_cache_flush();
   return true;
 }
}
