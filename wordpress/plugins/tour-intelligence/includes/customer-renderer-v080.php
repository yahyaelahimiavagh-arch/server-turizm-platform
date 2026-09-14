<?php
/** STTI v0.8.0 — Complete Customer Renderer. Private/read-only only. */
if (!defined('ABSPATH')) { exit; }

function stti_v080_stop_index($payload) {
    $stops = is_array($payload['route']['stops'] ?? null) ? array_values($payload['route']['stops']) : array();
    $out = array();
    foreach ($stops as $stop) {
        if (!is_array($stop)) continue;
        $id = stti_v070_text($stop['stop_id'] ?? '');
        if ($id !== '') $out[$id] = $stop;
    }
    return $out;
}

function stti_v080_variant_state($payload) {
    $variants = stti_v070_normalize_route_variants($payload['route']['variants'] ?? array());
    if (!$variants) return array('mode'=>'legacy_base_route','active'=>null,'confirmed'=>array(),'alternatives'=>array());
    $confirmed = array_values(array_filter($variants, static fn($v) => ($v['review_status'] ?? 'pending') === 'confirmed'));
    $primary = array_values(array_filter($confirmed, static fn($v) => ($v['role'] ?? 'unknown') === 'primary'));
    $alternatives = array_values(array_filter($confirmed, static fn($v) => ($v['role'] ?? 'unknown') === 'alternative'));
    return array(
        'mode'=>count($primary)===1?'reviewed_primary':'no_primary_selected',
        'active'=>count($primary)===1?$primary[0]:null,
        'confirmed'=>$confirmed,
        'alternatives'=>$alternatives,
    );
}

function stti_v080_route_stops($payload, $variant_state = null) {
    $variant_state = is_array($variant_state) ? $variant_state : stti_v080_variant_state($payload);
    $index = stti_v080_stop_index($payload);
    if (($variant_state['mode'] ?? '') === 'legacy_base_route') return array_values($index);
    $active = $variant_state['active'] ?? null;
    if (!is_array($active)) return array();
    $out = array();
    foreach (($active['stop_refs'] ?? array()) as $ref) {
        $ref = stti_v070_text($ref);
        if ($ref !== '' && isset($index[$ref])) $out[] = $index[$ref];
    }
    return $out;
}

function stti_v080_relation_ref_set($variant_state, $field) {
    $active = is_array($variant_state['active'] ?? null) ? $variant_state['active'] : null;
    if (!$active) return null;
    return array_fill_keys(stti_v070_ref_list($active[$field] ?? array()), true);
}

function stti_v080_hotel_facts($relation) {
    $relation = is_array($relation) ? $relation : array();
    $fact = array(
        'relation_id'=>stti_v070_text($relation['relation_id'] ?? ''),
        'selection_status'=>stti_v070_hotel_selection_status($relation['selection_status'] ?? 'unknown'),
        'mode'=>stti_v070_text($relation['mode'] ?? 'unresolved'),
        'hotel_stable_id'=>strtoupper(stti_v070_text($relation['hotel_stable_id'] ?? '')),
        'name'=>'','city'=>stti_v070_text($relation['city'] ?? ''),'stars'=>'','image'=>'',
        'identity_status'=>'unresolved','source'=>'tour_relation',
    );
    if ($fact['mode'] !== 'hotel_intelligence') {
        $fact['name'] = stti_v070_text($relation['unresolved_name'] ?? '');
        return $fact;
    }
    $state = stti_v070_hotel_link_state($fact['hotel_stable_id']);
    $fact['identity_status'] = (string)($state['status'] ?? 'unresolved');
    if ($fact['identity_status'] !== 'resolved' || empty($state['post_id'])) return $fact;
    $post_id = (int)$state['post_id'];
    $display = function_exists('get_post_meta') ? stti_v070_text(get_post_meta($post_id, '_sthi_display_name_tr', true)) : '';
    $title = function_exists('get_the_title') ? stti_v070_text(get_the_title($post_id)) : '';
    $fact['name'] = $display !== '' ? $display : $title;
    if (function_exists('get_post_meta')) {
        $city = stti_v070_text(get_post_meta($post_id, '_sthi_city', true));
        if ($city !== '') $fact['city'] = $city;
        $fact['stars'] = stti_v070_text(get_post_meta($post_id, '_sthi_star_rating', true));
    }
    if (function_exists('get_the_post_thumbnail_url')) {
        $image = get_the_post_thumbnail_url($post_id, 'large');
        $fact['image'] = is_string($image) ? $image : '';
    }
    $fact['source'] = 'hotel_intelligence_live_read';
    return $fact;
}

function stti_v080_renderable_hotels($payload, $variant_state = null) {
    $variant_state = is_array($variant_state) ? $variant_state : stti_v080_variant_state($payload);
    $relations = stti_v070_normalize_hotel_relations($payload['stays']['hotels'] ?? array());
    $scope = stti_v080_relation_ref_set($variant_state, 'hotel_relation_refs');
    $out = array();
    foreach ($relations as $relation) {
        if (($relation['review_status'] ?? 'pending') !== 'confirmed') continue;
        $id = (string)($relation['relation_id'] ?? '');
        if (is_array($scope) && !isset($scope[$id])) continue;
        if ($scope === null && ($variant_state['mode'] ?? '') !== 'legacy_base_route') continue;
        if ($scope === null && ($relation['selection_status'] ?? 'unknown') === 'unknown') continue;
        $out[] = stti_v080_hotel_facts($relation);
    }
    usort($out, static function($a,$b){
        $rank=array('selected'=>0,'alternative'=>1,'unknown'=>2);
        return ($rank[$a['selection_status']]??9) <=> ($rank[$b['selection_status']]??9);
    });
    return $out;
}

function stti_v080_renderable_transport($payload, $variant_state = null) {
    $variant_state = is_array($variant_state) ? $variant_state : stti_v080_variant_state($payload);
    $segments = stti_v070_normalize_transport_relations($payload['transport']['segments'] ?? array());
    $scope = stti_v080_relation_ref_set($variant_state, 'transport_segment_refs');
    $stops = stti_v080_stop_index($payload);
    $out = array();
    foreach ($segments as $segment) {
        if (($segment['review_status'] ?? 'pending') !== 'confirmed') continue;
        $id = (string)($segment['segment_id'] ?? '');
        if (is_array($scope) && !isset($scope[$id])) continue;
        if ($scope === null && ($variant_state['mode'] ?? '') !== 'legacy_base_route') continue;
        $from_ref=stti_v070_text($segment['from_stop_ref'] ?? '');
        $to_ref=stti_v070_text($segment['to_stop_ref'] ?? '');
        $from=$from_ref!==''&&isset($stops[$from_ref])?$stops[$from_ref]:array();
        $to=$to_ref!==''&&isset($stops[$to_ref])?$stops[$to_ref]:array();
        $segment['from_label']=stti_v070_text($from['city'] ?? ($from['label'] ?? ($segment['from'] ?? '')));
        $segment['to_label']=stti_v070_text($to['city'] ?? ($to['label'] ?? ($segment['to'] ?? '')));
        $out[]=$segment;
    }
    return $out;
}

function stti_v080_map_config($payload, $stable_id, $checksum, $variant_state = null) {
    $variant_state=is_array($variant_state)?$variant_state:stti_v080_variant_state($payload);
    $stops=stti_v080_route_stops($payload,$variant_state);
    $geo=stti_v071_normalize_geo_records($payload['geo']['route_stops'] ?? array());
    $by_ref=array(); foreach($geo as $r) if(($r['stop_ref']??'')!=='') $by_ref[$r['stop_ref']]=$r;
    $points=array();$unresolved=array();
    foreach($stops as $i=>$stop){
        $ref=stti_v070_text($stop['stop_id']??'');
        $label=stti_v070_text($stop['city']??($stop['label']??'Durak'));
        $r=$ref!==''?($by_ref[$ref]??null):null;
        if(!$r||($r['state']??'')!=='resolved'||($r['review_status']??'')!=='confirmed'){
            $unresolved[]=array('order'=>$i+1,'stop_id'=>$ref,'name'=>$label,'reason'=>'geo_not_confirmed');continue;
        }
        $points[]=array('order'=>$i+1,'stop_id'=>$ref,'name'=>$label,'lat'=>(float)$r['latitude'],'lng'=>(float)$r['longitude'],'source_type'=>(string)$r['source_type'],'reviewed_at'=>$r['reviewed_at']??null);
    }
    return array(
        'contract'=>'STTI-CUSTOMER-RENDERER-1.0.0','stableId'=>(string)$stable_id,'checksum'=>(string)$checksum,
        'variantMode'=>(string)($variant_state['mode']??''),
        'variantId'=>is_array($variant_state['active']??null)?(string)($variant_state['active']['variant_id']??''):'',
        'stops'=>$points,'unresolved'=>$unresolved,
        'tiles'=>'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png','tileAttribution'=>'&copy; OpenStreetMap contributors',
        'maxZoom'=>13,'minZoom'=>2,'segmentDurationMs'=>700,'markerRevealMs'=>160,
    );
}

function stti_v080_renderer_model($payload, $stable_id = '', $checksum = '') {
    $payload=is_array($payload)?$payload:array();
    $variant_state=stti_v080_variant_state($payload);
    $relations=stti_v070_relation_review($payload);
    $geo=stti_v071_geo_review($payload);
    return array(
        'contract'=>'STTI-CUSTOMER-RENDERER-1.0.0','stable_id'=>(string)$stable_id,'checksum'=>(string)$checksum,
        'variant_state'=>$variant_state,'route_stops'=>stti_v080_route_stops($payload,$variant_state),
        'hotels'=>stti_v080_renderable_hotels($payload,$variant_state),'transport'=>stti_v080_renderable_transport($payload,$variant_state),
        'map'=>stti_v080_map_config($payload,$stable_id,$checksum,$variant_state),
        'relation_status'=>(string)($relations['status']??'pending'),'geo_status'=>(string)($geo['status']??'pending'),
        'ready'=>(($relations['ready_for_editorial_approval']??false)===true&&($geo['ready_for_renderer']??false)===true),
        'public'=>false,'indexable'=>false,'sitemap'=>false,'schema'=>false,'canonical'=>false,'writes'=>0,
    );
}

function stti_v080_scalar($value){$value=trim((string)$value);return $value===''?'':$value;}
function stti_v080_stop_label($stop){if(!is_array($stop))return'';$city=stti_v080_scalar($stop['city']??'');return $city!==''?$city:stti_v080_scalar($stop['label']??'');}

function stti_v080_prepare_private_surface(){
    remove_action('wp_head','rel_canonical');remove_action('wp_head','wp_shortlink_wp_head',10);
    add_filter('wp_robots','stti_customer_preview_robots',999);add_filter('wpseo_canonical','__return_false',999);
    add_filter('wpseo_json_ld_output','__return_false',999);add_filter('rank_math/frontend/canonical','__return_false',999);
    add_filter('rank_math/json_ld','__return_empty_array',999);add_filter('body_class','stti_customer_preview_body_class',999);
    if(function_exists('show_admin_bar'))show_admin_bar(false);
}

function stti_v080_enqueue_assets($map){
    wp_enqueue_style('stti-leaflet-v080','https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',array(),'1.9.4');
    wp_enqueue_style('stti-customer-preview-v080-base',STTI_URL.'assets/customer-preview.css',array('stti-leaflet-v080'),STTI_VERSION);
    wp_enqueue_style('stti-customer-renderer-v080',STTI_URL.'assets/customer-renderer-v080.css',array('stti-customer-preview-v080-base'),STTI_VERSION);
    wp_enqueue_script('stti-leaflet-v080','https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',array(),'1.9.4',true);
    wp_enqueue_script('stti-customer-shell-v080',STTI_URL.'assets/customer-shell-v080.js',array(),STTI_VERSION,true);
    wp_enqueue_script('stti-canonical-map-v080',STTI_URL.'assets/canonical-geo-map.js',array('stti-leaflet-v080','stti-customer-shell-v080'),STTI_VERSION,true);
    wp_localize_script('stti-canonical-map-v080','STTI_V071_GEO_MAP',$map);
}

require_once __DIR__ . '/customer-renderer-v080-template.php';

if(function_exists('remove_action')&&function_exists('add_action')){
    remove_action('template_redirect','stti_maybe_render_customer_theme_preview',0);
    remove_action('wp_enqueue_scripts','stti_v071_customer_geo_assets',999);
    add_action('template_redirect','stti_v080_maybe_render_customer_theme_preview',0);
}
