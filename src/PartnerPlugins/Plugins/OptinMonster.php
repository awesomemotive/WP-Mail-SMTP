<?php

namespace WPMailSMTP\PartnerPlugins\Plugins;

use WPMailSMTP\PartnerPlugins\PartnerPlugin;

/**
 * OptinMonster.
 *
 * @since 4.10.0
 */
class OptinMonster extends PartnerPlugin {

	/**
	 * Catalog slug.
	 *
	 * @since 4.10.0
	 */
	const SLUG = 'optinmonster';

	/**
	 * Product name.
	 *
	 * @since 4.10.0
	 */
	const NAME = 'OptinMonster';

	/**
	 * Free version main file, relative to the plugins directory.
	 *
	 * @since 4.10.0
	 */
	const BASENAME = 'optinmonster/optin-monster-wp-api.php';

	/**
	 * Where the free version is downloaded from.
	 *
	 * @since 4.10.0
	 */
	const DOWNLOAD_URL = 'https://downloads.wordpress.org/plugin/optinmonster.zip';

	/**
	 * Drop the welcome-screen redirect OptinMonster arms on activation.
	 *
	 * @since 4.10.0
	 */
	public function after_activation(): void {

		delete_transient( 'optin_monster_api_activation_redirect' );
	}
}
