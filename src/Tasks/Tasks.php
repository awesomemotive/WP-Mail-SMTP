<?php

namespace WPMailSMTP\Tasks;

/**
 * Class Tasks manages the tasks queue and provides API to work with it.
 *
 * @since 2.1.0
 */
class Tasks {

	/**
	 * Group that will be assigned to all actions.
	 *
	 * @since 2.1.0
	 */
	const GROUP = 'wp_mail_smtp';

	/**
	 * Perform certain things on class init.
	 *
	 * @since 2.1.0
	 */
	public function init() {

		// Register tasks.
		foreach ( $this->get_tasks() as $task ) {
			if ( ! is_subclass_of( $task, '\WPMailSMTP\Tasks\Task' ) ) {
				continue;
			}

			$new_task = new $task();

			// Run the init method, if a task has one defined.
			if ( method_exists( $new_task, 'init' ) ) {
				$new_task->init();
			}
		}

		add_action( 'admin_menu', array( $this, 'admin_hide_as_menu' ), PHP_INT_MAX );
	}

	/**
	 * Get the list of default scheduled tasks.
	 * Tasks, that are fired under certain specific circumstances
	 * (like sending emails) are not listed here.
	 *
	 * @since 2.1.0
	 *
	 * @return Task[] List of tasks classes.
	 */
	public function get_tasks() {

		return apply_filters( 'wp_mail_smtp_tasks_get_tasks', array() );
	}

	/**
	 * Hide Action Scheduler admin area when not in debug mode.
	 *
	 * @since 2.1.0
	 */
	public function admin_hide_as_menu() {

		// Filter to redefine that WP Mail SMTP hides Tools > Action Scheduler menu item.
		if ( apply_filters( 'wp_mail_smtp_tasks_admin_hide_as_menu', true ) ) {
			remove_submenu_page( 'tools.php', 'action-scheduler' );
		}
	}

	/**
	 * Create a new task.
	 * Used for "inline" tasks, that require additional information
	 * from the plugin runtime before they can be scheduled.
	 *
	 * Example:
	 *     wp_mail_smtp()->get( 'tasks' )
	 *              ->create( 'i_am_the_dude' )
	 *              ->async()
	 *              ->params( 'The Big Lebowski', 1998 )
	 *              ->register();
	 *
	 * This `i_am_the_dude` action will be later processed as:
	 *     add_action( 'i_am_the_dude', 'thats_what_you_call_me' );
	 *
	 * @since 2.1.0
	 *
	 * @param string $action Action that will be used as a hook.
	 *
	 * @return \WPMailSMTP\Tasks\Task
	 */
	public function create( $action ) {

		return new Task( $action );
	}

	/**
	 * Cancel all the AS actions for a group.
	 *
	 * @since 2.1.0
	 *
	 * @param string $group Group to cancel all actions for.
	 */
	public function cancel_all( $group = '' ) {

		if ( empty( $group ) ) {
			$group = self::GROUP;
		} else {
			$group = sanitize_key( $group );
		}

		if ( class_exists( 'ActionScheduler_DBStore' ) ) {
			\ActionScheduler_DBStore::instance()->cancel_actions_by_group( $group );
		}
	}

	/**
	 * Whether ActionScheduler thinks that it has migrated or not.
	 *
	 * @since 2.1.0
	 *
	 * @return bool
	 */
	public function is_usable() {

		// No tasks if ActionScheduler wasn't loaded.
		if ( ! class_exists( 'ActionScheduler_DataController' ) ) {
			return false;
		}

		return \ActionScheduler_DataController::is_migration_complete();
	}

	/**
	 * Whether task has been scheduled and is pending.
	 *
	 * @since 2.1.0
	 *
	 * @param string $hook Hook to check for.
	 *
	 * @return bool
	 */
	public function is_scheduled( $hook ) {

		if ( ! function_exists( 'as_next_scheduled_action' ) ) {
			return false;
		}

		return as_next_scheduled_action( $hook );
	}
}
