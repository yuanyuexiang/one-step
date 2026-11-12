<?php
if (!defined('ABSPATH')) exit;
function xdj_inquiry_table() { global $wpdb; return $wpdb->prefix.'xdj_inquiries'; }
add_action('init', function () {
    if (get_option('xdj_inquiry_schema') === '1') return;
    global $wpdb;
    require_once ABSPATH.'wp-admin/includes/upgrade.php';
    dbDelta('CREATE TABLE '.xdj_inquiry_table()." (
        id bigint unsigned NOT NULL AUTO_INCREMENT,
        request_key varchar(64) NOT NULL,
        payload_hash varchar(64) NOT NULL,
        reference varchar(40) NOT NULL,
        name varchar(120) NOT NULL,
        email varchar(190) NOT NULL DEFAULT '',
        phone varchar(80) NOT NULL DEFAULT '',
        country varchar(2) NOT NULL,
        service varchar(40) NOT NULL,
        message text NOT NULL,
        locale varchar(20) NOT NULL,
        status varchar(20) NOT NULL DEFAULT 'new',
        notification varchar(20) NOT NULL DEFAULT 'pending',
        created_at datetime NOT NULL,
        PRIMARY KEY (id), UNIQUE KEY request_key (request_key), UNIQUE KEY reference (reference)
    ) ".$wpdb->get_charset_collate().';');
    update_option('xdj_inquiry_schema','1');
});
add_action('rest_api_init', function () {
    register_rest_route('xdj/v1','/inquiry', [
        'methods'=>'POST','permission_callback'=>'__return_true','callback'=>'xdj_submit_inquiry',
    ]);
});
function xdj_submit_inquiry(WP_REST_Request $request) {
    if (strlen($request->get_body()) > 32768) return new WP_Error('too_large','提交内容过长。',['status'=>413]);
    $origin=$request->get_header('origin');
    if ($origin && wp_parse_url($origin,PHP_URL_HOST)!==wp_parse_url(home_url(),PHP_URL_HOST)) return new WP_Error('origin','请求来源不正确。',['status'=>403]);
    $v=$request->get_json_params();
    if (!is_array($v) || !empty($v['website'])) return new WP_Error('invalid','无法处理此提交。',['status'=>400]);
    $key=sanitize_text_field($request->get_header('idempotency-key'));
    if (!preg_match('/^[a-zA-Z0-9-]{20,64}$/',$key)) return new WP_Error('key','提交标识无效，请刷新后重试。',['status'=>422]);
    $data=['name'=>sanitize_text_field($v['name']??''),'email'=>sanitize_email($v['email']??''),'phone'=>sanitize_text_field($v['phone']??''),'country'=>sanitize_text_field($v['country']??''),'service'=>sanitize_text_field($v['service']??''),'message'=>sanitize_textarea_field($v['message']??''),'locale'=>sanitize_key($v['locale']??'zh-hans')];
    if (!$data['name'] || strlen($data['name'])>120 || !$data['message'] || strlen($data['message'])>15000 || !in_array($data['country'],['GB','FR','DE','IT','ES','CN','ZZ'],true) || !in_array($data['service'],['print','ordering','equipment','other'],true)) return new WP_Error('fields','请填写姓名、所在国家、服务类型和需求。',['status'=>422]);
    if ((!$data['email'] && !$data['phone']) || (!empty($v['email']) && !is_email($v['email'])) || strlen($data['phone'])>80 || strlen($data['email'])>190) return new WP_Error('contact','请填写有效邮箱或联系电话。',['status'=>422]);
    if (empty($v['consent'])) return new WP_Error('consent','请确认允许业务人员联系你。',['status'=>422]);
    global $wpdb;
    if (!in_array($data['locale'],['zh-hans','zh-hant','en','fr','de','it','es'],true)) return new WP_Error('locale','Invalid language.',['status'=>422]);
    $hash=hash('sha256',wp_json_encode($data));
    $existing=$wpdb->get_row($wpdb->prepare('SELECT reference,payload_hash FROM '.xdj_inquiry_table().' WHERE request_key=%s',$key));
    if ($existing) return hash_equals($existing->payload_hash,$hash)?new WP_REST_Response(['reference'=>$existing->reference,'saved'=>true],200):new WP_Error('conflict','这次提交的信息已改变，请重新提交。',['status'=>409]);
    $ip_key='xdj_rate_'.hash_hmac('sha256',$_SERVER['REMOTE_ADDR']??'unknown',wp_salt());
    $count=(int)get_transient($ip_key);
    if ($count>=20) return new WP_Error('rate','提交过于频繁，请稍后重试。',['status'=>429]);
    set_transient($ip_key,$count+1,HOUR_IN_SECONDS);
    $data+=['request_key'=>$key,'payload_hash'=>$hash,'reference'=>'OS-'.strtoupper(wp_generate_password(12,false,false)),'created_at'=>current_time('mysql',true)];
    $ok=$wpdb->insert(xdj_inquiry_table(),$data);
    if (!$ok) {
        $existing=$wpdb->get_row($wpdb->prepare('SELECT reference,payload_hash FROM '.xdj_inquiry_table().' WHERE request_key=%s',$key));
        if ($existing && hash_equals($existing->payload_hash,$hash)) return new WP_REST_Response(['reference'=>$existing->reference,'saved'=>true],200);
        return new WP_Error('save_failed','暂时无法保存，请保留信息并稍后重试。',['status'=>503]);
    }
    // Local-first persistence; outbound mail stays disabled until a provider is configured.
    return new WP_REST_Response(['reference'=>$data['reference'],'saved'=>true],201);
}
add_shortcode('xdj_inquiry_form',function () {
    wp_enqueue_script('xdj-inquiry',plugins_url('../assets/inquiry.js',__FILE__),[], '0.2.0',true);
    ob_start(); ?>
    <form class="inquiry-form" data-endpoint="<?php echo esc_url(rest_url('xdj/v1/inquiry')); ?>">
    <p class="form-note">本地开发测试：提交将保存在本站后台，暂不发送外部邮件。</p>
    <div class="form-grid"><label>你的称呼 *<input name="name" autocomplete="name" required maxlength="80"></label><label>门店所在国家 *<select name="country" required><option value="">请选择</option><option value="GB">英国</option><option value="FR">法国</option><option value="DE">德国</option><option value="IT">意大利</option><option value="ES">西班牙</option><option value="CN">中国</option><option value="ZZ">其他国家</option></select></label><label>电子邮箱<input type="email" name="email" autocomplete="email" maxlength="190"></label><label>联系电话<input type="tel" name="phone" autocomplete="tel" maxlength="80"></label></div>
    <p class="field-help">邮箱与电话至少填写一项。</p><label>感兴趣的服务 *<select name="service" required><option value="print">菜单设计与印刷</option><option value="ordering">点餐与收银</option><option value="equipment">门店与后厨设备</option><option value="other">其他咨询</option></select></label><label>说说你的需求 *<textarea name="message" rows="6" required maxlength="5000" placeholder="例如：门店所在城市、需要的产品、数量和期望安排。"></textarea></label><div class="honeypot" aria-hidden="true"><label>Website<input name="website" tabindex="-1" autocomplete="off"></label></div><label class="consent"><input type="checkbox" name="consent" required>我同意将以上信息用于处理本次咨询及联系跟进。</label><button class="button" type="submit">提交需求 ↗</button><div class="form-result" role="status" aria-live="polite"></div></form>
    <?php return ob_get_clean();
});
add_action('admin_menu',function(){add_menu_page('网站询盘','网站询盘','manage_options','xdj-inquiries','xdj_inquiry_admin','dashicons-email-alt',26);});
function xdj_inquiry_admin() {
    if (!current_user_can('manage_options')) return;
    global $wpdb;
    $states=['new'=>'新询盘','assigned'=>'已分配','contacted'=>'已联系','following'=>'跟进中','converted'=>'已转化','closed'=>'已关闭'];
    if ($_SERVER['REQUEST_METHOD']==='POST') {
        check_admin_referer('xdj_inquiry_status');
        $status=sanitize_key($_POST['status']??'');
        if (isset($states[$status])) $wpdb->update(xdj_inquiry_table(),['status'=>$status],['id'=>absint($_POST['lead_id']??0)]);
    }
    $page=max(1,absint($_GET['paged']??1));$offset=($page-1)*25;
    $rows=$wpdb->get_results($wpdb->prepare('SELECT * FROM '.xdj_inquiry_table().' ORDER BY id DESC LIMIT 25 OFFSET %d',$offset));
    echo '<div class="wrap"><h1>网站询盘</h1><p>当前仅管理员可查看。通知尚未接入，所有记录保持待通知。</p><table class="widefat striped"><thead><tr><th>编号 / 时间 UTC</th><th>联系人</th><th>地区 / 业务</th><th>需求</th><th>状态</th></tr></thead><tbody>';
    foreach($rows as $r){echo '<tr><td>'.esc_html($r->reference).'<br>'.esc_html($r->created_at).'</td><td>'.esc_html($r->name).'<br>'.esc_html($r->email).'<br>'.esc_html($r->phone).'</td><td>'.esc_html($r->country).' / '.esc_html($r->service).'</td><td style="max-width:400px;white-space:pre-wrap">'.esc_html($r->message).'</td><td><form method="post">';wp_nonce_field('xdj_inquiry_status');echo '<input type="hidden" name="lead_id" value="'.absint($r->id).'"><select name="status">';foreach($states as $k=>$label)echo '<option value="'.esc_attr($k).'" '.selected($r->status,$k,false).'>'.esc_html($label).'</option>';echo '</select><button class="button">保存</button></form></td></tr>';}
    echo '</tbody></table><p>';if($page>1)echo '<a href="'.esc_url(add_query_arg('paged',$page-1)).'">上一页</a> ';if(count($rows)===25)echo '<a href="'.esc_url(add_query_arg('paged',$page+1)).'">下一页</a>';echo '</p></div>';
}
