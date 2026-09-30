<?php

namespace WPMailSMTP\Providers\Mailgun;

use WPMailSMTP\Providers\Preflight\ApiPreflightAbstract;
use WPMailSMTP\Providers\Preflight\Code;
use WPMailSMTP\Providers\Preflight\Findings;

/**
 * Reads the configured Mailgun domain to establish whether the key authenticates and the
 * domain exists on the account in the configured region.
 *
 * @since 4.10.0
 */
class Preflight extends ApiPreflightAbstract {

	/**
	 * Mailgun's wording for a domain the key's account does not hold, on the host that was asked.
	 *
	 * @since 4.10.0
	 *
	 * @var string
	 */
	private const ERROR_DOMAIN_ABSENT = 'Domain not found';

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
			'domain'  => sanitize_text_field( (string) ( $config['domain'] ?? '' ) ),
			'region'  => sanitize_text_field( (string) ( $config['region'] ?? '' ) ),
		];

		if ( $sanitized_config['api_key'] === '' || $sanitized_config['domain'] === '' ) {
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
		$response = $this->fetch_domain( $config, $this->base_for( $config ) );

		// Mailgun answers 401 for a rejected key and for an absent header alike, and reserves 403 for
		// authorization, which this check is not asking about.
		if ( $this->status( $response ) === 401 ) {
			$findings->add( $this->error( Code::AUTH_FAILED, 'api_key' ) );

			return $findings;
		}

		if ( $this->says_domain_absent( $response ) ) {
			return $this->resolve_missing_domain( $config );
		}

		return $findings;
	}

	/**
	 * Whether Mailgun said the domain is not on the host that was asked.
	 *
	 * Both a domain the account does not hold and a domain held in the other region answer with this
	 * one wording, so the literal is what the retry then separates.
	 *
	 * @since 4.10.0
	 *
	 * @param array $response Response to inspect.
	 *
	 * @return bool
	 */
	private function says_domain_absent( $response ) {

		$body = $this->status( $response ) === 404 ? $this->json( $response ) : null;

		return isset( $body['message'] ) && $body['message'] === self::ERROR_DOMAIN_ABSENT;
	}

	/**
	 * Separate a domain the account does not hold from one held in the other region.
	 *
	 * @since 4.10.0
	 *
	 * @param array $config Mailer options.
	 *
	 * @return Findings
	 */
	private function resolve_missing_domain( $config ) {

		$findings = Findings::none();
		$other    = $this->fetch_domain( $config, $this->other_base_for( $config ) );
		$body     = $this->status( $other ) === 200 ? $this->json( $other ) : null;

		// A bare 200 is not a domain read: this host serves non-JSON edge pages.
		if ( ! empty( $body['domain'] ) && is_array( $body['domain'] ) ) {
			$findings->add( $this->error( Code::REGION_MISMATCH, 'region' ) );

			return $findings;
		}

		$findings->add( $this->error( Code::DOMAIN_NOT_FOUND, 'domain' ) );

		return $findings;
	}

	/**
	 * Read the domain details from one region host.
	 *
	 * @since 4.10.0
	 *
	 * @param array  $config Mailer options.
	 * @param string $base   API base URL.
	 *
	 * @return array
	 */
	private function fetch_domain( $config, $base ) {

		return $this->request(
			$base . 'domains/' . rawurlencode( $config['domain'] ),
			[ 'headers' => $this->headers( $config ) ]
		);
	}

	/**
	 * Build the auth header.
	 *
	 * @since 4.10.0
	 *
	 * @param array $config Mailer options.
	 *
	 * @return array
	 */
	private function headers( $config ) {

		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		$credential = base64_encode( 'api:' . $config['api_key'] );

		return [ 'Authorization' => 'Basic ' . $credential ];
	}

	/**
	 * Get the base URL for the configured region.
	 *
	 * @since 4.10.0
	 *
	 * @param array $config Mailer options.
	 *
	 * @return string
	 */
	private function base_for( $config ) {

		return $this->is_eu( $config ) ? Mailer::API_BASE_EU : Mailer::API_BASE_US;
	}

	/**
	 * Get the base URL for the region that was not configured.
	 *
	 * @since 4.10.0
	 *
	 * @param array $config Mailer options.
	 *
	 * @return string
	 */
	private function other_base_for( $config ) {

		return $this->is_eu( $config ) ? Mailer::API_BASE_US : Mailer::API_BASE_EU;
	}

	/**
	 * Whether the EU region is configured.
	 *
	 * @since 4.10.0
	 *
	 * @param array $config Mailer options.
	 *
	 * @return bool
	 */
	private function is_eu( $config ) {

		return $config['region'] === 'EU';
	}
}
