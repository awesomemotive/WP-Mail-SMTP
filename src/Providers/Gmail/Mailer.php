<?php

namespace WPMailSMTP\Providers\Gmail;

use WPMailSMTP\Debug;
use WPMailSMTP\MailCatcherInterface;
use WPMailSMTP\Providers\MailerAbstract;

/**
 * Class Mailer.
 *
 * @since 1.0.0
 */
class Mailer extends MailerAbstract {

	/**
	 * URL to make an API request to.
	 * Not used for Gmail, as we are using its API.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $url = 'https://www.googleapis.com/upload/gmail/v1/users/{userId}/messages/send';

	/**
	 * Gmail message.
	 *
	 * @since 1.0.0
	 *
	 * @var \Google_Service_Gmail_Message
	 */
	protected $message;

	/**
	 * Mailer constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param MailCatcherInterface $phpmailer The MailCatcher object.
	 */
	public function __construct( $phpmailer ) {

	    parent::__construct( $phpmailer );

		if ( ! $this->is_php_compatible() ) {
			return;
		}
	}

	/**
	 * Re-use the MailCatcher class methods and properties.
	 *
	 * @since 1.2.0
	 *
	 * @param MailCatcherInterface $phpmailer The MailCatcher object.
	 */
	public function process_phpmailer( $phpmailer ) {

	    // Make sure that we have access to PHPMailer class methods.
		if ( ! wp_mail_smtp()->is_valid_phpmailer( $phpmailer ) ) {
			return;
		}

		$this->phpmailer = $phpmailer;
	}

	/**
	 * Use Google API Services to send emails.
	 *
	 * @since 1.0.0
	 */
	public function send() {

		// Include the Google library.
		require_once wp_mail_smtp()->plugin_path . '/vendor/autoload.php';

		$auth    = new Auth();
		$message = new \Google_Service_Gmail_Message();

		// Set the authorized Gmail email address as the "from email" if the set email is not on the list of aliases.
		$possible_from_emails = $auth->get_user_possible_send_from_addresses();

		if ( ! in_array( $this->phpmailer->From, $possible_from_emails, true ) ) {
			$user_info = $auth->get_user_info();

			if ( ! empty( $user_info['email'] ) ) {
				$this->phpmailer->From   = $user_info['email'];
				$this->phpmailer->Sender = $user_info['email'];
			}
		}

		try {
			// Prepare a message for sending if any changes happened above.
			$this->phpmailer->preSend();

			// Get the raw MIME email using MailCatcher data. We need to make base64URL-safe string.
			$base64 = str_replace(
				[ '+', '/', '=' ],
				[ '-', '_', '' ],
				base64_encode( $this->phpmailer->getSentMIMEMessage() ) //phpcs:ignore
			);

			$message->setRaw( $base64 );

			$service  = new \Google_Service_Gmail( $auth->get_client() );
			$response = $service->users_messages->send( 'me', $message );

			$this->process_response( $response );
		} catch ( \Exception $e ) {
			Debug::set(
				'Mailer: Gmail' . "\r\n" .
				$this->process_exception_message( $e->getMessage() )
			);

			return;
		}
	}

	/**
	 * Save response from the API to use it later.
	 *
	 * @since 1.0.0
	 * @since 1.5.0 Added action "wp_mail_smtp_providers_gmail_mailer_process_response" with $response.
	 *
	 * @param \Google_Service_Gmail_Message $response Instance of Gmail response.
	 */
	protected function process_response( $response ) {

		$this->response = $response;

		do_action( 'wp_mail_smtp_providers_gmail_mailer_process_response', $this->response, $this->phpmailer );
	}

	/**
	 * Check whether the email was sent.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function is_email_sent() {

		$is_sent = false;

		if ( method_exists( $this->response, 'getId' ) ) {
			$message_id = $this->response->getId();
			if ( ! empty( $message_id ) ) {
				$is_sent = true;
			}
		}

		// Clear debug messages if email is successfully sent.
		if ( $is_sent ) {
			Debug::clear();
		}

		return $is_sent;
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

		$gmail_text = array();

		$options  = new \WPMailSMTP\Options();
		$gmail    = $options->get_group( 'gmail' );
		$curl_ver = 'No';
		if ( function_exists( 'curl_version' ) ) {
			$curl     = curl_version(); // phpcs:ignore
			$curl_ver = $curl['version'];
		}

		$gmail_text[] = '<strong>Client ID/Secret:</strong> ' . ( ! empty( $gmail['client_id'] ) && ! empty( $gmail['client_secret'] ) ? 'Yes' : 'No' );
		$gmail_text[] = '<strong>Auth Code:</strong> ' . ( ! empty( $gmail['auth_code'] ) ? 'Yes' : 'No' );
		$gmail_text[] = '<strong>Access Token:</strong> ' . ( ! empty( $gmail['access_token'] ) ? 'Yes' : 'No' );

		$gmail_text[] = '<br><strong>Server:</strong>';

		$gmail_text[] = '<strong>OpenSSL:</strong> ' . ( extension_loaded( 'openssl' ) && defined( 'OPENSSL_VERSION_TEXT' ) ? OPENSSL_VERSION_TEXT : 'No' );
		$gmail_text[] = '<strong>PHP.allow_url_fopen:</strong> ' . ( ini_get( 'allow_url_fopen' ) ? 'Yes' : 'No' );
		$gmail_text[] = '<strong>PHP.stream_socket_client():</strong> ' . ( function_exists( 'stream_socket_client' ) ? 'Yes' : 'No' );
		$gmail_text[] = '<strong>PHP.fsockopen():</strong> ' . ( function_exists( 'fsockopen' ) ? 'Yes' : 'No' );
		$gmail_text[] = '<strong>PHP.curl_version():</strong> ' . $curl_ver; // phpcs:ignore
		if ( function_exists( 'apache_get_modules' ) ) {
			$modules      = apache_get_modules();
			$gmail_text[] = '<strong>Apache.mod_security:</strong> ' . ( in_array( 'mod_security', $modules, true ) || in_array( 'mod_security2', $modules, true ) ? 'Yes' : 'No' );
		}
		if ( function_exists( 'selinux_is_enabled' ) ) {
			$gmail_text[] = '<strong>OS.SELinux:</strong> ' . ( selinux_is_enabled() ? 'Yes' : 'No' );
		}
		if ( function_exists( 'grsecurity_is_enabled' ) ) {
			$gmail_text[] = '<strong>OS.grsecurity:</strong> ' . ( grsecurity_is_enabled() ? 'Yes' : 'No' );
		}

		return implode( '<br>', $gmail_text );
	}

	/**
	 * Whether the mailer has all its settings correctly set up and saved.
	 *
	 * @since 1.4.0
	 *
	 * @return bool
	 */
	public function is_mailer_complete() {

		if ( ! $this->is_php_compatible() ) {
			return false;
		}

		$auth = new Auth();

		if (
			$auth->is_clients_saved() &&
			! $auth->is_auth_required()
		) {
			return true;
		}

		return false;
	}

	/**
	 * Process the exception message and append additional explanation to it.
	 *
	 * @since 2.1.0
	 *
	 * @param mixed $message A string or an object with strings.
	 *
	 * @return string
	 */
	protected function process_exception_message( $message ) {

		// Transform the passed message to a string.
		if ( ! is_string( $message ) ) {
			$message = wp_json_encode( $message );
		} else {
			$message = wp_strip_all_tags( $message, false );
		}

		// Define known errors, that we will scan the message with.
		$known_errors = array(
			array(
				'errors'      => array(
					'invalid_grant',
				),
				'explanation' => esc_html__( 'Please re-grant Google app permissions!', 'wp-mail-smtp' ) . ' ' . PHP_EOL .
					esc_html__( 'Go to WP Mail SMTP plugin settings page. Click the “Remove Connection” button.', 'wp-mail-smtp' ) . ' ' . PHP_EOL .
					esc_html__( 'Then click the “Allow plugin to send emails using your Google account” button and re-enable access.', 'wp-mail-smtp' ),
			),
		);

		// Check if we get a match and append the explanation to the original message.
		foreach ( $known_errors as $error ) {
			foreach ( $error['errors'] as $error_fragment ) {
				if ( false !== strpos( $message, $error_fragment ) ) {
					return $message . PHP_EOL . $error['explanation'];
				}
			}
		}

		// If we get no match we return the original message (as a string).
		return $message;
	}

	/**
	 * Get the default email addresses for the reply to email parameter.
	 *
	 * @deprecated 2.1.1
	 *
	 * @since 2.1.0
	 * @since 2.1.1 Not used anymore.
	 *
	 * @return array
	 */
	public function default_reply_to_addresses() {

		_deprecated_function( __CLASS__ . '::' . __METHOD__, '2.1.1 of WP Mail SMTP plugin' );

		$gmail_creds = ( new Auth() )->get_user_info();

		if ( empty( $gmail_creds['email'] ) ) {
			return [];
		}

		return [
			$gmail_creds['email'] => [
				$gmail_creds['email'],
				'',
			],
		];
	}
}
