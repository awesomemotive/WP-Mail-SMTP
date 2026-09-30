<?php

namespace WPMailSMTP\Admin\Dashboard\Widgets;

use WPMailSMTP\Admin\Dashboard\WidgetState;

/**
 * The version strip: product name, installed version, and a per-tier action.
 *
 * @since 4.10.0
 */
class Version extends AbstractWidget {

	/**
	 * Column placement: sidebar, topmost.
	 *
	 * @since 4.10.0
	 */
	public const COLUMN = 'sidebar';

	/**
	 * Default sort position within the sidebar.
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

		return 'version';
	}

	/**
	 * Get the widget title. Empty: the strip renders the product name in its body,
	 * not as a widget heading.
	 *
	 * @since 4.10.0
	 *
	 * @return string
	 */
	public function get_title(): string {

		return '';
	}

	/**
	 * Get the widget state.
	 *
	 * @since 4.10.0
	 *
	 * @return WidgetState
	 */
	public function get_state(): WidgetState {

		return new WidgetState( true, $this->access->is_pro() ? 'data' : 'education' );
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

		$license_cta = $this->get_license_cta();

		return (string) wp_mail_smtp_render(
			'dashboard/widgets/version',
			[
				'variant'        => $variant,
				'product_name'   => $this->get_product_name( $variant ),
				'version'        => WPMS_PLUGIN_VER,
				'license_label'  => isset( $license_cta['label'] ) ? $license_cta['label'] : '',
				'license_url'    => isset( $license_cta['url'] ) ? $license_cta['url'] : '',
				'license_notice' => isset( $license_cta['notice'] ) ? $license_cta['notice'] : '',
				'license_tone'   => isset( $license_cta['tone'] ) ? $license_cta['tone'] : '',
				'update_url'     => $this->get_update_url(),
				'upgrade_url'    => wp_mail_smtp()->get_upgrade_link(
					[
						'medium'  => 'dashboard',
						'content' => 'version-strip-upgrade',
					]
				),
				'whats_new_url'  => wp_mail_smtp()->get_utm_url(
					'https://wpmailsmtp.com/docs/how-to-view-recent-changes-to-the-wp-mail-smtp-plugin-changelog',
					[
						'medium'  => 'dashboard',
						'content' => 'version-strip-whats-new',
					]
				),
			],
			true
		);
	}

	/**
	 * The license CTA shown in place of the What's New link. Empty on a tier with no
	 * license to act on.
	 *
	 * @since 4.10.0
	 *
	 * @return array Either empty, or `[ 'label' => string, 'url' => string, 'notice' => string, 'tone' => string ]`.
	 */
	protected function get_license_cta(): array {

		return [];
	}

	/**
	 * Where the update variant's CTA goes. Empty on a tier with no update to offer.
	 *
	 * @since 4.10.0
	 *
	 * @return string
	 */
	protected function get_update_url(): string {

		return '';
	}

	/**
	 * The displayed product name. Lite and Pro ship different strings, and only Lite
	 * renders the education variant, so the name follows it.
	 *
	 * @since 4.10.0
	 *
	 * @param string $variant Template variant.
	 *
	 * @return string
	 */
	private function get_product_name( string $variant ): string {

		return $variant === 'education'
			? esc_html__( 'WP Mail SMTP Lite', 'wp-mail-smtp' )
			: esc_html__( 'WP Mail SMTP Pro', 'wp-mail-smtp' );
	}
}
