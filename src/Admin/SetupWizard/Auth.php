<?php

namespace WPMailSMTP\Admin\SetupWizard;

use WP_Error;

/**
 * Class Auth.
 *
 * @since 4.10.0
 */
class Auth {

	/**
	 * Token transient name.
	 *
	 * Stores an array: [ 'token' => string, 'user_id' => int, 'nonces' => array, 'issued_at' => int ].
	 * The nonces map spent request nonces to their expiry.
	 *
	 * @since 4.10.0
	 *
	 * @var string
	 */
	const TRANSIENT = 'wp_mail_smtp_setup_wizard_token';

	/**
	 * How long a request signature stays valid, measured from its timestamp.
	 *
	 * Doubles as the replay window for an intercepted request and the tolerance
	 * for clock drift between the hosted app and this site.
	 *
	 * @since 4.10.0
	 *
	 * @var int
	 */
	const SIGNATURE_WINDOW = 15 * MINUTE_IN_SECONDS;

	/**
	 * Error code for every signature failure that is not clock skew.
	 *
	 * One bucket: a rejected request must not reveal which check it tripped.
	 *
	 * @since 4.10.0
	 *
	 * @var string
	 */
	const ERROR_AUTH_FAILED = 'auth_failed';

	/**
	 * Error code for an authentically signed request whose timestamp is outside
	 * the signature window.
	 *
	 * @since 4.10.0
	 *
	 * @var string
	 */
	const ERROR_CLOCK_SKEW = 'clock_skew';

	/**
	 * How long a signing key stays valid from the moment it was minted, whatever the activity.
	 *
	 * @since 4.10.3
	 *
	 * @var int
	 */
	private const MAX_LIFETIME = 2 * HOUR_IN_SECONDS;

	/**
	 * Mint a new wizard signing key for the current user, retiring any previous one.
	 *
	 * Sent to the hosted wizard server to server, never through the browser. Bound
	 * to the issuing user because the signed requests carry no auth cookies and
	 * still have to resolve to an identity.
	 *
	 * The caller must check that the current user is permitted to launch the wizard.
	 *
	 * @since 4.10.0
	 *
	 * @return string Signing key, or empty string if there is no current
	 *                user to bind it to.
	 */
	public function get_token() {

		$user_id = get_current_user_id();

		if ( ! $user_id ) {
			return '';
		}

		$raw = hash( 'sha512', wp_rand() );

		set_transient(
			self::TRANSIENT,
			[
				'token'     => $raw,
				'user_id'   => $user_id,
				'nonces'    => [],
				'issued_at' => time(),
			],
			HOUR_IN_SECONDS
		);

		return hash_hmac( 'sha512', $raw, wp_salt() );
	}

	/**
	 * Verify a wizard request signature, returning the bound user ID so the caller
	 * can hydrate the request as that user.
	 *
	 * The signing key is never transmitted; the request carries an HMAC of its
	 * canonical payload instead. The timestamp bounds replay to the signature
	 * window, and the single-use nonce closes that window entirely.
	 *
	 * The HMAC is checked before the timestamp so an unauthenticated caller can
	 * never get the clock-skew code back. Recording the spent nonce also refreshes
	 * the token transient's rolling expiry, up to the key's MAX_LIFETIME.
	 *
	 * @since 4.10.0
	 *
	 * @param string $payload   Canonical signed payload, including the timestamp and nonce.
	 * @param int    $timestamp Request timestamp from the X-Timestamp header.
	 * @param string $nonce     Per-request nonce from the X-Nonce header.
	 * @param string $signature HMAC from the X-Signature header.
	 *
	 * @return int|WP_Error The bound user ID on success, a WP_Error carrying
	 *                      ERROR_CLOCK_SKEW or ERROR_AUTH_FAILED on failure.
	 */
	public function verify_signature( $payload, $timestamp, $nonce, $signature ) {

		if ( ! is_string( $signature ) || $signature === '' || ! is_string( $nonce ) || $nonce === '' ) {
			return new WP_Error( self::ERROR_AUTH_FAILED );
		}

		$stored = get_transient( self::TRANSIENT );

		if ( ! is_array( $stored ) || empty( $stored['token'] ) ) {
			return new WP_Error( self::ERROR_AUTH_FAILED );
		}

		$key      = hash_hmac( 'sha512', $stored['token'], wp_salt() );
		$expected = hash_hmac( 'sha512', $payload, $key );

		if ( ! hash_equals( $expected, $signature ) ) {
			return new WP_Error( self::ERROR_AUTH_FAILED );
		}

		if ( abs( time() - (int) $timestamp ) > self::SIGNATURE_WINDOW ) {
			return new WP_Error( self::ERROR_CLOCK_SKEW );
		}

		$user_id = (int) ( $stored['user_id'] ?? 0 );

		if ( $user_id <= 0 ) {
			return new WP_Error( self::ERROR_AUTH_FAILED );
		}

		$now = time();

		$stored['issued_at'] = $this->get_issued_at( $stored, $now );

		if ( $now - $stored['issued_at'] > self::MAX_LIFETIME ) {
			return new WP_Error( self::ERROR_AUTH_FAILED );
		}

		$nonces = ( isset( $stored['nonces'] ) && is_array( $stored['nonces'] ) ) ? $stored['nonces'] : [];

		// Drop nonces past the window: the timestamp check already rejects anything
		// that old.
		$nonces = array_filter(
			$nonces,
			static function ( $expiry ) use ( $now ) {

				return $expiry > $now;
			}
		);

		if ( isset( $nonces[ $nonce ] ) ) {
			return new WP_Error( self::ERROR_AUTH_FAILED );
		}

		$nonces[ $nonce ] = $now + self::SIGNATURE_WINDOW;
		$stored['nonces'] = $nonces;

		set_transient( self::TRANSIENT, $stored, HOUR_IN_SECONDS );

		return $user_id;
	}

	/**
	 * Get the stored key's issue time, starting it now for a key minted before it was recorded.
	 *
	 * @since 4.10.3
	 *
	 * @param array $stored The stored key.
	 * @param int   $now    The current time.
	 *
	 * @return int
	 */
	private function get_issued_at( $stored, $now ) {

		return isset( $stored['issued_at'] ) && is_int( $stored['issued_at'] ) ? $stored['issued_at'] : $now;
	}
}
