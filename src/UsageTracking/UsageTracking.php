<?php

namespace WPMailSMTP\UsageTracking;

use WPMailSMTP\Conflicts;
use WPMailSMTP\Options;
use WPMailSMTP\WP;

/**
 * Usage Tracker functionality to understand what's going on on client's sites.
 *
 * @since 2.3.0
 */
class UsageTracking {

	/**
	 * The slug that will be used to save the option of Usage Tracker.
	 *
	 * @since 2.3.0
	 */
	const SETTINGS_SLUG = 'usage-tracking-enabled';

	/**
	 * Whether Usage Tracking is enabled.
	 * Needs to check with a fresh copy of options in order to provide accurate results.
	 *
	 * @since 2.3.0
	 *
	 * @return bool
	 */
	public function is_enabled() {

		return (bool) apply_filters(
			'wp_mail_smtp_usage_tracking_is_enabled',
			( new Options() )->get( 'general', self::SETTINGS_SLUG )
		);
	}

	/**
	 * Load usage tracking functionality.
	 *
	 * @since 2.3.0
	 */
	public function load() {

		// Check if loading the usage tracking functionality is allowed.
		if ( ! (bool) apply_filters( 'wp_mail_smtp_usage_tracking_load_allowed', true ) ) {
			return;
		}

		// Deregister the action if option is disabled.
		add_action(
			'wp_mail_smtp_options_set_after',
			function () {

				if ( ! $this->is_enabled() ) {
					( new SendUsageTask() )->cancel();
				}
			}
		);

		// Register the action handler only if enabled.
		if ( $this->is_enabled() ) {
			add_filter(
				'wp_mail_smtp_tasks_get_tasks',
				static function ( $tasks ) {
					$tasks[] = SendUsageTask::class;

					return $tasks;
				}
			);
		}
	}

	/**
	 * Get the User Agent string that will be sent to the API.
	 *
	 * @since 2.3.0
	 *
	 * @return string
	 */
	public function get_user_agent() {

		return 'WPMailSMTP/' . WPMS_PLUGIN_VER . '; ' . get_bloginfo( 'url' );
	}

	/**
	 * Get data for sending to the server.
	 *
	 * @since 2.3.0
	 *
	 * @return array
	 */
	public function get_data() {

		global $wpdb;

		$theme_data      = wp_get_theme();
		$activated_dates = get_option( 'wp_mail_smtp_activated', [] );
		$options         = Options::init();
		$mailer          = wp_mail_smtp()->get_providers()->get_mailer(
			$options->get( 'mail', 'mailer' ),
			wp_mail_smtp()->get_processor()->get_phpmailer()
		);

		$data = [
			// Generic data (environment).
			'url'                                => home_url(),
			'php_version'                        => PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION,
			'wp_version'                         => get_bloginfo( 'version' ),
			'mysql_version'                      => $wpdb->db_version(),
			'server_version'                     => isset( $_SERVER['SERVER_SOFTWARE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) ) : '',
			'is_ssl'                             => is_ssl(),
			'is_multisite'                       => is_multisite(),
			'sites_count'                        => $this->get_sites_total(),
			'active_plugins'                     => $this->get_active_plugins(),
			'theme_name'                         => $theme_data->name,
			'theme_version'                      => $theme_data->version,
			'locale'                             => get_locale(),
			'timezone_offset'                    => $this->get_timezone_offset(),
			// WP Mail SMTP - specific data.
			'wp_mail_smtp_version'               => WPMS_PLUGIN_VER,
			'wp_mail_smtp_license_key'           => wp_mail_smtp()->get_license_key(),
			'wp_mail_smtp_license_type'          => wp_mail_smtp()->get_license_type(),
			'wp_mail_smtp_is_pro'                => wp_mail_smtp()->is_pro(),
			'wp_mail_smtp_activated'             => get_option( 'wp_mail_smtp_activated_time', 0 ),
			'wp_mail_smtp_lite_installed_date'   => $this->get_installed( $activated_dates, 'lite' ),
			'wp_mail_smtp_pro_installed_date'    => $this->get_installed( $activated_dates, 'pro' ),
			'wp_mail_smtp_mailer'                => $options->get( 'mail', 'mailer' ),
			'wp_mail_smtp_from_email_force'      => (bool) $options->get( 'mail', 'from_email_force' ),
			'wp_mail_smtp_from_name_force'       => (bool) $options->get( 'mail', 'from_name_force' ),
			'wp_mail_smtp_return_path'           => (bool) $options->get( 'mail', 'return_path' ),
			'wp_mail_smtp_do_not_send'           => (bool) $options->get( 'general', 'do_not_send' ),
			'wp_mail_smtp_is_white_labeled'      => wp_mail_smtp()->is_white_labeled(),
			'wp_mail_smtp_is_const_enabled'      => (bool) $options->is_const_enabled(),
			'wp_mail_smtp_conflicts_is_detected' => ( new Conflicts() )->is_detected(),
			'wp_mail_smtp_is_mailer_complete'    => empty( $mailer ) ? false : $mailer->is_mailer_complete(),
		];

		if ( 'smtp' === $options->get( 'mail', 'mailer' ) ) {
			$data['wp_mail_smtp_other_smtp_host']       = $options->get( 'smtp', 'host' );
			$data['wp_mail_smtp_other_smtp_encryption'] = $options->get( 'smtp', 'encryption' );
			$data['wp_mail_smtp_other_smtp_port']       = $options->get( 'smtp', 'port' );
			$data['wp_mail_smtp_other_smtp_auth']       = (bool) $options->get( 'smtp', 'auth' );
			$data['wp_mail_smtp_other_smtp_autotls']    = (bool) $options->get( 'smtp', 'autotls' );
		}

		if ( is_multisite() ) {
			$data['wp_mail_smtp_multisite_network_wide'] = WP::use_global_plugin_settings();
		}

		return apply_filters( 'wp_mail_smtp_usage_tracking_get_data', $data );
	}

	/**
	 * Get timezone offset.
	 * We use `wp_timezone_string()` when it's available (WP 5.3+),
	 * otherwise fallback to the same code, copy-pasted.
	 *
	 * @since 2.3.0
	 *
	 * @return string
	 */
	private function get_timezone_offset() {

		// It was added in WordPress 5.3.
		if ( function_exists( 'wp_timezone_string' ) ) {
			return wp_timezone_string();
		}

		/*
		 * The code below is basically a copy-paste from that function.
		 */

		$timezone_string = get_option( 'timezone_string' );

		if ( $timezone_string ) {
			return $timezone_string;
		}

		$offset  = (float) get_option( 'gmt_offset' );
		$hours   = (int) $offset;
		$minutes = ( $offset - $hours );

		$sign     = ( $offset < 0 ) ? '-' : '+';
		$abs_hour = abs( $hours );
		$abs_mins = abs( $minutes * 60 );

		return sprintf( '%s%02d:%02d', $sign, $abs_hour, $abs_mins );
	}

	/**
	 * Get the list of active plugins.
	 *
	 * @since 2.3.0
	 *
	 * @return array
	 */
	private function get_active_plugins() {

		if ( ! function_exists( 'get_plugins' ) ) {
			include ABSPATH . '/wp-admin/includes/plugin.php';
		}

		$active_plugins = [];

		foreach ( get_mu_plugins() as $path => $plugin ) {
			$active_plugins[ $path ] = isset( $plugin['Version'] ) ? $plugin['Version'] : 'Not Set';
		}

		foreach ( get_plugins() as $path => $plugin ) {
			if ( is_plugin_active( $path ) ) {
				$active_plugins[ $path ] = isset( $plugin['Version'] ) ? $plugin['Version'] : 'Not Set';
			}
		}

		return $active_plugins;
	}

	/**
	 * Installed date.
	 *
	 * @since 2.3.0
	 *
	 * @param array  $activated_dates Input array with dates.
	 * @param string $key             Input key what you want to get.
	 *
	 * @return mixed
	 */
	private function get_installed( $activated_dates, $key ) {

		if ( ! empty( $activated_dates[ $key ] ) ) {
			return $activated_dates[ $key ];
		}

		return false;
	}

	/**
	 * Total number of sites.
	 *
	 * @since 2.3.0
	 *
	 * @return int
	 */
	private function get_sites_total() {

		return function_exists( 'get_blog_count' ) ? (int) get_blog_count() : 1;
	}
}
