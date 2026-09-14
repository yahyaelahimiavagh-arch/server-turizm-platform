<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class STHI_Plugin {
    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'init', array( $this, 'register_hotel_post_type' ) );
        add_action( 'admin_menu', array( $this, 'register_admin_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
        add_action( 'admin_init', array( $this, 'redirect_legacy_hotel_admin_routes' ) );
        add_action( 'admin_post_sthi_update_publication_lock', array( $this, 'update_publication_lock' ) );
        add_filter( 'manage_sthi_hotel_posts_columns', array( $this, 'admin_columns' ) );
        add_action( 'manage_sthi_hotel_posts_custom_column', array( $this, 'admin_column_values' ), 10, 2 );
    }

    public static function activate() {
        if ( false === get_option( 'sthi_next_hotel_number', false ) ) {
            add_option( 'sthi_next_hotel_number', 1, '', false );
        }
        if ( false === get_option( 'sthi_hotel_public_indexation', false ) ) {
            add_option( 'sthi_hotel_public_indexation', 'locked', '', false );
        }
        if ( class_exists( 'STHI_Public_Routes' ) ) {
            STHI_Public_Routes::register_rewrites();
        }
        update_option( 'sthi_route_version', STHI_Frontend::ROUTE_VERSION, false );
        flush_rewrite_rules();
    }

    public function register_hotel_post_type() {
        $labels = array(
            'name'               => 'Hotels',
            'singular_name'      => 'Hotel',
            'menu_name'          => 'Hotel Intelligence',
            'add_new'            => 'Add Hotel',
            'add_new_item'       => 'Add New Hotel',
            'edit_item'          => 'Edit Hotel',
            'new_item'           => 'New Hotel',
            'view_item'          => 'View Hotel',
            'search_items'       => 'Search Hotels',
            'not_found'          => 'No hotels found',
            'not_found_in_trash' => 'No hotels found in Trash',
        );

        register_post_type( 'sthi_hotel', array(
            'labels'             => $labels,
            'public'             => true,
            // Front-end pilot route. Kept separate from legacy /oteller/ until controlled migration.
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => false,
            'show_in_rest'       => true,
            'has_archive'        => false,
            'exclude_from_search'=> true,
            'rewrite'            => array( 'slug' => 'hotel-preview', 'with_front' => false ),
            'supports'           => array( 'title', 'excerpt', 'thumbnail', 'revisions' ),
            'menu_icon'          => 'dashicons-building',
            'capability_type'    => 'post',
            'map_meta_cap'       => true,
        ) );
    }

    public function register_admin_menu() {
        add_menu_page(
            'Hotel Intelligence',
            'Hotel Intelligence',
            'edit_posts',
            'sthi-dashboard',
            array( $this, 'render_dashboard' ),
            'dashicons-building',
            26
        );

        add_submenu_page( 'sthi-dashboard', 'Dashboard', 'Dashboard', 'edit_posts', 'sthi-dashboard', array( $this, 'render_dashboard' ) );
        add_submenu_page( 'sthi-dashboard', 'Hotels', 'Hotels', 'edit_posts', 'sthi-hotel-grid', array( 'STHI_Admin_Grid', 'render' ) );
        add_submenu_page( 'sthi-dashboard', 'Destinations', 'Destinations', 'manage_categories', 'edit-tags.php?taxonomy=sthi_destination&post_type=sthi_hotel' );
        add_submenu_page( 'sthi-dashboard', 'Collections', 'Collections', 'manage_categories', 'edit-tags.php?taxonomy=sthi_collection&post_type=sthi_hotel' );
        add_submenu_page( 'sthi-dashboard', 'Launch Control', 'Launch Control', 'manage_options', 'sthi-launch-control', array( $this, 'render_launch_control' ) );
    }


    public function update_publication_lock() {
        if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Insufficient permissions.' ); }
        check_admin_referer( 'sthi_update_publication_lock' );

        $state = isset( $_POST['enable_public_indexation'] ) ? 'enabled' : 'locked';
        update_option( 'sthi_hotel_public_indexation', $state, false );

        wp_safe_redirect( add_query_arg( array(
            'page'    => 'sthi-launch-control',
            'updated' => '1',
        ), admin_url( 'admin.php' ) ) );
        exit;
    }

    public function render_launch_control() {
        if ( ! current_user_can( 'manage_options' ) ) { return; }

        $enabled = class_exists( 'STHI_Public_Routes' ) && STHI_Public_Routes::publication_enabled();
        $routable_hotels = class_exists( 'STHI_Public_Routes' ) ? STHI_Public_Routes::routable_hotel_ids() : array();
        $indexable_hotels = class_exists( 'STHI_Public_Routes' ) ? STHI_Public_Routes::indexable_hotel_ids() : array();
        $indexable_hubs = class_exists( 'STHI_Public_Routes' ) ? STHI_Public_Routes::indexable_hub_ids() : array();
        ?>
        <div class="wrap sthi-wrap">
            <h1>Hotel Intelligence — Launch Control</h1>
            <?php if ( isset( $_GET['updated'] ) ) : ?>
                <div class="notice notice-success is-dismissible"><p>Publication lock updated. Purge LiteSpeed cache before runtime verification.</p></div>
            <?php endif; ?>

            <div class="sthi-panel" style="border-left:5px solid <?php echo $enabled ? '#b32d2e' : '#2271b1'; ?>;padding:18px 22px;margin-top:18px;max-width:900px">
                <h2 style="margin-top:0">Current state: <?php echo $enabled ? '<span style="color:#b32d2e">PUBLIC INDEXATION ENABLED</span>' : '<span style="color:#2271b1">LOCKED — NOINDEX PILOT</span>'; ?></h2>
                <p><strong>LOCKED</strong> keeps final Hotel Intelligence URLs available for owner QA, but removes canonical tags, forces <code>noindex, follow</code>, and excludes Hotel Intelligence URLs from the native WordPress sitemap.</p>
                <p><strong>ENABLED</strong> permits only eligible <code>verified + published + public-ready</code> hotels and non-empty hubs to emit self-canonical, become <code>index, follow</code>, and enter the Hotel Intelligence sitemap.</p>
            </div>

            <div class="sthi-cards" style="margin-top:18px">
                <div class="sthi-card"><span>Routable QA Hotels</span><strong><?php echo esc_html( count( $routable_hotels ) ); ?></strong></div>
                <div class="sthi-card"><span>Indexable Hotels Now</span><strong><?php echo esc_html( count( $indexable_hotels ) ); ?></strong></div>
                <div class="sthi-card"><span>Indexable Hubs Now</span><strong><?php echo esc_html( count( $indexable_hubs ) ); ?></strong></div>
            </div>

            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top:20px;max-width:900px">
                <?php wp_nonce_field( 'sthi_update_publication_lock' ); ?>
                <input type="hidden" name="action" value="sthi_update_publication_lock">
                <div class="sthi-panel">
                    <h2>Master publication switch</h2>
                    <label style="display:flex;gap:10px;align-items:flex-start;font-size:15px;line-height:1.5">
                        <input type="checkbox" name="enable_public_indexation" value="1" <?php checked( $enabled ); ?> style="margin-top:4px">
                        <span><strong>Enable public Hotel indexation.</strong><br>I understand that eligible Hotel Intelligence URLs can then be crawled/indexed by search engines and included in the sitemap.</span>
                    </label>
                    <p class="description">For the inventory-building phase, leave this unchecked.</p>
                    <?php submit_button( $enabled ? 'Save Launch State' : 'Keep Publication Locked' ); ?>
                </div>
            </form>
        </div>
        <?php
    }


    public function redirect_legacy_hotel_admin_routes() {
        if ( ! is_admin() || wp_doing_ajax() ) { return; }
        global $pagenow;
        $post_type = isset( $_GET['post_type'] ) ? sanitize_key( wp_unslash( $_GET['post_type'] ) ) : '';
        if ( 'sthi_hotel' !== $post_type ) { return; }

        // Native Trash must remain reachable so trashed Stable Hotel IDs can be restored safely.
        $post_status = isset( $_GET['post_status'] ) ? sanitize_key( wp_unslash( $_GET['post_status'] ) ) : '';
        if ( 'edit.php' === $pagenow && 'trash' === $post_status ) { return; }

        if ( in_array( $pagenow, array( 'post-new.php', 'edit.php' ), true ) ) {
            $args = array( 'page' => 'sthi-hotel-grid' );

            // Preserve taxonomy context from native WordPress count/filter links.
            foreach ( array( 'sthi_destination', 'sthi_collection' ) as $taxonomy ) {
                if ( isset( $_GET[ $taxonomy ] ) && '' !== trim( (string) $_GET[ $taxonomy ] ) ) {
                    $args[ $taxonomy ] = sanitize_title( wp_unslash( $_GET[ $taxonomy ] ) );
                }
            }

            wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php' ) ) );
            exit;
        }
    }

    public function render_dashboard() {
        if ( ! current_user_can( 'edit_posts' ) ) { return; }

        $counts = wp_count_posts( 'sthi_hotel' );
        $total = 0;
        foreach ( (array) $counts as $key => $value ) {
            if ( ! in_array( $key, array( 'trash', 'auto-draft' ), true ) ) {
                $total += (int) $value;
            }
        }

        $needs_verification = $this->count_by_meta( '_sthi_verification_status', array( 'new', 'needs_verification', 'needs_reverify' ) );
        $verified = $this->count_by_meta( '_sthi_verification_status', array( 'verified' ) );
        $ready = $this->count_by_meta( '_sthi_workflow_status', array( 'ready_to_publish' ) );
        ?>
        <div class="wrap sthi-wrap">
            <h1>Server Turizm Hotel Intelligence</h1>
            <p class="sthi-lead">Structured Hotel Intelligence foundation. Final public routes are available for QA, while the H9E master publication lock controls canonical, indexation and sitemap exposure.</p>
            <div class="sthi-cards">
                <div class="sthi-card"><span>Total Hotels</span><strong><?php echo esc_html( $total ); ?></strong></div>
                <div class="sthi-card"><span>Verified</span><strong><?php echo esc_html( $verified ); ?></strong></div>
                <div class="sthi-card"><span>Needs Verification</span><strong><?php echo esc_html( $needs_verification ); ?></strong></div>
                <div class="sthi-card"><span>Ready to Publish</span><strong><?php echo esc_html( $ready ); ?></strong></div>
            </div>
            <div class="sthi-panel">
                <h2>v0.9.9 — H10.0 Provenance Consistency Hotfix</h2>
                <ul>
                    <li>Stable Hotel ID (<code>STH-000001</code>)</li>
                    <li>Structured hotel fields + verification metadata</li>
                    <li>Hierarchical Destinations</li>
                    <li>Hierarchical Hotel Collections</li>
                    <li>Unified Hotel Spreadsheet: create + edit + status + taxonomy + delete from one screen</li>
                    <li>Manual row ordering by drag & drop</li>
                    <li>Primary hotel-photo workflow: one manual same-host Media Folder URL per hotel; folder scan syncs the gallery</li>
                    <li>Hotel Data Bridge: Paste/CSV/TSV → column mapping → Dry Run → Approve → audit/rollback</li>
                    <li>Structured Details drawer inside Hotels: standardized amenities, room types, verified reference points/distances, multiple hotel contacts and multi-scene 360° data</li>
                    <li>Data Health score per hotel to expose incomplete records before publication</li>
                    <li>Premium mobile-first hotel detail template on temporary <code>/hotel-preview/</code> URLs</li>
                    <li>Semantic H1/H2 structure, breadcrumbs, Hotel schema when verified, lazy media and click-to-load map</li>
                    <li>Long Hotel Editorial Caption: server-rendered TR summary + long content with human verification workflow</li>
                    <li>Stable Internal Link Resolver: entity/page/hub intents resolve to canonical public URLs at render time; no draft/noindex Hotel links</li>
                    <li>Pilot route is noindex + excluded from sitemap; no takeover of legacy <code>/oteller/</code> route</li>
                    <li>H7 data-driven Mekke / Medine Hotel Hub preview architecture; public activation remains locked for H9</li>
                </ul>
            </div>
        </div>
        <?php
    }

    private function count_by_meta( $key, $values ) {
        $query = new WP_Query( array(
            'post_type'      => 'sthi_hotel',
            'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'meta_query'     => array(
                array(
                    'key'     => $key,
                    'value'   => $values,
                    'compare' => 'IN',
                ),
            ),
        ) );
        return (int) $query->found_posts;
    }

    public function enqueue_admin_assets( $hook ) {
        $screen = get_current_screen();
        if ( ! $screen ) { return; }

        $is_sthi = ( 'sthi_hotel' === $screen->post_type ) || ( false !== strpos( (string) $screen->id, 'sthi' ) );
        if ( ! $is_sthi ) { return; }

        wp_enqueue_style( 'sthi-admin', STHI_URL . 'assets/admin.css', array(), STHI_VERSION );
        if ( 'sthi_hotel' === $screen->post_type ) {
            wp_enqueue_media();
        }
        wp_enqueue_script( 'sthi-admin', STHI_URL . 'assets/admin.js', array( 'jquery' ), STHI_VERSION, true );
        wp_localize_script( 'sthi-admin', 'STHI', array(
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'      => wp_create_nonce( 'sthi_grid_nonce' ),
            'mediaNonce' => wp_create_nonce( 'sthi_media_nonce' ),
            'bridgeNonce' => wp_create_nonce( 'sthi_bridge_nonce' ),
        ) );
    }

    public function admin_columns( $columns ) {
        $new = array();
        foreach ( $columns as $key => $label ) {
            $new[ $key ] = $label;
            if ( 'title' === $key ) {
                $new['sthi_hotel_id'] = 'Hotel ID';
                $new['sthi_city'] = 'City';
                $new['sthi_verify'] = 'Verification';
            }
        }
        return $new;
    }

    public function admin_column_values( $column, $post_id ) {
        if ( 'sthi_hotel_id' === $column ) {
            echo esc_html( get_post_meta( $post_id, '_sthi_hotel_id', true ) );
        } elseif ( 'sthi_city' === $column ) {
            echo esc_html( get_post_meta( $post_id, '_sthi_city', true ) );
        } elseif ( 'sthi_verify' === $column ) {
            echo esc_html( get_post_meta( $post_id, '_sthi_verification_status', true ) ?: 'new' );
        }
    }
}
