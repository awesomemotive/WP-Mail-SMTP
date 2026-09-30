<?php

namespace WPMailSMTP\Providers\Preflight;

use WPMailSMTP\Helpers\Helpers;

/**
 * Shared HTTP behaviour for the API-based preflights.
 *
 * **A check may only name a field when the provider itself said so.** Anything between the site and
 * the provider host answers with whatever status and body it likes, so a verdict drawn from a status
 * alone puts a red outline on a value that is correct. Two signals establish the provider spoke, and
 * nothing in front of one produces either:
 *
 * - a **401**, which needs a challenge from the origin, and is all Mailjet ever gives; its 401 body
 *   is zero-length;
 * - the provider's **own error vocabulary** in the body: a code, a name, or an exact literal.
 *
 * A check with neither names nothing and passes. A check that made no request at all, because it
 * rejected a typed value locally, has nothing to attribute and stays confident.
 *
 * Recognising a *success* is a separate job and stays per endpoint, since a check that reads two
 * endpoints sees two shapes.
 *
 * @since 4.10.0
 */
abstract class ApiPreflightAbstract extends PreflightAbstract {

	/**
	 * Run the check, ending it at a response that carries no verdict.
	 *
	 * @since 4.10.0
	 *
	 * @param array $config Mailer options keyed by the wizard's field names.
	 *
	 * @return Findings
	 */
	final public function run( $config ) {

		$sanitized_config = $this->sanitize_config( $config );

		if ( $sanitized_config === null ) {
			return $this->incomplete_config();
		}

		try {
			return $this->probe( $sanitized_config );
		} catch ( TransportFailure $failure ) {
			$findings = Findings::none();

			$findings->add( $failure->get_finding() );

			return $findings;
		}
	}

	/**
	 * Establish what one configuration can send.
	 *
	 * @since 4.10.0
	 *
	 * @param array $config Mailer options.
	 *
	 * @return Findings
	 */
	abstract protected function probe( $config );

	/**
	 * Issue a bounded read request, classified before the caller sees it.
	 *
	 * @since 4.10.0
	 *
	 * @param string $url  Absolute URL.
	 * @param array  $args Overrides merged over the defaults.
	 *
	 * @return array
	 *
	 * @throws TransportFailure When the response carries no verdict about the configuration.
	 */
	protected function request( $url, $args = [] ) {

		$headers = isset( $args['headers'] ) ? $args['headers'] : [];

		$args['headers'] = is_array( $headers ) ? $this->merge_headers( $headers ) : $headers;

		$response = wp_remote_request(
			$url,
			array_merge(
				[
					'method'     => 'GET',
					'timeout'    => self::TIMEOUT,
					'user-agent' => Helpers::get_default_user_agent(),
				],
				$args
			)
		);

		$finding = $this->transport_finding( $response );

		if ( $finding !== null ) {
			throw new TransportFailure( $finding ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}

		return $response;
	}

	/**
	 * Lay a caller's headers over the defaults instead of replacing them.
	 *
	 * A default is dropped rather than duplicated when the caller names it in any case, since
	 * header names are case-insensitive on the wire.
	 *
	 * @since 4.10.0
	 *
	 * @param array $headers Caller headers.
	 *
	 * @return array
	 */
	private function merge_headers( $headers ) {

		$defaults = [ 'Accept' => 'application/json' ];
		$named    = array_map( 'strtolower', array_keys( $headers ) );

		foreach ( array_keys( $defaults ) as $name ) {
			if ( in_array( strtolower( $name ), $named, true ) ) {
				unset( $defaults[ $name ] );
			}
		}

		return array_merge( $defaults, $headers );
	}

	/**
	 * Get the HTTP status.
	 *
	 * @since 4.10.0
	 *
	 * @param array $response Response to inspect.
	 *
	 * @return int
	 */
	protected function status( $response ) {

		return (int) wp_remote_retrieve_response_code( $response );
	}

	/**
	 * Get the raw response body.
	 *
	 * @since 4.10.0
	 *
	 * @param array $response Response to read.
	 *
	 * @return string
	 */
	protected function body( $response ) {

		return (string) wp_remote_retrieve_body( $response );
	}

	/**
	 * Whether the response body decodes to the provider's own JSON envelope.
	 *
	 * The Content-Type is not consulted, so JSON served under any type still parses.
	 *
	 * @since 4.10.0
	 *
	 * @param array $response Response to inspect.
	 *
	 * @return bool
	 */
	protected function is_json( $response ) {

		return $this->json( $response ) !== null;
	}

	/**
	 * Decode the response body.
	 *
	 * @since 4.10.0
	 *
	 * @param array $response Response to decode.
	 *
	 * @return array|null
	 */
	protected function json( $response ) {

		$decoded = json_decode( wp_remote_retrieve_body( $response ), true );

		return is_array( $decoded ) ? $decoded : null;
	}

	/**
	 * Classify a response the provider check cannot use.
	 *
	 * @since 4.10.0
	 *
	 * @param array|\WP_Error $response Response to classify.
	 *
	 * @return Finding|null Null when the response is usable by a provider check.
	 */
	protected function transport_finding( $response ) {

		if ( is_wp_error( $response ) ) {
			return $this->inconclusive( Code::NETWORK_ERROR );
		}

		$status = $this->status( $response );

		if ( $status === 429 ) {
			return $this->inconclusive( Code::RATE_LIMITED );
		}

		if ( $status >= 500 ) {
			return $this->inconclusive( Code::PROVIDER_UNAVAILABLE );
		}

		return null;
	}
}
