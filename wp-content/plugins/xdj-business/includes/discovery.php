<?php
if(!defined('ABSPATH'))exit;
add_action('wp_head',function(){
    if(is_admin()||is_search()||is_404())return;
    $w=xdj_words();$description=$w[6];$schema=['@context'=>'https://schema.org','@type'=>'Organization','@id'=>home_url('/#organization'),'name'=>'One Step Group · 小当家','url'=>home_url('/'),'logo'=>get_theme_file_uri('assets/logo.png')];
    if(is_singular()){
        $id=get_queried_object_id();$description=get_post_meta($id,'_xdj_description',true)?:wp_strip_all_tags(strip_shortcodes(get_post_field('post_content',$id)));
        if(!$description)$description=get_the_title($id).' · '.$w[6];
        if(is_singular('product')){
            $images=[];foreach(get_post_meta($id,'_xdj_gallery',true)?:[] as $image)$images[]=xdj_media_url($image['file']);
            $schema=['@context'=>'https://schema.org','@type'=>'Product','name'=>get_the_title($id),'url'=>get_permalink($id),'description'=>$description,'image'=>$images,'brand'=>['@type'=>'Brand','name'=>'One Step Group'],'category'=>xdj_t(get_post_meta($id,'_xdj_category',true))];
        }
    }
    $description=mb_substr(preg_replace('/\s+/u',' ',trim($description)),0,180);
    echo '<meta name="description" content="'.esc_attr($description).'">' . "\n";
    echo '<meta property="og:title" content="'.esc_attr(wp_get_document_title()).'"><meta property="og:description" content="'.esc_attr($description).'">' . "\n";
    echo '<script type="application/ld+json">'.wp_json_encode($schema,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_HEX_TAG|JSON_HEX_AMP).'</script>' . "\n";
},8);
add_action('template_redirect',function(){
    $path=wp_parse_url($_SERVER['REQUEST_URI']??'',PHP_URL_PATH);if($path!=='/llms.txt')return;
    status_header(200);header('Content-Type: text/plain; charset=utf-8');header('Cache-Control: public, max-age=3600');
    echo "# One Step Group · 小当家\n\nRestaurant menu design, printing, ordering/POS and equipment information.\nConfirm specifications, pricing, delivery and service availability with the business.\nHistorical galleries and figures are identified as archives, not verified current customer claims.\n\n## Languages and published information\n";
    foreach(['zh-hans','zh-hant','en','fr','de','it','es'] as $lang){$GLOBALS['xdj_render_language']=$lang;foreach(['services','about','markets','support','contact'] as $key){$title=$key==='services'?xdj_words()[1]:($key==='about'?xdj_words()[3]:($key==='markets'?xdj_words()[2]:xdj_t($key)));echo '- ['.$lang.' · '.$title.']('.xdj_content_url($key).")\n";}}
    unset($GLOBALS['xdj_render_language']);exit;
},5);
