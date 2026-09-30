<?php

namespace WPMailSMTP\PartnerPlugins\Plugins;

use WPMailSMTP\PartnerPlugins\PartnerPlugin;

/**
 * WPVibe.
 *
 * @since 4.10.0
 */
class WPVibe extends PartnerPlugin {

	/**
	 * Catalog slug.
	 *
	 * @since 4.10.0
	 */
	const SLUG = 'wpvibe';

	/**
	 * Product name.
	 *
	 * @since 4.10.0
	 */
	const NAME = 'WPVibe';

	/**
	 * Free version main file, relative to the plugins directory.
	 *
	 * @since 4.10.0
	 */
	const BASENAME = 'vibe-ai/vibe-ai.php';

	/**
	 * Where the free version is downloaded from.
	 *
	 * @since 4.10.0
	 */
	const DOWNLOAD_URL = 'https://downloads.wordpress.org/plugin/vibe-ai.zip';

	/**
	 * Whether the plugin's own API is loaded and callable.
	 *
	 * @since 4.10.0
	 *
	 * @return bool
	 */
	public function is_loaded(): bool {

		return defined( 'WPVIBE_VERSION' );
	}

	/**
	 * Whether an AI assistant has connected at least once.
	 *
	 * @since 4.10.0
	 *
	 * @return bool
	 */
	public function is_configured(): bool {

		return $this->is_active() && (int) $this->get_site_option( 'wpvibe_last_active', 0 ) > 0;
	}

	/**
	 * The plugin's own settings screen.
	 *
	 * @since 4.10.0
	 *
	 * @return string
	 */
	public function get_setup_url(): string {

		return admin_url( 'admin.php?page=vibe-ai' );
	}

	/**
	 * Drop the onboarding redirect WPVibe arms on activation.
	 *
	 * @since 4.10.0
	 */
	public function after_activation(): void {

		delete_transient( 'wpvibe_activation_redirect' );
	}
}
