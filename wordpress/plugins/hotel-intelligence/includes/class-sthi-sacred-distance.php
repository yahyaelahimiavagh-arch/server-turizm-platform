<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Automatic Makkah / Madinah sacred-site detection and straight-line distance.
 * This is a geometric (great-circle) distance, not a walking/driving route distance.
 */
final class STHI_Sacred_Distance {
    const MAKKAH_LAT = 21.423611;
    const MAKKAH_LNG = 39.827222;
    const MADINAH_LAT = 24.468330;
    const MADINAH_LNG = 39.610830;

    public static function get( $post_id ) {
        $coords = self::hotel_coordinates( $post_id );
        $city   = trim( (string) get_post_meta( $post_id, '_sthi_city', true ) );
        $target = self::detect_target( $city, $coords );

        if ( ! $target ) {
            return array(
                'detected' => false,
                'hotel_lat' => $coords ? $coords[0] : null,
                'hotel_lng' => $coords ? $coords[1] : null,
            );
        }

        // H10.0: when the Hotel dossier contains a VERIFIED holy-site reference
        // for the detected primary sacred site, its coordinates are the source of
        // truth for public/admin distance rendering. The hard-coded city anchor
        // remains only a safe fallback for Hotels that do not yet have a dossier.
        $target = self::apply_verified_reference( $post_id, $target );

        $distance = null;
        if ( $coords ) {
            $distance = self::haversine_m( $coords[0], $coords[1], $target['lat'], $target['lng'] );
        } elseif ( isset( $target['stored_distance_m'] ) && is_numeric( $target['stored_distance_m'] ) ) {
            $distance = (float) $target['stored_distance_m'];
        }

        return array_merge( $target, array(
            'detected'   => true,
            'hotel_lat'  => $coords ? $coords[0] : null,
            'hotel_lng'  => $coords ? $coords[1] : null,
            'distance_m' => null === $distance ? null : (int) round( $distance ),
            'distance_label' => null === $distance ? '' : self::format_distance( $distance ),
        ) );
    }

    private static function apply_verified_reference( $post_id, $target ) {
        if ( ! class_exists( 'STHI_Structured_Details' ) || ! method_exists( 'STHI_Structured_Details', 'get_references' ) ) {
            return $target;
        }

        $references = STHI_Structured_Details::get_references( $post_id );
        if ( ! is_array( $references ) || ! $references ) { return $target; }

        $best = null;
        $best_anchor_distance = null;

        foreach ( $references as $reference ) {
            if ( ! is_array( $reference ) ) { continue; }
            if ( 'verified' !== sanitize_key( $reference['status'] ?? '' ) ) { continue; }
            if ( 'holy_site' !== sanitize_key( $reference['type'] ?? '' ) ) { continue; }

            $lat = $reference['latitude'] ?? '';
            $lng = $reference['longitude'] ?? '';
            if ( ! is_numeric( $lat ) || ! is_numeric( $lng ) ) { continue; }

            // Match the dossier reference to the detected city's primary sacred
            // site by proximity to the fallback anchor. This remains robust across
            // Turkish/English/Arabic reference names and avoids selecting another
            // holy site in the same city.
            $anchor_distance = self::haversine_m( (float) $lat, (float) $lng, $target['lat'], $target['lng'] );
            if ( $anchor_distance > 2000 ) { continue; }

            if ( null === $best_anchor_distance || $anchor_distance < $best_anchor_distance ) {
                $best = $reference;
                $best_anchor_distance = $anchor_distance;
            }
        }

        if ( ! $best ) { return $target; }

        $target['lat'] = (float) $best['latitude'];
        $target['lng'] = (float) $best['longitude'];
        $target['reference_source'] = 'verified_structured_reference';
        $target['reference_id'] = sanitize_text_field( $best['id'] ?? '' );
        $target['stored_distance_m'] = isset( $best['distance_m'] ) && is_numeric( $best['distance_m'] ) ? (float) $best['distance_m'] : null;
        $target['distance_method'] = sanitize_key( $best['distance_method'] ?? 'unknown' );
        return $target;
    }

    public static function hotel_coordinates( $post_id ) {
        $lat = trim( (string) get_post_meta( $post_id, '_sthi_latitude', true ) );
        $lng = trim( (string) get_post_meta( $post_id, '_sthi_longitude', true ) );
        if ( is_numeric( $lat ) && is_numeric( $lng ) ) {
            return array( (float) $lat, (float) $lng );
        }

        foreach ( array( '_sthi_google_maps_embed_url', '_sthi_google_maps_url' ) as $key ) {
            $url = html_entity_decode( (string) get_post_meta( $post_id, $key, true ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
            $parsed = self::coordinates_from_google_url( $url );
            if ( $parsed ) { return $parsed; }
        }
        return null;
    }

    public static function coordinates_from_google_url( $url ) {
        if ( ! $url ) { return null; }
        // Google Maps embed payload commonly stores !2dLONGITUDE!3dLATITUDE.
        if ( preg_match( '/!2d(-?\d+(?:\.\d+)?)!3d(-?\d+(?:\.\d+)?)/', $url, $m ) ) {
            return array( (float) $m[2], (float) $m[1] );
        }
        // Generic @LAT,LNG fallback.
        if ( preg_match( '/@(-?\d+(?:\.\d+)?),(-?\d+(?:\.\d+)?)/', $url, $m ) ) {
            return array( (float) $m[1], (float) $m[2] );
        }
        // query=LAT,LNG fallback.
        if ( preg_match( '/(?:query|q)=(-?\d+(?:\.\d+)?)(?:%2C|,)(-?\d+(?:\.\d+)?)/i', $url, $m ) ) {
            return array( (float) $m[1], (float) $m[2] );
        }
        return null;
    }

    private static function detect_target( $city, $coords ) {
        if ( class_exists( 'STHI_Geography' ) ) {
            $city_key = STHI_Geography::city_key( $city );
            if ( 'makkah' === $city_key ) { return self::target_makkah(); }
            if ( 'madinah' === $city_key ) { return self::target_madinah(); }
        }

        $normalized = self::normalize_city( $city );

        if ( self::contains_any( $normalized, array( 'makkah', 'mekke', 'mecca', 'macca', 'makkah al mukarramah' ) ) ) {
            return self::target_makkah();
        }
        if ( self::contains_any( $normalized, array( 'madinah', 'medinah', 'medina', 'medine', 'madina' ) ) ) {
            return self::target_madinah();
        }

        // Arabic names as a fallback without transliteration dependency.
        if ( false !== strpos( $city, 'مكة' ) || false !== strpos( $city, 'مكه' ) ) {
            return self::target_makkah();
        }
        if ( false !== strpos( $city, 'المدينة' ) || false !== strpos( $city, 'المدينه' ) ) {
            return self::target_madinah();
        }

        // If city text is not reliable, classify only when coordinates are clearly close to one sacred city.
        if ( $coords ) {
            $makkah  = self::haversine_m( $coords[0], $coords[1], self::MAKKAH_LAT, self::MAKKAH_LNG );
            $madinah = self::haversine_m( $coords[0], $coords[1], self::MADINAH_LAT, self::MADINAH_LNG );
            $nearest = min( $makkah, $madinah );
            if ( $nearest <= 50000 ) {
                return $makkah <= $madinah ? self::target_makkah() : self::target_madinah();
            }
        }

        return null;
    }

    private static function target_makkah() {
        return array(
            'city_type' => 'makkah',
            'city_label' => 'Mekke',
            'target_name' => 'Mescid-i Haram',
            'target_short' => 'Harem-i Şerif',
            'lat' => self::MAKKAH_LAT,
            'lng' => self::MAKKAH_LNG,
        );
    }

    private static function target_madinah() {
        return array(
            'city_type' => 'madinah',
            'city_label' => 'Medine',
            'target_name' => 'Mescid-i Nebevi',
            'target_short' => 'Mescid-i Nebevi',
            'lat' => self::MADINAH_LAT,
            'lng' => self::MADINAH_LNG,
        );
    }

    public static function format_distance( $meters ) {
        $meters = max( 0, (float) $meters );
        if ( $meters < 1000 ) {
            return number_format_i18n( round( $meters / 10 ) * 10 ) . ' m';
        }
        return number_format_i18n( $meters / 1000, $meters < 10000 ? 2 : 1 ) . ' km';
    }

    public static function haversine_m( $lat1, $lng1, $lat2, $lng2 ) {
        $earth = 6371000;
        $phi1  = deg2rad( (float) $lat1 );
        $phi2  = deg2rad( (float) $lat2 );
        $dphi  = deg2rad( (float) $lat2 - (float) $lat1 );
        $dlmb  = deg2rad( (float) $lng2 - (float) $lng1 );
        $a = sin( $dphi / 2 ) ** 2 + cos( $phi1 ) * cos( $phi2 ) * sin( $dlmb / 2 ) ** 2;
        return $earth * 2 * atan2( sqrt( $a ), sqrt( 1 - $a ) );
    }

    private static function normalize_city( $value ) {
        $value = strtolower( remove_accents( (string) $value ) );
        $value = preg_replace( '/[^a-z0-9\s-]+/', ' ', $value );
        return trim( preg_replace( '/\s+/', ' ', $value ) );
    }

    private static function contains_any( $haystack, $needles ) {
        foreach ( $needles as $needle ) {
            if ( false !== strpos( $haystack, $needle ) ) { return true; }
        }
        return false;
    }
}
