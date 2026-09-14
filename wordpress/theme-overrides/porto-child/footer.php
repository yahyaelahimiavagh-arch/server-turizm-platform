<?php
global $porto_settings, $porto_layout, $porto_block_template;

$default_layout = porto_meta_default_layout();
$wrapper        = porto_get_wrapper_type();
?>
		<?php get_sidebar(); ?>
		<?php
		if ( empty( $porto_block_template ) ) :
			?>
			<?php if ( porto_get_meta_value( 'footer', true ) ) : ?>

				<?php

				$cols = 0;
				for ( $i = 1; $i <= 4; $i++ ) {
					if ( is_registered_sidebar( 'content-bottom-' . $i ) && is_active_sidebar( 'content-bottom-' . $i ) ) {
						$cols++;
					}
				}

				if ( is_404() ) {
					$cols = 0;
				}

				if ( $cols ) :
					?>
					<?php if ( 'boxed' == $wrapper || 'fullwidth' == $porto_layout || 'left-sidebar' == $porto_layout || 'right-sidebar' == $porto_layout ) : ?>
						<div class="container sidebar content-bottom-wrapper">
						<?php
					else :
						if ( 'fullwidth' == $default_layout || 'left-sidebar' == $default_layout || 'right-sidebar' == $default_layout ) :
							?>
						<div class="container sidebar content-bottom-wrapper">
						<?php else : ?>
						<div class="container-fluid sidebar content-bottom-wrapper">
							<?php
						endif;
					endif;
					?>

					<div class="row">

						<?php
						$col_class = array();
						switch ( $cols ) {
							case 1:
								$col_class[1] = 'col-md-12';
								break;
							case 2:
								$col_class[1] = 'col-md-12';
								$col_class[2] = 'col-md-12';
								break;
							case 3:
								$col_class[1] = 'col-lg-4';
								$col_class[2] = 'col-lg-4';
								$col_class[3] = 'col-lg-4';
								break;
							case 4:
								$col_class[1] = 'col-lg-3';
								$col_class[2] = 'col-lg-3';
								$col_class[3] = 'col-lg-3';
								$col_class[4] = 'col-lg-3';
								break;
						}
						?>
							<?php
							$cols = 1;
							for ( $i = 1; $i <= 4; $i++ ) {
								if ( is_registered_sidebar( 'content-bottom-' . $i ) && is_active_sidebar( 'content-bottom-' . $i ) ) {
									?>
									<div class="<?php echo esc_attr( $col_class[ $cols++ ] ); ?>">
										<?php dynamic_sidebar( 'content-bottom-' . $i ); ?>
									</div>
									<?php
								}
							}
							?>

						</div>
					</div>
				<?php endif; ?>

				</div><!-- end main -->

				<?php
				do_action( 'porto_after_main' );
				$footer_view = porto_get_meta_value( 'footer_view' );
				?>

				<div class="footer-wrapper<?php echo 'wide' == $porto_settings['footer-wrapper'] ? ' wide' : '', $footer_view ? ' ' . esc_attr( $footer_view ) : '', isset( $porto_settings['footer-reveal'] ) && $porto_settings['footer-reveal'] ? ' footer-reveal' : ''; ?>">

					<?php if ( porto_get_wrapper_type() != 'boxed' && 'boxed' == $porto_settings['footer-wrapper'] ) : ?>
					<div id="footer-boxed">
					<?php endif; ?>
					<?php $footer_id = porto_check_builder_condition( 'footer' ); ?>
					<?php if ( ! $footer_id && empty( $porto_settings['elementor_pro_footer'] ) ) : ?>
						<?php if ( isset( $porto_footer_escaped ) ) : ?>
							<?php echo porto_filter_output( $porto_footer_escaped ); ?>
						<?php else : ?>
							<?php if ( is_registered_sidebar( 'footer-top' ) && is_active_sidebar( 'footer-top' ) && ! $footer_view ) : ?>
								<div class="footer-top">
									<div class="container">
										<?php dynamic_sidebar( 'footer-top' ); ?>
									</div>
								</div>
							<?php endif; ?>

							<?php
								/* Server Turizm: legacy Porto widget footer intentionally retired. */
							?>
						<?php endif; ?>
						<?php
					else :
						echo '<footer id="footer" class="footer footer-builder">';
						if ( ( ! empty( $porto_settings['show-footer-tooltip'] ) && $porto_settings['footer-tooltip'] ) || $porto_settings['footer-ribbon'] ) {
							echo '<div class="container z-index-1">';
							if ( $porto_settings['footer-ribbon'] ) :
								?>
								<div class="footer-ribbon"><?php echo wp_kses_post( $porto_settings['footer-ribbon'] ); ?></div>
								<?php
							endif;
							get_template_part( 'footer/footer_tooltip' );
							echo '</div>';
						}
						if ( empty( $porto_settings['elementor_pro_footer'] ) ) {
							echo do_shortcode( '[porto_block id="' . intval( $footer_id ) . '"]' );
						} else {
							do_action( 'porto_elementor_pro_footer_location' );
						}
						echo '</footer>';
					endif;
					?>
<style>
.lottie-wa-link {
    position: fixed;
    bottom: 115px;
    right: 20px;
    z-index: 999999;
    width: 80px;
    height: 80px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    box-shadow: 0 4px 10px rgba(0,0,0,0.25);
    transition: all 0.3s ease;
}
.lottie-wa-link:hover {
    transform: scale(1.1);
    box-shadow: 0 6px 14px rgba(0,0,0,0.35);
}
</style>

<script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>

<a href="https://api.whatsapp.com/send?phone=905302015284" target="_blank" title="Bize Ulaşın!" class="lottie-wa-link">
    <lottie-player 
        src="https://www.serverturizm.com.tr/wp-content/uploads/c9fe54dc-aee9-11ee-905c-e372b6ec16bd.json" 
        background="transparent" 
        speed="1" 
        style="width: 100%; height: 100%;" 
        loop 
        autoplay
        mode="bounce">
    </lottie-player>
</a>
<style>
.hotline-phone-ring-wrap{position:fixed;bottom:0;left:0;z-index:999999}.hotline-phone-ring{position:relative;visibility:visible;background-color:transparent;width:110px;height:110px;cursor:pointer;z-index:11;-webkit-backface-visibility:hidden;-webkit-transform:translateZ(0);transition:visibility .5s;left:0;bottom:0;display:block}.hotline-phone-ring-circle{width:85px;height:85px;top:10px;left:10px;position:absolute;background-color:transparent;border-radius:100%;border:2px solid #e60808;-webkit-animation:phonering-alo-circle-anim 1.2s infinite ease-in-out;animation:phonering-alo-circle-anim 1.2s infinite ease-in-out;transition:all .5s;-webkit-transform-origin:50% 50%;-ms-transform-origin:50% 50%;transform-origin:50% 50%;opacity:.5}.hotline-phone-ring-circle-fill{width:55px;height:55px;top:25px;left:25px;position:absolute;background-color:rgba(230,8,8,0.7);border-radius:100%;border:2px solid transparent;-webkit-animation:phonering-alo-circle-fill-anim 2.3s infinite ease-in-out;animation:phonering-alo-circle-fill-anim 2.3s infinite ease-in-out;transition:all .5s;-webkit-transform-origin:50% 50%;-ms-transform-origin:50% 50%;transform-origin:50% 50%}.hotline-phone-ring-img-circle{background-color:#e60808;width:33px;height:33px;top:37px;left:37px;position:absolute;background-size:20px;border-radius:100%;border:2px solid transparent;-webkit-animation:phonering-alo-circle-img-anim 1s infinite ease-in-out;animation:phonering-alo-circle-img-anim 1s infinite ease-in-out;-webkit-transform-origin:50% 50%;-ms-transform-origin:50% 50%;transform-origin:50% 50%;display:-webkit-box;display:-webkit-flex;display:-ms-flexbox;display:flex;align-items:center;justify-content:center}.hotline-phone-ring-img-circle .pps-btn-img{display:-webkit-box;display:-webkit-flex;display:-ms-flexbox;display:flex;height:20px}.hotline-bar{position:absolute;background:rgba(230,8,8,0.75);height:40px;width:200px;line-height:40px;border-radius:3px;padding:0 10px;background-size:100%;cursor:pointer;transition:all .8s;-webkit-transition:all .8s;z-index:9;box-shadow:0 14px 28px rgba(0,0,0,0.25),0 10px 10px rgba(0,0,0,0.1);border-radius:50px!important;left:33px;bottom:37px}
@-webkit-keyframes phonering-alo-circle-anim{0%{-webkit-transform:rotate(0) scale(0.5) skew(1deg);-webkit-opacity:.1}30%{-webkit-transform:rotate(0) scale(0.7) skew(1deg);-webkit-opacity:.5}100%{-webkit-transform:rotate(0) scale(1) skew(1deg);-webkit-opacity:.1}}@-webkit-keyframes phonering-alo-circle-fill-anim{0%{-webkit-transform:rotate(0) scale(0.7) skew(1deg);opacity:.6}50%{-webkit-transform:rotate(0) scale(1) skew(1deg);opacity:.6}100%{-webkit-transform:rotate(0) scale(0.7) skew(1deg);opacity:.6}}@-webkit-keyframes phonering-alo-circle-img-anim{0%{-webkit-transform:rotate(0) scale(1) skew(1deg)}10%{-webkit-transform:rotate(-25deg) scale(1) skew(1deg)}20%{-webkit-transform:rotate(25deg) scale(1) skew(1deg)}30%{-webkit-transform:rotate(-25deg) scale(1) skew(1deg)}40%{-webkit-transform:rotate(25deg) scale(1) skew(1deg)}50%{-webkit-transform:rotate(0) scale(1) skew(1deg)}100%{-webkit-transform:rotate(0) scale(1) skew(1deg)}}
@media (max-width: 768px) {
.hotline-bar { display: none;}
}
.hotline-phone-ring-circle {border-color: #a29060;}
.hotline-phone-ring-circle-fill, .hotline-phone-ring-img-circle, .hotline-bar {background-color: #a29060;}
.hotline-bar {background: #a29060;}
.hotline-phone-ring-img-circle .pps-btn-img img {width: 20px;height: 20px;}
.hotline-bar > a{color:#fff;text-decoration:none;font-size:15px;font-weight:700;text-indent:50px;display:block;letter-spacing:1px;line-height:40px;font-family:Arial}
.hotline-bar > a:hover,
.hotline-bar > a:active {color: #fff;}
</style>
<div class="hotline-phone-ring-wrap">
<div class="hotline-phone-ring">
<div class="hotline-phone-ring-circle"></div>
<div class="hotline-phone-ring-circle-fill"></div>
<div class="hotline-phone-ring-img-circle">
<a href="tel:+902126210500" class="pps-btn-img"><img src="https://www.serverturizm.com.tr/wp-content/uploads/2023/10/74504.png" alt="Hotline" style="height: 20px;"/></a>
</div>
</div>
<div class="hotline-bar">
<a href="tel:+902126210500"><span class="text-hotline">+902126210500</span></a>
</div>
</div>
					<?php if ( porto_get_wrapper_type() != 'boxed' && 'boxed' == $porto_settings['footer-wrapper'] ) : ?>
					</div>
					<?php endif; ?>

				</div>
				<?php
					get_template_part( 'footer/sticky-bottom' );
				?>
			<?php else : ?>

				</div><!-- end main -->

				<?php
				do_action( 'porto_after_main' );
			endif;
			?>

			<?php if ( 'side' == porto_get_header_type() ) : ?>
				</div>
			<?php endif; ?>
		<?php endif; ?>

	</div><!-- end wrapper -->
	<?php do_action( 'porto_after_wrapper' ); ?>

<?php
if ( isset( $porto_settings['mobile-panel-type'] ) && 'side' === $porto_settings['mobile-panel-type'] && empty( $porto_block_template ) ) {
	// navigation panel
	get_template_part( 'panel' );
}

// On load popup
$popup_id = porto_check_builder_condition( 'popup' );
if ( $popup_id && empty( $_COOKIE['porto_modal_disable_onload'] ) ) {
	if ( ( function_exists( 'porto_is_vc_preview' ) && ! porto_is_vc_preview() ) &&
		( function_exists( 'porto_is_elementor_preview' ) && ! porto_is_elementor_preview() ) &&
		( ( function_exists( 'vc_is_inline' ) && ! vc_is_inline() ) || ! function_exists( 'vc_is_inline ' ) ) ) {
		$popup_options = get_post_meta( $popup_id, 'popup_options', true );
		if ( empty( $popup_options ) && empty( get_post_meta( $popup_id, 'popup_animation', true ) ) ) {
			return;
		}
		if ( empty( $popup_options ) ) {
			$popup_options = array(
				'horizontal' => 50,
				'vertical'   => 50,
			);
			if ( ! empty( get_post_meta( $popup_id, 'popup_animation', true ) ) ) {
				$popup_options['animation'] = get_post_meta( $popup_id, 'popup_animation', true );
			}
			if ( ! empty( get_post_meta( $popup_id, 'popup_width', true ) ) ) {
				$popup_options['width'] = (int) get_post_meta( $popup_id, 'popup_width', true );
			}
			if ( ! empty( get_post_meta( $popup_id, 'load_duration', true ) ) ) {
				$popup_options['load_duration'] = (int) get_post_meta( $popup_id, 'load_duration', true );
			}
		}
		$style = '';
		if ( empty( $popup_options['builder'] ) ) {

			$style .= 'width: calc(100% - ' . ( empty( $porto_settings['grid-gutter-width'] ) ? '30' : (int) $porto_settings['grid-gutter-width'] ) . 'px); max-width: ' . (int) $popup_options['width'] . 'px; ';

			if ( is_rtl() ) {
				$left  = 'right';
				$right = 'left';
			} else {
				$left  = 'left';
				$right = 'right';
			}

			if ( 50 === (int) $popup_options['horizontal'] ) {
				if ( 50 === (int) $popup_options['vertical'] ) {
					$style .= 'left: 50%;top: 50%;transform: translate(-50%, -50%);';
				} else {
					$style .= 'left: 50%;transform: translateX(-50%);';
				}
			} elseif ( 50 > (int) $popup_options['horizontal'] ) {
				$style .= $left . ':' . $popup_options['horizontal'] . '%;';
			} else {
				$style .= $right . ':' . ( 100 - $popup_options['horizontal'] ) . '%;';
			}
			if ( 50 === (int) $popup_options['vertical'] ) {
				if ( 50 !== (int) $popup_options['horizontal'] ) {
					$style .= 'top: 50%;transform: translateY(-50%);';
				}
			} elseif ( 50 > (int) $popup_options['vertical'] ) {
				$style .= 'top:' . $popup_options['vertical'] . '%;';
			} else {
				$style .= 'bottom:' . ( 100 - $popup_options['vertical'] ) . '%;';
			}
		}

		$html  = '<div data-trigger-id="popup-builder" data-extra-class="popup-builder " data-type="inline" class="porto-modal-trigger porto-onload" data-overlay-class="' . esc_attr( $popup_options['animation'] ) . '"' . ( $popup_options['load_duration'] ? ' data-timeout="' . $popup_options['load_duration'] . '"' : '' ) . '></div>';
		$html .= '<div class="mfp-hide ' . ( empty( $popup_options['builder'] ) ? 'position-absolute' : '' ) . '" id="popup-builder" style="' . $style . '" >';
		$html .= do_shortcode( '[porto_block id="' . intval( $popup_id ) . '"]' );
		$html .= '</div>';
		echo porto_filter_output( $html );
	}
}

if ( ! isset( $porto_footer_escaped ) && empty( $porto_block_template ) ) {
	wp_footer();
	echo "</body>\n</html>";
}
