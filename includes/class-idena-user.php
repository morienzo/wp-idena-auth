<?php
class Idena_User {
    
    /**
     * Main entry point: Finds or creates a user, then logs them in.
     */
    public function create_or_login_user($address) {
        // Try to find existing user (by hash or legacy address)
        $user_id = $this->get_user_by_idena_address($address);
        
        if (!$user_id) {
            // Create new anonymous user
            $user_id = $this->create_new_user($address);
            if (is_wp_error($user_id)) return false;
        }
        
        $user = get_user_by('id', $user_id);
        if ($user) {
            $this->login_user($user);
            return $user->ID;
        }
        
        return false;
    }
    
    /**
     * Creates a new WordPress user with privacy features.
     */
    private function create_new_user($address) {
        // Generate anonymous ID (e.g. id-a1b2c3d4)
        $username = 'id-' . substr(md5($address), 0, 8);
        
        // Ensure uniqueness
        $i = 1;
        $original_username = $username;
        while (username_exists($username)) {
            $username = $original_username . $i;
            $i++;
        }
        
        // Generate safe fallback email
        $host = wp_parse_url(home_url(), PHP_URL_HOST);
        if (!$host) $host = 'idena-auth.local';
        $email = $username . '@' . $host;
        
        $user_data = array(
            'user_login'    => $username,
            'user_email'    => $email,
            'user_pass'     => wp_generate_password(32, true, true),
            'display_name'  => $username, // Anonymized display name
            'nickname'      => $username,
            'role'          => 'subscriber'
        );
        
        $user_id = wp_insert_user($user_data);
        
        if (!is_wp_error($user_id)) {
            // PRIVACY: Store SHA-256 hash instead of raw address
            $address_hash = hash('sha256', $address);
            update_user_meta($user_id, 'idena_address_hash', $address_hash);
            
            update_user_meta($user_id, 'idena_auth', true);
            update_user_meta($user_id, 'show_admin_bar_front', 'false');
        }
        
        return $user_id;
    }
    
    /**
     * Set auth cookies and trigger login hooks.
     */
    private function login_user($user) {
        wp_clear_auth_cookie();
        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID, true);
        do_action('wp_login', $user->user_login, $user);
    }
    
    /**
     * Retrieve user by Idena address (Hash check with Legacy fallback).
     */
    public function get_user_by_idena_address($address) {
        global $wpdb;
        
        // 1. Calculate hash
        $address_hash = hash('sha256', $address);
        
        // 2. Search by Hash (Standard privacy method)
        $user_id = $wpdb->get_var($wpdb->prepare(
            "SELECT user_id FROM $wpdb->usermeta WHERE meta_key = 'idena_address_hash' AND meta_value = %s LIMIT 1",
            $address_hash
        ));
        
        if ($user_id) {
            return (int) $user_id;
        }
        
        // 3. Legacy Fallback (Migration)
        // Check for old cleartext addresses and migrate them to hash if found
        $legacy_user_id = $wpdb->get_var($wpdb->prepare(
            "SELECT user_id FROM $wpdb->usermeta WHERE meta_key = 'idena_address' AND meta_value = %s LIMIT 1",
            $address
        ));
        
        if ($legacy_user_id) {
            // Migrate: Save hash and remove cleartext address
            update_user_meta($legacy_user_id, 'idena_address_hash', $address_hash);
            delete_user_meta($legacy_user_id, 'idena_address');
            
            return (int) $legacy_user_id;
        }
        
        return null;
    }
}
