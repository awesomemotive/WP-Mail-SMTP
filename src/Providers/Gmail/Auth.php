<?php

namespace WPMailSMTP\Providers\Gmail;

use WPMailSMTP\Options as PluginOptions;
use WPMailSMTP\Providers\AuthAbstract;
use WPMailSMTP\WP;

/**
 * Class Auth to request access and refresh tokens.
 *
 * @package WPMailSMTP\Providers\Gmail
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
	 */
	public function __construct() {

		$this->include_google_lib();

		$options      = new PluginOptions();
		$this->mailer = $options->get( 'mail', 'mailer' );
		$this->gmail  = $options->get_group( $this->mailer );

		$this->client = $this->get_client();
	}

	/**
	 * Use the composer autoloader to include the Google Library.
	 */
	protected function include_google_lib() {
		require wp_mail_smtp()->plugin_path . '/vendor/autoload.php';
	}

	/**
	 * Init and get the Google Client object.
	 */
	public function get_client() {

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
		$client->setIncludeGrantedScopes( true );
		// We request only the sending capability, as it's what we only need to do.
		$client->addScope( \Google_Service_Gmail::GMAIL_SEND );
		$client->setRedirectUri( self::get_plugin_auth_url() );

		if ( isset( $this->gmail['auth_code'] ) ) {
			$creds = $client->fetchAccessTokenWithAuthCode( $this->gmail['auth_code'] );

			if ( ! empty( $creds['error'] ) ) {
				WP::add_admin_notice(
					esc_html__( 'There was an error while authenticating you via Google. Please try again.', 'wp-mail-smtp' ),
					WP::ADMIN_NOTICE_ERROR
				);

				return $client;
			}

			$access_token = $client->getAccessToken();

			$client->setAccessToken( $access_token );

			// Refresh the token if it's expired.
			if ( $client->isAccessTokenExpired() ) {
				$client->fetchAccessTokenWithRefreshToken( $client->getRefreshToken() );
			}

			$this->update_access_token( $client->getAccessToken() );
		}

		return $client;
	}

	/**
	 * Do all the logic of the user authentication.
	 */
	public function process() {
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
			$scope === ( \Google_Service_Gmail::GMAIL_SEND . ' ' . \Google_Service_Gmail::MAIL_GOOGLE_COM )
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
	 * @param array $token
	 */
	protected function update_access_token( $token ) {

		$options = new PluginOptions();
		$all     = $options->get_all();

		$all[ $this->mailer ]['access_token'] = $token;

		$options->set( $all );
	}

	/**
	 * @param string $code
	 */
	protected function update_auth_code( $code ) {

		$options = new PluginOptions();
		$all     = $options->get_all();

		$all[ $this->mailer ]['auth_code'] = $code;

		$options->set( $all );
	}

	/**
	 * Get the auth URL used to proceed to Google to request access to send emails.
	 *
	 * @return string
	 */
	public function get_google_auth_url() {
		return filter_var( $this->client->createAuthUrl(), FILTER_SANITIZE_URL );
	}

	/**
	 * Whether we have a code or not.
	 *
	 * @return bool
	 */
	public function is_completed() {
		return ! empty( $this->gmail['access_token'] );
	}
}
