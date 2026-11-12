<?php
$languages=[['zh-hans','zh_CN','简体中文'],['zh-hant','zh_TW','繁體中文'],['en','en_US','English'],['fr','fr_FR','Français'],['de','de_DE','Deutsch'],['it','it_IT','Italiano'],['es','es_ES','Español']];
foreach($languages as [$slug,$locale,$name]){if(!PLL()->model->get_language($slug)){ $r=PLL()->model->add_language(compact('slug','locale','name'));if(is_wp_error($r))WP_CLI::error($r->get_error_message());}}
foreach(['default_lang'=>'zh-hans','hide_default'=>false,'force_lang'=>1,'rewrite'=>true,'redirect_lang'=>true,'browser'=>false,'post_types'=>['product','solution','case_study','market']] as $k=>$v)PLL()->options->set($k,$v);
PLL()->options->save_all();delete_transient('pll_languages_list');
foreach(get_posts(['post_type'=>'page','posts_per_page'=>-1,'lang'=>'']) as $p){if(!pll_get_post_language($p->ID))pll_set_post_language($p->ID,'zh-hans');}
WP_CLI::success('Languages initialized.');
