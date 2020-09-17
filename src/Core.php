<?php

namespace WPMailSMTP;

use WPMailSMTP\Admin\AdminBarMenu;
use WPMailSMTP\Admin\Notifications;
use WPMailSMTP\UsageTracking\UsageTracking;

/**
 * Class Core to handle all plugin initialization.
 *
 * @since 1.0.0
 */
class Core {

	/**
	 * URL to plugin directory.
	 *
	 * @since 1.0.0
	 *
	 * @var string Without trailing slash.
	 */
	public $plugin_url;

	/**
	 * URL to Lite plugin assets directory.
	 *
	 * @since 1.5.0
	 *
	 * @var string Without trailing slash.
	 */
	public $assets_url;

	/**
	 * Path to plugin directory.
	 *
	 * @since 1.0.0
	 *
	 * @var string Without trailing slash.
	 */
	public $plugin_path;

	/**
	 * Shortcut to get access to Pro functionality using wp_mail_smtp()->pro->example().
	 *
	 * @since 1.5.0
	 *
	 * @var \WPMailSMTP\Pro\Pro
	 */
	public $pro;

	/**
	 * Core constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		$this->plugin_url  = rtrim( plugin_dir_url( __DIR__ ), '/\\' );
		$this->assets_url  = $this->plugin_url . '/assets';
		$this->plugin_path = rtrim( plugin_dir_path( __DIR__ ), '/\\' );

		if ( $this->is_not_loadable() ) {
			add_action( 'admin_notices', 'wp_mail_smtp_insecure_php_version_notice' );

			if ( WP::use_global_plugin_settings() ) {
				add_action( 'network_admin_notices', 'wp_mail_smtp_insecure_php_version_notice' );
			}

			return;
		}

		// Finally, load all the plugin.
		$this->hooks();
		$this->init_early();
	}

	/**
	 * Currently used for Pro version only.
	 *
	 * @since 1.5.0
	 *
	 * @return bool
	 */
	protected function is_not_loadable() {

		// Check the Pro.
		if (
			is_readable( $this->plugin_path . '/src/Pro/Pro.php' ) &&
			! $this->is_pro_allowed()
		) {
			// So there is a Pro version, but its PHP version check failed.
			return true;
		}

		return false;
	}

	/**
	 * Assign all hooks to proper places.
	 *
	 * @since 1.0.0
	 */
	public function hooks() {

		// Force from_email_force to always return true if current mailer is Gmail.
		if ( ( new Options() )->get( 'mail', 'mailer' ) === 'gmail' ) {
			add_filter( 'wp_mail_smtp_options_get', [ $this, 'gmail_mailer_get_from_email_force' ], 1, 3 );
		}

		// Action Scheduler requires a special early loading procedure.
		add_action( 'plugins_loaded', [ $this, 'load_action_scheduler' ], - 10 );

		// Activation hook.
		register_activation_hook( WPMS_PLUGIN_FILE, [ $this, 'activate' ] );

		// Redefine PHPMailer.
		add_action( 'plugins_loaded', [ $this, 'get_processor' ] );
		add_action( 'plugins_loaded', [ $this, 'replace_phpmailer' ] );

		// Various notifications.
		add_action( 'admin_init', [ $this, 'init_notifications' ] );

		add_action( 'init', [ $this, 'init' ] );

		// Initialize Action Scheduler tasks.
		add_action( 'init', [ $this, 'get_tasks' ], 5 );

		add_action( 'plugins_loaded', [ $this, 'get_pro' ] );
		add_action( 'plugins_loaded', [ $this, 'get_usage_tracking' ] );
		add_action( 'plugins_loaded', [ $this, 'get_admin_bar_menu' ] );
		add_action( 'plugins_loaded', [ $this, 'get_notifications' ] );
	}

	/**
	 * Initial plugin actions.
	 *
	 * @since 1.0.0
	 */
	public function init() {

		// Load translations just in case.
		load_plugin_textdomain( 'wp-mail-smtp', false, plugin_basename( wp_mail_smtp()->plugin_path ) . '/assets/languages' );

		/*
		 * Constantly check in admin area, that we don't need to upgrade DB.
		 * Do not wait for the `admin_init` hook, because some actions are already done
		 * on `plugins_loaded`, so migration has to be done before.
		 * We should not fire this in AJAX requests.
		 */
		if ( WP::in_wp_admin() ) {
			$this->get_migration();
			$this->get_upgrade();
			$this->detect_conflicts();
		}

		// In admin area, regardless of AJAX or not AJAX request.
		if ( is_admin() ) {
			$this->get_admin();
			$this->get_site_health()->init();
		}

		// Plugin admin area notices. Display to "admins" only.
		if ( current_user_can( 'manage_options' ) ) {
			add_action( 'admin_notices', array( '\WPMailSMTP\WP', 'display_admin_notices' ) );
			add_action( 'admin_notices', array( $this, 'display_general_notices' ) );

			if ( WP::use_global_plugin_settings() ) {
				add_action( 'network_admin_notices', array( '\WPMailSMTP\WP', 'display_admin_notices' ) );
				add_action( 'network_admin_notices', array( $this, 'display_general_notices' ) );
			}
		}
	}

	/**
	 * Whether the Pro part of the plugin is allowed to be loaded.
	 *
	 * @since 1.5.0
	 * @since 1.6.0 Added a filter.
	 *
	 * @return bool
	 */
	protected function is_pro_allowed() {

		$is_allowed = true;

		if ( ! is_readable( $this->plugin_path . '/src/Pro/Pro.php' ) ) {
			$is_allowed = false;
		}

		if ( version_compare( phpversion(), '5.6', '<' ) ) {
			$is_allowed = false;
		}

		return apply_filters( 'wp_mail_smtp_core_is_pro_allowed', $is_allowed );
	}

	/**
	 * Get/Load the Pro code of the plugin if it exists.
	 *
	 * @since 1.6.2
	 *
	 * @return \WPMailSMTP\Pro\Pro
	 */
	public function get_pro() {

		if ( ! $this->is_pro_allowed() ) {
			return $this->pro;
		}

		if ( ! $this->is_pro() ) {
			$this->pro = new \WPMailSMTP\Pro\Pro();
		}

		return $this->pro;
	}

	/**
	 * Get/Load the Tasks code of the plugin.
	 *
	 * @since 2.1.0
	 *
	 * @return \WPMailSMTP\Tasks\Tasks
	 */
	public function get_tasks() {

		static $tasks;

		if ( ! isset( $tasks ) ) {
			$tasks = apply_filters( 'wp_mail_smtp_core_get_tasks', new Tasks\Tasks() );
			$tasks->init();
		}

		return $tasks;
	}

	/**
	 * This method allows to overwrite certain core WP functions, because it's fired:
	 *  - after `muplugins_loaded` hook,
	 *  - before WordPress own `wp-includes/pluggable.php` file include,
	 *  - before `plugin_loaded` and `plugins_loaded` hooks.
	 *
	 * @since 1.5.0
	 */
	protected function init_early() {

		$pro_files = $this->is_pro_allowed() ? \WPMailSMTP\Pro\Pro::PLUGGABLE_FILES : array();

		$files = (array) apply_filters( 'wp_mail_smtp_core_init_early_include_files', $pro_files );

		foreach ( $files as $file ) {
			$path = $this->plugin_path . '/' . $file;

			if ( is_readable( $path ) ) {
				/** @noinspection PhpIncludeInspection */
				include_once $path;
			}
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

			if ( method_exists( $processor, 'hooks' ) ) {
				$processor->hooks();
			}
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
	 * Get the plugin's WP Site Health object.
	 *
	 * @since 1.9.0
	 *
	 * @return SiteHealth
	 */
	public function get_site_health() {

		static $site_health;

		if ( ! isset( $site_health ) ) {
			$site_health = apply_filters( 'wp_mail_smtp_core_get_site_health', new SiteHealth() );
		}

		return $site_health;
	}

	/**
	 * Display various notifications to a user
	 *
	 * @since 1.0.0
	 */
	public function init_notifications() {

		// Old PHP version notification.
		if (
			version_compare( phpversion(), '5.6', '<' ) &&
			is_super_admin() &&
			(
				isset( $GLOBALS['pagenow'] ) &&
				$GLOBALS['pagenow'] === 'index.php'
			)
		) {
			WP::add_admin_notice(
				sprintf(
					wp_kses( /* translators: %1$s - WP Mail SMTP plugin name; %2$s - WPMailSMTP.com URL to a related doc. */
						__( 'Your site is running an outdated version of PHP that is no longer supported and may cause issues with %1$s. <a href="%2$s" target="_blank" rel="noopener noreferrer">Read more</a> for additional information.', 'wp-mail-smtp' ),
						array(
							'a' => array(
								'href'   => array(),
								'target' => array(),
								'rel'    => array(),
							),
						)
					),
					'<strong>WP Mail SMTP</strong>',
					'https://wpmailsmtp.com/docs/supported-php-versions-for-wp-mail-smtp/'
				) .
				'<br><br><em>' .
				wp_kses(
					__( '<strong>Please Note:</strong> Support for PHP 5.5 will be discontinued in 2020. After this, if no further action is taken, WP Mail SMTP functionality will be disabled.', 'wp-mail-smtp' ),
					array(
						'strong' => array(),
						'em'     => array(),
					)
				) .
				'</em>',
				WP::ADMIN_NOTICE_ERROR,
				false
			);
		}

		// Awesome Motive Notifications.
		if ( Options::init()->get( 'general', 'am_notifications_hidden' ) ) {
			return;
		}
	}

	/**
	 * Display all debug mail-delivery related notices.
	 *
	 * @since 1.3.0
	 * @since 1.6.0 Added a filter that allows to hide debug errors.
	 */
	public static function display_general_notices() {

		if ( wp_mail_smtp()->is_blocked() ) {
			?>

			<div class="notice <?php echo esc_attr( WP::ADMIN_NOTICE_ERROR ); ?>">
				<p>
					<?php
					$notices[] = sprintf(
						wp_kses( /* translators: %s - plugin name and its version. */
							__( '<strong>EMAILING DISABLED:</strong> The %s is currently blocking all emails from being sent.', 'wp-mail-smtp' ),
							array(
								'strong' => true,
							)
						),
						esc_html( 'WP Mail SMTP v' . WPMS_PLUGIN_VER )
					);

					if ( Options::init()->is_const_defined( 'general', 'do_not_send' ) ) {
						$notices[] = sprintf(
							wp_kses( /* translators: %1$s - constant name; %2$s - constant value. */
								__( 'To send emails, change the value of the %1$s constant to %2$s.', 'wp-mail-smtp' ),
								array(
									'code' => true,
								)
							),
							'<code>WPMS_DO_NOT_SEND</code>',
							'<code>false</code>'
						);
					} else {
						$notices[] = sprintf(
							wp_kses( /* translators: %s - plugin Misc settings page URL. */
								__( 'To send emails, go to plugin <a href="%s">Misc settings</a> and disable the "Do Not Send" option.', 'wp-mail-smtp' ),
								array(
									'a' => array(
										'href' => true,
									),
								)
							),
							esc_url( add_query_arg( 'tab', 'misc', wp_mail_smtp()->get_admin()->get_admin_page_url() ) )
						);
					}

					echo implode( ' ', $notices );
					?>
				</p>
			</div>

			<?php
			return;
		}

		if ( wp_mail_smtp()->get_admin()->is_error_delivery_notice_enabled() ) {
			$screen = get_current_screen();

			// Skip the error notice if not on plugin page.
			if (
				is_object( $screen ) &&
				strpos( $screen->id, 'page_wp-mail-smtp' ) === false
			) {
				return;
			}

			$notice = apply_filters(
				'wp_mail_smtp_core_display_general_notices_email_delivery_error_notice',
				Debug::get_last()
			);

			if ( ! empty( $notice ) ) {
				?>

				<div class="notice <?php echo esc_attr( WP::ADMIN_NOTICE_ERROR ); ?>">
					<p>
						<?php
						printf(
							wp_kses( /* translators: %s - plugin name and its version. */
								__( '<strong>EMAIL DELIVERY ERROR:</strong> the plugin %s logged this error during the last time it tried to send an email:', 'wp-mail-smtp' ),
								array(
									'strong' => array(),
								)
							),
							esc_html( 'WP Mail SMTP v' . WPMS_PLUGIN_VER )
						);
						?>
					</p>

					<blockquote>
						<pre><?php echo $notice; ?></pre>
					</blockquote>

					<p>
						<?php
						if ( ! wp_mail_smtp()->get_admin()->is_admin_page() ) {
							printf(
								wp_kses( /* translators: %s - plugin admin page URL. */
									__( 'Please review your WP Mail SMTP settings in <a href="%s">plugin admin area</a>.' ) . ' ',
									array(
										'a' => array(
											'href' => array(),
										),
									)
								),
								esc_url( wp_mail_smtp()->get_admin()->get_admin_page_url() )
							);
						}

						esc_html_e( 'Consider running an email test after fixing it.', 'wp-mail-smtp' );
						?>
					</p>
				</div>

				<?php
			}
		}
	}

	/**
	 * Check whether we are working with a new plugin install.
	 *
	 * @since 1.3.0
	 *
	 * @return bool
	 */
	protected function is_new_install() {

		/*
		 * No previously installed 0.*.
		 * 'wp_mail_smtp_initial_version' option appeared in 1.3.0. So we make sure it exists.
		 * No previous plugin upgrades.
		 */
		if (
			! get_option( 'mailer', false ) &&
			get_option( 'wp_mail_smtp_initial_version', false ) &&
			version_compare( WPMS_PLUGIN_VER, get_option( 'wp_mail_smtp_initial_version' ), '=' )
		) {
			return true;
		}

		return false;
	}

	/**
	 * Detect if there are plugins activated that will cause a conflict.
	 *
	 * @since 1.3.0
	 * @since 1.5.0 Moved the logic to Conflicts class.
	 */
	public function detect_conflicts() {

		// Display only for those who can actually deactivate plugins.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$conflicts = new Conflicts();

		if ( $conflicts->is_detected() ) {
			$conflicts->notify();
		}
	}

	/**
	 * Init the \PHPMailer replacement.
	 *
	 * @since 1.0.0
	 *
	 * @return MailCatcherInterface
	 */
	public function replace_phpmailer() {

		global $phpmailer;

		return $this->replace_w_fake_phpmailer( $phpmailer );
	}

	/**
	 * Overwrite default PhpMailer with our MailCatcher.
	 *
	 * @since 1.0.0
	 * @since 1.5.0 Throw external PhpMailer exceptions, inherits default WP behavior.
	 *
	 * @param null $obj PhpMailer object to override with own implementation.
	 *
	 * @return MailCatcherInterface
	 */
	protected function replace_w_fake_phpmailer( &$obj = null ) {

		$obj = $this->generate_mail_catcher( true );

		return $obj;
	}

	/**
	 * What to do on plugin activation.
	 *
	 * @since 1.0.0
	 * @since 2.0.0 Changed from general `plugin_activate` hook to this plugin specific activation hook.
	 */
	public function activate() {

		// Store the plugin version when initial install occurred.
		add_option( 'wp_mail_smtp_initial_version', WPMS_PLUGIN_VER, '', false );

		// Store the plugin version activated to reference with upgrades.
		update_option( 'wp_mail_smtp_version', WPMS_PLUGIN_VER, false );

		// Save default options, only once.
		Options::init()->set( Options::get_defaults(), true );

		/**
		 * Store the timestamp of first plugin activation.
		 *
		 * @since 2.1.0
		 */
		add_option( 'wp_mail_smtp_activated_time', time(), '', false );

		/**
		 * Store the timestamp of the first plugin activation by license type.
		 *
		 * @since 2.3.0
		 */
		$license_type = is_readable( $this->plugin_path . '/src/Pro/Pro.php' ) ? 'pro' : 'lite';
		$activated    = get_option( 'wp_mail_smtp_activated', [] );

		if ( empty( $activated[ $license_type ] ) ) {
			$activated[ $license_type ] = time();
			update_option( 'wp_mail_smtp_activated', $activated );
		}
	}

	/**
	 * Whether this is a Pro version of a plugin.
	 *
	 * @since 1.5.0
	 *
	 * @return bool
	 */
	public function is_pro() {

		return apply_filters( 'wp_mail_smtp_core_is_pro', ! empty( $this->pro ) );
	}

	/**
	 * Get the current license type.
	 *
	 * @since 1.5.0
	 *
	 * @return string Default value: lite.
	 */
	public function get_license_type() {

		$type = Options::init()->get( 'license', 'type' );

		if ( empty( $type ) ) {
			$type = 'lite';
		}

		return strtolower( $type );
	}

	/**
	 * Get the current license key.
	 *
	 * @since 1.5.0
	 *
	 * @return string
	 */
	public function get_license_key() {

		$key = Options::init()->get( 'license', 'key' );

		if ( empty( $key ) ) {
			$key = '';
		}

		return $key;
	}

	/**
	 * Upgrade link used within the various admin pages.
	 *
	 * @since 1.5.0
	 * @since 1.5.1 Support all UTM params.
	 *
	 * @param array|string $utm Array of UTM params, or if string provided - utm_content URL parameter.
	 *
	 * @return string
	 */
	public function get_upgrade_link( $utm ) {

		// Defaults.
		$source   = 'WordPress';
		$medium   = 'plugin-settings';
		$campaign = 'liteplugin';
		$content  = 'general';

		if ( is_array( $utm ) ) {
			if ( isset( $utm['source'] ) ) {
				$source = $utm['source'];
			}
			if ( isset( $utm['medium'] ) ) {
				$medium = $utm['medium'];
			}
			if ( isset( $utm['campaign'] ) ) {
				$campaign = $utm['campaign'];
			}
			if ( isset( $utm['content'] ) ) {
				$content = $utm['content'];
			}
		} elseif ( is_string( $utm ) ) {
			$content = $utm;
		}

		$url = 'https://wpmailsmtp.com/lite-upgrade/?utm_source=' . esc_attr( $source ) . '&utm_medium=' . esc_attr( $medium ) . '&utm_campaign=' . esc_attr( $campaign );

		if ( ! empty( $content ) ) {
			$url .= '&utm_content=' . esc_attr( $content );
		}

		return apply_filters( 'wp_mail_smtp_core_get_upgrade_link', $url );
	}

	/**
	 * Whether the emailing functionality is blocked, with either an option or a constatnt.
	 *
	 * @since 1.7.0
	 *
	 * @return bool
	 */
	public function is_blocked() {

		return (bool) Options::init()->get( 'general', 'do_not_send' );
	}

	/**
	 * Whether the white-labeling is enabled.
	 * White-labeling disables the plugin "About us" page, it replaces any plugin marketing texts or images with
	 * white label ones.
	 *
	 * @since 2.0.0
	 *
	 * @return bool
	 */
	public function is_white_labeled() {

		return (bool) apply_filters( 'wp_mail_smtp_is_white_labeled', false );
	}

	/**
	 * Require the action scheduler in an early plugins_loaded hook (-10).
	 *
	 * @see   https://actionscheduler.org/usage/#load-order
	 *
	 * @since 2.1.0
	 */
	public function load_action_scheduler() {

		require_once $this->plugin_path . '/vendor/woocommerce/action-scheduler/action-scheduler.php';
	}

	/**
	 * Get the list of all custom DB tables that should be present in the DB.
	 *
	 * @since 2.1.2
	 *
	 * @return array List of table names.
	 */
	public function get_custom_db_tables() {

		$tables = [
			\WPMailSMTP\Tasks\Meta::get_table_name(),
		];

		return apply_filters( 'wp_mail_smtp_core_get_custom_db_tables', $tables );
	}

	/**
	 * Generate the correct MailCatcher object based on the PHPMailer version used in WP.
	 *
	 * Also conditionally require the needed class files.
	 *
	 * @see   https://make.wordpress.org/core/2020/07/01/external-library-updates-in-wordpress-5-5-call-for-testing/
	 *
	 * @since 2.2.0
	 *
	 * @param bool $exceptions True if external exceptions should be thrown.
	 *
	 * @return MailCatcherInterface
	 */
	public function generate_mail_catcher( $exceptions = null ) {

		if ( version_compare( get_bloginfo( 'version' ), '5.5-alpha', '<' ) ) {
			if ( ! class_exists( '\PHPMailer', false ) ) {
				require_once ABSPATH . WPINC . '/class-phpmailer.php';
			}

			$mail_catcher = new MailCatcher( $exceptions );
		} else {
			if ( ! class_exists( '\PHPMailer\PHPMailer\PHPMailer', false ) ) {
				require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
			}

			if ( ! class_exists( '\PHPMailer\PHPMailer\Exception', false ) ) {
				require_once ABSPATH . WPINC . '/PHPMailer/Exception.php';
			}

			if ( ! class_exists( '\PHPMailer\PHPMailer\SMTP', false ) ) {
				require_once ABSPATH . WPINC . '/PHPMailer/SMTP.php';
			}

			$mail_catcher = new MailCatcherV6( $exceptions );
		}

		return $mail_catcher;
	}

	/**
	 * Check if the passed object is a valid PHPMailer object.
	 *
	 * @since 2.2.0
	 *
	 * @param object $phpmailer A potential PHPMailer object to be tested.
	 *
	 * @return bool
	 */
	public function is_valid_phpmailer( $phpmailer ) {

		return $phpmailer instanceof MailCatcherInterface ||
		       $phpmailer instanceof \PHPMailer ||
		       $phpmailer instanceof \PHPMailer\PHPMailer\PHPMailer;
	}

	/**
	 * Force the `mail.from_email_force` plugin option to always return true if the current saved mailer is Gmail.
	 * Alters the plugin options retrieving via the Options::get method.
	 *
	 * The gmail mailer check is performed when this filter is added.
	 *
	 * @since 2.2.0
	 *
	 * @param mixed  $value The value of the plugin option that is being retrieved via Options::get method.
	 * @param string $group The group of the plugin option that is being retrieved via Options::get method.
	 * @param string $key   The key of the plugin option that is being retrieved via Options::get method.
	 *
	 * @return mixed
	 */
	public function gmail_mailer_get_from_email_force( $value, $group, $key ) {

		if ( $group === 'mail' && $key === 'from_email_force' ) {
			$value = true;
		}

		return $value;
	}

	/**
	 * Load the plugin admin bar menu and initialize it.
	 *
	 * @since 2.3.0
	 *
	 * @return AdminBarMenu
	 */
	public function get_admin_bar_menu() {

		static $admin_bar_menu;

		if ( ! isset( $admin_bar_menu ) ) {
			$admin_bar_menu = apply_filters(
				'wp_mail_smtp_core_get_admin_bar_menu',
				new AdminBarMenu()
			);

			if ( method_exists( $admin_bar_menu, 'init' ) ) {
				$admin_bar_menu->init();
			}
		}

		return $admin_bar_menu;
	}

	/**
	 * Load the plugin usage tracking.
	 *
	 * @since 2.3.0
	 *
	 * @return UsageTracking
	 */
	public function get_usage_tracking() {

		static $usage_tracking;

		if ( ! isset( $usage_tracking ) ) {
			$usage_tracking = apply_filters( 'wp_mail_smtp_core_get_usage_tracking', new UsageTracking() );

			if ( method_exists( $usage_tracking, 'load' ) ) {
				$usage_tracking->load();
			}
		}

		return $usage_tracking;
	}

	/**
	 * Load the plugin admin notifications functionality and initializes it.
	 *
	 * @since 2.3.0
	 *
	 * @return Notifications
	 */
	public function get_notifications() {

		static $notifications;

		if ( ! isset( $notifications ) ) {
			$notifications = apply_filters(
				'wp_mail_smtp_core_get_notifications',
				new Notifications()
			);

			if ( method_exists( $notifications, 'init' ) ) {
				$notifications->init();
			}
		}

		return $notifications;
	}

	/**
	 * Prepare the HTML output for a plugin loader/spinner.
	 *
	 * @since 2.4.0
	 *
	 * @param string $color The color of the loader ('', 'blue' or 'white'), where '' is default orange.
	 * @param string $size  The size of the loader ('lg', 'md', 'sm').
	 *
	 * @return string
	 */
	public function prepare_loader( $color = '', $size = 'md' ) {

		$svg_name = 'loading';

		if ( in_array( $color, [ 'blue', 'white' ], true ) ) {
			$svg_name .= '-' . $color;
		}

		if ( ! in_array( $size, [ 'lg', 'md', 'sm' ], true ) ) {
			$size = 'md';
		}

		return '<img src="' . esc_url( $this->plugin_url . '/assets/images/loaders/' . $svg_name . '.svg' ) . '" alt="' . esc_html__( 'Loading', 'wp-mail-smtp' ) . '" class="wp-mail-smtp-loading wp-mail-smtp-loading-' . $size . '">';
	}
}
