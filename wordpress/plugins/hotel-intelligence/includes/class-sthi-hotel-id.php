<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class STHI_Hotel_ID {
    public static function init() {
        add_action( 'save_post_sthi_hotel', array( __CLASS__, 'assign_id' ), 5, 3 );
    }

    public static function assign_id( $post_id, $post, $update ) {
        if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) || 'auto-draft' === $post->post_status ) { return; }
        if ( get_post_meta( $post_id, '_sthi_hotel_id', true ) ) { return; }

        $next = max( 1, (int) get_option( 'sthi_next_hotel_number', 1 ) );
        $attempts = 0;

        do {
            $candidate = sprintf( 'STH-%06d', $next );
            $exists = new WP_Query( array(
                'post_type'      => 'sthi_hotel',
                'post_status'    => 'any',
                'posts_per_page' => 1,
                'fields'         => 'ids',
                'meta_key'       => '_sthi_hotel_id',
                'meta_value'     => $candidate,
            ) );
            if ( ! $exists->have_posts() ) { break; }
            $next++;
            $attempts++;
        } while ( $attempts < 1000 );

        if ( $attempts >= 1000 ) { return; }

        update_post_meta( $post_id, '_sthi_hotel_id', $candidate );
        update_option( 'sthi_next_hotel_number', $next + 1, false );
    }
}
STHI_Hotel_ID::init();
