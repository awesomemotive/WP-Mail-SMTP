<?php

namespace WPMailSMTP;

// Load PHPMailer class, so we can subclass it.
if ( ! class_exists( 'PHPMailer', false ) ) {
	require_once ABSPATH . WPINC . '/class-phpmailer.php';
}

/**
 * Class MailCatcher replaces the \PHPMailer and modifies the email sending logic.
 * Thus, we can use other mailers API to do what we need, or stop emails completely.
 *
 * @since 1.0.0
 */
class MailCatcher extends \PHPMailer {

	/**
	 * Callback Action function name.
	 *
	 * The function that handles the result of the send email action.
	 * It is called out by send() for each email sent.
	 *
	 * @since 1.3.0
	 *
	 * @var string
	 */
	public $action_function = '\WPMailSMTP\Processor::send_callback';

	/**
	 * Modify the default send() behaviour.
	 * For those mailers, that relies on PHPMailer class - call it directly.
	 * For others - init the correct provider and process it.
	 *
	 * @since 1.0.0
	 * @since 1.4.0 Process "Do Not Send" option, but always allow test email.
	 *
	 * @throws \phpmailerException Throws when sending via PhpMailer fails for some reason.
	 *
	 * @return bool
	 */
	public function send() {

		$options = new Options();

		$is_emailing_blocked = false;

		if ( $options->get( 'general', 'do_not_send' ) ) {
			$is_emailing_blocked = true;
		}

		// Always allow a test email - check for the specific header.
		foreach ( (array) $this->getCustomHeaders() as $header ) {
			if (
				! empty( $header[0] ) &&
				! empty( $header[1] ) &&
				$header[0] === 'X-Mailer-Type' &&
				trim( $header[1] ) === 'WPMailSMTP\Admin\Test'
			) {
				$is_emailing_blocked = false;
			}
		};

		// Do not send emails if admin desired that.
		if ( $is_emailing_blocked ) {
			return false;
		}

		$mail_mailer = $options->get( 'mail', 'mailer' );

		// Define a custom header, that will be used in Gmail/SMTP mailers.
		$this->XMailer = 'WPMailSMTP/Mailer/' . sanitize_key( $mail_mailer ) . ' ' . WPMS_PLUGIN_VER;

		// Use the default PHPMailer, as we inject our settings there for certain providers.
		if (
			$mail_mailer === 'mail' ||
			$mail_mailer === 'smtp' ||
			$mail_mailer === 'pepipost'
		) {
			return parent::send();
		}

		// We need this so that the \PHPMailer class will correctly prepare all the headers.
		$this->Mailer = 'mail';

		// Prepare everything (including the message) for sending.
		if ( ! $this->preSend() ) {
			return false;
		}

		$mailer = wp_mail_smtp()->get_providers()->get_mailer( $mail_mailer, $this );

		if ( ! $mailer ) {
			return false;
		}

		if ( ! $mailer->is_php_compatible() ) {
			return false;
		}

		/*
		 * Send the actual email.
		 * We reuse everything, that was preprocessed for usage in \PHPMailer.
		 */
		$mailer->send();

		return $mailer->is_email_sent();
	}
}
