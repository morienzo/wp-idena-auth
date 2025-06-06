# Idena Authentication for WordPress

A WordPress plugin that enables secure, privacy-preserving authentication using Idena blockchain identity.

## Overview

Idena Authentication allows your WordPress users to sign in using their Idena identity. Idena is a blockchain-based proof-of-person protocol that ensures each user is a unique human without collecting personal data.

## Features

- 🔐 **One-click sign-in** with Idena
- 🚫 **No passwords required**
- 🔒 **Privacy-preserving** authentication
- 👤 **Automatic user creation**
- 🎭 **Role assignment** based on Idena status
- ⚙️ **Customizable** allowed status levels
- 📱 **Works with both** Idena Web App and Desktop App
- 🌐 **Mobile-friendly** experience

## Requirements

- WordPress 5.0 or higher
- PHP 7.2 or higher
- SSL certificate (HTTPS) recommended
- Users need a validated Idena identity

## Installation

1. Download the plugin files
2. Upload the `wp-idena-auth` folder to `/wp-content/plugins/`
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Go to Settings > Idena Auth to configure

## Configuration

### Basic Setup

1. Navigate to **Settings > Idena Auth**
2. Configure allowed status levels:
   - **Human** (default) - Validated 4+ times with high score
   - **Verified** (default) - Validated 3+ times
   - **Newbie** - Validated 1-2 times
   - **Candidate** - Invited but not yet validated
   - **Suspended** - Missed the last validation
   - **Zombie** - Missed two or more validations
   - **Not validated** - Address exists but not validated

### Usage

Add the Idena login button to your site using one of these methods:

#### Method 1: Shortcode
```
[idena_login]
```

With custom redirect:
```
[idena_login redirect="/dashboard/"]
```

With custom CSS class:
```
[idena_login class="my-custom-class"]
```

#### Method 2: Automatic Login Page
The button automatically appears on the WordPress login page.

#### Method 3: PHP Template
```php
<?php echo do_shortcode('[idena_login]'); ?>
```

## User Experience

1. User clicks "Sign in with Idena"
2. Redirected to Idena Web App
3. User signs the authentication request
4. Automatically redirected back and logged in
5. New users are created automatically

## Security Features

- Cryptographic signature verification
- Session tokens expire after 1 hour
- No personal data stored
- Nonce verification for CSRF protection
- Automatic session cleanup

## Developer API

### Hooks

#### Actions
```php
// After successful authentication (fires in API)
do_action('idena_auth_success', $idena_address);

// After failed authentication attempt
do_action('idena_auth_failed', $idena_address, $reason);

// After new user is created
do_action('idena_user_created', $user_id, $idena_address);

// After user successfully logs in
do_action('idena_user_logged_in', $user_id, $idena_address);

// After user role is changed based on Idena status
do_action('idena_user_role_changed', $user_id, $new_role, $idena_status);
```

#### Filters
```php
// Modify allowed status levels
add_filter('idena_auth_allowed_status', function($statuses) {
    $statuses[] = 'Newbie';
    return $statuses;
});

// Customize new user data before creation
add_filter('idena_auth_new_user_data', function($user_data, $idena_address) {
    $user_data['role'] = 'contributor';
    $user_data['first_name'] = 'Idena';
    $user_data['last_name'] = 'User';
    return $user_data;
}, 10, 2);

// Customize role assignment based on Idena status
add_filter('idena_auth_user_role', function($role, $status, $user_id) {
    if ($status === 'Human') {
        return 'author'; // Give more privileges to Human status
    }
    return $role;
}, 10, 3);

// Control "Remember Me" option for Idena logins
add_filter('idena_auth_remember_user', function($remember, $user_id) {
    return true; // Always remember Idena users
}, 10, 2);
```

### Functions

```php
// Get user's Idena address
$address = get_user_meta($user_id, 'idena_address', true);

// Check if user authenticated with Idena
$is_idena_user = get_user_meta($user_id, 'idena_auth', true);

// Get user's Idena status
$status = get_user_meta($user_id, 'idena_status', true);

// Get user's last Idena login time
$last_login = get_user_meta($user_id, 'idena_last_login', true);

// Get when user was created via Idena
$created_date = get_user_meta($user_id, 'idena_created', true);
```

### User Manager Class Methods

```php
// Get the Idena_User instance
$idena_user = new Idena_User();

// Get all users who authenticated with Idena
$idena_users = $idena_user->get_idena_users(array(
    'orderby' => 'ID',
    'order' => 'DESC',
    'number' => 10 // Limit to 10 users
));

// Count users by their Idena status
$status_counts = $idena_user->count_users_by_status();
// Returns: array('Human' => 5, 'Verified' => 12, 'Newbie' => 3, ...)

// Get user by Idena address (fast query)
$user_id = $idena_user->get_user_by_idena_address('0x...');

// Update user's Idena status and role
$idena_user->update_user_status($user_id, 'Human');
```

### Example Implementations

#### 1. Restrict Content to Human Status Only
```php
function restrict_to_humans_only() {
    if (!is_user_logged_in()) {
        return false;
    }
    
    $user_id = get_current_user_id();
    $idena_status = get_user_meta($user_id, 'idena_status', true);
    
    return $idena_status === 'Human';
}

// In your template
if (restrict_to_humans_only()) {
    echo 'This content is only for verified Humans';
}
```

#### 2. Display Idena Status Badge
```php
function display_idena_badge($user_id) {
    $is_idena = get_user_meta($user_id, 'idena_auth', true);
    
    if ($is_idena) {
        $status = get_user_meta($user_id, 'idena_status', true);
        $address = get_user_meta($user_id, 'idena_address', true);
        
        echo '<div class="idena-badge">';
        echo '<img src="' . IDENA_AUTH_PLUGIN_URL . 'assets/images/idena-logo.svg" alt="Idena">';
        echo '<span class="status">' . esc_html($status) . '</span>';
        echo '<span class="address" title="' . esc_attr($address) . '">';
        echo esc_html(substr($address, 0, 6) . '...' . substr($address, -4));
        echo '</span>';
        echo '</div>';
    }
}
```

#### 3. Custom Welcome Message for Idena Users
```php
add_action('idena_user_logged_in', function($user_id, $address) {
    // Set a transient for welcome message
    set_transient('idena_welcome_' . $user_id, true, 60);
    
    // Log the login
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log(sprintf(
            'Idena login: User %d with address %s at %s',
            $user_id,
            $address,
            current_time('mysql')
        ));
    }
}, 10, 2);

// Display welcome message
add_action('admin_notices', function() {
    $user_id = get_current_user_id();
    
    if (get_transient('idena_welcome_' . $user_id)) {
        $address = get_user_meta($user_id, 'idena_address', true);
        ?>
        <div class="notice notice-success is-dismissible">
            <p>Welcome back! You're logged in with Idena address: 
            <code><?php echo esc_html($address); ?></code></p>
        </div>
        <?php
        delete_transient('idena_welcome_' . $user_id);
    }
});
```

#### 4. Sync Idena Status Periodically
```php
// Schedule status sync for all Idena users
add_action('init', function() {
    if (!wp_next_scheduled('idena_sync_user_statuses')) {
        wp_schedule_event(time(), 'daily', 'idena_sync_user_statuses');
    }
});

add_action('idena_sync_user_statuses', function() {
    $idena_user = new Idena_User();
    $users = $idena_user->get_idena_users();
    
    foreach ($users as $user) {
        $address = get_user_meta($user->ID, 'idena_address', true);
        // Call API to get current status
        // Update if changed
    }
});
```

### REST API Endpoints

- `POST /wp-json/idena-auth/v1/start-session` - Initialize authentication
- `POST /wp-json/idena-auth/v1/authenticate` - Verify signature
- `GET /wp-json/idena-auth/v1/check-status` - Check authentication status
- `GET /wp-json/idena-auth/v1/callback` - Handle authentication callback

### Performance Considerations

1. **API Response Caching**: User status is cached for 5 minutes using WordPress transients
2. **Database Queries**: Direct SQL queries are used for better performance when looking up users by Idena address
3. **Session Cleanup**: Automatic cleanup of expired sessions every hour

## Troubleshooting

### Common Issues

**Q: The authentication window doesn't close automatically**
A: This is normal on mobile devices. Users need to manually close the window and return to your site.

**Q: Users get "Authentication failed" error**
A: Check that:
- Their Idena status is in the allowed list
- The Idena API is accessible from your server
- Session tokens haven't expired

**Q: The button doesn't appear**
A: Ensure:
- The plugin is activated
- You're using the correct shortcode
- No JavaScript errors in console

### Debug Mode

Enable debug logging by adding to `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## Localization

The plugin is translation-ready. To translate:

1. Use the `idena-auth.pot` file in `/languages/`
2. Create `.po` and `.mo` files for your language
3. Place them in `/wp-content/languages/plugins/`

Example: For French, create:
- `idena-auth-fr_FR.po`
- `idena-auth-fr_FR.mo`

## Support

- **GitHub Issues**: [Report bugs](https://github.com/morienzo/wp-idena-auth)
- **Discord**: [Join Idena Discord](https://discord.gg/idena-community-634481767352369162)
- **Documentation**: [Idena Docs](https://docs.idena.io/)

## Contributing

Contributions are welcome! Please:

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

This plugin is licensed under the GPL v2 or later.

## Credits

- Developed by morienzo
- Based on the [Idena Protocol](https://idena.io/)
- Uses [WordPress REST API](https://developer.wordpress.org/rest-api/)

## Changelog

### 1.1.0 (Upcoming)
- Added API response caching (5 minutes) for better performance
- Optimized database queries using direct SQL
- Added developer hooks for extensibility
- Added `get_idena_users()` and `count_users_by_status()` methods
- Improved user creation with customizable data
- Added role mapping based on Idena status
- Better error handling and logging
- Removed unused code and optimized assets

### 1.0.0 (2025-06-05)
- Initial release
- Basic authentication functionality
- Automatic user creation
- Configurable status levels
- Mobile-friendly design
- Session cleanup system
