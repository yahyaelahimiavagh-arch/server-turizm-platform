<?php
/**
 * Explicit one-time confirmation of R2's MADINAH accommodation: Jiwar Al-Madina Hotel.
 * No program, hotel, approval, or display changes occur before an admin confirms.
 */
if (!defined('ABSPATH')) exit;
final class ST2L_R2_Confirmed {
    const PROGRAM_ID = 'STP-000051';
    const HOTEL_ID   = 'STH-000023';
    const HOTEL_NAME = 'Jiwar Al-Madina Hotel';

    private static function err($message){return new WP_Error('st2l_r2_confirm', $message);}

    public static function preflight(){
        if (!current_user_can('manage_options')) return self::err('Administrator permission required.');
        if ((bool)get_option('st2l_write_enabled', false)) return self::err('Pause automatic database updates first.');
        if (!class_exists('STPI_Store') && defined('STPI_DIR')) require_once STPI_DIR.'includes/class-stpi-store.php';
        if (!class_exists('STPI_Hotel_Adapter') && defined('STPI_DIR')) require_once STPI_DIR.'includes/class-stpi-hotel-adapter.php';
        if (!class_exists('STPI_Contract') && defined('STPI_DIR')) require_once STPI_DIR.'includes/class-stpi-contract.php';
        if (!class_exists('STPI_Store') || !class_exists('STPI_Hotel_Adapter') || !class_exists('STPI_Contract'))
            return self::err('Program Intelligence dependencies unavailable.');
        $posts=get_posts(array('post_type'=>'stpi_program', 'post_status'=>array('private','draft','pending','publish'),
            'fields'=>'ids','posts_per_page'=>2,'meta_key'=>'_stpi_program_id','meta_value'=>self::PROGRAM_ID,'no_found_rows'=>true));
        if (count($posts)!==1) return self::err('R2 Stable ID missing or duplicated; no changes made.');
        $pid=(int)$posts[0];$post=get_post($pid);$p=STPI_Store::get_program($pid);
        if (!is_array($p) || ($p['program_id']??'')!==self::PROGRAM_ID || ($p['program_code']??'')!=='R2'
            || ($p['workflow']['editorial']??'')!=='approved' || $post->post_status!=='private')
            return self::err('R2 identity, approval, or post state differs; no changes made.');
        $source=get_post_meta($pid,'_st2_fields',true);
        $source=is_array($source)?$source:array();
        if (ST2L_Hotels::norm((string)($source['medinah_hotel']??''))!==ST2L_Hotels::norm(self::HOTEL_NAME))
            return self::err('Sheet source for R2 Madinah is not Jiwar Al-Madina Hotel.');
        $hotel_posts=get_posts(array('post_type'=>'sthi_hotel','post_status'=>array('publish','draft','private','pending'),
            'fields'=>'ids','posts_per_page'=>2,'meta_key'=>'_sthi_hotel_id','meta_value'=>self::HOTEL_ID,'no_found_rows'=>true));
        if(count($hotel_posts)!==1) return self::err('Jiwar Hotel Stable ID missing or duplicated.');
        $hid=(int)$hotel_posts[0];
        $names=array(get_the_title($hid), get_post_meta($hid,'_sthi_official_name',true),get_post_meta($hid,'_sthi_display_name_tr',true));
        $matched=false;foreach($names as $n)if($n && ST2L_Hotels::norm($n)===ST2L_Hotels::norm(self::HOTEL_NAME))$matched=true;
        if (!$matched) return self::err('Hotel name differs from Jiwar Al-Madina Hotel; manual review required.');
        $resolved=STPI_Hotel_Adapter::resolve(self::HOTEL_ID);
        if (($resolved['status']??'')!=='resolved'
            || ($resolved['verification_status']??'')!=='verified'
            || ($resolved['workflow_status']??'')!=='published'
            || get_post_status($hid)!=='publish'
            || empty($resolved['public_url']) || !class_exists('STPPI_Renderer')
            || !STPPI_Renderer::local_url($resolved['public_url']))
            return self::err('Hotel record exists, but it is not fully Verified / Published with a valid public URL. No changes made.');
        $indexes=array();
        foreach((array)($p['stays']??array()) as $i=>$stay){
            if (($stay['destination']??'')==='Madinah') $indexes[]=$i;
        }
        if(count($indexes)!==1) return self::err('R2 must contain exactly one Madinah stay.');
        $idx=$indexes[0]; $stay=$p['stays'][$idx];
        $already=(string)($stay['hotel_id']??'')===self::HOTEL_ID;
        if (!$already && ((!empty($stay['hotel_id']))
             || ST2L_Hotels::norm((string)($stay['unresolved_hotel_name']??''))!==ST2L_Hotels::norm(self::HOTEL_NAME)))
            return self::err('Existing R2 Madinah stay points to another hotel; no changes made.');
        return array('post_id'=>$pid,'hotel_post_id'=>$hid,'program'=>$p,'stay_index'=>$idx,'already'=>$already,'hotel'=>$resolved);
    }

    public static function handle_post(){
        if(!isset($_POST['st2l_confirm_r2_jiwar']))return;
        if(!current_user_can('manage_options') || !check_admin_referer('st2l_confirm_r2_jiwar','st2l_confirm_r2_nonce'))wp_die('Forbidden');
        $p=self::preflight();
        if(is_wp_error($p)){self::message($p->get_error_message(),true);return;}
        if($p['already']){self::message('Jiwar is already connected to R2.');return;}
        $id=$p['post_id'];$program=$p['program'];$idx=$p['stay_index'];
        $old_content=(string)get_post_field('post_content',$id);
        $old_meta=get_post_meta($id,'_stpi_payload_hash',true);
        $old_registry=get_option('stppi_registry',array());
        // Local, scoped rollback record. It is not a full-site backup.
        update_option('st2l_r2_jiwar_before',array('post_id'=>$id,'content'=>$old_content,
            'payload_hash'=>$old_meta,'renderer_config'=>is_array($old_registry)?($old_registry[self::PROGRAM_ID]??null):null,
            'created_at'=>gmdate('c')),false);
        $program['stays'][$idx]['hotel_id']=self::HOTEL_ID;
        $program['stays'][$idx]['unresolved_hotel_name']=null;
        unset($program['stays'][$idx]['pending_hotel_reviewed']);
        $write=wp_update_post(array('ID'=>$id,'post_content'=>wp_slash(wp_json_encode($program,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE))),true);
        if(is_wp_error($write)) {self::message('WordPress write failed: '.$write->get_error_message(),true);return;}
        update_post_meta($id,'_stpi_payload_hash',STPI_Contract::payload_hash($program));
        $ok=true;$detail='';
        try {
            $r=ST2L_Legacy_Publisher::reconcile();
            if(!empty($r['errors'][self::PROGRAM_ID]) || !empty($r['pending'][self::PROGRAM_ID])){
                $ok=false;$detail=(string)($r['errors'][self::PROGRAM_ID]??$r['pending'][self::PROGRAM_ID]);
            }
            // Check the actual public renderer model after pin/hash reconciliation.
            if ($ok && class_exists('STPPI_Repository') && class_exists('STPPI_Renderer')){
                $reg=STPPI_Repository::registry();
                $cfg=$reg[self::PROGRAM_ID]??array();
                $check=$cfg?STPPI_Renderer::model($cfg,true):self::err('R2 publishing registry missing.');
                if(is_wp_error($check)){$ok=false;$detail=$check->get_error_message();}
            }
        }catch(Throwable $e){$ok=false;$detail=$e->getMessage();}
        if(!$ok){
            wp_update_post(array('ID'=>$id,'post_content'=>wp_slash($old_content)));
            if($old_meta==='')delete_post_meta($id,'_stpi_payload_hash');else update_post_meta($id,'_stpi_payload_hash',$old_meta);
            update_option('stppi_registry',$old_registry,false);
            self::message('Change rolled back: '.$detail,true);return;
        }
        // Purge caches supported by the installed LiteSpeed and WordPress when available.
        if(class_exists('LiteSpeed_Cache_API')){try{LiteSpeed_Cache_API::purge_all();}catch(Throwable $e){}}
        if(function_exists('wp_cache_flush'))wp_cache_flush();
        self::message('R2 Madinah is now linked to verified Jiwar Hotel. Makkah stay and R1 are unchanged.');
    }
    private static function message($message,$error=false){
        echo '<div class="notice notice-'.($error?'error':'success').' is-dismissible"><p>'.esc_html($message).'</p></div>';
    }
    public static function render_section(){
        $p=self::preflight();
        echo '<hr><h2>R2 — Confirmed Jiwar Al-Madina hotel connection</h2>';
        echo '<p>Only links the R2 Madinah stay to <strong>STH-000023</strong>. R1, R2 Makkah stay, and the existing design are left unchanged.</p>';
        if (is_wp_error($p)) {echo '<p><strong>Not ready:</strong> '.esc_html($p->get_error_message()).'</p>';return;}
        if($p['already']){echo '<p><strong>Connected:</strong> R2 / Madinah → STH-000023.</p>';return;}
        echo '<p>Verified hotel: <strong>'.esc_html((string)($p['hotel']['name']??self::HOTEL_NAME)).'</strong>.</p>';
        echo '<form method="post">';wp_nonce_field('st2l_confirm_r2_jiwar','st2l_confirm_r2_nonce');
        echo '<button type="submit" name="st2l_confirm_r2_jiwar" value="1" class="button button-primary">Connect confirmed Jiwar to R2</button></form>';
    }
}
