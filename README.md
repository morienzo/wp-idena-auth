# Idena Authentication for WordPress

A WordPress plugin that enables secure, privacy-preserving authentication using Idena blockchain identity.

## Overview

Idena Authentication allows your WordPress users to sign in using their Idena identity. Idena is a blockchain-based proof-of-person protocol that ensures each user is a unique human without collecting personal data.

## Features

- 🔐 **Secure Sign-in**: Authenticate via cryptographic signature (no passwords).
- 🚀 **Robust Redirection**: Uses a direct redirect flow compatible with all browsers (no blocking popups).
- 🔒 **Privacy-First**: 
  - Automatic user creation with anonymous handles (e.g., `id-a1b2c3d4`).
  - **Hashed Storage**: Idena addresses are stored as SHA-256 hashes in the database, ensuring privacy even if the database is compromised.
  - Display names are anonymized by default.
- 🛡️ **Access Control**:
  - Configure allowed statuses (Human, Verified, Newbie, etc.).
  - **Redirect on Failure**: Send unauthorized users to a custom landing page.
- ⚙️ **High Compatibility**: 
  - Works with Nginx and Apache.
  - Supports custom directory structures.
- 📱 **Mobile-Friendly**: Seamless experience on mobile devices.

## Requirements

- WordPress 5.0 or higher
- PHP 7.2 or higher
- SSL certificate (HTTPS) recommended
- Users need a validated Idena identity

## Installation

1. Download the plugin files.
2. Upload the `wp-idena-auth` folder to `/wp-content/plugins/`.
3. Activate the plugin through the 'Plugins' menu in WordPress.
4. Go to **Settings > Idena Auth** to configure.

## Configuration

### Basic Setup

1. Navigate to **Settings > Idena Auth**.
2. **Allowed Statuses**: Select which identity statuses can log in:
   - **Human** (default)
   - **Verified** (default)
   - **Newbie**
   - **Candidate**
   - ...and others.
3. **Redirect on Failure** (Optional): Enter a URL to redirect users who try to log in but don't have a valid status.

### Usage

Add the Idena login button to your site using shortcodes:

#### Method 1: Standard Button

''' [idena_login] '''

#### Method 2: Custom Redirect (After Success)
To redirect users to a specific page (e.g., Dashboard) after login:

''' [idena_login class="my-custom-class"] '''

The button also automatically appears on the default WordPress login page (`wp-login.php`).

## User Experience

1. User clicks "Sign in with Idena".
2. User is redirected to `app.idena.io` to sign the authentication request.
3. Upon success, user is redirected back to your site.
4. **Success**: User is logged in and redirected to the homepage (or custom URL).
5. **Failure** (Status not allowed): User is redirected to your custom "Failure URL" or shown an error message.

## Developer API

### Hooks & Actions

'''
// After successful authentication (fires in API)
do_action('idena_auth_success', $idena_address);

// After failed authentication attempt
do_action('idena_auth_failed', $idena_address, $reason);

// After new user is created
do_action('idena_user_created', $user_id, $idena_address);

// After user successfully logs in
do_action('idena_user_logged_in', $user_id, $idena_address);

'''
### Troubleshooting

#### Common Issues

Q: I get a 404 error when clicking sign in A: The plugin now forces index.php in API URLs to prevent this. Ensure you have updated to version 1.0.3+.

Q: Users are redirected to the homepage but not logged in A: This usually happens if the user creation fails. Enable WP_DEBUG to see if there are errors related to email generation or username conflicts.

Q: Access Denied redirection isn't working A: Ensure you have entered a valid absolute URL (starting with https://) in the settings.

### License

This plugin is licensed under the GPL v2 or later.

### Credits 

Developed by morienzo
Based on the Idena Protocol
