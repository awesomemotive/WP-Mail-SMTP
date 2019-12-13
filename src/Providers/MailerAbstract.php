<?php

namespace WPMailSMTP\Providers;

use WPMailSMTP\Conflicts;
use WPMailSMTP\Debug;
use WPMailSMTP\MailCatcher;
use WPMailSMTP\Options;
use WPMailSMTP\WP;

/**
 * Class MailerAbstract.
 *
 * @since 1.0.0
 */
abstract class MailerAbstract implements MailerInterface {

	/**
	 * Which response code from HTTP provider is considered to be successful?
	 *
	 * @since 1.0.0
	 *
	 * @var int
	 */
	protected $email_sent_code = 200;
	/**
	 * @since 1.0.0
	 *
	 * @var Options
	 */
	protected $options;
	/**
	 * @since 1.0.0
	 *
	 * @var MailCatcher
	 */
	protected $phpmailer;
	/**
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $mailer = '';

	/**
	 * URL to make an API request to.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $url = '';
	/**
	 * @since 1.0.0
	 *
	 * @var array
	 */
	protected $headers = array();
	/**
	 * @since 1.0.0
	 *
	 * @var array
	 */
	protected $body = array();
	/**
	 * @since 1.0.0
	 *
	 * @var mixed
	 */
	protected $response = array();

	/**
	 * Mailer constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param MailCatcher $phpmailer
	 */
	public function __construct( MailCatcher $phpmailer ) {

		$this->options = new Options();
		$this->mailer  = $this->options->get( 'mail', 'mailer' );

		// Only non-SMTP mailers need URL and extra processing for PHPMailer class.
		if ( ! $this->options->is_mailer_smtp() && empty( $this->url ) ) {
			return;
		}

		$this->process_phpmailer( $phpmailer );
	}

	/**
	 * Re-use the MailCatcher class methods and properties.
	 *
	 * @since 1.0.0
	 *
	 * @param MailCatcher $phpmailer
	 */
	public function process_phpmailer( $phpmailer ) {

		// Make sure that we have access to MailCatcher class methods.
		if (
			! $phpmailer instanceof MailCatcher &&
			! $phpmailer instanceof \PHPMailer
		) {
			return;
		}

		$this->phpmailer = $phpmailer;

		// Prevent working with those methods, as they are not needed for SMTP-like mailers.
		if ( $this->options->is_mailer_smtp() ) {
			return;
		}

		$this->set_headers( $this->phpmailer->getCustomHeaders() );
		$this->set_from( $this->phpmailer->From, $this->phpmailer->FromName );
		$this->set_recipients(
			array(
				'to'  => $this->phpmailer->getToAddresses(),
				'cc'  => $this->phpmailer->getCcAddresses(),
				'bcc' => $this->phpmailer->getBccAddresses(),
			)
		);
		$this->set_subject( $this->phpmailer->Subject );
		if ( $this->phpmailer->ContentType === 'text/plain' ) {
			$this->set_content( $this->phpmailer->Body );
		} else {
			$this->set_content(
				array(
					'text' => $this->phpmailer->AltBody,
					'html' => $this->phpmailer->Body,
				)
			);
		}
		$this->set_return_path( $this->phpmailer->From );
		$this->set_reply_to( $this->phpmailer->getReplyToAddresses() );

		/*
		 * In some cases we will need to modify the internal structure
		 * of the body content, if attachments are present.
		 * So lets make this call the last one.
		 */
		$this->set_attachments( $this->phpmailer->getAttachments() );
	}

	/**
	 * Set the email headers.
	 *
	 * @since 1.0.0
	 *
	 * @param array $headers List of key=>value pairs.
	 */
	public function set_headers( $headers ) {

		foreach ( $headers as $header ) {
			$name  = isset( $header[0] ) ? $header[0] : false;
			$value = isset( $header[1] ) ? $header[1] : false;

			if ( empty( $name ) || empty( $value ) ) {
				continue;
			}

			$this->set_header( $name, $value );
		}
	}

	/**
	 * Set individual header key=>value pair for the email.
	 *
	 * @since 1.0.0
	 *
	 * @param string $name
	 * @param string $value
	 */
	public function set_header( $name, $value ) {

		$name = sanitize_text_field( $name );

		$this->headers[ $name ] = WP::sanitize_value( $value );
	}

	/**
	 * Set email subject.
	 *
	 * @since 1.0.0
	 *
	 * @param string $subject
	 */
	public function set_subject( $subject ) {

		$this->set_body_param(
			array(
				'subject' => $subject,
			)
		);
	}

	/**
	 * Set the request params, that goes to the body of the HTTP request.
	 *
	 * @since 1.0.0
	 *
	 * @param array $param Key=>value of what should be sent to a 3rd party API.
	 *
	 * @internal param array $params
	 */
	protected function set_body_param( $param ) {

		$this->body = Options::array_merge_recursive( $this->body, $param );
	}

	/**
	 * Get the email body.
	 *
	 * @since 1.0.0
	 *
	 * @return string|array
	 */
	public function get_body() {

		return apply_filters( 'wp_mail_smtp_providers_mailer_get_body', $this->body, $this->mailer );
	}

	/**
	 * Get the email headers.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_headers() {

		return apply_filters( 'wp_mail_smtp_providers_mailer_get_headers', $this->headers, $this->mailer );
	}

	/**
	 * Send the email.
	 *
	 * @since 1.0.0
	 * @since 1.8.0 Added timeout for requests, same as max_execution_time.
	 */
	public function send() {

		$timeout = (int) ini_get( 'max_execution_time' );

		$params = Options::array_merge_recursive(
			$this->get_default_params(),
			array(
				'headers' => $this->get_headers(),
				'body'    => $this->get_body(),
				'timeout' => $timeout ? $timeout : 30,
			)
		);

		$response = wp_safe_remote_post( $this->url, $params );

		$this->process_response( $response );
	}

	/**
	 * We might need to do something after the email was sent to the API.
	 * In this method we preprocess the response from the API.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $response
	 */
	protected function process_response( $response ) {

		if ( is_wp_error( $response ) ) {
			// Save the error text.
			$errors = $response->get_error_messages();
			foreach ( $errors as $error ) {
				Debug::set( $error );
			}

			return;
		}

		if ( isset( $response['body'] ) && WP::is_json( $response['body'] ) ) {
			$response['body'] = \json_decode( $response['body'] );
		}

		$this->response = $response;
	}

	/**
	 * Get the default params, required for wp_safe_remote_post().
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	protected function get_default_params() {

		return apply_filters(
			'wp_mail_smtp_providers_mailer_get_default_params',
			array(
				'timeout'     => 15,
				'httpversion' => '1.1',
				'blocking'    => true,
			),
			$this->mailer
		);
	}

	/**
	 * Whether the email is sent or not.
	 * We basically check the response code from a request to provider.
	 * Might not be 100% correct, not guarantees that email is delivered.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function is_email_sent() {

		$is_sent = false;

		if ( wp_remote_retrieve_response_code( $this->response ) === $this->email_sent_code ) {
			$is_sent = true;
		} else {
			$error = $this->get_response_error();

			if ( ! empty( $error ) ) {
				// Add mailer to the beginning and save to display later.
				$message = 'Mailer: ' . esc_html( wp_mail_smtp()->get_providers()->get_options( $this->mailer )->get_title() ) . "\r\n";

				$conflicts = new Conflicts();
				if ( $conflicts->is_detected() ) {
					$message .= 'Conflicts: ' . esc_html( $conflicts->get_conflict_name() ) . "\r\n";
				}

				Debug::set( $message . $error );
			}
		}

		// Clear debug messages if email is successfully sent.
		if ( $is_sent ) {
			Debug::clear();
		}

		return apply_filters( 'wp_mail_smtp_providers_mailer_is_email_sent', $is_sent, $this->mailer );
	}

	/**
	 * Should be overwritten when appropriate.
	 *
	 * @since 1.2.0
	 *
	 * @return string
	 */
	protected function get_response_error() {

		return '';
	}

	/**
	 * Whether the mailer supports the current PHP version or not.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function is_php_compatible() {

		$options = wp_mail_smtp()->get_providers()->get_options( $this->mailer );

		return version_compare( phpversion(), $options->get_php_version(), '>=' );
	}

	/**
	 * This method is relevant to SMTP and Pepipost.
	 * All other custom mailers should override it with own information.
	 *
	 * @since 1.2.0
	 *
	 * @return string
	 */
	public function get_debug_info() {

		global $phpmailer;

		$smtp_text = array();

		// Mail mailer has nothing to return.
		if ( $this->options->is_mailer_smtp() ) {
			// phpcs:disable
			$smtp_text[] = '<strong>ErrorInfo:</strong> ' . make_clickable( wp_strip_all_tags( $phpmailer->ErrorInfo ) );
			$smtp_text[] = '<strong>Host:</strong> ' . $phpmailer->Host;
			$smtp_text[] = '<strong>Port:</strong> ' . $phpmailer->Port;
			$smtp_text[] = '<strong>SMTPSecure:</strong> ' . Debug::pvar( $phpmailer->SMTPSecure );
			$smtp_text[] = '<strong>SMTPAutoTLS:</strong> ' . Debug::pvar( $phpmailer->SMTPAutoTLS );
			$smtp_text[] = '<strong>SMTPAuth:</strong> ' . Debug::pvar( $phpmailer->SMTPAuth );
			if ( ! empty( $phpmailer->SMTPOptions ) ) {
				$smtp_text[] = '<strong>SMTPOptions:</strong> <code>' . wp_json_encode( $phpmailer->SMTPOptions ) . '</code>';
			}
			// phpcs:enable
		}

		$smtp_text[] = '<br><strong>Server:</strong>';
		$smtp_text[] = '<strong>OpenSSL:</strong> ' . ( extension_loaded( 'openssl' ) && defined( 'OPENSSL_VERSION_TEXT' ) ? OPENSSL_VERSION_TEXT : 'No' );
		if ( function_exists( 'apache_get_modules' ) ) {
			$modules     = apache_get_modules();
			$smtp_text[] = '<strong>Apache.mod_security:</strong> ' . ( in_array( 'mod_security', $modules, true ) || in_array( 'mod_security2', $modules, true ) ? 'Yes' : 'No' );
		}
		if ( function_exists( 'selinux_is_enabled' ) ) {
			$smtp_text[] = '<strong>OS.SELinux:</strong> ' . ( selinux_is_enabled() ? 'Yes' : 'No' );
		}
		if ( function_exists( 'grsecurity_is_enabled' ) ) {
			$smtp_text[] = '<strong>OS.grsecurity:</strong> ' . ( grsecurity_is_enabled() ? 'Yes' : 'No' );
		}

		return implode( '<br>', $smtp_text );
	}
}
