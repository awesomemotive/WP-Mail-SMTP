<?php

namespace WPMailSMTP\Admin\Dashboard;

// wpms:icon-[fa6-solid--bell] is built from an `icon` value, so it is named here for
// Tailwind's scanner, which never sees it.
/**
 * The dismissible cards that appear inside a widget's body, under its content.
 *
 * @since 4.10.0
 */
trait InlineCards {

	/**
	 * Resolve the cards this host should show: the condition holds, the remedy is not
	 * already in place, and the user has not dismissed it.
	 *
	 * @since 4.10.0
	 *
	 * @param array $data Aggregated widget data.
	 *
	 * @return array
	 */
	protected function get_inline_cards( array $data ): array {

		return $this->prepare_inline_cards( [ $this->get_email_alerts_card( $data ) ] );
	}

	/**
	 * Reduce a host's candidate cards to the ones it may show.
	 *
	 * @since 4.10.0
	 *
	 * @param array $cards Candidate cards, empty entries allowed.
	 *
	 * @return array
	 */
	protected function prepare_inline_cards( array $cards ): array {

		$dismissals = $this->access->get_dismissals();

		$cards = array_filter(
			array_filter( $cards ),
			static function ( $card ) use ( $dismissals ) {

				return ! isset( $dismissals[ $card['id'] ] );
			}
		);

		return array_values( $cards );
	}

	/**
	 * Render the resolved cards.
	 *
	 * @since 4.10.0
	 *
	 * @param array $cards Cards from get_inline_cards().
	 *
	 * @return string
	 */
	protected function render_inline_cards( array $cards ): string {

		if ( empty( $cards ) ) {
			return '';
		}

		$html = '';

		foreach ( $cards as $card ) {
			$html .= (string) wp_mail_smtp_render(
				'dashboard/inline-card',
				[ 'card' => $card ],
				true
			);
		}

		return $html;
	}

	/**
	 * The "Set Up Email Alerts" card: shows once the failed-email threshold is
	 * exceeded and no alert channel is configured.
	 *
	 * @since 4.10.0
	 *
	 * @param array $data Aggregated data.
	 *
	 * @return array Empty when the card does not apply.
	 */
	protected function get_email_alerts_card( array $data ): array {

		if ( empty( $data['has_high_failure_rate'] ) || $this->is_alerts_configured() ) {
			return [];
		}

		return [
			'id'    => 'email_alerts',
			'icon'  => 'fa6-solid--bell',
			'title' => esc_html__( 'Set Up Email Alerts', 'wp-mail-smtp' ),
			'text'  => esc_html__( 'A large share of your recent emails failed to send. Set up alerts to get notified the next time it happens.', 'wp-mail-smtp' ),
			'cta'   => $this->access->is_pro() ? $this->get_email_alerts_cta() : $this->get_email_alerts_upgrade_cta(),
		];
	}

	/**
	 * The email-alerts card CTA, linking to the alerts settings tab.
	 *
	 * @since 4.10.0
	 *
	 * @return array
	 */
	protected function get_email_alerts_cta(): array {

		return [
			'label'        => esc_html__( 'Set Up Alerts', 'wp-mail-smtp' ),
			'url'          => add_query_arg( 'tab', 'alerts', wp_mail_smtp()->get_admin()->get_admin_page_url() ),
			'target_blank' => false,
		];
	}

	/**
	 * The email-alerts card CTA on Lite, linking to the Pro upgrade instead.
	 *
	 * @since 4.10.0
	 *
	 * @return array
	 */
	protected function get_email_alerts_upgrade_cta(): array {

		return [
			'label'        => esc_html__( 'Get Email Alerts with Pro', 'wp-mail-smtp' ),
			'url'          => wp_mail_smtp()->get_upgrade_link(
				[
					'medium'  => 'dashboard',
					'content' => 'alerts-card',
				]
			),
			'target_blank' => true,
		];
	}

	/**
	 * Whether at least one alert channel is configured. Always false on Lite,
	 * which has no alert channels.
	 *
	 * @since 4.10.0
	 *
	 * @return bool
	 */
	protected function is_alerts_configured() {

		return false;
	}
}
