<?php

namespace WPMailSMTP\PartnerPlugins\Plugins;

use WPMailSMTP\PartnerPlugins\PartnerPlugin;

/**
 * Smash Balloon Facebook Feeds.
 *
 * @since 4.10.0
 */
class SmashBalloonFacebookFeeds extends PartnerPlugin {

	/**
	 * Catalog slug.
	 *
	 * @since 4.10.0
	 */
	const SLUG = 'smash-balloon-facebook-feeds';

	/**
	 * Product name.
	 *
	 * @since 4.10.0
	 */
	const NAME = 'Smash Balloon Facebook Feeds';

	/**
	 * Free version main file, relative to the plugins directory.
	 *
	 * @since 4.10.0
	 */
	const BASENAME = 'custom-facebook-feed/custom-facebook-feed.php';

	/**
	 * Premium version main file, relative to the plugins directory.
	 *
	 * @since 4.10.0
	 */
	const BASENAME_PRO = 'custom-facebook-feed-pro/custom-facebook-feed.php';

	/**
	 * Where the free version is downloaded from.
	 *
	 * @since 4.10.0
	 */
	const DOWNLOAD_URL = 'https://downloads.wordpress.org/plugin/custom-facebook-feed.zip';

	/**
	 * Where the premium version is bought.
	 *
	 * @since 4.10.0
	 */
	const UPGRADE_URL = 'https://smashballoon.com/custom-facebook-feed/?utm_source=WordPress&utm_medium=about&utm_campaign=smtp';

	/**
	 * Drop the onboarding redirect the feed plugin arms on activation.
	 *
	 * @since 4.10.0
	 */
	public function after_activation(): void {

		delete_option( 'cff_plugin_do_activation_redirect' );
	}
}
