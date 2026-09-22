<?php
if (!defined('ABSPATH')) { define('ABSPATH', __DIR__ . '/'); }
require_once dirname(__DIR__) . '/includes/class-stca-intent.php';

$cases = array(
    array('Ümre ziyareti ile ilgili bilgi alabilir miyim ekim kasım ayı gibi', 'umrah', 'tr', array(10,11)),
    array('Kasım ayında 2 kişilik fiyat nedir?', 'price', 'tr', array(11)),
    array('Temsilciye bağlanmak istiyorum', 'human', 'tr', array()),
    array('ویزای عمره چطور میشه؟', 'visa', 'fa', array()),
    array('I need an Umrah program in October', 'umrah', 'en', array(10)),
    array('Oteller hangileri?', 'hotel', 'tr', array()),
);

$fail = 0;
foreach ($cases as $i => $case) {
    [$message, $intent, $lang, $months] = $case;
    $actual = STCA_Intent::analyze($message);
    if ($actual['intent'] !== $intent || $actual['language'] !== $lang || $actual['months'] !== $months) {
        fwrite(STDERR, "FAIL case {$i}: " . json_encode($actual, JSON_UNESCAPED_UNICODE) . PHP_EOL);
        $fail++;
    }
}

$double = STCA_Intent::analyze('Kasım 2 kişilik fiyat');
if ($double['occupancy'] !== 'double') {
    fwrite(STDERR, "FAIL occupancy: " . json_encode($double, JSON_UNESCAPED_UNICODE) . PHP_EOL);
    $fail++;
}

if ($fail) { exit(1); }
echo "STCA intent tests PASS\n";
