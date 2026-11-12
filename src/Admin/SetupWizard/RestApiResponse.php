<?php

namespace WPMailSMTP\Admin\SetupWizard;

use WP_Error;

/**
 * Shared response envelope for setup wizard REST handlers.
 *
 * Used by the Lite REST controller and by Pro route contributors so both
 * format success and error responses identically without depending on each
 * other.
 *
 * @since 4.10.0
 */
trait RestApiResponse {

	/**
	 * Output response.
	 *
	 * @since 4.10.0
	 *
	 * @param mixed $data    Response data.
	 * @param bool  $success Response status.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function response( $data = [], $success = true ) {

		$envelope = [
			'success' => $success,
			'data'    => $data,
		];
		$context  = Transport::current();

		if ( $context === null ) {
			return rest_ensure_response( $envelope );
		}

		Transport::end();

		$plaintext = wp_json_encode( $envelope );

		if ( $plaintext === false ) {
			return $this->error( __( 'The response could not be encoded.', 'wp-mail-smtp' ), 500 );
		}

		$nonce = bin2hex( random_bytes( 16 ) );

		return rest_ensure_response(
			[
				'n' => $nonce,
				'c' => Transport::seal( $context['token'], $plaintext, Transport::response_ad( $context['endpoint'], $context['nonce'] ), $nonce ),
			]
		);
	}

	/**
	 * Output error.
	 *
	 * @since 4.10.0
	 *
	 * @param string $message     Error message.
	 * @param int    $status_code Error status code.
	 * @param string $error_code  Machine-readable code the client can surface or branch on.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function error( $message, $status_code = 200, $error_code = '' ) {

		if ( $status_code !== 200 ) {
			Transport::end();

			$error_data = [ 'status' => $status_code ];

			if ( $error_code !== '' ) {
				$error_data['error_code'] = $error_code;
			}

			// The WP_Error code stays 'auth' so the HTTP error shape does not
			// change; the reason rides in data.error_code alongside the status.
			return new WP_Error( 'auth', $message, $error_data );
		}

		$data = [ 'message' => $message ];

		if ( $error_code !== '' ) {
			$data['error_code'] = $error_code;
		}

		return $this->response( $data, false );
	}
}
