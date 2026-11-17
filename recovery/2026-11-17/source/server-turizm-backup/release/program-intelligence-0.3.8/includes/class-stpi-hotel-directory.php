<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class STPI_Hotel_Directory {
    public static function payload() {
        $hotels = array();
        $duplicates = array();
        $seen = array();

        if ( ! post_type_exists( 'sthi_hotel' ) ) {
            return array(
                'schema_version' => '1.0.0',
                'generated_at'   => gmdate( DATE_W3C ),
                'source'         => 'hotel_intelligence',
                'ready'          => false,
                'errors'         => array( 'Hotel Intelligence is unavailable.' ),
                'hotels'         => array(),
            );
        }

        $ids = get_posts( array(
            'post_type'      => 'sthi_hotel',
            'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'orderby'        => array( 'title' => 'ASC', 'ID' => 'ASC' ),
            'no_found_rows'  => true,
        ) );

        foreach ( $ids as $post_id ) {
            $hotel_id = strtoupper( trim( (string) get_post_meta( $post_id, '_sthi_hotel_id', true ) ) );
            if ( ! preg_match( '/^STH-\d{6}$/', $hotel_id ) ) { continue; }
            if ( isset( $seen[ $hotel_id ] ) ) { $duplicates[] = $hotel_id; }
            $seen[ $hotel_id ] = true;

            $official = trim( (string) get_post_meta( $post_id, '_sthi_official_name', true ) );
            $display = trim( (string) get_post_meta( $post_id, '_sthi_display_name_tr', true ) );
            if ( ! $display ) { $display = $official ?: get_the_title( $post_id ); }
            $aliases_raw = (string) get_post_meta( $post_id, '_sthi_alternative_names', true );
            $aliases = preg_split( '/[\r\n,;|]+/u', $aliases_raw, -1, PREG_SPLIT_NO_EMPTY );
            $aliases = array_values( array_unique( array_filter( array_map( 'trim', is_array( $aliases ) ? $aliases : array() ) ) ) );

            $url = trim( (string) apply_filters( 'sthi_hotel_public_url', '', $post_id ) );
            $images = array();
            if ( class_exists( 'STHI_Media' ) ) {
                foreach ( array_slice( STHI_Media::get_display_items( $post_id ), 0, 1 ) as $item ) {
                    if ( ! empty( $item['url'] ) ) { $images[] = esc_url_raw( $item['url'] ); }
                }
            }

            $hotels[] = array(
                'hotel_id'            => $hotel_id,
                'display_name'        => $display,
                'official_name'       => $official ?: $display,
                'aliases'             => $aliases,
                'city'                => trim( (string) get_post_meta( $post_id, '_sthi_city', true ) ),
                'verification_status' => trim( (string) get_post_meta( $post_id, '_sthi_verification_status', true ) ),
                'workflow_status'     => trim( (string) get_post_meta( $post_id, '_sthi_workflow_status', true ) ),
                'public_url'          => esc_url_raw( $url ),
                'primary_image_url'   => $images ? $images[0] : '',
                'modified_at'         => get_post_modified_time( DATE_W3C, true, $post_id ),
            );
        }

        $duplicates = array_values( array_unique( $duplicates ) );
        return array(
            'schema_version' => '1.0.0',
            'generated_at'   => gmdate( DATE_W3C ),
            'source'         => 'hotel_intelligence',
            'ready'          => empty( $duplicates ) && ! empty( $hotels ),
            'errors'         => $duplicates ? array( 'Duplicate Hotel IDs: ' . implode( ', ', $duplicates ) ) : array(),
            'hotels'         => $hotels,
        );
    }

    public static function render() {
        if ( ! current_user_can( 'manage_options' ) ) { wp_die( esc_html__( 'Insufficient permissions.', 'stpi' ) ); }
        $payload = self::payload();
        $json = wp_json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
        ?>
        <div class="wrap stpi-wrap">
            <h1>Hotel Directory Export</h1>
            <div class="stpi-lock"><strong>READ-ONLY</strong><span>This directory is generated from Hotel Intelligence and changes no hotel or program.</span></div>
            <?php if ( ! $payload['ready'] ) : ?><div class="notice notice-warning"><p><?php echo esc_html( implode( ' ', $payload['errors'] ) ?: 'No exportable hotels found.' ); ?></p></div><?php endif; ?>
            <div class="stpi-grid stpi-grid-small">
                <div class="stpi-stat"><span>Hotels</span><strong><?php echo esc_html( count( $payload['hotels'] ) ); ?></strong></div>
                <div class="stpi-stat"><span>Directory state</span><strong><?php echo $payload['ready'] ? 'READY' : 'REVIEW'; ?></strong></div>
                <div class="stpi-stat"><span>Schema</span><strong><?php echo esc_html( $payload['schema_version'] ); ?></strong></div>
                <div class="stpi-stat"><span>Writes</span><strong>0</strong></div>
            </div>
            <p><button type="button" class="button button-primary" id="stpi-copy-directory">Copy Hotel Directory JSON</button></p>
            <textarea id="stpi-directory-json" class="large-text code" rows="18" readonly><?php echo esc_textarea( $json ); ?></textarea>
            <div class="stpi-table-wrap"><table class="widefat striped"><thead><tr><th>Stable ID</th><th>Name</th><th>City</th><th>Verification</th><th>Workflow</th><th>Public URL</th></tr></thead><tbody>
            <?php foreach ( $payload['hotels'] as $hotel ) : ?><tr><td><code><?php echo esc_html( $hotel['hotel_id'] ); ?></code></td><td><?php echo esc_html( $hotel['display_name'] ); ?></td><td><?php echo esc_html( $hotel['city'] ); ?></td><td><?php echo esc_html( $hotel['verification_status'] ); ?></td><td><?php echo esc_html( $hotel['workflow_status'] ); ?></td><td><?php if ( $hotel['public_url'] ) : ?><a href="<?php echo esc_url( $hotel['public_url'] ); ?>" target="_blank" rel="noopener">Open</a><?php else : ?>—<?php endif; ?></td></tr><?php endforeach; ?>
            </tbody></table></div>
        </div>
        <script>
        document.getElementById('stpi-copy-directory').addEventListener('click', function () {
            var field = document.getElementById('stpi-directory-json');
            navigator.clipboard.writeText(field.value).then(function () {
                document.getElementById('stpi-copy-directory').textContent = 'Copied';
            });
        });
        </script>
        <?php
    }
}
