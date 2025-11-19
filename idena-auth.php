<?php
/**
 * Plugin Name: Idena Authentication
 * Plugin URI: https://github.com/morienzo/wp-idena-auth
 * Description: Enable authentication via Idena for WordPress
 * Version: 1.0.4
 * Author: Morienzo
 * License: GPL v2 or later
 * Text Domain: idena-auth
 */

if (!defined('ABSPATH')) exit;

// Constants
define('IDENA_AUTH_VERSION', '1.0.4');
define('IDENA_AUTH_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('IDENA_AUTH_PLUGIN_URL', plugin_dir_url(__FILE__));
define('IDENA_AUTH_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Required files
require_once IDENA_AUTH_PLUGIN_DIR . 'includes/class-idena-auth.php';
require_once IDENA_AUTH_PLUGIN_DIR . 'includes/class-idena-api.php';
require_once IDENA_AUTH_PLUGIN_DIR . 'includes/class-idena-user.php';

// Initialize
add_action('plugins_loaded', function() {
    $idena_auth = new Idena_Auth();
    $idena_auth->init();
});

// Activation: Create DB table and set defaults
register_activation_hook(__FILE__, 'idena_auth_activate');
function idena_auth_activate() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'idena_sessions';
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        token varchar(255) NOT NULL,
        address varchar(255) DEFAULT '' NOT NULL,
        nonce varchar(255) DEFAULT '' NOT NULL,
        status varchar(50) DEFAULT 'pending' NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY token (token),
        KEY status (status),
        KEY created_at (created_at)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    
    add_option('idena_auth_api_url', 'https://api.idena.io');
    add_option('idena_auth_allowed_status', array('Verified', 'Human'));
    
    // Ensure API routes work immediately
    flush_rewrite_rules();
}

// Deactivation
register_deactivation_hook(__FILE__, 'idena_auth_deactivate');
function idena_auth_deactivate() {
    flush_rewrite_rules();
}

// Add Settings link to plugin list
add_filter('plugin_action_links_' . IDENA_AUTH_PLUGIN_BASENAME, 'idena_auth_add_action_links');
function idena_auth_add_action_links($links) {
    $settings_link = '<a href="' . admin_url('options-general.php?page=idena-auth') . '">' . __('Settings') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}