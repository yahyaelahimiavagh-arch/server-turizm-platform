<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class STPI_Store {
    public static function source_key( $source, $program ) {
        $provenance = is_array( $program['provenance'] ?? null ) ? $program['provenance'] : array();
        $uuid = STPI_Contract::source_uuid( $program );
        if ( $uuid ) {
            return hash( 'sha256', wp_json_encode( array(
                'source_type' => 'google_sheets_uuid_v1',
                'document_ref' => (string) ( $source['document_ref'] ?? '' ),
                'worksheet' => (string) ( $source['worksheet'] ?? '' ),
                'source_uuid' => $uuid,
            ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) );
        }
        $identity = array(
            'source_type'  => (string) ( $source['type'] ?? $provenance['source_type'] ?? '' ),
            'document_ref' => (string) ( $source['document_ref'] ?? '' ),
            'worksheet'    => (string) ( $source['worksheet'] ?? '' ),
            'source_ref'   => (string) ( $provenance['source_ref'] ?? '' ),
            'source_row'   => (int) ( $provenance['source_row'] ?? 0 ),
        );
        return hash( 'sha256', wp_json_encode( $identity, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) );
    }

    public static function find_by_source_key( $source_key ) {
        $source_key = trim( (string) $source_key );
        if ( '' === $source_key ) { return array(); }
        return self::find_ids( '_stpi_source_key', $source_key );
    }


    public static function find_by_program_id( $program_id ) {
        $program_id = strtoupper( trim( (string) $program_id ) );
        if ( ! preg_match( '/^STP-[0-9]{6}$/D', $program_id ) ) { return array(); }
        return self::find_ids( '_stpi_program_id', $program_id );
    }

    public static function identity_fingerprint( $program ) {
    /*
     * Durable source-row identity guard.
     *
     * IMPORTANT:
     * Title is intentionally NOT identity.
     * Titles are editable presentation/content and may change during
     * normal SEO/editorial work without changing the Program entity.
     *
     * program_code + service_type + locale are used only as the
     * defensive fingerprint when no explicit Stable Program ID is
     * available.
     */
    $code    = trim( (string) ( $program['program_code'] ?? '' ) );
    $service = trim( (string) ( $program['service_type'] ?? '' ) );
    $locale  = trim( (string) ( $program['locale'] ?? '' ) );

    $identity = array(
        'program_code' => $code,
        'service_type' => $service,
        'locale'       => $locale,
    );

    return hash(
        'sha256',
        wp_json_encode(
            $identity,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        )
    );
}

    public static function source_removal_intent( $post_id ) {
        $intent = get_post_meta( absint( $post_id ), '_stpi_source_removal_intent', true );
        return is_array( $intent ) ? $intent : array();
    }

    public static function archive_eligible( $post_id, $program ) {
        if ( STPI_Lifecycle::archive_eligible( $program ) ) { return true; }
        return ! empty( self::source_removal_intent( $post_id ) );
    }

    public static function archive_reason( $post_id, $program ) {
        $intent = self::source_removal_intent( $post_id );
        if ( $intent ) { return 'source_removal'; }
        $workflow = is_array( $program['workflow'] ?? null ) ? $program['workflow'] : array();
        if ( 'cancelled' === ( $workflow['schedule'] ?? '' ) ) { return 'cancelled'; }
        if ( 'completed' === STPI_Lifecycle::temporal_state( $program ) ) { return 'completed'; }
        return '';
    }

    public static function plan_program( $source, $program, $controlled_program_id = '' ) {
        $program_id = strtoupper( trim( (string) ( $program['program_id'] ?? '' ) ) );
        if ( $controlled_program_id ) { $program_id = strtoupper( trim( (string) $controlled_program_id ) ); }
        $source_key = self::source_key( $source, $program );
        $source_content = $program;
        if ( 'google_sheets' === ( $source['type'] ?? '' ) && STPI_Contract::source_uuid( $program ) ) {
            $source_content['program_id'] = null; // AQ is an identity control, not mutable source content.
        }
        $incoming_hash = STPI_Contract::payload_hash( $source_content );
        $ids = array();
        $uuid_sources = get_option( 'stpi_uuid_sources', array() );
        if ( 'google_sheets' === ( $source['type'] ?? '' ) && ! STPI_Contract::source_uuid( $program )
            && ! empty( $uuid_sources[STPI_Contract::uuid_source_scope( $source )] ) ) {
            return array( 'operation' => 'CONFLICT', 'reason' => 'STABLE_ROW_UUID_REQUIRED: update the Apps Script exporter.', 'post_id' => 0, 'program_id' => $program_id, 'source_key' => $source_key, 'incoming_hash' => $incoming_hash );
        }

        $provenance = is_array( $program['provenance'] ?? null ) ? $program['provenance'] : array();
        $has_source_locator = '' !== trim( (string) ( $source['document_ref'] ?? '' ) )
            || '' !== trim( (string) ( $provenance['source_ref'] ?? '' ) )
            || 0 < absint( $provenance['source_row'] ?? 0 );
        if ( ! $program_id && ! $has_source_locator ) {
            return array( 'operation' => 'CONFLICT', 'reason' => 'A new candidate requires an explicit source locator; names and program codes are not identity.', 'post_id' => 0, 'program_id' => '', 'source_key' => $source_key, 'incoming_hash' => $incoming_hash );
        }

        if ( $program_id ) {
            $ids = self::find_ids( '_stpi_program_id', $program_id );
            if ( ! $ids ) {
                return array( 'operation' => 'CONFLICT', 'reason' => 'Unknown externally supplied Program ID.', 'post_id' => 0, 'program_id' => $program_id, 'source_key' => $source_key, 'incoming_hash' => $incoming_hash );
            }
        } else {
            $ids = self::find_ids( '_stpi_source_key', $source_key );
        }

        if ( count( $ids ) > 1 ) {
            return array( 'operation' => 'CONFLICT', 'reason' => 'More than one candidate matches this identity.', 'post_id' => 0, 'program_id' => $program_id, 'source_key' => $source_key, 'incoming_hash' => $incoming_hash );
        }
        if ( ! $ids ) {
            return array( 'operation' => 'CREATE_CANDIDATE', 'reason' => '', 'post_id' => 0, 'program_id' => '', 'source_key' => $source_key, 'incoming_hash' => $incoming_hash );
        }

        $post_id = (int) $ids[0];
        $bound_uuid = STPI_Contract::source_uuid( self::get_program( $post_id ) );
        if ( $bound_uuid && $bound_uuid !== STPI_Contract::source_uuid( $program ) ) {
            return array( 'operation' => 'CONFLICT', 'reason' => 'STABLE_SOURCE_UUID_MISMATCH', 'post_id' => $post_id, 'program_id' => $program_id, 'source_key' => $source_key, 'incoming_hash' => $incoming_hash );
        }

        if ( $bound_uuid && ! hash_equals( (string) get_post_meta( $post_id, '_stpi_source_key', true ), $source_key ) ) {
            return array( 'operation' => 'CONFLICT', 'reason' => 'STABLE_SOURCE_SCOPE_MISMATCH', 'post_id' => $post_id, 'program_id' => $program_id, 'source_key' => $source_key, 'incoming_hash' => $incoming_hash );
        }

        // v0.3.4 hard gate: a row locator is not a durable business identity.
        // If the same source row suddenly points at a different code/title identity,
        // block instead of overwriting the existing Stable-ID entity.
        if ( ! $program_id && ! $bound_uuid ) {
            $stored_program = self::get_program( $post_id );
            if ( $stored_program ) {
                $stored_fingerprint = self::identity_fingerprint( $stored_program );
                $incoming_fingerprint = self::identity_fingerprint( $program );
                if ( ! hash_equals( $stored_fingerprint, $incoming_fingerprint ) ) {
                    return array(
                        'operation' => 'CONFLICT',
                        'reason' => 'SOURCE_ROW_IDENTITY_DRIFT: this sheet row now describes a different Program identity. Stable-ID overwrite blocked; use controlled Identity Repair / explicit Program ID reconciliation.',
                        'post_id' => $post_id,
                        'program_id' => (string) get_post_meta( $post_id, '_stpi_program_id', true ),
                        'source_key' => $source_key,
                        'incoming_hash' => $incoming_hash,
                    );
                }
            }
        }

        $stored_source_hash = (string) get_post_meta( $post_id, '_stpi_source_payload_hash', true );
        $stored_payload_hash = (string) get_post_meta( $post_id, '_stpi_payload_hash', true );
        $comparison_hash = ( $program_id && 'wordpress' === ( $source['type'] ?? '' ) ) ? $stored_payload_hash : $stored_source_hash;
        return array(
            'operation'   => $comparison_hash && hash_equals( $comparison_hash, $incoming_hash ) ? 'UNCHANGED' : 'UPDATE_CANDIDATE',
            'reason'      => '',
            'post_id'     => $post_id,
            'program_id'  => (string) get_post_meta( $post_id, '_stpi_program_id', true ),
            'source_key'  => $source_key,
            'incoming_hash'=> $incoming_hash,
        );
    }

    private static function clear_source_removal_intent( $post_id, $program_id, $batch_id ) {
        $post_id = absint( $post_id );
        if ( ! $post_id || ! self::source_removal_intent( $post_id ) ) { return; }
        delete_post_meta( $post_id, '_stpi_source_removal_intent' );
        STPI_Audit::log( 'source_removal_intent_cleared', $program_id, array( 'reason' => 'source_reappeared_in_active_import', 'batch_id' => $batch_id ) );
    }

    public static function import_batch( $batch, $verified_at, $verified_by, $uuid_targets = array() ) {
        $source = is_array( $batch['source'] ?? null ) ? $batch['source'] : array();
        $summary = array( 'created' => 0, 'updated' => 0, 'unchanged' => 0, 'conflicts' => 0, 'program_ids' => array(), 'errors' => array() );
        $batch_id = sanitize_text_field( (string) ( $batch['export_id'] ?? '' ) );
        $seen_batch_keys = array();

        foreach ( array_values( $batch['programs'] ?? array() ) as $program ) {
            if ( ! is_array( $program ) ) { continue; }
            $uuid = STPI_Contract::source_uuid( $program );
            $plan = self::plan_program( $source, $program, $uuid ? (string) ( $uuid_targets[$uuid] ?? '' ) : '' );
            if ( empty( $program['program_id'] ) && isset( $seen_batch_keys[ $plan['source_key'] ] ) ) {
                $plan['operation'] = 'CONFLICT';
                $plan['reason'] = 'Duplicate source identity inside this batch.';
            }
            $seen_batch_keys[ $plan['source_key'] ] = true;
            if ( 'CONFLICT' === $plan['operation'] ) {
                $summary['conflicts']++;
                $summary['errors'][] = (string) ( $program['program_code'] ?? 'unknown' ) . ': ' . $plan['reason'];
                continue;
            }
            if ( 'UNCHANGED' === $plan['operation'] ) {
                // Row location is bookkeeping. UUID payload hashes omit only this field,
                // so a whole-row move keeps approval and public registry pins intact.
                if ( $uuid ) {
                    $current = self::get_program( $plan['post_id'] );
                    $row = (int) ( $program['provenance']['source_row'] ?? 0 );
                    if ( $row !== (int) ( $current['provenance']['source_row'] ?? 0 ) ) {
                        $current['provenance']['source_row'] = $row;
                        $relocated = wp_update_post( array( 'ID' => (int) $plan['post_id'], 'post_content' => wp_slash( wp_json_encode( $current, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) ) ), true );
                        if ( is_wp_error( $relocated ) ) { $summary['errors'][] = $relocated->get_error_message(); continue; }
                        update_post_meta( $plan['post_id'], '_stpi_source_row', $row );
                    }
                }
                $summary['unchanged']++;
                $summary['program_ids'][] = $plan['program_id'];
                self::clear_source_removal_intent( $plan['post_id'], $plan['program_id'], $batch_id );
                STPI_Audit::log( 'candidate_unchanged', $plan['program_id'], array( 'batch_id' => $batch_id, 'source_hash' => $plan['incoming_hash'] ) );
                continue;
            }

            $program_id = $plan['program_id'];
            if ( ! $program_id ) {
                $program_id = self::allocate_program_id();
                if ( is_wp_error( $program_id ) ) {
                    $summary['errors'][] = $program_id->get_error_message();
                    continue;
                }
            }

            $stored = $program;
            if ( ! empty( $plan['post_id'] ) ) {
                $stored = STPI_Contract::carry_pending_hotels( $stored, self::get_program( $plan['post_id'] ) );
            }
            $stored['program_id'] = $program_id;
            if ( ! isset( $stored['workflow'] ) || ! is_array( $stored['workflow'] ) ) { $stored['workflow'] = array(); }
            $stored['workflow']['editorial'] = 'needs_review';
            if ( ! isset( $stored['provenance'] ) || ! is_array( $stored['provenance'] ) ) { $stored['provenance'] = array(); }
            $stored['provenance']['verified_at'] = $verified_at;
            $stored['provenance']['verified_by'] = $verified_by;
            $stored['provenance']['notes'] = trim( (string) ( $stored['provenance']['notes'] ?? '' ) . ' Imported as a private review candidate.' );

            $saved = self::save_program( $plan['post_id'], $stored, $plan['source_key'], $plan['incoming_hash'], $batch_id );
            if ( is_wp_error( $saved ) ) {
                $summary['errors'][] = $program_id . ': ' . $saved->get_error_message();
                continue;
            }
            if ( $plan['post_id'] ) { self::clear_source_removal_intent( $plan['post_id'], $program_id, $batch_id ); }
            if ( $plan['post_id'] ) { $summary['updated']++; } else { $summary['created']++; }
            $summary['program_ids'][] = $program_id;
            STPI_Audit::log( $plan['post_id'] ? 'candidate_updated' : 'candidate_created', $program_id, array( 'batch_id' => $batch_id, 'source_hash' => $plan['incoming_hash'], 'post_id' => (int) $saved ) );
        }

        if ( 'google_sheets' === ( $source['type'] ?? '' ) && ( $summary['updated'] || $summary['created'] || $summary['unchanged'] ) ) {
            foreach ( (array) ( $batch['programs'] ?? array() ) as $p ) {
                if ( STPI_Contract::source_uuid( $p ) ) {
                    $scopes = get_option( 'stpi_uuid_sources', array() );
                    if ( ! is_array( $scopes ) ) { $scopes = array(); }
                    $scopes[STPI_Contract::uuid_source_scope( $source )] = true;
                    update_option( 'stpi_uuid_sources', $scopes, false );
                    break;
                }
            }
        }
        return $summary;
    }

    public static function get_program( $post_id ) {
        $post = get_post( absint( $post_id ) );
        if ( ! $post || 'stpi_program' !== $post->post_type ) { return array(); }
        $program = json_decode( (string) $post->post_content, true );
        return is_array( $program ) ? $program : array();
    }

    public static function all_programs() {
        $ids = get_posts( array(
            'post_type'      => 'stpi_program',
            'post_status'    => array( 'private', 'draft', 'pending' ),
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'orderby'        => array( 'meta_value' => 'ASC', 'ID' => 'ASC' ),
            'meta_key'       => '_stpi_program_id',
            'no_found_rows'  => true,
        ) );
        $rows = array();
        foreach ( $ids as $post_id ) {
            $program = self::get_program( $post_id );
            if ( $program ) { $rows[] = array( 'post_id' => (int) $post_id, 'program' => $program ); }
        }
        return $rows;
    }

    public static function transition( $post_id, $action ) {
        $post_id = absint( $post_id );
        $program = self::get_program( $post_id );
        if ( ! $program ) { return new WP_Error( 'stpi_missing_program', 'Program candidate was not found.' ); }
        $program_id = (string) ( $program['program_id'] ?? '' );
        $before = (string) ( $program['workflow']['editorial'] ?? '' );
        $approval_before_hash = STPI_Contract::payload_hash( $program );

        if ( in_array( $action, array( 'approve', 'approve_hotel_pending' ), true ) ) {
            if ( 'approve_hotel_pending' === $action ) {
                if ( ! current_user_can( 'manage_options' ) || ! get_current_user_id()
                    || ! in_array( $before, array( 'draft', 'needs_review' ), true ) ) {
                    return new WP_Error( 'stpi_pending_permission', 'Only an administrator can review a pending hotel candidate.' );
                }
                // Exact scoped exception: these two programs have source-entered
                // provisional accommodation labels, NOT confirmed hotel entities.
                $named_pending_allowed = ( 'STP-000050' === $program_id && 'R1' === (string) ( $program['program_code'] ?? '' ) )
                    || ( 'STP-000051' === $program_id && 'R2' === (string) ( $program['program_code'] ?? '' ) );
                $pending_count = 0;
                $source_fields = $named_pending_allowed ? get_post_meta( $post_id, '_st2_fields', true ) : array();
                $source_fields = is_array( $source_fields ) ? $source_fields : array();
                foreach ( (array) ( $program['stays'] ?? array() ) as $i => $stay ) {
                    // R1/R2 are explicitly provisional regardless of whether a
                    // similarly named Hotel Intelligence entity already exists.
                    // A hotel entity is NOT a confirmed booking for this Program.
                    if ( $named_pending_allowed ) {
                        $city = (string) ( $stay['destination'] ?? '' );
                        $source_key = 'Madinah' === $city ? 'medinah_hotel' : ( 'Makkah' === $city ? 'makkah_hotel' : 'other_hotel' );
                        $label = trim( (string) ( $source_fields[$source_key] ?? $stay['unresolved_hotel_name'] ?? '' ) );
                        if ( $label === '' ) { $label = STPI_Contract::HOTEL_PENDING_LABEL; }
                        $program['stays'][$i]['hotel_id'] = null;
                        $program['stays'][$i]['unresolved_hotel_name'] = $label;
                        $program['stays'][$i]['pending_hotel_reviewed'] = true;
                        $pending_count++;
                        continue;
                    }
                    if ( ! empty( $stay['hotel_id'] ) ) { continue; }
                    $label = trim( (string) ( $stay['unresolved_hotel_name'] ?? '' ) );
                    if ( $label !== '' && ! STPI_Contract::pending_stay( $stay ) ) {
                        return new WP_Error( 'stpi_pending_named_hotel', 'A named unresolved hotel is not permitted for this Program; resolve it first.' );
                    }
                    if ( $label === '' ) { $program['stays'][$i]['unresolved_hotel_name'] = STPI_Contract::HOTEL_PENDING_LABEL; }
                    $program['stays'][$i]['pending_hotel_reviewed'] = true;
                    $pending_count++;
                }
                if ( ! $pending_count ) { return new WP_Error( 'stpi_no_pending_hotel', 'No pending hotel stay exists.' ); }
                $program['seo']['index_policy'] = 'noindex';
                $program['provenance']['verified_at'] = gmdate( DATE_W3C );
                $program['provenance']['verified_by'] = 'wp_user_id:' . get_current_user_id();
                if ( ! STPI_Contract::hotel_pending_ready( $program ) ) {
                    return new WP_Error( 'stpi_pending_dates', 'Pending hotel approval requires exact, consistent program and stay dates, nights and destinations.' );
                }
            }
            $program['workflow']['editorial'] = 'approved';
            $report = STPI_Validator::validate_batch( array(
                'schema_version' => STPI_SCHEMA_VERSION,
                'source' => array( 'type' => 'wordpress', 'mode' => 'partial' ),
                'programs' => array( $program ),
            ) );
            if ( ! empty( $report['errors'] ) || 'READY' !== ( $report['programs'][0]['publish_gate'] ?? '' ) ) {
                return new WP_Error( 'stpi_approval_blocked', 'Approval is blocked until validation, verification and hotel gates are READY.' );
            }
        } elseif ( 'archive' === $action ) {
            if ( ! self::archive_eligible( $post_id, $program ) ) {
                return new WP_Error( 'stpi_archive_blocked', 'Only completed/cancelled programs or exact Programı Kaldır source-removal candidates can be archived.' );
            }
            $archive_snapshot = self::archive_snapshot( $program, $post_id );
            $program['workflow']['editorial'] = 'archived';
            $program['workflow']['availability'] = 'closed';
            $program['publication_mode'] = 'archived_departure';
        } elseif ( 'restore' === $action ) {
            if ( 'archived' !== $before ) { return new WP_Error( 'stpi_restore_blocked', 'Only archived candidates can be restored.' ); }
            $program['workflow']['editorial'] = 'needs_review';
            $program['publication_mode'] = ( ! empty( $program['schedule']['start_date'] ) && ! empty( $program['schedule']['end_date'] ) ) ? 'scheduled_departure' : 'interest_campaign';
        } else {
            return new WP_Error( 'stpi_bad_action', 'Unsupported lifecycle action.' );
        }

        $result = self::save_program( $post_id, $program, (string) get_post_meta( $post_id, '_stpi_source_key', true ), (string) get_post_meta( $post_id, '_stpi_source_payload_hash', true ), (string) get_post_meta( $post_id, '_stpi_import_batch_id', true ) );
        if ( is_wp_error( $result ) ) { return $result; }
        if ( 'approve_hotel_pending' === $action ) {
            update_post_meta( $post_id, '_stpi_hotel_pending_approval_receipt', array(
                'before_hash' => $approval_before_hash,
                'after_hash' => STPI_Contract::payload_hash( $program ),
                'source_key' => (string) get_post_meta( $post_id, '_stpi_source_key', true ),
                'source_hash' => (string) get_post_meta( $post_id, '_stpi_source_payload_hash', true ),
            ) );
        }
        if ( 'archive' === $action && isset( $archive_snapshot ) ) {
            $snapshots = get_post_meta( $post_id, '_stpi_archive_snapshots', true );
            if ( ! is_array( $snapshots ) ) { $snapshots = array(); }
            $snapshots[] = $archive_snapshot;
            update_post_meta( $post_id, '_stpi_archive_snapshots', $snapshots );
            update_post_meta( $post_id, '_stpi_last_archive_reason', (string) ( $archive_snapshot['archive_reason'] ?? '' ) );
            delete_post_meta( $post_id, '_stpi_source_removal_intent' );
        } elseif ( 'restore' === $action ) {
            delete_post_meta( $post_id, '_stpi_source_removal_intent' );
        }
        STPI_Audit::log( 'program_' . $action, $program_id, array( 'before' => $before, 'after' => $program['workflow']['editorial'], 'post_id' => $post_id, 'archive_reason' => isset( $archive_snapshot ) ? (string) ( $archive_snapshot['archive_reason'] ?? '' ) : '' ) );
        return true;
    }

    private static function archive_snapshot( $program, $post_id ) {
        $hotels = array();
        foreach ( (array) ( $program['stays'] ?? array() ) as $stay ) {
            $hotel_id = strtoupper( trim( (string) ( $stay['hotel_id'] ?? '' ) ) );
            if ( $hotel_id ) { $hotels[] = STPI_Hotel_Adapter::resolve( $hotel_id ); }
        }
        $user = wp_get_current_user();
        return array(
            'archived_at' => gmdate( DATE_W3C ),
            'archived_by' => array( 'user_id' => (int) $user->ID, 'display_name' => (string) $user->display_name ),
            'archive_reason' => self::archive_reason( $post_id, $program ),
            'source_removal_intent' => self::source_removal_intent( $post_id ),
            'payload_hash'=> STPI_Contract::payload_hash( $program ),
            'program'     => $program,
            'hotel_facts' => $hotels,
        );
    }

    private static function save_program( $post_id, $program, $source_key, $source_hash, $batch_id ) {
        $program_id = strtoupper( trim( (string) ( $program['program_id'] ?? '' ) ) );
        $postarr = array(
            'post_type'    => 'stpi_program',
            'post_status'  => 'private',
            'post_title'   => wp_strip_all_tags( (string) ( $program['title'] ?? $program_id ) ),
            'post_content' => wp_slash( wp_json_encode( $program, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) ),
        );
        if ( $post_id ) { $postarr['ID'] = absint( $post_id ); }
        $saved = $post_id ? wp_update_post( $postarr, true ) : wp_insert_post( $postarr, true );
        if ( is_wp_error( $saved ) ) { return $saved; }

        $workflow = is_array( $program['workflow'] ?? null ) ? $program['workflow'] : array();
        $schedule = is_array( $program['schedule'] ?? null ) ? $program['schedule'] : array();
        $meta = array(
            '_stpi_program_id'          => $program_id,
            '_stpi_program_code'        => (string) ( $program['program_code'] ?? '' ),
            '_stpi_service_type'        => (string) ( $program['service_type'] ?? '' ),
            '_stpi_payload_hash'        => STPI_Contract::payload_hash( $program ),
            '_stpi_source_payload_hash' => (string) $source_hash,
            '_stpi_source_key'          => (string) $source_key,
            '_stpi_import_batch_id'     => (string) $batch_id,
            '_stpi_editorial_status'    => (string) ( $workflow['editorial'] ?? 'needs_review' ),
            '_stpi_schedule_status'     => (string) ( $workflow['schedule'] ?? 'tentative' ),
            '_stpi_availability_status' => (string) ( $workflow['availability'] ?? 'on_request' ),
            '_stpi_start_date'          => (string) ( $schedule['start_date'] ?? '' ),
            '_stpi_end_date'            => (string) ( $schedule['end_date'] ?? '' ),
            '_stpi_last_imported_at'    => gmdate( DATE_W3C ),
        );
        foreach ( $meta as $key => $value ) { update_post_meta( $saved, $key, $value ); }
        $uuid = STPI_Contract::source_uuid( $program );
        if ( $uuid ) {
            update_post_meta( $saved, '_stpi_source_uuid', $uuid );
        }
        return (int) $saved;
    }


    public static function identity_repair_snapshot( $post_id ) {
        $post_id = absint( $post_id );
        $post = get_post( $post_id );
        if ( ! $post || 'stpi_program' !== $post->post_type ) { return new WP_Error( 'stpi_repair_missing', 'Program candidate was not found.' ); }
        $keys = array(
            '_stpi_program_id','_stpi_program_code','_stpi_service_type','_stpi_payload_hash',
            '_stpi_source_payload_hash','_stpi_source_key','_stpi_import_batch_id',
            '_stpi_editorial_status','_stpi_schedule_status','_stpi_availability_status',
            '_stpi_start_date','_stpi_end_date','_stpi_last_imported_at',
            '_stpi_identity_repair_last','_stpi_source_uuid','_stpi_source_row',
            '_stpi_hotel_pending_approval_receipt','_stpi_uuid_binding_receipt'
        );
        $meta = array();
        foreach ( $keys as $key ) { $meta[ $key ] = get_post_meta( $post_id, $key, false ); }
        return array(
            'post_id' => $post_id,
            'post_title' => (string) $post->post_title,
            'post_content' => (string) $post->post_content,
            'post_status' => (string) $post->post_status,
            'meta' => $meta,
        );
    }

    public static function identity_repair_restore_snapshot( $snapshot ) {
        if ( ! is_array( $snapshot ) || empty( $snapshot['post_id'] ) ) { return new WP_Error( 'stpi_repair_snapshot', 'Invalid repair snapshot.' ); }
        $post_id = absint( $snapshot['post_id'] );
        $updated = wp_update_post( array(
            'ID' => $post_id,
            'post_title' => (string) ( $snapshot['post_title'] ?? '' ),
            'post_content' => wp_slash( (string) ( $snapshot['post_content'] ?? '' ) ),
            'post_status' => (string) ( $snapshot['post_status'] ?? 'private' ),
        ), true );
        if ( is_wp_error( $updated ) ) { return $updated; }
        foreach ( (array) ( $snapshot['meta'] ?? array() ) as $key => $values ) {
            delete_post_meta( $post_id, $key );
            foreach ( (array) $values as $value ) { add_post_meta( $post_id, $key, $value ); }
        }
        clean_post_cache( $post_id );
        return true;
    }

    public static function identity_repair_save( $post_id, $raw_program, $source, $target_program_id, $restore_editorial, $repair_id, $verified_at, $verified_by ) {
        $post_id = absint( $post_id );
        $target_program_id = strtoupper( trim( (string) $target_program_id ) );
        if ( ! preg_match( '/^STP-[0-9]{6}$/D', $target_program_id ) ) { return new WP_Error( 'stpi_repair_target', 'Invalid Stable Program ID.' ); }
        if ( ! is_array( $raw_program ) ) { return new WP_Error( 'stpi_repair_payload', 'Invalid canonical Program payload.' ); }

        $source_hash = STPI_Contract::payload_hash( $raw_program );
        $source_key = self::source_key( is_array( $source ) ? $source : array(), $raw_program );
        $stored = $raw_program;
        $stored['program_id'] = $target_program_id;
        if ( ! isset( $stored['workflow'] ) || ! is_array( $stored['workflow'] ) ) { $stored['workflow'] = array(); }
        $stored['workflow']['editorial'] = in_array( $restore_editorial, array( 'needs_review','approved' ), true ) ? $restore_editorial : 'needs_review';
        if ( ! isset( $stored['provenance'] ) || ! is_array( $stored['provenance'] ) ) { $stored['provenance'] = array(); }
        $stored['provenance']['verified_at'] = (string) $verified_at;
        $stored['provenance']['verified_by'] = (string) $verified_by;
        $note = trim( (string) ( $stored['provenance']['notes'] ?? '' ) );
        $repair_note = 'Identity repaired under ' . sanitize_text_field( (string) $repair_id ) . '; Stable ID preserved.';
        $stored['provenance']['notes'] = trim( $note . ' ' . $repair_note );

        $saved = self::save_program( $post_id, $stored, $source_key, $source_hash, sanitize_text_field( (string) $repair_id ) );
        if ( is_wp_error( $saved ) ) { return $saved; }
        update_post_meta( $post_id, '_stpi_identity_repair_last', array(
            'repair_id' => sanitize_text_field( (string) $repair_id ),
            'at' => gmdate( DATE_W3C ),
            'source_row' => (int) ( $raw_program['provenance']['source_row'] ?? 0 ),
            'program_id' => $target_program_id,
        ) );
        return true;
    }

    private static function allocate_program_id() {
        global $wpdb;
        $lock_name = 'stpi_program_id_allocator';
        $locked = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, %d)', $lock_name, 5 ) );
        if ( 1 !== $locked ) { return new WP_Error( 'stpi_id_lock', 'Could not acquire the Program ID allocator lock.' ); }
        try {
            $number = max( 1, absint( get_option( 'stpi_next_program_number', 1 ) ) );
            do {
                $candidate = 'STP-' . str_pad( (string) $number, 6, '0', STR_PAD_LEFT );
                $number++;
            } while ( self::find_ids( '_stpi_program_id', $candidate ) );
            update_option( 'stpi_next_program_number', $number, false );
            return $candidate;
        } finally {
            $wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock_name ) );
        }
    }

    private static function find_ids( $meta_key, $meta_value ) {
        return get_posts( array(
            'post_type'      => 'stpi_program',
            'post_status'    => array( 'private', 'draft', 'pending' ),
            'posts_per_page' => 2,
            'fields'         => 'ids',
            'meta_key'       => $meta_key,
            'meta_value'     => $meta_value,
            'no_found_rows'  => true,
        ) );
    }
}
