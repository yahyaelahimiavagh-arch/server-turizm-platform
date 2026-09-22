<?php
if (!defined('ABSPATH')) { exit; }

final class STCA_Config {
    public const GRAPH_API_DEFAULT = 'v26.0';
    public const UMRah_URL = 'https://www.serverturizm.com.tr/umre-1/';
    public const PHONE = '0212 621 05 00';
    public const MOBILE = '+90 532 267 65 49';

    public static function instagram_enabled(): bool {
        return defined('STCA_INSTAGRAM_ENABLED') && STCA_INSTAGRAM_ENABLED === true;
    }

    public static function graph_api_version(): string {
        $value = defined('STCA_META_GRAPH_VERSION') ? trim((string) STCA_META_GRAPH_VERSION) : self::GRAPH_API_DEFAULT;
        return preg_match('/^v\d+\.\d+$/D', $value) ? $value : self::GRAPH_API_DEFAULT;
    }

    public static function verify_token(): string {
        return defined('STCA_META_VERIFY_TOKEN') ? trim((string) STCA_META_VERIFY_TOKEN) : '';
    }

    public static function app_secret(): string {
        return defined('STCA_META_APP_SECRET') ? trim((string) STCA_META_APP_SECRET) : '';
    }

    public static function access_token(): string {
        return defined('STCA_IG_ACCESS_TOKEN') ? trim((string) STCA_IG_ACCESS_TOKEN) : '';
    }

    public static function instagram_account_id(): string {
        return defined('STCA_IG_ACCOUNT_ID') ? trim((string) STCA_IG_ACCOUNT_ID) : '';
    }

    public static function configured(): bool {
        return self::verify_token() !== ''
            && self::app_secret() !== ''
            && self::access_token() !== ''
            && self::instagram_account_id() !== '';
    }

    public static function webhook_url(): string {
        return rest_url('server-turizm/v1/instagram/webhook');
    }

    public static function whatsapp_url(string $text = ''): string {
        $number = preg_replace('/\D+/', '', self::MOBILE);
        $url = 'https://wa.me/' . $number;
        if ($text !== '') {
            $url .= '?text=' . rawurlencode($text);
        }
        return $url;
    }
}
