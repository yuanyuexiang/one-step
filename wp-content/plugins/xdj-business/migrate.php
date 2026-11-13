<?php
// Run explicitly through WP-CLI; never import content during a visitor request.
if (!defined('WP_CLI') || !WP_CLI) exit;
if (!function_exists('pll_get_post')) WP_CLI::error('Activate Polylang before completing content migration.');
$data=xdj_source_data();$version=$data['revision'].'-5';
if (get_option('xdj_complete_revision')===$version) { WP_CLI::success('Content migration already current.'); return; }
$langs=['zh-hans','zh-hant','en','fr','de','it','es'];
foreach($langs as $lang)if(!PLL()->model->get_language($lang))WP_CLI::error('Missing language: '.$lang);
function xdj_migration_text($key,$lang) { $GLOBALS['xdj_render_language']=$lang;return xdj_t($key); }
function xdj_migration_html($html,$lang) {
    $html=str_replace('{{media}}',content_url('/uploads/legacy'),$html);
    if($lang==='zh-hant') {
        // The source Chinese remains preserved in the linked archive; template chrome is localized.
        return wp_kses_post($html);
    }
    return wp_kses_post($html);
}
function xdj_upsert_page($key,$lang,$title,$body) {
    $posts=get_posts(['post_type'=>'page','post_status'=>'any','numberposts'=>1,'lang'=>$lang,'meta_key'=>'_xdj_page_key','meta_value'=>$key]);
    if(!$posts && $lang==='zh-hans'){$existing=get_page_by_path($key);if($existing)$posts=[$existing];}
    $post=['post_type'=>'page','post_title'=>$title,'post_content'=>$body,'post_status'=>'publish','post_name'=>$key.($lang==='zh-hans'?'':'-'.$lang)];
    if($posts){$post['ID']=$posts[0]->ID;$id=wp_update_post($post,true);}else $id=wp_insert_post($post,true);
    if(is_wp_error($id))WP_CLI::error($id->get_error_message());
    update_post_meta($id,'_xdj_page_key',$key);pll_set_post_language($id,$lang);return $id;
}
$category_groups=[];
foreach(['print','ordering','management','kitchen'] as $key){foreach($langs as $lang){
    $slug=$key.'-'.$lang;$term=get_term_by('slug',$slug,'product_category');
    if(!$term){$r=wp_insert_term(xdj_migration_text($key,$lang),'product_category',['slug'=>$slug]);if(is_wp_error($r))WP_CLI::error($r->get_error_message());$id=$r['term_id'];}else $id=$term->term_id;
    pll_set_term_language($id,$lang);$category_groups[$key][$lang]=$id;
}pll_save_term_translations($category_groups[$key]);}
$de_names=['Speisekarte','Faltkarte','Tischkarte','Kalender','Tischset','Gutschein','Visitenkarte','Online-Bestellwebsite','PDA-Bestelltablet','Kassencomputer','Digitaler Werbeaufsteller','Überwachungskamera','Wand-Werbedisplay','Kompakte Kochmaschine','Kochmaschine','Gemüseschneider','Frühlingszwiebelschneider','Fleischschneider'];
$descriptions=[
'zh-hans'=>['查看原站产品图片与资料。咨询时请说明门店所在国家、所需规格和数量。','技术配置、设计稿、安装条件和报价以双方确认结果为准。'],
'zh-hant'=>['查看原站產品圖片與資料。諮詢時請說明門店所在國家、所需規格和數量。','技術配置、設計稿、安裝條件和報價以雙方確認結果為準。'],
'en'=>['Explore the original product gallery and catalogue information. Tell us your restaurant country, required format and quantity.','Confirm technical specifications, artwork, installation requirements and pricing with our team.'],
'fr'=>['Découvrez les photos et les informations du catalogue d’origine. Précisez le pays du restaurant, le format et la quantité souhaités.','Les caractéristiques, les maquettes, les conditions d’installation et le prix sont à confirmer avec notre équipe.'],
'de'=>['Entdecken Sie die Originalbilder und Kataloginformationen. Nennen Sie bei Ihrer Anfrage das Restaurantland, die benötigte Ausführung und die Menge.','Technische Daten, Gestaltung, Installationsbedingungen und Preise werden individuell bestätigt.'],
'it'=>['Consulta le immagini e le informazioni del catalogo originale. Indica il paese del ristorante, il formato e la quantità richiesti.','Specifiche, bozze grafiche, condizioni di installazione e prezzi vanno concordati con il team.'],
'es'=>['Consulta las imágenes y la información del catálogo original. Indica el país del restaurante, el formato y la cantidad necesarios.','Las especificaciones, el diseño, la instalación y el precio deben confirmarse con nuestro equipo.']];
$product_groups=[];
foreach($data['products'] as $i=>$item){
    $source=get_posts(['post_type'=>'product','post_status'=>'publish','numberposts'=>1,'lang'=>'','meta_key'=>'_legacy_source','meta_value'=>$item['source']]);
    if(!$source)WP_CLI::error('Missing source product: '.$item['source']);
    $group=pll_get_post_translations($source[0]->ID);
    if(empty($group['de'])) {
        $original=$source[0];preg_match_all('/<img\b[^>]*>/i',$original->post_content,$images);
        $id=wp_insert_post(['post_type'=>'product','post_status'=>'publish','post_title'=>$de_names[$i],'post_name'=>'produkt-'.($i+1),'post_content'=>implode('',$images[0])],true);
        if(is_wp_error($id))WP_CLI::error($id->get_error_message());pll_set_post_language($id,'de');$group['de']=$id;
        if(has_post_thumbnail($original->ID))set_post_thumbnail($id,get_post_thumbnail_id($original->ID));
        update_post_meta($id,'_legacy_source',$item['source']);
    }
    foreach($group as $lang=>$id){
        if(!in_array($lang,$langs,true))continue;
        update_post_meta($id,'_xdj_gallery',$item['gallery']);update_post_meta($id,'_xdj_category',$item['category']);update_post_meta($id,'stable_content_key','product-'.($i+1));
        update_post_meta($id,'_xdj_position',$i+1);wp_set_object_terms($id,[$category_groups[$item['category']][$lang]],'product_category');
        update_post_meta($id,'_xdj_description',implode(' ',$descriptions[$lang]));
    }
    pll_save_post_translations($group);$product_groups[]=$group;
}
// Keep legacy aliases reachable, while showing only one card for the same Spanish product.
$spanish_titles=[];foreach($product_groups as $group)if(isset($group['es']))$spanish_titles[mb_strtolower(trim(get_the_title($group['es'])))]= $group['es'];
foreach(get_posts(['post_type'=>'product','numberposts'=>-1,'lang'=>'es']) as $p){$canonical=$spanish_titles[mb_strtolower(trim($p->post_title))]??0;if($canonical&&$canonical!==$p->ID)update_post_meta($p->ID,'_xdj_alias_of',$canonical);}
$about=[
'zh-hans'=>'小当家集团于 2015 年创立，面向海外中餐馆，提供菜单设计与印刷、点餐收银、门店管理及后厨设备相关服务。旧站介绍记录了英国、欧洲和美国市场的业务发展。我们重视诚挚服务、团队协作、努力、关爱与感恩。',
'zh-hant'=>'小當家集團於 2015 年創立，面向海外中餐館，提供菜單設計與印刷、點餐收銀、門店管理及後廚設備相關服務。舊站介紹記錄了英國、歐洲與美國市場的業務發展。我們重視誠摯服務、團隊協作、努力、關愛與感恩。',
'en'=>'Founded in 2015, One Step Group serves Chinese restaurants overseas with menu design and printing, ordering and POS, restaurant management and kitchen equipment. The previous website documents its work across the UK, Europe and the United States. Its stated values are sincere service, teamwork, effort, care and gratitude.',
'fr'=>'Fondé en 2015, One Step Group accompagne les restaurants chinois à l’étranger : création et impression de menus, commande et caisse, gestion et équipements de cuisine. Le site précédent présente ses activités au Royaume-Uni, en Europe et aux États-Unis. Ses valeurs sont un service sincère, la coopération, l’effort, l’attention et la gratitude.',
'de'=>'One Step Group wurde 2015 gegründet und unterstützt chinesische Restaurants im Ausland bei Speisekartengestaltung und Druck, Bestell- und Kassensystemen, Restaurantmanagement und Küchenausstattung. Der bisherige Internetauftritt beschreibt Aktivitäten in Großbritannien, Europa und den USA. Zu den genannten Werten gehören aufrichtiger Service, Teamarbeit, Einsatz, Fürsorge und Dankbarkeit.',
'it'=>'Fondato nel 2015, One Step Group si rivolge ai ristoranti cinesi all’estero con design e stampa di menu, ordinazione e cassa, gestione e attrezzature da cucina. Il sito precedente documenta attività nel Regno Unito, in Europa e negli Stati Uniti. I valori dichiarati sono servizio sincero, collaborazione, impegno, attenzione e gratitudine.',
'es'=>'Fundado en 2015, One Step Group atiende a restaurantes chinos en el extranjero con diseño e impresión de menús, pedidos y TPV, gestión y equipamiento de cocina. El sitio anterior documenta actividades en Reino Unido, Europa y Estados Unidos. Sus valores declarados son servicio sincero, colaboración, esfuerzo, cuidado y gratitud.'];
$privacy=[
'zh-hans'=>'我们使用你提交的姓名、联系方式、门店国家、需求和来源页面处理询盘。请勿填写支付卡、身份证件或密码。询盘可由本站授权管理员查看；你可以通过联系页邮箱申请更正或删除。当前没有开启营销订阅。资料助手检索本站公开内容，不会把输入自动发送给外部模型。',
'zh-hant'=>'我們使用提交的姓名、聯絡方式、門店國家、需求與來源頁面處理詢盤。請勿填寫付款卡、身分證件或密碼。本站授權管理員可查看詢盤；可透過聯絡頁信箱申請更正或刪除。目前未開啟行銷訂閱。資料助手檢索本站公開內容，不自動向外部模型傳送輸入。',
'en'=>'We use your name, contact details, restaurant country, requirements and source page to handle your enquiry. Do not submit payment cards, identity documents or passwords. Authorised site administrators can review enquiries. Use the contact email to request correction or deletion. Marketing subscriptions are not enabled. The information assistant searches public site content and does not automatically send your input to external models.',
'fr'=>'Nous utilisons vos coordonnées, le pays du restaurant, votre demande et la page d’origine pour traiter votre demande. Ne transmettez ni carte bancaire, ni pièce d’identité, ni mot de passe. Les administrateurs autorisés peuvent consulter les demandes. Contactez-nous par e-mail pour demander une correction ou une suppression. Aucun abonnement marketing n’est activé. L’assistant recherche les contenus publics du site sans transmettre automatiquement votre saisie à un modèle externe.',
'de'=>'Wir verwenden Namen, Kontaktdaten, Restaurantland, Anliegen und Herkunftsseite zur Bearbeitung Ihrer Anfrage. Bitte keine Zahlungsdaten, Ausweisdokumente oder Passwörter übermitteln. Berechtigte Administratoren können Anfragen einsehen. Berichtigung oder Löschung können per Kontakt-E-Mail angefragt werden. Es gibt kein aktiviertes Marketing-Abonnement. Der Informationsassistent durchsucht öffentliche Website-Inhalte und sendet Eingaben nicht automatisch an externe Modelle.',
'it'=>'Usiamo nome, contatti, paese del ristorante, richiesta e pagina di origine per gestire la richiesta. Non inviare carte di pagamento, documenti di identità o password. Gli amministratori autorizzati possono consultare le richieste. Scrivi all’e-mail di contatto per rettifiche o cancellazione. Non sono attive iscrizioni marketing. L’assistente cerca nei contenuti pubblici senza inviare automaticamente il testo a modelli esterni.',
'es'=>'Usamos nombre, contacto, país del restaurante, necesidad y página de origen para gestionar la consulta. No envíes tarjetas, documentos de identidad ni contraseñas. Los administradores autorizados pueden revisar las consultas. Solicita corrección o eliminación mediante el correo de contacto. No hay suscripciones de marketing activadas. El asistente busca contenido público sin enviar automáticamente tu texto a modelos externos.'];
$groups=[];
foreach($langs as $lang){
    $GLOBALS['xdj_render_language']=$lang;$w=xdj_words();
    $groups['about'][$lang]=xdj_upsert_page('about',$lang,$w[3],'<p>'.esc_html($about[$lang]).'</p>[xdj_company_links]');
    $groups['markets'][$lang]=xdj_upsert_page('markets',$lang,$w[2],'[xdj_regions][xdj_market_archive]');
    $groups['contact'][$lang]=xdj_upsert_page('contact',$lang,$w[4],'[xdj_contact_details][xdj_inquiry_form]');
    foreach(['cases'=>'[xdj_partners]','activities'=>'[xdj_activities]','resources'=>'[xdj_resources]','support'=>'[xdj_support]','privacy'=>''] as $key=>$body){
        if($key==='privacy')$body='<p>'.esc_html($privacy[$lang]).'</p>';
        $groups[$key][$lang]=xdj_upsert_page($key,$lang,xdj_t($key),$body);
    }
    foreach($data['manuals'] as $manual){
        $body='<p>'.esc_html(xdj_t('source_note')).'</p><p>'.esc_html(xdj_t('image_note')).'</p>';
        // Keep original instructions visible with a clear source-language label; never republish defaults.
        $original=preg_replace('/(1977|00000)/','（请联系设备管理员确认并修改初始密码）',$manual['html']);
        if(str_starts_with($lang,'zh')){
            if($lang==='zh-hant')$original=$manual['html_hant']??$original;
            $body.='<div class="source-manual" lang="'.esc_attr($lang).'">'.xdj_migration_html($original,$lang).'</div>';
        }else{
            $translated=json_decode(file_get_contents(__DIR__.'/data/manuals.json'),true);
            $body.='<ol class="manual-steps">';foreach($translated[$lang][$manual['key']] as $step)$body.='<li>'.esc_html($step).'</li>';$body.='</ol>';
            preg_match_all('/<img\b[^>]*>/i',$original,$screenshots);
            $body.='<div class="source-manual">'.xdj_migration_html(implode('',$screenshots[0]),$lang).'</div><details class="source-archive"><summary>'.esc_html(xdj_t('history')).' · 中文</summary><div lang="zh-Hans">'.xdj_migration_html($original,$lang).'</div></details>';
        }
        $groups[$manual['key']][$lang]=xdj_upsert_page($manual['key'],$lang,xdj_t($manual['key']),$body);
    }
}
foreach($groups as $group)pll_save_post_translations($group);
update_option('xdj_site_archive',$data);update_option('xdj_complete_revision',$version);
unset($GLOBALS['xdj_render_language']);delete_transient('pll_languages_list');flush_rewrite_rules();
WP_CLI::success('Completed galleries, four translated categories, German products and seven-language page groups.');
