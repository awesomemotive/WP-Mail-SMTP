<?php

namespace WPMailSMTP\PartnerPlugins\Plugins;

use WPMailSMTP\PartnerPlugins\PartnerPlugin;

/**
 * SearchWP. Premium only: there is no free version, so nothing to install.
 *
 * @since 4.10.0
 */
class SearchWP extends PartnerPlugin {

	/**
	 * Catalog slug.
	 *
	 * @since 4.10.0
	 */
	const SLUG = 'searchwp';

	/**
	 * Product name.
	 *
	 * @since 4.10.0
	 */
	const NAME = 'SearchWP';

	/**
	 * Premium version main file, relative to the plugins directory.
	 *
	 * @since 4.10.0
	 */
	const BASENAME_PRO = 'searchwp/index.php';

	/**
	 * Where the premium version is bought.
	 *
	 * @since 4.10.0
	 */
	const UPGRADE_URL = 'https://searchwp.com/?utm_source=WordPress&utm_medium=about&utm_campaign=smtp';
}
