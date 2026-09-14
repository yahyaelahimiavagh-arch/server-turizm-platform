<?php
if (!defined('ABSPATH')) { exit; }
final class STDS_Auth {
    const MAX_SKEW=300;
    public static function verify(WP_REST_Request $request,$raw) {
        if (!defined('ST_DIRECT_SYNC_SECRET') || trim((string)ST_DIRECT_SYNC_SECRET)==='') return new WP_Error('stds_secret_missing','Direct Sync secret is not configured.',array('status'=>503));
        $timestamp=trim((string)$request->get_header('x-st-sync-timestamp'));
        $nonce=trim((string)$request->get_header('x-st-sync-nonce'));
        $key_id=trim((string)$request->get_header('x-st-sync-key-id'));
        $signature=strtolower(trim((string)$request->get_header('x-st-sync-signature')));
        $expected_key=defined('ST_DIRECT_SYNC_KEY_ID')?(string)ST_DIRECT_SYNC_KEY_ID:'server-turizm-sheets-v1';
        if (!ctype_digit($timestamp) || abs(time()-(int)$timestamp)>self::MAX_SKEW) return new WP_Error('stds_timestamp','Expired or invalid sync timestamp.',array('status'=>401));
        if (!preg_match('/^[A-Za-z0-9._:-]{16,120}$/D',$nonce)) return new WP_Error('stds_nonce','Invalid sync nonce.',array('status'=>401));
        if ($key_id==='' || !hash_equals($expected_key,$key_id)) return new WP_Error('stds_key','Invalid sync key ID.',array('status'=>401));
        if (!preg_match('/^[a-f0-9]{64}$/D',$signature)) return new WP_Error('stds_signature','Invalid sync signature.',array('status'=>401));
        $body_hash=hash('sha256',$raw);
        $canonical="ST-DIRECT-SYNC-1\n{$timestamp}\n{$nonce}\n{$body_hash}";
        $expected=hash_hmac('sha256',$canonical,(string)ST_DIRECT_SYNC_SECRET);
        if (!hash_equals($expected,$signature)) return new WP_Error('stds_signature','Invalid sync signature.',array('status'=>401));
        return array('body_hash'=>$body_hash,'nonce_hash'=>hash('sha256',$nonce));
    }
}
