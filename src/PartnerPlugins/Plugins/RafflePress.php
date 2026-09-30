<?php

namespace WPMailSMTP\PartnerPlugins\Plugins;

use WPMailSMTP\PartnerPlugins\PartnerPlugin;

/**
 * RafflePress.
 *
 * @since 4.10.0
 */
class RafflePress extends PartnerPlugin {

	/**
	 * Catalog slug.
	 *
	 * @since 4.10.0
	 */
	const SLUG = 'rafflepress';

	/**
	 * Product name.
	 *
	 * @since 4.10.0
	 */
	const NAME = 'RafflePress';

	/**
	 * Product name of the premium version.
	 *
	 * @since 4.10.0
	 */
	const NAME_PRO = 'RafflePress Pro';

	/**
	 * Free version main file, relative to the plugins directory.
	 *
	 * @since 4.10.0
	 */
	const BASENAME = 'rafflepress/rafflepress.php';

	/**
	 * Premium version main file, relative to the plugins directory.
	 *
	 * @since 4.10.0
	 */
	const BASENAME_PRO = 'rafflepress-pro/rafflepress-pro.php';

	/**
	 * Where the free version is downloaded from.
	 *
	 * @since 4.10.0
	 */
	const DOWNLOAD_URL = 'https://downloads.wordpress.org/plugin/rafflepress.zip';

	/**
	 * Where the premium version is bought.
	 *
	 * @since 4.10.0
	 */
	const UPGRADE_URL = 'https://rafflepress.com/pricing/?utm_source=WordPress&utm_medium=about&utm_campaign=smtp';

	/**
	 * Whether the plugin's own API is loaded and callable.
	 *
	 * @since 4.10.0
	 *
	 * @return bool
	 */
	public function is_loaded(): bool {

		return defined( 'RAFFLEPRESS_BUILD' );
	}

	/**
	 * Drop the welcome-screen redirect RafflePress sets on activation.
	 *
	 * @since 4.10.0
	 */
	public function after_activation(): void {

		delete_transient( '_rafflepress_welcome_screen_activation_redirect' );
	}
}
