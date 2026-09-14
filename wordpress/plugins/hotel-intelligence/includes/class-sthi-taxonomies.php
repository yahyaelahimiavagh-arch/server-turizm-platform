<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class STHI_Taxonomies {
    public static function init() {
        add_action( 'init', array( __CLASS__, 'register' ), 20 );
    }

    public static function register() {
        register_taxonomy( 'sthi_destination', array( 'sthi_hotel' ), array(
            'labels' => array(
                'name'          => 'Destinations',
                'singular_name' => 'Destination',
                'search_items'  => 'Search Destinations',
                'all_items'     => 'All Destinations',
                'parent_item'   => 'Parent Destination',
                'edit_item'     => 'Edit Destination',
                'add_new_item'  => 'Add New Destination',
                'menu_name'     => 'Destinations',
            ),
            'hierarchical'      => true,
            'public'            => false,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_rest'      => true,
            'rewrite'           => false,
        ) );

        register_taxonomy( 'sthi_collection', array( 'sthi_hotel' ), array(
            'labels' => array(
                'name'          => 'Hotel Collections',
                'singular_name' => 'Hotel Collection',
                'search_items'  => 'Search Collections',
                'all_items'     => 'All Collections',
                'parent_item'   => 'Parent Collection',
                'edit_item'     => 'Edit Collection',
                'add_new_item'  => 'Add New Collection',
                'menu_name'     => 'Collections',
            ),
            'hierarchical'      => true,
            'public'            => false,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_rest'      => true,
            'rewrite'           => false,
        ) );
    }
}
STHI_Taxonomies::init();
