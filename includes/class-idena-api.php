<?php
class Idena_API {
    
    public function register_routes() {
        $routes = array(
            'start-session' => array(
                'methods' => 'POST',
                'callback' => array($this, 'start_session')
            ),
            'authenticate' => array(
                'methods' => 'POST',
                'callback' => array($this, 'authenticate')
            ),
            'check-status' => array(
                'methods' => 'GET',
                'callback' => array($this, 'check_status')
            ),
            'callback' => array(
                'methods' => 'GET',
                'callback' => array($this, 'handle_callback'),
                'args' => array(
                    'token' => array(
                        'required' => false,
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_text_field'
                    )
                )
            )
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
            return new WP_REST_Response(array(
                'success' => false,
                'error' => 'Token and address are required'
            ), 400);
        }
        
        // Generate nonce
        $nonce = 'signin-' . wp_generate_uuid4();
        
        // Save session
        global $wpdb;
        $table_name = $wpdb->prefix . 'idena_sessions';
        
        $wpdb->replace(
            $table_name,
            array(
                'token' => $token,
                'address' => $address,
                'nonce' => $nonce,
                'status' => 'pending',
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%s', '%s')
        );
        
        return new WP_REST_Response(array(
            'success' => true,
            'data' => array(
                'nonce' => $nonce
            )
        ));
    }
    
    public function authenticate($request) {
        $token = sanitize_text_field($request->get_param('token'));
        $signature = sanitize_text_field($request->get_param('signature'));
        
        if (empty($token) || empty($signature)) {
            return new WP_REST_Response(array(
                'success' => false,
                'error' => 'Token and signature are required'
            ), 400);
        }
        
        // Retrieve session
        global $wpdb;
        $table_name = $wpdb->prefix . 'idena_sessions';
        $session = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE token = %s",
            $token
        ));
        
        if (!$session) {
            return new WP_REST_Response(array(
                'success' => false,
                'error' => 'Session not found'
            ), 404);
        }
        
        // Check user status
        $user_status = $this->get_user_status($session->address);
        $allowed_status = get_option('idena_auth_allowed_status', array('Verified', 'Human'));
        
        if (in_array($user_status, $allowed_status)) {
            // Update session
            $wpdb->update(
                $table_name,
                array('status' => 'authenticated'),
                array('token' => $token),
                array('%s'),
                array('%s')
            );
            
            // Log event
            do_action('idena_auth_success', $session->address);
            
            // Return callback URL for Idena to redirect
            $callback_url = rest_url('idena-auth/v1/callback?token=' . $token);
            
            return new WP_REST_Response(array(
                'success' => true,
                'data' => array(
                    'authenticated' => true,
                    'callback_url' => $callback_url
                )
            ));
        }
        
        // Log failed authentication
        do_action('idena_auth_failed', $session->address, $user_status);
        
        return new WP_REST_Response(array(
            'success' => false,
            'data' => array(
                'authenticated' => false,
                'error' => 'User status not allowed: ' . $user_status
            )
        ));
    }
    
    public function handle_callback($request) {
        // This endpoint returns HTML that closes the window
        header('Content-Type: text/html; charset=utf-8');
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title><?php _e('Authentication Complete', 'idena-auth'); ?></title>
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <style>
                body {
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                    text-align: center;
                    padding: 50px 20px;
                    background: #f5f5f5;
                    margin: 0;
                }
                .message {
                    background: white;
                    padding: 30px;
                    border-radius: 8px;
                    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                    max-width: 400px;
                    margin: 0 auto;
                }
                .success-icon {
                    color: #4CAF50;
                    font-size: 48px;
                    margin-bottom: 20px;
                }
                button {
                    padding: 10px 20px;
                    margin-top: 20px;
                    cursor: pointer;
                    background: #578bf5;
                    color: white;
                    border: none;
                    border-radius: 5px;
                    font-size: 16px;
                }
                button:hover {
                    background: #4a7dd8;
                }
            </style>
        </head>
        <body>
            <div class="message">
                <div class="success-icon">✓</div>
                <h2><?php _e('Authentication Successful!', 'idena-auth'); ?></h2>
                <p><?php _e('You can close this window and return to the main page.', 'idena-auth'); ?></p>
                <button onclick="window.close()"><?php _e('Close Window', 'idena-auth'); ?></button>
            </div>
            <script>
                // Try to close window
                try { window.close(); } catch(e) {}
            </script>
        </body>
        </html>
        <?php
        exit;
    }
    
    public function check_status($request) {
        $token = sanitize_text_field($request->get_param('token'));
        
        if (!$token) {
            return new WP_REST_Response(array(
                'success' => false,
                'error' => 'Token required'
            ), 400);
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'idena_sessions';
        $session = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE token = %s",
            $token
        ));
        
        if ($session && $session->status === 'authenticated') {
            return new WP_REST_Response(array(
                'success' => true,
                'data' => array(
                    'authenticated' => true,
                    'address' => $session->address
                )
            ));
        }
        
        return new WP_REST_Response(array(
            'success' => true,
            'data' => array(
                'authenticated' => false
            )
        ));
    }
    
    private function get_user_status($address) {
        // Cache results for performance
        $cache_key = 'idena_status_' . $address;
        $cached_status = get_transient($cache_key);
        
        if ($cached_status !== false) {
            return $cached_status;
        }
        
        $response = wp_remote_get('https://api.idena.io/api/identity/' . $address, array(
            'timeout' => 10,
            'headers' => array(
                'Accept' => 'application/json',
                'User-Agent' => 'WordPress Idena Auth Plugin'
            )
        ));
        
        if (!is_wp_error($response)) {
            $code = wp_remote_retrieve_response_code($response);
            $body = wp_remote_retrieve_body($response);
            
            if ($code == 200) {
                $data = json_decode($body, true);
                
                // Check for "no data found" error
                if (isset($data['error']) && strpos($data['error']['message'], 'no data found') !== false) {
                    $status = 'Not validated';
                } else {
                    // Look for status in result
                    $status = isset($data['result']['state']) ? $data['result']['state'] : 'Undefined';
                }
                
                // Cache for 5 minutes
                set_transient($cache_key, $status, 300);
                return $status;
            }
        }
        
        return 'Undefined';
    }
}