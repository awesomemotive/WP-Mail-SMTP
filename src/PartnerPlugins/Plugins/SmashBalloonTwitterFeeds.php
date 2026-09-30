<?php

namespace WPMailSMTP\PartnerPlugins\Plugins;

use WPMailSMTP\PartnerPlugins\PartnerPlugin;

/**
 * Smash Balloon Twitter Feeds.
 *
 * @since 4.10.0
 */
class SmashBalloonTwitterFeeds extends PartnerPlugin {

	/**
	 * Catalog slug.
	 *
	 * @since 4.10.0
	 */
	const SLUG = 'smash-balloon-twitter-feeds';

	/**
	 * Product name.
	 *
	 * @since 4.10.0
	 */
	const NAME = 'Smash Balloon Twitter Feeds';

	/**
	 * Free version main file, relative to the plugins directory.
	 *
	 * @since 4.10.0
	 */
	const BASENAME = 'custom-twitter-feeds/custom-twitter-feed.php';

	/**
	 * Premium version main file, relative to the plugins directory.
	 *
	 * @since 4.10.0
	 */
	const BASENAME_PRO = 'custom-twitter-feeds-pro/custom-twitter-feed.php';

	/**
	 * Where the free version is downloaded from.
	 *
	 * @since 4.10.0
	 */
	const DOWNLOAD_URL = 'https://downloads.wordpress.org/plugin/custom-twitter-feeds.zip';

	/**
	 * Where the premium version is bought.
	 *
	 * @since 4.10.0
	 */
	const UPGRADE_URL = 'https://smashballoon.com/custom-twitter-feeds/?utm_source=WordPress&utm_medium=about&utm_campaign=smtp';

	/**
	 * Drop the onboarding redirect the feed plugin arms on activation.
	 *
	 * @since 4.10.0
	 */
	public function after_activation(): void {

		delete_option( 'ctf_plugin_do_activation_redirect' );
	}
}
