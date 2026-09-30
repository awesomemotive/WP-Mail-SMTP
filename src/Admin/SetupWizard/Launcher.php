<?php

namespace WPMailSMTP\Admin\SetupWizard;

use WPMailSMTP\Admin\Area;
use WPMailSMTP\WP;

/**
 * Entry point shared by both Setup Wizard variants.
 *
 * @since 4.10.0
 */
class Launcher {

	/**
	 * Admin page slug the wizard renders on.
	 *
	 * @since 4.10.0
	 *
	 * @var string
	 */
	const SLUG = Area::SLUG . '-setup-wizard';

	/**
	 * Query arg selecting a wizard variant explicitly.
	 *
	 * @since 4.10.0
	 *
	 * @var string
	 */
	const ARG_TYPE = 'type';

	/**
	 * The bundled wizard variant.
	 *
	 * @since 4.10.0
	 *
	 * @var string
	 */
	const VARIANT_LOCAL = 'local';

	/**
	 * The hosted wizard variant.
	 *
	 * @since 4.10.0
	 *
	 * @var string
	 */
	const VARIANT_HOSTED = 'hosted';

	/**
	 * Hosted wizard instance.
	 *
	 * @since 4.10.0
	 *
	 * @var Hosted
	 */
	private $hosted;

	/**
	 * Bundled wizard instance.
	 *
	 * @since 4.10.0
	 *
	 * @var Local
	 */
	private $local;

	/**
	 * Register hooks.
	 *
	 * @since 4.10.0
	 *
	 * @return void
	 */
	public function hooks() {

		add_action( 'admin_menu', [ $this, 'add_dashboard_page' ], 20 );
		add_action( 'admin_init', [ $this, 'maybe_load' ], PHP_INT_MAX );
		add_action( 'admin_init', [ $this, 'maybe_redirect_after_activation' ], 9999 );

		// Ungated: the hosted wizard's REST routes are served on requests where
		// is_admin() is false.
		$this->get_hosted()->hooks();

		// admin-ajax.php defines WP_ADMIN before plugins load, so is_admin() still
		// covers the bundled wizard's ajax handlers.
		if ( is_admin() ) {
			$this->get_local()->hooks();
		}
	}

	/**
	 * Get the hosted wizard.
	 *
	 * @since 4.10.0
	 *
	 * @return Hosted
	 */
	private function get_hosted() {

		if ( is_null( $this->hosted ) ) {
			$this->hosted = new Hosted();
		}

		return $this->hosted;
	}

	/**
	 * Get the bundled wizard.
	 *
	 * @since 4.10.0
	 *
	 * @return Local
	 */
	private function get_local() {

		if ( is_null( $this->local ) ) {
			$this->local = new Local();
		}

		return $this->local;
	}

	/**
	 * Register page through WordPress's hooks.
	 *
	 * Create a dummy admin page, where the Setup Wizard app can be displayed,
	 * but it's not visible in the admin dashboard menu.
	 *
	 * @since 4.10.0
	 *
	 * @return void
	 */
	public function add_dashboard_page() {

		if ( ! $this->should_setup_wizard_load() ) {
			return;
		}

		add_submenu_page( '', '', '', wp_mail_smtp()->get_capability_manage_global_options(), self::SLUG, '' );
	}

	/**
	 * Check if the Setup Wizard should load.
	 *
	 * @since 4.10.0
	 *
	 * @return bool
	 */
	public function should_setup_wizard_load() {

		/**
		 * Whether the Setup Wizard should be available at all.
		 *
		 * Closing this gate hides the admin page, suppresses the post-activation
		 * redirect, and stops both variants from rendering.
		 *
		 * The name is depended on externally, including by the WP.org preview
		 * blueprint.
		 *
		 * @since 2.6.0
		 *
		 * @param bool $load Whether to load the Setup Wizard.
		 */
		return (bool) apply_filters( 'wp_mail_smtp_admin_setup_wizard_load_wizard', true ); // phpcs:ignore WPForms.PHP.ValidateHooks.InvalidHookName -- Public hook name, kept for backwards compatibility.
	}

	/**
	 * Route the wizard page to a variant.
	 *
	 * Nothing but the page slug is read off this URL: a return into a wizard
	 * mid-run addresses that wizard's own URL instead.
	 *
	 * Runs last on admin_init: rendering ends the request, so the redirect bridge
	 * and the provider auth callbacks have to run first.
	 *
	 * @since 4.10.0
	 *
	 * @return void
	 */
	public function maybe_load() {

		if ( ! $this->can_load() ) {
			return;
		}

		// Only its own type=local URL renders the bundled wizard, so that URL stays
		// bookmarkable and valid as a return target.
		if ( $this->is_local() ) {
			$this->get_local()->render();
			exit;
		}

		$this->get_hosted()->render_welcome( self::get_url( self::VARIANT_LOCAL ) );
		exit;
	}

	/**
	 * Whether this request should be served a wizard at all.
	 *
	 * Ordered cheapest-first so the launch gate filter only runs on the wizard
	 * page, not on every admin request.
	 *
	 * @since 4.10.0
	 *
	 * @return bool
	 */
	private function can_load() {

		return $this->is_wizard_page()
			&& ! wp_doing_ajax()
			&& current_user_can( wp_mail_smtp()->get_capability_manage_global_options() )
			&& $this->should_setup_wizard_load();
	}

	/**
	 * Whether the current request is for the wizard page.
	 *
	 * @since 4.10.0
	 *
	 * @return bool
	 */
	private function is_wizard_page() {

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return isset( $_GET['page'] ) && $_GET['page'] === self::SLUG;
	}

	/**
	 * Whether the request explicitly asks for the bundled wizard.
	 *
	 * @since 4.10.0
	 *
	 * @return bool
	 */
	private function is_local() {

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return isset( $_GET[ self::ARG_TYPE ] ) && sanitize_key( $_GET[ self::ARG_TYPE ] ) === self::VARIANT_LOCAL;
	}

	/**
	 * Maybe redirect to the Setup Wizard after plugin activation on a new install.
	 *
	 * @since 4.10.0
	 *
	 * @return void
	 */
	public function maybe_redirect_after_activation() { // phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh

		if ( wp_doing_ajax() || wp_doing_cron() ) {
			return;
		}

		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		if ( ! get_transient( 'wp_mail_smtp_activation_redirect' ) ) {
			return;
		}

		delete_transient( 'wp_mail_smtp_activation_redirect' );

		if ( get_option( 'wp_mail_smtp_activation_prevent_redirect' ) ) {
			return;
		}

		if ( isset( $_GET['activate-multi'] ) || is_network_admin() || WP::use_global_plugin_settings() ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		if ( ! $this->should_setup_wizard_load() ) {
			return;
		}

		if ( get_option( 'wp_mail_smtp_initial_version' ) === WPMS_PLUGIN_VER ) {
			update_option( 'wp_mail_smtp_activation_prevent_redirect', true );
			wp_safe_redirect( self::get_url() );
			exit;
		}
	}

	/**
	 * Record the wizard run that is starting.
	 *
	 * @since 4.10.0
	 *
	 * @param string $variant Wizard variant the run is starting in.
	 * @param string $reason  Why it is the bundled wizard, empty for a hosted run.
	 *
	 * @return void
	 */
	public static function record_start( $variant, $reason ) {

		Stats::update(
			[
				'launched_time' => time(),
				'type'          => $variant,
			]
		);

		/**
		 * When a Setup Wizard run starts. Fired once per launch, when the variant is
		 * settled.
		 *
		 * @since 4.10.0
		 *
		 * @param string $variant Wizard variant the run started in: 'local' or 'hosted'.
		 * @param string $reason  Why it is the bundled wizard: a reason of the wizard's
		 *                        own, or a handshake error code. Empty for a hosted run.
		 */
		do_action( 'wp_mail_smtp_admin_setup_wizard_launcher_started', $variant, $reason );
	}

	/**
	 * Build the URL a provider's auth callback returns a wizard user to.
	 *
	 * The variant is addressed rather than left to the router: the router forwards
	 * no query args, so a failure returning through it loses its reason.
	 *
	 * @since 4.10.0
	 *
	 * @param mixed  $variant Wizard variant stored when the connection was started.
	 * @param string $mailer  Mailer slug whose step to return to.
	 *
	 * @return string
	 */
	public static function get_oauth_return_url( $variant, $mailer ) {

		// The stored flag can be a plain boolean, so anything truthy that is not the
		// hosted variant means the bundled wizard.
		if ( $variant !== self::VARIANT_HOSTED ) {
			$variant = self::VARIANT_LOCAL;
		}

		return self::get_url( $variant, [], '/step/configure_mailer/' . $mailer );
	}

	/**
	 * Build a Setup Wizard URL.
	 *
	 * Query values are encoded here because add_query_arg() does not encode what
	 * it adds: a value carrying a '#' would otherwise be read as the fragment of
	 * the URL it was added to.
	 *
	 * @since 4.10.0
	 *
	 * @param string|null $variant Variant to address: 'local', 'hosted', or null
	 *                             to let the router decide per request.
	 * @param array       $query   Query args to add.
	 * @param string      $hash    Fragment, with or without a leading '#'.
	 *
	 * @return string
	 */
	public static function get_url( $variant = null, array $query = [], $hash = '' ) {

		if ( $variant === self::VARIANT_HOSTED ) {
			$url = Hosted::get_base_url();
		} elseif ( $variant === self::VARIANT_LOCAL ) {
			$url = Local::get_base_url();
		} else {
			$url = wp_mail_smtp()->get_admin()->get_admin_page_url( self::SLUG );
		}

		if ( ! empty( $query ) ) {
			$url = add_query_arg( array_map( 'rawurlencode', $query ), $url );
		}

		if ( $hash !== '' ) {
			$url .= '#' . ltrim( $hash, '#' );
		}

		return $url;
	}
}
