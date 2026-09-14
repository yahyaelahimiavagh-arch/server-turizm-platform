<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class STPF_Program_ID {
    const META_KEY = '_stpf_program_id';

    public static function init() {
        add_action( 'save_post_stpf_program', array( __CLASS__, 'assign_id' ), 5, 3 );
    }

    public static function assign_id( $post_id, $post, $update ) {
        if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) || 'auto-draft' === $post->post_status ) { return; }
        if ( get_post_meta( $post_id, self::META_KEY, true ) ) { return; }

        $next = max( 1, (int) get_option( 'stpf_next_program_number', 1 ) );
        $attempts = 0;
        do {
            $candidate = sprintf( 'STP-%06d', $next );
            $exists = self::find_by_id( $candidate );
            if ( ! $exists ) { break; }
            $next++;
            $attempts++;
        } while ( $attempts < 10000 );

        if ( $attempts >= 10000 ) { return; }
        update_post_meta( $post_id, self::META_KEY, $candidate );
        update_option( 'stpf_next_program_number', $next + 1, false );
    }

    public static function find_by_id( $program_id ) {
        if ( ! preg_match( '/^STP-\\d{6}$/', (string) $program_id ) ) { return 0; }
        $ids = get_posts( array(
            'post_type' => 'stpf_program', 'post_status' => 'any', 'posts_per_page' => 2,
            'fields' => 'ids', 'meta_key' => self::META_KEY, 'meta_value' => $program_id,
        ) );
        return 1 === count( $ids ) ? (int) $ids[0] : 0;
    }

    public static function is_valid_format( $program_id ) {
        return (bool) preg_match( '/^STP-\\d{6}$/', (string) $program_id );
    }
}
STPF_Program_ID::init();
