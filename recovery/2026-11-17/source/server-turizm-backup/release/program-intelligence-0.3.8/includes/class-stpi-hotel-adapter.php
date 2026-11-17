<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class STPI_Hotel_Adapter {
    public static function resolve( $hotel_id ) {
        $hotel_id = strtoupper( trim( (string) $hotel_id ) );
        if ( ! preg_match( '/^STH-\d{6}$/', $hotel_id ) ) {
            return array( 'status' => 'invalid', 'hotel_id' => $hotel_id );
        }

        if ( ! post_type_exists( 'sthi_hotel' ) ) {
            return array( 'status' => 'integration_unavailable', 'hotel_id' => $hotel_id );
        }

        $ids = get_posts( array(
            'post_type'      => 'sthi_hotel',
            'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
            'posts_per_page' => 2,
            'fields'         => 'ids',
            'meta_key'       => '_sthi_hotel_id',
            'meta_value'     => $hotel_id,
            'no_found_rows'  => true,
        ) );

        if ( 0 === count( $ids ) ) { return array( 'status' => 'unresolved', 'hotel_id' => $hotel_id ); }
        if ( 1 !== count( $ids ) ) { return array( 'status' => 'duplicate', 'hotel_id' => $hotel_id ); }

        $post_id = (int) $ids[0];
        $name = trim( (string) get_post_meta( $post_id, '_sthi_official_name', true ) );
        if ( ! $name ) { $name = get_the_title( $post_id ); }

        $url = trim( (string) apply_filters( 'sthi_hotel_public_url', '', $post_id ) );
        $gallery = array();
        if ( class_exists( 'STHI_Media' ) ) {
            foreach ( array_slice( STHI_Media::get_display_items( $post_id ), 0, 4 ) as $item ) {
                if ( ! empty( $item['url'] ) ) { $gallery[] = esc_url_raw( $item['url'] ); }
            }
        }

        $dto = array(
            'status'     => 'resolved',
            'post_id'    => $post_id,
            'hotel_id'   => $hotel_id,
            'name'       => $name,
            'city'       => trim( (string) get_post_meta( $post_id, '_sthi_city', true ) ),
            'stars'      => class_exists( 'STHI_Frontend' ) ? (int) STHI_Frontend::verified_star_rating( $post_id ) : 0,
            'verification_status' => trim( (string) get_post_meta( $post_id, '_sthi_verification_status', true ) ),
            'workflow_status'     => trim( (string) get_post_meta( $post_id, '_sthi_workflow_status', true ) ),
            'public_url' => esc_url_raw( $url ),
            'images'     => $gallery,
        );

        return apply_filters( 'stpi_hotel_dto', $dto, $post_id, $hotel_id );
    }
}
