<?php

namespace WPMailSMTP\Providers\Mail;

use WPMailSMTP\Providers\MailerAbstract;

/**
 * Class Mailer inherits everything from parent abstract class.
 * This file is required for a proper work of Loader and \ReflectionClass.
 *
 * @package WPMailSMTP\Providers\Mail
 */
class Mailer extends MailerAbstract {

	/**
	 * @inheritdoc
	 */
	public function get_debug_info() {

		$mail_text = array();

		$mail_text[] = '<br><strong>Server:</strong>';

		$disabled_functions = ini_get( 'disable_functions' );
		$disabled           = (array) explode( ',', trim( $disabled_functions ) );

		$mail_text[] = '<strong>PHP.mail():</strong> ' . ( in_array( 'mail', $disabled, true ) || ! function_exists( 'mail' ) ? 'No' : 'Yes' );
		$mail_text[] = '<strong>Apache.mod_security:</strong> ' . ( in_array( 'mod_security', apache_get_modules(), true ) || in_array( 'mod_security2', apache_get_modules(), true ) ? 'Yes' : 'No' );

		return implode( '<br>', $mail_text );
	}
}
