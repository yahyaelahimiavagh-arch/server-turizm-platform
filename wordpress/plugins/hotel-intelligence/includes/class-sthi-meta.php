<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class STHI_Meta {
    public static function init() {
        add_action( 'add_meta_boxes_sthi_hotel', array( __CLASS__, 'add_boxes' ) );
        add_action( 'save_post_sthi_hotel', array( __CLASS__, 'save' ), 20, 2 );
    }

    public static function fields() {
        return array(
            // Identity.
            '_sthi_official_name'        => array( 'label' => 'Official Name', 'type' => 'text', 'group' => 'Identity' ),
            '_sthi_local_name'           => array( 'label' => 'Local Name', 'type' => 'text', 'group' => 'Identity' ),
            '_sthi_alternative_names'    => array( 'label' => 'Alternative Names', 'type' => 'textarea', 'group' => 'Identity' ),
            '_sthi_display_name_tr'       => array( 'label' => 'Display Name (TR)', 'type' => 'text', 'group' => 'Identity' ),
            '_sthi_display_name_en'       => array( 'label' => 'Display Name (EN)', 'type' => 'text', 'group' => 'Identity' ),
            '_sthi_display_name_ar'       => array( 'label' => 'Display Name (AR)', 'type' => 'text', 'group' => 'Identity' ),
            '_sthi_brand'                 => array( 'label' => 'Brand', 'type' => 'text', 'group' => 'Identity' ),
            '_sthi_hotel_chain'          => array( 'label' => 'Hotel Chain', 'type' => 'text', 'group' => 'Identity' ),
            '_sthi_hotel_type'           => array( 'label' => 'Hotel Type', 'type' => 'text', 'group' => 'Identity' ),
            '_sthi_star_rating'          => array( 'label' => 'Official Star Rating', 'type' => 'number', 'group' => 'Identity', 'min' => 1, 'max' => 5 ),
            '_sthi_license_number'       => array( 'label' => 'License / Registration Number', 'type' => 'text', 'group' => 'Identity' ),
            '_sthi_opening_year'         => array( 'label' => 'Opening Year', 'type' => 'number', 'group' => 'Identity' ),
            '_sthi_renovation_year'      => array( 'label' => 'Renovation Year', 'type' => 'number', 'group' => 'Identity' ),

            // Location.
            '_sthi_country'              => array( 'label' => 'Country', 'type' => 'text', 'group' => 'Location' ),
            '_sthi_region'               => array( 'label' => 'Region', 'type' => 'text', 'group' => 'Location' ),
            '_sthi_city'                 => array( 'label' => 'City', 'type' => 'text', 'group' => 'Location' ),
            '_sthi_district'             => array( 'label' => 'District', 'type' => 'text', 'group' => 'Location' ),
            '_sthi_neighborhood'         => array( 'label' => 'Neighborhood', 'type' => 'text', 'group' => 'Location' ),
            '_sthi_address'              => array( 'label' => 'Full Address', 'type' => 'textarea', 'group' => 'Location' ),
            '_sthi_postal_code'          => array( 'label' => 'Postal Code', 'type' => 'text', 'group' => 'Location' ),
            '_sthi_latitude'             => array( 'label' => 'Latitude', 'type' => 'text', 'group' => 'Location' ),
            '_sthi_longitude'            => array( 'label' => 'Longitude', 'type' => 'text', 'group' => 'Location' ),
            '_sthi_google_maps_url'      => array( 'label' => 'Google Maps URL', 'type' => 'url', 'group' => 'Location' ),
            '_sthi_google_place_id'      => array( 'label' => 'Google Place ID', 'type' => 'text', 'group' => 'Location' ),
            '_sthi_google_maps_embed_url'=> array( 'label' => 'Google Maps Embed URL', 'type' => 'url', 'group' => 'Location' ),

            // Contact.
            '_sthi_main_phone'           => array( 'label' => 'Main Phone', 'type' => 'text', 'group' => 'Contact' ),
            '_sthi_reservation_phone'    => array( 'label' => 'Reservation Phone', 'type' => 'text', 'group' => 'Contact' ),
            '_sthi_whatsapp'             => array( 'label' => 'WhatsApp', 'type' => 'text', 'group' => 'Contact' ),
            '_sthi_email'                => array( 'label' => 'Email', 'type' => 'email', 'group' => 'Contact' ),
            '_sthi_reservation_email'    => array( 'label' => 'Reservation Email', 'type' => 'email', 'group' => 'Contact' ),
            '_sthi_website'              => array( 'label' => 'Official Website', 'type' => 'url', 'group' => 'Contact' ),
            '_sthi_instagram'            => array( 'label' => 'Instagram', 'type' => 'url', 'group' => 'Contact' ),
            '_sthi_facebook'             => array( 'label' => 'Facebook', 'type' => 'url', 'group' => 'Contact' ),
            '_sthi_youtube'              => array( 'label' => 'YouTube', 'type' => 'url', 'group' => 'Contact' ),

            // Operations.
            '_sthi_checkin'              => array( 'label' => 'Check-in', 'type' => 'text', 'group' => 'Operations' ),
            '_sthi_checkout'             => array( 'label' => 'Check-out', 'type' => 'text', 'group' => 'Operations' ),
            '_sthi_room_count'           => array( 'label' => 'Room Count', 'type' => 'number', 'group' => 'Operations' ),
            '_sthi_floor_count'          => array( 'label' => 'Floor Count', 'type' => 'number', 'group' => 'Operations' ),
            '_sthi_wifi'                 => array( 'label' => 'Wi-Fi', 'type' => 'tri', 'group' => 'Operations' ),
            '_sthi_restaurant'           => array( 'label' => 'Restaurant', 'type' => 'tri', 'group' => 'Operations' ),
            '_sthi_elevator'             => array( 'label' => 'Elevator', 'type' => 'tri', 'group' => 'Operations' ),
            '_sthi_meal_plan'            => array( 'label' => 'Meal Plan', 'type' => 'text', 'group' => 'Operations' ),
            '_sthi_accessibility_notes'  => array( 'label' => 'Accessibility Notes', 'type' => 'textarea', 'group' => 'Operations' ),
            '_sthi_operational_notes'    => array( 'label' => 'Server Turizm Operational Notes', 'type' => 'textarea', 'group' => 'Operations' ),
            '_sthi_360_label'            => array( 'label' => '360° Tour Label', 'type' => 'text', 'group' => 'Experience' ),
            '_sthi_360_url'              => array( 'label' => '360° Tour URL', 'type' => 'url', 'group' => 'Experience' ),

            // Media source. The folder URL is the primary v0.4.2 hotel-photo intake path.
            '_sthi_media_folder_url'     => array( 'label' => 'Media Folder URL', 'type' => 'url', 'group' => 'Media' ),

            // Search / social metadata. Optional operator overrides; structured-data fallbacks remain deterministic.
            '_sthi_seo_title_tr'          => array( 'label' => 'SEO Title (TR)', 'type' => 'text', 'group' => 'Search & Social' ),
            '_sthi_meta_description_tr'   => array( 'label' => 'Meta Description (TR)', 'type' => 'textarea', 'group' => 'Search & Social' ),
            '_sthi_primary_topic_tr'      => array( 'label' => 'Primary Semantic Topic (TR)', 'type' => 'text', 'group' => 'Search & Social' ),
            '_sthi_secondary_topics_tr'   => array( 'label' => 'Secondary Semantic Topics (TR)', 'type' => 'textarea', 'group' => 'Search & Social' ),

            // Verification / provenance.
            '_sthi_verification_status'  => array( 'label' => 'Verification Status', 'type' => 'verification', 'group' => 'Verification' ),
            '_sthi_star_rating_source_url'=> array( 'label' => 'Star Rating Source URL', 'type' => 'url', 'group' => 'Verification' ),
            '_sthi_star_rating_verified_at'=> array( 'label' => 'Star Rating Verified At', 'type' => 'date', 'group' => 'Verification' ),
            '_sthi_last_verified_at'     => array( 'label' => 'Last Verified At', 'type' => 'date', 'group' => 'Verification' ),
            '_sthi_source_url'           => array( 'label' => 'Primary Source URL', 'type' => 'url', 'group' => 'Verification' ),
            '_sthi_source_note'          => array( 'label' => 'Source Note', 'type' => 'textarea', 'group' => 'Verification' ),
            '_sthi_source_checked_at'    => array( 'label' => 'Source Checked At', 'type' => 'date', 'group' => 'Verification' ),
            '_sthi_source_ledger_json'   => array( 'label' => 'Source Ledger JSON', 'type' => 'json', 'group' => 'Verification' ),
            '_sthi_media_rights_note'    => array( 'label' => 'Media Source / Rights Note', 'type' => 'textarea', 'group' => 'Verification' ),
            '_sthi_workflow_status'      => array( 'label' => 'Workflow Status', 'type' => 'workflow', 'group' => 'Verification' ),
            '_sthi_manual_order'         => array( 'label' => 'Manual Order', 'type' => 'number', 'group' => 'Internal' ),
        );
    }

    public static function add_boxes() {
        add_meta_box( 'sthi_identity', 'Hotel Intelligence Data', array( __CLASS__, 'render_box' ), 'sthi_hotel', 'normal', 'high' );
        add_meta_box( 'sthi_id', 'Hotel Entity ID', array( __CLASS__, 'render_id_box' ), 'sthi_hotel', 'side', 'high' );
    }

    public static function render_id_box( $post ) {
        $hotel_id = get_post_meta( $post->ID, '_sthi_hotel_id', true );
        echo '<p><strong>' . esc_html( $hotel_id ?: 'Assigned on first save' ) . '</strong></p>';
        echo '<p class="description">Stable entity identifier. It does not change when the hotel title changes.</p>';
    }

    public static function render_box( $post ) {
        wp_nonce_field( 'sthi_save_meta', 'sthi_meta_nonce' );
        $fields = self::fields();
        $groups = array();
        foreach ( $fields as $key => $config ) {
            $groups[ $config['group'] ][ $key ] = $config;
        }

        if ( class_exists( 'STHI_Geography' ) ) {
            echo '<datalist id="sthi-country-options">';
            foreach ( STHI_Geography::countries() as $country_name ) { echo '<option value="' . esc_attr( $country_name ) . '"></option>'; }
            echo '</datalist>';
            echo '<datalist id="sthi-city-options">';
            foreach ( STHI_Geography::city_names() as $city_name ) { echo '<option value="' . esc_attr( $city_name ) . '"></option>'; }
            echo '</datalist>';
        }

        echo '<div class="sthi-meta-tabs">';
        foreach ( $groups as $group => $group_fields ) {
            echo '<section class="sthi-meta-group">';
            echo '<h3>' . esc_html( $group ) . '</h3><div class="sthi-meta-grid">';
            foreach ( $group_fields as $key => $config ) {
                self::render_field( $post->ID, $key, $config );
            }
            echo '</div></section>';
        }
        echo '</div>';
    }

    private static function render_field( $post_id, $key, $config ) {
        $value = get_post_meta( $post_id, $key, true );
        $name = 'sthi_meta[' . $key . ']';
        echo '<label class="sthi-field"><span>' . esc_html( $config['label'] ) . '</span>';

        if ( 'textarea' === $config['type'] ) {
            echo '<textarea name="' . esc_attr( $name ) . '" rows="3">' . esc_textarea( $value ) . '</textarea>';
        } elseif ( 'json' === $config['type'] ) {
            echo '<textarea class="code" name="' . esc_attr( $name ) . '" rows="10" placeholder="[{&quot;field&quot;:&quot;official_name&quot;,&quot;value&quot;:&quot;...&quot;,&quot;source&quot;:&quot;https://...&quot;,&quot;confidence&quot;:&quot;VERIFIED&quot;}]">' . esc_textarea( $value ) . '</textarea>';
        } elseif ( 'tri' === $config['type'] ) {
            self::select( $name, $value, array( 'unknown' => 'Unknown', 'yes' => 'Yes', 'no' => 'No' ) );
        } elseif ( 'verification' === $config['type'] ) {
            self::select( $name, $value ?: 'new', array(
                'new' => 'New',
                'needs_verification' => 'Needs Verification',
                'verified' => 'Verified',
                'needs_reverify' => 'Needs Reverification',
            ) );
        } elseif ( 'workflow' === $config['type'] ) {
            self::select( $name, $value ?: 'new', array(
                'new' => 'New',
                'needs_verification' => 'Needs Verification',
                'verified' => 'Verified',
                'ready_to_publish' => 'Ready to Publish',
                'published' => 'Published',
                'needs_reverify' => 'Needs Reverification',
                'archived' => 'Archived',
            ) );
        } else {
            $min = isset( $config['min'] ) ? ' min="' . esc_attr( $config['min'] ) . '"' : '';
            $max = isset( $config['max'] ) ? ' max="' . esc_attr( $config['max'] ) . '"' : '';
            $list = '';
            if ( '_sthi_country' === $key ) { $list = ' list="sthi-country-options" autocomplete="off"'; }
            if ( '_sthi_city' === $key ) { $list = ' list="sthi-city-options" autocomplete="off"'; }
            echo '<input type="' . esc_attr( $config['type'] ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '"' . $min . $max . $list . ' />';
        }
        echo '</label>';
    }

    private static function select( $name, $value, $options ) {
        echo '<select name="' . esc_attr( $name ) . '">';
        foreach ( $options as $option_value => $label ) {
            echo '<option value="' . esc_attr( $option_value ) . '" ' . selected( $value, $option_value, false ) . '>' . esc_html( $label ) . '</option>';
        }
        echo '</select>';
    }

    public static function save( $post_id, $post ) {
        if ( ! isset( $_POST['sthi_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['sthi_meta_nonce'] ) ), 'sthi_save_meta' ) ) { return; }
        if ( ! current_user_can( 'edit_post', $post_id ) ) { return; }
        if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) { return; }
        if ( empty( $_POST['sthi_meta'] ) || ! is_array( $_POST['sthi_meta'] ) ) { return; }

        $submitted = wp_unslash( $_POST['sthi_meta'] );
        foreach ( self::fields() as $key => $config ) {
            if ( ! array_key_exists( $key, $submitted ) ) { continue; }
            $raw = $submitted[ $key ];
            $value = self::sanitize( $raw, $config['type'] );
            if ( is_wp_error( $value ) ) { continue; }
            if ( class_exists( 'STHI_Geography' ) && in_array( $key, array( '_sthi_country', '_sthi_city' ), true ) ) {
                $value = STHI_Geography::normalize_meta_value( $key, $value );
            }
            update_post_meta( $post_id, $key, $value );
        }

        if ( class_exists( 'STHI_Geography' ) ) { STHI_Geography::sync_post_geography( $post_id ); }

        if ( ! get_post_meta( $post_id, '_sthi_official_name', true ) && $post->post_title ) {
            update_post_meta( $post_id, '_sthi_official_name', sanitize_text_field( $post->post_title ) );
        }
    }

    public static function sanitize( $value, $type ) {
        if ( 'email' === $type ) { return sanitize_email( $value ); }
        if ( 'url' === $type ) { return esc_url_raw( $value ); }
        if ( 'textarea' === $type ) { return sanitize_textarea_field( $value ); }
        if ( 'json' === $type ) {
            $raw = trim( (string) $value );
            if ( '' === $raw ) { return ''; }
            $decoded = json_decode( html_entity_decode( $raw, ENT_QUOTES | ENT_HTML5, 'UTF-8' ), true );
            if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $decoded ) ) { return new WP_Error( 'bad_sthi_json', 'Invalid JSON; previous value was kept.' ); }
            return wp_json_encode( $decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
        }
        if ( 'number' === $type ) { return '' === $value ? '' : (string) (int) $value; }
        if ( in_array( $type, array( 'tri', 'verification', 'workflow' ), true ) ) { return sanitize_key( $value ); }
        return sanitize_text_field( $value );
    }
}
STHI_Meta::init();
