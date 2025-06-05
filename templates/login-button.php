<?php
// Show error message if authentication failed
if (isset($_GET['idena_error'])) : ?>
    <div class="notice notice-error" style="margin: 10px 0; padding: 10px; background: #f8d7da; border-left: 4px solid #dc3545; color: #721c24;">
        <p><?php _e('Authentication failed. Please try again.', 'idena-auth'); ?></p>
    </div>
<?php endif;

$token = wp_generate_uuid4();

// Dynamic URLs - works on any WordPress site
$site_url = site_url();
$use_index_php = (strpos(rest_url(), '/index.php/') !== false);

if ($use_index_php) {
    $nonce_endpoint = $site_url . '/index.php/wp-json/idena-auth/v1/start-session';
    $auth_endpoint = $site_url . '/index.php/wp-json/idena-auth/v1/authenticate';
    $callback_url = $site_url . '/index.php/wp-json/idena-auth/v1/callback?token=' . $token;
} else {
    $nonce_endpoint = rest_url('idena-auth/v1/start-session');
    $auth_endpoint = rest_url('idena-auth/v1/authenticate');
    $callback_url = rest_url('idena-auth/v1/callback?token=' . $token);
}

$favicon_url = get_site_icon_url() ?: $site_url . '/favicon.ico';

// URL for Web App
$web_app_url = add_query_arg(array(
    'token' => $token,
    'callback_url' => $callback_url,
    'nonce_endpoint' => $nonce_endpoint,
    'authentication_endpoint' => $auth_endpoint,
    'favicon_url' => $favicon_url
), 'https://app.idena.io/dna/signin');

// Custom class if specified
$custom_class = isset($atts['class']) ? esc_attr($atts['class']) : '';
?>

<div class="idena-auth-wrapper <?php echo $custom_class; ?>">
    <div class="idena-auth-button-container">
        <a href="<?php echo esc_url($web_app_url); ?>" 
           class="idena-auth-button idena-web-app" 
           data-token="<?php echo esc_attr($token); ?>"
           data-site-url="<?php echo esc_attr($site_url); ?>"
           data-use-index="<?php echo $use_index_php ? 'true' : 'false'; ?>">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="white" style="margin-right: 10px;">
                <circle cx="12" cy="12" r="10" stroke="white" stroke-width="2" fill="none"/>
                <text x="12" y="16" text-anchor="middle" fill="white" font-size="12">ID</text>
            </svg>
            <span><?php _e('Sign in with Idena', 'idena-auth'); ?></span>
        </a>

    </div>
</div>