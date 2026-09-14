<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class STHHD_Repository {
    public static function launch_ready_hotels() {
        if ( ! STHHD_Plugin::dependency_ready() ) { return array(); }

        $ids = get_posts( array(
            'post_type'      => 'sthi_hotel',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'orderby'        => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
            'no_found_rows'  => true,
        ) );

        $out = array();
        foreach ( $ids as $post_id ) {
            $record = self::record( (int) $post_id );
            if ( $record ) { $out[ $record['hotel_id'] ] = $record; }
        }
        ksort( $out, SORT_NATURAL );
        return $out;
    }

    public static function selected_hotels() {
        $all = self::launch_ready_hotels();
        if ( ! $all ) { return array(); }

        $selected = get_option( STHHD_Plugin::OPTION_SELECTED_IDS, array() );
        $selected = is_array( $selected ) ? array_values( array_unique( array_map( 'sanitize_text_field', $selected ) ) ) : array();
        $order = get_option( STHHD_Plugin::OPTION_ORDER, array() );
        $order = is_array( $order ) ? $order : array();

        $out = array();
        foreach ( $selected as $hotel_id ) {
            if ( isset( $all[ $hotel_id ] ) ) { $out[] = $all[ $hotel_id ]; }
        }

        usort( $out, function( $a, $b ) use ( $order ) {
            $oa = isset( $order[ $a['hotel_id'] ] ) ? intval( $order[ $a['hotel_id'] ] ) : 999;
            $ob = isset( $order[ $b['hotel_id'] ] ) ? intval( $order[ $b['hotel_id'] ] ) : 999;
            if ( $oa === $ob ) { return strnatcasecmp( $a['hotel_id'], $b['hotel_id'] ); }
            return $oa <=> $ob;
        } );
        return $out;
    }

    public static function record( $post_id ) {
        $post_id = absint( $post_id );
        if ( ! $post_id || 'sthi_hotel' !== get_post_type( $post_id ) || 'publish' !== get_post_status( $post_id ) ) { return array(); }
        if ( ! class_exists( 'STHI_Frontend' ) || ! STHI_Frontend::is_public_ready( $post_id ) ) { return array(); }

        $routable = (bool) apply_filters( 'sthi_hotel_link_target_is_routable', false, $post_id );
        $indexable = (bool) apply_filters( 'sthi_hotel_link_target_is_indexable', false, $post_id );
        if ( ! $routable || ! $indexable ) { return array(); }

        $hotel_id = trim( (string) get_post_meta( $post_id, '_sthi_hotel_id', true ) );
        if ( ! preg_match( '/^STH-\d{6}$/', $hotel_id ) ) { return array(); }

        $url = trim( (string) apply_filters( 'sthi_hotel_public_url', '', $post_id ) );
        if ( ! $url && class_exists( 'STHI_Public_Routes' ) && is_callable( array( 'STHI_Public_Routes', 'hotel_public_url' ) ) ) {
            $url = STHI_Public_Routes::hotel_public_url( $post_id );
        }
        if ( ! $url ) { return array(); }

        $items = STHI_Media::get_display_items( $post_id );
        if ( empty( $items ) || empty( $items[0]['url'] ) ) { return array(); }
        $primary = $items[0];
        $image_url = esc_url_raw( $primary['url'] );
        $width = 0;
        $height = 0;
        if ( is_callable( array( 'STHI_Media', 'image_dimensions' ) ) ) {
            list( $width, $height ) = STHI_Media::image_dimensions( $primary );
        }

        $name = trim( (string) get_post_meta( $post_id, '_sthi_display_name_tr', true ) );
        if ( ! $name ) { $name = trim( (string) get_post_meta( $post_id, '_sthi_official_name', true ) ); }
        if ( ! $name ) { $name = get_the_title( $post_id ); }

        $city = trim( (string) get_post_meta( $post_id, '_sthi_city', true ) );
        $district = trim( (string) get_post_meta( $post_id, '_sthi_district', true ) );
        $country = trim( (string) get_post_meta( $post_id, '_sthi_country', true ) );
        $stars = is_callable( array( 'STHI_Frontend', 'verified_star_rating' ) ) ? absint( STHI_Frontend::verified_star_rating( $post_id ) ) : 0;

        return array(
            'post_id'      => $post_id,
            'hotel_id'     => $hotel_id,
            'name'         => $name,
            'city'         => $city,
            'district'     => $district,
            'country'      => $country,
            'stars'        => min( 5, max( 0, $stars ) ),
            'image_url'    => $image_url,
            'image_width'  => absint( $width ),
            'image_height' => absint( $height ),
            'url'          => esc_url_raw( $url ),
            'modified'     => get_post_modified_time( DATE_W3C, true, $post_id ),
        );
    }

    public static function normalized_city_key( $city ) {
        $city = remove_accents( strtolower( trim( (string) $city ) ) );
        $city = preg_replace( '/\s+/u', '-', $city );
        return sanitize_title( $city );
    }
}
