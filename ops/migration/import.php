<?php
$data=json_decode(file_get_contents('/tmp/legacy.json'),true);
$map=json_decode(file_get_contents('/tmp/media-map.json'),true);
$site_lang=['cn-simple'=>'zh-hans','cn'=>'zh-hant','uk'=>'en','fr'=>'fr','ita'=>'it','es'=>'es'];$report=[];$groups=[];
foreach($data as $site){foreach($site['records'] as $record){
 if($record['type']!=='product')continue;
 $lang=$site_lang[$site['site']]??'en';$title=trim($record['title']);
 if(!$title)continue;
 $case=stripos($title,'Cooperative customer')!==false||stripos($title,'Cliente cooperativo')!==false;
 $key=hash('sha256',$record['source']);$existing=get_posts(['post_type'=>['product','case_study'],'post_status'=>'any','meta_key'=>'_legacy_key','meta_value'=>$key,'lang'=>'','numberposts'=>1]);
 if($site['site']==='de'){ $report[]=['source'=>$record['source'],'state'=>'duplicate-english-source','target'=>''];continue; }
 $html=$record['html'];
 foreach($map as $url=>$filename){if($filename)$html=str_replace($url,home_url('/wp-content/uploads/legacy/'.$filename),$html);}
 $body='<p class="migration-notice">'.esc_html('Legacy source / 旧站迁移资料：规格、图片文字及服务范围待人工校对。').'</p>'.$html;
 $post=['post_title'=>$title,'post_content'=>wp_kses_post($body),'post_type'=>$case?'case_study':'product','post_status'=>$case?'draft':'publish','post_name'=>'legacy-'.substr($key,0,10)];
 if($existing){$id=$existing[0]->ID;$post['ID']=$id;wp_update_post($post);}else $id=wp_insert_post($post);
 if(is_wp_error($id))continue;
 pll_set_post_language($id,$lang);update_post_meta($id,'_legacy_key',$key);update_post_meta($id,'_legacy_source',$record['source']);update_post_meta($id,'_legacy_review','required');
 if($record['featured']&&!empty($map[$record['featured']])){
  $file=$map[$record['featured']];$path=WP_CONTENT_DIR.'/uploads/legacy/'.$file;
  $attachment=get_posts(['post_type'=>'attachment','meta_key'=>'_legacy_asset','meta_value'=>$file,'numberposts'=>1,'lang'=>'']);
  if($attachment)$aid=$attachment[0]->ID;else{$aid=wp_insert_attachment(['post_mime_type'=>wp_check_filetype($path)['type'],'post_title'=>$title,'post_status'=>'inherit'],$path);update_post_meta($aid,'_legacy_asset',$file);require_once ABSPATH.'wp-admin/includes/image.php';wp_update_attachment_metadata($aid,wp_generate_attachment_metadata($aid,$path));}set_post_thumbnail($id,$aid);
 }
 $report[]=['source'=>$record['source'],'target'=>get_permalink($id),'post_id'=>$id,'language'=>$lang,'state'=>$case?'draft-case-review':'local-preview-review'];
}}
file_put_contents('/tmp/import-report.json',wp_json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));WP_CLI::success('Imported '.count($report).' source records.');
