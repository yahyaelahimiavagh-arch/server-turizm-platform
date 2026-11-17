<?php
if(!defined('ABSPATH'))exit;
final class ST2L_Hotels {
 public static function norm($s){$s=remove_accents(function_exists('mb_strtolower')?mb_strtolower(trim((string)$s),'UTF-8'):strtolower(trim((string)$s)));return preg_replace('/[^a-z0-9]+/u','', $s);}
 public static function find_exact($name){
   $query=get_posts(array('post_type'=>'sthi_hotel','post_status'=>array('publish','private','draft','pending'),'posts_per_page'=>-1,'fields'=>'ids','no_found_rows'=>true));
   $target=self::norm($name);$matches=[];
   foreach($query as $id){
     $names=array(get_the_title($id),get_post_meta($id,'_sthi_display_name_tr',true),get_post_meta($id,'_sthi_official_name',true));
     $other=(string)get_post_meta($id,'_sthi_alternative_names',true);
     if($other!=='')$names=array_merge($names,preg_split('/[|,;\n]+/u',$other));
     foreach($names as $candidate){if($candidate&&self::norm($candidate)===$target){$matches[(int)$id]=true;break;}}
   }
   return array_keys($matches);
 }
 public static function upsert($entry,$saved){
   if(!post_type_exists('sthi_hotel'))return new WP_Error('st2_hotel_dependency','Hotel Intelligence is not active');
   $uuid=(string)$entry['uuid'];$f=(array)($entry['fields']??array());$name=sanitize_text_field((string)($f['name']??''));
   if(!$name)return new WP_Error('st2_empty_hotel','Hotel name is missing');
   $hash=hash('sha256',wp_json_encode($f));
   // WordPress-owned hotel rows use a stable ID. Never rewrite their curated
   // title, gallery, location, verification or any other Hotel Intelligence data.
   $linked=strtoupper(trim((string)($f['wp_hotel_id']??'')));
   if($linked!==''){
     if(!preg_match('/^STH-\d{6}$/D',$linked))return new WP_Error('st2_hotel_id_format','Invalid Hotel Intelligence stable ID');
     $matches=get_posts(array('post_type'=>'sthi_hotel','post_status'=>array('publish','draft','pending','private'),
       'posts_per_page'=>2,'fields'=>'ids','meta_key'=>'_sthi_hotel_id','meta_value'=>$linked,'no_found_rows'=>true));
     if(count($matches)!==1)return new WP_Error('st2_hotel_id_match','Hotel Intelligence ID absent or duplicated');
     $ownedId=(int)$matches[0];
     if(!in_array($ownedId,self::find_exact($name),true))return new WP_Error('st2_hotel_name_mismatch','Hotel name and stable ID disagree; no updates made');
     if(!empty($saved['post_id']) && (int)$saved['post_id']!==$ownedId)return new WP_Error('st2_hotel_binding_drift','Sheet UUID was linked to another hotel');
     $otherUuid=(string)get_post_meta($ownedId,'_st2_source_uuid',true);
     if($otherUuid!=='' && $otherUuid!==$uuid)return new WP_Error('st2_hotel_uuid_drift','Hotel is already bound to another Sheet UUID');
     update_post_meta($ownedId,'_st2_source_uuid',$uuid);
     update_post_meta($ownedId,'_st2_source_row',(int)$entry['source_row']);
     return array('post_id'=>$ownedId,'stable_id'=>$linked,'hash'=>$hash,'source_row'=>(int)$entry['source_row'],
       'created_by_sync'=>false,'archived'=>false);
   }
   $id=isset($saved['post_id'])?(int)$saved['post_id']:0;
   if($id&&get_post_type($id)!=='sthi_hotel')return new WP_Error('st2_hotel_identity','Hotel ID points to different entity');
   if(!$id){
     $owned=get_posts(array('post_type'=>'sthi_hotel','post_status'=>array('publish','draft','pending','private'),'posts_per_page'=>2,'fields'=>'ids','meta_key'=>'_st2_source_uuid','meta_value'=>$uuid,'no_found_rows'=>true));
     if(count($owned)>1)return new WP_Error('st2_hotel_duplicate','Source UUID belongs to multiple hotels');
     if($owned)$id=(int)$owned[0];
   }
   if(!$id){$matches=self::find_exact($name);if(count($matches)>1)return new WP_Error('st2_hotel_ambiguous','Several existing hotels have this exact name');if($matches)$id=(int)$matches[0];}
   if($id){
     $bound=(string)get_post_meta($id,'_st2_source_uuid',true);
     if($bound&&$bound!==$uuid)return new WP_Error('st2_hotel_ownership','Hotel already linked to another source row');
   }
   $created=!$id;
   if($id&&!empty($saved['created_by_sync'])&&get_post_status($id)==='draft'){wp_update_post(array('ID'=>$id,'post_status'=>'publish'));}
   if(!$id){
     $id=wp_insert_post(array('post_type'=>'sthi_hotel','post_status'=>'publish','post_title'=>$name),true);
     if(is_wp_error($id))return $id;
     update_post_meta($id,'_sthi_official_name',$name);
     update_post_meta($id,'_sthi_verification_status','unverified');
     update_post_meta($id,'_sthi_workflow_status','draft');
   }else{
     if($hash!==($saved['hash']??null)){
       $res=wp_update_post(array('ID'=>$id,'post_title'=>$name),true);if(is_wp_error($res))return $res;
     }
   }
   $map=trim((string)($f['map']??''));
   if(preg_match('~src=["\'](https://[^"\']+)~i',$map,$m))$map=$m[1];
   $map=esc_url_raw($map,array('https'));
   $images=trim((string)($f['images']??''));
   // Preserve curated Hotel Intelligence galleries and primary images. The sheet
   // can supply an image for the new Sync list, but never clears a curated gallery.
   // Only seed native gallery when it is currently empty and Sheets supplies media.
   $gallery=[];$firstKey='';
   $ourHost=strtolower(preg_replace('/^www\./','',(string)wp_parse_url(home_url('/'),PHP_URL_HOST)));
   foreach(array_slice(explode(',',$images),0,250) as $raw){
     $url=esc_url_raw(trim($raw),array('https'));
     $host=strtolower(preg_replace('/^www\./','',(string)wp_parse_url($url,PHP_URL_HOST)));
     $ext=strtolower(pathinfo((string)wp_parse_url($url,PHP_URL_PATH),PATHINFO_EXTENSION));
     if(!$url||$host!==$ourHost||!in_array($ext,array('jpg','jpeg','png','webp','gif','avif'),true))continue;
     $key='url:'.md5($url);if(!$firstKey)$firstKey=$key;
     $gallery[]=array('key'=>$key,'type'=>'url','url'=>$url,'thumb'=>$url,'title'=>sanitize_text_field(basename((string)wp_parse_url($url,PHP_URL_PATH))));
   }
   $nativeGallery=get_post_meta($id,'_sthi_gallery_items',true);
   if($gallery && (!$nativeGallery || (is_array($nativeGallery)&&count($nativeGallery)===0))){
     update_post_meta($id,'_sthi_gallery_items',$gallery);
     if(!get_post_meta($id,'_sthi_primary_image_key',true))update_post_meta($id,'_sthi_primary_image_key',$firstKey);
   }
   if(strpos($map,'https://www.google.com/maps/embed')===0 || strpos($map,'https://google.com/maps/embed')===0){
      update_post_meta($id,'_sthi_google_maps_embed_url',$map);
   }
   $city=sanitize_text_field((string)($f['city']??''));
   $meta=array('_st2_source_uuid'=>$uuid,'_st2_source_row'=>(int)$entry['source_row'],
    '_sthi_display_name_tr'=>$name);
   if($images && count($gallery))$meta['_st2_hotel_images']=implode(',',array_column($gallery,'url'));
   if($map)$meta['_st2_hotel_map']=$map;
   if(!empty($f['logo']))$meta['_st2_hotel_logo']=esc_url_raw((string)$f['logo'],array('https'));
   if($city)$meta['_sthi_city']=$city;
   foreach($meta as $key=>$value)update_post_meta($id,$key,$value);
   $stable=(string)get_post_meta($id,'_sthi_hotel_id',true);
   if(!$stable&&class_exists('STHI_Hotel_ID'))STHI_Hotel_ID::assign_id($id,get_post($id),false);
   $stable=(string)get_post_meta($id,'_sthi_hotel_id',true);
   if(!preg_match('/^STH-\d{6}$/D',$stable))return new WP_Error('st2_hotel_id','Hotel stable ID missing after creation');
   return array('post_id'=>(int)$id,'stable_id'=>$stable,'hash'=>$hash,'source_row'=>(int)$entry['source_row'],'created_by_sync'=>$created||!empty($saved['created_by_sync']),'archived'=>false);
 }
 public static function lookup($name,$preferred=''){
   if(!$name)return '';
   if($preferred!==''){
     $preferred=strtoupper(trim((string)$preferred));
     if(!preg_match('/^STH-\d{6}$/D',$preferred))return '';
     $ids=get_posts(array('post_type'=>'sthi_hotel','post_status'=>array('publish','draft','pending','private'),
       'posts_per_page'=>2,'fields'=>'ids','meta_key'=>'_sthi_hotel_id','meta_value'=>$preferred,'no_found_rows'=>true));
     if(count($ids)!==1)return '';
     // An ID is authoritative only if the sheet's selected name is an alias of this hotel.
     return in_array((int)$ids[0],self::find_exact($name),true)?$preferred:'';
   }
   $matches=self::find_exact($name);
   if(count($matches)===1)return (string)get_post_meta($matches[0],'_sthi_hotel_id',true);
   return '';
 }
}
