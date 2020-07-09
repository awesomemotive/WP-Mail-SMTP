<?php

namespace WPMailSMTP\Providers\Mailgun;

use WPMailSMTP\Debug;
use WPMailSMTP\Providers\MailerAbstract;
use WPMailSMTP\WP;

/**
 * Class Mailer.
 *
 * @since 1.0.0
 */
class Mailer extends MailerAbstract {

	/**
	 * Which response code from HTTP provider is considered to be successful?
	 *
	 * @since 1.0.0
	 *
	 * @var int
	 */
	protected $email_sent_code = 200;

	/**
	 * API endpoint used for sites from all regions.
	 *
	 * @since 1.4.0
	 *
	 * @var string
	 */
	const API_BASE_US = 'https://api.mailgun.net/v3/';

	/**
	 * API endpoint used for sites from EU region.
	 *
	 * @since 1.4.0
	 *
	 * @var string
	 */
	const API_BASE_EU = 'https://api.eu.mailgun.net/v3/';

	/**
	 * URL to make an API request to.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $url = '';

	/**
	 * @inheritdoc
	 */
	public function __construct( $phpmailer ) {

		// Default value should be defined before the parent class contructor fires.
		$this->url = self::API_BASE_US;

		// We want to prefill everything from MailCatcher class, which extends PHPMailer.
		parent::__construct( $phpmailer );

		// We have a special API URL to query in case of EU region.
		if ( 'EU' === $this->options->get( $this->mailer, 'region' ) ) {
			$this->url = self::API_BASE_EU;
		}

		/*
		 * Append the url with a domain,
		 * to avoid passing the domain name as a query parameter with all requests.
		 */
		$this->url .= sanitize_text_field( $this->options->get( $this->mailer, 'domain' ) . '/messages' );

		$this->set_header( 'Authorization', 'Basic ' . base64_encode( 'api:' . $this->options->get( $this->mailer, 'api_key' ) ) );
	}

	/**
	 * @inheritdoc
	 */
	public function set_from( $email, $name = '' ) {

		if ( ! filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
			return;
		}

		if ( ! empty( $name ) ) {
			$this->set_body_param(
				array(
					'from' => $name . ' <' . $email . '>',
				)
			);
		} else {
			$this->set_body_param(
				array(
					'from' => $email,
				)
			);
		}
	}

	/**
	 * @inheritdoc
	 */
	public function set_recipients( $recipients ) {

		if ( empty( $recipients ) ) {
			return;
		}

		$default = array( 'to', 'cc', 'bcc' );

		foreach ( $recipients as $kind => $emails ) {
			if (
				! in_array( $kind, $default, true ) ||
				empty( $emails ) ||
				! is_array( $emails )
			) {
				continue;
			}

			$data = array();

			foreach ( $emails as $email ) {
				$addr = isset( $email[0] ) ? $email[0] : false;
				$name = isset( $email[1] ) ? $email[1] : false;

				if ( ! filter_var( $addr, FILTER_VALIDATE_EMAIL ) ) {
					continue;
				}

				if ( ! empty( $name ) ) {
					$data[] = $name . ' <' . $addr . '>';
				} else {
					$data[] = $addr;
				}
			}

			if ( ! empty( $data ) ) {
				$this->set_body_param(
					array(
						$kind => implode( ', ', $data ),
					)
				);
			}
		}
	}

	/**
	 * @inheritdoc
	 */
	public function set_content( $content ) {

		if ( is_array( $content ) ) {

			$default = array( 'text', 'html' );

			foreach ( $content as $type => $mail ) {
				if (
					! in_array( $type, $default, true ) ||
					empty( $mail )
				) {
					continue;
				}

				$this->set_body_param(
					array(
						$type => $mail,
					)
				);
			}
		} else {

			$type = 'html';

			if ( $this->phpmailer->ContentType === 'text/plain' ) {
				$type = 'text';
			}

			if ( ! empty( $content ) ) {
				$this->set_body_param(
					array(
						$type => $content,
					)
				);
			}
		}
	}

	/**
	 * Redefine the way custom headers are process for this mailer - they should be in body.
	 *
	 * @since 1.5.0
	 *
	 * @param array $headers
	 */
	public function set_headers( $headers ) {

		foreach ( $headers as $header ) {
			$name  = isset( $header[0] ) ? $header[0] : false;
			$value = isset( $header[1] ) ? $header[1] : false;

			$this->set_body_header( $name, $value );
		}

		// Add custom PHPMailer-specific header.
		$this->set_body_header( 'X-Mailer', 'WPMailSMTP/Mailer/' . $this->mailer . ' ' . WPMS_PLUGIN_VER );
	}

	/**
	 * This mailer supports email-related custom headers inside a body of the message with a special prefix "h:".
	 *
	 * @since 1.5.0
	 */
	public function set_body_header( $name, $value ) {

		$name = sanitize_text_field( $name );

		$this->set_body_param(
			array(
				'h:' . $name => WP::sanitize_value( $value ),
			)
		);
	}

	/**
	 * It's the last one, so we can modify the whole body.
	 *
	 * @since 1.0.0
	 *
	 * @param array $attachments
	 */
	public function set_attachments( $attachments ) {

		if ( empty( $attachments ) ) {
			return;
		}

		$payload = '';
		$data    = array();

		foreach ( $attachments as $attachment ) {
			$file = false;

			/*
			 * We are not using WP_Filesystem API as we can't reliably work with it.
			 * It is not always available, same as credentials for FTP.
			 */
			try {
				if ( is_file( $attachment[0] ) && is_readable( $attachment[0] ) ) {
					$file = file_get_contents( $attachment[0] );
				}
			}
			catch ( \Exception $e ) {
				$file = false;
			}

			if ( $file === false ) {
				continue;
			}

			$data[] = array(
				'content' => $file,
				'name'    => $attachment[2],
			);
		}

		if ( ! empty( $data ) ) {

			// First, generate a boundary for the multipart message.
			$boundary = base_convert( uniqid( 'boundary', true ), 10, 36 );

			// Iterate through pre-built params and build a payload.
			foreach ( $this->body as $key => $value ) {
				if ( is_array( $value ) ) {
					foreach ( $value as $child_key => $child_value ) {
						$payload .= '--' . $boundary;
						$payload .= "\r\n";
						$payload .= 'Content-Disposition: form-data; name="' . $key . "\"\r\n\r\n";
						$payload .= $child_value;
						$payload .= "\r\n";
					}
				} else {
					$payload .= '--' . $boundary;
					$payload .= "\r\n";
					$payload .= 'Content-Disposition: form-data; name="' . $key . '"' . "\r\n\r\n";
					$payload .= $value;
					$payload .= "\r\n";
				}
			}

			// Now iterate through our attachments, and add them too.
			foreach ( $data as $key => $attachment ) {
				$payload .= '--' . $boundary;
				$payload .= "\r\n";
				$payload .= 'Content-Disposition: form-data; name="attachment[' . $key . ']"; filename="' . $attachment['name'] . '"' . "\r\n\r\n";
				$payload .= $attachment['content'];
				$payload .= "\r\n";
			}

			$payload .= '--' . $boundary . '--';

			// Redefine the body the "dirty way".
			$this->body = $payload;

			$this->set_header( 'Content-Type', 'multipart/form-data; boundary=' . $boundary );
		}
	}

	/**
	 * @inheritdoc
	 */
	public function set_reply_to( $reply_to ) {

		if ( empty( $reply_to ) ) {
			return;
		}

		$data = array();

		foreach ( $reply_to as $key => $emails ) {
			if (
				empty( $emails ) ||
				! is_array( $emails )
			) {
				continue;
			}

			$addr = isset( $emails[0] ) ? $emails[0] : false;
			$name = isset( $emails[1] ) ? $emails[1] : false;

			if ( ! filter_var( $addr, FILTER_VALIDATE_EMAIL ) ) {
				continue;
			}

			if ( ! empty( $name ) ) {
				$data[] = $name . ' <' . $addr . '>';
			} else {
				$data[] = $addr;
			}
		}

		if ( ! empty( $data ) ) {
			$this->set_body_param(
				array(
					'h:Reply-To' => implode( ',', $data ),
				)
			);
		}
	}

	/**
	 * @inheritdoc
	 */
	public function set_return_path( $email ) {

		if (
			$this->options->get( 'mail', 'return_path' ) !== true ||
			! filter_var( $email, FILTER_VALIDATE_EMAIL )
		) {
			return;
		}

		$this->set_body_param(
			array(
				'sender' => $email,
			)
		);
	}

	/**
	 * Whether the email is sent or not.
	 * We basically check the response code from a request to provider.
	 * Might not be 100% correct, not guarantees that email is delivered.
	 *
	 * In Mailgun's case it looks like we have to check if the response body has the message ID.
	 * All successful API responses should have `id` key in the response body.
	 *
	 * @since 2.2.0
	 *
	 * @return bool
	 */
	public function is_email_sent() {

		$is_sent = parent::is_email_sent();

		if (
			$is_sent &&
			isset( $this->response['body'] ) &&
			! array_key_exists( 'id', (array) $this->response['body'] )
		) {
			$message = 'Mailer: Mailgun' . PHP_EOL .
				esc_html__( 'Mailgun API request was successful, but it could not queue the email for delivery.', 'wp-mail-smtp' ) . PHP_EOL .
				esc_html__( 'This could point to an incorrect Domain Name in the plugin settings.', 'wp-mail-smtp' ) . PHP_EOL .
				esc_html__( 'Please check the WP Mail SMTP plugin settings and make sure the Mailgun Domain Name setting is correct.', 'wp-mail-smtp' );

			Debug::set( $message );

			return false;
		}

		return $is_sent;
	}

	/**
	 * Get a Mailgun-specific response with a helpful error.
	 *
	 * @since 1.2.0
	 *
	 * @return string
	 */
	protected function get_response_error() {

		$body = (array) wp_remote_retrieve_body( $this->response );

		$error_text = array();

		if ( ! empty( $body['message'] ) ) {
			if ( is_string( $body['message'] ) ) {
				$error_text[] = $body['message'];
			} else {
				$error_text[] = \json_encode( $body['message'] );
			}
		} elseif ( ! empty( $body[0] ) ) {
			if ( is_string( $body[0] ) ) {
				$error_text[] = $body[0];
			} else {
				$error_text[] = \json_encode( $body[0] );
			}
		}

		return implode( '<br>', array_map( 'esc_textarea', $error_text ) );
	}

	/**
	 * @inheritdoc
	 */
	public function get_debug_info() {

		$mg_text = array();

		$options = new \WPMailSMTP\Options();
		$mailgun = $options->get_group( 'mailgun' );

		$mg_text[] = '<strong>Api Key / Domain:</strong> ' . ( ! empty( $mailgun['api_key'] ) && ! empty( $mailgun['domain'] ) ? 'Yes' : 'No' );

		return implode( '<br>', $mg_text );
	}

	/**
	 * @inheritdoc
	 */
	public function is_mailer_complete() {

		$options = $this->options->get_group( $this->mailer );

		// API key is the only required option.
		if (
			! empty( $options['api_key'] ) &&
			! empty( $options['domain'] )
		) {
			return true;
		}

		return false;
	}
}
