<?php

namespace WPMailSMTP\Providers\SparkPost;

use WPMailSMTP\Providers\Preflight\ApiPreflightAbstract;
use WPMailSMTP\Providers\Preflight\Code;
use WPMailSMTP\Providers\Preflight\Findings;

/**
 * Reads the SparkPost account on the selected region host to establish whether the key
 * authenticates, and on which of the two regions.
 *
 * @since 4.10.0
 */
class Preflight extends ApiPreflightAbstract {

	/**
	 * Account details.
	 *
	 * @since 4.10.0
	 *
	 * @var string
	 */
	private const ACCOUNT_PATH = '/account';

	/**
	 * Read the fields this check sends.
	 *
	 * An absent region takes the US host, exactly as the send path does.
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
			'region'  => sanitize_text_field( (string) ( $config['region'] ?? '' ) ),
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

		$response = $this->fetch_account( $config, $this->base_for( $config ) );

		if ( $this->status( $response ) === 401 ) {
			return $this->resolve_rejected_credential( $config );
		}

		return Findings::none();
	}

	/**
	 * Separate a wrong key from a wrong region.
	 *
	 * A 401 is identical in status, headers, body and latency for both, and SparkPost partitions
	 * authentication by region, so a key authenticates on exactly one of the two hosts.
	 *
	 * @since 4.10.0
	 *
	 * @param array $config Mailer options.
	 *
	 * @return Findings
	 */
	private function resolve_rejected_credential( $config ) {

		$findings = Findings::none();
		$other    = $this->fetch_account( $config, $this->other_base_for( $config ) );
		$body     = $this->status( $other ) === 200 ? $this->json( $other ) : null;

		// A bare 200 is not an account read: an edge page in front of the host would serve one.
		if ( isset( $body['results'] ) && is_array( $body['results'] ) ) {
			$findings->add( $this->error( Code::REGION_MISMATCH, 'region' ) );

			return $findings;
		}

		$findings->add( $this->error( Code::AUTH_FAILED, 'api_key' ) );

		return $findings;
	}

	/**
	 * Read the account on one host.
	 *
	 * @since 4.10.0
	 *
	 * @param array  $config Mailer options.
	 * @param string $base   API base URL.
	 *
	 * @return array
	 */
	private function fetch_account( $config, $base ) {

		return $this->request(
			$base . self::ACCOUNT_PATH,
			[ 'headers' => [ 'Authorization' => $config['api_key'] ] ]
		);
	}

	/**
	 * Get the base for the configured region.
	 *
	 * The comparison is the Mailer's own strict one, so every value but `EU` reaches the US host
	 * on the probe exactly as it does on the send path.
	 *
	 * @since 4.10.0
	 *
	 * @param array $config Mailer options.
	 *
	 * @return string
	 */
	private function base_for( $config ) {

		return $config['region'] === 'EU' ? Mailer::API_BASE_EU : Mailer::API_BASE_US;
	}

	/**
	 * Get the base the configured region did not select.
	 *
	 * @since 4.10.0
	 *
	 * @param array $config Mailer options.
	 *
	 * @return string
	 */
	private function other_base_for( $config ) {

		return $this->base_for( $config ) === Mailer::API_BASE_EU ? Mailer::API_BASE_US : Mailer::API_BASE_EU;
	}
}
