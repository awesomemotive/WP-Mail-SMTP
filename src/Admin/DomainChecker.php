<?php

namespace WPMailSMTP\Admin;

/**
 * Class for interacting with the Domain Checker API.
 *
 * @since 2.6.0
 */
class DomainChecker {

	/**
	 * The domain checker API endpoint.
	 *
	 * @since 2.6.0
	 */
	const ENDPOINT = 'https://connect.wpmailsmtp.com/domain-check/';

	/**
	 * The API results.
	 *
	 * @since 2.6.0
	 *
	 * @var array
	 */
	private $results;

	/**
	 * The plugin mailer slug.
	 *
	 * @since 2.7.0
	 *
	 * @var string
	 */
	protected $mailer;

	/**
	 * Verify the domain for the provided mailer and email address and save the API results.
	 *
	 * @since 2.6.0
	 *
	 * @param string $mailer         The plugin mailer.
	 * @param string $email          The email address from which the domain will be extracted.
	 * @param string $sending_domain The optional sending domain to check the domain records for.
	 */
	public function __construct( $mailer, $email, $sending_domain = '' ) {

		$this->mailer = $mailer;

		$params = [
			'mailer' => $mailer,
			'email'  => base64_encode( $email ), // phpcs:ignore
			'domain' => $sending_domain,
		];

		$response = wp_remote_get( add_query_arg( $params, self::ENDPOINT ) );

		if ( is_wp_error( $response ) ) {
			$this->results = [
				'success' => false,
				'message' => method_exists( $response, 'get_error_message' ) ?
					$response->get_error_message() :
					esc_html__( 'Something went wrong. Please try again later.', 'wp-mail-smtp' ),
				'checks'  => [],
			];
		} else {
			$this->results = json_decode( wp_remote_retrieve_body( $response ), true );
		}
	}

	/**
	 * Simple getter for the API results.
	 *
	 * @since 2.6.0
	 *
	 * @return array
	 */
	public function get_results() {
		return $this->results;
	}

	/**
	 * Check if the domain checker has found any errors.
	 *
	 * @since 2.6.0
	 *
	 * @return bool
	 */
	public function has_errors() {

		if ( empty( $this->results['success'] ) ) {
			return true;
		}

		if ( empty( $this->results['checks'] ) ) {
			return false;
		}

		$has_error = false;

		foreach ( $this->results['checks'] as $check ) {
			if ( $check['state'] === 'error' ) {
				$has_error = true;
				break;
			}
		}

		return $has_error;
	}

	/**
	 * Check if the domain checker has not found any errors or warnings.
	 *
	 * @since 2.6.0
	 *
	 * @return bool
	 */
	public function no_issues() {

		if ( empty( $this->results['success'] ) ) {
			return false;
		}

		$no_issues = true;

		foreach ( $this->results['checks'] as $check ) {
			if ( in_array( $check['state'], [ 'error', 'warning' ], true ) ) {
				$no_issues = false;
				break;
			}
		}

		return $no_issues;
	}

	/**
	 * Check if the domain checker support mailer.
	 *
	 * @since 2.7.0
	 *
	 * @return bool
	 */
	public function is_supported_mailer() {

		return ! in_array( $this->mailer, [ 'mail', 'pepipostapi' ], true );
	}
}
