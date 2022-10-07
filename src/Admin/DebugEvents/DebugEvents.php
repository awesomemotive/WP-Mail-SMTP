<?php

namespace WPMailSMTP\Admin\DebugEvents;

use WPMailSMTP\Admin\Area;
use WPMailSMTP\Options;
use WPMailSMTP\Tasks\DebugEventsCleanupTask;
use WPMailSMTP\WP;

/**
 * Debug Events class.
 *
 * @since 3.0.0
 */
class DebugEvents {

	/**
	 * Register hooks.
	 *
	 * @since 3.0.0
	 */
	public function hooks() {

		// Process AJAX requests.
		add_action( 'wp_ajax_wp_mail_smtp_debug_event_preview', [ $this, 'process_ajax_debug_event_preview' ] );
		add_action( 'wp_ajax_wp_mail_smtp_delete_all_debug_events', [ $this, 'process_ajax_delete_all_debug_events' ] );

		// Initialize screen options for the Debug Events page.
		add_action( 'load-wp-mail-smtp_page_wp-mail-smtp-tools', [ $this, 'screen_options' ] );
		add_filter( 'set-screen-option', [ $this, 'set_screen_options' ], 10, 3 );
		add_filter( 'set_screen_option_wp_mail_smtp_debug_events_per_page', [ $this, 'set_screen_options' ], 10, 3 );

		// Cancel previous debug events cleanup task if retention period option was changed.
		add_filter( 'wp_mail_smtp_options_set', [ $this, 'maybe_cancel_debug_events_cleanup_task' ] );

		// Detect debug events log retention period constant change.
		if ( Options::init()->is_const_defined( 'debug_events', 'retention_period' ) ) {
			add_action( 'admin_init', [ $this, 'detect_debug_events_retention_period_constant_change' ] );
		}
	}

	/**
	 * Detect debug events retention period constant change.
	 *
	 * @since 3.6.0
	 */
	public function detect_debug_events_retention_period_constant_change() {

		if ( ! WP::in_wp_admin() ) {
			return;
		}

		if ( Options::init()->is_const_changed( 'debug_events', 'retention_period' ) ) {
			( new DebugEventsCleanupTask() )->cancel();
		}
	}

	/**
	 * Cancel previous debug events cleanup task if retention period option was changed.
	 *
	 * @since 3.6.0
	 *
	 * @param array $options Currently processed options passed to a filter hook.
	 *
	 * @return array
	 */
	public function maybe_cancel_debug_events_cleanup_task( $options ) {

		if ( isset( $options['debug_events']['retention_period'] ) ) {
			// If this option has changed, cancel the recurring cleanup task and init again.
			if ( Options::init()->is_option_changed( $options['debug_events']['retention_period'], 'debug_events', 'retention_period' ) ) {
				( new DebugEventsCleanupTask() )->cancel();
			}
		}

		return $options;
	}

	/**
	 * Process AJAX request for deleting all debug event entries.
	 *
	 * @since 3.0.0
	 */
	public function process_ajax_delete_all_debug_events() {

		if (
			empty( $_POST['nonce'] ) ||
			! wp_verify_nonce( sanitize_key( $_POST['nonce'] ), 'wp_mail_smtp_debug_events' )
		) {
			wp_send_json_error( esc_html__( 'Access rejected.', 'wp-mail-smtp' ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( esc_html__( 'You don\'t have the capability to perform this action.', 'wp-mail-smtp' ) );
		}

		global $wpdb;

		$table = self::get_table_name();

		$sql = "TRUNCATE TABLE `$table`;";

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$result = $wpdb->query( $sql );

		if ( $result !== false ) {
			wp_send_json_success( esc_html__( 'All debug event entries were deleted successfully.', 'wp-mail-smtp' ) );
		}

		wp_send_json_error(
			sprintf( /* translators: %s - WPDB error message. */
				esc_html__( 'There was an issue while trying to delete all debug event entries. Error message: %s', 'wp-mail-smtp' ),
				$wpdb->last_error
			)
		);
	}

	/**
	 * Process AJAX request for debug event preview.
	 *
	 * @since 3.0.0
	 */
	public function process_ajax_debug_event_preview() {

		if (
			empty( $_POST['nonce'] ) ||
			! wp_verify_nonce( sanitize_key( $_POST['nonce'] ), 'wp_mail_smtp_debug_events' )
		) {
			wp_send_json_error( esc_html__( 'Access rejected.', 'wp-mail-smtp' ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( esc_html__( 'You don\'t have the capability to perform this action.', 'wp-mail-smtp' ) );
		}

		$event_id = isset( $_POST['id'] ) ? intval( $_POST['id'] ) : false;

		if ( empty( $event_id ) ) {
			wp_send_json_error( esc_html__( 'No Debug Event ID provided!', 'wp-mail-smtp' ) );
		}

		$event = new Event( $event_id );

		wp_send_json_success(
			[
				'title'   => $event->get_title(),
				'content' => $event->get_details_html(),
			]
		);
	}

	/**
	 * Add the debug event to the DB.
	 *
	 * @since 3.0.0
	 *
	 * @param string $message The event's message.
	 * @param int    $type    The event's type.
	 *
	 * @return bool|int
	 */
	public static function add( $message = '', $type = 0 ) {

		if ( ! in_array( $type, array_keys( Event::get_types() ), true ) ) {
			return false;
		}

		if ( $type === Event::TYPE_DEBUG && ! self::is_debug_enabled() ) {
			return false;
		}

		try {
			$event = new Event();
			$event->set_type( $type );
			$event->set_content( $message );
			$event->set_initiator();

			return $event->save()->get_id();
		} catch ( \Exception $exception ) {
			return false;
		}
	}

	/**
	 * Save the debug message.
	 *
	 * @since 3.0.0
	 * @since 3.5.0 Returns Event ID.
	 *
	 * @param string $message The debug message.
	 *
	 * @return bool|int
	 */
	public static function add_debug( $message = '' ) {

		return self::add( $message, Event::TYPE_DEBUG );
	}

	/**
	 * Get the debug message from the provided debug event IDs.
	 *
	 * @since 3.0.0
	 *
	 * @param array|string|int $ids A single or a list of debug event IDs.
	 *
	 * @return array
	 */
	public static function get_debug_messages( $ids ) {

		global $wpdb;

		if ( empty( $ids ) ) {
			return [];
		}

		if ( ! self::is_valid_db() ) {
			return [];
		}

		// Convert to a string.
		if ( is_array( $ids ) ) {
			$ids = implode( ',', $ids );
		}

		$ids          = explode( ',', (string) $ids );
		$ids          = array_map( 'intval', $ids );
		$placeholders = implode( ', ', array_fill( 0, count( $ids ), '%d' ) );

		$table = self::get_table_name();

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
		$events_data = $wpdb->get_results(
			$wpdb->prepare( "SELECT id, content, initiator, event_type, created_at  FROM {$table} WHERE id IN ( {$placeholders} )", $ids )
		);
		// phpcs:enable

		if ( empty( $events_data ) ) {
			return [];
		}

		return array_map(
			function ( $event_item ) {
				$event = new Event( $event_item );

				return $event->get_short_details();
			},
			$events_data
		);
	}

	/**
	 * Register the screen options for the debug events page.
	 *
	 * @since 3.0.0
	 */
	public function screen_options() {

		$screen = get_current_screen();

		if (
			! is_object( $screen ) ||
			strpos( $screen->id, 'wp-mail-smtp_page_wp-mail-smtp-tools' ) === false ||
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			! isset( $_GET['tab'] ) || $_GET['tab'] !== 'debug-events'
		) {
			return;
		}

		add_screen_option(
			'per_page',
			[
				'label'   => esc_html__( 'Number of events per page:', 'wp-mail-smtp' ),
				'option'  => 'wp_mail_smtp_debug_events_per_page',
				'default' => EventsCollection::PER_PAGE,
			]
		);
	}

	/**
	 * Set the screen options for the debug events page.
	 *
	 * @since 3.0.0
	 *
	 * @param bool   $keep   Whether to save or skip saving the screen option value.
	 * @param string $option The option name.
	 * @param int    $value  The number of items to use.
	 *
	 * @return bool|int
	 */
	public function set_screen_options( $keep, $option, $value ) {

		if ( 'wp_mail_smtp_debug_events_per_page' === $option ) {
			return (int) $value;
		}

		return $keep;
	}

	/**
	 * Whether the email debug for debug events is enabled or not.
	 *
	 * @since 3.0.0
	 *
	 * @return bool
	 */
	public static function is_debug_enabled() {

		return (bool) Options::init()->get( 'debug_events', 'email_debug' );
	}

	/**
	 * Get the debug events page URL.
	 *
	 * @since 3.0.0
	 *
	 * @return string
	 */
	public static function get_page_url() {

		return add_query_arg(
			[
				'tab' => 'debug-events',
			],
			wp_mail_smtp()->get_admin()->get_admin_page_url( Area::SLUG . '-tools' )
		);
	}

	/**
	 * Get the DB table name.
	 *
	 * @since 3.0.0
	 *
	 * @return string Table name, prefixed.
	 */
	public static function get_table_name() {

		global $wpdb;

		return $wpdb->prefix . 'wpmailsmtp_debug_events';
	}

	/**
	 * Whether the DB table exists.
	 *
	 * @since 3.0.0
	 *
	 * @return bool
	 */
	public static function is_valid_db() {

		global $wpdb;

		$table = self::get_table_name();

		return (bool) $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s;', $table ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
	}
}
