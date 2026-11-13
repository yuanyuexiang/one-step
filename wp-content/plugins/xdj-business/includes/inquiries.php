<?php
if (!defined('ABSPATH')) exit;
function xdj_client_address() {
    $remote=$_SERVER['REMOTE_ADDR']??'unknown';
    $proxy=gethostbyname('traefik');
    if($proxy!=='traefik'&&hash_equals($proxy,$remote)){
        $real=$_SERVER['HTTP_X_REAL_IP']??'';
        if(filter_var($real,FILTER_VALIDATE_IP))return $real;
    }
    return $remote;
}
function xdj_inquiry_table() { global $wpdb; return $wpdb->prefix.'xdj_inquiries'; }
add_action('init', function () {
    if (get_option('xdj_inquiry_schema') === '2') return;
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
        product_id bigint unsigned NOT NULL DEFAULT 0,
        source_url varchar(500) NOT NULL DEFAULT '',
        topic varchar(120) NOT NULL DEFAULT '',
        notification_attempts int NOT NULL DEFAULT 0,
        created_at datetime NOT NULL,
        PRIMARY KEY (id), UNIQUE KEY request_key (request_key), UNIQUE KEY reference (reference)
    ) ".$wpdb->get_charset_collate().';');
    update_option('xdj_inquiry_schema','2');
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
    if (!$data['name'] || strlen($data['name'])>120 || !$data['message'] || strlen($data['message'])>15000 || !in_array($data['country'],['GB','FR','DE','IT','ES','US','CN','ZZ'],true) || !in_array($data['service'],['print','ordering','equipment','other'],true)) return new WP_Error('fields','请填写姓名、所在国家、服务类型和需求。',['status'=>422]);
    if ((!$data['email'] && !$data['phone']) || (!empty($v['email']) && !is_email($v['email'])) || strlen($data['phone'])>80 || strlen($data['email'])>190) return new WP_Error('contact','请填写有效邮箱或联系电话。',['status'=>422]);
    if (empty($v['consent'])) return new WP_Error('consent','请确认允许业务人员联系你。',['status'=>422]);
    global $wpdb;
    if (!in_array($data['locale'],['zh-hans','zh-hant','en','fr','de','it','es'],true)) return new WP_Error('locale','Invalid language.',['status'=>422]);
    $product_id=absint($v['product_id']??0);
    if($product_id&&(get_post_type($product_id)!=='product'||get_post_status($product_id)!=='publish'))return new WP_Error('product','Invalid product.',['status'=>422]);
    $source=esc_url_raw($v['source_url']??'');
    if($source&&wp_parse_url($source,PHP_URL_HOST)!==wp_parse_url(home_url(),PHP_URL_HOST))return new WP_Error('source','Invalid source page.',['status'=>422]);
    $data['product_id']=$product_id;$data['source_url']=$source?home_url(wp_parse_url($source,PHP_URL_PATH)?:'/'):'';$data['topic']=mb_substr(sanitize_text_field($v['topic']??''),0,120);
    $hash=hash('sha256',wp_json_encode($data));
    $existing=$wpdb->get_row($wpdb->prepare('SELECT reference,payload_hash FROM '.xdj_inquiry_table().' WHERE request_key=%s',$key));
    if ($existing) return hash_equals($existing->payload_hash,$hash)?new WP_REST_Response(['reference'=>$existing->reference,'saved'=>true],200):new WP_Error('conflict','这次提交的信息已改变，请重新提交。',['status'=>409]);
    $ip_key='xdj_rate_'.hash_hmac('sha256',xdj_client_address(),wp_salt());
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
    if(get_option('xdj_notification_email'))wp_schedule_single_event(time()+5,'xdj_process_notifications');
    return new WP_REST_Response(['reference'=>$data['reference'],'saved'=>true,'notification'=>'pending'],201);
}
add_shortcode('xdj_inquiry_form',function () {
    wp_enqueue_script('xdj-inquiry',plugins_url('../assets/inquiry.js',__FILE__),[], filemtime(dirname(__DIR__).'/assets/inquiry.js'),true);
    ob_start(); ?>
    <form class="inquiry-form" data-endpoint="<?php echo esc_url(rest_url('xdj/v1/inquiry')); ?>" data-saved="<?php echo esc_attr(xdj_t('saved')); ?>" data-saved-note="<?php echo esc_attr(xdj_t('saved_note')); ?>">
    <?php $product=is_singular('product')?get_the_ID():absint($_GET['product']??0);if($product&&(get_post_type($product)!=='product'||get_post_status($product)!=='publish'))$product=0; ?>
    <input type="hidden" name="product_id" value="<?php echo (int)$product; ?>"><input type="hidden" name="topic" value="<?php echo esc_attr(mb_substr(sanitize_text_field($_GET['topic']??''),0,120)); ?>">
    <?php if($product):?><p class="inquiry-context"><?php echo esc_html(get_the_title($product)); ?></p><?php endif;?>
    <div class="form-grid"><label>你的称呼 *<input name="name" autocomplete="name" required maxlength="80"></label><label>门店所在国家 *<select name="country" required><option value="">请选择</option><option value="GB">英国</option><option value="FR">法国</option><option value="DE">德国</option><option value="IT">意大利</option><option value="ES">西班牙</option><option value="US"><?php echo esc_html(['zh-hans'=>'美国','zh-hant'=>'美國','en'=>'United States','fr'=>'États-Unis','de'=>'USA','it'=>'Stati Uniti','es'=>'Estados Unidos'][xdj_lang()]); ?></option><option value="CN">中国</option><option value="ZZ">其他国家</option></select></label><label>电子邮箱<input type="email" name="email" autocomplete="email" maxlength="190"></label><label>联系电话<input type="tel" name="phone" autocomplete="tel" maxlength="80"></label></div>
    <p class="field-help">邮箱与电话至少填写一项。</p><label>感兴趣的服务 *<select name="service" required><option value="print">菜单设计与印刷</option><option value="ordering">点餐与收银</option><option value="equipment">门店与后厨设备</option><option value="other">其他咨询</option></select></label><label>说说你的需求 *<textarea name="message" rows="6" required maxlength="5000" placeholder="例如：门店所在城市、需要的产品、数量和期望安排。"></textarea></label><div class="honeypot" aria-hidden="true"><label>Website<input name="website" tabindex="-1" autocomplete="off"></label></div><label class="consent"><input type="checkbox" name="consent" required>我同意将以上信息用于处理本次咨询及联系跟进。</label><button class="button" type="submit">提交需求 ↗</button><div class="form-result" role="status" aria-live="polite"></div><a class="privacy-link" href="<?php echo esc_url(xdj_content_url('privacy')); ?>"><?php echo esc_html(xdj_t('privacy')); ?></a></form>
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
    echo '<div class="wrap"><h1>网站询盘</h1><p>当前仅管理员可查看。通知收件人可在“网站服务设置”中配置。通知结果与询盘保存状态分别记录。</p><table class="widefat striped"><thead><tr><th>编号 / 时间 UTC</th><th>联系人</th><th>地区 / 业务</th><th>需求</th><th>状态</th></tr></thead><tbody>';
    foreach($rows as $r){echo '<tr><td>'.esc_html($r->reference).'<br>'.esc_html($r->created_at).'</td><td>'.esc_html($r->name).'<br>'.esc_html($r->email).'<br>'.esc_html($r->phone).'</td><td>'.esc_html($r->country).' / '.esc_html($r->service).'</td><td style="max-width:400px;white-space:pre-wrap">'.esc_html($r->message).'<hr>产品 #'.absint($r->product_id).' '.esc_html($r->topic).'<br><a href="'.esc_url($r->source_url).'">'.esc_html($r->source_url).'</a><br>通知：'.esc_html($r->notification).'</td><td><form method="post">';wp_nonce_field('xdj_inquiry_status');echo '<input type="hidden" name="lead_id" value="'.absint($r->id).'"><select name="status">';foreach($states as $k=>$label)echo '<option value="'.esc_attr($k).'" '.selected($r->status,$k,false).'>'.esc_html($label).'</option>';echo '</select><button class="button">保存</button></form></td></tr>';}
    echo '</tbody></table><p>';if($page>1)echo '<a href="'.esc_url(add_query_arg('paged',$page-1)).'">上一页</a> ';if(count($rows)===25)echo '<a href="'.esc_url(add_query_arg('paged',$page+1)).'">下一页</a>';echo '</p></div>';
}
add_action('admin_menu',function(){add_options_page('网站服务设置','网站服务设置','manage_options','xdj-service-settings',function(){
    if(!current_user_can('manage_options'))return;
    if($_SERVER['REQUEST_METHOD']==='POST'){check_admin_referer('xdj_service_settings');$email=sanitize_email(wp_unslash($_POST['notification_email']??''));if(!$email||is_email($email)){update_option('xdj_notification_email',$email);echo '<div class="notice notice-success"><p>设置已保存。</p></div>';}}
    echo '<div class="wrap"><h1>网站服务设置</h1><p>询盘会先保存到数据库。配置通知邮箱后由后台任务发送；实际送达应在邮件服务配置后验收。不会通过本页面发送测试邮件。</p><form method="post">';wp_nonce_field('xdj_service_settings');echo '<label>询盘通知收件邮箱 <input type="email" name="notification_email" value="'.esc_attr(get_option('xdj_notification_email','')).'"></label>';submit_button();echo '</form><h2>资料助手</h2><p>当前使用本站公开资料检索，不调用外部模型，不承诺价格或服务条件。AnythingLLM 接入需先配置服务和模型。</p><h2>邮件传输</h2><p>'.(getenv('ONESTEP_SMTP_HOST')?'已配置 SMTP 环境变量，请检查实际通知结果。':'尚未配置 SMTP 环境变量；不要将保存成功误认为邮件已送达。').'</p></div>';
});});
add_action('phpmailer_init',function($mail){
    if(!getenv('ONESTEP_SMTP_HOST'))return;
    $mail->isSMTP();$mail->Host=getenv('ONESTEP_SMTP_HOST');$mail->Port=(int)(getenv('ONESTEP_SMTP_PORT')?:587);$mail->SMTPAuth=true;$mail->Username=getenv('ONESTEP_SMTP_USER');
    $file=getenv('ONESTEP_SMTP_PASSWORD_FILE');$mail->Password=$file&&is_readable($file)?trim(file_get_contents($file)):(getenv('ONESTEP_SMTP_PASSWORD')?:'');$mail->SMTPSecure=getenv('ONESTEP_SMTP_ENCRYPTION')?:'tls';
    if(getenv('ONESTEP_SMTP_FROM'))$mail->setFrom(getenv('ONESTEP_SMTP_FROM'),'One Step Group',false);
});
add_action('init',function(){if(get_option('xdj_notification_email')&&!wp_next_scheduled('xdj_process_notifications'))wp_schedule_single_event(time()+60,'xdj_process_notifications');},50);
add_action('xdj_process_notifications',function(){
    $email=get_option('xdj_notification_email');if(!$email||!getenv('ONESTEP_SMTP_HOST'))return;
    $locked=add_option('xdj_notification_lock',time(),'','no');if(!$locked){if((int)get_option('xdj_notification_lock')<time()-300)delete_option('xdj_notification_lock');return;}
    try{global $wpdb;$rows=$wpdb->get_results('SELECT * FROM '.xdj_inquiry_table()." WHERE notification IN ('pending','failed') AND notification_attempts<5 ORDER BY id LIMIT 10");
        foreach($rows as $row){if(str_ends_with($row->email,'.invalid')){$wpdb->update(xdj_inquiry_table(),['notification'=>'test-skipped'],['id'=>$row->id]);continue;}
            $body="Reference: {$row->reference}\nName: {$row->name}\nEmail: {$row->email}\nPhone: {$row->phone}\nCountry: {$row->country}\nLanguage: {$row->locale}\nProduct: {$row->product_id}\nSource: {$row->source_url}\nTopic: {$row->topic}\n\n{$row->message}";
            $sent=wp_mail($email,'[One Step] '.$row->reference,$body);$wpdb->update(xdj_inquiry_table(),['notification'=>$sent?'sent':'failed','notification_attempts'=>$row->notification_attempts+1],['id'=>$row->id]);
        }
    }finally{delete_option('xdj_notification_lock');}
});
