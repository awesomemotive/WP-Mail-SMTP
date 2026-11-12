<?php

namespace WPMailSMTP;

use WPMailSMTP\Deprecated\Connect as DeprecatedMethods;

/**
 * WP Mail SMTP Connect.
 *
 * WP Mail SMTP Connect sends a Lite user with a license key to the upgrade page,
 * which hands out the Pro plugin download.
 *
 * @since 2.6.0
 */
class Connect {

	/**
	 * Backward-compatibility methods relocated from this class.
	 *
	 * @since 4.10.4
	 */
	use DeprecatedMethods;

	/**
	 * Query parameter marking a return URL as coming back from an upgrade.
	 *
	 * @since 4.10.0
	 *
	 * @var string
	 */
	const UPGRADED_QUERY_ARG = 'wp_mail_smtp_upgraded';

	/**
	 * The Pro plugin's basename.
	 *
	 * @since 4.10.1
	 *
	 * @var string
	 */
	private const PRO_PLUGIN_BASENAME = 'wp-mail-smtp-pro/wp_mail_smtp.php';

	/**
	 * Hooks.
	 *
	 * @since 2.6.0
	 */
	public function hooks() {

		add_action( 'wp_ajax_wp_mail_smtp_connect_url', [ $this, 'ajax_generate_url' ] );
	}

	/**
	 * Generate and return WP Mail SMTP Connect URL.
	 *
	 * @since 2.6.0
	 *
	 * @param string $key      The license key.
	 * @param string $oth      Unused, kept for backward compatibility.
	 * @param string $redirect The redirect URL.
	 *
	 * @return bool|string
	 */
	public static function generate_url( $key, $oth = '', $redirect = '' ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed

		if ( empty( $key ) || wp_mail_smtp()->is_pro() ) {
			return false;
		}

		$redirect = ! empty( $redirect ) ? $redirect : wp_mail_smtp()->get_admin()->get_admin_page_url();

		return add_query_arg(
			[
				'key'      => $key,
				'version'  => WPMS_PLUGIN_VER,
				'siteurl'  => admin_url(),
				'homeurl'  => wp_mail_smtp()->get_license_site_url()->derive(),
				'redirect' => rawurldecode( base64_encode( $redirect ) ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
				'v'        => 2,
			],
			'https://upgrade.wpmailsmtp.com'
		);
	}

	/**
	 * The response that points the user at the Plugins screen when Pro is
	 * already installed, empty otherwise.
	 *
	 * @since 4.10.4
	 *
	 * @param bool $is_activation_link Point at a one-click activation instead, for requests that carry the user's login cookies.
	 *
	 * @return array
	 */
	public static function get_pro_installed_response( $is_activation_link = false ) {

		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		if ( ! array_key_exists( self::PRO_PLUGIN_BASENAME, get_plugins() ) ) {
			return [];
		}

		// The Plugins screen keeps `s` through the post-activation redirect, so the list stays filtered to Pro.
		$search = rawurlencode( 'WP Mail SMTP Pro' );

		if ( $is_activation_link && current_user_can( 'activate_plugins' ) ) {
			$url = add_query_arg(
				[
					'action'   => 'activate',
					'plugin'   => rawurlencode( self::PRO_PLUGIN_BASENAME ),
					's'        => $search,
					'_wpnonce' => wp_create_nonce( 'activate-plugin_' . self::PRO_PLUGIN_BASENAME ),
				],
				admin_url( 'plugins.php' )
			);
		} else {
			$url = add_query_arg(
				[
					'plugin_status' => 'inactive',
					's'             => $search,
				],
				admin_url( 'plugins.php' )
			);
		}

		return [
			'message'          => esc_html__( 'WP Mail SMTP Pro is already installed on your site. Activate it to start using your Pro features.', 'wp-mail-smtp' ),
			'plugins_page_url' => $url,
		];
	}

	/**
	 * AJAX callback to generate and return the WP Mail SMTP Connect URL.
	 *
	 * @since 2.6.0
	 */
	public function ajax_generate_url() { //phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh

		if ( check_ajax_referer( 'wp-mail-smtp-admin', 'nonce', false ) === false ) {
			wp_send_json_error( esc_html__( 'Your session expired. Please reload the page and try again.', 'wp-mail-smtp' ) );
		}

		// Check for permissions.
		if ( ! current_user_can( 'install_plugins' ) ) {
			wp_send_json_error( esc_html__( 'You are not allowed to install plugins.', 'wp-mail-smtp' ) );
		}

		// A license key is an md5 hash, so sanitize_key cannot mangle it.
		$key = ! empty( $_POST['key'] ) ? sanitize_key( wp_unslash( $_POST['key'] ) ) : '';

		if ( empty( $key ) ) {
			wp_send_json_error( esc_html__( 'Please enter your license key to connect.', 'wp-mail-smtp' ) );
		}

		if ( ! $this->is_valid_key_format( $key ) ) {
			wp_send_json_error( esc_html__( 'License key format is not valid.', 'wp-mail-smtp' ) );
		}

		if ( wp_mail_smtp()->is_pro() ) {
			wp_send_json_error( esc_html__( 'Only the Lite version can be upgraded.', 'wp-mail-smtp' ) );
		}

		$pro_installed = self::get_pro_installed_response( true );

		if ( ! empty( $pro_installed ) ) {
			wp_send_json_success( $pro_installed );
		}

		$url = self::generate_url( $key, '', $this->get_return_url() );

		if ( empty( $url ) ) {
			wp_send_json_error( esc_html__( 'There was an error while generating an upgrade URL. Please try again.', 'wp-mail-smtp' ) );
		}

		wp_send_json_success( [ 'url' => $url ] );
	}

	/**
	 * Where the upgrade service sends the browser back to once it is done.
	 *
	 * @since 4.10.0
	 *
	 * @return string A marked URL on this site.
	 */
	private function get_return_url() {

		// The value travels to the service as a query parameter, so an unvalidated one
		// would turn the upgrade page into an open redirect.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- ajax_generate_url() verifies the nonce before calling this.
		$requested = ! empty( $_POST['redirect'] ) ? esc_url_raw( wp_unslash( $_POST['redirect'] ) ) : '';
		$fallback  = wp_mail_smtp()->get_admin()->get_admin_page_url();

		$url = empty( $requested ) ? $fallback : wp_validate_redirect( $requested, $fallback );

		return add_query_arg( self::UPGRADED_QUERY_ARG, '1', $url );
	}

	/**
	 * Whether the key is a 32-char MD5 hex string.
	 *
	 * @since 4.10.0
	 *
	 * @param string $key License key.
	 *
	 * @return bool
	 */
	private function is_valid_key_format( $key ) {

		return (bool) preg_match( '/^[a-f0-9]{32}$/i', $key );
	}
}
