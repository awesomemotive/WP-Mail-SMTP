<?php

namespace WPMailSMTP\Admin\Dashboard\Widgets;

use WPMailSMTP\Admin\Dashboard\WidgetState;

/**
 * "Getting Started" sidebar widget.
 *
 * @since 4.10.0
 */
class GettingStarted extends AbstractWidget {

	/**
	 * Column placement.
	 *
	 * @since 4.10.0
	 */
	public const COLUMN = 'sidebar';

	/**
	 * Default sort position within the sidebar (last).
	 *
	 * @since 4.10.0
	 */
	public const ORDER = 40;

	/**
	 * Get the widget identifier.
	 *
	 * @since 4.10.0
	 *
	 * @return string
	 */
	public function get_id(): string {

		return 'getting_started';
	}

	/**
	 * Get the widget title.
	 *
	 * @since 4.10.0
	 *
	 * @return string
	 */
	public function get_title(): string {

		return esc_html__( 'Getting Started', 'wp-mail-smtp' );
	}

	/**
	 * Get the widget state.
	 *
	 * @since 4.10.0
	 *
	 * @return WidgetState
	 */
	public function get_state(): WidgetState {

		return new WidgetState( true, 'data' );
	}

	/**
	 * Render the widget body.
	 *
	 * @since 4.10.0
	 *
	 * @param string $variant Template variant.
	 * @param array  $data    Aggregated data.
	 *
	 * @return string
	 */
	protected function render_body( string $variant, array $data ): string {

		return (string) wp_mail_smtp_render(
			'dashboard/sidebar/getting-started',
			[
				'links'    => $this->get_links(),
				'view_all' => [
					'label' => esc_html__( 'View All Documentation', 'wp-mail-smtp' ),
					'url'   => wp_mail_smtp()->get_utm_url(
						'https://wpmailsmtp.com/docs/',
						[
							'medium'  => 'dashboard',
							'content' => 'View All Documentation',
						]
					),
				],
			],
			true
		);
	}

	/**
	 * Documentation links for new users.
	 *
	 * @since 4.10.0
	 *
	 * @return array
	 */
	protected function get_links(): array {

		return [
			[
				'label' => esc_html__( 'Setting Up Your Mailer (Complete Guide)', 'wp-mail-smtp' ),
				'url'   => wp_mail_smtp()->get_utm_url(
					'https://wpmailsmtp.com/docs/a-complete-guide-to-wp-mail-smtp-mailers/',
					[
						'medium'  => 'dashboard',
						'content' => 'Setting Up Your Mailer (Complete Guide)',
					]
				),
			],
			[
				'label' => esc_html__( 'Sending a Test Email', 'wp-mail-smtp' ),
				'url'   => wp_mail_smtp()->get_utm_url(
					'https://wpmailsmtp.com/docs/how-to-send-a-test-email-in-wp-mail-smtp/',
					[
						'medium'  => 'dashboard',
						'content' => 'Sending a Test Email',
					]
				),
			],
			[
				'label' => esc_html__( 'Enabling Email Logging', 'wp-mail-smtp' ),
				'url'   => wp_mail_smtp()->get_utm_url(
					'https://wpmailsmtp.com/docs/how-to-set-up-email-logging/',
					[
						'medium'  => 'dashboard',
						'content' => 'Enabling Email Logging',
					]
				),
			],
			[
				'label' => esc_html__( 'Understanding Email Reports', 'wp-mail-smtp' ),
				'url'   => wp_mail_smtp()->get_utm_url(
					'https://wpmailsmtp.com/docs/how-to-use-email-reports-in-wp-mail-smtp/',
					[
						'medium'  => 'dashboard',
						'content' => 'Understanding Email Reports',
					]
				),
			],
			[
				'label' => esc_html__( 'Using WP Mail SMTP with AI Assistants', 'wp-mail-smtp' ),
				'url'   => wp_mail_smtp()->get_utm_url(
					'https://wpmailsmtp.com/docs/using-wpmailsmtp-with-ai-assistants/',
					[
						'medium'  => 'dashboard',
						'content' => 'Using WP Mail SMTP with AI Assistants',
					]
				),
			],
		];
	}
}
