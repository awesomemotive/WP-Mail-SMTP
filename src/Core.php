<?php

namespace WPMailSMTP;

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
			$this->do_not_load();

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
	 * What to do if plugin is not loaded.
	 *
	 * @since 1.5.0
	 */
	protected function do_not_load() {

		add_action( 'admin_notices', function () {

			?>
			<div class="notice notice-error">
				<p>
					<?php
					printf(
						wp_kses( /* translators: %1$s - WPBeginner URL for recommended WordPress hosting. */
							__( 'Your site is running an <strong>insecure version</strong> of PHP that is no longer supported. Please contact your web hosting provider to update your PHP version or switch to a <a href="%1$s" target="_blank" rel="noopener noreferrer">recommended WordPress hosting company</a>.', 'wp-mail-smtp' ),
							array(
								'a'      => array(
									'href'   => array(),
									'target' => array(),
									'rel'    => array(),
								),
								'strong' => array(),
							)
						),
						'https://www.wpbeginner.com/wordpress-hosting/'
					);
					?>
					<br><br>
					<?php
					printf(
						wp_kses( /* translators: %s - WPForms.com URL for documentation with more details. */
							__( '<strong>Note:</strong> WP Mail SMTP plugin is disabled on your site until you fix the issue. <a href="%s" target="_blank" rel="noopener noreferrer">Read more for additional information.</a>', 'wp-mail-smtp' ),
							array(
								'a'      => array(
									'href'   => array(),
									'target' => array(),
									'rel'    => array(),
								),
								'strong' => array(),
							)
						),
						'https://wpforms.com/docs/supported-php-version/'
					);
					?>
				</p>
			</div>

			<?php

			// In case this is on plugin activation.
			if ( isset( $_GET['activate'] ) ) { //phpcs:ignore
				unset( $_GET['activate'] ); //phpcs:ignore
			}
		} );
	}

	/**
	 * Assign all hooks to proper places.
	 *
	 * @since 1.0.0
	 */
	public function hooks() {

		// Activation hook.
		add_action( 'activate_plugin', array( $this, 'activate' ), 10, 2 );

		// Redefine PHPMailer.
		add_action( 'plugins_loaded', array( $this, 'get_processor' ) );
		add_action( 'plugins_loaded', array( $this, 'replace_phpmailer' ) );

		// Various notifications.
		add_action( 'admin_init', array( $this, 'init_notifications' ) );

		add_action( 'init', array( $this, 'init' ) );
	}

	/**
	 * Initial plugin actions.
	 *
	 * @since 1.0.0
	 * @since 1.5.0 Added Pro version initialization.
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
		}

		// Plugin admin area notices. Display to "admins" only.
		if ( current_user_can( 'manage_options' ) ) {
			add_action( 'admin_notices', array( '\WPMailSMTP\WP', 'display_admin_notices' ) );
			add_action( 'admin_notices', array( $this, 'display_general_notices' ) );
		}

		/*
		 * Should be the last thing here to be able to overwrite anything from the above.
		 */
		if ( $this->is_pro_allowed() ) {
			$this->pro = new \WPMailSMTP\Pro\Pro();
		}
	}

	/**
	 * Whether the Pro part of the plugin is allowed to be loaded.
	 *
	 * @since 1.5.0
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

		return $is_allowed;
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
					wp_kses( /* translators: %1$s - WP Mail SMTP plugin name; %2$s - WPForms.com URL to a related doc. */
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
					'https://wpforms.com/docs/supported-php-version/'
				) .
				'<br><br><em>' .
				wp_kses(
					__( '<strong>Please Note:</strong> Support for PHP 5.3-5.5 will be discontinued in 2019. After this, if no further action is taken, WP Mail SMTP functionality will be disabled.', 'wp-mail-smtp' ),
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

		static $notification;

		if ( ! isset( $notification ) ) {
			$notification = new AM_Notification( 'smtp', WPMS_PLUGIN_VER );
		}
	}

	/**
	 * Display all debug mail-delivery related notices.
	 *
	 * @since 1.3.0
	 */
	public static function display_general_notices() {

		if ( Options::init()->get( 'general', 'do_not_send' ) ) {
			?>

			<div class="notice <?php echo esc_attr( WP::ADMIN_NOTICE_ERROR ); ?>">
				<p>
					<?php
					printf(
						wp_kses( /* translators: %1$s - plugin name and its version, %2$s - plugin Misc settings page. */
							__( '<strong>EMAILING DISABLED:</strong> The %1$s is currently blocking all emails from being sent. To send emails, go to plugin <a href="%2$s">Misc settings</a> and disable the "Do Not Send" option.', 'wp-mail-smtp' ),
							array(
								'strong' => array(),
								'a'      => array(
									'href' => array(),
								),
							)
						),
						esc_html( 'WP Mail SMTP v' . WPMS_PLUGIN_VER ),
						esc_url( add_query_arg( 'tab', 'misc', wp_mail_smtp()->get_admin()->get_admin_page_url() ) )
					);
					?>
				</p>
			</div>

			<?php
			return;
		}

		$notice = Debug::get_last();

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
			return;
		}
		?>

		<?php
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
	 * @return \WPMailSMTP\MailCatcher
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
	 * @return \WPMailSMTP\MailCatcher
	 */
	protected function replace_w_fake_phpmailer( &$obj = null ) {

		$obj = new MailCatcher( true );

		return $obj;
	}

	/**
	 * What to do on plugin activation.
	 *
	 * @since 1.0.0
	 *
	 * @param string $plugin       Path to the plugin file relative to the plugins directory.
	 * @param bool   $network_wide Whether to enable the plugin for all sites in the network
	 *                             or just the current site. Multisite only. Default is false.
	 */
	public function activate( $plugin, $network_wide ) {

		// Store the plugin version when initial install occurred.
		add_option( 'wp_mail_smtp_initial_version', WPMS_PLUGIN_VER, '', false );

		// Store the plugin version activated to reference with upgrades.
		update_option( 'wp_mail_smtp_version', WPMS_PLUGIN_VER, false );

		// Save default options, only once.
		Options::init()->set( Options::get_defaults(), true );
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

		return apply_filters(
			'wp_mail_smtp_core_get_upgrade_link',
			'https://wpmailsmtp.com/lite-upgrade/?utm_source=' . esc_attr( $source ) . '&utm_medium=' . esc_attr( $medium ) . '&utm_campaign=' . esc_attr( $campaign ) . '&utm_content=' . esc_attr( $content )
		);
	}
}
