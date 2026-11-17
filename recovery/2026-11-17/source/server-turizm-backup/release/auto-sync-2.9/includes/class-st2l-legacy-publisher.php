<?php
/**
 * Connect fully validated Auto Sync-created programs to the ORIGINAL
 * Server Turizm Program Publishing Integration route and hub renderer.
 * Does not render cards, register shortcodes or alter CSS/JS/templates.
 */
if (!defined('ABSPATH')) exit;
final class ST2L_Legacy_Publisher {
    public static function reconcile() {
        $report=array('at'=>gmdate('c'),'published'=>array(),'already_live'=>array(),'pending'=>array(),'errors'=>array(),'renderer'=>'STPPI original');
        // Never open the site's master publishing or hub gates by automation.
        if(!defined('STPPI_DIR') || !function_exists('stppi_public_master_enabled') || !function_exists('stppi_hub_bridge_enabled') || !stppi_public_master_enabled() || !stppi_hub_bridge_enabled()) {
            $report['notice']='Original publishing master or hub bridge is not enabled; existing site settings preserved.';
            self::save($report); return $report;
        }
        foreach(array('class-stppi-repository.php'=>'STPPI_Repository', 'class-stppi-renderer.php'=>'STPPI_Renderer') as $file=>$class) {
            if(class_exists($class))continue;
            $path=STPPI_DIR.'includes/'.$file;
            if(!is_file($path)) { $report['errors'][]='Publishing runtime file missing: '.$file;self::save($report);return $report; }
            require_once $path;
        }
        if(!class_exists('STPPI_Renderer') || !class_exists('STPPI_Repository')) {
            $report['errors'][]='Original STPPI renderer not available';self::save($report);return $report;
        }
        $registry=STPPI_Repository::registry(); $changed=false; $slugs=array();
        foreach($registry as $oldID=>$cfg){ if(is_array($cfg)&&!empty($cfg['slug'])) $slugs[(string)$cfg['slug']]=(string)$oldID; }
        $target=get_option('st2l_target',array()); $removed=array();
        foreach((array)($target['programs']??array()) as $e){if(is_array($e)&&!empty($e['removed'])&&!empty($e['uuid']))$removed[(string)$e['uuid']]=true;}
        foreach(ST2L_Core::reg('programs') as $uuid=>$binding) {
            if(!is_array($binding)||!empty($binding['archived'])||isset($removed[$uuid]))continue;
            $stable=(string)($binding['stable_id']??'');$postID=(int)($binding['post_id']??0);
            if(!preg_match('/^STP-\d{6}$/D',$stable)||!$postID || in_array($stable,array('STP-000036','STP-000037'),true))continue;
            if(get_post_type($postID)!=='stpi_program' || (string)get_post_meta($postID,'_stpi_program_id',true)!==$stable || (string)get_post_meta($postID,'_st2_source_uuid',true)!==(string)$uuid) {
                $report['errors'][$stable]='Stable ID or source UUID mismatch; publication refused.';continue;
            }
            $post=get_post($postID);$p=$post?json_decode((string)$post->post_content,true):null;
            if(!is_array($p) || (string)($p['program_id']??'')!==$stable) {
                $report['errors'][$stable]='Invalid canonical payload; publication refused.';continue;
            }
            // Only entries actually created by this sync are enrolled automatically.
            // Older adopted Programs keep their existing publishing registry untouched.
            $origin=(string)($p['provenance']['source_ref']??'');
            $created= !empty($binding['created_by_sync']) || (bool)get_post_meta($postID,'_st2_created_by_sync',true) || $origin==='ST-ROW:'.$uuid;
            if(!$created)continue;
            if(($p['workflow']['editorial']??'')!=='approved' || ($p['workflow']['schedule']??'')!=='scheduled') {
                $report['pending'][$stable]='Needs review, pending hotel, or no fixed schedule; original renderer requires approval.';continue;
            }
            $prior=isset($registry[$stable])&&is_array($registry[$stable])?$registry[$stable]:array();
            $cfg=$prior;
            $cfg['id']=$stable;
            $cfg['post_id']=$postID;
            $cfg['seo']=true;
            $cfg['enabled']=true;
            // Keep already issued canonical URL and publication mode unchanged.
            if(!empty($prior['mode'])&&in_array((string)$prior['mode'],array('indexable','public_noindex'),true)){
                $cfg['mode']=$prior['mode'];
            } else {
                $cfg['mode']='public_noindex'; // never indexable automatically
            }
            if(empty($cfg['slug'])) {
                $proposed=STPPI_Repository::suggested_slug($p);
                $slug=$proposed;
                if(isset($slugs[$slug])&&$slugs[$slug]!==$stable){
                    $tail='-'.strtolower($stable);
                    $slug=trim(substr($proposed,0,80-strlen($tail)),'-').$tail;
                }
                if(!STPPI_Renderer::valid_slug($slug) || (isset($slugs[$slug])&&$slugs[$slug]!==$stable)){
                    $report['errors'][$stable]='Unique safe slug unavailable; manual publication required.';continue;
                }
                $cfg['slug']=$slug;
            }
            if(!STPPI_Renderer::valid_slug($cfg['slug']) || (isset($slugs[$cfg['slug']]) && $slugs[$cfg['slug']]!==$stable)){
                $report['errors'][$stable]='Conflicting or invalid existing publishing slug.';continue;
            }
            $m=STPPI_Renderer::model($cfg,false);
            if(is_wp_error($m)){
                $report['pending'][$stable]=$m->get_error_message();continue;
            }
            $cfg['hash']=(string)$m['hash'];$cfg['hotel_hash']=(string)$m['hotel_hash'];
            // No live route is made indexable here. Final content stays entirely STPPI-owned.
            if($prior===$cfg) { $report['already_live'][]=$stable;continue; }
            $cfg['auto_sync_native_renderer']=true;
            $cfg['auto_sync_published_at']=gmdate('c');
            if(empty($cfg['opened_at'])) $cfg['opened_at']=gmdate('c');
            $registry[$stable]=$cfg;$slugs[$cfg['slug']]=$stable;
            $report['published'][]=$stable;$changed=true;
        }
        if($changed)STPPI_Repository::save_registry($registry);
        self::save($report);return $report;
    }
    private static function save($report){update_option('st2l_original_renderer_report',$report,false);}
}
