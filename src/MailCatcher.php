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
class MailCatcher extends \PHPMailer implements MailCatcherInterface {

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
	 * @throws \phpmailerException When sending via PhpMailer fails for some reason.
	 *
	 * @return bool
	 */
	public function send() {

		$options     = new Options();
		$mail_mailer = sanitize_key( $options->get( 'mail', 'mailer' ) );

		$is_emailing_blocked = false;

		if ( wp_mail_smtp()->is_blocked() ) {
			$is_emailing_blocked = true;
		}

		// Always allow a test email - check for the specific header.
		foreach ( (array) $this->getCustomHeaders() as $header ) {
			if (
				! empty( $header[0] ) &&
				! empty( $header[1] ) &&
				$header[0] === 'X-Mailer-Type' &&
				trim( $header[1] ) === 'WPMailSMTP/Admin/Test'
			) {
				$is_emailing_blocked = false;
			}
		};

		// Do not send emails if admin desired that.
		if ( $is_emailing_blocked ) {
			return false;
		}

		// Define a custom header, that will be used to identify the plugin and the mailer.
		$this->XMailer = 'WPMailSMTP/Mailer/' . $mail_mailer . ' ' . WPMS_PLUGIN_VER;

		// Use the default PHPMailer, as we inject our settings there for certain providers.
		if (
			$mail_mailer === 'mail' ||
			$mail_mailer === 'smtp' ||
			$mail_mailer === 'pepipost'
		) {
			try {
				// Allow to hook early to catch any early failed emails.
				do_action( 'wp_mail_smtp_mailcatcher_smtp_pre_send_before', $this );

				// Prepare all the headers.
				if ( ! $this->preSend() ) {
					return false;
				}

				// Allow to hook after all the preparation before the actual sending.
				do_action( 'wp_mail_smtp_mailcatcher_smtp_send_before', $this );

				return $this->postSend();
			} catch ( \phpmailerException $e ) {
				$this->mailHeader = '';
				$this->setError( $e->getMessage() );

				// Set the debug error, but not for default PHP mailer.
				if ( $mail_mailer !== 'mail' ) {
					Debug::set(
						'Mailer: ' . esc_html( wp_mail_smtp()->get_providers()->get_options( $mail_mailer )->get_title() ) . PHP_EOL .
						$e->getMessage()
					);
				}

				if ( $this->exceptions ) {
					throw $e;
				}

				return false;
			}
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

		$is_sent = $mailer->is_email_sent();

		// Allow to perform any actions with the data.
		do_action( 'wp_mail_smtp_mailcatcher_send_after', $mailer, $this );

		return $is_sent;
	}

	/**
	 * Returns all custom headers.
	 * In older versions of \PHPMailer class this method didn't exist.
	 * As we support WordPress 3.6+ - we need to make sure this method is always present.
	 *
	 * @since 1.5.0
	 *
	 * @return array
	 */
	public function getCustomHeaders() {

		return $this->CustomHeader;
	}

	/**
	 * Get the PHPMailer line ending.
	 *
	 * @since 2.2.0
	 *
	 * @return string
	 */
	public function get_line_ending() {

		return $this->LE; // phpcs:ignore
	}

	/**
	 * Create a unique ID to use for multipart email boundaries.
	 *
	 * @since 2.4.0
	 *
	 * @return string
	 */
	public function generate_id() {

		return $this->generateId();
	}
}
