<?php

namespace WPMailSMTP\Admin;

use WPMailSMTP\WP;
use WPMailSMTP\Options;

/**
 * Class Area registers and process all wp-admin display functionality.
 *
 * @since 1.0.0
 */
class Area {

	/**
	 * Slug of the admin area page.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const SLUG = 'wp-mail-smtp';

	/**
	 * Admin page unique hook.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	public $hook;

	/**
	 * List of admin area pages.
	 *
	 * @since 1.0.0
	 *
	 * @var PageAbstract[]
	 */
	private $pages;

	/**
	 * List of official registered pages.
	 *
	 * @since 1.5.0
	 *
	 * @var array
	 */
	public static $pages_registered = [ 'general', 'logs', 'about' ];

	/**
	 * Area constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		$this->hooks();
	}

	/**
	 * Assign all hooks to proper places.
	 *
	 * @since 1.0.0
	 */
	protected function hooks() {

		// Add the Settings link to a plugin on Plugins page.
		add_filter( 'plugin_action_links_' . plugin_basename( WPMS_PLUGIN_FILE ), [ $this, 'add_plugin_action_link' ], 10, 1 );

		// Add the options page.
		add_action( 'admin_menu', [ $this, 'add_admin_options_page' ] );

		// Add WPMS network-wide setting page for product education.
		add_action( 'network_admin_menu', [ $this, 'add_wpms_network_wide_setting_product_education_page' ] );

		// Register on load Email Log admin menu hook.
		add_action( 'load-wp-mail-smtp_page_wp-mail-smtp-logs', [ $this, 'maybe_redirect_email_log_menu_to_email_log_settings_tab' ] );

		// Admin footer text.
		add_filter( 'admin_footer_text', [ $this, 'get_admin_footer' ], 1, 2 );

		// Enqueue admin area scripts and styles.
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );

		// Process the admin page forms actions.
		add_action( 'admin_init', [ $this, 'process_actions' ] );

		// Display custom notices based on the error/success codes.
		add_action( 'admin_init', [ $this, 'display_custom_auth_notices' ] );

		// Display notice instructing the user to complete plugin setup.
		add_action( 'admin_init', [ $this, 'display_setup_notice' ] );

		// Outputs the plugin admin header.
		add_action( 'in_admin_header', [ $this, 'display_admin_header' ], 100 );

		// Hide all unrelated to the plugin notices on the plugin admin pages.
		add_action( 'admin_print_scripts', [ $this, 'hide_unrelated_notices' ] );

		// Process all AJAX requests.
		add_action( 'wp_ajax_wp_mail_smtp_ajax', [ $this, 'process_ajax' ] );

		( new Review() )->hooks();
		( new Education() )->hooks();
	}

	/**
	 * Display custom notices based on the error/success codes.
	 *
	 * @since 1.0.0
	 */
	public function display_custom_auth_notices() {

		$error   = isset( $_GET['error'] ) ? sanitize_key( $_GET['error'] ) : ''; // phpcs:ignore
		$success = isset( $_GET['success'] ) ? sanitize_key( $_GET['success'] ) : '';  // phpcs:ignore

		if ( empty( $error ) && empty( $success ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		switch ( $error ) {
			case 'google_access_denied':
				WP::add_admin_notice( /* translators: %s - error code, returned by Google API. */
					sprintf( esc_html__( 'There was an error while processing the authentication request: %s. Please try again.', 'wp-mail-smtp' ), '<code>' . $error . '</code>' ),
					WP::ADMIN_NOTICE_ERROR
				);
				break;

			case 'google_no_code_scope':
				WP::add_admin_notice(
					esc_html__( 'There was an error while processing the authentication request. Please try again.', 'wp-mail-smtp' ),
					WP::ADMIN_NOTICE_ERROR
				);
				break;

			case 'google_no_clients':
				WP::add_admin_notice(
					esc_html__( 'There was an error while processing the authentication request. Please make sure that you have Client ID and Client Secret both valid and saved.', 'wp-mail-smtp' ),
					WP::ADMIN_NOTICE_ERROR
				);
				break;
		}

		switch ( $success ) {
			case 'google_site_linked':
				WP::add_admin_notice(
					esc_html__( 'You have successfully linked the current site with your Google API project. Now you can start sending emails through Gmail.', 'wp-mail-smtp' ),
					WP::ADMIN_NOTICE_SUCCESS
				);
				break;
		}
	}

	/**
	 * Display notice instructing the user to complete plugin setup.
	 *
	 * @since 1.3.0
	 */
	public function display_setup_notice() {

		// Bail if we're not on a plugin page.
		if ( ! $this->is_admin_page( 'general' ) ) {
			return;
		}

		$default_options = wp_json_encode( Options::get_defaults() );
		$current_options = wp_json_encode( Options::init()->get_all() );

		// Check if the current settings are the same as the default settings.
		if ( $current_options !== $default_options ) {
			return;
		}

		// Display notice informing user further action is needed.
		WP::add_admin_notice(
			sprintf(
				wp_kses( /* translators: %s - Mailer anchor link. */
					__( 'Thanks for using WP Mail SMTP! To complete the plugin setup and start sending emails, <strong>please select and configure your <a href="%s">Mailer</a></strong>.', 'wp-mail-smtp' ),
					[
						'a'      => [
							'href' => [],
						],
						'strong' => [],
					]
				),
				wp_mail_smtp()->get_admin()->get_admin_page_url( self::SLUG . '#wp-mail-smtp-setting-row-mailer' )
			),
			WP::ADMIN_NOTICE_INFO
		);
	}

	/**
	 * Add admin area menu item.
	 *
	 * @since 1.0.0
	 * @since 1.5.0 Moved the menu to the top level. Added several more pages.
	 */
	public function add_admin_options_page() {

		$this->hook = \add_menu_page(
			\esc_html__( 'WP Mail SMTP', 'wp-mail-smtp' ),
			\esc_html__( 'WP Mail SMTP', 'wp-mail-smtp' ),
			'manage_options',
			self::SLUG,
			array( $this, 'display' ),
			'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIGZpbGw9IiM5ZWEzYTgiIHdpZHRoPSI2NCIgaGVpZ2h0PSI2NCIgdmlld0JveD0iMCAwIDQzIDM0Ij48cGF0aCBkPSJNMC4wMDcsMy41ODVWMjAuNDIxcTAsMy41ODYsMy43NTEsMy41ODVMMjAsMjRWMTlIMzBWMTQuMDE0bDAuOTkxLTFMMzQsMTNWMy41ODVRMzQsMCwzMC4yNDksMEgzLjc1OFEwLjAwNywwLC4wMDcsMy41ODVoMFpNMy41MjQsNi4xNTdhMS40OSwxLjQ5LDAsMCwxLS41MDgtMC45MzUsMS41ODEsMS41ODEsMCwwLDEsLjI3NC0xLjIwOCwxLjQ0OSwxLjQ0OSwwLDAsMSwxLjA5NC0uNjYzLDEuNzU2LDEuNzU2LDAsMCwxLDEuMjUuMzEybDExLjQwOSw3LjcxNkwyOC4zNzQsMy42NjNhMS45NiwxLjk2LDAsMCwxLDEuMjg5LS4zMTIsMS41NDYsMS41NDYsMCwwLDEsMS4wOTQuNjYzLDEuNCwxLjQsMCwwLDEsLjI3MywxLjIwOCwxLjY3LDEuNjcsMCwwLDEtLjU0Ny45MzVMMTcuMDQzLDE3LjIyNVoiLz48cGF0aCBkPSJNMjIsMjhIMzJsLTAuMDA5LDQuNjI0YTEuMTI2LDEuMTI2LDAsMCwwLDEuOTIyLjhsOC4yNS04LjIzNmExLjEyNiwxLjEyNiwwLDAsMCwwLTEuNTk0bC04LjI1LTguMjQxYTEuMTI2LDEuMTI2LDAsMCwwLTEuOTIyLjh2NC44NjZMMjIsMjF2N1oiLz48L3N2Zz4=',
			98
		);

		\add_submenu_page(
			self::SLUG,
			$this->get_current_tab_title() . ' &lsaquo; ' . \esc_html__( 'Settings', 'wp-mail-smtp' ),
			\esc_html__( 'Settings', 'wp-mail-smtp' ),
			'manage_options',
			self::SLUG,
			array( $this, 'display' )
		);
		\add_submenu_page(
			self::SLUG,
			\esc_html__( 'Email Log', 'wp-mail-smtp' ),
			\esc_html__( 'Email Log', 'wp-mail-smtp' ),
			'manage_options',
			self::SLUG . '-logs',
			array( $this, 'display' )
		);

		if ( ! wp_mail_smtp()->is_white_labeled() ) {
			\add_submenu_page(
				self::SLUG,
				\esc_html__( 'About Us', 'wp-mail-smtp' ),
				\esc_html__( 'About Us', 'wp-mail-smtp' ),
				'manage_options',
				self::SLUG . '-about',
				array( $this, 'display' )
			);
		}
	}

	/**
	 * Add network admin settings page for the WPMS product education.
	 *
	 * @since 2.5.0
	 */
	public function add_wpms_network_wide_setting_product_education_page() {

		add_menu_page(
			esc_html__( 'WP Mail SMTP', 'wp-mail-smtp' ),
			esc_html__( 'WP Mail SMTP', 'wp-mail-smtp' ),
			'manage_options',
			self::SLUG,
			[ $this, 'display_network_product_education_page' ],
			'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIGZpbGw9IiM5ZWEzYTgiIHdpZHRoPSI2NCIgaGVpZ2h0PSI2NCIgdmlld0JveD0iMCAwIDQzIDM0Ij48cGF0aCBkPSJNMC4wMDcsMy41ODVWMjAuNDIxcTAsMy41ODYsMy43NTEsMy41ODVMMjAsMjRWMTlIMzBWMTQuMDE0bDAuOTkxLTFMMzQsMTNWMy41ODVRMzQsMCwzMC4yNDksMEgzLjc1OFEwLjAwNywwLC4wMDcsMy41ODVoMFpNMy41MjQsNi4xNTdhMS40OSwxLjQ5LDAsMCwxLS41MDgtMC45MzUsMS41ODEsMS41ODEsMCwwLDEsLjI3NC0xLjIwOCwxLjQ0OSwxLjQ0OSwwLDAsMSwxLjA5NC0uNjYzLDEuNzU2LDEuNzU2LDAsMCwxLDEuMjUuMzEybDExLjQwOSw3LjcxNkwyOC4zNzQsMy42NjNhMS45NiwxLjk2LDAsMCwxLDEuMjg5LS4zMTIsMS41NDYsMS41NDYsMCwwLDEsMS4wOTQuNjYzLDEuNCwxLjQsMCwwLDEsLjI3MywxLjIwOCwxLjY3LDEuNjcsMCwwLDEtLjU0Ny45MzVMMTcuMDQzLDE3LjIyNVoiLz48cGF0aCBkPSJNMjIsMjhIMzJsLTAuMDA5LDQuNjI0YTEuMTI2LDEuMTI2LDAsMCwwLDEuOTIyLjhsOC4yNS04LjIzNmExLjEyNiwxLjEyNiwwLDAsMCwwLTEuNTk0bC04LjI1LTguMjQxYTEuMTI2LDEuMTI2LDAsMCwwLTEuOTIyLjh2NC44NjZMMjIsMjF2N1oiLz48L3N2Zz4=',
			98
		);
	}

	/**
	 * HTML output for the network admin settings page (for the WPMS product education).
	 *
	 * @since 2.5.0
	 */
	public function display_network_product_education_page() {

		// Skip if not on multisite and not on network admin site.
		if ( ! is_multisite() || ! is_network_admin() ) {
			return;
		}

		?>

		<div class="wrap" id="wp-mail-smtp">
			<div class="wp-mail-smtp-page wp-mail-smtp-page-general wp-mail-smtp-page-nw-product-edu wp-mail-smtp-tab-settings">
				<div class="wp-mail-smtp-page-title">
					<a href="#" class="tab active">
						<?php esc_html_e( 'General', 'wp-mail-smtp' ); ?>
					</a>
				</div>

				<div class="wp-mail-smtp-page-content">
					<h1 class="screen-reader-text">
						<?php esc_html_e( 'General', 'wp-mail-smtp' ); ?>
					</h1>

					<?php do_action( 'wp_mail_smtp_admin_pages_before_content' ); ?>

					<!-- Multisite Section Title -->
					<div class="wp-mail-smtp-setting-row wp-mail-smtp-setting-row-content wp-mail-smtp-clear section-heading no-desc" id="wp-mail-smtp-setting-row-multisite-heading">
						<div class="wp-mail-smtp-setting-field">
							<h2><?php esc_html_e( 'Multisite', 'wp-mail-smtp' ); ?></h2>
							<img src="<?php echo esc_url( wp_mail_smtp()->assets_url . '/images/pro-badge.svg' ); ?>" class="badge" alt="<?php esc_attr_e( 'Pro+ badge icon', 'wp-mail-smtp' ); ?>">
						</div>
						<p>
							<?php esc_html_e( 'Simply enable network-wide settings and every site on your network will inherit the same SMTP settings. Save time and only configure your SMTP provider once.', 'wp-mail-smtp' ); ?>
						</p>
					</div>

					<!-- Network wide setting -->
					<div id="wp-mail-smtp-setting-row-multisite" class="wp-mail-smtp-setting-row wp-mail-smtp-setting-row-multisite wp-mail-smtp-clear">
						<div class="wp-mail-smtp-setting-label">
							<label for="wp-mail-smtp-setting-multisite-settings-control"><?php esc_html_e( 'Settings control', 'wp-mail-smtp' ); ?></label>
						</div>
						<div class="wp-mail-smtp-setting-field">
							<input name="wp-mail-smtp[general][nw_product_edu]" type="checkbox" value="true" id="wp-mail-smtp-setting-nw-product-edu" disabled>

							<label for="wp-mail-smtp-setting-nw-product-edu">
								<?php esc_html_e( 'Make the plugin settings global network-wide', 'wp-mail-smtp' ); ?>
							</label>

							<p class="desc">
								<?php esc_html_e( 'If disabled, each subsite of this multisite will have its own WP Mail SMTP settings page that has to be configured separately.', 'wp-mail-smtp' ); ?>
								<br>
								<?php esc_html_e( 'If enabled, these global settings will manage email sending for all subsites of this multisite.', 'wp-mail-smtp' ); ?>
							</p>
						</div>
					</div>

					<div class="wp-mail-smtp-setting-row-no-setting">
						<a href="<?php echo esc_url( wp_mail_smtp()->get_upgrade_link( [ 'medium' => 'network-settings', 'content' => '' ] ) ); // phpcs:ignore ?>" target="_blank" rel="noopener noreferrer" class="wp-mail-smtp-btn wp-mail-smtp-btn-lg wp-mail-smtp-btn-orange">
							<?php esc_html_e( 'Upgrade to WP Mail SMTP Pro', 'wp-mail-smtp' ); ?>
						</a>
					</div>

				</div>
			</div>
		</div>

		<?php
	}

	/**
	 * Redirect the "Email Log" WP menu link to the "Email Log" setting tab for lite version of the plugin.
	 *
	 * @since 2.1.0
	 */
	public function maybe_redirect_email_log_menu_to_email_log_settings_tab() {

		/**
		 * The Email Logs object to be used for loading the Email Log page.
		 *
		 * @var \WPMailSMTP\Admin\PageAbstract $logs
		 */
		$logs = $this->generate_display_logs_object();

		if ( $logs instanceof \WPMailSMTP\Admin\Pages\Logs ) {
			wp_safe_redirect( $logs->get_link() );
			exit;
		}
	}

	/**
	 * Enqueue admin area scripts and styles.
	 *
	 * @since 1.0.0
	 * @since 1.5.0 Added new assets for new pages.
	 * @since 1.7.0 Added jQuery Confirm library css/js files.
	 *
	 * @param string $hook Current hook.
	 */
	public function enqueue_assets( $hook ) {

		if ( strpos( $hook, self::SLUG ) === false ) {
			return;
		}

		// General styles and js.
		\wp_enqueue_style(
			'wp-mail-smtp-admin',
			\wp_mail_smtp()->assets_url . '/css/smtp-admin.min.css',
			false,
			WPMS_PLUGIN_VER
		);

		\wp_enqueue_script(
			'wp-mail-smtp-admin',
			\wp_mail_smtp()->assets_url . '/js/smtp-admin' . WP::asset_min() . '.js',
			array( 'jquery' ),
			WPMS_PLUGIN_VER,
			false
		);

		\wp_localize_script(
			'wp-mail-smtp-admin',
			'wp_mail_smtp',
			array(
				'text_provider_remove'    => esc_html__( 'Are you sure you want to reset the current provider connection? You will need to immediately create a new one to be able to send emails.', 'wp-mail-smtp' ),
				'text_settings_not_saved' => esc_html__( 'Changes that you made to the settings are not saved!', 'wp-mail-smtp' ),
				'default_mailer_notice'   => array(
					'title'         => esc_html__( 'Heads up!', 'wp-mail-smtp' ),
					'content'       => wp_kses(
						__( '<p>The Default (PHP) mailer is currently selected, but is not recommended because in most cases it does not resolve email delivery issues.</p><p>Please consider selecting and configuring one of the other mailers.</p>', 'wp-mail-smtp' ),
						[ 'p' => [] ]
					),
					'save_button'   => esc_html__( 'Save Settings', 'wp-mail-smtp' ),
					'cancel_button' => esc_html__( 'Cancel', 'wp-mail-smtp' ),
					'icon_alt'      => esc_html__( 'Warning icon', 'wp-mail-smtp' ),
				),
				'plugin_url'              => wp_mail_smtp()->plugin_url,
				'education'               => array(
					'upgrade_icon_lock' => '<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="lock" class="svg-inline--fa fa-lock fa-w-14" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path fill="currentColor" d="M400 224h-24v-72C376 68.2 307.8 0 224 0S72 68.2 72 152v72H48c-26.5 0-48 21.5-48 48v192c0 26.5 21.5 48 48 48h352c26.5 0 48-21.5 48-48V272c0-26.5-21.5-48-48-48zm-104 0H152v-72c0-39.7 32.3-72 72-72s72 32.3 72 72v72z"></path></svg>',
					'upgrade_title'     => esc_html__( '%name% is a PRO Feature', 'wp-mail-smtp' ),
					'upgrade_button'    => esc_html__( 'Upgrade to Pro', 'wp-mail-smtp' ),
					'upgrade_url'       => add_query_arg( 'discount', 'SMTPLITEUPGRADE', wp_mail_smtp()->get_upgrade_link( '' ) ),
					'upgrade_bonus'     => '<p>' .
											wp_kses(
												__( '<strong>Bonus:</strong> WP Mail SMTP users get <span>$50 off</span> regular price,<br>applied at checkout.', 'wp-mail-smtp' ),
												[
													'strong' => [],
													'span'   => [],
													'br'     => [],
												]
											)
											. '</p>',
					'upgrade_doc'       => '<a href="https://wpmailsmtp.com/docs/how-to-upgrade-wp-mail-smtp-to-pro-version/?utm_source=WordPress&amp;utm_medium=link&amp;utm_campaign=liteplugin" target="_blank" rel="noopener noreferrer" class="already-purchased">
												' . esc_html__( 'Already purchased?', 'wp-mail-smtp' ) . '
											</a>',
				),
				'all_mailers_supports'    => wp_mail_smtp()->get_providers()->get_supports_all(),
				'nonce'                   => wp_create_nonce( 'wp-mail-smtp-admin' ),
			)
		);

		/*
		 * jQuery Confirm library v3.3.4.
		 */
		\wp_enqueue_style(
			'wp-mail-smtp-admin-jconfirm',
			\wp_mail_smtp()->assets_url . '/libs/jquery-confirm.min.css',
			array( 'wp-mail-smtp-admin' ),
			'3.3.4'
		);
		\wp_enqueue_script(
			'wp-mail-smtp-admin-jconfirm',
			\wp_mail_smtp()->assets_url . '/libs/jquery-confirm.min.js',
			array( 'wp-mail-smtp-admin' ),
			'3.3.4',
			false
		);

		/*
		 * Logs page.
		 */
		if ( $this->is_admin_page( 'logs' ) ) {
			\wp_enqueue_style(
				'wp-mail-smtp-admin-logs',
				apply_filters( 'wp_mail_smtp_admin_enqueue_assets_logs_css', '' ),
				array( 'wp-mail-smtp-admin' ),
				WPMS_PLUGIN_VER
			);

			\wp_enqueue_script(
				'wp-mail-smtp-admin-logs',
				apply_filters( 'wp_mail_smtp_admin_enqueue_assets_logs_js', '' ),
				array( 'wp-mail-smtp-admin' ),
				WPMS_PLUGIN_VER,
				false
			);
		}

		/*
		 * About page.
		 */
		if ( $this->is_admin_page( 'about' ) ) {

			\wp_enqueue_style(
				'wp-mail-smtp-admin-about',
				\wp_mail_smtp()->assets_url . '/css/smtp-about.min.css',
				array( 'wp-mail-smtp-admin' ),
				WPMS_PLUGIN_VER
			);

			\wp_enqueue_script(
				'wp-mail-smtp-admin-about',
				\wp_mail_smtp()->assets_url . '/js/smtp-about' . WP::asset_min() . '.js',
				array( 'wp-mail-smtp-admin' ),
				'0.7.2',
				false
			);

			$settings = array(
				'ajax_url'                    => \admin_url( 'admin-ajax.php' ),
				'nonce'                       => \wp_create_nonce( 'wp-mail-smtp-about' ),
				// Strings.
				'plugin_activate'             => \esc_html__( 'Activate', 'wp-mail-smtp' ),
				'plugin_activated'            => \esc_html__( 'Activated', 'wp-mail-smtp' ),
				'plugin_active'               => \esc_html__( 'Active', 'wp-mail-smtp' ),
				'plugin_inactive'             => \esc_html__( 'Inactive', 'wp-mail-smtp' ),
				'plugin_processing'           => \esc_html__( 'Processing...', 'wp-mail-smtp' ),
				'plugin_install_error'        => \esc_html__( 'Could not install a plugin. Please download from WordPress.org and install manually.', 'wp-mail-smtp' ),
				'plugin_install_activate_btn' => \esc_html__( 'Install and Activate', 'wp-mail-smtp' ),
				'plugin_activate_btn'         => \esc_html__( 'Activate', 'wp-mail-smtp' ),
				'plugin_download_btn'         => \esc_html__( 'Download', 'wp-mail-smtp' ),
			);

			\wp_localize_script(
				'wp-mail-smtp-admin-about',
				'wp_mail_smtp_about',
				$settings
			);

			\wp_enqueue_script(
				'wp-mail-smtp-admin-about-matchheight',
				\wp_mail_smtp()->assets_url . '/js/vendor/jquery.matchHeight.min.js',
				array( 'wp-mail-smtp-admin' ),
				'0.7.2',
				false
			);
		}

		do_action( 'wp_mail_smtp_admin_area_enqueue_assets', $hook );
	}

	/**
	 * Outputs the plugin admin header.
	 *
	 * @since 1.0.0
	 */
	public function display_admin_header() {

		// Bail if we're not on a plugin page.
		if ( ! $this->is_admin_page() ) {
			return;
		}

		do_action( 'wp_mail_smtp_admin_header_before' );
		?>

		<div id="wp-mail-smtp-header-temp"></div>
		<div id="wp-mail-smtp-header">
			<!--suppress HtmlUnknownTarget -->
			<img class="wp-mail-smtp-header-logo" src="<?php echo esc_url( wp_mail_smtp()->assets_url ); ?>/images/logo<?php echo wp_mail_smtp()->is_white_labeled() ? '-whitelabel' : ''; ?>.svg" alt="WP Mail SMTP"/>
		</div>

		<?php
	}

	/**
	 * Display a text to ask users to review the plugin on WP.org.
	 *
	 * @since 1.0.0
	 *
	 * @param string $text
	 *
	 * @return string
	 */
	public function get_admin_footer( $text ) {

		if ( $this->is_admin_page() ) {
			$url = 'https://wordpress.org/support/plugin/wp-mail-smtp/reviews/?filter=5#new-post';

			$text = sprintf(
				wp_kses(
					/* translators: %1$s - WP.org link; %2$s - same WP.org link. */
					__( 'Please rate <strong>WP Mail SMTP</strong> <a href="%1$s" target="_blank" rel="noopener noreferrer">&#9733;&#9733;&#9733;&#9733;&#9733;</a> on <a href="%2$s" target="_blank" rel="noopener noreferrer">WordPress.org</a> to help us spread the word. Thank you from the WP Mail SMTP team!', 'wp-mail-smtp' ),
					array(
						'strong' => array(),
						'a'      => array(
							'href'   => array(),
							'target' => array(),
							'rel'    => array(),
						),
					)
				),
				$url,
				$url
			);
		}

		return $text;
	}

	/**
	 * Display content of the admin area page.
	 *
	 * @since 1.0.0
	 * @since 1.5.0 Rewrite to distinguish between General tabs and separate pages.
	 */
	public function display() {

		// Bail if we're not on a plugin page.
		if ( ! $this->is_admin_page() ) {
			return;
		}

		$page = ! empty( $_GET['page'] ) ? \sanitize_key( $_GET['page'] ) : ''; // phpcs:ignore
		?>

		<div class="wrap" id="wp-mail-smtp">

				<?php
				switch ( $page ) {
					case self::SLUG:
						?>

						<div class="wp-mail-smtp-page wp-mail-smtp-page-general wp-mail-smtp-tab-<?php echo esc_attr( $this->get_current_tab() ); ?>">
							<?php $this->display_tabs(); ?>
						</div>

						<?php
						break;

					case self::SLUG . '-logs':
						/**
						 * The Email Logs object to be used for loading the Email Log page.
						 *
						 * @var \WPMailSMTP\Admin\PageAbstract $logs
						 */
						$logs = $this->generate_display_logs_object();

						$is_archive = wp_mail_smtp()->is_pro() && wp_mail_smtp()->pro->get_logs()->is_archive();
						?>

						<div class="wp-mail-smtp-page wp-mail-smtp-page-logs <?php echo $is_archive ? 'wp-mail-smtp-page-logs-archive' : 'wp-mail-smtp-page-logs-single'; ?>">
							<?php $logs->display(); ?>
						</div>

						<?php
						break;

					case self::SLUG . '-about':
						$about = new Pages\About();
						?>

						<div class="wp-mail-smtp-page wp-mail-smtp-page-about wp-mail-smtp-tab-about-<?php echo \esc_attr( $about->get_current_tab() ); ?>">
							<?php $about->display(); ?>
						</div>

						<?php
						break;
				}
				?>
		</div>

		<?php
	}

	/**
	 * Generate the appropriate Email Log page object used for displaying the Email Log page.
	 *
	 * @since 2.1.0
	 *
	 * @return \WPMailSMTP\Admin\PageAbstract
	 */
	public function generate_display_logs_object() {

		$logs_class = apply_filters( 'wp_mail_smtp_admin_display_get_logs_fqcn', \WPMailSMTP\Admin\Pages\Logs::class );

		return new $logs_class();
	}

	/**
	 * Display General page tabs.
	 *
	 * @since 1.5.0
	 */
	protected function display_tabs() {
		?>

		<div class="wp-mail-smtp-page-title">
			<?php
			foreach ( $this->get_pages() as $page_slug => $page ) :
				$label = $page->get_label();
				if ( empty( $label ) ) {
					continue;
				}
				$class = $page_slug === $this->get_current_tab() ? 'active' : '';
				?>

				<a href="<?php echo esc_url( $page->get_link() ); ?>" class="tab <?php echo esc_attr( $class ); ?>">
					<?php echo esc_html( $label ); ?>
				</a>

			<?php endforeach; ?>
		</div>

		<div class="wp-mail-smtp-page-content">
			<h1 class="screen-reader-text">
				<?php echo esc_html( $this->get_current_tab_title() ); ?>
			</h1>

			<?php do_action( 'wp_mail_smtp_admin_pages_before_content' ); ?>

			<?php $this->display_current_tab_content(); ?>
		</div>

		<?php
	}

	/**
	 * Get the current tab content.
	 *
	 * @since 1.0.0
	 */
	public function display_current_tab_content() {

		$pages = $this->get_pages();

		if ( ! array_key_exists( $this->get_current_tab(), $pages ) ) {
			return;
		}

		$pages[ $this->get_current_tab() ]->display();
	}

	/**
	 * Get the current admin area tab.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	protected function get_current_tab() {

		$current = '';

		if ( $this->is_admin_page( 'general' ) ) {
			$current = ! empty( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'settings'; // phpcs:ignore
		}

		return $current;
	}

	/**
	 * Get the array of default registered tabs for General page admin area.
	 *
	 * @since 1.0.0
	 *
	 * @return \WPMailSMTP\Admin\PageAbstract[]
	 */
	public function get_pages() {

		if ( empty( $this->pages ) ) {
			$this->pages = array(
				'settings' => new Pages\SettingsTab(),
				'test'     => new Pages\TestTab(),
				'logs'     => new Pages\LogsTab(),
				'control'  => new Pages\ControlTab(),
				'misc'     => new Pages\MiscTab(),
				'auth'     => new Pages\AuthTab(),
			);
		}

		return apply_filters( 'wp_mail_smtp_admin_get_pages', $this->pages );
	}

	/**
	 * Get the current tab title.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_current_tab_title() {

		$pages = $this->get_pages();

		if ( ! array_key_exists( $this->get_current_tab(), $pages ) ) {
			return '';
		}

		return $pages[ $this->get_current_tab() ]->get_title();
	}

	/**
	 * Check whether we are on an admin page.
	 *
	 * @since 1.0.0
	 * @since 1.5.0 Added support for new pages.
	 *
	 * @param array|string $slug ID(s) of a plugin page. Possible values: 'general', 'logs', 'about' or array of them.
	 *
	 * @return bool
	 */
	public function is_admin_page( $slug = array() ) {

		$cur_page    = isset( $_GET['page'] ) ? sanitize_key( $_GET['page'] ) : ''; // phpcs:ignore
		$check       = self::SLUG;
		$pages_equal = false;

		if ( is_string( $slug ) ) {
			$slug = sanitize_key( $slug );

			if (
				in_array( $slug, self::$pages_registered, true ) &&
				$slug !== 'general'
			) {
				$check = self::SLUG . '-' . $slug;
			}

			$pages_equal = $cur_page === $check;
		} elseif ( is_array( $slug ) ) {
			if ( empty( $slug ) ) {
				$slug = array_map( function ( $v ) {
					if ( $v === 'general' ) {
						return Area::SLUG;
					}
					return Area::SLUG . '-' . $v;
				}, self::$pages_registered );
			} else {
				$slug = array_map( function ( $v ) {
					if ( $v === 'general' ) {
						return Area::SLUG;
					}
					return Area::SLUG . '-' . sanitize_key( $v );
				}, $slug );
			}

			$pages_equal = in_array( $cur_page, $slug, true );
		}

		return is_admin() && $pages_equal;
	}

	/**
	 * Give ability to use either admin area option or a filter to hide error notices about failed email delivery.
	 * Filter has higher priority and overrides an option.
	 *
	 * @since 1.6.0
	 *
	 * @return bool
	 */
	public function is_error_delivery_notice_enabled() {

		$is_hard_enabled = (bool) apply_filters( 'wp_mail_smtp_admin_is_error_delivery_notice_enabled', true );

		// If someone changed the value to false using a filter - disable completely.
		if ( ! $is_hard_enabled ) {
			return false;
		}

		return ! (bool) Options::init()->get( 'general', 'email_delivery_errors_hidden' );
	}

	/**
	 * All possible plugin forms manipulation will be done here.
	 *
	 * @since 1.0.0
	 */
	public function process_actions() {

		// Bail if we're not on a plugin General page.
		if ( ! $this->is_admin_page( 'general' ) ) {
			return;
		}

		$pages = $this->get_pages();

		// Allow to process only own tabs.
		if ( ! array_key_exists( $this->get_current_tab(), $pages ) ) {
			return;
		}

		// Process POST only if it exists.
		if ( ! empty( $_POST ) ) {
			if ( ! empty( $_POST['wp-mail-smtp'] ) ) {
				$post = $_POST['wp-mail-smtp'];
			} else {
				$post = array();
			}

			$pages[ $this->get_current_tab() ]->process_post( $post );
		}

		// This won't do anything for most pages.
		// Works for plugin page only, when GET params are allowed.
		$pages[ $this->get_current_tab() ]->process_auth();
	}

	/**
	 * Process all AJAX requests.
	 *
	 * @since 1.3.0
	 * @since 1.5.0 Added tasks to process plugins management.
	 */
	public function process_ajax() {

		$data = array();

		// Only admins can fire these ajax requests.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( $data );
		}

		if ( empty( $_POST['task'] ) ) { // phpcs:ignore
			wp_send_json_error( $data );
		}

		$task = sanitize_key( $_POST['task'] ); // phpcs:ignore

		switch ( $task ) {
			case 'pro_banner_dismiss':
				update_user_meta( get_current_user_id(), 'wp_mail_smtp_pro_banner_dismissed', true );
				$data['message'] = esc_html__( 'WP Mail SMTP Pro related message was successfully dismissed.', 'wp-mail-smtp' );
				break;

			case 'about_plugin_install':
				Pages\About::ajax_plugin_install();
				break;

			case 'about_plugin_activate':
				Pages\About::ajax_plugin_activate();
				break;

			case 'notice_dismiss':
				$notice = sanitize_key( $_POST['notice'] ); // phpcs:ignore
				$mailer = sanitize_key( $_POST['mailer'] ); // phpcs:ignore
				if ( empty( $notice ) || empty( $mailer ) ) {
					break;
				}

				update_user_meta( get_current_user_id(), "wp_mail_smtp_notice_{$notice}_for_{$mailer}_dismissed", true );
				$data['message'] = esc_html__( 'Educational notice for this mailer was successfully dismissed.', 'wp-mail-smtp' );
				break;

			default:
				// Allow custom tasks data processing being added here.
				$data = apply_filters( 'wp_mail_smtp_admin_process_ajax_' . $task . '_data', $data );
		}

		// Final ability to rewrite all the data, just in case.
		$data = (array) apply_filters( 'wp_mail_smtp_admin_process_ajax_data', $data, $task );

		if ( empty( $data ) ) {
			wp_send_json_error( $data );
		}

		wp_send_json_success( $data );
	}

	/**
	 * Add plugin action links on Plugins page (lite version only).
	 *
	 * @since 1.0.0
	 * @since 1.5.0 Added a link to Email Log.
	 * @since 2.0.0 Adjusted links. Process only the Lite plugin.
	 *
	 * @param array $links Existing plugin action links.
	 *
	 * @return array
	 */
	public function add_plugin_action_link( $links ) {

		// Do not register lite plugin action links if on pro version.
		if ( wp_mail_smtp()->is_pro() ) {
			return $links;
		}

		$custom['settings'] = sprintf(
			'<a href="%s" aria-label="%s">%s</a>',
			esc_url( $this->get_admin_page_url() ),
			esc_attr__( 'Go to WP Mail SMTP Settings page', 'wp-mail-smtp' ),
			esc_html__( 'Settings', 'wp-mail-smtp' )
		);

		$custom['support'] = sprintf(
			'<a href="%1$s" aria-label="%2$s" style="font-weight:bold;">%3$s</a>',
			esc_url( add_query_arg( 'tab','versus', $this->get_admin_page_url( Area::SLUG . '-about' ) ) ),
			esc_attr__( 'Go to WP Mail SMTP Lite vs Pro comparison page', 'wp-mail-smtp' ),
			esc_html__( 'Premium Support', 'wp-mail-smtp' )
		);

		return array_merge( $custom, (array) $links );
	}

	/**
	 * Get plugin admin area page URL.
	 *
	 * @since 1.0.0
	 * @since 1.5.0 URL is changed to support the top level position of the plugin admin area.
	 *
	 * @param string $page
	 *
	 * @return string
	 */
	public function get_admin_page_url( $page = '' ) {

		if ( empty( $page ) ) {
			$page = self::SLUG;
		}

		return add_query_arg(
			'page',
			$page,
			WP::admin_url( 'admin.php' )
		);
	}

	/**
	 * Remove all non-WP Mail SMTP plugin notices from plugin pages.
	 *
	 * @since 1.0.0
	 */
	public function hide_unrelated_notices() {

		// Bail if we're not on our screen or page.
		if ( empty( $_REQUEST['page'] ) || strpos( $_REQUEST['page'], self::SLUG ) === false ) {
			return;
		}

		global $wp_filter;

		if ( ! empty( $wp_filter['user_admin_notices']->callbacks ) && is_array( $wp_filter['user_admin_notices']->callbacks ) ) {
			foreach ( $wp_filter['user_admin_notices']->callbacks as $priority => $hooks ) {
				foreach ( $hooks as $name => $arr ) {
					if ( is_object( $arr['function'] ) && $arr['function'] instanceof \Closure ) {
						unset( $wp_filter['user_admin_notices']->callbacks[ $priority ][ $name ] );
						continue;
					}
					if ( ! empty( $arr['function'][0] ) && is_object( $arr['function'][0] ) && strpos( strtolower( get_class( $arr['function'][0] ) ), 'wpmailsmtp' ) !== false ) {
						continue;
					}
					if ( ! empty( $name ) && strpos( strtolower( $name ), 'wpmailsmtp' ) === false ) {
						unset( $wp_filter['user_admin_notices']->callbacks[ $priority ][ $name ] );
					}
				}
			}
		}

		if ( ! empty( $wp_filter['admin_notices']->callbacks ) && is_array( $wp_filter['admin_notices']->callbacks ) ) {
			foreach ( $wp_filter['admin_notices']->callbacks as $priority => $hooks ) {
				foreach ( $hooks as $name => $arr ) {
					if ( is_object( $arr['function'] ) && $arr['function'] instanceof \Closure ) {
						unset( $wp_filter['admin_notices']->callbacks[ $priority ][ $name ] );
						continue;
					}
					if ( ! empty( $arr['function'][0] ) && is_object( $arr['function'][0] ) && strpos( strtolower( get_class( $arr['function'][0] ) ), 'wpmailsmtp' ) !== false ) {
						continue;
					}
					if ( ! empty( $name ) && strpos( strtolower( $name ), 'wpmailsmtp' ) === false ) {
						unset( $wp_filter['admin_notices']->callbacks[ $priority ][ $name ] );
					}
				}
			}
		}

		if ( ! empty( $wp_filter['all_admin_notices']->callbacks ) && is_array( $wp_filter['all_admin_notices']->callbacks ) ) {
			foreach ( $wp_filter['all_admin_notices']->callbacks as $priority => $hooks ) {
				foreach ( $hooks as $name => $arr ) {
					if ( is_object( $arr['function'] ) && $arr['function'] instanceof \Closure ) {
						unset( $wp_filter['all_admin_notices']->callbacks[ $priority ][ $name ] );
						continue;
					}
					if ( ! empty( $arr['function'][0] ) && is_object( $arr['function'][0] ) && strpos( strtolower( get_class( $arr['function'][0] ) ), 'wpmailsmtp' ) !== false ) {
						continue;
					}
					if ( ! empty( $name ) && strpos( strtolower( $name ), 'wpmailsmtp' ) === false ) {
						unset( $wp_filter['all_admin_notices']->callbacks[ $priority ][ $name ] );
					}
				}
			}
		}
	}
}
