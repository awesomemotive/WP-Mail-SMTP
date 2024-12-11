<?php

namespace WPMailSMTP\Providers\Outlook;

use WPMailSMTP\WP;

/**
 * Class Provider.
 *
 * @since 4.3.0
 */
class Provider {

	/**
	 * Dismissed basic auth deprecation notice user meta.
	 *
	 * @since 4.3.0
	 *
	 * @var string
	 */
	private $dismissed_notice_key = 'wp_mail_smtp_microsoft_basic_auth_deprecation_notice_dismissed';

	/**
	 * Register hooks.
	 *
	 * @since 4.3.0
	 */
	public function hooks() {

		// Maybe display basic auth deprecation notice.
		add_action( 'admin_init', [ $this, 'maybe_display_basic_auth_notice' ] );

		// AJAX callback for basic auth deprecation notice dismissal.
		add_action( 'wp_ajax_wp_mail_smtp_microsoft_basic_auth_deprecation_notice_dismiss', [ $this, 'dismiss_basic_auth_notice' ] );
	}

	/**
	 * Display basic auth deprecation notice.
	 *
	 * @since 4.3.0
	 */
	public function maybe_display_basic_auth_notice() {

		// Bail if not a plugin admin page.
		if ( ! wp_mail_smtp()->get_admin()->is_admin_page() ) {
			return;
		}

		$connection = wp_mail_smtp()->get_connections_manager()->get_primary_connection();

		// Bail if Other SMTP is not the current mailer.
		if ( $connection->get_mailer_slug() !== 'smtp' ) {
			return;
		}

		$host        = $connection->get_options()->get( 'smtp', 'host' );
		$host_suffix = strtolower( implode( '.', array_slice( explode( '.', $host ), - 2 ) ) );
		$domains     = [
			'live.com',
			'hotmail.com',
			'outlook.com',
			'office365.com',
		];

		// Bail if current SMTP host is not Microsoft-related.
		if ( ! in_array( $host_suffix, $domains, true ) ) {
			return;
		}

		// Bail if the notice has been dismissed.
		if ( metadata_exists( 'user', get_current_user_id(), $this->dismissed_notice_key ) ) {
			return;
		}

		$message = wp_kses(
			sprintf( /* translators: %1$s - documentation link. */
				__( 'Heads up! Microsoft is <a href="%1$s" target="_blank" rel="noopener noreferrer">discontinuing support for basic SMTP connections</a>. To continue using Outlook or Hotmail, switch to our Outlook mailer for uninterrupted email sending.', 'wp-mail-smtp' ),
				wp_mail_smtp()->get_utm_url(
					'https://wpmailsmtp.com/microsoft-outlook-smtp-how-to-fix-basic-authentication-error/',
					[
						'medium'  => 'outlook-smtp-notice',
						'content' => 'other-smtp-lite-to-outlook',
					]
				)
			),
			[
				'a' => [
					'href'   => [],
					'rel'    => [],
					'target' => [],
				],
			]
		);

		if ( ! wp_mail_smtp()->is_pro() ) {
			$message = wp_kses(
				sprintf( /* translators: %1$s - Notice message; %2$s - upgrade link. */
					__( '%1$s <a href="%2$s" target="_blank" rel="noopener noreferrer">Upgrade to Pro now for easy, one-click Outlook setup</a>.', 'wp-mail-smtp' ),
					$message,
					wp_mail_smtp()->get_upgrade_link( [ 'medium' => 'outlook-smtp-notice', 'content' => 'other-smtp-lite-to-outlook' ] ) // phpcs:ignore WordPress.Arrays.ArrayDeclarationSpacing.AssociativeArrayFound
				),
				[
					'a' => [
						'href'   => [],
						'rel'    => [],
						'target' => [],
					],
				]
			);
		}

		WP::add_admin_notice( $message, implode( ' ', [ WP::ADMIN_NOTICE_WARNING, 'microsoft_basic_auth_deprecation_notice' ] ) );
	}

	/**
	 * Dismiss basic auth deprecation notice.
	 *
	 * @since 4.3.0
	 */
	public function dismiss_basic_auth_notice() {

		if ( ! current_user_can( wp_mail_smtp()->get_capability_manage_options() ) ) {
			wp_send_json_error();
		}

		if ( ! check_ajax_referer( 'wp-mail-smtp-admin', 'nonce', false ) ) {
			return;
		}

		update_user_meta( get_current_user_id(), $this->dismissed_notice_key, true );

		wp_send_json_success();
	}
}
