<?php

namespace WPMailSMTP\Providers\Sendinblue;

use WPMailSMTP\ConnectionInterface;
use WPMailSMTP\Vendor\SendinBlue\Client\Api\AccountApi;
use WPMailSMTP\Vendor\SendinBlue\Client\Api\SendersApi;
use WPMailSMTP\Vendor\SendinBlue\Client\Api\TransactionalEmailsApi;
use WPMailSMTP\Vendor\SendinBlue\Client\Configuration;

/**
 * Class Api is a wrapper for Sendinblue library with handy methods.
 *
 * @since 1.6.0
 */
class Api {

	/**
	 * The Connection object.
	 *
	 * @since 3.7.0
	 *
	 * @var ConnectionInterface
	 */
	private $connection;

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
	 *
	 * @param ConnectionInterface $connection The Connection object.
	 */
	public function __construct( $connection = null ) {

		if ( ! is_null( $connection ) ) {
			$this->connection = $connection;
		} else {
			$this->connection = wp_mail_smtp()->get_connections_manager()->get_primary_connection();
		}

		$this->options = $this->connection->get_options()->get_group( Options::SLUG );
	}

	/**
	 * Configure API key authorization: api-key.
	 *
	 * @since 1.6.0
	 *
	 * @return Configuration
	 */
	protected function get_api_config() {

		return Configuration::getDefaultConfiguration()->setApiKey( 'api-key', isset( $this->options['api_key'] ) ? $this->options['api_key'] : '' );
	}

	/**
	 * Get the mailer client instance for Account API.
	 *
	 * @since 1.6.0
	 */
	public function get_account_client() {

		// Include the library.
		require_once wp_mail_smtp()->plugin_path . '/vendor/autoload.php';

		return new AccountApi( null, $this->get_api_config() );
	}

	/**
	 * Get the mailer client instance for Sender API.
	 *
	 * @since 1.6.0
	 */
	public function get_sender_client() {

		// Include the library.
		require_once wp_mail_smtp()->plugin_path . '/vendor/autoload.php';

		return new SendersApi( null, $this->get_api_config() );
	}

	/**
	 * Get the mailer client instance for SMTP API.
	 *
	 * @since 1.6.0
	 */
	public function get_smtp_client() {

		// Include the library.
		require_once wp_mail_smtp()->plugin_path . '/vendor/autoload.php';

		return new TransactionalEmailsApi( null, $this->get_api_config() );
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
