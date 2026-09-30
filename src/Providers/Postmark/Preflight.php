<?php

namespace WPMailSMTP\Providers\Postmark;

use WPMailSMTP\Providers\Preflight\ApiPreflightAbstract;
use WPMailSMTP\Providers\Preflight\Code;
use WPMailSMTP\Providers\Preflight\Findings;

/**
 * Reads the configured Postmark message stream to establish whether the server token is accepted
 * and the stream is one this server holds.
 *
 * @since 4.10.0
 */
class Preflight extends ApiPreflightAbstract {

	/**
	 * Message-stream details endpoint. The stream ID completes the path and nothing follows it:
	 * a trailing slash reaches the list endpoint, which answers 200 with no stream at all.
	 *
	 * @since 4.10.0
	 *
	 * @var string
	 */
	private const API_MESSAGE_STREAM = 'https://api.postmarkapp.com/message-streams/';

	/**
	 * The stream Postmark applies when the send request names none, and the one stream no
	 * server can delete.
	 *
	 * @since 4.10.0
	 *
	 * @var string
	 */
	private const DEFAULT_STREAM = 'outbound';

	/**
	 * Postmark's code for any rejected server token.
	 *
	 * @since 4.10.0
	 *
	 * @var int
	 */
	private const ERROR_TOKEN_REJECTED = 10;

	/**
	 * Postmark's code for a stream that is not on this server.
	 *
	 * @since 4.10.0
	 *
	 * @var int
	 */
	private const ERROR_STREAM_NOT_FOUND = 1226;

	/**
	 * The stream is not on this server.
	 *
	 * @since 4.10.0
	 *
	 * @var string
	 */
	private const STREAM_NOT_FOUND = 'postmark_stream_not_found';

	/**
	 * Read the fields this check sends.
	 *
	 * An absent stream is the account's default, which the probe reads in its place.
	 *
	 * @since 4.10.0
	 *
	 * @param array $config Mailer options as submitted.
	 *
	 * @return array|null
	 */
	protected function sanitize_config( $config ) {

		$sanitized_config = [
			'server_api_token' => sanitize_text_field( (string) ( $config['server_api_token'] ?? '' ) ),
			'message_stream'   => sanitize_text_field( (string) ( $config['message_stream'] ?? '' ) ),
		];

		if ( $sanitized_config['server_api_token'] === '' ) {
			return null;
		}

		return $sanitized_config;
	}

	/**
	 * Run the check.
	 *
	 * @since 4.10.0
	 *
	 * @param array $config Mailer options.
	 *
	 * @return Findings
	 */
	protected function probe( $config ) {

		$findings = Findings::none();
		$stream   = $config['message_stream'];

		$response = $this->request(
			self::API_MESSAGE_STREAM . rawurlencode( $stream === '' ? self::DEFAULT_STREAM : $stream ),
			[ 'headers' => [ 'X-Postmark-Server-Token' => $config['server_api_token'] ] ]
		);

		$body = $this->json( $response );

		// The code carries the verdict and the status carries none of its own, a missing stream being a
		// 422. The published table is incomplete, so an unrecognised code concludes nothing.
		$code = isset( $body['ErrorCode'] ) ? (int) $body['ErrorCode'] : 0;

		if ( $code === self::ERROR_TOKEN_REJECTED ) {
			$findings->add( $this->error( Code::AUTH_FAILED, 'server_api_token' ) );

			return $findings;
		}

		if ( $code === self::ERROR_STREAM_NOT_FOUND && $stream !== '' ) {
			$findings->add( $this->error( self::STREAM_NOT_FOUND, 'message_stream' ) );
		}

		return $findings;
	}
}
