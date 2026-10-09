<?php

namespace WPMailSMTP\Tasks;

use Exception;

/**
 * Class NotificationsUpdateTask.
 *
 * @since 4.3.0
 */
class NotificationsUpdateTask extends Task {

	/**
	 * Action name for this task.
	 *
	 * @since 4.3.0
	 */
	const ACTION = 'wp_mail_smtp_admin_notifications_update';

	/**
	 * Class constructor.
	 *
	 * @since 4.3.0
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
	 * @since 4.3.0
	 */
	public function init() {

		// Exit if notifications are disabled
		// or this task is already scheduled.
		if (
			! wp_mail_smtp()->get_notifications()->is_enabled() ||
			Tasks::is_scheduled( self::ACTION ) !== false
		) {
			return;
		}

		// Schedule the task.
		$this->recurring(
			strtotime( '+1 minute' ),
			wp_mail_smtp()->get_notifications()->get_notification_update_task_interval()
		)
		     ->unique()
		     ->register();
	}

	/**
	 * Update the notification feed.
	 *
	 * @since 4.3.0
	 */
	public function process() {

		// Announcements were hidden after the task was scheduled; init() schedules it again once they are shown.
		if ( ! wp_mail_smtp()->get_notifications()->is_enabled() ) {
			$this->cancel_force();

			return;
		}

		// Delete task duplicates.
		try {
			$this->remove_pending( 1000 );
		} catch ( Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
			// Do nothing.
		}

		wp_mail_smtp()->get_notifications()->update();
	}
}
