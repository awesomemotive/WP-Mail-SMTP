<?php

namespace WPMailSMTP\Providers\Mailgun;

use WPMailSMTP\Providers\MailerAbstract;

/**
 * Class Mailer.
 *
 * @since 1.0.0
 */
class Mailer extends MailerAbstract {

	/**
	 * Which response code from HTTP provider is considered to be successful?
	 *
	 * @var int
	 */
	protected $email_sent_code = 200;

	/**
	 * URL to make an API request to.
	 *
	 * @var string
	 */
	protected $url = 'https://api.mailgun.net/v3/';

	/**
	 * @inheritdoc
	 */
	public function __construct( $phpmailer ) {

		// We want to prefill everything from \WPMailSMTP\MailCatcher class, which extends \PHPMailer.
		parent::__construct( $phpmailer );

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
			} catch ( \Exception $e ) {
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
}
