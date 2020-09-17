<?php

namespace WPMailSMTP\Providers\Gmail;

use WPMailSMTP\Admin\Area;
use WPMailSMTP\Debug;
use WPMailSMTP\Options as PluginOptions;
use WPMailSMTP\Providers\AuthAbstract;
use WPMailSMTP\Vendor\Google_Client;
use WPMailSMTP\Vendor\Google_Service_Gmail;

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

		$options           = new PluginOptions();
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
	 * @return Google_Client
	 */
	public function get_client() {

		// Doesn't load client twice + gives ability to overwrite.
		if ( ! empty( $this->client ) ) {
			return $this->client;
		}

		$this->include_vendor_lib();

		$client = new Google_Client(
			array(
				'client_id'     => $this->options['client_id'],
				'client_secret' => $this->options['client_secret'],
				'redirect_uris' => array(
					self::get_plugin_auth_url(),
				),
			)
		);
		$client->setApplicationName( 'WP Mail SMTP v' . WPMS_PLUGIN_VER );
		$client->setAccessType( 'offline' );
		$client->setApprovalPrompt( 'force' );
		$client->setIncludeGrantedScopes( true );
		// We request only the sending capability, as it's what we only need to do.
		$client->setScopes( array( Google_Service_Gmail::MAIL_GOOGLE_COM ) );
		$client->setRedirectUri( self::get_plugin_auth_url() );

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
				Debug::set(
					'Mailer: Gmail' . "\r\n" .
					$creds['error']
				);
			}

			// Bail if we have an error.
			if ( ! empty( $creds['error'] ) ) {
				return $client;
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
	public function process() {

		if ( ! ( isset( $_GET['tab'] ) && $_GET['tab'] === 'auth' ) ) {
			wp_safe_redirect( wp_mail_smtp()->get_admin()->get_admin_page_url() );
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
					wp_mail_smtp()->get_admin()->get_admin_page_url()
				)
			);
			exit;
		}

		$this->include_vendor_lib();

		$code  = '';
		$scope = '';
		$error = '';

		if ( isset( $_GET['error'] ) ) {
			$error = sanitize_key( $_GET['error'] );
		}

		// In case of any error: display a message to a user.
		if ( ! empty( $error ) ) {
			wp_safe_redirect(
				add_query_arg(
					'error',
					'google_' . $error,
					wp_mail_smtp()->get_admin()->get_admin_page_url()
				)
			);
			exit;
		}

		if ( isset( $_GET['code'] ) ) {
			$code = $_GET['code'];
		}
		if ( isset( $_GET['scope'] ) ) {
			$scope = urldecode( $_GET['scope'] );
		}

		// Let's try to get the access token.
		if (
			! empty( $code ) &&
			(
				$scope === Google_Service_Gmail::MAIL_GOOGLE_COM . ' ' . Google_Service_Gmail::GMAIL_SEND ||
				$scope === Google_Service_Gmail::GMAIL_SEND . ' ' . Google_Service_Gmail::MAIL_GOOGLE_COM ||
				$scope === Google_Service_Gmail::GMAIL_SEND ||
				$scope === Google_Service_Gmail::MAIL_GOOGLE_COM
			)
		) {
			// Save the auth code. So Google_Client can reuse it to retrieve the access token.
			$this->update_auth_code( $code );
		} else {
			wp_safe_redirect(
				add_query_arg(
					'error',
					'google_no_code_scope',
					wp_mail_smtp()->get_admin()->get_admin_page_url()
				)
			);
			exit;
		}

		wp_safe_redirect(
			add_query_arg(
				'success',
				'google_site_linked',
				wp_mail_smtp()->get_admin()->get_admin_page_url()
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

		$gmail = new Google_Service_Gmail( $this->get_client() );

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

		$gmail = new Google_Service_Gmail( $this->get_client() );

		try {
			$response = $gmail->users_settings_sendAs->listUsersSettingsSendAs( 'me' ); // phpcs:ignore

			// phpcs:disable
			$this->aliases = array_map(
				function( $sendAsObject ) {
					return $sendAsObject['sendAsEmail'];
				},
				(array) $response->getSendAs()
			);
			// phpcs:enable

		} catch ( \Exception $exception ) {
			$this->aliases = [];
		}

		return $this->aliases;
	}
}
