<?php

namespace WPMailSMTP;

use Exception;
use WPMailSMTP\Admin\DebugEvents\DebugEvents;
use WPMailSMTP\Providers\MailerAbstract;

/**
 * Trait MailCatcherTrait.
 *
 * @since 3.7.0
 */
trait MailCatcherTrait {

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
	 * Whether the current email is a Setup Wizard test email.
	 *
	 * @since 3.5.0
	 *
	 * @var bool
	 */
	private $is_setup_wizard_test_email = false;

	/**
	 * Whether the current email is blocked to be sent.
	 *
	 * @since 3.8.0
	 *
	 * @var bool
	 */
	private $is_emailing_blocked = false;

	/**
	 * Holds the most recent error message.
	 *
	 * @since 3.7.0
	 *
	 * @var string
	 */
	protected $latest_error = '';

	/**
	 * Modify the default send() behaviour.
	 * For those mailers, that relies on PHPMailer class - call it directly.
	 * For others - init the correct provider and process it.
	 *
	 * @since 1.0.0
	 * @since 1.4.0 Process "Do Not Send" option, but always allow test email.
	 *
	 * @throws Exception When sending via PhpMailer fails for some reason.
	 *
	 * @return bool
	 */
	public function send() { // phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh

		// Reset email related variables.
		$this->debug_event_id             = false;
		$this->is_test_email              = false;
		$this->is_setup_wizard_test_email = false;
		$this->is_emailing_blocked        = false;
		$this->latest_error               = '';

		if ( wp_mail_smtp()->is_blocked() ) {
			$this->is_emailing_blocked = true;
		}

		// Always allow a test email - check for the specific header.
		foreach ( (array) $this->getCustomHeaders() as $header ) {
			if (
				! empty( $header[0] ) &&
				! empty( $header[1] ) &&
				$header[0] === 'X-Mailer-Type'
			) {
				if ( trim( $header[1] ) === 'WPMailSMTP/Admin/Test' ) {
					$this->is_emailing_blocked = false;
					$this->is_test_email       = true;
				} elseif ( trim( $header[1] ) === 'WPMailSMTP/Admin/SetupWizard/Test' ) {
					$this->is_setup_wizard_test_email = true;
				}
			}
		}

		// Do not send emails if admin desired that.
		if ( $this->is_emailing_blocked ) {
			return false;
		}

		// If it's not a test email,
		// check if the email should be enqueued
		// instead of being sent immediately.
		if ( ! $this->is_test_email && ! $this->is_setup_wizard_test_email ) {

			/**
			 * Filters whether an email should be enqueued or sent immediately.
			 *
			 * @since 4.0.0
			 *
			 * @param bool  $should_enqueue Whether to enqueue an email, or send it.
			 * @param array $wp_mail_args   Original arguments of the `wp_mail` call.
			 */
			$should_enqueue_email = apply_filters(
				'wp_mail_smtp_mail_catcher_send_enqueue_email',
				false,
				wp_mail_smtp()->get_processor()->get_filtered_wp_mail_args()
			);

			$queue = wp_mail_smtp()->get_queue();

			// If we should enqueue the email,
			// and the email has been enqueued,
			// bail.
			if ( $should_enqueue_email && $queue->enqueue_email() ) {
				return true;
			}
		}

		$connection  = wp_mail_smtp()->get_connections_manager()->get_mail_connection();
		$mailer_slug = $connection->get_mailer_slug();

		// Define a custom header, that will be used to identify the plugin and the mailer.
		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$this->XMailer = 'WPMailSMTP/Mailer/' . $mailer_slug . ' ' . WPMS_PLUGIN_VER;

		// Use the default PHPMailer, as we inject our settings there for certain providers.
		if (
			$mailer_slug === 'mail' ||
			$mailer_slug === 'smtp' ||
			$mailer_slug === 'pepipost'
		) {
			return $this->smtp_send( $connection );
		} else {
			return $this->api_send( $connection );
		}
	}

	/**
	 * Send email via SMTP.
	 *
	 * @since 4.0.0
	 *
	 * @param ConnectionInterface $connection The connection object.
	 *
	 * @throws Exception When sending via PhpMailer fails for some reason.
	 *
	 * @return bool
	 */
	private function smtp_send( $connection ) { // phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh

		$mailer_slug = $connection->get_mailer_slug();

		// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		try {
			if ( DebugEvents::is_debug_enabled() && ! $this->is_test_email ) {
				$this->SMTPDebug   = 3;
				$this->Debugoutput = [ $this, 'debug_output_callback' ];
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
				$this->throw_exception( $this->ErrorInfo );
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

			if ( ! $this->postSend() ) {
				$this->throw_exception( $this->ErrorInfo );
			}

			DebugEvents::add_debug(
				esc_html__( 'An email request was sent.', 'wp-mail-smtp' )
			);

			return true;
		} catch ( Exception $e ) {
			$this->mailHeader = '';

			// We need this to append SMTP error to the `PHPMailer::ErrorInfo` property.
			$this->setError( $e->getMessage() );

			// Set the debug error, but not for default PHP mailer.
			if ( $mailer_slug !== 'mail' ) {
				$error_message = 'Mailer: ' . esc_html( wp_mail_smtp()->get_providers()->get_options( $mailer_slug )->get_title() ) . "\r\n" . $this->ErrorInfo;

				$this->debug_event_id = Debug::set( $error_message );
				$this->latest_error   = $error_message;

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
			 * @param string               $mailer_slug   Current mailer name.
			 */
			do_action( 'wp_mail_smtp_mailcatcher_send_failed', $this->ErrorInfo, $this, $mailer_slug );

			if ( $this->exceptions ) {
				throw $e;
			}

			return false;
		} finally {

			// Clear debug output buffer.
			$this->debug_output_buffer = [];
		}
		// phpcs:enable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
	}

	/**
	 * Send email via API integration.
	 *
	 * @since 4.0.0
	 *
	 * @param ConnectionInterface $connection The connection object.
	 *
	 * @throws Exception When sending fails for some reason.
	 *
	 * @return bool
	 */
	private function api_send( $connection ) { // phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh

		$mailer_slug = $connection->get_mailer_slug();

		// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		try {
			// We need this so that the \PHPMailer class will correctly prepare all the headers.
			$this->Mailer = 'mail';

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
				$this->throw_exception( $this->ErrorInfo );
			}

			$mailer = wp_mail_smtp()->get_providers()->get_mailer( $mailer_slug, $this, $connection );

			if ( ! $mailer ) {
				$this->throw_exception( esc_html__( 'The selected mailer not found.', 'wp-mail-smtp' ) );
			}

			if ( ! $mailer->is_php_compatible() ) {
				$this->throw_exception( esc_html__( 'The selected mailer is not compatible with your PHP version.', 'wp-mail-smtp' ) );
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
			 * We reuse everything, that was preprocessed for usage in \PHPMailer.
			 */
			$mailer->send();

			$is_sent = $mailer->is_email_sent();

			/**
			 * Fires after email send.
			 *
			 * Allow to perform any actions with the data.
			 *
			 * @since 3.5.0
			 *
			 * @param MailerAbstract       $mailer      The Mailer object.
			 * @param MailCatcherInterface $mailcatcher The MailCatcher object.
			 */
			do_action( 'wp_mail_smtp_mailcatcher_send_after', $mailer, $this );

			if ( $is_sent !== true ) {
				$this->throw_exception( $mailer->get_response_error() );
			}

			// Clear debug messages if email is successfully sent.
			Debug::clear();

			return true;
		} catch ( Exception $e ) {
			// Add mailer to the beginning and save to display later.
			$message = 'Mailer: ' . esc_html( wp_mail_smtp()->get_providers()->get_options( $mailer_slug )->get_title() ) . "\r\n";

			$conflicts = new Conflicts();

			if ( $conflicts->is_detected() ) {
				$conflict_plugin_names = implode( ', ', $conflicts->get_all_conflict_names() );
				$message              .= 'Conflicts: ' . esc_html( $conflict_plugin_names ) . "\r\n";
			}

			$error_message        = $message . $e->getMessage();
			$this->debug_event_id = Debug::set( $error_message );
			$this->latest_error   = $error_message;

			/**
			 * Fires after email sent failure.
			 *
			 * @since 3.5.0
			 *
			 * @param string               $error_message Error message.
			 * @param MailCatcherInterface $mailcatcher   The MailCatcher object.
			 * @param string               $mailer_slug   Current mailer name.
			 */
			do_action( 'wp_mail_smtp_mailcatcher_send_failed', $e->getMessage(), $this, $mailer_slug );

			if ( $this->exceptions ) {
				throw $e;
			}

			return false;
		}
		// phpcs:enable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
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

	/**
	 * Whether the current email is blocked to be sent.
	 *
	 * @since 3.8.0
	 *
	 * @return bool
	 */
	public function is_emailing_blocked() {

		return $this->is_emailing_blocked;
	}

	/**
	 * Return the list of properties representing
	 * this class' state.
	 *
	 * @since 4.0.0
	 *
	 * @return array State of this class.
	 */
	private function get_state_properties() {

		return [
			'CharSet',
			'ContentType',
			'Encoding',
			'CustomHeader',
			'Subject',
			'Body',
			'AltBody',
			'ReplyTo',
			'to',
			'cc',
			'bcc',
			'attachment',
		];
	}

	/**
	 * Return an array of relevant properties.
	 *
	 * @since 4.0.0
	 *
	 * @return array State of this class.
	 */
	public function get_state() {

		$state = [];

		foreach ( $this->get_state_properties() as $property ) {
			$state[ $property ] = $this->{$property};
		}

		return $state;
	}

	/**
	 * Set properties from a provided array of data.
	 *
	 * @since 4.0.0
	 *
	 * @param array $state Array of properties to apply.
	 */
	public function set_state( $state ) { // phpcs:ignore Generic.Metrics.NestingLevel.MaxExceeded

		// Filter out non-allowed properties.
		$state = array_intersect_key(
			$state,
			array_flip( $this->get_state_properties() )
		);

		foreach ( $state as $property => $value ) {
			if ( $property !== 'attachment' ) {
				$this->{$property} = $value;
			} else {
				// Handle potential I/O exceptions
				// in PHPMailer when attaching files.
				$this->clearAttachments();

				foreach ( $state['attachment'] as $attachment ) {
					[ $path, , $name ] = $attachment;

					try {
						$this->addAttachment( $path, $name );
					} catch ( Exception $e ) {
						continue;
					}
				}
			}
		}
	}
}
