<?php

namespace WPMailSMTP\PartnerPlugins\Plugins;

use WPMailSMTP\PartnerPlugins\PartnerPlugin;

/**
 * ActiveLayer.
 *
 * @since 4.10.0
 */
class ActiveLayer extends PartnerPlugin {

	/**
	 * Catalog slug.
	 *
	 * @since 4.10.0
	 */
	const SLUG = 'activelayer';

	/**
	 * Product name.
	 *
	 * @since 4.10.0
	 */
	const NAME = 'ActiveLayer';

	/**
	 * Free version main file, relative to the plugins directory.
	 *
	 * @since 4.10.0
	 */
	const BASENAME = 'activelayer-anti-spam-spam-protection-for-forms-comments/activelayer-anti-spam-spam-protection-for-forms-comments.php';

	/**
	 * Where the free version is downloaded from.
	 *
	 * @since 4.10.0
	 */
	const DOWNLOAD_URL = 'https://downloads.wordpress.org/plugin/activelayer-anti-spam-spam-protection-for-forms-comments.zip';

	/**
	 * Main files of plugins that solve the same problem this one does.
	 *
	 * @since 4.10.0
	 */
	const COMPETITORS = [
		'akismet/akismet.php',
		'antispam-bee/antispam_bee.php',
		'honeypot/wp-armour.php',
		'wp-armour-extended/wp-armour-extended.php',
		'cleantalk-spam-protect/cleantalk.php',
		'wp-cerber/wp-cerber.php',
		'anti-spam/anti-spam.php',
	];

	/**
	 * Whether the plugin's own API is loaded and callable.
	 *
	 * @since 4.10.0
	 *
	 * @return bool
	 */
	public function is_loaded(): bool {

		return class_exists( 'ActiveLayer\Plugin' );
	}

	/**
	 * Whether spam protection is actually running, which needs an API key.
	 *
	 * @since 4.10.0
	 *
	 * @return bool
	 */
	public function is_configured(): bool {

		if ( ! $this->is_active() ) {
			return false;
		}

		$settings = $this->get_site_option( 'activelayer_global_settings' );

		return is_array( $settings ) && ! empty( $settings['api_key'] );
	}

	/**
	 * The plugin's own settings screen.
	 *
	 * @since 4.10.0
	 *
	 * @return string
	 */
	public function get_setup_url(): string {

		return admin_url( 'admin.php?page=activelayer-settings' );
	}

	/**
	 * Drop the onboarding redirect ActiveLayer arms on activation.
	 *
	 * @since 4.10.0
	 */
	public function after_activation(): void {

		delete_transient( 'activelayer_activation_redirect' );
	}
}
