<?php
/**
 * Plugin Name: WP Mail SMTP Pro
 * Version: 1.9.0
 * Plugin URI: https://wpmailsmtp.com/
 * Description: Reconfigures the <code>wp_mail()</code> function to use Gmail/Mailgun/SendGrid/SMTP instead of the default <code>mail()</code> and creates an options page to manage the settings.
 * Author: WPForms
 * Author URI: https://wpforms.com/
 * Network: false
 * Text Domain: wp-mail-smtp
 * Domain Path: /assets/languages
 */

/**
 * @author    WPForms
 * @copyright WPForms, 2007-20, All Rights Reserved
 * This code is released under the GPL licence version 3 or later, available here
 * https://www.gnu.org/licenses/gpl.txt
 */

/**
 * Setting options in wp-config.php
 *
 * Specifically aimed at WP Multisite users, you can set the options for this plugin as
 * constants in wp-config.php. Copy the code below into wp-config.php and tweak settings.
 * Values from constants are NOT stripslash()'ed.
 *
 * When enabled, make sure to comment out (at the beginning of the line using //) those constants that you do not need,
 * or remove them completely, so they won't interfere with plugin settings.
 */

/*
define( 'WPMS_ON', true ); // True turns on the whole constants support and usage, false turns it off.

define( 'WPMS_DO_NOT_SEND', true ); // Or false, in that case constant is ignored.

define( 'WPMS_MAIL_FROM', 'mail@example.com' );
define( 'WPMS_MAIL_FROM_FORCE', true ); // True turns it on, false turns it off.
define( 'WPMS_MAIL_FROM_NAME', 'From Name' );
define( 'WPMS_MAIL_FROM_NAME_FORCE', true ); // True turns it on, false turns it off.
define( 'WPMS_MAILER', 'sendinblue' ); // Possible values: 'mail', 'sendinblue', 'mailgun', 'sendgrid', 'gmail', 'smtp'.
define( 'WPMS_SET_RETURN_PATH', true ); // Sets $phpmailer->Sender if true, relevant only for Other SMTP mailer.

// Recommended mailers.
define( 'WPMS_PEPIPOST_API_KEY', '' );
define( 'WPMS_SENDINBLUE_API_KEY', '' );

define( 'WPMS_MAILGUN_API_KEY', '' );
define( 'WPMS_MAILGUN_DOMAIN', '' );
define( 'WPMS_MAILGUN_REGION', 'US' ); // or 'EU' for Europe.

define( 'WPMS_SENDGRID_API_KEY', '' );

define( 'WPMS_GMAIL_CLIENT_ID', '' );
define( 'WPMS_GMAIL_CLIENT_SECRET', '' );

define( 'WPMS_SMTP_HOST', 'localhost' ); // The SMTP mail host.
define( 'WPMS_SMTP_PORT', 25 ); // The SMTP server port number.
define( 'WPMS_SSL', '' ); // Possible values '', 'ssl', 'tls' - note TLS is not STARTTLS.
define( 'WPMS_SMTP_AUTH', true ); // True turns it on, false turns it off.
define( 'WPMS_SMTP_USER', 'username' ); // SMTP authentication username, only used if WPMS_SMTP_AUTH is true.
define( 'WPMS_SMTP_PASS', 'password' ); // SMTP authentication password, only used if WPMS_SMTP_AUTH is true.
define( 'WPMS_SMTP_AUTOTLS', true ); // True turns it on, false turns it off.
*/

/**
 * Don't allow multiple versions of 1.5.x (Lite and Pro) and above to be active.
 *
 * @since 1.5.0
 */
if ( function_exists( 'wp_mail_smtp' ) ) {

	if ( ! function_exists( 'wp_mail_smtp_deactivate' ) ) {
		/**
		 * Deactivate if plugin already activated.
		 * Needed when transitioning from 1.5+ Lite to Pro.
		 *
		 * @since 1.5.0
		 */
		function wp_mail_smtp_deactivate() {

			deactivate_plugins( plugin_basename( __FILE__ ) );
		}
	}
	add_action( 'admin_init', 'wp_mail_smtp_deactivate' );

	// Do not process the plugin code further.
	return;
}

if ( ! function_exists( 'wp_mail_smtp_check_pro_loading_allowed' ) ) {
	/**
	 * Don't allow 1.4.x and below to break when 1.5+ Pro is activated.
	 * This will stop the current plugin from loading and display a message in admin area.
	 *
	 * @since 1.5.0
	 */
	function wp_mail_smtp_check_pro_loading_allowed() {

		// Check for pro without using wp_mail_smtp()->is_pro(), because at this point it's too early.
		if ( ! is_readable( rtrim( plugin_dir_path( __FILE__ ), '/\\' ) . '/src/Pro/Pro.php' ) ) {
			// Currently, not a pro version of the plugin is loaded.
			return false;
		}

		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . '/wp-admin/includes/plugin.php';
		}

		// Search for old plugin name.
		if ( is_plugin_active( 'wp-mail-smtp/wp_mail_smtp.php' ) ) {
			// As Pro is loaded and Lite too - deactivate *silently* itself not to break older SMTP plugin.
			deactivate_plugins( plugin_basename( __FILE__ ) );

			add_action( 'admin_notices', 'wp_mail_smtp_lite_deactivation_notice' );

			return true;
		}

		return false;
	}

	if ( ! function_exists( 'wp_mail_smtp_lite_deactivation_notice' ) ) {
		/**
		 * Display the notice after deactivation.
		 *
		 * @since 1.5.0
		 */
		function wp_mail_smtp_lite_deactivation_notice() {

			echo '<div class="notice notice-warning"><p>' . esc_html__( 'Please deactivate the free version of the WP Mail SMTP plugin before activating WP Mail SMTP Pro.', 'wp-mail-smtp' ) . '</p></div>';

			if ( isset( $_GET['activate'] ) ) { // phpcs:ignore
				unset( $_GET['activate'] ); // phpcs:ignore
			}
		}
	}

	// Stop the plugin loading.
	if ( wp_mail_smtp_check_pro_loading_allowed() === true ) {
		return;
	}
}

if ( ! defined( 'WPMS_PLUGIN_VER' ) ) {
	define( 'WPMS_PLUGIN_VER', '1.9.0' );
}
if ( ! defined( 'WPMS_PHP_VER' ) ) {
	define( 'WPMS_PHP_VER', '5.3.6' );
}

/**
 * Newer PHP version 5.3+ will be handled a lot differently,
 * with better code and newer logic.
 *
 * @since 1.0.0
 */
if ( version_compare( phpversion(), WPMS_PHP_VER, '>=' ) ) {
	require_once dirname( __FILE__ ) . '/wp-mail-smtp.php';

	return;
}

/**
 * PHP 5.2 only.
 */
require_once dirname( __FILE__ ) . '/wp-mail-smtp-0.11.2.php';
