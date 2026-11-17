<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class STPI_Lifecycle {
    public static function temporal_state( $program, $now = null ) {
        $schedule = is_array( $program['schedule'] ?? null ) ? $program['schedule'] : array();
        $start = trim( (string) ( $schedule['start_date'] ?? '' ) );
        $end = trim( (string) ( $schedule['end_date'] ?? '' ) );
        $today = $now ?: wp_date( 'Y-m-d', null, new DateTimeZone( 'Europe/Istanbul' ) );
        if ( ! $start && ! $end ) { return 'undated'; }
        if ( $end && $end < $today ) { return 'completed'; }
        if ( $start && $start <= $today && ( ! $end || $today <= $end ) ) { return 'in_progress'; }
        if ( $start && $start > $today ) { return 'upcoming'; }
        return 'undated';
    }

    public static function effective_state( $program ) {
        $workflow = is_array( $program['workflow'] ?? null ) ? $program['workflow'] : array();
        if ( 'archived' === ( $workflow['editorial'] ?? '' ) ) { return 'archived'; }
        if ( 'cancelled' === ( $workflow['schedule'] ?? '' ) ) { return 'cancelled'; }
        if ( 'sold_out' === ( $workflow['availability'] ?? '' ) ) { return 'sold_out'; }
        return self::temporal_state( $program );
    }

    public static function archive_eligible( $program ) {
        $workflow = is_array( $program['workflow'] ?? null ) ? $program['workflow'] : array();
        if ( 'archived' === ( $workflow['editorial'] ?? '' ) ) { return false; }
        return in_array( self::temporal_state( $program ), array( 'completed' ), true ) || 'cancelled' === ( $workflow['schedule'] ?? '' );
    }
}
