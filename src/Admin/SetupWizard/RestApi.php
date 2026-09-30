<?php

namespace WPMailSMTP\Admin\SetupWizard;

use Throwable;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WPMailSMTP\Admin\Area;
use WPMailSMTP\PartnerPlugins\Catalog;
use WPMailSMTP\PartnerPlugins\Installer;
use WP_Error;
use WPMailSMTP\Admin\DomainChecker;
use WPMailSMTP\Admin\EmailSendingErrors\EmailSendingErrors;
use WPMailSMTP\Connect;
use WPMailSMTP\EmailSendingDebug;
use WPMailSMTP\Options;
use WPMailSMTP\Providers\Preflight\Code as PreflightCode;
use WPMailSMTP\Providers\Preflight\Finding as PreflightFinding;
use WPMailSMTP\Providers\Preflight\PreflightInterface;
use WPMailSMTP\Providers\Preflight\Throttle as PreflightThrottle;
use WPMailSMTP\Providers\Sendlayer\QuickConnect;
use WPMailSMTP\SettingsImport\SettingsImport;
use WPMailSMTP\TestEmail\TestEmail;
use WPMailSMTP\UsageTracking\UsageTracking;
use WPMailSMTP\WP;

/**
 * Class Rest API.
 *
 * @since 4.10.0
 */
class RestApi {

	use RestApiResponse;

	/**
	 * Setup wizard instance.
	 *
	 * @since 4.10.0
	 *
	 * @var Hosted
	 */
	private $setup_wizard;

	/**
	 * Settings gateway.
	 *
	 * @since 4.10.0
	 *
	 * @var Settings
	 */
	private $settings;

	/**
	 * URL prefix.
	 *
	 * @since 4.10.0
	 *
	 * @var string
	 */
	const URL_PREFIX = 'wp-mail-smtp/setup-wizard/v1';

	/**
	 * Constructor.
	 *
	 * @since 4.10.0
	 *
	 * @param Hosted $setup_wizard Hosted setup wizard instance.
	 */
	public function __construct( Hosted $setup_wizard ) {

		$this->setup_wizard = $setup_wizard;
		$this->settings     = new Settings();
	}

	/**
	 * Get the API URL.
	 *
	 * @since 4.10.0
	 *
	 * @return string
	 */
	public function get_url() {

		return get_rest_url( null, self::URL_PREFIX );
	}

	/**
	 * Register routes.
	 *
	 * @since 4.10.0
	 *
	 * @return void
	 */
	public function register_routes() {

		$routes = [
			'/health'                     => [
				WP_REST_Server::CREATABLE,
				[ $this, 'health' ],
			],
			'/hydrate'                    => [
				WP_REST_Server::CREATABLE,
				[ $this, 'hydrate' ],
			],
			'/update'                     => [
				WP_REST_Server::CREATABLE,
				[ $this, 'update' ],
			],
			'/settings'                   => [
				WP_REST_Server::CREATABLE,
				[ $this, 'get_plugin_settings' ],
			],
			'/import-settings'            => [
				WP_REST_Server::CREATABLE,
				[ $this, 'import_settings' ],
			],
			'/get-connected-data'         => [
				WP_REST_Server::CREATABLE,
				[ $this, 'get_connected_data' ],
			],
			'/get-oauth-url'              => [
				WP_REST_Server::CREATABLE,
				[ $this, 'get_oauth_url' ],
			],
			'/remove-oauth-connection'    => [
				WP_REST_Server::CREATABLE,
				[ $this, 'remove_oauth_connection' ],
			],
			'/sendlayer-disconnect'       => [
				WP_REST_Server::CREATABLE,
				[ $this, 'sendlayer_disconnect' ],
			],
			'/check-mailer-configuration' => [
				WP_REST_Server::CREATABLE,
				[ $this, 'check_mailer_configuration' ],
			],
			'/mailer-preflight'           => [
				WP_REST_Server::CREATABLE,
				[ $this, 'mailer_preflight' ],
			],
			'/install-plugin'             => [
				WP_REST_Server::CREATABLE,
				[ $this, 'install_plugin' ],
			],
			'/upgrade-plugin'             => [
				WP_REST_Server::CREATABLE,
				[ $this, 'upgrade_plugin' ],
			],
			'/get-partner-plugins-info'   => [
				WP_REST_Server::CREATABLE,
				[ $this, 'get_partner_plugins_info' ],
			],
		];

		/**
		 * Filter the setup wizard REST routes.
		 *
		 * Lets Pro handlers register under the wizard's signed-request gate and its
		 * shared response helpers.
		 *
		 * @since 4.10.0
		 *
		 * @param array   $routes   Route map: path => [ method, callback ].
		 * @param RestApi $rest_api The wizard REST API instance.
		 */
		$routes = apply_filters( 'wp_mail_smtp_admin_setup_wizard_rest_api_routes', $routes, $this );

		foreach ( $routes as $route => $route_params ) {
			[ $method, $callback ] = $route_params;

			register_rest_route(
				self::URL_PREFIX,
				$route,
				[
					'methods'             => $method,
					'callback'            => $callback,
					'permission_callback' => [ $this, 'validate_request' ],
				]
			);
		}
	}

	/**
	 * Validate request.
	 *
	 * @since 4.10.0
	 *
	 * @param WP_REST_Request $request Current request.
	 *
	 * @return bool|WP_Error
	 */
	public function validate_request( $request ) {

		$timestamp = $request->get_header( 'X-Timestamp' );
		$nonce     = $request->get_header( 'X-Nonce' );
		$signature = $request->get_header( 'X-Signature' );
		$auth      = $this->setup_wizard->get_auth();

		$endpoint = $this->get_signed_endpoint( $request->get_route() );
		$payload  = $request->get_method() . "\n" . $endpoint . "\n" . $timestamp . "\n" . $nonce;

		$user_id = $auth->verify_signature( $payload, $timestamp, $nonce, $signature );

		if ( is_wp_error( $user_id ) ) {
			// The code distinguishes clock skew from a failed signature; the message
			// stays generic.
			return $this->error( __( 'Session expired.', 'wp-mail-smtp' ), 401, $user_id->get_error_code() );
		}

		// Hydrate the request as the bound user so capability checks work even though
		// the cross-origin wizard request carries no auth cookies.
		wp_set_current_user( $user_id );

		// Re-checked to catch a user whose role changed after the token was issued.
		if ( ! current_user_can( wp_mail_smtp()->get_capability_manage_global_options() ) ) {
			return $this->error( __( 'Insufficient permissions.', 'wp-mail-smtp' ), 403 );
		}

		return true;
	}

	/**
	 * The endpoint name the caller signed, taken from the route it requested.
	 *
	 * That is the route minus this API's registered prefix, which is the string the
	 * hosted wizard appends to the REST URL. The whole route is used rather than its
	 * basename, because a nested route can share a basename with a top-level one.
	 *
	 * get_route() returns the registered path for both /wp-json/… and ?rest_route=…
	 * requests, so permalink structure does not enter into it.
	 *
	 * @since 4.10.0
	 *
	 * @param string $route Requested route.
	 *
	 * @return string
	 */
	private function get_signed_endpoint( $route ) {

		return trim( substr( $route, strlen( '/' . self::URL_PREFIX ) ), '/' );
	}

	/**
	 * REST endpoint answering whether a signing key reaches this site.
	 *
	 * The hosted wizard opens a session by calling this with the key the site handed
	 * it, which is what binds that key to this site's REST URL: a caller who invents
	 * a pairing cannot make the site sign for it.
	 *
	 * It answers the authentication question and nothing else, so a site too slow to
	 * assemble bootstrap data can still hand off.
	 *
	 * @since 4.10.0
	 *
	 * @return WP_REST_Response
	 */
	public function health() {

		return $this->response(
			[
				'ok'      => true,
				// Lets the hosted wizard measure this site's clock offset, which every
				// signature window depends on.
				'time'    => time(),
				'version' => WPMS_PLUGIN_VER,
				'is_pro'  => wp_mail_smtp()->is_pro(),
			]
		);
	}

	/**
	 * Hydrate setup wizard data.
	 *
	 * @since 4.10.0
	 *
	 * @return WP_REST_Response
	 */
	public function hydrate() {

		return $this->response( $this->setup_wizard->get_bootstrap_data() );
	}

	/**
	 * Update plugin options.
	 *
	 * @since 4.10.0
	 *
	 * @param WP_REST_Request $request Current request.
	 *
	 * @return WP_REST_Response
	 */
	public function update( $request ) {

		$value = $request->get_param( 'value' );

		$this->settings->update( is_array( $value ) ? $value : [] );

		return $this->response();
	}

	/**
	 * REST endpoint for retrieving the plugin settings.
	 *
	 * @since 4.10.0
	 */
	public function get_plugin_settings() {

		return $this->response( $this->settings->get_payload() );
	}

	/**
	 * REST endpoint for importing settings from other SMTP plugins.
	 *
	 * @since 4.10.0
	 *
	 * @param WP_REST_Request $request Current request.
	 */
	public function import_settings( $request ) {

		$other_plugin = ! empty( $request->get_param( 'value' ) ) ? sanitize_text_field( $request->get_param( 'value' ) ) : '';

		if ( empty( $other_plugin ) ) {
			return $this->error( esc_html__( 'Please select a plugin to import settings from.', 'wp-mail-smtp' ) );
		}

		if ( ! ( new SettingsImport() )->import_from_plugin( $other_plugin ) ) {
			return $this->error( esc_html__( 'Could not find any settings to import from the selected plugin.', 'wp-mail-smtp' ) );
		}

		return $this->response();
	}

	/**
	 * REST endpoint for getting the oAuth connected data.
	 *
	 * @since 4.10.0
	 *
	 * @param WP_REST_Request $request Current request.
	 */
	public function get_connected_data( $request ) { // phpcs:ignore Generic.Metrics.NestingLevel.MaxExceeded

		$data   = [];
		$mailer = ! empty( $request->get_param( 'mailer' ) ) ? sanitize_text_field( $request->get_param( 'mailer' ) ) : '';

		if ( empty( $mailer ) ) {
			return $this->error( esc_html__( 'Please provide a mailer to get the connected account for.', 'wp-mail-smtp' ) );
		}

		switch ( $mailer ) {
			case 'gmail':
				$auth = wp_mail_smtp()->get_providers()->get_auth( 'gmail' );

				if ( $auth->is_clients_saved() && ! $auth->is_auth_required() ) {
					$user_info               = $auth->get_user_info();
					$data['connected_email'] = $user_info['email'];
				}
				break;
		}

		/**
		 * Filter the setup wizard connected data for a mailer.
		 *
		 * Lets mailers that need extra work to resolve the connected account
		 * (e.g. oAuth providers) add their connected email to the response.
		 *
		 * @since 4.10.0
		 *
		 * @param array  $data   Connected data (e.g. `connected_email`).
		 * @param string $mailer Mailer slug.
		 */
		$data = apply_filters( 'wp_mail_smtp_admin_setup_wizard_rest_api_connected_data', $data, $mailer );

		return $this->response( array_merge( [ 'mailer' => $mailer ], $data ) );
	}

	/**
	 * REST endpoint for getting the oAuth authorization URL.
	 *
	 * @since 4.10.0
	 *
	 * @param WP_REST_Request $request Current request.
	 */
	public function get_oauth_url( $request ) {

		$data   = [];
		$mailer = ! empty( $request->get_param( 'mailer' ) ) ? sanitize_text_field( $request->get_param( 'mailer' ) ) : '';

		// Stored slashed, matching what Options::get() strips back off on read.
		$settings = wp_slash( (array) $request->get_param( 'settings' ) );

		if ( empty( $mailer ) ) {
			return $this->error( esc_html__( 'Please provide a mailer to get the OAuth authorization URL for.', 'wp-mail-smtp' ) );
		}

		// Which wizard to return to; the provider's auth callback reads it back.
		$settings = array_merge( $settings, [ 'is_setup_wizard_auth' => Launcher::VARIANT_HOSTED ] );

		$options = Options::init();
		$options->set( [ $mailer => $settings ], false, false );

		// A bounce URL, not the OAuth URL itself: the provider's state nonce has to be
		// minted in the bridge's cookie-authenticated request.
		$data['oauth_url'] = RedirectBridge::get_url( RedirectBridge::ACTION_OAUTH_CONNECT, [ 'mailer' => $mailer ] );

		return $this->response( array_merge( [ 'mailer' => $mailer ], $data ) );
	}

	/**
	 * REST endpoint for removing the oAuth authorization connection.
	 *
	 * @since 4.10.0
	 *
	 * @param WP_REST_Request $request Current request.
	 */
	public function remove_oauth_connection( $request ) {

		$mailer = ! empty( $request->get_param( 'mailer' ) ) ? sanitize_text_field( $request->get_param( 'mailer' ) ) : '';

		if ( empty( $mailer ) ) {
			return $this->error( esc_html__( 'Please provide a mailer to remove the OAuth connection for.', 'wp-mail-smtp' ) );
		}

		$options = Options::init();
		$old_opt = $options->get_all_raw();

		// Gmail shares its settings array between the custom app and One-Click Setup, so clear only the tokens.
		if ( $mailer === 'gmail' ) {
			unset( $old_opt[ $mailer ]['access_token'] );
			unset( $old_opt[ $mailer ]['refresh_token'] );
			unset( $old_opt[ $mailer ]['user_details'] );
			unset( $old_opt[ $mailer ]['auth_code'] );
		} else {
			foreach ( $old_opt[ $mailer ] as $key => $value ) {
				// Unset everything except Client ID, Client Secret and Domain (for Zoho).
				if ( ! in_array( $key, [ 'domain', 'client_id', 'client_secret' ], true ) ) {
					unset( $old_opt[ $mailer ][ $key ] );
				}
			}
		}

		$options->set( $old_opt );

		return $this->response();
	}

	/**
	 * REST endpoint for disconnecting SendLayer Quick Connect.
	 *
	 * @since 4.10.0
	 */
	public function sendlayer_disconnect() {

		( new QuickConnect() )->disconnect();

		return $this->response();
	}

	/**
	 * REST endpoint for checking the mailer configuration.
	 * - Send a test email
	 * - Check the domain setup with the Domain Checker API.
	 *
	 * Always answers 200: the client reads an error status as a dead session and
	 * navigates away from the step.
	 *
	 * @since 4.10.0
	 */
	public function check_mailer_configuration() {

		$options    = Options::init();
		$mailer     = $options->get( 'mail', 'mailer' );
		$from_email = $options->get( 'mail', 'from_email' );
		$domain     = '';

		$started = time();

		// Send the test mail. Domain check runs below with its own (warnings-tolerated)
		// threshold, so we opt out of TestEmail's stricter no_issues() check.
		$test_email = ( new TestEmail() )
			->with_context( TestEmail::CONTEXT_SETUP_WIZARD )
			->as_html( false )
			->with_domain_check( false );

		$test_email->send( $this->get_test_email_recipient() );

		$sent = $test_email->is_successful();

		// Completion tracks whether the test email went out. Deliverability warnings do
		// not un-complete the wizard.
		Stats::update_completed( $sent, $mailer );

		if ( ! $sent ) {
			( new UsageTracking() )->send_failed_setup_wizard_usage_tracking_data();

			return $this->response(
				[
					'sent'         => false,
					'mailer'       => $mailer,
					'failure'      => $this->get_send_failure( $started ),
					'preflight'    => $this->get_failed_send_preflight( $mailer, $options ),
					'domain_check' => null,
				]
			);
		}

		// Add the optional sending domain parameter.
		if ( in_array( $mailer, [ 'mailgun', 'sendinblue', 'sendgrid' ], true ) ) {
			$domain = $options->get( $mailer, 'domain' );
		}

		// Perform the domain checker API test.
		$domain_checker = new DomainChecker( $mailer, $from_email, $domain );

		if ( $domain_checker->has_errors() ) {
			( new UsageTracking() )->send_failed_setup_wizard_usage_tracking_data( $domain_checker );
		}

		return $this->response(
			[
				'sent'         => true,
				'mailer'       => $mailer,
				'failure'      => null,
				'domain_check' => $domain_checker->is_supported_mailer() ? $this->get_domain_check( $domain_checker ) : null,
			]
		);
	}

	/**
	 * Reduce the failure record left by the send that just ran to the wire shape.
	 *
	 * @since 4.10.0
	 *
	 * @param int $started Unix timestamp taken before the send.
	 *
	 * @return array
	 */
	private function get_send_failure( $started ) {

		// The wizard configures the primary connection and sends the test on it.
		$record = EmailSendingDebug::get( 'primary' );

		$failure = [
			'error_key'      => '',
			'error_code'     => '',
			'error_message'  => '',
			'error_log_html' => '',
			'steps'          => [],
		];

		// A record predating this send describes an earlier failure, not this one.
		if ( empty( $record['occurred_at'] ) || (int) $record['occurred_at'] < $started ) {
			return $failure;
		}

		foreach ( [ 'error_key', 'error_code', 'error_message' ] as $field ) {
			if ( isset( $record[ $field ] ) ) {
				$failure[ $field ] = (string) $record[ $field ];
			}
		}

		$errors = new EmailSendingErrors();
		$info   = $errors->get_local_failure_info( $record );

		// `steps` is on every registry entry and on the generic fallback bundle.
		$failure['error_log_html'] = $errors->build_error_log( $record );
		$failure['steps']          = array_values( (array) $info['steps'] );

		return $failure;
	}

	/**
	 * Reduce the Domain Checker API results, a json_decode() of a remote body, to the
	 * three keys the client indexes into.
	 *
	 * @since 4.10.0
	 *
	 * @param DomainChecker $domain_checker Domain checker that has run.
	 *
	 * @return array
	 */
	private function get_domain_check( $domain_checker ) {

		$results = (array) $domain_checker->get_results();

		return [
			'success' => ! empty( $results['success'] ),
			'message' => isset( $results['message'] ) ? (string) $results['message'] : '',
			'checks'  => ! empty( $results['checks'] ) ? array_values( (array) $results['checks'] ) : [],
		];
	}

	/**
	 * Get the test email recipient.
	 *
	 * @since 4.10.0
	 *
	 * @return string
	 */
	private function get_test_email_recipient() {

		$options    = Options::init();
		$mailer     = $options->get( 'mail', 'mailer' );
		$from_email = $options->get( 'mail', 'from_email' );

		/*
		 * Some mailers in a test mode allows to send emails only to the registered
		 * From email address, so we need to cover this case.
		 */
		$to_email = $from_email;

		$mailer_specific_constant_name = 'WPMS_SETUP_WIZARD_TEST_' . strtoupper( $mailer ) . '_EMAIL_RECIPIENT';

		if (
			defined( $mailer_specific_constant_name ) &&
			is_email( constant( $mailer_specific_constant_name ) )
		) {
			$to_email = constant( $mailer_specific_constant_name );
		} elseif (
			defined( 'WPMS_SETUP_WIZARD_TEST_EMAIL_RECIPIENT' ) &&
			is_email( \WPMS_SETUP_WIZARD_TEST_EMAIL_RECIPIENT )
		) {
			$to_email = \WPMS_SETUP_WIZARD_TEST_EMAIL_RECIPIENT;
		}

		return $to_email;
	}

	/**
	 * REST endpoint establishing whether a submitted mailer configuration could send.
	 *
	 * Persists nothing: the configuration arrives in the body and is used for the probe only. Every
	 * outcome answers 200 and describes itself in `findings`, because the wizard client treats an
	 * error status as a dead session and navigates away from the form.
	 *
	 * @since 4.10.0
	 *
	 * @param WP_REST_Request $request Current request.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function mailer_preflight( $request ) {

		$mailer = sanitize_key( (string) $request->get_param( 'mailer' ) );

		if ( wp_mail_smtp()->get_providers()->get_provider_path( $mailer ) === null ) {
			return $this->error( esc_html__( 'Unknown mailer.', 'wp-mail-smtp' ), 400, 'unknown_mailer' );
		}

		$preflight = wp_mail_smtp()->get_providers()->get_preflight( $mailer );

		// A mailer with nothing to probe makes no call, so it spends no budget and reports
		// that it was not checked rather than that it passed.
		if ( ! $preflight instanceof PreflightInterface ) {
			return $this->response(
				[
					'findings' => [],
					'checked'  => false,
				]
			);
		}

		// One bucket per user, not per user and mailer: switching mailers would otherwise
		// multiply the budget by the number of mailers.
		$throttle = new PreflightThrottle();
		$key      = (string) get_current_user_id();

		if ( ! $throttle->allows( $key ) ) {
			return $this->error(
				esc_html__( 'Too many attempts. Wait a moment and try again.', 'wp-mail-smtp' ),
				429,
				'preflight_throttled'
			);
		}

		$throttle->record( $key );

		return $this->response(
			[
				'findings' => $this->run_preflight( $preflight, $this->get_preflight_config( $mailer, $request ) ),
				'checked'  => true,
			]
		);
	}

	/**
	 * Probe the saved configuration behind a send that just failed.
	 *
	 * @since 4.10.0
	 *
	 * @param string  $mailer  Mailer slug.
	 * @param Options $options Plugin options.
	 *
	 * @return array|null Null when the mailer has nothing to probe.
	 */
	private function get_failed_send_preflight( $mailer, $options ) {

		$preflight = wp_mail_smtp()->get_providers()->get_preflight( $mailer );

		if ( ! $preflight instanceof PreflightInterface ) {
			return null;
		}

		// Outside the preflight route's throttle: the send already reached the provider.
		return [
			'findings' => $this->run_preflight( $preflight, (array) $options->get_group( $mailer ) ),
			'checked'  => true,
		];
	}

	/**
	 * Run one mailer's probe, reducing a provider bug to an inconclusive finding.
	 *
	 * @since 4.10.0
	 *
	 * @param PreflightInterface $preflight The mailer's probe.
	 * @param array              $config    The mailer's own settings group.
	 *
	 * @return array Findings in wire shape.
	 */
	private function run_preflight( $preflight, $config ) {

		try {
			return $preflight->run( $config )->to_array();
		} catch ( Throwable $exception ) {
			// A provider bug must not cost the user their place in the wizard.
			return [ ( new PreflightFinding( PreflightCode::UNEXPECTED_RESPONSE, null, PreflightFinding::INCONCLUSIVE ) )->to_array() ];
		}
	}

	/**
	 * Reduce a submitted configuration to the fields one mailer's probe may receive.
	 *
	 * @since 4.10.0
	 *
	 * @param string          $mailer  Mailer slug.
	 * @param WP_REST_Request $request Current request.
	 *
	 * @return array
	 */
	private function get_preflight_config( $mailer, $request ) {

		$submitted = (array) $request->get_param( 'config' );

		// The wizard's own save allowlist, reused so a probe can never be handed a path the
		// wizard is not allowed to write either.
		$allowed = $this->settings->filter_allowed(
			[
				$mailer => $submitted,
			]
		);

		return isset( $allowed[ $mailer ] ) ? $allowed[ $mailer ] : [];
	}

	/**
	 * Catalog slug to the name the wizard shows, in display order. Only these
	 * can be installed from the wizard.
	 *
	 * @since 4.10.0
	 *
	 * @return array
	 */
	private function get_offered_partners() {

		return [
			'wpforms'                       => esc_html__( 'Contact Forms by WPForms', 'wp-mail-smtp' ),
			'aioseo'                        => esc_html__( 'All in One SEO', 'wp-mail-smtp' ),
			'monsterinsights'               => esc_html__( 'Google Analytics by MonsterInsights', 'wp-mail-smtp' ),
			'wpcode'                        => esc_html__( 'Code Snippets by WPCode', 'wp-mail-smtp' ),
			'rafflepress'                   => esc_html__( 'Giveaways by RafflePress', 'wp-mail-smtp' ),
			'smash-balloon-instagram-feeds' => esc_html__( 'Smash Balloon Social Photo Feed', 'wp-mail-smtp' ),
			'seedprod'                      => esc_html__( 'SeedProd Landing Page Builder', 'wp-mail-smtp' ),
			'wp-call-button'                => esc_html__( 'WP Call Button', 'wp-mail-smtp' ),
		];
	}

	/**
	 * The partner plugins the wizard offers, with their install state.
	 *
	 * @since 2.6.0
	 *
	 * @return array
	 */
	private function get_partner_plugins() {

		$catalog = new Catalog();
		$plugins = [];

		foreach ( $this->get_offered_partners() as $slug => $name ) {
			$plugin = $catalog->get( $slug );

			if ( $plugin === null ) {
				continue;
			}

			$plugins[] = [
				'slug'         => $plugin->get_wporg_slug(),
				'name'         => $name,
				'is_activated' => $plugin->is_loaded(),
				'is_installed' => $plugin->is_installed(),
			];
		}

		return $plugins;
	}

	/**
	 * Install and activate a partner plugin the wizard offers, by its
	 * WordPress.org slug.
	 *
	 * @since 4.10.0
	 *
	 * @param string $slug WordPress.org directory slug.
	 *
	 * @return array|WP_Error The wizard's install result on success.
	 */
	private function install_partner_plugin( $slug ) {

		$catalog = new Catalog();
		$plugin  = $catalog->get_by_wporg_slug( $slug );

		if ( $plugin === null || ! array_key_exists( $plugin->get_slug(), $this->get_offered_partners() ) ) {
			return new WP_Error(
				'wp_mail_smtp_partner_not_offered',
				esc_html__( 'Could not install the plugin. Plugin is not whitelisted.', 'wp-mail-smtp' )
			);
		}

		$redirect_url = esc_url_raw( WP::admin_url( 'admin.php?page=' . Area::SLUG . '-setup-wizard' ) );

		$result = ( new Installer() )->install( $plugin, $redirect_url );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		if ( $plugin->get_slug() === 'wpforms' ) {
			add_option( 'wpforms_installation_source', 'wp-mail-smtp-setup-wizard' );
		}

		return [
			'slug'         => $slug,
			'is_installed' => true,
			'is_activated' => $result['activated'],
		];
	}

	/**
	 * REST endpoint for installing a partner plugin.
	 *
	 * @since 4.6.0
	 *
	 * @param WP_REST_Request $request Current request.
	 *
	 * @return WP_REST_Response
	 */
	public function install_plugin( $request ) {

		// Beyond the route gate, which only covers the plugin's options capability.
		if ( ! current_user_can( 'install_plugins' ) ) {
			return $this->error( esc_html__( 'Could not install the plugin. You don\'t have permission to install plugins.', 'wp-mail-smtp' ) );
		}

		if ( ! current_user_can( 'activate_plugins' ) ) {
			return $this->error( esc_html__( 'Could not install the plugin. You don\'t have permission to activate plugins.', 'wp-mail-smtp' ) );
		}

		$slug = ! empty( $request->get_param( 'slug' ) ) ? sanitize_text_field( $request->get_param( 'slug' ) ) : '';

		if ( empty( $slug ) ) {
			return $this->error( esc_html__( 'Could not install the plugin. Plugin slug is missing.', 'wp-mail-smtp' ) );
		}

		$result = $this->install_partner_plugin( $slug );

		if ( is_wp_error( $result ) ) {
			return $this->error( $result->get_error_message() );
		}

		return $this->response( $result );
	}

	/**
	 * REST endpoint for getting all partner's plugin information.
	 *
	 * @since 4.10.0
	 */
	public function get_partner_plugins_info() {

		$plugins = $this->get_partner_plugins();

		$contact_form_plugin_already_installed = false;

		$contact_form_basenames = [
			'wpforms-lite/wpforms.php',
			'wpforms/wpforms.php',
			'formidable/formidable.php',
			'formidable/formidable-pro.php',
			'gravityforms/gravityforms.php',
			'ninja-forms/ninja-forms.php',
		];

		$installed_plugins = get_plugins();

		foreach ( $installed_plugins as $basename => $plugin_info ) {
			if ( in_array( $basename, $contact_form_basenames, true ) ) {
				$contact_form_plugin_already_installed = true;

				break;
			}
		}

		// Final check if maybe WPForms is already install and active as a MU plugin.
		if ( class_exists( '\WPForms\WPForms' ) ) {
			$contact_form_plugin_already_installed = true;
		}

		$data = [
			'plugins'                               => $plugins,
			'contact_form_plugin_already_installed' => $contact_form_plugin_already_installed,
		];

		return $this->response( $data );
	}

	/**
	 * REST endpoint for plugin upgrade, from lite to pro.
	 *
	 * @since 4.10.0
	 *
	 * @param WP_REST_Request $request Current request.
	 */
	public function upgrade_plugin( $request ) {

		if ( wp_mail_smtp()->is_pro() ) {
			return $this->response(
				[
					'message' => esc_html__( 'You are already using the WP Mail SMTP PRO version. Please refresh this page and verify your license key.', 'wp-mail-smtp' ),
				]
			);
		}

		// The upgrade swaps the plugin out, so it needs the install capability on top
		// of the options capability the route gate checks.
		if ( ! current_user_can( 'install_plugins' ) ) {
			return $this->error( esc_html__( 'You don\'t have the permission to perform this action.', 'wp-mail-smtp' ) );
		}

		$license_key = ! empty( $request->get_param( 'license_key' ) ) ? sanitize_key( $request->get_param( 'license_key' ) ) : '';

		if ( empty( $license_key ) ) {
			return $this->error( esc_html__( 'Please enter a valid license key!', 'wp-mail-smtp' ) );
		}

		$url = Connect::generate_url(
			$license_key,
			'',
			Launcher::get_url( Launcher::VARIANT_HOSTED, [ 'upgrade-redirect' => '1' ], '/step/license' )
		);

		if ( empty( $url ) ) {
			return $this->error( esc_html__( 'Upgrade functionality not available!', 'wp-mail-smtp' ) );
		}

		return $this->response( [ 'redirect_url' => $url ] );
	}
}
