<?php

namespace WPMailSMTP\Providers\Gmail;

use WPMailSMTP\Debug;
use WPMailSMTP\MailCatcher;
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
	 * @var string
	 */
	protected $url = 'https://www.googleapis.com/upload/gmail/v1/users/userId/messages/send';

	/**
	 * Gmail custom Auth library.
	 *
	 * @var Auth
	 */
	protected $auth;

	/**
	 * Gmail message.
	 *
	 * @var \Google_Service_Gmail_Message
	 */
	protected $message;

	/**
	 * Mailer constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param \WPMailSMTP\MailCatcher $phpmailer
	 */
	public function __construct( $phpmailer ) {
		parent::__construct( $phpmailer );

		if ( ! $this->is_php_compatible() ) {
			return;
		}

		// Include the Google library.
		require wp_mail_smtp()->plugin_path . '/vendor/autoload.php';

		$this->auth    = new Auth();
		$this->message = new \Google_Service_Gmail_Message();
	}

	/**
	 * Re-use the MailCatcher class methods and properties.
	 *
	 * @since 1.2.0
	 *
	 * @param \WPMailSMTP\MailCatcher $phpmailer
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
	}

	/**
	 * Use Google API Services to send emails.
	 *
	 * @since 1.0.0
	 */
	public function send() {

		// Get the raw MIME email using \MailCatcher data.
		$base64 = base64_encode( $this->phpmailer->getSentMIMEMessage() );
		$base64 = str_replace( array( '+', '/', '=' ), array( '-', '_', '' ), $base64 ); // url safe.
		$this->message->setRaw( $base64 );

		$service = new \Google_Service_Gmail( $this->auth->get_client() );

		try {
			$response = $service->users_messages->send( 'me', $this->message );

			$this->process_response( $response );
		} catch ( \Exception $e ) {
			Debug::set( 'Error while sending via Gmail mailer: ' . $e->getMessage() );

			return;
		}
	}

	/**
	 * Save response from the API to use it later.
	 *
	 * @since 1.0.0
	 *
	 * @param \Google_Service_Gmail_Message $response
	 */
	protected function process_response( $response ) {
		$this->response = $response;
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
				return true;
			}
		}

		return $is_sent;
	}

	/**
	 * @inheritdoc
	 */
	public function get_debug_info() {

		$gmail_text = array();

		$options = new \WPMailSMTP\Options();
		$gmail   = $options->get_group( 'gmail' );

		$gmail_text[] = '<strong>Client ID/Secret:</strong> ' . ( ! empty( $gmail['client_id'] ) && ! empty( $gmail['client_secret'] ) ? 'Yes' : 'No' );
		$gmail_text[] = '<strong>Auth Code:</strong> ' . ( ! empty( $gmail['auth_code'] ) ? 'Yes' : 'No' );
		$gmail_text[] = '<strong>Access Token:</strong> ' . ( ! empty( $gmail['access_token'] ) ? 'Yes' : 'No' );

		$gmail_text[] = '<br><strong>Server:</strong>';

		$gmail_text[] = '<strong>OpenSSL:</strong> ' . ( extension_loaded( 'openssl' ) ? 'Yes' : 'No' );
		$gmail_text[] = '<strong>PHP.allow_url_fopen:</strong> ' . ( ini_get( 'allow_url_fopen' ) ? 'Yes' : 'No' );
		$gmail_text[] = '<strong>PHP.stream_socket_client():</strong> ' . ( function_exists( 'stream_socket_client' ) ? 'Yes' : 'No' );
		$gmail_text[] = '<strong>PHP.fsockopen():</strong> ' . ( function_exists( 'fsockopen' ) ? 'Yes' : 'No' );
		$gmail_text[] = '<strong>PHP.curl_version():</strong> ' . ( function_exists( 'curl_version' ) ? 'Yes' : 'No' );
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
}
