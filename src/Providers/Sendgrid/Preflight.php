<?php

namespace WPMailSMTP\Providers\Sendgrid;

use WPMailSMTP\Providers\Preflight\ApiPreflightAbstract;
use WPMailSMTP\Providers\Preflight\Code;
use WPMailSMTP\Providers\Preflight\Findings;

/**
 * Reads the SendGrid key's own scope list, which is one of only two endpoints a Mail-Send-restricted
 * key can reach, so it answers for the narrowest key that can still send.
 *
 * @since 4.10.0
 */
class Preflight extends ApiPreflightAbstract {

	/**
	 * Scope list for the authenticating key.
	 *
	 * @since 4.10.0
	 *
	 * @var string
	 */
	private const API_SCOPES = 'https://api.sendgrid.com/v3/scopes';

	/**
	 * Messages that refuse the credential itself.
	 *
	 * SendGrid spreads them over 400, 401 and 403, and words the same two states differently per
	 * endpoint, so the message decides and the status does not. The account's credit and message
	 * limits and the key's own IP allowlist share those statuses and must stay outside this list,
	 * since authentication precedes authorization and each of them leaves the key valid.
	 *
	 * @since 4.10.0
	 *
	 * @var string[]
	 */
	private const MESSAGES_REJECTED = [
		'unauthorized',
		'authorization required',
		'authorization grant is invalid',
		'wrong credentials',
	];

	/**
	 * Read the fields this check sends.
	 *
	 * @since 4.10.0
	 *
	 * @param array $config Mailer options as submitted.
	 *
	 * @return array|null
	 */
	protected function sanitize_config( $config ) {

		$sanitized_config = [
			'api_key' => sanitize_text_field( (string) ( $config['api_key'] ?? '' ) ),
		];

		if ( $sanitized_config['api_key'] === '' ) {
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

		$response = $this->request(
			self::API_SCOPES,
			[ 'headers' => [ 'Authorization' => 'Bearer ' . $config['api_key'] ] ]
		);

		$message = $this->error_message( $this->json( $response ) );

		if ( $this->says_any( $message, self::MESSAGES_REJECTED ) ) {
			$findings->add( $this->error( Code::AUTH_FAILED, 'api_key' ) );
		}

		return $findings;
	}

	/**
	 * Read the message out of SendGrid's own error envelope, lowercased for matching.
	 *
	 * The envelope is what separates SendGrid's refusals from an edge block's, which reaches this API
	 * as a 403 of its own.
	 *
	 * @since 4.10.0
	 *
	 * @param array|null $body Decoded body.
	 *
	 * @return string Empty when the body carried no envelope.
	 */
	private function error_message( $body ) {

		$message = isset( $body['errors'][0]['message'] ) ? $body['errors'][0]['message'] : '';

		return is_string( $message ) ? strtolower( $message ) : '';
	}

	/**
	 * Whether the message carries any of the given fragments.
	 *
	 * SendGrid publishes no error codes, and it appends account-specific detail to some of these, so a
	 * fragment is all there is to match on.
	 *
	 * @since 4.10.0
	 *
	 * @param string   $message   Lowercased message.
	 * @param string[] $fragments Fragments to look for.
	 *
	 * @return bool
	 */
	private function says_any( $message, $fragments ) {

		foreach ( $fragments as $fragment ) {
			if ( $message !== '' && strpos( $message, $fragment ) !== false ) {
				return true;
			}
		}

		return false;
	}
}
