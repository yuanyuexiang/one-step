<!doctype html><html <?php language_attributes(); ?>><head><meta charset="<?php bloginfo('charset'); ?>"><meta name="viewport" content="width=device-width,initial-scale=1"><?php wp_head(); ?></head><body <?php body_class(); ?>><?php wp_body_open();$w=xdj_words(); ?>
<a class="skip" href="#main"><?php echo esc_html(xdj_design_text('skip')); ?></a><div class="notice"><span>小当家 · ONE STEP GROUP</span><a href="tel:+441416286277">+44 (0)141 628 6277</a><span>WeChat · a01414166669</span></div>
<header><a class="brand" href="<?php echo esc_url(function_exists('pll_home_url')?pll_home_url():home_url('/')); ?>"><img class="brand-logo" src="<?php echo esc_url(get_theme_file_uri('assets/logo.png')); ?>" alt="小當家 · One Step Group" width="682" height="412"></a>
<button class="nav-toggle" type="button" aria-expanded="false" aria-controls="primary-navigation" aria-label="<?php echo esc_attr(xdj_design_text('menu')); ?>"><span aria-hidden="true">☰</span></button>
<nav id="primary-navigation" aria-label="<?php echo esc_attr(xdj_design_text('menu')); ?>"><ul>
<li><details class="nav-dropdown"><summary><?php echo esc_html($w[1]); ?></summary><div><a href="<?php echo esc_url(xdj_page_url('services')); ?>"><?php echo esc_html(xdj_t('all')); ?></a><?php foreach(['print','ordering','management','kitchen'] as $key):?><a href="<?php echo esc_url(xdj_category_url($key)); ?>"><?php echo esc_html(xdj_t($key)); ?></a><?php endforeach;?></div></details></li>
<li><a href="<?php echo esc_url(xdj_content_url('cases')); ?>"><?php echo esc_html(xdj_t('cases')); ?></a></li>
<li><details class="nav-dropdown"><summary><?php echo esc_html($w[3]); ?></summary><div><a href="<?php echo esc_url(xdj_page_url('about')); ?>"><?php echo esc_html($w[3]); ?></a><a href="<?php echo esc_url(xdj_page_url('markets')); ?>"><?php echo esc_html($w[2]); ?></a><a href="<?php echo esc_url(xdj_content_url('activities')); ?>"><?php echo esc_html(xdj_t('activities')); ?></a><a href="<?php echo esc_url(xdj_video_page_url()); ?>"><?php echo esc_html(['zh-hans'=>'视频资料','zh-hant'=>'影片資料','en'=>'Videos','fr'=>'Vidéos','de'=>'Videos','it'=>'Video','es'=>'Vídeos'][xdj_lang()]); ?></a></div></details></li>
<li><a href="<?php echo esc_url(xdj_content_url('support')); ?>"><?php echo esc_html(xdj_t('support')); ?></a></li>
<li class="mobile-contact"><a href="<?php echo esc_url(xdj_page_url('contact')); ?>"><?php echo esc_html(xdj_t('contact')); ?> ↗</a></li></ul></nav><a class="button small" href="<?php echo esc_url(xdj_page_url('contact')); ?>"><?php echo esc_html($w[4]); ?> ↗</a>
<?php
$languages = function_exists('pll_the_languages') ? pll_the_languages(['raw'=>1,'hide_if_empty'=>0]) : [];
$languages = array_filter($languages ?: [], fn($language) => in_array($language['slug'], ['zh-hans','zh-hant','en','fr','de','it','es'], true));
$current_language = 'Language';
foreach ($languages as $language) if ($language['current_lang']) $current_language = $language['name'];
?>
<details class="language-switcher" id="site-language">
    <summary aria-label="<?php echo esc_attr('Language: '.$current_language); ?>"><svg aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="9"/><ellipse cx="12" cy="12" rx="4" ry="9"/><path d="M3 12h18M5 7h14M5 17h14"/></svg><span><?php echo esc_html($current_language); ?></span><svg class="language-chevron" aria-hidden="true" width="14" height="14" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><path d="m4 6 4 4 4-4"/></svg></summary>
    <ul class="language-options" aria-label="Language"><?php foreach ($languages as $language): ?>
        <li><a href="<?php echo esc_url($language['url']); ?>" lang="<?php echo esc_attr(str_replace('_','-',$language['locale'])); ?>" hreflang="<?php echo esc_attr(str_replace('_','-',$language['locale'])); ?>" <?php if ($language['current_lang']) echo 'aria-current="page"'; ?>><span><?php echo esc_html($language['name']); ?></span><?php if ($language['current_lang']): ?><span aria-hidden="true">✓</span><?php endif; ?></a></li>
    <?php endforeach; ?></ul>
</details>
<details class="header-search"><summary aria-label="<?php echo esc_attr(xdj_t('search')); ?>">⌕</summary><form role="search" method="get" action="<?php echo esc_url(function_exists('pll_home_url')?pll_home_url(xdj_lang()):home_url('/')); ?>"><label for="header-query"><?php echo esc_html(xdj_t('search_hint')); ?></label><div><input id="header-query" name="s" type="search" required maxlength="100" value="<?php echo esc_attr(get_search_query()); ?>"><button type="submit"><?php echo esc_html(xdj_t('search')); ?></button></div></form></details>
</header>
