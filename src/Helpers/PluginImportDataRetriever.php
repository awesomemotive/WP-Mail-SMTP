<?php

namespace WPMailSMTP\Helpers;

/**
 * Class for preparing import data from other SMTP plugins.
 *
 * @since 2.6.0
 */
class PluginImportDataRetriever {

	/**
	 * The slug of the SMTP plugin to prepare the data for.
	 *
	 * @since 2.6.0
	 *
	 * @var string
	 */
	private $slug;

	/**
	 * PluginImportDataRetriever constructor.
	 *
	 * @since 2.6.0
	 *
	 * @param string $slug The SMTP plugin slug.
	 */
	public function __construct( $slug ) {

		$this->slug = $slug;
	}

	/**
	 * Get the data for the current plugin slug.
	 *
	 * @since 2.6.0
	 *
	 * @return false|array
	 */
	public function get() {

		$method_name = preg_replace( '/[\-]/', '_', sanitize_key( "get_$this->slug" ) );

		if ( method_exists( $this, $method_name ) ) {
			return $this->$method_name();
		}

		return false;
	}

	/**
	 * Check if Easy WP SMTP plugin settings are present and extract them.
	 *
	 * @since 2.6.0
	 *
	 * @return array
	 */
	private function get_easy_smtp() {

		$options = get_option( 'swpsmtp_options' );

		if ( empty( $options ) ) {
			return [];
		}

		return [
			'mail' => [
				'mailer'          => 'smtp',
				'from_email'      => isset( $options['from_email_field'] ) ? $options['from_email_field'] : '',
				'from_name'       => isset( $options['from_name_field'] ) ? $options['from_name_field'] : '',
				'from_name_force' => isset( $options['force_from_name_replace'] ) ? $options['force_from_name_replace'] : false,
			],
			'smtp' => [
				'host'       => isset( $options['smtp_settings']['host'] ) ? $options['smtp_settings']['host'] : '',
				'encryption' => isset( $options['smtp_settings']['type_encryption'] ) ? $options['smtp_settings']['type_encryption'] : 'none',
				'port'       => isset( $options['smtp_settings']['port'] ) ? $options['smtp_settings']['port'] : 25,
				'auth'       => isset( $options['smtp_settings']['autentication'] ) ? $options['smtp_settings']['autentication'] : true,
				'user'       => isset( $options['smtp_settings']['username'] ) ? $options['smtp_settings']['username'] : '',
				'pass'       => '',
				'autotls'    => true,
			],
		];
	}

	/**
	 * Check if Post SMTP Mailer plugin settings are present and extract them.
	 *
	 * @since 2.6.0
	 *
	 * @return array
	 */
	private function get_post_smtp_mailer() {

		$options = get_option( 'postman_options' );

		if ( empty( $options ) ) {
			return [];
		}

		$allowed_mailers = [
			'smtp'         => 'smtp',
			'gmail_api'    => 'gmail',
			'sendgrid_api' => 'sendgrid',
			'mailgun_api'  => 'mailgun',
		];

		$data = [
			'mail'     => [
				'mailer'     => ( isset( $options['transport_type'] ) && in_array( $options['transport_type'], array_keys( $allowed_mailers ), true ) ) ? $allowed_mailers[ $options['transport_type'] ] : 'mail',
				'from_email' => isset( $options['sender_email'] ) ? $options['sender_email'] : '',
				'from_name'  => isset( $options['sender_name'] ) ? $options['sender_name'] : '',
			],
			'smtp'     => [
				'host'       => isset( $options['hostname'] ) ? $options['hostname'] : '',
				'encryption' => isset( $options['enc_type'] ) ? $options['enc_type'] : 'none',
				'port'       => isset( $options['port'] ) ? $options['port'] : 25,
				'auth'       => isset( $options['auth_type'] ) && $options['auth_type'] !== 'none',
				'user'       => isset( $options['basic_auth_username'] ) ? $options['basic_auth_username'] : '',
				'pass'       => ! empty( $options['basic_auth_password'] ) ? base64_decode( $options['basic_auth_password'] ) : '', // phpcs:ignore
				'autotls'    => true,
			],
			'gmail'    => [
				'client_id'     => isset( $options['oauth_client_id'] ) ? $options['oauth_client_id'] : '',
				'client_secret' => isset( $options['oauth_client_secret'] ) ? $options['oauth_client_secret'] : '',
			],
			'sendgrid' => [
				'api_key' => ! empty( $options['sendgrid_api_key'] ) ? base64_decode( $options['sendgrid_api_key'] ) : '', // phpcs:ignore
			],
			'mailgun'  => [
				'api_key' => ! empty( $options['mailgun_api_key'] ) ? base64_decode( $options['mailgun_api_key'] ) : '', // phpcs:ignore
				'domain'  => isset( $options['mailgun_domain_name'] ) ? $options['mailgun_domain_name'] : '',
				'region'  => ( isset( $options['mailgun_region'] ) && ! empty( $options['mailgun_region'] ) ) ? 'EU' : 'US',
			],
		];

		if ( class_exists( '\PostmanOptions' ) ) {
			$pm_options = \PostmanOptions::getInstance();

			$data['sendgrid']['api_key'] = $pm_options->getSendGridApiKey();
			$data['mailgun']['api_key']  = $pm_options->getMailgunApiKey();
			$data['smtp']['pass']        = $pm_options->getPassword();
		}

		return $data;
	}

	/**
	 * Check if SMTP Mailer plugin settings are present and extract them.
	 *
	 * @since 2.6.0
	 *
	 * @return array
	 */
	private function get_smtp_mailer() {

		$options = get_option( 'smtp_mailer_options' );

		if ( empty( $options ) ) {
			return [];
		}

		return [
			'mail' => [
				'mailer'     => 'smtp',
				'from_email' => isset( $options['from_email'] ) ? $options['from_email'] : '',
				'from_name'  => isset( $options['from_name'] ) ? $options['from_name'] : '',
			],
			'smtp' => [
				'host'       => isset( $options['smtp_host'] ) ? $options['smtp_host'] : '',
				'encryption' => isset( $options['type_of_encryption'] ) ? $options['type_of_encryption'] : 'none',
				'port'       => isset( $options['smtp_port'] ) ? $options['smtp_port'] : 25,
				'auth'       => isset( $options['smtp_auth'] ) && $options['smtp_auth'] === 'true',
				'user'       => isset( $options['smtp_username'] ) ? $options['smtp_username'] : '',
				'pass'       => ! empty( $options['smtp_password'] ) ? base64_decode( $options['smtp_password'] ) : '', // phpcs:ignore
				'autotls'    => true,
			],
		];
	}

	/**
	 * Check if WP SMTP plugin settings are present and extract them.
	 *
	 * @since 2.6.0
	 *
	 * @return array
	 */
	private function get_wp_smtp() {

		$options = get_option( 'wp_smtp_options' );

		if ( empty( $options ) ) {
			return [];
		}

		return [
			'mail' => [
				'mailer'     => 'smtp',
				'from_email' => isset( $options['from'] ) ? $options['from'] : '',
				'from_name'  => isset( $options['fromname'] ) ? $options['fromname'] : '',
			],
			'smtp' => [
				'host'       => isset( $options['host'] ) ? $options['host'] : '',
				'encryption' => ! empty( $options['smtpsecure'] ) ? $options['smtpsecure'] : 'none',
				'port'       => isset( $options['port'] ) ? $options['port'] : 25,
				'auth'       => isset( $options['smtpauth'] ) && $options['smtpauth'] === 'yes',
				'user'       => isset( $options['username'] ) ? $options['username'] : '',
				'pass'       => isset( $options['password'] ) ? $options['password'] : '',
				'autotls'    => true,
			],
		];
	}
}
