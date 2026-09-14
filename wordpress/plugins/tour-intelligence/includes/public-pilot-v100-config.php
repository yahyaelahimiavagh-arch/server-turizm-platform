<?php
/** STTI v1.0.0 controlled public pilot configuration. */
if (!defined('ABSPATH')) { exit; }
function stti_v100_public_config(){
    return array(
        'contract'=>'STTI-PUBLIC-PILOT-1.0.0',
        'stable_id'=>'STT-000001',
        'path'=>'/turlar/buyuk-iran-kultur-turu/',
        'options'=>array(
            'master'=>'stti_v100_public_master',
            'route'=>'stti_v100_public_route',
            'indexation'=>'stti_v100_indexation',
            'sitemap'=>'stti_v100_sitemap',
            'schema'=>'stti_v100_schema',
            'canonical'=>'stti_v100_canonical'
        )
    );
}
function stti_v100_option_enabled($name){return get_option((string)$name,'0')==='1';}
function stti_v100_gate_state(){
    $cfg=stti_v100_public_config();$out=array();
    foreach($cfg['options'] as $key=>$name)$out[$key]=stti_v100_option_enabled($name);
    return $out;
}
function stti_v100_public_url(){return home_url(stti_v100_public_config()['path']);}
