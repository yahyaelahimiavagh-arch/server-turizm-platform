<?php
if (!defined('ABSPATH')) { exit; }
final class STPPI_Renderer {
    private static $csp_nonce = '';
    public static function csp_nonce() {
        if (self::$csp_nonce === '') {
            try { self::$csp_nonce = base64_encode(random_bytes(18)); }
            catch (Exception $e) { self::$csp_nonce = str_replace('-', '', wp_generate_uuid4()); }
        }
        return self::$csp_nonce;
    }
    public static function config($program_id='') {
        $registry=STPPI_Repository::registry();
        if($program_id && isset($registry[$program_id]) && is_array($registry[$program_id])) return $registry[$program_id];
        return array('enabled'=>false,'id'=>'','slug'=>'','post_id'=>0,'hash'=>'','hotel_hash'=>'','seo'=>true,'mode'=>'prepared');
    }
    public static function dependencies() { return STPPI_Repository::dependencies(); }
    public static function url($c) { return home_url(STPPI_ROUTE_BASE . $c['slug'] . '/'); }
    public static function valid_slug($slug) { return is_string($slug) && strlen($slug) <= 80 && preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', $slug); }
    public static function route_allowed($c,$uri,$method) {
        if (!stppi_public_master_enabled() || (($c['mode']??'')!=='public_noindex') || !self::valid_slug($c['slug']??'') || !in_array($method,array('GET','HEAD'),true)) return false;
        if (!empty($_SERVER['QUERY_STRING'])) return false;
        return $uri === (string)parse_url(self::url($c),PHP_URL_PATH);
    }
    public static function model($c, $pinned = true) {
        $dep = self::dependencies(); if (is_wp_error($dep)) { return $dep; }
        if (!preg_match('/^STP-[0-9]{6}$/D', (string)$c['id']) || in_array($c['id'], array('STP-000036','STP-000037'), true)) { return new WP_Error('id', 'Gerçek bir program seçin; test kayıtları kullanılamaz.'); }
        $ids = get_posts(array('post_type'=>'stpi_program', 'post_status'=>array('private','publish','draft','pending','future','trash'), 'posts_per_page'=>2, 'fields'=>'ids', 'meta_key'=>'_stpi_program_id', 'meta_value'=>$c['id'], 'no_found_rows'=>true));
        if (count($ids) !== 1) { return new WP_Error('identity', 'Program kimliği bulunamadı veya birden fazla kayıt var.'); }
        $post = get_post((int)$ids[0]);
        $p = $post ? json_decode($post->post_content, true) : null;
        if (!$post || $post->post_type !== 'stpi_program' || $post->post_status !== 'private' || !is_array($p) || ($p['program_id']??'') !== $c['id']) { return new WP_Error('identity', 'Özel program kaydı ile kimlik eşleşmiyor.'); }
        foreach (array('workflow','schedule','destinations','segments','stays','pricing') as $field) { if (!isset($p[$field]) || !is_array($p[$field])) { return new WP_Error('shape', 'Program veri yapısı eksik.'); } }
        if (stripos((string)($p['program_code']??''), 'TEST') !== false || stripos((string)($p['provenance']['document_ref']??''), 'TEST') !== false) { return new WP_Error('fixture', 'Test kaydı yayınlanamaz.'); }
        if (($p['workflow']['editorial']??'') !== 'approved' || ($p['workflow']['schedule']??'') !== 'scheduled' || !in_array($p['workflow']['availability']??'', array('open','limited','on_request','sold_out'), true)) { return new WP_Error('state', 'Onaylı ve planlanmış gerçek program gerekli.'); }
        if (!in_array($p['service_type']??'', array('umrah','cultural_tour'), true) || ($p['locale']??'') !== 'tr_TR' || ($p['publication_mode']??'') !== 'scheduled_departure') { return new WP_Error('scope', 'Pilot yalnızca Türkçe, tarihli umre/kültür programlarını destekler.'); }
        $start=$p['schedule']['start_date']??''; $end=$p['schedule']['end_date']??'';
        foreach (array($start,$end) as $d) { $v=is_string($d)?DateTimeImmutable::createFromFormat('!Y-m-d',$d):false; if (!$v || $v->format('Y-m-d')!==$d) { return new WP_Error('dates','Kesin başlangıç ve bitiş tarihleri gerekli.'); } }
        $days=(int)(new DateTimeImmutable($start))->diff(new DateTimeImmutable($end))->format('%r%a');
        $temporal = STPI_Lifecycle::temporal_state($p);
        if ($days<1 || ($p['schedule']['date_precision']??'')!=='exact' || (int)($p['schedule']['duration_nights']??-1)!==$days || (int)($p['schedule']['duration_days']??0)!==$days+1 || !in_array($temporal,array('upcoming','in_progress'),true)) { return new WP_Error('dates','Tarih/süre tutarsız veya program artık güncel/gelecek durumda değil.'); }
        if (empty($p['title']) || empty($p['destinations']) || empty($p['stays'])) { return new WP_Error('content', 'Başlık, rota ve konaklama bilgileri gerekli.'); }
        $hotels=array(); $nights=0;
        foreach ($p['destinations'] as $d) { if (!is_array($d) || empty($d['city']) || !is_string($d['city']) || empty($d['country']) || !is_string($d['country'])) { return new WP_Error('destination','Rota alanları eksik.'); } }
        $last_checkout=$start;
        foreach ($p['stays'] as $stay) {
            if (!is_array($stay) || !preg_match('/^STH-[0-9]{6}$/D',(string)($stay['hotel_id']??''))) { return new WP_Error('hotel', 'Geçerli STH otel bağlantısı gerekli.'); }
            $id=$stay['hotel_id']; if (!isset($hotels[$id])) { $hotels[$id]=STPI_Hotel_Adapter::resolve($id); }
            $h=$hotels[$id];
            if (($h['status']??'')!=='resolved' || ($h['verification_status']??'')!=='verified' || ($h['workflow_status']??'')!=='published' || !self::local_url($h['public_url']??'') || empty($h['name'])) { return new WP_Error('hotel', 'Otel doğrulaması/yayın bağlantısı hazır değil: '.$id); }
            $nights+=(int)($stay['nights']??0);
            $in=$stay['check_in']??''; $out=$stay['check_out']??'';
            foreach(array($in,$out) as $d) { $v=is_string($d)?DateTimeImmutable::createFromFormat('!Y-m-d',$d):false; if(!$v || $v->format('Y-m-d')!==$d) { return new WP_Error('stay_dates','Otel giriş/çıkış tarihleri gerekli.'); } }
            $stay_nights=(int)(new DateTimeImmutable($in))->diff(new DateTimeImmutable($out))->format('%r%a');
            if ($in!==$last_checkout || $out>$end || $stay_nights<1 || $stay_nights!==(int)($stay['nights']??0)) { return new WP_Error('stay_dates','Otel tarihleri sıralı ve programla uyumlu olmalı.'); }
            $last_checkout=$out;
        }
        if ($nights!==$days || $last_checkout!==$end) { return new WP_Error('nights','Otel geceleri program süresiyle eşleşmiyor.'); }
        foreach(array('itinerary_days','inclusions','exclusions') as $f) { if(isset($p[$f]) && !is_array($p[$f])) { return new WP_Error('shape','Program listesi hatalı.'); } }
        foreach($p['segments'] as $s) { if(!is_array($s)) { return new WP_Error('segment','Ulaşım alanı hatalı.'); } }
        foreach(($p['itinerary_days']??array()) as $d) { if(!is_array($d) || empty($d['day']) || empty($d['title'])) { return new WP_Error('day','Günlük plan hatalı.'); } }
        if (!in_array($p['pricing']['price_type']??'',array('fixed','from','on_request'),true) || !preg_match('/^[A-Z]{3}$/D',(string)($p['pricing']['currency']??'')) || !is_array($p['pricing']['entries']??null)) { return new WP_Error('pricing','Fiyat bilgisi eksik.'); }
        if (($p['pricing']['price_type']??'')!=='on_request' && empty($p['pricing']['entries'])) { return new WP_Error('pricing','Fiyat satırı gerekli.'); }
        foreach ($p['pricing']['entries'] as $price) { if (!is_array($price) || !is_numeric($price['amount']??null) || !is_finite((float)$price['amount']) || (float)$price['amount']<=0 || !in_array($price['unit']??'',array('per_person','per_room','per_group'),true)) { return new WP_Error('price','Fiyat/birim doğrulaması gerekli.'); } }
        foreach(array('valid_from','valid_until') as $f) { if(!empty($p['pricing'][$f])) { $d=$p['pricing'][$f];$v=is_string($d)?DateTimeImmutable::createFromFormat('!Y-m-d',$d):false; if(!$v || $v->format('Y-m-d')!==$d || ($f==='valid_until' && $d<wp_date('Y-m-d',null,new DateTimeZone('Europe/Istanbul'))) || ($f==='valid_from' && $d>wp_date('Y-m-d',null,new DateTimeZone('Europe/Istanbul')))) { return new WP_Error('price_dates','Fiyat geçerlilik tarihi uygun değil.'); } } }
        foreach(array('child_rules','surcharges') as $f) { if(isset($p['pricing'][$f]) && !is_array($p['pricing'][$f])) { return new WP_Error('pricing','Ek fiyat listesi hatalı.'); } }
        foreach(($p['pricing']['child_rules']??array()) as $r) {
            if(!is_array($r) || !is_numeric($r['min_age']??null) || !is_numeric($r['max_age']??null) || $r['min_age']<0 || $r['max_age']<$r['min_age'] || !is_bool($r['bed_included']??null) || !in_array($r['pricing_method']??'',array('fixed','discount','on_request'),true)) { return new WP_Error('child','Çocuk fiyat kuralı eksik.'); }
            $key=($r['pricing_method']==='fixed')?'amount':'discount_amount';
            if($r['pricing_method']!=='on_request' && (!is_numeric($r[$key]??null) || $r[$key]<0)) { return new WP_Error('child','Çocuk fiyat tutarı eksik.'); }
        }
        foreach(($p['pricing']['surcharges']??array()) as $r) { if(!is_array($r) || empty($r['label']) || !is_numeric($r['amount']??null) || $r['amount']<0) { return new WP_Error('surcharge','Ek ücret bilgisi eksik.'); } }
        $hash=STPI_Contract::payload_hash($p); $hotel_hash=STPI_Contract::payload_hash($hotels);
        if ($pinned && ((int)$c['post_id']!==(int)$post->ID || !hash_equals((string)$c['hash'],$hash) || !hash_equals((string)$c['hotel_hash'],$hotel_hash))) { return new WP_Error('changed','Veriler değişti. Önizleyip yeniden kaydedin; pilot yeniden açılana kadar kapalıdır.'); }
        // Public projection is explicit. No provenance, internal notes, IDs, hashes, capacities or raw JSON is rendered.
        return array('program'=>$p,'hotels'=>$hotels,'post_id'=>(int)$post->ID,'hash'=>$hash,'hotel_hash'=>$hotel_hash);
    }
    public static function local_url($url) {
        $a=wp_parse_url((string)$url); $b=wp_parse_url(home_url('/'));
        $host_a=strtolower((string)($a['host']??'')); $host_b=strtolower((string)($b['host']??''));
        if (strpos($host_a,'www.')===0) {$host_a=substr($host_a,4);} if (strpos($host_b,'www.')===0) {$host_b=substr($host_b,4);}
        return is_array($a) && ($a['scheme']??'')==='https' && $host_a!=='' && hash_equals($host_b,$host_a) && ($a['port']??443)===($b['port']??443) && empty($a['user']) && empty($a['pass']) && empty($a['query']) && empty($a['fragment']);
    }
    public static function media_url($url) {
        if (!self::local_url($url)) { return ''; }
        $source=wp_parse_url((string)$url); $home=wp_parse_url(home_url('/'));
        if (!is_array($source) || !is_array($home) || empty($home['host'])) { return ''; }
        $scheme='https'; $host=(string)$home['host']; $port=isset($home['port']) ? ':' . (int)$home['port'] : '';
        $path=(string)($source['path']??'/'); if ($path==='' || $path[0]!=='/') { $path='/'.$path; }
        return $scheme.'://'.$host.$port.$path;
    }
    public static function geography($value) {
        $key=strtolower(trim((string)$value));
        $map=array('madinah'=>'Medine','medina'=>'Medine','makkah'=>'Mekke','mecca'=>'Mekke','saudi arabia'=>'Suudi Arabistan','egypt'=>'Mısır','cairo'=>'Kahire','istanbul'=>'İstanbul');
        return $map[$key]??self::text($value);
    }
    public static function display_datetime($value) {
        $raw=trim((string)$value);
        if ($raw==='') { return 'Bilgi için danışın'; }
        try { $dt=new DateTimeImmutable($raw); } catch (Exception $e) { return 'Bilgi için danışın'; }
        try { $dt=$dt->setTimezone(new DateTimeZone('Europe/Istanbul')); } catch (Exception $e) { return 'Bilgi için danışın'; }
        return $dt->format('H:i:s')==='00:00:00' ? $dt->format('d.m.Y') : $dt->format('d.m.Y · H:i');
    }
    public static function headers($status=200) {
        status_header($status); nocache_headers();
        header('Cache-Control: private, no-store, no-cache, max-age=0, must-revalidate', true);
        header('X-Robots-Tag: noindex, nofollow, noarchive, nosnippet',true);
        header('Referrer-Policy: no-referrer',true); header('X-Content-Type-Options: nosniff',true); header('X-Frame-Options: DENY',true);
        $nonce = self::csp_nonce();
        header("Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline' https://fonts.googleapis.com; font-src https://fonts.gstatic.com; img-src 'self'; script-src 'nonce-" . $nonce . "'; connect-src 'none'; base-uri 'none'; frame-ancestors 'none'; form-action 'none'",true);
        header('Content-Type: text/html; charset=UTF-8',true);
    }
    public static function dispatch() {
        stppi_upgrade_guard();
        $path=(string)parse_url($_SERVER['REQUEST_URI']??'',PHP_URL_PATH);
        $method=$_SERVER['REQUEST_METHOD']??'';
        if(!in_array($method,array('GET','HEAD'),true)){ self::headers(405); header('Allow: GET, HEAD',true); if($method!=='HEAD') echo '<!doctype html><html lang="tr"><meta charset="utf-8"><meta name="robots" content="noindex,nofollow"><title>İstek desteklenmiyor</title><h1>İstek desteklenmiyor</h1></html>'; exit; }
        $registry=STPPI_Repository::registry(); $match=null;
        foreach($registry as $c){ if(!is_array($c) || empty($c['slug'])) continue; if($path===(string)parse_url(self::url($c),PHP_URL_PATH)){$match=$c;break;} }
        if(!$match || !self::route_allowed($match,$_SERVER['REQUEST_URI']??'',$method)) self::not_found();
        $m=self::model($match); if(is_wp_error($m)) self::not_found();

        // Canonical-host gate. Final routes execute before WordPress' normal canonical
        // redirect, so a request to the non-canonical host (for example bare domain
        // while home_url() uses www) would otherwise render under a different origin.
        // The strict CSP uses img-src 'self', and the shared Header/Footer plus media
        // URLs are generated from home_url(); rendering on the alternate host can
        // therefore make same-site images look cross-origin and get blocked.
        // Redirect only an already-authorized, exact, query-free route to the fixed
        // WordPress home_url() origin. Never derive the destination from Host input.
        $canonical = self::url($match);
        $canonical_parts = wp_parse_url($canonical);
        $request_host = strtolower(trim((string)($_SERVER['HTTP_HOST'] ?? '')));
        $request_host = preg_replace('/:\d+$/', '', $request_host);
        $canonical_host = strtolower((string)($canonical_parts['host'] ?? ''));
        if ($canonical_host !== '' && $request_host !== '' && !hash_equals($canonical_host, $request_host)) {
            wp_safe_redirect($canonical, 308, 'STPPI');
            exit;
        }

        self::headers(); if($method==='HEAD') exit; self::render($m,$match,false); exit;
    }
    public static function not_found() {
        self::headers(404);
        if (($_SERVER['REQUEST_METHOD']??'')!=='HEAD') { echo '<!doctype html><html lang="tr"><meta charset="utf-8"><meta name="robots" content="noindex,nofollow"><title>Sayfa bulunamadı</title><h1>Sayfa bulunamadı</h1></html>'; } exit;
    }
    public static function text($s) { return is_scalar($s) ? trim(wp_strip_all_tags((string)$s)) : ''; }
    public static function seo($m,$c) {
        $p=$m['program']; $url=self::url($c); $title=self::text($p['seo']['seo_title']??'') ?: self::text($p['title']).' | Server Turizm';
        $desc=self::text($p['seo']['meta_description']??'') ?: self::text($p['title']).': '.$p['schedule']['duration_days'].' gün, '.$p['schedule']['duration_nights'].' gece. Konaklama, rota ve fiyat seçeneklerini inceleyin.';
        $places=array(); foreach ($p['destinations'] as $i=>$d) { $places[]=array('@type'=>'ListItem','position'=>$i+1,'item'=>array('@type'=>'Place','name'=>self::text($d['city']??''))); }
        $trip=array('@type'=>'TouristTrip','@id'=>$url.'#trip','name'=>self::text($p['title']),'description'=>$desc,'url'=>$url,'mainEntityOfPage'=>array('@id'=>$url.'#webpage'),'provider'=>array('@id'=>home_url('/').'#organization'),'itinerary'=>array('@type'=>'ItemList','itemListElement'=>$places));
        // No offers/ratings/datetime inference: itinerary facts only in this noindex parity pilot.
        $graph=array('@context'=>'https://schema.org','@graph'=>array(array('@type'=>'WebPage','@id'=>$url.'#webpage','url'=>$url,'name'=>$title,'description'=>$desc,'inLanguage'=>'tr-TR','mainEntity'=>array('@id'=>$url.'#trip')),array('@type'=>'TravelAgency','@id'=>home_url('/').'#organization','name'=>'Server Turizm','url'=>home_url('/')),$trip));
        return array('title'=>$title,'description'=>$desc,'url'=>$url,'graph'=>$graph);
    }
    public static function render($model,$config,$private) { $seo=self::seo($model,$config); require STPPI_DIR.'templates/program.php'; }
}
