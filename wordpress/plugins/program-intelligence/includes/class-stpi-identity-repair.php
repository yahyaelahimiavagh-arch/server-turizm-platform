<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class STPI_Identity_Repair {
    const STAGE_TTL = 1800;

    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'menu' ), 82 );
        add_action( 'admin_post_stpi_identity_repair_stage', array( __CLASS__, 'handle_stage' ) );
        add_action( 'admin_post_stpi_identity_repair_commit', array( __CLASS__, 'handle_commit' ) );
    }

    public static function menu() {
        add_submenu_page(
            'stpi-dashboard',
            'Identity Repair',
            'Identity Repair',
            'manage_options',
            'stpi-identity-repair',
            array( __CLASS__, 'render' )
        );
    }

    public static function render() {
        self::guard();
        $stage_key = isset( $_GET['stage'] ) ? sanitize_key( wp_unslash( $_GET['stage'] ) ) : '';
        $result_key = isset( $_GET['repair_result'] ) ? sanitize_key( wp_unslash( $_GET['repair_result'] ) ) : '';
        $stage = $stage_key ? get_transient( self::key( 'stage', $stage_key ) ) : false;
        $result = $result_key ? get_transient( self::key( 'result', $result_key ) ) : false;
        ?>
        <div class="wrap stpi-wrap">
            <h1>Stable-ID Identity Repair</h1>
            <div class="stpi-lock"><strong>EXPLICIT REPAIR ONLY</strong><span>This tool never deletes Program entities and never allocates/recycles Stable IDs. It is only for administrator-reviewed recovery from proven source-row identity drift.</span></div>
            <?php if ( is_array( $result ) ) { self::render_result( $result ); } ?>
            <form class="stpi-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
                <input type="hidden" name="action" value="stpi_identity_repair_stage">
                <?php wp_nonce_field( 'stpi_identity_repair_stage', 'stpi_nonce' ); ?>
                <label for="stpi-repair-json"><strong>Paste identity repair manifest JSON</strong></label>
                <textarea id="stpi-repair-json" name="stpi_repair_json" rows="10" spellcheck="false"></textarea>
                <label for="stpi-repair-file"><strong>or choose a .json file (maximum 2 MB)</strong></label>
                <input id="stpi-repair-file" type="file" name="stpi_repair_file" accept="application/json,.json">
                <p><button class="button button-primary" type="submit">Stage Identity Repair</button></p>
            </form>
            <?php if ( is_array( $stage ) ) { self::render_stage( $stage_key, $stage ); } ?>
        </div>
        <?php
    }

    public static function handle_stage() {
        self::guard();
        check_admin_referer( 'stpi_identity_repair_stage', 'stpi_nonce' );
        $raw = self::request_json();
        if ( is_wp_error( $raw ) ) { self::redirect_result( array( 'status'=>'error', 'message'=>$raw->get_error_message() ) ); }
        $manifest = json_decode( $raw, true, 128 );
        if ( JSON_ERROR_NONE !== json_last_error() ) { self::redirect_result( array( 'status'=>'error', 'message'=>'Invalid JSON: ' . json_last_error_msg() ) ); }
        $plan = self::preflight( $manifest );
        $key = strtolower( wp_generate_password( 16, false, false ) );
        set_transient( self::key( 'stage', $key ), array( 'manifest'=>$manifest, 'plan'=>$plan, 'staged_at'=>gmdate( DATE_W3C ) ), self::STAGE_TTL );
        wp_safe_redirect( admin_url( 'admin.php?page=stpi-identity-repair&stage=' . rawurlencode( $key ) ) );
        exit;
    }

    public static function handle_commit() {
        self::guard();
        check_admin_referer( 'stpi_identity_repair_commit', 'stpi_nonce' );
        $stage_key = isset( $_POST['stage_key'] ) ? sanitize_key( wp_unslash( $_POST['stage_key'] ) ) : '';
        $stage = $stage_key ? get_transient( self::key( 'stage', $stage_key ) ) : false;
        if ( ! is_array( $stage ) || ! is_array( $stage['manifest'] ?? null ) ) {
            self::redirect_result( array( 'status'=>'error', 'message'=>'The staged repair expired. Stage the manifest again.' ) );
        }
        if ( trim( (string) ( $_POST['confirm_phrase'] ?? '' ) ) !== 'REPAIR IDENTITIES' ) {
            self::redirect_result( array( 'status'=>'error', 'message'=>'Type REPAIR IDENTITIES exactly to confirm.' ) );
        }
        $manifest = $stage['manifest'];
        $plan = self::preflight( $manifest );
        if ( ! empty( $plan['errors'] ) ) {
            self::redirect_result( array( 'status'=>'error', 'message'=>'Repair blocked because runtime identity state changed. Re-stage and inspect.', 'plan'=>$plan ) );
        }

        $user = wp_get_current_user();
        $verified_at = gmdate( DATE_W3C );
        $verified_by = trim( (string) $user->display_name ) . ' (WP user #' . (int) $user->ID . ')';
        $snapshots = array();
        foreach ( $plan['rows'] as $row ) {
            $snap = STPI_Store::identity_repair_snapshot( $row['post_id'] );
            if ( is_wp_error( $snap ) ) { self::redirect_result( array( 'status'=>'error', 'message'=>'Snapshot failed before any repair write: ' . $snap->get_error_message() ) ); }
            $snapshots[ $row['target_program_id'] ] = $snap;
        }

        $applied = array();
        $failure = null;
        foreach ( $plan['rows'] as $row ) {
            $entry = $manifest['repairs'][ $row['manifest_index'] ];
            $saved = STPI_Store::identity_repair_save(
                $row['post_id'],
                $entry['desired_program'],
                $manifest['source'],
                $row['target_program_id'],
                (string) ( $entry['restore_editorial'] ?? 'needs_review' ),
                (string) $manifest['repair_id'],
                $verified_at,
                $verified_by
            );
            if ( is_wp_error( $saved ) ) { $failure = $row['target_program_id'] . ': ' . $saved->get_error_message(); break; }
            $applied[] = $row['target_program_id'];
        }

        if ( $failure ) {
            $rollback_errors = array();
            foreach ( array_reverse( $applied ) as $program_id ) {
                $restored = STPI_Store::identity_repair_restore_snapshot( $snapshots[ $program_id ] );
                if ( is_wp_error( $restored ) ) { $rollback_errors[] = $program_id . ': ' . $restored->get_error_message(); }
            }
            STPI_Audit::log( 'identity_repair_failed', '', array( 'repair_id'=>$manifest['repair_id'], 'failure'=>$failure, 'rollback_errors'=>$rollback_errors ) );
            self::redirect_result( array( 'status'=>'error', 'message'=>'Identity repair failed and rollback was attempted. ' . $failure . ( $rollback_errors ? ' Rollback errors: ' . implode( ' | ', $rollback_errors ) : ' Rollback PASS.' ) ) );
        }

        foreach ( $plan['rows'] as $row ) {
            update_post_meta( $row['post_id'], '_stpi_identity_repair_snapshots', array_merge(
                (array) get_post_meta( $row['post_id'], '_stpi_identity_repair_snapshots', true ),
                array( array( 'repair_id'=>$manifest['repair_id'], 'at'=>$verified_at, 'before'=>$snapshots[ $row['target_program_id'] ] ) )
            ) );
            STPI_Audit::log( 'identity_repaired', $row['target_program_id'], array(
                'repair_id'=>$manifest['repair_id'],
                'from_source_row'=>$row['current_source_row'],
                'to_source_row'=>$row['desired_source_row'],
                'refresh_public_registry'=>$row['refresh_public_registry'],
            ) );
        }

        $refresh = array();
        $review = array();
        foreach ( $plan['rows'] as $row ) {
            if ( $row['refresh_public_registry'] ) { $refresh[] = $row['target_program_id']; }
            if ( 'needs_review' === $row['restore_editorial'] ) { $review[] = $row['target_program_id']; }
        }
        update_option( 'stpi_identity_repair_latest', array(
            'repair_id'=>(string)$manifest['repair_id'],
            'committed_at'=>$verified_at,
            'targets'=>array_column( $plan['rows'], 'target_program_id' ),
            'public_refresh_ids'=>$refresh,
            'review_ids'=>$review,
            'source_export_id'=>(string)($manifest['source_export_id'] ?? ''),
        ), false );

        delete_transient( self::key( 'stage', $stage_key ) );
        STPI_Audit::log( 'identity_repair_committed', '', array( 'repair_id'=>$manifest['repair_id'], 'count'=>count($plan['rows']), 'public_refresh_ids'=>$refresh, 'review_ids'=>$review ) );
        self::redirect_result( array(
            'status'=>'success',
            'message'=>'Identity repair committed transactionally: ' . count( $plan['rows'] ) . ' Stable-ID entities repaired; 0 deleted. Public registry hashes now require the dedicated Publishing Integration refresh.',
            'plan'=>$plan
        ) );
    }

    private static function preflight( $manifest ) {
        $out = array( 'rows'=>array(), 'errors'=>array() );
        if ( ! is_array( $manifest ) || ( $manifest['schema'] ?? '' ) !== 'st-tde-identity-repair/v1' ) {
            $out['errors'][] = 'Unsupported identity repair schema.'; return $out;
        }
        $repair_id = trim( (string) ( $manifest['repair_id'] ?? '' ) );
        $source = is_array( $manifest['source'] ?? null ) ? $manifest['source'] : array();
        $repairs = is_array( $manifest['repairs'] ?? null ) ? array_values( $manifest['repairs'] ) : array();
        if ( '' === $repair_id || ! $repairs || count( $repairs ) > 50 ) { $out['errors'][] = 'Repair manifest must contain 1-50 explicit repairs and a repair_id.'; return $out; }

        $targets = array();
        foreach ( $repairs as $index => $entry ) {
            if ( ! is_array( $entry ) ) { $out['errors'][] = 'Repair entry #' . ( $index + 1 ) . ' is invalid.'; continue; }
            $target = strtoupper( trim( (string) ( $entry['target_program_id'] ?? '' ) ) );
            if ( ! preg_match( '/^STP-[0-9]{6}$/D', $target ) || in_array( $target, array( 'STP-000036','STP-000037' ), true ) ) { $out['errors'][] = 'Invalid/protected target at entry #' . ( $index + 1 ) . '.'; continue; }
            if ( isset( $targets[ $target ] ) ) { $out['errors'][] = 'Duplicate repair target: ' . $target; continue; }
            $targets[ $target ] = true;
        }
        if ( $out['errors'] ) { return $out; }

        $desired_keys = array();
        foreach ( $repairs as $index => $entry ) {
            $target = strtoupper( trim( (string) $entry['target_program_id'] ) );
            $ids = STPI_Store::find_by_program_id( $target );
            if ( count( $ids ) !== 1 ) { $out['errors'][] = $target . ': Stable ID missing or duplicated.'; continue; }
            $post_id = (int) $ids[0];
            $current = STPI_Store::get_program( $post_id );
            if ( ! $current ) { $out['errors'][] = $target . ': stored Program payload missing.'; continue; }

            $expected = is_array( $entry['expected_current'] ?? null ) ? $entry['expected_current'] : array();
            $current_row = (int) ( $current['provenance']['source_row'] ?? 0 );
            $expected_row = (int) ( $expected['source_row'] ?? 0 );
            if ( $expected_row && $current_row !== $expected_row ) { $out['errors'][] = $target . ': current source row changed (' . $current_row . ' != ' . $expected_row . ').'; continue; }
            foreach ( array( 'program_code','title' ) as $field ) {
                if ( array_key_exists( $field, $expected ) && (string)( $current[ $field ] ?? '' ) !== (string)$expected[ $field ] ) { $out['errors'][] = $target . ': current ' . $field . ' no longer matches staged evidence.'; continue 2; }
            }
            foreach ( array( 'start_date','end_date' ) as $field ) {
                if ( array_key_exists( $field, $expected ) && (string)( $current['schedule'][ $field ] ?? '' ) !== (string)$expected[ $field ] ) { $out['errors'][] = $target . ': current ' . $field . ' no longer matches staged evidence.'; continue 2; }
            }

            $desired = is_array( $entry['desired_program'] ?? null ) ? $entry['desired_program'] : array();
            $desired_row = (int) ( $desired['provenance']['source_row'] ?? 0 );
            if ( $desired_row !== (int) ( $entry['desired_source_row'] ?? 0 ) ) { $out['errors'][] = $target . ': desired source row mismatch.'; continue; }
            $desired_key = STPI_Store::source_key( $source, $desired );
            if ( isset( $desired_keys[ $desired_key ] ) ) { $out['errors'][] = $target . ': duplicate desired source identity.'; continue; }
            $desired_keys[ $desired_key ] = $target;

            $candidate = $desired;
            $candidate['program_id'] = $target;
            if ( ! isset( $candidate['workflow'] ) || ! is_array( $candidate['workflow'] ) ) { $candidate['workflow'] = array(); }
            $restore_editorial = (string) ( $entry['restore_editorial'] ?? 'needs_review' );
            if ( ! in_array( $restore_editorial, array( 'approved','needs_review' ), true ) ) { $out['errors'][] = $target . ': invalid restore_editorial.'; continue; }
            $candidate['workflow']['editorial'] = $restore_editorial;

            /*
             * Identity Repair is itself an administrator-confirmed verification event.
             * The commit path writes the real repair-time verified_at / verified_by values
             * transactionally via identity_repair_save(). Preflight must validate the
             * post-repair canonical state, not reject approved payloads merely because the
             * source Sheet export intentionally carries verified_at=null.
             * These preview-only values are never persisted.
             */
            if ( 'approved' === $restore_editorial ) {
                if ( ! isset( $candidate['provenance'] ) || ! is_array( $candidate['provenance'] ) ) { $candidate['provenance'] = array(); }
                if ( empty( $candidate['provenance']['verified_at'] ) ) { $candidate['provenance']['verified_at'] = gmdate( DATE_W3C ); }
                if ( empty( $candidate['provenance']['verified_by'] ) ) { $candidate['provenance']['verified_by'] = 'Identity Repair preflight (preview only)'; }
            }
            $report = STPI_Validator::validate_batch( array(
                'schema_version'=>STPI_SCHEMA_VERSION,
                'source'=>$source,
                'programs'=>array( $candidate ),
            ) );
            if ( ! empty( $report['errors'] ) ) {
                $msg = (string) ( $report['errors'][0]['message'] ?? 'validation failed' );
                $out['errors'][] = $target . ': desired canonical payload invalid — ' . $msg; continue;
            }

            $out['rows'][] = array(
                'manifest_index'=>$index,
                'target_program_id'=>$target,
                'post_id'=>$post_id,
                'current_source_row'=>$current_row,
                'current_code'=>(string)($current['program_code'] ?? ''),
                'current_title'=>(string)($current['title'] ?? ''),
                'desired_source_row'=>$desired_row,
                'desired_code'=>(string)($desired['program_code'] ?? ''),
                'desired_title'=>(string)($desired['title'] ?? ''),
                'restore_editorial'=>$restore_editorial,
                'refresh_public_registry'=>! empty( $entry['refresh_public_registry'] ),
                'desired_source_key'=>$desired_key,
            );
        }

        if ( $out['errors'] ) { return $out; }

        // Desired source locators may currently belong to another member of this same repair rotation,
        // but never to an unrelated Stable-ID entity.
        foreach ( $out['rows'] as $row ) {
            $holders = STPI_Store::find_by_source_key( $row['desired_source_key'] );
            foreach ( $holders as $holder_post_id ) {
                $holder_program = STPI_Store::get_program( (int) $holder_post_id );
                $holder_id = (string) ( $holder_program['program_id'] ?? '' );
                if ( $holder_id && ! isset( $targets[ $holder_id ] ) ) {
                    $out['errors'][] = $row['target_program_id'] . ': desired source locator is owned by unrelated ' . $holder_id . '.';
                }
            }
        }
        return $out;
    }

    private static function render_stage( $key, $stage ) {
        $plan = is_array( $stage['plan'] ?? null ) ? $stage['plan'] : array();
        $errors = (array) ( $plan['errors'] ?? array() );
        echo '<section class="stpi-report"><h2>Identity repair preflight</h2>';
        echo '<p><strong>Repairs:</strong> ' . esc_html( (string) count( (array)( $plan['rows'] ?? array() ) ) ) . ' · <strong>Errors:</strong> ' . esc_html( (string) count( $errors ) ) . ' · <strong>Delete operations:</strong> 0</p>';
        if ( $errors ) {
            echo '<div class="notice notice-error inline"><p><strong>BLOCKED.</strong> ' . esc_html( implode( ' | ', array_slice( $errors, 0, 8 ) ) ) . '</p></div></section>'; return;
        }
        echo '<div class="stpi-table-wrap"><table class="widefat striped"><thead><tr><th>Stable ID</th><th>Current binding</th><th>Desired binding</th><th>Editorial</th><th>Registry</th></tr></thead><tbody>';
        foreach ( $plan['rows'] as $row ) {
            echo '<tr><td><code>' . esc_html( $row['target_program_id'] ) . '</code></td><td>row ' . esc_html( (string)$row['current_source_row'] ) . ' · ' . esc_html( $row['current_code'] ) . '<br>' . esc_html( $row['current_title'] ) . '</td><td><strong>row ' . esc_html( (string)$row['desired_source_row'] ) . ' · ' . esc_html( $row['desired_code'] ) . '</strong><br>' . esc_html( $row['desired_title'] ) . '</td><td>' . esc_html( $row['restore_editorial'] ) . '</td><td>' . ( $row['refresh_public_registry'] ? 'REFRESH AFTER COMMIT' : 'KEEP PRIVATE' ) . '</td></tr>';
        }
        echo '</tbody></table></div>';
        echo '<div class="notice notice-warning inline"><p>This commit rewrites canonical payload/source bindings only for the exact listed Stable IDs, creates immutable pre-repair snapshots, and deletes nothing. A separate Publishing Integration registry refresh is required immediately afterward for previously public routes.</p></div>';
        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="stpi_identity_repair_commit"><input type="hidden" name="stage_key" value="' . esc_attr( $key ) . '">';
        wp_nonce_field( 'stpi_identity_repair_commit', 'stpi_nonce' );
        echo '<label><strong>Type exactly:</strong> <code>REPAIR IDENTITIES</code><br><input type="text" name="confirm_phrase" autocomplete="off" required></label><p><button class="button button-primary">Commit Exact Identity Repair</button></p></form></section>';
    }

    private static function render_result( $result ) {
        $class = 'success' === ( $result['status'] ?? '' ) ? 'notice-success' : 'notice-error';
        echo '<div class="notice ' . esc_attr( $class ) . ' inline"><p>' . esc_html( $result['message'] ?? '' ) . '</p></div>';
    }

    private static function request_json() {
        $raw = isset( $_POST['stpi_repair_json'] ) ? (string) wp_unslash( $_POST['stpi_repair_json'] ) : '';
        if ( ! empty( $_FILES['stpi_repair_file']['tmp_name'] ) ) {
            if ( ! empty( $_FILES['stpi_repair_file']['error'] ) || (int) $_FILES['stpi_repair_file']['size'] > 2097152 ) { return new WP_Error( 'stpi_repair_upload', 'The JSON file could not be read or is larger than 2 MB.' ); }
            $raw = (string) file_get_contents( $_FILES['stpi_repair_file']['tmp_name'] );
        }
        if ( '' === trim( $raw ) || strlen( $raw ) > 2097152 ) { return new WP_Error( 'stpi_repair_json', 'Provide repair JSON no larger than 2 MB.' ); }
        return $raw;
    }

    private static function redirect_result( $result ) {
        $key = strtolower( wp_generate_password( 14, false, false ) );
        set_transient( self::key( 'result', $key ), $result, 600 );
        wp_safe_redirect( admin_url( 'admin.php?page=stpi-identity-repair&repair_result=' . rawurlencode( $key ) ) );
        exit;
    }

    private static function key( $kind, $key ) { return 'stpi_identity_' . $kind . '_' . get_current_user_id() . '_' . $key; }
    private static function guard() { if ( ! current_user_can( 'manage_options' ) ) { wp_die( esc_html__( 'Insufficient permissions.', 'stpi' ) ); } }
}
