<?php
if (!defined('WP_CLI') || !WP_CLI || getenv('ONESTEP_CI')!=='1') throw new RuntimeException('CI fixture only.');
$source=xdj_source_data();$languages=['zh-hans','zh-hant','en','fr','it','es'];
foreach($source['products'] as $i=>$item){$group=[];foreach($languages as $lang){
    $id=wp_insert_post(['post_type'=>'product','post_status'=>'publish','post_title'=>str_starts_with($lang,'zh')?$item['name']:'Fixture product '.($i+1),'post_content'=>'<p>Catalogue reference.</p>','post_name'=>'fixture-'.$lang.'-'.($i+1)],true);
    if(is_wp_error($id))throw new RuntimeException($id->get_error_message());pll_set_post_language($id,$lang);update_post_meta($id,'_legacy_source',$lang==='zh-hant'?$item['source']:$item['source'].'?fixture-language='.$lang);$group[$lang]=$id;
}pll_save_post_translations($group);}
WP_CLI::success('Isolated migration fixtures created.');
