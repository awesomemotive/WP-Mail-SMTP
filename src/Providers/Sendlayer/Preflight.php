<?php

namespace WPMailSMTP\Providers\Sendlayer;

use WPMailSMTP\Providers\Preflight\ApiPreflightAbstract;
use WPMailSMTP\Providers\Preflight\Code;
use WPMailSMTP\Providers\Preflight\Findings;

/**
 * Reads the account's webhook list to establish whether the API key works.
 *
 * @since 4.10.0
 */
class Preflight extends ApiPreflightAbstract {

	/**
	 * Webhook list. The only other read returns recipient addresses and message subjects.
	 *
	 * @since 4.10.0
	 *
	 * @var string
	 */
	private const API_WEBHOOKS = 'https://console.sendlayer.com/api/v1/webhooks';

	/**
	 * The key was not recognised.
	 *
	 * @since 4.10.0
	 *
	 * @var int
	 */
	private const CODE_INVALID_KEY = 13;

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
			self::API_WEBHOOKS,
			[ 'headers' => [ 'Authorization' => 'Bearer ' . $config['api_key'] ] ]
		);
		$body     = $this->json( $response );

		// Routing is resolved before authentication, so an unknown path answers 200 with an `Errors`
		// array and no code, with any credential or none. The edge's own 403 also carries a field named
		// `error_code`, so the code inside SendLayer's envelope is the only verdict.
		$errors = isset( $body['Errors'] ) && is_array( $body['Errors'] ) ? $body['Errors'] : [];

		if ( $this->verdict_code( $errors ) === self::CODE_INVALID_KEY ) {
			$findings->add( $this->error( Code::AUTH_FAILED, 'api_key' ) );
		}

		return $findings;
	}

	/**
	 * Find the one enumerated code in the envelope.
	 *
	 * `Errors` is typed as a list, so the entry carrying the code need not be the first.
	 *
	 * @since 4.10.0
	 *
	 * @param array $errors SendLayer's error list.
	 *
	 * @return int Zero when the envelope carries no single enumerated code.
	 */
	private function verdict_code( $errors ) {

		$enumerated = [ self::CODE_INVALID_KEY ];
		$found      = [];

		foreach ( $errors as $entry ) {
			$code = is_array( $entry ) && isset( $entry['Code'] ) ? (int) $entry['Code'] : 0;

			if ( in_array( $code, $enumerated, true ) ) {
				$found[ $code ] = true;
			}
		}

		$codes = array_keys( $found );

		return count( $codes ) === 1 ? (int) $codes[0] : 0;
	}
}
