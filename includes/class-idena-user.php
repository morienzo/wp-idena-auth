<?php
class Idena_User {
    
    /**
     * Main entry point: Finds or creates a user, then logs them in.
     */
    public function create_or_login_user($address) {
        $user_id = $this->get_user_by_idena_address($address);
        
        if (!$user_id) {
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
     * Creates a new WordPress user with privacy in mind.
     */
    private function create_new_user($address) {
        // Anonymous ID: id-xxxxxxxxx
        $username = 'id-' . substr(md5($address), 0, 8);
        
        // Ensure uniqueness
        $i = 1;
        $original_username = $username;
        while (username_exists($username)) {
            $username = $original_username . $i;
            $i++;
        }
        
        // Fallback email
        $host = wp_parse_url(home_url(), PHP_URL_HOST);
        if (!$host) $host = 'idena-auth.local';
        $email = $username . '@' . $host;
        
        $user_data = array(
            'user_login'    => $username,
            'user_email'    => $email,
            'user_pass'     => wp_generate_password(32, true, true),
            // Display Name uses the anonymous ID too
            'display_name'  => $username,
            'nickname'      => $username,
            'role'          => 'subscriber'
        );
        
        $user_id = wp_insert_user($user_data);
        
        if (!is_wp_error($user_id)) {
            update_user_meta($user_id, 'idena_address', $address);
            update_user_meta($user_id, 'idena_auth', true);
            update_user_meta($user_id, 'show_admin_bar_front', 'false');
        }
        
        return $user_id;
    }
    
    /**
     * Sets the authentication cookie.
     */
    private function login_user($user) {
        wp_clear_auth_cookie();
        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID, true);
        do_action('wp_login', $user->user_login, $user);
    }
    
    public function get_user_by_idena_address($address) {
        global $wpdb;
        $user_id = $wpdb->get_var($wpdb->prepare(
            "SELECT user_id FROM $wpdb->usermeta WHERE meta_key = 'idena_address' AND meta_value = %s LIMIT 1",
            $address
        ));
        return $user_id ? (int) $user_id : null;
    }
}