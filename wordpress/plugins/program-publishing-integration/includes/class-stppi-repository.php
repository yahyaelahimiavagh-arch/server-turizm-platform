<?php
if (!defined('ABSPATH')) { exit; }
final class STPPI_Repository {
    public static function dependencies() {
        if (!defined('STPI_VERSION') || !in_array(STPI_VERSION, array('0.3.2','0.3.3','0.3.4','0.3.5'), true) || !defined('STPI_DIR') || !defined('STPI_SCHEMA_VERSION') || STPI_SCHEMA_VERSION !== '1.0.0') {
            return new WP_Error('dependency', 'Program Intelligence 0.3.2/0.3.3/0.3.4/0.3.5 + ST-TDE 1.0.0 gerekli.');
        }
        foreach (array('contract','lifecycle','hotel-adapter') as $file) { require_once STPI_DIR . 'includes/class-stpi-' . $file . '.php'; }
        return true;
    }
    public static function registry() {
        $r = get_option('stppi_registry', array()); return is_array($r) ? $r : array();
    }
    public static function save_registry($r) { update_option('stppi_registry', is_array($r)?$r:array(), false); }
    public static function all_rows() {
        $dep=self::dependencies(); if (is_wp_error($dep)) return $dep;
        $ids=get_posts(array('post_type'=>'stpi_program','post_status'=>array('private','draft','pending','publish','future','trash'),'posts_per_page'=>-1,'fields'=>'ids','orderby'=>'ID','order'=>'ASC','no_found_rows'=>true));
        $rows=array(); $dupes=array();
        foreach($ids as $post_id) {
            $stable=(string)get_post_meta($post_id,'_stpi_program_id',true);
            if(!preg_match('/^STP-[0-9]{6}$/D',$stable)) continue;
            $post=get_post($post_id); $p=$post?json_decode($post->post_content,true):null;
            if(!is_array($p)) continue;
            if(isset($rows[$stable])) { $dupes[]=$stable; continue; }
            $rows[$stable]=array('program_id'=>$stable,'post_id'=>(int)$post_id,'post_status'=>$post->post_status,'program'=>$p,'payload_hash'=>STPI_Contract::payload_hash($p),'post_hash'=>STPI_Contract::payload_hash(get_object_vars($post)),'metadata_hash'=>STPI_Contract::payload_hash(get_post_meta($post_id)));
        }
        ksort($rows);
        return array('rows'=>$rows,'duplicates'=>array_values(array_unique($dupes)));
    }
    public static function row($program_id) {
        $all=self::all_rows(); if(is_wp_error($all)) return $all;
        return $all['rows'][$program_id] ?? new WP_Error('not_found','Program bulunamadı.');
    }
    public static function temporal($p) { return class_exists('STPI_Lifecycle') ? STPI_Lifecycle::temporal_state($p) : 'unknown'; }
    public static function effective($p) { return class_exists('STPI_Lifecycle') ? STPI_Lifecycle::effective_state($p) : 'unknown'; }
    public static function hotel_ids($p) {
        $ids=array(); foreach((array)($p['stays']??array()) as $s){$id=(string)($s['hotel_id']??'');if(preg_match('/^STH-[0-9]{6}$/D',$id))$ids[$id]=true;} return array_keys($ids);
    }
    public static function review_url($post_id) { return add_query_arg(array('page'=>'stpi-program-view','program_post'=>(int)$post_id),admin_url('admin.php')); }
    public static function suggested_slug($p) {
        $title=sanitize_title((string)($p['title']??'program')) ?: 'program';
        $code=sanitize_title((string)($p['program_code']??''));
        $slug=$code ? $title.'-'.$code : $title;
        $slug=trim(substr($slug,0,80),'-'); return $slug ?: 'program';
    }
    public static function find_by_code($code) {
        $all=self::all_rows(); if(is_wp_error($all)) return array(); $found=array();
        foreach($all['rows'] as $row){ if((string)($row['program']['program_code']??'')===(string)$code) $found[]=$row; }
        return $found;
    }
}
