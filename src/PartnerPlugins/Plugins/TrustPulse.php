<?php

namespace WPMailSMTP\PartnerPlugins\Plugins;

use WPMailSMTP\PartnerPlugins\PartnerPlugin;

/**
 * TrustPulse.
 *
 * @since 4.10.0
 */
class TrustPulse extends PartnerPlugin {

	/**
	 * Catalog slug.
	 *
	 * @since 4.10.0
	 */
	const SLUG = 'trustpulse';

	/**
	 * Product name.
	 *
	 * @since 4.10.0
	 */
	const NAME = 'TrustPulse';

	/**
	 * Free version main file, relative to the plugins directory.
	 *
	 * @since 4.10.0
	 */
	const BASENAME = 'trustpulse-api/trustpulse.php';

	/**
	 * Where the free version is downloaded from.
	 *
	 * @since 4.10.0
	 */
	const DOWNLOAD_URL = 'https://downloads.wordpress.org/plugin/trustpulse-api.zip';
}
