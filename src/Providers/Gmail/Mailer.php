<?php

namespace WPMailSMTP\Providers\Gmail;

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
	 * Set email FROM.
	 *
	 * @since 1.0.0
	 *
	 * @param string $email
	 * @param string $name
	 */
	public function set_from( $email, $name ) {
	}

	/**
	 * Set a bunch of email recipients: to, cc, bcc.
	 *
	 * @since 1.0.0
	 *
	 * @param array $recipients
	 */
	public function set_recipients( $recipients ) {
	}

	/**
	 * Set the email content.
	 *
	 * @since 1.0.0
	 *
	 * @param string|array $content
	 */
	public function set_content( $content ) {
	}

	/**
	 * Set the email attachments.
	 *
	 * @since 1.0.0
	 *
	 * @param array $attachments
	 */
	public function set_attachments( $attachments ) {
	}

	/**
	 * Set the email reply_to option.
	 *
	 * @since 1.0.0
	 *
	 * @param array $reply_to
	 */
	public function set_reply_to( $reply_to ) {
	}

	/**
	 * Set the email return_path (when supported).
	 *
	 * @since 1.0.0
	 *
	 * @param string $email
	 */
	public function set_return_path( $email ) {
	}

	/**
	 * Use Google API Services to send emails.
	 *
	 * @since 1.0.0
	 */
	public function send() {

		// Get the raw MIME email using \PHPMailer data.
		$mime = $this->phpmailer->getSentMIMEMessage();
		$data = base64_encode( $mime );
		$data = str_replace( array( '+', '/', '=' ), array( '-', '_', '' ), $data ); // url safe.
		$this->message->setRaw( $data );

		$service = new \Google_Service_Gmail( $this->auth->get_client() );

		try {
			$response = $service->users_messages->send( 'me', $this->message );

			$this->process_response( $response );
		} catch ( \Exception $e ) {
			// TODO: save here the error message to display to a user later.
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
}
