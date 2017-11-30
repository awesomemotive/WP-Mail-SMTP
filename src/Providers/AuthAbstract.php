<?php

namespace WPMailSMTP\Providers;

/**
 * Class AuthAbstract.
 *
 * @since 1.0.0
 */
abstract class AuthAbstract implements AuthInterface {

	/**
	 * Get the url, that users will be redirected back to finish the OAuth process.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public static function get_plugin_auth_url() {
		return add_query_arg( 'tab', 'auth', wp_mail_smtp()->get_admin()->get_admin_page_url() );
	}
}
