<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class STPI_Importer {
    const STAGE_TTL = 1800;

    public static function init() {
        add_action( 'admin_post_stpi_stage_import', array( __CLASS__, 'handle_stage' ) );
        add_action( 'admin_post_stpi_commit_import', array( __CLASS__, 'handle_commit' ) );
    }

    public static function render() {
        self::guard();
        $stage_key = isset( $_GET['stage'] ) ? sanitize_key( wp_unslash( $_GET['stage'] ) ) : '';
        $result_key = isset( $_GET['import_result'] ) ? sanitize_key( wp_unslash( $_GET['import_result'] ) ) : '';
        $stage = $stage_key ? get_transient( self::transient_key( 'stage', $stage_key ) ) : false;
        $result = $result_key ? get_transient( self::transient_key( 'result', $result_key ) ) : false;
        ?>
        <div class="wrap stpi-wrap">
            <h1>Candidate Import</h1>
            <div class="stpi-lock"><strong>PRIVATE DATA ONLY</strong><span>Imports create or update non-public Program candidates. No page, route, SEO output or archive mutation is created.</span></div>
            <?php if ( is_array( $result ) ) { self::render_result( $result ); } ?>
            <form class="stpi-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
                <input type="hidden" name="action" value="stpi_stage_import">
                <?php wp_nonce_field( 'stpi_stage_import', 'stpi_nonce' ); ?>
                <label for="stpi-import-json"><strong>Paste canonical ST-TDE JSON</strong></label>
                <textarea id="stpi-import-json" name="stpi_json" rows="12" spellcheck="false"></textarea>
                <label for="stpi-import-file"><strong>or choose a .json file (maximum 2 MB)</strong></label>
                <input id="stpi-import-file" type="file" name="stpi_file" accept="application/json,.json">
                <p><button class="button button-primary" type="submit">Validate and Stage Candidates</button></p>
            </form>
            <?php if ( is_array( $stage ) ) { self::render_stage( $stage_key, $stage ); } ?>
        </div>
        <?php
    }

    public static function handle_stage() {
        self::guard();
        check_admin_referer( 'stpi_stage_import', 'stpi_nonce' );
        $raw = self::request_json();
        if ( is_wp_error( $raw ) ) { self::redirect_result( array( 'status' => 'error', 'message' => $raw->get_error_message() ) ); }
        $decoded = json_decode( $raw, true, 128 );
        if ( JSON_ERROR_NONE !== json_last_error() ) { self::redirect_result( array( 'status' => 'error', 'message' => 'Invalid JSON: ' . json_last_error_msg() ) ); }

        $batch = STPI_Contract::normalize_batch( $decoded );
        $report = STPI_Validator::validate_batch( $batch );
        $report = self::decorate_report( $batch, $report );
        $key = strtolower( wp_generate_password( 16, false, false ) );
        set_transient( self::transient_key( 'stage', $key ), array( 'batch' => $batch, 'report' => $report, 'staged_at' => gmdate( DATE_W3C ) ), self::STAGE_TTL );
        wp_safe_redirect( admin_url( 'admin.php?page=stpi-import&stage=' . rawurlencode( $key ) ) );
        exit;
    }

    public static function handle_commit() {
        self::guard();
        check_admin_referer( 'stpi_commit_import', 'stpi_nonce' );
        $key = isset( $_POST['stage_key'] ) ? sanitize_key( wp_unslash( $_POST['stage_key'] ) ) : '';
        $stage = $key ? get_transient( self::transient_key( 'stage', $key ) ) : false;
        if ( ! is_array( $stage ) || ! is_array( $stage['batch'] ?? null ) ) {
            self::redirect_result( array( 'status' => 'error', 'message' => 'The staged import expired. Validate the JSON again.' ) );
        }
        if ( empty( $_POST['confirm_review'] ) ) {
            self::redirect_result( array( 'status' => 'error', 'message' => 'Human review confirmation is required.' ) );
        }

        $batch = $stage['batch'];
        $current = self::decorate_report( $batch, STPI_Validator::validate_batch( $batch ) );
        if ( ! empty( $current['errors'] ) || ! empty( $current['conflict_count'] ) ) {
            self::redirect_result( array( 'status' => 'error', 'message' => 'Import is blocked because validation or identity state changed. Run Stage again.' ) );
        }

        $user = wp_get_current_user();
        $verified_at = gmdate( DATE_W3C );
        $verified_by = trim( (string) $user->display_name ) . ' (WP user #' . (int) $user->ID . ')';
        $summary = STPI_Store::import_batch( $batch, $verified_at, $verified_by );
        delete_transient( self::transient_key( 'stage', $key ) );
        STPI_Audit::log( 'batch_import_committed', '', array(
            'export_id' => (string) ( $batch['export_id'] ?? '' ),
            'created' => $summary['created'], 'updated' => $summary['updated'], 'unchanged' => $summary['unchanged'], 'conflicts' => $summary['conflicts'],
        ) );
        self::redirect_result( array( 'status' => empty( $summary['errors'] ) ? 'success' : 'warning', 'message' => empty( $summary['errors'] ) ? 'Candidate import completed.' : implode( ' ', $summary['errors'] ), 'summary' => $summary ) );
    }

    private static function decorate_report( $batch, $report ) {
        $source = is_array( $batch['source'] ?? null ) ? $batch['source'] : array();
        $conflicts = 0;
        $seen_source_keys = array();
        foreach ( array_values( $batch['programs'] ?? array() ) as $index => $program ) {
            if ( ! is_array( $program ) || ! isset( $report['programs'][ $index ] ) ) { continue; }
            $plan = STPI_Store::plan_program( $source, $program );
            if ( empty( $program['program_id'] ) && isset( $seen_source_keys[ $plan['source_key'] ] ) ) {
                $plan['operation'] = 'CONFLICT';
                $plan['reason'] = 'Duplicate source identity inside this batch.';
            }
            $seen_source_keys[ $plan['source_key'] ] = true;
            $report['programs'][ $index ]['operation'] = $plan['operation'];
            $report['programs'][ $index ]['target_program_id'] = $plan['program_id'];
            if ( 'CONFLICT' === $plan['operation'] ) {
                $conflicts++;
                $issue = array( 'severity' => 'error', 'scope' => 'row-' . ( $index + 1 ), 'field' => 'identity', 'message' => $plan['reason'] );
                $report['errors'][] = $issue;
                $report['programs'][ $index ]['errors'][] = $issue;
                $report['programs'][ $index ]['publish_gate'] = 'BLOCKED';
            }
        }
        $operations = array();
        foreach ( $report['programs'] as $row ) { $operations[ $row['operation'] ] = 1 + ( $operations[ $row['operation'] ] ?? 0 ); }
        $report['operations'] = $operations;
        $report['conflict_count'] = $conflicts;
        $report['blocked_count'] = count( array_filter( $report['programs'], function( $row ) { return 'BLOCKED' === $row['publish_gate']; } ) );
        return $report;
    }

    private static function render_stage( $key, $stage ) {
        $report = $stage['report'];
        $can_commit = empty( $report['errors'] ) && empty( $report['conflict_count'] );
        ?>
        <section class="stpi-report">
            <h2>Staged import plan</h2>
            <div class="stpi-grid stpi-grid-small">
                <div class="stpi-stat"><span>Programs</span><strong><?php echo esc_html( $report['program_count'] ); ?></strong></div>
                <div class="stpi-stat"><span>Create</span><strong><?php echo esc_html( $report['operations']['CREATE_CANDIDATE'] ?? 0 ); ?></strong></div>
                <div class="stpi-stat"><span>Update / Unchanged</span><strong><?php echo esc_html( ( $report['operations']['UPDATE_CANDIDATE'] ?? 0 ) . ' / ' . ( $report['operations']['UNCHANGED'] ?? 0 ) ); ?></strong></div>
                <div class="stpi-stat"><span>Blocked / Warnings</span><strong><?php echo esc_html( $report['blocked_count'] . ' / ' . count( $report['warnings'] ) ); ?></strong></div>
            </div>
            <p><strong>Full-snapshot safety:</strong> Missing rows still have no automatic lifecycle effect in v0.3.2.</p>
            <div class="stpi-table-wrap"><table class="widefat striped"><thead><tr><th>Code</th><th>Title</th><th>Stable target</th><th>Operation</th><th>Gate</th><th>Score</th></tr></thead><tbody>
            <?php foreach ( $report['programs'] as $row ) : ?><tr><td><?php echo esc_html( $row['program_code'] ); ?></td><td><?php echo esc_html( $row['title'] ); ?></td><td><code><?php echo esc_html( $row['target_program_id'] ?: 'ALLOCATE ON COMMIT' ); ?></code></td><td><?php echo esc_html( $row['operation'] ); ?></td><td><?php echo esc_html( $row['publish_gate'] ); ?></td><td><?php echo esc_html( $row['score'] ); ?></td></tr><?php endforeach; ?>
            </tbody></table></div>
            <?php if ( $report['errors'] ) : ?><div class="notice notice-error inline"><p>Import blocked: <?php echo esc_html( count( $report['errors'] ) ); ?> error(s).</p></div><?php endif; ?>
            <?php if ( $can_commit ) : ?>
                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="stpi-confirm">
                    <input type="hidden" name="action" value="stpi_commit_import"><input type="hidden" name="stage_key" value="<?php echo esc_attr( $key ); ?>">
                    <?php wp_nonce_field( 'stpi_commit_import', 'stpi_nonce' ); ?>
                    <label><input type="checkbox" name="confirm_review" value="1" required> I reviewed dates, prices, hotel mappings and source identity. Create/update private candidates only.</label>
                    <p><button type="submit" class="button button-primary">Commit Private Candidates</button></p>
                </form>
            <?php endif; ?>
        </section>
        <?php
    }

    private static function render_result( $result ) {
        $class = 'success' === ( $result['status'] ?? '' ) ? 'notice-success' : ( 'warning' === ( $result['status'] ?? '' ) ? 'notice-warning' : 'notice-error' );
        echo '<div class="notice ' . esc_attr( $class ) . ' inline"><p>' . esc_html( $result['message'] ?? '' ) . '</p></div>';
        if ( ! empty( $result['summary'] ) ) {
            $s = $result['summary'];
            echo '<div class="stpi-grid stpi-grid-small"><div class="stpi-stat"><span>Created</span><strong>' . esc_html( $s['created'] ) . '</strong></div><div class="stpi-stat"><span>Updated</span><strong>' . esc_html( $s['updated'] ) . '</strong></div><div class="stpi-stat"><span>Unchanged</span><strong>' . esc_html( $s['unchanged'] ) . '</strong></div><div class="stpi-stat"><span>Conflicts</span><strong>' . esc_html( $s['conflicts'] ) . '</strong></div></div>';
        }
    }

    private static function request_json() {
        $raw = isset( $_POST['stpi_json'] ) ? (string) wp_unslash( $_POST['stpi_json'] ) : '';
        if ( ! empty( $_FILES['stpi_file']['tmp_name'] ) ) {
            if ( ! empty( $_FILES['stpi_file']['error'] ) || (int) $_FILES['stpi_file']['size'] > 2097152 ) { return new WP_Error( 'stpi_upload', 'The JSON file could not be read or is larger than 2 MB.' ); }
            $raw = (string) file_get_contents( $_FILES['stpi_file']['tmp_name'] );
        }
        if ( '' === trim( $raw ) || strlen( $raw ) > 2097152 ) { return new WP_Error( 'stpi_json', 'Provide JSON no larger than 2 MB.' ); }
        return $raw;
    }

    private static function redirect_result( $result ) {
        $key = strtolower( wp_generate_password( 14, false, false ) );
        set_transient( self::transient_key( 'result', $key ), $result, 600 );
        wp_safe_redirect( admin_url( 'admin.php?page=stpi-import&import_result=' . rawurlencode( $key ) ) );
        exit;
    }

    private static function transient_key( $kind, $key ) { return 'stpi_' . $kind . '_' . get_current_user_id() . '_' . $key; }
    private static function guard() { if ( ! current_user_can( 'manage_options' ) ) { wp_die( esc_html__( 'Insufficient permissions.', 'stpi' ) ); } }
}
