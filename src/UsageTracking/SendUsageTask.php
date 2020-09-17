<?php

namespace WPMailSMTP\UsageTracking;

use WPMailSMTP\Tasks\Task;

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
	 *
	 * @var string
	 */
	const TRACK_URL = 'https://wpmailsmtpusage.com/v1/smtptrack';

	/**
	 * Class constructor.
	 *
	 * @since 2.3.0
	 */
	public function __construct() {

		parent::__construct( self::ACTION );
	}

	/**
	 * Initialize the task with all the proper checks.
	 *
	 * @since 2.3.0
	 */
	public function init() {

		// Register the action handler.
		add_action( self::ACTION, [ $this, 'process' ] );

		if ( ! function_exists( 'as_next_scheduled_action' ) ) {
			return;
		}

		// Add new if none exists.
		if ( as_next_scheduled_action( self::ACTION ) !== false ) {
			return;
		}

		$this->recurring( $this->generate_start_date(), WEEK_IN_SECONDS )
			->register();
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

		$tracking            = [];
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
	}
}
