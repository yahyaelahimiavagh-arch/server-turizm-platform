<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class STPI_Plugin {
    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) { self::$instance = new self(); }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'init', array( $this, 'register_program_post_type' ) );
        STPI_Admin::init();
        STPI_Removals::init();
        STPI_Identity_Repair::init();
    }

    public static function activate() {
        if ( false === get_option( 'stpi_launch_state', false ) ) {
            add_option( 'stpi_launch_state', 'shadow_locked', '', false );
        }
        if ( false === get_option( 'stpi_schema_version', false ) ) {
            add_option( 'stpi_schema_version', STPI_SCHEMA_VERSION, '', false );
        }
        if ( false === get_option( 'stpi_next_program_number', false ) ) {
            add_option( 'stpi_next_program_number', 1, '', false );
        }
    }

    public function register_program_post_type() {
        register_post_type( 'stpi_program', array(
            'labels' => array(
                'name'          => 'Programs',
                'singular_name' => 'Program',
            ),
            'public'              => false,
            'publicly_queryable'  => false,
            'show_ui'             => false,
            'show_in_menu'        => false,
            'show_in_rest'        => false,
            'exclude_from_search' => true,
            'has_archive'         => false,
            'rewrite'             => false,
            'supports'            => array( 'title', 'revisions' ),
            'capability_type'      => 'post',
            'map_meta_cap'         => true,
        ) );

        register_post_type( 'stpi_event', array(
            'labels'              => array( 'name' => 'Program Audit Events', 'singular_name' => 'Program Audit Event' ),
            'public'              => false,
            'publicly_queryable'  => false,
            'show_ui'             => false,
            'show_in_menu'        => false,
            'show_in_rest'        => false,
            'exclude_from_search' => true,
            'has_archive'         => false,
            'rewrite'             => false,
            'supports'            => array( 'title', 'editor' ),
            'capability_type'      => 'post',
            'map_meta_cap'         => true,
        ) );
    }
}
