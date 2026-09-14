<?php
if (!defined('ABSPATH')) { fwrite(STDERR, "WordPress bootstrap required\n"); exit(1); }
function stti_v071_reg_ok($condition,$message){if(!$condition){fwrite(STDERR,"FAIL: {$message}\n");exit(1);}echo "PASS: {$message}\n";}
stti_v071_reg_ok(function_exists('stti_v070_relation_review'),'v0.7 Review Relations runtime remains loaded');
stti_v071_reg_ok(function_exists('stti_completion_review_dry_run'),'v0.6.5 AI Completion runtime remains loaded');
wp_set_current_user(1);
if (!post_type_exists('sthi_hotel')) register_post_type('sthi_hotel',array('public'=>false,'show_ui'=>false,'supports'=>array('title')));
$hotel=wp_insert_post(array('post_type'=>'sthi_hotel','post_status'=>'private','post_title'=>'STTI relation fixture'));stti_v071_reg_ok(!is_wp_error($hotel)&&$hotel>0,'private Hotel identity fixture created');update_post_meta($hotel,'_sthi_hotel_id','STH-999998');
$payload=array('route'=>array('stops'=>array(array('stop_id'=>'R1'),array('stop_id'=>'R2')),'variants'=>array(array('variant_id'=>'V1','role'=>'primary','stop_refs'=>array('R1','R2'),'hotel_relation_refs'=>array('H1'),'transport_segment_refs'=>array('T1'),'review_status'=>'confirmed'))),'stays'=>array('hotels'=>array(array('relation_id'=>'H1','mode'=>'hotel_intelligence','hotel_stable_id'=>'STH-999998','option_group_id'=>'HG1','selection_status'=>'selected','review_status'=>'confirmed'))),'transport'=>array('segments'=>array(array('segment_id'=>'T1','from_stop_ref'=>'R1','to_stop_ref'=>'R2','route_variant_ref'=>'V1','review_status'=>'confirmed'))),'lifecycle'=>array('editorial'=>'approved'));
$r=stti_v070_relation_review($payload);stti_v071_reg_ok(($r['status']??'')==='ready'&&($r['ready_for_editorial_approval']??false)===true,'v0.7 fully reviewed relation graph remains approval-ready');
$pending=$payload;$pending['lifecycle']['editorial']='needs_review';$pending['route']['variants'][0]['review_status']='pending';$r=stti_v070_relation_review($pending);stti_v071_reg_ok(($r['status']??'')==='pending'&&!($r['ready_for_editorial_approval']??true),'v0.7 pending relation still fails closed');
$bad=$payload;$bad['lifecycle']['editorial']='needs_review';$bad['transport']['segments'][0]['from_stop_ref']='R404';$r=stti_v070_relation_review($bad);stti_v071_reg_ok((bool)array_filter($r['errors'],fn($e)=>str_contains($e,'unknown origin stop R404')),'v0.7 unknown stop relation still fails closed');
wp_delete_post($hotel,true);echo "STTI v0.7 Review Relations regression under v0.7.1: PASS\n";
