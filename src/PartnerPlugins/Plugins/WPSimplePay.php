<?php

namespace WPMailSMTP\PartnerPlugins\Plugins;

use WPMailSMTP\PartnerPlugins\PartnerPlugin;

/**
 * WP Simple Pay.
 *
 * @since 4.10.0
 */
class WPSimplePay extends PartnerPlugin {

	/**
	 * Catalog slug.
	 *
	 * @since 4.10.0
	 */
	const SLUG = 'wp-simple-pay';

	/**
	 * Product name.
	 *
	 * @since 4.10.0
	 */
	const NAME = 'WP Simple Pay';

	/**
	 * Product name of the premium version.
	 *
	 * @since 4.10.0
	 */
	const NAME_PRO = 'WP Simple Pay Pro';

	/**
	 * Free version main file, relative to the plugins directory.
	 *
	 * @since 4.10.0
	 */
	const BASENAME = 'stripe/stripe-checkout.php';

	/**
	 * Premium version main file, relative to the plugins directory.
	 *
	 * @since 4.10.0
	 */
	const BASENAME_PRO = 'wp-simple-pay-pro-3/simple-pay.php';

	/**
	 * Where the free version is downloaded from.
	 *
	 * @since 4.10.0
	 */
	const DOWNLOAD_URL = 'https://downloads.wordpress.org/plugin/stripe.zip';

	/**
	 * Where the premium version is bought.
	 *
	 * @since 4.10.0
	 */
	const UPGRADE_URL = 'https://wpsimplepay.com/?utm_source=WordPress&utm_medium=about&utm_campaign=smtp';
}
