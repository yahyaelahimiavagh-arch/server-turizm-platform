<?php
if (!defined('ABSPATH')) { exit; }

function stti_v124_render_detail_template($surface) {
    $d = stti_v124_customer_model($surface);
    $p = $d['payload'];
    $m = $d['model'];
    $date = $d['date'];
    $pricing = $d['pricing'];
    $requirements = $d['requirements'];
    $title = $d['title'] !== '' ? $d['title'] : $d['stable_id'];
    $route_summary = $d['route_summary'];
    $start_label = stti_v119_date_long($date['start_date'] ?? '');
    $end_label = stti_v119_date_long($date['end_date'] ?? '');
    $days = isset($date['duration_days']) && $date['duration_days'] !== null ? (int)$date['duration_days'] : null;
    $nights = isset($date['duration_nights']) && $date['duration_nights'] !== null ? (int)$date['duration_nights'] : null;
    $price = stti_v119_price_model($pricing);
    $basis = strtolower(stti_v124_text($pricing['basis'] ?? 'unknown'));
    $hero_image = stti_v119_media_url($p);
    $whatsapp = stti_v119_whatsapp_url($title);
    $has_itinerary_detail = stti_v119_itinerary_has_content($d['itinerary']);
    $visa_status = strtolower(stti_v124_text($requirements['visa_status'] ?? 'unknown'));
    $visa_notes = stti_v124_text($requirements['visa_notes'] ?? '');
    $requirement_items = is_array($requirements['items'] ?? null) ? $requirements['items'] : array();
    $visa_has_fact = $visa_notes !== '' || !empty($requirement_items) || !in_array($visa_status, array('', 'unknown'), true);
    $overview_has_fact = $d['short_description'] !== '' || $d['tour_code'] !== '' || $d['short_title'] !== '' || $d['primary_city'] !== '' || $d['countries'] || $d['cities'] || $d['departure_city'] !== '' || $d['return_city'] !== '';
    $travel_has_fact = $d['hotels'] || $d['transport'];
    $service_has_fact = $d['included'] || $d['excluded'] || $visa_has_fact;

    add_filter('pre_get_document_title', static function() use ($title) { return $title . ' · Server Turizm'; }, 999);
    get_header(); ?>
    <main class="stti-cx stti-v119-premium-detail stti-v124-complete-detail">
      <section class="stti-cx-hero<?php echo $hero_image !== '' ? ' has-image' : ''; ?>"<?php if ($hero_image !== ''): ?> style="--stti-hero:url('<?php echo esc_url($hero_image); ?>')"<?php endif; ?>>
        <div class="stti-cx-hero-overlay"></div>
        <div class="stti-cx-wrap stti-cx-hero-inner">
          <div class="stti-cx-hero-copy">
            <span class="stti-cx-eyebrow">SERVER TURİZM · KÜLTÜR TURLARI<?php if ($d['tour_code'] !== '') echo ' · ' . esc_html($d['tour_code']); ?></span>
            <h1><?php echo esc_html($title); ?></h1>
            <?php if ($d['short_title'] !== '' && $d['short_title'] !== $title): ?><p class="stti-v124-short-title"><?php echo esc_html($d['short_title']); ?></p><?php endif; ?>
            <?php if ($route_summary !== ''): ?><p class="stti-cx-route-summary"><?php echo esc_html($route_summary); ?></p><?php endif; ?>
            <?php if ($d['short_description'] !== ''): ?><p class="stti-v124-hero-description"><?php echo esc_html($d['short_description']); ?></p><?php endif; ?>
            <div class="stti-cx-facts">
              <?php if ($start_label !== '—' && $start_label !== '' && $end_label !== '—' && $end_label !== ''): ?><span><?php echo esc_html($start_label); ?> — <?php echo esc_html($end_label); ?></span><?php endif; ?>
              <?php if ($days !== null && $nights !== null): ?><span><?php echo esc_html((string)$days); ?> Gün · <?php echo esc_html((string)$nights); ?> Gece</span><?php endif; ?>
              <?php if ($d['country'] !== ''): ?><span><?php echo esc_html($d['country']); ?></span><?php endif; ?>
              <?php if ($d['availability'] !== ''): ?><span><?php echo esc_html($d['availability']); ?></span><?php endif; ?>
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
          <article class="stti-cx-summary-card destination"><span>DESTİNASYON</span><b><?php echo esc_html($d['country'] ?: '—'); ?></b><i><?php echo esc_html($d['primary_city'] !== '' ? $d['primary_city'] : 'Keşif rotası'); ?></i></article>
          <article class="stti-cx-summary-card dates"><span>TARİH</span><b><?php echo esc_html($start_label ?: '—'); ?><small><?php echo esc_html($end_label ?: '—'); ?></small></b><i>Gidiş · dönüş</i></article>
          <article class="stti-cx-summary-card duration"><span>SÜRE</span><b><?php echo $days === null ? '—' : esc_html((string)$days) . ' Gün'; ?><small><?php echo $nights === null ? '—' : esc_html((string)$nights) . ' Gece'; ?></small></b><i>Toplam program</i></article>
          <article class="stti-cx-summary-card price"><span>FİYAT</span><b><?php echo esc_html($price['main']); ?></b><i><?php echo $basis === 'unknown' ? 'Baz bilgisi kaynakta yok' : 'Güncel fiyat'; ?></i></article>
        </div>
      </section>

      <?php if ($overview_has_fact): ?>
      <section class="stti-cx-section stti-v124-overview">
        <div class="stti-cx-wrap">
          <header class="stti-cx-section-head"><span>00 · TUR HAKKINDA</span><h2><?php echo esc_html($d['short_title'] !== '' ? $d['short_title'] : 'Turun özeti.'); ?></h2><?php if ($d['short_description'] !== ''): ?><p><?php echo esc_html($d['short_description']); ?></p><?php endif; ?></header>
          <div class="stti-v124-fact-grid">
            <?php if ($d['tour_code'] !== ''): ?><article><span>TUR KODU</span><b><?php echo esc_html($d['tour_code']); ?></b></article><?php endif; ?>
            <?php if ($d['primary_city'] !== ''): ?><article><span>ANA ŞEHİR</span><b><?php echo esc_html($d['primary_city']); ?></b></article><?php endif; ?>
            <?php if ($d['countries']): ?><article><span>ÜLKELER</span><b><?php echo esc_html(implode(' · ', $d['countries'])); ?></b></article><?php endif; ?>
            <?php if ($d['cities']): ?><article class="is-wide"><span>ŞEHİRLER</span><b><?php echo esc_html(implode(' · ', $d['cities'])); ?></b></article><?php endif; ?>
            <?php if ($d['departure_city'] !== ''): ?><article><span>GİDİŞ ŞEHRİ</span><b><?php echo esc_html($d['departure_city']); ?></b></article><?php endif; ?>
            <?php if ($d['return_city'] !== ''): ?><article><span>DÖNÜŞ ŞEHRİ</span><b><?php echo esc_html($d['return_city']); ?></b></article><?php endif; ?>
          </div>
        </div>
      </section>
      <?php endif; ?>

      <section class="stti-cx-section" id="stti-program">
        <div class="stti-cx-wrap">
          <header class="stti-cx-section-head"><span>01 · ROTA</span><h2><?php echo esc_html((string)count($d['route_stops'])); ?> durak, tek yolculuk.</h2><?php if ($route_summary !== ''): ?><p><?php echo esc_html($route_summary); ?></p><?php endif; ?></header>
          <?php if ($d['route_stops']): ?>
            <div class="stti-cx-map-shell">
              <div class="stti-cx-map-topline"><div><span>CANONICAL ROTA HARİTASI</span><b><?php echo esc_html((string)count((array)($m['map']['stops'] ?? array()))); ?> doğrulanmış nokta</b></div><div class="stti-cx-map-legend"><i></i> Server Turizm rotası</div></div>
              <div id="stti-cx-route-map" class="stti-cx-route-map" role="region" aria-label="<?php echo esc_attr($title); ?> rota haritası"></div>
              <div id="stti-cx-map-status" class="stti-cx-map-status">Rota konumları yükleniyor…</div>
            </div>
            <div class="stti-cx-route-line"><?php foreach ($d['route_stops'] as $i => $stop): ?><article><b><?php echo esc_html(str_pad((string)($i + 1), 2, '0', STR_PAD_LEFT)); ?></b><span><?php echo esc_html(stti_v080_stop_label($stop) ?: 'Durak'); ?></span></article><?php endforeach; ?></div>
            <div class="stti-v124-route-details">
              <?php foreach ($d['route_stops'] as $i => $stop): $stop_name = stti_v080_stop_label($stop); $stop_country = stti_v124_text($stop['country'] ?? ''); $stop_note = stti_v124_text($stop['note'] ?? ''); if ($stop_name === '' && $stop_country === '' && $stop_note === '') continue; ?>
                <article><span><?php echo esc_html(str_pad((string)($i + 1), 2, '0', STR_PAD_LEFT)); ?></span><div><b><?php echo esc_html($stop_name ?: 'Durak'); ?></b><?php if ($stop_country !== ''): ?><small><?php echo esc_html($stop_country); ?></small><?php endif; ?><?php if ($stop_note !== ''): ?><p><?php echo esc_html($stop_note); ?></p><?php endif; ?></div></article>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </section>

      <section class="stti-cx-section stti-cx-section-soft">
        <div class="stti-cx-wrap">
          <header class="stti-cx-section-head"><span>02 · TUR PROGRAMI</span><h2>Yolculuğun akışı.</h2></header>
          <?php if ($has_itinerary_detail): ?>
            <div class="stti-cx-days"><?php foreach ($d['itinerary'] as $day):
              if (!is_array($day)) continue;
              $has = false;
              foreach (array('title','city','summary','activities','meals') as $key) { $v = $day[$key] ?? null; if ((is_array($v) && $v) || (!is_array($v) && stti_v080_scalar($v) !== '')) { $has = true; break; } }
              if (!$has) continue;
              $activities = stti_v119_render_value($day['activities'] ?? '');
              $meals = stti_v119_render_value($day['meals'] ?? ''); ?>
              <details class="stti-cx-day"><summary><b><?php echo esc_html(str_pad((string)((int)($day['day_number'] ?? 0)), 2, '0', STR_PAD_LEFT)); ?></b><span><small><?php echo esc_html(stti_v119_date_long($day['date'] ?? '')); ?></small><strong><?php echo esc_html(stti_v124_text($day['title'] ?? '') ?: ('Gün ' . (int)($day['day_number'] ?? 0))); ?></strong></span><i>+</i></summary><div><?php if (stti_v124_text($day['city'] ?? '') !== ''): ?><p><b>Şehir</b> <?php echo esc_html($day['city']); ?></p><?php endif; ?><?php if (stti_v124_text($day['summary'] ?? '') !== ''): ?><p><?php echo esc_html($day['summary']); ?></p><?php endif; ?><?php if ($activities !== ''): ?><p><b>Aktiviteler</b> <?php echo esc_html($activities); ?></p><?php endif; ?><?php if ($meals !== ''): ?><p><b>Öğünler</b> <?php echo esc_html($meals); ?></p><?php endif; ?></div></details>
            <?php endforeach; ?></div>
          <?php else: ?>
            <div class="stti-cx-program-pending"><div><span>PROGRAM DETAYI</span><h3>Gün gün program hazırlanıyor.</h3><p><?php echo count($d['itinerary']) > 0 ? esc_html((string)count($d['itinerary'])) . ' günlük takvim kaydı mevcut; açıklaması girilmiş günler burada otomatik gösterilir.' : 'Günlük program bilgisi henüz girilmemiştir.'; ?></p></div><div class="stti-cx-date-stack"><b><?php echo esc_html($start_label); ?></b><i>→</i><b><?php echo esc_html($end_label); ?></b></div></div>
          <?php endif; ?>
        </div>
      </section>

      <?php if ($travel_has_fact): ?>
      <section class="stti-cx-section stti-v124-travel">
        <div class="stti-cx-wrap">
          <header class="stti-cx-section-head"><span>03 · KONAKLAMA & ULAŞIM</span><h2>Seyahat düzeni.</h2></header>
          <?php if ($d['hotels']): ?><div class="stti-v124-subhead"><span>KONAKLAMA</span><h3>Oteller</h3></div><div class="stti-v124-hotel-grid">
            <?php foreach ($d['hotels'] as $hotel): if (!is_array($hotel)) continue; $hotel_name = stti_v124_text($hotel['name'] ?? ($hotel['unresolved_name'] ?? ($hotel['hotel_stable_id'] ?? 'Otel'))); ?>
              <article class="stti-v124-hotel-card"><?php if (stti_v124_text($hotel['image'] ?? '') !== ''): ?><div class="visual" style="background-image:url('<?php echo esc_url($hotel['image']); ?>')"></div><?php endif; ?><div class="copy"><span><?php echo esc_html(stti_v124_text($hotel['selection_status'] ?? '') === 'alternative' ? 'ALTERNATİF OTEL' : 'OTEL'); ?></span><h3><?php echo esc_html($hotel_name); ?></h3><p><?php $hotel_meta=array(); if(stti_v124_text($hotel['city']??'')!=='')$hotel_meta[]=stti_v124_text($hotel['city']); if(stti_v124_text($hotel['stars']??'')!=='')$hotel_meta[]=stti_v124_text($hotel['stars']).'★'; echo esc_html(implode(' · ',$hotel_meta)); ?></p><div class="meta"><?php if (stti_v124_text($hotel['check_in'] ?? '') !== ''): ?><small>Giriş <b><?php echo esc_html(stti_v119_date_long($hotel['check_in'])); ?></b></small><?php endif; ?><?php if (stti_v124_text($hotel['check_out'] ?? '') !== ''): ?><small>Çıkış <b><?php echo esc_html(stti_v119_date_long($hotel['check_out'])); ?></b></small><?php endif; ?><?php if (stti_v124_text($hotel['room_type'] ?? '') !== ''): ?><small>Oda <b><?php echo esc_html($hotel['room_type']); ?></b></small><?php endif; ?><?php if (stti_v124_text($hotel['board'] ?? '') !== ''): ?><small>Pansiyon <b><?php echo esc_html($hotel['board']); ?></b></small><?php endif; ?></div><?php if (stti_v124_text($hotel['note'] ?? '') !== ''): ?><p class="note"><?php echo esc_html($hotel['note']); ?></p><?php endif; ?></div></article>
            <?php endforeach; ?>
          </div><?php endif; ?>

          <?php if ($d['transport']): ?><div class="stti-v124-subhead"><span>ULAŞIM</span><h3>Ulaşım segmentleri</h3></div><div class="stti-v124-transport-list">
            <?php foreach ($d['transport'] as $seg): if (!is_array($seg)) continue; $from=stti_v124_text($seg['from_label']??($seg['from']??'')); $to=stti_v124_text($seg['to_label']??($seg['to']??'')); ?>
              <article><div class="kind"><?php echo esc_html(strtoupper(stti_v124_text($seg['type'] ?? 'ULAŞIM'))); ?></div><div class="route"><b><?php echo esc_html($from ?: '—'); ?></b><span>→</span><b><?php echo esc_html($to ?: '—'); ?></b></div><div class="details"><?php if (stti_v124_text($seg['provider'] ?? '') !== ''): ?><span>Firma <b><?php echo esc_html($seg['provider']); ?></b></span><?php endif; ?><?php if (stti_v124_text($seg['code'] ?? ($seg['flight_no'] ?? '')) !== ''): ?><span>Kod <b><?php echo esc_html(stti_v124_text($seg['code'] ?? $seg['flight_no'])); ?></b></span><?php endif; ?><?php if (stti_v124_text($seg['date'] ?? ($seg['departure_date'] ?? '')) !== ''): ?><span>Tarih <b><?php echo esc_html(stti_v119_date_long($seg['date'] ?? $seg['departure_date'])); ?></b></span><?php endif; ?><?php if (stti_v124_text($seg['departure_time'] ?? '') !== ''): ?><span>Kalkış <b><?php echo esc_html($seg['departure_time']); ?></b></span><?php endif; ?><?php if (stti_v124_text($seg['arrival_time'] ?? '') !== ''): ?><span>Varış <b><?php echo esc_html($seg['arrival_time']); ?></b></span><?php endif; ?></div><?php if (stti_v124_text($seg['note'] ?? '') !== ''): ?><p><?php echo esc_html($seg['note']); ?></p><?php endif; ?></article>
            <?php endforeach; ?>
          </div><?php endif; ?>
        </div>
      </section>
      <?php endif; ?>

      <?php if ($d['price_items'] || ($pricing['amount'] ?? null) !== null): ?>
      <section class="stti-cx-section stti-cx-section-soft stti-v124-pricing">
        <div class="stti-cx-wrap">
          <header class="stti-cx-section-head"><span>04 · FİYATLAR</span><h2>Fiyat seçenekleri.</h2><p>Tur için girilmiş tüm fiyat satırları aşağıda gösterilir.</p></header>
          <div class="stti-v124-price-grid">
            <?php if ($d['price_items']): foreach ($d['price_items'] as $item): if (!is_array($item)) continue; $item_label=stti_v124_text($item['label']??''); $occupancy=stti_v124_occupancy_label($item['occupancy']??''); ?>
              <article><span><?php echo esc_html($item_label !== '' ? $item_label : ($occupancy !== '' ? $occupancy : 'FİYAT')); ?></span><strong><?php echo esc_html(stti_v124_price_label($item)); ?></strong><?php if ($occupancy !== '' && $occupancy !== $item_label): ?><b><?php echo esc_html($occupancy); ?></b><?php endif; ?><?php if (stti_v124_text($item['note'] ?? '') !== ''): ?><p><?php echo esc_html($item['note']); ?></p><?php endif; ?></article>
            <?php endforeach; else: ?><article><span>ANA FİYAT</span><strong><?php echo esc_html($price['main']); ?></strong><?php if ($basis !== 'unknown' && $basis !== ''): ?><b><?php echo esc_html($basis); ?></b><?php endif; ?></article><?php endif; ?>
          </div>
        </div>
      </section>
      <?php endif; ?>

      <?php if ($service_has_fact): ?>
      <section class="stti-cx-section stti-cx-deep stti-v124-services">
        <div class="stti-cx-wrap">
          <header class="stti-cx-section-head light"><span>05 · HİZMETLER & VİZE</span><h2>Pakete dair tüm bilgiler.</h2></header>
          <?php if ($d['included'] || $d['excluded']): ?><div class="stti-cx-service-grid"><?php if ($d['included']): ?><article><span>FİYATA DAHİL</span><ul><?php stti_v119_render_list_items($d['included']); ?></ul></article><?php endif; ?><?php if ($d['excluded']): ?><article><span>FİYATA DAHİL DEĞİL</span><ul><?php stti_v119_render_list_items($d['excluded']); ?></ul></article><?php endif; ?></div><?php endif; ?>
          <?php if ($visa_has_fact): ?><article class="stti-v124-visa"><span>VİZE</span><h3><?php echo esc_html(strtoupper($visa_status ?: 'BİLGİ')); ?></h3><?php if ($visa_notes !== ''): ?><p><?php echo esc_html($visa_notes); ?></p><?php endif; ?><?php if ($requirement_items): ?><ul><?php stti_v119_render_list_items($requirement_items); ?></ul><?php endif; ?></article><?php endif; ?>
        </div>
      </section>
      <?php endif; ?>

      <?php if ($d['gallery']): ?>
      <section class="stti-cx-section stti-v124-gallery-section">
        <div class="stti-cx-wrap">
          <header class="stti-cx-section-head"><span>06 · GALERİ</span><h2>Turdan görseller.</h2></header>
          <div class="stti-v124-gallery"><?php foreach ($d['gallery'] as $item): ?><figure><img src="<?php echo esc_url($item['url']); ?>" alt="<?php echo esc_attr($item['title'] !== '' ? $item['title'] : $title); ?>" loading="lazy"><?php if ($item['title'] !== '' || $item['caption'] !== ''): ?><figcaption><?php if ($item['title'] !== ''): ?><b><?php echo esc_html($item['title']); ?></b><?php endif; ?><?php if ($item['caption'] !== ''): ?><span><?php echo esc_html($item['caption']); ?></span><?php endif; ?></figcaption><?php endif; ?></figure><?php endforeach; ?></div>
        </div>
      </section>
      <?php endif; ?>

      <section class="stti-cx-cta">
        <div class="stti-cx-wrap stti-cx-cta-inner"><div><span>BİR SONRAKİ YOLCULUĞUNUZ</span><h2>Güvenle başlayın.</h2><p>Tur detayları ve güncel uygunluk için seyahat danışmanımızla görüşün.</p></div><a class="stti-cx-btn stti-cx-btn-gold" href="<?php echo esc_url($whatsapp); ?>" target="_blank" rel="noopener noreferrer">WhatsApp'tan Bilgi Al <span>→</span></a></div>
      </section>

      <div class="stti-cx-adminnote"><div class="stti-cx-wrap"><b>QA route:</b> Müşteri içeriği canonical Tur kaydından canlı okunur; Indexation / Sitemap / Schema / Canonical kilitleri kapalıdır.</div></div>
    </main>
    <?php get_footer();
}
