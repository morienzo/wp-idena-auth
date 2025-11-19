<?php
class Idena_Auth {
    
    private $api;
    private $user_manager;
    
    public function __construct() {
        $this->api = new Idena_API();
        $this->user_manager = new Idena_User();
    }
    
    public function init() {
        // Core hooks
        add_action('rest_api_init', array($this->api, 'register_routes'));
        add_action('login_form', array($this, 'add_login_button'));
        add_shortcode('idena_login', array($this, 'login_button_shortcode'));
        
        // Load styles on frontend and login page
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('login_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // Callback interception (Priority 1 for speed)
        add_action('parse_request', array($this, 'handle_callback'), 1);
        add_action('init', array($this, 'handle_callback'), 1);
        
        // Admin
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
    }
    
    public function enqueue_scripts() {
        wp_enqueue_style('idena-auth', IDENA_AUTH_PLUGIN_URL . 'assets/css/idena-auth.css', array(), IDENA_AUTH_VERSION);
    }
    
    public function add_login_button() {
        include IDENA_AUTH_PLUGIN_DIR . 'templates/login-button.php';
    }
    
    public function login_button_shortcode($atts) {
        ob_start();
        include IDENA_AUTH_PLUGIN_DIR . 'templates/login-button.php';
        return ob_get_clean();
    }
    
    public function handle_callback() {
        // Quick check to avoid overhead
        if (!isset($_GET['idena_token'])) {
            return;
        }

        $token = sanitize_text_field($_GET['idena_token']);
        global $wpdb;
        $table_name = $wpdb->prefix . 'idena_sessions';
        
        $session = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE token = %s", $token));
        
        if (!$session) {
            wp_redirect(wp_login_url() . '?idena_error=auth_failed');
            exit;
        }
        
        // --- CASE 1: DENIED STATUS ---
        if ($session->status === 'denied') {
            $wpdb->delete($table_name, array('token' => $token));
            
            $custom_redirect = get_option('idena_auth_redirect_failed');
            if (!empty($custom_redirect)) {
                wp_redirect($custom_redirect);
                exit;
            }
            
            wp_redirect(wp_login_url() . '?idena_error=status_not_allowed');
            exit;
        }
        
        // --- CASE 2: AUTHENTICATED ---
        if ($session->status === 'authenticated') {
            if (!class_exists('Idena_User')) {
                require_once IDENA_AUTH_PLUGIN_DIR . 'includes/class-idena-user.php';
            }
            
            $user_manager = new Idena_User();
            $user_id = $user_manager->create_or_login_user($session->address);
            
            if ($user_id) {
                $wpdb->delete($table_name, array('token' => $token));
                
                // Handle redirect param
                $redirect_url = home_url('/');
                if (isset($_GET['redirect_to'])) {
                    $redirect_url = esc_url_raw($_GET['redirect_to']);
                }
                
                wp_redirect($redirect_url);
                exit;
            } else {
                wp_redirect(wp_login_url() . '?idena_error=create_failed');
                exit;
            }
        }
        
        // Default fallback
        wp_redirect(wp_login_url() . '?idena_error=auth_failed');
        exit;
    }
    
    public function add_admin_menu() {
        add_options_page('Idena Authentication', 'Idena Auth', 'manage_options', 'idena-auth', array($this, 'admin_page'));
    }
    
    public function register_settings() {
        register_setting('idena_auth_settings', 'idena_auth_api_url');
        register_setting('idena_auth_settings', 'idena_auth_allowed_status', array('type'=>'array', 'sanitize_callback'=>array($this, 'sanitize_allowed_status')));
        register_setting('idena_auth_settings', 'idena_auth_redirect_failed');
    }
    
    public function sanitize_allowed_status($input) {
        if (!is_array($input)) return array();
        return array_map('sanitize_text_field', $input);
    }
    
    public function admin_page() {
        if (!current_user_can('manage_options')) return;
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
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Allowed Statuses', 'idena-auth'); ?></th>
                        <td>
                            <?php 
                            $allowed_status = get_option('idena_auth_allowed_status', array('Verified', 'Human'));
                            $all_statuses = array('Candidate', 'Newbie', 'Verified', 'Human', 'Suspended', 'Zombie', 'Not validated');
                            foreach ($all_statuses as $val) {
                                echo '<label style="display:block; margin-bottom:5px;"><input type="checkbox" name="idena_auth_allowed_status[]" value="'.$val.'" '.checked(in_array($val, (array)$allowed_status), true, false).'> '.$val.'</label>';
                            }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Redirect on Failure', 'idena-auth'); ?></th>
                        <td>
                            <input type="url" name="idena_auth_redirect_failed" value="<?php echo get_option('idena_auth_redirect_failed'); ?>" class="regular-text" placeholder="https://..." />
                            <p class="description">
                                <?php _e('Optional. Redirect users to this URL if their Idena status is not allowed.', 'idena-auth'); ?>
                            </p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            
            <hr>
            
            <h2><?php _e('Status Descriptions', 'idena-auth'); ?></h2>
            <ul>
                <li><strong>Human</strong>: Validated 4+ times with high score</li>
                <li><strong>Verified</strong>: Validated 3+ times</li>
                <li><strong>Newbie</strong>: Validated 1-2 times</li>
                <li><strong>Candidate</strong>: Invited but not yet validated</li>
                <li><strong>Suspended</strong>: Missed the last validation</li>
                <li><strong>Zombie</strong>: Missed two or more validations</li>
                <li><strong>Not validated</strong>: Address exists but not validated</li>
            </ul>
            
            <h2><?php _e('Usage Instructions', 'idena-auth'); ?></h2>
            <p><?php _e('You can add the Idena login button anywhere using shortcodes:', 'idena-auth'); ?></p>
            
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>Description</th>
                        <th>Shortcode</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><?php _e('Standard button', 'idena-auth'); ?></td>
                        <td><code>[idena_login]</code></td>
                    </tr>
                    <tr>
                        <td><?php _e('Redirect after SUCCESSFUL login', 'idena-auth'); ?></td>
                        <td><code>[idena_login redirect="/dashboard/"]</code></td>
                    </tr>
                    <tr>
                        <td><?php _e('Custom CSS class', 'idena-auth'); ?></td>
                        <td><code>[idena_login class="my-custom-class"]</code></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <?php
    }
}