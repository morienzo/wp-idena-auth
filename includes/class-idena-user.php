<?php
class Idena_User {
    
    /**
     * Create or login user by Idena address
     *
     * @param string $address Idena blockchain address
     * @return int|false User ID on success, false on failure
     */
    public function create_or_login_user($address) {
        // Validate address format
        if (!$this->is_valid_address($address)) {
            return false;
        }
        
        // Find existing user by meta
        $user_id = $this->get_user_by_idena_address($address);
        
        if ($user_id) {
            $user = get_user_by('id', $user_id);
        } else {
            // Create new user
            $user_data = $this->prepare_new_user_data($address);
            
            // Allow customization of user data
            $user_data = apply_filters('idena_auth_new_user_data', $user_data, $address);
            
            $user_id = wp_insert_user($user_data);
            
            if (is_wp_error($user_id)) {
                error_log('Idena Auth: Failed to create user - ' . $user_id->get_error_message());
                return false;
            }
            
            // Add metadata
            $this->set_user_idena_metadata($user_id, $address);
            
            // Fire action for new user
            do_action('idena_user_created', $user_id, $address);
            
            $user = get_user_by('id', $user_id);
        }
        
        if ($user) {
            // Update last login time
            update_user_meta($user->ID, 'idena_last_login', current_time('mysql'));
            
            // Log in user
            $this->login_user($user);
            
            // Fire action after successful login
            do_action('idena_user_logged_in', $user->ID, $address);
            
            return $user->ID;
        }
        
        return false;
    }
    
    /**
     * Update user status and role
     *
     * @param int $user_id WordPress user ID
     * @param string $status Idena status
     * @return bool Success
     */
    public function update_user_status($user_id, $status) {
        if (!$user_id || !$status) {
            return false;
        }
        
        // Update status meta
        update_user_meta($user_id, 'idena_status', $status);
        update_user_meta($user_id, 'idena_status_updated', current_time('mysql'));
        
        // Get user object
        $user = get_user_by('id', $user_id);
        if (!$user) {
            return false;
        }
        
        // Determine role based on status
        $role = $this->get_role_by_status($status);
        
        // Allow role customization
        $role = apply_filters('idena_auth_user_role', $role, $status, $user_id);
        
        // Update role if changed
        if (!in_array($role, $user->roles)) {
            $user->set_role($role);
            do_action('idena_user_role_changed', $user_id, $role, $status);
        }
        
        return true;
    }
    
    /**
     * Get user by Idena address
     *
     * @param string $address Idena address
     * @return int|null User ID or null
     */
    public function get_user_by_idena_address($address) {
        global $wpdb;
        
        // Use direct query for better performance
        $user_id = $wpdb->get_var($wpdb->prepare(
            "SELECT user_id FROM $wpdb->usermeta 
            WHERE meta_key = 'idena_address' AND meta_value = %s 
            LIMIT 1",
            $address
        ));
        
        return $user_id ? (int) $user_id : null;
    }
    
    /**
     * Get user's Idena address
     *
     * @param int $user_id WordPress user ID
     * @return string|false Idena address or false
     */
    public function get_user_idena_address($user_id) {
        return get_user_meta($user_id, 'idena_address', true);
    }
    
    /**
     * Get user's Idena status
     *
     * @param int $user_id WordPress user ID
     * @return string|false Idena status or false
     */
    public function get_user_idena_status($user_id) {
        return get_user_meta($user_id, 'idena_status', true);
    }
    
    /**
     * Check if address is valid
     *
     * @param string $address Address to validate
     * @return bool
     */
    private function is_valid_address($address) {
        // Idena addresses start with 0x and are 42 characters long
        return preg_match('/^0x[a-fA-F0-9]{40}$/', $address);
    }
    
    /**
     * Prepare data for new user
     *
     * @param string $address Idena address
     * @return array User data
     */
    private function prepare_new_user_data($address) {
        // Generate unique username
        $base_username = 'ID-' . substr($address, 2, 8);
        $username = $this->generate_unique_username($base_username);
        
        // Generate display name
        $display_name = 'ID-' . substr($address, 2, 8);
        
        return array(
            'user_login' => $username,
            'user_pass' => wp_generate_password(32, true, true),
            'user_email' => $username . '@' . wp_parse_url(home_url(), PHP_URL_HOST),
            'display_name' => $display_name,
            'nickname' => $display_name,
            'role' => get_option('default_role', 'subscriber')
        );
    }
    
    /**
     * Generate unique username
     *
     * @param string $base_username Base username
     * @return string Unique username
     */
    private function generate_unique_username($base_username) {
        $username = $base_username;
        $suffix = 1;
        
        while (username_exists($username)) {
            $username = $base_username . '_' . $suffix;
            $suffix++;
        }
        
        return $username;
    }
    
    /**
     * Set user Idena metadata
     *
     * @param int $user_id User ID
     * @param string $address Idena address
     */
    private function set_user_idena_metadata($user_id, $address) {
        update_user_meta($user_id, 'idena_address', $address);
        update_user_meta($user_id, 'idena_auth', true);
        update_user_meta($user_id, 'idena_created', current_time('mysql'));
        
        // Hide admin bar for new Idena users by default
        update_user_meta($user_id, 'show_admin_bar_front', 'false');
    }
    
    /**
     * Log in user
     *
     * @param WP_User $user User object
     */
    private function login_user($user) {
        // Clean auth cookies
        wp_clear_auth_cookie();
        
        // Set current user
        wp_set_current_user($user->ID);
        
        // Set auth cookie with remember option
        $remember = apply_filters('idena_auth_remember_user', true, $user->ID);
        wp_set_auth_cookie($user->ID, $remember);
        
        // Fire login action
        do_action('wp_login', $user->user_login, $user);
    }
    
    /**
     * Get WordPress role by Idena status
     *
     * @param string $status Idena status
     * @return string WordPress role
     */
    private function get_role_by_status($status) {
        $role_mapping = array(
            'Human' => 'contributor',
            'Verified' => 'subscriber',
            'Newbie' => 'subscriber',
            'Candidate' => 'subscriber',
            'Suspended' => 'subscriber',
            'Zombie' => 'subscriber',
            'Not validated' => 'subscriber'
        );
        
        return isset($role_mapping[$status]) ? $role_mapping[$status] : 'subscriber';
    }
    
    /**
     * Get all users with Idena authentication
     *
     * @param array $args Additional query arguments
     * @return array Array of WP_User objects
     */
    public function get_idena_users($args = array()) {
        $defaults = array(
            'meta_key' => 'idena_auth',
            'meta_value' => true,
            'orderby' => 'ID',
            'order' => 'DESC'
        );
        
        $args = wp_parse_args($args, $defaults);
        
        return get_users($args);
    }
    
    /**
     * Count users by Idena status
     *
     * @return array Status => count
     */
    public function count_users_by_status() {
        global $wpdb;
        
        $results = $wpdb->get_results(
            "SELECT meta_value as status, COUNT(DISTINCT user_id) as count 
            FROM $wpdb->usermeta 
            WHERE meta_key = 'idena_status' 
            GROUP BY meta_value",
            OBJECT_K
        );
        
        $counts = array();
        foreach ($results as $status => $data) {
            $counts[$status] = (int) $data->count;
        }
        
        return $counts;
    }
}