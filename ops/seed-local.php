<?php
if (!defined('WP_CLI') || !WP_CLI) { exit; }
$pages = [
 'home'=>['首页',''],
 'services'=>['产品与服务','<p>从菜单设计到点餐与设备，围绕门店每天的经营需求选择服务。具体可售产品与交付条件需要根据所在国家确认。</p><h2>菜单设计与印刷</h2><p>菜单、折页、名片、代金券等设计印刷需求。沟通时请准备现有菜单、尺寸、数量和设计要求。</p><h2>点餐与收银系统</h2><p>在线点餐网站、点餐设备与收银设备。请说明堂食或外卖场景、现有设备及需要改善的流程。</p><h2>门店与后厨设备</h2><p>展示、监控及后厨设备。选型需核对尺寸、供电条件、安装与售后范围。</p><p><a href="/contact/">进一步咨询 →</a></p>'],
 'markets'=>['服务地区','<p>我们以国家为单位确认产品与交付条件，页面语言与服务国家分别选择。</p><h2>英国、法国、德国、意大利、西班牙</h2><p>请在沟通时提供门店所在国家与城市，我们据此核实可提供的产品、配送、安装和售后支持。</p><p>本预览版尚未录入经审核的各国联系人与承诺，未确认的服务不视为可交付。</p><p><a href="/contact/">咨询所在地区服务 →</a></p>'],
 'about'=>['关于小当家','<p>小当家与 One Step Group 面向海外中餐馆的经营需求，提供设计印刷、点餐与门店设备相关服务。</p><h2>从具体需求开始</h2><p>我们希望让门店经营者看清适用的产品、交付条件与下一步安排。公司主体、团队介绍和真实项目资料将在审核后补充。</p>'],
 'contact'=>['联系与咨询','<p>准备沟通时，可以先整理以下信息，帮助确认适合的方案。</p><h2>你的门店在哪里？</h2><p>所在国家、城市、门店名称，以及堂食或外卖的经营方式。</p><h2>现在想解决什么问题？</h2><p>菜单印刷、点餐收银、门店或后厨设备；如有现有资料、规格或期望时间，也可以一并说明。</p><h2>咨询功能正在接入</h2><p>本地预览暂未开通表单提交与 AI 客服，页面不会收集或发送你的信息。正式联系方式及询盘入口将在审核和联调后开放。</p>'],
];
foreach ($pages as $slug=>[$title,$body]) {
 $existing=get_page_by_path($slug);
 if (!$existing) {$id=wp_insert_post(['post_type'=>'page','post_status'=>'publish','post_name'=>$slug,'post_title'=>$title,'post_content'=>$body]);}
 else {$id=$existing->ID;}
 $ids[$slug]=$id;
}
update_option('show_on_front','page');update_option('page_on_front',$ids['home']);update_option('blogname','小当家 · One Step Group');update_option('blogdescription','为海外中餐馆，走好每一步');update_option('blog_public',0);
switch_theme('xdj-site');
$menu=wp_get_nav_menu_object('主导航');$menu_id=$menu?$menu->term_id:wp_create_nav_menu('主导航');
if (!wp_get_nav_menu_items($menu_id)) {foreach(['services','markets','about','contact'] as $slug)wp_update_nav_menu_item($menu_id,0,['menu-item-object-id'=>$ids[$slug],'menu-item-object'=>'page','menu-item-type'=>'post_type','menu-item-status'=>'publish']);}
$locations=get_theme_mod('nav_menu_locations',[]);$locations['primary']=$menu_id;set_theme_mod('nav_menu_locations',$locations);
activate_plugin('xdj-business/xdj-business.php');update_option('permalink_structure','/%postname%/');flush_rewrite_rules();
WP_CLI::success('Local website pages, theme and navigation configured.');
