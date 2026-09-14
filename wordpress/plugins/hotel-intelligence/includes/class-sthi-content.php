<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * F-HOTEL-CONTENT-001
 * Long Hotel Editorial Caption + Stable Auto Internal Linking.
 *
 * Storage rule: Hotel facts/content stay on the Hotel Entity. Internal links are
 * represented by stable intents/tokens and resolved to canonical public URLs at
 * render time. No Program/Hotel link is activated before its target is public.
 */
final class STHI_Content {
    const META_SUMMARY       = '_sthi_editorial_summary_tr';
    const META_LONG          = '_sthi_editorial_long_tr';
    const META_LONG_CAPTION  = '_sthi_long_caption_tr';
    const META_SOURCE_MODE   = '_sthi_content_source_mode';
    const META_VERIFIED_AT   = '_sthi_content_verified_at';
    const META_VERIFIED_BY   = '_sthi_content_verified_by';
    const META_SOURCE_NOTES  = '_sthi_content_source_notes';
    const META_LINK_INTENTS  = '_sthi_link_intents';
    const NONCE_ACTION       = 'sthi_save_content';

    public static function init() {
        add_action( 'add_meta_boxes_sthi_hotel', array( __CLASS__, 'add_box' ), 20 );
        add_action( 'save_post_sthi_hotel', array( __CLASS__, 'save' ), 35, 2 );
    }

    public static function meta_keys() {
        return array(
            self::META_SUMMARY,
            self::META_LONG,
            self::META_LONG_CAPTION,
            self::META_SOURCE_MODE,
            self::META_VERIFIED_AT,
            self::META_VERIFIED_BY,
            self::META_SOURCE_NOTES,
            self::META_LINK_INTENTS,
        );
    }

    public static function add_box() {
        add_meta_box(
            'sthi_editorial_content',
            'Long Hotel Editorial Content',
            array( __CLASS__, 'render_box' ),
            'sthi_hotel',
            'normal',
            'high'
        );
    }

    public static function render_box( $post ) {
        wp_nonce_field( self::NONCE_ACTION, 'sthi_content_nonce' );
        $summary      = (string) get_post_meta( $post->ID, self::META_SUMMARY, true );
        $long         = (string) get_post_meta( $post->ID, self::META_LONG, true );
        $long_caption = (string) get_post_meta( $post->ID, self::META_LONG_CAPTION, true );
        $source_mode  = (string) get_post_meta( $post->ID, self::META_SOURCE_MODE, true );
        $verified_at  = (string) get_post_meta( $post->ID, self::META_VERIFIED_AT, true );
        $verified_by  = (string) get_post_meta( $post->ID, self::META_VERIFIED_BY, true );
        $source_notes = (string) get_post_meta( $post->ID, self::META_SOURCE_NOTES, true );
        $intents      = self::get_link_intents( $post->ID );
        $current_user = wp_get_current_user();
        ?>
        <div class="sthi-content-editor" data-sthi-content-editor data-current-user="<?php echo esc_attr( $current_user->display_name ); ?>">
            <div class="sthi-content-notice">
                <strong>ENTER ONCE → PUBLISH MANY</strong>
                <p>This is the Hotel's long editorial content, not the meta description. Use only verified Hotel Entity facts and approved Server Turizm evidence. Do not invent facilities, ratings, reviews, prices, room inventory, walking times or routes.</p>
            </div>

            <div class="sthi-content-grid">
                <label class="sthi-field sthi-content-full"><span>Editorial Summary (TR)</span>
                    <textarea name="sthi_content[summary]" rows="4" placeholder="Kısa, faydalı ve otele özgü açılış özeti."><?php echo esc_textarea( $summary ); ?></textarea>
                </label>

                <label class="sthi-field sthi-content-full"><span>Reusable Long Caption (TR)</span>
                    <textarea name="sthi_content[long_caption]" rows="7" placeholder="Hotel page, program page, cards, social and AI context için doğrulanmış, tekrar kullanılabilir uzun açıklama."><?php echo esc_textarea( $long_caption ); ?></textarea>
                    <p class="description">This is stored separately from the 900–1800 word editorial article. Do not paste the full article here.</p>
                </label>

                <div class="sthi-content-full">
                    <label class="sthi-field"><span>Long Editorial Content (TR)</span></label>
                    <?php
                    wp_editor( $long, 'sthi_editorial_long_tr', array(
                        'textarea_name' => 'sthi_content[long]',
                        'textarea_rows' => 16,
                        'media_buttons' => false,
                        'teeny'         => false,
                        'quicktags'     => true,
                    ) );
                    ?>
                    <p class="description">Use semantic H2/H3 sections. H1 is automatically downgraded to H2 to preserve one page H1. Stable inline link token example: <code>{{sthi-link:hub:umre|güncel Umre programları}}</code>. The URL is resolved at render time.</p>
                </div>

                <label class="sthi-field"><span>Content Source Mode</span>
                    <select name="sthi_content[source_mode]">
                        <?php foreach ( self::source_modes() as $value => $label ) : ?>
                            <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $source_mode ?: 'manual', $value ); ?>><?php echo esc_html( $label ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label class="sthi-field"><span>Content Verified At</span>
                    <input type="date" name="sthi_content[verified_at]" value="<?php echo esc_attr( $verified_at ); ?>">
                </label>

                <label class="sthi-field"><span>Content Verified By</span>
                    <input type="text" name="sthi_content[verified_by]" value="<?php echo esc_attr( $verified_by ); ?>" placeholder="Reviewer / staff name">
                </label>

                <div class="sthi-content-verify-actions">
                    <button type="button" class="button" data-sthi-content-verify-now>Mark verified today</button>
                    <button type="button" class="button-link-delete" data-sthi-content-clear-verify>Clear verification</button>
                </div>

                <label class="sthi-field sthi-content-full"><span>Content Source Notes</span>
                    <textarea name="sthi_content[source_notes]" rows="4" placeholder="Approved evidence/source notes, editorial verification notes, internal references."><?php echo esc_textarea( $source_notes ); ?></textarea>
                </label>

                <label class="sthi-field sthi-content-full"><span>Stable Link Intents (JSON)</span>
                    <textarea name="sthi_content[link_intents_json]" rows="7" class="code" placeholder='[{"target_type":"hub","target_id":"umre","anchor":"Umre programları"}]'><?php echo esc_textarea( $intents ? wp_json_encode( $intents, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ) : '' ); ?></textarea>
                </label>
                <div class="sthi-content-full sthi-content-link-help">
                    <strong>Stable target types</strong>
                    <code>hub:umre</code>
                    <code>hub:mekke-hotels</code>
                    <code>hub:medine-hotels</code>
                    <code>contact:primary</code>
                    <code>page:&lt;WP_POST_ID&gt;</code>
                    <code>hotel:STH-000001</code>
                    <code>program:&lt;future Program ID&gt;</code>
                    <p>Hotel/Program/Destination targets stay inactive until the relationship target has a valid public canonical URL. No link is created to draft/noindex/non-public Hotel targets.</p>
                </div>
            </div>
        </div>
        <?php
    }

    public static function save( $post_id, $post ) {
        if ( ! isset( $_POST['sthi_content_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['sthi_content_nonce'] ) ), self::NONCE_ACTION ) ) { return; }
        if ( ! current_user_can( 'edit_post', $post_id ) ) { return; }
        if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) { return; }
        if ( ! $post || 'sthi_hotel' !== $post->post_type ) { return; }

        $raw = isset( $_POST['sthi_content'] ) && is_array( $_POST['sthi_content'] ) ? wp_unslash( $_POST['sthi_content'] ) : array();

        self::save_scalar( $post_id, self::META_SUMMARY, isset( $raw['summary'] ) ? sanitize_textarea_field( $raw['summary'] ) : '' );
        self::save_scalar( $post_id, self::META_LONG, isset( $raw['long'] ) ? self::sanitize_editorial_html( $raw['long'] ) : '' );
        self::save_scalar( $post_id, self::META_LONG_CAPTION, isset( $raw['long_caption'] ) ? sanitize_textarea_field( $raw['long_caption'] ) : '' );

        $mode = isset( $raw['source_mode'] ) ? sanitize_key( $raw['source_mode'] ) : 'manual';
        if ( ! isset( self::source_modes()[ $mode ] ) ) { $mode = 'manual'; }
        self::save_scalar( $post_id, self::META_SOURCE_MODE, $mode );

        $verified_at = isset( $raw['verified_at'] ) ? self::sanitize_date( $raw['verified_at'] ) : '';
        self::save_scalar( $post_id, self::META_VERIFIED_AT, $verified_at );
        self::save_scalar( $post_id, self::META_VERIFIED_BY, isset( $raw['verified_by'] ) ? sanitize_text_field( $raw['verified_by'] ) : '' );
        self::save_scalar( $post_id, self::META_SOURCE_NOTES, isset( $raw['source_notes'] ) ? sanitize_textarea_field( $raw['source_notes'] ) : '' );

        $intents_raw = isset( $raw['link_intents_json'] ) ? trim( (string) $raw['link_intents_json'] ) : '';
        if ( '' === $intents_raw ) {
            delete_post_meta( $post_id, self::META_LINK_INTENTS );
        } else {
            $decoded = json_decode( $intents_raw, true );
            if ( JSON_ERROR_NONE === json_last_error() && is_array( $decoded ) ) {
                $normalized = self::normalize_link_intents( $decoded );
                if ( ! is_wp_error( $normalized ) ) {
                    update_post_meta( $post_id, self::META_LINK_INTENTS, $normalized );
                }
            }
        }
    }

    private static function save_scalar( $post_id, $key, $value ) {
        if ( '' === (string) $value ) { delete_post_meta( $post_id, $key ); }
        else { update_post_meta( $post_id, $key, $value ); }
    }

    public static function source_modes() {
        return array(
            'manual'      => 'Manual',
            'ai-assisted' => 'AI-assisted',
            'imported'    => 'Imported',
            'hybrid'      => 'Hybrid',
        );
    }

    public static function sanitize_date( $value ) {
        $value = trim( (string) $value );
        if ( '' === $value ) { return ''; }
        $dt = DateTime::createFromFormat( '!Y-m-d', $value );
        return ( $dt && $dt->format( 'Y-m-d' ) === $value ) ? $value : '';
    }

    public static function sanitize_editorial_html( $html ) {
        $html = (string) $html;
        // Hotel page owns the only H1. Editorial H1 is safely downgraded.
        $html = preg_replace( '~<\s*h1\b~i', '<h2', $html );
        $html = preg_replace( '~<\s*/\s*h1\s*>~i', '</h2>', $html );
        return trim( wp_kses_post( $html ) );
    }

    public static function normalize_link_intents( $items ) {
        if ( ! is_array( $items ) ) { return new WP_Error( 'bad_link_intents', 'Link intents must be a JSON array.' ); }
        $allowed = array( 'hub', 'page', 'hotel', 'destination', 'program', 'contact' );
        $out = array();
        foreach ( array_slice( array_values( $items ), 0, 8 ) as $item ) {
            if ( ! is_array( $item ) ) { continue; }
            $type = isset( $item['target_type'] ) ? sanitize_key( $item['target_type'] ) : '';
            $id   = isset( $item['target_id'] ) ? trim( sanitize_text_field( (string) $item['target_id'] ) ) : '';
            $anchor = isset( $item['anchor'] ) ? trim( sanitize_text_field( (string) $item['anchor'] ) ) : '';
            if ( ! in_array( $type, $allowed, true ) || '' === $id || '' === $anchor ) { continue; }
            if ( 'hotel' === $type ) {
                $id = strtoupper( $id );
                if ( ! preg_match( '/^STH-\d{6}$/', $id ) ) { continue; }
            }
            if ( 'page' === $type ) {
                $id = (string) absint( $id );
                if ( '0' === $id ) { continue; }
            }
            $out[] = array( 'target_type' => $type, 'target_id' => $id, 'anchor' => $anchor );
        }
        return $out;
    }

    public static function get_link_intents( $post_id ) {
        $value = get_post_meta( $post_id, self::META_LINK_INTENTS, true );
        if ( is_array( $value ) ) { return $value; }
        if ( is_string( $value ) && '' !== trim( $value ) ) {
            $decoded = json_decode( $value, true );
            return is_array( $decoded ) ? $decoded : array();
        }
        return array();
    }

    public static function has_content( $post_id ) {
        return '' !== trim( (string) get_post_meta( $post_id, self::META_SUMMARY, true ) )
            || '' !== trim( (string) get_post_meta( $post_id, self::META_LONG, true ) );
    }

    public static function is_verified( $post_id ) {
        return (bool) ( self::sanitize_date( get_post_meta( $post_id, self::META_VERIFIED_AT, true ) )
            && trim( (string) get_post_meta( $post_id, self::META_VERIFIED_BY, true ) ) );
    }

    public static function can_render( $post_id ) {
        if ( ! self::has_content( $post_id ) ) { return false; }
        // Editors can review unverified content in the noindex preview. Anonymous
        // visitors only receive approved editorial content.
        if ( is_user_logged_in() && current_user_can( 'edit_post', $post_id ) ) { return true; }
        return self::is_verified( $post_id );
    }

    public static function render( $post_id ) {
        if ( ! self::can_render( $post_id ) ) { return; }
        $summary = trim( (string) get_post_meta( $post_id, self::META_SUMMARY, true ) );
        $long    = trim( (string) get_post_meta( $post_id, self::META_LONG, true ) );
        $verified_at = self::sanitize_date( get_post_meta( $post_id, self::META_VERIFIED_AT, true ) );
        $verified_by = trim( (string) get_post_meta( $post_id, self::META_VERIFIED_BY, true ) );
        $editor_review = is_user_logged_in() && current_user_can( 'edit_post', $post_id ) && ! self::is_verified( $post_id );

        if ( $editor_review ) {
            echo '<div class="sthi-editorial-review-note" role="status">İçerik önizlemesi • Doğrulama/onarım bekliyor • Public yayında gösterilmez</div>';
        }
        if ( $summary ) {
            echo '<p class="sthi-editorial-summary">' . nl2br( esc_html( $summary ) ) . '</p>';
        }
        if ( $long ) {
            echo '<div class="sthi-editorial-copy">' . self::render_long_html( $long, $post_id ) . '</div>';
        }
        self::render_related_links( $post_id );
        if ( $verified_at && $verified_by ) {
            echo '<p class="sthi-editorial-verification"><span>İçerik kontrolü</span><strong>' . esc_html( wp_date( 'd.m.Y', strtotime( $verified_at ) ) ) . '</strong><em>' . esc_html( $verified_by ) . '</em></p>';
        }
    }

    private static function render_long_html( $html, $post_id ) {
        $html = self::sanitize_editorial_html( $html );
        $count = 0;
        $html = preg_replace_callback(
            '/\{\{sthi-link:([a-zA-Z_-]+):([^|}]+)\|([^}]+)\}\}/u',
            static function( $m ) use ( $post_id, &$count ) {
                $anchor = sanitize_text_field( $m[3] );
                if ( $count >= 8 ) { return esc_html( $anchor ); }
                $target = self::resolve_target( sanitize_key( $m[1] ), trim( $m[2] ), $post_id );
                if ( ! $target ) { return esc_html( $anchor ); }
                $count++;
                return '<a class="sthi-auto-link" href="' . esc_url( $target['url'] ) . '">' . esc_html( $anchor ) . '</a>';
            },
            $html
        );
        return wp_kses_post( wpautop( $html ) );
    }

    private static function render_related_links( $post_id ) {
        $items = array();
        foreach ( self::get_link_intents( $post_id ) as $intent ) {
            $target = self::resolve_target( $intent['target_type'], $intent['target_id'], $post_id );
            if ( ! $target ) { continue; }
            $key = md5( $target['url'] . '|' . $intent['anchor'] );
            $items[ $key ] = array( 'url' => $target['url'], 'anchor' => $intent['anchor'] );
            if ( count( $items ) >= 6 ) { break; }
        }
        if ( ! $items ) { return; }
        echo '<nav class="sthi-editorial-links" aria-label="İlgili Server Turizm bağlantıları"><span>İlgili bağlantılar</span><div>';
        foreach ( $items as $item ) {
            echo '<a href="' . esc_url( $item['url'] ) . '">' . esc_html( $item['anchor'] ) . '<b aria-hidden="true">↗</b></a>';
        }
        echo '</div></nav>';
    }

    public static function resolve_target( $type, $id, $current_post_id = 0 ) {
        $type = sanitize_key( $type );
        $id   = trim( (string) $id );
        $target_post_id = 0;

        if ( 'page' === $type ) {
            $target_post_id = absint( $id );
        } elseif ( 'hub' === $type ) {
            /*
             * Hub IDs are durable; the registry owns their current route/ID.
             * Captions store only the stable hub ID. The registry may point at a
             * WordPress object or, for an owner-approved protected route such as
             * the current Umre hub, at a centrally approved canonical path.
             *
             * IMPORTANT: future hubs are NOT enabled by URL guesswork. Their
             * allow_route_fallback flag stays false until the page is actually
             * public and approved.
             */
            $registry = apply_filters( 'sthi_link_hub_registry', array(
                'umre' => array(
                    'path'                 => '/umre-1/',
                    'allow_route_fallback' => true,
                ),
                'mekke-hotels' => array(
                    'path'                 => '/mekke-otelleri/',
                    'allow_route_fallback' => false,
                ),
                'medine-hotels' => array(
                    'path'                 => '/medine-otelleri/',
                    'allow_route_fallback' => false,
                ),
            ) );
            if ( isset( $registry[ $id ] ) ) {
                $resolved = self::resolve_registry_target( $registry[ $id ], $current_post_id, 'hub', $id );
                if ( $resolved ) { return $resolved; }
            }
            return false;
        } elseif ( 'contact' === $type && 'primary' === $id ) {
            $resolved = self::resolve_registry_target(
                array( 'path' => '/letisim/', 'allow_route_fallback' => true ),
                $current_post_id,
                'contact',
                'primary'
            );
            if ( $resolved ) { return $resolved; }
            return false;
        } elseif ( 'hotel' === $type ) {
            $ids = get_posts( array(
                'post_type' => 'sthi_hotel', 'post_status' => 'publish', 'posts_per_page' => 1, 'fields' => 'ids',
                'meta_key' => '_sthi_hotel_id', 'meta_value' => strtoupper( $id ),
            ) );
            if ( $ids ) {
                $hotel_post_id = (int) $ids[0];
                // Hotel pilot URLs are noindex by design. H9/final-indexation work can
                // explicitly enable stable Hotel-to-Hotel links after canonical policy.
                if ( apply_filters( 'sthi_hotel_link_target_is_indexable', false, $hotel_post_id ) ) {
                    $target_post_id = $hotel_post_id;
                }
            }
        } elseif ( 'program' === $type ) {
            $url = apply_filters( 'sthi_resolve_program_canonical_url', '', $id, $current_post_id );
            if ( $url && self::safe_public_url( $url, $current_post_id ) ) { return array( 'url' => esc_url_raw( $url ), 'title' => $id ); }
            return false;
        } elseif ( 'destination' === $type ) {
            $url = apply_filters( 'sthi_resolve_destination_canonical_url', '', $id, $current_post_id );
            if ( $url && self::safe_public_url( $url, $current_post_id ) ) { return array( 'url' => esc_url_raw( $url ), 'title' => $id ); }
            return false;
        }

        if ( ! $target_post_id || $target_post_id === (int) $current_post_id || ! self::is_public_post_target( $target_post_id ) ) { return false; }
        $url = wp_get_canonical_url( $target_post_id );
        if ( ! $url ) { $url = get_permalink( $target_post_id ); }
        if ( ! $url || ! self::safe_public_url( $url, $current_post_id ) ) { return false; }
        return array( 'url' => $url, 'title' => get_the_title( $target_post_id ), 'post_id' => $target_post_id );
    }

    /**
     * Resolve one centrally registered public target.
     *
     * Preferred path: registry -> WordPress object -> public/noindex checks ->
     * canonical URL. A direct route fallback is allowed only when the registry
     * explicitly marks that route as already owner-approved/public. This keeps
     * stable captions working for protected legacy routes without enabling
     * guessed future hubs.
     */
    private static function resolve_registry_target( $entry, $current_post_id, $target_type, $target_id ) {
        if ( is_string( $entry ) || is_numeric( $entry ) ) {
            $entry = array( 'path' => $entry, 'allow_route_fallback' => false );
        }
        if ( ! is_array( $entry ) ) { return false; }

        $locator = isset( $entry['post_id'] ) && absint( $entry['post_id'] )
            ? absint( $entry['post_id'] )
            : ( isset( $entry['url'] ) ? $entry['url'] : ( isset( $entry['path'] ) ? $entry['path'] : '' ) );

        $target_post_id = self::resolve_registry_post_id( $locator );
        if ( $target_post_id ) {
            // If WordPress can identify the object, it MUST pass the public and
            // noindex checks. Never bypass a known blocked/draft target.
            if ( $target_post_id === (int) $current_post_id || ! self::is_public_post_target( $target_post_id ) ) { return false; }
            $url = wp_get_canonical_url( $target_post_id );
            if ( ! $url ) { $url = get_permalink( $target_post_id ); }
            if ( ! $url || ! self::safe_public_url( $url, $current_post_id ) ) { return false; }
            return array( 'url' => $url, 'title' => get_the_title( $target_post_id ), 'post_id' => $target_post_id );
        }

        if ( empty( $entry['allow_route_fallback'] ) || '0' === (string) get_option( 'blog_public', '1' ) ) { return false; }

        $raw = isset( $entry['url'] ) ? trim( (string) $entry['url'] ) : trim( (string) ( $entry['path'] ?? '' ) );
        if ( '' === $raw ) { return false; }
        $url = preg_match( '~^https?://~i', $raw ) ? esc_url_raw( $raw ) : home_url( '/' . trim( $raw, '/' ) . '/' );
        if ( ! $url || ! self::safe_public_url( $url, $current_post_id ) ) { return false; }

        $allowed = (bool) apply_filters(
            'sthi_registered_public_route_allowed',
            true,
            $url,
            $target_type,
            $target_id,
            $current_post_id
        );
        if ( ! $allowed ) { return false; }

        return array( 'url' => $url, 'title' => $target_id, 'registered_route' => true );
    }

    /**
     * Resolve a centrally-owned registry target to a WordPress object ID.
     * Accepts a stable numeric ID, absolute URL, or site-relative path/slug.
     * This deliberately avoids hard-coding a post type into editorial content.
     */
    private static function resolve_registry_post_id( $value ) {
        if ( is_numeric( $value ) ) { return absint( $value ); }

        $value = trim( (string) $value );
        if ( '' === $value ) { return 0; }

        if ( preg_match( '~^https?://~i', $value ) ) {
            $url = esc_url_raw( $value );
        } else {
            $path = '/' . trim( $value, '/' ) . '/';
            $url  = home_url( $path );
        }

        $post_id = url_to_postid( $url );
        if ( $post_id ) { return (int) $post_id; }

        // Conservative fallback for installations where url_to_postid() cannot
        // resolve a pretty permalink. The final target still must pass the public
        // and noindex checks below before any <a> is emitted.
        $path = trim( (string) wp_parse_url( $url, PHP_URL_PATH ), '/' );
        if ( '' === $path ) { return 0; }

        $public_types = get_post_types( array( 'public' => true ), 'names' );
        $object = get_page_by_path( $path, OBJECT, array_values( $public_types ) );
        return $object ? (int) $object->ID : 0;
    }

    private static function is_public_post_target( $post_id ) {
        $post = get_post( $post_id );
        if ( ! $post || 'publish' !== $post->post_status || post_password_required( $post ) ) { return false; }
        $pto = get_post_type_object( $post->post_type );
        if ( ! $pto ) { return false; }
        if ( function_exists( 'is_post_type_viewable' ) ) {
            if ( ! is_post_type_viewable( $pto ) ) { return false; }
        } elseif ( empty( $pto->public ) && empty( $pto->publicly_queryable ) ) {
            return false;
        }
        if ( '0' === (string) get_option( 'blog_public', '1' ) ) { return false; }

        // Common SEO-plugin noindex signals. A final filter lets the site SEO layer
        // add/override provider-specific policy without coupling this plugin to it.
        if ( '1' === (string) get_post_meta( $post_id, '_yoast_wpseo_meta-robots-noindex', true ) ) { return false; }
        $rank_math = get_post_meta( $post_id, 'rank_math_robots', true );
        if ( is_array( $rank_math ) && in_array( 'noindex', $rank_math, true ) ) { return false; }
        if ( is_string( $rank_math ) && false !== stripos( $rank_math, 'noindex' ) ) { return false; }

        return (bool) apply_filters( 'sthi_internal_link_target_allowed', true, $post_id );
    }

    private static function safe_public_url( $url, $current_post_id = 0 ) {
        $url = esc_url_raw( $url );
        if ( ! $url ) { return false; }
        $current = $current_post_id ? STHI_Frontend::stable_pilot_url( $current_post_id ) : '';
        if ( $current && untrailingslashit( $current ) === untrailingslashit( $url ) ) { return false; }
        return true;
    }
}
STHI_Content::init();
