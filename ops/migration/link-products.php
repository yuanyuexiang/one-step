<?php
$sites=json_decode(file_get_contents('/tmp/legacy.json'),true);$groups=[];$map=['cn'=>'zh-hant','cn-simple'=>'zh-hans','uk'=>'en','fr'=>'fr','ita'=>'it','es'=>'es'];
foreach($sites as $s){if(!isset($map[$s['site']]))continue;$rows=array_values(array_filter($s['records'],fn($r)=>$r['type']==='product'));
foreach(array_slice($rows,0,18) as $i=>$r){
 $slot=$i;if($s['site']==='es'){$order=[0,1,2,3,4,5,6,7,9,10,11,12,13,14,15,16,17,8];$slot=$order[$i];}
 $p=get_posts(['post_type'=>'product','meta_key'=>'_legacy_key','meta_value'=>hash('sha256',$r['source']),'lang'=>'','numberposts'=>1]);if($p)$groups[$slot][$map[$s['site']]]=$p[0]->ID;
}}
foreach($groups as $i=>$group){foreach($group as $id)update_post_meta($id,'stable_content_key','product-'.($i+1));pll_save_post_translations($group);}
delete_transient('pll_languages_list');flush_rewrite_rules();WP_CLI::success('Linked '.count($groups).' product translation groups.');
