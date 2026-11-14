<?php
/** Presentation copy and curated catalogue images. Existing source records stay intact. */
if (!defined('ABSPATH')) exit;

function xdj_design_text($key) {
    $copy = [
        'menu' => ['菜单导航','選單導覽','Menu','Menu','Menü','Menu','Menú'],
        'headline' => ['从一份菜单，到餐厅的每一步。','從一份菜單，到餐廳的每一步。','A thoughtful next step for your restaurant.','La prochaine étape de votre restaurant.','Der nächste Schritt für Ihr Restaurant.','Il prossimo passo per il tuo ristorante.','El siguiente paso para tu restaurante.'],
        'intro' => ['从一份菜单，到点餐收银与后厨设备。小当家为海外中餐馆梳理需求，连接设计、产品与服务。','從一份菜單，到點餐收銀與後廚設備。小當家為海外中餐館梳理需求，連接設計、產品與服務。','From the menu in your guest’s hands to ordering, POS and kitchen equipment. Explore design, products and services for Chinese restaurants abroad.','Du menu aux équipements de commande, de caisse et de cuisine : découvrez nos produits et services pour les restaurants chinois à l’étranger.','Von der Speisekarte über Bestellung und Kasse bis zur Küchenausstattung: Design, Produkte und Service für chinesische Restaurants im Ausland.','Dal menu alle ordinazioni, alla cassa e alla cucina: scopri design, prodotti e servizi per i ristoranti cinesi all’estero.','Del menú a los pedidos, el TPV y la cocina: descubre diseño, productos y servicios para restaurantes chinos en el extranjero.'],
        'explore' => ['探索产品与服务','探索產品與服務','Explore our catalogue','Explorer les produits','Produkte entdecken','Esplora il catalogo','Explorar productos'],
        'business_title' => ['从餐桌到后厨，找到所需。','從餐桌到後廚，找到所需。','From the table to the kitchen.','De la salle à la cuisine.','Vom Gastraum bis zur Küche.','Dalla sala alla cucina.','De la sala a la cocina.'],
        'print_intro' => ['让菜单与纸品，成为餐厅的第一印象。','讓菜單與紙品，成為餐廳的第一印象。','Make a first impression with menus and printed materials.','Menus et imprimés pour une première impression soignée.','Ein guter erster Eindruck mit Speisekarten und Drucksachen.','La prima impressione, con menu e materiali stampati.','Una primera impresión cuidada con menús e impresos.'],
        'ordering_intro' => ['了解点餐、收银与门店接单设备。','了解點餐、收銀與門店接單設備。','Explore ordering, POS and order-entry equipment.','Découvrez les équipements de commande et de caisse.','Bestell-, Kassen- und Auftragserfassungsgeräte entdecken.','Scopri i dispositivi per ordinazioni e cassa.','Descubre equipos para pedidos y TPV.'],
        'management_intro' => ['探索门店展示、监控与管理设备。','探索門店展示、監控與管理設備。','Discover displays, monitoring and management equipment.','Affichage, surveillance et équipements de gestion.','Displays, Überwachung und Managementgeräte entdecken.','Display, monitoraggio e dispositivi di gestione.','Pantallas, vigilancia y equipos de gestión.'],
        'kitchen_intro' => ['按实际厨房需求，了解备餐与烹饪设备。','按實際廚房需求，了解備餐與烹飪設備。','Explore preparation and cooking equipment for your kitchen.','Des équipements de préparation et de cuisson à découvrir.','Geräte für Vorbereitung und Zubereitung kennenlernen.','Scopri attrezzature per preparazione e cottura.','Descubre equipos de preparación y cocción.'],
        'featured' => ['值得仔细了解的产品。','值得仔細了解的產品。','Take a closer look.','À découvrir de plus près.','Ein genauerer Blick lohnt sich.','Uno sguardo più da vicino.','Descúbrelos de cerca.'],
        'featured_note' => ['先从这些产品开始，完整目录中还有更多选择。','先從這些產品開始，完整目錄中還有更多選擇。','A starting point. Find more options in the full catalogue.','Une sélection pour commencer. Retrouvez le reste dans le catalogue.','Eine erste Auswahl. Weitere Produkte finden Sie im Katalog.','Una selezione per iniziare. Altre opzioni nel catalogo completo.','Una selección para empezar. Más opciones en el catálogo completo.'],
        'story_title' => ['好菜单，从看得见的细节开始。','好菜單，從看得見的細節開始。','Good menus start with the details.','Un bon menu se reconnaît aux détails.','Gute Speisekarten beginnen im Detail.','Un buon menu inizia dai dettagli.','Un buen menú empieza en los detalles.'],
        'story_body' => ['折页、封面、版式与纸品，一起构成客人拿到菜单时的体验。浏览原有产品与设计资料，再与我们讨论适合门店的形式。','折頁、封面、版式與紙品，一起構成客人拿到菜單時的體驗。瀏覽原有產品與設計資料，再與我們討論適合門店的形式。','Format, cover and layout shape the way a guest discovers your menu. Browse our original catalogue and discuss the right format for your restaurant.','Format, couverture et mise en page façonnent la découverte du menu. Parcourez le catalogue et échangeons sur votre restaurant.','Format, Einband und Layout prägen den ersten Eindruck. Entdecken Sie den Katalog und besprechen Sie mit uns die passende Ausführung.','Formato, copertina e impaginazione guidano la lettura. Esplora il catalogo e parliamo della soluzione per il tuo locale.','Formato, cubierta y diseño dan forma a la experiencia. Explora el catálogo y hablemos de lo que necesita tu restaurante.'],
        'steps_title' => ['先聊需求，再走下一步。','先聊需求，再走下一步。','Start with what your restaurant needs.','Commençons par vos besoins.','Beginnen wir mit Ihrem Bedarf.','Partiamo dalle esigenze del tuo locale.','Empecemos por tus necesidades.'],
        'step_1' => ['告诉我们你的门店','告訴我們你的門店','Tell us about your restaurant','Présentez votre restaurant','Erzählen Sie uns von Ihrem Restaurant','Raccontaci del tuo ristorante','Cuéntanos sobre tu restaurante'],
        'step_1_body' => ['选择所在国家、业务类别，留下你的具体需求。','選擇所在國家、業務類別，留下你的具體需求。','Choose your country and product category, then describe your needs.','Indiquez le pays, la catégorie et votre besoin.','Land und Produktbereich wählen und den Bedarf beschreiben.','Indica paese, categoria ed esigenze.','Indica país, categoría y necesidades.'],
        'step_2' => ['一起确认细节','一起確認細節','Confirm the details','Précisons les détails','Details gemeinsam klären','Definiamo i dettagli','Confirmemos los detalles'],
        'step_2_body' => ['规格、数量、使用环境与服务范围，需要逐项核实。','規格、數量、使用環境與服務範圍，需要逐項核實。','Specifications, quantities, use and service availability need individual confirmation.','Caractéristiques, quantités, usage et disponibilité sont à confirmer.','Spezifikationen, Mengen, Einsatz und Service werden individuell bestätigt.','Specifiche, quantità, utilizzo e disponibilità vanno confermati.','Especificaciones, cantidades, uso y disponibilidad requieren confirmación.'],
        'step_3' => ['保留联系与支持入口','保留聯繫與支援入口','Keep support within reach','Gardez un accès à l’assistance','Support bleibt erreichbar','Assistenza a portata di mano','Soporte a tu alcance'],
        'step_3_body' => ['查阅产品资料、操作指南，或带着询盘编号继续沟通。','查閱產品資料、操作指南，或帶著詢盤編號繼續溝通。','Browse product information and guides, or follow up with your enquiry reference.','Consultez les guides ou reprenez contact avec votre référence de demande.','Produktinformationen und Anleitungen lesen oder mit Ihrer Anfragenummer nachfragen.','Consulta i materiali e le guide o ricontattaci con il riferimento della richiesta.','Consulta información y guías, o retoma el contacto con tu referencia.'],
        'archive_title' => ['更多原始资料，继续保留。','更多原始資料，繼續保留。','More to explore in the archive.','Les archives restent accessibles.','Weitere Einblicke im Archiv.','Altri contenuti nell’archivio.','Más contenido en el archivo.'],
        'archive_note' => ['查看旧站轮播与宣传资料','查看舊站輪播與宣傳資料','View original slides and materials','Voir les visuels et documents d’origine','Originale Folien und Unterlagen ansehen','Vedi immagini e materiali originali','Ver imágenes y materiales originales'],
        'video' => ['视频资料','影片資料','Videos','Vidéos','Videos','Video','Vídeos'],
        'print_prompt' => ['浏览成品与版式资料，告诉我们菜单形式、尺寸、数量及门店所在国家。纸张、工艺与报价以沟通确认结果为准。','瀏覽成品與版式資料，告訴我們菜單形式、尺寸、數量及門店所在國家。紙張、工藝與報價以溝通確認結果為準。','Explore the original designs and formats. Tell us your restaurant’s country, preferred format, size and quantity. Paper, finishes and pricing are confirmed individually.','Découvrez les modèles et formats. Indiquez le pays, le format, les dimensions et la quantité. Papier, finitions et prix sont à confirmer.','Entdecken Sie Designs und Formate. Nennen Sie Land, Format, Größe und Menge. Papier, Veredelung und Preise werden individuell bestätigt.','Esplora design e formati. Indica paese, formato, dimensioni e quantità. Carta, finiture e prezzi vanno confermati.','Explora diseños y formatos. Indica país, formato, tamaño y cantidad. Papel, acabados y precios requieren confirmación.'],
        'ordering_prompt' => ['从门店实际点餐和收银流程出发，了解设备与系统资料。请告知已有设备及使用需求，兼容性、安装与服务条件需进一步确认。','從門店實際點餐與收銀流程出發，了解設備與系統資料。請告知現有設備及使用需求，相容性、安裝與服務條件需進一步確認。','Explore equipment for your ordering and POS workflow. Tell us about your current setup and needs so compatibility, installation and service can be confirmed.','Explorez les équipements de commande et de caisse. Décrivez votre installation et vos besoins pour confirmer la compatibilité et les conditions de service.','Entdecken Sie Geräte für Bestellung und Kasse. Beschreiben Sie Ihre vorhandene Ausstattung und Ihren Bedarf, um Kompatibilität, Installation und Service zu klären.','Esplora i dispositivi per ordinazioni e cassa. Descrivi la configurazione attuale e le esigenze per verificare compatibilità, installazione e assistenza.','Explora equipos de pedidos y TPV. Describe tu instalación y necesidades para confirmar compatibilidad, instalación y servicio.'],
        'management_prompt' => ['浏览设备外观与使用资料，结合门店的展示或管理需求进行选择。设备型号、使用环境和安装条件请在咨询时核实。','瀏覽設備外觀與使用資料，結合門店的展示或管理需求選擇。型號、使用環境與安裝條件請於諮詢時核實。','Browse equipment and original information for your restaurant’s display or management needs. Confirm models, operating conditions and installation when enquiring.','Consultez les équipements pour l’affichage ou la gestion. Modèles, environnement et installation sont à confirmer.','Entdecken Sie Geräte für Anzeige oder Management. Modelle, Einsatzbedingungen und Installation bitte bei der Anfrage klären.','Consulta i dispositivi per visualizzazione e gestione. Modelli, ambiente di utilizzo e installazione vanno confermati.','Consulta equipos para pantallas y gestión. Confirma modelos, entorno de uso e instalación al consultar.'],
        'kitchen_prompt' => ['结合备餐与烹饪需求，浏览设备外观和原始资料。选型前请确认型号、尺寸、电源、安装空间与清洁维护要求。','結合備餐與烹飪需求，瀏覽設備外觀與原始資料。選型前請確認型號、尺寸、電源、安裝空間與清潔維護要求。','Explore equipment for food preparation and cooking. Confirm the model, dimensions, power supply, installation space and cleaning requirements before choosing.','Découvrez les équipements de préparation et de cuisson. Confirmez le modèle, les dimensions, l’alimentation, l’espace et l’entretien avant de choisir.','Entdecken Sie Geräte für Vorbereitung und Zubereitung. Modell, Maße, Stromversorgung, Platzbedarf und Reinigung vor der Auswahl klären.','Esplora attrezzature per preparazione e cottura. Conferma modello, dimensioni, alimentazione, spazio e pulizia prima della scelta.','Explora equipos de preparación y cocción. Confirma modelo, dimensiones, alimentación, espacio y limpieza antes de elegir.'],
        'details' => ['产品资料','產品資料','Product information','Informations produit','Produktinformationen','Informazioni sul prodotto','Información del producto'],
        'overview' => ['产品概览','產品概覽','Overview','Présentation','Überblick','Panoramica','Resumen'],
        'breadcrumb' => ['当前位置','目前位置','Breadcrumb','Fil d’Ariane','Brotkrumennavigation','Percorso','Ruta de navegación'],
        'skip' => ['跳到主要内容','跳至主要內容','Skip to content','Aller au contenu','Zum Inhalt','Vai al contenuto','Saltar al contenido'],
    ];
    $index = array_search(xdj_lang(), ['zh-hans','zh-hant','en','fr','de','it','es'], true);
    return $copy[$key][$index === false ? 2 : $index] ?? $key;
}

function xdj_design_image($position, $image = 0) {
    $products = xdj_source_data()['products'];
    return $products[$position]['gallery'][$image]['file'] ?? $products[$position]['gallery'][0]['file'] ?? '';
}

function xdj_design_product_url($position) {
    $posts = get_posts(['post_type'=>'product', 'lang'=>xdj_lang(), 'numberposts'=>1,
        'meta_key'=>'_xdj_position', 'meta_value'=>$position + 1,
        'meta_query'=>[['key'=>'_xdj_alias_of','compare'=>'NOT EXISTS']]]);
    return $posts ? get_permalink($posts[0]->ID) : xdj_page_url('services');
}

function xdj_design_cards($category) {
    // Two representative items per category; full catalogue remains unchanged.
    $positions = ['print'=>[2,7], 'ordering'=>[8,10], 'management'=>[11,13], 'kitchen'=>[16,18]];
    $query = new WP_Query(['post_type'=>'product', 'lang'=>xdj_lang(), 'posts_per_page'=>2,
        'meta_key'=>'_xdj_position', 'orderby'=>'meta_value_num', 'order'=>'ASC',
        'meta_query'=>[['key'=>'_xdj_position','value'=>$positions[$category],'compare'=>'IN','type'=>'NUMERIC'],
            ['key'=>'_xdj_alias_of','compare'=>'NOT EXISTS']]]);
    echo '<div class="catalogue">';
    while ($query->have_posts()) {
        $query->the_post();
        $position = (int)get_post_meta(get_the_ID(), '_xdj_position', true) - 1;
        // Use an existing clean device image instead of a video title card.
        $image = in_array($position, [9,10], true) ? 1 : 0;
        echo '<a class="service-card" href="'.esc_url(get_permalink()).'">';
        echo xdj_image_html(xdj_design_image($position, $image), get_the_title(), 768,
            ['loading'=>'lazy','width'=>600,'height'=>450], '(max-width: 600px) 90vw, (max-width: 1000px) 45vw, 22vw');
        echo '<span class="product-type">'.esc_html(xdj_t($category)).'</span><h3>'.esc_html(get_the_title()).'</h3><span class="card-arrow" aria-hidden="true">↗</span></a>';
    }
    wp_reset_postdata();
    echo '</div>';
}
