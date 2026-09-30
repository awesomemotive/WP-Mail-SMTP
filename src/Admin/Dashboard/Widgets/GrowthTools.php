<?php

namespace WPMailSMTP\Admin\Dashboard\Widgets;

use WPMailSMTP\Admin\Dashboard\WidgetState;
use WPMailSMTP\PartnerPlugins\Catalog;

/**
 * "Recommended Growth Tools" sidebar widget.
 *
 * @since 4.10.0
 */
class GrowthTools extends AbstractWidget {

	/**
	 * Column placement.
	 *
	 * @since 4.10.0
	 */
	public const COLUMN = 'sidebar';

	/**
	 * Default sort position within the sidebar.
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

		return 'growth_tools';
	}

	/**
	 * Get the widget title.
	 *
	 * @since 4.10.0
	 *
	 * @return string
	 */
	public function get_title(): string {

		return esc_html__( 'Recommended Growth Tools', 'wp-mail-smtp' );
	}

	/**
	 * Get the widget state.
	 *
	 * @since 4.10.0
	 *
	 * @return WidgetState
	 */
	public function get_state(): WidgetState {

		return new WidgetState( ! empty( $this->get_tiles() ), 'data' );
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

		$tiles = $this->get_tiles();

		if ( empty( $tiles ) ) {
			return '';
		}

		return (string) wp_mail_smtp_render(
			'dashboard/widgets/growth-tools',
			[ 'tiles' => $tiles ],
			true
		);
	}

	/**
	 * The recommended growth-tool tiles.
	 *
	 * @since 4.10.0
	 *
	 * @return array<int, array>
	 */
	protected function get_tiles(): array {

		$catalog = new Catalog();
		$tiles   = [];

		foreach ( $this->get_tools() as $slug => $tool ) {
			$plugin = $catalog->get( $slug );

			if ( $plugin === null ) {
				continue;
			}

			$cta = $plugin->get_install_cta();

			$tiles[] = [
				'image'         => $tool['icon'],
				'title'         => $plugin->get_name(),
				'description'   => $tool['description'],
				'link_text'     => $cta['text'],
				'link_url'      => $cta['url'],
				'link_action'   => $cta['action'],
				'link_plugin'   => $cta['plugin'],
			];
		}

		return $tiles;
	}

	/**
	 * The plugins this widget recommends, keyed by their catalog slug.
	 *
	 * @since 4.10.0
	 *
	 * @return array<string, array>
	 */
	protected function get_tools(): array {

		return [
			'aioseo'       => [
				'icon'        => 'setup-checklist/brand-aioseo.svg',
				'description' => __( 'Improve SEO rankings with AI tools and get valuable insights.', 'wp-mail-smtp' ),
			],
			'universally'  => [
				'icon'        => 'setup-checklist/brand-universally.svg',
				'description' => __( 'Translate your website into 110+ languages quickly with AI.', 'wp-mail-smtp' ),
			],
			'duplicator'   => [
				'icon'        => 'setup-checklist/brand-duplicator.svg',
				'description' => __( 'Fast, secure WordPress backups and migrations.', 'wp-mail-smtp' ),
			],
			'reviews-feed' => [
				'icon'        => 'setup-checklist/brand-smashballoon.svg',
				'description' => __( 'Customer reviews from Google, Yelp, and more to boost sales.', 'wp-mail-smtp' ),
			],
		];
	}
}
