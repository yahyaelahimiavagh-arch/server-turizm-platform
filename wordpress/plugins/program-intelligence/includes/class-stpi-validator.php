<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class STPI_Validator {
    public static function validate_batch( $input ) {
        $batch = STPI_Contract::normalize_batch( $input );
        $errors = array();
        $warnings = array();
        $rows = array();

        if ( ! is_array( $batch ) || empty( $batch['programs'] ) || ! is_array( $batch['programs'] ) ) {
            $errors[] = self::issue( 'batch', 'programs', 'Programs array is required.' );
            return self::report( $batch, $rows, $errors, $warnings );
        }
        if ( ( $batch['schema_version'] ?? '' ) !== STPI_SCHEMA_VERSION ) {
            $errors[] = self::issue( 'batch', 'schema_version', 'Unsupported schema version; expected ' . STPI_SCHEMA_VERSION . '.' );
        }
        $source = is_array( $batch['source'] ?? null ) ? $batch['source'] : array();
        if ( empty( $source['type'] ) || ! in_array( $source['type'], array( 'google_sheets', 'wordpress', 'manual_json', 'migration' ), true ) ) {
            $errors[] = self::issue( 'batch', 'source.type', 'A supported source type is required.' );
        }
        if ( empty( $source['mode'] ) || ! in_array( $source['mode'], array( 'partial', 'full_snapshot' ), true ) ) {
            $errors[] = self::issue( 'batch', 'source.mode', 'Source mode must be partial or full_snapshot.' );
        }

        $seen = array();
        foreach ( array_values( $batch['programs'] ) as $index => $program ) {
            $row = self::validate_program( is_array( $program ) ? $program : array(), $index + 1 );
            if ( $row['program_id'] ) {
                if ( isset( $seen[ $row['program_id'] ] ) ) {
                    $row['errors'][] = self::issue( $row['program_id'], 'program_id', 'Duplicate Program ID inside this batch.' );
                    $row['publish_gate'] = 'BLOCKED';
                }
                $seen[ $row['program_id'] ] = true;
            }
            $errors = array_merge( $errors, $row['errors'] );
            $warnings = array_merge( $warnings, $row['warnings'] );
            $rows[] = $row;
        }

        return self::report( $batch, $rows, $errors, $warnings );
    }

    private static function validate_program( $program, $row_number ) {
        $errors = array();
        $warnings = array();
        $hotel_results = array();
        $enums = STPI_Contract::enums();
        $program_id = strtoupper( trim( (string) ( $program['program_id'] ?? '' ) ) );
        $scope = $program_id ?: 'row-' . $row_number;

        if ( $program_id && ! preg_match( '/^STP-\d{6}$/', $program_id ) ) {
            $errors[] = self::issue( $scope, 'program_id', 'Program ID must match STP-000000.' );
        }
        self::required_text( $program, 'program_code', $scope, $errors );
        self::required_text( $program, 'title', $scope, $errors );
        self::enum( $program, 'service_type', $enums['service_type'], $scope, $errors );
        self::enum( $program, 'publication_mode', $enums['publication_mode'], $scope, $errors );

        $workflow = is_array( $program['workflow'] ?? null ) ? $program['workflow'] : array();
        self::enum( $workflow, 'editorial', $enums['editorial'], $scope, $errors, 'workflow.editorial' );
        self::enum( $workflow, 'schedule', $enums['schedule'], $scope, $errors, 'workflow.schedule' );
        self::enum( $workflow, 'availability', $enums['availability'], $scope, $errors, 'workflow.availability' );

        $schedule = is_array( $program['schedule'] ?? null ) ? $program['schedule'] : array();
        self::enum( $schedule, 'date_precision', $enums['date_precision'], $scope, $errors, 'schedule.date_precision' );
        $start = trim( (string) ( $schedule['start_date'] ?? '' ) );
        $end = trim( (string) ( $schedule['end_date'] ?? '' ) );
        if ( $start && ! self::valid_date( $start ) ) { $errors[] = self::issue( $scope, 'schedule.start_date', 'Use ISO date YYYY-MM-DD.' ); }
        if ( $end && ! self::valid_date( $end ) ) { $errors[] = self::issue( $scope, 'schedule.end_date', 'Use ISO date YYYY-MM-DD.' ); }
        if ( $start && $end && $end < $start ) { $errors[] = self::issue( $scope, 'schedule.end_date', 'End date cannot precede start date.' ); }

        if ( 'scheduled_departure' === ( $program['publication_mode'] ?? '' ) ) {
            if ( 'exact' !== ( $schedule['date_precision'] ?? '' ) || ! $start || ! $end ) {
                $errors[] = self::issue( $scope, 'schedule', 'Scheduled departures require exact start and end dates.' );
            }
        }

        $segments = is_array( $program['segments'] ?? null ) ? $program['segments'] : array();
        $last_sequence = 0;
        foreach ( $segments as $i => $segment ) {
            $sequence = absint( $segment['sequence'] ?? 0 );
            if ( $sequence <= $last_sequence ) {
                $errors[] = self::issue( $scope, 'segments.' . $i . '.sequence', 'Segment sequence must be strictly increasing.' );
            }
            $last_sequence = $sequence;
        }

        $stays = is_array( $program['stays'] ?? null ) ? $program['stays'] : array();
        foreach ( $stays as $i => $stay ) {
            $hotel_id = strtoupper( trim( (string) ( $stay['hotel_id'] ?? '' ) ) );
            $fallback = trim( (string) ( $stay['unresolved_hotel_name'] ?? '' ) );
            if ( ! $hotel_id && ! $fallback ) {
                $warnings[] = self::issue( $scope, 'stays.' . $i, 'Stay has neither a Stable Hotel ID nor an explicit TBD hotel label.', 'warning' );
                continue;
            }
            if ( $hotel_id ) {
                $resolved = STPI_Hotel_Adapter::resolve( $hotel_id );
                $hotel_results[] = $resolved;
                if ( 'resolved' !== $resolved['status'] ) {
                    $warnings[] = self::issue( $scope, 'stays.' . $i . '.hotel_id', 'Hotel reference status: ' . $resolved['status'] . '.', 'warning' );
                }
            }
        }

        $pricing = is_array( $program['pricing'] ?? null ) ? $program['pricing'] : array();
        $price_type = $pricing['price_type'] ?? '';
        if ( $price_type ) { self::enum( $pricing, 'price_type', $enums['price_type'], $scope, $errors, 'pricing.price_type' ); }
        $currency = strtoupper( trim( (string) ( $pricing['currency'] ?? '' ) ) );
        if ( $currency && ! preg_match( '/^[A-Z]{3}$/', $currency ) ) {
            $errors[] = self::issue( $scope, 'pricing.currency', 'Currency must be a three-letter ISO code.' );
        }
        $entries = is_array( $pricing['entries'] ?? null ) ? $pricing['entries'] : array();
        foreach ( $entries as $i => $entry ) {
            if ( isset( $entry['amount'] ) && ( ! is_numeric( $entry['amount'] ) || (float) $entry['amount'] < 0 ) ) {
                $errors[] = self::issue( $scope, 'pricing.entries.' . $i . '.amount', 'Price must be a non-negative number.' );
            }
        }
        if ( 'on_request' !== $price_type && empty( $entries ) ) {
            $warnings[] = self::issue( $scope, 'pricing.entries', 'No structured price entries supplied.', 'warning' );
        }

        $provenance = is_array( $program['provenance'] ?? null ) ? $program['provenance'] : array();
        if ( empty( $provenance['source_type'] ) ) {
            $errors[] = self::issue( $scope, 'provenance.source_type', 'Source type is required.' );
        }
        if ( empty( $provenance['verified_at'] ) ) {
            $warnings[] = self::issue( $scope, 'provenance.verified_at', 'Verification date is missing.', 'warning' );
        }

        if ( in_array( $workflow['editorial'] ?? '', array( 'approved', 'published' ), true ) ) {
            foreach ( $hotel_results as $hotel ) {
                if ( 'resolved' !== $hotel['status'] ) {
                    $errors[] = self::issue( $scope, 'stays.hotel_id', 'Approved programs require every Hotel ID to resolve.' );
                    continue;
                }
                if ( 'verified' !== ( $hotel['verification_status'] ?? '' ) || 'published' !== ( $hotel['workflow_status'] ?? '' ) ) {
                    $errors[] = self::issue( $scope, 'stays.hotel_id', 'Approved programs require verified + published Hotel Intelligence records.' );
                }
            }
            if ( empty( $provenance['verified_at'] ) ) { $errors[] = self::issue( $scope, 'provenance.verified_at', 'Approved programs require a verification event.' ); }
        }

        $score = self::completeness_score( $program, $hotel_results );
        $publish_gate = empty( $errors ) && $score >= 90 ? 'READY' : ( empty( $errors ) ? 'REVIEW' : 'BLOCKED' );

        $plan = self::operation_plan( $program_id, STPI_Contract::payload_hash( $program ) );
        return array(
            'row'           => $row_number,
            'program_id'    => $program_id,
            'program_code'  => (string) ( $program['program_code'] ?? '' ),
            'title'         => (string) ( $program['title'] ?? '' ),
            'service_type'  => (string) ( $program['service_type'] ?? '' ),
            'operation'     => $plan,
            'score'         => $score,
            'publish_gate'  => $publish_gate,
            'hotel_results' => $hotel_results,
            'errors'        => $errors,
            'warnings'      => $warnings,
        );
    }

    private static function operation_plan( $program_id, $hash ) {
        if ( ! $program_id ) { return 'CREATE_CANDIDATE'; }
        $ids = get_posts( array(
            'post_type'      => 'stpi_program',
            'post_status'    => 'any',
            'posts_per_page' => 2,
            'fields'         => 'ids',
            'meta_key'       => '_stpi_program_id',
            'meta_value'     => $program_id,
            'no_found_rows'  => true,
        ) );
        if ( count( $ids ) > 1 ) { return 'CONFLICT'; }
        if ( ! $ids ) { return 'CREATE_CANDIDATE'; }
        $stored_hash = (string) get_post_meta( (int) $ids[0], '_stpi_payload_hash', true );
        return hash_equals( $stored_hash, $hash ) ? 'UNCHANGED' : 'UPDATE_CANDIDATE';
    }

    private static function completeness_score( $program, $hotels ) {
        $score = 0;
        $schedule = is_array( $program['schedule'] ?? null ) ? $program['schedule'] : array();
        $pricing = is_array( $program['pricing'] ?? null ) ? $program['pricing'] : array();
        $media = is_array( $program['media'] ?? null ) ? $program['media'] : array();
        $seo = is_array( $program['seo'] ?? null ) ? $program['seo'] : array();
        if ( ! empty( $program['title'] ) && ! empty( $program['program_code'] ) ) { $score += 10; }
        if ( ! empty( $schedule['start_date'] ) && ! empty( $schedule['end_date'] ) ) { $score += 20; }
        elseif ( ! empty( $schedule['date_precision'] ) ) { $score += 8; }
        if ( ! empty( $program['destinations'] ) && ! empty( $program['segments'] ) ) { $score += 20; }
        if ( ! empty( $pricing['entries'] ) || 'on_request' === ( $pricing['price_type'] ?? '' ) ) { $score += 15; }
        if ( ! empty( $program['stays'] ) ) {
            $score += 8;
            if ( $hotels && count( array_filter( $hotels, function( $h ) { return 'resolved' === $h['status']; } ) ) === count( $hotels ) ) { $score += 7; }
        }
        if ( ! empty( $program['inclusions'] ) || ! empty( $program['exclusions'] ) ) { $score += 10; }
        if ( ! empty( $program['transport'] ) ) { $score += 10; }
        if ( ! empty( $media['hero_image_url'] ) ) { $score += 5; }
        if ( ! empty( $seo['meta_description'] ) || ! empty( $seo['primary_topic'] ) ) { $score += 5; }
        return min( 100, $score );
    }

    private static function required_text( $data, $key, $scope, &$errors ) {
        if ( ! isset( $data[ $key ] ) || '' === trim( (string) $data[ $key ] ) ) {
            $errors[] = self::issue( $scope, $key, ucfirst( str_replace( '_', ' ', $key ) ) . ' is required.' );
        }
    }

    private static function enum( $data, $key, $allowed, $scope, &$errors, $path = '' ) {
        $value = $data[ $key ] ?? null;
        if ( ! is_string( $value ) || ! in_array( $value, $allowed, true ) ) {
            $errors[] = self::issue( $scope, $path ?: $key, 'Allowed values: ' . implode( ', ', $allowed ) . '.' );
        }
    }

    private static function valid_date( $date ) {
        $parsed = DateTime::createFromFormat( '!Y-m-d', $date );
        return $parsed && $parsed->format( 'Y-m-d' ) === $date;
    }

    private static function issue( $scope, $field, $message, $severity = 'error' ) {
        return array( 'severity' => $severity, 'scope' => $scope, 'field' => $field, 'message' => $message );
    }

    private static function report( $batch, $rows, $errors, $warnings ) {
        $operations = array();
        foreach ( $rows as $row ) { $operations[ $row['operation'] ] = 1 + ( $operations[ $row['operation'] ] ?? 0 ); }
        return array(
            'shadow_mode'     => true,
            'writes_allowed'  => false,
            'schema_version'  => (string) ( $batch['schema_version'] ?? '' ),
            'source_mode'     => (string) ( is_array( $batch['source'] ?? null ) ? ( $batch['source']['mode'] ?? 'partial' ) : 'partial' ),
            'program_count'   => count( $rows ),
            'ready_count'     => count( array_filter( $rows, function( $row ) { return 'READY' === $row['publish_gate']; } ) ),
            'blocked_count'   => count( array_filter( $rows, function( $row ) { return 'BLOCKED' === $row['publish_gate']; } ) ),
            'operations'      => $operations,
            'archive_policy'  => 'Missing rows are never deleted or archived automatically; full snapshots may only create review candidates.',
            'errors'          => $errors,
            'warnings'        => $warnings,
            'programs'        => $rows,
        );
    }
}
