<?php

namespace WPMailSMTP\PartnerPlugins;

/**
 * The partner plugins we recommend, install, or detect.
 *
 * An install cannot be offered for a plugin this catalog does not know.
 *
 * @since 4.10.0
 */
class Catalog {

	/**
	 * Every plugin, in the order the About page lists them.
	 *
	 * @since 4.10.0
	 */
	private const PLUGINS = [
		Plugins\OptinMonster::class,
		Plugins\WPForms::class,
		Plugins\MonsterInsights::class,
		Plugins\AIOSEO::class,
		Plugins\SeedProd::class,
		Plugins\RafflePress::class,
		Plugins\PushEngage::class,
		Plugins\SmashBalloonInstagramFeeds::class,
		Plugins\SmashBalloonFacebookFeeds::class,
		Plugins\SmashBalloonYouTubeFeeds::class,
		Plugins\SmashBalloonTwitterFeeds::class,
		Plugins\ReviewsFeed::class,
		Plugins\TrustPulse::class,
		Plugins\SearchWP::class,
		Plugins\AffiliateWP::class,
		Plugins\WPSimplePay::class,
		Plugins\EasyDigitalDownloads::class,
		Plugins\SugarCalendar::class,
		Plugins\Charitable::class,
		Plugins\WPCode::class,
		Plugins\Duplicator::class,
		Plugins\ActiveLayer::class,
		Plugins\WPConsent::class,
		Plugins\WPVibe::class,
		Plugins\Universally::class,
		Plugins\WPCallButton::class,
	];

	/**
	 * Instantiated plugins, keyed by slug.
	 *
	 * @since 4.10.0
	 *
	 * @var PartnerPlugin[]
	 */
	private $plugins;

	/**
	 * The site the catalog describes.
	 *
	 * @since 4.10.0
	 *
	 * @var int
	 */
	private $site_id;

	/**
	 * Constructor.
	 *
	 * @since 4.10.0
	 *
	 * @param int $site_id Site to describe, or 0 for the one the current admin screen
	 *                     administers.
	 */
	public function __construct( int $site_id = 0 ) {

		$this->site_id = $site_id;
	}

	/**
	 * Every plugin, keyed by slug.
	 *
	 * @since 4.10.0
	 *
	 * @return PartnerPlugin[]
	 */
	public function all(): array {

		if ( $this->plugins === null ) {
			$this->plugins = [];

			foreach ( self::PLUGINS as $class ) {
				$this->plugins[ $class::SLUG ] = new $class( $this->site_id );
			}
		}

		return $this->plugins;
	}

	/**
	 * One plugin by slug.
	 *
	 * @since 4.10.0
	 *
	 * @param string $slug Catalog slug.
	 *
	 * @return PartnerPlugin|null
	 */
	public function get( string $slug ): ?PartnerPlugin {

		return $this->all()[ $slug ] ?? null;
	}

	/**
	 * The plugin a main file belongs to, whether it names the free or
	 * the premium version.
	 *
	 * @since 4.10.0
	 *
	 * @param string $basename Plugin main file, relative to the plugins directory.
	 *
	 * @return PartnerPlugin|null
	 */
	public function get_by_basename( string $basename ): ?PartnerPlugin {

		if ( $basename === '' ) {
			return null;
		}

		foreach ( $this->all() as $plugin ) {
			if ( $basename === $plugin->get_basename() || $basename === $plugin->get_basename_pro() ) {
				return $plugin;
			}
		}

		return null;
	}

	/**
	 * The plugin a WordPress.org directory slug belongs to.
	 *
	 * @since 4.10.0
	 *
	 * @param string $slug WordPress.org directory slug.
	 *
	 * @return PartnerPlugin|null
	 */
	public function get_by_wporg_slug( string $slug ): ?PartnerPlugin {

		if ( $slug === '' ) {
			return null;
		}

		foreach ( $this->all() as $plugin ) {
			if ( $plugin->get_wporg_slug() === $slug ) {
				return $plugin;
			}
		}

		return null;
	}

	/**
	 * The plugin a download URL belongs to. Upgrade pages never resolve here.
	 *
	 * @since 4.10.0
	 *
	 * @param string $url Download URL.
	 *
	 * @return PartnerPlugin|null
	 */
	public function get_by_download_url( string $url ): ?PartnerPlugin {

		if ( $url === '' ) {
			return null;
		}

		foreach ( $this->all() as $plugin ) {
			if ( $url === $plugin->get_download_url() ) {
				return $plugin;
			}
		}

		return null;
	}
}
