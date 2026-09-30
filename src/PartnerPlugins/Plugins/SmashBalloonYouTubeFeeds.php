<?php

namespace WPMailSMTP\PartnerPlugins\Plugins;

use WPMailSMTP\PartnerPlugins\PartnerPlugin;

/**
 * Smash Balloon YouTube Feeds.
 *
 * @since 4.10.0
 */
class SmashBalloonYouTubeFeeds extends PartnerPlugin {

	/**
	 * Catalog slug.
	 *
	 * @since 4.10.0
	 */
	const SLUG = 'smash-balloon-youtube-feeds';

	/**
	 * Product name.
	 *
	 * @since 4.10.0
	 */
	const NAME = 'Smash Balloon YouTube Feeds';

	/**
	 * Free version main file, relative to the plugins directory.
	 *
	 * @since 4.10.0
	 */
	const BASENAME = 'feeds-for-youtube/youtube-feed.php';

	/**
	 * Premium version main file, relative to the plugins directory.
	 *
	 * @since 4.10.0
	 */
	const BASENAME_PRO = 'youtube-feed-pro/youtube-feed.php';

	/**
	 * Where the free version is downloaded from.
	 *
	 * @since 4.10.0
	 */
	const DOWNLOAD_URL = 'https://downloads.wordpress.org/plugin/feeds-for-youtube.zip';

	/**
	 * Where the premium version is bought.
	 *
	 * @since 4.10.0
	 */
	const UPGRADE_URL = 'https://smashballoon.com/youtube-feed/?utm_source=WordPress&utm_medium=about&utm_campaign=smtp';

	/**
	 * Drop the onboarding redirect the feed plugin arms on activation.
	 *
	 * @since 4.10.0
	 */
	public function after_activation(): void {

		delete_option( 'sby_plugin_do_activation_redirect' );
	}
}
