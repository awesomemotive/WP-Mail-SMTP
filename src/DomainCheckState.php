<?php

namespace WPMailSMTP;

use WPMailSMTP\Admin\DomainChecker;

/**
 * Per-connection domain-authentication check store, keyed by connection id with the
 * primary connection under `primary`.
 *
 * @since 4.10.0
 */
class DomainCheckState {

	/**
	 * Option key for the per-connection state map.
	 *
	 * @since 4.10.0
	 */
	const OPTION_KEY = 'wp_mail_smtp_domain_check_state';

	/**
	 * The check has never run for this connection. {@see self::set()} will not store it.
	 *
	 * @since 4.10.0
	 */
	const UNKNOWN = 'unknown';

	/**
	 * The check found SPF, DKIM or DMARC issues.
	 *
	 * @since 4.10.0
	 */
	const ISSUES = 'issues';

	/**
	 * The check passed.
	 *
	 * @since 4.10.0
	 */
	const CLEAN = 'clean';

	/**
	 * In-memory cache to avoid repeated option reads in one request.
	 *
	 * @since 4.10.0
	 *
	 * @var array|null
	 */
	private static $cached = null;

	/**
	 * Record what a completed domain check found.
	 *
	 * @since 4.10.0
	 *
	 * @param DomainChecker            $domain_checker The completed check.
	 * @param ConnectionInterface|null $connection     The connection that was checked.
	 */
	public static function record( DomainChecker $domain_checker, $connection = null ) {

		$results = $domain_checker->get_results();

		if ( empty( $results['success'] ) ) {
			return;
		}

		self::set(
			$connection instanceof ConnectionInterface ? $connection->get_id() : 'primary',
			$domain_checker->no_issues() ? self::CLEAN : self::ISSUES,
			[
				'mailer'         => $domain_checker->get_mailer(),
				'from_email'     => $domain_checker->get_from_email(),
				'sending_domain' => $domain_checker->get_sending_domain(),
				'checked_at'     => time(),
				'results'        => $results,
			]
		);
	}

	/**
	 * What the last check found for a connection.
	 *
	 * @since 4.10.0
	 *
	 * @param string $connection_id Connection id, or 'primary'.
	 *
	 * @return string One of the state constants.
	 */
	public static function get_state( $connection_id = 'primary' ) {

		$value = self::get( $connection_id )['state'] ?? self::UNKNOWN;

		return in_array( $value, [ self::ISSUES, self::CLEAN ], true ) ? $value : self::UNKNOWN;
	}

	/**
	 * Whether a check has ever reported issues for a connection. Stays true after a
	 * later check comes back clean.
	 *
	 * @since 4.10.0
	 *
	 * @param string $connection_id Connection id, or 'primary'.
	 *
	 * @return bool
	 */
	public static function has_issues_history( $connection_id = 'primary' ) {

		return ! empty( self::get( $connection_id )['had_issues'] );
	}

	/**
	 * Record a state for a connection, merging into any record already stored.
	 *
	 * @since 4.10.0
	 * @since 4.10.1 Added the $details parameter.
	 *
	 * @param string $connection_id Connection id, or 'primary'.
	 * @param string $value         One of the state constants.
	 * @param array  $details       Details of the check that produced the state.
	 */
	public static function set( $connection_id, $value, array $details = [] ) {

		if ( empty( $connection_id ) || ! in_array( $value, [ self::ISSUES, self::CLEAN ], true ) ) {
			return;
		}

		$all             = self::get_raw();
		$record          = array_merge( self::get( $connection_id ), $details );
		$record['state'] = $value;

		if ( $value === self::ISSUES ) {
			$record['had_issues'] = true;
		}

		$all[ $connection_id ] = $record;
		self::$cached          = $all;

		update_option( self::OPTION_KEY, $all, false );
	}

	/**
	 * Wipe every connection's record.
	 *
	 * @since 4.10.0
	 */
	public static function clear_all() {

		self::$cached = [];

		update_option( self::OPTION_KEY, [], false );
	}

	/**
	 * One connection's record, or an empty array when none is stored.
	 *
	 * @since 4.10.0
	 * @since 4.10.1 Made public.
	 *
	 * @param string $connection_id Connection id, or 'primary'.
	 *
	 * @return array
	 */
	public static function get( $connection_id ) {

		$record = self::get_raw()[ $connection_id ] ?? [];

		return is_array( $record ) ? $record : [];
	}

	/**
	 * Raw read with in-memory caching.
	 *
	 * @since 4.10.0
	 *
	 * @return array
	 */
	private static function get_raw() {

		if ( self::$cached !== null ) {
			return self::$cached;
		}

		$all = get_option( self::OPTION_KEY, [] );

		self::$cached = is_array( $all ) ? $all : [];

		return self::$cached;
	}
}
