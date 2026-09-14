<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class STPI_Audit {
    public static function log( $event_type, $program_id = '', $details = array() ) {
        $user = wp_get_current_user();
        $event_type = sanitize_key( (string) $event_type );
        $program_id = strtoupper( trim( (string) $program_id ) );
        $payload = array(
            'event_type' => $event_type,
            'program_id' => $program_id,
            'occurred_at'=> gmdate( DATE_W3C ),
            'actor'      => array(
                'user_id'      => (int) $user->ID,
                'display_name' => (string) $user->display_name,
                'email'        => (string) $user->user_email,
            ),
            'details'    => is_array( $details ) ? $details : array( 'value' => (string) $details ),
        );

        $title = strtoupper( str_replace( '_', ' ', $event_type ) );
        if ( $program_id ) { $title .= ' — ' . $program_id; }
        return wp_insert_post( array(
            'post_type'    => 'stpi_event',
            'post_status'  => 'private',
            'post_title'   => wp_strip_all_tags( $title ),
            'post_content' => wp_slash( wp_json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) ),
            'post_author'  => (int) $user->ID,
        ), true );
    }

    public static function recent( $limit = 100 ) {
        $posts = get_posts( array(
            'post_type'      => 'stpi_event',
            'post_status'    => 'private',
            'posts_per_page' => max( 1, min( 500, absint( $limit ) ) ),
            'orderby'        => 'date',
            'order'          => 'DESC',
            'no_found_rows'  => true,
        ) );
        $events = array();
        foreach ( $posts as $post ) {
            $decoded = json_decode( (string) $post->post_content, true );
            if ( is_array( $decoded ) ) { $events[] = $decoded; }
        }
        return $events;
    }
}
