<?php

namespace WPMailSMTP\PartnerPlugins\Plugins;

use WPMailSMTP\PartnerPlugins\PartnerPlugin;

/**
 * All in One SEO.
 *
 * @since 4.10.0
 */
class AIOSEO extends PartnerPlugin {

	/**
	 * Catalog slug.
	 *
	 * @since 4.10.0
	 */
	const SLUG = 'aioseo';

	/**
	 * Product name.
	 *
	 * @since 4.10.0
	 */
	const NAME = 'AIOSEO';

	/**
	 * Free version main file, relative to the plugins directory.
	 *
	 * @since 4.10.0
	 */
	const BASENAME = 'all-in-one-seo-pack/all_in_one_seo_pack.php';

	/**
	 * Premium version main file, relative to the plugins directory.
	 *
	 * @since 4.10.0
	 */
	const BASENAME_PRO = 'all-in-one-seo-pack-pro/all_in_one_seo_pack.php';

	/**
	 * Where the free version is downloaded from.
	 *
	 * @since 4.10.0
	 */
	const DOWNLOAD_URL = 'https://downloads.wordpress.org/plugin/all-in-one-seo-pack.zip';

	/**
	 * Where the premium version is bought.
	 *
	 * @since 4.10.0
	 */
	const UPGRADE_URL = 'https://aioseo.com/?utm_source=WordPress&utm_medium=about&utm_campaign=smtp';

	/**
	 * Whether the plugin's own API is loaded and callable.
	 *
	 * @since 4.10.0
	 *
	 * @return bool
	 */
	public function is_loaded(): bool {

		return class_exists( 'AIOSEOP_Core' );
	}

	/**
	 * Pre-empt the welcome screen AIOSEO redirects to on activation.
	 *
	 * @since 4.10.0
	 */
	public function before_activation(): void {

		update_option( 'aioseo_activation_redirect', true );
	}
}
