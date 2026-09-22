<?php
if (!defined('ABSPATH')) { exit(1); }

function stca_test_fail($message) {
    fwrite(STDERR, "FAIL: {$message}\n");
    exit(1);
}

if (!class_exists('STCA_Responder')) { stca_test_fail('STCA_Responder missing'); }
if (!class_exists('STCA_Data')) { stca_test_fail('STCA_Data missing'); }
if (!class_exists('STCA_Meta')) { stca_test_fail('STCA_Meta missing'); }

$routes = rest_get_server()->get_routes();
if (!isset($routes['/server-turizm/v1/instagram/webhook'])) { stca_test_fail('Instagram webhook route missing'); }
if (!isset($routes['/server-turizm/v1/stca/simulate'])) { stca_test_fail('Simulator route missing'); }
if (!isset($routes['/server-turizm/v1/stca/health'])) { stca_test_fail('Health route missing'); }

$program = array(
    'program_id' => 'STP-999999',
    'service_type' => 'umrah',
    'publication_mode' => 'scheduled_departure',
    'title' => 'Runtime Test Umre',
    'tier' => 'Ekonomik',
    'workflow' => array('editorial' => 'approved', 'schedule' => 'scheduled', 'availability' => 'open'),
    'schedule' => array('start_date' => '2099-10-10', 'end_date' => '2099-10-18', 'duration_days' => 9, 'duration_nights' => 8),
    'stays' => array(),
    'pricing' => array('currency' => 'USD', 'price_type' => 'fixed', 'entries' => array(array('occupancy' => 'double', 'amount' => 1999, 'unit' => 'per_person'))),
);
if (!STCA_Data::program_customer_eligible($program)) { stca_test_fail('eligible program rejected'); }
$program['workflow']['schedule'] = 'cancelled';
if (STCA_Data::program_customer_eligible($program)) { stca_test_fail('cancelled program accepted'); }

$publicTour = array('editorial' => 'approved', 'schedule_status' => 'scheduled', 'temporal' => 'future');
$payload = array('lifecycle' => array('editorial' => 'approved', 'schedule' => 'scheduled', 'temporal' => 'future'), 'publication' => array('hub_visible' => true));
if (!STCA_Data::tour_customer_eligible($publicTour, $payload)) { stca_test_fail('hub-visible tour rejected'); }
$payload['publication']['hub_visible'] = false;
if (STCA_Data::tour_customer_eligible($publicTour, $payload)) { stca_test_fail('hub-hidden tour leaked'); }
$payload['publication'] = array();
if (STCA_Data::tour_customer_eligible($publicTour, $payload)) { stca_test_fail('missing hub_visible leaked'); }

$analysis = STCA_Intent::analyze('Ümre ekim kasım fiyat');
if ($analysis['intent'] !== 'price' || $analysis['months'] !== array(10,11)) { stca_test_fail('intent runtime mismatch'); }

if (STCA_Config::instagram_enabled()) { stca_test_fail('Instagram must default OFF in disposable runtime'); }

echo "STCA WordPress runtime tests PASS\n";
