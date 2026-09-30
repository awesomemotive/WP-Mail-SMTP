<?php

namespace WPMailSMTP\Providers\Mailjet;

use WPMailSMTP\Providers\Preflight\ApiPreflightAbstract;
use WPMailSMTP\Providers\Preflight\Code;
use WPMailSMTP\Providers\Preflight\Findings;

/**
 * Reads the Mailjet sender list to establish whether the credential pair works.
 *
 * @since 4.10.0
 */
class Preflight extends ApiPreflightAbstract {

	/**
	 * Senders on the authenticating API key, counted rather than listed: the records carry the
	 * account's own email addresses and nothing here reads them.
	 *
	 * @since 4.10.0
	 *
	 * @var string
	 */
	private const API_SENDER = 'https://api.mailjet.com/v3/REST/sender?CountOnly=1';

	/**
	 * The two halves are base64'd into one header value, so no response can say which is wrong.
	 *
	 * @since 4.10.0
	 *
	 * @var string[]
	 */
	private const CREDENTIAL_FIELDS = [ 'api_key', 'secret_key' ];

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
			'api_key'    => sanitize_text_field( (string) ( $config['api_key'] ?? '' ) ),
			'secret_key' => sanitize_text_field( (string) ( $config['secret_key'] ?? '' ) ),
		];

		if ( $sanitized_config['api_key'] === '' || $sanitized_config['secret_key'] === '' ) {
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
		$response = $this->fetch( self::API_SENDER, $config );

		// A rejected pair answers 401 with a zero-length body, so neither value can be named alone. A 401
		// carrying an envelope was refused for another reason, a temporarily blocked account among them,
		// and leaves both halves valid.
		if ( $this->status( $response ) === 401 && trim( $this->body( $response ) ) === '' ) {
			$findings->add( $this->error( Code::AUTH_FAILED, self::CREDENTIAL_FIELDS ) );
		}

		return $findings;
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
	private function fetch( $url, $config ) {

		$pair = $config['api_key'] . ':' . $config['secret_key'];

		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		$credential = base64_encode( $pair );

		return $this->request( $url, [ 'headers' => [ 'Authorization' => 'Basic ' . $credential ] ] );
	}
}
