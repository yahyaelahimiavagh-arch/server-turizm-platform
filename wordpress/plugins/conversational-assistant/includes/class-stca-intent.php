<?php
if (!defined('ABSPATH') && PHP_SAPI !== 'cli') { exit; }

final class STCA_Intent {
    private const MONTHS = array(
        1 => array('ocak', 'january', 'jan'),
        2 => array('şubat', 'subat', 'february', 'feb'),
        3 => array('mart', 'march', 'mar'),
        4 => array('nisan', 'april', 'apr'),
        5 => array('mayıs', 'mayis', 'may'),
        6 => array('haziran', 'june', 'jun'),
        7 => array('temmuz', 'july', 'jul'),
        8 => array('ağustos', 'agustos', 'august', 'aug'),
        9 => array('eylül', 'eylul', 'september', 'sep'),
        10 => array('ekim', 'october', 'oct'),
        11 => array('kasım', 'kasim', 'november', 'nov'),
        12 => array('aralık', 'aralik', 'december', 'dec'),
    );

    public static function analyze(string $message): array {
        $normalized = self::normalize($message);
        $language = self::language($message);
        $months = self::months($normalized);
        $occupancy = self::occupancy($normalized);
        $intent = self::intent($normalized);

        return array(
            'intent' => $intent,
            'language' => $language,
            'months' => $months,
            'occupancy' => $occupancy,
            'tier' => self::tier($normalized),
            'wants_human' => $intent === 'human',
            'normalized' => $normalized,
        );
    }

    public static function normalize(string $message): string {
        $message = trim(self::lower($message));
        $message = strtr($message, array(
            'ı' => 'i', 'İ' => 'i', 'ş' => 's', 'Ş' => 's', 'ğ' => 'g', 'Ğ' => 'g',
            'ü' => 'u', 'Ü' => 'u', 'ö' => 'o', 'Ö' => 'o', 'ç' => 'c', 'Ç' => 'c',
            'ي' => 'ی', 'ك' => 'ک',
        ));
        $message = preg_replace('/[^\p{L}\p{N}\s\-+.$€₺]/u', ' ', $message);
        return preg_replace('/\s+/u', ' ', trim((string) $message));
    }

    public static function language(string $message): string {
        if (preg_match('/[\x{0600}-\x{06FF}]/u', $message)) {
            return 'fa';
        }
        $lower = self::lower($message);
        if (preg_match('/[çğıöşü]/u', $lower) || preg_match('/\b(umre|ümre|fiyat|otel|oteller|kasim|kasım|ekim|bilgi|tur|vize|hac|temsilci|hangileri|nedir|istiyorum)\b/u', $lower)) {
            return 'tr';
        }
        return 'en';
    }

    private static function months(string $normalized): array {
        $matches = array();
        foreach (self::MONTHS as $number => $aliases) {
            foreach ($aliases as $alias) {
                $alias = self::normalize($alias);
                if (preg_match('/(?:^|\s)' . preg_quote($alias, '/') . '(?:\s|$)/u', $normalized)) {
                    $matches[] = $number;
                    break;
                }
            }
        }
        return array_values(array_unique($matches));
    }

    private static function occupancy(string $normalized): ?string {
        $patterns = array(
            'double' => array('2 kisilik', 'iki kisilik', 'double', 'ikili', 'دو نفر', 'دونفر'),
            'triple' => array('3 kisilik', 'uc kisilik', 'triple', 'uclu', 'سه نفر', 'سه نفره'),
            'quad' => array('4 kisilik', 'dort kisilik', 'quad', 'dortlu', 'چهار نفر', 'چهارنفره'),
            'single' => array('1 kisilik', 'tek kisilik', 'single', 'tekli', 'یک نفر', 'تک نفره'),
        );
        foreach ($patterns as $key => $needles) {
            foreach ($needles as $needle) {
                if (self::pos($normalized, self::normalize($needle)) !== false) {
                    return $key;
                }
            }
        }
        return null;
    }

    private static function tier(string $normalized): ?string {
        foreach (array('luxury' => array('luks', 'premium', 'لوکس'), 'economic' => array('ekonomik', 'eko', 'uygun', 'ارزان', 'اقتصادی')) as $tier => $words) {
            foreach ($words as $word) {
                if (self::pos($normalized, self::normalize($word)) !== false) {
                    return $tier;
                }
            }
        }
        return null;
    }

    private static function intent(string $normalized): string {
        $sets = array(
            'human' => array('temsilci', 'operator', 'insanla', 'birini bagla', 'musteri hizmet', 'canli destek', 'ادم', 'اپراتور', 'کارشناس'),
            'contact' => array('telefon', 'numara', 'whatsapp', 'adres', 'iletisim', 'contact', 'شماره', 'تماس', 'واتساپ', 'آدرس'),
            'hajj' => array('hac', 'hajj', 'حج'),
            'visa' => array('vize', 'visa', 'ویز', 'ويزا'),
            'hotel' => array('otel', 'hotel', 'هتل'),
            'price' => array('fiyat', 'ucret', 'kaç para', 'kac para', 'price', 'cost', 'قیمت', 'هزینه'),
            'tour' => array('kultur tur', 'kultur turu', 'tour', 'tur program', 'iran tur', 'balkan', 'ozbekistan', 'misir', 'تور'),
            'umrah' => array('umre', 'ümre', 'umrah', 'mekke', 'medine', 'عمره', 'مکه', 'مدینه'),
        );
        foreach ($sets as $intent => $needles) {
            foreach ($needles as $needle) {
                if (self::pos($normalized, self::normalize($needle)) !== false) {
                    return $intent;
                }
            }
        }
        if (self::months($normalized)) {
            return 'umrah';
        }
        return 'fallback';
    }

    public static function lower(string $value): string {
        return function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
    }

    public static function pos(string $haystack, string $needle) {
        return function_exists('mb_strpos') ? mb_strpos($haystack, $needle, 0, 'UTF-8') : strpos($haystack, $needle);
    }

    public static function length(string $value): int {
        return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
    }

    public static function slice(string $value, int $start, int $length): string {
        return function_exists('mb_substr') ? mb_substr($value, $start, $length, 'UTF-8') : substr($value, $start, $length);
    }
}
