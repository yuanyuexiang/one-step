<?php
/** Read-only dashboard rendering and capability checks; run with wp eval-file. */
function xdj_admin_check($condition, $message) {
    if (!$condition) throw new RuntimeException($message);
}
$previous = wp_get_current_user();
$users = get_users(['role'=>'administrator','number'=>1]);
xdj_admin_check((bool)$users, 'Administrator fixture required');
try {
    wp_set_current_user($users[0]->ID);
    ob_start();
    xdj_render_workbench();
    $html = ob_get_clean();
    foreach (['小当家运营工作台','待处理询盘','site-health.php','xdj-inquiries','zh-hans','zh-hant','English','Español'] as $text) {
        xdj_admin_check(str_contains($html, $text), 'Missing dashboard content: '.$text);
    }
    global $wpdb;
    $expected = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} p WHERE p.post_type='product' AND p.post_status='publish' AND NOT EXISTS (SELECT 1 FROM {$wpdb->postmeta} m WHERE m.post_id=p.ID AND m.meta_key='_xdj_alias_of')");
    xdj_admin_check(xdj_workbench_count('product') === $expected, 'All-language product count mismatch');
    $limited = clone wp_get_current_user();
    $limited->allcaps = ['read'=>true,'edit_posts'=>true];
    $GLOBALS['current_user'] = $limited;
    ob_start();
    xdj_render_workbench();
    $limited_html = ob_get_clean();
    foreach (['xdj-inquiries','最近询盘','网站服务设置','site-health.php'] as $private) {
        xdj_admin_check(!str_contains($limited_html, $private), 'Private dashboard content leaked: '.$private);
    }
    wp_set_current_user(0);
    ob_start();
    xdj_render_workbench();
    xdj_admin_check(ob_get_clean() === '', 'Anonymous rendering must be empty');
} finally {
    $GLOBALS['current_user'] = $previous;
}
echo "Admin workbench checks passed\n";
