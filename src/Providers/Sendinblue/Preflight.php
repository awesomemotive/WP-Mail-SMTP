<?php

namespace WPMailSMTP\Providers\Sendinblue;

use WPMailSMTP\Providers\Preflight\ApiPreflightAbstract;
use WPMailSMTP\Providers\Preflight\Code;
use WPMailSMTP\Providers\Preflight\Findings;

/**
 * Reads the Brevo account to establish whether the API key authenticates.
 *
 * @since 4.10.0
 */
class Preflight extends ApiPreflightAbstract {

	/**
	 * Account details, the cheapest read Brevo answers for a key.
	 *
	 * @since 4.10.0
	 *
	 * @var string
	 */
	private const API_ACCOUNT = 'https://api.brevo.com/v3/account';

	/**
	 * Messages Brevo answers under its `unauthorized` code that refuse the credential itself.
	 *
	 * The same code also carries an unrecognised source IP, which leaves the key valid and reaches
	 * more sites than all of these together, so the message decides and the code does not.
	 *
	 * @since 4.10.0
	 *
	 * @var string[]
	 */
	private const MESSAGES_REJECTED = [
		'key not found',
		'api key is not enabled',
		'authentication not found',
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
		$response = $this->request( self::API_ACCOUNT, [ 'headers' => [ 'api-key' => $config['api_key'] ] ] );

		$body = $this->json( $response );

		// The code is the envelope marker rather than the verdict: Brevo answers `unauthorized` for a
		// wrong key, a disabled key, an absent header and an unrecognised source IP alike.
		if ( $this->error_code( $body ) === 'unauthorized' && $this->refuses_credential( $this->message( $body ) ) ) {
			$findings->add( $this->error( Code::AUTH_FAILED, 'api_key' ) );
		}

		return $findings;
	}

	/**
	 * Read Brevo's own error prose, lowercased for matching.
	 *
	 * @since 4.10.0
	 *
	 * @param array|null $body Decoded body.
	 *
	 * @return string Empty when the body carried no message.
	 */
	private function message( $body ) {

		$message = $body['message'] ?? '';

		return is_string( $message ) ? strtolower( $message ) : '';
	}

	/**
	 * Whether the message refuses the credential.
	 *
	 * The unrecognised-IP message names the address it saw, so a fragment is what stays stable.
	 *
	 * @since 4.10.0
	 *
	 * @param string $message Lowercased message.
	 *
	 * @return bool
	 */
	private function refuses_credential( $message ) {

		foreach ( self::MESSAGES_REJECTED as $fragment ) {
			if ( strpos( $message, $fragment ) !== false ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get Brevo's own error code, if the body carries one.
	 *
	 * @since 4.10.0
	 *
	 * @param array|null $body Decoded body.
	 *
	 * @return string
	 */
	private function error_code( $body ) {

		$code = $body['code'] ?? '';

		return is_string( $code ) ? $code : '';
	}
}
