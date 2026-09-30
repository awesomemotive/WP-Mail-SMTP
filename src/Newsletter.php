<?php

namespace WPMailSMTP;

use WPMailSMTP\Helpers\Helpers;

/**
 * Subscribing to the WP Mail SMTP newsletter.
 *
 * @since 4.10.0
 */
class Newsletter {

	/**
	 * Subscription endpoint URL.
	 *
	 * @since 4.10.0
	 *
	 * @var string
	 */
	private const ENDPOINT = 'https://connect.wpmailsmtp.com/subscribe/drip/';

	/**
	 * Subscribe an email address to the newsletter.
	 *
	 * @since 4.10.0
	 *
	 * @param string $email Email address to subscribe.
	 */
	public function subscribe( $email ) {

		$body = [
			'email' => base64_encode( $email ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		];

		$wpforms_version_type = $this->get_wpforms_version_type();

		if ( ! empty( $wpforms_version_type ) ) {
			$body['wpforms_version_type'] = $wpforms_version_type;
		}

		wp_remote_post(
			self::ENDPOINT,
			[
				'user-agent' => Helpers::get_default_user_agent(),
				'body'       => $body,
			]
		);

		/**
		 * Fires after an email address has been submitted to the newsletter.
		 *
		 * @since 4.10.0
		 *
		 * @param string $email The submitted email address.
		 */
		do_action( 'wp_mail_smtp_newsletter_subscribe_after', $email );
	}

	/**
	 * Get the WPForms version type if it's installed.
	 *
	 * @since 4.10.0
	 *
	 * @return false|string Return `false` if WPForms is not installed, otherwise return either `lite` or `pro`.
	 */
	private function get_wpforms_version_type() {

		if ( ! function_exists( 'wpforms' ) ) {
			return false;
		}

		if ( method_exists( wpforms(), 'is_pro' ) ) {
			$is_wpforms_pro = wpforms()->is_pro();
		} else {
			$is_wpforms_pro = wpforms()->pro;
		}

		return $is_wpforms_pro ? 'pro' : 'lite';
	}
}
