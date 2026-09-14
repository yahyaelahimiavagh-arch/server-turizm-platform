<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Google Sheet program helpers.
 */
function stai_parse_csv($csv) {
    $rows = [];
    $tmp = tmpfile();

    if (!$tmp) {
        return [];
    }

    fwrite($tmp, $csv);
    rewind($tmp);

    while (($row = fgetcsv($tmp)) !== false) {
        $rows[] = $row;
    }

    fclose($tmp);

    return $rows;
}

function stai_cell($row, $index) {
    return isset($row[$index]) ? trim((string) $row[$index]) : '';
}

function stai_format_date($value) {
    $value = trim((string) $value);

    if ($value === '') {
        return '';
    }

    if (is_numeric($value) && (float) $value > 30000 && (float) $value < 70000) {
        $timestamp = ((float) $value - 25569) * 86400;
        return gmdate('d.m.Y', (int) $timestamp);
    }

    if (preg_match('/^(\d{1,2})\.(\d{1,2})\.(\d{4})$/', $value, $m)) {
        return sprintf('%02d.%02d.%04d', (int) $m[1], (int) $m[2], (int) $m[3]);
    }

    if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $value, $m)) {
        return sprintf('%02d.%02d.%04d', (int) $m[2], (int) $m[1], (int) $m[3]);
    }

    if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $value, $m)) {
        return sprintf('%02d.%02d.%04d', (int) $m[3], (int) $m[2], (int) $m[1]);
    }

    return $value;
}

function stai_format_price($value) {
    $value = trim((string) $value);

    if ($value === '') {
        return '-';
    }

    $value = str_replace(['$', 'USD', 'usd'], '', $value);
    $value = trim($value);

    if ($value === '') {
        return '-';
    }

    return $value . '$';
}

function stai_price_to_number($value) {
    $value = str_replace(['$', 'USD', 'usd', ' '], '', (string) $value);
    $value = trim($value);

    if ($value === '') {
        return 999999999;
    }

    if (preg_match('/^\d{1,3}(\.\d{3})+(,\d+)?$/', $value)) {
        $value = str_replace('.', '', $value);
        $value = str_replace(',', '.', $value);
        return is_numeric($value) ? (float) $value : 999999999;
    }

    if (preg_match('/^\d{1,3}(,\d{3})+(\.\d+)?$/', $value)) {
        $value = str_replace(',', '', $value);
        return is_numeric($value) ? (float) $value : 999999999;
    }

    $value = str_replace(',', '.', $value);
    $value = preg_replace('/[^0-9.]/', '', $value);

    return is_numeric($value) ? (float) $value : 999999999;
}

function stai_get_programs_from_sheet($force_refresh = false) {
    if (!defined('STAI_SHEET_CSV_URL') || empty(STAI_SHEET_CSV_URL)) {
        return [];
    }

    if (!$force_refresh) {
        $cached = get_transient('stai_programs_cache');

        if (!empty($cached) && is_array($cached)) {
            return $cached;
        }
    }

    $response = wp_remote_get(STAI_SHEET_CSV_URL, [
        'timeout' => 60,
    ]);

    if (is_wp_error($response)) {
        return [];
    }

    $csv = wp_remote_retrieve_body($response);

    if (empty($csv)) {
        return [];
    }

    if (stripos($csv, '<html') !== false || stripos($csv, '<!doctype html') !== false) {
        return [];
    }

    $rows = stai_parse_csv($csv);

    if (count($rows) < 3) {
        return [];
    }

    $programs = [];

    for ($i = 2; $i < count($rows); $i++) {
        $row = $rows[$i];

        $program_no = stai_cell($row, 1);
        $title = stai_cell($row, 3);

        if (empty($program_no) && empty($title)) {
            continue;
        }

        $remove_program = strtolower(trim((string) stai_cell($row, 29)));

if (in_array($remove_program, ['true', '1', 'yes', 'evet'], true)) {
    continue;
}

$middle_transfer = stai_cell($row, 8);

if (empty($middle_transfer)) {
    $middle_transfer = stai_cell($row, 7);
}

$programs[] = [
    'no'              => stai_cell($row, 0),
    'program_no'      => $program_no,
    'type'            => stai_cell($row, 2),
    'title'           => $title,
    'departure'       => stai_format_date(stai_cell($row, 6)),
    'middle_transfer' => stai_format_date($middle_transfer),
    'return_date'     => stai_format_date(stai_cell($row, 9)),
    'duration'        => stai_cell($row, 10),

    'medinah_hotel'   => stai_cell($row, 15),
    'makkah_hotel'    => stai_cell($row, 18),

    'price_double'    => stai_cell($row, 21),
    'price_triple'    => stai_cell($row, 22),
    'price_quad'      => stai_cell($row, 23),

    'child_prices'    => stai_cell($row, 24),
    'important_notes' => stai_cell($row, 25),
    'phone'           => stai_cell($row, 26),
    'quota'           => stai_cell($row, 27),
    'is_full'         => stai_cell($row, 28),
    'image'           => stai_cell($row, 34),
];
    }

    set_transient('stai_programs_cache', $programs, 15 * MINUTE_IN_SECONDS);

    return $programs;
}

function stai_normalize_digits($text) {
    $persian = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
    $arabic  = ['٠','١','٢','٣','٤','٥','٦','٧','٨','٩'];
    $latin   = ['0','1','2','3','4','5','6','7','8','9'];

    $text = str_replace($persian, $latin, $text);
    $text = str_replace($arabic, $latin, $text);

    return $text;
}

function stai_extract_program_no_from_message($message) {
    $m = stai_normalize_digits(mb_strtolower((string) $message, 'UTF-8'));

    if (preg_match('/(?:program|برنامه)\s*(?:no|numarası|numarasi|شماره)?\s*[:#-]?\s*(\d+)/u', $m, $matches)) {
        return $matches[1];
    }

    if (preg_match('/\b(\d{2,5})\b/u', $m, $matches)) {
        return $matches[1];
    }

    return null;
}

function stai_find_program_from_message($programs, $message) {
    $program_no = stai_extract_program_no_from_message($message);

    if (!$program_no) {
        return null;
    }

    foreach ($programs as $program) {
        if ((string) $program['program_no'] === (string) $program_no) {
            return $program;
        }
    }

    return null;
}

function stai_is_cheapest_request($message) {
    $m = mb_strtolower((string) $message, 'UTF-8');

    return (
        mb_strpos($m, 'en uygun') !== false ||
        mb_strpos($m, 'en ucuz') !== false ||
        mb_strpos($m, 'uygun fiyat') !== false ||
        mb_strpos($m, 'ucuz') !== false ||
        mb_strpos($m, 'ارزان') !== false ||
        mb_strpos($m, 'مناسب') !== false ||
        mb_strpos($m, 'کمترین قیمت') !== false
    );
}

function stai_is_program_list_request($message) {
    $m = mb_strtolower((string) $message, 'UTF-8');

    $keywords = [
        'programları göster',
        'programları listele',
        'programlari goster',
        'programlari listele',
        'mevcut program',
        'tüm program',
        'tum program',
        'liste',
        'listele',
        'göster',
        'goster',
        'lüks program',
        'luks program',
        'eko program',
        'ekonomik program',
        'umre program',
        'umre programları',
        'umre programlari',
        'همه برنامه',
        'برنامه ها',
        'برنامه‌ها',
        'برنامه‌های',
        'برنامه های',
        'لیست',
        'نشان بده',
        'نمایش بده',
        'تور عمره',
        'تورهای عمره',
    ];

    foreach ($keywords as $keyword) {
        if (mb_strpos($m, $keyword) !== false) {
            return true;
        }
    }

    return false;
}

function stai_select_programs_for_cards($programs, $message, $limit = 6) {
    $m = mb_strtolower((string) $message, 'UTF-8');

    $wants_luxury = (
        mb_strpos($m, 'lüks') !== false ||
        mb_strpos($m, 'luks') !== false ||
        mb_strpos($m, 'لوکس') !== false
    );

    $wants_eko = (
        mb_strpos($m, 'eko') !== false ||
        mb_strpos($m, 'ekonomik') !== false ||
        mb_strpos($m, 'اقتصادی') !== false ||
        mb_strpos($m, 'ارزان') !== false
    );

    $filtered = [];

    foreach ($programs as $program) {
        $type = mb_strtolower((string) $program['type'], 'UTF-8');

        if ($wants_luxury && mb_strpos($type, 'lüks') === false && mb_strpos($type, 'luks') === false) {
            continue;
        }

        if ($wants_eko && mb_strpos($type, 'eko') === false && mb_strpos($type, 'ekonomik') === false) {
            continue;
        }

        $filtered[] = $program;
    }

    if (empty($filtered)) {
        $filtered = $programs;
    }

    return array_slice($filtered, 0, $limit);
}

function stai_get_cheapest_programs($programs, $limit = 5) {
    usort($programs, function ($a, $b) {
        return stai_price_to_number($a['price_quad']) <=> stai_price_to_number($b['price_quad']);
    });

    return array_slice($programs, 0, $limit);
}
function stai_get_program_detail_url($program_no) {
    $program_no = preg_replace('/\D+/', '', (string) $program_no);

    if (empty($program_no)) {
        return 'https://www.serverturizm.com.tr/umre-1/';
    }

    /*
     * Better stable URL.
     * On the program page we will scroll to the matching card by ?program=210.
     */
    return 'https://www.serverturizm.com.tr/umre-1/?program=' . rawurlencode($program_no);
}

function stai_program_to_card($p) {
    $whatsapp_text = "Merhaba, Program {$p['program_no']} - {$p['title']} hakkında bilgi almak istiyorum. Müsaitlik ve fiyat bilgisi alabilir miyim?";

    return [
        
        'program_no'    => $p['program_no'],
        'type'          => $p['type'],
        'title'         => $p['title'],
        'departure'     => $p['departure'],
        'return_date'   => $p['return_date'],
        'duration'      => $p['duration'],
        'medinah_hotel' => $p['medinah_hotel'],
        'makkah_hotel'  => $p['makkah_hotel'],
        'price_double'  => stai_format_price($p['price_double']),
        'price_triple'  => stai_format_price($p['price_triple']),
        'price_quad'    => stai_format_price($p['price_quad']),
        'child_prices'  => $p['child_prices'],
        'whatsapp_url'  => stai_get_whatsapp_url($whatsapp_text),
        'detail_url' => stai_get_program_detail_url($p['program_no']),
    ];
}


function stai_programs_to_cards($programs, $limit = 6) {
    $cards = [];

    foreach (array_slice($programs, 0, $limit) as $program) {
        $cards[] = stai_program_to_card($program);
    }

    return $cards;
}

function stai_build_specific_program_answer($p, $message) {
    $is_persian = stai_is_persian_message($message);

    if ($is_persian) {
        $answer = "اطلاعات برنامه {$p['program_no']}:\n\n";
        $answer .= "• نوع برنامه: {$p['type']}\n";
        $answer .= "• عنوان: {$p['title']}\n";
        $answer .= "• تاریخ رفت: {$p['departure']}\n";
        $answer .= "• تاریخ برگشت: {$p['return_date']}\n";
        $answer .= "• مدت سفر: {$p['duration']}\n";
        $answer .= "• هتل مدینه: {$p['medinah_hotel']}\n";
        $answer .= "• هتل مکه: {$p['makkah_hotel']}\n";
        $answer .= "• قیمت ۲ نفره: " . stai_format_price($p['price_double']) . "\n";
        $answer .= "• قیمت ۳ نفره: " . stai_format_price($p['price_triple']) . "\n";
        $answer .= "• قیمت ۴ نفره: " . stai_format_price($p['price_quad']) . "\n";

        if (!empty($p['child_prices'])) {
            $answer .= "• قیمت کودک: {$p['child_prices']}\n";
        }

        $answer .= "\nقیمت نهایی و ظرفیت باید توسط تیم فروش Server Turizm تأیید شود.";
        return $answer;
    }

    $answer = "Program {$p['program_no']} bilgileri:\n\n";
    $answer .= "• Tür: {$p['type']}\n";
    $answer .= "• Program: {$p['title']}\n";
    $answer .= "• Gidiş: {$p['departure']}\n";
    $answer .= "• Dönüş: {$p['return_date']}\n";
    $answer .= "• Süre: {$p['duration']}\n";
    $answer .= "• Medine Oteli: {$p['medinah_hotel']}\n";
    $answer .= "• Mekke Oteli: {$p['makkah_hotel']}\n";
    $answer .= "• 2 Kişilik: " . stai_format_price($p['price_double']) . "\n";
    $answer .= "• 3 Kişilik: " . stai_format_price($p['price_triple']) . "\n";
    $answer .= "• 4 Kişilik: " . stai_format_price($p['price_quad']) . "\n";

    if (!empty($p['child_prices'])) {
        $answer .= "• Çocuk Ücreti: {$p['child_prices']}\n";
    }

    $answer .= "\nSon fiyat ve müsaitlik durumu Server Turizm satış ekibi tarafından teyit edilmelidir.";

    return $answer;
}

function stai_build_cheapest_program_answer($programs, $message) {
    if (stai_is_persian_message($message)) {
        return "مناسب‌ترین برنامه‌ها را در کارت‌های زیر مشاهده کنید.";
    }

    return "En uygun programları aşağıdaki kartlardan inceleyebilirsiniz.";
}

function stai_build_program_list_answer($programs, $message) {
    if (stai_is_persian_message($message)) {
        return "برنامه‌های مناسب را در کارت‌های زیر مشاهده کنید.";
    }

    return "Uygun programları aşağıdaki kartlardan inceleyebilirsiniz.";
}
function stai_is_whatsapp_request($message) {
    $m = mb_strtolower((string) $message, 'UTF-8');

    return (
        mb_strpos($m, 'whatsapp') !== false ||
        mb_strpos($m, 'واتساپ') !== false ||
        mb_strpos($m, 'تماس') !== false ||
        mb_strpos($m, 'iletişim') !== false ||
        mb_strpos($m, 'iletisim') !== false
    );
}

function stai_build_whatsapp_answer($message) {
    $is_persian = stai_is_persian_message($message);
    $url = stai_get_whatsapp_url('Merhaba, umre programları hakkında bilgi almak istiyorum.');

    if (empty($url)) {
        return $is_persian
            ? "شماره واتساپ هنوز در تنظیمات ثبت نشده است."
            : "WhatsApp numarası henüz ayarlanmamış.";
    }

    return $is_persian
        ? "برای دریافت اطلاعات و رزرو، می‌توانید از طریق واتساپ با Server Turizm تماس بگیرید:\n\n{$url}"
        : "Bilgi almak ve rezervasyon için WhatsApp üzerinden Server Turizm ile iletişime geçebilirsiniz:\n\n{$url}";
}

function stai_is_child_price_request($message) {
    $m = mb_strtolower((string) $message, 'UTF-8');

    return (
        mb_strpos($m, 'çocuk') !== false ||
        mb_strpos($m, 'cocuk') !== false ||
        mb_strpos($m, 'bebek') !== false ||
        mb_strpos($m, 'کودک') !== false ||
        mb_strpos($m, 'بچه') !== false
    );
}

function stai_build_child_price_answer($programs, $message, $specific_program = null) {
    $is_persian = stai_is_persian_message($message);

    if ($specific_program) {
        $child_price = !empty($specific_program['child_prices']) ? $specific_program['child_prices'] : '-';

        if ($is_persian) {
            return "قیمت کودک برای برنامه {$specific_program['program_no']}:\n\n{$child_price}\n\nبرای محاسبه نهایی، سن کودک و شرایط برنامه باید توسط تیم فروش Server Turizm تأیید شود.";
        }

        return "Program {$specific_program['program_no']} için çocuk ücreti:\n\n{$child_price}\n\nÇocuk yaşı ve program şartlarına göre son ücret Server Turizm satış ekibi tarafından teyit edilmelidir.";
    }

    $items = array_slice($programs, 0, 10);

    if ($is_persian) {
        $answer = "قیمت کودک بسته به برنامه متفاوت است. چند نمونه از برنامه‌های موجود:\n\n";

        foreach ($items as $p) {
            $child_price = !empty($p['child_prices']) ? $p['child_prices'] : '-';
            $answer .= "• برنامه {$p['program_no']}: {$child_price}\n";
        }

        $answer .= "\nبرای قیمت دقیق کودک، شماره برنامه و سن کودک را به تیم فروش Server Turizm اعلام کنید.";
        return $answer;
    }

    $answer = "Çocuk ücreti programa göre değişebilir. Mevcut programlardan bazıları:\n\n";

    foreach ($items as $p) {
        $child_price = !empty($p['child_prices']) ? $p['child_prices'] : '-';
        $answer .= "• Program {$p['program_no']}: {$child_price}\n";
    }

    $answer .= "\nNet çocuk ücreti için program numarası ve çocuğun yaşı Server Turizm satış ekibi tarafından teyit edilmelidir.";

    return $answer;
}

function stai_detect_room_type($message) {
    $m = stai_normalize_digits(mb_strtolower((string) $message, 'UTF-8'));

    if (
        mb_strpos($m, '2 kişilik') !== false ||
        mb_strpos($m, 'iki kişilik') !== false ||
        mb_strpos($m, '2 kisilik') !== false ||
        mb_strpos($m, 'iki kisilik') !== false ||
        mb_strpos($m, '2 نفره') !== false ||
        mb_strpos($m, 'دو نفره') !== false
    ) {
        return 'double';
    }

    if (
        mb_strpos($m, '3 kişilik') !== false ||
        mb_strpos($m, 'üç kişilik') !== false ||
        mb_strpos($m, 'uc kisilik') !== false ||
        mb_strpos($m, '3 kisilik') !== false ||
        mb_strpos($m, '3 نفره') !== false ||
        mb_strpos($m, 'سه نفره') !== false
    ) {
        return 'triple';
    }

    if (
        mb_strpos($m, '4 kişilik') !== false ||
        mb_strpos($m, 'dört kişilik') !== false ||
        mb_strpos($m, 'dort kisilik') !== false ||
        mb_strpos($m, '4 kisilik') !== false ||
        mb_strpos($m, '4 نفره') !== false ||
        mb_strpos($m, 'چهار نفره') !== false
    ) {
        return 'quad';
    }

    return null;
}

function stai_is_room_price_request($message) {
    $m = mb_strtolower((string) $message, 'UTF-8');

    return stai_detect_room_type($message) !== null && (
        mb_strpos($m, 'fiyat') !== false ||
        mb_strpos($m, 'ücret') !== false ||
        mb_strpos($m, 'ucret') !== false ||
        mb_strpos($m, 'ne kadar') !== false ||
        mb_strpos($m, 'قیمت') !== false ||
        mb_strpos($m, 'چقدر') !== false
    );
}

function stai_build_room_price_answer($programs, $message, $specific_program = null) {
    $is_persian = stai_is_persian_message($message);
    $room_type = stai_detect_room_type($message);

    $field = 'price_double';
    $label_tr = '2 Kişilik';
    $label_fa = '۲ نفره';

    if ($room_type === 'triple') {
        $field = 'price_triple';
        $label_tr = '3 Kişilik';
        $label_fa = '۳ نفره';
    }

    if ($room_type === 'quad') {
        $field = 'price_quad';
        $label_tr = '4 Kişilik';
        $label_fa = '۴ نفره';
    }

    if ($specific_program) {
        $price = stai_format_price($specific_program[$field]);

        if ($is_persian) {
            return "قیمت اتاق {$label_fa} برای برنامه {$specific_program['program_no']}:\n\n{$price}\n\nقیمت نهایی و ظرفیت باید توسط تیم فروش Server Turizm تأیید شود.";
        }

        return "Program {$specific_program['program_no']} için {$label_tr} oda fiyatı:\n\n{$price}\n\nSon fiyat ve müsaitlik durumu Server Turizm satış ekibi tarafından teyit edilmelidir.";
    }

    $items = array_slice($programs, 0, 10);

    if ($is_persian) {
        $answer = "قیمت اتاق {$label_fa} در چند برنامه موجود:\n\n";

        foreach ($items as $p) {
            $answer .= "• برنامه {$p['program_no']} - {$p['type']}: " . stai_format_price($p[$field]) . "\n";
        }

        $answer .= "\nبرای قیمت دقیق‌تر، شماره برنامه را هم بنویسید؛ مثلاً: برنامه 210 قیمت ۲ نفره";
        return $answer;
    }

    $answer = "{$label_tr} oda fiyatları, mevcut bazı programlara göre:\n\n";

    foreach ($items as $p) {
        $answer .= "• Program {$p['program_no']} - {$p['type']}: " . stai_format_price($p[$field]) . "\n";
    }

    $answer .= "\nDaha net bilgi için program numarasını da yazabilirsiniz. Örnek: Program 210 2 kişilik fiyatı";

    return $answer;
}

function stai_detect_month_number($message) {
    $m = mb_strtolower((string) $message, 'UTF-8');

    $months = [
        'ocak' => 1,
        'şubat' => 2,
        'subat' => 2,
        'mart' => 3,
        'nisan' => 4,
        'mayıs' => 5,
        'mayis' => 5,
        'haziran' => 6,
        'temmuz' => 7,
        'ağustos' => 8,
        'agustos' => 8,
        'eylül' => 9,
        'eylul' => 9,
        'ekim' => 10,
        'kasım' => 11,
        'kasim' => 11,
        'aralık' => 12,
        'aralik' => 12,

        'january' => 1,
        'february' => 2,
        'march' => 3,
        'april' => 4,
        'may' => 5,
        'june' => 6,
        'july' => 7,
        'august' => 8,
        'september' => 9,
        'october' => 10,
        'november' => 11,
        'december' => 12,

        'ژانویه' => 1,
        'فوریه' => 2,
        'مارس' => 3,
        'آوریل' => 4,
        'می' => 5,
        'ژوئن' => 6,
        'جون' => 6,
        'جولای' => 7,
        'اوت' => 8,
        'آگوست' => 8,
        'سپتامبر' => 9,
        'اکتبر' => 10,
        'نوامبر' => 11,
        'دسامبر' => 12,
    ];

    foreach ($months as $name => $number) {
        if (mb_strpos($m, $name) !== false) {
            return $number;
        }
    }

    return null;
}

function stai_get_month_from_date($date) {
    $date = trim((string) $date);

    if (preg_match('/^(\d{1,2})\.(\d{1,2})\.(\d{4})$/', $date, $m)) {
        return (int) $m[2];
    }

    if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $date, $m)) {
        return (int) $m[1];
    }

    return null;
}

function stai_get_month_programs($programs, $month_number, $limit = 6) {
    $filtered = [];

    foreach ($programs as $program) {
        if (stai_get_month_from_date($program['departure']) === (int) $month_number) {
            $filtered[] = $program;
        }
    }

    return array_slice($filtered, 0, $limit);
}

function stai_build_month_program_answer($programs, $message, $month_number) {
    $is_persian = stai_is_persian_message($message);
    $filtered = stai_get_month_programs($programs, $month_number, 12);

    if (empty($filtered)) {
        return $is_persian
            ? "در حال حاضر برای این ماه برنامه‌ای در داده‌های موجود پیدا نشد. برای اطلاعات نهایی لطفاً با تیم فروش Server Turizm تماس بگیرید."
            : "Mevcut verilerde bu ay için umre programı bulunamadı. Güncel bilgi için lütfen Server Turizm satış ekibiyle iletişime geçin.";
    }

    if ($is_persian) {
        $answer = "برنامه‌های موجود در این ماه:\n\n";

        foreach ($filtered as $p) {
            $answer .= "• برنامه {$p['program_no']} - {$p['type']}\n";
            $answer .= "  {$p['title']}\n";
            $answer .= "  رفت: {$p['departure']} | برگشت: {$p['return_date']}\n";
            $answer .= "  قیمت ۴ نفره: " . stai_format_price($p['price_quad']) . "\n\n";
        }

        $answer .= "برای رزرو نهایی و تأیید ظرفیت با تیم فروش Server Turizm تماس بگیرید.";
        return $answer;
    }

    $answer = "Bu ay için mevcut umre programları:\n\n";

    foreach ($filtered as $p) {
        $answer .= "• Program {$p['program_no']} - {$p['type']}\n";
        $answer .= "  {$p['title']}\n";
        $answer .= "  Gidiş: {$p['departure']} | Dönüş: {$p['return_date']}\n";
        $answer .= "  4 Kişilik: " . stai_format_price($p['price_quad']) . "\n\n";
    }

    $answer .= "Son rezervasyon ve müsaitlik durumu Server Turizm satış ekibi tarafından teyit edilmelidir.";

    return $answer;
}
