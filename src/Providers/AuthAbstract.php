<?php

namespace WPMailSMTP\Providers;

use WPMailSMTP\Options as PluginOptions;

/**
 * Class AuthAbstract.
 *
 * @since 1.0.0
 */
abstract class AuthAbstract implements AuthInterface {

	/**
	 * Mailer DB options.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	protected $options = array();

	/**
	 * @since 1.0.0
	 *
	 * @var mixed
	 */
	protected $client;

	/**
	 * Mailer slug.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $mailer_slug = '';

	/**
	 * Key for a stored unique state value.
	 *
	 * @since 1.5.0
	 *
	 * @var string
	 */
	public $state_key = 'wp_mail_smtp_provider_client_state';

	/**
	 * Use the composer autoloader to include the auth library and all dependencies.
	 *
	 * @since 1.0.0
	 */
	protected function include_vendor_lib() {

		require_once wp_mail_smtp()->plugin_path . '/vendor/autoload.php';
	}

	/**
	 * Get the url, that users will be redirected back to finish the OAuth process.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public static function get_plugin_auth_url() {

		return add_query_arg( 'tab', 'auth', wp_mail_smtp()->get_admin()->get_admin_page_url() );
	}

	/**
	 * Update auth code in our DB.
	 *
	 * @since 1.0.0
	 *
	 * @param string $code
	 */
	protected function update_auth_code( $code ) {

		$options = PluginOptions::init();
		$all     = $options->get_all();

		// To save in DB.
		$all[ $this->mailer_slug ]['auth_code'] = $code;

		// To save in currently retrieved options array.
		$this->options['auth_code'] = $code;

		// NOTE: These options need to be saved by overwriting all options, because WP automatic updates can cause an issue: GH #575!
		$options->set( $all, false, true );
	}

	/**
	 * Update Setup Wizard flag in our DB.
	 *
	 * @since 2.6.0
	 *
	 * @param boolean $state A state (true/false) to set the is_setup_wizard_auth mailer setting.
	 */
	public function update_is_setup_wizard_auth( $state ) {

		$options = PluginOptions::init();
		$all     = $options->get_all();

		// To save in DB.
		$all[ $this->mailer_slug ]['is_setup_wizard_auth'] = (bool) $state;

		// To save in currently retrieved options array.
		$this->options['is_setup_wizard_auth'] = (bool) $state;

		// NOTE: These options need to be saved by overwriting all options, because WP automatic updates can cause an issue: GH #575!
		$options->set( $all, false, true );
	}

	/**
	 * Update access token in our DB.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $token
	 */
	protected function update_access_token( $token ) {

		$options = PluginOptions::init();
		$all     = $options->get_all();

		// To save in DB.
		$all[ $this->mailer_slug ]['access_token'] = $token;

		// To save in currently retrieved options array.
		$this->options['access_token'] = $token;

		// NOTE: These options need to be saved by overwriting all options, because WP automatic updates can cause an issue: GH #575!
		$options->set( $all, false, true );
	}

	/**
	 * Update refresh token in our DB.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $token
	 */
	protected function update_refresh_token( $token ) {

		$options = PluginOptions::init();
		$all     = $options->get_all();

		// To save in DB.
		$all[ $this->mailer_slug ]['refresh_token'] = $token;

		// To save in currently retrieved options array.
		$this->options['refresh_token'] = $token;

		// NOTE: These options need to be saved by overwriting all options, because WP automatic updates can cause an issue: GH #575!
		$options->set( $all, false, true );
	}

	/**
	 * @inheritdoc
	 */
	public function is_clients_saved() {

		return ! empty( $this->options['client_id'] ) && ! empty( $this->options['client_secret'] );
	}

	/**
	 * @inheritdoc
	 */
	public function is_auth_required() {

		return empty( $this->options['access_token'] ) || empty( $this->options['refresh_token'] );
	}
}
