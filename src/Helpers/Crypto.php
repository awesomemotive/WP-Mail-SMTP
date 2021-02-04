<?php

namespace WPMailSMTP\Helpers;

// WP 5.2+ already load Sodium Compat polyfill for libsodium-fallback.
// We need to do the same for under 5.2 versions (4.9-5.1).
if ( ! version_compare( get_bloginfo( 'version' ), '5.2', '>=' ) && ! function_exists( 'sodium_crypto_box' ) ) {
	require_once dirname( WPMS_PLUGIN_FILE ) . '/vendor/paragonie/sodium_compat/autoload.php';
}

/**
 * Class for encryption functionality.
 *
 * @since 2.5.0
 *
 * @link https://www.php.net/manual/en/intro.sodium.php
 */
class Crypto {

	/**
	 * Get a secret key for encrypt/decrypt.
	 *
	 * @since 2.5.0
	 *
	 * @param bool $create Should the key be created, if it does not exist yet.
	 *
	 * @return string|bool
	 */
	public static function get_secret_key( $create = false ) {

		if ( defined( 'WPMS_CRYPTO_KEY' ) ) {
			return WPMS_CRYPTO_KEY;
		}

		$secret_key = apply_filters( 'wp_mail_smtp_helpers_crypto_get_secret_key', get_option( 'wp_mail_smtp_mail_key' ) );

		// If we already have the secret, send it back.
		if ( false !== $secret_key ) {
			return base64_decode( $secret_key ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		}

		if ( $create ) {
			// We don't have a secret, so let's generate one.
			try {
				$secret_key = sodium_crypto_secretbox_keygen(); // phpcs:ignore
			} catch ( \Exception $e ) {
				$secret_key = wp_generate_password( SODIUM_CRYPTO_SECRETBOX_KEYBYTES ); // phpcs:ignore
			}

			add_option( 'wp_mail_smtp_mail_key', base64_encode( $secret_key ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode

			return $secret_key;
		}

		return false;
	}

	/**
	 * Encrypt a message.
	 *
	 * @since 2.5.0
	 *
	 * @param string $message Message to encrypt.
	 * @param string $key     Encryption key.
	 *
	 * @return string
	 * @throws \Exception The exception object.
	 */
	public static function encrypt( $message, $key = '' ) {

		if ( apply_filters( 'wp_mail_smtp_helpers_crypto_stop', false ) ) {
			return $message;
		}

		// Create a nonce for this operation. It will be stored and recovered in the message itself.
		$nonce = random_bytes( SODIUM_CRYPTO_SECRETBOX_NONCEBYTES ); // phpcs:ignore

		if ( empty( $key ) ) {
			$key = self::get_secret_key( true );
		}

		// Encrypt message and combine with nonce.
		$cipher = base64_encode( // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
			$nonce .
			sodium_crypto_secretbox( // phpcs:ignore
				$message,
				$nonce,
				$key
			)
		);

		try {
			sodium_memzero( $message ); // phpcs:ignore
			sodium_memzero( $key ); // phpcs:ignore
		} catch ( \Exception $e ) {
			return $cipher;
		}

		return $cipher;
	}

	/**
	 * Decrypt a message.
	 * Returns encrypted message on any failure and the decrypted message on success.
	 *
	 * @since 2.5.0
	 *
	 * @param string $encrypted Encrypted message.
	 * @param string $key       Encryption key.
	 *
	 * @return string
	 * @throws \Exception The exception object.
	 */
	public static function decrypt( $encrypted, $key = '' ) {

		if ( apply_filters( 'wp_mail_smtp_helpers_crypto_stop', false ) ) {
			return $encrypted;
		}

		// Unpack base64 message.
		$decoded = base64_decode( $encrypted ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode

		if ( false === $decoded ) {
			return $encrypted;
		}

		if ( mb_strlen( $decoded, '8bit' ) < ( SODIUM_CRYPTO_SECRETBOX_NONCEBYTES + SODIUM_CRYPTO_SECRETBOX_MACBYTES ) ) { // phpcs:ignore
			return $encrypted;
		}

		// Pull nonce and ciphertext out of unpacked message.
		$nonce      = mb_substr( $decoded, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, '8bit' ); // phpcs:ignore
		$ciphertext = mb_substr( $decoded, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, null, '8bit' ); // phpcs:ignore

		$key = empty( $key ) ? self::get_secret_key() : $key;

		if ( empty( $key ) ) {
			return $encrypted;
		}

		// Decrypt it.
		$message = sodium_crypto_secretbox_open( // phpcs:ignore
			$ciphertext,
			$nonce,
			$key
		);

		// Check for decryption failures.
		if ( false === $message ) {
			return $encrypted;
		}

		try {
			sodium_memzero( $ciphertext ); // phpcs:ignore
			sodium_memzero( $key ); // phpcs:ignore
		} catch ( \Exception $e ) {
			return $message;
		}

		return $message;
	}
}
