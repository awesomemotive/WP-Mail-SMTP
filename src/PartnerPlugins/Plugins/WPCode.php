<?php

namespace WPMailSMTP\PartnerPlugins\Plugins;

use WPMailSMTP\PartnerPlugins\PartnerPlugin;

/**
 * WPCode.
 *
 * @since 4.10.0
 */
class WPCode extends PartnerPlugin {

	/**
	 * Catalog slug.
	 *
	 * @since 4.10.0
	 */
	const SLUG = 'wpcode';

	/**
	 * Product name.
	 *
	 * @since 4.10.0
	 */
	const NAME = 'WPCode';

	/**
	 * Product name of the premium version.
	 *
	 * @since 4.10.0
	 */
	const NAME_PRO = 'WPCode Pro';

	/**
	 * Free version main file, relative to the plugins directory.
	 *
	 * @since 4.10.0
	 */
	const BASENAME = 'insert-headers-and-footers/ihaf.php';

	/**
	 * Premium version main file, relative to the plugins directory.
	 *
	 * @since 4.10.0
	 */
	const BASENAME_PRO = 'wpcode-premium/wpcode.php';

	/**
	 * Where the free version is downloaded from.
	 *
	 * @since 4.10.0
	 */
	const DOWNLOAD_URL = 'https://downloads.wordpress.org/plugin/insert-headers-and-footers.zip';

	/**
	 * Where the premium version is bought.
	 *
	 * @since 4.10.0
	 */
	const UPGRADE_URL = 'https://wpcode.com/?utm_source=WordPress&utm_medium=about&utm_campaign=smtp';

	/**
	 * Whether the plugin's own API is loaded and callable.
	 *
	 * @since 4.10.0
	 *
	 * @return bool
	 */
	public function is_loaded(): bool {

		return class_exists( 'InsertHeadersAndFooters' );
	}

	/**
	 * Whether the premium tier is unlocked.
	 *
	 * @since 4.10.0
	 *
	 * @return bool
	 */
	public function is_pro_active(): bool {

		return class_exists( 'WPCode_License' );
	}

	/**
	 * Whether WPCode has finished its own activation routine.
	 *
	 * @since 4.10.0
	 *
	 * @return bool
	 */
	public function is_configured(): bool {

		if ( ! $this->is_active() || ! $this->is_loaded() ) {
			return false;
		}

		$activated = $this->get_site_option( 'ihaf_activated' );

		return is_array( $activated ) && ! empty( $activated['wpcode'] );
	}

	/**
	 * Whether the snippet library API this plugin renders cards from is
	 * available, which needs a newer WPCode than mere activation implies.
	 *
	 * @since 4.10.0
	 *
	 * @return bool
	 */
	public function has_snippet_library(): bool {

		return function_exists( 'wpcode_get_library_snippets_by_username' );
	}

	/**
	 * The plugin's own settings screen.
	 *
	 * @since 4.10.0
	 *
	 * @return string
	 */
	public function get_setup_url(): string {

		return admin_url( 'admin.php?page=wpcode' );
	}
}
