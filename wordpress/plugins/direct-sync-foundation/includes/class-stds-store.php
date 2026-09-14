<?php
if (!defined('ABSPATH')) { exit; }
final class STDS_Store {
    public static function table() { global $wpdb; return $wpdb->prefix . 'st_direct_sync_requests'; }
    public static function install() {
        global $wpdb; require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $table=self::table(); $charset=$wpdb->get_charset_collate();
        dbDelta("CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            request_id VARCHAR(96) NOT NULL,
            nonce_hash CHAR(64) NOT NULL,
            body_hash CHAR(64) NOT NULL,
            adapter VARCHAR(16) NOT NULL,
            state VARCHAR(16) NOT NULL,
            response_json LONGTEXT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY request_id (request_id),
            UNIQUE KEY nonce_hash (nonce_hash),
            KEY created_at (created_at)
        ) {$charset};");
    }
    public static function get($request_id) {
        global $wpdb; $row=$wpdb->get_row($wpdb->prepare('SELECT * FROM '.self::table().' WHERE request_id=%s LIMIT 1',$request_id),ARRAY_A);
        return is_array($row)?$row:null;
    }
    public static function reserve($request_id,$nonce_hash,$body_hash,$adapter) {
        global $wpdb; self::prune(); $now=current_time('mysql');
        $ok=$wpdb->insert(self::table(),array('request_id'=>$request_id,'nonce_hash'=>$nonce_hash,'body_hash'=>$body_hash,'adapter'=>$adapter,'state'=>'processing','response_json'=>null,'created_at'=>$now,'updated_at'=>$now),array('%s','%s','%s','%s','%s','%s','%s','%s'));
        return $ok===1 ? true : new WP_Error('stds_replay','Request ID or nonce was already used.',array('status'=>409));
    }
    public static function complete($request_id,$response,$state='completed') {
        global $wpdb; $wpdb->update(self::table(),array('state'=>$state,'response_json'=>wp_json_encode($response,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),'updated_at'=>current_time('mysql')),array('request_id'=>$request_id),array('%s','%s','%s'),array('%s'));
    }
    public static function prune() {
        global $wpdb; $cutoff=gmdate('Y-m-d H:i:s',time()-7*DAY_IN_SECONDS);
        $wpdb->query($wpdb->prepare('DELETE FROM '.self::table().' WHERE created_at < %s',$cutoff));
    }
}
