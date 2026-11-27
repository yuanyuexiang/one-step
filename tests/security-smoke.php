<?php
/** Mutates isolated test rate counters/logs only; never run against production. */
if (getenv('ONESTEP_CI') !== '1') throw new RuntimeException('Requires isolated ONESTEP_CI=1');
function xdj_security_check($ok, $message) { if (!$ok) throw new RuntimeException($message); }
$remote = $_SERVER['REMOTE_ADDR'] ?? null;
$previous = wp_get_current_user();
try {
    $_SERVER['REMOTE_ADDR'] = '192.0.2.27';
    $key = xdj_login_rate_key();
    delete_transient($key);
    $users = get_users(['role'=>'administrator','number'=>1]);
    xdj_security_check((bool)$users, 'Administrator fixture required');
    $user = $users[0];
    xdj_security_check(apply_filters('authenticate', $user, $user->user_login, 'fixture') instanceof WP_User, 'Normal authentication must pass');
    for ($i=0; $i<8; $i++) do_action('wp_login_failed', 'xdj-invalid-test', new WP_Error('incorrect_password','Fixture'));
    $blocked = apply_filters('authenticate', $user, $user->user_login, 'fixture');
    xdj_security_check(is_wp_error($blocked) && $blocked->get_error_code()==='xdj_login_throttled', 'Repeated failures must throttle even a valid authentication result');
    do_action('wp_login_failed', 'xdj-invalid-test', $blocked);
    xdj_security_check((int)get_transient($key)===8, 'Blocked retries must not extend counter');
    $_SERVER['REMOTE_ADDR'] = '192.0.2.28';
    xdj_security_check(xdj_login_rate_key()!==$key, 'IP counters must be isolated');
    $_SERVER['REMOTE_ADDR'] = '192.0.2.27';
    delete_transient($key);
    xdj_security_check(apply_filters('authenticate', $user, $user->user_login, 'fixture') instanceof WP_User, 'Expired lock must allow authentication');
    $errors = new WP_Error();
    do_action('user_profile_update_errors', $errors, true, (object)['user_pass'=>'short']);
    xdj_security_check($errors->has_errors(), 'Short replacement passwords must fail');
    xdj_security_check(apply_filters('xmlrpc_enabled',true)===false, 'XML-RPC authentication must be disabled');
    wp_set_current_user(0);
    $before = wp_count_posts('post');
    $request = new WP_REST_Request('POST','/wp/v2/posts');
    $response = rest_do_request($request);
    xdj_security_check(in_array($response->get_status(),[401,403],true), 'Anonymous content writing must be denied');
    xdj_security_check(wp_count_posts('post') == $before, 'Denied request must not create content');
    $row = json_decode(trim(array_slice(file(WP_CONTENT_DIR.'/xdj-private/security.log'),-1)[0]),true);
    xdj_security_check(($row['event']??'')==='login_failed' && !isset($row['password']), 'Audit must record event without credentials');
} finally {
    if (isset($key)) delete_transient($key);
    if ($remote === null) unset($_SERVER['REMOTE_ADDR']); else $_SERVER['REMOTE_ADDR']=$remote;
    $GLOBALS['current_user']=$previous;
}
echo "Security smoke checks passed\n";
