<?php

namespace WPMailSMTP\Helpers;

use WPMailSMTP\Options;

/**
 * Class with all the misc helper functions that don't belong elsewhere.
 *
 * @since 3.0.0
 */
class Helpers {

	/**
	 * Check if the current active mailer has email send confirmation functionality.
	 *
	 * @since 3.0.0
	 *
	 * @return bool
	 */
	public static function mailer_without_send_confirmation() {

		return ! in_array(
			Options::init()->get( 'mail', 'mailer' ),
			[
				'smtpcom',
				'sendinblue',
				'mailgun',
				'postmark',
				'sparkpost'
			],
			true
		);
	}

	/**
	 * Include mbstring polyfill.
	 *
	 * @since 3.1.0
	 */
	public static function include_mbstring_polyfill() {

		require_once wp_mail_smtp()->plugin_path . '/vendor_prefixed/symfony/polyfill-mbstring/Mbstring.php';
		require_once wp_mail_smtp()->plugin_path . '/vendor_prefixed/symfony/polyfill-mbstring/bootstrap.php';
	}

	/**
	 * Test if the REST API is accessible.
	 *
	 * @since 3.3.0
	 *
	 * @return true|\WP_Error
	 */
	public static function test_rest_availability() {

		$headers = [
			'Cache-Control' => 'no-cache',
		];

		/** This filter is documented in wp-includes/class-wp-http-streams.php */
		$sslverify = apply_filters( 'https_local_ssl_verify', false );

		$url = rest_url( 'wp-mail-smtp/v1' );

		$response = wp_remote_get(
			$url,
			[
				'headers'   => $headers,
				'sslverify' => $sslverify,
			]
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		} elseif ( wp_remote_retrieve_response_code( $response ) !== 200 ) {
			return new \WP_Error( wp_remote_retrieve_response_code( $response ), wp_remote_retrieve_body( $response ) );
		}

		return true;
	}
}
