<?php

namespace WPMailSMTP\Providers\Sendinblue;

/**
 * Class Api is a wrapper for Sendinblue library with handy methods.
 *
 * @since 1.6.0
 */
class Api {

	/**
	 * Contains mailer options, constants + DB values.
	 *
	 * @since 1.6.0
	 *
	 * @var array
	 */
	private $options;

	/**
	 * API constructor that inits defaults and retrieves options.
	 *
	 * @since 1.6.0
	 */
	public function __construct() {

		$this->options = \WPMailSMTP\Options::init()->get_group( Options::SLUG );
	}

	/**
	 * Configure API key authorization: api-key.
	 *
	 * @since 1.6.0
	 *
	 * @return \SendinBlue\Client\Configuration
	 */
	protected function get_api_config() {

		return \SendinBlue\Client\Configuration::getDefaultConfiguration()->setApiKey( 'api-key', isset( $this->options['api_key'] ) ? $this->options['api_key'] : '' );
	}

	/**
	 * Get the mailer client instance for Account API.
	 *
	 * @since 1.6.0
	 */
	public function get_account_client() {

		// Include the library.
		require_once wp_mail_smtp()->plugin_path . '/vendor/autoload.php';

		return new \SendinBlue\Client\Api\AccountApi( null, $this->get_api_config() );
	}

	/**
	 * Get the mailer client instance for Sender API.
	 *
	 * @since 1.6.0
	 */
	public function get_sender_client() {

		// Include the library.
		require_once wp_mail_smtp()->plugin_path . '/vendor/autoload.php';

		return new \SendinBlue\Client\Api\SendersApi( null, $this->get_api_config() );
	}

	/**
	 * Get the mailer client instance for SMTP API.
	 *
	 * @since 1.6.0
	 */
	public function get_smtp_client() {

		// Include the library.
		require_once wp_mail_smtp()->plugin_path . '/vendor/autoload.php';

		return new \SendinBlue\Client\Api\SMTPApi( null, $this->get_api_config() );
	}

	/**
	 * Whether the mailer is ready to be used in API calls.
	 *
	 * @since 1.6.0
	 *
	 * @return bool
	 */
	public function is_ready() {

		return ! empty( $this->options['api_key'] );
	}
}
