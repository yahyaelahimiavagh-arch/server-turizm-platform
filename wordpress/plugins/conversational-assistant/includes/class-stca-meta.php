<?php
if (!defined('ABSPATH')) { exit; }

final class STCA_Meta {
    public static function verify_signature(string $rawBody, string $signatureHeader): bool {
        $secret = STCA_Config::app_secret();
        if ($secret === '' || $signatureHeader === '' || strpos($signatureHeader, 'sha256=') !== 0) {
            return false;
        }
        $received = substr($signatureHeader, 7);
        $expected = hash_hmac('sha256', $rawBody, $secret);
        return hash_equals($expected, $received);
    }

    public static function send_text(string $recipientId, string $text): array {
        if (!STCA_Config::instagram_enabled() || !STCA_Config::configured()) {
            return array('ok' => false, 'status' => 0, 'error' => 'instagram_disabled_or_unconfigured');
        }
        $recipientId = trim($recipientId);
        $text = trim($text);
        if ($recipientId === '' || $text === '') {
            return array('ok' => false, 'status' => 0, 'error' => 'invalid_recipient_or_message');
        }
        if (STCA_Intent::length($text) > 950) {
            $text = STCA_Intent::slice($text, 0, 947) . '…';
        }
        $url = sprintf(
            'https://graph.instagram.com/%s/%s/messages',
            rawurlencode(STCA_Config::graph_api_version()),
            rawurlencode(STCA_Config::instagram_account_id())
        );
        $response = wp_remote_post($url, array(
            'timeout' => 20,
            'headers' => array(
                'Authorization' => 'Bearer ' . STCA_Config::access_token(),
                'Content-Type' => 'application/json',
            ),
            'body' => wp_json_encode(array(
                'recipient' => array('id' => $recipientId),
                'message' => array('text' => $text),
            ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ));
        if (is_wp_error($response)) {
            return array('ok' => false, 'status' => 0, 'error' => $response->get_error_message());
        }
        $status = (int) wp_remote_retrieve_response_code($response);
        $body = json_decode((string) wp_remote_retrieve_body($response), true);
        return array(
            'ok' => $status >= 200 && $status < 300,
            'status' => $status,
            'body' => is_array($body) ? $body : array(),
            'error' => ($status >= 200 && $status < 300) ? '' : 'meta_http_' . $status,
        );
    }
}
