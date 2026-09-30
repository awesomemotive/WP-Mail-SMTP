<?php

namespace WPMailSMTP\Providers\SMTPcom;

use WPMailSMTP\Providers\Preflight\ApiPreflightAbstract;
use WPMailSMTP\Providers\Preflight\Code;
use WPMailSMTP\Providers\Preflight\Findings;

/**
 * Reads the configured SMTP.com channel to establish whether the key is accepted and the channel
 * is one this account holds.
 *
 * @since 4.10.0
 */
class Preflight extends ApiPreflightAbstract {

	/**
	 * Channel-details endpoint. The channel name completes the path and nothing follows it: a
	 * trailing slash reaches the list endpoint instead.
	 *
	 * @since 4.10.0
	 *
	 * @var string
	 */
	private const API_CHANNEL = 'https://api.smtp.com/v4/channels/';

	/**
	 * The channel is not on this account. Also covers wrong case, stray whitespace, and an
	 * archived channel, which this endpoint does not return.
	 *
	 * @since 4.10.0
	 *
	 * @var string
	 */
	private const CHANNEL_NOT_FOUND = 'smtpcom_channel_not_found';

	/**
	 * SMTP.com's wording under `data.api_key` for a credential it refused.
	 *
	 * @since 4.10.0
	 *
	 * @var string
	 */
	private const MESSAGE_KEY_INVALID = 'invalid';

	/**
	 * SMTP.com's wording under `data.error` for a channel this account does not hold. It answers the
	 * same for a wrong case and for stray whitespace.
	 *
	 * @since 4.10.0
	 *
	 * @var string
	 */
	private const MESSAGE_CHANNEL_MISSING = 'sender not found';

	/**
	 * Read the fields this check sends.
	 *
	 * An absent channel gets its own verdict naming the Channel field, so it does not stop the
	 * run here.
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
			'channel' => sanitize_text_field( (string) ( $config['channel'] ?? '' ) ),
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
		$channel  = $config['channel'];

		// An empty name leaves the trailing slash that reaches the channel list, which answers 200.
		if ( $channel === '' ) {
			$findings->add( $this->error( self::CHANNEL_NOT_FOUND, 'channel' ) );

			return $findings;
		}

		$body = $this->json( $this->fetch_channel( $config, $channel ) );

		if ( $this->fault( $body, 'api_key' ) === self::MESSAGE_KEY_INVALID ) {
			$findings->add( $this->error( Code::AUTH_FAILED, 'api_key' ) );

			return $findings;
		}

		// A path we built wrong answers the same 404, but under `url`, so only this one is the channel.
		if ( $this->fault( $body, 'error' ) === self::MESSAGE_CHANNEL_MISSING ) {
			$findings->add( $this->error( self::CHANNEL_NOT_FOUND, 'channel' ) );
		}

		return $findings;
	}

	/**
	 * Read one member of SMTP.com's `data` object.
	 *
	 * There is no error code anywhere in the envelope, so the member name says which fault it is and
	 * the value says whether it is that fault.
	 *
	 * @since 4.10.0
	 *
	 * @param array|null $body Decoded body.
	 * @param string     $name Member to read.
	 *
	 * @return string Empty when the body carries no such member.
	 */
	private function fault( $body, $name ) {

		$value = isset( $body['data'][ $name ] ) ? $body['data'][ $name ] : '';

		return is_string( $value ) ? $value : '';
	}

	/**
	 * Read the channel details.
	 *
	 * @since 4.10.0
	 *
	 * @param array  $config  Mailer options.
	 * @param string $channel Trimmed channel name.
	 *
	 * @return array
	 */
	private function fetch_channel( $config, $channel ) {

		return $this->request(
			self::API_CHANNEL . rawurlencode( $channel ),
			[ 'headers' => [ 'Authorization' => 'Bearer ' . $config['api_key'] ] ]
		);
	}
}
