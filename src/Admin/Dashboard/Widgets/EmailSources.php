<?php

namespace WPMailSMTP\Admin\Dashboard\Widgets;

use WPMailSMTP\Admin\Area;
use WPMailSMTP\Admin\Dashboard\WidgetState;

/**
 * Dashboard "Email Sources" widget.
 *
 * @since 4.10.0
 */
class EmailSources extends AbstractWidget {

	/**
	 * Column placement.
	 *
	 * @since 4.10.0
	 */
	public const COLUMN = 'main';

	/**
	 * Default sort position, below the Addons widget (#40), where the design places the
	 * promo states.
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

		return 'email_sources';
	}

	/**
	 * Get the widget title.
	 *
	 * @since 4.10.0
	 *
	 * @return string
	 */
	public function get_title(): string {

		return esc_html__( 'Email Sources', 'wp-mail-smtp' );
	}

	/**
	 * Get the widget state. This tier shows the education variant with an upgrade prompt whatever
	 * the mailer or log state, so it never queries the log.
	 *
	 * @since 4.10.0
	 *
	 * @return WidgetState
	 */
	public function get_state(): WidgetState {

		if ( ! $this->access->is_pro() ) {
			return new WidgetState( true, 'education' );
		}

		if ( $this->has_logged_emails() ) {
			return new WidgetState( true, 'data' );
		}

		return new WidgetState( true, 'connect' );
	}

	/**
	 * Why the connect variant is showing, which decides its CTA.
	 *
	 * @since 4.10.0
	 *
	 * @return string Either 'primary_connection' or 'test_email'.
	 */
	public function get_connect_reason() {

		return $this->is_mailer_configured() ? 'test_email' : 'primary_connection';
	}

	/**
	 * Whether any email has ever been logged. This tier has no log to query, and
	 * get_state() returns from its education branch before reaching here.
	 *
	 * @since 4.10.0
	 *
	 * @return bool
	 */
	protected function has_logged_emails() {

		return false;
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
			'dashboard/widgets/email-sources',
			[
				'variant'        => $variant,
				'connect_reason' => $this->get_connect_reason(),
				'education_url'  => wp_mail_smtp()->get_upgrade_link(
					[
						'medium'  => 'dashboard',
						'content' => 'email-sources',
					]
				),
				'connect_url'    => add_query_arg( 'tab', 'connections', wp_mail_smtp()->get_admin()->get_admin_page_url() ),
				'test_email_url' => add_query_arg( 'tab', 'test', wp_mail_smtp()->get_admin()->get_admin_page_url( Area::SLUG . '-tools' ) ),
				'config'         => null,
			],
			true
		);
	}
}
