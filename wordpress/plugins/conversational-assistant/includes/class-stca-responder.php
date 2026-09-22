<?php
if (!defined('ABSPATH')) { exit; }

final class STCA_Responder {
    public static function reply(string $message): array {
        $analysis = STCA_Intent::analyze($message);
        $intent = $analysis['intent'];
        $language = $analysis['language'];

        if ($intent === 'human' || $intent === 'contact') {
            return self::result(self::contact_text($language), $analysis, array(), true);
        }
        if ($intent === 'hajj') {
            return self::result(self::hajj_text($language), $analysis, array(), true);
        }
        if ($intent === 'visa') {
            return self::result(self::visa_text($language), $analysis, array(), true);
        }
        if ($intent === 'tour') {
            $tours = STCA_Data::tours($analysis, 3);
            return self::result(self::tour_text($language, $tours), $analysis, $tours, empty($tours));
        }
        if ($intent === 'hotel') {
            $programs = STCA_Data::umrah_programs($analysis, 3);
            return self::result(self::umrah_text($language, $programs, $analysis, true), $analysis, $programs, empty($programs));
        }
        if (in_array($intent, array('umrah', 'price'), true)) {
            $programs = STCA_Data::umrah_programs($analysis, 3);
            return self::result(self::umrah_text($language, $programs, $analysis, false), $analysis, $programs, empty($programs));
        }

        return self::result(self::fallback_text($language), $analysis, array(), true);
    }

    private static function result(string $text, array $analysis, array $matches, bool $handoff): array {
        return array(
            'text' => trim($text),
            'intent' => (string) ($analysis['intent'] ?? 'fallback'),
            'language' => (string) ($analysis['language'] ?? 'tr'),
            'matches' => $matches,
            'handoff_recommended' => $handoff,
        );
    }

    private static function umrah_text(string $lang, array $programs, array $analysis, bool $hotelFocus): string {
        if (!$programs) {
            return self::no_program_text($lang, $analysis);
        }
        if ($lang === 'fa') {
            $lines = array('سلام 🌙 بر اساس برنامه‌های تأییدشده فعلی، این گزینه‌ها مرتبط هستند:');
        } elseif ($lang === 'en') {
            $lines = array('Hello 🌙 Based on the currently approved programs, these options match:');
        } else {
            $lines = array('Merhaba 🌙 Güncel ve onaylı programlarımızdan uygun seçenekler:');
        }
        foreach ($programs as $program) {
            $line = '• ' . self::program_title_line($program, $lang, $analysis, $hotelFocus);
            $lines[] = $line;
        }
        if ($lang === 'fa') {
            $lines[] = 'برنامه‌های به‌روز عمره: ' . STCA_Config::UMRah_URL;
            $lines[] = 'ظرفیت و رزرو نهایی باید توسط Server Turizm تأیید شود.';
            $lines[] = '☎️ ' . STCA_Config::PHONE . '  |  📱 ' . STCA_Config::MOBILE;
        } elseif ($lang === 'en') {
            $lines[] = 'Current Umrah programs: ' . STCA_Config::UMRah_URL;
            $lines[] = 'Final availability and reservation must be confirmed by Server Turizm.';
            $lines[] = '☎️ ' . STCA_Config::PHONE . '  |  📱 ' . STCA_Config::MOBILE;
        } else {
            $lines[] = 'Güncel Ümre programları: ' . STCA_Config::UMRah_URL;
            $lines[] = 'Kontenjan ve kesin rezervasyon Server Turizm tarafından teyit edilmelidir.';
            $lines[] = '☎️ ' . STCA_Config::PHONE . '  |  📱 ' . STCA_Config::MOBILE;
        }
        return implode("\n", $lines);
    }

    private static function program_title_line(array $program, string $lang, array $analysis, bool $hotelFocus): string {
        $parts = array((string) ($program['title'] ?: $program['id']));
        $date = self::date_range((string) ($program['start_date'] ?? ''), (string) ($program['end_date'] ?? ''), $lang);
        if ($date !== '') { $parts[] = $date; }
        $duration = self::duration($program, $lang);
        if ($duration !== '') { $parts[] = $duration; }
        $price = self::price($program['pricing'] ?? array(), (string) ($analysis['occupancy'] ?? ''), $lang);
        if ($price !== '') { $parts[] = $price; }
        if ($hotelFocus) {
            $hotels = array_values(array_filter(array_map(static fn($s) => trim((string) ($s['hotel_name'] ?? '')), (array) ($program['stays'] ?? array()))));
            if ($hotels) {
                $parts[] = ($lang === 'fa' ? 'هتل: ' : ($lang === 'en' ? 'Hotel: ' : 'Otel: ')) . implode(' / ', array_unique($hotels));
            }
        }
        if (($program['availability'] ?? '') === 'sold_out') {
            $parts[] = $lang === 'fa' ? 'تکمیل ظرفیت' : ($lang === 'en' ? 'Sold out' : 'Kontenjan dolu');
        }
        return implode(' — ', $parts);
    }

    private static function price(array $pricing, string $occupancy, string $lang): string {
        $currency = (string) ($pricing['currency'] ?? 'USD');
        $entries = (array) ($pricing['entries'] ?? array());
        $wanted = $occupancy !== '' ? $occupancy : 'quad';
        $selected = null;
        foreach ($entries as $entry) {
            if (!is_array($entry)) { continue; }
            if (($entry['occupancy'] ?? '') === $wanted && isset($entry['amount'])) {
                $selected = $entry;
                break;
            }
        }
        if ($selected === null && $occupancy === '') {
            foreach (array('quad', 'triple', 'double', 'single') as $try) {
                foreach ($entries as $entry) {
                    if (is_array($entry) && ($entry['occupancy'] ?? '') === $try && isset($entry['amount'])) {
                        $selected = $entry;
                        break 2;
                    }
                }
            }
        }
        if (!is_array($selected)) { return ''; }
        $amount = (float) $selected['amount'];
        $labelMap = array('single' => '1', 'double' => '2', 'triple' => '3', 'quad' => '4');
        $label = $labelMap[(string) ($selected['occupancy'] ?? '')] ?? '';
        $prefix = $lang === 'fa' ? 'قیمت' : ($lang === 'en' ? 'Price' : 'Fiyat');
        return trim($prefix . ($label !== '' ? " ({$label})" : '') . ': ' . number_format($amount, $amount == floor($amount) ? 0 : 2, '.', ',') . ' ' . $currency);
    }

    private static function duration(array $program, string $lang): string {
        $days = isset($program['duration_days']) ? (int) $program['duration_days'] : 0;
        $nights = isset($program['duration_nights']) ? (int) $program['duration_nights'] : 0;
        if (!$days && !$nights) { return ''; }
        if ($lang === 'fa') { return trim(($nights ? $nights . ' شب ' : '') . ($days ? $days . ' روز' : '')); }
        if ($lang === 'en') { return trim(($nights ? $nights . ' nights ' : '') . ($days ? $days . ' days' : '')); }
        return trim(($nights ? $nights . ' gece ' : '') . ($days ? $days . ' gün' : ''));
    }

    private static function date_range(string $start, string $end, string $lang): string {
        if ($start === '' && $end === '') { return ''; }
        $fmt = static function ($date) {
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/D', $date)) { return $date; }
            return wp_date('d.m.Y', strtotime($date . ' 12:00:00'));
        };
        $a = $fmt($start);
        $b = $fmt($end);
        if ($a && $b && $a !== $b) { return $a . ' → ' . $b; }
        return $a ?: $b;
    }

    private static function no_program_text(string $lang, array $analysis): string {
        $monthHint = !empty($analysis['months']);
        if ($lang === 'fa') {
            $lead = $monthHint ? 'برای ماه‌های درخواستی در داده‌های تأییدشده فعلی گزینه‌ای پیدا نکردم.' : 'برای این درخواست در داده‌های تأییدشده فعلی گزینه مشخصی پیدا نکردم.';
            return $lead . "\nبرنامه‌های به‌روز عمره: " . STCA_Config::UMRah_URL . "\nبرای اطلاعات کامل با ما تماس بگیرید:\n☎️ " . STCA_Config::PHONE . "\n📱 " . STCA_Config::MOBILE;
        }
        if ($lang === 'en') {
            $lead = $monthHint ? 'I could not find a currently approved program for the requested month(s).' : 'I could not find a specific currently approved program for this request.';
            return $lead . "\nCurrent Umrah programs: " . STCA_Config::UMRah_URL . "\nFor full details, contact us:\n☎️ " . STCA_Config::PHONE . "\n📱 " . STCA_Config::MOBILE;
        }
        $lead = $monthHint ? 'İstediğiniz ay(lar) için şu anda onaylı verilerde eşleşen program bulamadım.' : 'Bu talep için şu anda onaylı verilerde net bir program bulamadım.';
        return $lead . "\nGüncel Ümre programlarımızı inceleyebilirsiniz:\n" . STCA_Config::UMRah_URL . "\nDetaylı bilgi için bizimle iletişime geçebilirsiniz:\n☎️ " . STCA_Config::PHONE . "\n📱 " . STCA_Config::MOBILE;
    }

    private static function tour_text(string $lang, array $tours): string {
        if (!$tours) {
            if ($lang === 'fa') { return "در حال حاضر تور عمومیِ تأییدشده‌ای که ربات اجازه نمایش آن را داشته باشد پیدا نکردم. برای برنامه‌های جدید با ما تماس بگیرید:\n☎️ " . STCA_Config::PHONE . "\n📱 " . STCA_Config::MOBILE; }
            if ($lang === 'en') { return "I couldn't find a currently approved public tour that the assistant is allowed to show. Please contact us for current options:\n☎️ " . STCA_Config::PHONE . "\n📱 " . STCA_Config::MOBILE; }
            return "Şu anda asistanın gösterebileceği onaylı ve yayına açık bir kültür turu bulamadım. Güncel seçenekler için bizimle iletişime geçebilirsiniz:\n☎️ " . STCA_Config::PHONE . "\n📱 " . STCA_Config::MOBILE;
        }
        $lines = array($lang === 'fa' ? 'تورهای فعال:' : ($lang === 'en' ? 'Active tours:' : 'Aktif turlar:'));
        foreach ($tours as $tour) {
            $p = array('• ' . (string) ($tour['title'] ?? $tour['id'] ?? ''));
            $date = self::date_range((string) ($tour['start_date'] ?? ''), (string) ($tour['end_date'] ?? ''), $lang);
            if ($date) { $p[] = $date; }
            if (!empty($tour['country'])) { $p[] = (string) $tour['country']; }
            $lines[] = implode(' — ', $p);
        }
        $lines[] = '☎️ ' . STCA_Config::PHONE . '  |  📱 ' . STCA_Config::MOBILE;
        return implode("\n", $lines);
    }

    private static function contact_text(string $lang): string {
        if ($lang === 'fa') { return "برای اطلاعات کامل یا ارتباط با کارشناس Server Turizm:\n☎️ " . STCA_Config::PHONE . "\n📱 " . STCA_Config::MOBILE . "\n🌙 برنامه‌های عمره: " . STCA_Config::UMRah_URL; }
        if ($lang === 'en') { return "For full details or a Server Turizm representative:\n☎️ " . STCA_Config::PHONE . "\n📱 " . STCA_Config::MOBILE . "\n🌙 Umrah programs: " . STCA_Config::UMRah_URL; }
        return "Detaylı bilgi veya temsilcimizle görüşmek için:\n☎️ " . STCA_Config::PHONE . "\n📱 " . STCA_Config::MOBILE . "\n🌙 Ümre programları: " . STCA_Config::UMRah_URL;
    }

    private static function visa_text(string $lang): string {
        if ($lang === 'fa') { return "شرایط و مدارک ویزا می‌تواند بر اساس تابعیت و برنامه تغییر کند. برای اینکه اطلاعات اشتباه ندهم، جزئیات را با کارشناسان Server Turizm تأیید کنید:\n☎️ " . STCA_Config::PHONE . "\n📱 " . STCA_Config::MOBILE; }
        if ($lang === 'en') { return "Visa requirements can vary by nationality and program. To avoid giving incorrect information, please confirm your case with Server Turizm:\n☎️ " . STCA_Config::PHONE . "\n📱 " . STCA_Config::MOBILE; }
        return "Vize şartları uyruk ve programa göre değişebilir. Yanlış bilgi vermemek için güncel durumu Server Turizm ekibimizle teyit edebilirsiniz:\n☎️ " . STCA_Config::PHONE . "\n📱 " . STCA_Config::MOBILE;
    }

    private static function hajj_text(string $lang): string {
        if ($lang === 'fa') { return "برای برنامه‌ها و شرایط حج، اطلاعات روز را از کارشناسان Server Turizm دریافت کنید:\n☎️ " . STCA_Config::PHONE . "\n📱 " . STCA_Config::MOBILE; }
        if ($lang === 'en') { return "For current Hajj programs and conditions, please contact the Server Turizm team:\n☎️ " . STCA_Config::PHONE . "\n📱 " . STCA_Config::MOBILE; }
        return "Güncel Hac programları ve şartları için Server Turizm ekibimizden bilgi alabilirsiniz:\n☎️ " . STCA_Config::PHONE . "\n📱 " . STCA_Config::MOBILE;
    }

    private static function fallback_text(string $lang): string {
        if ($lang === 'fa') { return "می‌توانم درباره برنامه‌های عمره، تاریخ، قیمت، هتل و تورهای فعال کمک کنم. سؤال‌تان را بنویسید یا با ما تماس بگیرید:\n☎️ " . STCA_Config::PHONE . "\n📱 " . STCA_Config::MOBILE; }
        if ($lang === 'en') { return "I can help with current Umrah programs, dates, prices, hotels and active tours. Ask your question or contact us:\n☎️ " . STCA_Config::PHONE . "\n📱 " . STCA_Config::MOBILE; }
        return "Ümre programları, tarih, fiyat, otel ve aktif turlar hakkında yardımcı olabilirim. Sorunuzu yazabilir veya bizimle iletişime geçebilirsiniz:\n☎️ " . STCA_Config::PHONE . "\n📱 " . STCA_Config::MOBILE;
    }
}
