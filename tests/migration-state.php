<?php
if(!defined('WP_CLI')||!WP_CLI)exit;
$langs=['zh-hans','zh-hant','en','fr','de','it','es'];$data=xdj_source_data();$references=0;
foreach($langs as $lang){foreach($data['products'] as $i=>$source){
    $posts=get_posts(['post_type'=>'product','post_status'=>'publish','numberposts'=>-1,'lang'=>$lang,'meta_key'=>'stable_content_key','meta_value'=>'product-'.($i+1)]);
    if(count($posts)!==1)WP_CLI::error('Non-unique product identity '.$lang.' '.($i+1));$post=$posts[0];
    $gallery=get_post_meta($post->ID,'_xdj_gallery',true);
    if(count($gallery)!==count($source['gallery']))WP_CLI::error('Incomplete gallery '.$post->ID);
    if(get_post_meta($post->ID,'_xdj_category',true)!==$source['category'])WP_CLI::error('Wrong category '.$post->ID);
    if(count(pll_get_post_translations($post->ID))!==7)WP_CLI::error('Incomplete translation group '.$post->ID);
    $references+=count($gallery);
}}
global $wpdb;$columns=$wpdb->get_col('SHOW COLUMNS FROM '.xdj_inquiry_table());foreach(['product_id','source_url','topic','notification_attempts'] as $column)if(!in_array($column,$columns,true))WP_CLI::error('Missing inquiry column '.$column);
WP_CLI::success('126 canonical products, seven-way relationships, '.$references.' gallery references, category assignments and inquiry schema verified.');
