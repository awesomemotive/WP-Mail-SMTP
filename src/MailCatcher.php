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
 * @package WPMailSMTP
 */
class MailCatcher extends \PHPMailer {

	/**
	 * Modify the default send() behaviour.
	 * For those mailers, that relies on PHPMailer class - call it directly.
	 * For others - init the correct provider and process it.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function send() {
		/*
		 * TODO: attachments - $this->getAttachments()
		 * TODO: Content type.
		 * TODO: CharSet.
		 */
		$options = new Options();

		// Use the default PHPMailer, as we inject our settings there for certain providers.
		if (
			$options->get( 'mail', 'mailer' ) === 'mail' ||
			$options->get( 'mail', 'mailer' ) === 'smtp' ||
			$options->get( 'mail', 'mailer' ) === 'pepipost'
		) {
			return parent::send();
		}

		// Prepare everything (including the message) for sending.
		if ( ! $this->preSend() ) {
			return false;
		}

		$mailer = $this->get_provider( $options->get( 'mail', 'mailer' ) );

		if ( ! $mailer ) {
			return false;
		}

		/*
		 * Send the actual email.
		 * We reuse everything, that was preprocessed for usage in \PHPMailer.
		 */
		$mailer->send();

		return $mailer->is_email_sent();
	}

	/**
	 * Get the mailer class instance based on the Mailer setting.
	 *
	 * @param string $mailer
	 *
	 * @return null|\WPMailSMTP\Providers\MailerAbstract
	 */
	protected function get_provider( $mailer ) {

		$default = array(
			'sendgrid' => 'WPMailSMTP\Providers\Sendgrid\Mailer',
			'mailgun'  => 'WPMailSMTP\Providers\Mailgun\Mailer',
		);

		// Allow to modify the list of providers.
		$providers = apply_filters( 'wp_mail_smtp_mailcatcher_get_default_providers', $default );

		if ( isset( $providers[ $mailer ] ) ) {
			// Pass the \PHPMailer instance to the provider __construct().
			return new $providers[ $mailer ]( $this );
		}

		return null;
	}
}
