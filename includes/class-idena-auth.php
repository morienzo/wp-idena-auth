<?php
class Idena_Auth {
    
    private $api;
    private $user_manager;
    
    public function __construct() {
        $this->api = new Idena_API();
        $this->user_manager = new Idena_User();
    }
    
    public function init() {
        // Register API routes
        add_action('rest_api_init', array($this->api, 'register_routes'));
        
        // Add login button
        add_action('login_form', array($this, 'add_login_button'));
        add_shortcode('idena_login', array($this, 'login_button_shortcode'));
        
        // Register scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('login_enqueue_scripts', array($this, 'enqueue_login_scripts'));
        
        // Handle callback
        add_action('init', array($this, 'handle_callback'));
        
        // Add admin menu options
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Register settings
        add_action('admin_init', array($this, 'register_settings'));
    }
    
    public function enqueue_scripts() {
        wp_enqueue_style('idena-auth', IDENA_AUTH_PLUGIN_URL . 'assets/css/idena-auth.css', array(), IDENA_AUTH_VERSION);
        wp_enqueue_script('idena-auth', IDENA_AUTH_PLUGIN_URL . 'assets/js/idena-auth.js', array('jquery'), IDENA_AUTH_VERSION, true);
        
        wp_localize_script('idena-auth', 'idena_auth_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('idena-auth-nonce'),
            'site_url' => site_url()
        ));
    }
    
    public function enqueue_login_scripts() {
        $this->enqueue_scripts();
    }
    
    public function add_login_button() {
        include IDENA_AUTH_PLUGIN_DIR . 'templates/login-button.php';
    }
    
    public function login_button_shortcode($atts) {
        $atts = shortcode_atts(array(
            'redirect' => '',
            'class' => ''
        ), $atts);
        
        ob_start();
        include IDENA_AUTH_PLUGIN_DIR . 'templates/login-button.php';
        return ob_get_clean();
    }
    
    public function handle_callback() {
        if (isset($_GET['idena_callback']) && isset($_GET['token'])) {
            $token = sanitize_text_field($_GET['token']);
            
            // Check if this is the popup window opened by Idena
            if (isset($_GET['popup']) && $_GET['popup'] == '1') {
                // This is the popup - show a closing page
                ?>
                <!DOCTYPE html>
                <html>
                <head>
                    <meta charset="UTF-8">
                    <title>Authentication Complete</title>
                    <script>
                        // Try to close window
                        try {
                            window.close();
                        } catch(e) {}
                        
                        // Show message after a delay
                        setTimeout(function() {
                            document.getElementById('message').style.display = 'block';
                        }, 1000);
                    </script>
                    <style>
                        body {
                            font-family: Arial, sans-serif;
                            text-align: center;
                            padding: 50px;
                            background: #f5f5f5;
                        }
                        #message {
                            display: none;
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
                    </style>
                </head>
                <body>
                    <div id="message">
                        <div class="success-icon">✓</div>
                        <h2>Authentication Successful!</h2>
                        <p>You can close this window and return to the main page.</p>
                        <button onclick="window.close()" style="padding: 10px 20px; margin-top: 20px; cursor: pointer;">Close Window</button>
                    </div>
                </body>
                </html>
                <?php
                exit;
            }
            
            // Check session
            global $wpdb;
            $table_name = $wpdb->prefix . 'idena_sessions';
            $session = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table_name WHERE token = %s AND status = 'authenticated'",
                $token
            ));
            
            if ($session) {
                // Create or login user
                $user_id = $this->user_manager->create_or_login_user($session->address);
                
                if ($user_id) {
                    // Clean up session
                    $wpdb->delete($table_name, array('token' => $token));
                    
                    // Redirect
                    $redirect = isset($_GET['redirect']) ? esc_url($_GET['redirect']) : admin_url();
                    wp_redirect($redirect);
                    exit;
                }
            }
            
            // On error, redirect to login page
            wp_redirect(wp_login_url() . '?idena_error=1');
            exit;
        }
    }
    
    public function add_admin_menu() {
        add_options_page(
            'Idena Authentication',
            'Idena Auth',
            'manage_options',
            'idena-auth',
            array($this, 'admin_page')
        );
    }
    
    public function register_settings() {
        register_setting('idena_auth_settings', 'idena_auth_api_url');
        register_setting('idena_auth_settings', 'idena_auth_allowed_status');
    }
    
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Idena Authentication Settings', 'idena-auth'); ?></h1>
            <form method="post" action="options.php">
                <?php settings_fields('idena_auth_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Idena API URL', 'idena-auth'); ?></th>
                        <td>
                            <input type="text" name="idena_auth_api_url" value="<?php echo get_option('idena_auth_api_url', 'https://api.idena.io'); ?>" class="regular-text" />
                            <p class="description"><?php _e('URL of the Idena API to verify user status', 'idena-auth'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Allowed Status', 'idena-auth'); ?></th>
                        <td>
                            <?php 
                            $allowed_status = get_option('idena_auth_allowed_status', array('Verified', 'Human'));
                            $all_statuses = array(
                                'Candidate' => 'Candidate',
                                'Newbie' => 'Newbie',
                                'Verified' => 'Verified',
                                'Human' => 'Human',
                                'Suspended' => 'Suspended',
                                'Zombie' => 'Zombie',
                                'Not validated' => 'Not validated'
                            );
                            
                            foreach ($all_statuses as $value => $label) {
                                ?>
                                <label>
                                    <input type="checkbox" name="idena_auth_allowed_status[]" value="<?php echo esc_attr($value); ?>" <?php checked(in_array($value, $allowed_status)); ?>> 
                                    <?php echo esc_html($label); ?>
                                </label><br>
                                <?php
                            }
                            ?>
                            <p class="description"><?php _e('Select which Idena identity statuses are allowed to authenticate', 'idena-auth'); ?></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            
            <h2><?php _e('Status Descriptions', 'idena-auth'); ?></h2>
            <ul>
                <li><strong>Candidate</strong>: <?php _e('Invited but not yet validated', 'idena-auth'); ?></li>
                <li><strong>Newbie</strong>: <?php _e('Validated 1-2 times', 'idena-auth'); ?></li>
                <li><strong>Verified</strong>: <?php _e('Validated 3+ times', 'idena-auth'); ?></li>
                <li><strong>Human</strong>: <?php _e('Validated 4+ times with high score', 'idena-auth'); ?></li>
                <li><strong>Suspended</strong>: <?php _e('Missed the last validation', 'idena-auth'); ?></li>
                <li><strong>Zombie</strong>: <?php _e('Missed two or more validations', 'idena-auth'); ?></li>
                <li><strong>Not validated</strong>: <?php _e('Address exists but not validated', 'idena-auth'); ?></li>
            </ul>
            
            <h2><?php _e('Usage Instructions', 'idena-auth'); ?></h2>
            <p><?php _e('To add the Idena login button to your site:', 'idena-auth'); ?></p>
            <ol>
                <li><?php _e('Use shortcode:', 'idena-auth'); ?> <code>[idena_login]</code></li>
                <li><?php _e('The button automatically appears on the WordPress login page', 'idena-auth'); ?></li>
                <li><?php _e('You can add custom redirect:', 'idena-auth'); ?> <code>[idena_login redirect="/custom-page/"]</code></li>
            </ol>
        </div>
        <?php
    }
}