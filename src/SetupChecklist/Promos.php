<?php

namespace WPMailSMTP\SetupChecklist;

use WPMailSMTP\PartnerPlugins\PartnerPlugin;

/**
 * Promo sections model for the Setup Checklist.
 *
 * @since 4.10.0
 */
class Promos {

	/**
	 * The single upgrade CTA for the Pro-features section.
	 *
	 * @since 4.10.0
	 *
	 * @return array
	 */
	public function upgrade_cta() {

		return [
			'label' => esc_html__( 'Upgrade to Pro', 'wp-mail-smtp' ),
			'url'   => wp_mail_smtp()->get_upgrade_link(
				[
					'medium'  => 'setup-checklist',
					'content' => 'Upgrade to Pro',
				]
			),
			'text'  => esc_html__( 'Plus dozens of other powerful features to keep your WordPress emails hitting the inbox.', 'wp-mail-smtp' ),
			'chip'  => sprintf(
				/* translators: %s: the discount amount, emphasised. */
				esc_html__( '%s for WP Mail SMTP Lite users!', 'wp-mail-smtp' ),
				'<strong class="wpms-card__footer-chip-amount">' . esc_html__( '$50 OFF', 'wp-mail-smtp' ) . '</strong>'
			),
		];
	}

	/**
	 * Install state of a recommended plugin, including whether it still needs
	 * setting up.
	 *
	 * @since 4.10.0
	 *
	 * @param PartnerPlugin $plugin Partner plugin.
	 *
	 * @return array `state` is one of install, activate, setup or done; `setup_url` is set for setup.
	 */
	public function recommended_plugin_state( PartnerPlugin $plugin ): array {

		$state = $plugin->get_install_state();

		if ( $state === PartnerPlugin::STATE_NOT_INSTALLED ) {
			return [ 'state' => 'install' ];
		}

		if ( $state === PartnerPlugin::STATE_INACTIVE ) {
			return [ 'state' => 'activate' ];
		}

		$setup_url = $plugin->get_setup_url();

		if ( $plugin->is_configured() || $setup_url === '' ) {
			return [ 'state' => 'done' ];
		}

		return [
			'state'     => 'setup',
			'setup_url' => $setup_url,
		];
	}

	/**
	 * Render a promo section header: title, optional subtitle, and collapse toggle.
	 *
	 * @since 4.10.0
	 *
	 * @param string $body_id  ID of the collapsible body the toggle controls.
	 * @param string $title    Promo title.
	 * @param string $subtitle Optional promo subtitle.
	 */
	public function render_header( string $body_id, string $title, string $subtitle = '' ): void {

		?>
		<div class="wpms-card__head wpms-setup-checklist-promo__header">
			<h2 class="wpms-card__title"><?php echo esc_html( $title ); ?></h2>
			<?php if ( $subtitle !== '' ) : ?>
				<span class="wpms-setup-checklist-promo__subtitle"><?php echo esc_html( $subtitle ); ?></span>
			<?php endif; ?>
			<button type="button" class="wpms-icon-btn wpms-setup-checklist-promo__toggle" aria-expanded="true" aria-controls="<?php echo esc_attr( $body_id ); ?>">
				<i class="wpms-setup-checklist-toggle-icon wpms:icon-[fa6-solid--chevron-down] wpms:w-[12px] wpms:h-[12px]" aria-hidden="true"></i>
				<span class="screen-reader-text"><?php esc_html_e( 'Toggle section', 'wp-mail-smtp' ); ?></span>
			</button>
		</div>
		<?php
	}

	/**
	 * Render the promo footer bar: discount chip + the Upgrade to Pro CTA.
	 *
	 * @since 4.10.0
	 *
	 * @param array $cta CTA data: `label`, `url`, `text` (the helper copy), and `chip`
	 *                   (the Lite discount chip markup).
	 */
	public function render_footer( array $cta ): void {

		$cta_url = $cta['url'] ?? '#';

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by the template.
		echo wp_mail_smtp_render(
			'card-footer',
			[
				'text' => $cta['text'] ?? '',
				'chip' => $cta['chip'] ?? '',
				'cta'  => [
					'label'        => $cta['label'] ?? '',
					'url'          => $cta_url,
					'target_blank' => wp_parse_url( $cta_url, PHP_URL_HOST ) !== wp_parse_url( admin_url(), PHP_URL_HOST ),
				],
			],
			true
		);
	}
}
