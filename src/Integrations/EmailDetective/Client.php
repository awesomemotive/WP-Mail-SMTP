<?php

namespace WPMailSMTP\Integrations\EmailDetective;

use WP_Error;

/**
 * The Email Detective API client.
 *
 * @since 4.10.0
 */
class Client {

	/**
	 * The default API base URL.
	 *
	 * @since 4.10.0
	 */
	private const API_BASE_URL = 'https://detective.sendlayer.com/api/v1';

	/**
	 * The option the site token is stored under.
	 *
	 * @since 4.10.0
	 */
	private const TOKEN_OPTION = 'wp_mail_smtp_email_detective_site_token';

	/**
	 * Register this site and create a new test in one call.
	 *
	 * @since 4.10.0
	 *
	 * @return array|WP_Error {@type string $test_id, string $email_address, string $report_url}
	 */
	public function create_test() {

		if ( ! $this->get_token() ) {
			$registered = $this->register();

			if ( is_wp_error( $registered ) ) {
				return $registered;
			}
		}

		$result = $this->request( 'POST', '/tests', [ 'client_meta' => Meta::collect() ] );

		// A 401 means the stored token was invalidated server-side: re-register once and retry.
		if ( $this->is_unauthorized( $result ) ) {
			delete_option( self::TOKEN_OPTION );

			$registered = $this->register();

			if ( is_wp_error( $registered ) ) {
				return $registered;
			}

			$result = $this->request( 'POST', '/tests', [ 'client_meta' => Meta::collect() ] );
		}

		return $this->require_keys( $result, [ 'test_id', 'email_address', 'report_url' ] );
	}

	/**
	 * Poll the current status of a test.
	 *
	 * @since 4.10.0
	 *
	 * @param string $test_id The test's slug.
	 *
	 * @return array|WP_Error {@type string $test_id, string $status, mixed $score,
	 *                         string $summary, string $report_url,
	 *                         int $checks_completed, int $checks_total}
	 */
	public function get_status( $test_id ) {

		return $this->require_keys( $this->request( 'GET', '/tests/' . $test_id ), [ 'status' ] );
	}

	/**
	 * Report whether the local send succeeded, and why not if it did not.
	 *
	 * @since 4.10.0
	 *
	 * @param string $test_id   The test's slug.
	 * @param bool   $succeeded Whether the local send succeeded.
	 * @param string $error     The local error message, when it did not.
	 *
	 * @return array|WP_Error
	 */
	public function report_send_result( $test_id, $succeeded, $error = '' ) {

		return $this->request(
			'POST',
			'/tests/' . $test_id . '/send-result',
			[
				'succeeded' => (bool) $succeeded,
				'mailer'    => Meta::collect()['mailer'],
				'error'     => $error,
			]
		);
	}

	/**
	 * Submit the lead-capture email address to unlock the full report.
	 *
	 * @since 4.10.0
	 *
	 * @param string $test_id         The test's slug.
	 * @param string $email           The email address to send the report to.
	 * @param string $consent_wording The exact consent copy the user agreed to.
	 *
	 * @return array|WP_Error {@type bool $sent, bool $unlocked}
	 */
	public function submit_lead( $test_id, $email, $consent_wording ) {

		return $this->request(
			'POST',
			'/tests/' . $test_id . '/lead',
			[
				'email'           => $email,
				'consent'         => true,
				'consent_wording' => $consent_wording,
			]
		);
	}

	/**
	 * Register this site and persist the returned site token.
	 *
	 * @since 4.10.0
	 *
	 * @return array|WP_Error {@type string $site_token, string $site_url}
	 */
	private function register() {

		$result = $this->require_keys(
			$this->request( 'POST', '/register', [ 'site_url' => home_url() ], false ),
			[ 'site_token' ]
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		update_option( self::TOKEN_OPTION, [ 'site_token' => $result['site_token'] ], false );

		return $result;
	}

	/**
	 * Reject a 2xx response that is missing a field the callers index into.
	 *
	 * @since 4.10.0
	 *
	 * @param array|WP_Error $result A request() return value.
	 * @param array          $keys   The keys the response must carry.
	 *
	 * @return array|WP_Error
	 */
	private function require_keys( $result, array $keys ) {

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		foreach ( $keys as $key ) {
			if ( ! isset( $result[ $key ] ) ) {
				return new WP_Error( 'api_error', '', [ 'status' => 0 ] );
			}
		}

		return $result;
	}

	/**
	 * Whether a request() result is a 401.
	 *
	 * @since 4.10.0
	 *
	 * @param array|WP_Error $result A request() / register() return value.
	 *
	 * @return bool
	 */
	private function is_unauthorized( $result ) {

		if ( ! is_wp_error( $result ) ) {
			return false;
		}

		// The status, not the error code: a 401's code is whatever the server put in the body.
		$data = $result->get_error_data();

		return is_array( $data ) && isset( $data['status'] ) && $data['status'] === 401;
	}

	/**
	 * The stored site token, if any.
	 *
	 * @since 4.10.0
	 *
	 * @return string
	 */
	private function get_token() {

		$stored = get_option( self::TOKEN_OPTION, [] );

		return isset( $stored['site_token'] ) ? (string) $stored['site_token'] : '';
	}

	/**
	 * The API base URL.
	 *
	 * @since 4.10.0
	 *
	 * @return string
	 */
	private function base_url() {

		/**
		 * Filters the Email Detective API base URL.
		 *
		 * @since 4.10.0
		 *
		 * @param string $url API base URL.
		 */
		return untrailingslashit( apply_filters( 'wp_mail_smtp_integrations_email_detective_client_base_url', self::API_BASE_URL ) );
	}

	/**
	 * Make a request against the Email Detective API.
	 *
	 * @since 4.10.0
	 *
	 * @param string $method     HTTP method.
	 * @param string $path       Path relative to the API base URL, e.g. '/tests'.
	 * @param array  $body       Request body, JSON-encoded when non-empty.
	 * @param bool   $with_token Whether to send the stored site token header.
	 *                           Registration is the only call made without one.
	 *
	 * @return array|WP_Error
	 */
	private function request( $method, $path, array $body = [], $with_token = true ) { // phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh -- linear status-to-WP_Error mapping.

		$headers = [ 'Content-Type' => 'application/json' ];

		if ( $with_token ) {
			$headers['X-Site-Token'] = $this->get_token();
		}

		$args = [
			'method'  => $method,
			'timeout' => 15,
			'headers' => $headers,
		];

		if ( ! empty( $body ) ) {
			$args['body'] = wp_json_encode( $body );
		}

		$response = wp_remote_request( $this->base_url() . $path, $args );

		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'http_error', $response->get_error_message() );
		}

		$code = wp_remote_retrieve_response_code( $response );
		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		$data = is_array( $data ) ? $data : [];

		if ( $code >= 200 && $code < 300 ) {
			return $data;
		}

		$message = isset( $data['message'] ) ? $data['message'] : '';

		if ( $code === 429 ) {
			return new WP_Error(
				'rate_limited',
				$message,
				[
					'retry_after' => (int) wp_remote_retrieve_header( $response, 'retry-after' ),
					'status'      => $code,
				]
			);
		}

		if ( $code === 503 ) {
			return new WP_Error( 'service_unavailable', $message, [ 'status' => $code ] );
		}

		$error_code = ! empty( $data['error'] ) ? $data['error'] : 'api_error';

		return new WP_Error( $error_code, $message, [ 'status' => $code ] );
	}
}
