<?php
/** SEO/indexation isolation for STTI v1.0.0. */
if(!defined('ABSPATH')){exit;}
function stti_v100_robots_filter($robots){
    $surface=stti_v100_surface_state();if(!$surface['route'])return $robots;
    foreach(array('index','noindex','follow','nofollow','noarchive') as $key)unset($robots[$key]);
    if($surface['indexation']){$robots['index']=true;$robots['follow']=true;}
    else{$robots['noindex']=true;$robots['follow']=true;$robots['noarchive']=true;}
    return $robots;
}
function stti_v100_print_canonical(){
    $surface=stti_v100_surface_state();if(!$surface['canonical'])return;
    echo "\n<link rel=\"canonical\" href=\"".esc_url($surface['public_url'])."\">\n";
}
function stti_v100_print_schema(){
    $surface=stti_v100_surface_state();if(!$surface['schema'])return;$p=$surface['payload'];$row=$surface['row'];
    $schema=array('@context'=>'https://schema.org','@type'=>'WebPage','name'=>stti_v070_text($p['identity']['public_title']??''),'url'=>$surface['public_url'],'inLanguage'=>stti_v070_text($p['identity']['language']??'tr-TR'));
    if(!empty($row['updated_at']))$schema['dateModified']=(string)$row['updated_at'];
    echo "\n<script type=\"application/ld+json\">".wp_json_encode($schema,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."</script>\n";
}
function stti_v100_prepare_public_surface(){
    remove_action('wp_head','rel_canonical');
    add_filter('wp_robots','stti_v100_robots_filter',999);
    add_filter('wpseo_canonical','__return_false',999);add_filter('wpseo_json_ld_output','__return_false',999);
    add_filter('rank_math/frontend/canonical','__return_false',999);add_filter('rank_math/json_ld','__return_empty_array',999);
    add_action('wp_head','stti_v100_print_canonical',1);add_action('wp_head','stti_v100_print_schema',30);
    add_filter('body_class',static function($classes){$classes=array_values(array_diff((array)$classes,array('error404')));$classes[]='stti-v100-public-pilot';return $classes;},999);
}
function stti_v100_register_sitemap_provider(){
    if(!class_exists('WP_Sitemaps_Provider')||!function_exists('wp_register_sitemap_provider'))return;
    if(!class_exists('STTI_V100_Sitemap_Provider')){
        class STTI_V100_Sitemap_Provider extends WP_Sitemaps_Provider{
            public function __construct(){$this->name='stti-tours';$this->object_type='stti-tour';}
            public function get_object_subtypes(){return array();}
            public function get_url_list($page_num,$object_subtype=''){$surface=stti_v100_surface_state();if(!$surface['sitemap']||(int)$page_num!==1)return array();$entry=array('loc'=>$surface['public_url']);if(!empty($surface['row']['updated_at']))$entry['lastmod']=mysql2date('c',$surface['row']['updated_at'],false);return array($entry);}
            public function get_max_num_pages($object_subtype=''){return stti_v100_surface_state()['sitemap']?1:0;}
        }
    }
    wp_register_sitemap_provider('stti-tours',new STTI_V100_Sitemap_Provider());
}
