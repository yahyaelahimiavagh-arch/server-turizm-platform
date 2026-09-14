<?php
/** Exact allowlisted route handling for STTI v1.0.0. */
if(!defined('ABSPATH')){exit;}
function stti_v100_normalize_path($path){$path=rawurldecode((string)$path);$path='/'.trim($path,'/').'/';return $path==='//'?'/':$path;}
function stti_v100_request_path(){
    $raw=isset($_SERVER['REQUEST_URI'])?wp_unslash($_SERVER['REQUEST_URI']):'/';
    $path=wp_parse_url($raw,PHP_URL_PATH);
    return stti_v100_normalize_path(is_string($path)?$path:'/');
}
function stti_v100_is_exact_public_request($path=null){
    $candidate=$path===null?stti_v100_request_path():stti_v100_normalize_path($path);
    return hash_equals(stti_v100_public_config()['path'],$candidate);
}
function stti_v100_maybe_render_public_pilot(){
    if(!stti_v100_is_exact_public_request())return;
    $surface=stti_v100_surface_state();if(!$surface['route'])return;
    global $wp_query;if($wp_query)$wp_query->is_404=false;
    status_header(200);nocache_headers();
    stti_v100_prepare_public_surface();stti_v080_enqueue_assets($surface['model']['map']);
    stti_v100_render_public_template($surface);exit;
}
