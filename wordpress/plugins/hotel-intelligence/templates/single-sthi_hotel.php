<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header();
if ( have_posts() ) {
    while ( have_posts() ) {
        the_post();
        STHI_Frontend::render_page( get_the_ID() );
    }
}
get_footer();
