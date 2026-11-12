<?php

namespace WPMailSMTP\Deprecated;

/**
 * Deprecated WPMailSMTP\Connect methods, kept for backward compatibility.
 *
 * @since 4.10.4
 */
trait Connect {

	/**
	 * AJAX callback to process WP Mail SMTP Connect.
	 *
	 * @since      2.6.0
	 * @deprecated {VERSION}
	 *
	 * @return void
	 */
	public function process() {

		_deprecated_function( __METHOD__, '4.10.4' );

		wp_send_json_error( esc_html__( 'Please download WP Mail SMTP Pro from your account and install it on the Plugins screen.', 'wp-mail-smtp' ) );
	}

	/**
	 * Enqueue the connect JS file.
	 *
	 * @since      2.6.0
	 * @deprecated 4.10.0
	 *
	 * @return void
	 */
	public function enqueue_scripts() {

		_deprecated_function( __METHOD__, '4.10.0' );
	}
}
