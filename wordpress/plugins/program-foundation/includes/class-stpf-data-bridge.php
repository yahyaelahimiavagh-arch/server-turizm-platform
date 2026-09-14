<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class STPF_Data_Bridge {
    const BATCH_POST_TYPE = 'stpf_import_batch';
    const CLEAR_TOKEN = '__CLEAR__';
    const MAX_ROWS = 500;

    public static function init() {
        add_action( 'init', array( __CLASS__, 'register_batch_type' ), 30 );
        add_action( 'admin_post_stpf_export', array( __CLASS__, 'handle_export' ) );
    }

    public static function register_batch_type() {
        register_post_type( self::BATCH_POST_TYPE, array(
            'label'=>'Program Import Batches', 'public'=>false, 'publicly_queryable'=>false,
            'show_ui'=>false, 'show_in_menu'=>false, 'show_in_rest'=>false,
            'exclude_from_search'=>true, 'supports'=>array('title','editor'), 'capability_type'=>'post', 'map_meta_cap'=>true,
        ) );
    }

    public static function headers() {
        return array('schema_version','program_id','source_key','legacy_program_no','program_type','public_title','internal_label','departure_date','return_date','duration_nights','route_ref','lifecycle_state','hotel_refs','source_owner','verified_at','public_url');
    }

    public static function aliases() {
        return array(
            'schema_version'=>array('schema_version','schema'),
            'program_id'=>array('program_id','st_program_id','stable_program_id','stp_id'),
            'source_key'=>array('source_key','stable_source_key','row_key'),
            'legacy_program_no'=>array('legacy_program_no','program_no','program_number','program','programa_no'),
            'program_type'=>array('program_type','type','tour_type'),
            'public_title'=>array('public_title','title','program_title','baslik','başlık'),
            'internal_label'=>array('internal_label','internal_title','label'),
            'departure_date'=>array('departure_date','start_date','gidiş_tarihi','gidis_tarihi'),
            'return_date'=>array('return_date','end_date','dönüş_tarihi','donus_tarihi'),
            'duration_nights'=>array('duration_nights','nights','duration'),
            'route_ref'=>array('route_ref','route','destination_ref','destination'),
            'lifecycle_state'=>array('lifecycle_state','lifecycle','publication_state','state','status'),
            'hotel_refs'=>array('hotel_refs','hotel_ids','hotel_stable_ids','hotels'),
            'source_owner'=>array('source_owner','source','owner'),
            'verified_at'=>array('verified_at','updated_at','last_verified','last_updated'),
            'public_url'=>array('public_url','url','current_public_url'),
        );
    }

    public static function render_page() {
        if ( ! current_user_can( 'manage_options' ) ) return;
        $result = null;
        $raw = '';
        if ( 'POST' === $_SERVER['REQUEST_METHOD'] && isset( $_POST['stpf_bridge_action'] ) ) {
            check_admin_referer( 'stpf_bridge_action', 'stpf_bridge_nonce' );
            $action = sanitize_key( wp_unslash( $_POST['stpf_bridge_action'] ) );
            if ( 'preview' === $action ) {
                $raw = isset($_POST['stpf_raw']) ? wp_unslash($_POST['stpf_raw']) : '';
                $result = self::preview( $raw );
            } elseif ( 'apply' === $action ) {
                $token = isset($_POST['stpf_token']) ? sanitize_key(wp_unslash($_POST['stpf_token'])) : '';
                $result = self::apply( $token );
            } elseif ( 'rollback' === $action ) {
                $batch_id = isset($_POST['stpf_batch_id']) ? absint($_POST['stpf_batch_id']) : 0;
                $result = self::rollback( $batch_id );
            }
        }
        ?>
        <div class="wrap stpf-wrap">
            <h1>Program Data Bridge — H6A/H6B</h1>
            <p class="stpf-lead">Paste normalized CSV/TSV or JSON. Dry Run is mandatory before Apply. Blank cells are no-op; <code><?php echo esc_html(self::CLEAR_TOKEN); ?></code> is explicit clear.</p>
            <div class="stpf-panel">
                <p><strong>Canonical columns:</strong> <code><?php echo esc_html( implode(', ', self::headers()) ); ?></code></p>
                <p><strong>CREATE rule:</strong> leave <code>program_id</code> blank and provide a unique <code>source_key</code>. WordPress assigns the immutable <code>STP-xxxxxx</code>. On later imports, use that Program ID and keep the same source key.</p>
                <p><strong>Hotel refs:</strong> use real IDs such as <code>STH-000007</code>. Unknown IDs become INVALID; no hotel-name similarity matching exists.</p>
                <p>
                    <a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url('admin-post.php?action=stpf_export&mode=template'), 'stpf_export' ) ); ?>">Download CSV Template</a>
                    <a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url('admin-post.php?action=stpf_export&mode=data'), 'stpf_export' ) ); ?>">Export Programs CSV</a>
                </p>
            </div>
            <form method="post" class="stpf-panel">
                <?php wp_nonce_field( 'stpf_bridge_action', 'stpf_bridge_nonce' ); ?>
                <input type="hidden" name="stpf_bridge_action" value="preview">
                <h2>1. Paste data</h2>
                <textarea name="stpf_raw" class="large-text code" rows="14" placeholder="schema_version,program_id,source_key,..."><?php echo esc_textarea($raw); ?></textarea>
                <p><button class="button button-primary" type="submit">Dry Run / Preview</button></p>
            </form>
            <?php self::render_result( $result ); ?>
            <?php self::render_recent_batches(); ?>
        </div>
        <?php
    }

    private static function render_result( $result ) {
        if ( null === $result ) return;
        if ( is_wp_error($result) ) {
            echo '<div class="notice notice-error inline"><p>'.esc_html($result->get_error_message()).'</p></div>'; return;
        }
        if ( isset($result['kind']) && 'preview' === $result['kind'] ) {
            echo '<div class="stpf-panel"><h2>2. Dry Run result</h2>';
            self::render_summary($result['summary']);
            echo '<table class="widefat striped"><thead><tr><th>Row</th><th>Operation</th><th>Program ID</th><th>Source Key</th><th>Title</th><th>Hotels</th><th>Message / Changes</th></tr></thead><tbody>';
            foreach($result['plans'] as $p){
                echo '<tr><td>'.esc_html($p['row']).'</td><td><strong>'.esc_html($p['operation']).'</strong></td><td>'.esc_html($p['program_id']).'</td><td>'.esc_html($p['source_key']).'</td><td>'.esc_html($p['title']).'</td><td>'.esc_html(implode(', ',(array)$p['hotel_refs'])).'</td><td>'.esc_html($p['message']).'</td></tr>';
            }
            echo '</tbody></table>';
            if ( ($result['summary']['CREATE'] + $result['summary']['UPDATE']) > 0 ) {
                echo '<form method="post" style="margin-top:16px">'; wp_nonce_field('stpf_bridge_action','stpf_bridge_nonce');
                echo '<input type="hidden" name="stpf_bridge_action" value="apply"><input type="hidden" name="stpf_token" value="'.esc_attr($result['token']).'">';
                echo '<button class="button button-primary" type="submit">Approve Apply</button></form>';
            }
            echo '</div>';
        } elseif ( isset($result['kind']) && 'apply' === $result['kind'] ) {
            echo '<div class="notice notice-success inline"><p><strong>Apply complete.</strong> Created: '.esc_html($result['created']).' · Updated: '.esc_html($result['updated']).' · Skipped: '.esc_html($result['skipped']).'. Batch ID: '.esc_html($result['batch_id']).'</p></div>';
            if ( !empty($result['errors']) ) echo '<div class="notice notice-warning inline"><p>'.esc_html(implode(' | ',$result['errors'])).'</p></div>';
        } elseif ( isset($result['kind']) && 'rollback' === $result['kind'] ) {
            echo '<div class="notice notice-success inline"><p>Rollback finished. Restored: '.esc_html($result['restored']).' · Trashed newly-created: '.esc_html($result['trashed']).'.</p></div>';
            if(!empty($result['conflicts'])) echo '<div class="notice notice-warning inline"><p>Conflicts: '.esc_html(implode(' | ',$result['conflicts'])).'</p></div>';
        }
    }

    private static function render_summary($s){
        echo '<div class="stpf-summary">';
        foreach(array('CREATE','UPDATE','UNCHANGED','CONFLICT','INVALID') as $k) echo '<span><b>'.esc_html($k).'</b> '.esc_html(isset($s[$k])?$s[$k]:0).'</span>';
        echo '</div>';
    }

    private static function render_recent_batches(){
        $batches=get_posts(array('post_type'=>self::BATCH_POST_TYPE,'post_status'=>'private','posts_per_page'=>10,'orderby'=>'date','order'=>'DESC'));
        if(!$batches) return;
        echo '<div class="stpf-panel"><h2>Recent Import Batches</h2><table class="widefat striped"><thead><tr><th>Batch</th><th>Created</th><th>Summary</th><th>Rollback</th></tr></thead><tbody>';
        foreach($batches as $b){
            $summary=get_post_meta($b->ID,'_stpf_batch_summary',true); $rolled=get_post_meta($b->ID,'_stpf_batch_rolled_back',true);
            echo '<tr><td>#'.esc_html($b->ID).'</td><td>'.esc_html($b->post_date).'</td><td>'.esc_html(wp_json_encode($summary)).'</td><td>';
            if($rolled) echo 'Already rolled back'; else { echo '<form method="post">'; wp_nonce_field('stpf_bridge_action','stpf_bridge_nonce'); echo '<input type="hidden" name="stpf_bridge_action" value="rollback"><input type="hidden" name="stpf_batch_id" value="'.esc_attr($b->ID).'"><button class="button" type="submit" onclick="return confirm(\'Rollback this import batch?\')">Rollback</button></form>'; }
            echo '</td></tr>';
        }
        echo '</tbody></table></div>';
    }

    public static function preview( $raw ) {
        $dataset=self::parse_dataset($raw); if(is_wp_error($dataset)) return $dataset;
        $plans=array(); $summary=array('CREATE'=>0,'UPDATE'=>0,'UNCHANGED'=>0,'CONFLICT'=>0,'INVALID'=>0);
        foreach($dataset as $i=>$row){ $plan=self::plan_row($row,$i+2); $plans[]=$plan; if(isset($summary[$plan['operation']]))$summary[$plan['operation']]++; }
        $token=wp_generate_password(20,false,false);
        set_transient(self::preview_key($token),array('user_id'=>get_current_user_id(),'plans'=>$plans),20*MINUTE_IN_SECONDS);
        return array('kind'=>'preview','token'=>$token,'summary'=>$summary,'plans'=>$plans);
    }

    private static function parse_dataset($raw){
        $raw=trim((string)$raw); if(''===$raw) return new WP_Error('empty','Paste CSV/TSV or JSON first.');
        if('['===substr($raw,0,1) || '{'===substr($raw,0,1)){
            $decoded=json_decode($raw,true); if(JSON_ERROR_NONE!==json_last_error()) return new WP_Error('bad_json','Invalid JSON: '.json_last_error_msg());
            if(isset($decoded['programs']) && is_array($decoded['programs'])) $decoded=$decoded['programs'];
            if(self::is_assoc($decoded)) $decoded=array($decoded);
            if(!is_array($decoded) || !$decoded) return new WP_Error('bad_json_shape','JSON must be an object, an array of objects, or {"programs":[...]}.');
            if(count($decoded)>self::MAX_ROWS) return new WP_Error('too_many','Maximum '.self::MAX_ROWS.' rows per import.');
            $rows=array(); foreach($decoded as $obj){ if(!is_array($obj))return new WP_Error('bad_json_row','Every JSON row must be an object.'); $rows[]=self::canonicalize_assoc($obj); }
            return $rows;
        }
        $raw=preg_replace('/^\\xEF\\xBB\\xBF/','',$raw); $raw=str_replace(array("\r\n","\r"),"\n",$raw);
        $first=''; foreach(explode("\n",$raw) as $line){ if(''!==trim($line)){ $first=$line; break; } }
        $scores=array("\t"=>substr_count($first,"\t"),','=>substr_count($first,','),';'=>substr_count($first,';')); arsort($scores); $delimiter=current($scores)>0?key($scores):"\t";
        $fh=fopen('php://temp','r+'); fwrite($fh,$raw); rewind($fh); $all=array(); while(false!==($r=fgetcsv($fh,0,$delimiter))){ if(array_filter($r,function($v){return ''!==trim((string)$v);} ))$all[]=$r; if(count($all)>self::MAX_ROWS+1){fclose($fh);return new WP_Error('too_many','Maximum '.self::MAX_ROWS.' rows per import.');} } fclose($fh);
        if(count($all)<2) return new WP_Error('few_rows','CSV/TSV needs a header row and at least one data row.');
        $headers=array_map(function($v){return trim((string)$v);},array_shift($all));
        $map=self::header_map($headers); if(is_wp_error($map))return $map;
        $rows=array(); foreach($all as $r){ $assoc=array(); foreach($map as $idx=>$canonical){ if($canonical)$assoc[$canonical]=isset($r[$idx])?(string)$r[$idx]:''; } $rows[]=$assoc; }
        return $rows;
    }

    private static function canonicalize_assoc($obj){
        $out=array(); $aliases=self::aliases();
        foreach($obj as $k=>$v){ $norm=self::normalize_header($k); $canonical=''; foreach($aliases as $c=>$list){ foreach($list as $alias){ if($norm===self::normalize_header($alias)){ $canonical=$c; break 2; } } } if($canonical)$out[$canonical]=$v; }
        return $out;
    }

    private static function header_map($headers){
        $aliases=self::aliases(); $map=array(); $seen=array();
        foreach($headers as $i=>$h){ $norm=self::normalize_header($h); $canonical=''; foreach($aliases as $c=>$list){ foreach($list as $alias){ if($norm===self::normalize_header($alias)){ $canonical=$c; break 2; } } } if($canonical){ if(isset($seen[$canonical])) return new WP_Error('duplicate_header','Duplicate mapped column: '.$canonical); $seen[$canonical]=1; } $map[$i]=$canonical; }
        if(!in_array('program_id',$map,true) && !in_array('source_key',$map,true)) return new WP_Error('missing_identity','Include program_id and/or source_key column.');
        return $map;
    }

    private static function normalize_header($v){ $v=remove_accents(strtolower(trim((string)$v))); $v=preg_replace('/[^a-z0-9]+/','_',$v); return trim($v,'_'); }

    private static function plan_row($row,$row_number){
        $values=array(); $errors=array();
        foreach(self::headers() as $key){ if(!array_key_exists($key,$row))continue; $raw=$row[$key]; if(is_string($raw) && ''===trim($raw))continue; if(is_string($raw) && self::CLEAR_TOKEN===trim($raw)){ if(in_array($key,array('program_id','source_key'),true)){$errors[]=$key.' cannot be cleared.';continue;} $values[$key]=array('action'=>'clear','value'=>''); continue; }
            $norm=self::normalize($key,$raw); if(is_wp_error($norm)){$errors[]=$key.': '.$norm->get_error_message();continue;} $values[$key]=array('action'=>'set','value'=>$norm);
        }
        $program_id=self::value($values,'program_id'); $source_key=self::value($values,'source_key');
        if(!$program_id && !$source_key) $errors[]='program_id or source_key is required. CREATE requires source_key.';
        if($errors) return self::plan_base($row_number,'INVALID',$program_id,$source_key,$values,implode(' | ',$errors));
        $by_id=$program_id?STPF_Program_ID::find_by_id($program_id):0; $by_source=$source_key?self::find_by_source_key($source_key):0;
        if(-1===$by_source) return self::plan_base($row_number,'CONFLICT',$program_id,$source_key,$values,'Duplicate source_key exists on more than one Program entity. Resolve the duplicate before importing.');
        if($program_id && !$by_id) return self::plan_base($row_number,'INVALID',$program_id,$source_key,$values,'Unknown Program ID. Do not create a new entity using an unrecognized stable ID; leave program_id blank for CREATE.');
        if($by_id && $by_source && $by_id!==$by_source) return self::plan_base($row_number,'CONFLICT',$program_id,$source_key,$values,'Program ID and source_key resolve to different Program entities.');
        $post_id=$by_id?:$by_source;
        if($post_id){
            if('trash'===get_post_status($post_id)) return self::plan_base($row_number,'CONFLICT',$program_id,$source_key,$values,'Matched Program is in Trash. Restore it before import.');
            if($program_id && get_post_meta($post_id,STPF_Program_ID::META_KEY,true)!==$program_id) return self::plan_base($row_number,'CONFLICT',$program_id,$source_key,$values,'Stable Program ID mismatch.');
            $changes=self::diff($post_id,$values); $op=$changes?'UPDATE':'UNCHANGED'; $plan=self::plan_base($row_number,$op,get_post_meta($post_id,STPF_Program_ID::META_KEY,true),get_post_meta($post_id,'_stpf_source_key',true),$values,$changes?implode(' | ',array_slice($changes,0,8)):'No changes.');
            $plan['post_id']=$post_id; $before=self::snapshot($post_id); $plan['before_hash']=self::hash($before); return $plan;
        }
        if(!$source_key) return self::plan_base($row_number,'INVALID','',$source_key,$values,'CREATE requires source_key so repeat imports are idempotent.');
        if(!self::value($values,'public_title') && !self::value($values,'internal_label')) return self::plan_base($row_number,'INVALID','',$source_key,$values,'CREATE requires public_title or internal_label.');
        return self::plan_base($row_number,'CREATE','',$source_key,$values,'New Program Entity will be created and assigned a stable STP ID.');
    }

    private static function normalize($key,$raw){
        if('program_id'===$key){ $v=strtoupper(trim((string)$raw)); return STPF_Program_ID::is_valid_format($v)?$v:new WP_Error('bad_id','Expected STP-000001.'); }
        if('public_title'===$key) return sanitize_text_field((string)$raw);
        if('hotel_refs'===$key) return STPF_Meta::normalize_hotel_refs($raw,true);
        if('schema_version'===$key){$v=trim((string)$raw); return ''===$v?STPF_Meta::SCHEMA_VERSION:sanitize_text_field($v);}
        return STPF_Meta::normalize_value($key,$raw);
    }

    private static function plan_base($row,$op,$pid,$source,$values,$message){ return array('row'=>$row,'operation'=>$op,'program_id'=>$pid,'source_key'=>$source,'values'=>$values,'title'=>self::value($values,'public_title',self::value($values,'internal_label','')),'hotel_refs'=>(array)self::value($values,'hotel_refs',array()),'message'=>$message); }
    private static function value($values,$key,$default=''){ return isset($values[$key]) && 'set'===$values[$key]['action']?$values[$key]['value']:$default; }

    private static function find_by_source_key($key){
        $ids=get_posts(array('post_type'=>'stpf_program','post_status'=>'any','posts_per_page'=>2,'fields'=>'ids','meta_key'=>'_stpf_source_key','meta_value'=>$key));
        return 1===count($ids)?(int)$ids[0]:(count($ids)>1?-1:0);
    }

    private static function diff($post_id,$values){
        $changes=array(); foreach($values as $key=>$spec){ if('program_id'===$key)continue; $before=self::current($post_id,$key); $after='clear'===$spec['action']?(is_array($before)?array():''):$spec['value']; if(!self::equal($before,$after))$changes[]=$key.': '.self::display($before).' → '.self::display($after); } return $changes;
    }

    private static function current($post_id,$key){
        if('program_id'===$key)return get_post_meta($post_id,STPF_Program_ID::META_KEY,true);
        if('public_title'===$key){$p=get_post($post_id);return $p?$p->post_title:'';}
        $fields=STPF_Meta::fields(); return isset($fields[$key])?get_post_meta($post_id,$fields[$key]['meta'],true):'';
    }
    private static function equal($a,$b){ if(is_array($a)||is_array($b)){ $a=array_values((array)$a);$b=array_values((array)$b);sort($a);sort($b);return $a===$b;} return (string)$a===(string)$b; }
    private static function display($v){ if(is_array($v))return '['.implode(',',$v).']'; $v=(string)$v; return ''===$v?'∅':(strlen($v)>60?substr($v,0,57).'…':$v); }

    public static function apply($token){
        $payload=$token?get_transient(self::preview_key($token)):false; if(!is_array($payload)||!isset($payload['user_id'])||(int)$payload['user_id']!==get_current_user_id())return new WP_Error('expired','Preview expired. Run Dry Run again.');
        $created=array();$updated=array();$skipped=0;$errors=array();
        foreach($payload['plans'] as $plan){ if(!in_array($plan['operation'],array('CREATE','UPDATE'),true)){$skipped++;continue;}
            if('UPDATE'===$plan['operation']){
                $post_id=absint($plan['post_id']); if(!$post_id||'stpf_program'!==get_post_type($post_id)){$skipped++;$errors[]='Row '.$plan['row'].': Program disappeared after preview.';continue;}
                $before=self::snapshot($post_id); if(self::hash($before)!==$plan['before_hash']){$skipped++;$errors[]='Row '.$plan['row'].': Program changed after preview; skipped.';continue;}
                $r=self::apply_values($post_id,$plan['values']); if(is_wp_error($r)){self::restore($post_id,$before);$skipped++;$errors[]='Row '.$plan['row'].': '.$r->get_error_message();continue;}
                $updated[]=array('post_id'=>$post_id,'before'=>$before,'after'=>self::snapshot($post_id)); continue;
            }
            $source=self::value($plan['values'],'source_key'); if(!$source||self::find_by_source_key($source)){$skipped++;$errors[]='Row '.$plan['row'].': source_key already exists or is invalid; skipped.';continue;}
            $title=self::value($plan['values'],'public_title',self::value($plan['values'],'internal_label','Program'));
            $post_id=wp_insert_post(array('post_type'=>'stpf_program','post_status'=>'draft','post_title'=>$title),true); if(is_wp_error($post_id)){$skipped++;$errors[]='Row '.$plan['row'].': '.$post_id->get_error_message();continue;}
            if(!get_post_meta($post_id,STPF_Program_ID::META_KEY,true)){wp_trash_post($post_id);$skipped++;$errors[]='Row '.$plan['row'].': Stable Program ID assignment failed.';continue;}
            $r=self::apply_values($post_id,$plan['values']); if(is_wp_error($r)){wp_trash_post($post_id);$skipped++;$errors[]='Row '.$plan['row'].': '.$r->get_error_message();continue;}
            if(!get_post_meta($post_id,'_stpf_schema_version',true))update_post_meta($post_id,'_stpf_schema_version',STPF_Meta::SCHEMA_VERSION);
            $created[]=array('post_id'=>$post_id,'after'=>self::snapshot($post_id));
        }
        $summary=array('created'=>count($created),'updated'=>count($updated),'skipped'=>$skipped,'errors'=>count($errors)); $batch=self::store_batch($created,$updated,$summary); delete_transient(self::preview_key($token));
        return array('kind'=>'apply','created'=>count($created),'updated'=>count($updated),'skipped'=>$skipped,'errors'=>$errors,'batch_id'=>$batch);
    }

    private static function apply_values($post_id,$values){
        $fields=STPF_Meta::fields();
        foreach($values as $key=>$spec){ if('program_id'===$key)continue; if('public_title'===$key){ if('set'===$spec['action']&&''!==trim((string)$spec['value'])){ $r=wp_update_post(array('ID'=>$post_id,'post_title'=>$spec['value']),true); if(is_wp_error($r))return $r; } continue; }
            if(!isset($fields[$key]))continue; $meta=$fields[$key]['meta']; if('clear'===$spec['action'])delete_post_meta($post_id,$meta); else update_post_meta($post_id,$meta,$spec['value']);
        }
        return true;
    }

    private static function snapshot($post_id){
        $post=get_post($post_id); $meta=array(STPF_Program_ID::META_KEY=>array('exists'=>metadata_exists('post',$post_id,STPF_Program_ID::META_KEY),'value'=>get_post_meta($post_id,STPF_Program_ID::META_KEY,true)));
        foreach(STPF_Meta::fields() as $f){$k=$f['meta'];$e=metadata_exists('post',$post_id,$k);$meta[$k]=array('exists'=>$e,'value'=>$e?get_post_meta($post_id,$k,true):null);} return array('post_title'=>$post?$post->post_title:'','post_status'=>$post?$post->post_status:'draft','meta'=>$meta);
    }
    private static function hash($s){return md5(wp_json_encode($s));}
    private static function restore($post_id,$s){wp_update_post(array('ID'=>$post_id,'post_title'=>$s['post_title'],'post_status'=>$s['post_status']));foreach($s['meta'] as $k=>$spec){if(!empty($spec['exists']))update_post_meta($post_id,$k,$spec['value']);else delete_post_meta($post_id,$k);}}
    private static function store_batch($created,$updated,$summary){ if(!$created&&!$updated)return 0; $data=array('schema_version'=>'program-bridge-0.1.0','created'=>$created,'updated'=>$updated); $id=wp_insert_post(array('post_type'=>self::BATCH_POST_TYPE,'post_status'=>'private','post_title'=>'Program Import '.current_time('Y-m-d H:i:s'),'post_content'=>wp_slash(wp_json_encode($data))),true); if(is_wp_error($id))return 0; update_post_meta($id,'_stpf_batch_summary',$summary);update_post_meta($id,'_stpf_batch_user_id',get_current_user_id());return (int)$id; }

    public static function rollback($batch_id){
        if(!$batch_id||self::BATCH_POST_TYPE!==get_post_type($batch_id))return new WP_Error('bad_batch','Invalid import batch.'); if(get_post_meta($batch_id,'_stpf_batch_rolled_back',true))return new WP_Error('rolled','This batch was already rolled back.');
        $data=json_decode((string)get_post($batch_id)->post_content,true); if(!is_array($data))return new WP_Error('missing','Rollback data missing.');
        $restored=0;$trashed=0;$conflicts=array();
        foreach((array)$data['updated'] as $e){$id=absint($e['post_id']);if(!$id||'stpf_program'!==get_post_type($id))continue;$cur=self::snapshot($id);if(self::hash($cur)!==self::hash($e['after'])){$conflicts[]=get_post_meta($id,STPF_Program_ID::META_KEY,true).' changed after import';continue;}self::restore($id,$e['before']);$restored++;}
        foreach((array)$data['created'] as $e){$id=absint($e['post_id']);if(!$id||'stpf_program'!==get_post_type($id))continue;$cur=self::snapshot($id);if(self::hash($cur)!==self::hash($e['after'])){$conflicts[]=get_post_meta($id,STPF_Program_ID::META_KEY,true).' changed after import';continue;}if(wp_trash_post($id))$trashed++;}
        update_post_meta($batch_id,'_stpf_batch_rolled_back',1);update_post_meta($batch_id,'_stpf_batch_rollback_conflicts',$conflicts);return array('kind'=>'rollback','restored'=>$restored,'trashed'=>$trashed,'conflicts'=>$conflicts);
    }

    public static function handle_export(){
        if(!current_user_can('manage_options'))wp_die('Access denied.'); check_admin_referer('stpf_export'); $mode=isset($_GET['mode'])?sanitize_key(wp_unslash($_GET['mode'])):'data';
        $headers=self::headers();$rows=array(); if('template'!==$mode){$ids=get_posts(array('post_type'=>'stpf_program','post_status'=>array('draft','pending','publish','private'),'posts_per_page'=>-1,'orderby'=>'ID','order'=>'ASC','fields'=>'ids'));foreach($ids as $id){$r=array();foreach($headers as $h){$v=self::current($id,$h);if(is_array($v))$v=implode(',',$v);$r[]=(string)$v;}$rows[]=$r;}}
        $fh=fopen('php://temp','r+');fwrite($fh,"\xEF\xBB\xBF");fputcsv($fh,$headers);foreach($rows as $r)fputcsv($fh,$r);rewind($fh);$csv=stream_get_contents($fh);fclose($fh);
        nocache_headers();header('Content-Type: text/csv; charset=UTF-8');header('Content-Disposition: attachment; filename="server-turizm-programs-'.gmdate('Y-m-d-His').'.csv"');echo $csv;exit;
    }

    private static function preview_key($token){return 'stpf_bridge_'.get_current_user_id().'_'.sanitize_key($token);} private static function is_assoc($a){return is_array($a)&&array_keys($a)!==range(0,count($a)-1);}
}
STPF_Data_Bridge::init();
