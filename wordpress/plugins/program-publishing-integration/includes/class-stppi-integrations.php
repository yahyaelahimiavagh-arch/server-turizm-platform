<?php
if (!defined('ABSPATH')) { exit; }

final class STPPI_Integrations {
    public static function init() {
        add_shortcode('stppi_umre_programs', array(__CLASS__, 'hub_shortcode'));
        add_action('get_footer', array(__CLASS__, 'hotel_related'), 2);
        add_action('wp_enqueue_scripts', array(__CLASS__, 'register_assets'));
        add_filter('body_class', array(__CLASS__, 'umre_skin_body_class'));
    }

    public static function register_assets() {
        wp_register_style('stppi-hub', STPPI_URL . 'assets/hub.css', array(), STPPI_VERSION);
        wp_register_script('stppi-hub', STPPI_URL . 'assets/hub.js', array(), STPPI_VERSION, true);
        wp_register_style('stppi-hotel-relations', STPPI_URL . 'assets/hotel-relations.css', array(), STPPI_VERSION);
        wp_register_style('stppi-umre-skin', STPPI_URL . 'assets/umre-skin.css', array('stppi-hub'), STPPI_VERSION);
        if (self::umre_skin_active()) { wp_enqueue_style('stppi-umre-skin'); }
    }

    private static function umre_skin_preview_requested() {
        if (!stppi_is_umre_hub_request() || !is_user_logged_in() || !current_user_can('manage_options')) { return false; }
        $mode = sanitize_key((string)($_GET['stppi_skin_preview'] ?? ''));
        $nonce = sanitize_text_field((string)($_GET['stppi_skin_nonce'] ?? ''));
        return $mode === 'premium_soft' && $nonce !== '' && wp_verify_nonce($nonce, 'stppi_umre_skin_preview');
    }

    public static function umre_skin_active() {
        if (!stppi_is_umre_hub_request()) { return false; }
        return stppi_umre_skin_enabled() || self::umre_skin_preview_requested();
    }

    public static function umre_skin_body_class($classes) {
        if (self::umre_skin_active()) {
            $classes[] = 'stppi-umre-skin-premium-soft';
        }
        return $classes;
    }

    private static function preview_registry() {
        $out = array();
        foreach ((array)get_option('stppi_registry', array()) as $id => $r) {
            if (!is_array($r) || empty($r['slug']) || in_array($id, array('STP-000036', 'STP-000037'), true)) { continue; }
            if (in_array(($r['mode'] ?? ''), array('prepared', 'public_noindex', 'indexable'), true)) { $out[$id] = $r; }
        }
        return $out;
    }

    private static function public_noindex_registry() {
        $out = array();
        if (!stppi_public_master_enabled()) { return $out; }
        foreach ((array)get_option('stppi_registry', array()) as $id => $r) {
            if (!is_array($r) || empty($r['slug']) || in_array($id, array('STP-000036', 'STP-000037'), true)) { continue; }
            if (($r['mode'] ?? '') === 'public_noindex') { $out[$id] = $r; }
        }
        return $out;
    }

    private static function live_registry() {
        if (!stppi_hub_bridge_enabled()) { return array(); }
        return self::public_noindex_registry();
    }

    public static function preview_programs($include_all = false) {
        if (!class_exists('STPPI_Repository')) { require_once STPPI_DIR . 'includes/class-stppi-repository.php'; }
        if (!class_exists('STPPI_Renderer')) { require_once STPPI_DIR . 'includes/class-stppi-renderer.php'; }
        $dep = STPPI_Repository::dependencies();
        if (is_wp_error($dep)) { return array(); }
        $all = STPPI_Repository::all_rows();
        if (is_wp_error($all)) { return array(); }
        $reg = self::preview_registry();
        $out = array();
        foreach ($all['rows'] as $id => $row) {
            $p = $row['program'];
            $cfg = $reg[$id] ?? null;
            if (!$include_all) {
                if (!$cfg) { continue; }
                $wf = $p['workflow'] ?? array();
                if (($wf['editorial'] ?? '') !== 'approved' || ($wf['schedule'] ?? '') !== 'scheduled' || !in_array(STPPI_Repository::temporal($p), array('upcoming','in_progress'), true)) { continue; }
                $m = STPPI_Renderer::model($cfg);
                if (is_wp_error($m)) { continue; }
                $out[] = array('id' => $id, 'program' => $p, 'config' => $cfg, 'model' => $m);
            } else {
                $out[] = array('id' => $id, 'program' => $p, 'config' => $cfg, 'model' => null);
            }
        }
        usort($out, function($a, $b) {
            $ad=(string)($a['program']['schedule']['start_date'] ?? '9999-99-99');
            $bd=(string)($b['program']['schedule']['start_date'] ?? '9999-99-99');
            if($ad!==$bd) return strcmp($ad,$bd);
            $ar=isset($a['program']['provenance']['source_row']) && is_numeric($a['program']['provenance']['source_row']) ? (int)$a['program']['provenance']['source_row'] : PHP_INT_MAX;
            $br=isset($b['program']['provenance']['source_row']) && is_numeric($b['program']['provenance']['source_row']) ? (int)$b['program']['provenance']['source_row'] : PHP_INT_MAX;
            if($ar!==$br) return $ar <=> $br;
            return strcmp((string)($a['id'] ?? ''),(string)($b['id'] ?? ''));
        });
        return $out;
    }

    private static function program_items_from_registry($reg) {
        if (!class_exists('STPPI_Repository')) { require_once STPPI_DIR . 'includes/class-stppi-repository.php'; }
        if (!class_exists('STPPI_Renderer')) { require_once STPPI_DIR . 'includes/class-stppi-renderer.php'; }
        $dep = STPPI_Repository::dependencies();
        if (is_wp_error($dep)) { return array(); }
        $all = STPPI_Repository::all_rows();
        if (is_wp_error($all)) { return array(); }
        $out = array();
        foreach ((array)$reg as $id => $cfg) {
            if (empty($all['rows'][$id])) { continue; }
            $p = $all['rows'][$id]['program'];
            $wf = $p['workflow'] ?? array();
            if (($wf['editorial'] ?? '') !== 'approved' || ($wf['schedule'] ?? '') !== 'scheduled' || !in_array(STPPI_Repository::temporal($p), array('upcoming','in_progress'), true)) { continue; }
            $m = STPPI_Renderer::model($cfg);
            if (is_wp_error($m)) { continue; }
            $out[] = array('id' => $id, 'program' => $p, 'config' => $cfg, 'model' => $m);
        }
        usort($out, function($a, $b) {
            $ad=(string)($a['program']['schedule']['start_date'] ?? '9999-99-99');
            $bd=(string)($b['program']['schedule']['start_date'] ?? '9999-99-99');
            if($ad!==$bd) return strcmp($ad,$bd);
            $ar=isset($a['program']['provenance']['source_row']) && is_numeric($a['program']['provenance']['source_row']) ? (int)$a['program']['provenance']['source_row'] : PHP_INT_MAX;
            $br=isset($b['program']['provenance']['source_row']) && is_numeric($b['program']['provenance']['source_row']) ? (int)$b['program']['provenance']['source_row'] : PHP_INT_MAX;
            if($ar!==$br) return $ar <=> $br;
            return strcmp((string)($a['id'] ?? ''),(string)($b['id'] ?? ''));
        });
        return $out;
    }

    public static function eligible_public_noindex_programs() {
        return self::program_items_from_registry(self::public_noindex_registry());
    }

    public static function live_hub_programs() {
        if (!stppi_hub_bridge_enabled()) { return array(); }
        return self::eligible_public_noindex_programs();
    }

    public static function preview_hotel_relations() {
        $items = self::preview_programs();
        $out = array();
        foreach ($items as $x) {
            $resolved = (array)($x['model']['hotels'] ?? array());
            foreach (self::sequence_sorted($x['program']['stays'] ?? array()) as $stay) {
                $hid = (string)($stay['hotel_id'] ?? '');
                if (!preg_match('/^STH-[0-9]{6}$/D', $hid)) { continue; }
                $h = (isset($resolved[$hid]) && is_array($resolved[$hid])) ? $resolved[$hid] : array();
                $name = (string)($h['name'] ?? $hid);
                if (!isset($out[$hid])) { $out[$hid] = array('hotel_name' => $name, 'public_url' => (string)($h['public_url'] ?? ''), 'programs' => array()); }
                $out[$hid]['programs'][] = $x;
            }
        }
        ksort($out);
        return $out;
    }

    private static function hotel_relation_registry() {
        $out = array();
        if (!stppi_public_master_enabled()) { return $out; }
        foreach ((array)get_option('stppi_registry', array()) as $id => $r) {
            if (!is_array($r) || empty($r['slug']) || in_array($id, array('STP-000036', 'STP-000037'), true)) { continue; }
            if (in_array(($r['mode'] ?? ''), array('public_noindex', 'indexable'), true)) { $out[$id] = $r; }
        }
        return $out;
    }

    public static function programs_for_hotel($hotel_id) {
        $hotel_id = strtoupper(trim((string)$hotel_id));
        if (!preg_match('/^STH-[0-9]{6}$/D', $hotel_id)) { return array(); }
        if (!class_exists('STPPI_Repository')) { require_once STPPI_DIR . 'includes/class-stppi-repository.php'; }
        $dep = STPPI_Repository::dependencies();
        if (is_wp_error($dep)) { return array(); }
        $all = STPPI_Repository::all_rows();
        if (is_wp_error($all)) { return array(); }
        $reg = self::hotel_relation_registry();
        $out = array();
        foreach ($reg as $id => $cfg) {
            if (empty($all['rows'][$id])) { continue; }
            $p = $all['rows'][$id]['program'];
            $wf = is_array($p['workflow'] ?? null) ? $p['workflow'] : array();
            if (($wf['editorial'] ?? '') !== 'approved' || ($wf['schedule'] ?? '') !== 'scheduled') { continue; }
            if (!in_array(STPPI_Repository::temporal($p), array('upcoming','in_progress'), true)) { continue; }
            if (!in_array($hotel_id, STPPI_Repository::hotel_ids($p), true)) { continue; }
            $out[] = array('id' => $id, 'program' => $p, 'config' => $cfg);
        }
        usort($out, function($a, $b) {
            $ad=(string)($a['program']['schedule']['start_date'] ?? '9999-99-99');
            $bd=(string)($b['program']['schedule']['start_date'] ?? '9999-99-99');
            if($ad!==$bd) return strcmp($ad,$bd);
            $ar=isset($a['program']['provenance']['source_row']) && is_numeric($a['program']['provenance']['source_row']) ? (int)$a['program']['provenance']['source_row'] : PHP_INT_MAX;
            $br=isset($b['program']['provenance']['source_row']) && is_numeric($b['program']['provenance']['source_row']) ? (int)$b['program']['provenance']['source_row'] : PHP_INT_MAX;
            if($ar!==$br) return $ar <=> $br;
            return strcmp((string)($a['id'] ?? ''),(string)($b['id'] ?? ''));
        });
        return $out;
    }

    public static function hotel_post_id($hotel_id) {
        $hotel_id = strtoupper(trim((string)$hotel_id));
        if (!preg_match('/^STH-[0-9]{6}$/D', $hotel_id)) { return 0; }
        $ids = get_posts(array(
            'post_type' => 'sthi_hotel',
            'post_status' => array('publish','private','draft','pending','future'),
            'posts_per_page' => 2,
            'fields' => 'ids',
            'meta_key' => '_sthi_hotel_id',
            'meta_value' => $hotel_id,
            'no_found_rows' => true,
        ));
        return count($ids) === 1 ? (int)$ids[0] : 0;
    }

    public static function hotel_public_url($hotel_id) {
        $post_id = self::hotel_post_id($hotel_id);
        if (!$post_id) { return ''; }
        return trim((string)apply_filters('sthi_hotel_public_url', '', $post_id));
    }

    private static function date_tr($ymd) {
        $v = is_string($ymd) ? DateTimeImmutable::createFromFormat('!Y-m-d', $ymd) : false;
        return ($v && $v->format('Y-m-d') === $ymd) ? $v->format('d.m.Y') : (string)$ymd;
    }

    private static function date_parts_tr($ymd) {
        $v = is_string($ymd) ? DateTimeImmutable::createFromFormat('!Y-m-d', $ymd) : false;
        if (!$v || $v->format('Y-m-d') !== $ymd) { return array('day' => '', 'month' => '', 'full' => (string)$ymd); }
        $months = array(1=>'Ocak',2=>'Şubat',3=>'Mart',4=>'Nisan',5=>'Mayıs',6=>'Haziran',7=>'Temmuz',8=>'Ağustos',9=>'Eylül',10=>'Ekim',11=>'Kasım',12=>'Aralık');
        return array('day' => $v->format('j'), 'month' => $months[(int)$v->format('n')] ?? '', 'full' => $v->format('d.m.Y'));
    }

    private static function adult_prices($p) {
        $out = array();
        foreach ((array)($p['pricing']['entries'] ?? array()) as $r) {
            if (!is_array($r) || !is_numeric($r['amount'] ?? null) || (float)$r['amount'] <= 0) { continue; }
            $out[] = array('label' => (string)($r['label'] ?? ($r['occupancy'] ?? '')), 'amount' => (float)$r['amount']);
        }
        return array_slice($out, 0, 4);
    }

    private static function child_rules($p) {
        $out = array();
        foreach ((array)($p['pricing']['child_rules'] ?? array()) as $r) {
            if (!is_array($r)) { continue; }
            $min = isset($r['min_age']) ? (float)$r['min_age'] : null;
            $max = isset($r['max_age']) ? (float)$r['max_age'] : null;
            if ($min === null || $max === null) { continue; }
            $label = rtrim(rtrim(number_format($min, 1, '.', ''), '0'), '.') . '–' . rtrim(rtrim(number_format($max, 1, '.', ''), '0'), '.') . ' yaş';
            $value = 'Bilgi için danışın';
            if (($r['pricing_method'] ?? '') === 'fixed' && is_numeric($r['amount'] ?? null)) { $value = number_format((float)$r['amount'], 0, ',', '.') . ' USD'; }
            elseif (($r['pricing_method'] ?? '') === 'discount' && is_numeric($r['discount_amount'] ?? null)) { $value = number_format((float)$r['discount_amount'], 0, ',', '.') . ' USD indirim'; }
            $out[] = array('label' => $label, 'value' => $value, 'bed' => !empty($r['bed_included']));
        }
        return $out;
    }

    private static function transfer_step($p) {
        foreach (self::sequence_sorted($p['segments'] ?? array()) as $seg) {
            if (!is_array($seg) || ($seg['type'] ?? '') !== 'transfer') { continue; }
            return array(
                'raw_date' => substr((string)($seg['depart_at'] ?? ''), 0, 10),
                'date' => self::date_tr(substr((string)($seg['depart_at'] ?? ''), 0, 10)),
                'origin' => (string)($seg['origin'] ?? ''),
                'destination' => (string)($seg['destination'] ?? '')
            );
        }
        return array();
    }

    private static function sequence_sorted($items) {
        $items=array_values(array_filter((array)$items,'is_array'));
        usort($items,function($a,$b){
            $as=isset($a['sequence'])&&is_numeric($a['sequence'])?(int)$a['sequence']:PHP_INT_MAX;
            $bs=isset($b['sequence'])&&is_numeric($b['sequence'])?(int)$b['sequence']:PHP_INT_MAX;
            return $as===$bs ? 0 : ($as <=> $bs);
        });
        return $items;
    }

    private static function hotel_summaries($x) {
        $out = array();
        $resolved = (array)($x['model']['hotels'] ?? array());
        foreach (self::sequence_sorted($x['program']['stays'] ?? array()) as $stay) {
            if (!is_array($stay)) { continue; }
            $hid = (string)($stay['hotel_id'] ?? '');
            $h = (isset($resolved[$hid]) && is_array($resolved[$hid])) ? $resolved[$hid] : array();
            $name = (string)($h['name'] ?? $hid);
            if (!$name) { continue; }
            $images = array();
            // Program Intelligence's Hotel adapter intentionally exposes only the first
            // four images in its DTO. For the Hub card we need the full visible Hotel
            // Intelligence gallery, but we still read it directly from the canonical
            // Hotel entity (no duplication / no writes) and cap the card at 10 images.
            $display_items = array();
            if (!empty($h['post_id']) && class_exists('STHI_Media') && method_exists('STHI_Media', 'get_display_items')) {
                $display_items = (array)STHI_Media::get_display_items((int)$h['post_id']);
            }
            if (!$display_items) {
                foreach ((array)($h['images'] ?? array()) as $img) { $display_items[] = array('url' => $img); }
            }
            foreach ($display_items as $item) {
                $raw = is_array($item) ? (string)($item['url'] ?? '') : (string)$item;
                $safe = STPPI_Renderer::media_url($raw);
                if ($safe && !in_array($safe, $images, true)) { $images[] = $safe; }
                if (count($images) >= 10) { break; }
            }
            $out[] = array(
                'id' => $hid,
                'name' => $name,
                'destination' => STPPI_Renderer::geography((string)($stay['destination'] ?? '')),
                'nights' => (int)($stay['nights'] ?? 0),
                'check_in' => (string)($stay['check_in'] ?? ''),
                'check_out' => (string)($stay['check_out'] ?? ''),
                'stars' => max(0, (int)($h['stars'] ?? 0)),
                'public_url' => (string)($h['public_url'] ?? ''),
                'images' => $images
            );
        }
        return $out;
    }

    private static function destination_city($p, $index, $fallback = '') {
        $destinations = self::sequence_sorted($p['destinations'] ?? array());
        if (!isset($destinations[$index]) || !is_array($destinations[$index])) { return $fallback; }
        return STPPI_Renderer::geography((string)($destinations[$index]['city'] ?? $fallback));
    }

    private static function render_journey_step($label, $parts, $sub) {
        return '<div class="stppi-legacy-card__journey-step"><small>' . esc_html($label) . '</small><strong><span>' . esc_html($parts['day']) . '</span> ' . esc_html($parts['month']) . '</strong><em>' . esc_html($sub) . '</em></div>';
    }

    private static function legacy_price_symbol($currency) {
        $map = array('USD' => '$', 'EUR' => '€', 'TRY' => '₺', 'SAR' => 'SAR');
        return $map[$currency] ?? $currency;
    }

    private static function legacy_amount($amount, $currency) {
        $amount = is_numeric($amount) ? (float)$amount : 0;
        $symbol = self::legacy_price_symbol($currency);
        if ($symbol === '$' || $symbol === '€' || $symbol === '₺') {
            return number_format($amount, 0, '.', ',') . $symbol;
        }
        return number_format($amount, 0, '.', ',') . ' ' . $symbol;
    }

    private static function legacy_short_date($ymd) {
        $ymd = trim((string)$ymd);
        $d = DateTimeImmutable::createFromFormat('!Y-m-d', $ymd);
        return ($d && $d->format('Y-m-d') === $ymd) ? $d->format('d.m') : '';
    }

    private static function legacy_availability($value) {
        $map = array(
            'open' => array('label' => 'REZERVASYONA AÇIK', 'class' => 'is-open'),
            'limited' => array('label' => 'SINIRLI KONTENJAN', 'class' => 'is-limited'),
            'on_request' => array('label' => 'TALEP ÜZERİNE', 'class' => 'is-request'),
            'sold_out' => array('label' => 'KONTENJAN DOLU', 'class' => 'is-sold-out'),
        );
        return $map[(string)$value] ?? $map['open'];
    }

    private static function legacy_calendar_url($title, $ymd) {
        $date = preg_replace('/[^0-9]/', '', (string)$ymd);
        if (strlen($date) !== 8) { return '#'; }
        return 'https://calendar.google.com/calendar/render?action=TEMPLATE&text=' . rawurlencode(' ' . (string)$title) . '&dates=' . $date . '/' . $date;
    }

    private static function legacy_hotel_class($destination, $index) {
        $key = strtolower(trim((string)$destination));
        if (in_array($key, array('madinah','medina','medine'), true)) { return 'medine-hotel-block'; }
        if (in_array($key, array('makkah','mecca','mekke'), true)) { return 'mekke-hotel-block'; }
        return 'baska-hotel-block hotel-' . (int)$index;
    }

    private static function legacy_map_url($hotel_name, $destination) {
        $q = trim((string)$hotel_name . ' ' . STPPI_Renderer::geography((string)$destination));
        return 'https://www.google.com/maps?q=' . rawurlencode($q) . '&output=embed';
    }

    private static function legacy_journey_points($p) {
        $points = array();
        $start = (string)($p['schedule']['start_date'] ?? '');
        $end = (string)($p['schedule']['end_date'] ?? '');
        $dests = self::sequence_sorted($p['destinations'] ?? array());
        $first_city = isset($dests[0]['city']) ? STPPI_Renderer::geography((string)$dests[0]['city']) : '';
        $last_idx = max(0, count($dests) - 1);
        $last_city = isset($dests[$last_idx]['city']) ? STPPI_Renderer::geography((string)$dests[$last_idx]['city']) : '';
        $points[] = array('label' => 'Gidiş', 'date' => $start, 'sub' => $first_city);
        $transfers = array();
        foreach (self::sequence_sorted($p['segments'] ?? array()) as $seg) {
            if (!is_array($seg) || ($seg['type'] ?? '') !== 'transfer') { continue; }
            $raw = substr((string)($seg['depart_at'] ?? ''), 0, 10);
            if (!$raw) { continue; }
            $transfers[] = array(
                'date' => $raw,
                'sub' => trim(STPPI_Renderer::geography((string)($seg['origin'] ?? '')) . ' → ' . STPPI_Renderer::geography((string)($seg['destination'] ?? '')), ' →')
            );
        }
        $tc = count($transfers);
        foreach ($transfers as $i => $t) {
            $label = $tc === 1 ? 'Ara Geçiş' : (($i + 1) . '. Geçiş');
            $points[] = array('label' => $label, 'date' => $t['date'], 'sub' => $t['sub']);
        }
        $points[] = array('label' => 'Dönüş', 'date' => $end, 'sub' => $last_city);
        return $points;
    }

    private static function legacy_destination_summary($p) {
        $parts = array();
        foreach (self::sequence_sorted($p['stays'] ?? array()) as $stay) {
            if (!is_array($stay)) { continue; }
            $n = (int)($stay['nights'] ?? 0);
            $city = STPPI_Renderer::geography((string)($stay['destination'] ?? ''));
            if ($city && $n > 0) { $parts[] = $n . ' Gece ' . $city; }
        }
        return implode(', ', $parts);
    }

    public static function hub_shortcode($atts = array()) {
        $atts = shortcode_atts(array('preview' => '0'), $atts, 'stppi_umre_programs');
        $admin_preview = (($atts['preview'] ?? '0') === '1' && current_user_can('manage_options'));
        $items = $admin_preview ? self::preview_programs() : self::live_hub_programs();
        if (!$items) {
            if (current_user_can('manage_options')) { return '<div class="stppi-hub-admin-warning">STPPI Hub kapalı veya yayın koşullarını geçen Program yok. Bu uyarı yalnızca yöneticilere görünür.</div>'; }
            return '<!-- STPPI hub bridge closed -->';
        }

        wp_enqueue_style('stppi-hub');
        wp_enqueue_script('stppi-hub');

        $html = '<section class="stppi-legacy-hub" aria-label="Güncel ve yaklaşan Umre programları">';
        foreach ($items as $x) {
            $p = $x['program'];
            $c = $x['config'];
            $model = $x['model'];
            $currency = (string)($p['pricing']['currency'] ?? 'USD');
            $hero = STPPI_Renderer::media_url((string)($p['media']['hero_image_url'] ?? ''));
            $prices = self::adult_prices($p);
            $children = self::child_rules($p);
            $hotels = self::hotel_summaries($x);
            $days = (int)($p['schedule']['duration_days'] ?? 0);
            $nights = (int)($p['schedule']['duration_nights'] ?? 0);
            $start_raw = (string)($p['schedule']['start_date'] ?? '');
            $temporal = STPPI_Repository::temporal($p);
            $server_today = wp_date('Y-m-d', null, new DateTimeZone('Europe/Istanbul'));
            $code = (string)($p['program_code'] ?? '');
            $title = (string)($p['title'] ?? 'UMRE PROGRAMI');
            $availability = (string)($p['workflow']['availability'] ?? 'open');
            $availability_meta = self::legacy_availability($availability);
            $sold_out = ($availability === 'sold_out');
            $phone = (string)($p['contact']['whatsapp'] ?? ($p['contact']['phone'] ?? ''));
            $program_url = STPPI_Renderer::url($c);
            $root_id = 'card_' . sanitize_html_class(strtolower($code ?: $x['id'])) . '_' . substr(md5((string)$x['id']), 0, 5);
            $journey = self::legacy_journey_points($p);
            $duration_summary = self::legacy_destination_summary($p);
            $program_name = 'PROGRAM ' . $code;

            $html .= '<div class="lc-card-template" id="' . esc_attr($root_id) . '" data-stppi-legacy-card="1" data-stppi-program-id="' . esc_attr((string)$x['id']) . '" data-start-date="' . esc_attr($start_raw) . '" data-temporal="' . esc_attr($temporal) . '" data-server-today="' . esc_attr($server_today) . '" data-whatsapp="' . esc_attr($phone) . '" data-program-name="' . esc_attr($program_name) . '" data-program-url="' . esc_url($program_url) . '" data-sold-out="' . ($sold_out ? '1' : '0') . '">';
            $html .= '<div class="lc-head">';
            $html .= '<div class="lc-badge">PROGRAM ' . esc_html($code) . '</div>';
            $html .= '<div class="lc-title-wrap"><div class="lc-title">' . esc_html($title) . '</div><div class="lc-dur-total-top"><span class="dur-num">' . esc_html((string)$nights) . '</span> Gece <span class="dur-num">' . esc_html((string)$days) . '</span> Gün</div></div>';
            $html .= '<div class="lc-timer-wrap"><span class="lc-timer-caption">KALKIŞA KALAN</span><div class="lc-timer lc-timer-single"><div class="lc-t-box lc-t-box-days"><div class="lc-t-val timer-d">0</div><div class="lc-t-lbl">GÜN</div></div></div></div>';
            $html .= '</div>';

            $html .= '<div class="lc-grid">';
            $html .= '<div class="lc-img">';
            if ($hero) { $html .= '<img src="' . esc_url($hero) . '" alt="' . esc_attr($title) . '" decoding="async">'; }
            $html .= '</div>';
            $html .= '<div class="lc-center">';
            $html .= '<div class="lc-path">';
            foreach ($journey as $i => $pt) {
                if ($i > 0) { $html .= '<div class="lc-line"><div class="lc-icon">➔</div></div>'; }
                $parts = self::date_parts_tr((string)$pt['date']);
                $cal = self::legacy_calendar_url($title, (string)$pt['date']);
                $html .= '<div class="lc-pt"><a href="' . esc_url($cal) . '" target="_blank" rel="noopener"><span class="lc-date-label">' . esc_html((string)$pt['label']) . '</span><b>' . esc_html(trim($parts['day'] . ' ' . $parts['month'])) . '</b><span class="lc-hijri" data-date="' . esc_attr((string)$pt['date']) . '"></span></a></div>';
            }
            $html .= '</div>';

            foreach ($hotels as $hi => $h) {
                $hclass = self::legacy_hotel_class($h['destination'], $hi);
                $gallery_json = wp_json_encode(array_values($h['images']), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                $map_url = self::legacy_map_url($h['name'], $h['destination']);
                $html .= '<div class="lc-hotel ' . esc_attr($hclass) . '" data-gallery="' . esc_attr($gallery_json) . '" data-map-url="' . esc_url($map_url) . '" data-hotel-name="' . esc_attr($h['name']) . '" data-hotel-id="' . esc_attr((string)$h['id']) . '">';
                $hotel_name_html = esc_html($h['name']);
                if (!empty($h['public_url']) && STPPI_Renderer::local_url($h['public_url'])) {
                    $hotel_name_html = '<a class="lc-h-name lc-h-link" data-stppi-action="hotel_click" href="' . esc_url($h['public_url']) . '">' . esc_html($h['name']) . '<span class="lc-h-link-arrow" aria-hidden="true">↗</span></a>';
                } else {
                    $hotel_name_html = '<span class="lc-h-name">' . esc_html($h['name']) . '</span>';
                }
                $hotel_meta = array();
                if ((int)($h['stars'] ?? 0) > 0) { $hotel_meta[] = '<span class="lc-star-badge">' . esc_html((string)$h['stars']) . ' YILDIZ</span>'; }
                $in_short = self::legacy_short_date((string)($h['check_in'] ?? ''));
                $out_short = self::legacy_short_date((string)($h['check_out'] ?? ''));
                if ($in_short && $out_short) { $hotel_meta[] = '<span class="lc-stay-dates">' . esc_html($in_short . ' → ' . $out_short) . '</span>'; }
                if ((int)($h['nights'] ?? 0) > 0) { $hotel_meta[] = '<span class="lc-stay-nights">' . esc_html((string)$h['nights']) . ' GECE</span>'; }
                $html .= '<div class="lc-h-top"><div class="lc-h-txt"><span class="lc-h-title">' . esc_html(mb_strtoupper(STPPI_Renderer::geography($h['destination']), 'UTF-8')) . ' OTELİ</span>' . $hotel_name_html . (!empty($hotel_meta) ? '<div class="lc-h-meta">' . implode('', $hotel_meta) . '</div>' : '') . '</div>';
                $html .= '<div class="lc-actions">';
                $html .= '<button type="button" class="lc-btn-icon lc-map-btn" data-stppi-action="hotel_map" aria-label="' . esc_attr($h['name'] . ' konumunu aç') . '"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>KONUM</button>';
                $html .= '<button type="button" class="lc-btn-icon lc-gallery-btn" data-stppi-action="gallery_open" aria-label="' . esc_attr($h['name'] . ' galerisini aç') . '"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>GALERİ</button>';
                $html .= '</div></div>';
                $html .= '<div class="lc-thumbs" role="list" aria-label="' . esc_attr($h['name'] . ' fotoğrafları') . '">';
                foreach (array_slice((array)$h['images'], 0, 10) as $idx => $img) { $html .= '<img src="' . esc_url($img) . '" class="lc-thumb-img' . ($idx === 0 ? ' is-active' : '') . '" alt="' . esc_attr($h['name'] . ' fotoğraf ' . ($idx + 1)) . '" loading="lazy" decoding="async" tabindex="0" role="button" aria-label="' . esc_attr($h['name'] . ' fotoğraf ' . ($idx + 1) . ' galerisini aç') . '"' . ($idx === 0 ? ' aria-current="true"' : '') . '>'; }
                $html .= '</div></div>';
            }

            // Exact legacy trust/logo strip used by the previous Umrah RAW card.
            $html .= '<div class="lc-center-footer">';
            $html .= '<img class="lc-airline-logo is-thy" src="' . esc_url(home_url('/wp-content/uploads/2026/04/10806-scaled.png')) . '" alt="Turkish Airlines" loading="lazy" decoding="async">';
            $html .= '<img class="lc-airline-logo is-iata" src="' . esc_url(home_url('/wp-content/uploads/2026/04/IATA2-01-scaled.png')) . '" alt="IATA" loading="lazy" decoding="async">';
            $html .= '<img class="lc-airline-logo is-tursab" src="' . esc_url(home_url('/wp-content/uploads/2026/04/10803-01-scaled-e1776760368295.png')) . '" alt="TÜRSAB" loading="lazy" decoding="async">';
            $html .= '</div></div>';

            $html .= '<div class="lc-right">';
            $html .= '<div class="lc-availability ' . esc_attr($availability_meta['class']) . '"><span class="lc-availability-dot" aria-hidden="true"></span>' . esc_html($availability_meta['label']) . '</div>';
            $html .= '<div class="lc-price-list">';
            foreach ($prices as $i => $pr) {
                $label = (string)($pr['label'] ?? '');
                // Keep legacy concise labels: "2 kişilik oda" -> "2 Kişilik".
                $legacy_label = preg_replace('/\s*oda\s*$/iu', '', $label);
                $html .= '<div class="lc-p-row' . ($i === 0 ? ' selected' : '') . '" role="button" tabindex="0" aria-pressed="' . ($i === 0 ? 'true' : 'false') . '"><span class="lc-p-cat">' . esc_html($legacy_label) . '</span><span class="lc-p-price-wrap"><span class="lc-p-val">' . esc_html(self::legacy_amount($pr['amount'], $currency)) . '</span><small class="lc-p-unit">KİŞİ BAŞI</small></span></div>';
            }
            $html .= '</div>';
            if ($duration_summary) { $html .= '<div class="lc-duration-info"><span class="lc-dur-split">' . esc_html($duration_summary) . '</span></div>'; }

            if ($children || !empty($p['notes'])) {
                $child_panel_id = $root_id . '_child_prices';
                $html .= '<div class="lc-acc"><button type="button" class="lc-acc-head" aria-expanded="false" aria-controls="' . esc_attr($child_panel_id) . '"><span><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>ÇOCUK ÜCRETLERİ</span><span class="lc-acc-chevron" aria-hidden="true">▼</span></button><div class="lc-acc-body" id="' . esc_attr($child_panel_id) . '"><ul>';
                foreach ((array)($p['pricing']['child_rules'] ?? array()) as $r) {
                    if (!is_array($r)) { continue; }
                    $min = isset($r['min_age']) ? rtrim(rtrim(number_format((float)$r['min_age'],1,'.',''),'0'),'.') : '';
                    $max = isset($r['max_age']) ? rtrim(rtrim(number_format((float)$r['max_age'],1,'.',''),'0'),'.') : '';
                    $val = 'Bilgi için danışın';
                    if (($r['pricing_method'] ?? '') === 'fixed' && is_numeric($r['amount'] ?? null)) { $val = self::legacy_amount((float)$r['amount'], $currency); }
                    elseif (($r['pricing_method'] ?? '') === 'discount' && is_numeric($r['discount_amount'] ?? null)) { $val = self::legacy_amount((float)$r['discount_amount'], $currency) . ' indirim'; }
                    $html .= '<li>' . esc_html($min . '-' . $max . ' Yaş: ' . $val) . '</li>';
                }
                foreach ((array)($p['notes'] ?? array()) as $note) { if (is_scalar($note) && trim((string)$note) !== '') { $html .= '<li>' . esc_html((string)$note) . '</li>'; } }
                $html .= '</ul></div></div>';
            }

            if (!empty($p['inclusions'])) {
                $notes_panel_id = $root_id . '_important_notes';
                $html .= '<div class="lc-acc"><button type="button" class="lc-acc-head" aria-expanded="false" aria-controls="' . esc_attr($notes_panel_id) . '"><span><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>ÖNEMLİ NOTLAR</span><span class="lc-acc-chevron" aria-hidden="true">▼</span></button><div class="lc-acc-body" id="' . esc_attr($notes_panel_id) . '"><ul>';
                foreach ((array)$p['inclusions'] as $note) { if (is_scalar($note) && trim((string)$note) !== '') { $html .= '<li>' . esc_html((string)$note) . '</li>'; } }
                $html .= '</ul></div></div>';
            }

            $html .= '<div class="lc-detail-wrap"><a href="' . esc_url($program_url) . '" class="lc-details" data-stppi-action="program_detail"><span class="lc-details-text">TUR DETAYLARI</span><span class="lc-details-arrow" aria-hidden="true">→</span></a></div>';
            $html .= '<div class="' . ($sold_out ? 'lc-cta-wrap2' : 'lc-cta-wrap') . '"><a href="#" class="lc-cta" data-stppi-action="whatsapp_reservation">REZERVASYON YAP</a></div>';
            $html .= '</div></div>';
            $modal_title_id = 'modal_title_' . $root_id;
            $html .= '<div class="lc-modal" id="modal_' . esc_attr($root_id) . '" role="dialog" aria-modal="true" aria-hidden="true" aria-labelledby="' . esc_attr($modal_title_id) . '"><div class="lc-modal-box"><div class="lc-modal-head"><div><span class="lc-modal-kicker">OTEL GALERİSİ</span><strong class="lc-modal-title" id="' . esc_attr($modal_title_id) . '">OTEL</strong></div><button type="button" class="lc-close" aria-label="Galeriyi kapat">×</button></div><div class="lc-media-content"></div><div class="lc-modal-foot"><span class="lc-gallery-counter"></span><span class="lc-gallery-hint">← → tuşlarıyla geçiş · Esc ile kapat</span></div></div></div>';
            $html .= '</div>';
        }
        return $html . '</section>';
    }

    private static function hotel_relation_availability($value) {
        $map = array(
            'open' => 'Rezervasyona açık',
            'limited' => 'Sınırlı kontenjan',
            'on_request' => 'Talep üzerine',
            'sold_out' => 'Kontenjan dolu',
        );
        return $map[(string)$value] ?? '';
    }

    public static function hotel_related() {
        if (!stppi_hotel_links_enabled()) { return; }
        $allowlist = stppi_hotel_allowlist_ids();
        if (!$allowlist) { return; }
        if (!class_exists('STHI_Frontend') || !method_exists('STHI_Frontend', 'context_post_id')) { return; }
        $post_id = (int)STHI_Frontend::context_post_id();
        if (!$post_id) { return; }
        if (!(bool)apply_filters('sthi_hotel_is_public_route_context', false, $post_id)) { return; }
        $hotel_id = strtoupper(trim((string)get_post_meta($post_id, '_sthi_hotel_id', true)));
        if (!$hotel_id || !in_array($hotel_id, $allowlist, true)) { return; }
        $programs = self::programs_for_hotel($hotel_id);
        if (!$programs) { return; }

        wp_enqueue_style('stppi-hotel-relations');
        $visible = array_slice($programs, 0, 6);
        $count = count($programs);
        echo '<section class="stppi-hotel-programs" data-stppi-hotel-id="' . esc_attr($hotel_id) . '" aria-labelledby="stppi-hotel-programs-title">';
        echo '<div class="stppi-hotel-programs__inner">';
        echo '<div class="stppi-hotel-programs__head"><div><span>SERVER TURİZM · UMRE PROGRAMLARI</span><h2 id="stppi-hotel-programs-title">Bu oteli kullanan güncel Umre programları</h2><p>Bu otelde konaklama içeren yaklaşan programları tarih sırasıyla inceleyin.</p></div><a class="stppi-hotel-programs__all" href="' . esc_url(home_url('/umre-1/')) . '">Tüm Umre programları <b aria-hidden="true">→</b></a></div>';
        echo '<div class="stppi-hotel-programs__grid">';
        foreach ($visible as $x) {
            $p = $x['program']; $c = $x['config'];
            $schedule = is_array($p['schedule'] ?? null) ? $p['schedule'] : array();
            $workflow = is_array($p['workflow'] ?? null) ? $p['workflow'] : array();
            $url = home_url(STPPI_ROUTE_BASE . $c['slug'] . '/');
            $mode = (string)($c['mode'] ?? '');
            $rel = ($mode === 'public_noindex') ? ' rel="nofollow"' : '';
            $code = trim((string)($p['program_code'] ?? ''));
            $title = trim((string)($p['title'] ?? 'Umre Programı'));
            $start = self::date_tr((string)($schedule['start_date'] ?? ''));
            $end = self::date_tr((string)($schedule['end_date'] ?? ''));
            $nights = (int)($schedule['duration_nights'] ?? 0);
            $days = (int)($schedule['duration_days'] ?? 0);
            $availability = self::hotel_relation_availability($workflow['availability'] ?? '');
            echo '<article class="stppi-hotel-program-card">';
            echo '<div class="stppi-hotel-program-card__top"><span>' . esc_html($code ? 'PROGRAM ' . $code : 'UMRE PROGRAMI') . '</span>' . ($availability ? '<em>' . esc_html($availability) . '</em>' : '') . '</div>';
            echo '<h3>' . esc_html($title) . '</h3>';
            echo '<dl><div><dt>Gidiş</dt><dd>' . esc_html($start ?: '—') . '</dd></div><div><dt>Dönüş</dt><dd>' . esc_html($end ?: '—') . '</dd></div></dl>';
            if ($nights || $days) { echo '<p class="stppi-hotel-program-card__duration">' . esc_html(trim(($nights ? $nights . ' gece' : '') . ($nights && $days ? ' · ' : '') . ($days ? $days . ' gün' : ''))) . '</p>'; }
            echo '<a href="' . esc_url($url) . '"' . $rel . '>Programı incele <b aria-hidden="true">→</b></a>';
            echo '</article>';
        }
        echo '</div>';
        if ($count > 6) { echo '<p class="stppi-hotel-programs__more">Bu oteli kullanan ' . esc_html((string)$count) . ' yaklaşan program bulundu. En yakın 6 program gösteriliyor.</p>'; }
        echo '</div></section>';
    }
}
