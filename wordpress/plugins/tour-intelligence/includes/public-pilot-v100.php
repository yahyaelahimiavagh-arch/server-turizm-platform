<?php
/** STTI v1.0.0 controlled public pilot loader. */
if(!defined('ABSPATH')){exit;}
require_once __DIR__.'/public-pilot-v100-config.php';
require_once __DIR__.'/public-pilot-v100-readiness.php';
require_once __DIR__.'/public-pilot-v100-control.php';
require_once __DIR__.'/public-pilot-v100-seo.php';
require_once __DIR__.'/public-pilot-v100-admin.php';
require_once __DIR__.'/public-pilot-v100-template.php';
require_once __DIR__.'/public-pilot-v100-route.php';
add_action('admin_menu','stti_v100_register_public_pilot_page',99);
add_action('admin_post_stti_v100_save_public_pilot','stti_v100_handle_save_public_pilot');
add_action('init','stti_v100_register_sitemap_provider',20);
add_action('template_redirect','stti_v100_maybe_render_public_pilot',1);
