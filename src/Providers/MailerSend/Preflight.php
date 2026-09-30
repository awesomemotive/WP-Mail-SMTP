<?php

namespace WPMailSMTP\Providers\MailerSend;

use WPMailSMTP\Providers\Preflight\ApiPreflightAbstract;
use WPMailSMTP\Providers\Preflight\Code;
use WPMailSMTP\Providers\Preflight\Findings;

/**
 * Calls MailerSend's API-quota read to establish whether the token works.
 *
 * @since 4.10.0
 */
class Preflight extends ApiPreflightAbstract {

	/**
	 * Daily API request allowance. No token scope name gates it, and it is documented as exempt from
	 * the quota it reports.
	 *
	 * @since 4.10.0
	 *
	 * @var string
	 */
	private const API_QUOTA = 'https://api.mailersend.com/v1/api-quota';

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
		$response = $this->fetch( self::API_QUOTA, $config );

		// MailerSend answers 401 for a wrong token, an empty one, a padded one and an absent header
		// alike, and reserves 403 for authorization.
		if ( $this->status( $response ) === 401 ) {
			$findings->add( $this->error( Code::AUTH_FAILED, 'api_key' ) );
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

		return $this->request(
			$url,
			[ 'headers' => [ 'Authorization' => 'Bearer ' . $config['api_key'] ] ]
		);
	}
}
