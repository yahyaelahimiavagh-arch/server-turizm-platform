<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class STPI_Admin {
    const RESULT_TTL = 600;

    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
        add_action( 'admin_post_stpi_dry_run', array( __CLASS__, 'handle_dry_run' ) );
        add_action( 'admin_post_stpi_program_action', array( __CLASS__, 'handle_program_action' ) );
        add_action( 'admin_post_stpi_bulk_program_action', array( __CLASS__, 'handle_bulk_program_action' ) );
        STPI_Importer::init();
    }

    public static function menu() {
        add_menu_page( 'Program Intelligence', 'Program Intelligence', 'manage_options', 'stpi-dashboard', array( __CLASS__, 'dashboard' ), 'dashicons-location-alt', 27 );
        add_submenu_page( 'stpi-dashboard', 'Dashboard', 'Dashboard', 'manage_options', 'stpi-dashboard', array( __CLASS__, 'dashboard' ) );
        add_submenu_page( 'stpi-dashboard', 'JSON Dry Run', 'JSON Dry Run', 'manage_options', 'stpi-dry-run', array( __CLASS__, 'dry_run' ) );
        add_submenu_page( 'stpi-dashboard', 'Candidate Import', 'Candidate Import', 'manage_options', 'stpi-import', array( 'STPI_Importer', 'render' ) );
        add_submenu_page( 'stpi-dashboard', 'Programs', 'Programs', 'manage_options', 'stpi-programs', array( __CLASS__, 'programs' ) );
        add_submenu_page( 'stpi-dashboard', 'Archive Candidates', 'Archive Candidates', 'manage_options', 'stpi-archive', array( __CLASS__, 'archive_candidates' ) );
        add_submenu_page( 'stpi-dashboard', 'Audit Log', 'Audit Log', 'manage_options', 'stpi-audit', array( __CLASS__, 'audit_log' ) );
        add_submenu_page( 'stpi-dashboard', 'Hotel Directory', 'Hotel Directory', 'manage_options', 'stpi-hotel-directory', array( 'STPI_Hotel_Directory', 'render' ) );
        add_submenu_page( 'stpi-dashboard', 'Contract', 'Contract', 'manage_options', 'stpi-contract', array( __CLASS__, 'contract' ) );
        add_submenu_page( null, 'Program Review', 'Program Review', 'manage_options', 'stpi-program-view', array( __CLASS__, 'program_view' ) );
    }

    public static function assets( $hook ) {
        if ( false === strpos( (string) $hook, 'stpi-' ) ) { return; }
        wp_enqueue_style( 'stpi-admin', STPI_URL . 'assets/admin.css', array(), STPI_VERSION );
    }

    public static function dashboard() {
        self::guard();
        $programs = STPI_Store::all_programs();
        $approved = 0; $archived = 0; $archive_candidates = 0;
        foreach ( $programs as $row ) {
            $editorial = (string) ( $row['program']['workflow']['editorial'] ?? '' );
            if ( 'approved' === $editorial ) { $approved++; }
            if ( 'archived' === $editorial ) { $archived++; }
            if ( STPI_Store::archive_eligible( $row['post_id'], $row['program'] ) ) { $archive_candidates++; }
        }
        ?>
        <div class="wrap stpi-wrap">
            <h1>Server Turizm Program Intelligence</h1>
            <div class="stpi-lock"><strong>PRIVATE CANDIDATE GATE</strong><span>v0.3.3 keeps Program entities private and adds explicit Programı Kaldır removal-manifest archiving. Nothing is deleted and no removal is inferred from a missing partial row.</span></div>
            <div class="stpi-grid">
                <article class="stpi-card"><h2>Private programs</h2><strong><?php echo esc_html( count( $programs ) ); ?></strong><p>Stable STP entities stored outside all public queries.</p></article>
                <article class="stpi-card"><h2>Approved</h2><strong><?php echo esc_html( $approved ); ?></strong><p>Human-approved data candidates; still not publicly rendered.</p></article>
                <article class="stpi-card"><h2>Archive review</h2><strong><?php echo esc_html( $archive_candidates ); ?></strong><p>Completed/cancelled or exact Programı Kaldır candidates requiring explicit action. Archived: <?php echo esc_html( $archived ); ?>.</p></article>
                <article class="stpi-card"><h2>Public writes</h2><strong>0</strong><p>No program URL, HTML card, sitemap, schema or SEO output exists in this version.</p></article>
            </div>
            <p><a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=stpi-import' ) ); ?>">Open Candidate Import</a> <a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=stpi-programs' ) ); ?>">Open Programs</a></p>
        </div>
        <?php
    }

    public static function dry_run() {
        self::guard();
        $result_key = isset( $_GET['result'] ) ? sanitize_key( wp_unslash( $_GET['result'] ) ) : '';
        $result = $result_key ? get_transient( 'stpi_result_' . get_current_user_id() . '_' . $result_key ) : false;
        ?>
        <div class="wrap stpi-wrap">
            <h1>ST-TDE JSON Dry Run</h1>
            <div class="stpi-lock"><strong>READ-ONLY</strong><span>Validation creates no Program records and changes no public content.</span></div>
            <form class="stpi-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
                <input type="hidden" name="action" value="stpi_dry_run">
                <?php wp_nonce_field( 'stpi_dry_run', 'stpi_nonce' ); ?>
                <label for="stpi-json"><strong>Paste canonical JSON</strong></label>
                <textarea id="stpi-json" name="stpi_json" rows="18" spellcheck="false" placeholder='{"schema_version":"1.0.0","programs":[]}'></textarea>
                <label for="stpi-file"><strong>or choose a .json file (maximum 2 MB)</strong></label>
                <input id="stpi-file" type="file" name="stpi_file" accept="application/json,.json">
                <p><button class="button button-primary" type="submit">Validate and Preview Diff</button></p>
            </form>
            <?php if ( is_array( $result ) ) { self::render_report( $result ); } ?>
        </div>
        <?php
    }

    public static function handle_dry_run() {
        self::guard();
        check_admin_referer( 'stpi_dry_run', 'stpi_nonce' );
        $raw = isset( $_POST['stpi_json'] ) ? (string) wp_unslash( $_POST['stpi_json'] ) : '';

        if ( ! empty( $_FILES['stpi_file']['tmp_name'] ) ) {
            if ( ! empty( $_FILES['stpi_file']['error'] ) || (int) $_FILES['stpi_file']['size'] > 2097152 ) {
                self::redirect_error( 'The JSON file could not be read or is larger than 2 MB.' );
            }
            $raw = (string) file_get_contents( $_FILES['stpi_file']['tmp_name'] );
        }
        if ( '' === trim( $raw ) || strlen( $raw ) > 2097152 ) { self::redirect_error( 'Provide JSON no larger than 2 MB.' ); }

        $decoded = json_decode( $raw, true, 128 );
        if ( JSON_ERROR_NONE !== json_last_error() ) { self::redirect_error( 'Invalid JSON: ' . json_last_error_msg() ); }

        $report = STPI_Validator::validate_batch( $decoded );
        $key = strtolower( wp_generate_password( 12, false, false ) );
        set_transient( 'stpi_result_' . get_current_user_id() . '_' . $key, $report, self::RESULT_TTL );
        wp_safe_redirect( admin_url( 'admin.php?page=stpi-dry-run&result=' . rawurlencode( $key ) ) );
        exit;
    }

    private static function redirect_error( $message ) {
        $key = strtolower( wp_generate_password( 12, false, false ) );
        set_transient( 'stpi_result_' . get_current_user_id() . '_' . $key, array( 'fatal' => $message ), self::RESULT_TTL );
        wp_safe_redirect( admin_url( 'admin.php?page=stpi-dry-run&result=' . rawurlencode( $key ) ) );
        exit;
    }

    private static function render_report( $report ) {
        if ( isset( $report['fatal'] ) ) { echo '<div class="notice notice-error"><p>' . esc_html( $report['fatal'] ) . '</p></div>'; return; }
        ?>
        <section class="stpi-report">
            <h2>Dry-run report</h2>
            <div class="stpi-grid stpi-grid-small">
                <div class="stpi-stat"><span>Programs</span><strong><?php echo esc_html( $report['program_count'] ); ?></strong></div>
                <div class="stpi-stat"><span>Ready</span><strong><?php echo esc_html( $report['ready_count'] ); ?></strong></div>
                <div class="stpi-stat"><span>Blocked</span><strong><?php echo esc_html( $report['blocked_count'] ); ?></strong></div>
                <div class="stpi-stat"><span>Errors / Warnings</span><strong><?php echo esc_html( count( $report['errors'] ) . ' / ' . count( $report['warnings'] ) ); ?></strong></div>
            </div>
            <p><strong>Archive:</strong> <?php echo esc_html( $report['archive_policy'] ); ?></p>
            <div class="stpi-table-wrap"><table class="widefat striped"><thead><tr><th>Row</th><th>ID / Code</th><th>Type</th><th>Title</th><th>Plan</th><th>Score</th><th>Gate</th><th>Issues</th></tr></thead><tbody>
            <?php foreach ( $report['programs'] as $row ) : ?>
                <tr><td><?php echo esc_html( $row['row'] ); ?></td><td><code><?php echo esc_html( $row['program_id'] ?: 'NEW' ); ?></code><br><?php echo esc_html( $row['program_code'] ); ?></td><td><?php echo esc_html( $row['service_type'] ); ?></td><td><?php echo esc_html( $row['title'] ); ?></td><td><?php echo esc_html( $row['operation'] ); ?></td><td><?php echo esc_html( $row['score'] ); ?></td><td><strong class="stpi-gate stpi-gate-<?php echo esc_attr( strtolower( $row['publish_gate'] ) ); ?>"><?php echo esc_html( $row['publish_gate'] ); ?></strong></td><td><?php echo esc_html( count( $row['errors'] ) . ' error(s), ' . count( $row['warnings'] ) . ' warning(s)' ); ?></td></tr>
            <?php endforeach; ?>
            </tbody></table></div>
            <?php self::issue_list( 'Errors', $report['errors'], 'error' ); ?>
            <?php self::issue_list( 'Warnings', $report['warnings'], 'warning' ); ?>
        </section>
        <?php
    }

    private static function issue_list( $title, $issues, $kind ) {
        if ( ! $issues ) { return; }
        echo '<details class="stpi-issues stpi-' . esc_attr( $kind ) . '" open><summary>' . esc_html( $title . ' (' . count( $issues ) . ')' ) . '</summary><ul>';
        foreach ( $issues as $issue ) { echo '<li><code>' . esc_html( $issue['scope'] . ' / ' . $issue['field'] ) . '</code> ' . esc_html( $issue['message'] ) . '</li>'; }
        echo '</ul></details>';
    }

    public static function programs() {
        self::guard();
        $rows = STPI_Store::all_programs();
        self::render_action_notice();
        ?>
        <div class="wrap stpi-wrap"><h1>Private Program Candidates</h1>
            <div class="stpi-lock"><strong>NON-PUBLIC</strong><span>Approval here confirms data quality only; it does not publish a URL or alter the legacy Umrah page.</span></div>
            <?php self::render_bulk_form( 'approve_ready', 'Approve all READY review candidates', 'I reviewed this candidate batch and understand approval does not publish it.' ); ?>
            <?php self::render_program_table( $rows, false ); ?>
        </div>
        <?php
    }

    public static function archive_candidates() {
        self::guard();
        $rows = array_values( array_filter( STPI_Store::all_programs(), function( $row ) {
            return STPI_Store::archive_eligible( $row['post_id'], $row['program'] ) || 'archived' === ( $row['program']['workflow']['editorial'] ?? '' );
        } ) );
        self::render_action_notice();
        ?>
        <div class="wrap stpi-wrap"><h1>Archive Candidates</h1>
            <div class="stpi-lock"><strong>NO AUTO-ARCHIVE / NO DELETE</strong><span>Completed/cancelled Programs and exact Programı Kaldır removal intents can appear here. Archive and restore always require an explicit administrator action and create/retain history.</span></div>
            <?php self::render_bulk_form( 'archive_eligible', 'Archive all eligible candidates', 'I reviewed the completed/cancelled and explicit Programı Kaldır list and want to create immutable archive snapshots.' ); ?>
            <?php self::render_program_table( $rows, true ); ?>
        </div>
        <?php
    }

    public static function audit_log() {
        self::guard();
        $events = STPI_Audit::recent( 200 );
        ?>
        <div class="wrap stpi-wrap"><h1>Program Audit Log</h1><div class="stpi-lock"><strong>APPEND-ONLY UI</strong><span>This screen offers no edit or delete action.</span></div>
            <div class="stpi-table-wrap"><table class="widefat striped"><thead><tr><th>Time (UTC)</th><th>Event</th><th>Program ID</th><th>Actor</th><th>Details</th></tr></thead><tbody>
            <?php foreach ( $events as $event ) : ?><tr><td><?php echo esc_html( $event['occurred_at'] ?? '' ); ?></td><td><code><?php echo esc_html( $event['event_type'] ?? '' ); ?></code></td><td><?php echo esc_html( $event['program_id'] ?? '' ); ?></td><td><?php echo esc_html( $event['actor']['display_name'] ?? '' ); ?></td><td><code><?php echo esc_html( wp_json_encode( $event['details'] ?? array(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) ); ?></code></td></tr><?php endforeach; ?>
            <?php if ( ! $events ) : ?><tr><td colspan="5">No events yet.</td></tr><?php endif; ?>
            </tbody></table></div>
        </div>
        <?php
    }

    public static function program_view() {
        self::guard();
        $post_id = isset( $_GET['program_post'] ) ? absint( $_GET['program_post'] ) : 0;
        $program = STPI_Store::get_program( $post_id );
        if ( ! $program ) { wp_die( 'Program candidate was not found.' ); }
        $workflow = is_array( $program['workflow'] ?? null ) ? $program['workflow'] : array();
        $schedule = is_array( $program['schedule'] ?? null ) ? $program['schedule'] : array();
        ?>
        <div class="wrap stpi-wrap"><h1>Review <?php echo esc_html( $program['program_id'] ?? '' ); ?></h1>
            <div class="stpi-lock"><strong>PRIVATE REVIEW</strong><span>Reviewing or approving this entity does not create a public page.</span></div>
            <div class="stpi-grid">
                <article class="stpi-card"><h2>Identity</h2><strong><?php echo esc_html( $program['program_code'] ?? '' ); ?></strong><p><?php echo esc_html( $program['title'] ?? '' ); ?></p></article>
                <article class="stpi-card"><h2>Dates</h2><strong><?php echo esc_html( ( $schedule['start_date'] ?? '—' ) . ' → ' . ( $schedule['end_date'] ?? '—' ) ); ?></strong><p><?php echo esc_html( ( $schedule['duration_nights'] ?? '—' ) . ' nights / ' . ( $schedule['duration_days'] ?? '—' ) . ' days' ); ?></p></article>
                <article class="stpi-card"><h2>Workflow</h2><strong><?php echo esc_html( $workflow['editorial'] ?? '' ); ?></strong><p><?php echo esc_html( ( $workflow['schedule'] ?? '' ) . ' / ' . ( $workflow['availability'] ?? '' ) ); ?></p></article>
                <article class="stpi-card"><h2>Temporal state</h2><strong><?php echo esc_html( STPI_Lifecycle::temporal_state( $program ) ); ?></strong><p>Effective state: <code><?php echo esc_html( STPI_Lifecycle::effective_state( $program ) ); ?></code>. Computed in Europe/Istanbul; never auto-archives.</p></article>
            </div>
            <h2>Hotels and stays</h2><div class="stpi-table-wrap"><table class="widefat striped"><thead><tr><th>#</th><th>Destination</th><th>Stable Hotel</th><th>Resolved name</th><th>Check-in/out</th><th>Nights</th><th>Meal</th></tr></thead><tbody>
            <?php foreach ( (array) ( $program['stays'] ?? array() ) as $stay ) : $hotel = ! empty( $stay['hotel_id'] ) ? STPI_Hotel_Adapter::resolve( $stay['hotel_id'] ) : array(); ?><tr><td><?php echo esc_html( $stay['sequence'] ?? '' ); ?></td><td><?php echo esc_html( $stay['destination'] ?? '' ); ?></td><td><code><?php echo esc_html( $stay['hotel_id'] ?? 'UNRESOLVED' ); ?></code></td><td><?php echo esc_html( $hotel['name'] ?? $stay['unresolved_hotel_name'] ?? '—' ); ?></td><td><?php echo esc_html( ( $stay['check_in'] ?? '—' ) . ' → ' . ( $stay['check_out'] ?? '—' ) ); ?></td><td><?php echo esc_html( $stay['nights'] ?? '—' ); ?></td><td><?php echo esc_html( $stay['meal_plan'] ?? '—' ); ?></td></tr><?php endforeach; ?>
            </tbody></table></div>
            <h2>Prices</h2><div class="stpi-table-wrap"><table class="widefat striped"><thead><tr><th>Occupancy</th><th>Amount</th><th>Currency</th><th>Unit</th><th>Label</th></tr></thead><tbody>
            <?php foreach ( (array) ( $program['pricing']['entries'] ?? array() ) as $price ) : ?><tr><td><?php echo esc_html( $price['occupancy'] ?? '' ); ?></td><td><?php echo esc_html( $price['amount'] ?? '' ); ?></td><td><?php echo esc_html( $program['pricing']['currency'] ?? '' ); ?></td><td><?php echo esc_html( $price['unit'] ?? '' ); ?></td><td><?php echo esc_html( $price['label'] ?? '' ); ?></td></tr><?php endforeach; ?>
            </tbody></table></div>
            <h2>Provenance</h2><p><code><?php echo esc_html( ( $program['provenance']['verified_at'] ?? 'unverified' ) . ' — ' . ( $program['provenance']['verified_by'] ?? 'unknown' ) ); ?></code></p>
            <p><?php if ( in_array( $workflow['editorial'] ?? '', array( 'draft', 'needs_review' ), true ) ) { echo self::action_link( $post_id, 'approve', 'Approve data candidate' ) . ' '; } echo self::action_link( $post_id, 'export', 'Export JSON' ); ?></p>
            <details class="stpi-issues"><summary>Canonical stored JSON</summary><textarea class="large-text code" rows="24" readonly><?php echo esc_textarea( wp_json_encode( $program, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) ); ?></textarea></details>
        </div>
        <?php
    }

    public static function handle_program_action() {
        self::guard();
        $post_id = isset( $_GET['program_post'] ) ? absint( $_GET['program_post'] ) : 0;
        $action = isset( $_GET['program_action'] ) ? sanitize_key( wp_unslash( $_GET['program_action'] ) ) : '';
        check_admin_referer( 'stpi_program_action_' . $post_id . '_' . $action );

        if ( 'export' === $action ) {
            $program = STPI_Store::get_program( $post_id );
            if ( ! $program ) { wp_die( 'Program not found.' ); }
            $payload = array( 'schema_version' => STPI_SCHEMA_VERSION, 'export_id' => 'WP-' . gmdate( 'Ymd-His' ), 'generated_at' => gmdate( DATE_W3C ), 'source' => array( 'type' => 'wordpress', 'mode' => 'partial', 'document_ref' => null, 'worksheet' => null, 'timezone' => 'Europe/Istanbul' ), 'programs' => array( $program ) );
            nocache_headers();
            header( 'Content-Type: application/json; charset=utf-8' );
            header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( strtolower( (string) $program['program_id'] ) . '.json' ) . '"' );
            echo wp_json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
            exit;
        }

        $result = STPI_Store::transition( $post_id, $action );
        $key = strtolower( wp_generate_password( 12, false, false ) );
        set_transient( 'stpi_action_' . get_current_user_id() . '_' . $key, array( 'ok' => ! is_wp_error( $result ), 'message' => is_wp_error( $result ) ? $result->get_error_message() : ucfirst( $action ) . ' completed.' ), 600 );
        $page = in_array( $action, array( 'archive', 'restore' ), true ) ? 'stpi-archive' : 'stpi-programs';
        wp_safe_redirect( admin_url( 'admin.php?page=' . $page . '&action_result=' . rawurlencode( $key ) ) );
        exit;
    }

    public static function handle_bulk_program_action() {
        self::guard();
        check_admin_referer( 'stpi_bulk_program_action', 'stpi_nonce' );
        $action = isset( $_POST['bulk_program_action'] ) ? sanitize_key( wp_unslash( $_POST['bulk_program_action'] ) ) : '';
        if ( empty( $_POST['confirm_bulk'] ) || ! in_array( $action, array( 'approve_ready', 'archive_eligible' ), true ) ) { wp_die( 'Explicit confirmation is required.' ); }
        $transition = 'approve_ready' === $action ? 'approve' : 'archive';
        $done = 0; $skipped = 0; $failed = 0;
        foreach ( STPI_Store::all_programs() as $row ) {
            $program = $row['program'];
            $editorial = (string) ( $program['workflow']['editorial'] ?? '' );
            $eligible = 'approve' === $transition ? in_array( $editorial, array( 'draft', 'needs_review' ), true ) : STPI_Store::archive_eligible( $row['post_id'], $program );
            if ( ! $eligible ) { $skipped++; continue; }
            $result = STPI_Store::transition( $row['post_id'], $transition );
            if ( is_wp_error( $result ) ) { $failed++; } else { $done++; }
        }
        STPI_Audit::log( 'bulk_' . $action, '', array( 'completed' => $done, 'skipped' => $skipped, 'failed' => $failed ) );
        $key = strtolower( wp_generate_password( 12, false, false ) );
        set_transient( 'stpi_action_' . get_current_user_id() . '_' . $key, array( 'ok' => 0 === $failed, 'message' => 'Completed: ' . $done . '; skipped: ' . $skipped . '; failed: ' . $failed . '.' ), 600 );
        $page = 'approve' === $transition ? 'stpi-programs' : 'stpi-archive';
        wp_safe_redirect( admin_url( 'admin.php?page=' . $page . '&action_result=' . rawurlencode( $key ) ) );
        exit;
    }

    private static function render_program_table( $rows, $archive_view ) {
        ?>
        <div class="stpi-table-wrap"><table class="widefat striped"><thead><tr><th>Stable ID</th><th>Code / Type</th><th>Title</th><th>Dates</th><th>Editorial</th><th>Availability</th><th>Temporal</th><th>Effective state</th><th>Actions</th></tr></thead><tbody>
        <?php foreach ( $rows as $row ) : $program = $row['program']; $workflow = $program['workflow'] ?? array(); $schedule = $program['schedule'] ?? array(); $editorial = $workflow['editorial'] ?? ''; ?>
            <tr><td><code><?php echo esc_html( $program['program_id'] ?? '' ); ?></code></td><td><?php echo esc_html( $program['program_code'] ?? '' ); ?><br><small><?php echo esc_html( $program['service_type'] ?? '' ); ?></small></td><td><?php echo esc_html( $program['title'] ?? '' ); ?></td><td><?php echo esc_html( ( $schedule['start_date'] ?? '—' ) . ' → ' . ( $schedule['end_date'] ?? '—' ) ); ?></td><td><?php echo esc_html( $editorial ); ?></td><td><?php echo esc_html( $workflow['availability'] ?? '' ); ?></td><td><strong><?php echo esc_html( STPI_Lifecycle::temporal_state( $program ) ); ?></strong></td><td><code><?php echo esc_html( STPI_Lifecycle::effective_state( $program ) ); ?></code></td><td>
                <?php echo self::review_link( $row['post_id'] ) . ' '; ?>
                <?php if ( ! $archive_view && in_array( $editorial, array( 'draft', 'needs_review' ), true ) ) { echo self::action_link( $row['post_id'], 'approve', 'Approve' ) . ' '; } ?>
                <?php if ( STPI_Store::archive_eligible( $row['post_id'], $program ) ) { echo self::action_link( $row['post_id'], 'archive', 'Archive' ) . ' '; } ?>
                <?php if ( STPI_Store::source_removal_intent( $row['post_id'] ) ) { echo '<br><small><strong>Archive reason:</strong> Programı Kaldır</small>'; } ?>
                <?php if ( 'archived' === $editorial ) { echo self::action_link( $row['post_id'], 'restore', 'Restore' ) . ' '; } ?>
                <?php echo self::action_link( $row['post_id'], 'export', 'Export JSON' ); ?>
            </td></tr>
        <?php endforeach; ?>
        <?php if ( ! $rows ) : ?><tr><td colspan="9">No candidates in this view.</td></tr><?php endif; ?>
        </tbody></table></div>
        <?php
    }

    private static function action_link( $post_id, $action, $label ) {
        $url = add_query_arg( array( 'action' => 'stpi_program_action', 'program_post' => absint( $post_id ), 'program_action' => $action ), admin_url( 'admin-post.php' ) );
        $confirm = in_array( $action, array( 'approve', 'archive', 'restore' ), true ) ? ' onclick="return confirm(\'Confirm ' . esc_js( $action ) . '?\')"' : '';
        return '<a class="button button-small"' . $confirm . ' href="' . esc_url( wp_nonce_url( $url, 'stpi_program_action_' . absint( $post_id ) . '_' . $action ) ) . '">' . esc_html( $label ) . '</a>';
    }

    private static function review_link( $post_id ) {
        $url = add_query_arg( array( 'page' => 'stpi-program-view', 'program_post' => absint( $post_id ) ), admin_url( 'admin.php' ) );
        return '<a class="button button-small" href="' . esc_url( $url ) . '">Review</a>';
    }

    private static function render_bulk_form( $action, $button, $confirmation ) {
        ?>
        <form class="stpi-confirm" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <input type="hidden" name="action" value="stpi_bulk_program_action"><input type="hidden" name="bulk_program_action" value="<?php echo esc_attr( $action ); ?>">
            <?php wp_nonce_field( 'stpi_bulk_program_action', 'stpi_nonce' ); ?>
            <label><input type="checkbox" name="confirm_bulk" value="1" required> <?php echo esc_html( $confirmation ); ?></label>
            <button type="submit" class="button button-secondary"><?php echo esc_html( $button ); ?></button>
        </form>
        <?php
    }

    private static function render_action_notice() {
        $key = isset( $_GET['action_result'] ) ? sanitize_key( wp_unslash( $_GET['action_result'] ) ) : '';
        $result = $key ? get_transient( 'stpi_action_' . get_current_user_id() . '_' . $key ) : false;
        if ( ! is_array( $result ) ) { return; }
        echo '<div class="notice ' . ( $result['ok'] ? 'notice-success' : 'notice-error' ) . ' inline"><p>' . esc_html( $result['message'] ) . '</p></div>';
    }

    public static function contract() {
        self::guard();
        ?>
        <div class="wrap stpi-wrap"><h1>ST-TDE Contract <?php echo esc_html( STPI_SCHEMA_VERSION ); ?></h1>
            <div class="stpi-card"><p>The machine-readable JSON Schema is bundled with this plugin.</p><p><a class="button" href="<?php echo esc_url( STPI_Contract::schema_url() ); ?>" target="_blank" rel="noopener">Open JSON Schema</a></p></div>
            <div class="stpi-card" style="margin-top:16px"><h2>Google Sheets integration</h2><p>Add the exporter as a separate Apps Script file. It does not replace the current HTML or hotel reservation generator.</p><p><a class="button" href="<?php echo esc_url( STPI_URL . 'integrations/google-sheets/ST_TDE_Exporter.gs' ); ?>" target="_blank" rel="noopener">Open Google Sheets Exporter</a></p></div>
            <h2>Locked safety rules</h2><ul class="stpi-rules"><li>Names and slugs are not identity. Programs use STP-* and hotels use STH-*.</li><li>Invalid or ambiguous data fails closed.</li><li>Dry runs never write.</li><li>Missing rows never delete records.</li><li>Dates, prices, hotel facts and public publishing require human approval.</li><li>Unknown facts stay unknown and are omitted from SEO/schema output.</li></ul>
        </div>
        <?php
    }

    private static function guard() {
        if ( ! current_user_can( 'manage_options' ) ) { wp_die( esc_html__( 'Insufficient permissions.', 'stpi' ) ); }
    }
}
