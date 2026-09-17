<?php
if (!defined('ABSPATH')) { exit; }

function stti_v119_itinerary_has_content($items) {
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

function stti_v119_date_long($date) {
    if (function_exists('stti_customer_date_long')) return stti_customer_date_long($date);
    if (function_exists('stti_display_date')) return stti_display_date($date);
    return (string)$date;
}

function stti_v119_price_model($pricing) {
    if (function_exists('stti_customer_price')) return stti_customer_price($pricing);
    $pricing = is_array($pricing) ? $pricing : array();
    $type = (string)($pricing['type'] ?? 'on_request');
    if ($type === 'on_request') return array('main'=>'Talep üzerine','prefix'=>'','suffix'=>'');
    $amount = $pricing['amount'] ?? null;
    if ($amount === null || $amount === '') return array('main'=>'Fiyat bilgisi bekleniyor','prefix'=>'','suffix'=>'');
    $currency = strtoupper(trim((string)($pricing['currency'] ?? '')));
    $symbols = array('EUR'=>'€','USD'=>'$','TRY'=>'₺','GBP'=>'£');
    $symbol = $symbols[$currency] ?? $currency;
    $num = rtrim(rtrim(number_format((float)$amount, 2, ',', '.'), '0'), ',');
    return array('main'=>$num . ' ' . $symbol,'prefix'=>$type === 'from' ? 'Başlangıç fiyatı' : '','suffix'=>'');
}

function stti_v119_whatsapp_url($title) {
    if (function_exists('stti_customer_whatsapp_url')) return stti_customer_whatsapp_url($title);
    $message = 'Merhaba, Server Turizm "' . trim((string)$title) . '" hakkında bilgi almak istiyorum.';
    return 'https://wa.me/905302015284?text=' . rawurlencode($message);
}

function stti_v119_media_url($payload) {
    $payload = is_array($payload) ? $payload : array();
    $media = is_array($payload['media'] ?? null) ? $payload['media'] : array();
    foreach (array('hero_image_url','cover_image_url','image_url') as $key) {
        $url = esc_url_raw((string)($media[$key] ?? ''));
        if ($url !== '') return $url;
    }
    foreach ((array)($media['items'] ?? array()) as $item) {
        if (!is_array($item)) continue;
        $attachment_id = (int)($item['attachment_id'] ?? 0);
        if ($attachment_id > 0 && function_exists('wp_get_attachment_image_url')) {
            $url = wp_get_attachment_image_url($attachment_id, 'full');
            if (is_string($url) && $url !== '') return $url;
        }
        $url = esc_url_raw((string)($item['url'] ?? ''));
        if ($url !== '') return $url;
    }
    return '';
}

function stti_v119_route_summary($payload, $model) {
    $route = is_array($payload['route'] ?? null) ? $payload['route'] : array();
    $summary = trim((string)($route['summary'] ?? ''));
    if ($summary !== '') return $summary;
    $labels = array();
    foreach ((array)($model['route_stops'] ?? array()) as $stop) {
        $label = stti_v080_stop_label($stop);
        if ($label !== '') $labels[] = $label;
    }
    return implode(' → ', $labels);
}

function stti_v119_render_list_items($items) {
    foreach ((array)$items as $item) {
        $label = '';
        if (is_array($item)) $label = trim((string)($item['label'] ?? ($item['name'] ?? ($item['text'] ?? ''))));
        else $label = trim((string)$item);
        if ($label === '') continue;
        echo '<li><span aria-hidden="true">✓</span><b>' . esc_html($label) . '</b></li>';
    }
}

function stti_v119_render_value($value) {
    if (is_array($value)) {
        $parts = array();
        foreach ($value as $item) {
            $text = is_array($item) ? trim((string)($item['label'] ?? ($item['name'] ?? ($item['text'] ?? '')))) : trim((string)$item);
            if ($text !== '') $parts[] = $text;
        }
        return implode(' · ', $parts);
    }
    return trim((string)$value);
}

function stti_v119_render_detail_template($surface) {
    $p = is_array($surface['payload'] ?? null) ? $surface['payload'] : array();
    $m = is_array($surface['model'] ?? null) ? $surface['model'] : array();
    $identity = is_array($p['identity'] ?? null) ? $p['identity'] : array();
    $dest = is_array($p['destinations'] ?? null) ? $p['destinations'] : array();
    $date = is_array($p['date'] ?? null) ? $p['date'] : array();
    $pricing = is_array($p['pricing'] ?? null) ? $p['pricing'] : array();
    $itinerary = is_array($p['itinerary'] ?? null) ? array_values($p['itinerary']) : array();
    $services = is_array($p['services'] ?? null) ? $p['services'] : array();
    $requirements = is_array($p['requirements'] ?? null) ? $p['requirements'] : array();
    $stable_id = (string)($surface['stable_id'] ?? '');
    $title = stti_v080_scalar($identity['public_title'] ?? '') ?: $stable_id;
    $country = trim((string)($dest['primary_country'] ?? ''));
    if ($country === '') {
        $countries = array_values(array_filter(array_map('strval', (array)($dest['countries'] ?? array()))));
        $country = implode(' · ', $countries);
    }
    $route_summary = stti_v119_route_summary($p, $m);
    $price = stti_v119_price_model($pricing);
    $basis = strtolower(trim((string)($pricing['basis'] ?? 'unknown')));
    $start_label = stti_v119_date_long($date['start_date'] ?? '');
    $end_label = stti_v119_date_long($date['end_date'] ?? '');
    $days = isset($date['duration_days']) ? (int)$date['duration_days'] : null;
    $nights = isset($date['duration_nights']) ? (int)$date['duration_nights'] : null;
    $hero_image = stti_v119_media_url($p);
    $whatsapp = stti_v119_whatsapp_url($title);
    $has_itinerary_detail = stti_v119_itinerary_has_content($itinerary);
    $hotels = (array)($m['hotels'] ?? array());
    $transport = (array)($m['transport'] ?? array());
    $included = is_array($services['included'] ?? null) ? $services['included'] : array();
    $excluded = is_array($services['excluded'] ?? null) ? $services['excluded'] : array();
    $requirement_items = is_array($requirements['items'] ?? null) ? $requirements['items'] : array();
    $visa_status = strtolower(trim((string)($requirements['visa_status'] ?? 'unknown')));
    $visa_notes = trim((string)($requirements['visa_notes'] ?? ''));
    $visa_has_fact = $visa_notes !== '' || !empty($requirement_items) || !in_array($visa_status, array('', 'unknown'), true);

    add_filter('pre_get_document_title', static function() use ($title) { return $title . ' · Server Turizm'; }, 999);
    get_header(); ?>
    <main class="stti-cx stti-v119-premium-detail">
      <section class="stti-cx-hero<?php echo $hero_image !== '' ? ' has-image' : ''; ?>"<?php if ($hero_image !== ''): ?> style="--stti-hero:url('<?php echo esc_url($hero_image); ?>')"<?php endif; ?>>
        <div class="stti-cx-hero-overlay"></div>
        <div class="stti-cx-wrap stti-cx-hero-inner">
          <div class="stti-cx-hero-copy">
            <span class="stti-cx-eyebrow">SERVER TURİZM · KÜLTÜR TURLARI</span>
            <h1><?php echo esc_html($title); ?></h1>
            <?php if ($route_summary !== ''): ?><p class="stti-cx-route-summary"><?php echo esc_html($route_summary); ?></p><?php endif; ?>
            <div class="stti-cx-facts">
              <?php if ($start_label !== '—' && $start_label !== '' && $end_label !== '—' && $end_label !== ''): ?><span><?php echo esc_html($start_label); ?> — <?php echo esc_html($end_label); ?></span><?php endif; ?>
              <?php if ($days !== null && $nights !== null): ?><span><?php echo esc_html((string)$days); ?> Gün · <?php echo esc_html((string)$nights); ?> Gece</span><?php endif; ?>
              <?php if ($country !== ''): ?><span><?php echo esc_html($country); ?></span><?php endif; ?>
            </div>
            <div class="stti-cx-price"><small><?php echo esc_html($price['prefix'] ?: 'Tur fiyatı'); ?></small><strong><?php echo esc_html($price['main']); ?></strong><?php if ($basis === 'unknown'): ?><em>Fiyat baz bilgisi kaynakta belirtilmemiştir.</em><?php endif; ?></div>
            <div class="stti-cx-actions">
              <a class="stti-cx-btn stti-cx-btn-gold" href="<?php echo esc_url($whatsapp); ?>" target="_blank" rel="noopener noreferrer">WhatsApp'tan Bilgi Al <span>→</span></a>
              <a class="stti-cx-btn stti-cx-btn-line" href="#stti-program">Programı İncele <span>↓</span></a>
            </div>
          </div>
        </div>
      </section>

      <section class="stti-cx-summary">
        <div class="stti-cx-wrap stti-cx-summary-grid">
          <article class="stti-cx-summary-card destination"><span>DESTİNASYON</span><b><?php echo esc_html($country ?: '—'); ?></b><i>Keşif rotası</i></article>
          <article class="stti-cx-summary-card dates"><span>TARİH</span><b><?php echo esc_html($start_label ?: '—'); ?><small><?php echo esc_html($end_label ?: '—'); ?></small></b><i>Gidiş · dönüş</i></article>
          <article class="stti-cx-summary-card duration"><span>SÜRE</span><b><?php echo $days === null ? '—' : esc_html((string)$days) . ' Gün'; ?><small><?php echo $nights === null ? '—' : esc_html((string)$nights) . ' Gece'; ?></small></b><i>Toplam program</i></article>
          <article class="stti-cx-summary-card price"><span>FİYAT</span><b><?php echo esc_html($price['main']); ?></b><i><?php echo $basis === 'unknown' ? 'Baz bilgisi kaynakta yok' : 'Doğrulanmış fiyat'; ?></i></article>
        </div>
      </section>

      <section class="stti-cx-section" id="stti-program">
        <div class="stti-cx-wrap">
          <header class="stti-cx-section-head"><span>01 · ROTA</span><h2><?php echo esc_html((string)count((array)($m['route_stops'] ?? array()))); ?> durak, tek yolculuk.</h2><p>İnsan tarafından doğrulanmış canonical koordinatlar kullanılır; eksik konumlar tahmin edilmez.</p></header>
          <?php if (!empty($m['route_stops'])): ?>
            <div class="stti-cx-map-shell">
              <div class="stti-cx-map-topline"><div><span>CANONICAL ROTA HARİTASI</span><b><?php echo esc_html((string)count((array)($m['map']['stops'] ?? array()))); ?> doğrulanmış nokta</b></div><div class="stti-cx-map-legend"><i></i> Server Turizm rotası</div></div>
              <div id="stti-cx-route-map" class="stti-cx-route-map" role="region" aria-label="<?php echo esc_attr($title); ?> rota haritası"></div>
              <div id="stti-cx-map-status" class="stti-cx-map-status">Rota konumları yükleniyor…</div>
            </div>
            <div class="stti-cx-route-line"><?php foreach ((array)$m['route_stops'] as $i => $stop): ?><article><b><?php echo esc_html(str_pad((string)($i + 1), 2, '0', STR_PAD_LEFT)); ?></b><span><?php echo esc_html(stti_v080_stop_label($stop) ?: 'Durak'); ?></span></article><?php endforeach; ?></div>
          <?php endif; ?>
        </div>
      </section>

      <section class="stti-cx-section stti-cx-section-soft">
        <div class="stti-cx-wrap">
          <header class="stti-cx-section-head"><span>02 · TUR PROGRAMI</span><h2>Yolculuğun akışı.</h2></header>
          <?php if ($has_itinerary_detail): ?>
            <div class="stti-cx-days"><?php foreach ($itinerary as $day):
              $has = false;
              foreach (array('title','city','summary','activities','meals') as $key) {
                  $v = $day[$key] ?? null;
                  if ((is_array($v) && $v) || (!is_array($v) && stti_v080_scalar($v) !== '')) { $has = true; break; }
              }
              if (!$has) continue;
              $activities = stti_v119_render_value($day['activities'] ?? '');
              $meals = stti_v119_render_value($day['meals'] ?? ''); ?>
              <details class="stti-cx-day"><summary><b><?php echo esc_html(str_pad((string)((int)($day['day_number'] ?? 0)), 2, '0', STR_PAD_LEFT)); ?></b><span><small><?php echo esc_html(stti_v119_date_long($day['date'] ?? '')); ?></small><strong><?php echo esc_html(trim((string)($day['title'] ?? '')) ?: ('Gün ' . (int)($day['day_number'] ?? 0))); ?></strong></span><i>+</i></summary><div><?php if (trim((string)($day['city'] ?? '')) !== ''): ?><p><b>Şehir</b> <?php echo esc_html($day['city']); ?></p><?php endif; ?><?php if (trim((string)($day['summary'] ?? '')) !== ''): ?><p><?php echo esc_html($day['summary']); ?></p><?php endif; ?><?php if ($activities !== ''): ?><p><b>Aktiviteler</b> <?php echo esc_html($activities); ?></p><?php endif; ?><?php if ($meals !== ''): ?><p><b>Öğünler</b> <?php echo esc_html($meals); ?></p><?php endif; ?></div></details>
            <?php endforeach; ?></div>
          <?php else: ?>
            <div class="stti-cx-program-pending"><div><span>PROGRAM DETAYI</span><h3>Gün gün program hazırlanıyor.</h3><p><?php echo count($itinerary) > 0 ? esc_html((string)count($itinerary)) . ' günlük takvim kaydı mevcut; ancak kaynakta doğrulanmış günlük açıklamalar bulunmadığı için tahmini içerik göstermiyoruz.' : 'Kaynakta doğrulanmış günlük program bulunmadığı için boş içerik veya tahmini bilgi göstermiyoruz.'; ?></p></div><div class="stti-cx-date-stack"><b><?php echo esc_html($start_label); ?></b><i>→</i><b><?php echo esc_html($end_label); ?></b></div></div>
          <?php endif; ?>
        </div>
      </section>

      <?php if ($hotels || $transport || $included || $excluded || $visa_has_fact): ?>
      <section class="stti-cx-section stti-cx-deep">
        <div class="stti-cx-wrap">
          <header class="stti-cx-section-head light"><span>03 · SEYAHAT DETAYLARI</span><h2>Doğrulanmış detaylar.</h2></header>
          <?php if ($hotels || $transport || $visa_has_fact): ?><div class="stti-cx-detail-grid">
            <?php if ($hotels): ?><article><span>KONAKLAMA</span><h3><?php echo esc_html((string)count($hotels)); ?> otel</h3><ul><?php foreach ($hotels as $hotel): ?><li><?php echo esc_html(($hotel['name'] ?? '') !== '' ? $hotel['name'] : ($hotel['hotel_stable_id'] ?? 'Otel')); ?><?php if (($hotel['city'] ?? '') !== '') echo ' · ' . esc_html($hotel['city']); ?><?php if (($hotel['stars'] ?? '') !== '') echo ' · ' . esc_html($hotel['stars']) . '★'; ?></li><?php endforeach; ?></ul></article><?php endif; ?>
            <?php if ($transport): ?><article><span>ULAŞIM</span><h3><?php echo esc_html((string)count($transport)); ?> segment</h3><ul><?php foreach ($transport as $seg): ?><li><?php echo esc_html(strtoupper(stti_v080_scalar($seg['type'] ?? 'segment'))); ?> · <?php echo esc_html(($seg['from_label'] ?? '') . ' → ' . ($seg['to_label'] ?? '')); ?><?php if (stti_v080_scalar($seg['provider'] ?? '') !== '') echo ' · ' . esc_html($seg['provider']); ?></li><?php endforeach; ?></ul></article><?php endif; ?>
            <?php if ($visa_has_fact): ?><article><span>VİZE</span><h3><?php echo esc_html(strtoupper($visa_status ?: 'BİLGİ')); ?></h3><?php if ($visa_notes !== ''): ?><p><?php echo esc_html($visa_notes); ?></p><?php elseif ($requirement_items): ?><ul><?php stti_v119_render_list_items($requirement_items); ?></ul><?php endif; ?></article><?php endif; ?>
          </div><?php endif; ?>
          <?php if ($included || $excluded): ?><div class="stti-cx-service-grid"><?php if ($included): ?><article><span>FİYATA DAHİL</span><ul><?php stti_v119_render_list_items($included); ?></ul></article><?php endif; ?><?php if ($excluded): ?><article><span>FİYATA DAHİL DEĞİL</span><ul><?php stti_v119_render_list_items($excluded); ?></ul></article><?php endif; ?></div><?php endif; ?>
        </div>
      </section>
      <?php endif; ?>

      <section class="stti-cx-cta">
        <div class="stti-cx-wrap stti-cx-cta-inner"><div><span>BİR SONRAKİ YOLCULUĞUNUZ</span><h2>Güvenle başlayın.</h2><p>Tur detayları ve güncel uygunluk için seyahat danışmanımızla görüşün.</p></div><a class="stti-cx-btn stti-cx-btn-gold" href="<?php echo esc_url($whatsapp); ?>" target="_blank" rel="noopener noreferrer">WhatsApp'tan Bilgi Al <span>→</span></a></div>
      </section>

      <div class="stti-cx-adminnote"><div class="stti-cx-wrap"><b>QA route:</b> Bu tur detayı kontrollü olarak açık, ancak Indexation / Sitemap / Schema / Canonical kilitleri kapalıdır.</div></div>
    </main>
    <?php get_footer();
}
