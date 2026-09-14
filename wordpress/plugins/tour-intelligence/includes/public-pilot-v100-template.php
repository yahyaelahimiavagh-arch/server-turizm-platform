<?php
if (!defined('ABSPATH')) { exit; }
function stti_v100_public_itinerary_has_content($items) {
    foreach ((array)$items as $day) {
        if (!is_array($day)) continue;
        foreach (array('title','city','summary','activities','meals') as $key) {
            $value = $day[$key] ?? null;
            if (is_array($value) && $value) return true;
            if (!is_array($value) && stti_v080_scalar($value) !== '') return true;
        }
    }
    return false;
}
function stti_v100_render_public_template($surface) {
    $p = is_array($surface['payload'] ?? null) ? $surface['payload'] : array();
    $m = is_array($surface['model'] ?? null) ? $surface['model'] : array();
    $identity = is_array($p['identity'] ?? null) ? $p['identity'] : array();
    $title = stti_v080_scalar($identity['public_title'] ?? '') ?: 'Tur';
    $date = is_array($p['date'] ?? null) ? $p['date'] : array();
    $pricing = is_array($p['pricing'] ?? null) ? $p['pricing'] : array();
    $itinerary = is_array($p['itinerary'] ?? null) ? array_values($p['itinerary']) : array();
    $services = is_array($p['services'] ?? null) ? $p['services'] : array();
    add_filter('pre_get_document_title', static function() use ($title) { return $title . ' · Server Turizm'; }, 999);
    get_header(); ?>
    <main class="stti-cx stti-v080 stti-v100-public">
      <section class="stti-v080-hero"><div class="stti-v080-wrap">
        <div class="stti-v080-eyebrow"><?php echo esc_html(stti_v100_public_config()['stable_id']); ?> · SERVER TURİZM</div>
        <h1><?php echo esc_html($title); ?></h1>
        <div class="stti-v080-facts">
          <?php if (!empty($date['start_date']) && !empty($date['end_date'])): ?><div><span>TARİH</span><b><?php echo esc_html(stti_display_date($date['start_date']) . ' — ' . stti_display_date($date['end_date'])); ?></b></div><?php endif; ?>
          <?php if (!empty($date['duration_days'])): ?><div><span>SÜRE</span><b><?php echo esc_html((int)$date['duration_nights'] . ' Gece / ' . (int)$date['duration_days'] . ' Gün'); ?></b></div><?php endif; ?>
          <?php if (isset($pricing['amount']) && is_numeric($pricing['amount']) && !empty($pricing['currency'])): ?><div><span>KAYNAK FİYATI</span><b><?php echo esc_html(rtrim(rtrim(number_format((float)$pricing['amount'], 2, '.', ''), '0'), '.') . ' ' . $pricing['currency']); ?></b></div><?php endif; ?>
        </div>
      </div></section>
      <section class="stti-v080-section"><div class="stti-v080-wrap"><header><span>01 · ROTA</span><h2>Tur rotası</h2></header>
        <?php if (!empty($m['route_stops'])): ?><div class="stti-cx-map-shell"><div id="stti-cx-route-map" class="stti-cx-route-map"></div><div id="stti-cx-map-status" class="stti-cx-map-status">Rota yükleniyor…</div></div><div class="stti-v080-route"><?php foreach ($m['route_stops'] as $i => $stop): ?><article><b><?php echo esc_html(str_pad((string)($i + 1), 2, '0', STR_PAD_LEFT)); ?></b><span><?php echo esc_html(stti_v080_stop_label($stop) ?: 'Durak'); ?></span></article><?php endforeach; ?></div><?php endif; ?>
      </div></section>
      <?php if (stti_v100_public_itinerary_has_content($itinerary)): ?><section class="stti-v080-section soft"><div class="stti-v080-wrap"><header><span>02 · PROGRAM</span><h2>Tur programı</h2></header><div class="stti-v080-days">
        <?php foreach ($itinerary as $day): $has=false; foreach(array('title','city','summary','activities','meals') as $key){$v=$day[$key]??null;if((is_array($v)&&$v)||(!is_array($v)&&stti_v080_scalar($v)!=='')){$has=true;break;}} if(!$has)continue; ?><article><div><span>GÜN</span><b><?php echo (int)($day['day_number'] ?? 0); ?></b></div><section><?php if(stti_v080_scalar($day['title']??'')!==''):?><h3><?php echo esc_html($day['title']);?></h3><?php endif;?><?php if(stti_v080_scalar($day['city']??'')!==''):?><p><?php echo esc_html($day['city']);?></p><?php endif;?><?php if(stti_v080_scalar($day['summary']??'')!==''):?><p><?php echo esc_html($day['summary']);?></p><?php endif;?></section></article><?php endforeach; ?>
      </div></div></section><?php endif; ?>
      <?php if (!empty($m['hotels']) || !empty($m['transport'])): ?><section class="stti-v080-section deep"><div class="stti-v080-wrap"><header><span>03 · DETAYLAR</span><h2>Konaklama ve ulaşım</h2></header><div class="stti-v080-grid"><?php if(!empty($m['hotels'])):?><div><h3>Oteller</h3><div class="stti-v080-hotels"><?php foreach($m['hotels'] as $h):?><article><div><h4><?php echo esc_html($h['name']!==''?$h['name']:$h['hotel_stable_id']);?></h4><p><?php echo esc_html($h['city']);?></p></div></article><?php endforeach;?></div></div><?php endif;?><?php if(!empty($m['transport'])):?><div><h3>Ulaşım</h3><div class="stti-v080-transport"><?php foreach($m['transport'] as $s):?><article><span><?php echo esc_html(strtoupper(stti_v080_scalar($s['type']??'segment')));?></span><b><?php echo esc_html(($s['from_label']??'').' → '.($s['to_label']??''));?></b></article><?php endforeach;?></div></div><?php endif;?></div></div></section><?php endif; ?>
      <?php $inc=is_array($services['included']??null)?$services['included']:array();$exc=is_array($services['excluded']??null)?$services['excluded']:array(); if($inc||$exc):?><section class="stti-v080-section"><div class="stti-v080-wrap"><div class="stti-v080-grid"><?php if($inc):?><article class="stti-v080-list"><h3>Fiyata dahil</h3><ul><?php stti_v080_render_list_items($inc);?></ul></article><?php endif;?><?php if($exc):?><article class="stti-v080-list"><h3>Fiyata dahil değil</h3><ul><?php stti_v080_render_list_items($exc);?></ul></article><?php endif;?></div></div></section><?php endif;?>
      <section class="stti-v080-footer"><div class="stti-v080-wrap"><b>Server Turizm</b><span><?php echo esc_html($title); ?></span></div></section>
    </main><?php get_footer();
}
