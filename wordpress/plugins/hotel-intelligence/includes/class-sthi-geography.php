<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Canonical geography registry for Hotel Intelligence.
 *
 * Goals:
 * - keep Country / City values predictable across Grid + Data Bridge,
 * - understand common TR/EN/FA/AR aliases for pilot cities,
 * - auto-associate known cities with their country,
 * - keep custom cities allowed (the registry is helpful, not restrictive),
 * - sync canonical country/city into the hierarchical Destination taxonomy.
 */
final class STHI_Geography {

    public static function init() {
        add_action( 'save_post_sthi_hotel', array( __CLASS__, 'sync_post_geography' ), 55, 1 );
    }

    public static function countries() {
        $countries = array(
            'Afghanistan',
            'Albania',
            'Algeria',
            'American Samoa',
            'Andorra',
            'Angola',
            'Anguilla',
            'Antarctica',
            'Antigua and Barbuda',
            'Argentina',
            'Armenia',
            'Aruba',
            'Australia',
            'Austria',
            'Azerbaijan',
            'Bahamas',
            'Bahrain',
            'Bangladesh',
            'Barbados',
            'Belarus',
            'Belgium',
            'Belize',
            'Benin',
            'Bermuda',
            'Bhutan',
            'Bolivia, Plurinational State of',
            'Bonaire, Sint Eustatius and Saba',
            'Bosnia and Herzegovina',
            'Botswana',
            'Bouvet Island',
            'Brazil',
            'British Indian Ocean Territory',
            'Brunei Darussalam',
            'Bulgaria',
            'Burkina Faso',
            'Burundi',
            'Cabo Verde',
            'Cambodia',
            'Cameroon',
            'Canada',
            'Cayman Islands',
            'Central African Republic',
            'Chad',
            'Chile',
            'China',
            'Christmas Island',
            'Cocos (Keeling) Islands',
            'Colombia',
            'Comoros',
            'Congo',
            'Congo, The Democratic Republic of the',
            'Cook Islands',
            'Costa Rica',
            'Croatia',
            'Cuba',
            'Curaçao',
            'Cyprus',
            'Czechia',
            'Côte d\'Ivoire',
            'Denmark',
            'Djibouti',
            'Dominica',
            'Dominican Republic',
            'Ecuador',
            'Egypt',
            'El Salvador',
            'Equatorial Guinea',
            'Eritrea',
            'Estonia',
            'Eswatini',
            'Ethiopia',
            'Falkland Islands (Malvinas)',
            'Faroe Islands',
            'Fiji',
            'Finland',
            'France',
            'French Guiana',
            'French Polynesia',
            'French Southern Territories',
            'Gabon',
            'Gambia',
            'Georgia',
            'Germany',
            'Ghana',
            'Gibraltar',
            'Greece',
            'Greenland',
            'Grenada',
            'Guadeloupe',
            'Guam',
            'Guatemala',
            'Guernsey',
            'Guinea',
            'Guinea-Bissau',
            'Guyana',
            'Haiti',
            'Heard Island and McDonald Islands',
            'Holy See (Vatican City State)',
            'Honduras',
            'Hong Kong',
            'Hungary',
            'Iceland',
            'India',
            'Indonesia',
            'Iran, Islamic Republic of',
            'Iraq',
            'Ireland',
            'Isle of Man',
            'Israel',
            'Italy',
            'Jamaica',
            'Japan',
            'Jersey',
            'Jordan',
            'Kazakhstan',
            'Kenya',
            'Kiribati',
            'Korea, Democratic People\'s Republic of',
            'Korea, Republic of',
            'Kuwait',
            'Kyrgyzstan',
            'Lao People\'s Democratic Republic',
            'Latvia',
            'Lebanon',
            'Lesotho',
            'Liberia',
            'Libya',
            'Liechtenstein',
            'Lithuania',
            'Luxembourg',
            'Macao',
            'Madagascar',
            'Malawi',
            'Malaysia',
            'Maldives',
            'Mali',
            'Malta',
            'Marshall Islands',
            'Martinique',
            'Mauritania',
            'Mauritius',
            'Mayotte',
            'Mexico',
            'Micronesia, Federated States of',
            'Moldova, Republic of',
            'Monaco',
            'Mongolia',
            'Montenegro',
            'Montserrat',
            'Morocco',
            'Mozambique',
            'Myanmar',
            'Namibia',
            'Nauru',
            'Nepal',
            'Netherlands',
            'New Caledonia',
            'New Zealand',
            'Nicaragua',
            'Niger',
            'Nigeria',
            'Niue',
            'Norfolk Island',
            'North Macedonia',
            'Northern Mariana Islands',
            'Norway',
            'Oman',
            'Pakistan',
            'Palau',
            'Palestine, State of',
            'Panama',
            'Papua New Guinea',
            'Paraguay',
            'Peru',
            'Philippines',
            'Pitcairn',
            'Poland',
            'Portugal',
            'Puerto Rico',
            'Qatar',
            'Romania',
            'Russian Federation',
            'Rwanda',
            'Réunion',
            'Saint Barthélemy',
            'Saint Helena, Ascension and Tristan da Cunha',
            'Saint Kitts and Nevis',
            'Saint Lucia',
            'Saint Martin (French part)',
            'Saint Pierre and Miquelon',
            'Saint Vincent and the Grenadines',
            'Samoa',
            'San Marino',
            'Sao Tome and Principe',
            'Saudi Arabia',
            'Senegal',
            'Serbia',
            'Seychelles',
            'Sierra Leone',
            'Singapore',
            'Sint Maarten (Dutch part)',
            'Slovakia',
            'Slovenia',
            'Solomon Islands',
            'Somalia',
            'South Africa',
            'South Georgia and the South Sandwich Islands',
            'South Sudan',
            'Spain',
            'Sri Lanka',
            'Sudan',
            'Suriname',
            'Svalbard and Jan Mayen',
            'Sweden',
            'Switzerland',
            'Syrian Arab Republic',
            'Taiwan, Province of China',
            'Tajikistan',
            'Tanzania, United Republic of',
            'Thailand',
            'Timor-Leste',
            'Togo',
            'Tokelau',
            'Tonga',
            'Trinidad and Tobago',
            'Tunisia',
            'Turkmenistan',
            'Turks and Caicos Islands',
            'Tuvalu',
            'Türkiye',
            'Uganda',
            'Ukraine',
            'United Arab Emirates',
            'United Kingdom',
            'United States',
            'United States Minor Outlying Islands',
            'Uruguay',
            'Uzbekistan',
            'Vanuatu',
            'Venezuela, Bolivarian Republic of',
            'Viet Nam',
            'Virgin Islands, British',
            'Virgin Islands, U.S.',
            'Wallis and Futuna',
            'Western Sahara',
            'Yemen',
            'Zambia',
            'Zimbabwe',
            'Åland Islands',
        );
        return apply_filters( 'sthi_country_registry', $countries );
    }

    public static function country_aliases() {
        $aliases = array(
            'Saudi Arabia' => array( 'Saudi Arabia', 'KSA', 'Kingdom of Saudi Arabia', 'Suudi Arabistan', 'Suudi Arabistan Krallığı', 'عربستان سعودی', 'المملكة العربية السعودية', 'السعودية' ),
            'Türkiye'      => array( 'Türkiye', 'Turkey', 'Turkiye', 'Türkiye Cumhuriyeti', 'ترکیه', 'تركيا' ),
            'Egypt'        => array( 'Egypt', 'Mısır', 'Misir', 'مصر', 'مصر عربی' ),
            'Uzbekistan'   => array( 'Uzbekistan', 'Özbekistan', 'Ozbekistan', 'ازبکستان', 'أوزبكستان' ),
            'United Arab Emirates' => array( 'United Arab Emirates', 'UAE', 'Emirates', 'Birleşik Arap Emirlikleri', 'امارات', 'امارات متحده عربی', 'الإمارات العربية المتحدة' ),
            'Azerbaijan'   => array( 'Azerbaijan', 'Azerbaycan', 'آذربایجان', 'أذربيجان' ),
            'Iran, Islamic Republic of' => array( 'Iran, Islamic Republic of', 'Iran', 'İran', 'ایران', 'إيران' ),
        );
        return apply_filters( 'sthi_country_alias_registry', $aliases );
    }

    public static function cities() {
        $cities = array(
            'makkah' => array(
                'name'    => 'Makkah',
                'country' => 'Saudi Arabia',
                'aliases' => array( 'Makkah', 'Mekke', 'Mecca', 'Macca', 'Makkah Al Mukarramah', 'Makkah al-Mukarramah', 'مكة', 'مكه', 'مكة المكرمة', 'مكه المكرمه', 'مکه', 'مکّه', 'مکه مکرمه' ),
            ),
            'madinah' => array(
                'name'    => 'Madinah',
                'country' => 'Saudi Arabia',
                'aliases' => array( 'Madinah', 'Medinah', 'Medina', 'Medine', 'Madina', 'Al Madinah', 'Al Madinah Al Munawwarah', 'المدينة', 'المدينه', 'المدينة المنورة', 'المدينه المنوره', 'مدینه', 'مدينه', 'مدینه منوره' ),
            ),
            'jeddah' => array(
                'name' => 'Jeddah', 'country' => 'Saudi Arabia',
                'aliases' => array( 'Jeddah', 'Cidde', 'Jidda', 'جدّة', 'جدة', 'جده', 'جده عربستان' ),
            ),
            'riyadh' => array(
                'name' => 'Riyadh', 'country' => 'Saudi Arabia',
                'aliases' => array( 'Riyadh', 'Riyad', 'ریاض', 'الرياض' ),
            ),
            'cairo' => array(
                'name' => 'Cairo', 'country' => 'Egypt',
                'aliases' => array( 'Cairo', 'Kahire', 'Kahira', 'Kahirah', 'قاهرة', 'القاهرة', 'قاهره' ),
            ),
            'giza' => array(
                'name' => 'Giza', 'country' => 'Egypt',
                'aliases' => array( 'Giza', 'Gize', 'الجيزة', 'الجيزه', 'جیزه' ),
            ),
            'istanbul' => array(
                'name' => 'Istanbul', 'country' => 'Türkiye',
                'aliases' => array( 'Istanbul', 'İstanbul', 'استانبول', 'إسطنبول' ),
            ),
            'ankara' => array(
                'name' => 'Ankara', 'country' => 'Türkiye',
                'aliases' => array( 'Ankara', 'آنکارا', 'أنقرة' ),
            ),
            'antalya' => array(
                'name' => 'Antalya', 'country' => 'Türkiye',
                'aliases' => array( 'Antalya', 'آنتالیا', 'أنطاليا' ),
            ),
            'izmir' => array(
                'name' => 'Izmir', 'country' => 'Türkiye',
                'aliases' => array( 'Izmir', 'İzmir', 'ازمیر', 'إزمير' ),
            ),
            'trabzon' => array(
                'name' => 'Trabzon', 'country' => 'Türkiye',
                'aliases' => array( 'Trabzon', 'ترابزون', 'طرابزون' ),
            ),
            'samsun' => array(
                'name' => 'Samsun', 'country' => 'Türkiye',
                'aliases' => array( 'Samsun', 'سامسون' ),
            ),
            'tashkent' => array(
                'name' => 'Tashkent', 'country' => 'Uzbekistan',
                'aliases' => array( 'Tashkent', 'Taşkent', 'Toshkent', 'تاشکند', 'طشقند' ),
            ),
            'samarkand' => array(
                'name' => 'Samarkand', 'country' => 'Uzbekistan',
                'aliases' => array( 'Samarkand', 'Semerkant', 'Samarqand', 'سمرقند' ),
            ),
            'bukhara' => array(
                'name' => 'Bukhara', 'country' => 'Uzbekistan',
                'aliases' => array( 'Bukhara', 'Buhara', 'Buxoro', 'بخارا' ),
            ),
            'dubai' => array(
                'name' => 'Dubai', 'country' => 'United Arab Emirates',
                'aliases' => array( 'Dubai', 'دبی', 'دبي' ),
            ),
            'abu-dhabi' => array(
                'name' => 'Abu Dhabi', 'country' => 'United Arab Emirates',
                'aliases' => array( 'Abu Dhabi', 'Abu Dabi', 'ابوظبی', 'أبو ظبي' ),
            ),
            'baku' => array(
                'name' => 'Baku', 'country' => 'Azerbaijan',
                'aliases' => array( 'Baku', 'Bakü', 'Bakı', 'باکو' ),
            ),
            'tehran' => array(
                'name' => 'Tehran', 'country' => 'Iran, Islamic Republic of',
                'aliases' => array( 'Tehran', 'Tahran', 'تهران' ),
            ),
            'mashhad' => array(
                'name' => 'Mashhad', 'country' => 'Iran, Islamic Republic of',
                'aliases' => array( 'Mashhad', 'Meşhed', 'مشهد' ),
            ),
        );
        return apply_filters( 'sthi_city_registry', $cities );
    }

    public static function city_names() {
        $out = array();
        foreach ( self::cities() as $spec ) {
            if ( ! empty( $spec['name'] ) ) { $out[] = $spec['name']; }
        }
        natcasesort( $out );
        return array_values( array_unique( $out ) );
    }

    public static function canonical_city( $value ) {
        $value = trim( sanitize_text_field( (string) $value ) );
        if ( '' === $value ) { return ''; }
        $needle = self::normalize( $value );
        foreach ( self::cities() as $spec ) {
            $aliases = array_merge( array( $spec['name'] ), isset( $spec['aliases'] ) ? (array) $spec['aliases'] : array() );
            foreach ( $aliases as $alias ) {
                if ( $needle === self::normalize( $alias ) ) { return (string) $spec['name']; }
            }
        }
        return $value; // custom city remains allowed.
    }

    public static function city_key( $value ) {
        $needle = self::normalize( $value );
        if ( '' === $needle ) { return ''; }
        foreach ( self::cities() as $key => $spec ) {
            $aliases = array_merge( array( $spec['name'] ), isset( $spec['aliases'] ) ? (array) $spec['aliases'] : array() );
            foreach ( $aliases as $alias ) {
                if ( $needle === self::normalize( $alias ) ) { return (string) $key; }
            }
        }
        return sanitize_title( $value );
    }

    public static function canonical_country( $value ) {
        $value = trim( sanitize_text_field( (string) $value ) );
        if ( '' === $value ) { return ''; }
        $needle = self::normalize( $value );
        foreach ( self::country_aliases() as $canonical => $aliases ) {
            foreach ( array_merge( array( $canonical ), (array) $aliases ) as $alias ) {
                if ( $needle === self::normalize( $alias ) ) { return $canonical; }
            }
        }
        foreach ( self::countries() as $country ) {
            if ( $needle === self::normalize( $country ) ) { return $country; }
        }
        return $value; // custom/legacy values remain possible.
    }

    public static function country_for_city( $city ) {
        $key = self::city_key( $city );
        $cities = self::cities();
        return isset( $cities[ $key ]['country'] ) ? (string) $cities[ $key ]['country'] : '';
    }

    public static function normalize_meta_value( $meta_key, $value ) {
        if ( '_sthi_city' === $meta_key ) { return self::canonical_city( $value ); }
        if ( '_sthi_country' === $meta_key ) { return self::canonical_country( $value ); }
        return $value;
    }

    public static function sync_post_geography( $post_id ) {
        $post_id = absint( $post_id );
        if ( ! $post_id || 'sthi_hotel' !== get_post_type( $post_id ) || 'trash' === get_post_status( $post_id ) ) { return; }

        $city_raw = (string) get_post_meta( $post_id, '_sthi_city', true );
        $country_raw = (string) get_post_meta( $post_id, '_sthi_country', true );
        $city = self::canonical_city( $city_raw );
        $country = self::canonical_country( $country_raw );
        if ( ! $country && $city ) { $country = self::country_for_city( $city ); }

        if ( $city && $city !== $city_raw ) { update_post_meta( $post_id, '_sthi_city', $city ); }
        if ( $country && $country !== $country_raw ) { update_post_meta( $post_id, '_sthi_country', $country ); }

        if ( ! taxonomy_exists( 'sthi_destination' ) ) { return; }
        $append_ids = array();
        $parent_id = 0;
        if ( $country ) {
            $country_term = term_exists( $country, 'sthi_destination' );
            if ( ! $country_term ) { $country_term = wp_insert_term( $country, 'sthi_destination' ); }
            if ( ! is_wp_error( $country_term ) ) {
                $parent_id = (int) ( is_array( $country_term ) ? $country_term['term_id'] : $country_term );
                $append_ids[] = $parent_id;
            }
        }
        if ( $city ) {
            $city_term = term_exists( $city, 'sthi_destination', $parent_id );
            if ( ! $city_term ) {
                $args = $parent_id ? array( 'parent' => $parent_id ) : array();
                $city_term = wp_insert_term( $city, 'sthi_destination', $args );
            }
            if ( ! is_wp_error( $city_term ) ) {
                $append_ids[] = (int) ( is_array( $city_term ) ? $city_term['term_id'] : $city_term );
            }
        }
        if ( $append_ids ) { wp_set_object_terms( $post_id, array_values( array_unique( $append_ids ) ), 'sthi_destination', true ); }
    }

    private static function normalize( $value ) {
        $value = trim( (string) $value );
        if ( function_exists( 'mb_strtolower' ) ) { $value = mb_strtolower( $value, 'UTF-8' ); }
        else { $value = strtolower( $value ); }
        $value = remove_accents( $value );
        $value = strtr( $value, array(
            'ك' => 'ک', 'ي' => 'ی', 'ى' => 'ی', 'ئ' => 'ی', 'ة' => 'ه', 'ۀ' => 'ه',
            'أ' => 'ا', 'إ' => 'ا', 'آ' => 'ا', 'ؤ' => 'و', '‌' => ' ',
            'َ' => '', 'ِ' => '', 'ُ' => '', 'ّ' => '', 'ْ' => '', 'ً' => '', 'ٍ' => '', 'ٌ' => '',
        ) );
        $value = preg_replace( '/[\p{P}\p{S}]+/u', ' ', $value );
        return trim( preg_replace( '/\s+/u', ' ', $value ) );
    }
}
STHI_Geography::init();
