<?php
class Idena_API {
    
    public function register_routes() {
        $routes = array(
            'start-session' => array('methods' => 'POST', 'callback' => array($this, 'start_session')),
            'authenticate'  => array('methods' => 'POST', 'callback' => array($this, 'authenticate')),
            'check-status'  => array('methods' => 'GET',  'callback' => array($this, 'check_status'))
        );
        
        foreach ($routes as $route => $config) {
            register_rest_route('idena-auth/v1', '/' . $route, array_merge(
                $config,
                array('permission_callback' => '__return_true')
            ));
        }
    }
    
    public function start_session($request) {
        $token = sanitize_text_field($request->get_param('token'));
        $address = sanitize_text_field($request->get_param('address'));
        
        if (empty($token) || empty($address)) {
            return new WP_REST_Response(array('success' => false, 'error' => 'Missing params'), 400);
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'idena_sessions';
        $nonce = 'signin-' . wp_generate_uuid4();
        
        $wpdb->replace(
            $table_name,
            array('token' => $token, 'address' => $address, 'nonce' => $nonce, 'status' => 'pending', 'created_at' => current_time('mysql')),
            array('%s', '%s', '%s', '%s', '%s')
        );
        
        return new WP_REST_Response(array('success' => true, 'data' => array('nonce' => $nonce)));
    }
    
    public function authenticate($request) {
        $token = sanitize_text_field($request->get_param('token'));
        $signature = sanitize_text_field($request->get_param('signature'));
        
        if (empty($token) || empty($signature)) {
            return new WP_REST_Response(array('success' => false, 'error' => 'Missing params'), 400);
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'idena_sessions';
        $session = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE token = %s", $token));
        
        if (!$session) return new WP_REST_Response(array('success' => false, 'error' => 'Session not found'), 404);
        
        // 1. Verify Idena status (No cache)
        $user_status = $this->get_user_status($session->address);
        
        // 2. Check against allowed statuses
        $allowed_status = get_option('idena_auth_allowed_status');
        if (empty($allowed_status) || !is_array($allowed_status)) {
            $allowed_status = array('Verified', 'Human');
        }
        
        // 3. Decision Logic
        if (in_array($user_status, $allowed_status, true)) {
            // AUTHORIZED
            $wpdb->update($table_name, array('status' => 'authenticated'), array('token' => $token), array('%s'), array('%s'));
            do_action('idena_auth_success', $session->address);
        } else {
            // DENIED: Mark as denied in DB so Auth class handles the redirect
            $wpdb->update($table_name, array('status' => 'denied'), array('token' => $token), array('%s'), array('%s'));
            do_action('idena_auth_failed', $session->address, $user_status);
        }
        
        // Always return TRUE to force Idena App to redirect back to the site
        return new WP_REST_Response(array(
            'success' => true, 
            'data' => array('authenticated' => true)
        ));
    }
    
    public function check_status($request) {
        return new WP_REST_Response(array('success' => true));
    }
    
    private function get_user_status($address) {
        $response = wp_remote_get('https://api.idena.io/api/identity/' . $address, array('timeout' => 15));
        $status = 'Undefined';
        
        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) == 200) {
            $data = json_decode(wp_remote_retrieve_body($response), true);
            if (isset($data['result']['state'])) {
                $status = $data['result']['state'];
            } elseif (isset($data['error']) && strpos($data['error']['message'], 'no data found') !== false) {
                $status = 'Not validated';
            }
        }
        
        return $status;
    }
}