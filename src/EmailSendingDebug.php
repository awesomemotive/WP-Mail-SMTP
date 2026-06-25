<?php

namespace WPMailSMTP;

/**
 * Per-connection email-sending-failure store.
 *
 * @since 4.9.0
 */
class EmailSendingDebug {

	/**
	 * Option key for the per-connection failure map.
	 *
	 * @since 4.9.0
	 */
	const OPTION_KEY = 'wp_mail_smtp_email_sending_debug';

	/**
	 * In-memory cache to avoid repeated option reads in one request.
	 *
	 * @since 4.9.0
	 *
	 * @var array|null
	 */
	private static $cached = null;

	/**
	 * Write or overwrite the failure record for a single connection.
	 *
	 * @since 4.9.0
	 *
	 * @param string $connection_id Connection id ('primary' or an additional connection's id).
	 * @param array  $record        Failure-record payload.
	 */
	public static function set( $connection_id, $record ) {

		if ( empty( $connection_id ) ) {
			return;
		}

		$all                   = self::get_raw();
		$all[ $connection_id ] = $record;
		self::$cached          = $all;

		update_option( self::OPTION_KEY, $all, false );
	}

	/**
	 * Merge partial fields into the existing failure record for a single connection.
	 *
	 * Unlike {@see self::set()}, merge annotates an already-stored record - it does
	 * not create one when none exists. Provided fields overwrite stored ones; every
	 * other field is preserved.
	 *
	 * @since 4.9.0
	 *
	 * @param string $connection_id  Connection id ('primary' or an additional connection's id).
	 * @param array  $partial_record Fields to merge over the existing record.
	 */
	public static function merge( $connection_id, $partial_record ) {

		if ( empty( $connection_id ) || empty( $partial_record ) ) {
			return;
		}

		$all = self::get_raw();

		if ( empty( $all[ $connection_id ] ) ) {
			return;
		}

		$all[ $connection_id ] = array_merge( $all[ $connection_id ], $partial_record );
		self::$cached          = $all;

		update_option( self::OPTION_KEY, $all, false );
	}

	/**
	 * Remove the failure record for a single connection.
	 *
	 * @since 4.9.0
	 *
	 * @param string $connection_id Connection id.
	 */
	public static function clear( $connection_id ) {

		if ( empty( $connection_id ) ) {
			return;
		}

		$all = self::get_raw();

		if ( ! array_key_exists( $connection_id, $all ) ) {
			return;
		}

		unset( $all[ $connection_id ] );
		self::$cached = $all;

		update_option( self::OPTION_KEY, $all, false );
	}

	/**
	 * Wipe every connection's failure record.
	 *
	 * @since 4.9.0
	 */
	public static function clear_all() {

		self::$cached = [];

		update_option( self::OPTION_KEY, [], false );
	}

	/**
	 * Return a single connection's failure as a human-readable string in the
	 * form `Mailer: <title>` + EOL + `<error_message>`, or an empty string when
	 * the record does not exist or has no error_message.
	 *
	 * @since 4.9.0
	 *
	 * @param string $connection_id Connection id.
	 *
	 * @return string
	 */
	public static function get_message( $connection_id ) {

		return self::format_record( self::get( $connection_id ) );
	}

	/**
	 * Return formatted failure messages for every connection with a non-empty
	 * `error_message`. Each entry is formatted as `Mailer: <title>` + EOL +
	 * `<error_message>`.
	 *
	 * @since 4.9.0
	 *
	 * @return string[]
	 */
	public static function get_messages() {

		$messages = [];

		foreach ( self::get() as $record ) {
			$formatted = self::format_record( $record );

			if ( $formatted !== '' ) {
				$messages[] = $formatted;
			}
		}

		return $messages;
	}

	/**
	 * Build the `Mailer: <title>` + EOL + `<error_message>` string for a single
	 * failure record. Returns an empty string when the record is empty or has
	 * no `error_message`.
	 *
	 * @since 4.9.0
	 *
	 * @param array $record Failure-record payload.
	 *
	 * @return string
	 */
	private static function format_record( $record ) {

		if ( empty( $record['error_message'] ) ) {
			return '';
		}

		$options = ! empty( $record['mailer'] )
			? wp_mail_smtp()->get_providers()->get_options( $record['mailer'] )
			: null;

		$mailer_title = ! empty( $options )
			? $options->get_title()
			: esc_html__( 'Unknown', 'wp-mail-smtp' );

		return 'Mailer: ' . $mailer_title . WP::EOL . (string) $record['error_message'];
	}

	/**
	 * Read failure records. Returns the full map when `$connection_id` is null,
	 * otherwise that connection's record (or an empty array when none stored).
	 *
	 * @since 4.9.0
	 *
	 * @param string|null $connection_id Optional connection id.
	 *
	 * @return array
	 */
	public static function get( $connection_id = null ) {

		$all = self::get_raw();

		if ( $connection_id === null ) {
			return $all;
		}

		return isset( $all[ $connection_id ] ) ? $all[ $connection_id ] : [];
	}

	/**
	 * Raw read with in-memory caching.
	 *
	 * @since 4.9.0
	 *
	 * @return array
	 */
	private static function get_raw() {

		if ( self::$cached !== null ) {
			return self::$cached;
		}

		$all = get_option( self::OPTION_KEY, [] );

		if ( ! is_array( $all ) ) {
			$all = [];
		}

		self::$cached = $all;

		return $all;
	}
}
