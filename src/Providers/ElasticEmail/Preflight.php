<?php

namespace WPMailSMTP\Providers\ElasticEmail;

use WPMailSMTP\Providers\Preflight\ApiPreflightAbstract;
use WPMailSMTP\Providers\Preflight\Code;
use WPMailSMTP\Providers\Preflight\Findings;

/**
 * Reads Elastic Email's channel summary to establish whether the API key is one the account
 * recognises.
 *
 * @since 4.10.0
 */
class Preflight extends ApiPreflightAbstract {

	/**
	 * Channel summary. Parameterless: Elastic Email returns 400 for parameter and auth errors alike,
	 * separated only by prose.
	 *
	 * @since 4.10.0
	 *
	 * @var string
	 */
	private const API_CHANNELS = 'https://api.elasticemail.com/v4/statistics/channels';

	/**
	 * Elastic Email's wording for any credential it does not recognise, expired or not.
	 *
	 * @since 4.10.0
	 *
	 * @var string
	 */
	private const ERROR_KEY_UNRECOGNIZED = 'APIKey Expired';

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
		$response = $this->fetch( self::API_CHANNELS, $config );

		// 400 is the status Elastic Email refuses on, and the same host serves gateway pages carrying an
		// `Error` of their own.
		if ( $this->status( $response ) === 400 && $this->error_message( $response ) === self::ERROR_KEY_UNRECOGNIZED ) {
			$findings->add( $this->error( Code::AUTH_FAILED, 'api_key' ) );
		}

		return $findings;
	}

	/**
	 * Read the error literal Elastic Email carries on a refusal.
	 *
	 * @since 4.10.0
	 *
	 * @param array $response Response to read.
	 *
	 * @return string Empty when the response carries no literal.
	 */
	private function error_message( $response ) {

		$body = $this->json( $response );

		return isset( $body['Error'] ) && is_string( $body['Error'] ) ? trim( $body['Error'] ) : '';
	}

	/**
	 * Issue one bounded read, with the key on the mailer's own header.
	 *
	 * @since 4.10.0
	 *
	 * @param string $url    Absolute URL.
	 * @param array  $config Mailer options.
	 *
	 * @return array
	 */
	private function fetch( $url, $config ) {

		return $this->request(
			$url,
			[ 'headers' => [ 'X-ElasticEmail-ApiKey' => $config['api_key'] ] ]
		);
	}
}
