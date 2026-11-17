<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class STPI_Contract {
    const HOTEL_PENDING_LABEL = 'Otel bilgileri yakında açıklanacaktır.';

    public static function pending_stay( $stay ) {
        if ( ! is_array( $stay ) || ! empty( $stay['hotel_id'] ) ) { return false; }
        $name = trim( (string) ( $stay['unresolved_hotel_name'] ?? '' ) );
        // Legacy unnamed provisional stay remains supported.
        if ( self::HOTEL_PENDING_LABEL === $name ) { return true; }
        // Only an administrator may set this marker through the approved
        // lifecycle action. A raw Google Sheets name alone is NOT approval.
        return true === ( $stay['pending_hotel_reviewed'] ?? null )
            && $name !== '' && ( function_exists( 'mb_strlen' ) ? mb_strlen( $name, 'UTF-8' ) : strlen( $name ) ) <= 220;
    }

    public static function has_pending_hotels( $program ) {
        foreach ( (array) ( $program['stays'] ?? array() ) as $stay ) {
            if ( self::pending_stay( $stay ) ) { return true; }
        }
        return false;
    }

    public static function carry_pending_hotels( $candidate, $approved ) {
        if ( 'approved' !== ( $approved['workflow']['editorial'] ?? '' ) || ! self::hotel_pending_ready( $approved ) ) { return $candidate; }
        foreach ( (array) ( $candidate['stays'] ?? array() ) as $i => $stay ) {
            if ( ! is_array( $stay ) || ! empty( $stay['hotel_id'] ) ) { continue; }
            foreach ( (array) $approved['stays'] as $prior ) {
                if ( ! self::pending_stay( $prior )
                    || ( $stay['sequence'] ?? null ) !== ( $prior['sequence'] ?? null )
                    || ( $stay['destination'] ?? null ) !== ( $prior['destination'] ?? null )
                    || ( $stay['check_in'] ?? null ) !== ( $prior['check_in'] ?? null )
                    || ( $stay['check_out'] ?? null ) !== ( $prior['check_out'] ?? null )
                    || (int) ( $stay['nights'] ?? -1 ) !== (int) ( $prior['nights'] ?? -2 ) ) { continue; }
                $incoming = trim( (string) ( $stay['unresolved_hotel_name'] ?? '' ) );
                $previous = trim( (string) ( $prior['unresolved_hotel_name'] ?? '' ) );
                if ( $incoming === '' && $previous === self::HOTEL_PENDING_LABEL ) {
                    $candidate['stays'][$i]['unresolved_hotel_name'] = self::HOTEL_PENDING_LABEL;
                } elseif ( $incoming !== $previous ) {
                    // Different name = new hotel claim. Never inherit approval.
                    continue;
                }
                if ( ! empty( $prior['pending_hotel_reviewed'] ) ) { $candidate['stays'][$i]['pending_hotel_reviewed'] = true; }
                break;
            }
        }
        return $candidate;
    }

    // Pending hotel names are explicit, verified, noindex data. They never
    // substitute for a supplied Stable Hotel ID or an unresolved named hotel.
    public static function hotel_pending_ready( $program ) {
        if ( ! self::has_pending_hotels( $program ) || empty( $program['provenance']['verified_at'] )
            || empty( $program['provenance']['verified_by'] ) || 'noindex' !== ( $program['seo']['index_policy'] ?? '' )
            || 'scheduled_departure' !== ( $program['publication_mode'] ?? '' )
            || 'scheduled' !== ( $program['workflow']['schedule'] ?? '' )
            || 'exact' !== ( $program['schedule']['date_precision'] ?? '' ) ) { return false; }
        $schedule = $program['schedule'];
        $start = self::iso_date( $schedule['start_date'] ?? '' );
        $end = self::iso_date( $schedule['end_date'] ?? '' );
        if ( ! $start || ! $end || $end <= $start ) { return false; }
        $nights = (int) $start->diff( $end )->format( '%r%a' );
        if ( (int) ( $schedule['duration_nights'] ?? -1 ) !== $nights
            || (int) ( $schedule['duration_days'] ?? 0 ) !== $nights + 1 ) { return false; }
        $last = $start; $sequence = 0;
        $cities = array_column( (array) ( $program['destinations'] ?? array() ), 'city' );
        foreach ( (array) ( $program['stays'] ?? array() ) as $stay ) {
            if ( ! is_array( $stay ) || (int) ( $stay['sequence'] ?? 0 ) <= $sequence
                || ! in_array( $stay['destination'] ?? '', $cities, true ) ) { return false; }
            $sequence = (int) $stay['sequence'];
            if ( empty( $stay['hotel_id'] ) && ! self::pending_stay( $stay ) ) { return false; }
            $in = self::iso_date( $stay['check_in'] ?? '' );
            $out = self::iso_date( $stay['check_out'] ?? '' );
            if ( ! $in || ! $out || $in != $last || $out <= $in || $out > $end
                || ! is_numeric( $stay['nights'] ?? null )
                || (float) $stay['nights'] !== (float) $in->diff( $out )->format( '%r%a' ) ) { return false; }
            $last = $out;
        }
        return $last == $end;
    }

    private static function iso_date( $value ) {
        if ( ! is_string( $value ) ) { return false; }
        $date = DateTimeImmutable::createFromFormat( '!Y-m-d', $value );
        return $date && $date->format( 'Y-m-d' ) === $value ? $date : false;
    }

    public static function enums() {
        return array(
            'service_type'     => array( 'umrah', 'cultural_tour' ),
            'publication_mode' => array( 'scheduled_departure', 'date_window', 'on_request', 'interest_campaign', 'archived_departure' ),
            'editorial'        => array( 'draft', 'needs_review', 'approved', 'published', 'archived' ),
            'schedule'         => array( 'scheduled', 'tentative', 'postponed', 'rescheduled', 'cancelled' ),
            'availability'     => array( 'open', 'limited', 'sold_out', 'on_request', 'closed' ),
            'date_precision'   => array( 'exact', 'month', 'season', 'unknown' ),
            'price_type'       => array( 'fixed', 'from', 'on_request' ),
            'occupancy_type'   => array( 'single', 'double', 'triple', 'quad', 'child', 'infant', 'custom' ),
        );
    }

    public static function schema_path() {
        return STPI_DIR . 'schema/program-batch-v1.0.0.schema.json';
    }

    public static function schema_url() {
        return STPI_URL . 'schema/program-batch-v1.0.0.schema.json';
    }

    public static function normalize_batch( $batch ) {
        if ( ! is_array( $batch ) ) { return array(); }
        if ( isset( $batch['programs'] ) && is_array( $batch['programs'] ) ) { return $batch; }
        if ( self::is_list( $batch ) ) {
            return array(
                'schema_version' => STPI_SCHEMA_VERSION,
                'export_id'      => '',
                'generated_at'   => '',
                'source'         => array( 'type' => 'manual_json', 'mode' => 'partial' ),
                'programs'       => $batch,
            );
        }
        if ( isset( $batch['service_type'] ) ) {
            return array(
                'schema_version' => STPI_SCHEMA_VERSION,
                'export_id'      => '',
                'generated_at'   => '',
                'source'         => array( 'type' => 'manual_json', 'mode' => 'partial' ),
                'programs'       => array( $batch ),
            );
        }
        return $batch;
    }

    public static function payload_hash( $program ) {
        // A physical row is only a location once the source owns a durable UUID.
        if ( self::source_uuid( $program ) && isset( $program['provenance'] ) ) {
            unset( $program['provenance']['source_row'] );
        }
        return hash( 'sha256', wp_json_encode( self::sort_recursive( $program ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) );
    }

    public static function source_uuid( $program ) {
        $ref = (string) ( $program['provenance']['source_ref'] ?? '' );
        return preg_match( '/^ST-ROW:([a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12})$/D', $ref, $match ) ? $match[1] : '';
    }

    public static function uuid_source_scope( $source ) {
        return hash( 'sha256', wp_json_encode( array( (string) ( $source['document_ref'] ?? '' ), (string) ( $source['worksheet'] ?? '' ) ) ) );
    }

    private static function sort_recursive( $value ) {
        if ( ! is_array( $value ) ) { return $value; }
        if ( ! self::is_list( $value ) ) { ksort( $value ); }
        foreach ( $value as $key => $item ) { $value[ $key ] = self::sort_recursive( $item ); }
        return $value;
    }

    private static function is_list( $value ) {
        if ( ! is_array( $value ) ) { return false; }
        if ( array() === $value ) { return true; }
        return array_keys( $value ) === range( 0, count( $value ) - 1 );
    }
}
