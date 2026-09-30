<?php

namespace WPMailSMTP\Admin\SetupWizard;

use WP_Error;
use WPMailSMTP\Providers\Sendlayer\QuickConnect;

/**
 * Bridges the tokenless hosted-wizard REST session to a cookie-authenticated admin request.
 *
 * WordPress nonces bind to the session token, which is empty in the hosted wizard's signed,
 * cookie-free REST requests, so a nonce minted there never verifies on the cookie-bearing
 * return. The browser is sent to a wp-admin URL that lands here instead; the real connect
 * URL, and its nonce, is built on `admin_init`, then the user is redirected on to the
 * provider (or marketing site).
 *
 * @since 4.10.0
 */
class RedirectBridge {

	/**
	 * Query argument that triggers the bridge on admin_init; its value is the action slug.
	 *
	 * @since 4.10.0
	 *
	 * @var string
	 */
	const ARG = 'wp_mail_smtp_setup_wizard_redirect_bridge';

	/**
	 * Bridge action that resolves a provider OAuth authorization URL.
	 *
	 * @since 4.10.0
	 *
	 * @var string
	 */
	const ACTION_OAUTH_CONNECT = 'oauth_connect';

	/**
	 * Bridge action that starts a SendLayer Quick Connect session.
	 *
	 * @since 4.10.0
	 *
	 * @var string
	 */
	const ACTION_SENDLAYER_CONNECT = 'sendlayer_connect';

	/**
	 * Register hooks.
	 *
	 * @since 4.10.0
	 */
	public function hooks() {

		add_action( 'admin_init', [ $this, 'handle' ] );
	}

	/**
	 * Build the bounce URL that lands on the admin_init handler.
	 *
	 * @since 4.10.0
	 *
	 * @param string $action Bridge action slug: one of the ACTION_* constants.
	 * @param array  $params Extra query args carried through to the admin_init handler.
	 *
	 * @return string
	 */
	public static function get_url( $action, array $params = [] ) {

		$query = [ self::ARG => $action ];

		// add_query_arg() does not encode the values it adds, so a return_url fragment
		// like "#/step/..." would be read as this bounce URL's own fragment and dropped
		// before it ever reached $_GET.
		foreach ( $params as $key => $value ) {
			$query[ $key ] = rawurlencode( (string) $value );
		}

		return add_query_arg( $query, wp_mail_smtp()->get_admin()->get_admin_page_url() );
	}

	/**
	 * Handle the bounce request: build the real target in cookie context and redirect.
	 *
	 * @since 4.10.0
	 */
	public function handle() {

		// No nonce gate: this only initiates a connect flow, it is capability-gated, and
		// the state-changing return handlers verify their own WP nonce. A nonce cannot be
		// minted in get_url() anyway, which runs in the tokenless REST context.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( empty( $_GET[ self::ARG ] ) ) {
			return;
		}

		if ( ! current_user_can( wp_mail_smtp()->get_capability_manage_global_options() ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$action = sanitize_key( wp_unslash( $_GET[ self::ARG ] ) );
		$target = $this->resolve( $action );

		if ( is_wp_error( $target ) ) {
			$this->redirect_with_error( $action, $target->get_error_code() );
		}

		// An external provider or marketing-site URL built by our own code, so not
		// restricted to allowed_redirect_hosts.
		// phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
		wp_redirect( $target );
		exit;
	}

	/**
	 * Resolve the redirect target for a bridge action.
	 *
	 * @since 4.10.0
	 *
	 * @param string $action Sanitized action slug.
	 *
	 * @return string|WP_Error Target URL, or a WP_Error whose code identifies the failure.
	 */
	private function resolve( $action ) {

		if ( $action === self::ACTION_SENDLAYER_CONNECT ) {
			return $this->resolve_sendlayer();
		}

		if ( $action === self::ACTION_OAUTH_CONNECT ) {
			return $this->resolve_oauth();
		}

		return new WP_Error( 'invalid_action' );
	}

	/**
	 * Resolve the SendLayer Quick Connect marketing-site redirect.
	 *
	 * The session start (and its WP nonce) runs here, in cookie context, so the nonce
	 * appended to the return URL verifies when the user returns to handle_auth_complete().
	 *
	 * @since 4.10.0
	 *
	 * @return string|WP_Error
	 */
	private function resolve_sendlayer() {

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$return_url = isset( $_GET['return_url'] ) ? esc_url_raw( wp_unslash( $_GET['return_url'] ) ) : '';

		if ( empty( $return_url ) ) {
			return new WP_Error( 'missing_return_url' );
		}

		$connection_id = isset( $_GET['connection_id'] ) ? sanitize_key( wp_unslash( $_GET['connection_id'] ) ) : '';
		$mode          = isset( $_GET['mode'] ) ? sanitize_key( wp_unslash( $_GET['mode'] ) ) : '';
		$utm_content   = isset( $_GET['utm_content'] ) ? sanitize_text_field( wp_unslash( $_GET['utm_content'] ) ) : 'Quick Connect';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		$result = ( new QuickConnect() )->init_connect_session( $return_url, $connection_id, $mode, $utm_content );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		if ( empty( $result['redirect_url'] ) ) {
			return new WP_Error( 'sendlayer_connect_failed' );
		}

		return $result['redirect_url'];
	}

	/**
	 * Resolve the OAuth authorization URL for the requested mailer.
	 *
	 * The credentials and the is_setup_wizard_auth flag were persisted by the REST
	 * endpoint; here the provider builds its auth URL (and state nonce) in cookie context.
	 *
	 * @since 4.10.0
	 *
	 * @return string|WP_Error
	 */
	private function resolve_oauth() {

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$mailer = isset( $_GET['mailer'] ) ? sanitize_key( wp_unslash( $_GET['mailer'] ) ) : '';

		if ( empty( $mailer ) ) {
			return new WP_Error( 'missing_mailer' );
		}

		$data = [];

		if ( $mailer === 'gmail' ) {
			$auth = wp_mail_smtp()->get_providers()->get_auth( 'gmail' );

			if ( $auth->is_clients_saved() && $auth->is_auth_required() ) {
				$data['oauth_url'] = $auth->get_auth_url();
			}
		}

		/**
		 * Filter the setup wizard OAuth authorization URL for a mailer.
		 *
		 * Lets Pro OAuth mailers (Outlook, Zoho) resolve their auth URL. Fired here,
		 * on admin_init, so the auth URL's state nonce is bound to the current session.
		 *
		 * @since 4.10.0
		 *
		 * @param array  $data   OAuth data (e.g. `oauth_url`).
		 * @param string $mailer Mailer slug.
		 */
		$data = apply_filters( 'wp_mail_smtp_admin_setup_wizard_get_oauth_url', $data, $mailer ); // phpcs:ignore WPForms.PHP.ValidateHooks.InvalidHookName -- Public hook name, kept for backwards compatibility.

		if ( empty( $data['oauth_url'] ) ) {
			return new WP_Error( 'oauth_url_unavailable' );
		}

		return $data['oauth_url'];
	}

	/**
	 * Redirect back to the originating wizard step with an error code.
	 *
	 * Mirrors the provider OAuth return handlers: the hosted wizard shell loads at the
	 * wp-admin start URL and reads the `error` query arg on the configure-mailer step.
	 *
	 * @since 4.10.0
	 *
	 * @param string $action     Sanitized action slug.
	 * @param string $error_code WP_Error code identifying the failure.
	 */
	private function redirect_with_error( $action, $error_code ) {

		// SendLayer reports every outcome on one query arg the SPA and the settings-page
		// notice already read; OAuth uses the provider handlers' `error` arg.
		if ( $action === self::ACTION_SENDLAYER_CONNECT ) {
			$mailer    = 'sendlayer';
			$error_arg = 'sendlayer_quick_connect_result';
		} else {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$mailer    = isset( $_GET['mailer'] ) ? sanitize_key( wp_unslash( $_GET['mailer'] ) ) : '';
			$error_arg = 'error';
		}

		// Addressed to the hosted wizard, the only variant that reaches this bridge,
		// rather than to the launch URL, which forwards neither query args nor the
		// fragment.
		wp_safe_redirect(
			Launcher::get_url(
				Launcher::VARIANT_HOSTED,
				[ $error_arg => $error_code ],
				empty( $mailer ) ? '' : '/step/configure_mailer/' . $mailer
			)
		);
		exit;
	}
}
