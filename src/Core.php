<?php

namespace WPMailSMTP;

/**
 * Class Core to handle all plugin initialization.
 */
class Core {

	/**
	 * @var string
	 */
	public $plugin_url;
	/**
	 * @var string
	 */
	public $plugin_path;

	/**
	 * Core constructor.
	 */
	public function __construct() {
		$this->plugin_url  = trim( plugin_dir_url( dirname( __FILE__ ) ), '/\\' );
		$this->plugin_path = trim( plugin_dir_path( dirname( __FILE__ ) ) , '/\\' );

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

		add_action( 'admin_notices', array( 'WPMailSMTP\WP', 'display_admin_notices' ) );

		add_action( 'init', array( $this, 'init' ) );
	}

	/**
	 * Initial plugin actions.
	 */
	public function init() {
		/*
		 * Load translations just in case.
		 */
		load_plugin_textdomain( 'wp-mail-smtp', false, wp_mail_smtp()->plugin_path . '/languages' );

		/*
		 * Constantly check in admin area, that we don't need to upgrade DB.
		 * Do not wait for the `admin_init` hook, because some actions are already done
		 * on `plugins_loaded`, so migration has to be done before.
		 */
		if ( WP::in_wp_admin() ) {
			$this->get_migration();
			$this->get_admin();
		}
	}

	/**
	 * Load the plugin core processor.
	 *
	 * @return Processor
	 */
	public function get_processor() {
		static $processor;

		if ( ! isset( $processor ) ) {
			$processor = apply_filters( 'wp_mail_smtp_get_processor', new Processor() );
		}

		return $processor;
	}

	/**
	 * Load the plugin admin area.
	 *
	 * @return Admin\Area
	 */
	public function get_admin() {
		static $admin;

		if ( ! isset( $admin ) ) {
			$admin = apply_filters( 'wp_mail_smtp_get_admin', new Admin\Area() );
		}

		return $admin;
	}

	/**
	 * Load the plugin option migrator.
	 *
	 * @return Migration
	 */
	public function get_migration() {
		static $migration;

		if ( ! isset( $migration ) ) {
			$migration = apply_filters( 'wp_mail_smtp_get_migration', new Migration() );
		}

		return $migration;
	}

	/**
	 * Awesome Motive Notifications.
	 */
	public function init_notifications() {
		static $notification;

		if ( ! isset( $notification ) ) {
			$notification = new AM_Notification( 'smtp', WPMS_PLUGIN_VER );
		}

		return $notification;
	}
}
