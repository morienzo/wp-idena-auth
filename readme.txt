=== Idena Authentication ===
Author: morienzo
Tags: authentication, login, blockchain, idena, sso, web3, identity, verification
Requires at least: 5.0
Tested up to: 6.6
Requires PHP: 7.2
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Enable secure authentication via Idena blockchain identity. Allow only verified humans to access your WordPress site.

== Description ==

Idena Authentication plugin enables WordPress sites to authenticate users through the Idena blockchain network. Idena is a proof-of-person blockchain that ensures each user is a unique human being through regular validation sessions.

= Key Features =

* **Proof-of-Person Authentication**: Only verified humans can authenticate
* **No Password Required**: Users authenticate with their Idena identity
* **Configurable Access Levels**: Choose which Idena identity statuses can access your site
* **Seamless Integration**: Works with existing WordPress user system
* **Privacy-Focused**: No personal data is stored, only blockchain addresses
* **Mobile-Friendly**: Works on all devices with responsive design
* **Multiple API Endpoints**: Fallback support for high availability

= How It Works =

1. Users click "Sign in with Idena" button
2. They authenticate in the Idena web app or desktop app
3. Plugin verifies their identity status on the blockchain
4. Verified users are automatically logged into WordPress

= Use Cases =

* **Community Sites**: Ensure real humans in your community
* **Voting/Polling Sites**: One person = one vote guarantee
* **Premium Content**: Restrict access to verified humans only
* **Anti-Bot Protection**: Eliminate bots and fake accounts
* **Membership Sites**: Unique human verification for members

= Idena Identity Statuses =

* **Human**: Highest level - validated 4+ times with high score
* **Verified**: Validated 3+ times successfully
* **Newbie**: New identity validated 1-2 times
* **Candidate**: Invited but not yet validated
* **Suspended**: Missed the last validation
* **Zombie**: Missed two or more validations
* **Not validated**: Address exists but not validated

== Installation ==

= From WordPress Admin =

1. Navigate to Plugins > Add New
2. Search for "Idena Authentication"
3. Click "Install Now" and then "Activate"
4. Go to Settings > Idena Auth to configure

= Manual Installation =

1. Download the plugin zip file
2. Upload to `/wp-content/plugins/` directory
3. Extract the zip file
4. Activate the plugin through the 'Plugins' menu
5. Configure in Settings > Idena Auth

= Configuration =

1. Go to Settings > Idena Auth
2. Select which identity statuses can authenticate (default: Verified and Human)
3. Save your settings

= Usage =

**Add Login Button via Shortcode:**
`[idena_login]`

**With Custom Redirect:**
`[idena_login redirect="/dashboard/"]`

**With Custom CSS Class:**
`[idena_login class="my-custom-class"]`

The login button automatically appears on the WordPress login page.

== Frequently Asked Questions ==

= What is Idena? =

Idena is a proof-of-person blockchain where every node is linked to a cryptoidentity - a unique human. It uses regular validation sessions (every 3 weeks) where users solve flip-tests to prove they are human.

= Is it free to use Idena? =

Yes, creating an Idena identity is free. Users need to be invited by an existing member and pass validation sessions to maintain their status.

= Do users need cryptocurrency? =

No, users don't need to purchase any cryptocurrency. They can earn iDNA coins by participating in validations.

= What happens to existing WordPress users? =

Existing users continue to work normally. Idena authentication creates new users or can be linked to existing accounts.

= Can I require Idena authentication for all users? =

Yes, you can use this plugin alongside other plugins that restrict site access to logged-in users only.

= Is user privacy protected? =

Yes, only the blockchain address is stored. No personal information is collected or transmitted.

= What if Idena's API is down? =

The plugin tries multiple API endpoints for redundancy. Users can still use regular WordPress login if enabled.

= Can I customize the appearance? =

Yes, the plugin includes CSS classes that can be customized in your theme. The shortcode also accepts a custom class parameter.

== Screenshots ==

1. Login button on WordPress login page
2. Idena authentication popup
3. Plugin settings page
4. Shortcode implementation example
5. Successful authentication message

== Changelog ==

= 1.0.0 =
* Initial release
* Core authentication functionality
* Configurable identity status requirements
* Shortcode support
* Admin settings page
* Session management
* Multi-API endpoint support

== Upgrade Notice ==

= 1.0.0 =
Initial release of Idena Authentication plugin.

== Technical Details ==

= Requirements =

* WordPress 5.0 or higher
* PHP 7.2 or higher
* MySQL 5.6 or higher
* HTTPS recommended for security

= API Endpoints Used =

The plugin connects to official Idena API endpoints to verify user identity status. No third-party services are used.

= Database =

The plugin creates one table to manage authentication sessions. All sessions are automatically cleaned up.

= Hooks and Filters =

**Actions:**
* `idena_auth_user_authenticated` - Fired after successful authentication
* `idena_auth_user_created` - Fired when new user is created

**Filters:**
* `idena_auth_allowed_statuses` - Modify allowed identity statuses
* `idena_auth_username_format` - Customize username generation

== Support ==

For support, feature requests, or bug reports:

* Visit the [plugin support forum](https://github.com/morienzo/wp-idena-auth)
* Check the [Idena documentation](https://idena.io/docs)
* Join the [Idena community](https://discord.gg/idena-community-634481767352369162)

== Contributing ==

This plugin is open source and welcomes contributions. Visit our [GitHub repository](https://github.com/morienzo/wp-idena-auth) to contribute.

== Privacy Policy ==

This plugin:
* Only stores Idena blockchain addresses
* Does not collect personal information
* Does not use cookies beyond WordPress standards
* Does not send data to third parties
* All authentication is done directly with Idena network

== Credits ==

Developed by the Idena Community for the WordPress community.