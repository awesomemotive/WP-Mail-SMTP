<?php

namespace WPMailSMTP\Deprecated;

/**
 * Deprecated WPMailSMTP\WP methods, kept so existing calls do not fail.
 *
 * @since 4.10.5
 */
trait WP {

	/**
	 * Whether the plugin is administered for the whole network instead of per site.
	 *
	 * @since      2.2.0
	 * @deprecated 4.10.5
	 *
	 * @return bool
	 */
	public static function use_global_plugin_settings() {

		_deprecated_function( 'WPMailSMTP\\WP::use_global_plugin_settings', '4.10.5' );

		/**
		 * Filters whether the plugin is administered for the whole network instead of per site.
		 *
		 * @since 4.10.5
		 *
		 * @param bool $use_global_plugin_settings Whether the plugin is administered network-wide.
		 */
		return (bool) apply_filters( 'wp_mail_smtp_wp_use_global_plugin_settings', false );
	}
}
