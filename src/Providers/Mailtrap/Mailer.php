<?php

namespace WPMailSMTP\Providers\Mailtrap;

use WPMailSMTP\ConnectionInterface;
use WPMailSMTP\Helpers\Helpers;
use WPMailSMTP\WP;
use WPMailSMTP\MailCatcherInterface;
use WPMailSMTP\Providers\MailerAbstract;

/**
 * Class Mailer.
 */
class Mailer extends MailerAbstract {

	/**
	 * URL to make an API request to.
	 *
	 * @var string
	 */
	protected $url = 'https://send.api.mailtrap.io/api/send';

	/**
	 * Mailer constructor.
	 *
	 * @param MailCatcherInterface $phpmailer  The MailCatcher object.
	 * @param ConnectionInterface  $connection The Connection object.
	 */
	public function __construct( $phpmailer, $connection = null ) {

		// We want to prefill everything from MailCatcher class, which extends PHPMailer.
		parent::__construct( $phpmailer, $connection );

		// Set mailer specific headers.
		$this->set_header( 'Api-Token', $this->connection_options->get( $this->mailer, 'api_key' ) );
		$this->set_header( 'Accept', 'application/json' );
		$this->set_header( 'Content-Type', 'application/json' );
	}

	/**
	 * Redefine the way custom headers are processed for this mailer - they should be in body.
	 *
	 * @param array $headers Headers array.
	 */
	public function set_headers( $headers ) {

		foreach ( $headers as $header ) {
			$name  = isset( $header[0] ) ? $header[0] : false;
			$value = isset( $header[1] ) ? $header[1] : false;

			$this->set_body_header( $name, $value );
		}

		// Add custom header.
		$this->set_body_header( 'X-Mailer', 'WPMailSMTP/Mailer/' . $this->mailer . ' ' . WPMS_PLUGIN_VER );
	}

	/**
	 * This mailer supports email-related custom headers inside a body of the message.
	 *
	 * @param string $name  Header name.
	 * @param string $value Header value.
	 */
	public function set_body_header( $name, $value ) {

		$name = sanitize_text_field( $name );

		if ( empty( $name ) ) {
			return;
		}

		$headers = isset( $this->body['Headers'] ) ? (array) $this->body['Headers'] : [];

		$headers[ $name ] = $this->sanitize_header_value( $name, $value );

		$this->set_body_param(
			[
				'headers' => $headers,
			]
		);
	}

	/**
	 * Set the From information for an email.
	 *
	 * @param string $email The sender email address.
	 * @param string $name  The sender name.
	 */
	public function set_from( $email, $name ) {

		if ( ! filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
			return;
		}
		$this->set_body_param([
			'from' => $this->address_format([$email, $name]),
		]);
	}

	/**
	 * Set email recipients: to, cc, bcc.
	 *
	 * @param array $recipients Email recipients.
	 */
	public function set_recipients( $recipients ) { // phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh

		if ( empty( $recipients ) ) {
			return;
		}

		// Allow only these recipient types.
		$allowed_types = [ 'to', 'cc', 'bcc' ];
		$data          = [];

		foreach ( $recipients as $type => $emails ) {
			if (
				! in_array( $type, $allowed_types, true ) ||
				empty( $emails ) ||
				! is_array( $emails )
			) {
				continue;
			}

			// Iterate over all emails for each type.
			// There might be multiple cc/to/bcc emails.
			foreach ( $emails as $email ) {
				if ( ! isset( $email[0] ) || ! filter_var( $email[0], FILTER_VALIDATE_EMAIL ) ) {
					continue;
				}

				$data[ $type ][] = $this->address_format( $email );
			}
		}

		if ( ! empty( $data ) ) {
			$this->set_body_param( $data );
		}
	}

	/**
	 * Set the Reply To information for an email.
	 *
	 * @param array $emails Reply To email addresses.
	 */
	public function set_reply_to( $emails ) {

		if ( empty( $emails ) ) {
			return;
		}

		$first_email = reset( $emails );

		if ( ! isset( $first_email[0] ) || ! filter_var( $first_email[0], FILTER_VALIDATE_EMAIL ) ) {
			return;
		}

		$reply_to = [
			'email' => $first_email[0],
		];

		if ( ! empty( $first_email[1] ) ) {
			$reply_to['name'] = $first_email[1];
		}

		if ( ! empty( $reply_to ) ) {
			$this->set_body_param(
				[
					'reply_to' => $reply_to,
				]
			);
		}
	}

	/**
	 * Set email subject.
	 *
	 * @param string $subject Email subject.
	 */
	public function set_subject( $subject ) {

		$this->set_body_param(
			[
				'subject' => $subject,
			]
		);
	}

	/**
	 * Set email content.
	 *
	 * @param string|array $content Email content.
	 */
	public function set_content( $content ) {

		if ( empty( $content ) ) {
			return;
		}

		if ( is_array( $content ) ) {
			if ( ! empty( $content['text'] ) ) {
				$this->set_body_param(
					[
						'text' => $content['text'],
					]
				);
			}

			if ( ! empty( $content['html'] ) ) {
				$this->set_body_param(
					[
						'html' => $content['html'],
					]
				);
			}
		} else {
			if ( $this->phpmailer->ContentType === 'text/plain' ) {
				$this->set_body_param(
					[
						'text' => $content,
					]
				);
			} else {
				$this->set_body_param(
					[
						'html' => $content,
					]
				);
			}
		}
	}

	/**
	 * Set attachments for an email.
	 *
	 * @param array $attachments Attachments array.
	 */
	public function set_attachments( $attachments ) {

		if ( empty( $attachments ) ) {
			return;
		}

		$data = $this->prepare_attachments( $attachments );

		if ( ! empty( $data ) ) {
			$this->set_body_param(
				[
					'attachments' => $data,
				]
			);
		}
	}

	/**
	 * Prepare attachments data for Mailtrap API.
	 *
	 * @param array $attachments Array of attachments.
	 *
	 * @return array
	 */
	protected function prepare_attachments( $attachments ) {

		$data = [];

		foreach ( $attachments as $attachment ) {
			$file = $this->get_attachment_file_content( $attachment );

			if ( $file === false ) {
				continue;
			}

			$filetype = str_replace( ';', '', trim( $attachment[4] ) );

			$resultAttachment = [
				'filename'    => empty( $attachment[2] ) ? 'file-' . wp_hash( microtime() ) . '.' . $filetype : trim( $attachment[2] ),
				// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
				'content'     => str_replace("\r\n", '', base64_encode( $file )),
				'type'        => $attachment[4],
				'disposition' => in_array( $attachment[6], [ 'inline', 'attachment' ], true ) ? $attachment[6] : 'attachment',
			];

			if ('inline' === $resultAttachment['disposition']) {
				$resultAttachment['content_id'] = empty( $attachment[7] ) ? '' : trim( (string) $attachment[7] );
			}

			$data[] = $resultAttachment;
		}

		return $data;
	}

	/**
	 * Doesn't support this.
	 * So we do nothing.
	 *
	 * @param string $email Return Path email address.
	 */
	public function set_return_path( $email ) { }

	/**
	 * Redefine the way email body is returned.
	 * By default, we are sending an array of data.
	 * Mailtrap requires a JSON, so we encode the body.
	 */
	public function get_body() {

		$body = parent::get_body();

		return wp_json_encode( $body );
	}

	/**
	 * We might need to do something after the email was sent to the API.
	 * In this method we preprocess the response from the API.
	 *
	 * @param mixed $response Response data.
	 */
	protected function process_response( $response ) {

		parent::process_response( $response );

		if (
			! is_wp_error( $response ) &&
			! empty( $this->response['body']->message_ids )
		) {
			$this->phpmailer->addCustomHeader( 'X-Msg-ID', $this->response['body']->message_ids[0] );
			$this->verify_sent_status = true;
		}
	}

	/**
	 * Get a Mailtrap-specific response with a helpful error.
	 *
	 * @return string
	 */
	public function get_response_error() { // phpcs:ignore Generic.Metrics.NestingLevel.MaxExceeded

		$error_text[] = $this->error_message;

		if ( ! empty( $this->response ) ) {
			$body = wp_remote_retrieve_body( $this->response );

			// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			if ( ! empty($body->errors) || ! empty ($body->error)) {
				$message = self::getErrorMsg(!empty($body->errors) ? $body->errors : $body->error);
				$error_text[] = Helpers::format_error_message( $message );
			} else {
				$error_text[] = WP::wp_remote_get_response_error_message( $this->response );
			}
			// phpcs:enable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		}

		return implode( WP::EOL, array_map( 'esc_textarea', array_filter( $error_text ) ) );
	}

	public static function getErrorMsg(array|string $errors): string
	{
		$errorMsg = '';
		if (is_array($errors)) {
			foreach ($errors as $key => $value) {
				if (is_string($key)) {
					// add name of field
					$errorMsg .= $key . ' -> ';
				}

				$errorMsg .= self::getErrorMsg($value);
			}
		} else {
			$errorMsg .= $errors . '. ';
		}

		return $errorMsg;
	}

	/**
	 * Get mailer debug information, that is helpful during support.
	 *
	 * @return string
	 */
	public function get_debug_info() {

		$options = $this->connection_options->get_group( $this->mailer );

		$text[] = '<strong>' . esc_html__( 'API Key:', 'wp-mail-smtp' ) . '</strong> ' .
							( ! empty( $options['api_key'] ) ? 'Yes' : 'No' );

		return implode( '<br>', $text );
	}

	/**
	 * Whether the mailer has all its settings correctly set up and saved.
	 *
	 * This mailer is configured when `server_api_token` setting is defined.
	 *
	 * @return bool
	 */
	public function is_mailer_complete() {

		$options = $this->connection_options->get_group( $this->mailer );

		if ( ! empty( $options['api_key'] ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Prepare address param.
	 *
	 * @param array $address Address array.
	 *
	 * @return array
	 */
	private function address_format( $address ) {
		$email  = isset( $address[0] ) ? $address[0] : false;
		$name   = isset( $address[1] ) ? $address[1] : false;
		$res = [ 'email' => $email ];
		if ( ! empty( $name ) ) {
			$res['name'] = $name;
		}

		return $res;
	}
}
