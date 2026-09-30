<?php

namespace WPMailSMTP\PartnerPlugins\Plugins;

use WPMailSMTP\PartnerPlugins\PartnerPlugin;

/**
 * Universally.
 *
 * @since 4.10.0
 */
class Universally extends PartnerPlugin {

	/**
	 * Catalog slug.
	 *
	 * @since 4.10.0
	 */
	const SLUG = 'universally';

	/**
	 * Product name.
	 *
	 * @since 4.10.0
	 */
	const NAME = 'Universally';

	/**
	 * Free version main file, relative to the plugins directory.
	 *
	 * @since 4.10.0
	 */
	const BASENAME = 'universally-language-translation-multilingual-tool/universally.php';

	/**
	 * Where the free version is downloaded from.
	 *
	 * @since 4.10.0
	 */
	const DOWNLOAD_URL = 'https://downloads.wordpress.org/plugin/universally-language-translation-multilingual-tool.zip';

	/**
	 * Whether the plugin's own API is loaded and callable.
	 *
	 * @since 4.10.0
	 *
	 * @return bool
	 */
	public function is_loaded(): bool {

		return class_exists( 'Universally\AdminBar', false );
	}

	/**
	 * Whether translation is actually running, which needs an API key.
	 *
	 * @since 4.10.0
	 *
	 * @return bool
	 */
	public function is_configured(): bool {

		return $this->is_active() && ! empty( $this->get_site_option( 'universally_api_key' ) );
	}

	/**
	 * The plugin's own settings screen.
	 *
	 * @since 4.10.0
	 *
	 * @return string
	 */
	public function get_setup_url(): string {

		return admin_url( 'admin.php?page=universally_settings' );
	}
}
