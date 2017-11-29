<?php

namespace WPMailSMTP\Providers;

/**
 * Class AuthAbstract
 *
 * @package WPMailSMTP\Providers
 */
abstract class AuthAbstract implements AuthInterface {

	/**
	 * @return string
	 */
	public static function get_plugin_auth_url() {
		return add_query_arg( 'tab', 'auth', wp_mail_smtp()->get_admin()->get_admin_page_url() );
	}
}
