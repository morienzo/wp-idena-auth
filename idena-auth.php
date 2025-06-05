<?php
/**
 * Plugin Name: Idena Authentication
 * Plugin URI: https://github.com/morienzo/wp-idena-auth
 * Description: Enable authentication via Idena for WordPress
 * Version: 1.0.0
 * Author: Morienzo
 * Author URI: https://github.com/morienzo
 * License: GPL v2 or later
 * Text Domain: idena-auth
 * Domain Path: /languages
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define constants
define('IDENA_AUTH_VERSION', '1.0.0');
define('IDENA_AUTH_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('IDENA_AUTH_PLUGIN_URL', plugin_dir_url(__FILE__));
define('IDENA_AUTH_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Load classes
require_once IDENA_AUTH_PLUGIN_DIR . 'includes/class-idena-auth.php';
require_once IDENA_AUTH_PLUGIN_DIR . 'includes/class-idena-api.php';
require_once IDENA_AUTH_PLUGIN_DIR . 'includes/class-idena-user.php';

// Initialize plugin
function idena_auth_init() {
    $idena_auth = new Idena_Auth();
    $idena_auth->init();
}
add_action('plugins_loaded', 'idena_auth_init');

// Plugin activation
register_activation_hook(__FILE__, 'idena_auth_activate');
function idena_auth_activate() {
    // Create table to store sessions
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
    
    // Set default options
    add_option('idena_auth_api_url', 'https://api.idena.io');
    add_option('idena_auth_allowed_status', array('Verified', 'Human'));
    add_option('idena_auth_debug_mode', false);
    
    // Clear permalinks cache
    flush_rewrite_rules();
    
    // Clear scheduled cleanup
    wp_clear_scheduled_hook('idena_auth_cleanup_sessions');
}

// Plugin deactivation
register_deactivation_hook(__FILE__, 'idena_auth_deactivate');
function idena_auth_deactivate() {
    // Clean expired sessions
    global $wpdb;
    $table_name = $wpdb->prefix . 'idena_sessions';
    
    // Delete sessions older than 24 hours
    $wpdb->query(
        "DELETE FROM $table_name 
         WHERE created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)"
    );
    
    // Clear permalinks cache
    flush_rewrite_rules();
}

// Plugin uninstall
register_uninstall_hook(__FILE__, 'idena_auth_uninstall');
function idena_auth_uninstall() {
    // Drop table
    global $wpdb;
    $table_name = $wpdb->prefix . 'idena_sessions';
    $wpdb->query("DROP TABLE IF EXISTS $table_name");
    
    // Delete all options
    delete_option('idena_auth_api_url');
    delete_option('idena_auth_allowed_status');
    delete_option('idena_auth_debug_mode');
    
    // Delete user metadata
    delete_metadata('user', 0, 'idena_address', '', true);
    delete_metadata('user', 0, 'idena_status', '', true);
    delete_metadata('user', 0, 'idena_auth', '', true);
}

// Add settings link on plugins page
add_filter('plugin_action_links_' . IDENA_AUTH_PLUGIN_BASENAME, 'idena_auth_add_action_links');
function idena_auth_add_action_links($links) {
    $settings_link = '<a href="' . admin_url('options-general.php?page=idena-auth') . '">' . __('Settings', 'idena-auth') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}

// Schedule cron job for session cleanup
add_action('wp', 'idena_auth_schedule_cleanup');
function idena_auth_schedule_cleanup() {
    if (!wp_next_scheduled('idena_auth_cleanup_sessions')) {
        wp_schedule_event(time(), 'hourly', 'idena_auth_cleanup_sessions');
    }
}

// Cleanup expired sessions
add_action('idena_auth_cleanup_sessions', 'idena_auth_do_cleanup');
function idena_auth_do_cleanup() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'idena_sessions';
    
    // Delete sessions older than 1 hour
    $wpdb->query(
        "DELETE FROM $table_name 
         WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)"
    );
}