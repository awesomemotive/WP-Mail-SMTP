<?php

namespace WPMailSMTP\Providers\Sendinblue;

use WPMailSMTP\Admin\DebugEvents\DebugEvents;
use WPMailSMTP\Helpers\Helpers;
use WPMailSMTP\MailCatcherInterface;
use WPMailSMTP\Providers\MailerAbstract;
use WPMailSMTP\Vendor\SendinBlue\Client\ApiException;
use WPMailSMTP\Vendor\SendinBlue\Client\Model\CreateSmtpEmail;
use WPMailSMTP\Vendor\SendinBlue\Client\Model\SendSmtpEmail;
use WPMailSMTP\WP;

/**
 * Class Mailer.
 *
 * @since 1.6.0
 */
class Mailer extends MailerAbstract {

	/**
	 * Which response code from HTTP provider is considered to be successful?
	 *
	 * @since 1.6.0
	 *
	 * @var int
	 */
	protected $email_sent_code = 201;

	/**
	 * URL to make an API request to.
	 * Not actually used, because we use a lib to make requests.
	 *
	 * @since 1.6.0
	 *
	 * @var string
	 */
	protected $url = 'https://api.sendinblue.com/v3';

	/**
	 * The list of allowed attachment files extensions.
	 *
	 * @see   https://developers.sendinblue.com/reference#sendTransacEmail_attachment__title
	 *
	 * @since 1.6.0
	 *
	 * @var array
	 */
	// @formatter:off
	protected $allowed_attach_ext = array( 'xlsx', 'xls', 'ods', 'docx', 'docm', 'doc', 'csv', 'pdf', 'txt', 'gif', 'jpg', 'jpeg', 'png', 'tif', 'tiff', 'rtf', 'bmp', 'cgm', 'css', 'shtml', 'html', 'htm', 'zip', 'xml', 'ppt', 'pptx', 'tar', 'ez', 'ics', 'mobi', 'msg', 'pub', 'eps', 'odt', 'mp3', 'm4a', 'm4v', 'wma', 'ogg', 'flac', 'wav', 'aif', 'aifc', 'aiff', 'mp4', 'mov', 'avi', 'mkv', 'mpeg', 'mpg', 'wmv' );
	// @formatter:on

	/**
	 * @inheritDoc
	 *
	 * @since 1.6.0
	 */
	public function set_header( $name, $value ) {

		$name = sanitize_text_field( $name );

		$this->body['headers'][ $name ] = WP::sanitize_value( $value );
	}

	/**
	 * Set the From information for an email.
	 *
	 * @since 1.6.0
	 *
	 * @param string $email
	 * @param string $name
	 */
	public function set_from( $email, $name ) {

		if ( ! filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
			return;
		}

		$this->body['sender'] = array(
			'email' => $email,
			'name'  => ! empty( $name ) ? WP::sanitize_value( $name ) : '',
		);
	}

	/**
	 * Set email recipients: to, cc, bcc.
	 *
	 * @since 1.6.0
	 *
	 * @param array $recipients
	 */
	public function set_recipients( $recipients ) {

		if ( empty( $recipients ) ) {
			return;
		}

		// Allow for now only these recipient types.
		$default = array( 'to', 'cc', 'bcc' );
		$data    = array();

		foreach ( $recipients as $type => $emails ) {

			if (
				! in_array( $type, $default, true ) ||
				empty( $emails ) ||
				! is_array( $emails )
			) {
				continue;
			}

			$data[ $type ] = array();

			// Iterate over all emails for each type.
			// There might be multiple cc/to/bcc emails.
			foreach ( $emails as $email ) {
				$holder = array();
				$addr   = isset( $email[0] ) ? $email[0] : false;
				$name   = isset( $email[1] ) ? $email[1] : false;

				if ( ! filter_var( $addr, FILTER_VALIDATE_EMAIL ) ) {
					continue;
				}

				$holder['email'] = $addr;
				if ( ! empty( $name ) ) {
					$holder['name'] = $name;
				}

				array_push( $data[ $type ], $holder );
			}
		}

		foreach ( $data as $type => $type_recipients ) {
			$this->body[ $type ] = $type_recipients;
		}
	}

	/**
	 * @inheritDoc
	 *
	 * @since 1.6.0
	 */
	public function set_subject( $subject ) {

		$this->body['subject'] = $subject;
	}

	/**
	 * Set email content.
	 *
	 * @since 1.6.0
	 *
	 * @param string|array $content
	 */
	public function set_content( $content ) {

		if ( empty( $content ) ) {
			return;
		}

		if ( is_array( $content ) ) {

			if ( ! empty( $content['text'] ) ) {
				$this->body['textContent'] = $content['text'];
			}

			if ( ! empty( $content['html'] ) ) {
				$this->body['htmlContent'] = $content['html'];
			}
		} else {
			if ( $this->phpmailer->ContentType === 'text/plain' ) {
				$this->body['textContent'] = $content;
			} else {
				$this->body['htmlContent'] = $content;
			}
		}
	}

	/**
	 * Doesn't support this.
	 *
	 * @since 1.6.0
	 *
	 * @param string $email
	 */
	public function set_return_path( $email ) {

	}

	/**
	 * Set the Reply To headers if not set already.
	 *
	 * @since 1.6.0
	 *
	 * @param array $emails
	 */
	public function set_reply_to( $emails ) {

		if ( empty( $emails ) ) {
			return;
		}

		$data = array();

		foreach ( $emails as $user ) {
			$holder = array();
			$addr   = isset( $user[0] ) ? $user[0] : false;
			$name   = isset( $user[1] ) ? $user[1] : false;

			if ( ! filter_var( $addr, FILTER_VALIDATE_EMAIL ) ) {
				continue;
			}

			$holder['email'] = $addr;
			if ( ! empty( $name ) ) {
				$holder['name'] = $name;
			}

			$data[] = $holder;
		}

		if ( ! empty( $data ) ) {
			$this->body['replyTo'] = $data[0];
		}
	}

	/**
	 * Set attachments for an email.
	 *
	 * @since 1.6.0
	 *
	 * @param array $attachments The array of attachments data.
	 */
	public function set_attachments( $attachments ) {

		if ( empty( $attachments ) ) {
			return;
		}

		foreach ( $attachments as $attachment ) {

			$ext = pathinfo( $attachment[1], PATHINFO_EXTENSION );

			if ( ! in_array( $ext, $this->allowed_attach_ext, true ) ) {
				continue;
			}

			$file = $this->get_attachment_file_content( $attachment );

			if ( $file === false ) {
				continue;
			}

			$this->body['attachment'][] = [
				'name'    => $this->get_attachment_file_name( $attachment ),
				'content' => base64_encode( $file ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
			];
		}
	}

	/**
	 * Get the email body.
	 *
	 * @since 1.6.0
	 *
	 * @return SendSmtpEmail
	 */
	public function get_body() {

		/**
		 * Filters Sendinblue email body.
		 *
		 * @since 3.5.0
		 *
		 * @param array $body Email body.
		 */
		$body = apply_filters( 'wp_mail_smtp_providers_sendinblue_mailer_get_body', $this->body );

		return new SendSmtpEmail( $body );
	}

	/**
	 * Use a library to send emails.
	 *
	 * @since 1.6.0
	 */
	public function send() {

		try {
			$api = new Api( $this->connection );

			$response = $api->get_smtp_client()->sendTransacEmail( $this->get_body() );

			DebugEvents::add_debug(
				esc_html__( 'An email request was sent to the Sendinblue API.', 'wp-mail-smtp' )
			);

			$this->process_response( $response );
		} catch ( ApiException $e ) {
			$error = json_decode( $e->getResponseBody() );

			if ( json_last_error() === JSON_ERROR_NONE && ! empty( $error ) ) {
				$message = Helpers::format_error_message( $error->message, $error->code );
			} else {
				$message = $e->getMessage();
			}

			$this->error_message = $message;
		} catch ( \Exception $e ) {
			$this->error_message = $e->getMessage();
		}
	}

	/**
	 * Save response from the API to use it later.
	 * All the actually response processing is done in send() method,
	 * because SendinBlue throws exception if any error occurs.
	 *
	 * @since 1.6.0
	 *
	 * @param CreateSmtpEmail $response The Sendinblue Email object.
	 */
	protected function process_response( $response ) {

		$this->response = $response;

		if (
			is_a( $response, 'WPMailSMTP\Vendor\SendinBlue\Client\Model\CreateSmtpEmail' ) &&
			method_exists( $response, 'getMessageId' )
		) {
			$this->phpmailer->MessageID = $response->getMessageId();
			$this->verify_sent_status   = true;
		}
	}

	/**
	 * Check whether the email was sent.
	 *
	 * @since 1.6.0
	 *
	 * @return bool
	 */
	public function is_email_sent() {

		$is_sent = false;

		if ( $this->response instanceof CreateSmtpEmail ) {
			$is_sent = $this->response->valid();
		}

		/** This filter is documented in src/Providers/MailerAbstract.php. */
		return apply_filters( 'wp_mail_smtp_providers_mailer_is_email_sent', $is_sent, $this->mailer );
	}

	/**
	 * @inheritdoc
	 *
	 * @since 1.6.0
	 */
	public function get_debug_info() {

		$mailjet_text[] = '<strong>API Key:</strong> ' . ( $this->is_mailer_complete() ? 'Yes' : 'No' );

		return implode( '<br>', $mailjet_text );
	}

	/**
	 * @inheritdoc
	 *
	 * @since 1.6.0
	 */
	public function is_mailer_complete() {

		$options = $this->connection_options->get_group( $this->mailer );

		// API key is the only required option.
		if ( ! empty( $options['api_key'] ) ) {
			return true;
		}

		return false;
	}
}
