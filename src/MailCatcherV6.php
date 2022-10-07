<?php

namespace WPMailSMTP;

use WPMailSMTP\Admin\DebugEvents\DebugEvents;
use WPMailSMTP\Providers\MailerAbstract;

/**
 * Class MailCatcher replaces the \PHPMailer\PHPMailer\PHPMailer introduced in WP 5.5 and
 * modifies the email sending logic. Thus, we can use other mailers API to do what we need, or stop emails completely.
 *
 * @since 2.2.0
 */
class MailCatcherV6 extends \PHPMailer\PHPMailer\PHPMailer implements MailCatcherInterface {

	/**
	 * Callback Action function name.
	 *
	 * The function that handles the result of the send email action.
	 * It is called out by send() for each email sent.
	 *
	 * @since 2.2.0
	 *
	 * @var string
	 */
	public $action_function = '\WPMailSMTP\Processor::send_callback';

	/**
	 * Debug output buffer.
	 *
	 * @since 3.3.0
	 *
	 * @var array
	 */
	private $debug_output_buffer = [];

	/**
	 * Debug event ID.
	 *
	 * @since 3.5.0
	 *
	 * @var int
	 */
	private $debug_event_id = false;

	/**
	 * Whether the current email is a test email.
	 *
	 * @since 3.5.0
	 *
	 * @var bool
	 */
	private $is_test_email = false;

	/**
	 * Which validator to use by default when validating email addresses.
	 * We are using built-in WordPress function `is_email` to validate the email address.
	 *
	 * @see PHPMailer::validateAddress()
	 *
	 * @since 3.6.0
	 *
	 * @var string|callable
	 */
	public static $validator = [ Processor::class, 'is_email_callback' ];

	/**
	 * Whether the current email is a Setup Wizard test email.
	 *
	 * @since 3.5.0
	 *
	 * @var bool
	 */
	private $is_setup_wizard_test_email = false;

	/**
	 * Modify the default send() behaviour.
	 * For those mailers, that relies on PHPMailer class - call it directly.
	 * For others - init the correct provider and process it.
	 *
	 * @since 2.2.0
	 *
	 * @throws \PHPMailer\PHPMailer\Exception When sending via PhpMailer fails for some reason.
	 *
	 * @return bool
	 */
	public function send() { // phpcs:ignore Generic.Metrics.CyclomaticComplexity.MaxExceeded

		$options     = Options::init();
		$mail_mailer = sanitize_key( $options->get( 'mail', 'mailer' ) );

		// Reset email related variables.
		$this->debug_event_id             = false;
		$this->is_test_email              = false;
		$this->is_setup_wizard_test_email = false;

		$is_emailing_blocked = false;

		if ( wp_mail_smtp()->is_blocked() ) {
			$is_emailing_blocked = true;
		}

		// Always allow a test email - check for the specific header.
		foreach ( (array) $this->getCustomHeaders() as $header ) {
			if (
				! empty( $header[0] ) &&
				! empty( $header[1] ) &&
				$header[0] === 'X-Mailer-Type'
			) {
				if ( trim( $header[1] ) === 'WPMailSMTP/Admin/Test' ) {
					$is_emailing_blocked = false;
					$this->is_test_email = true;
				} elseif ( trim( $header[1] ) === 'WPMailSMTP/Admin/SetupWizard/Test' ) {
					$this->is_setup_wizard_test_email = true;
				}
			}
		}

		// Do not send emails if admin desired that.
		if ( $is_emailing_blocked ) {
			return false;
		}

		// Define a custom header, that will be used to identify the plugin and the mailer.
		$this->XMailer = 'WPMailSMTP/Mailer/' . $mail_mailer . ' ' . WPMS_PLUGIN_VER; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

		// Use the default PHPMailer, as we inject our settings there for certain providers.
		if (
			$mail_mailer === 'mail' ||
			$mail_mailer === 'smtp' ||
			$mail_mailer === 'pepipost'
		) {
			try {
				if ( DebugEvents::is_debug_enabled() && ! $this->is_test_email ) {
					$this->SMTPDebug   = 3; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
					$this->Debugoutput = [ $this, 'debug_output_callback' ]; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				}

				/**
				 * Fires before email pre send via SMTP.
				 *
				 * Allow to hook early to catch any early failed emails.
				 *
				 * @since 2.9.0
				 *
				 * @param MailCatcherInterface $mailcatcher The MailCatcher object.
				 */
				do_action( 'wp_mail_smtp_mailcatcher_smtp_pre_send_before', $this );

				// Prepare all the headers.
				if ( ! $this->preSend() ) {
					return false;
				}

				/**
				 * Fires before email send via SMTP.
				 *
				 * Allow to hook after all the preparation before the actual sending.
				 *
				 * @since 2.9.0
				 *
				 * @param MailCatcherInterface $mailcatcher The MailCatcher object.
				 */
				do_action( 'wp_mail_smtp_mailcatcher_smtp_send_before', $this );

				$post_send = $this->postSend();

				DebugEvents::add_debug(
					esc_html__( 'An email request was sent.', 'wp-mail-smtp' )
				);

				return $post_send;
			} catch ( \PHPMailer\PHPMailer\Exception $e ) {

				// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				$this->mailHeader = '';

				$this->setError( $e->getMessage() );

				// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				$error_message = 'Mailer: ' . esc_html( wp_mail_smtp()->get_providers()->get_options( $mail_mailer )->get_title() ) . "\r\n" . $this->ErrorInfo;

				// Set the debug error, but not for default PHP mailer.
				if ( $mail_mailer !== 'mail' ) {
					$this->debug_event_id = Debug::set( $error_message );

					if ( DebugEvents::is_debug_enabled() && ! empty( $this->debug_output_buffer ) ) {
						$debug_message  = $error_message . "\r\n" . esc_html__( 'Debug Output:', 'wp-mail-smtp' ) . "\r\n";
						$debug_message .= implode( "\r\n", $this->debug_output_buffer );

						$this->debug_event_id = DebugEvents::add_debug( $debug_message );
					}
				}

				/**
				 * Fires after email sent failure via SMTP.
				 *
				 * @since 3.5.0
				 *
				 * @param string               $error_message Error message.
				 * @param MailCatcherInterface $mailcatcher   The MailCatcher object.
				 * @param string               $mail_mailer   Current mailer name.
				 */
				do_action( 'wp_mail_smtp_mailcatcher_send_failed', $error_message, $this, $mail_mailer );

				if ( $this->exceptions ) {
					throw $e;
				}

				return false;
			} finally {

				// Clear debug output buffer.
				$this->debug_output_buffer = [];
			}
		}

		// We need this so that the PHPMailer class will correctly prepare all the headers.
		$this->Mailer = 'mail'; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

		/**
		 * Fires before email pre send.
		 *
		 * Allow to hook early to catch any early failed emails.
		 *
		 * @since 2.9.0
		 *
		 * @param MailCatcherInterface $mailcatcher The MailCatcher object.
		 */
		do_action( 'wp_mail_smtp_mailcatcher_pre_send_before', $this );

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

		/**
		 * Fires before email send.
		 *
		 * Allows to hook after all the preparation before the actual sending.
		 *
		 * @since 3.3.0
		 *
		 * @param MailerAbstract $mailer The Mailer object.
		 */
		do_action( 'wp_mail_smtp_mailcatcher_send_before', $mailer );

		/*
		 * Send the actual email.
		 * We reuse everything, that was preprocessed for usage in PHPMailer.
		 */
		$mailer->send();

		$is_sent = $mailer->is_email_sent();

		if ( ! $is_sent ) {
			$error = $mailer->get_response_error();

			if ( ! empty( $error ) ) {

				// Add mailer to the beginning and save to display later.
				$message = 'Mailer: ' . esc_html( wp_mail_smtp()->get_providers()->get_options( $mailer->get_mailer_name() )->get_title() ) . "\r\n";

				$conflicts = new Conflicts();

				if ( $conflicts->is_detected() ) {
					$conflict_plugin_names = implode( ', ', $conflicts->get_all_conflict_names() );

					$message .= 'Conflicts: ' . esc_html( $conflict_plugin_names ) . "\r\n";
				}

				$error_message = $message . $error;

				$this->debug_event_id = Debug::set( $error_message );

				/**
				 * Fires after email sent failure.
				 *
				 * @since 3.5.0
				 *
				 * @param string               $error_message Error message.
				 * @param MailCatcherInterface $mailcatcher   The MailCatcher object.
				 * @param string               $mail_mailer   Current mailer name.
				 */
				do_action( 'wp_mail_smtp_mailcatcher_send_failed', $error_message, $this, $mail_mailer );
			}
		} else {

			// Clear debug messages if email is successfully sent.
			Debug::clear();
		}

		/**
		 * Fires after email send.
		 *
		 * Allow to perform any actions with the data.
		 *
		 * @since  {VERSION}
		 *
		 * @param MailerAbstract       $mailer      The Mailer object.
		 * @param MailCatcherInterface $mailcatcher The MailCatcher object.
		 */
		do_action( 'wp_mail_smtp_mailcatcher_send_after', $mailer, $this );

		return $is_sent;
	}

	/**
	 * Get the PHPMailer line ending.
	 *
	 * @since 2.2.0
	 *
	 * @return string
	 */
	public function get_line_ending() {

		return static::$LE; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
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

	/**
	 * Debug output callback.
	 * Save debugging info to buffer array.
	 *
	 * @since 3.3.0
	 *
	 * @param string $str   Message.
	 * @param int    $level Debug level.
	 */
	public function debug_output_callback( $str, $level ) {

		/*
		 * Filter out all higher levels than 3.
		 * SMTPDebug level 3 is commands, data and connection status.
		 */
		if ( $level > 3 ) {
			return;
		}

		$this->debug_output_buffer[] = trim( $str, "\r\n" );
	}

	/**
	 * Get debug event ID.
	 *
	 * @since 3.5.0
	 *
	 * @return bool|int
	 */
	public function get_debug_event_id() {

		return $this->debug_event_id;
	}

	/**
	 * Whether the current email is a test email.
	 *
	 * @since 3.5.0
	 *
	 * @return bool
	 */
	public function is_test_email() {

		return $this->is_test_email;
	}

	/**
	 * Whether the current email is a Setup Wizard test email.
	 *
	 * @since 3.5.0
	 *
	 * @return bool
	 */
	public function is_setup_wizard_test_email() {

		return $this->is_setup_wizard_test_email;
	}
}
