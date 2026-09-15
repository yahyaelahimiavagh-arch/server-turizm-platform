<?php
/**
 * STTI v1.1.0 — Dynamic Culture Tours Hub.
 *
 * Replaces only the content of the existing /kultur-turlari/ WordPress page
 * when the dedicated Hub Master option is enabled. Route ownership, page SEO,
 * canonical/indexation settings and individual Tour public gates remain outside
 * this module.
 */
if (!defined('ABSPATH')) { exit; }

function stti_v110_hub_config() {
    return array(
        'contract'=>'STTI-TOUR-HUB-1.1.0',
        'path'=>'/kultur-turlari/',
        'master_option'=>'stti_v110_hub_master',
    );
}

function stti_v110_hub_enabled() {
    $cfg=stti_v110_hub_config();
    return get_option($cfg['master_option'],'0')==='1';
}

function stti_v110_normalize_path($path) {
    $path=rawurldecode((string)$path);
    $path='/'.trim($path,'/').'/';
    return $path==='//'?'/':$path;
}

function stti_v110_request_path() {
    if (function_exists('stti_v100_request_path')) return stti_v100_request_path();
    $raw=isset($_SERVER['REQUEST_URI'])?wp_unslash($_SERVER['REQUEST_URI']):'/';
    $path=wp_parse_url($raw,PHP_URL_PATH);
    return stti_v110_normalize_path(is_string($path)?$path:'/');
}

function stti_v110_is_hub_request($path=null) {
    $candidate=$path===null?stti_v110_request_path():stti_v110_normalize_path($path);
    return hash_equals(stti_v110_hub_config()['path'],$candidate);
}

function stti_v110_iso_today($today=null) {
    if (is_string($today) && preg_match('/^\d{4}-\d{2}-\d{2}$/',$today)) return $today;
    return wp_date('Y-m-d',current_time('timestamp'));
}

function stti_v110_payload($row) {
    $payload=json_decode((string)($row['payload']??''),true);
    return is_array($payload)?$payload:array();
}

function stti_v110_record_is_eligible($row,$payload,$today=null) {
    if (!is_array($row) || !is_array($payload)) return false;
    if ((string)($row['editorial']??'')!=='approved') return false;
    $lifecycle=is_array($payload['lifecycle']??null)?$payload['lifecycle']:array();
    $schedule=(string)($lifecycle['schedule']??($row['schedule_status']??''));
    if (in_array($schedule,array('cancelled','canceled'),true)) return false;
    if ((string)($row['editorial']??'')==='archived') return false;

    $date=is_array($payload['date']??null)?$payload['date']:array();
    $end=(string)($date['end_date']??'');
    $start=(string)($date['start_date']??'');
    $today=stti_v110_iso_today($today);
    if ($end!=='' && preg_match('/^\d{4}-\d{2}-\d{2}$/',$end) && $end<$today) return false;
    if ($end==='' && $start!=='' && preg_match('/^\d{4}-\d{2}-\d{2}$/',$start) && $start<$today) return false;
    if ((string)($row['temporal']??'')==='past') return false;
    return true;
}

function stti_v110_route_label($payload) {
    $route=is_array($payload['route']??null)?$payload['route']:array();
    $summary=trim((string)($route['summary']??''));
    if ($summary!=='') return $summary;
    $labels=array();
    foreach ((array)($route['stops']??array()) as $stop) {
        if (!is_array($stop)) continue;
        $label=trim((string)($stop['city']??($stop['label']??'')));
        if ($label!=='') $labels[]=$label;
    }
    return implode(' → ',array_values(array_unique($labels)));
}

function stti_v110_destination_label($payload) {
    $d=is_array($payload['destinations']??null)?$payload['destinations']:array();
    $primary=trim((string)($d['primary_country']??''));
    if ($primary!=='') return $primary;
    $countries=array_values(array_filter(array_map('strval',(array)($d['countries']??array()))));
    return implode(' · ',$countries);
}

function stti_v110_date_label($payload) {
    $date=is_array($payload['date']??null)?$payload['date']:array();
    $start=trim((string)($date['start_date']??''));
    $end=trim((string)($date['end_date']??''));
    if ($start!=='' && $end!=='') {
        $a=function_exists('stti_display_date')?stti_display_date($start):$start;
        $b=function_exists('stti_display_date')?stti_display_date($end):$end;
        return $a.' — '.$b;
    }
    return 'Tarih yakında';
}

function stti_v110_duration_label($payload) {
    $date=is_array($payload['date']??null)?$payload['date']:array();
    $days=isset($date['duration_days'])?(int)$date['duration_days']:0;
    $nights=isset($date['duration_nights'])?(int)$date['duration_nights']:0;
    if ($days>0 && $nights>=0) return $nights.' Gece / '.$days.' Gün';
    return '';
}

function stti_v110_price_label($payload) {
    $p=is_array($payload['pricing']??null)?$payload['pricing']:array();
    $type=(string)($p['type']??'');
    $currency=trim((string)($p['currency']??''));
    if ($type==='on_request') return 'Fiyat için bilgi alın';
    if (isset($p['amount']) && is_numeric($p['amount']) && $currency!=='') {
        $amount=rtrim(rtrim(number_format((float)$p['amount'],2,'.',''),'0'),'.');
        return ($type==='from'?'Başlangıç ' : '').$amount.' '.$currency;
    }
    return '';
}

function stti_v110_availability_label($payload,$row) {
    $lifecycle=is_array($payload['lifecycle']??null)?$payload['lifecycle']:array();
    $value=(string)($lifecycle['availability']??($row['availability']??'open'));
    $labels=array(
        'open'=>'Rezervasyona açık',
        'on_request'=>'Talep üzerine',
        'sold_out'=>'Kontenjan dolu',
        'waitlist'=>'Bekleme listesi',
    );
    return $labels[$value]??'Bilgi alın';
}

function stti_v110_media_url($payload) {
    $media=is_array($payload['media']??null)?$payload['media']:array();
    $candidates=array(
        $media['hero_image_url']??'',
        $media['cover_image_url']??'',
        $media['image_url']??'',
    );
    foreach ($candidates as $candidate) {
        $candidate=esc_url_raw((string)$candidate);
        if ($candidate!=='') return $candidate;
    }
    return '';
}

function stti_v110_detail_url($stable_id) {
    $stable_id=(string)$stable_id;
    if (function_exists('stti_v100_public_config') && function_exists('stti_v100_surface_state')) {
        $cfg=stti_v100_public_config();
        if (($cfg['stable_id']??'')===$stable_id) {
            $surface=stti_v100_surface_state();
            if (!empty($surface['route'])) return stti_v100_public_url();
        }
    }
    return '';
}

function stti_v110_card_model($row,$payload) {
    $identity=is_array($payload['identity']??null)?$payload['identity']:array();
    $title=trim((string)($identity['public_title']??($row['public_title']??'Tur')));
    $date=is_array($payload['date']??null)?$payload['date']:array();
    $start=(string)($date['start_date']??'');
    $detail=stti_v110_detail_url((string)($row['stable_id']??''));
    return array(
        'stable_id'=>(string)($row['stable_id']??''),
        'title'=>$title!==''?$title:'Tur',
        'destination'=>stti_v110_destination_label($payload),
        'route'=>stti_v110_route_label($payload),
        'date'=>stti_v110_date_label($payload),
        'duration'=>stti_v110_duration_label($payload),
        'price'=>stti_v110_price_label($payload),
        'availability'=>stti_v110_availability_label($payload,$row),
        'availability_key'=>(string)((is_array($payload['lifecycle']??null)?$payload['lifecycle']:array())['availability']??($row['availability']??'open')),
        'start_date'=>$start,
        'dated'=>preg_match('/^\d{4}-\d{2}-\d{2}$/',$start)===1,
        'updated_at'=>(string)($row['updated_at']??''),
        'image'=>stti_v110_media_url($payload),
        'detail_url'=>$detail,
        'cta_url'=>$detail!==''?$detail:home_url('/iletisim/'),
        'cta_label'=>$detail!==''?'Turu İncele':'Bilgi Al',
    );
}

function stti_v110_hub_records($today=null) {
    $out=array();
    foreach (stti_get_candidates() as $row) {
        $payload=stti_v110_payload($row);
        if (!stti_v110_record_is_eligible($row,$payload,$today)) continue;
        $out[]=stti_v110_card_model($row,$payload);
    }
    usort($out,static function($a,$b){
        if ($a['dated']!==$b['dated']) return $a['dated']?-1:1;
        if ($a['dated'] && $a['start_date']!==$b['start_date']) return strcmp($a['start_date'],$b['start_date']);
        return strcmp((string)$b['updated_at'],(string)$a['updated_at']);
    });
    return $out;
}

function stti_v110_render_hub($records) {
    $records=is_array($records)?$records:array();
    ob_start(); ?>
    <div class="stti-hub-v110" data-contract="STTI-TOUR-HUB-1.1.0">
      <section class="stti-hub-v110__hero">
        <div class="stti-hub-v110__hero-inner">
          <span class="stti-hub-v110__eyebrow">SERVER TURİZM · KÜLTÜR ROTALARI</span>
          <h1>Kültür Turları</h1>
          <p>Yaklaşan kültür turlarını tarih, rota ve güncel rezervasyon durumuyla tek yerde keşfedin.</p>
          <div class="stti-hub-v110__count"><b><?php echo esc_html((string)count($records)); ?></b><span>aktif / yaklaşan tur</span></div>
        </div>
      </section>
      <section class="stti-hub-v110__body">
        <?php if (!$records): ?>
          <div class="stti-hub-v110__empty"><h2>Yeni turlar hazırlanıyor</h2><p>Yeni programlarımız onaylandığında burada otomatik olarak yayınlanacaktır.</p><a href="<?php echo esc_url(home_url('/iletisim/')); ?>">Tur danışmanına ulaşın</a></div>
        <?php else: ?>
          <div class="stti-hub-v110__grid">
          <?php foreach ($records as $card): ?>
            <article class="stti-hub-v110__card<?php echo $card['availability_key']==='sold_out'?' is-sold-out':''; ?>">
              <div class="stti-hub-v110__visual"<?php if($card['image']!==''): ?> style="background-image:url('<?php echo esc_url($card['image']); ?>')"<?php endif; ?>>
                <?php if($card['image']===''): ?><div class="stti-hub-v110__monogram"><?php echo esc_html($card['destination']!==''?$card['destination']:'Server Turizm'); ?></div><?php endif; ?>
                <span class="stti-hub-v110__availability"><?php echo esc_html($card['availability']); ?></span>
              </div>
              <div class="stti-hub-v110__content">
                <?php if($card['destination']!==''): ?><span class="stti-hub-v110__destination"><?php echo esc_html($card['destination']); ?></span><?php endif; ?>
                <h2><?php echo esc_html($card['title']); ?></h2>
                <div class="stti-hub-v110__facts">
                  <div><span>Tarih</span><b><?php echo esc_html($card['date']); ?></b></div>
                  <?php if($card['duration']!==''): ?><div><span>Süre</span><b><?php echo esc_html($card['duration']); ?></b></div><?php endif; ?>
                  <?php if($card['price']!==''): ?><div><span>Fiyat</span><b><?php echo esc_html($card['price']); ?></b></div><?php endif; ?>
                </div>
                <?php if($card['route']!==''): ?><p class="stti-hub-v110__route"><?php echo esc_html($card['route']); ?></p><?php endif; ?>
                <a class="stti-hub-v110__cta" href="<?php echo esc_url($card['cta_url']); ?>"><?php echo esc_html($card['cta_label']); ?><span aria-hidden="true">→</span></a>
              </div>
            </article>
          <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </section>
    </div>
    <?php return (string)ob_get_clean();
}

function stti_v110_replace_hub_content($content) {
    if (is_admin() || !stti_v110_hub_enabled() || !stti_v110_is_hub_request()) return $content;
    if (function_exists('is_main_query') && !is_main_query()) return $content;
    if (function_exists('in_the_loop') && !in_the_loop()) return $content;
    return stti_v110_render_hub(stti_v110_hub_records());
}

function stti_v110_enqueue_hub_assets() {
    if (!stti_v110_hub_enabled() || !stti_v110_is_hub_request()) return;
    wp_enqueue_style('stti-tour-hub-v110',STTI_URL.'assets/tour-hub-v110.css',array(),STTI_RELEASE_VERSION);
}

function stti_v110_register_hub_admin() {
    add_submenu_page('stti-tour-intelligence','Tour Hub v1.1','Tour Hub','manage_options','stti-tour-hub-v110','stti_v110_render_hub_admin');
}

function stti_v110_render_hub_admin() {
    if (!current_user_can('manage_options')) return;
    $records=stti_v110_hub_records();
    $enabled=stti_v110_hub_enabled();
    ?>
    <div class="wrap"><h1>Tour Hub v1.1</h1>
      <p><code>/kultur-turlari/</code> mevcut WordPress sayfasının yalnızca içerik alanını dinamik STTI kartlarıyla değiştirir. Sayfa URL/SEO kabuğu ve tekil Tour public gate'leri değişmez.</p>
      <p><strong>Uygun tur:</strong> editorial <code>approved</code>, iptal/arşiv değil ve geçmiş tarihi bitmemiş kayıt. Yakın tarihli turlar önce, tarihsiz onaylı turlar sonra sıralanır.</p>
      <p><strong>Şu an uygun:</strong> <?php echo esc_html((string)count($records)); ?></p>
      <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="stti_v110_save_hub" />
        <?php wp_nonce_field('stti_v110_tour_hub'); ?>
        <label><input type="checkbox" name="hub_master" value="1" <?php checked($enabled); ?> /> Dynamic Tour Hub aktif</label>
        <?php submit_button('Kaydet'); ?>
      </form>
      <p><a href="<?php echo esc_url(home_url('/kultur-turlari/')); ?>" target="_blank" rel="noopener">Kültür Turları sayfasını aç</a></p>
    </div><?php
}

function stti_v110_handle_save_hub() {
    if (!current_user_can('manage_options')) wp_die('Yetki yok.');
    check_admin_referer('stti_v110_tour_hub');
    update_option(stti_v110_hub_config()['master_option'],isset($_POST['hub_master'])?'1':'0',false);
    wp_safe_redirect(add_query_arg(array('page'=>'stti-tour-hub-v110','updated'=>'1'),admin_url('admin.php')));
    exit;
}

add_filter('the_content','stti_v110_replace_hub_content',99);
add_action('wp_enqueue_scripts','stti_v110_enqueue_hub_assets',99);
add_action('admin_menu','stti_v110_register_hub_admin',100);
add_action('admin_post_stti_v110_save_hub','stti_v110_handle_save_hub');
