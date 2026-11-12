<?php
$langs=['zh-hans','zh-hant','en','fr','de','it','es'];$groups=[];
$names=[
'zh-hans'=>['首页','产品与服务','服务地区','关于我们','联系与咨询'], 'zh-hant'=>['首頁','產品與服務','服務地區','關於我們','聯繫與諮詢'], 'en'=>['Home','Products & services','Service regions','About us','Contact'], 'fr'=>['Accueil','Produits et services','Régions desservies','À propos','Contact'], 'de'=>['Startseite','Produkte und Leistungen','Servicegebiete','Über uns','Kontakt'], 'it'=>['Home','Prodotti e servizi','Aree servite','Chi siamo','Contatti'], 'es'=>['Inicio','Productos y servicios','Zonas de servicio','Quiénes somos','Contacto']];
$intro=['zh-hant'=>'小當家為海外中餐館提供菜單、點餐及門店設備相關服務。','en'=>'One Step Group provides menu, ordering and restaurant equipment services for overseas Chinese restaurants.','fr'=>'One Step Group propose des menus, des systèmes de commande et des équipements pour les restaurants chinois à l’étranger.','de'=>'One Step Group bietet Speisekarten, Bestellsysteme und Ausstattung für chinesische Restaurants im Ausland.','it'=>'One Step Group propone menu, sistemi di ordinazione e attrezzature per i ristoranti cinesi all’estero.','es'=>'One Step Group ofrece menús, sistemas de pedidos y equipamiento para restaurantes chinos en el extranjero.'];
$keys=['home','services','markets','about','contact'];
foreach($langs as $lang){foreach($keys as $i=>$key){
 $posts=get_posts(['post_type'=>'page','post_status'=>'any','meta_key'=>'_xdj_page_key','meta_value'=>$key,'lang'=>$lang,'numberposts'=>1]);
 $id=$posts?$posts[0]->ID:0;
 if(!$id&&$lang==='zh-hans'){$p=get_page_by_path($key);$id=$p?$p->ID:0;}
 $body=$key==='services'?'[xdj_catalogue]':('<p>'.esc_html($intro[$lang]??'面向海外中餐馆的菜单、点餐和门店设备服务。').'</p>');
 if($key==='markets')$body.='<p>United Kingdom · France · Germany · Italy · Spain</p>';
 if($key==='contact')$body='[xdj_inquiry_form]';
 $post=['post_type'=>'page','post_status'=>'publish','post_title'=>$names[$lang][$i],'post_content'=>$body,'post_name'=>$key.($lang==='zh-hans'?'':'-'.$lang)];
 if($id){$post['ID']=$id;wp_update_post($post);}else $id=wp_insert_post($post);
 pll_set_post_language($id,$lang);update_post_meta($id,'_xdj_page_key',$key);$groups[$key][$lang]=$id;
} }
foreach($groups as $translations)pll_save_post_translations($translations);
update_option('page_on_front',$groups['home']['zh-hans']);update_option('show_on_front','page');flush_rewrite_rules();
WP_CLI::success('Seven language page groups configured.');
