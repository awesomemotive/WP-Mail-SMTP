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

		// Redefine PHPMailer.
		add_action( 'plugins_loaded', array( $this, 'get_processor' ) );
		add_action( 'plugins_loaded', array( $this, 'replace_phpmailer' ) );

		// Awesome Motive Notifications.
		add_action( 'plugins_loaded', array( $this, 'init_notifications' ) );

		// Recommendations.
		if ( ! class_exists( '\WPMailSMTP\TGM_Plugin_Activation', false ) ) {
			require_once __DIR__ . '/TGMPA.php';
		}
		add_action( 'wpms_tgmpa_register', array( $this, 'init_recommendations' ) );

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
	 * Recommend WPForms Lite using TGM Activation.
	 *
	 * @since 1.3.0
	 * @since 1.4.0 Display to site admins only.
	 */
	public function init_recommendations() {

		// Recommend only fot site admins who can install plugins.
		if ( ! is_super_admin() ) {
			return;
		}

		// Recommend only for new installs.
		if ( ! $this->is_new_install() ) {
			return;
		}

		// Specify a plugin that we want to recommend.
		$plugins = apply_filters(
			'wp_mail_smtp_core_recommendations_plugins',
			array(
				array(
					'name'        => 'Contact Form by WPForms',
					'slug'        => 'wpforms-lite',
					'required'    => false,
					'is_callable' => 'wpforms', // This will target the Pro version as well, not only the one from WP.org repository.
				),
			)
		);

		/*
		 * Array of configuration settings.
		 */
		$config = apply_filters(
			'wp_mail_smtp_core_recommendations_config',
			array(
				'id'           => 'wp-mail-smtp',
				// Unique ID for hashing notices for multiple instances of TGMPA.
				'menu'         => 'wp-mail-smtp-install-plugins',
				// Menu slug.
				'parent_slug'  => 'plugins.php',
				// Parent menu slug.
				'capability'   => 'manage_options',
				// Capability needed to view plugin install page, should be a capability associated with the parent menu used.
				'has_notices'  => true,
				// Show admin notices or not.
				'dismissable'  => true,
				// If false, a user cannot dismiss the nag message.
				'dismiss_msg'  => '',
				// If 'dismissable' is false, this message will be output at top of nag.
				'is_automatic' => false,
				// Automatically activate plugins after installation or not.
				'message'      => '',
				// Message to output right before the plugins table.
				'strings'      => array(
					'page_title'                      => esc_html__( 'Install Recommended Plugin', 'wp-mail-smtp' ),
					'menu_title'                      => esc_html__( 'Recommended', 'wp-mail-smtp' ),
					/* translators: 1: plugin name(s). */
					'notice_can_install_recommended'  => _n_noop(
						'Thanks for installing WP Mail SMTP. We also recommend using %1$s. It\'s the best drag & drop form builder, has over 1 million active installs, and over 2000+ 5 star ratings.',
						'Thanks for installing WP Mail SMTP. We also recommend using %1$s. It\'s the best drag & drop form builder, has over 1 million active installs, and over 2000+ 5 star ratings.',
						'wp-mail-smtp'
					),
					/* translators: 1: plugin name(s). */
					'notice_can_activate_recommended' => _n_noop(
						'Thanks for installing WP Mail SMTP. We also recommend using %1$s. It\'s the best drag & drop form builder, has over 1 million active installs, and over 2000+ 5 star ratings.',
						'Thanks for installing WP Mail SMTP. We also recommend using %1$s. It\'s the best drag & drop form builder, has over 1 million active installs, and over 2000+ 5 star ratings.',
						'wp-mail-smtp'
					),
					'install_link'                    => _n_noop( 'Install WPForms Now', 'Begin installing plugins', 'wp-mail-smtp' ),
					'activate_link'                   => _n_noop( 'Activate WPForms', 'Begin activating plugins', 'wp-mail-smtp' ),
					'return'                          => esc_html__( 'Return to Recommended Plugin Installer', 'wp-mail-smtp' ),
					/* translators: 1: dashboard link. */
					'complete'                        => esc_html__( 'The recommended plugin was installed and activated successfully. %1$s', 'wp-mail-smtp' ),
					'notice_cannot_install_activate'  => esc_html__( 'There is one recommended plugin to install, update or activate.', 'wp-mail-smtp' ),
					'nag_type'                        => 'notice-info',
				),
			)
		);

		\WPMailSMTP\tgmpa( (array) $plugins, (array) $config );
	}

	/**
	 * Display all debug mail-delivery related notices.
	 *
	 * @since 1.3.0
	 */
	public static function display_general_notices() {

		if ( Options::init()->get( 'general', 'do_not_send' ) ) {
			?>

			<div id="message" class="<?php echo WP::ADMIN_NOTICE_ERROR; ?> notice">
				<p>
					<?php
					printf(
						wp_kses(
							/* translators: %1$s - plugin name and its version, %2$s - plugin Misc settings page. */
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

			<div id="message" class="<?php echo WP::ADMIN_NOTICE_ERROR; ?> notice">
				<p>
					<?php
					printf(
						wp_kses(
							/* translators: %s - plugin name and its version. */
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
							wp_kses(
								/* translators: %s - plugin admin page URL. */
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
	 */
	public function detect_conflicts() {

		// Display only for those who can actually deactivate plugins.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$conflicts = array(
			'swpsmtp_init_smtp'    => array(
				'name' => 'Easy WP SMTP',
			),
			'postman_start'        => array(
				'name' => 'Postman SMTP',
			),
			'post_start'           => array(
				'name' => 'Post SMTP Mailer/Email Log',
			),
			'mail_bank'            => array(
				'name' => 'WP Mail Bank',
			),
			'SMTP_MAILER'          => array(
				'name'  => 'SMTP Mailer',
				'class' => true,
			),
			'GMAIL_SMTP'           => array(
				'name'  => 'Gmail SMTP',
				'class' => true,
			),
			'WP_Email_Smtp'        => array(
				'name'  => 'WP Email SMTP',
				'class' => true,
			),
			'smtpmail_include'     => array(
				'name' => 'SMTP Mail',
			),
			'bwssmtp_init'         => array(
				'name' => 'SMTP by BestWebSoft',
			),
			'WPSendGrid_SMTP'      => array(
				'name'  => 'WP SendGrid SMTP',
				'class' => true,
			),
			'sar_friendly_smtp'    => array(
				'name' => 'SAR Friendly SMTP',
			),
			'WPGmail_SMTP'         => array(
				'name'  => 'WP Gmail SMTP',
				'class' => true,
			),
			'st_smtp_check_config' => array(
				'name' => 'Cimy Swift SMTP',
			),
			'WP_Easy_SMTP'         => array(
				'name'  => 'WP Easy SMTP',
				'class' => true,
			),
			'WPMailgun_SMTP'       => array(
				'name'  => 'WP Mailgun SMTP',
				'class' => true,
			),
			'my_smtp_wp'           => array(
				'name' => 'MY SMTP WP',
			),
			'mail_booster'         => array(
				'name' => 'WP Mail Booster',
			),
			'Sendgrid_Settings'    => array(
				'name'  => 'SendGrid',
				'class' => true,
			),
			'WPMS_php_mailer'      => array(
				'name' => 'WP Mail Smtp Mailer',
			),
			'WPAmazonSES_SMTP'     => array(
				'name'  => 'WP Amazon SES SMTP',
				'class' => true,
			),
			'Postmark_Mail'        => array(
				'name'  => 'Postmark for WordPress',
				'class' => true,
			),
			'Mailgun'              => array(
				'name'  => 'Mailgun',
				'class' => true,
			),
			'SparkPost'            => array(
				'name'  => 'SparkPost',
				'class' => true,
			),
			'WPYahoo_SMTP'         => array(
				'name'  => 'WP Yahoo SMTP',
				'class' => true,
			),
			'wpses_init'           => array(
				'name'  => 'WP SES',
				'class' => true,
			),
			'TSPHPMailer'          => array(
				'name' => 'turboSMTP',
			),
		);

		foreach ( $conflicts as $id => $conflict ) {
			if ( ! empty( $conflict['class'] ) ) {
				$detected = class_exists( $id, false );
			} else {
				$detected = function_exists( $id );
			}

			if ( $detected ) {
				WP::add_admin_notice(
					sprintf(
						/* translators: %1$s - Plugin name causing conflict; %2$s - Plugin name causing conflict. */
						esc_html__( 'Heads up! WP Mail SMTP has detected %1$s is activated. Please deactivate %2$s to prevent conflicts.', 'wp-mail-smtp' ),
						$conflict['name'],
						$conflict['name']
					),
					WP::ADMIN_NOTICE_WARNING
				);
				return;
			}
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

		// Store the plugin version when initial install occurred.
		add_option( 'wp_mail_smtp_initial_version', WPMS_PLUGIN_VER, '', false );

		// Store the plugin version activated to reference with upgrades.
		update_option( 'wp_mail_smtp_version', WPMS_PLUGIN_VER, false );

		// Save default options, only once.
		Options::init()->set( Options::get_defaults(), true );
	}
}
