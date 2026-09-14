<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Hotel Data Bridge
 * Manual CSV/TSV/Paste -> Analyze -> Map -> Dry Run -> Approve -> Audit/Rollback.
 * v0.4.2 intentionally keeps direct Google Sheets/API sync out of scope.
 */
final class STHI_Data_Bridge {
    const NONCE_ACTION = 'sthi_bridge_nonce';
    const BATCH_POST_TYPE = 'sthi_import_batch';
    const MAX_ROWS = 300;
    const CLEAR_TOKEN = '__CLEAR__';

    public static function init() {
        add_action( 'init', array( __CLASS__, 'register_batch_post_type' ), 30 );
        add_action( 'wp_ajax_sthi_bridge_analyze', array( __CLASS__, 'ajax_analyze' ) );
        add_action( 'wp_ajax_sthi_bridge_preview', array( __CLASS__, 'ajax_preview' ) );
        add_action( 'wp_ajax_sthi_bridge_apply', array( __CLASS__, 'ajax_apply' ) );
        add_action( 'wp_ajax_sthi_bridge_export', array( __CLASS__, 'ajax_export' ) );
        add_action( 'wp_ajax_sthi_bridge_rollback', array( __CLASS__, 'ajax_rollback' ) );
    }

    public static function register_batch_post_type() {
        register_post_type( self::BATCH_POST_TYPE, array(
            'label'               => 'Hotel Import Batches',
            'public'              => false,
            'publicly_queryable'  => false,
            'show_ui'             => false,
            'show_in_menu'        => false,
            'show_in_rest'        => false,
            'exclude_from_search' => true,
            'supports'            => array( 'title', 'editor' ),
            'capability_type'     => 'post',
            'map_meta_cap'        => true,
        ) );
    }

    private static function can_manage() {
        return current_user_can( 'manage_options' );
    }

    public static function bridge_fields() {
        $fields = array(
            'hotel_id' => array(
                'label' => 'Hotel ID', 'kind' => 'hotel_id', 'target' => '_sthi_hotel_id', 'type' => 'text',
                'aliases' => array( 'hotel_id', 'id', 'sthi_id', 'entity_id' ),
            ),
            'hotel_name' => array(
                'label' => 'Hotel Name', 'kind' => 'system', 'target' => 'post_title', 'type' => 'text',
                'aliases' => array( 'hotel_name', 'name', 'title', 'otel_adi', 'otel_adı' ),
            ),
            'wp_status' => array(
                'label' => 'WP Status', 'kind' => 'system', 'target' => 'post_status', 'type' => 'status',
                'aliases' => array( 'wp_status', 'post_status', 'status' ),
            ),
            'destinations' => array(
                'label' => 'Destinations', 'kind' => 'taxonomy', 'target' => 'sthi_destination', 'type' => 'terms',
                'aliases' => array( 'destinations', 'destination', 'destinasyon', 'destinasyonlar' ),
            ),
            'collections' => array(
                'label' => 'Collections', 'kind' => 'taxonomy', 'target' => 'sthi_collection', 'type' => 'terms',
                'aliases' => array( 'collections', 'collection', 'hotel_collections', 'koleksiyon', 'koleksiyonlar' ),
            ),
        );

        // F-HOTEL-CONTENT-001: long editorial content and verification state.
        // Blank cell = no-op. __CLEAR__ intentionally clears the selected field.
        $fields['editorial_summary_tr'] = array(
            'label' => 'Editorial Summary TR', 'kind' => 'meta', 'target' => STHI_Content::META_SUMMARY, 'type' => 'textarea',
            'aliases' => array( 'editorial_summary_tr', 'editorial_summary', 'hotel_summary_tr', 'long_caption_summary' ),
        );
        $fields['editorial_long_tr'] = array(
            'label' => 'Editorial Long TR', 'kind' => 'meta', 'target' => STHI_Content::META_LONG, 'type' => 'editorial_html',
            'aliases' => array( 'editorial_long_tr', 'editorial_long', 'hotel_editorial_tr', 'hotel_article_tr', 'long_form_content_tr' ),
        );
        $fields['long_caption_tr'] = array(
            'label' => 'Reusable Long Caption TR', 'kind' => 'meta', 'target' => STHI_Content::META_LONG_CAPTION, 'type' => 'textarea',
            'aliases' => array( 'long_caption_tr', 'long_caption', 'reusable_long_caption', 'caption_tr' ),
        );
        $fields['content_source_mode'] = array(
            'label' => 'Content Source Mode', 'kind' => 'meta', 'target' => STHI_Content::META_SOURCE_MODE, 'type' => 'content_source_mode',
            'aliases' => array( 'content_source_mode', 'editorial_source_mode', 'caption_source_mode' ),
        );
        $fields['content_verified_at'] = array(
            'label' => 'Content Verified At', 'kind' => 'meta', 'target' => STHI_Content::META_VERIFIED_AT, 'type' => 'date',
            'aliases' => array( 'content_verified_at', 'editorial_verified_at', 'caption_verified_at' ),
        );
        $fields['content_verified_by'] = array(
            'label' => 'Content Verified By', 'kind' => 'meta', 'target' => STHI_Content::META_VERIFIED_BY, 'type' => 'text',
            'aliases' => array( 'content_verified_by', 'editorial_verified_by', 'caption_verified_by' ),
        );
        $fields['content_source_notes'] = array(
            'label' => 'Content Source Notes', 'kind' => 'meta', 'target' => STHI_Content::META_SOURCE_NOTES, 'type' => 'textarea',
            'aliases' => array( 'content_source_notes', 'editorial_source_notes', 'caption_source_notes' ),
        );
        $fields['link_intents_json'] = array(
            'label' => 'Stable Link Intents JSON', 'kind' => 'meta', 'target' => STHI_Content::META_LINK_INTENTS, 'type' => 'link_intents_json',
            'aliases' => array( 'link_intents_json', 'link_intents', 'editorial_link_intents', 'internal_link_intents' ),
        );

        // Complex hotel details are stored as JSON-in-cell for CSV/TSV/Paste imports.
        // Blank cell = no-op. __CLEAR__ = clear the entire structured group.
        $fields['amenities_json'] = array(
            'label' => 'Amenities JSON', 'kind' => 'structured', 'target' => 'amenities', 'type' => 'structured_json',
            'aliases' => array( 'amenities_json', 'amenities', 'hotel_amenities', 'facilities_json' ),
        );
        $fields['rooms_json'] = array(
            'label' => 'Room Types JSON', 'kind' => 'structured', 'target' => 'rooms', 'type' => 'structured_json',
            'aliases' => array( 'rooms_json', 'room_types_json', 'rooms', 'room_types' ),
        );
        $fields['reference_points_json'] = array(
            'label' => 'Reference Points JSON', 'kind' => 'structured', 'target' => 'references', 'type' => 'structured_json',
            'aliases' => array( 'reference_points_json', 'references_json', 'reference_points', 'distances_json' ),
        );
        $fields['contacts_json'] = array(
            'label' => 'Contacts JSON', 'kind' => 'structured', 'target' => 'contacts', 'type' => 'structured_json',
            'aliases' => array( 'contacts_json', 'contacts', 'hotel_contacts_json' ),
        );
        $fields['scenes360_json'] = array(
            'label' => '360 Scenes JSON', 'kind' => 'structured', 'target' => 'scenes', 'type' => 'structured_json',
            'aliases' => array( 'scenes360_json', '360_scenes_json', 'scenes_360_json', '360_json', 'scenes360' ),
        );

        $friendly = array(
            '_sthi_official_name'         => array( 'official_name', array( 'official_name', 'official_hotel_name', 'resmi_ad' ) ),
            '_sthi_local_name'            => array( 'local_name', array( 'local_name', 'yerel_ad' ) ),
            '_sthi_alternative_names'     => array( 'alternative_names', array( 'alternative_names', 'alt_names', 'aliases' ) ),
            '_sthi_display_name_tr'        => array( 'display_name_tr', array( 'display_name_tr', 'hotel_display_name_tr' ) ),
            '_sthi_display_name_en'        => array( 'display_name_en', array( 'display_name_en', 'hotel_display_name_en' ) ),
            '_sthi_display_name_ar'        => array( 'display_name_ar', array( 'display_name_ar', 'hotel_display_name_ar' ) ),
            '_sthi_brand'                  => array( 'brand', array( 'brand', 'hotel_brand' ) ),
            '_sthi_hotel_chain'           => array( 'hotel_chain', array( 'hotel_chain', 'chain' ) ),
            '_sthi_hotel_type'            => array( 'hotel_type', array( 'hotel_type', 'type' ) ),
            '_sthi_star_rating'           => array( 'stars', array( 'stars', 'star_rating', 'official_star_rating', 'yildiz', 'yıldız' ) ),
            '_sthi_license_number'        => array( 'license_number', array( 'license_number', 'registration_number', 'hotel_license_number' ) ),
            '_sthi_opening_year'          => array( 'opening_year', array( 'opening_year', 'opened' ) ),
            '_sthi_renovation_year'       => array( 'renovation_year', array( 'renovation_year', 'renovated' ) ),
            '_sthi_country'               => array( 'country', array( 'country', 'ulke', 'ülke' ) ),
            '_sthi_region'                => array( 'region', array( 'region', 'bolge', 'bölge' ) ),
            '_sthi_city'                  => array( 'city', array( 'city', 'sehir', 'şehir' ) ),
            '_sthi_district'              => array( 'district', array( 'district', 'ilce', 'ilçe' ) ),
            '_sthi_neighborhood'          => array( 'neighborhood', array( 'neighborhood', 'mahalle' ) ),
            '_sthi_address'               => array( 'address', array( 'address', 'full_address', 'adres' ) ),
            '_sthi_postal_code'           => array( 'postal_code', array( 'postal_code', 'zip', 'zip_code' ) ),
            '_sthi_latitude'              => array( 'latitude', array( 'latitude', 'lat' ) ),
            '_sthi_longitude'             => array( 'longitude', array( 'longitude', 'lng', 'lon', 'long' ) ),
            '_sthi_google_maps_url'       => array( 'google_maps_url', array( 'google_maps_url', 'maps_url', 'map_url' ) ),
            '_sthi_google_place_id'       => array( 'google_place_id', array( 'google_place_id', 'place_id' ) ),
            '_sthi_google_maps_embed_url' => array( 'google_maps_embed_url', array( 'google_maps_embed_url', 'maps_embed_url' ) ),
            '_sthi_main_phone'            => array( 'main_phone', array( 'main_phone', 'phone', 'telefon' ) ),
            '_sthi_reservation_phone'     => array( 'reservation_phone', array( 'reservation_phone', 'booking_phone' ) ),
            '_sthi_whatsapp'              => array( 'whatsapp', array( 'whatsapp', 'whatsapp_phone' ) ),
            '_sthi_email'                 => array( 'email', array( 'email', 'main_email' ) ),
            '_sthi_reservation_email'     => array( 'reservation_email', array( 'reservation_email', 'booking_email' ) ),
            '_sthi_website'               => array( 'website', array( 'website', 'official_website', 'web' ) ),
            '_sthi_instagram'             => array( 'instagram', array( 'instagram' ) ),
            '_sthi_facebook'              => array( 'facebook', array( 'facebook' ) ),
            '_sthi_youtube'               => array( 'youtube', array( 'youtube' ) ),
            '_sthi_checkin'               => array( 'checkin', array( 'checkin', 'check_in' ) ),
            '_sthi_checkout'              => array( 'checkout', array( 'checkout', 'check_out' ) ),
            '_sthi_room_count'            => array( 'room_count', array( 'room_count', 'number_of_rooms' ) ),
            '_sthi_floor_count'           => array( 'floor_count', array( 'floor_count', 'number_of_floors', 'floors' ) ),
            '_sthi_wifi'                  => array( 'wifi', array( 'wifi', 'wi_fi' ) ),
            '_sthi_restaurant'            => array( 'restaurant', array( 'restaurant' ) ),
            '_sthi_elevator'              => array( 'elevator', array( 'elevator', 'lift' ) ),
            '_sthi_meal_plan'             => array( 'meal_plan', array( 'meal_plan', 'meals' ) ),
            '_sthi_accessibility_notes'   => array( 'accessibility_notes', array( 'accessibility_notes', 'accessibility' ) ),
            '_sthi_operational_notes'     => array( 'operational_notes', array( 'operational_notes', 'server_turizm_notes' ) ),
            '_sthi_360_label'             => array( '360_label', array( '360_label', 'tour_360_label' ) ),
            '_sthi_360_url'               => array( '360_url', array( '360_url', 'tour_360_url' ) ),
            '_sthi_media_folder_url'      => array( 'media_folder_url', array( 'media_folder_url', 'photo_folder_url', 'image_folder_url', 'folder_url' ) ),
            '_sthi_seo_title_tr'          => array( 'seo_title_tr', array( 'seo_title_tr', 'seo_title', 'search_title_tr' ) ),
            '_sthi_meta_description_tr'   => array( 'meta_description_tr', array( 'meta_description_tr', 'meta_description', 'search_description_tr' ) ),
            '_sthi_primary_topic_tr'       => array( 'primary_topic_tr', array( 'primary_topic_tr', 'primary_topic', 'primary_semantic_topic' ) ),
            '_sthi_secondary_topics_tr'    => array( 'secondary_topics_tr', array( 'secondary_topics_tr', 'secondary_topics', 'secondary_semantic_topics' ) ),
            '_sthi_verification_status'   => array( 'verification_status', array( 'verification_status', 'verification' ) ),
            '_sthi_star_rating_source_url'=> array( 'star_rating_source_url', array( 'star_rating_source_url', 'stars_source_url' ) ),
            '_sthi_star_rating_verified_at'=> array( 'star_rating_verified_at', array( 'star_rating_verified_at', 'stars_verified_at' ) ),
            '_sthi_last_verified_at'      => array( 'last_verified_at', array( 'last_verified_at', 'verified_at' ) ),
            '_sthi_source_url'            => array( 'source_url', array( 'source_url', 'primary_source_url' ) ),
            '_sthi_source_note'           => array( 'source_note', array( 'source_note', 'source_notes' ) ),
            '_sthi_source_checked_at'     => array( 'source_checked_at', array( 'source_checked_at', 'sources_checked_at' ) ),
            '_sthi_source_ledger_json'    => array( 'source_ledger_json', array( 'source_ledger_json', 'source_ledger', 'provenance_json' ) ),
            '_sthi_media_rights_note'     => array( 'media_rights_note', array( 'media_rights_note', 'media_source_note', 'photo_rights_note' ) ),
            '_sthi_workflow_status'       => array( 'workflow_status', array( 'workflow_status', 'workflow' ) ),
            '_sthi_manual_order'          => array( 'manual_order', array( 'manual_order', 'order', 'sort_order' ) ),
        );

        $meta_fields = STHI_Meta::fields();
        foreach ( $friendly as $meta_key => $pair ) {
            if ( ! isset( $meta_fields[ $meta_key ] ) ) { continue; }
            $canonical = $pair[0];
            $config = $meta_fields[ $meta_key ];
            $fields[ $canonical ] = array(
                'label'   => $config['label'],
                'kind'    => 'meta',
                'target'  => $meta_key,
                'type'    => $config['type'],
                'min'     => isset( $config['min'] ) ? $config['min'] : null,
                'max'     => isset( $config['max'] ) ? $config['max'] : null,
                'aliases' => array_unique( array_merge( array( $canonical, ltrim( $meta_key, '_' ) ), $pair[1] ) ),
            );
        }
        return $fields;
    }

    public static function canonical_headers() {
        return array_keys( self::bridge_fields() );
    }

    public static function render_panel() {
        if ( ! self::can_manage() ) { return; }
        $headers = self::canonical_headers();
        ?>
        <section class="sthi-bridge" id="sthi-data-bridge" hidden>
            <div class="sthi-bridge-head">
                <div>
                    <h2>Hotel Data Bridge</h2>
                    <p>Paste ChatGPT/Excel data or load a CSV/TSV file. Nothing changes until Dry Run is reviewed and <strong>Approve Import</strong> is clicked.</p>
                </div>
                <button type="button" class="button" id="sthi-close-bridge">Close</button>
            </div>

            <div class="sthi-bridge-flow">
                <span>1. Paste / CSV</span><b>→</b><span>2. Map Columns</span><b>→</b><span>3. Dry Run</span><b>→</b><span>4. Approve</span>
            </div>

            <div class="sthi-bridge-input-grid">
                <div class="sthi-bridge-input">
                    <div class="sthi-bridge-input-actions">
                        <label class="button" for="sthi-bridge-file">Load CSV / TSV</label>
                        <input id="sthi-bridge-file" type="file" accept=".csv,.tsv,.txt,text/csv,text/tab-separated-values" hidden>
                        <button class="button" type="button" id="sthi-bridge-template">Download Template</button>
                        <button class="button" type="button" id="sthi-bridge-copy-headers">Copy Headers</button>
                    </div>
                    <textarea id="sthi-bridge-raw" rows="11" placeholder="hotel_id\thotel_name\tofficial_name\tcity\tbrand\thotel_chain\tmedia_folder_url\teditorial_long_tr\tlong_caption_tr\tamenities_json\trooms_json\treference_points_json\tsource_ledger_json\nSTH-000010\tNusk AlHijra Hotel\tNusk AlHijra Hotel\tMadinah\t...\t...\thttps://serverturizm.com.tr/...\t&lt;h2&gt;...&lt;/h2&gt;\tReusable caption...\t{&quot;wifi&quot;:&quot;yes&quot;}\t[]\t[]\t[]"></textarea>
                    <p class="description">Recommended for ChatGPT output: tab-separated rows (TSV). CSV is also accepted. Blank cells do not overwrite existing values. Use <code><?php echo esc_html( self::CLEAR_TOKEN ); ?></code> only when you intentionally want to clear a value.</p>
                    <button class="button button-primary" type="button" id="sthi-bridge-analyze">Analyze Columns</button>
                </div>
                <div class="sthi-bridge-rules">
                    <h3>Import rules</h3>
                    <ul>
                        <li><strong>hotel_id</strong> updates an existing Hotel Entity.</li>
                        <li>Blank hotel_id creates a new Hotel ID automatically.</li>
                        <li>Missing column = do not touch that field.</li>
                        <li>Blank update cell = do not touch that field.</li>
                        <li><code>__CLEAR__</code> = intentionally clear that field.</li>
                        <li><strong>media_folder_url</strong> is the photo source; the folder is scanned only after Approve.</li>
                        <li><strong>amenities_json / rooms_json / reference_points_json / contacts_json / scenes360_json</strong> accept structured JSON generated by ChatGPT. Leave blank to skip that group.</li>
                        <li><strong>editorial_long_tr</strong> is the full Hotel article. <strong>long_caption_tr</strong> is a separate reusable caption; they never overwrite each other.</li>
                        <li><strong>source_ledger_json</strong> stores traceable field/claim evidence. Invalid JSON is rejected during Dry Run.</li>
                        <li><strong>rooms_json</strong> uses <code>capacity</code>; <code>occupancy</code> is accepted as a compatibility alias. Reference points can store coordinates + explicit distance method.</li>
                        <li><strong>link_intents_json</strong> stores stable relationship targets instead of fragile copied URLs.</li>
                        <li>A supplied unknown Hotel ID is blocked instead of silently creating a duplicate.</li>
                    </ul>
                </div>
            </div>

            <div id="sthi-bridge-mapping" class="sthi-bridge-step" hidden></div>
            <div id="sthi-bridge-preview" class="sthi-bridge-step" hidden></div>

            <div class="sthi-bridge-history">
                <h3>Recent imports</h3>
                <div id="sthi-bridge-history-list"><?php self::render_history(); ?></div>
            </div>
            <textarea id="sthi-bridge-header-source" hidden><?php echo esc_textarea( implode( "\t", $headers ) ); ?></textarea>
        </section>
        <?php
    }

    public static function render_history() {
        if ( ! self::can_manage() ) { return; }
        $batches = get_posts( array(
            'post_type'      => self::BATCH_POST_TYPE,
            'post_status'    => 'private',
            'posts_per_page' => 10,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ) );
        if ( ! $batches ) {
            echo '<p class="description">No Hotel Data Bridge imports yet.</p>';
            return;
        }
        echo '<table class="widefat striped sthi-history-table"><thead><tr><th>Date</th><th>Created</th><th>Updated</th><th>Skipped</th><th>Status</th><th></th></tr></thead><tbody>';
        foreach ( $batches as $batch ) {
            $summary = get_post_meta( $batch->ID, '_sthi_batch_summary', true );
            if ( ! is_array( $summary ) ) { $summary = array(); }
            $rolled = (bool) get_post_meta( $batch->ID, '_sthi_batch_rolled_back', true );
            echo '<tr data-batch-id="' . esc_attr( $batch->ID ) . '">';
            echo '<td>' . esc_html( get_date_from_gmt( $batch->post_date_gmt, 'Y-m-d H:i' ) ) . '</td>';
            echo '<td>' . esc_html( isset( $summary['created'] ) ? $summary['created'] : 0 ) . '</td>';
            echo '<td>' . esc_html( isset( $summary['updated'] ) ? $summary['updated'] : 0 ) . '</td>';
            echo '<td>' . esc_html( isset( $summary['skipped'] ) ? $summary['skipped'] : 0 ) . '</td>';
            echo '<td>' . esc_html( $rolled ? 'Rolled back' : 'Applied' ) . '</td>';
            echo '<td>';
            if ( ! $rolled ) {
                echo '<button type="button" class="button button-small sthi-bridge-rollback" data-batch-id="' . esc_attr( $batch->ID ) . '">Rollback</button>';
            }
            echo '</td></tr>';
        }
        echo '</tbody></table>';
    }

    public static function ajax_analyze() {
        check_ajax_referer( self::NONCE_ACTION, 'nonce' );
        if ( ! self::can_manage() ) { wp_send_json_error( array( 'message' => 'Access denied.' ), 403 ); }
        $raw = isset( $_POST['raw'] ) ? wp_unslash( $_POST['raw'] ) : '';
        $parsed = self::parse_dataset( $raw );
        if ( is_wp_error( $parsed ) ) { wp_send_json_error( array( 'message' => $parsed->get_error_message() ), 400 ); }

        $fields = self::bridge_fields();
        $alias_index = self::alias_index( $fields );
        $suggested = array();
        foreach ( $parsed['headers'] as $index => $header ) {
            $norm = self::normalize_header( $header );
            $suggested[ $index ] = isset( $alias_index[ $norm ] ) ? $alias_index[ $norm ] : '';
        }
        $options = array();
        foreach ( $fields as $key => $field ) { $options[] = array( 'value' => $key, 'label' => $field['label'] . ' [' . $key . ']' ); }

        wp_send_json_success( array(
            'headers'    => $parsed['headers'],
            'sample'     => array_slice( $parsed['rows'], 0, 3 ),
            'row_count'  => count( $parsed['rows'] ),
            'delimiter'  => self::delimiter_label( $parsed['delimiter'] ),
            'suggested'  => $suggested,
            'options'    => $options,
        ) );
    }

    public static function ajax_preview() {
        check_ajax_referer( self::NONCE_ACTION, 'nonce' );
        if ( ! self::can_manage() ) { wp_send_json_error( array( 'message' => 'Access denied.' ), 403 ); }
        $raw = isset( $_POST['raw'] ) ? wp_unslash( $_POST['raw'] ) : '';
        $mapping_json = isset( $_POST['mapping'] ) ? wp_unslash( $_POST['mapping'] ) : '';
        $mapping = json_decode( $mapping_json, true );
        if ( ! is_array( $mapping ) ) { wp_send_json_error( array( 'message' => 'Invalid column mapping.' ), 400 ); }

        $parsed = self::parse_dataset( $raw );
        if ( is_wp_error( $parsed ) ) { wp_send_json_error( array( 'message' => $parsed->get_error_message() ), 400 ); }
        $compiled = self::compile_records( $parsed, $mapping );
        if ( is_wp_error( $compiled ) ) { wp_send_json_error( array( 'message' => $compiled->get_error_message() ), 400 ); }

        $plans = array();
        $summary = array( 'create'=>0, 'update'=>0, 'unchanged'=>0, 'conflict'=>0, 'invalid'=>0 );
        foreach ( $compiled as $row_number => $record ) {
            $plan = self::plan_record( $record, $row_number );
            $plans[] = $plan;
            $key = strtolower( $plan['operation'] );
            if ( isset( $summary[ $key ] ) ) { $summary[ $key ]++; }
        }

        $token = wp_generate_password( 24, false, false );
        set_transient( self::preview_key( $token ), array(
            'user_id'    => get_current_user_id(),
            'created_at' => time(),
            'plans'      => $plans,
        ), 30 * MINUTE_IN_SECONDS );

        $display = array();
        foreach ( $plans as $plan ) {
            $display[] = array(
                'row'       => $plan['row'],
                'operation' => $plan['operation'],
                'hotel_id'  => $plan['hotel_id'],
                'name'      => $plan['name'],
                'changes'   => array_slice( $plan['change_labels'], 0, 8 ),
                'message'   => $plan['message'],
            );
        }

        wp_send_json_success( array( 'token'=>$token, 'summary'=>$summary, 'plans'=>$display ) );
    }

    public static function ajax_apply() {
        check_ajax_referer( self::NONCE_ACTION, 'nonce' );
        if ( ! self::can_manage() ) { wp_send_json_error( array( 'message' => 'Access denied.' ), 403 ); }
        $token = isset( $_POST['token'] ) ? sanitize_key( wp_unslash( $_POST['token'] ) ) : '';
        $payload = $token ? get_transient( self::preview_key( $token ) ) : false;
        if ( ! is_array( $payload ) || (int) $payload['user_id'] !== get_current_user_id() ) {
            wp_send_json_error( array( 'message' => 'Preview expired. Run Dry Run again.' ), 400 );
        }

        $created = array();
        $updated = array();
        $skipped = 0;
        $errors = array();

        foreach ( $payload['plans'] as $plan ) {
            if ( ! in_array( $plan['operation'], array( 'CREATE', 'UPDATE' ), true ) ) { $skipped++; continue; }

            if ( 'UPDATE' === $plan['operation'] ) {
                $post_id = absint( $plan['post_id'] );
                if ( ! $post_id || 'sthi_hotel' !== get_post_type( $post_id ) ) { $skipped++; $errors[] = 'Row ' . $plan['row'] . ': hotel disappeared after preview.'; continue; }
                $current = self::snapshot( $post_id );
                if ( self::snapshot_hash( $current ) !== $plan['before_hash'] ) {
                    $skipped++; $errors[] = 'Row ' . $plan['row'] . ': changed after preview; skipped.'; continue;
                }
                $before = $current;
                $result = self::apply_values( $post_id, $plan['values'], false );
                if ( is_wp_error( $result ) ) {
                    self::restore_snapshot( $post_id, $before );
                    $skipped++; $errors[] = 'Row ' . $plan['row'] . ': ' . $result->get_error_message(); continue;
                }
                $after = self::snapshot( $post_id );
                $updated[] = array( 'post_id'=>$post_id, 'before'=>$before, 'after'=>$after );
                continue;
            }

            // CREATE: re-check likely duplicate immediately before insert.
            $duplicate = self::find_possible_duplicate( $plan['values'] );
            if ( $duplicate ) {
                $skipped++; $errors[] = 'Row ' . $plan['row'] . ': possible duplicate appeared after preview; skipped.'; continue;
            }
            $title = self::title_from_values( $plan['values'] );
            $status = self::value_string( $plan['values'], 'wp_status', 'draft' );
            if ( ! in_array( $status, array( 'draft','pending','publish','private' ), true ) ) { $status = 'draft'; }
            $post_id = wp_insert_post( array( 'post_type'=>'sthi_hotel', 'post_status'=>$status, 'post_title'=>$title ), true );
            if ( is_wp_error( $post_id ) ) { $skipped++; $errors[] = 'Row ' . $plan['row'] . ': ' . $post_id->get_error_message(); continue; }
            update_post_meta( $post_id, '_sthi_verification_status', 'new' );
            update_post_meta( $post_id, '_sthi_workflow_status', 'new' );
            $result = self::apply_values( $post_id, $plan['values'], true );
            if ( is_wp_error( $result ) ) {
                wp_trash_post( $post_id );
                $skipped++; $errors[] = 'Row ' . $plan['row'] . ': ' . $result->get_error_message(); continue;
            }
            $created[] = array( 'post_id'=>$post_id, 'after'=>self::snapshot( $post_id ) );
        }

        $summary = array( 'created'=>count($created), 'updated'=>count($updated), 'skipped'=>$skipped, 'errors'=>count($errors) );
        $batch_id = self::store_batch( $created, $updated, $summary );
        delete_transient( self::preview_key( $token ) );

        wp_send_json_success( array(
            'summary'  => $summary,
            'batch_id' => $batch_id,
            'errors'   => array_slice( $errors, 0, 20 ),
        ) );
    }

    public static function ajax_export() {
        check_ajax_referer( self::NONCE_ACTION, 'nonce' );
        if ( ! self::can_manage() ) { wp_send_json_error( array( 'message' => 'Access denied.' ), 403 ); }
        $mode = isset( $_POST['mode'] ) ? sanitize_key( wp_unslash( $_POST['mode'] ) ) : 'data';
        $headers = self::canonical_headers();
        $rows = array();
        if ( 'template' !== $mode ) {
            $ids = isset( $_POST['ids'] ) ? array_values( array_filter( array_map( 'absint', (array) $_POST['ids'] ) ) ) : array();
            $args = array(
                'post_type'      => 'sthi_hotel',
                'post_status'    => array( 'publish','draft','pending','private' ),
                'posts_per_page' => -1,
                'orderby'        => 'title',
                'order'          => 'ASC',
                'fields'         => 'ids',
            );
            if ( $ids ) { $args['post__in'] = $ids; $args['orderby'] = 'post__in'; }
            foreach ( get_posts( $args ) as $post_id ) { $rows[] = self::export_row( $post_id, $headers ); }
        }
        $csv = self::make_csv( $headers, $rows );
        wp_send_json_success( array( 'csv'=>$csv, 'filename'=>'server-turizm-hotels-' . gmdate('Y-m-d-His') . '.csv' ) );
    }

    public static function ajax_rollback() {
        check_ajax_referer( self::NONCE_ACTION, 'nonce' );
        if ( ! self::can_manage() ) { wp_send_json_error( array( 'message' => 'Access denied.' ), 403 ); }
        $batch_id = isset( $_POST['batch_id'] ) ? absint( $_POST['batch_id'] ) : 0;
        if ( ! $batch_id || self::BATCH_POST_TYPE !== get_post_type( $batch_id ) ) { wp_send_json_error( array( 'message'=>'Invalid import batch.' ), 400 ); }
        if ( get_post_meta( $batch_id, '_sthi_batch_rolled_back', true ) ) { wp_send_json_error( array( 'message'=>'This import was already rolled back.' ), 400 ); }
        $batch = get_post( $batch_id );
        $data = json_decode( (string) $batch->post_content, true );
        if ( ! is_array( $data ) ) { wp_send_json_error( array( 'message'=>'Import rollback data is missing.' ), 400 ); }

        $restored = 0; $trashed = 0; $conflicts = array();
        foreach ( (array) $data['updated'] as $entry ) {
            $post_id = absint( $entry['post_id'] );
            if ( ! $post_id || 'sthi_hotel' !== get_post_type( $post_id ) ) { continue; }
            $current = self::snapshot( $post_id );
            if ( self::snapshot_hash( $current ) !== self::snapshot_hash( $entry['after'] ) ) {
                $conflicts[] = get_post_meta( $post_id, '_sthi_hotel_id', true ) . ' changed after import';
                continue;
            }
            self::restore_snapshot( $post_id, $entry['before'] );
            $restored++;
        }
        foreach ( (array) $data['created'] as $entry ) {
            $post_id = absint( $entry['post_id'] );
            if ( ! $post_id || 'sthi_hotel' !== get_post_type( $post_id ) ) { continue; }
            $current = self::snapshot( $post_id );
            if ( self::snapshot_hash( $current ) !== self::snapshot_hash( $entry['after'] ) ) {
                $conflicts[] = get_post_meta( $post_id, '_sthi_hotel_id', true ) . ' changed after import';
                continue;
            }
            if ( wp_trash_post( $post_id ) ) { $trashed++; }
        }
        update_post_meta( $batch_id, '_sthi_batch_rolled_back', 1 );
        update_post_meta( $batch_id, '_sthi_batch_rollback_conflicts', $conflicts );
        wp_send_json_success( array( 'restored'=>$restored, 'trashed'=>$trashed, 'conflicts'=>$conflicts ) );
    }

    private static function parse_dataset( $raw ) {
        $raw = (string) $raw;
        $raw = preg_replace( '/^\xEF\xBB\xBF/', '', $raw );
        $raw = str_replace( array("\r\n", "\r"), "\n", $raw );
        if ( '' === trim( $raw ) ) { return new WP_Error( 'empty_dataset', 'Paste hotel data or load a CSV/TSV file first.' ); }
        $delimiter = self::detect_delimiter( $raw );
        $fh = fopen( 'php://temp', 'r+' );
        fwrite( $fh, $raw ); rewind( $fh );
        $rows = array();
        while ( false !== ( $row = fgetcsv( $fh, 0, $delimiter ) ) ) {
            $nonempty = false;
            foreach ( $row as $cell ) { if ( '' !== trim( (string) $cell ) ) { $nonempty = true; break; } }
            if ( $nonempty ) { $rows[] = $row; }
            if ( count( $rows ) > self::MAX_ROWS + 1 ) { fclose( $fh ); return new WP_Error( 'too_many_rows', 'Import up to ' . self::MAX_ROWS . ' hotel rows per batch.' ); }
        }
        fclose( $fh );
        if ( count( $rows ) < 2 ) { return new WP_Error( 'not_enough_rows', 'The first row must contain headers and at least one hotel row is required.' ); }
        $headers = array_map( function( $v ){ return trim( preg_replace( '/^\xEF\xBB\xBF/', '', (string) $v ) ); }, array_shift( $rows ) );
        if ( ! array_filter( $headers ) ) { return new WP_Error( 'missing_headers', 'Header row is empty.' ); }
        $width = count( $headers );
        $clean_rows = array();
        foreach ( $rows as $row ) {
            $row = array_pad( array_slice( $row, 0, $width ), $width, '' );
            $clean_rows[] = array_map( 'strval', $row );
        }
        return array( 'headers'=>$headers, 'rows'=>$clean_rows, 'delimiter'=>$delimiter );
    }

    private static function detect_delimiter( $raw ) {
        $first = '';
        foreach ( explode( "\n", $raw ) as $line ) { if ( '' !== trim( $line ) ) { $first = $line; break; } }
        $scores = array( "\t"=>substr_count($first,"\t"), ','=>substr_count($first,','), ';'=>substr_count($first,';') );
        arsort( $scores );
        $delimiter = key( $scores );
        return current( $scores ) > 0 ? $delimiter : "\t";
    }

    private static function delimiter_label( $delimiter ) {
        if ( "\t" === $delimiter ) { return 'TAB / TSV'; }
        if ( ';' === $delimiter ) { return 'Semicolon CSV'; }
        return 'Comma CSV';
    }

    private static function normalize_header( $header ) {
        $header = remove_accents( strtolower( trim( (string) $header ) ) );
        $header = preg_replace( '/[^a-z0-9]+/', '_', $header );
        return trim( $header, '_' );
    }

    private static function alias_index( $fields ) {
        $index = array();
        foreach ( $fields as $canonical => $field ) {
            foreach ( array_merge( array( $canonical ), (array) $field['aliases'] ) as $alias ) {
                $index[ self::normalize_header( $alias ) ] = $canonical;
            }
        }
        return $index;
    }

    private static function compile_records( $parsed, $mapping ) {
        $fields = self::bridge_fields();
        $used = array();
        foreach ( $mapping as $index => $canonical ) {
            $canonical = sanitize_key( $canonical );
            if ( '' === $canonical ) { continue; }
            if ( ! isset( $fields[ $canonical ] ) ) { return new WP_Error( 'bad_mapping', 'Unknown mapped field: ' . $canonical ); }
            if ( isset( $used[ $canonical ] ) ) { return new WP_Error( 'duplicate_mapping', 'Two columns are mapped to ' . $canonical . '.' ); }
            $used[ $canonical ] = true;
        }
        if ( ! isset( $used['hotel_id'] ) && ! isset( $used['hotel_name'] ) && ! isset( $used['official_name'] ) ) {
            return new WP_Error( 'identity_mapping_required', 'Map at least hotel_id, hotel_name or official_name.' );
        }

        $records = array();
        foreach ( $parsed['rows'] as $rindex => $row ) {
            $record = array();
            foreach ( $mapping as $index => $canonical ) {
                $canonical = sanitize_key( $canonical );
                if ( '' === $canonical || ! isset( $fields[ $canonical ] ) ) { continue; }
                $record[ $canonical ] = isset( $row[ (int) $index ] ) ? (string) $row[ (int) $index ] : '';
            }
            $records[ $rindex + 2 ] = $record; // +2 = source row number including header.
        }
        return $records;
    }

    private static function plan_record( $record, $row_number ) {
        $fields = self::bridge_fields();
        $values = array();
        $errors = array();
        foreach ( $record as $canonical => $raw ) {
            if ( ! isset( $fields[ $canonical ] ) ) { continue; }
            $normalized = self::normalize_import_value( $canonical, $raw, $fields[ $canonical ] );
            if ( is_wp_error( $normalized ) ) { $errors[] = $fields[$canonical]['label'] . ': ' . $normalized->get_error_message(); continue; }
            if ( null === $normalized ) { continue; } // blank = no-op
            $values[ $canonical ] = $normalized;
        }

        // Known city aliases imply a canonical country when the import leaves Country blank.
        if ( class_exists( 'STHI_Geography' ) && isset( $values['city'] ) && ! isset( $values['country'] ) ) {
            $inferred_country = STHI_Geography::country_for_city( self::value_string( $values, 'city', '' ) );
            if ( $inferred_country ) { $values['country'] = array( 'action' => 'set', 'value' => $inferred_country ); }
        }

        $hotel_id = self::value_string( $values, 'hotel_id', '' );
        $name = self::title_from_values( $values );
        if ( $errors ) {
            return self::plan_base( $row_number, 'INVALID', $hotel_id, $name, $values, implode( ' | ', $errors ) );
        }

        if ( $hotel_id ) {
            $post_id = self::find_by_hotel_id( $hotel_id );
            if ( ! $post_id ) { return self::plan_base( $row_number, 'CONFLICT', $hotel_id, $name, $values, 'Hotel ID does not exist. Remove hotel_id to create a new entity, or use the correct existing ID.' ); }
            if ( 'trash' === get_post_status( $post_id ) ) { return self::plan_base( $row_number, 'CONFLICT', $hotel_id, $name, $values, 'This Hotel ID is currently in WordPress Trash. Restore it before importing updates.' ); }
            $before = self::snapshot( $post_id );
            $changes = self::diff_values( $post_id, $values );
            $plan = self::plan_base( $row_number, $changes ? 'UPDATE' : 'UNCHANGED', $hotel_id, get_the_title($post_id), $values, $changes ? count($changes) . ' field(s) will change.' : 'No changes.' );
            $plan['post_id'] = $post_id;
            $plan['before_hash'] = self::snapshot_hash( $before );
            $plan['change_labels'] = $changes;
            return $plan;
        }

        if ( '' === trim( $name ) || 'Untitled Hotel' === $name ) {
            return self::plan_base( $row_number, 'INVALID', '', $name, $values, 'A new hotel needs hotel_name or official_name.' );
        }
        $duplicate = self::find_possible_duplicate( $values );
        if ( $duplicate ) {
            return self::plan_base( $row_number, 'CONFLICT', get_post_meta($duplicate,'_sthi_hotel_id',true), $name, $values, 'Possible existing hotel found. Export/use its Hotel ID instead of creating a duplicate.' );
        }
        $plan = self::plan_base( $row_number, 'CREATE', '', $name, $values, 'A new stable Hotel ID will be assigned automatically.' );
        $plan['change_labels'] = array( 'New hotel entity' );
        return $plan;
    }

    private static function plan_base( $row, $operation, $hotel_id, $name, $values, $message ) {
        return array(
            'row' => (int) $row, 'operation'=>$operation, 'hotel_id'=>$hotel_id, 'name'=>$name,
            'values'=>$values, 'message'=>$message, 'post_id'=>0, 'before_hash'=>'', 'change_labels'=>array(),
        );
    }

    private static function normalize_import_value( $canonical, $raw, $field ) {
        $raw = is_string( $raw ) ? trim( $raw ) : $raw;
        if ( '' === $raw ) { return null; }
        if ( self::CLEAR_TOKEN === strtoupper( (string) $raw ) ) { return array( 'action'=>'clear', 'value'=>'' ); }

        $type = $field['type'];
        $value = $raw;
        if ( 'city' === $canonical && class_exists( 'STHI_Geography' ) ) {
            $value = STHI_Geography::canonical_city( $raw );
        } elseif ( 'country' === $canonical && class_exists( 'STHI_Geography' ) ) {
            $value = STHI_Geography::canonical_country( $raw );
        } elseif ( 'hotel_id' === $canonical ) {
            $value = strtoupper( sanitize_text_field( $raw ) );
            if ( ! preg_match( '/^STH-\d{6}$/', $value ) ) { return new WP_Error( 'bad_hotel_id', 'Expected format STH-000001.' ); }
        } elseif ( 'status' === $type ) {
            $value = sanitize_key( $raw );
            if ( ! in_array( $value, array('draft','pending','publish','private'), true ) ) { return new WP_Error( 'bad_status', 'Use draft, pending, publish or private.' ); }
        } elseif ( 'terms' === $type ) {
            $parts = preg_split( '/[,;\n]+/', sanitize_textarea_field( $raw ) );
            $parts = array_values( array_unique( array_filter( array_map( 'trim', $parts ) ) ) );
            $value = $parts;
        } elseif ( 'tri' === $type ) {
            $norm = self::normalize_header( $raw );
            $yes = array('yes','evet','var','1','true'); $no=array('no','hayir','yok','0','false'); $unknown=array('unknown','bilinmiyor','bilinmeyen','?');
            if ( in_array($norm,$yes,true) ) { $value='yes'; }
            elseif ( in_array($norm,$no,true) ) { $value='no'; }
            elseif ( in_array($norm,$unknown,true) ) { $value='unknown'; }
            else { return new WP_Error('bad_tri','Use yes/no/unknown (evet/hayır/var/yok also accepted).'); }
        } elseif ( 'verification' === $type ) {
            $value = sanitize_key( $raw );
            if ( ! in_array($value,array('new','needs_verification','verified','needs_reverify'),true) ) { return new WP_Error('bad_verification','Invalid verification status.'); }
        } elseif ( 'workflow' === $type ) {
            $value = sanitize_key( $raw );
            if ( ! in_array($value,array('new','needs_verification','verified','ready_to_publish','published','needs_reverify','archived'),true) ) { return new WP_Error('bad_workflow','Invalid workflow status.'); }
        } elseif ( 'date' === $type ) {
            $value = self::normalize_date( $raw );
            if ( ! $value ) { return new WP_Error('bad_date','Use YYYY-MM-DD or DD/MM/YYYY.'); }
        } elseif ( 'email' === $type ) {
            $value = sanitize_email( $raw );
            if ( ! $value || ! is_email( $value ) ) { return new WP_Error('bad_email','Invalid email address.'); }
        } elseif ( 'url' === $type ) {
            $value = esc_url_raw( $raw );
            if ( ! $value || ! filter_var( $value, FILTER_VALIDATE_URL ) ) { return new WP_Error('bad_url','Invalid URL.'); }
            if ( 'media_folder_url' === $canonical ) {
                $valid = STHI_Media::validate_folder_url( $value );
                if ( is_wp_error( $valid ) ) { return $valid; }
            }
        } elseif ( 'json' === $type ) {
            $decoded = json_decode( html_entity_decode( (string) $raw, ENT_QUOTES | ENT_HTML5, 'UTF-8' ), true );
            if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $decoded ) ) {
                return new WP_Error( 'bad_json', 'Invalid JSON.' );
            }
            $value = wp_json_encode( $decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
        } elseif ( 'structured_json' === $type ) {
            $decoded = json_decode( html_entity_decode( (string) $raw, ENT_QUOTES | ENT_HTML5, 'UTF-8' ), true );
            if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $decoded ) ) {
                return new WP_Error( 'bad_structured_json', 'Invalid JSON. Use the ChatGPT-generated import value or leave this cell blank.' );
            }
            $normalized = STHI_Structured_Details::normalize_import_group( $field['target'], $decoded );
            if ( is_wp_error( $normalized ) ) { return $normalized; }
            $value = $normalized;
        } elseif ( 'content_source_mode' === $type ) {
            $value = sanitize_key( $raw );
            if ( ! isset( STHI_Content::source_modes()[ $value ] ) ) { return new WP_Error('bad_content_source_mode','Use manual, ai-assisted, imported or hybrid.'); }
        } elseif ( 'editorial_html' === $type ) {
            $value = STHI_Content::sanitize_editorial_html( $raw );
        } elseif ( 'link_intents_json' === $type ) {
            $decoded = json_decode( html_entity_decode( (string) $raw, ENT_QUOTES | ENT_HTML5, 'UTF-8' ), true );
            if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $decoded ) ) {
                return new WP_Error( 'bad_link_intents_json', 'Invalid link-intents JSON array.' );
            }
            $value = STHI_Content::normalize_link_intents( $decoded );
            if ( is_wp_error( $value ) ) { return $value; }
        } elseif ( 'number' === $type ) {
            if ( ! is_numeric( $raw ) ) { return new WP_Error('bad_number','Expected a number.'); }
            $value = (string) (int) $raw;
            if ( isset($field['min']) && null !== $field['min'] && (int)$value < (int)$field['min'] ) { return new WP_Error('number_low','Minimum is '.$field['min'].'.'); }
            if ( isset($field['max']) && null !== $field['max'] && (int)$value > (int)$field['max'] ) { return new WP_Error('number_high','Maximum is '.$field['max'].'.'); }
        } elseif ( 'textarea' === $type ) {
            $value = sanitize_textarea_field( $raw );
        } else {
            $value = sanitize_text_field( $raw );
        }
        return array( 'action'=>'set', 'value'=>$value );
    }

    private static function normalize_date( $raw ) {
        $raw = trim( (string) $raw );
        foreach ( array('Y-m-d','d/m/Y','d.m.Y','d-m-Y') as $format ) {
            $dt = DateTime::createFromFormat( '!' . $format, $raw );
            if ( $dt && $dt->format($format) === $raw ) { return $dt->format('Y-m-d'); }
        }
        return '';
    }

    private static function value_string( $values, $key, $default='' ) {
        if ( ! isset($values[$key]) || ! is_array($values[$key]) || 'set' !== $values[$key]['action'] ) { return $default; }
        return is_array($values[$key]['value']) ? implode(', ', $values[$key]['value']) : (string)$values[$key]['value'];
    }

    private static function title_from_values( $values ) {
        $name = self::value_string( $values, 'hotel_name', '' );
        if ( ! $name ) { $name = self::value_string( $values, 'official_name', '' ); }
        return $name ? $name : 'Untitled Hotel';
    }

    private static function find_by_hotel_id( $hotel_id ) {
        $ids = get_posts( array(
            'post_type'=>'sthi_hotel', 'post_status'=>array('publish','draft','pending','private','trash'), 'posts_per_page'=>1,
            'fields'=>'ids', 'meta_key'=>'_sthi_hotel_id', 'meta_value'=>$hotel_id,
        ) );
        return $ids ? (int)$ids[0] : 0;
    }

    private static function find_possible_duplicate( $values ) {
        $official = self::value_string( $values, 'official_name', '' );
        $name = self::title_from_values( $values );
        $city = self::value_string( $values, 'city', '' );
        $args = array(
            'post_type'=>'sthi_hotel', 'post_status'=>array('publish','draft','pending','private'), 'posts_per_page'=>5, 'fields'=>'ids',
        );
        if ( $official ) {
            $args['meta_query'] = array( array('key'=>'_sthi_official_name','value'=>$official,'compare'=>'=') );
        } else {
            $args['title'] = $name;
        }
        $ids = get_posts( $args );
        foreach ( $ids as $id ) {
            $existing_city = (string) get_post_meta( $id, '_sthi_city', true );
            if ( class_exists( 'STHI_Geography' ) ) { $existing_city = STHI_Geography::canonical_city( $existing_city ); }
            if ( ! $city || 0 === strcasecmp( $existing_city, $city ) ) { return (int)$id; }
        }
        return 0;
    }

    private static function diff_values( $post_id, $values ) {
        $fields = self::bridge_fields();
        $changes = array();
        foreach ( $values as $canonical => $spec ) {
            if ( 'hotel_id' === $canonical ) { continue; }
            $field = $fields[$canonical];
            $before = self::current_value( $post_id, $canonical, $field );
            $after = ( 'clear' === $spec['action'] ) ? ( 'terms' === $field['type'] ? array() : '' ) : $spec['value'];
            if ( self::values_equal( $before, $after ) ) { continue; }
            $changes[] = $field['label'] . ': ' . self::display_value($before) . ' → ' . self::display_value($after);
        }
        return $changes;
    }

    private static function current_value( $post_id, $canonical, $field ) {
        if ( 'system' === $field['kind'] ) {
            $post = get_post($post_id);
            return 'post_title' === $field['target'] ? $post->post_title : $post->post_status;
        }
        if ( 'taxonomy' === $field['kind'] ) {
            $names = wp_get_object_terms($post_id,$field['target'],array('fields'=>'names'));
            if ( is_wp_error($names) ) { return array(); }
            natcasesort($names); return array_values($names);
        }
        if ( 'hotel_id' === $field['kind'] ) { return get_post_meta($post_id,'_sthi_hotel_id',true); }
        if ( 'structured' === $field['kind'] ) { return STHI_Structured_Details::get_import_group( $post_id, $field['target'] ); }
        return get_post_meta($post_id,$field['target'],true);
    }

    private static function values_equal( $a, $b ) {
        if ( is_array($a) || is_array($b) ) {
            $a=(array)$a; $b=(array)$b;
            $a_nested = self::array_is_assoc_compat($a) || (bool) array_filter($a,'is_array');
            $b_nested = self::array_is_assoc_compat($b) || (bool) array_filter($b,'is_array');
            if ( $a_nested || $b_nested ) { return wp_json_encode($a) === wp_json_encode($b); }
            natcasesort($a); natcasesort($b); return array_values($a) === array_values($b);
        }
        return (string)$a === (string)$b;
    }

    private static function array_is_assoc_compat( $array ) {
        if ( ! is_array($array) || array() === $array ) { return false; }
        return array_keys($array) !== range(0,count($array)-1);
    }

    private static function display_value( $value ) {
        if ( is_array($value) ) {
            $is_assoc = self::array_is_assoc_compat($value);
            if ( $is_assoc ) {
                $known = 0; foreach($value as $v){ if('unknown' !== (string)$v) $known++; }
                return $known . ' known item(s)';
            }
            return count($value) . ' item(s)';
        }
        $value=(string)$value; if (''===$value) return '∅';
        return mb_strlen($value)>60 ? mb_substr($value,0,57).'…' : $value;
    }

    private static function apply_values( $post_id, $values, $is_create ) {
        $fields = self::bridge_fields();
        $post_update = array('ID'=>$post_id);
        $has_post_update=false;
        foreach ( $values as $canonical => $spec ) {
            if ( ! isset($fields[$canonical]) || 'hotel_id' === $canonical || 'taxonomy' === $fields[$canonical]['kind'] || 'meta' === $fields[$canonical]['kind'] ) { continue; }
            if ( 'system' !== $fields[$canonical]['kind'] ) { continue; }
            if ( 'post_title' === $fields[$canonical]['target'] ) {
                if ('set'===$spec['action'] && trim((string)$spec['value'])!=='') { $post_update['post_title']=$spec['value']; $has_post_update=true; }
            } elseif ( 'post_status' === $fields[$canonical]['target'] && 'set'===$spec['action'] ) {
                $post_update['post_status']=$spec['value']; $has_post_update=true;
            }
        }
        if ( $has_post_update ) {
            $r=wp_update_post($post_update,true); if(is_wp_error($r)) return $r;
        }

        foreach ( $values as $canonical=>$spec ) {
            if ( ! isset($fields[$canonical]) || in_array($fields[$canonical]['kind'],array('hotel_id','system'),true) ) { continue; }
            $field=$fields[$canonical];
            if ( 'taxonomy' === $field['kind'] ) {
                $names = 'clear'===$spec['action'] ? array() : (array)$spec['value'];
                $r=self::set_terms_by_name($post_id,$field['target'],$names); if(is_wp_error($r)) return $r;
                continue;
            }
            if ( 'structured' === $field['kind'] ) {
                $clear = 'clear' === $spec['action'];
                $r = STHI_Structured_Details::apply_import_group( $post_id, $field['target'], $clear ? array() : $spec['value'], $clear );
                if ( is_wp_error( $r ) ) { return $r; }
                continue;
            }
            if ( 'meta' === $field['kind'] ) {
                if ( 'media_folder_url' === $canonical ) { continue; }
                if ( 'clear' === $spec['action'] ) { delete_post_meta($post_id,$field['target']); }
                else { update_post_meta($post_id,$field['target'],$spec['value']); }
            }
        }

        // Keep title and official name coherent for newly created rows when only one was supplied.
        if ( $is_create ) {
            $official = get_post_meta($post_id,'_sthi_official_name',true);
            if ( ! $official ) { update_post_meta($post_id,'_sthi_official_name',get_the_title($post_id)); }
        }

        // Canonicalize city/country, infer known country from city, and attach canonical Destination terms.
        if ( class_exists( 'STHI_Geography' ) ) {
            STHI_Geography::sync_post_geography( $post_id );
        }

        if ( isset($values['media_folder_url']) ) {
            $url = 'clear'===$values['media_folder_url']['action'] ? '' : $values['media_folder_url']['value'];
            $sync = STHI_Media::sync_folder_to_gallery($post_id,$url);
            if ( is_wp_error($sync) ) { return $sync; }
        }

        // H10E: Data Bridge applies readiness metadata/media after save_post.
        // Freeze the public slug only now, when the complete Entity state exists.
        if ( class_exists( 'STHI_Public_Routes' ) ) {
            STHI_Public_Routes::ensure_public_slug( $post_id );
        }
        return true;
    }

    private static function set_terms_by_name( $post_id, $taxonomy, $names ) {
        $term_ids=array();
        foreach(array_unique(array_filter(array_map('trim',(array)$names))) as $name){
            if ( 'sthi_destination' === $taxonomy && class_exists( 'STHI_Geography' ) ) {
                $name = STHI_Geography::canonical_city( $name );
                $name = STHI_Geography::canonical_country( $name );
            }
            $term=term_exists($name,$taxonomy); if(!$term){$term=wp_insert_term($name,$taxonomy);} if(is_wp_error($term)) return $term;
            $term_ids[]=(int)(is_array($term)?$term['term_id']:$term);
        }
        return wp_set_object_terms($post_id,$term_ids,$taxonomy,false);
    }

    private static function snapshot( $post_id ) {
        $post=get_post($post_id);
        $meta_keys=array_keys(STHI_Meta::fields());
        $meta_keys=array_merge($meta_keys,STHI_Content::meta_keys());
        $meta_keys[]=STHI_Media::META_KEY; $meta_keys[]='_sthi_media_synced_folder_url';
        $meta_keys[]=STHI_Structured_Details::META_AMENITIES;
        $meta_keys[]=STHI_Structured_Details::META_ROOMS;
        $meta_keys[]=STHI_Structured_Details::META_REFS;
        $meta_keys[]=STHI_Structured_Details::META_CONTACTS;
        $meta_keys[]=STHI_Structured_Details::META_SCENES;
        $meta_keys[]=STHI_Structured_Details::META_SCHEMA;
        $meta_keys[]=STHI_Structured_Details::META_HEALTH;
        $meta=array();
        foreach(array_unique($meta_keys) as $key){
            $exists=metadata_exists('post',$post_id,$key);
            $meta[$key]=array('exists'=>$exists,'value'=>$exists?get_post_meta($post_id,$key,true):null);
        }
        $tax=array();
        foreach(array('sthi_destination','sthi_collection') as $taxonomy){
            $names=wp_get_object_terms($post_id,$taxonomy,array('fields'=>'names')); if(is_wp_error($names)) $names=array(); natcasesort($names); $tax[$taxonomy]=array_values($names);
        }
        return array('post_title'=>$post?$post->post_title:'','post_status'=>$post?$post->post_status:'draft','meta'=>$meta,'taxonomies'=>$tax);
    }

    private static function snapshot_hash( $snapshot ) { return md5(wp_json_encode($snapshot)); }

    private static function restore_snapshot( $post_id, $snapshot ) {
        wp_update_post(array('ID'=>$post_id,'post_title'=>$snapshot['post_title'],'post_status'=>$snapshot['post_status']));
        foreach((array)$snapshot['meta'] as $key=>$spec){
            if(!empty($spec['exists'])) update_post_meta($post_id,$key,$spec['value']); else delete_post_meta($post_id,$key);
        }
        foreach((array)$snapshot['taxonomies'] as $tax=>$names){ self::set_terms_by_name($post_id,$tax,$names); }
    }

    private static function store_batch( $created, $updated, $summary ) {
        if(!$created && !$updated) return 0;
        $data=array('schema_version'=>'hotel-bridge-0.8.0','created'=>$created,'updated'=>$updated);
        $batch_id=wp_insert_post(array(
            'post_type'=>self::BATCH_POST_TYPE,'post_status'=>'private','post_title'=>'Hotel Import '.current_time('Y-m-d H:i:s'),
            'post_content'=>wp_slash(wp_json_encode($data)),
        ),true);
        if(is_wp_error($batch_id)) return 0;
        update_post_meta($batch_id,'_sthi_batch_summary',$summary);
        update_post_meta($batch_id,'_sthi_batch_user_id',get_current_user_id());
        return (int)$batch_id;
    }

    private static function preview_key( $token ) { return 'sthi_bridge_' . get_current_user_id() . '_' . sanitize_key($token); }

    private static function export_row( $post_id, $headers ) {
        $fields=self::bridge_fields(); $row=array();
        foreach($headers as $canonical){
            $field=$fields[$canonical]; $value=self::current_value($post_id,$canonical,$field);
            if ( 'structured' === $field['kind'] || 'link_intents_json' === $field['type'] ) {
                $value = wp_json_encode( $value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
            } elseif(is_array($value)) { $value=implode(', ',$value); }
            $row[]=(string)$value;
        }
        return $row;
    }

    private static function make_csv( $headers, $rows ) {
        $fh=fopen('php://temp','r+');
        fwrite($fh,"\xEF\xBB\xBF");
        fputcsv($fh,$headers);
        foreach($rows as $row) fputcsv($fh,$row);
        rewind($fh); $csv=stream_get_contents($fh); fclose($fh); return $csv;
    }
}
STHI_Data_Bridge::init();
