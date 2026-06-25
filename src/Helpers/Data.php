<?php

namespace WPMailSMTP\Helpers;

use ArrayAccess;

/**
 * Class Data.
 *
 * Helper class that allows to get/set value in the nested array by string key.
 *
 * @since 4.9.0
 */
class Data {

	/**
	 * Keys string separator.
	 *
	 * @since 4.9.0
	 *
	 * @var string
	 */
	const KEY_SEPARATOR = '.';

	/**
	 * Get nested array value by string key.
	 *
	 * @since 4.9.0
	 *
	 * @param array  $array   Input array.
	 * @param string $str_key String key. E.g. "level1.level2.level3".
	 * @param mixed  $default The default value that should be returned if value by key not found.
	 *
	 * @return mixed
	 */
	public static function get( $array, $str_key, $default = null ) { // phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.arrayFound, Universal.NamingConventions.NoReservedKeywordParameterNames.defaultFound

		if ( is_array( $array ) && array_key_exists( $str_key, $array ) ) {
			return $array[ $str_key ];
		}

		$keys = self::get_keys_array( $str_key );

		foreach ( $keys as $key ) {
			if ( ! is_array( $array ) && ! $array instanceof ArrayAccess ) {
				return $default;
			}

			if (
				( $array instanceof ArrayAccess && $array->offsetExists( $key ) ) ||
				array_key_exists( $key, $array )
			) {
				$array = $array[ $key ];
			} else {
				return $default;
			}
		}

		return $array;
	}

	/**
	 * Get keys array from keys string.
	 *
	 * @since 4.9.0
	 *
	 * @param string $keys String key. E.g. "level1.level2.level3".
	 *
	 * @return array
	 */
	private static function get_keys_array( $keys ) {

		return explode( self::KEY_SEPARATOR, $keys );
	}

	/**
	 * Set value in the nested array by string key (by reference).
	 *
	 * @since 4.9.0
	 *
	 * @param array  &$array  Input array (passed by reference).
	 * @param string $str_key String key. E.g. "level1.level2.level3".
	 * @param mixed  $value   Value that should be added to array.
	 *
	 * @return void
	 */
	public static function set( array &$array, $str_key, $value ) { // phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.arrayFound

		// Fast path for non-nested key.
		if ( strpos( $str_key, self::KEY_SEPARATOR ) === false ) {
			$array[ $str_key ] = $value;

			return;
		}

		$keys = self::get_keys_array( $str_key );
		$tmp  = &$array;

		$keys_count = count( $keys );

		while ( $keys_count > 0 ) {
			$key = array_shift( $keys );

			--$keys_count;

			if ( ! is_array( $tmp ) ) {
				$tmp = [];
			}

			$tmp = &$tmp[ $key ];
		}

		$tmp = $value;
	}

	/**
	 * Find a value in an array.
	 *
	 * @since 4.9.0
	 *
	 * @param array        $array       The array to search in.
	 * @param string       $key         The key to search for.
	 * @param mixed        $value       The value to search for.
	 * @param string|false $desired_key The key to return from the found item, or false to return the whole item.
	 * @param mixed        $default     The default value to return if not found.
	 *
	 * @return mixed
	 */
	public static function find( $array, $key, $value, $desired_key = false, $default = '' ) { // phpcs:ignore Generic.Metrics.NestingLevel.MaxExceeded, Universal.NamingConventions.NoReservedKeywordParameterNames.arrayFound, Universal.NamingConventions.NoReservedKeywordParameterNames.defaultFound

		foreach ( $array as $item ) {
			if ( isset( $item[ $key ] ) && $item[ $key ] === $value ) {
				if ( $desired_key !== false ) {
					if ( isset( $item[ $desired_key ] ) ) {
						return $item[ $desired_key ];
					}
					break;
				}

				return $item;
			}
		}

		return $default;
	}

	/**
	 * Walk through array recursively.
	 *
	 * @since 4.9.0
	 *
	 * @param array    $array      Array to walk through.
	 * @param callable $callback   Callback function that will be executed for each scalar array item.
	 * @param string   $parent_key Parent key. Used for recursive calls.
	 */
	public static function walk_recursive( &$array, $callback, $parent_key = '' ) { // phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.arrayFound

		foreach ( $array as $key => &$value ) {
			if ( ! is_numeric( $key ) ) {
				$current_key = $parent_key ? $parent_key . '.' . $key : $key;
			} else {
				$current_key = $parent_key;
			}

			if ( is_array( $value ) ) {
				self::walk_recursive( $value, $callback, $current_key );
			} else {
				call_user_func_array( $callback, [ &$value, $current_key ] );
			}
		}
	}
}
