<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( class_exists( 'WP_Sitemaps_Provider' ) ) {
    final class STHI_Sitemap_Provider extends WP_Sitemaps_Provider {
        public function __construct() {
            $this->name = 'sthi';
            $this->object_type = 'sthi';
        }

        public function get_url_list( $page_num, $object_subtype = '' ) {
            if ( 1 !== (int) $page_num || ! class_exists( 'STHI_Public_Routes' ) || ! STHI_Public_Routes::publication_enabled() ) { return array(); }
            $urls = array();

            foreach ( STHI_Public_Routes::indexable_hub_ids() as $hub_id ) {
                $loc = STHI_Public_Routes::hub_public_url( $hub_id );
                if ( ! $loc ) { continue; }
                $item = array( 'loc' => $loc );
                $lastmod = STHI_Public_Routes::hub_lastmod( $hub_id );
                if ( $lastmod ) { $item['lastmod'] = $lastmod; }
                $urls[] = $item;
            }

            foreach ( STHI_Public_Routes::indexable_hotel_ids() as $post_id ) {
                $loc = STHI_Public_Routes::hotel_public_url( $post_id );
                if ( ! $loc ) { continue; }
                $item = array( 'loc' => $loc );
                $lastmod = STHI_Public_Routes::hotel_lastmod( $post_id );
                if ( $lastmod ) { $item['lastmod'] = $lastmod; }
                $urls[] = $item;
            }

            return $urls;
        }

        public function get_max_num_pages( $object_subtype = '' ) {
            if ( ! class_exists( 'STHI_Public_Routes' ) || ! STHI_Public_Routes::publication_enabled() ) { return 0; }
            return ( STHI_Public_Routes::indexable_hub_ids() || STHI_Public_Routes::indexable_hotel_ids() ) ? 1 : 0;
        }
    }
}

final class STHI_Sitemap {
    public static function init() { add_action( 'wp_sitemaps_init', array( __CLASS__, 'register_provider' ) ); }

    public static function register_provider() {
        if ( class_exists( 'STHI_Sitemap_Provider' ) && function_exists( 'wp_register_sitemap_provider' ) ) {
            wp_register_sitemap_provider( 'sthi', new STHI_Sitemap_Provider() );
        }
    }
}
STHI_Sitemap::init();
