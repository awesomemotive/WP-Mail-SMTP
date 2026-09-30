<?php

namespace WPMailSMTP\Admin\Dashboard\Widgets;

use WPMailSMTP\Admin\Area;
use WPMailSMTP\Admin\Dashboard\InlineCards;
use WPMailSMTP\Admin\Dashboard\Stats;
use WPMailSMTP\Admin\Dashboard\WidgetState;

/**
 * Emails Overview widget.
 *
 * @since 4.10.0
 */
class EmailsOverview extends AbstractWidget {

	// parent:: can't reach a trait method, so the override below reaches the
	// trait's own card through this alias instead.
	use InlineCards {
		get_email_alerts_card as private get_generic_email_alerts_card;
	}

	/**
	 * Column placement.
	 *
	 * @since 4.10.0
	 */
	public const COLUMN = 'main';

	/**
	 * Default sort position within the column.
	 *
	 * @since 4.10.0
	 */
	public const ORDER = 20;

	/**
	 * Widget identifier.
	 *
	 * @since 4.10.0
	 *
	 * @return string
	 */
	public function get_id(): string {

		return 'emails_overview';
	}

	/**
	 * Widget title.
	 *
	 * @since 4.10.0
	 *
	 * @return string
	 */
	public function get_title(): string {

		return esc_html__( 'Emails Overview', 'wp-mail-smtp' );
	}

	/**
	 * Widget state for the given access context.
	 *
	 * @since 4.10.0
	 *
	 * @return WidgetState
	 */
	public function get_state(): WidgetState {

		return new WidgetState( true, $this->has_data() ? 'data' : 'connect' );
	}

	/**
	 * Whether the weekly series carries any sent or failed emails.
	 *
	 * @since 4.10.0
	 *
	 * @return bool
	 */
	protected function has_data() {

		foreach ( $this->get_stats()->get_weekly_series() as $week ) {
			if ( $week['sent'] > 0 || $week['failed'] > 0 ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get the Dashboard statistics instance backing the has-data check.
	 *
	 * @since 4.10.0
	 *
	 * @return Stats
	 */
	protected function get_stats() {

		return new Stats();
	}

	/**
	 * The reason the 'connect' variant is showing, driving its CTA copy and link.
	 *
	 * @since 4.10.0
	 *
	 * @return string Either 'primary_connection' or 'test_email'.
	 */
	public function get_connect_reason() {

		return $this->is_mailer_configured() ? 'test_email' : 'primary_connection';
	}

	/**
	 * The chart's series, in legend order. Colours are literal hex, not design
	 * tokens: Chart.js needs a real value at draw time.
	 *
	 * @since 4.10.0
	 *
	 * @return array
	 */
	public function get_series_meta(): array {

		return [
			[
				'id'    => 'sent',
				'label' => esc_html__( 'Sent Emails', 'wp-mail-smtp' ),
				'color' => '#056aab',
				'fill'  => true,
			],
			[
				'id'    => 'failed',
				'label' => esc_html__( 'Failed', 'wp-mail-smtp' ),
				'color' => '#d63638',
				'fill'  => false,
			],
		];
	}

	/**
	 * The chart legend, which the design puts in the head beside the title. Only the
	 * data variant has a chart to legend.
	 *
	 * @since 4.10.0
	 *
	 * @param string $variant Template variant.
	 * @param array  $data    Aggregated data.
	 *
	 * @return string
	 */
	protected function render_head_aside( string $variant, array $data ): string {

		if ( $variant !== 'data' ) {
			return '';
		}

		return (string) wp_mail_smtp_render(
			'dashboard/widgets/emails-overview-legend',
			[ 'series_meta' => $this->get_series_meta() ],
			true
		);
	}

	/**
	 * Widget body.
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
			'dashboard/widgets/emails-overview',
			[
				'variant'        => $variant,
				'connect_reason' => $this->get_connect_reason(),
				'series'         => $data['chart_series'] ?? [],
				'series_meta'    => $this->get_series_meta(),
				'connect_url'    => wp_mail_smtp()->get_admin()->get_admin_page_url( Area::SLUG . '#wp-mail-smtp-setting-row-mailer' ),
				'test_email_url' => add_query_arg( 'tab', 'test', wp_mail_smtp()->get_admin()->get_admin_page_url( Area::SLUG . '-tools' ) ),
				'inline_cards'   => $this->render_inline_cards( $this->get_inline_cards( $data ) ),
			],
			true
		);
	}

	/**
	 * The "Set Up Email Alerts" card, worded with the real failed-email count instead of
	 * the trait's generic copy.
	 *
	 * @since 4.10.0
	 *
	 * @param array $data Aggregated data, carrying `recent_failed_count`.
	 *
	 * @return array Empty when the card does not apply.
	 */
	protected function get_email_alerts_card( array $data ): array {

		$card = $this->get_generic_email_alerts_card( $data );

		if ( empty( $card ) ) {
			return $card;
		}

		$count = (int) ( $data['recent_failed_count'] ?? 0 );

		if ( $this->access->is_pro() ) {
			$card['title'] = esc_html(
				sprintf(
					/* translators: %d - number of failed emails in the last 30 days. */
					_n( 'Your Site Had %d Failed Email in the Last 30 Days', 'Your Site Had %d Failed Emails in the Last 30 Days', $count, 'wp-mail-smtp' ),
					$count
				)
			);
			$card['text']         = esc_html__( 'Get notified instantly when emails fail to send with the Email Alerts feature.', 'wp-mail-smtp' );
			$card['cta']['label'] = esc_html__( 'Set Up Email Alerts', 'wp-mail-smtp' );
		} else {
			$card['title'] = esc_html(
				sprintf(
					/* translators: %d - number of failed emails in the last 30 days. */
					_n( 'We Detected %d Failed Email in the Last 30 Days', 'We Detected %d Failed Emails in the Last 30 Days', $count, 'wp-mail-smtp' ),
					$count
				)
			);
			$card['text']         = esc_html__( 'Upgrade to Pro and get instant alert notifications when emails fail to send.', 'wp-mail-smtp' );
			$card['cta']['label'] = esc_html__( 'Upgrade to Pro', 'wp-mail-smtp' );
		}

		return $card;
	}
}
