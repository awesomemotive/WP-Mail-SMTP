<?php

namespace WPMailSMTP;

/**
 * Class Core to handle all plugin initialization.
 *
 * @since 1.0.0
 */
class Core {

	/**
	 * Without trailing slash.
	 *
	 * @var string
	 */
	public $plugin_url;
	/**
	 * Without trailing slash.
	 *
	 * @var string
	 */
	public $plugin_path;

	/**
	 * Core constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		$this->plugin_url  = rtrim( plugin_dir_url( __DIR__ ), '/\\' );
		$this->plugin_path = rtrim( plugin_dir_path( __DIR__ ), '/\\' );

		$this->hooks();
	}

	/**
	 * Assign all hooks to proper places.
	 *
	 * @since 1.0.0
	 */
	public function hooks() {

		// Activation hook.
		add_action( 'activate_wp-mail-smtp/wp_mail_smtp.php', array( $this, 'activate' ) );

		add_action( 'plugins_loaded', array( $this, 'get_processor' ) );
		add_action( 'plugins_loaded', array( $this, 'replace_phpmailer' ) );
		add_action( 'plugins_loaded', array( $this, 'init_notifications' ) );

		add_action( 'admin_notices', array( '\WPMailSMTP\WP', 'display_admin_notices' ) );

		add_action( 'init', array( $this, 'init' ) );
	}

	/**
	 * Initial plugin actions.
	 *
	 * @since 1.0.0
	 */
	public function init() {

		// Load translations just in case.
		load_plugin_textdomain( 'wp-mail-smtp', false, plugin_basename( wp_mail_smtp()->plugin_path ) . '/languages' );

		/*
		 * Constantly check in admin area, that we don't need to upgrade DB.
		 * Do not wait for the `admin_init` hook, because some actions are already done
		 * on `plugins_loaded`, so migration has to be done before.
		 */
		if ( WP::in_wp_admin() ) {
			$this->get_migration();
			$this->get_upgrade();
			$this->get_admin();
		}
	}

	/**
	 * Load the plugin core processor.
	 *
	 * @since 1.0.0
	 *
	 * @return Processor
	 */
	public function get_processor() {

		static $processor;

		if ( ! isset( $processor ) ) {
			$processor = apply_filters( 'wp_mail_smtp_core_get_processor', new Processor() );
		}

		return $processor;
	}

	/**
	 * Load the plugin admin area.
	 *
	 * @since 1.0.0
	 *
	 * @return Admin\Area
	 */
	public function get_admin() {

		static $admin;

		if ( ! isset( $admin ) ) {
			$admin = apply_filters( 'wp_mail_smtp_core_get_admin', new Admin\Area() );
		}

		return $admin;
	}

	/**
	 * Load the plugin providers loader.
	 *
	 * @since 1.0.0
	 *
	 * @return Providers\Loader
	 */
	public function get_providers() {

		static $providers;

		if ( ! isset( $providers ) ) {
			$providers = apply_filters( 'wp_mail_smtp_core_get_providers', new Providers\Loader() );
		}

		return $providers;
	}

	/**
	 * Load the plugin option migrator.
	 *
	 * @since 1.0.0
	 *
	 * @return Migration
	 */
	public function get_migration() {

		static $migration;

		if ( ! isset( $migration ) ) {
			$migration = apply_filters( 'wp_mail_smtp_core_get_migration', new Migration() );
		}

		return $migration;
	}

	/**
	 * Load the plugin upgrader.
	 *
	 * @since 1.1.0
	 *
	 * @return Upgrade
	 */
	public function get_upgrade() {

		static $upgrade;

		if ( ! isset( $upgrade ) ) {
			$upgrade = apply_filters( 'wp_mail_smtp_core_get_upgrade', new Upgrade() );
		}

		return $upgrade;
	}

	/**
	 * Awesome Motive Notifications.
	 *
	 * @since 1.0.0
	 */
	public function init_notifications() {

		if ( Options::init()->get( 'general', 'am_notifications_hidden' ) ) {
			return;
		}

		static $notification;

		if ( ! isset( $notification ) ) {
			$notification = new AM_Notification( 'smtp', WPMS_PLUGIN_VER );
		}
	}

	/**
	 * Init the \PHPMailer replacement.
	 *
	 * @since 1.0.0
	 *
	 * @return \WPMailSMTP\MailCatcher
	 */
	public function replace_phpmailer() {
		global $phpmailer;

		return $this->replace_w_fake_phpmailer( $phpmailer );
	}

	/**
	 * Overwrite default PhpMailer with out MailCatcher.
	 *
	 * @since 1.0.0
	 *
	 * @param null $obj
	 *
	 * @return \WPMailSMTP\MailCatcher
	 */
	protected function replace_w_fake_phpmailer( &$obj = null ) {

		$obj = new MailCatcher();

		return $obj;
	}

	/**
	 * What to do on plugin activation.
	 *
	 * @since 1.0.0
	 */
	public function activate() {

		// Store the plugin version activated to reference with upgrades.
		update_option( 'wp_mail_smtp_version', WPMS_PLUGIN_VER );

		// Create and store initial plugin settings.
		$options = array(
			'mail' => array(
				'from_email'  => get_option( 'admin_email' ),
				'from_name'   => get_bloginfo( 'name' ),
				'mailer'      => 'mail',
				'return_path' => false,
			),
			'smtp' => array(
				'autotls' => true,
			),
		);

		Options::init()->set( $options, true );
	}
}
