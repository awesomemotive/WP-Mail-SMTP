<?php

namespace WPMailSMTP\PartnerPlugins\Plugins;

use WPMailSMTP\PartnerPlugins\PartnerPlugin;

/**
 * Easy Digital Downloads.
 *
 * @since 4.10.0
 */
class EasyDigitalDownloads extends PartnerPlugin {

	/**
	 * Catalog slug.
	 *
	 * @since 4.10.0
	 */
	const SLUG = 'easy-digital-downloads';

	/**
	 * Product name.
	 *
	 * @since 4.10.0
	 */
	const NAME = 'Easy Digital Downloads';

	/**
	 * Free version main file, relative to the plugins directory.
	 *
	 * @since 4.10.0
	 */
	const BASENAME = 'easy-digital-downloads/easy-digital-downloads.php';

	/**
	 * Where the free version is downloaded from.
	 *
	 * @since 4.10.0
	 */
	const DOWNLOAD_URL = 'https://downloads.wordpress.org/plugin/easy-digital-downloads.zip';
}
