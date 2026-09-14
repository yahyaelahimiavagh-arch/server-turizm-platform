<?php
if (!defined('ABSPATH')) { exit; }
final class STPPI_Admin {
    public static function init(){
        add_action('admin_menu',array(__CLASS__,'menu'),99);
        add_action('admin_enqueue_scripts',array(__CLASS__,'assets'));
        foreach(array('prepare','open','close') as $a) add_action('admin_post_stppi_'.$a,array(__CLASS__,$a));
        add_action('admin_post_stppi_preview',array(__CLASS__,'preview'));
        add_action('admin_post_stppi_evidence',array(__CLASS__,'evidence'));
        add_action('admin_post_stppi_hub_preview',array(__CLASS__,'hub_preview'));
        add_action('admin_post_stppi_hotel_preview',array(__CLASS__,'hotel_preview'));
        add_action('admin_post_stppi_archive_preview',array(__CLASS__,'archive_preview'));
        add_action('admin_post_stppi_hub_enable',array(__CLASS__,'hub_enable'));
        add_action('admin_post_stppi_hub_disable',array(__CLASS__,'hub_disable'));
        add_action('admin_post_stppi_hotel_allowlist_save',array(__CLASS__,'hotel_allowlist_save'));
        add_action('admin_post_stppi_hotel_rollout_enable',array(__CLASS__,'hotel_rollout_enable'));
        add_action('admin_post_stppi_hotel_rollout_disable',array(__CLASS__,'hotel_rollout_disable'));
        add_action('admin_post_stppi_hotel_allowlist_clear',array(__CLASS__,'hotel_allowlist_clear'));
        add_action('admin_post_stppi_umre_skin_enable',array(__CLASS__,'umre_skin_enable'));
        add_action('admin_post_stppi_umre_skin_disable',array(__CLASS__,'umre_skin_disable'));
        add_action('admin_post_stppi_recover_live_baseline',array(__CLASS__,'recover_live_baseline'));
        add_action('admin_post_stppi_identity_repair_refresh',array(__CLASS__,'identity_repair_refresh'));
    }
    public static function menu(){ add_submenu_page('stpi-dashboard','Yayın & Entegrasyon','Yayın & Entegrasyon','manage_options','stppi-publishing',array(__CLASS__,'page')); }
    public static function assets($hook){ if(strpos((string)$hook,'stppi-publishing')===false)return; wp_enqueue_style('stppi-admin',STPPI_URL.'assets/admin.css',array(),STPPI_VERSION); }
    private static function guard($action,$method='POST'){ if(!current_user_can('manage_options') || ($_SERVER['REQUEST_METHOD']??'')!==$method)wp_die('Erişim reddedildi.','',array('response'=>403)); check_admin_referer('stppi_'.$action); }
    private static function finish($msg){ wp_safe_redirect(add_query_arg(array('page'=>'stppi-publishing','stppi_message'=>$msg),admin_url('admin.php'))); exit; }
    private static function record($id){$r=STPPI_Repository::registry(); return (isset($r[$id])&&is_array($r[$id]))?$r[$id]:null;}
    private static function log($action,$row=array()){add_option('stppi_audit_'.str_replace('-','',wp_generate_uuid4()),array('at'=>gmdate('c'),'user'=>get_current_user_id(),'action'=>$action,'program_id'=>$row['id']??'' ,'slug'=>$row['slug']??'','mode'=>$row['mode']??''),'',false);}
    public static function prepare(){
        self::guard('prepare'); $id=sanitize_text_field(wp_unslash($_POST['program_id']??''));
        if(in_array($id,array('STP-000036','STP-000037'),true))self::finish('Test kayıtları yayın kaydına hazırlanamaz.');
        $existing=self::record($id); $base=$existing?:array('id'=>$id,'slug'=>'','mode'=>'prepared','seo'=>true,'post_id'=>0,'hash'=>'','hotel_hash'=>'');
        if(empty($base['slug'])){ $raw=STPPI_Repository::row($id); if(is_wp_error($raw))self::finish($raw->get_error_message()); $base['slug']=STPPI_Repository::suggested_slug($raw['program']); }
        $m=STPPI_Renderer::model($base,false); if(is_wp_error($m))self::finish('Yayın hazırlığı durdu: '.$m->get_error_message().' Program Intelligence → Review ekranını kullanın.');
        $registry=STPPI_Repository::registry(); foreach($registry as $other=>$r){if($other!==$id && is_array($r) && ($r['slug']??'')===$base['slug'])self::finish('Adres çakışması bulundu. Manuel inceleme gerekli.');}
        $prior_mode=(string)($base['mode']??'prepared');$base['post_id']=$m['post_id'];$base['hash']=$m['hash'];$base['hotel_hash']=$m['hotel_hash'];$base['mode']=in_array($prior_mode,array('public_noindex','indexable'),true)?$prior_mode:'prepared';$base['seo']=true;$base['review_token']=wp_generate_uuid4();$base['prepared_at']=gmdate('c');
        $registry[$id]=$base;STPPI_Repository::save_registry($registry);self::log('prepared',$base);self::finish('Final adres doğrulandı/yenilendi; mevcut route modu korundu: '.STPPI_Renderer::url($base));
    }
    public static function open(){
        self::guard('open'); $id=sanitize_text_field(wp_unslash($_POST['program_id']??'')); $ack=($_POST['ack']??'')==='1'; if(!$ack)self::finish('Genel noindex erişim için açık onay gerekli.');
        $registry=STPPI_Repository::registry();$c=$registry[$id]??null;if(!is_array($c))self::finish('Önce final adresi hazırlayın.');
        $token=(string)wp_unslash($_POST['review_token']??'');if(empty($c['review_token'])||!hash_equals((string)$c['review_token'],$token))self::finish('Sayfa güncellendi. Yenileyip tekrar inceleyin.');
        $m=STPPI_Renderer::model($c);if(is_wp_error($m)){self::finish('Açılış durdu; mevcut canlı runtime DEĞİŞMEDİ: '.$m->get_error_message());}
        // v0.4.8 additive publishing: never demote other accepted public/noindex routes.
        $registry[$id]['mode']='public_noindex';update_option('stppi_registry',$registry,false);update_option('stppi_public_master',true,false);self::log('public_noindex_opened',$registry[$id]);self::finish('Tek final Program adresi public/noindex açıldı. Hub ve Hotel linkleri hâlâ kapalı.');
    }
    public static function hub_enable(){ self::guard('hub_enable'); $ack=($_POST['ack']??'')==='1'; if(!$ack)self::finish('Canlı Hub köprüsü için açık onay gerekli.'); if(!stppi_public_master_enabled())self::finish('Önce en az bir final Program adresini public/noindex açın.'); $items=STPPI_Integrations::eligible_public_noindex_programs(); if(!$items)self::finish('Hub için geçerli public/noindex Program bulunamadı.'); update_option('stppi_hub_bridge_enabled',true,false); self::log('hub_bridge_enabled'); self::finish('Hub köprüsü HAZIR/AÇIK. /umre-1/ ancak [stppi_umre_programs] shortcode yerleştirilirse değişir.'); }
    public static function hub_disable(){ self::guard('hub_disable'); update_option('stppi_hub_bridge_enabled',false,false); stppi_close_hotel_links(false); self::log('hub_bridge_disabled'); self::finish('Hub köprüsü kapatıldı. Hotel public relation da güvenlik için kapatıldı.'); }
    public static function recover_live_baseline(){
        self::guard('recover_live_baseline');
        if(($_POST['ack']??'')!=='1') self::finish('Acil recovery için açık onay gerekli.');
        $registry=STPPI_Repository::registry();
        if(!$registry || !is_array($registry)) self::finish('Recovery için mevcut final route registry bulunamadı.');
        $candidate=$registry; $errors=array(); $skipped=array(); $count=0;
        foreach($registry as $id=>$cfg){
            if(!is_array($cfg) || in_array($id,array('STP-000036','STP-000037'),true)) continue;
            if(empty($cfg['slug']) || !STPPI_Renderer::valid_slug((string)$cfg['slug'])){ $errors[$id]='Geçersiz/missing slug.'; continue; }

            // Current Hub contract includes UPCOMING + IN_PROGRESS Programs.
            // Only completed/other non-current temporal states stay PREPARED for archive-policy handling.
            $row=STPPI_Repository::row($id);
            if(is_wp_error($row)){ $errors[$id]=$row->get_error_message(); continue; }
            $program=is_array($row['program']??null)?$row['program']:array();
            $temporal=STPPI_Repository::temporal($program);
            if(!in_array($temporal,array('upcoming','in_progress'),true)){
                $candidate[$id]['mode']='prepared';
                $candidate[$id]['recovery_temporal_skip']=$temporal;
                $candidate[$id]['recovery_temporal_skip_at']=gmdate('c');
                $skipped[$id]=$temporal;
                continue;
            }

            $probe=$cfg; $probe['id']=$id;
            $m=STPPI_Renderer::model($probe,false);
            if(is_wp_error($m)){ $errors[$id]=$m->get_error_message(); continue; }
            $candidate[$id]['post_id']=$m['post_id'];
            $candidate[$id]['hash']=$m['hash'];
            $candidate[$id]['hotel_hash']=$m['hotel_hash'];
            $candidate[$id]['mode']='public_noindex';
            $candidate[$id]['seo']=true;
            $candidate[$id]['review_token']=wp_generate_uuid4();
            $candidate[$id]['prepared_at']=gmdate('c');
            $candidate[$id]['opened_at']=gmdate('c');
            $candidate[$id]['recovered_at']=gmdate('c');
            unset($candidate[$id]['recovery_temporal_skip'],$candidate[$id]['recovery_temporal_skip_at']);
            $count++;
        }
        if($errors){
            $first=array_slice($errors,0,6,true); $parts=array(); foreach($first as $pid=>$err){$parts[]=$pid.': '.$err;}
            self::finish('RECOVERY BLOKE — hiçbir runtime/registry değişmedi. '.count($errors).' gerçek hata: '.implode(' | ',$parts));
        }
        if($count<1) self::finish('Recovery için doğrulanmış güncel/gelecek gerçek route bulunamadı; hiçbir şey değişmedi.');
        STPPI_Repository::save_registry($candidate);
        update_option('stppi_public_master',true,false);
        update_option('stppi_hub_bridge_enabled',true,false);
        // Hotel relations stay OFF after emergency recovery; re-enable only through its own preflight gate.
        update_option('stppi_hotel_links_enabled',false,false);
        self::log('live_baseline_recovered',array('id'=>'','slug'=>'','mode'=>'public_noindex'));
        $skip_text=$skipped ? (' '.count($skipped).' tamamlanmış/güncel olmayan eski route Hub dışında PREPARED bırakıldı: '.implode(', ',array_keys($skipped)).'.') : '';
        self::finish('RECOVERY PASS: '.$count.' güncel/gelecek registry route canonical veriyle refresh edildi; PUBLIC/NOINDEX + /umre-1/ Hub AÇIK.'.$skip_text.' Hotel→Program rollout güvenlik için KAPALI.');
    }
    public static function identity_repair_refresh(){
        self::guard('identity_repair_refresh');
        if(($_POST['ack']??'')!=='1') self::finish('Identity repair registry refresh için açık onay gerekli.');
        $repair=get_option('stpi_identity_repair_latest',array());
        if(!is_array($repair)||empty($repair['repair_id'])||empty($repair['public_refresh_ids'])||!is_array($repair['public_refresh_ids'])) self::finish('Program Intelligence tarafında committed identity repair bulunamadı.');
        $last=get_option('stppi_identity_repair_refresh_last',array());
        if(is_array($last)&&($last['repair_id']??'')===($repair['repair_id']??'')) self::finish('Bu identity repair registry refresh daha önce tamamlandı.');

        $ids=array_values(array_unique(array_filter(array_map('strval',$repair['public_refresh_ids']),function($id){
            return preg_match('/^STP-[0-9]{6}$/D',$id)&&!in_array($id,array('STP-000036','STP-000037','STP-000038'),true);
        })));
        if(!$ids) self::finish('Refresh için güvenli public target bulunamadı.');

        $registry=STPPI_Repository::registry();
        $candidate=$registry;
        $errors=array();
        $count=0;
        foreach($ids as $id){
            $cfg=$registry[$id]??null;
            if(!is_array($cfg)){ $errors[$id]='Existing registry entry missing.'; continue; }
            if(($cfg['mode']??'')!=='public_noindex'){ $errors[$id]='Prior route mode is not public_noindex; automatic reopening refused.'; continue; }
            if(empty($cfg['slug'])||!STPPI_Renderer::valid_slug((string)$cfg['slug'])){ $errors[$id]='Invalid/missing final slug.'; continue; }

            $row=STPPI_Repository::row($id);
            if(is_wp_error($row)){ $errors[$id]=$row->get_error_message(); continue; }
            $p=$row['program']??array(); $wf=$p['workflow']??array();
            if(($wf['editorial']??'')!=='approved'||($wf['schedule']??'')!=='scheduled'||!in_array(STPPI_Repository::temporal($p),array('upcoming','in_progress'),true)){
                $errors[$id]='Program is not APPROVED + SCHEDULED + CURRENT/UPCOMING.'; continue;
            }
            $probe=$cfg; $probe['id']=$id;
            $m=STPPI_Renderer::model($probe,false);
            if(is_wp_error($m)){ $errors[$id]=$m->get_error_message(); continue; }
            $candidate[$id]['post_id']=$m['post_id'];
            $candidate[$id]['hash']=$m['hash'];
            $candidate[$id]['hotel_hash']=$m['hotel_hash'];
            $candidate[$id]['mode']='public_noindex';
            $candidate[$id]['seo']=true;
            $candidate[$id]['review_token']=wp_generate_uuid4();
            $candidate[$id]['prepared_at']=gmdate('c');
            $candidate[$id]['identity_repair_refreshed_at']=gmdate('c');
            $candidate[$id]['identity_repair_id']=(string)$repair['repair_id'];
            $count++;
        }
        if($errors){
            $parts=array(); foreach(array_slice($errors,0,8,true) as $pid=>$err){$parts[]=$pid.': '.$err;}
            self::finish('IDENTITY REPAIR REFRESH BLOKE — registry/runtime unchanged. '.count($errors).' hata: '.implode(' | ',$parts));
        }
        if($count!==count($ids)) self::finish('IDENTITY REPAIR REFRESH BLOKE — target count mismatch; hiçbir registry write yapılmadı.');

        STPPI_Repository::save_registry($candidate);
        update_option('stppi_identity_repair_refresh_last',array(
            'repair_id'=>(string)$repair['repair_id'],
            'at'=>gmdate('c'),
            'program_ids'=>$ids,
            'count'=>$count
        ),false);
        self::log('identity_repair_registry_refreshed',array('id'=>'','slug'=>'','mode'=>'public_noindex'));
        self::finish('IDENTITY REPAIR REFRESH PASS: '.$count.' önceki public/noindex route hash/hotel hash canonical Stable-ID verisine göre atomik yenilendi. STP-000038 açılmadı; Hub/Public Master mevcut durumda korundu.');
    }

    public static function umre_skin_enable(){ self::guard('umre_skin_enable'); if(($_POST['ack']??'')!=='1') self::finish('Performance-Light Pearl background için açık onay gerekli.'); update_option('stppi_umre_skin_enabled',true,false); self::log('umre_skin_enabled',array('mode'=>'premium_soft')); self::finish('Umre Performance-Light Pearl background AÇIK. Yalnızca /umre-1/ sayfa arka planı değişti; Program kartları korunur.'); }
    public static function umre_skin_disable(){ self::guard('umre_skin_disable'); update_option('stppi_umre_skin_enabled',false,false); self::log('umre_skin_disabled',array('mode'=>'legacy')); self::finish('Umre Performance-Light Pearl background KAPALI. /umre-1/ Legacy görünümüne döndü.'); }
    public static function hotel_allowlist_save(){
        self::guard('hotel_allowlist_save');
        $raw = isset($_POST['hotel_ids']) && is_array($_POST['hotel_ids']) ? wp_unslash($_POST['hotel_ids']) : array();
        $requested = array();
        foreach (array_slice($raw, 0, 25) as $id) {
            if (!is_scalar($id)) { continue; }
            $id = strtoupper(sanitize_text_field((string)$id));
            if (preg_match('/^STH-[0-9]{6}$/D', $id)) { $requested[$id] = true; }
        }
        $requested = array_keys($requested);
        sort($requested, SORT_STRING);
        $relations = STPPI_Integrations::preview_hotel_relations();
        foreach ($requested as $hotel_id) {
            if (empty($relations[$hotel_id])) {
                stppi_close_hotel_links(false);
                self::finish('STOP: '.$hotel_id.' için güncel/eligible derived Program ilişkisi doğrulanamadı. Rollout KAPALI kaldı.');
            }
            if (!STPPI_Integrations::hotel_public_url($hotel_id)) {
                stppi_close_hotel_links(false);
                self::finish('STOP: '.$hotel_id.' final Hotel public route doğrulanamadı. Rollout KAPALI kaldı.');
            }
        }
        $saved = stppi_set_hotel_allowlist($requested);
        delete_option('stppi_hotel_link_canary');
        stppi_close_hotel_links(false);
        self::log('hotel_relation_allowlist_saved',array('id'=>implode(',', $saved),'mode'=>'allowlist_off'));
        self::finish('Hotel→Program allowlist kaydedildi ve KAPALI tutuldu: '.count($saved).' Hotel.');
    }
    public static function hotel_rollout_enable(){
        self::guard('hotel_rollout_enable');
        if (($_POST['ack']??'') !== '1') self::finish('Canlı Hotel allowlist rollout için açık onay gerekli.');
        if (!stppi_public_master_enabled() || !stppi_hub_bridge_enabled()) self::finish('Önce accepted public/noindex Program + canlı Hub baseline açık olmalı.');
        $allowlist = stppi_hotel_allowlist_ids();
        if (!$allowlist) self::finish('Önce en az bir Stable Hotel ID allowlist’e ekleyin.');
        $relations = STPPI_Integrations::preview_hotel_relations();
        foreach ($allowlist as $hotel_id) {
            if (empty($relations[$hotel_id])) { stppi_close_hotel_links(false); self::finish('STOP: '.$hotel_id.' relation preflight başarısız. Rollout KAPALI kaldı.'); }
            if (!STPPI_Integrations::hotel_public_url($hotel_id)) { stppi_close_hotel_links(false); self::finish('STOP: '.$hotel_id.' public route doğrulanamadı. Rollout KAPALI kaldı.'); }
            if (!STPPI_Integrations::programs_for_hotel($hotel_id)) { stppi_close_hotel_links(false); self::finish('STOP: '.$hotel_id.' için eligible public Program yok. Rollout KAPALI kaldı.'); }
        }
        update_option('stppi_hotel_links_enabled', true, false);
        self::log('hotel_relation_allowlist_opened',array('id'=>implode(',', $allowlist),'mode'=>'public_allowlist'));
        self::finish('Hotel→Program allowlist rollout AÇIK: '.count($allowlist).' exact Hotel. Allowlist dışındaki Hotel sayfaları etkilenmez.');
    }
    public static function hotel_rollout_disable(){
        self::guard('hotel_rollout_disable');
        $allowlist = stppi_hotel_allowlist_ids();
        stppi_close_hotel_links(false);
        self::log('hotel_relation_allowlist_closed',array('id'=>implode(',', $allowlist),'mode'=>'off'));
        self::finish('Hotel→Program rollout acil olarak KAPATILDI. Allowlist rollback/inceleme için korundu.');
    }
    public static function hotel_allowlist_clear(){
        self::guard('hotel_allowlist_clear');
        stppi_close_hotel_links(true);
        self::log('hotel_relation_allowlist_cleared',array('mode'=>'off_empty'));
        self::finish('Hotel→Program rollout KAPALI ve Stable Hotel allowlist temizlendi.');
    }
    public static function close(){ self::guard('close'); stppi_close_all(); self::log('closed'); self::finish('Tüm final Program genel erişimi kapatıldı. Kaynak Program/Hotel verisi değişmedi.'); }
    public static function preview(){
        self::guard('preview','GET');$id=sanitize_text_field(wp_unslash($_GET['program_id']??''));$c=self::record($id);if(!$c)wp_die('Final adres hazırlanmadı.','',array('response'=>409));$m=STPPI_Renderer::model($c);if(is_wp_error($m))wp_die(esc_html($m->get_error_message()),'',array('response'=>409));STPPI_Renderer::headers();STPPI_Renderer::render($m,$c,true);exit;
    }
    public static function evidence(){
        self::guard('evidence','GET');$all=STPPI_Repository::all_rows();if(is_wp_error($all))wp_die(esc_html($all->get_error_message()),'',array('response'=>409));
        $hotel_allowlist=stppi_hotel_allowlist_ids();
        $out=array('version'=>STPPI_VERSION,'captured_at'=>gmdate('c'),'public_master'=>stppi_public_master_enabled(),'hub_bridge'=>stppi_hub_bridge_enabled(),'hotel_links'=>stppi_hotel_links_enabled(),'hotel_relation_mode'=>'stable_id_allowlist','hotel_allowlist'=>$hotel_allowlist,'hotel_allowlist_count'=>count($hotel_allowlist),'hotel_link_canary'=>stppi_hotel_link_canary_id(),'umre_visual_skin'=>'performance_light_pearl_background','umre_visual_skin_public'=>stppi_umre_skin_enabled(),'route_base'=>STPPI_ROUTE_BASE,'registry'=>STPPI_Repository::registry(),'programs'=>array(),'duplicate_ids'=>$all['duplicates'],'program_count'=>count($all['rows']),'foundation'=>array('detected'=>defined('STPF_VERSION'),'version'=>defined('STPF_VERSION')?STPF_VERSION:null));
        foreach($all['rows'] as $id=>$r){$out['programs'][$id]=array('post_id'=>$r['post_id'],'post_status'=>$r['post_status'],'payload_hash'=>$r['payload_hash'],'post_hash'=>$r['post_hash'],'metadata_hash'=>$r['metadata_hash']);}
        nocache_headers();header('Content-Type: application/json; charset=UTF-8');header('Content-Disposition: attachment; filename="stppi-evidence-'.gmdate('Ymd-His').'.json"');echo wp_json_encode($out,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);exit;
    }

    private static function preview_shell_start($title,$subtitle){
        nocache_headers(); header('X-Robots-Tag: noindex, nofollow, noarchive, nosnippet',true); header('Content-Type: text/html; charset=UTF-8',true);
        echo '<!doctype html><html lang="tr"><head><meta charset="utf-8"><meta name="robots" content="noindex,nofollow,noarchive,nosnippet"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.esc_html($title).' | Server Turizm</title><style>';
        echo 'body{margin:0;background:#f8f6f0;color:#14202d;font:16px/1.55 system-ui,-apple-system,Segoe UI,Arial,sans-serif}.bar{background:#fff2c9;border-bottom:1px solid #e2c66d;padding:12px 20px;text-align:center;font-weight:800;color:#71520d}.wrap{max-width:1180px;margin:34px auto;padding:0 20px}.head{display:flex;justify-content:space-between;gap:24px;align-items:end;margin-bottom:26px}.eyebrow{color:#9b7428;font-size:12px;letter-spacing:.12em;font-weight:800;text-transform:uppercase}.head h1{margin:.25rem 0 0;font-size:clamp(30px,4vw,48px);line-height:1.05}.muted{color:#66717c}.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:18px}.card{background:#fff;border:1px solid #e7dfcf;border-radius:18px;overflow:hidden;box-shadow:0 10px 30px rgba(12,27,45,.06)}.card .body{padding:20px}.card h2,.card h3{margin:0 0 8px}.meta{display:flex;flex-wrap:wrap;gap:8px;margin:12px 0}.pill{border:1px solid #e4d19d;background:#fbf5e5;border-radius:999px;padding:5px 9px;font-size:12px;font-weight:700}.price{font-size:26px;font-weight:800}.btn{display:inline-block;background:#d1aa52;color:#0b1b2e;text-decoration:none;font-weight:800;padding:10px 14px;border-radius:10px}.hotel{border-left:4px solid #d1aa52}.code{font-family:ui-monospace,SFMono-Regular,Consolas,monospace;background:#f2efe8;padding:2px 6px;border-radius:5px}.section{margin-top:42px}.notice{padding:16px 18px;border:1px solid #e7dfcf;background:#fff;border-radius:12px}.list{display:grid;gap:12px}.row{background:#fff;border:1px solid #e7dfcf;padding:16px;border-radius:12px}.ok{color:#246b45;font-weight:800}.blocked{color:#a33;font-weight:800}';
        echo '</style></head><body><div class="bar">ÖZEL ENTEGRASYON ÖNİZLEMESİ · Canlı /umre-1/ veya Hotel sayfasını değiştirmez</div><main class="wrap"><div class="head"><div><div class="eyebrow">Server Turizm · Publishing Integration v'.esc_html(STPPI_VERSION).'</div><h1>'.esc_html($title).'</h1><p class="muted">'.esc_html($subtitle).'</p></div></div>';
    }
    private static function preview_shell_end(){ echo '</main></body></html>'; exit; }
    public static function hub_preview(){
        self::guard('hub_preview','GET');
        $items=STPPI_Integrations::preview_programs();
        self::preview_shell_start('/umre-1/ Program Hub Preview','Program Intelligence verisinden üretilen kartların migration önizlemesi. Canlı /umre-1/ değişmez.');
        if(!$items){ echo '<div class="notice">Önizlenebilir approved + upcoming + final-route hazırlanmış Program yok.</div>'; self::preview_shell_end(); }
        echo '<div class="grid">';
        foreach($items as $x){$p=$x['program'];$c=$x['config'];$prices=(array)($p['pricing']['entries']??array());$amounts=array();foreach($prices as $pr){if(is_numeric($pr['amount']??null))$amounts[]=(float)$pr['amount'];}$from=$amounts?min($amounts):null;$hotels=array();foreach((array)($x['model']['hotels']??array()) as $h){$n=(string)($h['name']??'');if($n!=='')$hotels[]=$n;}
            echo '<article class="card"><div class="body"><div class="eyebrow">PROGRAM '.esc_html($p['program_code']??'').'</div><h2>'.esc_html($p['title']??'').'</h2><div class="meta"><span class="pill">'.esc_html(($p['schedule']['start_date']??'').' → '.($p['schedule']['end_date']??'')).'</span><span class="pill">'.esc_html(($p['schedule']['duration_nights']??'—').' gece · '.($p['schedule']['duration_days']??'—').' gün').'</span></div><p>'.esc_html(implode(' · ',$hotels)).'</p>'.($from!==null?'<p class="price">'.esc_html(number_format($from,0,',','.')).' '.esc_html($p['pricing']['currency']??'USD').'</p>':'').'<p><span class="code">'.esc_html(STPPI_ROUTE_BASE.$c['slug'].'/').'</span></p><a class="btn" href="'.esc_url(STPPI_Renderer::url($c)).'" onclick="return false">Programı incele →</a></div></article>';
        }
        echo '</div><section class="section"><div class="notice"><strong>Migration contract:</strong> Bu blok onaylandığında /umre-1/ içindeki eski manuel Program kartlarının yerine tek seferlik shortcode/bridge yerleştirilecek. Günlük veri bundan sonra Program Intelligence’tan gelecektir.</div></section>';
        self::preview_shell_end();
    }
    public static function hotel_preview(){
        self::guard('hotel_preview','GET');
        $items=STPPI_Integrations::preview_hotel_relations();
        self::preview_shell_start('Hotel → Program Relation Preview','Program içindeki Stable Hotel ID referanslarından türetilen ters ilişki. Hotel kaydına Program listesi yazılmaz.');
        if(!$items){ echo '<div class="notice">Önizlenebilir Hotel → Program ilişkisi bulunamadı.</div>'; self::preview_shell_end(); }
        echo '<div class="list">';
        foreach($items as $hotel_id=>$row){ echo '<section class="row hotel"><div class="eyebrow">'.esc_html($hotel_id).'</div><h2>'.esc_html($row['hotel_name']).'</h2><p class="muted">Derived relation · source of truth = Program.stays[].hotel_id</p><div class="grid">'; foreach($row['programs'] as $x){$p=$x['program'];$c=$x['config'];echo '<article class="card"><div class="body"><div class="eyebrow">PROGRAM '.esc_html($p['program_code']??'').'</div><h3>'.esc_html($p['title']??'').'</h3><p>'.esc_html(($p['schedule']['start_date']??'').' → '.($p['schedule']['end_date']??'')).'</p><span class="code">'.esc_html(STPPI_ROUTE_BASE.$c['slug'].'/').'</span></div></article>'; } echo '</div></section>'; }
        echo '</div><section class="section"><div class="notice"><strong>Public-link gate:</strong> Bu ilişkiler şimdi sadece admin önizlemesidir. Hotel sayfasına public Program linki eklenmez.</div></section>';
        self::preview_shell_end();
    }
    public static function archive_preview(){
        self::guard('archive_preview','GET');
        $items=STPPI_Integrations::preview_programs(true);
        self::preview_shell_start('Archive / Expiry Policy Preview','Programlar silinmez. Temporal state ve editorial lifecycle ayrı tutulur.');
        echo '<div class="list">';
        foreach($items as $x){$p=$x['program'];$id=$x['id'];$tem=STPPI_Repository::temporal($p);$wf=$p['workflow']??array();$candidate=in_array($tem,array('completed'),true)||in_array(($wf['schedule']??''),array('cancelled'),true);echo '<div class="row"><strong>'.esc_html($id).' · '.esc_html($p['program_code']??'').' · '.esc_html($p['title']??'').'</strong><p>Temporal: <span class="code">'.esc_html($tem).'</span> · Editorial: <span class="code">'.esc_html($wf['editorial']??'').'</span> · Schedule: <span class="code">'.esc_html($wf['schedule']??'').'</span></p><p class="'.($candidate?'blocked':'ok').'">'.($candidate?'Archive Candidate olabilir; insan onayı gerekir.':'Aktif/gelecek kayıt: archive işlemi yok.').'</p></div>';}
        echo '</div><section class="section"><div class="notice"><strong>Policy:</strong> Sheet’ten kaybolma = delete/archive değildir. Completed/Cancelled → Archive Candidates → insan onayı → archived. Stable STP ID korunur.</div></section>';
        self::preview_shell_end();
    }

    private static function action_form($action,$id,$label,$class='button'){echo '<form class="stppi-inline" method="post" action="'.esc_url(admin_url('admin-post.php')).'"><input type="hidden" name="action" value="stppi_'.esc_attr($action).'"><input type="hidden" name="program_id" value="'.esc_attr($id).'">';wp_nonce_field('stppi_'.$action);echo '<button class="'.esc_attr($class).'">'.esc_html($label).'</button></form>';}
    public static function page(){
        if(!current_user_can('manage_options'))return;$all=STPPI_Repository::all_rows();$dep=is_wp_error($all)?$all:null;$registry=STPPI_Repository::registry();$master=stppi_public_master_enabled();$code222=!$dep?STPPI_Repository::find_by_code('222'):array();
        $current_repair_ids=array();
        if(!$dep){foreach($registry as $rid=>$rcfg){if(!is_array($rcfg)||($rcfg['mode']??'')!=='prepared'||empty($all['rows'][$rid]))continue;$rp=$all['rows'][$rid]['program']??array();$rwf=$rp['workflow']??array();$rt=STPPI_Repository::temporal($rp);if(($rwf['editorial']??'')==='approved'&&($rwf['schedule']??'')==='scheduled'&&in_array($rt,array('upcoming','in_progress'),true))$current_repair_ids[]=$rid;}}
        echo '<div class="wrap stppi-wrap"><h1>Program Yayın & Entegrasyon</h1><p class="stppi-lead">Program Intelligence → final Program URL → /umre-1/ → Hotel ilişkisini tek akışta birleştiren güvenli geçiş katmanı.</p>';
        if(isset($_GET['stppi_message']))echo '<div class="notice notice-info"><p>'.esc_html(wp_unslash($_GET['stppi_message'])).'</p></div>';
        if($dep){echo '<div class="notice notice-error"><p>'.esc_html($dep->get_error_message()).'</p></div></div>';return;}
        echo '<div class="stppi-banner '.($master?'is-open':'is-closed').'"><strong>FINAL PROGRAM ROUTES: '.($master?'AÇIK / NOINDEX':'KAPALI').'</strong><span>v'.esc_html(STPPI_VERSION).' indexation and sitemap remain LOCKED. Hotel→Program uses an exact Stable-ID allowlist, defaults OFF, and never writes to Hotel records. Fixtures remain permanently blocked.</span></div>';
        $identity_repair=get_option('stpi_identity_repair_latest',array());
        $identity_refresh=get_option('stppi_identity_repair_refresh_last',array());
        if(is_array($identity_repair)&&!empty($identity_repair['repair_id'])&&(!is_array($identity_refresh)||($identity_refresh['repair_id']??'')!==($identity_repair['repair_id']??''))){
            $refresh_ids=array_values(array_filter((array)($identity_repair['public_refresh_ids']??array())));
            if($refresh_ids){
                echo '<div class="notice notice-warning"><p><strong>IDENTITY REPAIR REGISTRY REFRESH REQUIRED:</strong> Program Intelligence repair <code>'.esc_html((string)$identity_repair['repair_id']).'</code> committed. '.esc_html((string)count($refresh_ids)).' previously accepted public/noindex Stable-ID route(s) must refresh pinned payload/hotel hashes. No new route will be opened; STP-000038 is explicitly excluded.</p><form method="post" action="'.esc_url(admin_url('admin-post.php')).'"><input type="hidden" name="action" value="stppi_identity_repair_refresh">';wp_nonce_field('stppi_identity_repair_refresh');echo '<label><input type="checkbox" name="ack" value="1" required> Exact repaired Stable-ID list için atomic registry hash refresh yap.</label> <button class="button button-primary">IDENTITY REPAIR ROUTE HASH REFRESH</button></form></div>';
            }
        }
        if((!$master || !stppi_hub_bridge_enabled() || $current_repair_ids) && !empty($registry)){
            $repair_note=$current_repair_ids ? (' Ayrıca '.count($current_repair_ids).' güncel/gelecek PREPARED route yeniden PUBLIC/NOINDEX yapılmalı: '.implode(', ',$current_repair_ids).'.') : '';
            echo '<div class="notice notice-error"><p><strong>CANLI BASELINE / CURRENT ROUTE REPAIR:</strong> Registry tam preflight edilir; yalnız APPROVED + SCHEDULED + UPCOMING/IN_PROGRESS route’lar PUBLIC/NOINDEX olur, completed kayıtlar PREPARED kalır. Tek gerçek hata varsa hiçbir şey yazılmaz.'.esc_html($repair_note).'</p><form method="post" action="'.esc_url(admin_url('admin-post.php')).'"><input type="hidden" name="action" value="stppi_recover_live_baseline">';wp_nonce_field('stppi_recover_live_baseline');echo '<label><input type="checkbox" name="ack" value="1"> Mevcut current/upcoming registry route’larını doğrulayıp PUBLIC/NOINDEX + Hub baseline’ını düzeltmeyi onaylıyorum.</label> <button class="button button-primary">CURRENT ROUTE BASELINE ONAR</button></form></div>';
        }
        echo '<div class="stppi-flow"><div><b>1</b><strong>Sheet / JSON</strong><span>Yeni ve güncel veri</span></div><div><b>2</b><strong>Candidate Import</strong><span>CREATE / UPDATE / UNCHANGED</span></div><div><b>3</b><strong>Review / Approve</strong><span>Program Intelligence</span></div><div><b>4</b><strong>Final Route</strong><span>'.esc_html(STPPI_ROUTE_BASE).'slug/</span></div><div><b>5</b><strong>Hub + Hotel</strong><span>Hub controlled cutover</span></div></div>';
        echo '<div class="stppi-cards"><article><h2>Program Intelligence</h2><strong>CANONICAL OPERATIONS</strong><p>Yeni iş kayıtları Candidate Import / Programs / Archive üzerinden yönetilir.</p><p><a class="button button-primary" href="'.esc_url(admin_url('admin.php?page=stpi-import')).'">Candidate Import</a> <a class="button" href="'.esc_url(admin_url('admin.php?page=stpi-programs')).'">Programs</a></p></article><article><h2>Program Foundation</h2><strong>'.(defined('STPF_VERSION')?'LEGACY DETECTED v'.esc_html(STPF_VERSION):'NOT DETECTED').'</strong><p>Eski H6 Data Bridge/Relations katmanı. Otomatik silme/devre dışı bırakma yapılmaz; retirement audit bekler.</p></article><article><h2>Public Renderer</h2><strong>v0.2.4 VISUAL BASELINE</strong><p>Görsel şablon bu eklentide dondurulmuş biçimde yeniden kullanılır; eski Pilot ayrı rollback kanıtı olarak kalabilir.</p></article></div>';
        if(count($code222)===1){$r=$code222[0];$p=$r['program'];echo '<div class="stppi-highlight"><strong>Program 222 bulundu:</strong> <code>'.esc_html($r['program_id']).'</code> · '.esc_html($p['title']??'').' · '.esc_html(($p['schedule']['start_date']??'').' → '.($p['schedule']['end_date']??'')).' · <a href="'.esc_url(STPPI_Repository::review_url($r['post_id'])).'">Review ekranı</a></div>';}elseif(count($code222)>1){echo '<div class="notice notice-error"><p>Program code 222 birden fazla kayıtta bulundu. STOP.</p></div>';}else{echo '<div class="notice notice-warning"><p>Program code 222 bu runtime envanterinde bulunamadı; kaynak eşleştirmesi kontrol edilmeli.</p></div>';}
        echo '<h2>Program Envanteri</h2><p>Kaynak veriye yazmaz. Test 36/37 kalıcı olarak yayın dışıdır. Final adres APPROVED + CURRENT/UPCOMING kayıt için hazırlanır.</p><div class="stppi-table-wrap"><table class="widefat striped"><thead><tr><th>ID / Kod</th><th>Başlık</th><th>Tarih</th><th>Lifecycle</th><th>Hotel IDs</th><th>Final Route</th><th>İşlem</th></tr></thead><tbody>';
        foreach($all['rows'] as $id=>$r){$p=$r['program'];$wf=$p['workflow']??array();$tem=STPPI_Repository::temporal($p);$cfg=$registry[$id]??null;$blocked=in_array($id,array('STP-000036','STP-000037'),true);echo '<tr'.(($p['program_code']??'')==='222'?' class="stppi-222"':'').'><td><code>'.esc_html($id).'</code><br><b>'.esc_html($p['program_code']??'').'</b></td><td>'.esc_html($p['title']??'').'</td><td>'.esc_html(($p['schedule']['start_date']??'—').' → '.($p['schedule']['end_date']??'—')).'</td><td>'.esc_html(($wf['editorial']??'').' / '.($wf['schedule']??'').' / '.($wf['availability']??'')).'<br><code>'.esc_html($tem).'</code></td><td>'.esc_html(implode(', ',STPPI_Repository::hotel_ids($p)) ?: '—').'</td><td>'.($cfg?'<code>'.esc_html(STPPI_ROUTE_BASE.$cfg['slug'].'/').'</code><br><b>'.esc_html($cfg['mode']).'</b>':'—').'</td><td><a class="button" href="'.esc_url(STPPI_Repository::review_url($r['post_id'])).'">Review</a> ';
            if(!$blocked && ($wf['editorial']??'')==='approved' && ($wf['schedule']??'')==='scheduled' && in_array($tem,array('upcoming','in_progress'),true)){if(!$cfg)self::action_form('prepare',$id,'Final adres hazırla');else{echo '<a class="button" target="_blank" rel="noopener" href="'.esc_url(wp_nonce_url(add_query_arg(array('action'=>'stppi_preview','program_id'=>$id),admin_url('admin-post.php')),'stppi_preview')).'">Önizle</a> '; if(($cfg['mode']??'')==='prepared'){echo '<form class="stppi-inline" method="post" action="'.esc_url(admin_url('admin-post.php')).'"><input type="hidden" name="action" value="stppi_open"><input type="hidden" name="program_id" value="'.esc_attr($id).'"><input type="hidden" name="review_token" value="'.esc_attr($cfg['review_token']??'').'">';wp_nonce_field('stppi_open');echo '<label class="stppi-ack"><input type="checkbox" name="ack" value="1" required> noindex public testi</label><button class="button">Aç</button></form>';}}}elseif($blocked){echo '<span class="stppi-blocked">TEST / BLOCKED</span>';}else{echo '<span class="stppi-muted">Review/Approve gerekli</span>';} echo '</td></tr>';}
        echo '</tbody></table></div>';
        STPPI_Batch::render_panel($all,$registry);
        $hub_on=stppi_hub_bridge_enabled();
        echo '<h2>Migration / Cutover</h2><div class="notice notice-warning inline"><p><strong>WPBakery cutover contract:</strong> RAW1 ve RAW2 korunur. Program 219 ile başlayan tüm eski manuel Umre kartlarını taşıyan tek RAW3 daha sonra tek shortcode elementiyle değiştirilir. RAW3 sonrasındaki üç SEO RAW bloğu ve sayfa Custom CSS bu ilk cutover sırasında korunur. Otomatik sayfa düzenleme yapılmaz.</p></div><div class="stppi-cards"><article><h3>/umre-1/ Hub</h3><p>Program kartları artık Raw HTML yerine tek shortcode ile Program Intelligence verisinden üretilebilir. Köprü varsayılan KAPALI ve indexation hâlâ kilitli.</p><p><a class="button" target="_blank" rel="noopener" href="'.esc_url(wp_nonce_url(admin_url('admin-post.php?action=stppi_hub_preview'),'stppi_hub_preview')).'">Hub önizle</a></p><p><code>[stppi_umre_programs]</code></p><p><strong>CANLI HUB: '.($hub_on?'AÇIK':'KAPALI').'</strong></p>';
        if($hub_on){echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'"><input type="hidden" name="action" value="stppi_hub_disable">';wp_nonce_field('stppi_hub_disable');echo '<button class="button">Hub köprüsünü kapat</button></form>';}else{echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'"><input type="hidden" name="action" value="stppi_hub_enable">';wp_nonce_field('stppi_hub_enable');echo '<label class="stppi-ack"><input type="checkbox" name="ack" value="1" required> /umre-1/ içindeki eski manuel Program kartlarının tek shortcode ile değiştirileceğini onaylıyorum.</label><br><button class="button button-primary">Hub köprüsünü hazırla / aç</button></form>';}
        $hotel_rel=STPPI_Integrations::preview_hotel_relations();$hotel_allowlist=stppi_hotel_allowlist_ids();$hotel_live=stppi_hotel_links_enabled();
        echo '</article><article><h3>Hotel → Program</h3><p>Reverse relation yalnızca Program <code>stays[].hotel_id</code> üzerinden türetilir. Hotel kaydına Program listesi yazılmaz.</p><p><a class="button" target="_blank" rel="noopener" href="'.esc_url(wp_nonce_url(admin_url('admin-post.php?action=stppi_hotel_preview'),'stppi_hotel_preview')).'">İlişkileri önizle</a></p><p><strong>PUBLIC ROLLOUT: '.($hotel_live?'AÇIK':'KAPALI').'</strong><br><code>STABLE-ID ALLOWLIST · '.esc_html((string)count($hotel_allowlist)).' HOTEL</code></p>';
        if($hotel_rel){echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'"><input type="hidden" name="action" value="stppi_hotel_allowlist_save">';wp_nonce_field('stppi_hotel_allowlist_save');echo '<fieldset class="stppi-hotel-allowlist"><legend><strong>Controlled Hotel allowlist</strong></legend><p class="description">Yalnızca seçili Stable Hotel ID’ler rollout açıldığında public relation gösterebilir. Kaydetmek rollout’u KAPALI tutar.</p>';foreach($hotel_rel as $hid=>$hr){echo '<label><input type="checkbox" name="hotel_ids[]" value="'.esc_attr($hid).'"'.checked(in_array($hid,$hotel_allowlist,true),true,false).'> <code>'.esc_html($hid).'</code> · '.esc_html($hr['hotel_name']).' · '.esc_html((string)count($hr['programs'])).' program</label>';}echo '<p><button class="button">Allowlist’i kaydet / KAPALI tut</button></p></fieldset></form>';}
        if($hotel_live){echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'"><input type="hidden" name="action" value="stppi_hotel_rollout_disable">';wp_nonce_field('stppi_hotel_rollout_disable');echo '<button class="button button-secondary">Hotel→Program ACİL KAPAT</button></form>';}elseif($hotel_allowlist){echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'"><input type="hidden" name="action" value="stppi_hotel_rollout_enable">';wp_nonce_field('stppi_hotel_rollout_enable');echo '<label class="stppi-ack"><input type="checkbox" name="ack" value="1" required> Yalnızca allowlist’teki exact Stable Hotel sayfalarında derived Program kartlarını aç.</label><br><button class="button button-primary">Allowlist rollout aç</button></form>';}
        echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'" class="stppi-hotel-clear"><input type="hidden" name="action" value="stppi_hotel_allowlist_clear">';wp_nonce_field('stppi_hotel_allowlist_clear');echo '<button class="button-link-delete" type="submit">Hotel rollout’u kapat + allowlist’i temizle</button></form>';
        $skin_on=stppi_umre_skin_enabled();
        $skin_preview=add_query_arg(array('stppi_skin_preview'=>'premium_soft','stppi_skin_nonce'=>wp_create_nonce('stppi_umre_skin_preview')),home_url('/umre-1/'));
        echo '</article><article><h3>Archive</h3><p>Silme yok. Completed/Cancelled → Archive Candidates → insan onayı. Stable STP ID korunur.</p><p><a class="button" target="_blank" rel="noopener" href="'.esc_url(wp_nonce_url(admin_url('admin-post.php?action=stppi_archive_preview'),'stppi_archive_preview')).'">Archive politikasını önizle</a> <a class="button" href="'.esc_url(admin_url('admin.php?page=stpi-archive')).'">Archive Candidates</a></p></article>';
        echo '<article><h3>Umre Visual Skin</h3><p><strong>SOFT NAVY / PEARL BACKGROUND: '.($skin_on?'AÇIK':'KAPALI').'</strong></p><p>Yalnızca <code>/umre-1/</code> sayfa arka planını Hotel Discovery tasarım dilinin daha hafif pearl/lacivert yorumuna taşır. Program kartları, fiyat renkleri, kırmızı/yeşil CTA&rsquo;lar, veri, H1/metin, linkler, canonical, robots, schema, sitemap ve final route mantığına dokunmaz.</p><p><a class="button" target="_blank" rel="noopener" href="'.esc_url($skin_preview).'">Soft Navy / Pearl önizle ↗</a></p>';
        if($skin_on){echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'"><input type="hidden" name="action" value="stppi_umre_skin_disable">';wp_nonce_field('stppi_umre_skin_disable');echo '<button class="button">Legacy görünüme dön</button></form>';}else{echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'"><input type="hidden" name="action" value="stppi_umre_skin_enable">';wp_nonce_field('stppi_umre_skin_enable');echo '<label class="stppi-ack"><input type="checkbox" name="ack" value="1" required> Performance-Light Pearl background yalnızca /umre-1/ için public açılsın.</label><br><button class="button button-primary">Soft Navy / Pearl public aç</button></form>';}
        echo '</article></div>';
        echo '<p><a class="button" href="'.esc_url(wp_nonce_url(admin_url('admin-post.php?action=stppi_evidence'),'stppi_evidence')).'">Read-only evidence indir</a></p>';
        echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'"><input type="hidden" name="action" value="stppi_close">';wp_nonce_field('stppi_close');submit_button('Tüm final erişimi hemen kapat','primary');echo '</form></div>';
    }
}
