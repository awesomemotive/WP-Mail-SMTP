<?php

/**
 * Class WP_Mail_SMTP
 */
class WP_Mail_SMTP {

	/**
	 * WP_Mail_SMTP constructor.
	 */
	public function __construct() {
		$this->hooks();
	}

	/**
	 * Assign all hooks to proper places.
	 */
	public function hooks() {
		add_action( 'plugins_loaded', array( $this, 'get_processor' ) );
		add_action( 'init', array( $this, 'get_admin' ) );
		add_action( 'plugins_loaded', array( $this, 'get_migration' ) );
		add_action( 'plugins_loaded', array( $this, 'init_notifications' ) );

		add_action( 'init', array( $this, 'init' ) );
	}

	/**
	 * Initial plugin actions.
	 */
	public function init() {
		/*
		 * Load translations just in case.
		 */
		load_plugin_textdomain( 'wp-mail-smtp', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

		/*
		 * Constantly check in admin area, that we don't need to upgrade DB.
		 * Do not wait for the `admin_init` hook, because some actions are already done
		 * on `plugins_loaded`, so migration has to be done before.
		 */
		if ( is_admin() && ! ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
			$this->get_migration();
			$this->get_admin();
		}
	}

	/**
	 * Load the plugin core processor.
	 *
	 * @return WPMS_Processor
	 */
	public function get_processor() {
		static $processor;

		if ( ! isset( $processor ) ) {
			require_once './src/class-wpms-processor.php';

			$processor = apply_filters( 'wp_mail_smtp_get_processor', new WPMS_Processor() );
		}

		return $processor;
	}

	/**
	 * Load the plugin admin area.
	 *
	 * @return WPMS_Admin_Area
	 */
	public function get_admin() {
		static $admin;

		if ( ! isset( $admin ) ) {
			require_once './src/admin/class-wpms-admin-area.php';

			$admin = apply_filters( 'wp_mail_smtp_get_admin', new WPMS_Admin_Area() );
		}

		return $admin;
	}

	/**
	 * Load the plugin option migrator.
	 *
	 * @return WPMS_Migration
	 */
	public function get_migration() {
		static $migration;

		if ( ! isset( $migration ) ) {
			require_once './src/class-wpms-migration.php';

			$migration = apply_filters( 'wp_mail_smtp_get_migration', new WPMS_Migration() );
		}

		return $migration;
	}

	/**
	 * Awesome Motive Notifications.
	 */
	public function init_notifications() {

		if ( ! class_exists( 'WPMS_AM_Notification' ) ) {
			require_once 'src/class-wpms-am-notification.php';
		}

		new WPMS_AM_Notification( 'smtp', WPMS_PLUGIN_VER );
	}
}

new WP_Mail_SMTP();
