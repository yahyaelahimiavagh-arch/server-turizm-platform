<?php
if (!defined('ABSPATH') || !isset($model, $config, $seo, $private)) { exit; }

$p = $model['program'];
$e = function($v) { return esc_html(STPPI_Renderer::text($v)); };
$date = function($v) {
    $d = DateTimeImmutable::createFromFormat('!Y-m-d', (string)$v);
    return $d ? $d->format('d.m.Y') : '—';
};
$money = function($v) { return number_format((float)$v, 2, ',', '.'); };
$unit = array(
    'per_person' => 'kişi başı',
    'per_room'   => 'oda başı',
    'per_group'  => 'grup başı',
);
$availability = array(
    'open'       => 'Rezervasyona açık',
    'limited'    => 'Sınırlı kontenjan',
    'sold_out'   => 'Kontenjan dolu',
    'on_request' => 'Talep üzerine',
);
$hub = ($p['service_type'] === 'umrah') ? home_url('/umre-1/') : home_url('/kultur-turlari/');
$hero = STPPI_Renderer::media_url($p['media']['hero_image_url'] ?? '');
$segments = is_array($p['segments'] ?? null) ? $p['segments'] : array();
$stays = is_array($p['stays'] ?? null) ? $p['stays'] : array();
$prices = is_array($p['pricing']['entries'] ?? null) ? $p['pricing']['entries'] : array();
$child_rules = is_array($p['pricing']['child_rules'] ?? null) ? $p['pricing']['child_rules'] : array();
$surcharges = is_array($p['pricing']['surcharges'] ?? null) ? $p['pricing']['surcharges'] : array();
$itinerary_days = is_array($p['itinerary_days'] ?? null) ? $p['itinerary_days'] : array();

$stay_summary = array();
foreach ($stays as $stay) {
    $city = STPPI_Renderer::geography($stay['destination'] ?? '');
    $n = (int)($stay['nights'] ?? 0);
    if ($city !== '' && $n > 0) { $stay_summary[] = $n . ' gece ' . $city; }
}
$stay_summary_text = $stay_summary ? implode(' · ', $stay_summary) : ($p['schedule']['duration_nights'] . ' gece');

$journey_count = count($segments);
$middle_count = max(0, $journey_count - 2);

$shell_available = class_exists('ST_Header_Footer');
$shell_dir = '';
$shell_css_file = '';
$shell_js_file = '';
if ($shell_available) {
    try {
        $shell_ref = new ReflectionClass('ST_Header_Footer');
        $shell_file = $shell_ref->getFileName();
        if (is_string($shell_file) && $shell_file !== '') {
            $shell_dir = dirname($shell_file);
            $candidate_css = $shell_dir . '/assets/css/server-header-footer.css';
            $candidate_js = $shell_dir . '/assets/js/server-header-footer.js';
            if (is_readable($candidate_css)) { $shell_css_file = $candidate_css; }
            if (is_readable($candidate_js)) { $shell_js_file = $candidate_js; }
        }
    } catch (ReflectionException $ex) {
        $shell_available = false;
    }
}
$shell_ready = $shell_available && $shell_css_file !== '';
$csp_nonce = STPPI_Renderer::csp_nonce();
?>
<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow,noarchive,nosnippet">
<title><?php echo $e($seo['title']); ?></title>
<meta name="description" content="<?php echo esc_attr($seo['description']); ?>">
<?php if (!$private && $config['seo'] === true): ?>
<link rel="canonical" href="<?php echo esc_url($seo['url']); ?>">
<meta property="og:type" content="website">
<meta property="og:locale" content="tr_TR">
<meta property="og:site_name" content="Server Turizm">
<meta property="og:title" content="<?php echo esc_attr($seo['title']); ?>">
<meta property="og:description" content="<?php echo esc_attr($seo['description']); ?>">
<meta property="og:url" content="<?php echo esc_url($seo['url']); ?>">
<meta name="twitter:card" content="summary">
<meta name="twitter:title" content="<?php echo esc_attr($seo['title']); ?>">
<meta name="twitter:description" content="<?php echo esc_attr($seo['description']); ?>">
<script type="application/ld+json" nonce="<?php echo esc_attr($csp_nonce); ?>"><?php echo wp_json_encode($seo['graph'], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?></script>
<?php endif; ?>
<?php if ($shell_ready): ?>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Playfair+Display:wght@500;600&display=swap">
<style><?php readfile($shell_css_file); ?></style>
<?php endif; ?>
<style><?php readfile(STPPI_DIR . 'assets/program.css'); ?></style>
<style>
body.stpub-private{--st-admin-offset:34px}
body.stpub-private #main{padding-top:calc(var(--st-shell-height) + var(--st-admin-offset)) !important}
body.stpub-private .preview{position:fixed;z-index:10060;inset:0 0 auto;height:34px;display:flex;align-items:center;justify-content:center}
.stpub-shell-fallback{background:#0b1b2e;color:#fff;padding:18px 26px;border-bottom:1px solid rgba(201,165,92,.35)}
.stpub-shell-fallback a{color:#e4cb91;font-weight:800;text-decoration:none}
</style>
</head>
<body class="<?php echo $shell_ready ? 'st-shell-active st-shell-internal ' : ''; ?><?php echo $private ? 'stpub-private' : 'stpub-public'; ?>">
<?php if ($private): ?><div class="preview">ÖZEL ÖNİZLEME · Bu görünüm genel yayını açmaz</div><?php endif; ?>
<?php if ($shell_ready): ?>
    <?php ST_Header_Footer::render_header(); ?>
<?php else: ?>
    <header class="stpub-shell-fallback"><a href="<?php echo esc_url(home_url('/')); ?>">SERVER TURİZM</a></header>
<?php endif; ?>

<main id="main">
<section class="hero">
    <div class="hero-copy-area">
        <div class="hero-kicker">
            <span class="status-dot" aria-hidden="true"></span>
            <?php echo $e($availability[$p['workflow']['availability']] ?? 'Program bilgisi'); ?>
        </div>
        <p class="eyebrow"><?php echo $p['service_type'] === 'umrah' ? 'SERVER TURİZM · UMRE PROGRAMI' : 'SERVER TURİZM · KÜLTÜR TURU'; ?></p>
        <h1><?php echo $e($p['title']); ?></h1>
        <p class="hero-description"><?php echo $e($seo['description']); ?></p>
        <div class="hero-meta">
            <span>PROGRAM <?php echo $e($p['program_code']); ?></span>
            <span><?php echo $e($p['schedule']['duration_days']); ?> GÜN · <?php echo $e($p['schedule']['duration_nights']); ?> GECE</span>
        </div>
        <div class="hero-actions">
            <a class="button button-gold" href="#prices">Fiyatları inceleyin</a>
            <a class="button button-ghost" href="#hotels">Otelleri görün</a>
        </div>
    </div>
    <div class="hero-media<?php echo $hero ? '' : ' hero-media-empty'; ?>">
        <?php if ($hero): ?>
            <img class="hero-photo" src="<?php echo esc_url($hero); ?>" alt="<?php echo esc_attr(STPPI_Renderer::text($p['title'])); ?>" width="1200" height="900" fetchpriority="high" decoding="async">
        <?php else: ?>
            <div class="hero-monogram" aria-hidden="true">ST</div>
        <?php endif; ?>
        <div class="hero-media-badge"><strong><?php echo $e($p['schedule']['duration_nights']); ?></strong><span>GECE</span><strong><?php echo $e($p['schedule']['duration_days']); ?></strong><span>GÜN</span></div>
    </div>
</section>

<section class="facts" aria-label="Program özeti">
    <div class="fact"><span>GİDİŞ</span><strong><time datetime="<?php echo esc_attr($p['schedule']['start_date']); ?>"><?php echo $e($date($p['schedule']['start_date'])); ?></time></strong></div>
    <div class="fact"><span>DÖNÜŞ</span><strong><time datetime="<?php echo esc_attr($p['schedule']['end_date']); ?>"><?php echo $e($date($p['schedule']['end_date'])); ?></time></strong></div>
    <div class="fact fact-wide"><span>KONAKLAMA PLANI</span><strong><?php echo $e($stay_summary_text); ?></strong></div>
</section>

<nav class="section-nav" aria-label="Program bölümleri">
    <div class="section-nav-inner">
        <a href="#route">Rota</a>
        <a href="#hotels">Oteller</a>
        <a href="#prices">Fiyatlar</a>
        <a href="#details">Detaylar</a>
        <a href="<?php echo $shell_ready ? '#stSiteFooter' : '#contact'; ?>">İletişim</a>
    </div>
</nav>

<div class="page-shell">
<div class="program-main">

<section id="route" class="section-block">
    <div class="section-heading">
        <p class="eyebrow">01 / YOLCULUK PLANI</p>
        <h2>Yolculuğun her adımı net.</h2>
        <p>Gidişten ara geçişlere ve dönüşe kadar program akışını tek bakışta görün.</p>
    </div>

    <?php if ($journey_count > 0): ?>
    <ol class="journey" aria-label="Yolculuk zaman çizelgesi">
        <?php foreach ($segments as $i => $s):
            if ($i === 0) { $step_label = 'GİDİŞ'; }
            elseif ($i === $journey_count - 1) { $step_label = 'DÖNÜŞ'; }
            else { $step_label = ($middle_count > 1 ? $i . '. ' : '') . 'ARA GEÇİŞ'; }
            $origin = STPPI_Renderer::geography($s['origin'] ?? '');
            $destination = STPPI_Renderer::geography($s['destination'] ?? '');
            $route_bits = array();
            if ($origin !== '' && $origin !== '—') { $route_bits[] = $origin; }
            if ($destination !== '' && $destination !== '—') { $route_bits[] = $destination; }
            $route_text = implode(' → ', $route_bits);
        ?>
        <li class="journey-step">
            <span class="journey-index"><?php echo (int)($i + 1); ?></span>
            <div class="journey-body">
                <span class="journey-label"><?php echo $e($step_label); ?></span>
                <strong><?php echo $e(STPPI_Renderer::display_datetime($s['depart_at'] ?? '')); ?></strong>
                <?php if ($route_text !== ''): ?><small><?php echo $e($route_text); ?></small><?php endif; ?>
            </div>
        </li>
        <?php endforeach; ?>
    </ol>
    <?php else: ?>
    <ol class="destination-route">
        <?php foreach ($p['destinations'] as $i => $d): ?>
        <li><span><?php echo (int)($i + 1); ?></span><div><strong><?php echo $e(STPPI_Renderer::geography($d['city'])); ?></strong><small><?php echo $e(STPPI_Renderer::geography($d['country'])); ?><?php if (isset($d['nights'])): ?> · <?php echo $e($d['nights']); ?> gece<?php endif; ?></small></div></li>
        <?php endforeach; ?>
    </ol>
    <?php endif; ?>

    <?php if ($itinerary_days): ?>
    <div class="accordion-stack">
        <?php foreach ($itinerary_days as $day): ?>
        <details class="premium-details">
            <summary><span><?php echo $e($day['day']); ?>. Gün</span><?php echo $e($day['title']); ?></summary>
            <p class="prose"><?php echo $e($day['description'] ?? ''); ?></p>
        </details>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</section>

<section id="hotels" class="section-block">
    <div class="section-heading">
        <p class="eyebrow">02 / KONAKLAMA</p>
        <h2>Konaklamanız için seçili oteller.</h2>
        <p>Otel adı, yıldız bilgisi, konaklama tarihleri ve görseller güncel otel kayıtlarımızdan otomatik olarak gösterilir.</p>
    </div>

    <div class="hotel-stack">
    <?php foreach ($stays as $stay):
        $h = $model['hotels'][$stay['hotel_id']];
        $gallery = array();
        foreach ((array)($h['images'] ?? array()) as $image_url) {
            $safe = STPPI_Renderer::media_url($image_url);
            if ($safe && !in_array($safe, $gallery, true)) { $gallery[] = $safe; }
            if (count($gallery) >= 8) { break; }
        }
        $photo = $gallery[0] ?? '';
    ?>
    <article class="hotel-card">
        <div class="hotel-media">
            <?php if ($photo): ?>
                <button type="button" class="hotel-main-trigger" data-gallery-src="<?php echo esc_url($photo); ?>" data-gallery-alt="<?php echo esc_attr(STPPI_Renderer::text($h['name'])); ?>" data-gallery-hotel="<?php echo esc_attr(STPPI_Renderer::text($h['name'])); ?>" aria-label="<?php echo esc_attr(STPPI_Renderer::text($h['name']) . ' fotoğraf galerisini aç'); ?>">
                    <img class="hotel-main-photo" src="<?php echo esc_url($photo); ?>" alt="<?php echo esc_attr(STPPI_Renderer::text($h['name'])); ?>" width="900" height="650" loading="lazy" decoding="async">
                    <span class="hotel-photo-hint" aria-hidden="true">Galeriyi aç</span>
                </button>
            <?php else: ?>
                <div class="hotel-placeholder" aria-hidden="true"><?php echo $e(STPPI_Renderer::geography($h['city'])); ?></div>
            <?php endif; ?>
            <span class="hotel-city-badge"><?php echo $e(STPPI_Renderer::geography($stay['destination'])); ?> · <?php echo $e($stay['nights']); ?> GECE</span>
        </div>
        <div class="hotel-content">
            <div class="hotel-title-row">
                <div>
                    <p class="eyebrow">KONAKLAMA</p>
                    <h3><?php echo $e($h['name']); ?></h3>
                </div>
                <?php if ((int)($h['stars'] ?? 0) > 0): ?><span class="star-pill"><?php echo $e($h['stars']); ?> yıldız</span><?php endif; ?>
            </div>
            <dl class="hotel-facts">
                <div><dt>Giriş</dt><dd><?php echo $e($date($stay['check_in'] ?? '')); ?></dd></div>
                <div><dt>Çıkış</dt><dd><?php echo $e($date($stay['check_out'] ?? '')); ?></dd></div>
                <?php if (!empty($stay['meal_plan'])): ?><div><dt>Yemek planı</dt><dd><?php echo $e($stay['meal_plan']); ?></dd></div><?php endif; ?>
            </dl>
            <?php if (count($gallery) > 1): ?>
            <div class="gallery-strip" aria-label="<?php echo esc_attr(STPPI_Renderer::text($h['name'])); ?> fotoğraf galerisi">
                <?php foreach ($gallery as $gi => $img): ?>
                <button type="button" class="gallery-thumb" data-gallery-src="<?php echo esc_url($img); ?>" data-gallery-alt="<?php echo esc_attr(STPPI_Renderer::text($h['name']) . ' fotoğraf ' . ($gi + 1)); ?>" data-gallery-hotel="<?php echo esc_attr(STPPI_Renderer::text($h['name'])); ?>" aria-label="<?php echo esc_attr(STPPI_Renderer::text($h['name']) . ' fotoğraf ' . ($gi + 1) . ' büyüt'); ?>">
                    <img src="<?php echo esc_url($img); ?>" alt="" width="160" height="110" loading="lazy" decoding="async">
                </button>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <a class="hotel-link" href="<?php echo esc_url($h['public_url']); ?>">Otel detaylarını inceleyin <span aria-hidden="true">↗</span></a>
        </div>
    </article>
    <?php endforeach; ?>
    </div>
</section>

<section id="prices" class="section-block price-section">
    <div class="section-heading">
        <p class="eyebrow">03 / FİYAT SEÇENEKLERİ</p>
        <h2>Oda tipinizi seçin.</h2>
        <p>Güncel yetişkin fiyatlarını oda tipine göre karşılaştırın. Kesin kontenjan ve fiyat rezervasyon öncesinde teyit edilir.</p>
    </div>

    <?php if ($prices): ?>
    <div class="price-grid">
        <?php foreach ($prices as $price): ?>
        <article class="price-card">
            <span class="price-label"><?php echo $e($price['label'] ?? $price['occupancy']); ?></span>
            <p class="amount"><?php echo $e($money($price['amount'])); ?><span><?php echo $e($p['pricing']['currency']); ?></span></p>
            <p class="price-unit"><?php echo $e($unit[$price['unit']] ?? $price['unit']); ?><?php if ($p['pricing']['price_type'] === 'from'): ?> · başlayan fiyat<?php endif; ?></p>
        </article>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
        <div class="info-panel"><strong>Güncel fiyat için tur danışmanımızla görüşün.</strong></div>
    <?php endif; ?>

    <?php if ($child_rules): ?>
    <div class="subsection-card">
        <div class="subsection-title"><span class="mini-icon" aria-hidden="true">◌</span><div><p class="eyebrow">ÇOCUK ÜCRETLERİ</p><h3>Yaşa ve yatak durumuna göre.</h3></div></div>
        <div class="child-grid">
            <?php foreach ($child_rules as $r): ?>
            <div class="child-rule">
                <strong><?php echo $e($r['min_age']); ?>–<?php echo $e($r['max_age']); ?> yaş</strong>
                <span><?php echo !empty($r['bed_included']) ? 'Yatak dahil' : 'Yatak dahil değil'; ?></span>
                <b><?php if (($r['pricing_method'] ?? '') === 'on_request'): ?>Fiyat için danışın<?php else: ?><?php echo $e($money($r[($r['pricing_method'] === 'fixed') ? 'amount' : 'discount_amount'])); ?> <?php echo $e($p['pricing']['currency']); ?><?php echo ($r['pricing_method'] === 'discount') ? ' indirim' : ''; ?><?php endif; ?></b>
                <?php if (!empty($r['notes'])): ?><small><?php echo $e($r['notes']); ?></small><?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($surcharges): ?>
    <details class="premium-details surcharge-details">
        <summary><span>Ek ücretler</span>Programdaki ilave fiyat kalemleri</summary>
        <ul class="clean-list">
            <?php foreach ($surcharges as $r): ?><li><span><?php echo $e($r['label']); ?></span><strong><?php echo $e($money($r['amount'])); ?> <?php echo $e($p['pricing']['currency']); ?></strong></li><?php endforeach; ?>
        </ul>
    </details>
    <?php endif; ?>

    <div class="price-footnotes">
        <?php if (!empty($p['pricing']['valid_until'])): ?><p><strong>Fiyat geçerlilik tarihi:</strong> <?php echo $e($date($p['pricing']['valid_until'])); ?></p><?php endif; ?>
        <?php if (!empty($p['pricing']['disclaimer'])): ?><p class="prose"><?php echo $e($p['pricing']['disclaimer']); ?></p><?php endif; ?>
        <p>Fiyat ve müsaitliği rezervasyon öncesinde tur danışmanımızla teyit edin.</p>
    </div>
</section>

<section id="details" class="section-block">
    <div class="section-heading">
        <p class="eyebrow">04 / PROGRAM DETAYLARI</p>
        <h2>Rezervasyondan önce bilin.</h2>
        <p>Dahil olan ve olmayan hizmetleri ayrı ayrı görün; eksik alanlar danışman teyidine bırakılır.</p>
    </div>

    <div class="detail-grid">
        <?php foreach (array('inclusions' => array('Dahil olanlar', '✓'), 'exclusions' => array('Dahil olmayanlar', '—')) as $key => $meta): ?>
        <div class="detail-card">
            <div class="detail-card-head"><span aria-hidden="true"><?php echo esc_html($meta[1]); ?></span><h3><?php echo esc_html($meta[0]); ?></h3></div>
            <?php if (!empty($p[$key])): ?>
            <ul><?php foreach ($p[$key] as $item): ?><li><?php echo $e($item); ?></li><?php endforeach; ?></ul>
            <?php else: ?><p>Detayları tur danışmanımızdan öğrenebilirsiniz.</p><?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<?php if (!$shell_ready): ?>
<section id="contact" class="contact-card">
    <div>
        <p class="eyebrow">BİRLİKTE PLANLAYALIM</p>
        <h2>Umre yolculuğunuzu güvenle planlayın.</h2>
        <p>Güncel kontenjan, oda seçimi, çocuk ücretleri ve kayıt süreci için tur danışmanımızla görüşün.</p>
    </div>
    <div class="contact-actions">
        <a class="button button-gold" href="<?php echo esc_url(home_url('/iletisim/')); ?>">Rezervasyon bilgisi alın</a>
        <a class="button button-outline" href="<?php echo esc_url($hub); ?>">Diğer programlar</a>
    </div>
</section>
<?php endif; ?>

</div>

<aside class="booking-rail" aria-label="Rezervasyon özeti">
    <div class="booking-card">
        <p class="eyebrow">PROGRAM ÖZETİ</p>
        <h2><?php echo $e($p['schedule']['duration_nights']); ?> gece <span><?php echo $e($p['schedule']['duration_days']); ?> gün</span></h2>
        <div class="rail-route"><?php echo $e($stay_summary_text); ?></div>
        <?php if ($prices): ?>
        <div class="rail-prices">
            <?php foreach ($prices as $price): ?>
            <div><span><?php echo $e($price['label'] ?? $price['occupancy']); ?></span><strong><?php echo $e($money($price['amount'])); ?> <small><?php echo $e($p['pricing']['currency']); ?></small></strong></div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <div class="rail-dates"><span><small>Gidiş</small><?php echo $e($date($p['schedule']['start_date'])); ?></span><span><small>Dönüş</small><?php echo $e($date($p['schedule']['end_date'])); ?></span></div>
        <a class="button button-gold button-full" href="<?php echo esc_url(home_url('/iletisim/')); ?>">Rezervasyon bilgisi alın</a>
        <p class="rail-note">Fiyat ve müsaitlik rezervasyon öncesinde teyit edilmelidir.</p>
    </div>
</aside>
</div>
</main>

<dialog id="stpubLightbox" class="stpub-lightbox" aria-labelledby="stpubLightboxTitle">
    <div class="stpub-lightbox-panel">
        <div class="stpub-lightbox-head">
            <div>
                <p class="stpub-lightbox-kicker">OTEL GALERİSİ</p>
                <h2 id="stpubLightboxTitle">Otel fotoğrafları</h2>
            </div>
            <button type="button" class="stpub-lightbox-close" aria-label="Galeriyi kapat">×</button>
        </div>
        <div class="stpub-lightbox-stage">
            <button type="button" class="stpub-lightbox-nav stpub-lightbox-prev" aria-label="Önceki fotoğraf">‹</button>
            <img class="stpub-lightbox-image" src="" alt="">
            <button type="button" class="stpub-lightbox-nav stpub-lightbox-next" aria-label="Sonraki fotoğraf">›</button>
        </div>
        <div class="stpub-lightbox-foot">
            <span class="stpub-lightbox-counter" aria-live="polite"></span>
            <span class="stpub-lightbox-help">← → tuşlarıyla geçiş · Esc ile kapat</span>
        </div>
    </div>
</dialog>
<script nonce="<?php echo esc_attr($csp_nonce); ?>"><?php readfile(STPPI_DIR . 'assets/program.js'); ?></script>

<?php if ($shell_ready): ?>
    <?php ST_Header_Footer::render_footer(); ?>
    <?php if ($shell_js_file !== ''): ?><script nonce="<?php echo esc_attr($csp_nonce); ?>"><?php readfile($shell_js_file); ?></script><?php endif; ?>
<?php else: ?>
<footer class="footer">
    <div class="footer-brand"><strong>SERVER TURİZM</strong><span>1998'den beri güvenli yolculuklar.</span></div>
    <div class="footer-links"><a href="<?php echo esc_url(home_url('/umre-1/')); ?>">Umre Programları</a><a href="<?php echo esc_url(home_url('/oteller/')); ?>">Oteller</a><a href="<?php echo esc_url(home_url('/iletisim/')); ?>">İletişim</a></div>
    <p>Program bilgileri rezervasyon öncesinde teyit edilmelidir.</p>
</footer>
<?php endif; ?>
</body>
</html>
