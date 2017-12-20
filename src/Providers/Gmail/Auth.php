<?php

namespace WPMailSMTP\Providers\Gmail;

use WPMailSMTP\Debug;
use WPMailSMTP\Options as PluginOptions;
use WPMailSMTP\Providers\AuthAbstract;

/**
 * Class Auth to request access and refresh tokens.
 *
 * @since 1.0.0
 */
class Auth extends AuthAbstract {

	/**
	 * Gmail options.
	 *
	 * @var array
	 */
	private $gmail;

	/**
	 * @var \Google_Client
	 */
	private $client;

	/**
	 * @var string
	 */
	private $mailer;

	/**
	 * Auth constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		$options      = new PluginOptions();
		$this->mailer = $options->get( 'mail', 'mailer' );

		if ( $this->mailer !== 'gmail' ) {
			return;
		}

		$this->gmail = $options->get_group( $this->mailer );

		if ( $this->is_clients_saved() ) {

			$this->include_google_lib();

			$this->client = $this->get_client();
		}
	}

	/**
	 * Use the composer autoloader to include the Google Library and all its dependencies.
	 *
	 * @since 1.0.0
	 */
	protected function include_google_lib() {
		require wp_mail_smtp()->plugin_path . '/vendor/autoload.php';
	}

	/**
	 * Init and get the Google Client object.
	 *
	 * @since 1.0.0
	 */
	public function get_client() {

		// Doesn't load client twice + gives ability to overwrite.
		if ( ! empty( $this->client ) ) {
			return $this->client;
		}

		$client = new \Google_Client(
			array(
				'client_id'     => $this->gmail['client_id'],
				'client_secret' => $this->gmail['client_secret'],
				'redirect_uris' => array(
					Auth::get_plugin_auth_url(),
				),
			)
		);
		$client->setAccessType( 'offline' );
		$client->setApprovalPrompt( 'force' );
		$client->setIncludeGrantedScopes( true );
		// We request only the sending capability, as it's what we only need to do.
		$client->setScopes( array( \Google_Service_Gmail::GMAIL_SEND ) );
		$client->setRedirectUri( self::get_plugin_auth_url() );

		if (
			empty( $this->gmail['access_token'] ) &&
			! empty( $this->gmail['auth_code'] )
		) {
			try {
				$creds = $client->fetchAccessTokenWithAuthCode( $this->gmail['auth_code'] );
			} catch ( \Exception $e ) {
				$creds['error'] = $e->getMessage();
				Debug::set( $e->getMessage() );
			}

			// Bail if we have an error.
			if ( ! empty( $creds['error'] ) ) {
				// TODO: save this error to display to a user later.
				return $client;
			}

			$this->update_access_token( $client->getAccessToken() );
			$this->update_refresh_token( $client->getRefreshToken() );
		}

		if ( ! empty( $this->gmail['access_token'] ) ) {
			$client->setAccessToken( $this->gmail['access_token'] );
		}

		// Refresh the token if it's expired.
		if ( $client->isAccessTokenExpired() ) {
			$refresh = $client->getRefreshToken();
			if ( empty( $refresh ) && isset( $this->gmail['refresh_token'] ) ) {
				$refresh = $this->gmail['refresh_token'];
			}

			if ( ! empty( $refresh ) ) {
				try {
					$creds = $client->fetchAccessTokenWithRefreshToken( $refresh );
				} catch ( \Exception $e ) {
					$creds['error'] = $e->getMessage();
					Debug::set( $e->getMessage() );
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

		// We can't process without saved client_id/secret.
		if ( ! $this->is_clients_saved() ) {
			Debug::set( 'There was an error while processing the Google authentication request. Please make sure that you have Client ID and Client Secret both valid and saved.' );
			wp_redirect(
				add_query_arg(
					'error',
					'google_no_clients',
					wp_mail_smtp()->get_admin()->get_admin_page_url()
				)
			);
			exit;
		}

		$code  = '';
		$scope = '';
		$error = '';

		if ( isset( $_GET['error'] ) ) {
			$error = sanitize_key( $_GET['error'] );
		}

		// In case of any error: display a message to a user.
		if ( ! empty( $error ) ) {
			wp_redirect(
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
				$scope === ( \Google_Service_Gmail::GMAIL_SEND . ' ' . \Google_Service_Gmail::MAIL_GOOGLE_COM ) ||
				$scope === \Google_Service_Gmail::GMAIL_SEND
			)
		) {
			// Save the auth code. So \Google_Client can reuse it to retrieve the access token.
			$this->update_auth_code( $code );
		} else {
			wp_redirect(
				add_query_arg(
					'error',
					'google_no_code_scope',
					wp_mail_smtp()->get_admin()->get_admin_page_url()
				)
			);
			exit;
		}

		wp_redirect(
			add_query_arg(
				'success',
				'google_site_linked',
				wp_mail_smtp()->get_admin()->get_admin_page_url()
			)
		);
		exit;
	}

	/**
	 * Update access token in our DB.
	 *
	 * @since 1.0.0
	 *
	 * @param array $token
	 */
	protected function update_access_token( $token ) {

		$options = new PluginOptions();
		$all     = $options->get_all();

		$all[ $this->mailer ]['access_token'] = $token;
		$this->gmail['access_token']          = $token;

		$options->set( $all );
	}

	/**
	 * Update refresh token in our DB.
	 *
	 * @since 1.0.0
	 *
	 * @param array $token
	 */
	protected function update_refresh_token( $token ) {

		$options = new PluginOptions();
		$all     = $options->get_all();

		$all[ $this->mailer ]['refresh_token'] = $token;
		$this->gmail['refresh_token']          = $token;

		$options->set( $all );
	}

	/**
	 * Update auth code in our DB.
	 *
	 * @since 1.0.0
	 *
	 * @param string $code
	 */
	protected function update_auth_code( $code ) {

		$options = new PluginOptions();
		$all     = $options->get_all();

		$all[ $this->mailer ]['auth_code'] = $code;
		$this->gmail['auth_code']          = $code;

		$options->set( $all );
	}

	/**
	 * Get the auth URL used to proceed to Google to request access to send emails.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_google_auth_url() {
		if (
			! empty( $this->client ) &&
			class_exists( 'Google_Client', false ) &&
			$this->client instanceof \Google_Client
		) {
			return filter_var( $this->client->createAuthUrl(), FILTER_SANITIZE_URL );
		}

		return '';
	}

	/**
	 * Whether user saved Client ID and Client Secret or not.
	 * Both options are required.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function is_clients_saved() {
		return ! empty( $this->gmail['client_id'] ) && ! empty( $this->gmail['client_secret'] );
	}

	/**
	 * Whether we have an access and refresh tokens or not.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function is_auth_required() {
		return empty( $this->gmail['access_token'] ) || empty( $this->gmail['refresh_token'] );
	}
}
