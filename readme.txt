=== Idena Authentication ===
Author: morienzo
Tags: authentication, login, blockchain, idena, sso, web3, identity, verification
Requires at least: 5.0
Tested up to: 6.6
Requires PHP: 7.2
Stable tag: 1.0.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Enable secure authentication via Idena blockchain identity. Allow only verified humans to access your WordPress site.

== Description ==

Idena Authentication plugin enables WordPress sites to authenticate users through the Idena blockchain network. Idena is a proof-of-person blockchain that ensures each user is a unique human being through regular validation sessions.

= Key Features =

* **Proof-of-Person Authentication**: Only verified humans can authenticate.
* **No Password Required**: Users authenticate via cryptographic signature.
* **Robust Redirection Flow**: Works on all browsers (mobile & desktop) without popups.
* **Privacy-First**: 
    * Accounts are created with anonymous IDs (e.g., `id-a1b2c3d4`).
    * Blockchain addresses are stored as **SHA-256 hashes** (not plain text) for maximum privacy.
* **Configurable Access Levels**: Choose which Idena statuses (Human, Verified, etc.) can access your site.
* **Custom "Access Denied" Page**: Redirect unauthorized users to a specific page.
* **High Compatibility**: Works with Nginx/Apache and strict server configurations.

= How It Works =

1. Users click "Sign in with Idena".
2. They are redirected to the Idena Web App to sign a unique token.
3. Upon success, they are redirected back to your site.
4. The plugin verifies the signature and creates/logs in the user automatically.

= Idena Identity Statuses =

* **Human**: Highest level - validated 4+ times with high score.
* **Verified**: Validated 3+ times successfully.
* **Newbie**: New identity validated 1-2 times.
* **Candidate**: Invited but not yet validated.
* **Suspended**: Missed the last validation.
* **Zombie**: Missed two or more validations.
* **Not validated**: Address exists but not validated.

== Installation ==

= From WordPress Admin =

1. Navigate to Plugins > Add New.
2. Search for "Idena Authentication".
3. Click "Install Now" and then "Activate".
4. Go to Settings > Idena Auth to configure.

= Manual Installation =

1. Download the plugin zip file.
2. Upload to `/wp-content/plugins/` directory.
3. Extract the zip file.
4. Activate the plugin through the 'Plugins' menu.
5. Configure in Settings > Idena Auth.

== Configuration ==

1. Go to **Settings > Idena Auth**.
2. **API URL**: Default is `https://api.idena.io` (Official node).
3. **Allowed Statuses**: Check the statuses you want to allow (e.g., Human, Verified).
4. **Redirect on Failure**: (Optional) Enter a URL to redirect users who don't have the required status (e.g., a "How to join" page).
5. Save your settings.

== Usage ==

**Standard Shortcode:**
`[idena_login]`

**Redirect after success:**
`[idena_login redirect="/dashboard/"]`

**Custom CSS Class:**
`[idena_login class="my-custom-btn"]`

The login button automatically appears on the standard WordPress login page.

== Frequently Asked Questions ==

= What is Idena? =
Idena is a proof-of-person blockchain where every node is linked to a cryptoidentity - a unique human.

= Does it work on mobile? =
Yes, the plugin uses a direct redirection flow that is fully compatible with mobile browsers and wallets.

= Can I restrict content based on status? =
Yes, the plugin denies login to users without the allowed status. You can also set a custom redirection URL for them.

= Is user privacy protected? =
Yes. The plugin creates accounts using a shortened hash of the address (e.g., `id-a1b2c3d4`) and does not collect emails or real names.

== Changelog ==

= 1.0.5 =
* Security: Implemented SHA-256 hashing for storing Idena addresses in the database.
* Feature: Added automatic migration for existing users (plain text addresses are converted to hashes upon login).
* Privacy: Enforced anonymous display names by default.

= 1.0.4 =
* Feature: Added "Redirect on Failure" setting for unauthorized statuses.
* Feature: Anonymous user creation (id-xxxx) for better privacy.
* Fix: Switched to direct redirection flow (removed popups) to solve about:blank issues.
* Fix: Improved API URL construction for Nginx compatibility.
* Performance: Optimized callback handling with early hooks.

= 1.0.0 =
* Initial release.
