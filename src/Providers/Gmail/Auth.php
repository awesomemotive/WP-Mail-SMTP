<?php

namespace WPMailSMTP\Providers\Gmail;

use WPMailSMTP\Admin\Area;
use WPMailSMTP\Admin\DebugEvents\DebugEvents;
use WPMailSMTP\Debug;
use WPMailSMTP\Options as PluginOptions;
use WPMailSMTP\Providers\AuthAbstract;
use WPMailSMTP\Vendor\Google_Client;
use WPMailSMTP\Vendor\Google\Service\Gmail;

/**
 * Class Auth to request access and refresh tokens.
 *
 * @since 1.0.0
 */
class Auth extends AuthAbstract {

	/**
	 * List of all possible "from email" email addresses (aliases).
	 *
	 * @since 2.2.0
	 *
	 * @var null|array
	 */
	private $aliases = null;

	/**
	 * Auth constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		$options           = PluginOptions::init();
		$this->mailer_slug = $options->get( 'mail', 'mailer' );

		if ( $this->mailer_slug !== Options::SLUG ) {
			return;
		}

		$this->options = $options->get_group( $this->mailer_slug );

		if ( $this->is_clients_saved() ) {

			$this->include_vendor_lib();

			$this->client = $this->get_client();
		}
	}

	/**
	 * Get the url, that users will be redirected back to finish the OAuth process.
	 *
	 * @since 1.5.2 Returned to the old, pre-1.5, structure of the link to preserve BC.
	 *
	 * @return string
	 */
	public static function get_plugin_auth_url() {

		return apply_filters(
			'wp_mail_smtp_gmail_get_plugin_auth_url',
			add_query_arg(
				array(
					'page' => Area::SLUG,
					'tab'  => 'auth',
				),
				admin_url( 'options-general.php' )
			)
		);
	}

	/**
	 * Init and get the Google Client object.
	 *
	 * @since 1.0.0
	 * @since 1.5.0 Add ability to apply custom options to the client via a filter.
	 *
	 * @param bool $force If the client should be forcefully reinitialized.
	 *
	 * @return Google_Client
	 */
	public function get_client( $force = false ) { // phpcs:ignore Generic.Metrics.CyclomaticComplexity.MaxExceeded

		// Doesn't load client twice + gives ability to overwrite.
		if ( ! empty( $this->client ) && ! $force ) {
			return $this->client;
		}

		$this->include_vendor_lib();

		$client = new Google_Client(
			array(
				'client_id'     => $this->options['client_id'],
				'client_secret' => $this->options['client_secret'],
				'redirect_uris' => array(
					self::get_oauth_redirect_url(),
				),
			)
		);
		$client->setApplicationName( 'WP Mail SMTP v' . WPMS_PLUGIN_VER );
		$client->setAccessType( 'offline' );
		$client->setApprovalPrompt( 'force' );
		$client->setIncludeGrantedScopes( true );
		// We request only the sending capability, as it's what we only need to do.
		$client->setScopes( array( Gmail::MAIL_GOOGLE_COM ) );
		$client->setRedirectUri( self::get_oauth_redirect_url() );
		$client->setState( self::get_plugin_auth_url() );

		// Apply custom options to the client.
		$client = apply_filters( 'wp_mail_smtp_providers_gmail_auth_get_client_custom_options', $client );

		if (
			$this->is_auth_required() &&
			! empty( $this->options['auth_code'] )
		) {
			try {
				$creds = $client->fetchAccessTokenWithAuthCode( $this->options['auth_code'] );
			} catch ( \Exception $e ) {
				$creds['error'] = $e->getMessage();
			}

			// Bail if we have an error.
			if ( ! empty( $creds['error'] ) ) {
				if ( $creds['error'] === 'invalid_client' ) {
					$creds['error'] .= PHP_EOL . esc_html__( 'Please make sure your Google Client ID and Secret in the plugin settings are valid. Save the settings and try the Authorization again.' , 'wp-mail-smtp' );
				}

				Debug::set(
					'Mailer: Gmail' . "\r\n" .
					$creds['error']
				);

				return $client;
			} else {
				Debug::clear();
			}

			$this->update_access_token( $client->getAccessToken() );
			$this->update_refresh_token( $client->getRefreshToken() );
		}

		if ( ! empty( $this->options['access_token'] ) ) {
			$client->setAccessToken( $this->options['access_token'] );
		}

		// Refresh the token if it's expired.
		if ( $client->isAccessTokenExpired() ) {
			$refresh = $client->getRefreshToken();
			if ( empty( $refresh ) && isset( $this->options['refresh_token'] ) ) {
				$refresh = $this->options['refresh_token'];
			}

			if ( ! empty( $refresh ) ) {
				try {
					$creds = $client->fetchAccessTokenWithRefreshToken( $refresh );
				} catch ( \Exception $e ) {
					$creds['error'] = $e->getMessage();
					Debug::set(
						'Mailer: Gmail' . "\r\n" .
						$e->getMessage()
					);
				}

				// Bail if we have an error.
				if ( ! empty( $creds['error'] ) ) {
					return $client;
				}

				$this->update_access_token( $client->getAccessToken() );
				$this->update_refresh_token( $client->getRefreshToken() );
			}
		}

		return $client;
	}

	/**
	 * Get the auth code from the $_GET and save it.
	 * Redirect user back to settings with an error message, if failed.
	 *
	 * @since 1.0.0
	 */
	public function process() { // phpcs:ignore Generic.Metrics.CyclomaticComplexity.MaxExceeded

		$redirect_url         = wp_mail_smtp()->get_admin()->get_admin_page_url();
		$is_setup_wizard_auth = ! empty( $this->options['is_setup_wizard_auth'] );

		if ( $is_setup_wizard_auth ) {
			$this->update_is_setup_wizard_auth( false );

			$redirect_url = \WPMailSMTP\Admin\SetupWizard::get_site_url() . '#/step/configure_mailer/gmail';
		}

		if ( ! ( isset( $_GET['tab'] ) && $_GET['tab'] === 'auth' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			wp_safe_redirect( $redirect_url );
			exit;
		}

		// We can't process without saved client_id/secret.
		if ( ! $this->is_clients_saved() ) {
			Debug::set(
				esc_html__( 'There was an error while processing the Google authentication request. Please make sure that you have Client ID and Client Secret both valid and saved.', 'wp-mail-smtp' )
			);
			wp_safe_redirect(
				add_query_arg(
					'error',
					'google_no_clients',
					$redirect_url
				)
			);
			exit;
		}

		$this->include_vendor_lib();

		$code  = '';
		$scope = '';
		$error = '';

		if ( isset( $_GET['error'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$error = sanitize_key( $_GET['error'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}

		// In case of any error: display a message to a user.
		if ( ! empty( $error ) ) {
			DebugEvents::add_debug(
				sprintf( /* Translators: %s the error code passed from Google. */
					esc_html__( 'There was an error while processing Google authorization: %s' ),
					esc_html( $error )
				)
			);

			wp_safe_redirect(
				add_query_arg(
					'error',
					'google_' . $error,
					$redirect_url
				)
			);
			exit;
		}

		if ( isset( $_GET['code'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$code = urldecode( $_GET['code'] );
		}
		if ( isset( $_GET['scope'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$scope = urldecode( base64_decode( $_GET['scope'] ) );
		}

		// Let's try to get the access token.
		if (
			! empty( $code ) &&
			(
				$scope === Gmail::MAIL_GOOGLE_COM . ' ' . Gmail::GMAIL_SEND ||
				$scope === Gmail::GMAIL_SEND . ' ' . Gmail::MAIL_GOOGLE_COM ||
				$scope === Gmail::GMAIL_SEND ||
				$scope === Gmail::MAIL_GOOGLE_COM
			)
		) {
			// Save the auth code. So Google_Client can reuse it to retrieve the access token.
			$this->update_auth_code( $code );
		} else {
			DebugEvents::add_debug(
				esc_html__( 'There was an error while processing Google authorization: missing code or scope parameter.' )
			);

			wp_safe_redirect(
				add_query_arg(
					'error',
					'google_no_code_scope',
					$redirect_url
				)
			);
			exit;
		}

		if ( $is_setup_wizard_auth ) {
			Debug::clear();

			$this->get_client( true );

			$error = Debug::get_last();

			if ( ! empty( $error ) ) {
				wp_safe_redirect(
					add_query_arg(
						'error',
						'google_unsuccessful_oauth',
						$redirect_url
					)
				);
				exit;
			}
		}

		wp_safe_redirect(
			add_query_arg(
				'success',
				'google_site_linked',
				$redirect_url
			)
		);
		exit;
	}

	/**
	 * Get the auth URL used to proceed to Provider to request access to send emails.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_auth_url() {

		if (
			! empty( $this->client ) &&
			class_exists( 'WPMailSMTP\Vendor\Google_Client', false ) &&
			$this->client instanceof Google_Client
		) {
			return filter_var( $this->client->createAuthUrl(), FILTER_SANITIZE_URL );
		}

		return '#';
	}

	/**
	 * Get user information (like email etc) that is associated with the current connection.
	 *
	 * @since 1.5.0
	 *
	 * @return array
	 */
	public function get_user_info() {

		$gmail = new Gmail( $this->get_client() );

		try {
			$email = $gmail->users->getProfile( 'me' )->getEmailAddress();
		} catch ( \Exception $e ) {
			$email = '';
		}

		return array( 'email' => $email );
	}

	/**
	 * Get the registered email addresses that the user can use as the "from email".
	 *
	 * @since 2.2.0
	 *
	 * @return array The list of possible from email addresses.
	 */
	public function get_user_possible_send_from_addresses() {

		if ( isset( $this->aliases ) ) {
			return $this->aliases;
		}

		$gmail = new Gmail( $this->get_client() );

		try {

			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			$response = $gmail->users_settings_sendAs->listUsersSettingsSendAs( 'me' );

			// phpcs:disable
			$this->aliases = array_map(
				function( $sendAsObject ) {
					return $sendAsObject['sendAsEmail'];
				},
				(array) $response->getSendAs()
			);
			// phpcs:enable

		} catch ( \Exception $exception ) {
			DebugEvents::add_debug(
				sprintf( /* Translators: %s the error message. */
					esc_html__( 'An error occurred when trying to get Gmail aliases: %s' ),
					esc_html( $exception->getMessage() )
				)
			);

			$this->aliases = [];
		}

		return $this->aliases;
	}

	/**
	 * Get the Google oAuth 2.0 redirect URL.
	 *
	 * This is the URL that Google will redirect after the access to the Gmail account is granted or rejected.
	 * The below endpoint will then redirect back to the user's WP site (to self::get_plugin_auth_url() URL).
	 *
	 * @since 2.5.0
	 *
	 * @return string
	 */
	public static function get_oauth_redirect_url() {

		return 'https://connect.wpmailsmtp.com/google/';
	}
}
