<?php

namespace WPMailSMTP\Providers\MailChannels;

use WPMailSMTP\Admin\DebugEvents\DebugEvents;
use WPMailSMTP\ConnectionInterface;
use WPMailSMTP\MailCatcherInterface;
use WPMailSMTP\Providers\MailerAbstract;
use WPMailSMTP\WP;
use WP_Error;

/**
 * MailChannels Email API mailer.
 *
 * @since 4.10.0
 */
class Mailer extends MailerAbstract {

	const API_BASE          = 'https://api.mailchannels.net/tx/v1';
	const MAX_RECIPIENTS    = 1000;
	const MAX_ATTACHMENTS   = 1000;
	const MAX_REQUEST_BYTES = 30000000;

	/**
	 * MailChannels accepts both direct and queued requests with HTTP 202.
	 *
	 * @since 4.10.0
	 *
	 * @var int
	 */
	protected $email_sent_code = 202;

	/**
	 * Fixed API URL selected from the saved submission mode.
	 *
	 * @since 4.10.0
	 *
	 * @var string
	 */
	protected $url = self::API_BASE . '/send';

	/**
	 * Payload validation error detected before an HTTP request is made.
	 *
	 * @since 4.10.0
	 *
	 * @var string
	 */
	private $validation_error = '';

	/**
	 * Whether the message is transactional.
	 *
	 * @since 4.10.0
	 *
	 * @var bool
	 */
	private $transactional = true;

	/**
	 * Mailer constructor.
	 *
	 * @since 4.10.0
	 *
	 * @param MailCatcherInterface $phpmailer  The MailCatcher object.
	 * @param ConnectionInterface  $connection The Connection object.
	 */
	public function __construct( MailCatcherInterface $phpmailer, $connection = null ) {

		parent::__construct( $phpmailer, $connection );

		$this->url = $this->get_send_mode() === 'queued' ? self::API_BASE . '/send-async' : self::API_BASE . '/send';

		$this->set_header( 'X-Api-Key', $this->connection_options->get( $this->mailer, 'api_key' ) );
		$this->set_header( 'Accept', 'application/json' );
		$this->set_header( 'Content-Type', 'application/json' );
	}

	/**
	 * Set the From address.
	 *
	 * @since 4.10.0
	 *
	 * @param string $email Sender email.
	 * @param string $name  Sender name.
	 */
	public function set_from( $email, $name ) {

		if ( ! is_email( $email ) ) {
			$this->validation_error = esc_html__( 'MailChannels requires a valid From Email address.', 'wp-mail-smtp' );

			return;
		}

		$this->body['from'] = $this->format_address( [ $email, $name ] );
	}

	/**
	 * Set To, CC, and BCC recipients in one personalization.
	 *
	 * @since 4.10.0
	 *
	 * @param array $recipients Recipient groups.
	 */
	public function set_recipients( $recipients ) { // phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh

		$personalization = [];
		$count           = 0;

		foreach ( [ 'to', 'cc', 'bcc' ] as $type ) {
			foreach ( isset( $recipients[ $type ] ) ? (array) $recipients[ $type ] : [] as $recipient ) {
				if ( empty( $recipient[0] ) || ! is_email( $recipient[0] ) ) {
					continue;
				}

				$personalization[ $type ][] = $this->format_address( $recipient );

				++$count;
			}
		}

		if ( empty( $personalization['to'] ) ) {
			$this->validation_error = esc_html__( 'MailChannels requires at least one valid To recipient.', 'wp-mail-smtp' );

			return;
		}

		if ( $count > self::MAX_RECIPIENTS ) {
			$this->validation_error = esc_html__( 'The MailChannels request exceeds the 1,000-recipient limit.', 'wp-mail-smtp' );

			return;
		}

		$this->body['personalizations'] = [ $personalization ];
	}

	/**
	 * Set one Reply-To address.
	 *
	 * @since 4.10.0
	 *
	 * @param array $emails Reply-To addresses.
	 */
	public function set_reply_to( $emails ) {

		foreach ( (array) $emails as $email ) {
			if ( ! empty( $email[0] ) && is_email( $email[0] ) ) {
				$this->body['reply_to'] = $this->format_address( $email );

				return;
			}
		}
	}

	/**
	 * Set content in MailChannels content-item format.
	 *
	 * @since 4.10.0
	 *
	 * @param string|array $content Email content.
	 */
	public function set_content( $content ) { // phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh

		$items = [];

		if ( is_array( $content ) ) {
			if ( isset( $content['text'] ) && $content['text'] !== '' ) {
				$items[] = [
					'type'  => 'text/plain',
					'value' => $content['text'],
				];
			}
			if ( isset( $content['html'] ) && $content['html'] !== '' ) {
				$items[] = [
					'type'  => 'text/html',
					'value' => $content['html'],
				];
			}
		} else {
			$items[] = [
				'type'  => $this->phpmailer->ContentType === 'text/plain' ? 'text/plain' : 'text/html',
				'value' => $content,
			];
		}

		if ( empty( $items ) ) {
			$items[] = [
				'type'  => $this->phpmailer->ContentType === 'text/plain' ? 'text/plain' : 'text/html',
				'value' => '',
			];
		}

		$this->body['content'] = $items;
	}

	/**
	 * Map PHPMailer attachments and inline embeds.
	 *
	 * @since 4.10.0
	 *
	 * @param array $attachments PHPMailer attachments.
	 */
	public function set_attachments( $attachments ) { // phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh

		if ( count( (array) $attachments ) > self::MAX_ATTACHMENTS ) {
			$this->validation_error = esc_html__( 'The MailChannels request exceeds the 1,000-attachment limit.', 'wp-mail-smtp' );

			return;
		}

		$data        = [];
		$content_ids = [];

		foreach ( (array) $attachments as $attachment ) {
			$file = $this->get_attachment_file_content( $attachment );

			if ( $file === false ) {
				$this->validation_error = esc_html__( 'MailChannels could not read an attachment. The email was not submitted.', 'wp-mail-smtp' );

				return;
			}

			$item = [
				'filename' => $this->get_attachment_file_name( $attachment ),
				'type'     => empty( $attachment[4] ) ? 'application/octet-stream' : $attachment[4],
				'content'  => base64_encode( $file ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
			];

			if ( isset( $attachment[6], $attachment[7] ) && $attachment[6] === 'inline' && $attachment[7] !== '' ) {
				$content_id = trim( (string) $attachment[7], "<> \t\r\n" );

				if ( isset( $content_ids[ $content_id ] ) ) {
					$this->validation_error = esc_html__( 'MailChannels attachment content IDs must be unique.', 'wp-mail-smtp' );

					return;
				}

				$content_ids[ $content_id ] = true;
				$item['content_id']         = $content_id;
			}

			$data[] = $item;
		}

		if ( $data ) {
			$this->body['attachments'] = $data;
		}
	}

	/**
	 * Set the envelope sender from PHPMailer's resolved Sender value.
	 *
	 * @since 4.10.0
	 *
	 * @param string $email Fallback return-path email.
	 */
	public function set_return_path( $email ) {

		$sender = ! empty( $this->phpmailer->Sender ) ? $this->phpmailer->Sender : '';

		if ( is_email( $sender ) ) {
			$this->body['envelope_from'] = [ 'email' => $sender ];
		}
	}

	/**
	 * Preserve safe custom headers and consume the internal transactional header.
	 *
	 * @since 4.10.0
	 *
	 * @param array $headers Custom headers.
	 */
	public function set_headers( $headers ) {

		$reserved = [
			'authentication-results',
			'bcc',
			'cc',
			'content-transfer-encoding',
			'content-type',
			'date',
			'dkim-signature',
			'from',
			'message-id',
			'mime-version',
			'received',
			'reply-to',
			'return-path',
			'sender',
			'subject',
			'to',
			'x-api-key',
			'x-mailchannels-transactional',
		];
		$safe     = [];

		foreach ( (array) $headers as $header ) {
			$name  = isset( $header[0] ) ? trim( (string) $header[0] ) : '';
			$value = isset( $header[1] ) ? trim( (string) $header[1] ) : '';
			$lower = strtolower( $name );

			if ( $lower === 'x-mailchannels-transactional' ) {
				$this->transactional = ! in_array( strtolower( $value ), [ '0', 'false', 'no', 'off' ], true );

				continue;
			}

			if (
				empty( $name ) ||
				in_array( $lower, $reserved, true ) ||
				preg_match( '/[^A-Za-z0-9-]/', $name ) ||
				preg_match( '/[\r\n]/', $value )
			) {
				continue;
			}

			$safe[ $name ] = $this->sanitize_header_value( $name, $value );
		}

		$safe['X-Mailer'] = 'WPMailSMTP/Mailer/' . $this->mailer . ' ' . WPMS_PLUGIN_VER;

		$this->body['headers'] = $safe;

		/**
		 * Filters whether a MailChannels message is classified as transactional.
		 *
		 * @since 4.10.0
		 *
		 * @param bool                   $transactional Whether the message is transactional.
		 * @param MailCatcherInterface   $phpmailer     The MailCatcher object.
		 * @param ConnectionInterface    $connection    The connection object.
		 */
		$this->transactional = (bool) apply_filters(
			'wp_mail_smtp_providers_mail_channels_mailer_transactional',
			$this->transactional,
			$this->phpmailer,
			$this->connection
		);

		$this->body['transactional'] = $this->transactional;
	}

	/**
	 * JSON encode the filtered MailChannels payload.
	 *
	 * @since 4.10.0
	 *
	 * @return string
	 */
	public function get_body() {

		$body = parent::get_body();

		/**
		 * Filters the MailChannels request body before JSON encoding.
		 *
		 * @since 4.10.0
		 *
		 * @param array  $body   MailChannels request body.
		 * @param Mailer $mailer MailChannels mailer instance.
		 */
		$body = apply_filters( 'wp_mail_smtp_providers_mail_channels_mailer_payload', $body, $this );

		if ( ! is_array( $body ) ) {
			$this->validation_error = esc_html__( 'The WP Mail SMTP MailChannels payload filter returned an invalid value.', 'wp-mail-smtp' );

			return '';
		}

		$validation = $this->validate_filtered_payload( $body );

		if ( is_wp_error( $validation ) ) {
			$this->validation_error = $validation->get_error_message();

			return '';
		}

		return wp_json_encode( $body );
	}

	/**
	 * Submit exactly one bounded HTTPS request without automatic retries.
	 *
	 * @since 4.10.0
	 */
	public function send() {

		if ( ! empty( $this->validation_error ) ) {
			$this->process_response( new WP_Error( 'mailchannels_validation', $this->validation_error ) );

			return;
		}

		$body = $this->get_body();

		if ( ! empty( $this->validation_error ) ) {
			$this->process_response( new WP_Error( 'mailchannels_validation', $this->validation_error ) );

			return;
		}

		if ( ! is_string( $body ) || $body === '' ) {
			$this->process_response( new WP_Error( 'mailchannels_json', esc_html__( 'MailChannels could not encode the email payload.', 'wp-mail-smtp' ) ) );

			return;
		}

		if ( strlen( $body ) > self::MAX_REQUEST_BYTES ) {
			$this->process_response( new WP_Error( 413, esc_html__( 'The MailChannels request exceeds the 30 MB API limit.', 'wp-mail-smtp' ) ) );

			return;
		}

		$response = wp_safe_remote_post(
			$this->url,
			[
				'headers'     => $this->get_headers(),
				'body'        => $body,
				'timeout'     => 30,
				'httpversion' => '1.1',
				'blocking'    => true,
				'redirection' => 0,
				'sslverify'   => true,
			]
		);

		DebugEvents::add_debug( esc_html__( 'A MailChannels HTTPS email request was sent.', 'wp-mail-smtp' ) );
		$this->process_response( $response );
	}

	/**
	 * Capture acceptance identifiers after the base response parser runs.
	 *
	 * @since 4.10.0
	 *
	 * @param mixed $response HTTP response.
	 */
	protected function process_response( $response ) { // phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh

		parent::process_response( $response );

		if ( ! $this->is_email_sent() ) {
			return;
		}

		$body       = wp_remote_retrieve_body( $this->response );
		$request_id = ! empty( $body->request_id ) ? sanitize_text_field( $body->request_id ) : '';

		if ( $request_id ) {
			$this->phpmailer->addCustomHeader( 'X-MailChannels-Request-ID', $request_id );
			$this->phpmailer->addCustomHeader( 'X-Msg-ID', $request_id );
		}

		if ( $this->get_send_mode() === 'direct' && ! empty( $body->results ) && is_array( $body->results ) ) {
			foreach ( $body->results as $result ) {
				if ( ! empty( $result->message_id ) ) {
					$this->phpmailer->addCustomHeader( 'X-MailChannels-Message-ID', sanitize_text_field( $result->message_id ) );
				}
			}
		}
	}

	/**
	 * Direct mode requires all result items to be sent; queued mode requires request_id.
	 *
	 * @since 4.10.0
	 *
	 * @return bool
	 */
	public function is_email_sent() { // phpcs:ignore Generic.Metrics.NestingLevel.MaxExceeded, Generic.Metrics.CyclomaticComplexity.TooHigh

		$is_sent = false;

		if ( wp_remote_retrieve_response_code( $this->response ) === $this->email_sent_code ) {
			$body = wp_remote_retrieve_body( $this->response );

			if ( is_object( $body ) ) {
				if ( $this->get_send_mode() === 'queued' ) {
					$is_sent = ! empty( $body->request_id );
				} elseif ( ! empty( $body->results ) && is_array( $body->results ) ) {
					$is_sent = true;

					foreach ( $body->results as $result ) {
						if ( ! is_object( $result ) || empty( $result->status ) || $result->status !== 'sent' ) {
							$is_sent = false;

							break;
						}
					}
				}
			}
		}

		// phpcs:disable WPForms.Comments.Since.MissingPhpDoc, WPForms.PHP.ValidateHooks.InvalidHookName

		/** This filter is documented in src/Providers/MailerAbstract.php. */
		return apply_filters( 'wp_mail_smtp_providers_mailer_is_email_sent', $is_sent, $this->mailer );
		// phpcs:enable WPForms.Comments.Since.MissingPhpDoc, WPForms.PHP.ValidateHooks.InvalidHookName
	}

	/**
	 * Return a redacted, human-readable MailChannels error.
	 *
	 * @since 4.10.0
	 *
	 * @return string
	 */
	public function get_response_error() { // phpcs:ignore Generic.Metrics.NestingLevel.MaxExceeded, Generic.Metrics.CyclomaticComplexity.TooHigh, Generic.Metrics.CyclomaticComplexity.MaxExceeded

		$error_text = [ $this->error_message ];
		$code       = $this->get_response_code();

		if ( ! empty( $this->response ) ) {
			$body = wp_remote_retrieve_body( $this->response );

			if ( is_object( $body ) && ! empty( $body->errors ) && is_array( $body->errors ) ) {
				foreach ( array_slice( $body->errors, 0, 3 ) as $error ) {
					if ( is_string( $error ) ) {
						$error_text[] = sanitize_text_field( $error );
					} elseif ( is_object( $error ) && ! empty( $error->message ) ) {
						$error_text[] = sanitize_text_field( $error->message );
					}
				}
			}

			if ( is_object( $body ) && ! empty( $body->results ) && is_array( $body->results ) ) {
				foreach ( $body->results as $result ) {
					if ( is_object( $result ) && isset( $result->status ) && $result->status !== 'sent' && ! empty( $result->reason ) ) {
						$error_text[] = sanitize_text_field( $result->reason );
					}
				}
			}
		}

		$defaults = [
			400 => esc_html__( 'MailChannels rejected the request. Check sender, recipient, and message settings.', 'wp-mail-smtp' ),
			401 => esc_html__( 'MailChannels authentication failed. Check the API key.', 'wp-mail-smtp' ),
			403 => esc_html__( 'The MailChannels API key does not have permission for this request.', 'wp-mail-smtp' ),
			413 => esc_html__( 'The MailChannels request exceeds the 30 MB API limit.', 'wp-mail-smtp' ),
			429 => esc_html__( 'The MailChannels account or API rate limit was reached.', 'wp-mail-smtp' ),
		];

		if ( isset( $defaults[ $code ] ) ) {
			$error_text[] = $defaults[ $code ];
		} elseif ( $code >= 500 ) {
			$error_text[] = esc_html__( 'MailChannels is temporarily unavailable. The email was not submitted.', 'wp-mail-smtp' );
		} elseif ( empty( array_filter( $error_text ) ) ) {
			$error_text[] = esc_html__( 'MailChannels returned an unexpected response. The email was not submitted.', 'wp-mail-smtp' );
		}

		if ( $code === 429 && $this->get_response_header( 'retry-after' ) ) {
			$error_text[] = sprintf(
				/* translators: %s - Retry-After response value. */
				esc_html__( 'Try again after %s. WP Mail SMTP did not retry automatically.', 'wp-mail-smtp' ),
				sanitize_text_field( $this->get_response_header( 'retry-after' ) )
			);
		}

		return implode( WP::EOL, array_map( 'esc_textarea', array_unique( array_filter( $error_text ) ) ) );
	}

	/**
	 * Return a provider error code where available.
	 *
	 * @since 4.10.0
	 *
	 * @return string
	 */
	public function get_response_error_code() {

		$body = wp_remote_retrieve_body( $this->response );

		if ( is_object( $body ) && ! empty( $body->request_id ) ) {
			return sanitize_text_field( $body->request_id );
		}

		return parent::get_response_error_code();
	}

	/**
	 * Whether the mailer configuration has the required key and mode.
	 *
	 * @since 4.10.0
	 *
	 * @return bool
	 */
	public function is_mailer_complete() {

		$options = $this->connection_options->get_group( $this->mailer );

		return ! empty( $options['api_key'] ) && in_array( $this->get_send_mode(), [ 'direct', 'queued' ], true );
	}

	/**
	 * Redacted debug information.
	 *
	 * @since 4.10.0
	 *
	 * @return string
	 */
	public function get_debug_info() {

		$options = $this->connection_options->get_group( $this->mailer );
		$text[]  = '<strong>' . esc_html__( 'API Key:', 'wp-mail-smtp' ) . '</strong> ' . ( ! empty( $options['api_key'] ) ? 'Yes' : 'No' );
		$text[]  = '<strong>' . esc_html__( 'Submission Mode:', 'wp-mail-smtp' ) . '</strong> ' . esc_html( ucfirst( $this->get_send_mode() ) );

		return implode( '<br>', $text );
	}

	/**
	 * Format an API address object.
	 *
	 * @since 4.10.0
	 *
	 * @param array $address PHPMailer address tuple.
	 *
	 * @return array
	 */
	private function format_address( $address ) {

		$result = [ 'email' => $address[0] ];

		if ( ! empty( $address[1] ) ) {
			$result['name'] = $address[1];
		}

		return $result;
	}

	/**
	 * Resolve the configured send mode and its integration filter.
	 *
	 * @since 4.10.0
	 *
	 * @return string
	 */
	private function get_send_mode() {

		$mode = $this->connection_options->get( $this->mailer, 'send_mode' );

		/**
		 * Filters the MailChannels submission mode.
		 *
		 * @since 4.10.0
		 *
		 * @param string $mode   Submission mode, direct or queued.
		 * @param Mailer $mailer MailChannels mailer instance.
		 */
		$mode = apply_filters( 'wp_mail_smtp_providers_mail_channels_mailer_send_mode', $mode ? $mode : 'direct', $this );

		return $mode === 'queued' ? 'queued' : 'direct';
	}

	/**
	 * Revalidate payload shape and limits after third-party filters run.
	 *
	 * @since 4.10.0
	 *
	 * @param array $body Filtered MailChannels payload.
	 *
	 * @return true|WP_Error
	 */
	private function validate_filtered_payload( $body ) { // phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh, Generic.Metrics.CyclomaticComplexity.MaxExceeded

		if (
			empty( $body['personalizations'] ) ||
			! is_array( $body['personalizations'] ) ||
			count( $body['personalizations'] ) !== 1 ||
			! is_array( $body['personalizations'][0] )
		) {
			return new WP_Error( 422, esc_html__( 'MailChannels requires exactly one personalization.', 'wp-mail-smtp' ) );
		}

		$entry = $body['personalizations'][0];
		$count = 0;

		foreach ( [ 'to', 'cc', 'bcc' ] as $type ) {
			$addresses = isset( $entry[ $type ] ) ? $entry[ $type ] : [];

			if ( ! is_array( $addresses ) ) {
				return new WP_Error( 422, esc_html__( 'The filtered MailChannels recipient list is invalid.', 'wp-mail-smtp' ) );
			}
			foreach ( $addresses as $address ) {
				if ( ! is_array( $address ) || empty( $address['email'] ) || ! is_email( $address['email'] ) ) {
					return new WP_Error( 422, esc_html__( 'The filtered MailChannels recipient list contains an invalid address.', 'wp-mail-smtp' ) );
				}
				++$count;
			}
		}

		if ( empty( $entry['to'] ) || ! is_array( $entry['to'] ) ) {
			return new WP_Error( 422, esc_html__( 'MailChannels requires at least one valid To recipient.', 'wp-mail-smtp' ) );
		}
		if ( $count > self::MAX_RECIPIENTS ) {
			return new WP_Error( 422, esc_html__( 'The MailChannels request exceeds the 1,000-recipient limit.', 'wp-mail-smtp' ) );
		}

		$attachments = isset( $body['attachments'] ) ? $body['attachments'] : [];

		if ( ! is_array( $attachments ) || count( $attachments ) > self::MAX_ATTACHMENTS ) {
			return new WP_Error( 422, esc_html__( 'The MailChannels request exceeds the 1,000-attachment limit.', 'wp-mail-smtp' ) );
		}
		if ( isset( $body['transactional'] ) && $body['transactional'] === false && $count !== 1 ) {
			return new WP_Error( 422, esc_html__( 'MailChannels non-transactional email requires exactly one recipient.', 'wp-mail-smtp' ) );
		}

		return true;
	}
}
