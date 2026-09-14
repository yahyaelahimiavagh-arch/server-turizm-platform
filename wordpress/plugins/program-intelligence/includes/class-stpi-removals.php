<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class STPI_Removals {
    const STAGE_TTL = 1800;
    const MANIFEST_SCHEMA = 'st-tde-removal-manifest/v1';

    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'menu' ), 12 );
        add_action( 'admin_post_stpi_removal_stage', array( __CLASS__, 'handle_stage' ) );
        add_action( 'admin_post_stpi_removal_commit', array( __CLASS__, 'handle_commit' ) );
    }

    public static function menu() {
        add_submenu_page(
            'stpi-dashboard',
            'Removal Review',
            'Removal Review',
            'manage_options',
            'stpi-removals',
            array( __CLASS__, 'render' )
        );
    }

    public static function render() {
        self::guard();
        $stage_key = isset( $_GET['stage'] ) ? sanitize_key( wp_unslash( $_GET['stage'] ) ) : '';
        $result_key = isset( $_GET['result'] ) ? sanitize_key( wp_unslash( $_GET['result'] ) ) : '';
        $stage = $stage_key ? get_transient( self::transient_key( 'stage', $stage_key ) ) : false;
        $result = $result_key ? get_transient( self::transient_key( 'result', $result_key ) ) : false;
        ?>
        <div class="wrap stpi-wrap">
            <h1>Programı Kaldır — Removal Review</h1>
            <div class="stpi-lock"><strong>EXPLICIT ARCHIVE ONLY</strong><span>This flow consumes only a signed-in administrator-reviewed removal manifest. It never deletes Program entities. Exact source identity must match before any archive snapshot is created.</span></div>
            <?php if ( is_array( $result ) ) : ?>
                <div class="notice <?php echo ! empty( $result['ok'] ) ? 'notice-success' : 'notice-error'; ?> inline"><p><?php echo esc_html( $result['message'] ?? '' ); ?></p></div>
            <?php endif; ?>
            <form class="stpi-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
                <input type="hidden" name="action" value="stpi_removal_stage">
                <?php wp_nonce_field( 'stpi_removal_stage', 'stpi_nonce' ); ?>
                <label for="stpi-removal-json"><strong>Paste removal manifest JSON</strong></label>
                <textarea id="stpi-removal-json" name="stpi_removal_json" rows="14" spellcheck="false" placeholder='{"schema":"st-tde-removal-manifest/v1","removals":[]}'></textarea>
                <label for="stpi-removal-file"><strong>or choose a .json file (maximum 1 MB)</strong></label>
                <input id="stpi-removal-file" type="file" name="stpi_removal_file" accept="application/json,.json">
                <p><button class="button button-primary" type="submit">Stage Removal Manifest</button></p>
            </form>
            <?php if ( is_array( $stage ) ) { self::render_stage( $stage_key, $stage ); } ?>
        </div>
        <?php
    }

    public static function handle_stage() {
        self::guard();
        check_admin_referer( 'stpi_removal_stage', 'stpi_nonce' );
        $raw = self::request_json();
        if ( is_wp_error( $raw ) ) { self::redirect_result( false, $raw->get_error_message() ); }
        $manifest = json_decode( $raw, true, 64 );
        if ( JSON_ERROR_NONE !== json_last_error() ) { self::redirect_result( false, 'Invalid JSON: ' . json_last_error_msg() ); }
        $report = self::validate_manifest( $manifest );
        $key = strtolower( wp_generate_password( 16, false, false ) );
        set_transient( self::transient_key( 'stage', $key ), array( 'manifest' => $manifest, 'report' => $report, 'staged_at' => gmdate( DATE_W3C ) ), self::STAGE_TTL );
        wp_safe_redirect( admin_url( 'admin.php?page=stpi-removals&stage=' . rawurlencode( $key ) ) );
        exit;
    }

    public static function handle_commit() {
        self::guard();
        check_admin_referer( 'stpi_removal_commit', 'stpi_nonce' );
        if ( empty( $_POST['confirm_removal_archive'] ) ) { self::redirect_result( false, 'Explicit archive confirmation is required.' ); }
        $key = isset( $_POST['stage_key'] ) ? sanitize_key( wp_unslash( $_POST['stage_key'] ) ) : '';
        $stage = $key ? get_transient( self::transient_key( 'stage', $key ) ) : false;
        if ( ! is_array( $stage ) || ! is_array( $stage['manifest'] ?? null ) ) { self::redirect_result( false, 'The staged removal manifest expired. Stage it again.' ); }

        // Re-run the exact source-identity preflight immediately before any mutation.
        $report = self::validate_manifest( $stage['manifest'] );
        if ( ! empty( $report['errors'] ) || empty( $report['rows'] ) ) {
            self::redirect_result( false, 'Removal archive blocked because source identity changed or validation failed. No archive action was performed.' );
        }

        $done = 0; $already = 0; $failed = array();
        foreach ( $report['rows'] as $row ) {
            if ( 'ALREADY_ARCHIVED' === $row['status'] ) { $already++; continue; }
            $post_id = (int) $row['post_id'];
            $intent = array(
                'schema'       => self::MANIFEST_SCHEMA,
                'export_id'    => (string) ( $stage['manifest']['export_id'] ?? '' ),
                'generated_at' => (string) ( $stage['manifest']['generated_at'] ?? '' ),
                'marked_at'    => gmdate( DATE_W3C ),
                'source'       => $stage['manifest']['source'],
                'removal'      => $row['removal'],
            );
            update_post_meta( $post_id, '_stpi_source_removal_intent', $intent );
            STPI_Audit::log( 'source_removal_intent_marked', $row['program_id'], array(
                'export_id' => (string) ( $stage['manifest']['export_id'] ?? '' ),
                'source_row'=> (int) ( $row['removal']['source_row'] ?? 0 ),
                'reason'    => 'programi_kaldir',
            ) );

            $result = STPI_Store::transition( $post_id, 'archive' );
            if ( is_wp_error( $result ) ) {
                delete_post_meta( $post_id, '_stpi_source_removal_intent' );
                $failed[] = $row['program_id'] . ': ' . $result->get_error_message();
                continue;
            }
            $done++;
        }
        delete_transient( self::transient_key( 'stage', $key ) );
        STPI_Audit::log( 'removal_manifest_committed', '', array(
            'export_id' => (string) ( $stage['manifest']['export_id'] ?? '' ),
            'archived'  => $done,
            'already_archived' => $already,
            'failed'    => count( $failed ),
        ) );
        if ( $failed ) {
            self::redirect_result( false, 'Removal manifest partially failed. Archived: ' . $done . '; already archived: ' . $already . '; failed: ' . implode( ' | ', $failed ) );
        }
        self::redirect_result( true, 'Removal manifest committed. Archived with immutable snapshots: ' . $done . '; already archived: ' . $already . '. No Program entity was deleted.' );
    }

    private static function validate_manifest( $manifest ) {
        $errors = array(); $warnings = array(); $rows = array();
        if ( ! is_array( $manifest ) ) { return array( 'errors' => array( 'Manifest must be a JSON object.' ), 'warnings' => array(), 'rows' => array() ); }
        if ( ( $manifest['schema'] ?? '' ) !== self::MANIFEST_SCHEMA ) { $errors[] = 'Unsupported removal manifest schema.'; }
        $source = is_array( $manifest['source'] ?? null ) ? $manifest['source'] : array();
        if ( ( $source['type'] ?? '' ) !== 'google_sheets' ) { $errors[] = 'Removal manifest source.type must be google_sheets.'; }
        if ( '' === trim( (string) ( $source['document_ref'] ?? '' ) ) ) { $errors[] = 'Removal manifest source.document_ref is required.'; }
        if ( '' === trim( (string) ( $source['worksheet'] ?? '' ) ) ) { $errors[] = 'Removal manifest source.worksheet is required.'; }
        $removals = is_array( $manifest['removals'] ?? null ) ? array_values( $manifest['removals'] ) : array();
        if ( ! $removals ) { $errors[] = 'Removal manifest contains no removals.'; }
        if ( count( $removals ) > 500 ) { $errors[] = 'Removal manifest is too large.'; }

        $seen = array();
        foreach ( $removals as $index => $removal ) {
            if ( ! is_array( $removal ) ) { $errors[] = 'Removal row ' . ( $index + 1 ) . ' is invalid.'; continue; }
            $code = trim( (string) ( $removal['program_code'] ?? '' ) );
            $source_row = absint( $removal['source_row'] ?? 0 );
            $source_ref = trim( (string) ( $removal['source_ref'] ?? '' ) );
            $reason = sanitize_key( (string) ( $removal['reason'] ?? '' ) );
            if ( ! $code || ! $source_row || ! $source_ref || 'programi_kaldir' !== $reason ) {
                $errors[] = 'Removal row ' . ( $index + 1 ) . ' is missing program_code/source identity or has an unsupported reason.';
                continue;
            }
            $identity_program = array( 'provenance' => array(
                'source_type' => 'google_sheets',
                'source_ref'  => $source_ref,
                'source_row'  => $source_row,
            ) );
            $source_key = STPI_Store::source_key( $source, $identity_program );
            if ( isset( $seen[ $source_key ] ) ) { $errors[] = 'Duplicate removal source identity: row ' . $source_row . '.'; continue; }
            $seen[ $source_key ] = true;
            $ids = STPI_Store::find_by_source_key( $source_key );
            if ( 1 !== count( $ids ) ) {
                $errors[] = 'Removal row ' . $source_row . ' did not resolve to exactly one stored Program entity.';
                continue;
            }
            $post_id = (int) $ids[0];
            $program = STPI_Store::get_program( $post_id );
            $program_id = strtoupper( trim( (string) ( $program['program_id'] ?? '' ) ) );
            if ( in_array( $program_id, array( 'STP-000036', 'STP-000037' ), true ) ) {
                $errors[] = 'Protected fixture ' . $program_id . ' cannot be removed.';
                continue;
            }
            if ( ! hash_equals( (string) ( $program['program_code'] ?? '' ), $code ) ) {
                $errors[] = 'Removal row ' . $source_row . ' code mismatch; stored Program is ' . (string) ( $program['program_code'] ?? '' ) . ', manifest says ' . $code . '.';
                continue;
            }
            $manifest_title = trim( (string) ( $removal['title'] ?? '' ) );
            if ( $manifest_title && trim( (string) ( $program['title'] ?? '' ) ) !== $manifest_title ) {
                $warnings[] = 'Removal row ' . $source_row . ' title differs from the stored Program; exact source identity and code still match.';
            }
            $editorial = (string) ( $program['workflow']['editorial'] ?? '' );
            $rows[] = array(
                'post_id'    => $post_id,
                'program_id' => $program_id,
                'program_code'=> (string) ( $program['program_code'] ?? '' ),
                'title'      => (string) ( $program['title'] ?? '' ),
                'temporal'   => STPI_Lifecycle::temporal_state( $program ),
                'editorial'  => $editorial,
                'status'     => 'archived' === $editorial ? 'ALREADY_ARCHIVED' : 'READY_TO_ARCHIVE',
                'removal'    => $removal,
            );
        }
        return array( 'errors' => $errors, 'warnings' => $warnings, 'rows' => $rows );
    }

    private static function render_stage( $key, $stage ) {
        $report = is_array( $stage['report'] ?? null ) ? $stage['report'] : array();
        $errors = (array) ( $report['errors'] ?? array() );
        $warnings = (array) ( $report['warnings'] ?? array() );
        $rows = (array) ( $report['rows'] ?? array() );
        ?>
        <section class="stpi-report">
            <h2>Removal preflight</h2>
            <div class="stpi-grid stpi-grid-small">
                <div class="stpi-stat"><span>Matched</span><strong><?php echo esc_html( count( $rows ) ); ?></strong></div>
                <div class="stpi-stat"><span>Errors</span><strong><?php echo esc_html( count( $errors ) ); ?></strong></div>
                <div class="stpi-stat"><span>Warnings</span><strong><?php echo esc_html( count( $warnings ) ); ?></strong></div>
                <div class="stpi-stat"><span>Delete operations</span><strong>0</strong></div>
            </div>
            <div class="stpi-table-wrap"><table class="widefat striped"><thead><tr><th>Source row</th><th>Stable ID</th><th>Code</th><th>Title</th><th>Temporal</th><th>Editorial</th><th>Plan</th></tr></thead><tbody>
                <?php foreach ( $rows as $row ) : ?>
                    <tr><td><?php echo esc_html( $row['removal']['source_row'] ?? '' ); ?></td><td><code><?php echo esc_html( $row['program_id'] ); ?></code></td><td><?php echo esc_html( $row['program_code'] ); ?></td><td><?php echo esc_html( $row['title'] ); ?></td><td><?php echo esc_html( $row['temporal'] ); ?></td><td><?php echo esc_html( $row['editorial'] ); ?></td><td><strong><?php echo esc_html( $row['status'] ); ?></strong></td></tr>
                <?php endforeach; ?>
                <?php if ( ! $rows ) : ?><tr><td colspan="7">No exact removal matches.</td></tr><?php endif; ?>
            </tbody></table></div>
            <?php if ( $errors ) : ?><div class="notice notice-error inline"><p><?php echo esc_html( implode( ' | ', $errors ) ); ?></p></div><?php endif; ?>
            <?php if ( $warnings ) : ?><div class="notice notice-warning inline"><p><?php echo esc_html( implode( ' | ', $warnings ) ); ?></p></div><?php endif; ?>
            <?php if ( ! $errors && $rows ) : ?>
                <form class="stpi-confirm" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                    <input type="hidden" name="action" value="stpi_removal_commit"><input type="hidden" name="stage_key" value="<?php echo esc_attr( $key ); ?>">
                    <?php wp_nonce_field( 'stpi_removal_commit', 'stpi_nonce' ); ?>
                    <label><input type="checkbox" name="confirm_removal_archive" value="1" required> I reviewed these exact <code>Programı Kaldır</code> rows and explicitly approve archiving them. Create immutable snapshots; delete nothing.</label>
                    <p><button type="submit" class="button button-primary">Archive Exact Removed Programs</button></p>
                </form>
            <?php endif; ?>
        </section>
        <?php
    }

    private static function request_json() {
        $raw = isset( $_POST['stpi_removal_json'] ) ? (string) wp_unslash( $_POST['stpi_removal_json'] ) : '';
        if ( ! empty( $_FILES['stpi_removal_file']['tmp_name'] ) ) {
            if ( ! empty( $_FILES['stpi_removal_file']['error'] ) || (int) $_FILES['stpi_removal_file']['size'] > 1048576 ) { return new WP_Error( 'stpi_removal_upload', 'The removal JSON file could not be read or is larger than 1 MB.' ); }
            $raw = (string) file_get_contents( $_FILES['stpi_removal_file']['tmp_name'] );
        }
        if ( '' === trim( $raw ) || strlen( $raw ) > 1048576 ) { return new WP_Error( 'stpi_removal_json', 'Provide removal JSON no larger than 1 MB.' ); }
        return $raw;
    }

    private static function redirect_result( $ok, $message ) {
        $key = strtolower( wp_generate_password( 14, false, false ) );
        set_transient( self::transient_key( 'result', $key ), array( 'ok' => (bool) $ok, 'message' => (string) $message ), 600 );
        wp_safe_redirect( admin_url( 'admin.php?page=stpi-removals&result=' . rawurlencode( $key ) ) );
        exit;
    }

    private static function transient_key( $kind, $key ) { return 'stpi_removal_' . $kind . '_' . get_current_user_id() . '_' . $key; }
    private static function guard() { if ( ! current_user_can( 'manage_options' ) ) { wp_die( esc_html__( 'Insufficient permissions.', 'stpi' ) ); } }
}
