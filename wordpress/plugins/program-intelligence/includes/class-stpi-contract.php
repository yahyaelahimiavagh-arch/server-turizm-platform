<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class STPI_Contract {
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
        return hash( 'sha256', wp_json_encode( self::sort_recursive( $program ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) );
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
