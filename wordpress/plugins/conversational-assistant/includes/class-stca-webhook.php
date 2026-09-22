<?php
if (!defined('ABSPATH')) { exit; }

final class STCA_Webhook {
    public static function init(): void {
        add_action('rest_api_init', array(__CLASS__, 'routes'));
    }

    public static function routes(): void {
        register_rest_route('server-turizm/v1', '/instagram/webhook', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array(__CLASS__, 'verify'),
                'permission_callback' => '__return_true',
            ),
            array(
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => array(__CLASS__, 'receive'),
                'permission_callback' => '__return_true',
            ),
        ));
        register_rest_route('server-turizm/v1', '/stca/simulate', array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => array(__CLASS__, 'simulate'),
            'permission_callback' => static fn() => current_user_can('manage_options'),
        ));
        register_rest_route('server-turizm/v1', '/stca/health', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array(__CLASS__, 'health'),
            'permission_callback' => static fn() => current_user_can('manage_options'),
        ));
    }

    public static function verify(WP_REST_Request $request) {
        $mode = (string) $request->get_param('hub_mode');
        $token = (string) $request->get_param('hub_verify_token');
        $challenge = (string) $request->get_param('hub_challenge');
        if ($mode === '') { $mode = (string) $request->get_param('hub.mode'); }
        if ($token === '') { $token = (string) $request->get_param('hub.verify_token'); }
        if ($challenge === '') { $challenge = (string) $request->get_param('hub.challenge'); }

        if ($mode === 'subscribe' && STCA_Config::verify_token() !== '' && hash_equals(STCA_Config::verify_token(), $token)) {
            return new WP_REST_Response($challenge, 200, array('Content-Type' => 'text/plain; charset=UTF-8'));
        }
        return new WP_REST_Response(array('success' => false, 'error' => 'verification_failed'), 403);
    }

    public static function receive(WP_REST_Request $request) {
        $raw = (string) $request->get_body();
        $signature = (string) $request->get_header('x-hub-signature-256');
        if (!STCA_Meta::verify_signature($raw, $signature)) {
            STCA_Logging::event('webhook_rejected', array('status' => 'bad_signature'));
            return new WP_REST_Response(array('success' => false), 403);
        }
        $payload = json_decode($raw, true);
        if (!is_array($payload) || ($payload['object'] ?? '') !== 'instagram') {
            return new WP_REST_Response(array('success' => true, 'ignored' => 'unsupported_object'), 200);
        }
        if (!STCA_Config::instagram_enabled()) {
            STCA_Logging::event('webhook_ignored', array('status' => 'instagram_disabled'));
            return new WP_REST_Response(array('success' => true, 'ignored' => 'instagram_disabled'), 200);
        }

        $processed = 0;
        foreach ((array) ($payload['entry'] ?? array()) as $entry) {
            foreach ((array) ($entry['messaging'] ?? array()) as $event) {
                if (self::process_event(is_array($event) ? $event : array())) {
                    $processed++;
                }
            }
        }
        return new WP_REST_Response(array('success' => true, 'processed' => $processed), 200);
    }

    private static function process_event(array $event): bool {
        $message = is_array($event['message'] ?? null) ? $event['message'] : array();
        if (!$message || !empty($message['is_echo'])) {
            return false;
        }
        $mid = sanitize_text_field((string) ($message['mid'] ?? ''));
        $sender = sanitize_text_field((string) ($event['sender']['id'] ?? ''));
        $text = trim((string) ($message['text'] ?? ''));
        if ($sender === '' || $text === '') {
            return false;
        }
        if ($mid !== '') {
            $key = 'stca_mid_' . substr(hash('sha256', $mid), 0, 40);
            if (get_transient($key)) { return false; }
            set_transient($key, 1, 2 * DAY_IN_SECONDS);
        }

        $reply = STCA_Responder::reply($text);
        $send = STCA_Meta::send_text($sender, (string) $reply['text']);
        STCA_Logging::event('message', array(
            'message_id' => $mid,
            'sender_id' => $sender,
            'message' => $text,
            'intent' => (string) $reply['intent'],
            'language' => (string) $reply['language'],
            'status' => $send['ok'] ? 'replied' : 'send_failed',
            'meta' => array(
                'match_count' => count((array) ($reply['matches'] ?? array())),
                'handoff_recommended' => (bool) ($reply['handoff_recommended'] ?? false),
                'meta_status' => (int) ($send['status'] ?? 0),
                'meta_error' => (string) ($send['error'] ?? ''),
            ),
        ));
        return true;
    }

    public static function simulate(WP_REST_Request $request) {
        $params = $request->get_json_params();
        $message = sanitize_textarea_field((string) (is_array($params) ? ($params['message'] ?? '') : ''));
        if ($message === '') {
            return new WP_REST_Response(array('success' => false, 'error' => 'message_required'), 400);
        }
        return new WP_REST_Response(array('success' => true, 'reply' => STCA_Responder::reply($message)), 200);
    }

    public static function health() {
        return new WP_REST_Response(array(
            'success' => true,
            'version' => STCA_VERSION,
            'instagram_enabled' => STCA_Config::instagram_enabled(),
            'meta_configured' => STCA_Config::configured(),
            'graph_api_version' => STCA_Config::graph_api_version(),
            'webhook_url' => STCA_Config::webhook_url(),
            'canonical_sources' => array(
                'program_intelligence' => class_exists('STPI_Store'),
                'hotel_intelligence' => post_type_exists('sthi_hotel'),
                'tour_intelligence' => function_exists('stti_get_candidates'),
            ),
            'counts' => STCA_Data::counts(),
        ), 200);
    }
}
