<?php

namespace WPMailSMTP\PartnerPlugins\Plugins;

use WPMailSMTP\PartnerPlugins\PartnerPlugin;

/**
 * Smash Balloon Instagram Feeds.
 *
 * @since 4.10.0
 */
class SmashBalloonInstagramFeeds extends PartnerPlugin {

	/**
	 * Catalog slug.
	 *
	 * @since 4.10.0
	 */
	const SLUG = 'smash-balloon-instagram-feeds';

	/**
	 * Product name.
	 *
	 * @since 4.10.0
	 */
	const NAME = 'Smash Balloon Instagram Feeds';

	/**
	 * Free version main file, relative to the plugins directory.
	 *
	 * @since 4.10.0
	 */
	const BASENAME = 'instagram-feed/instagram-feed.php';

	/**
	 * Premium version main file, relative to the plugins directory.
	 *
	 * @since 4.10.0
	 */
	const BASENAME_PRO = 'instagram-feed-pro/instagram-feed.php';

	/**
	 * Where the free version is downloaded from.
	 *
	 * @since 4.10.0
	 */
	const DOWNLOAD_URL = 'https://downloads.wordpress.org/plugin/instagram-feed.zip';

	/**
	 * Where the premium version is bought.
	 *
	 * @since 4.10.0
	 */
	const UPGRADE_URL = 'https://smashballoon.com/instagram-feed/?utm_source=WordPress&utm_medium=about&utm_campaign=smtp';

	/**
	 * Whether the plugin's own API is loaded and callable.
	 *
	 * @since 4.10.0
	 *
	 * @return bool
	 */
	public function is_loaded(): bool {

		return function_exists( 'sb_instagram_feed_init' );
	}

	/**
	 * Drop the onboarding redirect the feed plugin arms on activation.
	 *
	 * @since 4.10.0
	 */
	public function after_activation(): void {

		delete_option( 'sbi_plugin_do_activation_redirect' );
	}
}
