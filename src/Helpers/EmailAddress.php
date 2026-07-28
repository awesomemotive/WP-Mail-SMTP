<?php

namespace WPMailSMTP\Helpers;

/**
 * Class for internationalized email address (IDN) handling.
 *
 * WordPress core validation and sanitization functions (`is_email()`, `sanitize_email()`)
 * and `filter_var( ..., FILTER_VALIDATE_EMAIL )` only accept ASCII domains, so an address
 * like `info@münchen.net` is rejected even though it is a perfectly deliverable address.
 * The helpers below convert the domain part to its Punycode (ASCII) representation before
 * handing the address over to those functions, and back to Unicode for display.
 *
 * Only the domain part is converted. A non-ASCII local part needs SMTPUTF8, and WordPress
 * core's `is_email()` and `sanitize_email()` reject it independently of whether the bundled
 * PHPMailer supports it, so those addresses stay unsupported.
 *
 * @since {VERSION}
 *
 * @link https://www.rfc-editor.org/rfc/rfc5890
 */
class EmailAddress {

	/**
	 * Convert the domain part of an email address to its Punycode (ASCII) representation.
	 *
	 * Addresses that already have an ASCII domain are returned untouched, so the casing
	 * of existing values is preserved.
	 *
	 * @since {VERSION}
	 *
	 * @param string $email Email address, with a Unicode or ASCII domain.
	 *
	 * @return string Email address with an ASCII domain. The unmodified input if the
	 *                conversion is not possible.
	 */
	public static function punyencode_email( $email ) {

		$email = (string) $email;

		list( $local_part, $domain ) = self::split( $email );

		if ( $domain === '' || ! self::has_non_ascii( $domain ) ) {
			return $email;
		}

		if ( ! function_exists( 'idn_to_ascii' ) ) {
			return $email;
		}

		// The default UTS #46 variant is the only one available as of PHP 7.4.
		$encoded = idn_to_ascii( $domain );

		if ( empty( $encoded ) ) {
			return $email;
		}

		return $local_part . '@' . $encoded;
	}

	/**
	 * Convert the Punycode domain part of an email address back to Unicode.
	 *
	 * Meant for displaying a stored address to the user. Addresses without a Punycode
	 * domain are returned untouched.
	 *
	 * @since {VERSION}
	 *
	 * @param string $email Email address, with an ASCII domain.
	 *
	 * @return string Email address with a Unicode domain. The unmodified input if the
	 *                conversion is not possible.
	 */
	public static function punydecode_email( $email ) {

		$email = (string) $email;

		list( $local_part, $domain ) = self::split( $email );

		if ( $domain === '' || stripos( $domain, 'xn--' ) === false ) {
			return $email;
		}

		if ( ! function_exists( 'idn_to_utf8' ) ) {
			return $email;
		}

		// The default UTS #46 variant is the only one available as of PHP 7.4.
		$decoded = idn_to_utf8( $domain );

		if ( empty( $decoded ) ) {
			return $email;
		}

		return $local_part . '@' . $decoded;
	}

	/**
	 * Verify that an email address is valid, accepting internationalized domains.
	 *
	 * IDN-aware counterpart of the WordPress `is_email()` function.
	 *
	 * @since {VERSION}
	 *
	 * @param string $email Email address to check.
	 *
	 * @return bool
	 */
	public static function is_email( $email ) {

		return (bool) is_email( self::punyencode_email( $email ) );
	}

	/**
	 * Split an email address into its local and domain part.
	 *
	 * The address is split at the last `@`, which is the delimiter defined by RFC 5322
	 * for a quoted local part that itself contains an `@`.
	 *
	 * @since {VERSION}
	 *
	 * @param string $email Email address to split.
	 *
	 * @return string[] The local part and the domain part. Both are empty strings if the
	 *                  address does not contain an `@`.
	 */
	private static function split( $email ) {

		$position = strrpos( $email, '@' );

		if ( $position === false ) {
			return [ '', '' ];
		}

		return [ substr( $email, 0, $position ), substr( $email, $position + 1 ) ];
	}

	/**
	 * Check whether a string contains characters outside of printable ASCII.
	 *
	 * @since {VERSION}
	 *
	 * @param string $value String to check.
	 *
	 * @return bool
	 */
	private static function has_non_ascii( $value ) {

		return (bool) preg_match( '/[^\x20-\x7E]/', $value );
	}
}
