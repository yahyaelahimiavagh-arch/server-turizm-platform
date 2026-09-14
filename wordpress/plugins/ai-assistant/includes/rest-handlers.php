<?php
if (!defined('ABSPATH')) {
    exit;
}

function stai_status_handler() {
    $programs = stai_get_programs_from_sheet(true);
    $faqs = stai_get_faqs_from_sheet(true);

    return new WP_REST_Response([
        'success' => true,
        'plugin'  => 'Server Turizm AI Assistant',
        'version' => STAI_VERSION,
        'mode'    => 'safe-rebuild-with-sheet-faq-gemini',
        'sheet_url_configured' => defined('STAI_SHEET_CSV_URL') && !empty(STAI_SHEET_CSV_URL),
        'programs_count' => count($programs),
        'faq_url_configured' => defined('STAI_FAQ_CSV_URL') && !empty(STAI_FAQ_CSV_URL),
        'faq_count' => count($faqs),
        'api_key_configured' => defined('STAI_GEMINI_API_KEY') && !empty(STAI_GEMINI_API_KEY),
        'model' => defined('STAI_GEMINI_MODEL') ? STAI_GEMINI_MODEL : 'gemini-2.5-flash',
        'gemini_cooldown_remaining' => function_exists('stai_gemini_get_cooldown_remaining') ? stai_gemini_get_cooldown_remaining() : 0,
        'assets'  => [
            'css' => file_exists(STAI_PLUGIN_DIR . 'assets/stai-widget.css'),
            'js'  => file_exists(STAI_PLUGIN_DIR . 'assets/stai-widget.js'),
        ],
    ], 200);
}

function stai_chat_handler(WP_REST_Request $request) {
    $params = $request->get_json_params();
    $user_message = isset($params['message']) ? sanitize_textarea_field($params['message']) : '';

    if (empty($user_message)) {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'Message is required.',
        ], 400);
    }

    if (mb_strlen($user_message, 'UTF-8') > 1000) {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'Message is too long.',
        ], 400);
    }
    
    $antispam = stai_check_chat_antispam($user_message, $params);

if (!$antispam['allowed']) {
    return new WP_REST_Response([
        'success' => false,
        'message' => $antispam['message'],
        'retry_after' => $antispam['retry_after'],
    ], $antispam['status']);
}

    $page_url = isset($params['page_url']) ? esc_url_raw($params['page_url']) : '';

stai_log_event('message', [
    'message' => $user_message,
    'page_url' => $page_url,
    'language' => stai_detect_message_language($user_message),
]);
    $programs = stai_get_programs_from_sheet();

    if (empty($programs)) {
        return new WP_REST_Response([
            'success' => true,
            'answer' => stai_is_persian_message($user_message)
                ? "پلاگین فعال است، اما فعلاً برنامه‌ها از Google Sheet خوانده نشدند. لطفاً لینک CSV شیت را بررسی کن."
                : "Eklenti aktif, ancak Google Sheet programları şu anda okunamadı. Lütfen CSV bağlantısını kontrol edin.",
            'matched_programs_count' => 0,
            'cards' => [],
        ], 200);
    }

    $specific_program = stai_find_program_from_message($programs, $user_message);
    if (stai_is_whatsapp_request($user_message)) {
        return new WP_REST_Response([
            'success' => true,
            'answer'  => stai_build_whatsapp_answer($user_message),
            'matched_programs_count' => 0,
            'cards' => [],
        ], 200);
    }

    if (stai_is_child_price_request($user_message)) {
        return new WP_REST_Response([
            'success' => true,
            'answer'  => stai_build_child_price_answer($programs, $user_message, $specific_program),
            'cards'   => $specific_program ? [stai_program_to_card($specific_program)] : stai_programs_to_cards($programs, 5),
            'matched_programs_count' => $specific_program ? 1 : count($programs),
        ], 200);
    }

    if (stai_is_room_price_request($user_message)) {
        return new WP_REST_Response([
            'success' => true,
            'answer'  => stai_build_room_price_answer($programs, $user_message, $specific_program),
            'cards'   => $specific_program ? [stai_program_to_card($specific_program)] : stai_programs_to_cards($programs, 5),
            'matched_programs_count' => $specific_program ? 1 : count($programs),
        ], 200);
    }

    $month_number = stai_detect_month_number($user_message);

    if ($month_number !== null) {
        $month_programs = stai_get_month_programs($programs, $month_number, 6);

        return new WP_REST_Response([
            'success' => true,
            'answer'  => stai_build_month_program_answer($programs, $user_message, $month_number),
            'cards'   => stai_programs_to_cards($month_programs, 6),
            'matched_programs_count' => count($month_programs),
        ], 200);
    }
    if ($specific_program) {
        return new WP_REST_Response([
            'success' => true,
            'answer'  => stai_build_specific_program_answer($specific_program, $user_message),
            'cards'   => [
                stai_program_to_card($specific_program),
            ],
            'matched_programs_count' => 1,
        ], 200);
    }

    if (stai_is_cheapest_request($user_message)) {
        $cheapest_programs = stai_get_cheapest_programs($programs, 5);

        return new WP_REST_Response([
            'success' => true,
            'answer'  => stai_build_cheapest_program_answer($programs, $user_message),
            'cards'   => stai_programs_to_cards($cheapest_programs, 5),
            'matched_programs_count' => count($programs),
        ], 200);
    }

    if (stai_is_program_list_request($user_message)) {
        $card_programs = stai_select_programs_for_cards($programs, $user_message, 6);

        return new WP_REST_Response([
            'success' => true,
            'answer'  => stai_build_program_list_answer($programs, $user_message),
            'cards'   => stai_programs_to_cards($card_programs, 6),
            'matched_programs_count' => count($programs),
            
        ], 200);
    }
    
        $faq_answer = stai_find_faq_answer($user_message);

    if (!empty($faq_answer)) {
        return new WP_REST_Response([
            'success' => true,
            'answer'  => $faq_answer,
            'matched_programs_count' => 0,
            'cards' => [],
        ], 200);
    }
    if (!stai_get_bool_setting('gemini_enabled', true)) {
    return new WP_REST_Response([
        'success' => true,
        'answer'  => stai_get_safe_fallback_answer($user_message),
        'cards' => stai_programs_to_cards($programs, 3),
        'matched_programs_count' => count($programs),
    ], 200);
}
    
        /**
     * Gemini fallback - production with admin-only debug.
     */
    if (!defined('STAI_GEMINI_API_KEY') || empty(STAI_GEMINI_API_KEY)) {
        return new WP_REST_Response([
            'success' => true,
            'answer'  => stai_get_safe_fallback_answer($user_message),
            'cards' => stai_programs_to_cards($programs, 3),
            'matched_programs_count' => count($programs),
        ], 200);
    }
    
    if (stai_gemini_is_in_cooldown()) {
    $relevant_programs = stai_find_relevant_programs($programs, $user_message);

    return new WP_REST_Response([
        'success' => true,
        'answer'  => stai_get_safe_fallback_answer($user_message),
        'cards' => stai_programs_to_cards($relevant_programs, 3),
        'matched_programs_count' => count($relevant_programs),
    ], 200);
}
    
    $relevant_programs = stai_find_relevant_programs($programs, $user_message);
    $context = stai_build_program_context($relevant_programs);

    $ai_result = stai_ask_gemini($user_message, $context);

    if (
        is_array($ai_result) &&
        !empty($ai_result['success']) &&
        !empty($ai_result['answer'])
    ) {
        return new WP_REST_Response([
            'success' => true,
            'answer'  => $ai_result['answer'],
            'cards' => stai_programs_to_cards($relevant_programs, 3),
            'matched_programs_count' => count($relevant_programs),
        ], 200);
    }

    $answer = stai_get_safe_fallback_answer($user_message);

return new WP_REST_Response([
    'success' => true,
    'answer'  => stai_get_safe_fallback_answer($user_message),
    'cards' => stai_programs_to_cards($relevant_programs, 3),
    'matched_programs_count' => count($relevant_programs),
], 200);

return new WP_REST_Response([
    'success' => true,
    'answer'  => $answer,
    'cards' => stai_programs_to_cards($relevant_programs, 3),
    'matched_programs_count' => count($relevant_programs),
], 200);
}



function stai_track_handler(WP_REST_Request $request) {
    $params = $request->get_json_params();

    if (!is_array($params)) {
        $params = [];
    }

    $event_type = 'track';

    if (!empty($params['event_type'])) {
        $event_type = sanitize_key($params['event_type']);
    } elseif (!empty($params['event'])) {
        $event_type = sanitize_key($params['event']);
    } elseif (!empty($params['type'])) {
        $event_type = sanitize_key($params['type']);
    }

    $allowed_events = [
        'track',
        'widget_open',
        'whatsapp_click',
        'lead_open',
        'program_detail',
    ];

    if (!in_array($event_type, $allowed_events, true)) {
        $event_type = 'track';
    }

    $program_no = '';

    if (!empty($params['program_no'])) {
        $program_no = sanitize_text_field($params['program_no']);
    } elseif (!empty($params['programNo'])) {
        $program_no = sanitize_text_field($params['programNo']);
    }

    $message = isset($params['message']) ? sanitize_textarea_field($params['message']) : '';
    $page_url = isset($params['page_url']) ? esc_url_raw($params['page_url']) : '';

    stai_log_event($event_type, [
        'message' => $message,
        'program_no' => $program_no,
        'page_url' => $page_url,
        'language' => '',
        'meta' => [
            'raw_event' => $event_type,
        ],
    ]);

    return new WP_REST_Response([
        'success' => true,
        'message' => 'Track received.',
    ], 200);
}


function stai_lead_handler(WP_REST_Request $request) {
    $params = $request->get_json_params();

    if (!is_array($params)) {
        $params = [];
    }
$lead_antispam = stai_check_lead_antispam($params);

if (!$lead_antispam['allowed']) {
    return new WP_REST_Response([
        'success' => false,
        'message' => $lead_antispam['message'],
        'retry_after' => $lead_antispam['retry_after'],
    ], $lead_antispam['status']);
}
    $name = isset($params['name']) ? sanitize_text_field($params['name']) : '';
    $phone = isset($params['phone']) ? sanitize_text_field($params['phone']) : '';
    $program_no = isset($params['program_no']) ? sanitize_text_field($params['program_no']) : '';
    $note = isset($params['note']) ? sanitize_textarea_field($params['note']) : '';
    $page_url = isset($params['page_url']) ? esc_url_raw($params['page_url']) : '';

    $phone_digits = preg_replace('/\D+/', '', $phone);

    if (mb_strlen($name, 'UTF-8') < 2) {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'Lütfen adınızı yazın.',
        ], 400);
    }

    if (strlen($phone_digits) < 7) {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'Lütfen geçerli bir telefon numarası yazın.',
        ], 400);
    }

    stai_log_event('lead', [
        'message' => $note,
        'program_no' => $program_no,
        'page_url' => $page_url,
        'language' => '',
        'meta' => [
            'name' => $name,
            'phone' => $phone,
            'phone_digits' => $phone_digits,
            'program_no' => $program_no,
            'note' => $note,
        ],
    ]);
$lead_email = stai_get_setting('lead_email', '');

if (!empty($lead_email) && is_email($lead_email)) {
    $subject = 'Yeni Server Turizm AI Lead';

    $body =
        "Yeni bir müşteri formu gönderildi:\n\n" .
        "Ad Soyad: {$name}\n" .
        "Telefon: {$phone}\n" .
        "Program No: {$program_no}\n" .
        "Not: {$note}\n" .
        "Sayfa: {$page_url}\n" .
        "Tarih: " . current_time('mysql') . "\n";

    wp_mail($lead_email, $subject, $body);
}
    return new WP_REST_Response([
        'success' => true,
        'message' => 'Talebiniz alındı. Server Turizm ekibi sizinle iletişime geçecektir.',
        'data' => [
            'name' => $name,
            'phone' => $phone,
            'program_no' => $program_no,
        ],
    ], 200);
}

function stai_analytics_handler() {
    stai_maybe_create_log_table();

    $today_start = date('Y-m-d 00:00:00', current_time('timestamp'));
    $week_start = date('Y-m-d 00:00:00', current_time('timestamp') - 7 * DAY_IN_SECONDS);

    return new WP_REST_Response([
        'success' => true,
        'today' => [
            'messages' => stai_admin_get_count('message', $today_start),
            'whatsapp_clicks' => stai_admin_get_count('whatsapp_click', $today_start),
            'widget_opens' => stai_admin_get_count('widget_open', $today_start),
            'leads' => stai_admin_get_count('lead', $today_start),
        ],
        'last_7_days' => [
            'messages' => stai_admin_get_count('message', $week_start),
            'whatsapp_clicks' => stai_admin_get_count('whatsapp_click', $week_start),
            'widget_opens' => stai_admin_get_count('widget_open', $week_start),
            'leads' => stai_admin_get_count('lead', $week_start),
        ],
        'recent_messages' => stai_admin_get_recent_messages(20),
        'recent_leads' => stai_admin_get_recent_leads(20),
    ], 200);
}
