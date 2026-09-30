<?php

namespace WPMailSMTP\Providers\Mandrill;

use WPMailSMTP\Providers\Preflight\ApiPreflightAbstract;
use WPMailSMTP\Providers\Preflight\Code;
use WPMailSMTP\Providers\Preflight\Findings;

/**
 * Validates the Mandrill key against the ping endpoint.
 *
 * Mandrill's reads are POSTs and the credential is a body parameter, which is why the call is a
 * POST and is not a dispatch: the path is the RPC method name, so no body parameter can redirect
 * one method to another.
 *
 * @since 4.10.0
 */
class Preflight extends ApiPreflightAbstract {

	/**
	 * Key validation. The object-envelope variant, because `/users/ping` answers a bare JSON string
	 * that decodes to a PHP string and is rejected as non-JSON everywhere in the plugin.
	 *
	 * @since 4.10.0
	 *
	 * @var string
	 */
	private const API_PING = 'https://mandrillapp.com/api/1.0/users/ping2';

	/**
	 * The name Mandrill answers for a rejected key and for its per-key IP refusal alike, so it marks
	 * the envelope and does not carry the verdict.
	 *
	 * @since 4.10.0
	 *
	 * @var string
	 */
	private const ERROR_INVALID_KEY = 'Invalid_Key';

	/**
	 * The message that refuses the credential itself.
	 *
	 * The IP refusal sharing this name names the address it saw, so a fragment is what stays stable.
	 *
	 * @since 4.10.0
	 *
	 * @var string
	 */
	private const MESSAGE_REJECTED = 'invalid api key';

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
		$body     = $this->json( $this->post( self::API_PING, $config ) );

		if (
			$this->error_name( $body ) === self::ERROR_INVALID_KEY
			&& strpos( $this->message( $body ), self::MESSAGE_REJECTED ) !== false
		) {
			$findings->add( $this->error( Code::AUTH_FAILED, 'api_key' ) );
		}

		return $findings;
	}

	/**
	 * Get Mandrill's own error name, if the body carries one.
	 *
	 * @since 4.10.0
	 *
	 * @param array|null $body Decoded body.
	 *
	 * @return string
	 */
	private function error_name( $body ) {

		return isset( $body['name'] ) && is_string( $body['name'] ) ? $body['name'] : '';
	}

	/**
	 * Read Mandrill's own error prose, lowercased for matching.
	 *
	 * @since 4.10.0
	 *
	 * @param array|null $body Decoded body.
	 *
	 * @return string Empty when the body carried no message.
	 */
	private function message( $body ) {

		$message = isset( $body['message'] ) ? $body['message'] : '';

		return is_string( $message ) ? strtolower( $message ) : '';
	}

	/**
	 * Issue one bounded read.
	 *
	 * @since 4.10.0
	 *
	 * @param string $url    Absolute URL.
	 * @param array  $config Mailer options.
	 *
	 * @return array
	 */
	private function post( $url, $config ) {

		return $this->request(
			$url,
			[
				'method'  => 'POST',
				'headers' => [ 'Content-Type' => 'application/json' ],
				'body'    => wp_json_encode( [ 'key' => $config['api_key'] ] ),
			]
		);
	}
}
