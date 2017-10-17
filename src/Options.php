<?php

namespace WPMailSMTP;

/**
 * Class Options to handle all options management.
 * WordPress does all the heavy work for caching get_option() data,
 * so we don't have to do that.
 */
class Options {

	/**
	 * That's where plugin options are saved in wp_options table.
	 *
	 * @var string
	 */
	const META_KEY = 'wp_mail_smtp';

	/**
	 * Get options by a group and a key or by group only:
	 *
	 * Options::get()               - will return all options.
	 * Options::get('smtp')         - will return only SMTP options (array).
	 * Options::get('smtp', 'host') - will return only SMTP 'host' option (string).
	 *
	 * @since 1.0.0
	 *
	 * @param string $group
	 * @param string $key
	 *
	 * @return array|string
	 */
	public static function get( $group = '', $key = '' ) {

		// Just to feel safe.
		$group = sanitize_key( $group );
		$key   = sanitize_key( $key );

		$options = get_option( self::META_KEY, array() );

		// Get the options group.
		if ( array_key_exists( $group, $options ) ) {

			// Get the options key of a group.
			if ( array_key_exists( $key, $options[ $group ] ) ) {
				return $options[ $group ][ $key ];
			}

			return $options[ $group ];
		}

		return $options;
	}

	/**
	 * Set plugin options, all at once.
	 *
	 * @since 1.0.0
	 *
	 * @param array $options Data to save, already processed.
	 */
	public static function set( $options ) {
		update_option( self::META_KEY, $options );
	}
}
