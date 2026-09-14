<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * H6B stable Program <-> Hotel relation layer.
 *
 * Source of truth stays one-way:
 * Program Entity -> _stpf_hotel_refs[] -> Stable Hotel IDs.
 * Hotel -> Program usage is always derived at read time; no mirrored Program list
 * is stored on Hotel posts.
 */
final class STPF_Relations {
    public static function init() {
        add_action( 'add_meta_boxes_stpf_program', array( __CLASS__, 'add_program_box' ), 20 );
        add_action( 'add_meta_boxes_sthi_hotel', array( __CLASS__, 'add_hotel_box' ), 20 );
        add_filter( 'sthi_resolve_program_canonical_url', array( __CLASS__, 'resolve_program_for_hotel_content' ), 10, 3 );
        add_filter( 'stpf_programs_for_hotel', array( __CLASS__, 'filter_programs_for_hotel' ), 10, 3 );
    }

    public static function add_program_box() {
        add_meta_box(
            'stpf_h6b_relations',
            'Hotel Relations / H6B',
            array( __CLASS__, 'render_program_box' ),
            'stpf_program',
            'side',
            'default'
        );
    }

    public static function add_hotel_box() {
        add_meta_box(
            'stpf_h6b_reverse_relations',
            'Umre / Program Usage (Derived)',
            array( __CLASS__, 'render_hotel_box' ),
            'sthi_hotel',
            'side',
            'default'
        );
    }

    public static function render_program_box( $post ) {
        $refs = self::hotel_refs_for_program( $post->ID );
        if ( ! $refs ) {
            echo '<p>No Hotel Stable IDs are attached.</p>';
        } else {
            echo '<p class="description">Source of truth: <code>Program → hotel_refs[]</code></p><ul class="stpf-relation-list">';
            foreach ( $refs as $hotel_id ) {
                $hotel_post_id = self::find_hotel_post_id( $hotel_id );
                if ( ! $hotel_post_id ) {
                    echo '<li><code>' . esc_html( $hotel_id ) . '</code> <strong>Missing</strong></li>';
                    continue;
                }
                $title = get_the_title( $hotel_post_id );
                $edit  = get_edit_post_link( $hotel_post_id, '' );
                $preview = ( class_exists( 'STHI_Frontend' ) && is_callable( array( 'STHI_Frontend', 'stable_pilot_url' ) ) )
                    ? STHI_Frontend::stable_pilot_url( $hotel_post_id )
                    : get_preview_post_link( $hotel_post_id );
                echo '<li><code>' . esc_html( $hotel_id ) . '</code><br><strong>' . esc_html( $title ) . '</strong>';
                if ( $edit ) { echo '<br><a href="' . esc_url( $edit ) . '">Edit Hotel</a>'; }
                if ( $preview ) { echo ' · <a href="' . esc_url( $preview ) . '" target="_blank" rel="noopener">Preview</a>'; }
                echo '</li>';
            }
            echo '</ul>';
        }

        $program_id = get_post_meta( $post->ID, STPF_Program_ID::META_KEY, true );
        $target = self::public_target_status( $program_id );
        echo '<hr><p><strong>Program public target:</strong><br>';
        if ( ! empty( $target['eligible'] ) ) {
            echo '<span class="stpf-ok">Eligible</span><br><a href="' . esc_url( $target['url'] ) . '" target="_blank" rel="noopener">' . esc_html( $target['url'] ) . '</a>';
        } else {
            echo '<span class="stpf-muted">Not link-eligible</span><br><span class="description">' . esc_html( $target['reason'] ) . '</span>';
        }
        echo '</p>';
    }

    public static function render_hotel_box( $post ) {
        $hotel_id = strtoupper( trim( (string) get_post_meta( $post->ID, '_sthi_hotel_id', true ) ) );
        if ( ! self::valid_hotel_id( $hotel_id ) ) {
            echo '<p>This Hotel has no valid Stable Hotel ID.</p>';
            return;
        }

        $programs = self::programs_for_hotel( $hotel_id, true );
        echo '<p class="description">Derived live from Program <code>hotel_refs[]</code>. No Program list is stored on this Hotel.</p>';
        if ( ! $programs ) {
            echo '<p>No Program currently references <code>' . esc_html( $hotel_id ) . '</code>.</p>';
            return;
        }

        echo '<ul class="stpf-relation-list">';
        foreach ( $programs as $program ) {
            $edit = get_edit_post_link( $program['post_id'], '' );
            echo '<li><code>' . esc_html( $program['program_id'] ) . '</code><br><strong>' . esc_html( $program['title'] ) . '</strong><br>';
            echo '<span class="description">Lifecycle: ' . esc_html( $program['lifecycle'] ) . '</span>';
            if ( $edit ) { echo '<br><a href="' . esc_url( $edit ) . '">Open Program</a>'; }
            echo '</li>';
        }
        echo '</ul>';
    }

    public static function hotel_refs_for_program( $program_post_id ) {
        $refs = get_post_meta( absint( $program_post_id ), '_stpf_hotel_refs', true );
        if ( ! is_array( $refs ) ) { return array(); }
        $out = array();
        foreach ( $refs as $ref ) {
            $ref = strtoupper( trim( (string) $ref ) );
            if ( self::valid_hotel_id( $ref ) ) { $out[] = $ref; }
        }
        return array_values( array_unique( $out ) );
    }

    public static function programs_for_hotel( $hotel_id, $include_inactive = true ) {
        $hotel_id = strtoupper( trim( (string) $hotel_id ) );
        if ( ! self::valid_hotel_id( $hotel_id ) ) { return array(); }

        $ids = get_posts( array(
            'post_type'      => 'stpf_program',
            'post_status'    => array( 'draft', 'pending', 'publish', 'private' ),
            'posts_per_page' => -1,
            'orderby'        => 'ID',
            'order'          => 'ASC',
            'fields'         => 'ids',
        ) );

        $out = array();
        foreach ( $ids as $post_id ) {
            if ( ! in_array( $hotel_id, self::hotel_refs_for_program( $post_id ), true ) ) { continue; }
            $lifecycle = sanitize_key( get_post_meta( $post_id, '_stpf_lifecycle_state', true ) ?: 'draft' );
            if ( ! $include_inactive && 'active' !== $lifecycle ) { continue; }
            $program_id = get_post_meta( $post_id, STPF_Program_ID::META_KEY, true );
            if ( ! STPF_Program_ID::is_valid_format( $program_id ) ) { continue; }
            $out[] = array(
                'post_id'       => (int) $post_id,
                'program_id'    => $program_id,
                'title'         => get_the_title( $post_id ),
                'lifecycle'     => $lifecycle,
                'legacy_no'     => (string) get_post_meta( $post_id, '_stpf_legacy_program_no', true ),
                'public_target' => self::public_target_status( $program_id ),
            );
        }
        return $out;
    }

    public static function filter_programs_for_hotel( $programs, $hotel_id, $include_inactive = true ) {
        return self::programs_for_hotel( $hotel_id, (bool) $include_inactive );
    }

    public static function find_hotel_post_id( $hotel_id ) {
        $hotel_id = strtoupper( trim( (string) $hotel_id ) );
        if ( ! self::valid_hotel_id( $hotel_id ) || ! post_type_exists( 'sthi_hotel' ) ) { return 0; }
        $ids = get_posts( array(
            'post_type'      => 'sthi_hotel',
            'post_status'    => 'any',
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'meta_key'       => '_sthi_hotel_id',
            'meta_value'     => $hotel_id,
        ) );
        return $ids ? (int) $ids[0] : 0;
    }

    public static function resolve_program_for_hotel_content( $resolved_url, $program_id, $current_hotel_post_id = 0 ) {
        if ( $resolved_url ) { return $resolved_url; }
        $status = self::public_target_status( $program_id );
        if ( empty( $status['eligible'] ) ) { return ''; }
        return $status['url'];
    }

    /**
     * A Program relation does not become a crawlable link merely because the
     * relationship exists. It also needs an explicitly approved public target.
     */
    public static function public_target_status( $program_id ) {
        $program_id = strtoupper( trim( (string) $program_id ) );
        if ( ! STPF_Program_ID::is_valid_format( $program_id ) ) {
            return array( 'eligible'=>false, 'url'=>'', 'reason'=>'Invalid Stable Program ID.' );
        }
        $post_id = STPF_Program_ID::find_by_id( $program_id );
        if ( ! $post_id || 'trash' === get_post_status( $post_id ) ) {
            return array( 'eligible'=>false, 'url'=>'', 'reason'=>'Program Entity is missing or in Trash.' );
        }
        $lifecycle = sanitize_key( get_post_meta( $post_id, '_stpf_lifecycle_state', true ) ?: 'draft' );
        if ( 'active' !== $lifecycle ) {
            return array( 'eligible'=>false, 'url'=>'', 'reason'=>'Lifecycle must be active before a Program-specific public link can resolve.' );
        }

        $url = esc_url_raw( (string) get_post_meta( $post_id, '_stpf_public_url', true ) );
        if ( ! $url ) {
            return array( 'eligible'=>false, 'url'=>'', 'reason'=>'No approved public_url is assigned.' );
        }

        $home_host = strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
        $url_host  = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
        if ( ! $home_host || $home_host !== $url_host ) {
            return array( 'eligible'=>false, 'url'=>'', 'reason'=>'Program public_url must be an internal Server Turizm URL.' );
        }
        if ( '0' === (string) get_option( 'blog_public', '1' ) ) {
            return array( 'eligible'=>false, 'url'=>'', 'reason'=>'Site-wide search visibility is disabled.' );
        }

        $parts = wp_parse_url( $url );
        if ( ! is_array( $parts ) ) {
            return array( 'eligible'=>false, 'url'=>'', 'reason'=>'Program public_url could not be parsed.' );
        }
        $base = ( isset( $parts['scheme'] ) ? $parts['scheme'] . '://' : '' ) . ( $parts['host'] ?? '' );
        if ( isset( $parts['port'] ) ) { $base .= ':' . absint( $parts['port'] ); }
        $base .= isset( $parts['path'] ) ? $parts['path'] : '/';
        if ( ! empty( $parts['query'] ) ) { $base .= '?' . $parts['query']; }

        $target_post_id = url_to_postid( $base );
        if ( ! $target_post_id ) {
            $path = trim( (string) wp_parse_url( $base, PHP_URL_PATH ), '/' );
            if ( $path ) {
                $types = get_post_types( array( 'public' => true ), 'names' );
                $obj = get_page_by_path( $path, OBJECT, array_values( $types ) );
                if ( $obj ) { $target_post_id = (int) $obj->ID; }
            }
        }
        if ( ! $target_post_id || ! self::is_public_target_post( $target_post_id ) ) {
            return array( 'eligible'=>false, 'url'=>'', 'reason'=>'The assigned public_url does not resolve to an eligible published public WordPress target.' );
        }

        $canonical = wp_get_canonical_url( $target_post_id );
        if ( ! $canonical ) { $canonical = get_permalink( $target_post_id ); }
        if ( ! $canonical ) {
            return array( 'eligible'=>false, 'url'=>'', 'reason'=>'The assigned public target has no canonical/permalink.' );
        }

        // Preserve a deliberate fragment (future stable Program anchor) while
        // canonicalizing the owning public document itself.
        $final = $canonical;
        if ( ! empty( $parts['fragment'] ) ) { $final .= '#' . rawurlencode( rawurldecode( $parts['fragment'] ) ); }

        if ( ! apply_filters( 'stpf_program_public_target_allowed', true, $final, $post_id, $target_post_id ) ) {
            return array( 'eligible'=>false, 'url'=>'', 'reason'=>'Program public target was rejected by site policy.' );
        }

        return array(
            'eligible'       => true,
            'url'            => esc_url_raw( $final ),
            'reason'         => 'Eligible public target.',
            'program_post_id'=> (int) $post_id,
            'target_post_id' => (int) $target_post_id,
        );
    }

    private static function is_public_target_post( $post_id ) {
        $post = get_post( $post_id );
        if ( ! $post || 'publish' !== $post->post_status || post_password_required( $post ) ) { return false; }
        $pto = get_post_type_object( $post->post_type );
        if ( ! $pto ) { return false; }
        if ( function_exists( 'is_post_type_viewable' ) ) {
            if ( ! is_post_type_viewable( $pto ) ) { return false; }
        } elseif ( empty( $pto->public ) && empty( $pto->publicly_queryable ) ) {
            return false;
        }
        if ( '1' === (string) get_post_meta( $post_id, '_yoast_wpseo_meta-robots-noindex', true ) ) { return false; }
        $rank_math = get_post_meta( $post_id, 'rank_math_robots', true );
        if ( is_array( $rank_math ) && in_array( 'noindex', $rank_math, true ) ) { return false; }
        if ( is_string( $rank_math ) && false !== stripos( $rank_math, 'noindex' ) ) { return false; }
        return true;
    }

    private static function valid_hotel_id( $hotel_id ) {
        return (bool) preg_match( '/^STH-\d{6}$/', (string) $hotel_id );
    }

    public static function render_relations_page() {
        if ( ! current_user_can( 'manage_options' ) ) { return; }
        $ids = get_posts( array(
            'post_type'=>'stpf_program', 'post_status'=>array('draft','pending','publish','private'),
            'posts_per_page'=>-1, 'orderby'=>'ID', 'order'=>'ASC', 'fields'=>'ids'
        ) );
        ?>
        <div class="wrap stpf-wrap">
            <h1>Program ↔ Hotel Relations — H6B</h1>
            <p class="stpf-lead">One source of truth: <code>Program Entity → hotel_refs[] → Stable Hotel IDs</code>. Reverse Hotel usage below is derived live and is never stored as an independent Hotel-side Program list.</p>
            <div class="stpf-panel">
                <h2>Relationship graph</h2>
                <?php if ( ! $ids ) : ?>
                    <p>No Program Entities found.</p>
                <?php else : ?>
                    <table class="widefat striped">
                        <thead><tr><th>Program</th><th>Lifecycle</th><th>Hotel refs</th><th>Derived reverse proof</th><th>Program public target</th></tr></thead>
                        <tbody>
                        <?php foreach ( $ids as $post_id ) :
                            $program_id = get_post_meta( $post_id, STPF_Program_ID::META_KEY, true );
                            $lifecycle  = get_post_meta( $post_id, '_stpf_lifecycle_state', true ) ?: 'draft';
                            $refs       = self::hotel_refs_for_program( $post_id );
                            $target     = self::public_target_status( $program_id );
                        ?>
                            <tr>
                                <td><strong><?php echo esc_html( get_the_title( $post_id ) ); ?></strong><br><code><?php echo esc_html( $program_id ); ?></code></td>
                                <td><?php echo esc_html( $lifecycle ); ?></td>
                                <td><?php echo $refs ? esc_html( implode( ', ', $refs ) ) : '<span class="stpf-muted">None</span>'; ?></td>
                                <td>
                                    <?php if ( ! $refs ) : ?><span class="stpf-muted">No relation</span><?php else : ?>
                                        <?php foreach ( $refs as $ref ) :
                                            $reverse = self::programs_for_hotel( $ref, true );
                                            $found = false;
                                            foreach ( $reverse as $rp ) { if ( $rp['program_id'] === $program_id ) { $found = true; break; } }
                                        ?>
                                            <div><code><?php echo esc_html( $ref ); ?></code> → <?php echo $found ? '<span class="stpf-ok">derived ✓</span>' : '<span class="stpf-bad">missing</span>'; ?></div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo !empty($target['eligible']) ? '<span class="stpf-ok">Eligible</span><br><code>'.esc_html($target['url']).'</code>' : '<span class="stpf-muted">Inactive</span><br><span class="description">'.esc_html($target['reason']).'</span>'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            <div class="stpf-panel">
                <h2>H6B safety contract</h2>
                <ul>
                    <li>No mirrored Program list is written into Hotel metadata.</li>
                    <li>Reverse Hotel → Program usage is derived from Program <code>hotel_refs[]</code>.</li>
                    <li>Program-specific Hotel caption links resolve only through Stable <code>STP-xxxxxx</code> IDs.</li>
                    <li>A relationship alone does not create a public link: the Program must be lifecycle <code>active</code> and have an eligible internal <code>public_url</code>.</li>
                    <li>Draft/noindex/non-public public targets are rejected.</li>
                    <li>No Hotel or Program relationship is inferred from names, slugs, dates or approximate text similarity.</li>
                </ul>
            </div>
        </div>
        <?php
    }
}
STPF_Relations::init();
