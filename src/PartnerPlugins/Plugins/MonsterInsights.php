<?php

namespace WPMailSMTP\PartnerPlugins\Plugins;

use WPMailSMTP\PartnerPlugins\PartnerPlugin;

/**
 * MonsterInsights.
 *
 * @since 4.10.0
 */
class MonsterInsights extends PartnerPlugin {

	/**
	 * Catalog slug.
	 *
	 * @since 4.10.0
	 */
	const SLUG = 'monsterinsights';

	/**
	 * Product name.
	 *
	 * @since 4.10.0
	 */
	const NAME = 'MonsterInsights';

	/**
	 * Product name of the premium version.
	 *
	 * @since 4.10.0
	 */
	const NAME_PRO = 'MonsterInsights Pro';

	/**
	 * Free version main file, relative to the plugins directory.
	 *
	 * @since 4.10.0
	 */
	const BASENAME = 'google-analytics-for-wordpress/googleanalytics.php';

	/**
	 * Premium version main file, relative to the plugins directory.
	 *
	 * @since 4.10.0
	 */
	const BASENAME_PRO = 'google-analytics-premium/googleanalytics-premium.php';

	/**
	 * Where the free version is downloaded from.
	 *
	 * @since 4.10.0
	 */
	const DOWNLOAD_URL = 'https://downloads.wordpress.org/plugin/google-analytics-for-wordpress.zip';

	/**
	 * Where the premium version is bought.
	 *
	 * @since 4.10.0
	 */
	const UPGRADE_URL = 'https://www.monsterinsights.com/?utm_source=WordPress&utm_medium=about&utm_campaign=smtp';

	/**
	 * Whether the plugin's own API is loaded and callable.
	 *
	 * @since 4.10.0
	 *
	 * @return bool
	 */
	public function is_loaded(): bool {

		return function_exists( 'MonsterInsights' );
	}

	/**
	 * Drop the welcome-screen redirect MonsterInsights sets on activation.
	 *
	 * @since 4.10.0
	 */
	public function after_activation(): void {

		delete_transient( '_monsterinsights_activation_redirect' );
	}
}
