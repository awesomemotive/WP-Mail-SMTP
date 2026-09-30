<?php

namespace WPMailSMTP\SetupChecklist;

use WPMailSMTP\PartnerPlugins\Catalog;

/**
 * The partner plugins the Setup Checklist recommends, as render-ready tiles.
 *
 * @since 4.10.0
 */
class GrowthTools {

	/**
	 * Partner plugin catalog.
	 *
	 * @since 4.10.0
	 *
	 * @var Catalog
	 */
	private $catalog;

	/**
	 * Constructor.
	 *
	 * @since 4.10.0
	 *
	 * @param Catalog $catalog Partner plugin catalog.
	 */
	public function __construct( Catalog $catalog ) {

		$this->catalog = $catalog;
	}

	/**
	 * Render-ready tiles, one per recommended plugin the catalog knows.
	 *
	 * @since 4.10.0
	 *
	 * @return array<int, array>
	 */
	public function get_tiles(): array {

		$tiles = [];

		foreach ( $this->get_tools() as $slug => $tool ) {
			$plugin = $this->catalog->get( $slug );

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
	 * The plugins this section recommends, keyed by their catalog slug.
	 *
	 * @since 4.10.0
	 *
	 * @return array<string, array>
	 */
	private function get_tools(): array {

		return [
			'aioseo'          => [
				'icon'        => 'setup-checklist/brand-aioseo.svg',
				'description' => __( 'Improve SEO rankings with AI tools and get valuable insights.', 'wp-mail-smtp' ),
			],
			'universally'     => [
				'icon'        => 'setup-checklist/brand-universally.svg',
				'description' => __( 'Translate your website into 110+ languages quickly with AI.', 'wp-mail-smtp' ),
			],
			'duplicator'      => [
				'icon'        => 'setup-checklist/brand-duplicator.svg',
				'description' => __( 'Fast, secure WordPress backups and migrations.', 'wp-mail-smtp' ),
			],
			'reviews-feed'    => [
				'icon'        => 'setup-checklist/brand-smashballoon.svg',
				'description' => __( 'Customer reviews from Google, Yelp, and more to boost sales.', 'wp-mail-smtp' ),
			],
			'optinmonster'    => [
				'icon'        => 'setup-checklist/brand-optinmonster.svg',
				'description' => __( 'Get more email subscribers & sales with the #1 CRO toolkit for WordPress.', 'wp-mail-smtp' ),
			],
			'monsterinsights' => [
				'icon'        => 'setup-checklist/brand-monsterinsights.svg',
				'description' => __( 'Website analytics made easy for WordPress. Form tracking, reports, and more.', 'wp-mail-smtp' ),
			],
		];
	}
}
