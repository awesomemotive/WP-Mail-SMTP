<?php

namespace WPMailSMTP\Admin\SetupWizard;

use WPMailSMTP\Admin\Area;
use WPMailSMTP\Options;
use WPMailSMTP\Providers\Gmail\Auth as GmailAuth;
use WPMailSMTP\Reports\Emails\Summary as SummaryReportEmail;
use WPMailSMTP\SettingsImport\SettingsImport;
use WPMailSMTP\WP;

/**
 * The hosted Setup Wizard: a cross-origin SPA the plugin hands off to, backed by
 * this plugin's signed REST endpoints.
 *
 * @since 4.10.0
 */
class Hosted {

	/**
	 * Rest API instance.
	 *
	 * @since 4.10.0
	 *
	 * @var RestApi
	 */
	private $api;

	/**
	 * Auth instance.
	 *
	 * @since 4.10.0
	 *
	 * @var Auth
	 */
	private $auth;

	/**
	 * Redirect bridge instance.
	 *
	 * @since 4.10.0
	 *
	 * @var RedirectBridge
	 */
	private $bridge;

	/**
	 * Setup Wizard URL.
	 *
	 * @since 4.10.0
	 *
	 * @var string
	 */
	const URL = 'https://wpmailsmtpapi.com/setupwizard/v1';

	/**
	 * Constructor.
	 *
	 * @since 4.10.0
	 */
	public function __construct() {

		$this->auth   = new Auth();
		$this->api    = new RestApi( $this );
		$this->bridge = new RedirectBridge();
	}

	/**
	 * Register hooks.
	 *
	 * @since 4.10.0
	 *
	 * @return void
	 */
	public function hooks() {

		add_action( 'rest_api_init', [ $this, 'initialize_api' ] );
		add_action( 'wp_ajax_wp_mail_smtp_setup_wizard_start', [ $this, 'ajax_start_wizard' ] );

		// Before the handlers that validate the cross-origin return_url: the redirect
		// bridge and SendLayer's handle_auth_complete, both on admin_init at the
		// default priority.
		add_action( 'admin_init', [ $this, 'register_allowed_hosts' ], 5 );

		$this->bridge->hooks();
	}

	/**
	 * Register allowed redirect hosts filter.
	 *
	 * Registered for every admin request rather than only the ones returning to the
	 * wizard: a provider's OAuth callback redirects there while carrying nothing but
	 * that provider's own query args, so there is no reliable way to recognise those
	 * requests up front.
	 *
	 * @since 4.10.0
	 *
	 * @return void
	 */
	public function register_allowed_hosts() { // phpcs:ignore WPForms.PHP.HooksMethod.InvalidPlaceForAddingHooks

		add_filter( 'allowed_redirect_hosts', [ $this, 'get_redirect_hosts' ] );
	}

	/**
	 * Get the token instance.
	 *
	 * @since 4.10.0
	 *
	 * @return Auth
	 */
	public function get_auth() {

		return $this->auth;
	}

	/**
	 * Render the wizard's welcome step on this site, handing off to the hosted
	 * wizard only when the user clicks through (a WordPress.org requirement).
	 *
	 * @since 4.10.0
	 *
	 * @param string $fallback_url Where to send the browser when the hosted wizard
	 *                             is not reached: either because it is not on offer
	 *                             here at all, or because the handshake failed for
	 *                             a reason that says it is unavailable.
	 *
	 * @return void
	 */
	public function render_welcome( $fallback_url ) {

		// JSON-encoded, not HTML-escaped: these are read as JavaScript string literals.
		$settings = wp_json_encode(
			[
				'ajax_url'     => admin_url( 'admin-ajax.php' ),
				'action'       => 'wp_mail_smtp_setup_wizard_start',
				'nonce'        => wp_create_nonce( 'wp-mail-smtp-admin' ),
				'handoff_url'  => $this->get_url(),
				'fallback_url' => $fallback_url,
			],
			JSON_HEX_TAG
		);

		$assets_url = wp_mail_smtp()->assets_url;
		$rtl        = is_rtl() ? '.rtl' : '';

		nocache_headers();
		header( 'Referrer-Policy: no-referrer' );
		header( 'X-Frame-Options: DENY' );
		header( 'X-Content-Type-Options: nosniff' );
		header( 'Content-Type: text/html; charset=' . get_option( 'blog_charset' ) );

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The template escapes what it prints.
		echo wp_mail_smtp_render(
			'setup-wizard/welcome',
			[
				'settings'             => $settings,
				'css_url'              => $assets_url . '/vue/css/wizard' . $rtl . '.min.css?ver=' . WPMS_PLUGIN_VER,
				'logo_url'             => $assets_url . '/vue/img/logo.svg',
				'loading_url'          => $assets_url . '/vue/img/loading-pattie.svg',
				'settings_url'         => wp_mail_smtp()->get_admin()->get_admin_page_url(),
				// A site with nothing to transfer to is shown no transfer notice.
				'is_local_environment' => WP::is_local_environment(),
			],
			true
		);
	}

	/**
	 * AJAX handler exchanging this site's signing key for a single-use ticket the
	 * browser can carry to the hosted wizard.
	 *
	 * The key travels server to server and never reaches the browser: signed
	 * requests are hydrated as the user it was issued to, so a copy in the page
	 * would be a portable, cookie-free admin credential for this site for as long
	 * as the key lives.
	 *
	 * @since 4.10.0
	 *
	 * @return void
	 */
	public function ajax_start_wizard() {

		if ( ! check_ajax_referer( 'wp-mail-smtp-admin', 'nonce', false ) ) {
			$this->send_start_session_error(
				esc_html__( 'Security check failed. Please reload the page and try again.', 'wp-mail-smtp' ),
				'plugin.start_session.invalid_nonce'
			);
		}

		if ( ! current_user_can( wp_mail_smtp()->get_capability_manage_global_options() ) ) {
			$this->send_start_session_error(
				esc_html__( 'You don\'t have permission to change options for this WP site!', 'wp-mail-smtp' ),
				'plugin.start_session.permission_denied'
			);
		}

		// Nothing to hand off to, so the run is a bundled one and already settled.
		if ( WP::is_local_environment() ) {
			Launcher::record_start( Launcher::VARIANT_LOCAL, 'local_environment' );

			wp_send_json_success( [] );
		}

		$token = $this->get_auth()->get_token();

		if ( empty( $token ) ) {
			$this->send_start_session_fallback(
				esc_html__( 'The setup wizard could not be started for your account.', 'wp-mail-smtp' ),
				'plugin.start_session.no_token'
			);
		}

		$session = $this->request_session( $token );

		Launcher::record_start( Launcher::VARIANT_HOSTED, '' );

		wp_send_json_success(
			[
				'ticket' => sanitize_text_field( $session['ticket'] ),
			]
		);
	}

	/**
	 * Trade the signing key for a ticket with the hosted wizard.
	 *
	 * @since 4.10.0
	 *
	 * @param string $token Signing key for this site's wizard REST API.
	 *
	 * @return array Session response carrying the ticket. Does not return at all
	 *               when the exchange fails: the response has already been sent.
	 */
	private function request_session( $token ) {

		$response = wp_remote_post(
			$this->get_url( '/session' ),
			[
				// Clear of the hosted wizard's own budget for reaching this site and
				// answering, and inside a 30 second max_execution_time.
				'timeout' => 12,
				'body'    => array_merge(
					[
						'token'           => $token,
						'rest_url'        => $this->api->get_url(),
						'installation_id' => $this->get_installation_id(),
						'site_url'        => wp_mail_smtp()->get_license_site_url()->get(),
					],
					$this->get_site_info()
				),
			]
		);

		if ( is_wp_error( $response ) ) {
			$this->send_start_session_fallback(
				esc_html__( 'The setup wizard could not be reached.', 'wp-mail-smtp' ),
				'plugin.start_session.api_unreachable'
			);
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( wp_remote_retrieve_response_code( $response ) !== 200 ) {
			$this->send_start_session_fallback(
				esc_html__( 'The setup wizard is temporarily unavailable.', 'wp-mail-smtp' ),
				$this->get_session_error_code( $body )
			);
		}

		if ( empty( $body['ticket'] ) ) {
			$this->send_start_session_fallback(
				esc_html__( 'The setup wizard returned an unexpected response.', 'wp-mail-smtp' ),
				'plugin.start_session.unexpected_response'
			);
		}

		return $body;
	}

	/**
	 * This site's installation ID, read or created exactly as `ProductApi\Auth\InstallationId::get()` does.
	 *
	 * @since 4.10.0
	 *
	 * @return string
	 */
	private function get_installation_id() {

		$option  = 'wp_mail_smtp_product_api_options';
		$options = get_option( $option, [] );
		$options = is_array( $options ) ? $options : [];

		if ( ! empty( $options['wp_installation_id'] ) ) {
			return $options['wp_installation_id'];
		}

		$data = sprintf(
			'%s%s%s',
			defined( 'AUTH_KEY' ) ? AUTH_KEY : '',
			defined( 'SECURE_AUTH_KEY' ) ? SECURE_AUTH_KEY : '',
			defined( 'LOGGED_IN_KEY' ) ? LOGGED_IN_KEY : ''
		);

		if ( trim( $data ) === '' ) {
			$data = bin2hex( random_bytes( 32 ) );
		}

		$options['wp_installation_id'] = substr( hash( 'sha256', $data ), 0, 30 );

		// Merged, not replaced: the client keeps its auth token in the same option.
		update_option( $option, $options, false );

		return $options['wp_installation_id'];
	}

	/**
	 * Site details, gathered exactly as `ProductApi\Events\EventsAuthStrategy::get_site_info()` does.
	 *
	 * @since 4.10.0
	 *
	 * @return array
	 */
	private function get_site_info() {

		$admin_email = get_option( 'admin_email' );

		$info = [
			'site_title'    => get_bloginfo( 'name' ),
			'site_locale'   => get_locale(),
			'site_timezone' => wp_timezone_string(),
			'admin_email'   => $admin_email,
			'environment'   => wp_get_environment_type(),
			'is_multisite'  => is_multisite(),
		];

		if ( empty( $admin_email ) ) {
			return $info;
		}

		$user = get_user_by( 'email', $admin_email );

		if ( $user === false ) {
			return $info;
		}

		if ( ! empty( $user->first_name ) ) {
			$info['admin_first_name'] = $user->first_name;
		}

		if ( ! empty( $user->last_name ) ) {
			$info['admin_last_name'] = $user->last_name;
		}

		return $info;
	}

	/**
	 * The code to record for a refused handshake, preferring the hosted wizard's
	 * own over anything this side can tell.
	 *
	 * The value is sanitized rather than trusted, keeping the dots that
	 * sanitize_key() would strip.
	 *
	 * @since 4.10.0
	 *
	 * @param mixed $body Decoded session response body.
	 *
	 * @return string
	 */
	private function get_session_error_code( $body ) {

		$code = isset( $body['error_code'] ) && is_string( $body['error_code'] )
			? preg_replace( '/[^a-z0-9._-]/', '', strtolower( $body['error_code'] ) )
			: '';

		return $code === '' ? 'plugin.start_session.api_error' : $code;
	}

	/**
	 * Refuse the launch, answering the welcome page with why. No wizard starts, so
	 * there is no run to record.
	 *
	 * @since 4.10.0
	 *
	 * @param string $message    Localized message.
	 * @param string $error_code Machine-readable code the welcome page reports and
	 *                           support tickets quote.
	 *
	 * @return void
	 */
	private function send_start_session_error( $message, $error_code ) {

		wp_send_json_error(
			[
				'message'    => esc_html( $message ),
				'error_code' => $error_code,
				'fallback'   => false,
			]
		);
	}

	/**
	 * Refuse the handshake and send the browser to the bundled wizard, which is the
	 * run that starts here.
	 *
	 * @since 4.10.0
	 *
	 * @param string $message    Localized message.
	 * @param string $error_code Machine-readable code the welcome page reports and
	 *                           support tickets quote.
	 *
	 * @return void
	 */
	private function send_start_session_fallback( $message, $error_code ) {

		Launcher::record_start( Launcher::VARIANT_LOCAL, $error_code );

		wp_send_json_error(
			[
				'message'    => esc_html( $message ),
				'error_code' => $error_code,
				'fallback'   => true,
			]
		);
	}

	/**
	 * Initialize the rest API.
	 *
	 * @since 4.10.0
	 *
	 * @return void
	 */
	public function initialize_api() {

		$this->api->register_routes();
	}

	/**
	 * Base URL of the product-api hosted wizard shell.
	 *
	 * @since 4.10.0
	 *
	 * @return string
	 */
	public static function get_base_url() {

		return defined( 'WPMS_SETUP_WIZARD_URL' ) ? WPMS_SETUP_WIZARD_URL : self::URL;
	}

	/**
	 * Get the setup wizard URL.
	 *
	 * @since 4.10.0
	 *
	 * @param string $path Optional URL path.
	 *
	 * @return string
	 */
	public function get_url( $path = '' ) {

		return self::get_base_url() . $path;
	}

	/**
	 * Filter safe redirect hosts.
	 *
	 * @since 4.10.0
	 *
	 * @param array $hosts List of hosts.
	 *
	 * @return array
	 */
	public function get_redirect_hosts( $hosts ) {

		// Parsed from the base URL directly so this filter callback does not issue a
		// token as a side effect on every wp_safe_redirect call.
		$host = wp_parse_url( self::get_base_url(), PHP_URL_HOST );

		if ( ! empty( $host ) ) {
			$hosts[] = $host;
		}

		return $hosts;
	}

	/**
	 * Bootstrap data shared with the Setup Wizard Vue app via the REST hydrate response.
	 *
	 * @since 4.10.0
	 *
	 * @return array
	 */
	public function get_bootstrap_data() {

		/**
		 * The license's standing, which the wizard's license step renders.
		 *
		 * Empty in Lite, which has no license to report on. The wizard owns the copy for
		 * each state, so only the state itself crosses.
		 *
		 * @since 4.10.0
		 *
		 * @param string $license_state One of `valid`, `no_key`, `expired`, `limit_reached`,
		 *                              `disabled` or `invalid`. Empty in Lite.
		 */
		$license_state = apply_filters( 'wp_mail_smtp_admin_setup_wizard_hosted_license_state', '' );

		return [
			'is_multisite'         => is_multisite(),
			'translations'         => WP::get_jed_locale_data( 'wp-mail-smtp' ),
			'plugin_admin_url'     => wp_mail_smtp()->get_admin()->get_admin_page_url(),
			'wizard_restart_url'   => Launcher::get_url(),
			'email_test_tab_url'   => add_query_arg( 'tab', 'test', wp_mail_smtp()->get_admin()->get_admin_page_url( Area::SLUG . '-tools' ) ),
			'is_pro'               => wp_mail_smtp()->is_pro(),
			'is_ssl'               => is_ssl(),
			// A key is present in every state but these two, so the state answers this as well.
			'license_exists'       => ! in_array( $license_state, [ '', 'no_key' ], true ),
			'license_state'        => $license_state,
			'plugin_version'       => WPMS_PLUGIN_VER,
			'license_type'         => wp_mail_smtp()->get_license_type(),
			'wpforms_version_type' => $this->get_wpforms_version_type(),
			'other_smtp_plugins'   => $this->detect_other_smtp_plugins(),
			'mailer_options'       => $this->prepare_mailer_options(),
			'defined_constants'    => $this->prepare_defined_constants(),
			'upgrade_link'         => wp_mail_smtp()->get_upgrade_link( [ 'medium' => 'setup-wizard' ] ),
			'versions'             => $this->prepare_versions_data(),
			'current_user_email'   => wp_get_current_user()->user_email,
			'completed_time'       => Stats::get()['completed_time'],
			'sendlayer'            => [
				'return_url' => self::get_base_url() . '#/step/configure_mailer/sendlayer',
			],
			'redirect_bridge'      => [
				'arg'     => RedirectBridge::ARG,
				'actions' => [
					'sendlayer_connect' => RedirectBridge::ACTION_SENDLAYER_CONNECT,
				],
			],
		];
	}

	/**
	 * Detect if any other SMTP plugin options are defined.
	 *
	 * @since 4.10.0
	 *
	 * @return array
	 */
	private function detect_other_smtp_plugins() {

		return ( new SettingsImport() )->get_detected_plugin_slugs();
	}

	/**
	 * Prepare mailer options for all mailers.
	 *
	 * @since 4.10.0
	 *
	 * @return array
	 */
	private function prepare_mailer_options() {

		$data = [];

		foreach ( wp_mail_smtp()->get_providers()->get_options_all() as $provider ) {
			$data[ $provider->get_slug() ] = [
				'slug'          => $provider->get_slug(),
				'title'         => $provider->get_title(),
				'description'   => $provider->get_description(),
				'edu_notice'    => $provider->get_notice( 'educational' ),
				'min_php'       => $provider->get_php_version(),
				'disabled'      => $provider->is_disabled(),
				'has_preflight' => wp_mail_smtp()->get_providers()->has_preflight( $provider->get_slug() ),
			];

			if ( $provider->get_slug() === 'gmail' ) {
				$data['gmail']['redirect_uri'] = GmailAuth::get_oauth_redirect_url();
			}
		}

		/**
		 * Filter the mailer options handed to the Setup Wizard.
		 *
		 * @since 2.6.0
		 *
		 * @param array $data Mailer options keyed by mailer slug.
		 */
		return apply_filters( 'wp_mail_smtp_admin_setup_wizard_prepare_mailer_options', $data ); // phpcs:ignore WPForms.PHP.ValidateHooks.InvalidHookName -- Public hook name, kept for backwards compatibility.
	}

	/**
	 * Prepare version data.
	 *
	 * @since 4.10.0
	 *
	 * @return array
	 */
	private function prepare_versions_data() {

		global $wp_version;

		/**
		 * Whether to suppress the PHP 5.5 upgrade warning.
		 *
		 * @since 2.6.0
		 *
		 * @param bool $hide Whether the running PHP version is below 5.5.
		 */
		$below_55 = apply_filters( 'wp_mail_smtp_temporarily_hide_php_under_55_upgrade_warnings', version_compare( phpversion(), '5.5', '<' ) ); // phpcs:ignore WPForms.PHP.ValidateHooks.InvalidHookName -- Public hook name, kept for backwards compatibility.

		/**
		 * Whether to suppress the PHP 5.6 upgrade warning.
		 *
		 * @since 2.6.0
		 *
		 * @param bool $hide Whether the running PHP version is below 5.6.
		 */
		$below_56 = apply_filters( 'wp_mail_smtp_temporarily_hide_php_56_upgrade_warnings', version_compare( phpversion(), '5.6', '<' ) ); // phpcs:ignore WPForms.PHP.ValidateHooks.InvalidHookName -- Public hook name, kept for backwards compatibility.

		return [
			'php_version'          => phpversion(),
			'php_version_below_55' => $below_55,
			'php_version_below_56' => $below_56,
			'wp_version'           => $wp_version,
			'wp_version_below_49'  => version_compare( $wp_version, '4.9', '<' ),
		];
	}

	/**
	 * Prepare an array of WP Mail SMTP PHP constants in use.
	 * Those that are used in the setup wizard.
	 *
	 * @since 4.10.0
	 *
	 * @return array
	 */
	private function prepare_defined_constants() {

		$options = Options::init();

		if ( ! $options->is_const_enabled() ) {
			return [];
		}

		$constants = [
			'WPMS_MAIL_FROM'                     => [ 'mail', 'from_email' ],
			'WPMS_MAIL_FROM_FORCE'               => [ 'mail', 'from_email_force' ],
			'WPMS_MAIL_FROM_NAME'                => [ 'mail', 'from_name' ],
			'WPMS_MAIL_FROM_NAME_FORCE'          => [ 'mail', 'from_name_force' ],
			'WPMS_MAILER'                        => [ 'mail', 'mailer' ],
			'WPMS_SMTPCOM_API_KEY'               => [ 'smtpcom', 'api_key' ],
			'WPMS_SMTPCOM_CHANNEL'               => [ 'smtpcom', 'channel' ],
			'WPMS_SENDINBLUE_API_KEY'            => [ 'sendinblue', 'api_key' ],
			'WPMS_SENDINBLUE_DOMAIN'             => [ 'sendinblue', 'domain' ],
			'WPMS_AMAZONSES_CLIENT_ID'           => [ 'amazonses', 'client_id' ],
			'WPMS_AMAZONSES_CLIENT_SECRET'       => [ 'amazonses', 'client_secret' ],
			'WPMS_AMAZONSES_REGION'              => [ 'amazonses', 'region' ],
			'WPMS_GMAIL_CLIENT_ID'               => [ 'gmail', 'client_id' ],
			'WPMS_GMAIL_CLIENT_SECRET'           => [ 'gmail', 'client_secret' ],
			'WPMS_MAILGUN_API_KEY'               => [ 'mailgun', 'api_key' ],
			'WPMS_MAILGUN_DOMAIN'                => [ 'mailgun', 'domain' ],
			'WPMS_MAILGUN_REGION'                => [ 'mailgun', 'region' ],
			'WPMS_OUTLOOK_CLIENT_ID'             => [ 'outlook', 'client_id' ],
			'WPMS_OUTLOOK_CLIENT_SECRET'         => [ 'outlook', 'client_secret' ],
			'WPMS_POSTMARK_SERVER_API_TOKEN'     => [ 'postmark', 'server_api_token' ],
			'WPMS_POSTMARK_MESSAGE_STREAM'       => [ 'postmark', 'message_stream' ],
			'WPMS_SENDGRID_API_KEY'              => [ 'sendgrid', 'api_key' ],
			'WPMS_SENDGRID_DOMAIN'               => [ 'sendgrid', 'domain' ],
			'WPMS_SPARKPOST_API_KEY'             => [ 'sparkpost', 'api_key' ],
			'WPMS_SPARKPOST_REGION'              => [ 'sparkpost', 'region' ],
			'WPMS_ZOHO_DOMAIN'                   => [ 'zoho', 'domain' ],
			'WPMS_ZOHO_CLIENT_ID'                => [ 'zoho', 'client_id' ],
			'WPMS_ZOHO_CLIENT_SECRET'            => [ 'zoho', 'client_secret' ],
			'WPMS_RESEND_API_KEY'                => [ 'resend', 'api_key' ],
			'WPMS_SENDLAYER_API_KEY'             => [ 'sendlayer', 'api_key' ],
			'WPMS_MAILJET_API_KEY'               => [ 'mailjet', 'api_key' ],
			'WPMS_MAILJET_SECRET_KEY'            => [ 'mailjet', 'secret_key' ],
			'WPMS_MANDRILL_API_KEY'              => [ 'mandrill', 'api_key' ],
			'WPMS_MAILERSEND_API_KEY'            => [ 'mailersend', 'api_key' ],
			'WPMS_MAILERSEND_HAS_PRO_PLAN'       => [ 'mailersend', 'has_pro_plan' ],
			'WPMS_SMTP2GO_API_KEY'               => [ 'smtp2go', 'api_key' ],
			'WPMS_ELASTICEMAIL_API_KEY'          => [ 'elasticemail', 'api_key' ],
			'WPMS_SMTP_HOST'                     => [ 'smtp', 'host' ],
			'WPMS_SMTP_PORT'                     => [ 'smtp', 'port' ],
			'WPMS_SSL'                           => [ 'smtp', 'encryption' ],
			'WPMS_SMTP_AUTH'                     => [ 'smtp', 'auth' ],
			'WPMS_SMTP_AUTOTLS'                  => [ 'smtp', 'autotls' ],
			'WPMS_SMTP_USER'                     => [ 'smtp', 'user' ],
			'WPMS_SMTP_PASS'                     => [ 'smtp', 'pass' ],
			'WPMS_LOGS_ENABLED'                  => [ 'logs', 'enabled' ],
			'WPMS_SUMMARY_REPORT_EMAIL_DISABLED' => [ 'general', SummaryReportEmail::SETTINGS_SLUG ],
		];

		$defined = [];

		foreach ( $constants as $constant => $group_and_key ) {
			if ( $options->is_const_defined( $group_and_key[0], $group_and_key[1] ) ) {
				$defined[] = $constant;
			}
		}

		return $defined;
	}

	/**
	 * Get the WPForms version type if it's installed.
	 *
	 * @since 4.10.0
	 *
	 * @return false|string Return `false` if WPForms is not installed, otherwise return either `lite` or `pro`.
	 */
	private function get_wpforms_version_type() {

		if ( ! function_exists( 'wpforms' ) ) {
			return false;
		}

		if ( method_exists( wpforms(), 'is_pro' ) ) {
			$is_wpforms_pro = wpforms()->is_pro();
		} else {
			$is_wpforms_pro = wpforms()->pro;
		}

		return $is_wpforms_pro ? 'pro' : 'lite';
	}
}
