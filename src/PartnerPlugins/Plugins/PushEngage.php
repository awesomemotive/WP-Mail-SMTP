<?php

namespace WPMailSMTP\PartnerPlugins\Plugins;

use WPMailSMTP\PartnerPlugins\PartnerPlugin;

/**
 * PushEngage.
 *
 * @since 4.10.0
 */
class PushEngage extends PartnerPlugin {

	/**
	 * Catalog slug.
	 *
	 * @since 4.10.0
	 */
	const SLUG = 'pushengage';

	/**
	 * Product name.
	 *
	 * @since 4.10.0
	 */
	const NAME = 'PushEngage';

	/**
	 * Free version main file, relative to the plugins directory.
	 *
	 * @since 4.10.0
	 */
	const BASENAME = 'pushengage/main.php';

	/**
	 * Where the free version is downloaded from.
	 *
	 * @since 4.10.0
	 */
	const DOWNLOAD_URL = 'https://downloads.wordpress.org/plugin/pushengage.zip';

	/**
	 * Drop the onboarding redirect PushEngage arms on activation.
	 *
	 * @since 4.10.0
	 */
	public function after_activation(): void {

		delete_transient( 'pushengage_activation_redirect' );
	}
}
