<?php
if(!defined('ABSPATH'))exit;
final class ST2L_Programs {
 private static function load(){
   if(!defined('STPI_DIR'))return new WP_Error('st2_program_dependency','Program Intelligence 0.3.8 is not active');
   foreach(array('contract','store','validator','lifecycle','hotel-adapter','audit') as $name){
     $path=STPI_DIR.'includes/class-stpi-'.$name.'.php';if(is_file($path))require_once $path;
   }
   if(!class_exists('STPI_Store'))return new WP_Error('st2_program_dependency','STPI_Store unavailable');
   return true;
 }
 public static function date($raw){
   $raw=trim((string)$raw);if(!$raw)return null;
   foreach(array('!d.m.Y','!Y-m-d','!d/m/Y') as $fmt){
     $x=DateTimeImmutable::createFromFormat($fmt,$raw,new DateTimeZone('Europe/Istanbul'));
     if($x&&$x->format(substr($fmt,1))===$raw)return $x->format('Y-m-d');
   }
   return null;
 }
 public static function number($raw){
   $x=trim((string)$raw);if($x==='')return null;
   $x=preg_replace('/[^0-9,.\-]/','',$x);if(!$x)return null;
   if(strpos($x,',')!==false)$x=str_replace(',','.',str_replace('.','',$x));
   else if(preg_match('/^\d{1,3}(\.\d{3})+$/',$x))$x=str_replace('.','',$x);
   return is_numeric($x)&&floatval($x)>0?floatval($x):null;
 }
 private static function diff_days($start,$end){
   if(!$start||!$end)return null;
   try{$a=new DateTimeImmutable($start);$b=new DateTimeImmutable($end);$n=(int)$a->diff($b)->format('%r%a');return $n>0?$n:null;}catch(Throwable $e){return null;}
 }
 private static function hotel_name($city,$f){return $city==='Madinah'?trim((string)($f['medinah_hotel']??'')):($city==='Makkah'?trim((string)($f['makkah_hotel']??'')):trim((string)($f['other_hotel']??'')));}
 private static function split_nights($raw){
   $out=[];$raw=preg_replace('/\R/u',',',(string)$raw);
   if(preg_match_all('/(\d+)\s*Gece\s*(Mekke|Medine|Makkah|Madinah|Kahire|Cairo)/iu',$raw,$m,PREG_SET_ORDER)){
     foreach($m as $x){$loc=function_exists('mb_strtolower')?mb_strtolower($x[2],'UTF-8'):strtolower($x[2]);$city=preg_match('/medine|madinah/u',$loc)?'Madinah':(preg_match('/mekke|makkah/u',$loc)?'Makkah':'Cairo');
       $out[]=array('sequence'=>count($out)+1,'country'=>$city==='Cairo'?'Egypt':'Saudi Arabia','city'=>$city,'nights'=>(int)$x[1]);
     }
   }
   return $out;
 }
 private static function make($entry){
   $f=(array)$entry['fields'];$code=sanitize_text_field((string)($f['code']??''));$title=sanitize_text_field((string)($f['title']??''));
   $start=self::date($f['departure']??'');$end=self::date($f['return_date']??'');
   $nights=self::diff_days($start,$end);$exact=$nights!==null;
   $route=self::split_nights($f['nights']??'');
   $dateLabel=sanitize_text_field((string)($f['counter_date']??''));
   $segments=[];
   if($start)$segments[]=array('sequence'=>1,'type'=>'departure','origin'=>null,'destination'=>$route[0]['city']??null,'depart_at'=>$start.'T00:00:00+03:00','arrive_at'=>null,'transport_ref'=>null,'notes'=>null);
   $transferIndex=0;
   foreach(array('transfer_1','transfer_2') as $key){$t=self::date($f[$key]??'');if(!$t)continue;
     $segments[]=array('sequence'=>count($segments)+1,'type'=>'transfer','origin'=>$route[$transferIndex]['city']??null,'destination'=>$route[$transferIndex+1]['city']??null,'depart_at'=>$t.'T00:00:00+03:00','arrive_at'=>null,'transport_ref'=>null,'notes'=>null);
     $transferIndex++;
   }
   if($end)$segments[]=array('sequence'=>count($segments)+1,'type'=>'return','origin'=>!empty($route)?$route[count($route)-1]['city']:null,'destination'=>null,'depart_at'=>$end.'T00:00:00+03:00','arrive_at'=>null,'transport_ref'=>null,'notes'=>null);
   $stays=[];$cursor=$start;
   foreach($route as $d){
     $hotel=self::hotel_name($d['city'],$f);$idKey=$d['city']==='Madinah'?'medinah_hotel_id':($d['city']==='Makkah'?'makkah_hotel_id':'other_hotel_id');$preferred=trim((string)($f[$idKey]??''));$hotelId=ST2L_Hotels::lookup($hotel,$preferred);$next=null;
     if($cursor){try{$next=(new DateTimeImmutable($cursor))->modify('+'.(int)$d['nights'].' days')->format('Y-m-d');}catch(Throwable $e){}}
     $stays[]=array('sequence'=>$d['sequence'],'destination'=>$d['city'],'hotel_id'=>$hotelId?:null,'unresolved_hotel_name'=>$hotelId?null:($hotel?:null),
       'check_in'=>$cursor,'check_out'=>$next,'nights'=>(int)$d['nights'],
       'meal_plan'=>($d['city']==='Madinah'?(string)($f['meal_1']??''):(string)($f['meal_2']??''))?:null,
       'room_notes'=>($d['city']==='Madinah'?(string)($f['room_1']??''):(string)($f['room_2']??''))?:null);
     $cursor=$next;
   }
   $prices=[];foreach(array('price_double'=>'double','price_triple'=>'triple','price_quad'=>'quad') as $key=>$occupancy){$amount=self::number($f[$key]??'');
     if($amount!==null)$prices[]=array('occupancy'=>$occupancy,'amount'=>$amount,'unit'=>'per_person','label'=>array('double'=>'2 kişilik oda','triple'=>'3 kişilik oda','quad'=>'4 kişilik oda')[$occupancy]);
   }
   $phone=preg_replace('/[^0-9+]/','',(string)($f['phone']??''));
   if(preg_match('/^90\d{10}$/',$phone))$phone='+'.$phone;
   $full=$title?:($code?'Umre Programı '.$code:'Umre Programı');
   $notes=array_values(array_filter(array_map('trim',preg_split('/\R/u',(string)($f['notes']??'')))));
   $rawChild=trim((string)($f['child_fares']??''));if($rawChild)$notes[]='Çocuk Ücretleri: '.$rawChild;
   if(!empty($f['highlight_show'])&&in_array(strtolower((string)$f['highlight_show']),array('true','1'),true)&&!empty($f['highlight']))$notes[]='[STPPI_HIGHLIGHT] '.substr((string)$f['highlight'],0,120);
   if(!empty($f['albaraka_show'])&&in_array(strtolower((string)$f['albaraka_show']),array('true','1'),true)&&!empty($f['albaraka_tooltip']))$notes[]='[STPPI_ALBARAKA] '.substr((string)$f['albaraka_tooltip'],0,280);
   if(!empty($f['albaraka_logo'])&&preg_match('/^https:\/\//i',(string)$f['albaraka_logo']))$notes[]='[STPPI_ALBARAKA_LOGO] '.(string)$f['albaraka_logo'];
   $inclusions=array_values(array_filter(array_map('trim',preg_split('/\R/u',(string)($f['notes']??'')))));
   $media=esc_url_raw((string)($f['hero']??''),array('https'));
   $days=$exact?$nights+1:null;
   return array(
     'program_id'=>null,'program_code'=>$code?:('DRAFT-'.strtoupper(substr($entry['uuid'],0,8))),'service_type'=>'umrah',
     'publication_mode'=>$exact?'scheduled_departure':'interest_campaign','title'=>$full,'locale'=>'tr_TR',
     'tier'=>preg_match('/lux|lüks/iu',(string)($f['tier']??''))?'luxury':'economy',
     'workflow'=>array('editorial'=>'needs_review','schedule'=>$exact?'scheduled':'tentative','availability'=>!empty($f['sold_out'])&&strtolower((string)$f['sold_out'])==='true'?'sold_out':'open','featured'=>false,'priority'=>100),
     'schedule'=>array('date_precision'=>$exact?'exact':'unknown','start_date'=>$start,'end_date'=>$end,'date_label'=>$exact?null:$dateLabel,'timezone'=>'Europe/Istanbul','duration_days'=>$days,'duration_nights'=>$nights),
     'destinations'=>$route,'segments'=>$segments,'itinerary_days'=>array(),'stays'=>$stays,'transport'=>array(),
     'pricing'=>array('currency'=>'USD','price_type'=>$prices?'fixed':'on_request','entries'=>$prices,'child_rules'=>array(),'surcharges'=>array(),'valid_from'=>null,'valid_until'=>null,'disclaimer'=>null),
     'capacity'=>array('total'=>isset($f['capacity'])&&is_numeric($f['capacity'])?(int)$f['capacity']:null,'remaining'=>null,'show_exact_remaining'=>false),
     'inclusions'=>$inclusions,'exclusions'=>array(),'notes'=>$notes,
     'contact'=>array('phone'=>$phone?:null,'whatsapp'=>$phone?:null,'cta_mode'=>!empty($f['sold_out'])&&strtolower((string)$f['sold_out'])==='true'?'closed':($exact?'reserve':'pre_register')),
     'media'=>array('hero_image_url'=>$media?:null,'gallery'=>array(),'media_rights_note'=>$media?'Google Sheets source media':null,'design_template'=>'umrah-program'),
     'seo'=>array('seo_title'=>null,'meta_description'=>null,'primary_topic'=>(function_exists('mb_strtolower')?mb_strtolower($full,'UTF-8'):strtolower($full)),'index_policy'=>'noindex'),
     'provenance'=>array('source_type'=>'google_sheets','source_ref'=>'ST-ROW:'.$entry['uuid'],'source_row'=>(int)$entry['source_row'],
        'verified_at'=>gmdate(DATE_W3C),'verified_by'=>'Google Sheets Auto Sync 2','notes'=>'Source of truth: Home worksheet')
   );
 }
 public static function bind_removed($entry,$saved){
   $ready=self::load();if(is_wp_error($ready))return $ready;
   if(is_array($saved)&&!empty($saved['post_id'])){
     $post=(int)$saved['post_id'];$id=(string)($saved['stable_id']??'');
     if(get_post_type($post)!=='stpi_program' ||
        (string)get_post_meta($post,'_stpi_program_id',true)!==$id ||
        (!empty($entry['stable_id']) && $entry['stable_id']!==$id))
       return new WP_Error('st2_removed_binding','Removal target Stable ID mismatch');
     $old=STPI_Store::get_program($post);
     if(trim((string)($old['program_code']??''))!==trim((string)($entry['fields']['code']??'')))
       return new WP_Error('st2_removed_code','Removal target program code mismatch');
     foreach(array('_st2_source_uuid','_stpi_source_uuid') as $sourceKey){
       $uuid=(string)get_post_meta($post,$sourceKey,true);
       if($uuid && $uuid!==(string)$entry['uuid'])return new WP_Error('st2_removed_uuid','Removal target UUID mismatch');
     }
     return $saved;
   }
   $id=strtoupper(trim((string)($entry['stable_id']??'')));
   if(!preg_match('/^STP-\d{6}$/D',$id))return null;
   $ready=self::load();if(is_wp_error($ready))return $ready;
   $matches=STPI_Store::find_by_program_id($id);
   if(count($matches)!==1)return new WP_Error('st2_removed_identity','Marked removed Program ID cannot be resolved uniquely');
   $postId=(int)$matches[0];$program=STPI_Store::get_program($postId);
   if(trim((string)($program['program_code']??''))!==trim((string)($entry['fields']['code']??'')))return new WP_Error('st2_removed_code','Marked removed Program code does not match website');
   foreach(array('_stpi_source_uuid','_st2_source_uuid') as $sourceKey){
     $bound=(string)get_post_meta($postId,$sourceKey,true);
     if($bound&&$bound!==$entry['uuid'])return new WP_Error('st2_removed_uuid','Marked removed Program is linked to different UUID');
   }
   return array('post_id'=>$postId,'stable_id'=>$id,'hash'=>'','source_row'=>(int)$entry['source_row'],'archived'=>false);
 }
 public static function upsert($entry,$saved){
   $ready=self::load();if(is_wp_error($ready))return $ready;
   $uuid=(string)$entry['uuid'];$fields=(array)($entry['fields']??array());
   $hash=hash('sha256',wp_json_encode($fields));$given=strtoupper(trim((string)($entry['stable_id']??'')));
   $postId=(int)($saved['post_id']??0);$hasBinding=is_array($saved)&&$postId>0;
   if($postId&&get_post_type($postId)!=='stpi_program')return new WP_Error('st2_identity','Registry post mismatch');
   if(!$postId){
     foreach(array('_st2_source_uuid','_stpi_source_uuid') as $key){
       $byUUID=get_posts(array('post_type'=>'stpi_program','post_status'=>array('private','draft','pending','publish'),
         'posts_per_page'=>2,'fields'=>'ids','meta_key'=>$key,'meta_value'=>$uuid,'no_found_rows'=>true));
       if(count($byUUID)>1)return new WP_Error('st2_duplicate_uuid','More than one Program has this UUID');
       if($byUUID){$postId=(int)$byUUID[0];break;}
     }
   }
   if(!$postId&&$given){
     $byID=STPI_Store::find_by_program_id($given);
     if(count($byID)!==1)return new WP_Error('st2_legacy_id','Original STP ID not found or duplicated');
     $postId=(int)$byID[0];
   }
   if($postId){
     $stable=(string)get_post_meta($postId,'_stpi_program_id',true);
     if(!preg_match('/^STP-\d{6}$/D',$stable)||($given&&$stable!==$given))return new WP_Error('st2_id_drift','Website Program stable ID mismatch');
     $old=STPI_Store::get_program($postId);
     if(!is_array($old)||!$old)return new WP_Error('st2_missing_program','Website Program payload is missing');
     $oldCode=trim((string)($old['program_code']??''));$newCode=trim((string)($fields['code']??''));
     if(!$hasBinding && $oldCode&&$newCode&&$oldCode!==$newCode)return new WP_Error('st2_adoption','Program code does not match existing Stable ID');
     $bound=(string)get_post_meta($postId,'_st2_source_uuid',true);
     if($bound&&$bound!==$uuid)return new WP_Error('st2_uuid_drift','Existing Program is bound to a different Sync UUID');
     $oldBound=(string)get_post_meta($postId,'_stpi_source_uuid',true);
     if($oldBound&&$oldBound!==$uuid)return new WP_Error('st2_legacy_uuid_drift','Legacy source UUID mismatch');
     // CRITICAL: first adoption is metadata-only. Earlier 2.3 overwrote approved
     // programs merely because the registry had no saved hash. Never do that.
     if(!$hasBinding){
       update_post_meta($postId,'_st2_source_uuid',$uuid);
       update_post_meta($postId,'_st2_source_row',(int)$entry['source_row']);
       update_post_meta($postId,'_st2_fields',$fields);
       return array('post_id'=>$postId,'stable_id'=>$stable,'hash'=>$hash,
         'source_row'=>(int)$entry['source_row'],
         'archived'=>(($old['workflow']['editorial']??'')==='archived'),
         'adopted_without_rewrite'=>true);
     }
     $archived=(($old['workflow']['editorial']??'')==='archived');
     if($archived){
       // Manual archive is not silently reversed just because a Sheet flag changes.
       return array('post_id'=>$postId,'stable_id'=>$stable,'hash'=>$hash,
         'source_row'=>(int)$entry['source_row'],'archived'=>true,
         'adopted_without_rewrite'=>!empty($saved['adopted_without_rewrite']),
         'created_by_sync'=>!empty($saved['created_by_sync']));
     }
     if($hash===($saved['hash']??null) && empty($saved['archived'])){
       update_post_meta($postId,'_st2_source_row',(int)$entry['source_row']);
       update_post_meta($postId,'_st2_fields',$fields);
       return array('post_id'=>$postId,'stable_id'=>$stable,'hash'=>$hash,
         'source_row'=>(int)$entry['source_row'],'archived'=>false,
         'adopted_without_rewrite'=>!empty($saved['adopted_without_rewrite']),
         'created_by_sync'=>!empty($saved['created_by_sync']));
     }
     // Only source-owned fields change. Preserve approval, editorial additions,
     // publication settings, curated SEO, curated galleries, and Post status.
     $fresh=self::make($entry);$program=$old;
     foreach(array('program_code','title','tier','publication_mode','schedule','destinations','segments','stays','pricing','capacity','inclusions','notes','contact') as $key){
       $program[$key]=$fresh[$key];
     }
     // Carry administrator-verified provisional hotel labels ONLY when their
     // name, source city, sequence, nights and dates match exactly.
     if (($old['workflow']['editorial']??'')==='approved' && class_exists('STPI_Contract') && method_exists('STPI_Contract','carry_pending_hotels')) {
       $program=STPI_Contract::carry_pending_hotels($program,$old);
       // Preserve an explicitly confirmed, verified R2 Jiwar hotel on future
       // source edits, provided source hotel name AND stay identity are unchanged.
       // Never infer booking confirmation merely from a matching hotel name.
       if($stable==='STP-000051' && ($old['program_code']??'')==='R2'){
         foreach((array)$program['stays'] as $i=>$stay){
           if(($stay['destination']??'')!=='Madinah')continue;
           $sourceName=self::hotel_name('Madinah',$fields);
           foreach((array)($old['stays']??array()) as $prior){
             if(($prior['destination']??'')!=='Madinah'
               || ($prior['hotel_id']??'')!=='STH-000023'
               || (int)($prior['sequence']??0)!==(int)($stay['sequence']??-1)
               || ($prior['check_in']??'')!==($stay['check_in']??'')
               || ($prior['check_out']??'')!==($stay['check_out']??'')
               || (int)($prior['nights']??-1)!==(int)($stay['nights']??-2)
               || ST2L_Hotels::norm($sourceName)!==ST2L_Hotels::norm('Jiwar Al-Madina Hotel'))continue;
             $program['stays'][$i]['hotel_id']='STH-000023';
             $program['stays'][$i]['unresolved_hotel_name']=null;
             unset($program['stays'][$i]['pending_hotel_reviewed']);
             break;
           }
         }
       }
       // Exact source label rules for already human-approved provisional R1/R2.
       // Hotel lookup may now find a real STH entity; that alone must NEVER
       // assert a confirmed stay when the booking is still provisional.
       $eligible=(($stable==='STP-000050' && ($old['program_code']??'')==='R1') || ($stable==='STP-000051' && ($old['program_code']??'')==='R2'));
       if($eligible && !empty($old['stays']) && is_array($old['stays'])){
         foreach((array)$program['stays'] as $i=>$stay){
           $sourceName=self::hotel_name((string)($stay['destination']??''),$fields);
           foreach((array)$old['stays'] as $prior){
             if(!STPI_Contract::pending_stay($prior)
                || (int)($prior['sequence']??0)!==(int)($stay['sequence']??-1)
                || ($prior['destination']??'')!==($stay['destination']??'')
                || ($prior['check_in']??'')!==($stay['check_in']??'')
                || ($prior['check_out']??'')!==($stay['check_out']??'')
                || (int)($prior['nights']??-1)!==(int)($stay['nights']??-2)
                || $sourceName!==trim((string)($prior['unresolved_hotel_name']??'')))continue;
             $program['stays'][$i]['hotel_id']=null;
             $program['stays'][$i]['unresolved_hotel_name']=$sourceName;
             $program['stays'][$i]['pending_hotel_reviewed']=true;
             break;
           }
         }
       }
     }
     if(!isset($program['media'])||!is_array($program['media']))$program['media']=array();
     if(!empty($fresh['media']['hero_image_url']))$program['media']['hero_image_url']=$fresh['media']['hero_image_url'];
     if(!isset($program['workflow'])||!is_array($program['workflow']))$program['workflow']=array();
     $program['workflow']['schedule']=$fresh['workflow']['schedule'];
     $program['workflow']['availability']=$fresh['workflow']['availability'];
     $program['program_id']=$stable;
     $program['provenance']['source_row']=(int)$entry['source_row'];
     $program['provenance']['verified_at']=gmdate(DATE_W3C);
     $program['provenance']['verified_by']='Google Sheets Auto Sync 2.4';
     // Approved pages must remain approved and must pass existing publication
     // validation before a canonical write; otherwise leave their old data intact.
     $approved=(($old['workflow']['editorial']??'')==='approved');
     if($approved && class_exists('STPI_Validator')){
       $report=STPI_Validator::validate_batch(array('schema_version'=>STPI_SCHEMA_VERSION,
         'source'=>array('type'=>'wordpress','mode'=>'partial'),'programs'=>array($program)));
       if(!empty($report['errors'])||($report['programs'][0]['publish_gate']??'')!=='READY'){
         update_post_meta($postId,'_st2_pending_fields',$fields);
         return new WP_Error('st2_approved_validation','Approved Program update staged, not overwritten: source fields need publication-ready values');
       }
     }
     $snap=method_exists('STPI_Store','identity_repair_snapshot')?STPI_Store::identity_repair_snapshot($postId):null;
     if(is_wp_error($snap)||!is_array($snap))return new WP_Error('st2_snapshot','Could not capture per-Program rollback snapshot');
     $originalExtras=array();foreach(array('_st2_source_uuid','_st2_source_row','_st2_fields','_st2_pending_fields') as $k)$originalExtras[$k]=get_post_meta($postId,$k,false);
     $restore=function()use($snap,$originalExtras,$postId){
       STPI_Store::identity_repair_restore_snapshot($snap);
       foreach($originalExtras as $key=>$values){delete_post_meta($postId,$key);foreach($values as $value)add_post_meta($postId,$key,$value);}
     };
     $wp=wp_update_post(array('ID'=>$postId,'post_title'=>$program['title'],
       'post_content'=>wp_slash(wp_json_encode($program,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES))),true);
     if(is_wp_error($wp))return $wp;
     $meta=array('_st2_source_uuid'=>$uuid,'_st2_source_row'=>(int)$entry['source_row'],
       '_stpi_program_code'=>$program['program_code'],'_stpi_payload_hash'=>STPI_Contract::payload_hash($program),
       '_stpi_editorial_status'=>$program['workflow']['editorial'],'_stpi_schedule_status'=>$program['workflow']['schedule'],
       '_stpi_availability_status'=>$program['workflow']['availability'],
       '_stpi_start_date'=>$program['schedule']['start_date'],'_stpi_end_date'=>$program['schedule']['end_date']);
     foreach($meta as $key=>$value)update_post_meta($postId,$key,$value);
     update_post_meta($postId,'_st2_fields',$fields);delete_post_meta($postId,'_st2_pending_fields');
     // Refresh an already-active STPPI route in-place without changing its mode/slug.
     $rawRegistry=get_option('stppi_registry',array());
     $activeCfg=is_array($rawRegistry)?($rawRegistry[$stable]??null):null;
     if($approved && is_array($activeCfg) && in_array((string)($activeCfg['mode']??''),array('public_noindex','indexable'),true)){
       if(!class_exists('STPPI_Repository') && defined('STPPI_DIR')){
         foreach(array('class-stppi-repository.php','class-stppi-renderer.php') as $file){
           $path=STPPI_DIR.'includes/'.$file;if(is_file($path))require_once $path;
         }
       }
       if(!class_exists('STPPI_Repository')||!class_exists('STPPI_Renderer')){
         $restore();return new WP_Error('st2_route_dependency','Protected Program update rolled back: publishing runtime missing');
       }
       $registryBefore=STPPI_Repository::registry();$cfg=$registryBefore[$stable]??null;
       if(!is_array($cfg)||!in_array((string)($cfg['mode']??''),array('public_noindex','indexable'),true)){
         $restore();return new WP_Error('st2_route_changed','Protected Program update rolled back: route changed');
       }
       $model=STPPI_Renderer::model($cfg,false);
       if(is_wp_error($model)){
         $restore();return new WP_Error('st2_live_route','Program update rolled back: '.$model->get_error_message());
       }
       $registryBefore[$stable]['hash']=(string)$model['hash'];
       $registryBefore[$stable]['hotel_hash']=(string)$model['hotel_hash'];
       $registryBefore[$stable]['post_id']=(int)$model['post_id'];
       STPPI_Repository::save_registry($registryBefore);
     }
     return array('post_id'=>$postId,'stable_id'=>$stable,'hash'=>$hash,
       'source_row'=>(int)$entry['source_row'],'archived'=>false);
   }
   // New source UUID => create a new private Program with a new stable ID.
   $source=array('type'=>'google_sheets','mode'=>'partial','document_ref'=>ST2L_Core::SOURCE,
     'worksheet'=>'Home','timezone'=>'Europe/Istanbul');
   $program=self::make($entry);
   $batch=array('schema_version'=>STPI_SCHEMA_VERSION,'export_id'=>'ST2-'.gmdate('YmdHis').'-'.substr($uuid,0,8),
     'source'=>$source,'programs'=>array($program));
   $result=STPI_Store::import_batch($batch,gmdate(DATE_W3C),'Server Turizm Auto Sync 2.4');
   if(!empty($result['errors'])||empty($result['program_ids'][0]))return new WP_Error('st2_import',implode('; ',$result['errors']??array('Program creation failed')));
   $stable=(string)$result['program_ids'][0];$matches=STPI_Store::find_by_program_id($stable);
   if(count($matches)!==1)return new WP_Error('st2_import','Program creation returned ambiguous ID');
   $postId=(int)$matches[0];$program['program_id']=$stable;
   // New records may be incomplete: create as needs_review without blocking
   // the remaining batch. Approval is earned by existing validator only.
   $program['workflow']['editorial']='needs_review';
   if(!empty($fields['code'])&&!empty($fields['title'])&&class_exists('STPI_Validator')){
     $approval=$program;$approval['workflow']['editorial']='approved';
     $report=STPI_Validator::validate_batch(array('schema_version'=>STPI_SCHEMA_VERSION,
       'source'=>array('type'=>'wordpress','mode'=>'partial'),'programs'=>array($approval)));
     if(empty($report['errors'])&&($report['programs'][0]['publish_gate']??'')==='READY')$program=$approval;
   }
   $wp=wp_update_post(array('ID'=>$postId,'post_title'=>$program['title'],
     'post_content'=>wp_slash(wp_json_encode($program,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES))),true);
   if(is_wp_error($wp))return $wp;
   foreach(array('_st2_source_uuid'=>$uuid,'_st2_source_row'=>(int)$entry['source_row'],
     '_stpi_program_code'=>$program['program_code'],'_stpi_payload_hash'=>STPI_Contract::payload_hash($program),
     '_stpi_editorial_status'=>$program['workflow']['editorial'],
     '_stpi_schedule_status'=>$program['workflow']['schedule'],
     '_stpi_availability_status'=>$program['workflow']['availability'],
     '_stpi_start_date'=>$program['schedule']['start_date'],
     '_stpi_end_date'=>$program['schedule']['end_date']) as $key=>$value)update_post_meta($postId,$key,$value);
   update_post_meta($postId,'_st2_fields',$fields);
   update_post_meta($postId,'_st2_created_by_sync',1);
   return array('post_id'=>$postId,'stable_id'=>$stable,'hash'=>$hash,
     'source_row'=>(int)$entry['source_row'],'archived'=>false,'created_by_sync'=>true);
 }
 public static function refresh_route($program,$postId){
   if(!get_option('st2l_public_enabled',false))return;
   if(!class_exists('STPPI_Renderer')&&defined('STPPI_DIR')){
     foreach(array('class-stppi-repository.php','class-stppi-renderer.php') as $file){$path=STPPI_DIR.'includes/'.$file;if(file_exists($path))require_once $path;}
   }
   if(!class_exists('STPPI_Renderer')||!class_exists('STPPI_Repository'))return;
   $stable=(string)$program['program_id'];$registry=STPPI_Repository::registry();$cfg=$registry[$stable]??array();
   // Never expose unverified or incomplete content via the strict final-route renderer.
   if(($program['workflow']['editorial']??'')!=='approved'){
     if(!empty($cfg)){$cfg['mode']='prepared';$registry[$stable]=$cfg;STPPI_Repository::save_registry($registry);}return;
   }
   if(!is_array($cfg))$cfg=array();
   $cfg['id']=$stable;$cfg['post_id']=$postId;$cfg['mode']='public_noindex';$cfg['seo']=true;$cfg['enabled']=true;
   if(empty($cfg['slug']))$cfg['slug']=STPPI_Repository::suggested_slug($program);
   // Stable slugs protect existing links. Don't take a slug owned by another Program.
   foreach($registry as $other=>$r){if($other!==$stable&&is_array($r)&&($r['slug']??'')===$cfg['slug'])$cfg['slug']=substr($cfg['slug'],0,68).'-'.strtolower($stable);}
   $model=STPPI_Renderer::model($cfg,false);
   if(is_wp_error($model))return;
   $cfg['hash']=$model['hash'];$cfg['hotel_hash']=$model['hotel_hash'];
   $registry[$stable]=$cfg;STPPI_Repository::save_registry($registry);
   update_option('stppi_public_master',true,false);
 }
 public static function archive($r){
   $postId=(int)($r['post_id']??0);$stable=(string)($r['stable_id']??'');
   if(!$postId||get_post_type($postId)!=='stpi_program'||!preg_match('/^STP-\d{6}$/D',$stable))return new WP_Error('st2_archive_id','Program binding invalid');
   if((string)get_post_meta($postId,'_stpi_program_id',true)!==$stable)return new WP_Error('st2_archive_id','Program identity mismatch');
   $p=json_decode((string)get_post_field('post_content',$postId),true);if(!is_array($p))return new WP_Error('st2_archive_payload','Invalid Program JSON');
   if(($p['workflow']['editorial']??'')==='archived')return true;
   $rawRegistry=get_option('stppi_registry',array());
   $cfg=is_array($rawRegistry)?($rawRegistry[$stable]??null):null;
   $live=is_array($cfg)&&in_array((string)($cfg['mode']??''),array('public_noindex','indexable'),true);
   if($live && !class_exists('STPPI_Repository') && defined('STPPI_DIR')){
     $path=STPPI_DIR.'includes/class-stppi-repository.php';if(is_file($path))require_once $path;
   }
   if($live && !class_exists('STPPI_Repository'))return new WP_Error('st2_archive_route','Archive blocked: protected public route runtime unavailable');
   update_post_meta($postId,'_st2_before_archive',$p);
   $p['workflow']['editorial']='archived';$p['workflow']['availability']='closed';$p['publication_mode']='archived_departure';
   $res=wp_update_post(array('ID'=>$postId,'post_content'=>wp_slash(wp_json_encode($p,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES))),true);
   if(is_wp_error($res))return $res;
   update_post_meta($postId,'_stpi_editorial_status','archived');update_post_meta($postId,'_stpi_availability_status','closed');
   if(class_exists('STPPI_Repository')){
     $registry=STPPI_Repository::registry();if(isset($registry[$stable])){$registry[$stable]['mode']='prepared';STPPI_Repository::save_registry($registry);}
   }
   return true;
 }
}
