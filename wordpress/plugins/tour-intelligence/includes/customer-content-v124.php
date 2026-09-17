<?php
/**
 * STTI v1.2.4 — complete customer-facing Tour content surface.
 *
 * Read-only presentation layer. It exposes every canonical customer-facing
 * business fact stored by Tur Düzenle while keeping workflow, provenance,
 * audit and SEO/publication controls private. Explicitly rejected relations
 * remain hidden; pending canonical operator-entered facts may render without
 * invented enrichment.
 */
if (!defined('ABSPATH')) { exit; }

function stti_v124_text($value) {
    if (is_array($value) || is_object($value)) return '';
    return trim((string)$value);
}

function stti_v124_list($value) {
    if (!is_array($value)) return array();
    $out = array();
    foreach ($value as $item) {
        $text = '';
        if (is_array($item)) {
            foreach (array('label','name','text','title') as $key) {
                $text = stti_v124_text($item[$key] ?? '');
                if ($text !== '') break;
            }
        } else {
            $text = stti_v124_text($item);
        }
        if ($text !== '' && !in_array($text, $out, true)) $out[] = $text;
    }
    return $out;
}

function stti_v124_media_items($payload) {
    $payload = is_array($payload) ? $payload : array();
    $media = is_array($payload['media'] ?? null) ? $payload['media'] : array();
    $items = array();
    $seen = array();

    foreach ((array)($media['items'] ?? array()) as $item) {
        if (!is_array($item)) continue;
        $url = esc_url_raw((string)($item['url'] ?? ''));
        $attachment_id = (int)($item['attachment_id'] ?? 0);
        if ($url === '' && $attachment_id > 0 && function_exists('wp_get_attachment_image_url')) {
            $candidate = wp_get_attachment_image_url($attachment_id, 'large');
            if (is_string($candidate)) $url = esc_url_raw($candidate);
        }
        if ($url === '' || isset($seen[$url])) continue;
        $seen[$url] = true;
        $items[] = array(
            'url'=>$url,
            'title'=>stti_v124_text($item['title'] ?? ($item['label'] ?? '')),
            'caption'=>stti_v124_text($item['caption'] ?? ($item['note'] ?? '')),
        );
    }
    return $items;
}

function stti_v124_raw_hotel_fallbacks($payload, $existing = array()) {
    $payload = is_array($payload) ? $payload : array();
    $rows = is_array($payload['stays']['hotels'] ?? null) ? array_values($payload['stays']['hotels']) : array();
    $out = is_array($existing) ? array_values($existing) : array();
    $seen = array();
    foreach ($out as $item) {
        if (!is_array($item)) continue;
        $key = stti_v124_text($item['relation_id'] ?? '');
        if ($key === '') $key = stti_v124_text($item['hotel_stable_id'] ?? '');
        if ($key === '') $key = stti_v124_text($item['name'] ?? '');
        if ($key !== '') $seen[$key] = true;
    }

    foreach ($rows as $index => $relation) {
        if (!is_array($relation)) continue;
        if (stti_v124_text($relation['review_status'] ?? 'pending') === 'rejected') continue;
        $key = stti_v124_text($relation['relation_id'] ?? '');
        if ($key === '') $key = stti_v124_text($relation['hotel_stable_id'] ?? '');
        if ($key === '') $key = 'raw-hotel-' . ($index + 1);
        if (isset($seen[$key])) continue;

        $facts = function_exists('stti_v080_hotel_facts') ? stti_v080_hotel_facts($relation) : array();
        $name = stti_v124_text($facts['name'] ?? '');
        if ($name === '') $name = stti_v124_text($relation['unresolved_name'] ?? '');
        if ($name === '') $name = stti_v124_text($relation['hotel_stable_id'] ?? '');
        if ($name === '') continue;

        $out[] = array_merge($relation, is_array($facts) ? $facts : array(), array(
            'relation_id'=>$key,
            'name'=>$name,
            'review_status'=>stti_v124_text($relation['review_status'] ?? 'pending'),
        ));
        $seen[$key] = true;
    }
    return $out;
}

function stti_v124_raw_transport_fallbacks($payload, $existing = array()) {
    $payload = is_array($payload) ? $payload : array();
    $rows = is_array($payload['transport']['segments'] ?? null) ? array_values($payload['transport']['segments']) : array();
    $out = is_array($existing) ? array_values($existing) : array();
    $seen = array();
    foreach ($out as $item) {
        if (!is_array($item)) continue;
        $key = stti_v124_text($item['segment_id'] ?? '');
        if ($key !== '') $seen[$key] = true;
    }

    $stop_index = function_exists('stti_v080_stop_index') ? stti_v080_stop_index($payload) : array();
    foreach ($rows as $index => $segment) {
        if (!is_array($segment)) continue;
        if (stti_v124_text($segment['review_status'] ?? 'pending') === 'rejected') continue;
        $key = stti_v124_text($segment['segment_id'] ?? '');
        if ($key === '') $key = 'raw-segment-' . ($index + 1);
        if (isset($seen[$key])) continue;

        $from = stti_v124_text($segment['from_label'] ?? ($segment['from'] ?? ''));
        $to = stti_v124_text($segment['to_label'] ?? ($segment['to'] ?? ''));
        $from_ref = stti_v124_text($segment['from_stop_ref'] ?? '');
        $to_ref = stti_v124_text($segment['to_stop_ref'] ?? '');
        if ($from === '' && $from_ref !== '' && isset($stop_index[$from_ref])) $from = stti_v080_stop_label($stop_index[$from_ref]);
        if ($to === '' && $to_ref !== '' && isset($stop_index[$to_ref])) $to = stti_v080_stop_label($stop_index[$to_ref]);
        if ($from === '' && $to === '' && stti_v124_text($segment['provider'] ?? '') === '' && stti_v124_text($segment['type'] ?? '') === '') continue;

        $segment['segment_id'] = $key;
        $segment['from_label'] = $from;
        $segment['to_label'] = $to;
        $segment['review_status'] = stti_v124_text($segment['review_status'] ?? 'pending');
        $out[] = $segment;
        $seen[$key] = true;
    }
    return $out;
}

function stti_v124_price_label($item) {
    $item = is_array($item) ? $item : array();
    $amount = $item['amount'] ?? null;
    if ($amount === null || $amount === '') return 'Talep üzerine';
    $currency = strtoupper(stti_v124_text($item['currency'] ?? ''));
    $symbols = array('EUR'=>'€','USD'=>'$','TRY'=>'₺','GBP'=>'£');
    $symbol = $symbols[$currency] ?? $currency;
    $number = rtrim(rtrim(number_format((float)$amount, 2, ',', '.'), '0'), ',');
    return trim($number . ' ' . $symbol);
}

function stti_v124_occupancy_label($value) {
    $value = stti_v124_text($value);
    if ($value === '') return '';
    $map = array(
        'single'=>'Tek Kişilik','1_kisilik'=>'Tek Kişilik','double'=>'2 Kişilik','2_kisilik'=>'2 Kişilik',
        'triple'=>'3 Kişilik','3_kisilik'=>'3 Kişilik','quad'=>'4 Kişilik','4_kisilik'=>'4 Kişilik',
        'child'=>'Çocuk','infant'=>'Bebek',
    );
    return $map[$value] ?? str_replace('_', ' ', $value);
}

function stti_v124_availability_label($value) {
    $value = strtolower(stti_v124_text($value));
    $map = array(
        'open'=>'Rezervasyona Açık','limited'=>'Sınırlı Kontenjan','sold_out'=>'Kontenjan Dolu',
        'waitlist'=>'Bekleme Listesi','on_request'=>'Talep Üzerine','closed'=>'Kapalı',
    );
    return $map[$value] ?? '';
}

function stti_v124_customer_model($surface) {
    $surface = is_array($surface) ? $surface : array();
    $p = is_array($surface['payload'] ?? null) ? $surface['payload'] : array();
    $m = is_array($surface['model'] ?? null) ? $surface['model'] : array();
    $identity = is_array($p['identity'] ?? null) ? $p['identity'] : array();
    $dest = is_array($p['destinations'] ?? null) ? $p['destinations'] : array();
    $date = is_array($p['date'] ?? null) ? $p['date'] : array();
    $pricing = is_array($p['pricing'] ?? null) ? $p['pricing'] : array();
    $content = is_array($p['content'] ?? null) ? $p['content'] : array();
    $services = is_array($p['services'] ?? null) ? $p['services'] : array();
    $requirements = is_array($p['requirements'] ?? null) ? $p['requirements'] : array();
    $lifecycle = is_array($p['lifecycle'] ?? null) ? $p['lifecycle'] : array();

    $countries = stti_v124_list($dest['countries'] ?? array());
    if (!$countries && stti_v124_text($dest['countries_csv'] ?? '') !== '') $countries = array_values(array_filter(array_map('trim', explode(',', (string)$dest['countries_csv']))));
    $cities = stti_v124_list($dest['cities'] ?? array());
    if (!$cities && stti_v124_text($dest['cities_csv'] ?? '') !== '') $cities = array_values(array_filter(array_map('trim', explode(',', (string)$dest['cities_csv']))));

    $country = stti_v124_text($dest['primary_country'] ?? '');
    if ($country === '' && $countries) $country = implode(' · ', $countries);
    $route_stops = is_array($m['route_stops'] ?? null) ? array_values($m['route_stops']) : array();
    if (!$route_stops && is_array($p['route']['stops'] ?? null)) $route_stops = array_values($p['route']['stops']);

    $hotels = stti_v124_raw_hotel_fallbacks($p, is_array($m['hotels'] ?? null) ? $m['hotels'] : array());
    $transport = stti_v124_raw_transport_fallbacks($p, is_array($m['transport'] ?? null) ? $m['transport'] : array());

    return array(
        'payload'=>$p,
        'model'=>$m,
        'stable_id'=>stti_v124_text($surface['stable_id'] ?? ''),
        'title'=>stti_v124_text($identity['public_title'] ?? ($surface['stable_id'] ?? 'Tur')),
        'short_title'=>stti_v124_text($identity['short_title'] ?? ''),
        'tour_code'=>stti_v124_text($identity['tour_code'] ?? ''),
        'language'=>stti_v124_text($identity['language'] ?? ''),
        'country'=>$country,
        'countries'=>$countries,
        'primary_city'=>stti_v124_text($dest['primary_city'] ?? ''),
        'cities'=>$cities,
        'departure_city'=>stti_v124_text($dest['departure_city'] ?? ''),
        'return_city'=>stti_v124_text($dest['return_city'] ?? ''),
        'date'=>$date,
        'route_summary'=>function_exists('stti_v119_route_summary') ? stti_v119_route_summary($p, $m) : stti_v124_text($p['route']['summary'] ?? ''),
        'route_stops'=>$route_stops,
        'itinerary'=>is_array($p['itinerary'] ?? null) ? array_values($p['itinerary']) : array(),
        'hotels'=>$hotels,
        'transport'=>$transport,
        'pricing'=>$pricing,
        'price_items'=>is_array($pricing['items'] ?? null) ? array_values($pricing['items']) : array(),
        'included'=>is_array($services['included'] ?? null) ? $services['included'] : array(),
        'excluded'=>is_array($services['excluded'] ?? null) ? $services['excluded'] : array(),
        'requirements'=>$requirements,
        'short_description'=>stti_v124_text($content['short_description'] ?? ''),
        'gallery'=>stti_v124_media_items($p),
        'availability'=>stti_v124_availability_label($lifecycle['availability'] ?? ''),
    );
}

function stti_v124_enqueue_detail_assets() {
    wp_enqueue_style(
        'stti-v124-complete-customer-content',
        STTI_URL . 'assets/detail-route-v124.css',
        array('stti-v122-detail-fixes'),
        STTI_RELEASE_VERSION
    );
}

function stti_v124_maybe_render_detail_route() {
    $stable_id = function_exists('stti_v119_request_stable_id') ? stti_v119_request_stable_id() : '';
    if ($stable_id === '') return;
    $surface = stti_v119_detail_surface($stable_id);
    if (empty($surface['route'])) return;

    global $wp_query;
    if ($wp_query) $wp_query->is_404 = false;
    status_header(200);
    nocache_headers();
    stti_v119_prepare_detail_surface();
    stti_v120_enqueue_detail_assets($surface['model']['map']);
    stti_v124_enqueue_detail_assets();
    stti_v124_render_detail_template($surface);
    exit;
}

require_once __DIR__ . '/detail-route-v124-template.php';

remove_action('template_redirect', 'stti_v119_maybe_render_detail_route', 2);
add_action('template_redirect', 'stti_v124_maybe_render_detail_route', 2);
