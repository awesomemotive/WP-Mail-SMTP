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
	 * @since 1.0.0
	 *
	 * @var string Slug of the admin area page.
	 */
	const SLUG = 'wp-mail-smtp';

	/**
	 * @since 1.0.0
	 *
	 * @var string Admin page unique hook.
	 */
	public $hook;

	/**
	 * @since 1.0.0
	 *
	 * @var PageAbstract[]
	 */
	private $pages;

	/**
	 * @since 1.5.0
	 *
	 * @var array List of official registered pages.
	 */
	public static $pages_registered = array( 'general', 'logs', 'about' );

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
		add_filter( 'plugin_action_links', array( $this, 'add_plugin_action_link' ), 10, 2 );

		// Add the options page.
		add_action( 'admin_menu', array( $this, 'add_admin_options_page' ) );

		// Admin footer text.
		add_filter( 'admin_footer_text', array( $this, 'get_admin_footer' ), 1, 2 );

		// Enqueue admin area scripts and styles.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );

		// Process the admin page forms actions.
		add_action( 'admin_init', array( $this, 'process_actions' ) );

		// Display custom notices based on the error/success codes.
		add_action( 'admin_init', array( $this, 'display_custom_auth_notices' ) );

		// Display notice instructing the user to complete plugin setup.
		add_action( 'admin_init', array( $this, 'display_setup_notice' ) );

		// Outputs the plugin admin header.
		add_action( 'in_admin_header', array( $this, 'display_admin_header' ), 100 );

		// Hide all unrelated to the plugin notices on the plugin admin pages.
		add_action( 'admin_print_scripts', array( $this, 'hide_unrelated_notices' ) );

		// Process all AJAX requests.
		add_action( 'wp_ajax_wp_mail_smtp_ajax', array( $this, 'process_ajax' ) );
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
				WP::add_admin_notice(
					/* translators: %s - error code, returned by Google API. */
					sprintf( esc_html__( 'There was an error while processing the authentication request: %s. Please try again.', 'wp-mail-smtp' ), '<code>' . $error . '</code>' ),
					WP::ADMIN_NOTICE_ERROR
				);
				break;

			case 'google_no_code_scope':
			case 'microsoft_no_code':
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
			case 'microsoft_site_linked':
				WP::add_admin_notice(
					esc_html__( 'You have successfully linked the current site with your Microsoft API project. Now you can start sending emails through Outlook.', 'wp-mail-smtp' ),
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
		if ( ! $this->is_admin_page() ) {
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
				wp_kses(
					/* translators: %s - Mailer anchor link. */
					__( 'Thanks for using WP Mail SMTP! To complete the plugin setup and start sending emails, <strong>please select and configure your <a href="%s">Mailer</a></strong>.', 'wp-mail-smtp' ),
					array(
						'a'      => array(
							'href' => array(),
						),
						'strong' => array(),
					)
				),
				'#wp-mail-smtp-setting-row-mailer'
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
		\add_submenu_page(
			self::SLUG,
			\esc_html__( 'About Us', 'wp-mail-smtp' ),
			\esc_html__( 'About Us', 'wp-mail-smtp' ),
			'manage_options',
			self::SLUG . '-about',
			array( $this, 'display' )
		);
	}

	/**
	 * Enqueue admin area scripts and styles.
	 *
	 * @since 1.0.0
	 * @since 1.5.0 Added ajax tasks for plugin installation/activation.
	 *
	 * @param string $hook
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
				'text_provider_remove' => esc_html__( 'Are you sure you want to reset the current provider connection? You will need to immediately create a new one to be able to send emails.', 'wp-mail-smtp' ),
			)
		);

		/*
		 * Logs page.
		 */
		if ( $this->is_admin_page( 'logs' ) ) {
			\wp_enqueue_style(
				'wp-mail-smtp-admin-logs',
				apply_filters( 'wp_mail_smtp_admin_enqueue_assets_logs_css', \wp_mail_smtp()->assets_url . '/css/smtp-logs.min.css' ),
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
			);

			\wp_localize_script(
				'wp-mail-smtp-admin-about',
				'wp_mail_smtp_about',
				$settings
			);

			\wp_enqueue_script(
				'wp-mail-smtp-admin-about-matchheight',
				\wp_mail_smtp()->assets_url . '/js/jquery.matchHeight.min.js',
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
		?>

		<div id="wp-mail-smtp-header">
			<!--suppress HtmlUnknownTarget -->
			<img class="wp-mail-smtp-header-logo" src="<?php echo esc_url( wp_mail_smtp()->assets_url ); ?>/images/logo.svg" alt="WP Mail SMTP"/>
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
						$logs_class = apply_filters( 'wp_mail_smtp_admin_display_get_logs_fqcn', '\WPMailSMTP\Admin\Pages\Logs' );
						/** @var \WPMailSMTP\Admin\PageAbstract $logs */
						$logs = new $logs_class();

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
				'settings' => new Pages\Settings(),
				'test'     => new Pages\Test(),
				'misc'     => new Pages\Misc(),
				'auth'     => new Pages\Auth(),
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

		$task = sanitize_key( $_POST['task'] );

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
	 * Add a link to Settings page of a plugin on Plugins page.
	 *
	 * @since 1.0.0
	 * @since 1.5.0 Added a link to Email Log.
	 *
	 * @param array $links
	 * @param string $file
	 *
	 * @return mixed
	 */
	public function add_plugin_action_link( $links, $file ) {

		// Will target both pro and lite version of a plugin.
		if ( strpos( $file, 'wp-mail-smtp' ) === false ) {
			return $links;
		}

		$settings_link = '<a href="' . esc_url( $this->get_admin_page_url() ) . '">' . esc_html__( 'Settings', 'wp-mail-smtp' ) . '</a>';
		$logs_link     = '<a href="' . esc_url( $this->get_admin_page_url( self::SLUG . '-logs' ) ) . '">' . esc_html__( 'Email Log', 'wp-mail-smtp' ) . '</a>';

		array_unshift( $links, $settings_link, $logs_link );

		return $links;
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
			admin_url( 'admin.php' )
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
