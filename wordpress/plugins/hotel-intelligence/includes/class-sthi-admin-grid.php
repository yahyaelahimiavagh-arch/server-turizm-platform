<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class STHI_Admin_Grid {
    public static function init() {
        add_action( 'wp_ajax_sthi_grid_update', array( __CLASS__, 'ajax_update' ) );
        add_action( 'wp_ajax_sthi_grid_reorder', array( __CLASS__, 'ajax_reorder' ) );
        add_action( 'wp_ajax_sthi_grid_create', array( __CLASS__, 'ajax_create' ) );
        add_action( 'wp_ajax_sthi_grid_trash', array( __CLASS__, 'ajax_trash' ) );
    }

    public static function render() {
        if ( ! current_user_can( 'edit_posts' ) ) { wp_die( 'Access denied.' ); }

        $destination_filter = isset( $_GET['sthi_destination'] ) ? sanitize_title( wp_unslash( $_GET['sthi_destination'] ) ) : '';
        $collection_filter  = isset( $_GET['sthi_collection'] ) ? sanitize_title( wp_unslash( $_GET['sthi_collection'] ) ) : '';

        $query_args = array(
            'post_type'      => 'sthi_hotel',
            'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
        );
        $tax_query = array();
        if ( $destination_filter ) {
            $tax_query[] = array( 'taxonomy' => 'sthi_destination', 'field' => 'slug', 'terms' => $destination_filter );
        }
        if ( $collection_filter ) {
            $tax_query[] = array( 'taxonomy' => 'sthi_collection', 'field' => 'slug', 'terms' => $collection_filter );
        }
        if ( $tax_query ) {
            if ( count( $tax_query ) > 1 ) { $tax_query['relation'] = 'AND'; }
            $query_args['tax_query'] = $tax_query;
        }
        $hotels = get_posts( $query_args );
        $destination_terms = get_terms( array( 'taxonomy' => 'sthi_destination', 'hide_empty' => false ) );
        $collection_terms  = get_terms( array( 'taxonomy' => 'sthi_collection', 'hide_empty' => false ) );
        $trash_count = isset( wp_count_posts( 'sthi_hotel' )->trash ) ? (int) wp_count_posts( 'sthi_hotel' )->trash : 0;
        usort( $hotels, function( $a, $b ) {
            $ao = (int) get_post_meta( $a->ID, '_sthi_manual_order', true );
            $bo = (int) get_post_meta( $b->ID, '_sthi_manual_order', true );
            if ( $ao === $bo ) { return strcasecmp( $a->post_title, $b->post_title ); }
            if ( 0 === $ao ) { return 1; }
            if ( 0 === $bo ) { return -1; }
            return $ao <=> $bo;
        } );
        ?>
        <div class="wrap sthi-wrap sthi-sheet-wrap">
            <div class="sthi-grid-head">
                <div>
                    <h1>Hotels</h1>
                    <p>Server Turizm hotel spreadsheet. Add, edit, classify, publish and remove hotels from one screen. Every cell auto-saves.</p>
                </div>
                <div class="sthi-grid-actions">
                    <button type="button" class="button button-primary" id="sthi-add-row">+ Add Row</button>
                    <?php if ( current_user_can( 'manage_options' ) ) : ?>
                    <button type="button" class="button" id="sthi-open-bridge">Import / Paste</button>
                    <button type="button" class="button" id="sthi-export-csv">Export CSV</button>
                    <?php endif; ?>
                    <select id="sthi-filter-destination" aria-label="Filter by destination">
                        <option value="">All Destinations</option>
                        <?php if ( ! is_wp_error( $destination_terms ) ) : foreach ( $destination_terms as $term ) : ?>
                            <option value="<?php echo esc_attr( $term->slug ); ?>" <?php selected( $destination_filter, $term->slug ); ?>><?php echo esc_html( $term->name ); ?></option>
                        <?php endforeach; endif; ?>
                    </select>
                    <select id="sthi-filter-collection" aria-label="Filter by collection">
                        <option value="">All Collections</option>
                        <?php if ( ! is_wp_error( $collection_terms ) ) : foreach ( $collection_terms as $term ) : ?>
                            <option value="<?php echo esc_attr( $term->slug ); ?>" <?php selected( $collection_filter, $term->slug ); ?>><?php echo esc_html( $term->name ); ?></option>
                        <?php endforeach; endif; ?>
                    </select>
                    <select id="sthi-grid-view" aria-label="Column view">
                        <option value="essential">Essential</option>
                        <option value="all">All Fields</option>
                        <option value="identity">Identity</option>
                        <option value="location">Location</option>
                        <option value="contact">Contact</option>
                        <option value="operations">Operations</option>
                        <option value="experience">Experience</option>
                        <option value="media">Media</option>
                        <option value="verification">Verification</option>
                    </select>
                    <input type="search" id="sthi-grid-search" placeholder="Search all hotels…" />
                    <button class="button" id="sthi-toggle-order" type="button">Manual Order: Off</button>
                </div>
            </div>

            <div class="sthi-sheet-toolbar">
                <label class="sthi-select-all-label"><input type="checkbox" id="sthi-select-all"> Select all visible</label>
                <span id="sthi-selected-count">0 selected</span>
                <button type="button" class="button" id="sthi-bulk-trash" disabled>Delete Selected</button>
                <a class="button" href="<?php echo esc_url( admin_url( 'edit.php?post_type=sthi_hotel&post_status=trash' ) ); ?>">Trash (<?php echo esc_html( $trash_count ); ?>)</a>
                <span class="sthi-sheet-hint">Tip: use Tab / Shift+Tab to move between cells. Destination/Collection filters show only the matching hotels.</span>
            </div>

            <?php if ( class_exists( 'STHI_Geography' ) ) : ?>
                <datalist id="sthi-country-options">
                    <?php foreach ( STHI_Geography::countries() as $country_name ) : ?><option value="<?php echo esc_attr( $country_name ); ?>"></option><?php endforeach; ?>
                </datalist>
                <datalist id="sthi-city-options">
                    <?php foreach ( STHI_Geography::city_names() as $city_name ) : ?><option value="<?php echo esc_attr( $city_name ); ?>"></option><?php endforeach; ?>
                </datalist>
            <?php endif; ?>

            <div class="sthi-grid-scroll sthi-sheet-scroll">
                <table class="widefat sthi-grid sthi-sheet" id="sthi-hotel-grid">
                    <thead><tr>
                        <th class="sthi-select-col" data-group="system"><span class="screen-reader-text">Select</span></th>
                        <th class="sthi-drag-col" data-group="system">↕</th>
                        <th class="sthi-sticky-id" data-group="system">Hotel ID</th>
                        <th class="sthi-sticky-name" data-group="system">Hotel Name</th>
                        <th data-group="system" data-essential="1">WP Status</th>
                        <?php self::render_meta_headers(); ?>
                        <th data-group="taxonomy" data-essential="1">Destinations</th>
                        <th data-group="taxonomy" data-essential="1">Collections</th>
                        <th data-group="system" data-essential="1">Media</th>
                        <th data-group="location" data-essential="1">Harem Distance</th>
                        <th data-group="system" data-essential="1">Publish Ready</th>
                        <th data-group="system" data-essential="1">Data Health</th>
                        <th data-group="system" data-essential="1">Actions</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ( $hotels as $hotel ) { echo self::row_html( $hotel ); } ?>
                    </tbody>
                </table>
            </div>
            <?php STHI_Data_Bridge::render_panel(); ?>
            <?php STHI_Structured_Details::render_modal_shell(); ?>
            <div id="sthi-toast" class="sthi-toast" aria-live="polite"></div>
        </div>
        <?php
    }

    private static function essential_meta_fields() {
        return array(
            '_sthi_official_name', '_sthi_star_rating', '_sthi_country', '_sthi_city',
            '_sthi_media_folder_url', '_sthi_verification_status', '_sthi_last_verified_at', '_sthi_workflow_status'
        );
    }

    private static function render_meta_headers() {
        $essential = array_flip( self::essential_meta_fields() );
        foreach ( STHI_Meta::fields() as $key => $config ) {
            if ( '_sthi_manual_order' === $key ) { continue; }
            $group = strtolower( sanitize_key( $config['group'] ) );
            echo '<th data-group="' . esc_attr( $group ) . '" data-field="' . esc_attr( $key ) . '"' . ( isset( $essential[ $key ] ) ? ' data-essential="1"' : '' ) . '>' . esc_html( $config['label'] ) . '</th>';
        }
    }

    private static function row_html( $hotel ) {
        $id = (int) $hotel->ID;
        $hotel_id = get_post_meta( $id, '_sthi_hotel_id', true );
        $destinations = wp_get_object_terms( $id, 'sthi_destination', array( 'fields' => 'names' ) );
        $collections  = wp_get_object_terms( $id, 'sthi_collection', array( 'fields' => 'names' ) );
        $gallery = STHI_Media::get_items( $id );
        $order = get_post_meta( $id, '_sthi_manual_order', true );
        $health = STHI_Structured_Details::health_score( $id );
        $sacred = STHI_Sacred_Distance::get( $id );

        ob_start();
        ?>
        <tr data-post-id="<?php echo esc_attr( $id ); ?>" draggable="false">
            <td class="sthi-select-col" data-group="system"><input type="checkbox" class="sthi-row-select" aria-label="Select <?php echo esc_attr( $hotel->post_title ); ?>"></td>
            <td class="sthi-drag" data-group="system">☰</td>
            <td class="sthi-fixed sthi-sticky-id" data-group="system"><?php echo esc_html( $hotel_id ); ?></td>
            <td class="sthi-sticky-name sthi-publish-required" data-publish-rule="hotel_name" data-group="system"><input class="sthi-grid-field sthi-title-field" data-field="post_title" type="text" value="<?php echo esc_attr( $hotel->post_title ); ?>"></td>
            <td class="sthi-publish-required" data-publish-rule="wp_status" data-group="system" data-essential="1"><?php self::post_status_select( $hotel->post_status ); ?></td>
            <?php self::render_meta_cells( $id ); ?>
            <td class="sthi-publish-required" data-publish-rule="destinations" data-group="taxonomy" data-essential="1"><input class="sthi-grid-field" data-field="taxonomy:sthi_destination" type="text" value="<?php echo esc_attr( is_wp_error( $destinations ) ? '' : implode( ', ', $destinations ) ); ?>" placeholder="Makkah, Ajyad"></td>
            <td data-group="taxonomy" data-essential="1"><input class="sthi-grid-field" data-field="taxonomy:sthi_collection" type="text" value="<?php echo esc_attr( is_wp_error( $collections ) ? '' : implode( ', ', $collections ) ); ?>" placeholder="Umre Otelleri"></td>
            <td class="sthi-media-cell sthi-publish-required" data-publish-rule="media" data-group="system" data-essential="1"><span class="sthi-media-count-badge"><?php echo esc_html( count( $gallery ) ); ?></span></td>
            <td class="sthi-haram-cell" data-group="location" data-essential="1">
                <?php if ( ! empty( $sacred['detected'] ) ) : ?>
                    <span class="sthi-haram-badge"><strong><?php echo esc_html( $sacred['target_short'] ); ?></strong><small><?php echo esc_html( $sacred['distance_label'] ?: 'Coordinates needed' ); ?></small></span>
                <?php else : ?>
                    <span class="sthi-haram-badge is-muted"><strong>—</strong><small>Not Makkah/Madinah</small></span>
                <?php endif; ?>
            </td>
            <td class="sthi-publish-ready-cell" data-group="system" data-essential="1"><span class="sthi-publish-ready-badge" aria-live="polite"><strong>Checking…</strong><small></small></span></td>
            <td class="sthi-health-cell" data-group="system" data-essential="1"><span class="sthi-health-score" data-health-score="<?php echo esc_attr( $health ); ?>"><strong><?php echo esc_html( $health ); ?>%</strong><small><?php echo esc_html( STHI_Structured_Details::health_label( $health ) ); ?></small></span></td>
            <td class="sthi-row-actions" data-group="system" data-essential="1">
                <button type="button" class="button button-small sthi-open-details">Details</button>
                <a class="button button-small" href="<?php echo esc_url( get_edit_post_link( $id ) ); ?>">Media</a>
                <button type="button" class="button-link-delete sthi-trash-row">Delete</button>
                <span class="sthi-order-value" hidden><?php echo esc_html( $order ); ?></span>
            </td>
        </tr>
        <?php
        return ob_get_clean();
    }

    private static function render_meta_cells( $post_id ) {
        $essential = array_flip( self::essential_meta_fields() );
        foreach ( STHI_Meta::fields() as $key => $config ) {
            if ( '_sthi_manual_order' === $key ) { continue; }
            $group = strtolower( sanitize_key( $config['group'] ) );
            $value = get_post_meta( $post_id, $key, true );
            $publish_rules = array(
                '_sthi_official_name'       => 'official_name',
                '_sthi_country'             => 'country',
                '_sthi_city'                => 'city',
                '_sthi_verification_status' => 'verification',
                '_sthi_last_verified_at'    => 'last_verified',
                '_sthi_workflow_status'     => 'workflow',
            );
            $publish_class = isset( $publish_rules[ $key ] ) ? ' class="sthi-publish-required" data-publish-rule="' . esc_attr( $publish_rules[ $key ] ) . '"' : '';
            echo '<td data-group="' . esc_attr( $group ) . '"' . $publish_class . ( isset( $essential[ $key ] ) ? ' data-essential="1"' : '' ) . '>';
            self::render_grid_control( $key, $config, $value );
            echo '</td>';
        }
    }

    private static function render_grid_control( $key, $config, $value ) {
        $type = $config['type'];
        if ( 'tri' === $type ) {
            self::grid_select( $key, $value ?: 'unknown', array( 'unknown'=>'Unknown','yes'=>'Yes','no'=>'No' ) );
            return;
        }
        if ( 'verification' === $type ) {
            self::grid_select( $key, $value ?: 'new', array( 'new'=>'New','needs_verification'=>'Needs Verification','verified'=>'Verified','needs_reverify'=>'Needs Reverify' ) );
            return;
        }
        if ( 'workflow' === $type ) {
            self::grid_select( $key, $value ?: 'new', array( 'new'=>'New','needs_verification'=>'Needs Verification','verified'=>'Verified','ready_to_publish'=>'Ready to Publish','published'=>'Published','needs_reverify'=>'Needs Reverify','archived'=>'Archived' ) );
            return;
        }
        $html_type = in_array( $type, array( 'number', 'date', 'email', 'url' ), true ) ? $type : 'text';
        $min = isset( $config['min'] ) ? ' min="' . esc_attr( $config['min'] ) . '"' : '';
        $max = isset( $config['max'] ) ? ' max="' . esc_attr( $config['max'] ) . '"' : '';
        $list = '';
        if ( '_sthi_country' === $key ) { $list = ' list="sthi-country-options" autocomplete="off"'; }
        if ( '_sthi_city' === $key ) { $list = ' list="sthi-city-options" autocomplete="off"'; }
        echo '<input class="sthi-grid-field' . ( ( 'textarea' === $type || '_sthi_media_folder_url' === $key ) ? ' sthi-wide-field' : '' ) . '" data-field="' . esc_attr( $key ) . '" type="' . esc_attr( $html_type ) . '" value="' . esc_attr( $value ) . '"' . $min . $max . $list . '>';
    }

    private static function grid_select( $field, $current, $options ) {
        echo '<select class="sthi-grid-field sthi-select" data-field="' . esc_attr( $field ) . '">';
        foreach ( $options as $value => $label ) {
            echo '<option value="' . esc_attr( $value ) . '" ' . selected( $current, $value, false ) . '>' . esc_html( $label ) . '</option>';
        }
        echo '</select>';
    }

    private static function post_status_select( $current ) {
        self::grid_select( 'post_status', $current, array(
            'draft'   => 'Draft',
            'pending' => 'Pending',
            'publish' => 'Published',
            'private' => 'Private',
        ) );
    }

    public static function ajax_update() {
        check_ajax_referer( 'sthi_grid_nonce', 'nonce' );
        if ( ! current_user_can( 'edit_posts' ) ) { wp_send_json_error( array( 'message' => 'Access denied.' ), 403 ); }

        $post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
        $field = isset( $_POST['field'] ) ? sanitize_text_field( wp_unslash( $_POST['field'] ) ) : '';
        $value = isset( $_POST['value'] ) ? wp_unslash( $_POST['value'] ) : '';

        if ( ! $post_id || 'sthi_hotel' !== get_post_type( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
            wp_send_json_error( array( 'message' => 'Invalid hotel.' ), 400 );
        }

        if ( 'post_title' === $field ) {
            $old_title = get_the_title( $post_id );
            $title = sanitize_text_field( $value );
            if ( '' === $title ) { $title = 'Untitled Hotel'; }
            $result = wp_update_post( array( 'ID' => $post_id, 'post_title' => $title ), true );
            if ( is_wp_error( $result ) ) { wp_send_json_error( array( 'message' => $result->get_error_message() ), 400 ); }
            $official = get_post_meta( $post_id, '_sthi_official_name', true );
            if ( ! $official || 'Yeni Otel' === $official || $official === $old_title ) {
                update_post_meta( $post_id, '_sthi_official_name', $title );
            }
            if ( class_exists( 'STHI_Public_Routes' ) ) { STHI_Public_Routes::ensure_public_slug( $post_id ); }
            $health = STHI_Structured_Details::health_score( $post_id );
            wp_send_json_success( array( 'health' => $health, 'health_label' => STHI_Structured_Details::health_label( $health ) ) );
        }

        if ( 'post_status' === $field ) {
            $status = sanitize_key( $value );
            if ( ! in_array( $status, array( 'draft', 'pending', 'publish', 'private' ), true ) ) {
                wp_send_json_error( array( 'message' => 'Invalid status.' ), 400 );
            }
            $result = wp_update_post( array( 'ID' => $post_id, 'post_status' => $status ), true );
            if ( is_wp_error( $result ) ) { wp_send_json_error( array( 'message' => $result->get_error_message() ), 400 ); }
            if ( class_exists( 'STHI_Public_Routes' ) ) { STHI_Public_Routes::ensure_public_slug( $post_id ); }
            $health = STHI_Structured_Details::health_score( $post_id );
            wp_send_json_success( array( 'health' => $health, 'health_label' => STHI_Structured_Details::health_label( $health ) ) );
        }

        if ( 0 === strpos( $field, 'taxonomy:' ) ) {
            $taxonomy = sanitize_key( substr( $field, 9 ) );
            if ( ! in_array( $taxonomy, array( 'sthi_destination', 'sthi_collection' ), true ) ) {
                wp_send_json_error( array( 'message' => 'Taxonomy not allowed.' ), 400 );
            }
            $names = array_filter( array_map( 'trim', preg_split( '/[,\n]+/', sanitize_textarea_field( $value ) ) ) );
            $term_ids = array();
            foreach ( array_unique( $names ) as $name ) {
                if ( 'sthi_destination' === $taxonomy && class_exists( 'STHI_Geography' ) ) {
                    $name = STHI_Geography::canonical_city( $name );
                    $name = STHI_Geography::canonical_country( $name );
                }
                $term = term_exists( $name, $taxonomy );
                if ( ! $term ) { $term = wp_insert_term( $name, $taxonomy ); }
                if ( is_wp_error( $term ) ) { continue; }
                $term_ids[] = (int) ( is_array( $term ) ? $term['term_id'] : $term );
            }
            $result = wp_set_object_terms( $post_id, $term_ids, $taxonomy, false );
            if ( is_wp_error( $result ) ) { wp_send_json_error( array( 'message' => $result->get_error_message() ), 400 ); }
            if ( class_exists( 'STHI_Public_Routes' ) ) { STHI_Public_Routes::ensure_public_slug( $post_id ); }
            $health = STHI_Structured_Details::health_score( $post_id );
            wp_send_json_success( array( 'health' => $health, 'health_label' => STHI_Structured_Details::health_label( $health ) ) );
        }

        $fields = STHI_Meta::fields();
        if ( ! isset( $fields[ $field ] ) ) { wp_send_json_error( array( 'message' => 'Field not allowed.' ), 400 ); }

        $sanitized = STHI_Meta::sanitize( $value, $fields[ $field ]['type'] );
        if ( class_exists( 'STHI_Geography' ) && in_array( $field, array( '_sthi_country', '_sthi_city' ), true ) ) {
            $sanitized = STHI_Geography::normalize_meta_value( $field, $sanitized );
        }
        if ( '_sthi_media_folder_url' === $field ) {
            $sync = STHI_Media::sync_folder_to_gallery( $post_id, $sanitized );
            if ( is_wp_error( $sync ) ) { wp_send_json_error( array( 'message' => $sync->get_error_message() ), 400 ); }
            if ( class_exists( 'STHI_Public_Routes' ) ) { STHI_Public_Routes::ensure_public_slug( $post_id ); }
            $health = STHI_Structured_Details::health_score( $post_id );
            wp_send_json_success( array( 'media_count' => isset( $sync['count'] ) ? (int) $sync['count'] : 0, 'health' => $health, 'health_label' => STHI_Structured_Details::health_label( $health ) ) );
        }
        update_post_meta( $post_id, $field, $sanitized );
        if ( class_exists( 'STHI_Geography' ) && in_array( $field, array( '_sthi_country', '_sthi_city' ), true ) ) {
            STHI_Geography::sync_post_geography( $post_id );
        }
        if ( class_exists( 'STHI_Public_Routes' ) ) { STHI_Public_Routes::ensure_public_slug( $post_id ); }
        $health = STHI_Structured_Details::health_score( $post_id );
        wp_send_json_success( array(
            'health' => $health,
            'health_label' => STHI_Structured_Details::health_label( $health ),
            'normalized_value' => get_post_meta( $post_id, $field, true ),
            'country_value' => get_post_meta( $post_id, '_sthi_country', true ),
            'city_value' => get_post_meta( $post_id, '_sthi_city', true ),
            'destination_value' => implode( ', ', (array) wp_get_object_terms( $post_id, 'sthi_destination', array( 'fields' => 'names' ) ) ),
        ) );
    }

    public static function ajax_create() {
        check_ajax_referer( 'sthi_grid_nonce', 'nonce' );
        if ( ! current_user_can( 'edit_posts' ) ) { wp_send_json_error( array( 'message' => 'Access denied.' ), 403 ); }

        $max_order = 0;
        $last = get_posts( array(
            'post_type' => 'sthi_hotel', 'post_status' => array( 'publish','draft','pending','private' ),
            'posts_per_page' => 1, 'meta_key' => '_sthi_manual_order', 'orderby' => 'meta_value_num', 'order' => 'DESC', 'fields' => 'ids'
        ) );
        if ( $last ) { $max_order = (int) get_post_meta( $last[0], '_sthi_manual_order', true ); }

        $post_id = wp_insert_post( array(
            'post_type'   => 'sthi_hotel',
            'post_status' => 'draft',
            'post_title'  => 'Yeni Otel',
        ), true );
        if ( is_wp_error( $post_id ) ) { wp_send_json_error( array( 'message' => $post_id->get_error_message() ), 400 ); }

        update_post_meta( $post_id, '_sthi_official_name', 'Yeni Otel' );
        update_post_meta( $post_id, '_sthi_verification_status', 'new' );
        update_post_meta( $post_id, '_sthi_workflow_status', 'new' );
        update_post_meta( $post_id, '_sthi_manual_order', $max_order + 10 );

        $hotel = get_post( $post_id );
        wp_send_json_success( array(
            'post_id'  => $post_id,
            'hotel_id' => get_post_meta( $post_id, '_sthi_hotel_id', true ),
            'row_html' => self::row_html( $hotel ),
        ) );
    }

    public static function ajax_trash() {
        check_ajax_referer( 'sthi_grid_nonce', 'nonce' );
        if ( ! current_user_can( 'edit_posts' ) ) { wp_send_json_error( array( 'message' => 'Access denied.' ), 403 ); }
        $ids = isset( $_POST['ids'] ) ? (array) $_POST['ids'] : array();
        $trashed = array();
        foreach ( $ids as $raw_id ) {
            $post_id = absint( $raw_id );
            if ( ! $post_id || 'sthi_hotel' !== get_post_type( $post_id ) || ! current_user_can( 'delete_post', $post_id ) ) { continue; }
            $result = wp_trash_post( $post_id );
            if ( $result ) { $trashed[] = $post_id; }
        }
        if ( ! $trashed ) { wp_send_json_error( array( 'message' => 'No hotel was deleted.' ), 400 ); }
        wp_send_json_success( array( 'ids' => $trashed ) );
    }

    public static function ajax_reorder() {
        check_ajax_referer( 'sthi_grid_nonce', 'nonce' );
        if ( ! current_user_can( 'edit_posts' ) ) { wp_send_json_error( array( 'message' => 'Access denied.' ), 403 ); }

        $ids = isset( $_POST['ids'] ) ? (array) $_POST['ids'] : array();
        $position = 10;
        foreach ( $ids as $raw_id ) {
            $post_id = absint( $raw_id );
            if ( $post_id && 'sthi_hotel' === get_post_type( $post_id ) && current_user_can( 'edit_post', $post_id ) ) {
                update_post_meta( $post_id, '_sthi_manual_order', $position );
                $position += 10;
            }
        }
        wp_send_json_success();
    }
}
STHI_Admin_Grid::init();
