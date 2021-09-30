<?php

namespace WPMailSMTP\Helpers;

use WPMailSMTP\Options;

/**
 * Class with all the misc helper functions that don't belong elsewhere.
 *
 * @since 3.0.0
 */
class Helpers {

	/**
	 * Check if the current active mailer has email send confirmation functionality.
	 *
	 * @since 3.0.0
	 *
	 * @return bool
	 */
	public static function mailer_without_send_confirmation() {

		return ! in_array(
			Options::init()->get( 'mail', 'mailer' ),
			[
				'smtpcom',
				'sendinblue',
				'mailgun',
			],
			true
		);
	}

	/**
	 * Include mbstring polyfill.
	 *
	 * @since 3.1.0
	 */
	public static function include_mbstring_polyfill() {

		require_once wp_mail_smtp()->plugin_path . '/vendor_prefixed/symfony/polyfill-mbstring/Mbstring.php';
		require_once wp_mail_smtp()->plugin_path . '/vendor_prefixed/symfony/polyfill-mbstring/bootstrap.php';
	}
}
