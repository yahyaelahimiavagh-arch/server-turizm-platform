<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class STPF_Meta {
    const SCHEMA_VERSION = 'program-entity-0.1';

    public static function init() {
        add_action( 'add_meta_boxes_stpf_program', array( __CLASS__, 'add_boxes' ) );
        add_action( 'save_post_stpf_program', array( __CLASS__, 'save' ), 20, 3 );
    }

    public static function fields() {
        return array(
            'schema_version' => array( 'meta'=>'_stpf_schema_version', 'label'=>'Schema Version', 'type'=>'schema' ),
            'source_key' => array( 'meta'=>'_stpf_source_key', 'label'=>'Source Key', 'type'=>'text' ),
            'legacy_program_no' => array( 'meta'=>'_stpf_legacy_program_no', 'label'=>'Legacy Program No', 'type'=>'text' ),
            'program_type' => array( 'meta'=>'_stpf_program_type', 'label'=>'Program Type', 'type'=>'program_type' ),
            'internal_label' => array( 'meta'=>'_stpf_internal_label', 'label'=>'Internal Label', 'type'=>'text' ),
            'departure_date' => array( 'meta'=>'_stpf_departure_date', 'label'=>'Departure Date', 'type'=>'date' ),
            'return_date' => array( 'meta'=>'_stpf_return_date', 'label'=>'Return Date', 'type'=>'date' ),
            'duration_nights' => array( 'meta'=>'_stpf_duration_nights', 'label'=>'Duration / Nights', 'type'=>'nonneg_int' ),
            'route_ref' => array( 'meta'=>'_stpf_route_ref', 'label'=>'Destination / Route Ref', 'type'=>'text' ),
            'lifecycle_state' => array( 'meta'=>'_stpf_lifecycle_state', 'label'=>'Lifecycle State', 'type'=>'lifecycle' ),
            'hotel_refs' => array( 'meta'=>'_stpf_hotel_refs', 'label'=>'Hotel Stable IDs', 'type'=>'hotel_refs' ),
            'source_owner' => array( 'meta'=>'_stpf_source_owner', 'label'=>'Source / Owner', 'type'=>'text' ),
            'verified_at' => array( 'meta'=>'_stpf_verified_at', 'label'=>'Verified / Updated At', 'type'=>'datetime' ),
            'public_url' => array( 'meta'=>'_stpf_public_url', 'label'=>'Current Public URL Ref', 'type'=>'url' ),
        );
    }

    public static function add_boxes() {
        add_meta_box( 'stpf_identity', 'Program Entity / H6A', array( __CLASS__, 'render_box' ), 'stpf_program', 'normal', 'high' );
    }

    public static function render_box( $post ) {
        wp_nonce_field( 'stpf_save_program', 'stpf_program_nonce' );
        $program_id = get_post_meta( $post->ID, STPF_Program_ID::META_KEY, true );
        echo '<p><strong>Stable Program ID:</strong> <code>' . esc_html( $program_id ?: 'Assigned on first save' ) . '</code><br><span class="description">Immutable. It is not a slug, title, date or URL.</span></p>';
        echo '<table class="form-table"><tbody>';
        foreach ( self::fields() as $key => $field ) {
            $value = get_post_meta( $post->ID, $field['meta'], true );
            if ( 'schema_version' === $key && ! $value ) { $value = self::SCHEMA_VERSION; }
            echo '<tr><th><label for="stpf_' . esc_attr( $key ) . '">' . esc_html( $field['label'] ) . '</label></th><td>';
            if ( 'hotel_refs' === $field['type'] ) {
                echo '<textarea id="stpf_' . esc_attr( $key ) . '" name="stpf_meta[' . esc_attr( $key ) . ']" rows="4" class="large-text code" placeholder="STH-000007">' . esc_textarea( implode( "\n", (array) $value ) ) . '</textarea>';
                echo '<p class="description">One per line or comma-separated. Unknown IDs are rejected; Hotel names are never guessed.</p>';
            } elseif ( 'program_type' === $field['type'] ) {
                echo '<select id="stpf_' . esc_attr( $key ) . '" name="stpf_meta[' . esc_attr( $key ) . ']">';
                foreach ( array('umre'=>'Umre','hac'=>'Hac','tour'=>'Tour','other'=>'Other') as $v=>$label ) echo '<option value="'.esc_attr($v).'" '.selected($value,$v,false).'>'.esc_html($label).'</option>';
                echo '</select>';
            } elseif ( 'lifecycle' === $field['type'] ) {
                echo '<select id="stpf_' . esc_attr( $key ) . '" name="stpf_meta[' . esc_attr( $key ) . ']">';
                foreach ( array('draft'=>'Draft','active'=>'Active','paused'=>'Paused','archived'=>'Archived') as $v=>$label ) echo '<option value="'.esc_attr($v).'" '.selected($value,$v,false).'>'.esc_html($label).'</option>';
                echo '</select>';
            } else {
                $input_type = 'date' === $field['type'] ? 'date' : ( 'nonneg_int' === $field['type'] ? 'number' : 'text' );
                echo '<input id="stpf_' . esc_attr( $key ) . '" name="stpf_meta[' . esc_attr( $key ) . ']" type="' . esc_attr( $input_type ) . '" class="regular-text" value="' . esc_attr( is_array($value)?'':$value ) . '" ' . ( 'nonneg_int' === $field['type'] ? 'min="0" step="1"' : '' ) . '>';
                if ( 'public_url' === $key ) echo '<p class="description">Resolvable property only; never the entity identity.</p>';
            }
            echo '</td></tr>';
        }
        echo '</tbody></table>';
    }

    public static function save( $post_id, $post, $update ) {
        if ( ! isset( $_POST['stpf_program_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['stpf_program_nonce'] ) ), 'stpf_save_program' ) ) { return; }
        if ( ! current_user_can( 'manage_options' ) || wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) { return; }
        $raw = isset( $_POST['stpf_meta'] ) && is_array( $_POST['stpf_meta'] ) ? wp_unslash( $_POST['stpf_meta'] ) : array();
        foreach ( self::fields() as $key => $field ) {
            if ( ! array_key_exists( $key, $raw ) ) { continue; }
            $normalized = self::normalize_value( $key, $raw[$key] );
            if ( is_wp_error( $normalized ) ) {
                set_transient( 'stpf_notice_' . get_current_user_id(), array( 'error'=>true, 'message'=>$field['label'] . ': ' . $normalized->get_error_message() ), 60 );
                continue;
            }
            update_post_meta( $post_id, $field['meta'], $normalized );
        }
        if ( ! get_post_meta( $post_id, '_stpf_schema_version', true ) ) update_post_meta( $post_id, '_stpf_schema_version', self::SCHEMA_VERSION );
    }

    public static function normalize_value( $key, $value ) {
        $fields = self::fields();
        if ( ! isset( $fields[$key] ) ) return new WP_Error( 'unknown_field', 'Unknown field.' );
        $type = $fields[$key]['type'];
        if ( 'schema' === $type ) {
            $value = trim( (string) $value );
            return '' === $value ? self::SCHEMA_VERSION : sanitize_text_field( $value );
        }
        if ( 'program_type' === $type ) {
            $value = sanitize_key( $value );
            return in_array( $value, array('umre','hac','tour','other'), true ) ? $value : new WP_Error( 'bad_program_type', 'Use umre, hac, tour or other.' );
        }
        if ( 'lifecycle' === $type ) {
            $value = sanitize_key( $value );
            return in_array( $value, array('draft','active','paused','archived'), true ) ? $value : new WP_Error( 'bad_lifecycle', 'Use draft, active, paused or archived.' );
        }
        if ( 'date' === $type ) {
            $value = trim( (string) $value );
            if ( '' === $value ) return '';
            $dt = DateTime::createFromFormat( '!Y-m-d', $value );
            return ( $dt && $dt->format('Y-m-d') === $value ) ? $value : new WP_Error( 'bad_date', 'Use YYYY-MM-DD.' );
        }
        if ( 'datetime' === $type ) {
            $value = trim( (string) $value );
            if ( '' === $value ) return '';
            foreach ( array('Y-m-d H:i:s','Y-m-d\\TH:i:sP','Y-m-d') as $fmt ) {
                $dt = DateTime::createFromFormat( $fmt, $value );
                if ( $dt ) return sanitize_text_field( $value );
            }
            return new WP_Error( 'bad_datetime', 'Use YYYY-MM-DD, YYYY-MM-DD HH:MM:SS, or ISO-8601.' );
        }
        if ( 'nonneg_int' === $type ) {
            $value = trim( (string) $value );
            if ( '' === $value ) return '';
            if ( ! ctype_digit( $value ) ) return new WP_Error( 'bad_integer', 'Use a whole number 0 or greater.' );
            return (string) absint( $value );
        }
        if ( 'url' === $type ) {
            $value = trim( (string) $value );
            if ( '' === $value ) return '';
            $url = esc_url_raw( $value );
            return $url ? $url : new WP_Error( 'bad_url', 'Use a valid absolute URL.' );
        }
        if ( 'hotel_refs' === $type ) return self::normalize_hotel_refs( $value, true );
        return sanitize_text_field( (string) $value );
    }

    public static function normalize_hotel_refs( $raw, $require_existing = true ) {
        if ( is_array( $raw ) ) $parts = $raw;
        else {
            $text = trim( (string) $raw );
            if ( '' === $text ) return array();
            if ( '[' === substr( $text, 0, 1 ) ) {
                $decoded = json_decode( $text, true );
                if ( is_array( $decoded ) ) $parts = $decoded;
                else return new WP_Error( 'bad_hotel_json', 'Hotel refs JSON must be an array.' );
            } else $parts = preg_split( '/[\\r\\n,;|]+/', $text );
        }
        $refs = array();
        foreach ( (array) $parts as $part ) {
            $ref = strtoupper( trim( (string) $part ) );
            if ( '' === $ref ) continue;
            if ( ! preg_match( '/^STH-\\d{6}$/', $ref ) ) return new WP_Error( 'bad_hotel_id', 'Invalid Hotel ID ' . $ref . '; expected STH-000001.' );
            if ( $require_existing && ! self::hotel_exists( $ref ) ) return new WP_Error( 'unknown_hotel_id', 'Unknown Hotel ID ' . $ref . '. Relationship was not guessed.' );
            $refs[] = $ref;
        }
        return array_values( array_unique( $refs ) );
    }

    public static function hotel_exists( $hotel_id ) {
        if ( ! post_type_exists( 'sthi_hotel' ) ) return false;
        $ids = get_posts( array(
            'post_type'=>'sthi_hotel', 'post_status'=>'any', 'posts_per_page'=>1, 'fields'=>'ids',
            'meta_key'=>'_sthi_hotel_id', 'meta_value'=>$hotel_id,
        ) );
        return ! empty( $ids );
    }
}
STPF_Meta::init();
