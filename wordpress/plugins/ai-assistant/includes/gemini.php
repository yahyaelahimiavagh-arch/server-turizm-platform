<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Select relevant programs before sending context to Gemini.
 */
function stai_find_relevant_programs($programs, $message) {
    $message_lower = mb_strtolower((string) $message, 'UTF-8');
    $scored = [];

    foreach ($programs as $program) {
        $score = 0;

        $haystack = mb_strtolower(
            $program['program_no'] . ' ' .
            $program['type'] . ' ' .
            $program['title'] . ' ' .
            $program['duration'] . ' ' .
            $program['medinah_hotel'] . ' ' .
            $program['makkah_hotel'] . ' ' .
            $program['important_notes'],
            'UTF-8'
        );

        if (!empty($program['program_no'])) {
            $program_no_lower = mb_strtolower((string) $program['program_no'], 'UTF-8');

            if (mb_strpos($message_lower, $program_no_lower) !== false) {
                $score += 30;
            }
        }

        if (
            mb_strpos($message_lower, 'lüks') !== false ||
            mb_strpos($message_lower, 'luks') !== false ||
            mb_strpos($message_lower, 'لوکس') !== false
        ) {
            if (mb_strpos(mb_strtolower((string) $program['type'], 'UTF-8'), 'lüks') !== false) {
                $score += 10;
            }
        }

        if (
            mb_strpos($message_lower, 'eko') !== false ||
            mb_strpos($message_lower, 'ekonomik') !== false ||
            mb_strpos($message_lower, 'اقتصادی') !== false ||
            mb_strpos($message_lower, 'ارزان') !== false
        ) {
            if (
                mb_strpos(mb_strtolower((string) $program['type'], 'UTF-8'), 'eko') !== false ||
                mb_strpos(mb_strtolower((string) $program['type'], 'UTF-8'), 'ekonomik') !== false
            ) {
                $score += 10;
            }
        }

        $keywords = preg_split('/\s+/u', $message_lower);

        foreach ($keywords as $keyword) {
            $keyword = trim($keyword);

            if (mb_strlen($keyword, 'UTF-8') < 3) {
                continue;
            }

            if (mb_strpos($haystack, $keyword) !== false) {
                $score += 1;
            }
        }

        if (
            mb_strpos($message_lower, 'ucuz') !== false ||
            mb_strpos($message_lower, 'uygun') !== false ||
            mb_strpos($message_lower, 'ارزان') !== false ||
            mb_strpos($message_lower, 'مناسب') !== false
        ) {
            $price = stai_price_to_number($program['price_quad']);

            if ($price > 0 && $price < 999999999) {
                $score += max(0, 20 - ($price / 100));
            }
        }

        $scored[] = [
            'score' => $score,
            'program' => $program,
        ];
    }

    usort($scored, function ($a, $b) {
        return $b['score'] <=> $a['score'];
    });

    $selected = [];

    foreach ($scored as $item) {
        if ($item['score'] > 0) {
            $selected[] = $item['program'];
        }

        if (count($selected) >= 8) {
            break;
        }
    }

    if (empty($selected)) {
        $selected = array_slice($programs, 0, 8);
    }

    return $selected;
}

function stai_build_program_context($programs) {
    $lines = [];

    foreach ($programs as $p) {
        $lines[] =
            "Program No: {$p['program_no']}\n" .
            "Tür: {$p['type']}\n" .
            "Başlık: {$p['title']}\n" .
            "Gidiş Tarihi: {$p['departure']}\n" .
            "Ara Geçiş Tarihi: {$p['middle_transfer']}\n" .
            "Dönüş Tarihi: {$p['return_date']}\n" .
            "Süre: {$p['duration']}\n" .
            "Medine Oteli: {$p['medinah_hotel']}\n" .
            "Mekke Oteli: {$p['makkah_hotel']}\n" .
            "2 Kişilik Oda Fiyatı: " . stai_format_price($p['price_double']) . "\n" .
            "3 Kişilik Oda Fiyatı: " . stai_format_price($p['price_triple']) . "\n" .
            "4 Kişilik Oda Fiyatı: " . stai_format_price($p['price_quad']) . "\n" .
            "Çocuk Ücretleri: {$p['child_prices']}\n" .
            "Kontenjan: {$p['quota']}\n" .
            "Doluluk Durumu: {$p['is_full']}\n" .
            "Önemli Notlar: {$p['important_notes']}\n" .
            "Telefon: {$p['phone']}\n";
    }

    return implode("\n---\n", $lines);
}

/**
 * Gemini API call.
 */
function stai_ask_gemini($user_message, $context) {
    if (!defined('STAI_GEMINI_API_KEY') || empty(STAI_GEMINI_API_KEY)) {
        return [
            'success' => false,
            'answer' => '',
            'debug' => [
                'type' => 'missing_api_key',
            ],
        ];
    }

    $model = defined('STAI_GEMINI_MODEL') && STAI_GEMINI_MODEL
        ? STAI_GEMINI_MODEL
        : 'gemini-2.5-flash';

    $api_url = 'https://generativelanguage.googleapis.com/v1beta/models/' . rawurlencode($model) . ':generateContent';

    $system_prompt = "
You are the official AI assistant of Server Turizm.

You answer questions about Umrah tour programs using ONLY the provided tour data.

Rules:
- Answer in the same language as the user.
- If the user writes in Turkish, answer in Turkish.
- If the user writes in Persian, answer in Persian.
- Be concise, friendly, and sales-oriented.
- Do not invent prices, dates, hotel names, flight details, quota, or availability.
- If exact information is missing, say that the sales team should confirm it.
- Always mention that final reservation and availability must be confirmed by Server Turizm.
- Prices are in USD unless the data says otherwise.
- If multiple programs match, compare them clearly.
- If the user wants booking, guide them to contact Server Turizm.
";

    $prompt = $system_prompt . "\n\nTOUR DATA:\n" . $context . "\n\nUSER QUESTION:\n" . $user_message;

    $payload = [
        'contents' => [
            [
                'parts' => [
                    [
                        'text' => $prompt,
                    ],
                ],
            ],
        ],
        'generationConfig' => [
            'temperature' => 0.25,
            'maxOutputTokens' => 1200,
        ],
    ];

    $response = wp_remote_post($api_url, [
        'headers' => [
            'Content-Type'   => 'application/json',
            'x-goog-api-key' => STAI_GEMINI_API_KEY,
        ],
        'body'    => wp_json_encode($payload),
        'timeout' => 40,
    ]);

    if (is_wp_error($response)) {
        return [
            'success' => false,
            'answer' => '',
            'debug' => [
                'type' => 'wp_error',
                'message' => $response->get_error_message(),
            ],
        ];
    }

    $status = wp_remote_retrieve_response_code($response);
    $raw_body = wp_remote_retrieve_body($response);
    $body = json_decode($raw_body, true);

    if ($status < 200 || $status >= 300) {
    if ((int) $status === 429) {
        $retry_seconds = stai_gemini_extract_retry_seconds($body);

        /*
         * Free-tier quota can be very limited, so we use at least 30 minutes
         * even if Google says retry after a few seconds.
         */
        stai_gemini_set_cooldown(max($retry_seconds, 30 * MINUTE_IN_SECONDS));
    }

    if ((int) $status >= 500) {
        stai_gemini_set_cooldown(5 * MINUTE_IN_SECONDS);
    }

    return [
        'success' => false,
        'answer' => '',
        'debug' => [
            'type' => 'gemini_http_error',
            'status' => $status,
            'body' => $body,
        ],
    ];
}

    $answer = '';

    if (isset($body['candidates'][0]['content']['parts']) && is_array($body['candidates'][0]['content']['parts'])) {
        foreach ($body['candidates'][0]['content']['parts'] as $part) {
            if (isset($part['text'])) {
                $answer .= $part['text'];
            }
        }
    }

    if (trim($answer) === '') {
        return [
            'success' => false,
            'answer' => '',
            'debug' => [
                'type' => 'empty_answer',
                'body' => $body,
            ],
        ];
    }

    return [
        'success' => true,
        'answer' => trim($answer),
        'debug' => null,
    ];
}
