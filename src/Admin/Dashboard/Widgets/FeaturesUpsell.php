<?php

namespace WPMailSMTP\Admin\Dashboard\Widgets;

use WPMailSMTP\Admin\Area;
use WPMailSMTP\Admin\Dashboard\WidgetState;
use WPMailSMTP\Admin\ProFeatures;

/**
 * "Take Your Emails to the Next Level" Dashboard widget.
 *
 * @since 4.10.0
 */
class FeaturesUpsell extends AbstractWidget {

	/**
	 * Column placement.
	 *
	 * @since 4.10.0
	 */
	public const COLUMN = 'main';

	/**
	 * Default sort position within the main column (below the data widgets).
	 *
	 * @since 4.10.0
	 */
	public const ORDER = 50;

	/**
	 * Get the widget identifier.
	 *
	 * @since 4.10.0
	 *
	 * @return string
	 */
	public function get_id(): string {

		return 'features_upsell';
	}

	/**
	 * Get the widget title.
	 *
	 * @since 4.10.0
	 *
	 * @return string
	 */
	public function get_title(): string {

		return esc_html__( 'Take Your Emails to the Next Level', 'wp-mail-smtp' );
	}

	/**
	 * Get the widget state.
	 *
	 * @since 4.10.0
	 *
	 * @return WidgetState
	 */
	public function get_state(): WidgetState {

		$variant = $this->access->is_pro() ? 'data' : 'education';

		if ( empty( $this->get_tiles( $variant ) ) ) {
			return new WidgetState( false );
		}

		return new WidgetState( true, $variant );
	}

	/**
	 * Render the widget head: the per-tier title and, on Lite, the discount badge
	 * shown beside it.
	 *
	 * @since 4.10.0
	 *
	 * @param string $variant Template variant.
	 * @param array  $data    Aggregated data.
	 *
	 * @return string
	 */
	protected function render_head( string $variant, array $data ): string {

		ob_start();
		?>
		<h2 class="wpms-card__title wpms-card__title--widget"><?php echo esc_html( $this->get_head_title( $variant ) ); ?></h2>
		<?php if ( $variant === 'education' ) : ?>
			<span class="wpms-dashboard-features__badge">
				<i class="wpms:icon-[custom--badge-percent] wpms:w-[16px] wpms:h-[16px]" aria-hidden="true"></i>
				<?php
				echo esc_html(
					sprintf(
						/* translators: %s: discount value, for example $50. */
						__( '%s OFF for WP Mail SMTP Lite users!', 'wp-mail-smtp' ),
						'$50'
					)
				);
				?>
			</span>
		<?php endif; ?>
		<?php

		return (string) ob_get_clean();
	}

	/**
	 * The per-tier heading text: Pro frames the widget as a feature list, Lite as
	 * an upgrade prompt.
	 *
	 * @since 4.10.0
	 *
	 * @param string $variant Template variant.
	 *
	 * @return string
	 */
	private function get_head_title( string $variant ): string {

		if ( $variant === 'education' ) {
			return esc_html__( 'Take Your Email Deliverability to the Next Level', 'wp-mail-smtp' );
		}

		return esc_html__( 'Get the Most Out of WP Mail SMTP', 'wp-mail-smtp' );
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

		$tiles = $this->get_tiles( $variant );

		if ( empty( $tiles ) ) {
			return '';
		}

		return (string) wp_mail_smtp_render(
			'dashboard/widgets/features-upsell',
			[ 'tiles' => $tiles ],
			true
		);
	}

	/**
	 * The features footer: an upgrade CTA on Lite, a link to the full settings on
	 * Pro. Both tiers share the same helper text.
	 *
	 * @since 4.10.0
	 *
	 * @param string $variant Template variant.
	 * @param array  $data    Aggregated data.
	 *
	 * @return string
	 */
	protected function render_footer( string $variant, array $data ): string {

		$is_pro = $variant === 'data';

		return (string) wp_mail_smtp_render(
			'card-footer',
			[
				'text' => esc_html__( 'Plus dozens of other powerful features to keep your WordPress emails hitting the inbox.', 'wp-mail-smtp' ),
				'cta'  => [
					'label' => $is_pro ? esc_html__( 'View All Settings', 'wp-mail-smtp' ) : esc_html__( 'Upgrade to Pro', 'wp-mail-smtp' ),
					'url'   => $is_pro
						? wp_mail_smtp()->get_admin()->get_admin_page_url()
						: wp_mail_smtp()->get_upgrade_link(
							[
								'medium'  => 'dashboard',
								'content' => 'features-widget-footer',
							]
						),
				],
			],
			true
		);
	}

	/**
	 * The feature tiles. Both tiers list the same features; the variant decides whether
	 * they are framed as an upgrade prompt or as a feature list.
	 *
	 * @since 4.10.0
	 *
	 * @param string $variant Template variant.
	 *
	 * @return array<int, array>
	 */
	protected function get_tiles( string $variant ): array {

		$tiles = ( new ProFeatures() )->get_tiles();

		if ( $variant !== 'data' ) {
			return $tiles;
		}

		// Decorated here, not at `ProFeatures::get_tiles()`: that source also feeds the shipped
		// Setup Checklist page, which has never shown these links.
		$destinations = $this->get_tile_destinations();

		foreach ( $tiles as &$tile ) {
			$destination = $destinations[ $tile['slug'] ?? '' ] ?? [];

			// A feature with no screen of its own keeps the plain upgrade-prompt tile.
			if ( empty( $destination ) ) {
				continue;
			}

			$tile['link_text'] = $destination['label'];
			$tile['link_url']  = $destination['url'];
		}
		unset( $tile );

		return $tiles;
	}

	/**
	 * Where each Pro tile's action link points, keyed by the feature slug `ProFeatures::get_tiles()`
	 * gives it. A feature with no screen of its own is absent and keeps its plain tile.
	 *
	 * @since 4.10.0
	 *
	 * @return array<string, array{label: string, url: string}>
	 */
	private function get_tile_destinations(): array {

		$admin      = wp_mail_smtp()->get_admin();
		$settings   = $admin->get_admin_page_url();
		$mailer_row = $settings . '#wp-mail-smtp-setting-row-mailer';
		$manage     = esc_html__( 'Manage', 'wp-mail-smtp' );

		return [
			'email-logging'       => [
				'label' => $manage,
				'url'   => add_query_arg( 'tab', 'logs', $settings ),
			],
			'email-alerts'        => [
				'label' => $manage,
				'url'   => add_query_arg( 'tab', 'alerts', $settings ),
			],

			// The backup connection is picked on the settings page, from the connections
			// added on the Additional Connections tab.
			'backup-connections'  => [
				'label' => $manage,
				'url'   => $settings . '#wp-mail-smtp-setting-row-backup_connection',
			],
			'one-click-setup'     => [
				'label' => $manage,
				'url'   => $mailer_row,
			],
			'advanced-mailers'    => [
				'label' => $manage,
				'url'   => $mailer_row,
			],

			// Email Reports is a page of its own, with no tabs to land on.
			'email-reports'       => [
				'label' => $manage,
				'url'   => $admin->get_admin_page_url( Area::SLUG . '-reports' ),
			],

			// The open and click toggles live with the email log.
			'open-click-tracking' => [
				'label' => $manage,
				'url'   => add_query_arg( 'tab', 'logs', $settings ),
			],
			'wp-notifications'    => [
				'label' => $manage,
				'url'   => add_query_arg( 'tab', 'control', $settings ),
			],
			'smart-routing'       => [
				'label' => $manage,
				'url'   => add_query_arg( 'tab', 'routing', $settings ),
			],

			// Rate limiting is off by default, so this tile enables it rather than
			// managing it.
			'rate-limiting'       => [
				'label' => esc_html__( 'Enable', 'wp-mail-smtp' ),
				'url'   => add_query_arg( 'tab', 'misc', $settings ) . '#wp-mail-smtp-setting-row-rate_limit',
			],
			'log-export'          => [
				'label' => $manage,
				'url'   => add_query_arg( 'tab', 'export', $admin->get_admin_page_url( Area::SLUG . '-tools' ) ),
			],
		];
	}
}
