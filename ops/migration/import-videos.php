<?php
$report=json_decode(file_get_contents('/tmp/video-report.json'),true);$pages=json_decode(file_get_contents('/tmp/video-pages.json'),true);$videos=[];
foreach($report as $r){if($r['state']!=='verified-file')continue;$file=basename($r['file']);$path=WP_CONTENT_DIR.'/uploads/legacy/'.$file;$existing=get_posts(['post_type'=>'attachment','meta_key'=>'_legacy_video','meta_value'=>$file,'lang'=>'','numberposts'=>1]);
if($existing)$id=$existing[0]->ID;else{$id=wp_insert_attachment(['post_title'=>'One Step Group · '.(count($videos)+1),'post_status'=>'inherit','post_mime_type'=>'video/mp4'],$path);update_post_meta($id,'_legacy_video',$file);update_post_meta($id,'_legacy_source',$r['source']);}
$videos[]=$id;
foreach($pages[$file]??[] as $url){foreach(get_posts(['post_type'=>['product','page'],'meta_key'=>'_legacy_source','meta_value'=>$url,'lang'=>'','numberposts'=>-1]) as $p){$v=get_post_meta($p->ID,'_legacy_videos',true)?:[];$v[]= $id;update_post_meta($p->ID,'_legacy_videos',array_values(array_unique($v)));}}
}
update_option('xdj_video_library',$videos);
$names=['zh-hans'=>'视频资料','zh-hant'=>'影片資料','en'=>'Videos','fr'=>'Vidéos','de'=>'Videos','it'=>'Video','es'=>'Vídeos'];$group=[];
foreach($names as $lang=>$title){$p=get_posts(['post_type'=>'page','meta_key'=>'_xdj_page_key','meta_value'=>'videos','lang'=>$lang,'numberposts'=>1]);$data=['post_type'=>'page','post_status'=>'publish','post_title'=>$title,'post_name'=>'videos-'.$lang,'post_content'=>'[xdj_video_library]'];if($p){$data['ID']=$p[0]->ID;$id=wp_update_post($data);}else $id=wp_insert_post($data);pll_set_post_language($id,$lang);update_post_meta($id,'_xdj_page_key','videos');$group[$lang]=$id;}
pll_save_post_translations($group);delete_transient('pll_languages_list');flush_rewrite_rules();WP_CLI::success('Video files and seven language library pages created.');
