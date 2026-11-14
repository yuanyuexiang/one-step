<?php
get_header();
$w = xdj_words();
$data = xdj_source_data();
$categories = ['print','ordering','management','kitchen'];
$photos = ['print'=>[1,0], 'ordering'=>[9,1], 'management'=>[10,1], 'kitchen'=>[15,0]];
?>
<main id="main" class="design-home">
<section class="editorial-hero">
    <div class="hero-copy">
        <div class="eyebrow">ONE STEP GROUP · 小當家</div>
        <h1><?php if (str_starts_with(xdj_lang(), 'zh')): foreach (explode('，', xdj_design_text('headline'), 2) as $i=>$line): ?><span class="headline-line"><?php echo esc_html($line.($i===0?'，':'')); ?></span><?php endforeach; else: echo esc_html(xdj_design_text('headline')); endif; ?></h1>
        <p><?php echo esc_html(xdj_design_text('intro')); ?></p>
        <div class="actions"><a class="button" href="<?php echo esc_url(xdj_page_url('services')); ?>"><?php echo esc_html(xdj_design_text('explore')); ?> <span aria-hidden="true">↗</span></a><a class="text-link" href="<?php echo esc_url(xdj_page_url('contact')); ?>"><?php echo esc_html(xdj_t('contact')); ?> <span aria-hidden="true">→</span></a></div>
        <div class="hero-note"><?php foreach ($categories as $key): ?><a href="<?php echo esc_url(xdj_category_url($key)); ?>"><?php echo esc_html(xdj_t($key)); ?></a><?php endforeach; ?></div>
    </div>
    <div class="hero-composition">
        <a class="hero-print" href="<?php echo esc_url(xdj_design_product_url(1)); ?>">
            <span class="photo-label">01 / <?php echo esc_html(xdj_t('print')); ?></span>
            <?php echo xdj_image_html(xdj_design_image(1), xdj_t('print'), 1280, ['width'=>900,'height'=>700,'loading'=>'eager','fetchpriority'=>'high'], '(max-width: 900px) 90vw, 48vw'); ?>
            <span class="photo-link"><?php echo esc_html(xdj_design_text('overview')); ?> ↗</span>
        </a>
        <a class="hero-device" href="<?php echo esc_url(xdj_design_product_url(9)); ?>"><?php echo xdj_image_html(xdj_design_image(9,1), xdj_t('ordering'), 320, ['width'=>300,'height'=>250,'loading'=>'lazy'], '(max-width: 600px) 110px, 150px'); ?><span><?php echo esc_html(xdj_t('ordering')); ?> ↗</span></a>
        <span class="composition-index" aria-hidden="true">DESIGN. PRINT. CONNECT.</span>
    </div>
</section>
<section class="section business-section" aria-labelledby="business-title">
    <div class="section-head"><div><div class="eyebrow">01 / <?php echo esc_html($w[1]); ?></div><h2 id="business-title"><?php echo esc_html(xdj_design_text('business_title')); ?></h2></div><a class="text-link" href="<?php echo esc_url(xdj_page_url('services')); ?>"><?php echo esc_html(xdj_t('all')); ?> ↗</a></div>
    <div class="business-grid"><?php foreach ($categories as $n=>$key): [$position,$image] = $photos[$key]; ?>
        <a class="service-card" href="<?php echo esc_url(xdj_category_url($key)); ?>"><?php echo xdj_image_html(xdj_design_image($position,$image), xdj_t($key), 768, ['class'=>'business-photo','width'=>600,'height'=>450,'loading'=>'lazy'], '(max-width: 600px) 90vw, (max-width: 1000px) 45vw, 22vw'); ?><span class="eyebrow">0<?php echo (int)$n + 1; ?></span><h3><?php echo esc_html(xdj_t($key)); ?> <span aria-hidden="true">↗</span></h3><p><?php echo esc_html(xdj_design_text($key.'_intro')); ?></p></a>
    <?php endforeach; ?></div>
</section>
<section class="print-story section">
    <a class="story-image" href="<?php echo esc_url(xdj_design_product_url(0)); ?>"><?php echo xdj_image_html(xdj_design_image(0,1), xdj_t('print'), 1280, ['width'=>1000,'height'=>750,'loading'=>'lazy'], '(max-width: 900px) 90vw, 50vw'); ?></a>
    <div class="story-copy"><div class="eyebrow">02 / <?php echo esc_html(xdj_t('print')); ?></div><h2><?php echo esc_html(xdj_design_text('story_title')); ?></h2><p><?php echo esc_html(xdj_design_text('story_body')); ?></p><a class="text-link" href="<?php echo esc_url(xdj_category_url('print')); ?>"><?php echo esc_html(xdj_t('print')); ?> ↗</a></div>
</section>
<div class="featured-collection">
    <div class="section section-head"><div><div class="eyebrow">03 / <?php echo esc_html(xdj_t('all')); ?></div><h2><?php echo esc_html(xdj_design_text('featured')); ?></h2><p><?php echo esc_html(xdj_design_text('featured_note')); ?></p></div></div>
    <div class="featured-grid"><?php foreach ($categories as $key): ?>
        <section class="section product-section" id="<?php echo esc_attr($key); ?>"><div class="section-head"><h3><?php echo esc_html(xdj_t($key)); ?></h3><a class="text-link" href="<?php echo esc_url(xdj_category_url($key)); ?>"><?php echo esc_html(xdj_t('all')); ?> ↗</a></div><?php xdj_design_cards($key); ?></section>
    <?php endforeach; ?></div>
</div>
<section class="section service-steps"><div><div class="eyebrow">04 / ONE STEP AT A TIME</div><h2><?php echo esc_html(xdj_design_text('steps_title')); ?></h2><a class="button" href="<?php echo esc_url(xdj_page_url('contact')); ?>"><?php echo esc_html(xdj_t('contact')); ?> ↗</a></div><ol><?php for ($i=1;$i<=3;$i++): ?><li><span>0<?php echo (int)$i; ?></span><div><h3><?php echo esc_html(xdj_design_text('step_'.$i)); ?></h3><p><?php echo esc_html(xdj_design_text('step_'.$i.'_body')); ?></p></div></li><?php endfor; ?></ol></section>
<section class="section region-section"><div class="section-head"><div><div class="eyebrow">05 / <?php echo esc_html($w[2]); ?></div><h2><?php echo esc_html($w[2]); ?></h2></div></div><?php echo do_shortcode('[xdj_regions]'); ?></section>
<section class="section editorial-archive"><div class="section-head"><div><div class="eyebrow">06 / ONE STEP GROUP</div><h2><?php echo esc_html(xdj_design_text('archive_title')); ?></h2></div><a class="text-link" href="<?php echo esc_url(xdj_page_url('about')); ?>"><?php echo esc_html($w[3]); ?> ↗</a></div>
    <div class="archive-links"><?php foreach (['cases','activities','support'] as $key): ?><a href="<?php echo esc_url(xdj_content_url($key)); ?>"><?php echo esc_html(xdj_t($key)); ?> <span aria-hidden="true">↗</span></a><?php endforeach; ?><a href="<?php echo esc_url(xdj_video_page_url()); ?>"><?php echo esc_html(xdj_design_text('video')); ?> ↗</a></div>
    <details class="visual-archive"><summary><?php echo esc_html(xdj_design_text('archive_note')); ?></summary><p class="source-note"><?php echo esc_html(xdj_t('image_note')); ?></p>
        <div class="hero-carousel" data-carousel data-manual aria-roledescription="carousel" aria-label="<?php echo esc_attr(xdj_t('history')); ?>"><div class="carousel-track"><?php foreach ($data['banners'] as $i=>$image): ?><a class="carousel-slide" href="<?php echo esc_url(!empty($image['video']) ? xdj_intro_video_url() : xdj_page_url('services')); ?>" <?php if (!empty($image['video'])) echo 'data-video-open'; ?> <?php echo $i ? 'hidden' : ''; ?> data-slide><?php echo xdj_image_html($image['file'], xdj_t('history').' · '.($i+1), 1280, ['width'=>1180,'height'=>720,'loading'=>'lazy'], '(max-width: 900px) 90vw, 800px'); ?></a><?php endforeach; ?></div><div class="carousel-controls"><button type="button" data-prev aria-label="<?php echo esc_attr(xdj_t('previous')); ?>">←</button><div class="carousel-dots"><?php foreach ($data['banners'] as $i=>$image): ?><button type="button" data-dot="<?php echo (int)$i; ?>" aria-label="<?php echo esc_attr(($i+1).' / '.count($data['banners'])); ?>" aria-pressed="<?php echo $i===0 ? 'true' : 'false'; ?>"></button><?php endforeach; ?></div><button type="button" data-pause data-play-label="<?php echo esc_attr(xdj_t('play')); ?>" data-pause-label="<?php echo esc_attr(xdj_t('pause')); ?>" aria-label="<?php echo esc_attr(xdj_t('play')); ?>">▶</button><button type="button" data-next aria-label="<?php echo esc_attr(xdj_t('next')); ?>">→</button></div></div>
    </details>
    <details class="visual-archive"><summary><?php echo esc_html(xdj_t('history')); ?> · ONE STEP GROUP</summary><div class="archive-statistics"><strong>2015</strong><span>ONE STEP GROUP</span><p><?php echo esc_html(xdj_t('source_note')); ?></p><div class="stat-grid"><?php foreach ([70000,300000,17000] as $value): ?><div><b data-counter="<?php echo (int)$value; ?>"><?php echo number_format_i18n($value); ?></b><span>+</span></div><?php endforeach; ?></div><small><?php echo esc_html(['zh-hans'=>'旧站披露：客户资源 / 全球中餐馆 / 设计印刷订单；统计日期未提供。','zh-hant'=>'舊站披露：客戶資源 / 全球中餐館 / 設計印刷訂單；統計日期未提供。','en'=>'Historical website figures: customer resources / Chinese restaurants worldwide / print orders. Reference date not provided.','fr'=>'Chiffres historiques : ressources clients / restaurants chinois / commandes d’impression. Date non précisée.','de'=>'Historische Website-Angaben: Kundenressourcen / chinesische Restaurants weltweit / Druckaufträge. Stichtag nicht angegeben.','it'=>'Dati storici: risorse clienti / ristoranti cinesi nel mondo / ordini di stampa. Data non indicata.','es'=>'Cifras históricas: recursos de clientes / restaurantes chinos / pedidos de impresión. Sin fecha de referencia.'][xdj_lang()]); ?></small></div></details>
</section>
<section class="cta"><div><small>LET’S TAKE THE NEXT STEP</small><h2><?php echo esc_html($w[9]); ?></h2><p><?php echo esc_html(xdj_t('region_note')); ?></p></div><a class="button light" href="<?php echo esc_url(xdj_page_url('contact')); ?>"><?php echo esc_html(xdj_t('contact')); ?> ↗</a></section>
</main><?php get_footer(); ?>
