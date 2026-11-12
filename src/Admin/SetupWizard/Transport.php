<?php

namespace WPMailSMTP\Admin\SetupWizard;

use WP_Error;

/**
 * AES-256-GCM body encryption for setup wizard protocol v2, keyed from the signing key.
 *
 * @since 4.10.4
 */
class Transport {

	/**
	 * Cipher used for every sealed body.
	 *
	 * @since 4.10.4
	 *
	 * @var string
	 */
	private const CIPHER = 'aes-256-gcm';

	/**
	 * HKDF info string the key is derived with.
	 *
	 * @since 4.10.4
	 *
	 * @var string
	 */
	private const KEY_INFO = 'wpms-setup-wizard/v2/aead';

	/**
	 * Error code for a body that does not open.
	 *
	 * @since 4.10.4
	 *
	 * @var string
	 */
	const ERROR_TRANSPORT_FAILED = 'transport_failed';

	/**
	 * GCM tag length in bytes.
	 *
	 * @since 4.10.4
	 *
	 * @var int
	 */
	private const TAG_LENGTH = 16;

	/**
	 * The verified request being answered: token, endpoint and request nonce.
	 *
	 * @since 4.10.4
	 *
	 * @var array|null
	 */
	private static $context = null;

	/**
	 * Mark the current request as verified, so its response is sealed.
	 *
	 * @since 4.10.4
	 *
	 * @param string $token     Signing key.
	 * @param string $endpoint  Route minus the registered prefix.
	 * @param string $nonce_hex Request nonce.
	 */
	public static function begin( $token, $endpoint, $nonce_hex ) {

		self::$context = [
			'token'    => $token,
			'endpoint' => $endpoint,
			'nonce'    => $nonce_hex,
		];
	}

	/**
	 * Get the verified request being answered.
	 *
	 * @since 4.10.4
	 *
	 * @return array|null Keys token, endpoint and nonce, or null outside a verified request.
	 */
	public static function current() {

		return self::$context;
	}

	/**
	 * Forget the verified request once its response is built.
	 *
	 * @since 4.10.4
	 */
	public static function end() {

		self::$context = null;
	}

	/**
	 * Whether this server can run the encrypted transport.
	 *
	 * @since 4.10.4
	 *
	 * @return bool
	 */
	public static function is_supported() {

		$is_supported = function_exists( 'openssl_encrypt' ) && in_array( self::CIPHER, openssl_get_cipher_methods(), true );

		/**
		 * Filters whether this server can run the encrypted transport the hosted setup wizard needs.
		 *
		 * @since 4.10.4
		 *
		 * @param bool $is_supported Whether OpenSSL offers AES-256-GCM.
		 */
		return (bool) apply_filters( 'wp_mail_smtp_admin_setup_wizard_transport_is_supported', $is_supported );
	}

	/**
	 * Derive the 32-byte encryption key from the signing key.
	 *
	 * @since 4.10.4
	 *
	 * @param string $token Signing key.
	 *
	 * @return string Raw key bytes.
	 */
	public static function derive_key( $token ) {

		return hash_hkdf( 'sha256', $token, 32, self::KEY_INFO, '' );
	}

	/**
	 * The string a request signature covers, which is also a request body's associated data.
	 *
	 * @since 4.10.4
	 *
	 * @param string $endpoint  Route minus the registered prefix.
	 * @param string $timestamp Request timestamp.
	 * @param string $nonce_hex Request nonce, 32 hex characters.
	 *
	 * @return string
	 */
	public static function signed_string( $endpoint, $timestamp, $nonce_hex ) {

		return "POST\n" . $endpoint . "\n" . $timestamp . "\n" . $nonce_hex . "\n2";
	}

	/**
	 * Associated data for a response, binding it to the request it answers.
	 *
	 * @since 4.10.4
	 *
	 * @param string $endpoint          Route minus the registered prefix.
	 * @param string $request_nonce_hex The request's nonce.
	 *
	 * @return string
	 */
	public static function response_ad( $endpoint, $request_nonce_hex ) {

		return "RESPONSE\n" . $endpoint . "\n" . $request_nonce_hex;
	}

	/**
	 * Encrypt a body.
	 *
	 * @since 4.10.4
	 *
	 * @param string $token     Signing key.
	 * @param string $plaintext Body to encrypt.
	 * @param string $ad        Associated data.
	 * @param string $nonce_hex Message nonce, 32 hex characters.
	 *
	 * @return string Base64 of ciphertext followed by the tag.
	 */
	public static function seal( $token, $plaintext, $ad, $nonce_hex ) {

		$tag        = '';
		$ciphertext = openssl_encrypt( $plaintext, self::CIPHER, self::derive_key( $token ), OPENSSL_RAW_DATA, self::iv( $nonce_hex ), $tag, $ad, self::TAG_LENGTH );

		return base64_encode( $ciphertext . $tag ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
	}

	/**
	 * Decrypt and authenticate a body.
	 *
	 * @since 4.10.4
	 *
	 * @param string $token      Signing key.
	 * @param string $sealed_b64 Base64 of ciphertext followed by the tag.
	 * @param string $ad         Associated data.
	 * @param string $nonce_hex  Message nonce, 32 hex characters.
	 *
	 * @return string|WP_Error The plaintext, or a WP_Error coded transport_failed.
	 */
	public static function open( $token, $sealed_b64, $ad, $nonce_hex ) {

		$raw = base64_decode( $sealed_b64, true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode

		if ( $raw === false || strlen( $raw ) < self::TAG_LENGTH || ! preg_match( '/^[0-9a-f]{32}$/', $nonce_hex ) ) {
			return new WP_Error( self::ERROR_TRANSPORT_FAILED );
		}

		$plaintext = openssl_decrypt(
			substr( $raw, 0, -self::TAG_LENGTH ),
			self::CIPHER,
			self::derive_key( $token ),
			OPENSSL_RAW_DATA,
			self::iv( $nonce_hex ),
			substr( $raw, -self::TAG_LENGTH ),
			$ad
		);

		return $plaintext === false ? new WP_Error( self::ERROR_TRANSPORT_FAILED ) : $plaintext;
	}

	/**
	 * The GCM IV: the whole nonce, so the replay list that refuses a spent nonce refuses a reused IV.
	 *
	 * @since 4.10.4
	 *
	 * @param string $nonce_hex Message nonce, 32 hex characters.
	 *
	 * @return string
	 */
	private static function iv( $nonce_hex ) {

		return hex2bin( $nonce_hex );
	}
}
