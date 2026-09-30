<?php

namespace WPMailSMTP\PartnerPlugins\Plugins;

use WPMailSMTP\PartnerPlugins\PartnerPlugin;

/**
 * SeedProd.
 *
 * @since 4.10.0
 */
class SeedProd extends PartnerPlugin {

	/**
	 * Catalog slug.
	 *
	 * @since 4.10.0
	 */
	const SLUG = 'seedprod';

	/**
	 * Product name.
	 *
	 * @since 4.10.0
	 */
	const NAME = 'SeedProd';

	/**
	 * Free version main file, relative to the plugins directory.
	 *
	 * @since 4.10.0
	 */
	const BASENAME = 'coming-soon/coming-soon.php';

	/**
	 * Premium version main file, relative to the plugins directory.
	 *
	 * @since 4.10.0
	 */
	const BASENAME_PRO = 'seedprod-coming-soon-pro-5/seedprod-coming-soon-pro-5.php';

	/**
	 * Where the free version is downloaded from.
	 *
	 * @since 4.10.0
	 */
	const DOWNLOAD_URL = 'https://downloads.wordpress.org/plugin/coming-soon.zip';

	/**
	 * Where the premium version is bought.
	 *
	 * @since 4.10.0
	 */
	const UPGRADE_URL = 'https://www.seedprod.com/?utm_source=WordPress&utm_medium=about&utm_campaign=smtp';

	/**
	 * Whether the plugin's own API is loaded and callable.
	 *
	 * @since 4.10.0
	 *
	 * @return bool
	 */
	public function is_loaded(): bool {

		return defined( 'SEEDPROD_BUILD' );
	}

	/**
	 * Drop the welcome-screen redirect SeedProd sets on activation.
	 *
	 * @since 4.10.0
	 */
	public function after_activation(): void {

		delete_transient( '_seedprod_welcome_screen_activation_redirect' );
	}
}
