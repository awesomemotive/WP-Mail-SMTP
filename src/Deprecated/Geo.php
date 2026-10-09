<?php

namespace WPMailSMTP\Deprecated;

/**
 * Deprecated geolocation helpers, kept so existing calls do not fail.
 *
 * @since      1.5.0
 * @deprecated {VERSION}
 */
class Geo {

	/**
	 * Get the current site hostname.
	 *
	 * @since      1.5.0
	 * @deprecated {VERSION}
	 *
	 * @return string
	 */
	public static function get_site_domain() {

		_deprecated_function( __METHOD__, '4.10.1' );

		return '';
	}

	/**
	 * Get the domain IP address.
	 *
	 * @since      1.5.0
	 * @deprecated {VERSION}
	 *
	 * @param string $domain Domain name.
	 *
	 * @return string
	 */
	public static function get_ip_by_domain( $domain ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found

		_deprecated_function( __METHOD__, '4.10.1' );

		return '';
	}

	/**
	 * Get the location coordinates by IP address.
	 *
	 * @since      1.5.0
	 * @deprecated {VERSION}
	 *
	 * @param string $ip The IP address.
	 *
	 * @return array
	 */
	public static function get_location_by_ip( $ip ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found

		_deprecated_function( __METHOD__, '4.10.1' );

		return [];
	}

	/**
	 * Calculate the distance between two points.
	 *
	 * @since      1.5.0
	 * @deprecated {VERSION}
	 *
	 * @param float  $lat1 Latitude of point 1 (in decimal degrees).
	 * @param float  $lon1 Longitude of point 1 (in decimal degrees).
	 * @param float  $lat2 Latitude of point 2 (in decimal degrees).
	 * @param float  $lon2 Longitude of point 2 (in decimal degrees).
	 * @param string $unit Supported values: M, K, N. Miles by default.
	 *
	 * @return int
	 */
	public static function get_distance_between( $lat1, $lon1, $lat2, $lon2, $unit = 'M' ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed

		_deprecated_function( __METHOD__, '4.10.1' );

		return 0;
	}

	/**
	 * Get the user IP address.
	 *
	 * @since      3.11.0
	 * @deprecated {VERSION}
	 *
	 * @return string
	 */
	public static function get_ip() {

		_deprecated_function( __METHOD__, '4.10.1' );

		return '';
	}
}
