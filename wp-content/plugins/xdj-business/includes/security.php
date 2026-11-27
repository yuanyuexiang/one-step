<?php
/** Authentication controls and private, persistent incident audit records. */
if (!defined('ABSPATH')) exit;

function xdj_security_log($event, array $details = []) {
    // Never record passwords, cookies, tokens, enquiry bodies, or request payloads.
    $directory = WP_CONTENT_DIR.'/xdj-private';
    if (!is_dir($directory) && !wp_mkdir_p($directory)) return;
    @chmod($directory, 0700);
    $file = $directory.'/security.log';
    $handle = @fopen($file, 'a');
    if (!$handle) return;
    if (flock($handle, LOCK_EX)) {
        // Bounded retention; the directory is part of the persistent WordPress volume.
        if (fstat($handle)['size'] > 5 * 1024 * 1024) {
            for ($i = 3; $i >= 1; $i--) {
                $old = $file.'.'.$i;
                if (is_file($old)) @rename($old, $file.'.'.($i+1));
            }
            @rename($file, $file.'.1');
            flock($handle, LOCK_UN);
            fclose($handle);
            $handle = @fopen($file, 'a');
            if (!$handle) return;
            flock($handle, LOCK_EX);
        }
        fwrite($handle, wp_json_encode([
            'time_utc'=>gmdate('c'), 'event'=>$event, 'actor'=>get_current_user_id(),
            'ip'=>xdj_client_address(),
        ] + $details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n");
        @chmod($file, 0600);
        flock($handle, LOCK_UN);
    }
    fclose($handle);
}

function xdj_login_rate_key() {
    return 'xdj_login_'.hash_hmac('sha256', xdj_client_address(), wp_salt('auth'));
}
add_filter('authenticate', function ($result, $username, $password) {
    if (!$username && !$password) return $result;
    if ((int)get_transient(xdj_login_rate_key()) >= 8) {
        return new WP_Error('xdj_login_throttled', '登录尝试过多，请在 15 分钟后重试。');
    }
    return $result;
}, 100, 3);
add_action('wp_login_failed', function ($username, $error) {
    if ($error instanceof WP_Error && $error->get_error_code() === 'xdj_login_throttled') return;
    $key = xdj_login_rate_key();
    $count = (int)get_transient($key) + 1;
    set_transient($key, $count, 15 * MINUTE_IN_SECONDS);
    xdj_security_log('login_failed', ['login'=>mb_substr(sanitize_user($username),0,80),'attempt'=>$count]);
}, 10, 2);
add_action('wp_login', function ($username, $user) {
    delete_transient(xdj_login_rate_key());
    xdj_security_log('login_success', ['user_id'=>$user->ID,
        'user_agent'=>mb_substr(sanitize_text_field($_SERVER['HTTP_USER_AGENT']??''),0,300)]);
}, 10, 2);
add_filter('user_profile_update_errors', function ($errors, $update, $user) {
    $password = $user->user_pass ?? '';
    if ($password !== '' && (strlen($password) < 16 || preg_match('/^(.)\1+$/', $password))) {
        $errors->add('xdj_password', '请使用至少 16 位的独立密码，建议采用密码管理器生成的随机密码。');
    }
}, 10, 3);
// This site does not use Jetpack, remote publishing clients, or XML-RPC integrations.
add_filter('xmlrpc_enabled', '__return_false');
add_filter('xmlrpc_methods', '__return_empty_array');
add_filter('wp_headers', function ($headers) { unset($headers['X-Pingback']); return $headers; });
add_action('profile_update', function ($id, $old) {
    $current = get_userdata($id);
    xdj_security_log('user_updated', ['user_id'=>$id,
        'password_changed'=>$current && $current->user_pass !== $old->user_pass]);
}, 10, 2);
add_action('set_user_role', function ($id, $role, $old) {
    xdj_security_log('role_changed', ['user_id'=>$id,'role'=>$role,'previous_roles'=>$old]);
}, 10, 3);
add_action('wp_after_insert_post', function ($id, $post, $update, $before) {
    if (wp_is_post_revision($id) || $post->post_type === 'attachment') return;
    if ($post->post_status === 'auto-draft') return;
    xdj_security_log('content_saved', ['post_id'=>$id,'post_type'=>$post->post_type,
        'post_author'=>(int)$post->post_author,'status'=>$post->post_status,
        'previous_status'=>$before ? $before->post_status : null,
        'transport'=>defined('WP_CLI') && WP_CLI ? 'cli' : (defined('REST_REQUEST') && REST_REQUEST ? 'rest' : 'web')]);
}, 10, 4);
add_action('current_screen', function ($screen) {
    if ($screen->id === 'toplevel_page_xdj-inquiries' && current_user_can('manage_options')) {
        xdj_security_log('inquiries_viewed');
    }
});
