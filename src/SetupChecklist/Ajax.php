<?php

namespace WPMailSMTP\SetupChecklist;

use WPMailSMTP\Newsletter;
use WPMailSMTP\PartnerPlugins\Catalog;
use WPMailSMTP\PartnerPlugins\InstallPermission;
use WPMailSMTP\PartnerPlugins\Installer;
use WPMailSMTP\PartnerPlugins\PartnerPlugin;
use WPMailSMTP\Options;
use WPMailSMTP\SettingsImport\SettingsImport;
use WPMailSMTP\UsageTracking\UsageTracking;

/**
 * Setup Checklist AJAX endpoints.
 *
 * @since 4.10.0
 */
class Ajax {

	/**
	 * Shared plugin-wide nonce action every endpoint below verifies against.
	 *
	 * @since 4.10.0
	 *
	 * @var string
	 */
	const NONCE_ACTION = 'wp-mail-smtp-admin';

	/**
	 * Dismiss AJAX action.
	 *
	 * @since 4.10.0
	 *
	 * @var string
	 */
	const DISMISS_ACTION = 'wp_mail_smtp_setup_checklist_dismiss';

	/**
	 * Install-plugin AJAX action.
	 *
	 * @since 4.10.0
	 *
	 * @var string
	 */
	const INSTALL_PLUGIN_ACTION = 'wp_mail_smtp_setup_checklist_install_plugin';

	/**
	 * Import-settings AJAX action.
	 *
	 * @since 4.10.0
	 *
	 * @var string
	 */
	const IMPORT_SETTINGS_ACTION = 'wp_mail_smtp_setup_checklist_import_settings';

	/**
	 * Usage-tracking opt-in AJAX action.
	 *
	 * @since 4.10.0
	 *
	 * @var string
	 */
	const USAGE_TRACKING_ACTION = 'wp_mail_smtp_setup_checklist_usage_tracking_optin';

	/**
	 * Newsletter subscribe AJAX action.
	 *
	 * @since 4.10.0
	 *
	 * @var string
	 */
	const NEWSLETTER_ACTION = 'wp_mail_smtp_setup_checklist_subscribe';

	/**
	 * Checklist model (source of the progress percentage captured at dismissal).
	 *
	 * @since 4.10.0
	 *
	 * @var Checklist
	 */
	private $checklist;

	/**
	 * Per-site state store.
	 *
	 * @since 4.10.0
	 *
	 * @var State
	 */
	private $state;

	/**
	 * Partner plugin catalog, which is also the install allowlist.
	 *
	 * @since 4.10.0
	 *
	 * @var Catalog
	 */
	private $catalog;

	/**
	 * Constructor.
	 *
	 * @since 4.10.0
	 *
	 * @param Checklist $checklist Checklist model.
	 * @param State     $state     Per-site state store.
	 * @param Catalog   $catalog   Partner plugin catalog.
	 */
	public function __construct( Checklist $checklist, State $state, Catalog $catalog ) {

		$this->checklist = $checklist;
		$this->state     = $state;
		$this->catalog   = $catalog;
	}

	/**
	 * Register hooks.
	 *
	 * @since 4.10.0
	 */
	public function hooks(): void {

		add_action( 'wp_ajax_' . self::DISMISS_ACTION, [ $this, 'dismiss' ] );
		add_action( 'wp_ajax_' . self::INSTALL_PLUGIN_ACTION, [ $this, 'install_plugin' ] );
		add_action( 'wp_ajax_' . self::IMPORT_SETTINGS_ACTION, [ $this, 'import_settings' ] );
		add_action( 'wp_ajax_' . self::USAGE_TRACKING_ACTION, [ $this, 'usage_tracking_optin' ] );
		add_action( 'wp_ajax_' . self::NEWSLETTER_ACTION, [ $this, 'subscribe' ] );
	}

	/**
	 * Dismiss the checklist for this site and return where to redirect next.
	 *
	 * @since 4.10.0
	 */
	public function dismiss(): void {

		if ( check_ajax_referer( self::NONCE_ACTION, 'nonce', false ) === false ) {
			wp_send_json_error( esc_html__( 'Your session expired. Please reload the page and try again.', 'wp-mail-smtp' ) );
		}

		if ( ! current_user_can( wp_mail_smtp()->get_capability_manage_options() ) ) {
			wp_send_json_error( esc_html__( 'You do not have permission to dismiss the Setup Checklist.', 'wp-mail-smtp' ) );
		}

		$this->state->dismiss( $this->checklist->get_progress()['percent'] );

		wp_send_json_success(
			[
				'redirect_url' => wp_mail_smtp()->get_admin()->get_admin_page_url(),
			]
		);
	}

	/**
	 * Import settings from another detected SMTP plugin.
	 *
	 * @since 4.10.0
	 */
	public function import_settings(): void {

		if ( check_ajax_referer( self::NONCE_ACTION, 'nonce', false ) === false ) {
			wp_send_json_error( esc_html__( 'Your session expired. Please reload the page and try again.', 'wp-mail-smtp' ) );
		}

		if ( ! current_user_can( wp_mail_smtp()->get_capability_manage_options() ) ) {
			wp_send_json_error( esc_html__( 'You do not have permission to change these settings.', 'wp-mail-smtp' ) );
		}

		$plugin = isset( $_POST['plugin'] ) ? sanitize_key( wp_unslash( $_POST['plugin'] ) ) : '';

		if ( $plugin === '' ) {
			wp_send_json_error( esc_html__( 'Please select a plugin to import settings from.', 'wp-mail-smtp' ) );
		}

		if ( ! ( new SettingsImport() )->import_from_plugin( $plugin ) ) {
			wp_send_json_error( esc_html__( 'Could not find any settings to import from the selected plugin.', 'wp-mail-smtp' ) );
		}

		wp_send_json_success();
	}

	/**
	 * Opt into usage tracking from the checklist.
	 *
	 * @since 4.10.0
	 */
	public function usage_tracking_optin(): void {

		if ( check_ajax_referer( self::NONCE_ACTION, 'nonce', false ) === false ) {
			wp_send_json_error( esc_html__( 'Your session expired. Please reload the page and try again.', 'wp-mail-smtp' ) );
		}

		if ( ! current_user_can( wp_mail_smtp()->get_capability_manage_options() ) ) {
			wp_send_json_error( esc_html__( 'You do not have permission to change these settings.', 'wp-mail-smtp' ) );
		}

		// The third argument is `$overwrite_existing`: its `true` default would wipe every other setting.
		Options::init()->set(
			[ 'general' => [ UsageTracking::SETTINGS_SLUG => true ] ],
			false,
			false
		);

		wp_send_json_success();
	}

	/**
	 * Subscribe an email address to the newsletter from the checklist.
	 *
	 * @since 4.10.0
	 */
	public function subscribe(): void {

		if ( check_ajax_referer( self::NONCE_ACTION, 'nonce', false ) === false ) {
			wp_send_json_error( esc_html__( 'Your session expired. Please reload the page and try again.', 'wp-mail-smtp' ) );
		}

		if ( ! current_user_can( wp_mail_smtp()->get_capability_manage_options() ) ) {
			wp_send_json_error( esc_html__( 'You do not have permission to subscribe to the newsletter.', 'wp-mail-smtp' ) );
		}

		$email = isset( $_POST['email'] ) ? filter_var( wp_unslash( $_POST['email'] ), FILTER_VALIDATE_EMAIL ) : '';

		if ( empty( $email ) ) {
			wp_send_json_error( esc_html__( 'Please enter a valid email address.', 'wp-mail-smtp' ) );
		}

		( new Newsletter() )->subscribe( $email );

		wp_send_json_success();
	}

	/**
	 * Install a WordPress.org plugin, or activate it when the site already has it.
	 *
	 * @since 4.10.0
	 */
	public function install_plugin(): void {

		if ( check_ajax_referer( self::NONCE_ACTION, 'nonce', false ) === false ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Your session expired. Please reload the page and try again.', 'wp-mail-smtp' ) ] );
		}

		$plugin     = $this->resolve_requested_plugin();
		$permission = new InstallPermission();
		$permitted  = $permission->check( $plugin );

		if ( is_wp_error( $permitted ) ) {
			wp_send_json_error( $permission->get_refusal_payload( $permitted ) );
		}

		// Avoids undefined notices from the file operations the installer runs.
		set_current_screen( 'wp-mail-smtp_page_' . Page::SLUG );

		$redirect_url = esc_url_raw( wp_mail_smtp()->get_admin()->get_admin_page_url( Page::SLUG ) );

		$result = ( new Installer() )->install_or_activate( $plugin, $redirect_url );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( [ 'message' => $result->get_error_message() ] );
		}

		wp_send_json_success( [ 'activated' => $plugin->is_active() ] );
	}

	/**
	 * The plugin the request names, or a JSON error when it names one the catalog does
	 * not know.
	 *
	 * @since 4.10.0
	 *
	 * @return PartnerPlugin
	 */
	private function resolve_requested_plugin(): PartnerPlugin {

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- the caller verifies the nonce before this runs.
		$basename = isset( $_POST['plugin'] ) && is_string( $_POST['plugin'] ) ? sanitize_text_field( wp_unslash( $_POST['plugin'] ) ) : '';

		if ( $basename === '' ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Please select a plugin to install.', 'wp-mail-smtp' ) ] );
		}

		$plugin = $this->catalog->get_by_basename( $basename );

		if ( $plugin === null ) {
			wp_send_json_error( [ 'message' => esc_html__( 'That plugin is not one we can install for you.', 'wp-mail-smtp' ) ] );
		}

		return $plugin;
	}
}
