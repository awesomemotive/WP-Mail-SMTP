<?php

namespace WPMailSMTP\PartnerPlugins\Plugins;

use WPMailSMTP\PartnerPlugins\PartnerPlugin;

/**
 * Sugar Calendar.
 *
 * @since 4.10.0
 */
class SugarCalendar extends PartnerPlugin {

	/**
	 * Catalog slug.
	 *
	 * @since 4.10.0
	 */
	const SLUG = 'sugar-calendar';

	/**
	 * Product name.
	 *
	 * @since 4.10.0
	 */
	const NAME = 'Sugar Calendar Lite';

	/**
	 * Product name of the premium version.
	 *
	 * @since 4.10.0
	 */
	const NAME_PRO = 'Sugar Calendar';

	/**
	 * Free version main file, relative to the plugins directory.
	 *
	 * @since 4.10.0
	 */
	const BASENAME = 'sugar-calendar-lite/sugar-calendar-lite.php';

	/**
	 * Premium version main file, relative to the plugins directory.
	 *
	 * @since 4.10.0
	 */
	const BASENAME_PRO = 'sugar-calendar/sugar-calendar.php';

	/**
	 * Where the free version is downloaded from.
	 *
	 * @since 4.10.0
	 */
	const DOWNLOAD_URL = 'https://downloads.wordpress.org/plugin/sugar-calendar-lite.zip';

	/**
	 * Where the premium version is bought.
	 *
	 * @since 4.10.0
	 */
	const UPGRADE_URL = 'https://sugarcalendar.com/?utm_source=WordPress&utm_medium=about&utm_campaign=smtp';
}
