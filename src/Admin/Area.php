<?php

namespace WPMailSMTP\Admin;

use WPMailSMTP\Options;
use WPMailSMTP\Providers\Mail;
use WPMailSMTP\Providers\Pepipost;
use WPMailSMTP\Providers\ProviderAbstract;
use WPMailSMTP\Providers\SMTP;
use WPMailSMTP\WP;

/**
 * Class Area registers and process all wp-admin display functionality.
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

		// Get the list of providers.
		add_action( 'admin_init', array( $this, 'get_providers' ) );

		// Outputs the plugin admin header.
		add_action( 'in_admin_header', array( $this, 'display_admin_header' ), 100 );

		// Add the pepipost only if it's active on a site.
		if ( Options::init()->is_pepipost_active() ) {
			add_filter( 'wp_mail_smtp_admin_get_providers', function ( $providers ) {

				$providers[] = new Pepipost();

				return $providers;
			} );
		}
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
				__( 'Please rate <strong>WP Mail SMTP</strong> <a href="%1$s" target="_blank" rel="noopener">&#9733;&#9733;&#9733;&#9733;&#9733;</a> on <a href="%2$s" target="_blank">WordPress.org</a> to help us spread the word. Thank you from the WP Mail SMTP team!', 'wp-mail-smtp' ),
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
		?>

		<div class="wrap" id="wp-mail-smtp">

			<div class="wp-mail-smtp-page-title">
				<?php foreach ( $this->get_pages() as $page_slug => $page ) : ?>
					<?php $class = $page_slug === $this->get_current_subpage() ? 'class="active"' : ''; ?>
					<a href="<?php echo $page->get_link(); ?>" <?php echo $class; ?>><?php echo $page->get_label(); ?></a>
				<?php endforeach; ?>
			</div>

			<div class="wp-mail-smtp-page">
				<h1><?php echo $this->get_current_subpage_title(); ?></h1>

				<?php $this->display_current_subpage_content(); ?>
			</div>

		</div>

		<?php
	}

	/**
	 * Get the current subpage title.
	 *
	 * @since 1.0.0
	 */
	public function display_current_subpage_content() {

		if ( ! array_key_exists( $this->get_current_subpage(), $this->get_pages() ) ) {
			return;
		}

		$this->pages[ $this->get_current_subpage() ]->display();
	}

	/**
	 * Get the current admin area subpage.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	protected function get_current_subpage() {
		return ! empty( $_GET['subpage'] ) ? sanitize_key( $_GET['subpage'] ) : 'settings';
	}

	/**
	 * Get the array of default registered subpages for plugin admin area.
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
			);
		}

		return apply_filters( 'wp_mail_smtp_admin_get_pages', $this->pages );
	}

	/**
	 * Get the current subpage title.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_current_subpage_title() {

		if ( ! array_key_exists( $this->get_current_subpage(), $this->get_pages() ) ) {
			return '';
		}

		return $this->pages[ $this->get_current_subpage() ]->get_title();
	}

	/**
	 * Default providers (mail/smtp) should not be redefined or removed.
	 *
	 * @since 1.0.0
	 *
	 * @return \WPMailSMTP\Providers\ProviderAbstract[]
	 */
	public function get_providers() {

		$custom   = array();
		$filtered = apply_filters( 'wp_mail_smtp_admin_get_providers', array() );

		// Do not allow providers, that are not valid for further usage.
		foreach ( $filtered as $key => $provider ) {

			if ( ! $provider instanceof ProviderAbstract ) {
				continue;
			}

			$slug  = $provider->get_slug();
			$title = $provider->get_title();

			if ( empty( $title ) || empty( $slug ) ) {
				continue;
			}

			$custom[ $key ] = $provider;
		}

		// Some default providers should always be present.
		$providers = array_merge(
			array(
				new Mail(),
			),
			$custom,
			array(
				new SMTP(),
			)
		);

		return $providers;
	}

	/**
	 * Check whether we are on an admin page.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	protected function is_admin_page() {

		$page = isset( $_GET['page'] ) ? $_GET['page'] : '';

		return self::SLUG === $page;
	}

	/**
	 * All possible plugin forms manipulation will be done here.
	 *
	 * @since 1.0.0
	 */
	public function process_actions() {

		if ( empty( $_POST['wp-mail-smtp'] ) ) {
			return;
		}

		if ( ! array_key_exists( $this->get_current_subpage(), $this->get_pages() ) ) {
			return;
		}

		$this->pages[ $this->get_current_subpage() ]->process( $_POST['wp-mail-smtp'] );
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

		$settings_link = '<a href="options-general.php?page=' . self::SLUG . '">' . esc_html__( 'Settings', 'wp-mail-smtp' ) . '</a>';

		array_unshift( $links, $settings_link );

		return $links;
	}
}
