<?php
/**
 * Polyfills for functions missing on the plugin's supported-but-older WordPress and PHP versions.
 *
 * Must stay in the global namespace so the backfilled functions are reachable by the same
 * unqualified names their callers use. Required from wp-mail-smtp.php before the plugin boots.
 *
 * @since 4.9.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! function_exists( 'wp_is_serving_rest_request' ) ) {
	/**
	 * Backfill of WP 6.5's wp_is_serving_rest_request() for older WordPress.
	 *
	 * Action Scheduler 4.0+ calls this function unconditionally.
	 *
	 * @since 4.9.0
	 *
	 * @return bool
	 */
	function wp_is_serving_rest_request() {

		return defined( 'REST_REQUEST' ) && REST_REQUEST;
	}
}
