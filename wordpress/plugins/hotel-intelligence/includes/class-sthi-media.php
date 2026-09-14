<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class STHI_Media {
    const META_KEY = '_sthi_gallery_items';
    const PRIMARY_META_KEY = '_sthi_primary_image_key';
    const NONCE_ACTION = 'sthi_media_nonce';

    public static function init() {
        add_action( 'add_meta_boxes_sthi_hotel', array( __CLASS__, 'add_box' ) );
        add_action( 'save_post_sthi_hotel', array( __CLASS__, 'save' ), 25, 2 );
        add_action( 'wp_ajax_sthi_media_upload', array( __CLASS__, 'ajax_upload' ) );
        add_action( 'wp_ajax_sthi_scan_folder', array( __CLASS__, 'ajax_scan_folder' ) );
    }

    public static function add_box() {
        add_meta_box(
            'sthi_media_gallery',
            'Hotel Photos & Gallery',
            array( __CLASS__, 'render_box' ),
            'sthi_hotel',
            'normal',
            'high'
        );
    }

    public static function render_box( $post ) {
        wp_nonce_field( 'sthi_save_gallery', 'sthi_gallery_nonce' );
        $items = self::get_items( $post->ID );
        $folder_url = get_post_meta( $post->ID, '_sthi_media_folder_url', true );
        $primary_key = (string) get_post_meta( $post->ID, self::PRIMARY_META_KEY, true );
        if ( ! $primary_key && ! empty( $items[0]['key'] ) ) { $primary_key = $items[0]['key']; }
        ?>
        <div class="sthi-media-manager" data-post-id="<?php echo esc_attr( $post->ID ); ?>">
            <div class="sthi-media-toolbar">
                <button type="button" class="button button-primary" id="sthi-media-library">Select from WordPress Media</button>
                <button type="button" class="button" id="sthi-media-computer">Upload from Computer / Windows</button>
                <input type="file" id="sthi-media-file-input" accept="image/*" multiple hidden>
                <span class="sthi-media-count"><strong id="sthi-media-count-number"><?php echo esc_html( count( $items ) ); ?></strong> images selected</span>
            </div>

            <div class="sthi-folder-import">
                <label for="sthi-folder-url"><strong>Import from a folder already on this hosting</strong></label>
                <div class="sthi-folder-row">
                    <input type="url" id="sthi-folder-url" class="regular-text" value="<?php echo esc_attr( $folder_url ); ?>" placeholder="https://serverturizm.com.tr/my-images/Hotels/example/">
                    <button type="button" class="button" id="sthi-scan-folder">Scan Folder</button>
                </div>
                <p class="description">Primary v0.4.2 photo source: paste one same-site hotel folder URL. The plugin scans the server filesystem directly; directory listing does not need to be enabled. Up to 250 image files are linked per scan.</p>
                <div id="sthi-folder-status" class="sthi-folder-status" aria-live="polite"></div>
            </div>

            <input type="hidden" name="sthi_gallery_json" id="sthi-gallery-json" value="<?php echo esc_attr( wp_json_encode( $items ) ); ?>">
            <input type="hidden" name="sthi_primary_image_key" id="sthi-primary-image-key" value="<?php echo esc_attr( $primary_key ); ?>">

            <div id="sthi-gallery-list" class="sthi-gallery-list" aria-label="Hotel gallery images">
                <?php foreach ( $items as $item ) { self::render_item( $item, $primary_key ); } ?>
            </div>

            <p class="description sthi-gallery-help">Choose one image as <strong>Primary</strong>. It becomes the hero image and receives first priority in Hotel schema/gallery output. Dragging changes gallery order but does not change the selected primary image.</p>
        </div>
        <?php
    }

    private static function render_item( $item, $primary_key = '' ) {
        $key   = isset( $item['key'] ) ? $item['key'] : '';
        $url   = isset( $item['thumb'] ) && $item['thumb'] ? $item['thumb'] : ( isset( $item['url'] ) ? $item['url'] : '' );
        $title = isset( $item['title'] ) ? $item['title'] : basename( (string) wp_parse_url( $url, PHP_URL_PATH ) );
        $type  = isset( $item['type'] ) ? $item['type'] : 'url';
        if ( ! $url || ! $key ) { return; }
        ?>
        <div class="sthi-gallery-item" draggable="true" data-key="<?php echo esc_attr( $key ); ?>">
            <div class="sthi-gallery-thumb"><img src="<?php echo esc_url( $url ); ?>" alt="" loading="lazy"></div>
            <div class="sthi-gallery-meta">
                <strong title="<?php echo esc_attr( $title ); ?>"><?php echo esc_html( $title ); ?></strong>
                <span><?php echo esc_html( 'attachment' === $type ? 'WordPress Media' : 'Hosting Folder' ); ?></span>
            </div>
            <button type="button" class="button sthi-gallery-primary<?php echo $key === $primary_key ? ' is-primary' : ''; ?>" aria-pressed="<?php echo $key === $primary_key ? 'true' : 'false'; ?>"><?php echo $key === $primary_key ? 'Primary image' : 'Set primary'; ?></button>
            <button type="button" class="button-link-delete sthi-gallery-remove" aria-label="Remove image">Remove</button>
            <span class="sthi-gallery-drag" aria-hidden="true">☰</span>
        </div>
        <?php
    }

    public static function get_items( $post_id ) {
        $stored = get_post_meta( $post_id, self::META_KEY, true );
        if ( ! is_array( $stored ) ) { return array(); }
        $clean = array();
        foreach ( $stored as $item ) {
            $normalized = self::normalize_item( $item );
            if ( $normalized ) { $clean[] = $normalized; }
        }
        return $clean;
    }

    /** Return gallery items with the operator-selected primary image first. */
    public static function get_display_items( $post_id ) {
        $items = self::get_items( $post_id );
        if ( count( $items ) < 2 ) { return $items; }
        $primary = (string) get_post_meta( $post_id, self::PRIMARY_META_KEY, true );
        if ( ! $primary ) { return $items; }
        foreach ( $items as $index => $item ) {
            if ( isset( $item['key'] ) && $item['key'] === $primary ) {
                if ( 0 !== $index ) {
                    $picked = $item;
                    array_splice( $items, $index, 1 );
                    array_unshift( $items, $picked );
                }
                break;
            }
        }
        return $items;
    }

    public static function get_primary_key( $post_id ) {
        return (string) get_post_meta( $post_id, self::PRIMARY_META_KEY, true );
    }

    /** Return intrinsic image width/height for attachment or approved same-site URL media. */
    public static function image_dimensions( $item ) {
        if ( ! is_array( $item ) ) { return array( 0, 0 ); }
        if ( 'attachment' === ( $item['type'] ?? '' ) && ! empty( $item['id'] ) ) {
            $src = wp_get_attachment_image_src( absint( $item['id'] ), 'full' );
            if ( is_array( $src ) && ! empty( $src[1] ) && ! empty( $src[2] ) ) {
                return array( absint( $src[1] ), absint( $src[2] ) );
            }
        }
        $url = isset( $item['url'] ) ? esc_url_raw( $item['url'] ) : '';
        if ( ! $url || ! self::is_same_site_url( $url ) ) { return array( 0, 0 ); }
        $path = self::same_site_url_to_path( $url );
        if ( is_wp_error( $path ) || ! is_file( $path ) || ! is_readable( $path ) ) { return array( 0, 0 ); }
        $size = @getimagesize( $path );
        if ( ! is_array( $size ) || empty( $size[0] ) || empty( $size[1] ) ) { return array( 0, 0 ); }
        return array( absint( $size[0] ), absint( $size[1] ) );
    }


    /**
     * Validate a same-site folder URL without scanning all image files.
     * Returns the resolved server path or WP_Error.
     */
    public static function validate_folder_url( $url ) {
        $url = esc_url_raw( trim( (string) $url ) );
        if ( ! $url || ! self::is_same_site_url( $url ) ) {
            return new WP_Error( 'invalid_folder_host', 'Use a folder URL from this Server Turizm website only.' );
        }
        $path = self::same_site_url_to_path( $url );
        if ( is_wp_error( $path ) ) { return $path; }
        if ( ! is_dir( $path ) || ! is_readable( $path ) ) {
            return new WP_Error( 'folder_unreadable', 'The media folder was not found or is not readable on the server.' );
        }
        return $path;
    }

    /**
     * Scan a hotel folder and return normalized gallery items.
     */
    public static function scan_folder_items( $url, $limit = 250 ) {
        $url = esc_url_raw( trim( (string) $url ) );
        $path = self::validate_folder_url( $url );
        if ( is_wp_error( $path ) ) { return $path; }

        $limit = max( 1, min( 500, absint( $limit ) ) );
        $base_real = realpath( $path );
        $files = array();
        try {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator( $base_real, FilesystemIterator::SKIP_DOTS ),
                RecursiveIteratorIterator::LEAVES_ONLY
            );
            foreach ( $iterator as $fileinfo ) {
                if ( count( $files ) >= $limit ) { break; }
                if ( ! $fileinfo->isFile() ) { continue; }
                $ext = strtolower( $fileinfo->getExtension() );
                if ( ! in_array( $ext, array( 'jpg', 'jpeg', 'png', 'webp', 'gif', 'avif' ), true ) ) { continue; }
                $files[] = $fileinfo->getPathname();
            }
        } catch ( Exception $e ) {
            return new WP_Error( 'folder_scan_failed', 'The server could not scan this media folder.' );
        }

        natcasesort( $files );
        $items = array();
        $base_url = trailingslashit( $url );
        foreach ( $files as $filename ) {
            $relative = ltrim( str_replace( DIRECTORY_SEPARATOR, '/', substr( $filename, strlen( $base_real ) ) ), '/' );
            $segments = array_map( 'rawurlencode', explode( '/', $relative ) );
            $file_url = $base_url . implode( '/', $segments );
            $item = self::normalize_item( array( 'type' => 'url', 'url' => $file_url, 'title' => basename( $filename ) ) );
            if ( $item ) { $items[] = $item; }
        }

        return array(
            'items'   => $items,
            'count'   => count( $items ),
            'limited' => count( $files ) >= $limit,
        );
    }

    /**
     * Make the manually supplied folder URL the URL-image source for a hotel.
     * WordPress attachment items are preserved; URL items are replaced by the folder scan.
     */
    public static function sync_folder_to_gallery( $post_id, $url ) {
        $post_id = absint( $post_id );
        if ( ! $post_id || 'sthi_hotel' !== get_post_type( $post_id ) ) {
            return new WP_Error( 'invalid_hotel', 'Invalid hotel.' );
        }

        $url = trim( (string) $url );
        $existing = self::get_items( $post_id );
        $attachments = array_values( array_filter( $existing, function( $item ) {
            return isset( $item['type'] ) && 'attachment' === $item['type'];
        } ) );

        if ( '' === $url ) {
            update_post_meta( $post_id, '_sthi_media_folder_url', '' );
            update_post_meta( $post_id, '_sthi_media_synced_folder_url', '' );
            update_post_meta( $post_id, self::META_KEY, $attachments );
            return array( 'items' => $attachments, 'count' => count( $attachments ), 'limited' => false );
        }

        $scan = self::scan_folder_items( $url, 250 );
        if ( is_wp_error( $scan ) ) { return $scan; }

        $merged = array();
        $seen = array();
        foreach ( array_merge( $attachments, $scan['items'] ) as $item ) {
            if ( empty( $item['key'] ) || isset( $seen[ $item['key'] ] ) ) { continue; }
            $seen[ $item['key'] ] = true;
            $merged[] = $item;
        }
        update_post_meta( $post_id, '_sthi_media_folder_url', esc_url_raw( $url ) );
        update_post_meta( $post_id, '_sthi_media_synced_folder_url', esc_url_raw( $url ) );
        update_post_meta( $post_id, self::META_KEY, $merged );

        return array( 'items' => $merged, 'count' => count( $merged ), 'limited' => ! empty( $scan['limited'] ) );
    }

    public static function save( $post_id, $post ) {
        if ( ! isset( $_POST['sthi_gallery_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['sthi_gallery_nonce'] ) ), 'sthi_save_gallery' ) ) { return; }
        if ( ! current_user_can( 'edit_post', $post_id ) ) { return; }
        if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) { return; }
        if ( ! isset( $_POST['sthi_gallery_json'] ) ) { return; }

        $decoded = json_decode( wp_unslash( $_POST['sthi_gallery_json'] ), true );
        if ( ! is_array( $decoded ) ) { $decoded = array(); }

        $clean = array();
        $seen = array();
        foreach ( array_slice( $decoded, 0, 500 ) as $item ) {
            $normalized = self::normalize_item( $item );
            if ( ! $normalized || isset( $seen[ $normalized['key'] ] ) ) { continue; }
            $seen[ $normalized['key'] ] = true;
            $clean[] = $normalized;
        }
        update_post_meta( $post_id, self::META_KEY, $clean );

        $primary = isset( $_POST['sthi_primary_image_key'] ) ? sanitize_text_field( wp_unslash( $_POST['sthi_primary_image_key'] ) ) : '';
        $valid_keys = array_values( array_filter( array_map( function( $item ) { return isset( $item['key'] ) ? $item['key'] : ''; }, $clean ) ) );
        if ( ! $primary || ! in_array( $primary, $valid_keys, true ) ) { $primary = $valid_keys ? $valid_keys[0] : ''; }
        update_post_meta( $post_id, self::PRIMARY_META_KEY, $primary );

        // If the operator changed the manual folder URL in the Hotel Intelligence meta box,
        // resync URL-based images once after the normal gallery save.
        $folder_url = get_post_meta( $post_id, '_sthi_media_folder_url', true );
        $synced_url = get_post_meta( $post_id, '_sthi_media_synced_folder_url', true );
        if ( (string) $folder_url !== (string) $synced_url ) {
            self::sync_folder_to_gallery( $post_id, $folder_url );
        }
    }

    private static function normalize_item( $item ) {
        if ( ! is_array( $item ) ) { return false; }
        $type = isset( $item['type'] ) ? sanitize_key( $item['type'] ) : '';

        if ( 'attachment' === $type ) {
            $id = isset( $item['id'] ) ? absint( $item['id'] ) : 0;
            if ( ! $id || 'attachment' !== get_post_type( $id ) || 0 !== strpos( (string) get_post_mime_type( $id ), 'image/' ) ) { return false; }
            $full = wp_get_attachment_url( $id );
            if ( ! $full ) { return false; }
            $thumb = wp_get_attachment_image_url( $id, 'medium' );
            return array(
                'key'   => 'attachment:' . $id,
                'type'  => 'attachment',
                'id'    => $id,
                'url'   => esc_url_raw( $full ),
                'thumb' => esc_url_raw( $thumb ?: $full ),
                'title' => sanitize_text_field( get_the_title( $id ) ?: basename( (string) wp_parse_url( $full, PHP_URL_PATH ) ) ),
            );
        }

        if ( 'url' === $type ) {
            $url = isset( $item['url'] ) ? esc_url_raw( $item['url'] ) : '';
            if ( ! $url || ! self::is_same_site_url( $url ) || ! self::is_image_url( $url ) ) { return false; }
            return array(
                'key'   => 'url:' . md5( $url ),
                'type'  => 'url',
                'url'   => $url,
                'thumb' => $url,
                'title' => isset( $item['title'] ) ? sanitize_text_field( $item['title'] ) : basename( (string) wp_parse_url( $url, PHP_URL_PATH ) ),
            );
        }

        return false;
    }

    public static function ajax_upload() {
        check_ajax_referer( self::NONCE_ACTION, 'nonce' );
        if ( ! current_user_can( 'upload_files' ) ) { wp_send_json_error( array( 'message' => 'You are not allowed to upload files.' ), 403 ); }

        $post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
        if ( ! $post_id || 'sthi_hotel' !== get_post_type( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
            wp_send_json_error( array( 'message' => 'Please save the hotel first, then upload images.' ), 400 );
        }
        if ( empty( $_FILES['files'] ) || empty( $_FILES['files']['name'] ) ) {
            wp_send_json_error( array( 'message' => 'No files received.' ), 400 );
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $files = $_FILES['files'];
        $count = is_array( $files['name'] ) ? count( $files['name'] ) : 0;
        if ( $count < 1 ) { wp_send_json_error( array( 'message' => 'No files received.' ), 400 ); }
        if ( $count > 50 ) { wp_send_json_error( array( 'message' => 'Upload up to 50 images at a time.' ), 400 ); }

        $items = array();
        $errors = array();
        for ( $i = 0; $i < $count; $i++ ) {
            if ( empty( $files['name'][ $i ] ) ) { continue; }
            $_FILES['sthi_single_file'] = array(
                'name'     => $files['name'][ $i ],
                'type'     => $files['type'][ $i ],
                'tmp_name' => $files['tmp_name'][ $i ],
                'error'    => $files['error'][ $i ],
                'size'     => $files['size'][ $i ],
            );
            $attachment_id = media_handle_upload( 'sthi_single_file', $post_id );
            if ( is_wp_error( $attachment_id ) ) {
                $errors[] = sanitize_text_field( $files['name'][ $i ] ) . ': ' . $attachment_id->get_error_message();
                continue;
            }
            $item = self::normalize_item( array( 'type' => 'attachment', 'id' => $attachment_id ) );
            if ( $item ) { $items[] = $item; }
        }
        unset( $_FILES['sthi_single_file'] );

        if ( empty( $items ) ) {
            wp_send_json_error( array( 'message' => $errors ? implode( ' | ', array_slice( $errors, 0, 3 ) ) : 'Upload failed.' ), 400 );
        }
        wp_send_json_success( array( 'items' => $items, 'errors' => $errors ) );
    }

    public static function ajax_scan_folder() {
        check_ajax_referer( self::NONCE_ACTION, 'nonce' );
        if ( ! current_user_can( 'upload_files' ) ) { wp_send_json_error( array( 'message' => 'Access denied.' ), 403 ); }

        $url = isset( $_POST['url'] ) ? esc_url_raw( wp_unslash( $_POST['url'] ) ) : '';
        $scan = self::scan_folder_items( $url, 250 );
        if ( is_wp_error( $scan ) ) {
            wp_send_json_error( array( 'message' => $scan->get_error_message() ), 400 );
        }
        wp_send_json_success( $scan );
    }

    private static function same_site_url_to_path( $url ) {
        $parts = wp_parse_url( $url );
        $site  = wp_parse_url( site_url( '/' ) );
        if ( empty( $parts['path'] ) || empty( $site['host'] ) ) { return new WP_Error( 'invalid_folder', 'Invalid folder URL.' ); }

        $site_path = isset( $site['path'] ) ? trailingslashit( $site['path'] ) : '/';
        $url_path  = rawurldecode( $parts['path'] );
        if ( '/' !== $site_path ) {
            $prefix = untrailingslashit( $site_path );
            if ( 0 !== strpos( $url_path, $prefix ) ) { return new WP_Error( 'outside_site', 'The folder URL is outside the WordPress installation path.' ); }
            $url_path = substr( $url_path, strlen( $prefix ) );
        }

        $candidate = ABSPATH . ltrim( $url_path, '/' );
        $real = realpath( $candidate );
        $root = realpath( ABSPATH );
        if ( ! $real || ! $root || 0 !== strpos( $real, $root ) ) {
            return new WP_Error( 'outside_root', 'The folder is outside the allowed website directory.' );
        }
        return $real;
    }

    private static function normalize_site_host( $host ) {
        $host = strtolower( trim( (string) $host ) );
        $host = rtrim( $host, '.' );
        if ( 0 === strpos( $host, 'www.' ) ) {
            $host = substr( $host, 4 );
        }
        return $host;
    }

    private static function is_same_site_url( $url ) {
        $host = self::normalize_site_host( wp_parse_url( $url, PHP_URL_HOST ) );
        if ( ! $host ) { return false; }

        $allowed = array_filter( array_unique( array(
            self::normalize_site_host( wp_parse_url( home_url( '/' ), PHP_URL_HOST ) ),
            self::normalize_site_host( wp_parse_url( site_url( '/' ), PHP_URL_HOST ) ),
        ) ) );

        return in_array( $host, $allowed, true );
    }

    private static function is_image_url( $url ) {
        $path = (string) wp_parse_url( $url, PHP_URL_PATH );
        $ext = strtolower( pathinfo( $path, PATHINFO_EXTENSION ) );
        return in_array( $ext, array( 'jpg', 'jpeg', 'png', 'webp', 'gif', 'avif' ), true );
    }
}
STHI_Media::init();
