<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * REST API routes.
 */
add_action('rest_api_init', function () {
    register_rest_route('serverturizm-ai/v1', '/status', [
        'methods'  => 'GET',
        'callback' => 'stai_status_handler',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('serverturizm-ai/v1', '/chat', [
        'methods'  => 'POST',
        'callback' => 'stai_chat_handler',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('serverturizm-ai/v1', '/track', [
        'methods'  => 'POST',
        'callback' => 'stai_track_handler',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('serverturizm-ai/v1', '/lead', [
        'methods'  => 'POST',
        'callback' => 'stai_lead_handler',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('serverturizm-ai/v1', '/analytics', [
        'methods'  => 'GET',
        'callback' => 'stai_analytics_handler',
        'permission_callback' => function () {
            return current_user_can('manage_options');
        },
    ]);
});
