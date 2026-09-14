<?php
function stti_v070_admin_assets($hook) {
    if ($hook !== 'toplevel_page_stti-tour-intelligence') return;
    $variants = array();
    $stable_id = isset($_GET['tour']) ? stti_v070_text(function_exists('wp_unslash') ? wp_unslash($_GET['tour']) : $_GET['tour']) : '';
    if ($stable_id !== '' && function_exists('stti_get_candidate')) {
        $row = stti_get_candidate($stable_id);
        $payload = stti_v070_existing_payload($row);
        $variants = stti_v070_normalize_route_variants($payload['route']['variants'] ?? array());
    }
    wp_enqueue_style('stti-v070-review-relations', STTI_URL . 'assets/review-relations.css', array('stti-admin'), STTI_VERSION);
    wp_enqueue_script('stti-v070-review-relations', STTI_URL . 'assets/review-relations.js', array('stti-admin'), STTI_VERSION, true);
    wp_localize_script('stti-v070-review-relations', 'STTI_V070_REVIEW_RELATIONS', array(
        'version' => '0.7.0',
        'routeVariants' => $variants,
        'locks' => array('publicRoutes'=>false,'indexation'=>false,'sitemap'=>false,'schema'=>false,'canonicalRobots'=>false,'homepageAdapter'=>false),
    ));
}

/** Register extension hooks only inside WordPress. Include this file after the core add_action registrations. */
if (function_exists('add_action') && function_exists('remove_action')) {
    remove_action('admin_post_stti_save_candidate', 'stti_handle_save_candidate');
    add_action('admin_post_stti_save_candidate', 'stti_v070_handle_save_candidate');
    add_action('admin_enqueue_scripts', 'stti_v070_admin_assets', 30);
}
