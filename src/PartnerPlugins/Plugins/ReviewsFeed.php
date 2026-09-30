<?php

namespace WPMailSMTP\PartnerPlugins\Plugins;

use WPMailSMTP\PartnerPlugins\PartnerPlugin;

/**
 * Smash Balloon Reviews Feed.
 *
 * @since 4.10.0
 */
class ReviewsFeed extends PartnerPlugin {

	/**
	 * Catalog slug.
	 *
	 * @since 4.10.0
	 */
	const SLUG = 'reviews-feed';

	/**
	 * Product name.
	 *
	 * @since 4.10.0
	 */
	const NAME = 'Reviews Feed';

	/**
	 * Free version main file, relative to the plugins directory.
	 *
	 * @since 4.10.0
	 */
	const BASENAME = 'reviews-feed/sb-reviews.php';

	/**
	 * Where the free version is downloaded from.
	 *
	 * @since 4.10.0
	 */
	const DOWNLOAD_URL = 'https://downloads.wordpress.org/plugin/reviews-feed.zip';

	/**
	 * Drop the onboarding redirect the feed plugin arms on activation.
	 *
	 * @since 4.10.0
	 */
	public function after_activation(): void {

		delete_option( 'sbr_plugin_do_activation_redirect' );
	}
}
