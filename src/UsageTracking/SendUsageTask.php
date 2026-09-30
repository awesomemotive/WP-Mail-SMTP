<?php

namespace WPMailSMTP\UsageTracking;

use WPMailSMTP\Tasks\Task;
use WPMailSMTP\Tasks\Tasks;

/**
 * Class SendUsageTask.
 *
 * @since 2.3.0
 */
class SendUsageTask extends Task {

	/**
	 * Action name for this task.
	 *
	 * @since 2.3.0
	 */
	const ACTION = 'wp_mail_smtp_send_usage_data';

	/**
	 * Server URL to send requests to.
	 *
	 * @since 2.3.0
	 */
	const TRACK_URL = 'https://wpmailsmtpusage.com/v1/smtptrack';

	/**
	 * Option name to store the timestamp of the last run.
	 *
	 * @since 2.5.0
	 */
	const LAST_RUN = 'wp_mail_smtp_send_usage_last_run';

	/**
	 * Class constructor.
	 *
	 * @since 2.3.0
	 */
	public function __construct() {

		parent::__construct( self::ACTION );
	}

	/**
	 * Register the task's callbacks.
	 *
	 * @since 4.10.0
	 */
	public function hooks() { // phpcs:ignore WPForms.PHP.HooksMethod.InvalidPlaceForAddingHooks

		// Register the action handler.
		add_action( self::ACTION, [ $this, 'process' ] );
	}

	/**
	 * Initialize the task with all the proper checks.
	 *
	 * @since 2.3.0
	 * @since 4.10.0 A fresh install schedules its first ping one day in instead of the weekly slot.
	 */
	public function init() {

		// Add new if none exists.
		if ( Tasks::is_scheduled( self::ACTION ) !== false ) {
			return;
		}

		/*
		 * A fresh install gets a one-off first ping a day in, when the mailer
		 * is typically configured. Once it runs, process() sets LAST_RUN, and
		 * the weekly task registers here on the next request. No jitter:
		 * activation times are naturally distributed across installs.
		 */
		if ( $this->needs_initial_ping() ) {
			$this->once( time() + DAY_IN_SECONDS )
				->register();

			return;
		}

		$this->recurring( $this->generate_start_date(), WEEK_IN_SECONDS )
			->register();
	}

	/**
	 * Whether this install has never pinged and was activated recently,
	 * so the first ping should not wait for the weekly schedule.
	 *
	 * @since 4.10.0
	 *
	 * @return bool
	 */
	private function needs_initial_ping() {

		if ( get_option( self::LAST_RUN ) !== false ) {
			return false;
		}

		$activated = (int) get_option( 'wp_mail_smtp_activated_time', 0 );

		return $activated > 0 && ( time() - $activated ) < MONTH_IN_SECONDS;
	}

	/**
	 * Randomly pick a timestamp
	 * which is not more than 1 week in the future
	 * starting from next sunday.
	 *
	 * @since 2.3.0
	 *
	 * @return int
	 */
	private function generate_start_date() {

		$tracking = [];

		$tracking['days']    = wp_rand( 0, 6 ) * DAY_IN_SECONDS;
		$tracking['hours']   = wp_rand( 0, 23 ) * HOUR_IN_SECONDS;
		$tracking['minutes'] = wp_rand( 0, 59 ) * MINUTE_IN_SECONDS;
		$tracking['seconds'] = wp_rand( 0, 59 );

		return strtotime( 'next sunday' ) + array_sum( $tracking );
	}

	/**
	 * Send the actual data in a POST request.
	 * This will be executed in a separate process via Action Scheduler.
	 *
	 * @since 2.3.0
	 */
	public function process() {

		$last_run = get_option( self::LAST_RUN );

		// Make sure we do not run it more than once a day.
		if (
			$last_run !== false &&
			( time() - $last_run ) < DAY_IN_SECONDS
		) {
			return;
		}

		// Send data to the usage tracking API.
		$ut = new UsageTracking();

		wp_remote_post(
			self::TRACK_URL,
			[
				'timeout'     => 5,
				'redirection' => 5,
				'httpversion' => '1.1',
				'blocking'    => true,
				'body'        => $ut->get_data(),
				'user-agent'  => $ut->get_user_agent(),
			]
		);

		// Update the last run option to the current timestamp.
		update_option( self::LAST_RUN, time() );
	}
}
