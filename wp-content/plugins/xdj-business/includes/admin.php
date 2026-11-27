<?php
/** Branded operations dashboard; WordPress capabilities remain authoritative. */
if (!defined('ABSPATH')) exit;

add_action('init', function () {
    if (!is_admin()) return;
    $names = ['product'=>['产品管理','产品','dashicons-products'], 'solution'=>['解决方案','方案','dashicons-lightbulb'],
        'case_study'=>['案例管理','案例','dashicons-format-gallery'], 'market'=>['服务地区','地区','dashicons-location-alt']];
    foreach ($names as $type => [$name,$single,$icon]) {
        $object = get_post_type_object($type);
        if (!$object) continue;
        foreach (['name'=>$name,'singular_name'=>$single,'menu_name'=>$name,'all_items'=>'全部'.$single,
            'add_new'=>'新增'.$single,'add_new_item'=>'新增'.$single,'edit_item'=>'编辑'.$single,
            'new_item'=>'新'.$single,'view_item'=>'查看'.$single,'search_items'=>'搜索'.$single,
            'not_found'=>'暂无'.$single,'not_found_in_trash'=>'回收站暂无'.$single] as $key=>$value) {
            $object->labels->$key = $value;
        }
        $object->label = $name;
        $object->menu_icon = $icon;
    }
    $taxonomy = get_taxonomy('product_category');
    if ($taxonomy) {
        foreach (['name'=>'产品分类','singular_name'=>'产品分类','menu_name'=>'产品分类','all_items'=>'全部分类','edit_item'=>'编辑分类','add_new_item'=>'新增分类','search_items'=>'搜索分类'] as $key=>$value) $taxonomy->labels->$key = $value;
    }
}, 99);

add_action('admin_menu', function () {
    global $menu, $submenu;
    foreach ($menu as &$item) {
        if ($item[2] === 'index.php') $item[0] = '运营工作台';
        if ($item[2] === 'options-general.php') $item[0] = '系统设置';
        if ($item[2] === 'themes.php') $item[0] = '网站外观';
    }
    unset($item);
    if (isset($submenu['index.php'][0])) $submenu['index.php'][0][0] = '工作台首页';
}, 99);
add_filter('custom_menu_order', '__return_true');
add_filter('menu_order', function ($order) {
    $preferred = ['index.php','separator1','xdj-inquiries','edit.php?post_type=product','edit.php?post_type=solution',
        'edit.php?post_type=case_study','edit.php?post_type=market','edit.php?post_type=page','edit.php','upload.php',
        'mlang','separator2','themes.php','plugins.php','users.php','tools.php','options-general.php'];
    return array_merge(array_values(array_intersect($preferred, $order)), array_values(array_diff($order, $preferred)));
});
add_filter('admin_title', function ($title) {
    return str_replace('仪表盘', '运营工作台', $title);
});
add_filter('admin_footer_text', function () {
    return '小当家 · ONE STEP GROUP 运营工作台 · 基于 WordPress';
});
add_action('admin_bar_menu', function ($bar) {
    if (is_admin()) $bar->remove_node('wp-logo');
}, 100);

add_action('admin_enqueue_scripts', function () {
    wp_enqueue_style('xdj-admin', plugins_url('assets/admin.css', dirname(__DIR__).'/xdj-business.php'), [],
        filemtime(dirname(__DIR__).'/assets/admin.css'));
});
add_filter('admin_body_class', fn($classes) => $classes.' xdj-admin');
add_action('login_enqueue_scripts', function () {
    wp_enqueue_style('xdj-login', plugins_url('assets/admin.css', dirname(__DIR__).'/xdj-business.php'), [],
        filemtime(dirname(__DIR__).'/assets/admin.css'));
});
add_filter('login_headerurl', fn() => home_url('/'));
add_filter('login_headertext', fn() => '小当家 · One Step Group');

function xdj_workbench_count($type, $status = 'publish', $language = '') {
    $query = new WP_Query(['post_type'=>$type, 'post_status'=>$status, 'lang'=>$language,
        'posts_per_page'=>1, 'fields'=>'ids', 'meta_query'=>[['key'=>'_xdj_alias_of','compare'=>'NOT EXISTS']]]);
    return (int)$query->found_posts;
}

add_action('wp_dashboard_setup', function () {
    global $title;
    $title = '小当家运营工作台';
    remove_action('welcome_panel', 'wp_welcome_panel');
    foreach (['dashboard_quick_press','dashboard_primary','dashboard_right_now'] as $widget) {
        remove_meta_box($widget, 'dashboard', 'normal');
        remove_meta_box($widget, 'dashboard', 'side');
    }
    wp_add_dashboard_widget('xdj_operations', '小当家运营工作台', 'xdj_render_workbench');
    global $wp_meta_boxes;
    $normal = $wp_meta_boxes['dashboard']['normal']['core'] ?? [];
    if (isset($normal['xdj_operations'])) {
        $wp_meta_boxes['dashboard']['normal']['core'] = ['xdj_operations'=>$normal['xdj_operations']] + $normal;
    }
});

function xdj_workbench_link($path, $title, $description, $icon) {
    echo '<a class="xdj-shortcut" href="'.esc_url(admin_url($path)).'"><span class="dashicons '.esc_attr($icon).'" aria-hidden="true"></span><strong>'.esc_html($title).'</strong><span>'.esc_html($description).'</span><b aria-hidden="true">↗</b></a>';
}

function xdj_render_workbench() {
    if (!current_user_can('read')) return;
    $admin = current_user_can('manage_options');
    $edit = current_user_can('edit_posts');
    $edit_pages = current_user_can('edit_pages');
    $inquiries = 0;
    $recent = [];
    if ($admin) {
        global $wpdb;
        // Aggregate dashboard data only. Contact details remain in the protected inquiry screen.
        $inquiries = (int)$wpdb->get_var("SELECT COUNT(*) FROM ".xdj_inquiry_table()." WHERE status='new'");
        $recent = $wpdb->get_results('SELECT reference,country,status,created_at FROM '.xdj_inquiry_table().' ORDER BY id DESC LIMIT 5');
    }
    ?>
    <div class="xdj-workbench">
        <section class="xdj-welcome"><div><span class="xdj-overline">ONE STEP GROUP / OPERATIONS</span><h2>小当家运营工作台</h2><p>管理产品与多语言内容，及时跟进每一条客户询盘。</p></div><a class="button" href="<?php echo esc_url(home_url('/')); ?>">查看网站 ↗</a></section>
        <div class="xdj-metrics">
            <?php if ($admin): ?><a href="<?php echo esc_url(admin_url('admin.php?page=xdj-inquiries')); ?>"><span>待处理询盘</span><strong><?php echo esc_html(number_format_i18n($inquiries)); ?></strong><small>状态为“新询盘”的记录</small></a><?php endif; ?>
            <?php if ($edit): ?><a href="<?php echo esc_url(admin_url('edit.php?post_type=product')); ?>"><span>已发布产品</span><strong><?php echo esc_html(number_format_i18n(xdj_workbench_count('product'))); ?></strong><small>各语言版本合计，排除兼容别名</small></a><a href="<?php echo esc_url(admin_url('edit.php?post_type=product&post_status=draft')); ?>"><span>产品草稿</span><strong><?php echo esc_html(number_format_i18n(xdj_workbench_count('product','draft'))); ?></strong><small>待编辑或审核的产品版本</small></a><?php endif; ?>
            <?php if ($edit_pages): ?><a href="<?php echo esc_url(admin_url('edit.php?post_type=page')); ?>"><span>已发布页面</span><strong><?php echo esc_html(number_format_i18n(xdj_workbench_count('page'))); ?></strong><small>包含各语言页面版本</small></a><?php endif; ?>
        </div>
        <h3 class="xdj-section-title">常用操作</h3><div class="xdj-shortcuts">
            <?php
            if ($edit) xdj_workbench_link('post-new.php?post_type=product','新增产品','整理介绍、图片与分类','dashicons-products');
            if (current_user_can('upload_files')) xdj_workbench_link('upload.php','管理媒体','图片、视频和原始资料','dashicons-format-image');
            if ($admin) xdj_workbench_link('admin.php?page=xdj-inquiries','处理询盘','查看需求并更新跟进状态','dashicons-email-alt');
            if ($edit_pages) xdj_workbench_link('edit.php?post_type=page','编辑页面','更新关于我们与服务内容','dashicons-admin-page');
            ?></div>
        <div class="xdj-panel-grid">
            <?php if ($edit): ?><section class="xdj-panel"><div class="xdj-panel-heading"><h3>多语言内容</h3><span>已发布产品 / 页面</span></div><p>数量表示已发布条目，不代表翻译、规格或图片文字已经校对。</p><ul class="xdj-language-status">
            <?php foreach (['zh-hans'=>'简体中文','zh-hant'=>'繁體中文','en'=>'English','fr'=>'Français','de'=>'Deutsch','it'=>'Italiano','es'=>'Español'] as $slug=>$name): ?><li><a href="<?php echo esc_url(add_query_arg(['post_type'=>'product','lang'=>$slug], admin_url('edit.php'))); ?>"><?php echo esc_html($name); ?></a><span><?php echo esc_html(xdj_workbench_count('product','publish',$slug).' / '.xdj_workbench_count('page','publish',$slug)); ?></span></li><?php endforeach; ?></ul></section><?php endif; ?>
            <?php if ($admin): ?><section class="xdj-panel"><div class="xdj-panel-heading"><h3>最近询盘</h3><a href="<?php echo esc_url(admin_url('admin.php?page=xdj-inquiries')); ?>">查看全部 ↗</a></div><?php if (!$recent): ?><p class="xdj-empty">暂时没有询盘。新提交的需求会显示在这里。</p><?php else: ?><ul class="xdj-recent"><?php foreach ($recent as $row): ?><li><div><strong><?php echo esc_html($row->reference); ?></strong><small><?php echo esc_html($row->created_at.' UTC · '.$row->country); ?></small></div><span><?php echo esc_html(['new'=>'新询盘','assigned'=>'已分配','contacted'=>'已联系','following'=>'跟进中','converted'=>'已转化','closed'=>'已关闭'][$row->status] ?? $row->status); ?></span></li><?php endforeach; ?></ul><?php endif; ?><p>询盘已保存不等于通知邮件已经送达。</p></section><?php endif; ?>
        </div>
        <?php if ($admin): ?><section class="xdj-panel xdj-service-panel"><div><h3>服务与维护</h3><p>邮件通知：<?php echo getenv('ONESTEP_SMTP_HOST') ? '已配置传输，送达情况请查看询盘记录。' : '尚未配置 SMTP，请在后台查看和处理询盘。'; ?><br>资料助手：<?php echo xdj_ai_available() ? '已配置模型接口，回答与可用性仍需验证。' : '使用本站公开资料检索，尚未配置模型接口。'; ?></p></div><div class="xdj-maintenance-links"><a href="<?php echo esc_url(admin_url('site-health.php')); ?>">查看站点健康 →</a><a href="<?php echo esc_url(admin_url('options-general.php?page=xdj-service-settings')); ?>">网站服务设置 →</a><a href="<?php echo esc_url(admin_url('update-core.php')); ?>">版本与更新 →</a></div></section><?php endif; ?>
    </div>
    <?php
}
