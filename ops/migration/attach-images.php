<?php
$sites=json_decode(file_get_contents('/tmp/legacy.json'),true);$map=json_decode(file_get_contents('/tmp/media-map.json'),true);$thumbs=[];$attached=0;
require_once ABSPATH.'wp-admin/includes/image.php';
foreach($sites as $s){foreach($s['records'] as $r){foreach(($r['assets']??[]) as $a){if(!empty($map[$a['url']]))$thumbs[$a['alt']]=$map[$a['url']];}}}
foreach(get_posts(['post_type'=>'product','numberposts'=>-1,'lang'=>'']) as $post){
 $source=get_post_meta($post->ID,'_legacy_source',true);$site='';foreach($sites as $s)foreach($s['records'] as $r)if($r['source']===$source){$site=$r;break 2;}
 $file=($site&&!empty($map[$site['featured']]))?$map[$site['featured']]:($thumbs[$post->post_title]??'');
 if(!$file){$translations=pll_get_post_translations($post->ID);if(!empty($translations['en']))$file=$thumbs[get_the_title($translations['en'])]??'';}
 if($file){$path=WP_CONTENT_DIR.'/uploads/legacy/'.$file;$prior=get_posts(['post_type'=>'attachment','meta_key'=>'_legacy_asset','meta_value'=>$file,'numberposts'=>1,'lang'=>'']);if($prior)$id=$prior[0]->ID;else{$id=wp_insert_attachment(['post_title'=>$post->post_title,'post_mime_type'=>wp_check_filetype($path)['type'],'post_status'=>'inherit'],$path);update_post_meta($id,'_legacy_asset',$file);wp_update_attachment_metadata($id,wp_generate_attachment_metadata($id,$path));}set_post_thumbnail($post->ID,$id);$attached++;}
 $body=$post->post_content;foreach($map as $url=>$name)if($name)$body=str_replace($url,content_url('/uploads/legacy/'.$name),$body);if($body!==$post->post_content)wp_update_post(['ID'=>$post->ID,'post_content'=>$body]);
}
WP_CLI::success('Product images attached: '.$attached);
