<?php
if (!defined('ABSPATH')) { define('ABSPATH', __DIR__ . '/'); }
function current_time($type) { return $type === 'Y-m-d' ? '2026-09-22' : '2026-09-22 12:00:00'; }
function wp_date($format, $timestamp = null) { return date($format, $timestamp ?: time()); }
function get_posts($args = array()) { return array(); }
function get_post_meta($id, $key, $single = false) { return ''; }
function get_the_title($id) { return ''; }

class STPI_Store {
    public static function all_programs() {
        return array(
            array('post_id' => 1, 'program' => array(
                'program_id' => 'STP-000901',
                'program_code' => 'TEST-OCT-NOV',
                'service_type' => 'umrah',
                'publication_mode' => 'scheduled_departure',
                'title' => 'Ekim-Kasım Test Ümre',
                'locale' => 'tr_TR',
                'tier' => 'Ekonomik',
                'workflow' => array('editorial' => 'approved', 'schedule' => 'scheduled', 'availability' => 'open', 'priority' => 20),
                'schedule' => array('date_precision' => 'exact', 'start_date' => '2099-10-29', 'end_date' => '2099-11-07', 'duration_days' => 10, 'duration_nights' => 9),
                'destinations' => array(), 'segments' => array(), 'stays' => array(),
                'pricing' => array('currency' => 'USD', 'price_type' => 'fixed', 'entries' => array(
                    array('occupancy' => 'double', 'amount' => 2100, 'unit' => 'per_person'),
                    array('occupancy' => 'quad', 'amount' => 1900, 'unit' => 'per_person'),
                )),
                'provenance' => array('source_type' => 'google_sheets'),
            )),
            array('post_id' => 2, 'program' => array(
                'program_id' => 'STP-000902', 'service_type' => 'umrah', 'publication_mode' => 'archived_departure',
                'title' => 'Archived', 'workflow' => array('editorial' => 'archived', 'schedule' => 'scheduled', 'availability' => 'closed'),
                'schedule' => array('start_date' => '2025-10-01', 'end_date' => '2025-10-09'), 'stays' => array(), 'pricing' => array(),
            )),
        );
    }
}

function stti_get_candidates() {
    return array(
        array(
            'stable_id' => 'STT-000002', 'public_title' => 'Hidden Test Tour', 'editorial' => 'approved', 'schedule_status' => 'scheduled', 'temporal' => 'future',
            'payload' => json_encode(array('identity' => array('public_title' => 'Hidden Test Tour'), 'lifecycle' => array('editorial' => 'approved', 'schedule' => 'scheduled', 'temporal' => 'future'), 'publication' => array('hub_visible' => false))),
        ),
        array(
            'stable_id' => 'STT-000099', 'public_title' => 'Visible Real Tour', 'editorial' => 'approved', 'schedule_status' => 'scheduled', 'temporal' => 'future',
            'payload' => json_encode(array('identity' => array('public_title' => 'Visible Real Tour'), 'date' => array('start_date' => '2099-12-01', 'end_date' => '2099-12-05'), 'destinations' => array('primary_country' => 'Testland'), 'lifecycle' => array('editorial' => 'approved', 'schedule' => 'scheduled', 'temporal' => 'future'), 'publication' => array('hub_visible' => true))),
        ),
    );
}

require_once dirname(__DIR__) . '/includes/class-stca-config.php';
require_once dirname(__DIR__) . '/includes/class-stca-intent.php';
require_once dirname(__DIR__) . '/includes/class-stca-data.php';
require_once dirname(__DIR__) . '/includes/class-stca-responder.php';

function fail_test($message, $data = null) {
    fwrite(STDERR, "FAIL: {$message}" . ($data !== null ? ' ' . json_encode($data, JSON_UNESCAPED_UNICODE) : '') . PHP_EOL);
    exit(1);
}

$r = STCA_Responder::reply('Ümre ziyareti ile ilgili bilgi alabilir miyim ekim kasım ayı gibi');
if ($r['intent'] !== 'umrah') { fail_test('wrong intent', $r); }
if (count($r['matches']) !== 1) { fail_test('canonical filtering failed', $r); }
if (strpos($r['text'], 'Ekim-Kasım Test Ümre') === false) { fail_test('matching title missing', $r['text']); }
if (strpos($r['text'], STCA_Config::UMRah_URL) === false) { fail_test('official Umrah URL missing', $r['text']); }
if (strpos($r['text'], STCA_Config::PHONE) === false || strpos($r['text'], STCA_Config::MOBILE) === false) { fail_test('contacts missing', $r['text']); }

$r2 = STCA_Responder::reply('Kasım ayında 2 kişilik fiyat nedir?');
if (strpos($r2['text'], '2,100 USD') === false) { fail_test('double price not selected', $r2['text']); }

$tours = STCA_Data::tours(array(), 10);
if (count($tours) !== 1 || $tours[0]['id'] !== 'STT-000099') { fail_test('tour visibility gate failed', $tours); }

echo "STCA responder/data tests PASS\n";
