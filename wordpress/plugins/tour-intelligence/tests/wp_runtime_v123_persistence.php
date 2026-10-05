<?php
if (!defined('ABSPATH')) { exit(1); }

$assert = static function($condition, $message) {
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
    echo "PASS: {$message}\n";
};

$assert(function_exists('stti_v123_merge_existing_payload'), 'v1.2.3 merge helper loaded');
$assert(function_exists('stti_v123_apply_editor_visual_fields'), 'v1.2.3 visual persistence helper loaded');
$assert(has_action('admin_post_stti_save_candidate', 'stti_handle_save_candidate') === false, 'legacy destructive save handler removed');
$assert(has_action('admin_post_stti_save_candidate', 'stti_v123_handle_save_candidate') !== false, 'lossless save handler registered');

$existing = array(
    'identity' => array('public_title'=>'Eski Tur','language'=>'tr-TR'),
    'content' => array('short_description'=>'Eski açıklama','long_description'=>'Korunacak uzun içerik'),
    'media' => array('hero_image_url'=>'https://example.test/hero-old.jpg','cover_image_url'=>'https://example.test/card-old.jpg','items'=>array(array('attachment_id'=>55))),
    'presentation' => array('hero_overlay'=>0.61,'hero_focal_x'=>66,'hero_focal_y'=>44,'hero_height'=>720),
    'publication' => array('hub_visible'=>true,'detail_route'=>true,'indexable'=>false,'sitemap'=>false),
    'extension_data' => array('owner'=>'later-module','nested'=>array('keep'=>'yes')),
    'itinerary' => array(array('day_number'=>1,'title'=>'Eski Gün')),
);

$fresh = array(
    'identity' => array('public_title'=>'Yeni Tur','language'=>'tr-TR'),
    'content' => array('short_description'=>'Yeni açıklama'),
    'media' => array('items'=>array()),
    'publication' => array('hub_visible'=>false,'detail_route'=>false,'indexable'=>false,'sitemap'=>false),
    'itinerary' => array(array('day_number'=>1,'title'=>'Yeni Gün')),
);

$merged = stti_v123_merge_existing_payload($existing, $fresh);
$assert(($merged['identity']['public_title'] ?? '') === 'Yeni Tur', 'editor-owned identity updated');
$assert(($merged['content']['short_description'] ?? '') === 'Yeni açıklama', 'short description updated');
$assert(($merged['content']['long_description'] ?? '') === 'Korunacak uzun içerik', 'extended description preserved');
$assert(($merged['media']['hero_image_url'] ?? '') === 'https://example.test/hero-old.jpg', 'Tour hero survives normal Tour save');
$assert(($merged['media']['cover_image_url'] ?? '') === 'https://example.test/card-old.jpg', 'Hub card survives normal Tour save');
$assert(($merged['presentation']['hero_height'] ?? 0) === 720, 'presentation survives normal Tour save');
$assert(!empty($merged['publication']['hub_visible']) && !empty($merged['publication']['detail_route']), 'publication gates survive normal Tour save');
$assert(($merged['extension_data']['nested']['keep'] ?? '') === 'yes', 'unknown extension data survives normal Tour save');
$assert(($merged['itinerary'][0]['title'] ?? '') === 'Yeni Gün', 'ordered editor collections use fresh value');

$visual = stti_v123_apply_editor_visual_fields($merged, array(
    'stti_v123_hero_image_url'=>'https://example.test/hero-new.jpg',
    'stti_v123_cover_image_url'=>'https://example.test/card-new.jpg',
    'stti_v123_hero_overlay'=>'0.70',
    'stti_v123_hero_focal_x'=>'71',
    'stti_v123_hero_focal_y'=>'49',
    'stti_v123_hero_height'=>'760',
));
$assert(($visual['media']['hero_image_url'] ?? '') === 'https://example.test/hero-new.jpg', 'main Tour save accepts hero image');
$assert(($visual['media']['cover_image_url'] ?? '') === 'https://example.test/card-new.jpg', 'main Tour save accepts Hub card image');
$assert((int)($visual['presentation']['hero_height'] ?? 0) === 760, 'main Tour save accepts presentation controls');
$assert(!empty($visual['publication']['hub_visible']) && !empty($visual['publication']['detail_route']), 'visual update does not alter publication gates');

echo "STTI v1.2.3 persistence runtime accepted.\n";
