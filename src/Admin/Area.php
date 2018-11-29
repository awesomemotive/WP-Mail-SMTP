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
	 * @var string Slug of the admin area page.
	 */
	const SLUG = 'wp-mail-smtp';

	/**
	 * @var string Admin page unique hook.
	 */
	public $hook;

	/**
	 * @var PageAbstract[]
	 */
	private $pages;

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

		$error   = isset( $_GET['error'] ) ? $_GET['error'] : '';
		$success = isset( $_GET['success'] ) ? $_GET['success'] : '';

		if ( empty( $error ) && empty( $success ) ) {
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
					esc_html__( 'You have successfully linked the current site with your Google API project. Now you can start sending emails through Google.', 'wp-mail-smtp' ),
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

		$default_options = function_exists( 'wp_json_encode' ) ? wp_json_encode( Options::get_defaults() ) : json_encode( Options::get_defaults() ); // phpcs:ignore
		$current_options = function_exists( 'wp_json_encode' ) ? wp_json_encode( Options::init()->get_all() ) : json_encode( Options::init()->get_all() ); // phpcs:ignore

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
	 */
	public function add_admin_options_page() {

		$this->hook = add_options_page(
			esc_html__( 'WP Mail SMTP Options', 'wp-mail-smtp' ),
			esc_html__( 'WP Mail SMTP', 'wp-mail-smtp' ),
			'manage_options',
			self::SLUG,
			array( $this, 'display' )
		);
	}

	/**
	 * Enqueue admin area scripts and styles.
	 *
	 * @since 1.0.0
	 *
	 * @param string $hook
	 */
	public function enqueue_assets( $hook ) {

		if ( $hook !== $this->hook ) {
			return;
		}

		wp_enqueue_style(
			'wp-mail-smtp-admin',
			wp_mail_smtp()->plugin_url . '/assets/css/smtp-admin.min.css',
			false,
			WPMS_PLUGIN_VER
		);

		wp_enqueue_script(
			'wp-mail-smtp-admin',
			wp_mail_smtp()->plugin_url . '/assets/js/smtp-admin' . WP::asset_min() . '.js',
			array( 'jquery' ),
			WPMS_PLUGIN_VER
		);
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
			<img class="wp-mail-smtp-header-logo" src="<?php echo wp_mail_smtp()->plugin_url; ?>/assets/images/logo.png" alt="WP Mail SMTP"/>
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
				/* translators: %1$s - WP.org link; %2$s - same WP.org link. */
				__( 'Please rate <strong>WP Mail SMTP</strong> <a href="%1$s" target="_blank" rel="noopener noreferrer">&#9733;&#9733;&#9733;&#9733;&#9733;</a> on <a href="%2$s" target="_blank">WordPress.org</a> to help us spread the word. Thank you from the WP Mail SMTP team!', 'wp-mail-smtp' ),
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
	 */
	public function display() {

		// Bail if we're not on a plugin page.
		if ( ! $this->is_admin_page() ) {
			return;
		}
		?>

		<div class="wrap" id="wp-mail-smtp">

			<div class="wp-mail-smtp-page-title">
				<?php
				foreach ( $this->get_pages() as $page_slug => $page ) :
					$label = $page->get_label();
					if ( empty( $label ) ) {
						continue;
					}
					$class = $page_slug === $this->get_current_tab() ? 'class="active"' : '';
					?>

					<a href="<?php echo $page->get_link(); ?>" <?php echo $class; ?>><?php echo $label; ?></a>

				<?php endforeach; ?>
			</div>

			<div class="wp-mail-smtp-page wp-mail-smtp-tab-<?php echo $this->get_current_tab(); ?>">
				<h1 class="screen-reader-text"><?php echo $this->get_current_tab_title(); ?></h1>

				<?php $this->display_current_tab_content(); ?>
			</div>

		</div>

		<?php
	}

	/**
	 * Get the current tab title.
	 *
	 * @since 1.0.0
	 */
	public function display_current_tab_content() {

		if ( ! array_key_exists( $this->get_current_tab(), $this->get_pages() ) ) {
			return;
		}

		$this->pages[ $this->get_current_tab() ]->display();
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

		if ( $this->is_admin_page() ) {
			$current = ! empty( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'settings';
		}

		return $current;
	}

	/**
	 * Get the array of default registered tabs for plugin admin area.
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

		if ( ! array_key_exists( $this->get_current_tab(), $this->get_pages() ) ) {
			return '';
		}

		return $this->pages[ $this->get_current_tab() ]->get_title();
	}

	/**
	 * Check whether we are on an admin page.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function is_admin_page() {

		$page = isset( $_GET['page'] ) ? $_GET['page'] : '';

		return self::SLUG === $page;
	}

	/**
	 * All possible plugin forms manipulation will be done here.
	 *
	 * @since 1.0.0
	 */
	public function process_actions() {

		// Bail if we're not on a plugin page.
		if ( ! $this->is_admin_page() ) {
			return;
		}

		// Allow to process only own tabs.
		if ( ! array_key_exists( $this->get_current_tab(), $this->get_pages() ) ) {
			return;
		}

		// Process POST only if it exists.
		if ( ! empty( $_POST ) ) {
			if ( ! empty( $_POST['wp-mail-smtp'] ) ) {
				$post = $_POST['wp-mail-smtp'];
			} else {
				$post = array();
			}

			$this->pages[ $this->get_current_tab() ]->process_post( $post );
		}

		// This won't do anything for most pages.
		$this->pages[ $this->get_current_tab() ]->process_auth();
	}

	/**
	 * Process all AJAX requests.
	 *
	 * @since 1.3.0
	 */
	public function process_ajax() {

		$task = sanitize_key( $_POST['task'] );
		$data = array();

		switch ( $task ) {
			case 'wpforms_dismiss':
				update_user_meta( get_current_user_id(), 'wp_mail_smtp_wpforms_dismissed', true );
				$data['message'] = esc_html__( 'WPForms related message was successfully dismissed', 'wp-mail-smtp' );
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
	 *
	 * @param array $links
	 * @param string $file
	 *
	 * @return mixed
	 */
	public function add_plugin_action_link( $links, $file ) {

		if ( strpos( $file, 'wp-mail-smtp' ) === false ) {
			return $links;
		}

		$settings_link = '<a href="' . $this->get_admin_page_url() . '">' . esc_html__( 'Settings', 'wp-mail-smtp' ) . '</a>';

		array_unshift( $links, $settings_link );

		return $links;
	}

	/**
	 * Get plugin admin area page URL.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_admin_page_url() {
		return add_query_arg(
			'page',
			self::SLUG,
			admin_url( 'options-general.php' )
		);
	}

	/**
	 * Remove all non-WP Mail SMTP plugin notices from plugin pages.
	 *
	 * @since 1.0.0
	 */
	public function hide_unrelated_notices() {

		// Bail if we're not on a our screen or page.
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
