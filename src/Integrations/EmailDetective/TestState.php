<?php

namespace WPMailSMTP\Integrations\EmailDetective;

/**
 * The persisted in-flight Email Detective test. One per site at a time.
 *
 * @since 4.10.0
 */
class TestState {

	/**
	 * The option name this state is persisted under.
	 *
	 * @since 4.10.0
	 */
	const OPTION = 'wp_mail_smtp_email_detective_state';

	/**
	 * Waiting for the relay to receive the test send.
	 *
	 * @since 4.10.0
	 */
	const PHASE_WAITING = 'waiting';

	/**
	 * The send itself failed (wp_mail()/mailer error), before it ever left the site.
	 *
	 * @since 4.10.0
	 */
	const PHASE_SEND_FAILED = 'send_failed';

	/**
	 * The send succeeded locally but the relay never saw it arrive.
	 *
	 * @since 4.10.0
	 */
	const PHASE_NOT_DELIVERED = 'not_delivered';

	/**
	 * The relay received the test email and produced a report.
	 *
	 * @since 4.10.0
	 */
	const PHASE_RECEIVED = 'received';

	/**
	 * The lead-capture email address was sent to unlock the full report.
	 *
	 * @since 4.10.0
	 */
	const PHASE_LEAD_SENT = 'lead_sent';

	/**
	 * The full report has been unlocked.
	 *
	 * @since 4.10.0
	 */
	const PHASE_UNLOCKED = 'unlocked';

	/**
	 * Read the current in-flight test, if any.
	 *
	 * @since 4.10.0
	 *
	 * @return array|null The state array, or null when there is no test in
	 *                     flight, or the stored option is malformed.
	 */
	public static function get() {

		$state = get_option( self::OPTION, null );

		// A state without the fields start() always writes is not a test in
		// flight, so callers read it as "none" rather than indexing into it.
		if ( ! is_array( $state ) || ! isset( $state['phase'], $state['started_at'], $state['test_id'] ) ) {
			return null;
		}

		return $state;
	}

	/**
	 * Start a new in-flight test, replacing whatever was stored before.
	 *
	 * @since 4.10.0
	 *
	 * @param array $test The new test's fields: test_id, report_url,
	 *                     email_address (the minted address the test email was
	 *                     sent to) and email (the address to send the report to).
	 */
	public static function start( array $test ) {

		$state = array_merge(
			$test,
			[
				'phase'      => self::PHASE_WAITING,
				'started_at' => time(),
			]
		);

		update_option( self::OPTION, $state, false );
	}

	/**
	 * Merge fields into the current in-flight test.
	 *
	 * @since 4.10.0
	 *
	 * @param array $fields The fields to overwrite; anything already stored
	 *                       and not named here is left as is.
	 */
	public static function update( array $fields ) {

		$state = self::get();

		// Never create state here. A reset that lands between another request's
		// read and its write would otherwise resurrect the test it just cleared.
		if ( $state === null ) {
			return;
		}

		update_option( self::OPTION, array_merge( $state, $fields ), false );
	}

	/**
	 * Clear the in-flight test entirely.
	 *
	 * @since 4.10.0
	 */
	public static function clear() {

		delete_option( self::OPTION );
	}
}
