<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class STHI_Structured_Details {
    const META_AMENITIES = '_sthi_amenities_v1';
    const META_ROOMS     = '_sthi_room_types_v1';
    const META_REFS      = '_sthi_reference_points_v1';
    const META_CONTACTS  = '_sthi_contacts_v1';
    const META_SCENES    = '_sthi_360_scenes_v1';
    const META_HEALTH    = '_sthi_health_score';
    const META_SCHEMA    = '_sthi_details_schema_version';
    const SCHEMA_VERSION = '1.0';

    public static function init() {
        add_action( 'wp_ajax_sthi_details_get', array( __CLASS__, 'ajax_get' ) );
        add_action( 'wp_ajax_sthi_details_save', array( __CLASS__, 'ajax_save' ) );
        add_action( 'added_post_meta', array( __CLASS__, 'sync_flat_meta_change' ), 20, 4 );
        add_action( 'updated_post_meta', array( __CLASS__, 'sync_flat_meta_change' ), 20, 4 );
    }


    public static function sync_flat_meta_change( $meta_id, $post_id, $meta_key, $meta_value ) {
        if ( 'sthi_hotel' !== get_post_type( $post_id ) ) { return; }

        $amenity_map = array( '_sthi_wifi'=>'wifi', '_sthi_restaurant'=>'restaurant', '_sthi_elevator'=>'elevator' );
        if ( isset( $amenity_map[ $meta_key ] ) ) {
            $status = self::tri( $meta_value );
            $saved = get_post_meta( $post_id, self::META_AMENITIES, true );
            $saved = is_array( $saved ) ? $saved : array();
            $saved[ $amenity_map[ $meta_key ] ] = $status;
            update_post_meta( $post_id, self::META_AMENITIES, $saved );
            return;
        }

        $contact_map = array(
            '_sthi_main_phone'=>'phone', '_sthi_reservation_phone'=>'reservation_phone', '_sthi_whatsapp'=>'whatsapp',
            '_sthi_email'=>'email', '_sthi_reservation_email'=>'reservation_email', '_sthi_website'=>'website',
            '_sthi_instagram'=>'instagram', '_sthi_facebook'=>'facebook', '_sthi_youtube'=>'youtube'
        );
        if ( isset( $contact_map[ $meta_key ] ) ) {
            $type = $contact_map[ $meta_key ];
            $contacts = get_post_meta( $post_id, self::META_CONTACTS, true );
            $contacts = is_array( $contacts ) ? $contacts : array();
            $value = trim( (string) $meta_value );
            $found = false;
            foreach ( $contacts as $i => $contact ) {
                if ( ( $contact['type'] ?? '' ) === $type && ! empty( $contact['primary'] ) ) {
                    $found = true;
                    if ( '' === $value ) { unset( $contacts[ $i ] ); }
                    else { $contacts[ $i ]['value'] = $value; }
                    break;
                }
            }
            if ( ! $found && '' !== $value ) {
                $contacts[] = array( 'id'=>wp_generate_uuid4(), 'type'=>$type, 'label'=>self::contact_type_label($type), 'value'=>$value, 'primary'=>true, 'source_url'=>'', 'verified_at'=>'' );
            }
            update_post_meta( $post_id, self::META_CONTACTS, array_values( $contacts ) );
            return;
        }

        if ( in_array( $meta_key, array( '_sthi_360_label', '_sthi_360_url' ), true ) ) {
            $scenes = get_post_meta( $post_id, self::META_SCENES, true );
            $scenes = is_array( $scenes ) ? $scenes : array();
            $label = trim( (string) get_post_meta( $post_id, '_sthi_360_label', true ) );
            $url = trim( (string) get_post_meta( $post_id, '_sthi_360_url', true ) );
            if ( $url ) {
                if ( ! $scenes ) { $scenes[] = array( 'id'=>wp_generate_uuid4(), 'type'=>'other', 'label'=>$label ?: '360° Tour', 'url'=>$url ); }
                else { $scenes[0]['label'] = $label ?: ( $scenes[0]['label'] ?? '360° Tour' ); $scenes[0]['url'] = $url; }
            } elseif ( $scenes ) {
                array_shift( $scenes );
            }
            update_post_meta( $post_id, self::META_SCENES, array_values( $scenes ) );
        }
    }

    public static function amenity_catalog() {
        return array(
            'connectivity' => array(
                'label' => 'Connectivity & Front Desk',
                'items' => array(
                    'wifi' => 'Wi-Fi',
                    'reception_24h' => '24h Reception',
                    'concierge' => 'Concierge',
                    'luggage_storage' => 'Luggage Storage',
                ),
            ),
            'food' => array(
                'label' => 'Food & Beverage',
                'items' => array(
                    'restaurant' => 'Restaurant',
                    'breakfast' => 'Breakfast',
                    'lunch' => 'Lunch',
                    'dinner' => 'Dinner',
                    'room_service' => 'Room Service',
                    'minibar' => 'Mini Bar',
                ),
            ),
            'access' => array(
                'label' => 'Access & Transport',
                'items' => array(
                    'elevator' => 'Elevator',
                    'wheelchair_access' => 'Wheelchair Access',
                    'parking' => 'Parking',
                    'shuttle' => 'Shuttle',
                    'airport_transfer' => 'Airport Transfer',
                ),
            ),
            'room' => array(
                'label' => 'Room & Family',
                'items' => array(
                    'air_conditioning' => 'Air Conditioning',
                    'safe' => 'Safe',
                    'family_room' => 'Family Room',
                    'housekeeping' => 'Housekeeping',
                    'laundry' => 'Laundry',
                ),
            ),
            'facilities' => array(
                'label' => 'Facilities',
                'items' => array(
                    'prayer_room' => 'Prayer Room',
                    'gym' => 'Gym',
                    'pool' => 'Pool',
                    'business_center' => 'Business Center',
                ),
            ),
        );
    }

    public static function get_amenities( $post_id ) {
        $saved = get_post_meta( $post_id, self::META_AMENITIES, true );
        $saved = is_array( $saved ) ? $saved : array();
        $all = array();
        foreach ( self::amenity_catalog() as $group ) {
            foreach ( $group['items'] as $code => $label ) {
                $all[ $code ] = isset( $saved[ $code ] ) && in_array( $saved[ $code ], array( 'yes', 'no', 'unknown' ), true ) ? $saved[ $code ] : 'unknown';
            }
        }
        // Backward compatibility: use the accepted v0.4 flat fields until the structured value is explicitly set.
        foreach ( array( 'wifi' => '_sthi_wifi', 'restaurant' => '_sthi_restaurant', 'elevator' => '_sthi_elevator' ) as $code => $meta_key ) {
            if ( ! isset( $saved[ $code ] ) ) {
                $legacy = get_post_meta( $post_id, $meta_key, true );
                if ( in_array( $legacy, array( 'yes', 'no' ), true ) ) { $all[ $code ] = $legacy; }
            }
        }
        return $all;
    }

    public static function get_rooms( $post_id ) {
        $value = get_post_meta( $post_id, self::META_ROOMS, true );
        return is_array( $value ) ? $value : array();
    }

    public static function get_references( $post_id ) {
        $value = get_post_meta( $post_id, self::META_REFS, true );
        return is_array( $value ) ? $value : array();
    }

    public static function get_contacts( $post_id ) {
        $value = get_post_meta( $post_id, self::META_CONTACTS, true );
        if ( is_array( $value ) && $value ) { return $value; }

        // Seed once from the already accepted flat contact fields without modifying them.
        $map = array(
            'phone' => '_sthi_main_phone',
            'reservation_phone' => '_sthi_reservation_phone',
            'whatsapp' => '_sthi_whatsapp',
            'email' => '_sthi_email',
            'reservation_email' => '_sthi_reservation_email',
            'website' => '_sthi_website',
            'instagram' => '_sthi_instagram',
            'facebook' => '_sthi_facebook',
            'youtube' => '_sthi_youtube',
        );
        $seed = array();
        foreach ( $map as $type => $key ) {
            $v = trim( (string) get_post_meta( $post_id, $key, true ) );
            if ( '' === $v ) { continue; }
            $seed[] = array(
                'id' => wp_generate_uuid4(),
                'type' => $type,
                'label' => self::contact_type_label( $type ),
                'value' => $v,
                'primary' => true,
                'source_url' => '',
                'verified_at' => '',
            );
        }
        return $seed;
    }

    public static function get_scenes( $post_id ) {
        $value = get_post_meta( $post_id, self::META_SCENES, true );
        if ( is_array( $value ) && $value ) { return $value; }
        $url = trim( (string) get_post_meta( $post_id, '_sthi_360_url', true ) );
        if ( ! $url ) { return array(); }
        return array( array(
            'id' => wp_generate_uuid4(),
            'type' => 'other',
            'label' => trim( (string) get_post_meta( $post_id, '_sthi_360_label', true ) ) ?: '360° Tour',
            'url' => $url,
        ) );
    }

    public static function health_score( $post_id ) {
        $score = 0;
        $max = 100;
        $has = function( $key ) use ( $post_id ) { return '' !== trim( (string) get_post_meta( $post_id, $key, true ) ); };

        if ( $has( '_sthi_official_name' ) || trim( (string) get_the_title( $post_id ) ) ) { $score += 8; }
        if ( $has( '_sthi_country' ) ) { $score += 4; }
        if ( $has( '_sthi_city' ) ) { $score += 6; }
        if ( $has( '_sthi_star_rating' ) && $has( '_sthi_star_rating_source_url' ) && $has( '_sthi_star_rating_verified_at' ) ) { $score += 4; }
        if ( $has( '_sthi_address' ) ) { $score += 5; }
        if ( $has( '_sthi_google_maps_url' ) || ( $has( '_sthi_latitude' ) && $has( '_sthi_longitude' ) ) ) { $score += 5; }

        $media = STHI_Media::get_items( $post_id );
        if ( $has( '_sthi_media_folder_url' ) ) { $score += 4; }
        if ( count( $media ) >= 1 ) { $score += 5; }
        if ( count( $media ) >= 5 ) { $score += 6; }
        if ( $has( '_sthi_media_rights_note' ) ) { $score += 3; }

        $contacts = self::get_contacts( $post_id );
        if ( count( $contacts ) >= 1 || $has( '_sthi_main_phone' ) || $has( '_sthi_email' ) || $has( '_sthi_website' ) ) { $score += 6; }
        if ( count( $contacts ) >= 2 ) { $score += 3; }

        if ( $has( '_sthi_checkin' ) || $has( '_sthi_checkout' ) ) { $score += 3; }
        if ( $has( '_sthi_meal_plan' ) ) { $score += 3; }
        $amenities = self::get_amenities( $post_id );
        $known = count( array_filter( $amenities, function( $v ) { return 'unknown' !== $v; } ) );
        if ( $known >= 3 ) { $score += 4; }
        if ( $known >= 8 ) { $score += 4; }

        if ( self::get_rooms( $post_id ) ) { $score += 6; }
        if ( self::get_references( $post_id ) ) { $score += 6; }
        if ( self::get_scenes( $post_id ) ) { $score += 3; }

        if ( $has( '_sthi_source_url' ) ) { $score += 5; }
        if ( $has( '_sthi_last_verified_at' ) ) { $score += 5; }
        if ( 'verified' === get_post_meta( $post_id, '_sthi_verification_status', true ) ) { $score += 6; }

        $score = min( $max, max( 0, $score ) );
        update_post_meta( $post_id, self::META_HEALTH, $score );
        return $score;
    }

    public static function health_label( $score ) {
        if ( $score >= 85 ) { return 'Strong'; }
        if ( $score >= 65 ) { return 'Good'; }
        if ( $score >= 40 ) { return 'Needs data'; }
        return 'Incomplete';
    }

    public static function render_modal_shell() {
        ?>
        <div class="sthi-details-modal" id="sthi-details-modal" aria-hidden="true">
            <div class="sthi-details-backdrop" data-sthi-details-close></div>
            <section class="sthi-details-dialog" role="dialog" aria-modal="true" aria-labelledby="sthi-details-title">
                <header class="sthi-details-header">
                    <div>
                        <p class="sthi-details-kicker">HOTEL INTELLIGENCE</p>
                        <h2 id="sthi-details-title">Structured Details</h2>
                        <p class="sthi-details-subtitle" id="sthi-details-subtitle"></p>
                    </div>
                    <div class="sthi-details-head-actions">
                        <span class="sthi-health-pill" id="sthi-details-health">—</span>
                        <button type="button" class="button" data-sthi-details-close>Close</button>
                        <button type="button" class="button button-primary" id="sthi-details-save">Save Details</button>
                    </div>
                </header>
                <div class="sthi-details-loading" id="sthi-details-loading">Loading…</div>
                <div class="sthi-details-content" id="sthi-details-content"></div>
            </section>
        </div>
        <?php
    }

    public static function ajax_get() {
        check_ajax_referer( 'sthi_grid_nonce', 'nonce' );
        $post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
        if ( ! $post_id || 'sthi_hotel' !== get_post_type( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
            wp_send_json_error( array( 'message' => 'Invalid hotel.' ), 400 );
        }
        ob_start();
        self::render_editor( $post_id );
        $html = ob_get_clean();
        $score = self::health_score( $post_id );
        wp_send_json_success( array(
            'html' => $html,
            'hotel_id' => get_post_meta( $post_id, '_sthi_hotel_id', true ),
            'hotel_name' => get_the_title( $post_id ),
            'health' => $score,
            'health_label' => self::health_label( $score ),
        ) );
    }

    private static function render_editor( $post_id ) {
        $amenities = self::get_amenities( $post_id );
        $rooms = self::get_rooms( $post_id );
        $refs = self::get_references( $post_id );
        $contacts = self::get_contacts( $post_id );
        $scenes = self::get_scenes( $post_id );
        ?>
        <div class="sthi-details-tabs" role="tablist" aria-label="Structured hotel data">
            <button type="button" class="is-active" data-sthi-details-tab="amenities">Amenities</button>
            <button type="button" data-sthi-details-tab="rooms">Room Types <span><?php echo esc_html( count( $rooms ) ); ?></span></button>
            <button type="button" data-sthi-details-tab="references">Reference Points <span><?php echo esc_html( count( $refs ) ); ?></span></button>
            <button type="button" data-sthi-details-tab="contacts">Contacts <span><?php echo esc_html( count( $contacts ) ); ?></span></button>
            <button type="button" data-sthi-details-tab="scenes">360° Scenes <span><?php echo esc_html( count( $scenes ) ); ?></span></button>
        </div>

        <div class="sthi-details-pane is-active" data-sthi-details-pane="amenities">
            <div class="sthi-details-intro"><strong>Standardized amenities</strong><span>Use Unknown when the fact has not been verified. Unknown is not the same as No.</span></div>
            <div class="sthi-amenity-groups">
            <?php foreach ( self::amenity_catalog() as $group ) : ?>
                <section class="sthi-amenity-group">
                    <h3><?php echo esc_html( $group['label'] ); ?></h3>
                    <div class="sthi-amenity-grid">
                    <?php foreach ( $group['items'] as $code => $label ) : ?>
                        <label><span><?php echo esc_html( $label ); ?></span>
                            <select data-sthi-amenity="<?php echo esc_attr( $code ); ?>">
                                <option value="unknown" <?php selected( $amenities[ $code ], 'unknown' ); ?>>Unknown</option>
                                <option value="yes" <?php selected( $amenities[ $code ], 'yes' ); ?>>Yes</option>
                                <option value="no" <?php selected( $amenities[ $code ], 'no' ); ?>>No</option>
                            </select>
                        </label>
                    <?php endforeach; ?>
                    </div>
                </section>
            <?php endforeach; ?>
            </div>
        </div>

        <div class="sthi-details-pane" data-sthi-details-pane="rooms">
            <div class="sthi-details-intro"><strong>Room types</strong><span>Store only room facts you can verify. This is not a price/availability table.</span><button type="button" class="button" data-sthi-add-row="room">+ Add Room Type</button></div>
            <div class="sthi-repeater" data-sthi-repeater="rooms">
                <?php foreach ( $rooms as $item ) { self::render_room_row( $item ); } ?>
            </div>
        </div>

        <div class="sthi-details-pane" data-sthi-details-pane="references">
            <div class="sthi-details-intro"><strong>Reference points & distances</strong><span>Examples: Masjid al-Haram, Masjid an-Nabawi, airport, station. Never enter guessed distances.</span><button type="button" class="button" data-sthi-add-row="reference">+ Add Reference Point</button></div>
            <div class="sthi-repeater" data-sthi-repeater="references">
                <?php foreach ( $refs as $item ) { self::render_reference_row( $item ); } ?>
            </div>
        </div>

        <div class="sthi-details-pane" data-sthi-details-pane="contacts">
            <div class="sthi-details-intro"><strong>Multiple hotel contacts</strong><span>These are hotel contacts. Server Turizm CTA remains separate.</span><button type="button" class="button" data-sthi-add-row="contact">+ Add Contact</button></div>
            <div class="sthi-repeater" data-sthi-repeater="contacts">
                <?php foreach ( $contacts as $item ) { self::render_contact_row( $item ); } ?>
            </div>
        </div>

        <div class="sthi-details-pane" data-sthi-details-pane="scenes">
            <div class="sthi-details-intro"><strong>360° scenes</strong><span>Add one URL per verified scene. Front-end rendering can use this structured list in the next stage.</span><button type="button" class="button" data-sthi-add-row="scene">+ Add 360° Scene</button></div>
            <div class="sthi-repeater" data-sthi-repeater="scenes">
                <?php foreach ( $scenes as $item ) { self::render_scene_row( $item ); } ?>
            </div>
        </div>

        <?php self::render_templates(); ?>
        <?php
    }

    private static function render_room_row( $item ) {
        $item = wp_parse_args( (array) $item, array( 'id'=>'', 'name'=>'', 'capacity'=>'', 'beds'=>'', 'size_m2'=>'', 'view'=>'', 'extra_bed'=>'unknown', 'accessible'=>'unknown', 'notes'=>'', 'source_url'=>'', 'verified_at'=>'' ) );
        ?>
        <article class="sthi-repeater-row" data-sthi-row="room" data-row-id="<?php echo esc_attr( $item['id'] ); ?>">
            <div class="sthi-repeater-row-head"><strong>Room Type</strong><button type="button" class="button-link-delete" data-sthi-remove-row>Remove</button></div>
            <div class="sthi-repeater-fields">
                <?php self::text_input( 'name', 'Name', $item['name'], 'Standard Room' ); ?>
                <?php self::number_input( 'capacity', 'Max Occupancy', $item['capacity'], 1, 20 ); ?>
                <?php self::text_input( 'beds', 'Bed Configuration', $item['beds'], '2 Single Beds' ); ?>
                <?php self::number_input( 'size_m2', 'Size (m²)', $item['size_m2'], 0, 1000, '0.1' ); ?>
                <?php self::text_input( 'view', 'View', $item['view'], 'City / Haram / Courtyard' ); ?>
                <?php self::tri_input( 'extra_bed', 'Extra Bed', $item['extra_bed'] ); ?>
                <?php self::tri_input( 'accessible', 'Accessible Room', $item['accessible'] ); ?>
                <?php self::date_input( 'verified_at', 'Verified At', $item['verified_at'] ); ?>
                <?php self::url_input( 'source_url', 'Source URL', $item['source_url'] ); ?>
                <?php self::text_input( 'notes', 'Notes', $item['notes'], 'Verified operational note' ); ?>
            </div>
        </article>
        <?php
    }

    private static function render_reference_row( $item ) {
        $item = wp_parse_args( (array) $item, array( 'id'=>'', 'name'=>'', 'type'=>'holy_site', 'latitude'=>'', 'longitude'=>'', 'distance_m'=>'', 'distance_method'=>'unknown', 'walking_minutes'=>'', 'transport_note'=>'', 'source_url'=>'', 'verified_at'=>'', 'status'=>'needs_verification' ) );
        ?>
        <article class="sthi-repeater-row" data-sthi-row="reference" data-row-id="<?php echo esc_attr( $item['id'] ); ?>">
            <div class="sthi-repeater-row-head"><strong>Reference Point</strong><button type="button" class="button-link-delete" data-sthi-remove-row>Remove</button></div>
            <div class="sthi-repeater-fields">
                <?php self::text_input( 'name', 'Reference Name', $item['name'], 'Masjid al-Haram' ); ?>
                <label><span>Type</span><select data-field="type"><?php self::options( $item['type'], array( 'holy_site'=>'Holy Site','airport'=>'Airport','station'=>'Station','city_center'=>'City Center','landmark'=>'Landmark','transport'=>'Transport','other'=>'Other' ) ); ?></select></label>
                <?php self::text_input( 'latitude', 'Latitude', $item['latitude'], '21.42361' ); ?>
                <?php self::text_input( 'longitude', 'Longitude', $item['longitude'], '39.82722' ); ?>
                <?php self::number_input( 'distance_m', 'Distance (m)', $item['distance_m'], 0, 1000000 ); ?>
                <label><span>Distance Method</span><select data-field="distance_method"><?php self::options( $item['distance_method'], array( 'unknown'=>'Unknown','straight_line'=>'Straight-line','route_verified'=>'Verified route','manual_verified'=>'Verified manual source' ) ); ?></select></label>
                <?php self::number_input( 'walking_minutes', 'Walking Minutes', $item['walking_minutes'], 0, 10000 ); ?>
                <label><span>Verification</span><select data-field="status"><?php self::options( $item['status'], array( 'needs_verification'=>'Needs Verification','verified'=>'Verified','needs_reverify'=>'Needs Reverify' ) ); ?></select></label>
                <?php self::date_input( 'verified_at', 'Verified At', $item['verified_at'] ); ?>
                <?php self::url_input( 'source_url', 'Source URL', $item['source_url'] ); ?>
                <?php self::text_input( 'transport_note', 'Walking / Transport Note', $item['transport_note'], 'Only when verified' ); ?>
            </div>
        </article>
        <?php
    }

    private static function render_contact_row( $item ) {
        $item = wp_parse_args( (array) $item, array( 'id'=>'', 'type'=>'phone', 'label'=>'', 'value'=>'', 'primary'=>false, 'source_url'=>'', 'verified_at'=>'' ) );
        ?>
        <article class="sthi-repeater-row" data-sthi-row="contact" data-row-id="<?php echo esc_attr( $item['id'] ); ?>">
            <div class="sthi-repeater-row-head"><strong>Contact</strong><button type="button" class="button-link-delete" data-sthi-remove-row>Remove</button></div>
            <div class="sthi-repeater-fields">
                <label><span>Type</span><select data-field="type"><?php self::options( $item['type'], self::contact_types() ); ?></select></label>
                <?php self::text_input( 'label', 'Label', $item['label'], 'Reservations / Reception' ); ?>
                <?php self::text_input( 'value', 'Value', $item['value'], '+966 ... / email / URL' ); ?>
                <label class="sthi-checkbox-field"><span>Primary</span><input type="checkbox" data-field="primary" value="1" <?php checked( ! empty( $item['primary'] ) ); ?>></label>
                <?php self::date_input( 'verified_at', 'Verified At', $item['verified_at'] ); ?>
                <?php self::url_input( 'source_url', 'Source URL', $item['source_url'] ); ?>
            </div>
        </article>
        <?php
    }

    private static function render_scene_row( $item ) {
        $item = wp_parse_args( (array) $item, array( 'id'=>'', 'type'=>'other', 'label'=>'', 'url'=>'' ) );
        ?>
        <article class="sthi-repeater-row" data-sthi-row="scene" data-row-id="<?php echo esc_attr( $item['id'] ); ?>">
            <div class="sthi-repeater-row-head"><strong>360° Scene</strong><button type="button" class="button-link-delete" data-sthi-remove-row>Remove</button></div>
            <div class="sthi-repeater-fields">
                <label><span>Scene Type</span><select data-field="type"><?php self::options( $item['type'], array( 'lobby'=>'Lobby','room'=>'Room','suite'=>'Suite','restaurant'=>'Restaurant','reception'=>'Reception','exterior'=>'Exterior','other'=>'Other' ) ); ?></select></label>
                <?php self::text_input( 'label', 'Label', $item['label'], 'Lobby 360°' ); ?>
                <?php self::url_input( 'url', '360° URL', $item['url'] ); ?>
            </div>
        </article>
        <?php
    }

    private static function render_templates() {
        echo '<template id="sthi-template-room">'; self::render_room_row( array() ); echo '</template>';
        echo '<template id="sthi-template-reference">'; self::render_reference_row( array() ); echo '</template>';
        echo '<template id="sthi-template-contact">'; self::render_contact_row( array() ); echo '</template>';
        echo '<template id="sthi-template-scene">'; self::render_scene_row( array() ); echo '</template>';
    }

    private static function text_input( $field, $label, $value, $placeholder = '' ) {
        echo '<label><span>' . esc_html( $label ) . '</span><input type="text" data-field="' . esc_attr( $field ) . '" value="' . esc_attr( $value ) . '" placeholder="' . esc_attr( $placeholder ) . '"></label>';
    }
    private static function url_input( $field, $label, $value ) {
        echo '<label><span>' . esc_html( $label ) . '</span><input type="url" data-field="' . esc_attr( $field ) . '" value="' . esc_attr( $value ) . '" placeholder="https://..."></label>';
    }
    private static function date_input( $field, $label, $value ) {
        echo '<label><span>' . esc_html( $label ) . '</span><input type="date" data-field="' . esc_attr( $field ) . '" value="' . esc_attr( $value ) . '"></label>';
    }
    private static function number_input( $field, $label, $value, $min, $max, $step = '1' ) {
        echo '<label><span>' . esc_html( $label ) . '</span><input type="number" data-field="' . esc_attr( $field ) . '" value="' . esc_attr( $value ) . '" min="' . esc_attr( $min ) . '" max="' . esc_attr( $max ) . '" step="' . esc_attr( $step ) . '"></label>';
    }
    private static function tri_input( $field, $label, $value ) {
        echo '<label><span>' . esc_html( $label ) . '</span><select data-field="' . esc_attr( $field ) . '">';
        self::options( $value, array( 'unknown'=>'Unknown','yes'=>'Yes','no'=>'No' ) );
        echo '</select></label>';
    }
    private static function options( $current, $options ) {
        foreach ( $options as $value => $label ) { echo '<option value="' . esc_attr( $value ) . '" ' . selected( $current, $value, false ) . '>' . esc_html( $label ) . '</option>'; }
    }

    private static function contact_types() {
        return array(
            'phone'=>'Phone', 'reservation_phone'=>'Reservation Phone', 'whatsapp'=>'WhatsApp',
            'email'=>'Email', 'reservation_email'=>'Reservation Email', 'website'=>'Website',
            'instagram'=>'Instagram', 'facebook'=>'Facebook', 'youtube'=>'YouTube'
        );
    }
    private static function contact_type_label( $type ) {
        $types = self::contact_types();
        return isset( $types[ $type ] ) ? $types[ $type ] : ucfirst( str_replace( '_', ' ', $type ) );
    }

    /**
     * Normalize one structured import group from ChatGPT/CSV JSON.
     * This is deliberately public so Hotel Data Bridge can validate during Dry Run.
     */
    public static function normalize_import_group( $group, $input ) {
        switch ( sanitize_key( $group ) ) {
            case 'amenities':
                return self::sanitize_amenities( $input );
            case 'rooms':
                return self::sanitize_rooms( $input );
            case 'references':
                return self::sanitize_references( $input );
            case 'contacts':
                return self::sanitize_contacts( $input );
            case 'scenes':
                return self::sanitize_scenes( $input );
        }
        return new WP_Error( 'unknown_structured_group', 'Unknown structured hotel data group.' );
    }

    /** Return the canonical stored group used by import/export/diff. */
    public static function get_import_group( $post_id, $group ) {
        switch ( sanitize_key( $group ) ) {
            case 'amenities': return self::get_amenities( $post_id );
            case 'rooms': return self::get_rooms( $post_id );
            case 'references': return self::get_references( $post_id );
            case 'contacts': return self::get_contacts( $post_id );
            case 'scenes': return self::get_scenes( $post_id );
        }
        return array();
    }

    /**
     * Apply one structured group after Dry Run approval and keep v0.4 flat fields coherent.
     * __CLEAR__ is handled by the bridge and arrives here as $clear=true.
     */
    public static function apply_import_group( $post_id, $group, $input, $clear = false ) {
        $group = sanitize_key( $group );
        if ( ! $post_id || 'sthi_hotel' !== get_post_type( $post_id ) ) {
            return new WP_Error( 'invalid_hotel', 'Invalid hotel for structured import.' );
        }

        if ( $clear ) {
            if ( 'amenities' === $group ) {
                delete_post_meta( $post_id, self::META_AMENITIES );
                foreach ( array( '_sthi_wifi', '_sthi_restaurant', '_sthi_elevator' ) as $key ) { delete_post_meta( $post_id, $key ); }
            } elseif ( 'rooms' === $group ) {
                delete_post_meta( $post_id, self::META_ROOMS );
            } elseif ( 'references' === $group ) {
                delete_post_meta( $post_id, self::META_REFS );
            } elseif ( 'contacts' === $group ) {
                delete_post_meta( $post_id, self::META_CONTACTS );
                foreach ( array( '_sthi_main_phone','_sthi_reservation_phone','_sthi_whatsapp','_sthi_email','_sthi_reservation_email','_sthi_website','_sthi_instagram','_sthi_facebook','_sthi_youtube' ) as $key ) { delete_post_meta( $post_id, $key ); }
            } elseif ( 'scenes' === $group ) {
                delete_post_meta( $post_id, self::META_SCENES );
                delete_post_meta( $post_id, '_sthi_360_label' );
                delete_post_meta( $post_id, '_sthi_360_url' );
            } else {
                return new WP_Error( 'unknown_structured_group', 'Unknown structured hotel data group.' );
            }
            update_post_meta( $post_id, self::META_SCHEMA, self::SCHEMA_VERSION );
            self::health_score( $post_id );
            return true;
        }

        $value = self::normalize_import_group( $group, $input );
        if ( is_wp_error( $value ) ) { return $value; }

        if ( 'amenities' === $group ) {
            update_post_meta( $post_id, self::META_AMENITIES, $value );
            foreach ( array( 'wifi'=>'_sthi_wifi', 'restaurant'=>'_sthi_restaurant', 'elevator'=>'_sthi_elevator' ) as $code => $meta_key ) {
                if ( isset( $value[ $code ] ) ) { update_post_meta( $post_id, $meta_key, $value[ $code ] ); }
            }
        } elseif ( 'rooms' === $group ) {
            update_post_meta( $post_id, self::META_ROOMS, $value );
        } elseif ( 'references' === $group ) {
            update_post_meta( $post_id, self::META_REFS, $value );
        } elseif ( 'contacts' === $group ) {
            update_post_meta( $post_id, self::META_CONTACTS, $value );
            self::sync_legacy_contacts( $post_id, $value );
        } elseif ( 'scenes' === $group ) {
            update_post_meta( $post_id, self::META_SCENES, $value );
            if ( $value ) {
                update_post_meta( $post_id, '_sthi_360_label', $value[0]['label'] );
                update_post_meta( $post_id, '_sthi_360_url', $value[0]['url'] );
            } else {
                delete_post_meta( $post_id, '_sthi_360_label' );
                delete_post_meta( $post_id, '_sthi_360_url' );
            }
        } else {
            return new WP_Error( 'unknown_structured_group', 'Unknown structured hotel data group.' );
        }

        update_post_meta( $post_id, self::META_SCHEMA, self::SCHEMA_VERSION );
        self::health_score( $post_id );
        return true;
    }

    public static function ajax_save() {
        check_ajax_referer( 'sthi_grid_nonce', 'nonce' );
        $post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
        if ( ! $post_id || 'sthi_hotel' !== get_post_type( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
            wp_send_json_error( array( 'message' => 'Invalid hotel.' ), 400 );
        }
        $raw = isset( $_POST['payload'] ) ? wp_unslash( $_POST['payload'] ) : '';
        $payload = json_decode( $raw, true );
        if ( ! is_array( $payload ) ) { wp_send_json_error( array( 'message' => 'Invalid data payload.' ), 400 ); }

        $amenities = self::sanitize_amenities( isset( $payload['amenities'] ) ? $payload['amenities'] : array() );
        $rooms = self::sanitize_rooms( isset( $payload['rooms'] ) ? $payload['rooms'] : array() );
        $refs = self::sanitize_references( isset( $payload['references'] ) ? $payload['references'] : array() );
        $contacts = self::sanitize_contacts( isset( $payload['contacts'] ) ? $payload['contacts'] : array() );
        $scenes = self::sanitize_scenes( isset( $payload['scenes'] ) ? $payload['scenes'] : array() );

        update_post_meta( $post_id, self::META_AMENITIES, $amenities );
        update_post_meta( $post_id, self::META_ROOMS, $rooms );
        update_post_meta( $post_id, self::META_REFS, $refs );
        update_post_meta( $post_id, self::META_CONTACTS, $contacts );
        update_post_meta( $post_id, self::META_SCENES, $scenes );
        update_post_meta( $post_id, self::META_SCHEMA, self::SCHEMA_VERSION );

        // Keep the accepted v0.4 renderer compatible with structured data.
        foreach ( array( 'wifi'=>'_sthi_wifi', 'restaurant'=>'_sthi_restaurant', 'elevator'=>'_sthi_elevator' ) as $code => $meta_key ) {
            if ( isset( $amenities[ $code ] ) ) { update_post_meta( $post_id, $meta_key, $amenities[ $code ] ); }
        }
        self::sync_legacy_contacts( $post_id, $contacts );
        if ( $scenes ) {
            update_post_meta( $post_id, '_sthi_360_label', $scenes[0]['label'] );
            update_post_meta( $post_id, '_sthi_360_url', $scenes[0]['url'] );
        }

        $score = self::health_score( $post_id );
        wp_send_json_success( array(
            'health' => $score,
            'health_label' => self::health_label( $score ),
            'counts' => array( 'rooms'=>count($rooms), 'references'=>count($refs), 'contacts'=>count($contacts), 'scenes'=>count($scenes) ),
        ) );
    }

    private static function sanitize_amenities( $input ) {
        $allowed = array();
        foreach ( self::amenity_catalog() as $group ) { foreach ( $group['items'] as $code => $label ) { $allowed[ $code ] = true; } }
        $out = array();
        foreach ( (array) $input as $code => $status ) {
            $code = sanitize_key( $code );
            if ( ! isset( $allowed[ $code ] ) ) { continue; }
            $status = sanitize_key( $status );
            $out[ $code ] = in_array( $status, array( 'yes','no','unknown' ), true ) ? $status : 'unknown';
        }
        foreach ( $allowed as $code => $yes ) { if ( ! isset( $out[ $code ] ) ) { $out[ $code ] = 'unknown'; } }
        return $out;
    }

    private static function sanitize_rooms( $input ) {
        $out = array();
        foreach ( array_slice( (array) $input, 0, 50 ) as $item ) {
            if ( ! is_array( $item ) ) { continue; }
            $name = sanitize_text_field( $item['name'] ?? '' );
            if ( '' === $name ) { continue; }
            $out[] = array(
                'id' => self::clean_id( $item['id'] ?? '' ),
                'name' => $name,
                'capacity' => self::bounded_number( $item['capacity'] ?? ( $item['occupancy'] ?? '' ), 1, 20, true ),
                'beds' => sanitize_text_field( $item['beds'] ?? '' ),
                'size_m2' => self::bounded_number( $item['size_m2'] ?? '', 0, 1000, false ),
                'view' => sanitize_text_field( $item['view'] ?? '' ),
                'extra_bed' => self::tri( $item['extra_bed'] ?? 'unknown' ),
                'accessible' => self::tri( $item['accessible'] ?? 'unknown' ),
                'notes' => sanitize_text_field( $item['notes'] ?? '' ),
                'source_url' => esc_url_raw( $item['source_url'] ?? '' ),
                'verified_at' => self::date( $item['verified_at'] ?? '' ),
            );
        }
        return $out;
    }

    private static function sanitize_references( $input ) {
        $types = array( 'holy_site','airport','station','city_center','landmark','transport','other' );
        $statuses = array( 'needs_verification','verified','needs_reverify' );
        $out = array();
        foreach ( array_slice( (array) $input, 0, 50 ) as $item ) {
            if ( ! is_array( $item ) ) { continue; }
            $name = sanitize_text_field( $item['name'] ?? '' );
            if ( '' === $name ) { continue; }
            $type = sanitize_key( $item['type'] ?? 'other' );
            $status = sanitize_key( $item['status'] ?? 'needs_verification' );
            $distance_method = sanitize_key( $item['distance_method'] ?? '' );
            $allowed_methods = array( 'unknown','straight_line','route_verified','manual_verified' );
            $distance_m_raw = $item['distance_m'] ?? '';
            if ( '' === (string) $distance_m_raw && isset( $item['distance_km_straight_line'] ) && is_numeric( $item['distance_km_straight_line'] ) ) {
                $distance_m_raw = round( (float) $item['distance_km_straight_line'] * 1000 );
                if ( '' === $distance_method ) { $distance_method = 'straight_line'; }
            }
            if ( ! in_array( $distance_method, $allowed_methods, true ) ) { $distance_method = 'unknown'; }
            $out[] = array(
                'id' => self::clean_id( $item['id'] ?? '' ),
                'name' => $name,
                'type' => in_array( $type, $types, true ) ? $type : 'other',
                'latitude' => self::coordinate( $item['latitude'] ?? '', -90, 90 ),
                'longitude' => self::coordinate( $item['longitude'] ?? '', -180, 180 ),
                'distance_m' => self::bounded_number( $distance_m_raw, 0, 1000000, true ),
                'distance_method' => $distance_method,
                'walking_minutes' => self::bounded_number( $item['walking_minutes'] ?? '', 0, 10000, true ),
                'transport_note' => sanitize_text_field( $item['transport_note'] ?? '' ),
                'source_url' => esc_url_raw( $item['source_url'] ?? '' ),
                'verified_at' => self::date( $item['verified_at'] ?? '' ),
                'status' => in_array( $status, $statuses, true ) ? $status : 'needs_verification',
            );
        }
        return $out;
    }

    private static function sanitize_contacts( $input ) {
        $types = self::contact_types();
        $out = array();
        foreach ( array_slice( (array) $input, 0, 30 ) as $item ) {
            if ( ! is_array( $item ) ) { continue; }
            $type = sanitize_key( $item['type'] ?? 'phone' );
            if ( ! isset( $types[ $type ] ) ) { $type = 'phone'; }
            $value = sanitize_text_field( $item['value'] ?? '' );
            if ( '' === $value ) { continue; }
            if ( in_array( $type, array( 'website','instagram','facebook','youtube' ), true ) ) { $value = esc_url_raw( $value ); }
            if ( in_array( $type, array( 'email','reservation_email' ), true ) ) { $value = sanitize_email( $value ); }
            if ( '' === $value ) { continue; }
            $out[] = array(
                'id' => self::clean_id( $item['id'] ?? '' ),
                'type' => $type,
                'label' => sanitize_text_field( $item['label'] ?? '' ),
                'value' => $value,
                'primary' => ! empty( $item['primary'] ),
                'source_url' => esc_url_raw( $item['source_url'] ?? '' ),
                'verified_at' => self::date( $item['verified_at'] ?? '' ),
            );
        }
        return $out;
    }

    private static function sanitize_scenes( $input ) {
        $types = array( 'lobby','room','suite','restaurant','reception','exterior','other' );
        $out = array();
        foreach ( array_slice( (array) $input, 0, 50 ) as $item ) {
            if ( ! is_array( $item ) ) { continue; }
            $url = esc_url_raw( $item['url'] ?? '' );
            if ( '' === $url ) { continue; }
            $type = sanitize_key( $item['type'] ?? 'other' );
            $out[] = array(
                'id' => self::clean_id( $item['id'] ?? '' ),
                'type' => in_array( $type, $types, true ) ? $type : 'other',
                'label' => sanitize_text_field( $item['label'] ?? '' ) ?: '360° Tour',
                'url' => $url,
            );
        }
        return $out;
    }

    private static function sync_legacy_contacts( $post_id, $contacts ) {
        $map = array(
            'phone' => '_sthi_main_phone',
            'reservation_phone' => '_sthi_reservation_phone',
            'whatsapp' => '_sthi_whatsapp',
            'email' => '_sthi_email',
            'reservation_email' => '_sthi_reservation_email',
            'website' => '_sthi_website',
            'instagram' => '_sthi_instagram',
            'facebook' => '_sthi_facebook',
            'youtube' => '_sthi_youtube',
        );
        foreach ( $map as $type => $meta_key ) {
            $matching = array_values( array_filter( $contacts, function( $c ) use ( $type ) { return isset( $c['type'] ) && $type === $c['type']; } ) );
            if ( ! $matching ) { delete_post_meta( $post_id, $meta_key ); continue; }
            $primary = null;
            foreach ( $matching as $c ) { if ( ! empty( $c['primary'] ) ) { $primary = $c; break; } }
            if ( ! $primary ) { $primary = $matching[0]; }
            update_post_meta( $post_id, $meta_key, $primary['value'] );
        }
    }

    private static function clean_id( $id ) {
        $id = sanitize_text_field( $id );
        return $id ? $id : wp_generate_uuid4();
    }
    private static function tri( $value ) {
        $value = sanitize_key( $value );
        return in_array( $value, array( 'yes','no','unknown' ), true ) ? $value : 'unknown';
    }
    private static function date( $value ) {
        $value = sanitize_text_field( $value );
        return preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ? $value : '';
    }
    private static function coordinate( $value, $min, $max ) {
        if ( '' === (string) $value || ! is_numeric( $value ) ) { return ''; }
        $n = (float) $value;
        if ( $n < $min || $n > $max ) { return ''; }
        return rtrim( rtrim( number_format( $n, 7, '.', '' ), '0' ), '.' );
    }

    private static function bounded_number( $value, $min, $max, $integer ) {
        if ( '' === (string) $value || ! is_numeric( $value ) ) { return ''; }
        $n = $integer ? (int) $value : (float) $value;
        $n = min( $max, max( $min, $n ) );
        return $n;
    }
}
STHI_Structured_Details::init();
