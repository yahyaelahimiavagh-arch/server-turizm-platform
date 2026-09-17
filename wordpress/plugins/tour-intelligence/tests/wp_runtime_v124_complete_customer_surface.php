<?php
if (!defined('ABSPATH')) { exit; }

function stti_v124_runtime_assert($condition, $message) {
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
    echo "PASS: {$message}\n";
}

$payload = array(
    'identity'=>array('tour_code'=>'IR-TEST','public_title'=>'İran Test Turu','short_title'=>'İran Turu','language'=>'tr-TR'),
    'destinations'=>array(
        'primary_country'=>'İran','countries'=>array('İran'),'primary_city'=>'Tahran',
        'cities'=>array('Tahran','İsfahan','Şiraz'),'departure_city'=>'İstanbul','return_city'=>'İstanbul'
    ),
    'date'=>array('start_date'=>'2026-10-10','end_date'=>'2026-10-18','duration_days'=>9,'duration_nights'=>8),
    'route'=>array('summary'=>'Tahran → İsfahan → Şiraz','stops'=>array(
        array('stop_id'=>'R1','city'=>'Tahran','country'=>'İran','note'=>'Başlangıç'),
        array('stop_id'=>'R2','city'=>'İsfahan','country'=>'İran','note'=>'Merkez'),
        array('stop_id'=>'R3','city'=>'Şiraz','country'=>'İran','note'=>'Bitiş'),
    )),
    'itinerary'=>array(array('day_number'=>1,'date'=>'2026-10-10','title'=>'Varış','city'=>'Tahran','summary'=>'Otele transfer','activities'=>'Şehir turu','meals'=>'Akşam yemeği')),
    'stays'=>array('hotels'=>array(
        array('relation_id'=>'H1','mode'=>'unresolved','unresolved_name'=>'Test Hotel','city'=>'Tahran','review_status'=>'pending','selection_status'=>'selected','check_in'=>'2026-10-10','check_out'=>'2026-10-13'),
        array('relation_id'=>'H2','mode'=>'unresolved','unresolved_name'=>'Rejected Hotel','review_status'=>'rejected','selection_status'=>'selected'),
    )),
    'transport'=>array('segments'=>array(
        array('segment_id'=>'T1','type'=>'flight','from'=>'İstanbul','to'=>'Tahran','provider'=>'TK','review_status'=>'pending'),
        array('segment_id'=>'T2','type'=>'bus','from'=>'X','to'=>'Y','review_status'=>'rejected'),
    )),
    'pricing'=>array('type'=>'exact','amount'=>2000,'currency'=>'USD','basis'=>'per_person','items'=>array(
        array('label'=>'İkili Oda','amount'=>2000,'currency'=>'USD','occupancy'=>'2_kisilik'),
        array('label'=>'Üçlü Oda','amount'=>1900,'currency'=>'USD','occupancy'=>'3_kisilik'),
    )),
    'services'=>array('included'=>array('Otel','Transfer'),'excluded'=>array('Kişisel harcamalar')),
    'requirements'=>array('visa_status'=>'required','visa_notes'=>'Vize gereklidir.','items'=>array('Pasaport')),
    'media'=>array('items'=>array(array('url'=>'https://example.invalid/gallery-1.jpg','title'=>'Tahran'))),
    'content'=>array('short_description'=>'İran kültür rotasını kapsayan test turu.'),
    'lifecycle'=>array('availability'=>'open'),
);
$surface = array(
    'stable_id'=>'STT-999999',
    'payload'=>$payload,
    'model'=>array('route_stops'=>$payload['route']['stops'],'hotels'=>array(),'transport'=>array(),'map'=>array('stops'=>array())),
);

$model = stti_v124_customer_model($surface);
stti_v124_runtime_assert($model['tour_code'] === 'IR-TEST', 'tour code is customer-model visible');
stti_v124_runtime_assert($model['short_description'] === 'İran kültür rotasını kapsayan test turu.', 'short description preserved');
stti_v124_runtime_assert($model['departure_city'] === 'İstanbul' && $model['return_city'] === 'İstanbul', 'departure and return city visible');
stti_v124_runtime_assert(count($model['route_stops']) === 3, 'all route stops visible');
stti_v124_runtime_assert(count($model['itinerary']) === 1, 'itinerary visible');
stti_v124_runtime_assert(count($model['hotels']) === 1 && ($model['hotels'][0]['name'] ?? '') === 'Test Hotel', 'pending canonical hotel visible while rejected hotel hidden');
stti_v124_runtime_assert(count($model['transport']) === 1 && ($model['transport'][0]['provider'] ?? '') === 'TK', 'pending canonical transport visible while rejected segment hidden');
stti_v124_runtime_assert(count($model['price_items']) === 2, 'all price rows visible');
stti_v124_runtime_assert(count($model['included']) === 2 && count($model['excluded']) === 1, 'included and excluded services visible');
stti_v124_runtime_assert(($model['requirements']['visa_status'] ?? '') === 'required', 'visa facts visible');
stti_v124_runtime_assert(count($model['gallery']) === 1, 'media gallery visible');
stti_v124_runtime_assert($model['availability'] === 'Rezervasyona Açık', 'availability rendered for customer');

echo "STTI v1.2.4 complete customer surface runtime PASS\n";
