<?php
/**
 * Signed, read-only hotel catalog for the existing Apps Script. No public hotel
 * inventory is changed here; Hotel Intelligence remains authoritative.
 */
if (!defined('ABSPATH')) exit;
final class ST2L_Hotel_Directory {
  public static function register(){
    register_rest_route('server-turizm/v2','/hotel-directory',array(
      'methods'=>'GET','callback'=>array(__CLASS__,'directory'),'permission_callback'=>'__return_true'
    ));
  }
  public static function directory(WP_REST_Request $request){
    if(!class_exists('STDS_Auth'))return new WP_Error('st2_hotel_auth','Direct Sync Foundation is required',array('status'=>503));
    // GET carries no payload. Use the same authenticated HMAC envelope as /live.
    $verified=STDS_Auth::verify($request,'');
    if(is_wp_error($verified))return $verified;
    if(!post_type_exists('sthi_hotel'))return new WP_Error('st2_hotels_unavailable','Hotel Intelligence is unavailable',array('status'=>503));
    $ids=get_posts(array('post_type'=>'sthi_hotel','post_status'=>array('publish','draft','pending','private'),
      'posts_per_page'=>501,'fields'=>'ids','orderby'=>'ID','order'=>'ASC','no_found_rows'=>true));
    if(count($ids)>500)return new WP_Error('st2_hotel_limit','Too many hotels for directory export',array('status'=>413));
    $items=array();$seen=array();
    foreach($ids as $post_id){
      $stable=trim((string)get_post_meta($post_id,'_sthi_hotel_id',true));
      if(!preg_match('/^STH-\d{6}$/D',$stable))continue;
      if(isset($seen[$stable]))return new WP_Error('st2_hotel_duplicate','Duplicate stable Hotel ID: '.$stable,array('status'=>409));
      $seen[$stable]=true;
      $name=trim((string)get_the_title($post_id));
      if($name==='')continue;
      $other=trim((string)get_post_meta($post_id,'_sthi_alternative_names',true));
      $names=array($name,(string)get_post_meta($post_id,'_sthi_official_name',true),
        (string)get_post_meta($post_id,'_sthi_display_name_tr',true));
      if($other!=='')$names=array_merge($names,preg_split('/[|,;\n]+/u',$other));
      $names=array_values(array_unique(array_filter(array_map('trim',$names))));
      $is_live=get_post_status($post_id)==='publish';
      $url=$is_live?trim((string)apply_filters('sthi_hotel_public_url','',$post_id)):'';
      if(!$url && $is_live)$url=(string)get_permalink($post_id);
      $media='';
      if(class_exists('STHI_Media')){
        $gallery=STHI_Media::get_display_items($post_id);
        if(!empty($gallery[0]['url']))$media=esc_url_raw($gallery[0]['url']);
      }
      $map=trim((string)get_post_meta($post_id,'_sthi_google_maps_embed_url',true));
      if(!preg_match('~^https://(www\.)?google\.com/maps/embed~i',$map))$map='';
      $items[]=array(
        'hotel_id'=>$stable,'name'=>$name,'aliases'=>$names,
        'city'=>trim((string)get_post_meta($post_id,'_sthi_city',true)),
        'url'=>esc_url_raw($url),'primary_image'=>$media,'map_embed'=>$map,
        'wp_status'=>get_post_status($post_id),
        'verification'=>strtolower(trim((string)get_post_meta($post_id,'_sthi_verification_status',true))),
        'workflow'=>strtolower(trim((string)get_post_meta($post_id,'_sthi_workflow_status',true)))
      );
    }
    return new WP_REST_Response(array('ok'=>true,'version'=>'2.9','hotels'=>$items),200);
  }
  public static function on_hotel_saved($post_id,$post,$update){
    if($post && $post->post_type==='sthi_hotel')self::schedule_repin();
  }
  public static function on_meta_changed($meta_id,$post_id,$meta_key,$meta_value){
    if(get_post_type($post_id)!=='sthi_hotel')return;
    if(strpos((string)$meta_key,'_sthi_')!==0)return;
    self::schedule_repin();
  }
  private static function schedule_repin(){
    if(!wp_next_scheduled('st2l_hotel_repin'))wp_schedule_single_event(time()+20,'st2l_hotel_repin');
  }
  /** Refresh pinned hotel hashes after Hotel Intelligence galleries/details change.
   * Never alter publication modes, slugs, program data or approval statuses.
   */
  public static function repin_programs(){
    if(!class_exists('STPPI_Repository') && defined('STPPI_DIR')){
      foreach(array('class-stppi-repository.php','class-stppi-renderer.php') as $file){
        $path=STPPI_DIR.'includes/'.$file;if(is_file($path))require_once $path;
      }
    }
    if(!class_exists('STPPI_Repository')||!class_exists('STPPI_Renderer'))return;
    $registry=STPPI_Repository::registry();$updated=false;
    foreach((array)$registry as $id=>$cfg){
      if(!is_array($cfg)||empty($cfg['post_id'])||!in_array((string)($cfg['mode']??''),array('public_noindex','indexable'),true))continue;
      $m=STPPI_Renderer::model($cfg,false);
      if(is_wp_error($m))continue; // no unsafe publication on failed validation
      if((string)($cfg['hotel_hash']??'')!==(string)$m['hotel_hash']){
        $registry[$id]['hotel_hash']=(string)$m['hotel_hash'];$updated=true;
      }
    }
    if($updated){
      STPPI_Repository::save_registry($registry);
      if(class_exists('LiteSpeed_Cache_API') && method_exists('LiteSpeed_Cache_API','purge_all')){
        try{LiteSpeed_Cache_API::purge_all();}catch(Throwable $e){}
      }
    }
  }
}
