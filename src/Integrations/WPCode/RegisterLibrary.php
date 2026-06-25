<?php

namespace WPMailSMTP\Integrations\WPCode;

/**
 * Register the WP Mail SMTP username on the WPCode snippet library so the
 * plugin's snippets load inside an active WPCode install.
 *
 * @since 4.9.0
 */
class RegisterLibrary {

	/**
	 * Register hooks.
	 *
	 * @since 4.9.0
	 */
	public function hooks() {

		add_action( 'plugins_loaded', [ $this, 'register_wpcode_username' ], 20 );
	}

	/**
	 * The WPCode library username for this plugin.
	 *
	 * @since 4.9.0
	 *
	 * @return string
	 */
	public function get_username() {

		return 'wpmailsmtp';
	}

	/**
	 * Register the library username with WPCode, when available.
	 *
	 * @since 4.9.0
	 */
	public function register_wpcode_username() {

		if ( ! function_exists( 'wpcode_register_library_username' ) ) {
			return;
		}

		wpcode_register_library_username( $this->get_username(), 'WP Mail SMTP' );
	}
}
