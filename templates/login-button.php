<?php
/**
 * Template for the login button
 */
if (!defined('ABSPATH')) exit;

if (isset($_GET['idena_error'])) {
    $error_msg = __('Authentication failed.', 'idena-auth');
    if ($_GET['idena_error'] === 'create_failed') $error_msg = __('Could not create user.', 'idena-auth');
    echo '<div class="notice notice-error" style="margin: 10px 0; padding: 10px; background: #f8d7da; border-left: 4px solid #dc3545; color: #721c24;"><p>' . esc_html($error_msg) . ' <a href="' . esc_url(wp_login_url()) . '">' . __('Try again', 'idena-auth') . '</a></p></div>';
}

$token = wp_generate_uuid4();
$site_url = site_url();

// Force index.php in API URLs for strict servers
$nonce_endpoint = $site_url . '/index.php/wp-json/idena-auth/v1/start-session';
$auth_endpoint  = $site_url . '/index.php/wp-json/idena-auth/v1/authenticate';

// Callback
$callback_url = add_query_arg('idena_token', $token, wp_login_url());

$favicon_url = get_site_icon_url();
if (empty($favicon_url)) $favicon_url = $site_url . '/favicon.ico';

// Idena App URL
$web_app_url = add_query_arg(
    array(
        'token'                   => $token,
        'callback_url'            => $callback_url,
        'nonce_endpoint'          => $nonce_endpoint,
        'authentication_endpoint' => $auth_endpoint,
        'favicon_url'             => $favicon_url,
    ),
    'https://app.idena.io/dna/signin'
);

$custom_class = isset($atts['class']) ? esc_attr($atts['class']) : '';
?>

<div class="idena-auth-wrapper <?php echo $custom_class; ?>">
    <div class="idena-auth-button-container">
        <a href="<?php echo esc_url($web_app_url); ?>" class="idena-auth-button" target="_self">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="white" style="margin-right: 10px;">
                <circle cx="12" cy="12" r="10" stroke="white" stroke-width="2" fill="none"/>
                <text x="12" y="16" text-anchor="middle" fill="white" font-size="12">ID</text>
            </svg>
            <span><?php _e('Sign in with Idena', 'idena-auth'); ?></span>
        </a>
    </div>
</div>