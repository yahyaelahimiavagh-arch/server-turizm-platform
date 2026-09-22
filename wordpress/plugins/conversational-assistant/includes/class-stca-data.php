<?php
if (!defined('ABSPATH')) { exit; }

final class STCA_Data {
    public static function umrah_programs(array $analysis = array(), int $limit = 4): array {
        if (!class_exists('STPI_Store') || !method_exists('STPI_Store', 'all_programs')) {
            return array();
        }
        $results = array();
        foreach ((array) STPI_Store::all_programs() as $row) {
            $program = is_array($row['program'] ?? null) ? $row['program'] : array();
            if (!self::program_customer_eligible($program)) {
                continue;
            }
            if (!self::program_matches($program, $analysis)) {
                continue;
            }
            $results[] = self::program_view($program);
        }
        usort($results, static function ($a, $b) {
            $ad = (string) ($a['start_date'] ?? '9999-12-31');
            $bd = (string) ($b['start_date'] ?? '9999-12-31');
            if ($ad === $bd) {
                return ((int) ($b['priority'] ?? 0)) <=> ((int) ($a['priority'] ?? 0));
            }
            return strcmp($ad, $bd);
        });
        return array_slice($results, 0, max(1, min(10, $limit)));
    }

    public static function tours(array $analysis = array(), int $limit = 4): array {
        if (!function_exists('stti_get_candidates')) {
            return array();
        }
        $out = array();
        foreach ((array) stti_get_candidates() as $row) {
            $payload = json_decode((string) ($row['payload'] ?? ''), true);
            if (!is_array($payload) || !self::tour_customer_eligible($row, $payload)) {
                continue;
            }
            $out[] = array(
                'id' => (string) ($row['stable_id'] ?? $payload['stable_id'] ?? ''),
                'title' => (string) ($payload['identity']['public_title'] ?? $row['public_title'] ?? ''),
                'start_date' => (string) ($payload['date']['start_date'] ?? ''),
                'end_date' => (string) ($payload['date']['end_date'] ?? ''),
                'duration_days' => $payload['date']['duration_days'] ?? null,
                'country' => (string) ($payload['destinations']['primary_country'] ?? ''),
                'city' => (string) ($payload['destinations']['primary_city'] ?? ''),
                'pricing' => is_array($payload['pricing'] ?? null) ? $payload['pricing'] : array(),
                'description' => (string) ($payload['content']['short_description'] ?? ''),
            );
        }
        usort($out, static fn($a, $b) => strcmp((string) ($a['start_date'] ?: '9999-12-31'), (string) ($b['start_date'] ?: '9999-12-31')));
        return array_slice($out, 0, max(1, min(10, $limit)));
    }

    public static function hotel_by_id(string $hotel_id): array {
        $hotel_id = strtoupper(trim($hotel_id));
        if (!preg_match('/^STH-[0-9]{6}$/D', $hotel_id)) {
            return array();
        }
        $ids = get_posts(array(
            'post_type' => 'sthi_hotel',
            'post_status' => 'publish',
            'posts_per_page' => 1,
            'fields' => 'ids',
            'meta_key' => '_sthi_hotel_id',
            'meta_value' => $hotel_id,
            'no_found_rows' => true,
        ));
        if (!$ids) {
            return array();
        }
        return self::hotel_view((int) $ids[0]);
    }

    public static function counts(): array {
        $programs = self::umrah_programs(array(), 1000);
        $tours = self::tours(array(), 1000);
        $hotels = get_posts(array(
            'post_type' => 'sthi_hotel',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'no_found_rows' => true,
        ));
        return array(
            'umrah_customer_eligible' => count($programs),
            'tour_hub_visible' => count($tours),
            'published_hotels' => is_array($hotels) ? count($hotels) : 0,
        );
    }

    public static function program_customer_eligible(array $program): bool {
        if (($program['service_type'] ?? '') !== 'umrah') {
            return false;
        }
        $workflow = is_array($program['workflow'] ?? null) ? $program['workflow'] : array();
        if (!in_array((string) ($workflow['editorial'] ?? ''), array('approved', 'published'), true)) {
            return false;
        }
        if (in_array((string) ($workflow['schedule'] ?? ''), array('cancelled'), true)) {
            return false;
        }
        if (in_array((string) ($workflow['availability'] ?? ''), array('closed'), true)) {
            return false;
        }
        if (($program['publication_mode'] ?? '') === 'archived_departure') {
            return false;
        }
        $end = (string) ($program['schedule']['end_date'] ?? '');
        if ($end !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/D', $end) && $end < current_time('Y-m-d')) {
            return false;
        }
        return true;
    }

    public static function tour_customer_eligible(array $row, array $payload): bool {
        $lifecycle = is_array($payload['lifecycle'] ?? null) ? $payload['lifecycle'] : array();
        $publication = is_array($payload['publication'] ?? null) ? $payload['publication'] : array();
        $editorial = (string) ($row['editorial'] ?? $lifecycle['editorial'] ?? '');
        $schedule = (string) ($row['schedule_status'] ?? $lifecycle['schedule'] ?? '');
        $temporal = (string) ($row['temporal'] ?? $lifecycle['temporal'] ?? '');

        if (!in_array($editorial, array('approved', 'published'), true)) {
            return false;
        }
        if (in_array($schedule, array('cancelled', 'archived'), true)) {
            return false;
        }
        if (in_array($temporal, array('past', 'completed'), true)) {
            return false;
        }
        if (($publication['hub_visible'] ?? false) !== true) {
            return false;
        }
        return true;
    }

    private static function program_matches(array $program, array $analysis): bool {
        $months = array_values(array_filter(array_map('intval', (array) ($analysis['months'] ?? array()))));
        if ($months && !self::program_overlaps_months($program, $months)) {
            return false;
        }
        $tier = (string) ($analysis['tier'] ?? '');
        if ($tier !== '') {
            $raw = STCA_Intent::normalize((string) ($program['tier'] ?? '') . ' ' . (string) ($program['title'] ?? ''));
            if ($tier === 'luxury' && STCA_Intent::pos($raw, 'luks') === false && STCA_Intent::pos($raw, 'premium') === false) {
                return false;
            }
            if ($tier === 'economic' && STCA_Intent::pos($raw, 'eko') === false && STCA_Intent::pos($raw, 'ekonomik') === false) {
                return false;
            }
        }
        return true;
    }

    private static function program_overlaps_months(array $program, array $months): bool {
        $start = (string) ($program['schedule']['start_date'] ?? '');
        $end = (string) ($program['schedule']['end_date'] ?? $start);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/D', $start)) {
            $label = STCA_Intent::normalize((string) ($program['schedule']['date_label'] ?? ''));
            foreach ($months as $month) {
                $date = sprintf('2026-%02d-01', $month);
                $name = STCA_Intent::normalize(wp_date('F', strtotime($date)));
                if ($name !== '' && STCA_Intent::pos($label, $name) !== false) {
                    return true;
                }
            }
            return false;
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/D', $end)) {
            $end = $start;
        }
        $startTs = strtotime($start . ' 00:00:00');
        $endTs = strtotime($end . ' 23:59:59');
        foreach ($months as $month) {
            foreach (array((int) gmdate('Y', $startTs), (int) gmdate('Y', $endTs)) as $year) {
                $monthStart = strtotime(sprintf('%04d-%02d-01 00:00:00', $year, $month));
                $monthEnd = strtotime(date('Y-m-t 23:59:59', $monthStart));
                if ($startTs <= $monthEnd && $endTs >= $monthStart) {
                    return true;
                }
            }
        }
        return false;
    }

    private static function program_view(array $program): array {
        $stays = array();
        foreach ((array) ($program['stays'] ?? array()) as $stay) {
            if (!is_array($stay)) { continue; }
            $hotel = array();
            $hotelId = strtoupper(trim((string) ($stay['hotel_id'] ?? '')));
            if ($hotelId !== '') {
                $hotel = self::hotel_by_id($hotelId);
            }
            $stays[] = array(
                'destination' => (string) ($stay['destination'] ?? ''),
                'nights' => $stay['nights'] ?? null,
                'hotel_id' => $hotelId,
                'hotel_name' => (string) ($hotel['name'] ?? $stay['unresolved_hotel_name'] ?? ''),
                'hotel' => $hotel,
            );
        }
        return array(
            'id' => (string) ($program['program_id'] ?? ''),
            'code' => (string) ($program['program_code'] ?? ''),
            'title' => (string) ($program['title'] ?? ''),
            'tier' => (string) ($program['tier'] ?? ''),
            'start_date' => (string) ($program['schedule']['start_date'] ?? ''),
            'end_date' => (string) ($program['schedule']['end_date'] ?? ''),
            'date_label' => (string) ($program['schedule']['date_label'] ?? ''),
            'duration_days' => $program['schedule']['duration_days'] ?? null,
            'duration_nights' => $program['schedule']['duration_nights'] ?? null,
            'availability' => (string) ($program['workflow']['availability'] ?? ''),
            'priority' => (int) ($program['workflow']['priority'] ?? 0),
            'pricing' => is_array($program['pricing'] ?? null) ? $program['pricing'] : array(),
            'stays' => $stays,
            'contact' => is_array($program['contact'] ?? null) ? $program['contact'] : array(),
        );
    }

    private static function hotel_view(int $postId): array {
        $keys = array(
            'hotel_id' => '_sthi_hotel_id',
            'official_name' => '_sthi_official_name',
            'stars' => '_sthi_stars',
            'city' => '_sthi_city',
            'district' => '_sthi_district',
            'neighborhood' => '_sthi_neighborhood',
            'address' => '_sthi_address',
            'checkin' => '_sthi_checkin',
            'checkout' => '_sthi_checkout',
            'wifi' => '_sthi_wifi',
            'restaurant' => '_sthi_restaurant',
            'elevator' => '_sthi_elevator',
            'meal_plan' => '_sthi_meal_plan',
            'verification_status' => '_sthi_verification_status',
            'last_verified_at' => '_sthi_last_verified_at',
        );
        $out = array('name' => get_the_title($postId));
        foreach ($keys as $key => $metaKey) {
            $out[$key] = get_post_meta($postId, $metaKey, true);
        }
        return $out;
    }
}
