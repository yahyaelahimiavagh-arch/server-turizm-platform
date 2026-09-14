<?php
/**
 * Plugin Name: Server Turizm Umre URL Consolidation
 * Description: Consolidates legacy Umre URLs into /umre-1/ and provides SEO metadata/schema for selected Umre authority pages.
 * Version: 1.2.0
 * Author: Server Turizm
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/* ==========================================================================
 * CONFIG
 * ========================================================================== */

const ST_UMRE_COMMERCIAL_HUB = '/umre-1/';
const ST_UMRE_GUIDE_PAGE_ID  = 5910;


/**
 * Canonical Server Turizm organization entity.
 */
function st_umre_organization_id() {
	return home_url( '/#organization' );
}


/**
 * Meta description used by the Umre documents authority guide.
 */
function st_umre_documents_description() {
	return '2026 Umre için gerekli belgeler, pasaport, vize, Nusuk izni ve güncel sağlık-aşı şartlarını öğrenin. Server Turizm Umre rehberi.';
}


/* ==========================================================================
 * 1. LEGACY UMRE URL CONSOLIDATION
 * ========================================================================== */

add_action(
	'template_redirect',
	function () {

		if (
			is_admin()
			||
			wp_doing_ajax()
		) {
			return;
		}


		/* --------------------------------------------------------------
		 * Resolve current request path.
		 * ----------------------------------------------------------- */

		$request_uri = $_SERVER['REQUEST_URI'] ?? '/';

		$path = parse_url(
			$request_uri,
			PHP_URL_PATH
		);

		if ( ! is_string( $path ) ) {
			return;
		}

		$path = '/' . ltrim( $path, '/' );

		$path_clean = untrailingslashit(
			$path
		);


		/* --------------------------------------------------------------
		 * NEVER redirect the canonical commercial hub itself.
		 * ----------------------------------------------------------- */

		if (
			$path_clean ===
			untrailingslashit(
				ST_UMRE_COMMERCIAL_HUB
			)
		) {
			return;
		}


		/* --------------------------------------------------------------
		 * Confirmed legacy Umre URLs.
		 * ----------------------------------------------------------- */

		$legacy_exact = array(

			'/ekonomik-umre-programlari',

			'/tour-category/luks-umre-programi',

			'/tour-category/ekonomik-umre-programi',

			'/tour-category/ramazan-umresi',

			'/category/umre-turlari',

			'/138-2',

		);


		$is_exact_legacy = in_array(
			$path_clean,
			$legacy_exact,
			true
		);


		/* --------------------------------------------------------------
		 * Old TourMaster Umre architecture.
		 *
		 * Examples:
		 * /tour/...umre.../
		 * /tour-category/...umre.../
		 * ----------------------------------------------------------- */

		$is_legacy_tourmaster_umre =
			(bool) preg_match(
				'~^/(tour|tour-category)/[^?]*umre[^?]*$~iu',
				$path_clean
			);


		/* --------------------------------------------------------------
		 * Unknown/deleted legacy Umre URLs.
		 *
		 * Only redirect if WordPress already considers the URL a 404.
		 * This prevents future legitimate Umre pages from being
		 * redirected merely because "umre" exists in their slug.
		 * ----------------------------------------------------------- */

		$is_dead_umre_url =
			is_404()
			&&
			stripos(
				$path_clean,
				'umre'
			) !== false;


		/* --------------------------------------------------------------
		 * Final redirect.
		 * ----------------------------------------------------------- */

		if (
			! $is_exact_legacy
			&&
			! $is_legacy_tourmaster_umre
			&&
			! $is_dead_umre_url
		) {
			return;
		}


		wp_safe_redirect(
			home_url(
				ST_UMRE_COMMERCIAL_HUB
			),
			301
		);

		exit;
	},
	0
);


/* ==========================================================================
 * 2. AUTHORITY GUIDE — META DESCRIPTION
 *
 * Page:
 * Umre İçin Gerekli Belgeler
 *
 * ID:
 * 5910
 * ========================================================================== */

add_action(
	'wp_head',
	function () {

		if (
			get_queried_object_id()
			!== ST_UMRE_GUIDE_PAGE_ID
		) {
			return;
		}


		printf(
			'<meta name="description" content="%s">' . "\n",
			esc_attr(
				st_umre_documents_description()
			)
		);
	},
	5
);


/* ==========================================================================
 * 3. AUTHORITY GUIDE — STRUCTURED DATA
 *
 * Graph:
 *
 * Article
 * ├── author    → /#organization
 * ├── publisher → /#organization
 * └── mainEntityOfPage
 *
 * FAQPage
 * └── Question → Answer
 *
 * Important:
 * Server Turizm is NOT recreated here as another Organization node.
 * Both author and publisher reference the site's canonical organization
 * entity:
 *
 * https://www.serverturizm.com.tr/#organization
 * ========================================================================== */

add_action(
	'wp_head',
	function () {

		if (
			get_queried_object_id()
			!== ST_UMRE_GUIDE_PAGE_ID
		) {
			return;
		}


		$page_url = get_permalink(
			ST_UMRE_GUIDE_PAGE_ID
		);

		if ( ! $page_url ) {
			return;
		}


		$organization_id =
			st_umre_organization_id();


		$schema = array(

			'@context' =>
				'https://schema.org',

			'@graph' =>
				array(


					/* ==================================================
					 * ARTICLE
					 * =============================================== */

					array(

						'@type' =>
							'Article',

						'@id' =>
							$page_url . '#article',

						'headline' =>
							'Umre İçin Gerekli Belgeler Nelerdir?',

						'description' =>
							st_umre_documents_description(),

						'mainEntityOfPage' =>
							array(

								'@type' =>
									'WebPage',

								'@id' =>
									$page_url,

							),


						/*
						 * Reference the ONE canonical Server Turizm
						 * organization entity.
						 */

						'author' =>
							array(

								'@id' =>
									$organization_id,

							),

						'publisher' =>
							array(

								'@id' =>
									$organization_id,

							),


						'datePublished' =>
							get_the_date(
								DATE_W3C,
								ST_UMRE_GUIDE_PAGE_ID
							),

						'dateModified' =>
							get_the_modified_date(
								DATE_W3C,
								ST_UMRE_GUIDE_PAGE_ID
							),

						'inLanguage' =>
							'tr-TR',

					),


					/* ==================================================
					 * FAQ PAGE
					 * =============================================== */

					array(

						'@type' =>
							'FAQPage',

						'@id' =>
							$page_url . '#faq',

						'mainEntity' =>
							array(


								/* --------------------------------------
								 * FAQ #1
								 * ----------------------------------- */

								array(

									'@type' =>
										'Question',

									'name' =>
										'Umre için pasaport kaç ay geçerli olmalı?',

									'acceptedAnswer' =>
										array(

											'@type' =>
												'Answer',

											'text' =>
												'Saudi eVisa şartlarında pasaportun Suudi Arabistan’a giriş tarihinde en az 6 ay geçerliliğinin bulunması gerekmektedir. Kullanılan vize türüne göre güncel şart ayrıca kontrol edilmelidir.',

										),

								),


								/* --------------------------------------
								 * FAQ #2
								 * ----------------------------------- */

								array(

									'@type' =>
										'Question',

									'name' =>
										'Umre için Nusuk izni gerekiyor mu?',

									'acceptedAnswer' =>
										array(

											'@type' =>
												'Answer',

											'text' =>
												'Evet. Suudi Arabistan Hac ve Umre Bakanlığı’nın güncel rehberine göre Umre ibadeti için resmi izin alınmalı ve işlem Nusuk uygulaması üzerinden gerçekleştirilmelidir.',

										),

								),


								/* --------------------------------------
								 * FAQ #3
								 * ----------------------------------- */

								array(

									'@type' =>
										'Question',

									'name' =>
										'Umre için aşı gerekiyor mu?',

									'acceptedAnswer' =>
										array(

											'@type' =>
												'Answer',

											'text' =>
												'2026 Umre sağlık şartlarında meningokok ACYW aşısı zorunlu şartlar arasındadır. Ülkeye ve yolcunun sağlık durumuna göre ek aşı veya sağlık şartları da uygulanabilir.',

										),

								),


								/* --------------------------------------
								 * FAQ #4
								 * ----------------------------------- */

								array(

									'@type' =>
										'Question',

									'name' =>
										'Umre belgeleri herkes için aynı mı?',

									'acceptedAnswer' =>
										array(

											'@type' =>
												'Answer',

											'text' =>
												'Hayır. Vatandaşlık, vize türü, yaş, sağlık durumu ve seyahat koşullarına göre gerekli belgeler değişebilir.',

										),

								),

							),

					),

				),

		);


		/* --------------------------------------------------------------
		 * JSON-LD Output
		 * ----------------------------------------------------------- */

		echo "\n";

		echo '<script type="application/ld+json" id="st-umre-documents-schema-jsonld">';

		echo wp_json_encode(
			$schema,
			JSON_UNESCAPED_UNICODE
			|
			JSON_UNESCAPED_SLASHES
		);

		echo '</script>';

		echo "\n";
	},
	30
);