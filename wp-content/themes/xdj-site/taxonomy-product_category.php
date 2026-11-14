<?php
get_header();
$term = get_queried_object();
$key = preg_replace('/-(zh-hans|zh-hant|en|fr|de|it|es)$/', '', $term->slug);
$photos = ['print'=>[1,0], 'ordering'=>[9,1], 'management'=>[10,1], 'kitchen'=>[15,0]];
?>
<main id="main" class="page-content section">
    <div class="category-heading"><div><div class="eyebrow">ONE STEP GROUP / <?php echo esc_html(xdj_t('categories')); ?></div><h1><?php echo esc_html($term->name); ?></h1><?php if (isset($photos[$key])): ?><p><?php echo esc_html(xdj_design_text($key.'_intro')); ?></p><?php endif; ?><a class="text-link" href="<?php echo esc_url(xdj_page_url('contact')); ?>"><?php echo esc_html(xdj_t('contact')); ?> ↗</a></div>
    <?php if (isset($photos[$key])): [$position,$image] = $photos[$key]; echo xdj_image_html(xdj_design_image($position,$image), $term->name, 768, ['width'=>800,'height'=>600,'loading'=>'eager'], '(max-width: 600px) 90vw, 40vw'); endif; ?></div>
    <?php echo xdj_catalogue_markup($key); ?>
</main><?php get_footer(); ?>
