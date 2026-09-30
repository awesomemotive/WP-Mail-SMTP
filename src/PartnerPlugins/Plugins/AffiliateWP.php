<?php

namespace WPMailSMTP\PartnerPlugins\Plugins;

use WPMailSMTP\PartnerPlugins\PartnerPlugin;

/**
 * AffiliateWP. Premium only: there is no free version, so nothing to install.
 *
 * @since 4.10.0
 */
class AffiliateWP extends PartnerPlugin {

	/**
	 * Catalog slug.
	 *
	 * @since 4.10.0
	 */
	const SLUG = 'affiliatewp';

	/**
	 * Product name.
	 *
	 * @since 4.10.0
	 */
	const NAME = 'AffiliateWP';

	/**
	 * Premium version main file, relative to the plugins directory.
	 *
	 * @since 4.10.0
	 */
	const BASENAME_PRO = 'affiliate-wp/affiliate-wp.php';

	/**
	 * Where the premium version is bought.
	 *
	 * @since 4.10.0
	 */
	const UPGRADE_URL = 'https://affiliatewp.com/?utm_source=WordPress&utm_medium=about&utm_campaign=smtp';
}
