<?php
/**
 * STTI v1.1.1 — Operator-first editor UI.
 *
 * Presentation-only layer over the existing canonical editor. It does not
 * introduce a second data model, new persistence path, public route, SEO gate,
 * sitemap output, schema output or publication side effect.
 */
if (!defined('ABSPATH')) { exit; }

function stti_v111_operator_editor_is_screen() {
    if (!is_admin()) return false;
    $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
    $view = isset($_GET['view']) ? sanitize_key(wp_unslash($_GET['view'])) : 'dashboard';
    return $page === 'stti-tour-intelligence' && $view === 'editor';
}

function stti_v111_operator_editor_assets() {
    if (!stti_v111_operator_editor_is_screen()) return;

    wp_enqueue_style(
        'stti-operator-editor-v111',
        STTI_URL . 'assets/operator-editor-v111.css',
        array('stti-admin'),
        STTI_RELEASE_VERSION
    );

    wp_enqueue_script(
        'stti-operator-editor-v111',
        STTI_URL . 'assets/operator-editor-v111.js',
        array('stti-admin'),
        STTI_RELEASE_VERSION,
        true
    );
}
add_action('admin_enqueue_scripts', 'stti_v111_operator_editor_assets', 120);
