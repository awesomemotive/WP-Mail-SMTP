<?php

namespace WPMailSMTP\Admin\Dashboard\Widgets;

use WPMailSMTP\Admin\Dashboard\WidgetState;

// wpms:icon-[fa6-solid--envelope] wpms:icon-[fa7-solid--ban] wpms:icon-[fa6-solid--circle-check] wpms:icon-[fa7-regular--eye] wpms:icon-[fa6-solid--lock]
// are built from an `icon` value, so they are named here for Tailwind's scanner.
/**
 * Dashboard stat cards row, rendered as four free-standing cards spanning the content
 * width above both columns, outside the shared card shell.
 *
 * @since 4.10.0
 */
class StatCards extends AbstractWidget {

	/**
	 * Column placement: the full-width row above both columns.
	 *
	 * @since 4.10.0
	 */
	public const COLUMN = 'top';

	/**
	 * Default sort position.
	 *
	 * @since 4.10.0
	 */
	public const ORDER = 10;

	/**
	 * Get the widget identifier.
	 *
	 * @since 4.10.0
	 *
	 * @return string
	 */
	public function get_id(): string {

		return 'stat_cards';
	}

	/**
	 * Get the widget title. Empty: the cards row has no card title in the design.
	 *
	 * @since 4.10.0
	 *
	 * @return string
	 */
	public function get_title(): string {

		return '';
	}

	/**
	 * Get the widget state. The cards row is always visible, as education or data.
	 *
	 * @since 4.10.0
	 *
	 * @return WidgetState
	 */
	public function get_state(): WidgetState {

		return new WidgetState( true, 'data' );
	}

	/**
	 * Render the cards row, overriding the shared card shell this widget does not use.
	 *
	 * @since 4.10.0
	 *
	 * @param string $variant Template variant.
	 * @param array  $data    Aggregated data.
	 *
	 * @return string
	 */
	public function render( string $variant, array $data ): string {

		return sprintf(
			'<div class="wpms-dashboard-stat-cards" data-widget="%s">%s</div>',
			esc_attr( $this->get_id() ),
			$this->render_body( $variant, $data )
		);
	}

	/**
	 * Render the stat-card row for the given aggregated data.
	 *
	 * @since 4.10.0
	 *
	 * @param string $variant Template variant.
	 * @param array  $data    Aggregated dashboard data.
	 *
	 * @return string
	 */
	protected function render_body( string $variant, array $data ): string {

		$html = '';

		foreach ( $this->get_cards( $data ) as $card ) {
			$html .= wp_mail_smtp_render( 'dashboard/stat-card', [ 'card' => $card ], true );
		}

		return $html;
	}

	/**
	 * The stat cards, in display order.
	 *
	 * @since 4.10.0
	 *
	 * @param array $data Aggregated data.
	 *
	 * @return array
	 */
	public function get_cards( array $data ) {

		[ $sent_value, $sent_delta ]     = $this->get_metric( $data, 'sent' );
		[ $failed_value, $failed_delta ] = $this->get_metric( $data, 'failed' );

		return [
			$this->build_card( 'emails', esc_html__( 'Emails Last Week', 'wp-mail-smtp' ), $sent_value, $sent_delta, false, 'fa6-solid--envelope', 'emails' ),
			$this->build_card( 'failed', esc_html__( 'Failed Last Week', 'wp-mail-smtp' ), $failed_value, $failed_delta, false, 'fa7-solid--ban', 'failed' ),
			$this->build_card( 'sent', esc_html__( 'Sent', 'wp-mail-smtp' ), null, null, true, 'fa6-solid--circle-check', 'sent', 1190, 4.0 ),
			$this->build_card( 'opened', esc_html__( 'Opened', 'wp-mail-smtp' ), null, null, true, 'fa7-regular--eye', 'opened', 486, 2.5 ),
		];
	}

	/**
	 * Assemble one card.
	 *
	 * @since 4.10.0
	 *
	 * @param string     $id           Card identifier.
	 * @param string     $label        Card label.
	 * @param int|null   $value        Metric value, null for an education card.
	 * @param float|null $delta        Percent change against the previous week, null for an education card.
	 * @param bool       $locked       Whether the card is an education card for a Pro metric.
	 * @param string     $icon         Iconify identifier (set--name) for the card's icon.
	 * @param string     $tone         Icon tint, one of 'emails', 'failed', 'sent', 'opened'.
	 * @param int        $teaser_value Fixed teaser figure shown blurred on an education card. Not site
	 *                                 data: the design shows the same figure regardless of the
	 *                                 site's real numbers, so it must never be swapped for a live value.
	 * @param float      $teaser_blur  Blur radius, in pixels, for the teaser figure.
	 *
	 * @return array
	 */
	protected function build_card( string $id, string $label, $value, $delta, bool $locked, string $icon, string $tone, int $teaser_value = 0, float $teaser_blur = 0.0 ): array {

		return [
			'id'           => $id,
			'label'        => $label,
			'value'        => $value,
			'delta'        => $delta,
			'locked'       => $locked,
			'icon'         => $icon,
			'tone'         => $tone,
			'teaser_value' => $teaser_value,
			'teaser_blur'  => $teaser_blur,
		];
	}

	/**
	 * One metric's value and percent delta, read off the aggregated data.
	 *
	 * @since 4.10.0
	 *
	 * @param array  $data Aggregated data, carrying `stat_totals` and `stat_deltas`.
	 * @param string $key  Metric key to read from both.
	 *
	 * @return array `[ value, delta ]`; the delta is null with no earlier period to compare.
	 */
	protected function get_metric( array $data, string $key ): array {

		return [
			(int) ( $data['stat_totals'][ $key ] ?? 0 ),
			$data['stat_deltas'][ $key ] ?? null,
		];
	}
}
