<?php

namespace WPMailSMTP\Tasks\Reports;

use WPMailSMTP\WP;
use WPMailSMTP\Tasks\Task;
use WPMailSMTP\Reports\Emails\Summary as SummaryReportEmail;

/**
 * Class SummaryEmailTask.
 *
 * @since 3.0.0
 */
class SummaryEmailTask extends Task {

	/**
	 * Action name for this task.
	 *
	 * @since 3.0.0
	 */
	const ACTION = 'wp_mail_smtp_summary_report_email';

	/**
	 * Class constructor.
	 *
	 * @since 3.0.0
	 */
	public function __construct() {

		parent::__construct( self::ACTION );
	}

	/**
	 * Initialize the task with all the proper checks.
	 *
	 * @since 3.0.0
	 */
	public function init() {

		// Register the action handler.
		add_action( self::ACTION, array( $this, 'process' ) );

		// Exit if AS function does not exist.
		if ( ! function_exists( 'as_next_scheduled_action' ) ) {
			return;
		}

		$is_disabled = SummaryReportEmail::is_disabled();

		// Exit if summary report email is disabled or this task is already scheduled.
		if ( ! empty( $is_disabled ) || as_next_scheduled_action( self::ACTION ) !== false ) {
			return;
		}

		$date = new \DateTime( 'next monday 2pm', WP::wp_timezone() );

		// Schedule the task.
		$this->recurring( $date->getTimestamp(), WEEK_IN_SECONDS )->register();
	}

	/**
	 * Process summary report email send.
	 *
	 * @since 3.0.0
	 *
	 * @param int $meta_id The Meta ID with the stored task parameters.
	 */
	public function process( $meta_id ) {

		// Prevent email sending if summary report email is disabled.
		if ( SummaryReportEmail::is_disabled() ) {
			return;
		}

		$reports = wp_mail_smtp()->get_reports();

		$email = $reports->get_summary_report_email();

		$email->send();
	}
}
