<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class STPF_Plugin {
    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) { self::$instance = new self(); }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'init', array( $this, 'register_post_type' ) );
        add_action( 'admin_menu', array( $this, 'register_admin_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
        add_filter( 'manage_stpf_program_posts_columns', array( $this, 'columns' ) );
        add_action( 'manage_stpf_program_posts_custom_column', array( $this, 'column_values' ), 10, 2 );
        add_action( 'admin_notices', array( $this, 'admin_notices' ) );
    }

    public static function activate() {
        if ( false === get_option( 'stpf_next_program_number', false ) ) {
            add_option( 'stpf_next_program_number', 1, '', false );
        }
        flush_rewrite_rules();
    }

    public function register_post_type() {
        register_post_type( 'stpf_program', array(
            'labels' => array(
                'name' => 'Programs', 'singular_name' => 'Program', 'menu_name' => 'Programs',
                'add_new' => 'Add Program', 'add_new_item' => 'Add New Program', 'edit_item' => 'Edit Program',
                'new_item' => 'New Program', 'view_item' => 'View Program', 'search_items' => 'Search Programs',
                'not_found' => 'No programs found', 'not_found_in_trash' => 'No programs found in Trash',
            ),
            'public' => false,
            'publicly_queryable' => false,
            'show_ui' => true,
            'show_in_menu' => false,
            'show_in_rest' => false,
            'has_archive' => false,
            'exclude_from_search' => true,
            'rewrite' => false,
            'supports' => array( 'title', 'revisions' ),
            'capability_type' => 'post',
            'map_meta_cap' => true,
        ) );
    }

    public function register_admin_menu() {
        add_menu_page(
            'Program Foundation', 'Program Foundation', 'manage_options', 'stpf-dashboard',
            array( $this, 'render_dashboard' ), 'dashicons-networking', 27
        );
        add_submenu_page( 'stpf-dashboard', 'Dashboard', 'Dashboard', 'manage_options', 'stpf-dashboard', array( $this, 'render_dashboard' ) );
        add_submenu_page( 'stpf-dashboard', 'Programs', 'Programs', 'manage_options', 'edit.php?post_type=stpf_program' );
        add_submenu_page( 'stpf-dashboard', 'Program Data Bridge', 'Data Bridge', 'manage_options', 'stpf-bridge', array( 'STPF_Data_Bridge', 'render_page' ) );
        add_submenu_page( 'stpf-dashboard', 'Program ↔ Hotel Relations', 'Relations', 'manage_options', 'stpf-relations', array( 'STPF_Relations', 'render_relations_page' ) );
    }

    public function enqueue_admin_assets() {
        $screen = get_current_screen();
        if ( ! $screen ) { return; }
        if ( 'stpf_program' !== $screen->post_type && false === strpos( (string) $screen->id, 'stpf' ) ) { return; }
        wp_enqueue_style( 'stpf-admin', STPF_URL . 'assets/admin.css', array(), STPF_VERSION );
    }

    public function render_dashboard() {
        if ( ! current_user_can( 'manage_options' ) ) { return; }
        $counts = wp_count_posts( 'stpf_program' );
        $total = 0;
        foreach ( (array) $counts as $status => $count ) {
            if ( ! in_array( $status, array( 'trash', 'auto-draft' ), true ) ) { $total += (int) $count; }
        }
        $with_hotels = $this->count_meta_exists( '_stpf_hotel_refs' );
        ?>
        <div class="wrap stpf-wrap">
            <h1>Server Turizm Program Foundation</h1>
            <p class="stpf-lead">H6A is runtime accepted. v0.2.0 adds the H6B relation layer while preserving Hotel Intelligence v0.8.0 and the current <code>/umre-1/</code> publishing workflow.</p>
            <div class="stpf-cards">
                <div class="stpf-card"><span>Total Programs</span><strong><?php echo esc_html( $total ); ?></strong></div>
                <div class="stpf-card"><span>With Hotel Refs</span><strong><?php echo esc_html( $with_hotels ); ?></strong></div>
                <div class="stpf-card"><span>Program ID Format</span><strong>STP-000001</strong></div>
                <div class="stpf-card"><span>Entity Schema</span><strong>program-entity-0.1</strong></div>
            </div>
            <div class="stpf-panel">
                <h2>H6A + H6B safety contract</h2>
                <ul>
                    <li>Immutable Stable Program ID assigned once.</li>
                    <li>Private/non-public Program records: no frontend route, sitemap or indexation change.</li>
                    <li>Hotel relationships store only Stable Hotel IDs (<code>STH-xxxxxx</code>).</li>
                    <li>Unknown Hotel IDs are rejected; no name guessing.</li>
                    <li>Dry Run before mutation with CREATE / UPDATE / UNCHANGED / CONFLICT / INVALID.</li>
                    <li>Blank import cells are no-op; <code>__CLEAR__</code> is explicit clear.</li>
                    <li>Audit batches and conflict-aware rollback; no hard-delete for missing spreadsheet rows.</li>
                    <li>Hotel → Program usage is derived from Program <code>hotel_refs[]</code>; no mirrored Hotel-side Program list.</li>
                    <li>Program-specific public links require an active lifecycle plus an eligible internal public target.</li>
                </ul>
                <p><a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=stpf-bridge' ) ); ?>">Open Program Data Bridge</a></p>
            </div>
        </div>
        <?php
    }

    private function count_meta_exists( $key ) {
        $q = new WP_Query( array(
            'post_type' => 'stpf_program', 'post_status' => array( 'draft','pending','publish','private' ),
            'posts_per_page' => 1, 'fields' => 'ids',
            'meta_query' => array( array( 'key' => $key, 'compare' => 'EXISTS' ) ),
        ) );
        return (int) $q->found_posts;
    }

    public function columns( $columns ) {
        $out = array();
        foreach ( $columns as $key => $label ) {
            $out[$key] = $label;
            if ( 'title' === $key ) {
                $out['stpf_program_id'] = 'Program ID';
                $out['stpf_source_key'] = 'Source Key';
                $out['stpf_lifecycle'] = 'Lifecycle';
                $out['stpf_hotels'] = 'Hotel Refs';
            }
        }
        return $out;
    }

    public function column_values( $column, $post_id ) {
        if ( 'stpf_program_id' === $column ) { echo esc_html( get_post_meta( $post_id, '_stpf_program_id', true ) ); }
        if ( 'stpf_source_key' === $column ) { echo esc_html( get_post_meta( $post_id, '_stpf_source_key', true ) ); }
        if ( 'stpf_lifecycle' === $column ) { echo esc_html( get_post_meta( $post_id, '_stpf_lifecycle_state', true ) ?: 'draft' ); }
        if ( 'stpf_hotels' === $column ) {
            $refs = (array) get_post_meta( $post_id, '_stpf_hotel_refs', true );
            echo esc_html( implode( ', ', array_filter( $refs ) ) );
        }
    }

    public function admin_notices() {
        if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) { return; }
        $key = 'stpf_notice_' . get_current_user_id();
        $notice = get_transient( $key );
        if ( ! $notice ) { return; }
        delete_transient( $key );
        $class = ! empty( $notice['error'] ) ? 'notice notice-error' : 'notice notice-success';
        echo '<div class="' . esc_attr( $class ) . '"><p>' . esc_html( $notice['message'] ) . '</p></div>';
    }
}
