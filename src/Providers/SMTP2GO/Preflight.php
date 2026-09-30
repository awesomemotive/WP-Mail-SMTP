<?php

namespace WPMailSMTP\Providers\SMTP2GO;

use WPMailSMTP\Providers\Preflight\ApiPreflightAbstract;
use WPMailSMTP\Providers\Preflight\Code;
use WPMailSMTP\Providers\Preflight\Findings;

/**
 * Reads the API key's own permission list, which establishes that the key works and that it may
 * send.
 *
 * @since 4.10.0
 */
class Preflight extends ApiPreflightAbstract {

	/**
	 * The key's permission list. Documented as available to every API key, and proven so: the test
	 * key holds no `/api_keys/*` permission and still reads it. This API exposes no GET routes, so
	 * POST is its read verb.
	 *
	 * @since 4.10.0
	 *
	 * @var string
	 */
	private const API_PERMISSIONS = 'https://api.smtp2go.com/v3/api_keys/permissions';

	/**
	 * Every error code that means the credential was refused: a wrong value is 400, an absent or
	 * malformed one 403.
	 *
	 * @since 4.10.0
	 *
	 * @var string[]
	 */
	private const CODES_REJECTED = [
		'E_ApiResponseCodes.API_EXCEPTION',
		'E_ApiResponseCodes.INVALID_IN_PAYLOAD',
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
		$body     = $this->json( $this->post( self::API_PERMISSIONS, $config ) );

		// Every refusal this endpoint issues is JSON carrying `data.error_code`.
		$code = isset( $body['data']['error_code'] ) ? (string) $body['data']['error_code'] : '';

		if ( in_array( $code, self::CODES_REJECTED, true ) ) {
			$findings->add( $this->error( Code::AUTH_FAILED, 'api_key' ) );
		}

		return $findings;
	}

	/**
	 * Issue one bounded read.
	 *
	 * @since 4.10.0
	 *
	 * @param string $url     Absolute URL.
	 * @param array  $config  Mailer options.
	 * @param array  $payload Request body.
	 *
	 * @return array
	 */
	private function post( $url, $config, $payload = [] ) {

		return $this->request(
			$url,
			[
				'method'  => 'POST',
				'headers' => [
					'X-Smtp2go-Api-Key' => $config['api_key'],
					'Content-Type'      => 'application/json',
				],
				'body'    => wp_json_encode( (object) $payload ),
			]
		);
	}
}
