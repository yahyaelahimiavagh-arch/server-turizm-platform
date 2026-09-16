<?php
/**
 * STTI v1.1.3 — Operator-first Review Queue.
 *
 * Read-only review summary layer over canonical Tour records. It does not
 * approve, mutate, publish, index or duplicate Tour data.
 */
if (!defined('ABSPATH')) { exit; }

function stti_v113_review_queue_is_screen() {
    if (!is_admin()) return false;
    $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
    $view = isset($_GET['view']) ? sanitize_key(wp_unslash($_GET['view'])) : 'dashboard';
    return $page === 'stti-tour-intelligence' && $view === 'review';
}

function stti_v113_review_queue_text($value) {
    return is_scalar($value) ? trim((string) $value) : '';
}

function stti_v113_review_queue_has_blocker($blockers, $needle) {
    foreach ((array) $blockers as $blocker) {
        if (stripos((string) $blocker, $needle) !== false) return true;
    }
    return false;
}

function stti_v113_review_queue_date_label($payload) {
    $date = is_array($payload['date'] ?? null) ? $payload['date'] : array();
    $start = stti_v113_review_queue_text($date['start_date'] ?? '');
    $end = stti_v113_review_queue_text($date['end_date'] ?? '');
    $mode = stti_v113_review_queue_text($date['mode'] ?? '');
    if ($start !== '' && $end !== '') return $start . ' → ' . $end;
    if ($start !== '') return $start;
    if ($mode === 'coming_soon') return 'Tarih yakında';
    return 'Tarih eksik / belirsiz';
}

function stti_v113_review_queue_price_label($payload) {
    $pricing = is_array($payload['pricing'] ?? null) ? $payload['pricing'] : array();
    $type = stti_v113_review_queue_text($pricing['type'] ?? '');
    $amount = $pricing['amount'] ?? null;
    $currency = stti_v113_review_queue_text($pricing['currency'] ?? '');
    if ($amount !== null && $amount !== '') {
        $prefix = $type === 'from' ? 'Başlangıç ' : '';
        return $prefix . rtrim(rtrim(number_format((float) $amount, 2, '.', ''), '0'), '.') . ($currency !== '' ? ' ' . $currency : '');
    }
    if ($type === 'on_request') return 'Talep üzerine';
    return 'Fiyat belirtilmemiş';
}

function stti_v113_review_queue_summary($row) {
    $payload = json_decode((string) ($row['payload'] ?? ''), true);
    if (!is_array($payload)) $payload = array();

    $relations = function_exists('stti_v070_relation_review')
        ? stti_v070_relation_review($payload)
        : array('ready_for_editorial_approval'=>false,'counts'=>array(),'warnings'=>array(),'blockers'=>array('Relation review unavailable.'));

    $geo = function_exists('stti_v071_geo_review')
        ? stti_v071_geo_review($payload)
        : array('ready_for_renderer'=>false,'counts'=>array(),'warnings'=>array(),'blockers'=>array('Geo review unavailable.'));

    $relation_blockers = array_values(array_unique(array_map('strval', (array) ($relations['blockers'] ?? array()))));
    $geo_blockers = array_values(array_unique(array_map('strval', (array) ($geo['blockers'] ?? array()))));
    $hard_blockers = array_values(array_unique(array_merge($relation_blockers, $geo_blockers)));

    $warnings = array_values(array_unique(array_merge(
        array_map('strval', (array) ($relations['warnings'] ?? array())),
        array_map('strval', (array) ($geo['warnings'] ?? array()))
    )));

    $identity = is_array($payload['identity'] ?? null) ? $payload['identity'] : array();
    $destinations = is_array($payload['destinations'] ?? null) ? $payload['destinations'] : array();
    $route = is_array($payload['route'] ?? null) ? $payload['route'] : array();
    $stops = is_array($route['stops'] ?? null) ? array_values($route['stops']) : array();
    $itinerary = is_array($payload['itinerary'] ?? null) ? array_values($payload['itinerary']) : array();
    $pricing = is_array($payload['pricing'] ?? null) ? $payload['pricing'] : array();
    $provenance = is_array($payload['provenance'] ?? null) ? $payload['provenance'] : array();

    $source = stti_v113_review_queue_text($row['source_completeness'] ?? ($provenance['source_completeness'] ?? 'source_minimal'));
    $source_type = stti_v113_review_queue_text($provenance['source_type'] ?? 'unknown');
    $source_ref = stti_v113_review_queue_text($provenance['source_ref'] ?? '');

    $title = stti_v113_review_queue_text($row['public_title'] ?? ($identity['public_title'] ?? ''));
    $country = stti_v113_review_queue_text($destinations['primary_country'] ?? '');
    $route_summary = stti_v113_review_queue_text($route['summary'] ?? '');
    $price_amount = $pricing['amount'] ?? null;
    $price_type = stti_v113_review_queue_text($pricing['type'] ?? '');

    $relation_counts = is_array($relations['counts'] ?? null) ? $relations['counts'] : array();
    $geo_counts = is_array($geo['counts'] ?? null) ? $geo['counts'] : array();
    $hotel_count = (int) ($relation_counts['hotel_relations'] ?? 0);
    $transport_count = (int) ($relation_counts['transport_segments'] ?? 0);
    $confirmed_geo = (int) ($geo_counts['confirmed'] ?? 0);

    $checks = array();
    $checks[] = array(
        'key'=>'source','label'=>'Kaynak','tone'=>$source === 'source_complete' ? 'ok' : 'warn',
        'state'=>$source === 'source_complete' ? 'Tam' : ($source === 'source_partial' ? 'Kısmi' : 'Minimal'),
        'detail'=>trim($source_type . ($source_ref !== '' ? ' · ' . $source_ref : '')),
    );
    $checks[] = array(
        'key'=>'route','label'=>'Rota','tone'=>($route_summary !== '' || count($stops) > 0) ? 'ok' : 'warn',
        'state'=>count($stops) > 0 ? count($stops) . ' durak' : ($route_summary !== '' ? 'Özet var' : 'Belirsiz'),
        'detail'=>$route_summary !== '' ? $route_summary : 'Kaynakta doğrulanmış rota özeti yok.',
    );
    $checks[] = array(
        'key'=>'geo','label'=>'Konum / Geo','tone'=>!empty($geo['ready_for_renderer']) ? 'ok' : 'block',
        'state'=>!empty($geo['ready_for_renderer']) ? 'Hazır' : ($confirmed_geo . '/' . count($stops) . ' doğrulandı'),
        'detail'=>!empty($geo['ready_for_renderer']) ? 'Tüm aktif rota durakları doğrulandı.' : 'Eksik veya onaysız koordinatlar var.',
    );
    $checks[] = array(
        'key'=>'hotel','label'=>'Oteller','tone'=>stti_v113_review_queue_has_blocker($relation_blockers, 'Hotel relation') ? 'block' : ($hotel_count > 0 ? 'ok' : 'neutral'),
        'state'=>$hotel_count > 0 ? $hotel_count . ' ilişki' : 'İlişki yok',
        'detail'=>$hotel_count > 0 ? 'Canonical hotel relation kayıtları mevcut.' : 'Kaynakta doğrulanmış otel ilişkisi yoksa boş kalabilir.',
    );
    $checks[] = array(
        'key'=>'transport','label'=>'Ulaşım','tone'=>stti_v113_review_queue_has_blocker($relation_blockers, 'Transport segment') ? 'block' : ($transport_count > 0 ? 'ok' : 'neutral'),
        'state'=>$transport_count > 0 ? $transport_count . ' segment' : 'Segment yok',
        'detail'=>$transport_count > 0 ? 'Canonical transport segment kayıtları mevcut.' : 'Kaynakta doğrulanmış ulaşım segmenti yoksa boş kalabilir.',
    );
    $checks[] = array(
        'key'=>'date','label'=>'Tarih','tone'=>(!empty($payload['date']['start_date']) && !empty($payload['date']['end_date'])) ? 'ok' : 'warn',
        'state'=>stti_v113_review_queue_date_label($payload),
        'detail'=>'Schedule: ' . strtoupper(stti_v113_review_queue_text($row['schedule_status'] ?? 'unknown')),
    );
    $checks[] = array(
        'key'=>'price','label'=>'Fiyat','tone'=>($price_amount !== null && $price_amount !== '') || $price_type === 'on_request' ? 'ok' : 'warn',
        'state'=>stti_v113_review_queue_price_label($payload),
        'detail'=>'Basis: ' . strtoupper(stti_v113_review_queue_text($pricing['basis'] ?? 'unknown')),
    );
    $checks[] = array(
        'key'=>'itinerary','label'=>'Gün Gün Program','tone'=>count($itinerary) > 0 ? 'ok' : 'neutral',
        'state'=>count($itinerary) > 0 ? count($itinerary) . ' gün/kayıt' : 'Kayıt yok',
        'detail'=>'Kaynakta doğrulanmış günlük program yoksa boş kalabilir.',
    );

    if ($title === '') $hard_blockers[] = 'Tour title is missing.';
    $hard_blockers = array_values(array_unique($hard_blockers));

    $approval_ready = !$hard_blockers
        && !empty($relations['ready_for_editorial_approval'])
        && !empty($geo['ready_for_renderer'])
        && $title !== '';

    $stable_id = stti_v113_review_queue_text($row['stable_id'] ?? '');
    return array(
        'stableId'=>$stable_id,
        'title'=>$title,
        'country'=>$country !== '' ? $country : 'Hedef belirsiz',
        'editorial'=>stti_v113_review_queue_text($row['editorial'] ?? 'needs_review'),
        'schedule'=>stti_v113_review_queue_text($row['schedule_status'] ?? ''),
        'availability'=>stti_v113_review_queue_text($row['availability'] ?? ''),
        'sourceCompleteness'=>$source,
        'approvalReady'=>$approval_ready,
        'hardBlockerCount'=>count($hard_blockers),
        'warningCount'=>count($warnings),
        'checks'=>$checks,
        'blockers'=>$hard_blockers,
        'warnings'=>$warnings,
        'checksum'=>substr(stti_v113_review_queue_text($row['checksum'] ?? ''), 0, 12),
        'updatedAt'=>stti_v113_review_queue_text($row['updated_at'] ?? ''),
        'editUrl'=>stti_view_url('editor', array('tour'=>$stable_id)),
        'approvalUrl'=>function_exists('stti_v115_approval_url') ? stti_v115_approval_url($stable_id) : '',
        'previewUrl'=>function_exists('stti_customer_preview_url') ? stti_customer_preview_url($stable_id) : '',
    );
}

function stti_v113_review_queue_assets() {
    if (!stti_v113_review_queue_is_screen()) return;

    wp_enqueue_style('stti-review-queue-v113', STTI_URL . 'assets/review-queue-v113.css', array('stti-admin'), STTI_RELEASE_VERSION);
    wp_enqueue_script('stti-review-queue-v113', STTI_URL . 'assets/review-queue-v113.js', array('stti-admin'), STTI_RELEASE_VERSION, true);

    $items = array();
    foreach (stti_get_candidates() as $row) {
        if (($row['editorial'] ?? '') !== 'needs_review') continue;
        $items[] = stti_v113_review_queue_summary($row);
    }

    wp_localize_script('stti-review-queue-v113', 'STTI_V113_REVIEW_QUEUE', array(
        'version'=>STTI_RELEASE_VERSION,
        'items'=>$items,
        'publicLocks'=>array('publicRoute'=>false,'indexation'=>false,'sitemap'=>false,'hubMasterUnchanged'=>true),
    ));
}
add_action('admin_enqueue_scripts', 'stti_v113_review_queue_assets', 140);
