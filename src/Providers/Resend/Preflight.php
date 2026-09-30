<?php

namespace WPMailSMTP\Providers\Resend;

use WPMailSMTP\Providers\Preflight\ApiPreflightAbstract;
use WPMailSMTP\Providers\Preflight\Code;
use WPMailSMTP\Providers\Preflight\Findings;

/**
 * Reads the Resend domain list to establish whether the key is recognised and authorized to send.
 *
 * @since 4.10.0
 */
class Preflight extends ApiPreflightAbstract {

	/**
	 * Sending domains, carrying each domain's verification state and its two capabilities.
	 *
	 * @since 4.10.0
	 *
	 * @var string
	 */
	private const API_DOMAINS = 'https://api.resend.com/domains';

	/**
	 * Resend's documented name for a credential refused on its own. No malformed key produced it live.
	 *
	 * @since 4.10.0
	 *
	 * @var string
	 */
	private const ERROR_INVALID_KEY = 'invalid_api_key';

	/**
	 * Resend's catch-all name. Every malformed key produces it at 400, and it is also how three
	 * unrelated domain refusals arrive at 403, so it names nothing on its own.
	 *
	 * @since 4.10.0
	 *
	 * @var string
	 */
	private const ERROR_VALIDATION = 'validation_error';

	/**
	 * The `validation_error` message that means the key itself was refused, and the only thing
	 * separating it from the 403 domain refusals.
	 *
	 * @since 4.10.0
	 *
	 * @var string
	 */
	private const MESSAGE_INVALID_KEY = 'API key is invalid';

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
			self::API_DOMAINS,
			[ 'headers' => [ 'Authorization' => 'Bearer ' . $config['api_key'] ] ]
		);
		$body     = $this->json( $response );

		if ( $this->is_rejected_key( $response, $body ) ) {
			$findings->add( $this->error( Code::AUTH_FAILED, 'api_key' ) );
		}

		return $findings;
	}

	/**
	 * Whether Resend refused the credential itself.
	 *
	 * @since 4.10.0
	 *
	 * @param array      $response Response to judge.
	 * @param array|null $body     Decoded body.
	 *
	 * @return bool
	 */
	private function is_rejected_key( $response, $body ) {

		$name = $this->error_name( $body );

		// `validation_error` is Resend's catch-all, and at 403 it carries a domain refusal a working key
		// produced, so blaming `api_key` needs the status and the message too.
		return $name === self::ERROR_INVALID_KEY
			|| (
				$name === self::ERROR_VALIDATION
				&& $this->status( $response ) === 400
				&& isset( $body['message'] ) && $body['message'] === self::MESSAGE_INVALID_KEY
			);
	}

	/**
	 * Read Resend's own machine-readable error name.
	 *
	 * @since 4.10.0
	 *
	 * @param array|null $body Decoded body.
	 *
	 * @return string Empty when the envelope carried no name.
	 */
	private function error_name( $body ) {

		return isset( $body['name'] ) && is_string( $body['name'] ) ? $body['name'] : '';
	}
}
