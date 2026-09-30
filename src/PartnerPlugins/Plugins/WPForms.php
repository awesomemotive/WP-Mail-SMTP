<?php

namespace WPMailSMTP\PartnerPlugins\Plugins;

use WPMailSMTP\PartnerPlugins\PartnerPlugin;

/**
 * WPForms.
 *
 * @since 4.10.0
 */
class WPForms extends PartnerPlugin {

	/**
	 * Catalog slug.
	 *
	 * @since 4.10.0
	 */
	const SLUG = 'wpforms';

	/**
	 * Product name.
	 *
	 * @since 4.10.0
	 */
	const NAME = 'WPForms';

	/**
	 * Product name of the premium version.
	 *
	 * @since 4.10.0
	 */
	const NAME_PRO = 'WPForms Pro';

	/**
	 * Free version main file, relative to the plugins directory.
	 *
	 * @since 4.10.0
	 */
	const BASENAME = 'wpforms-lite/wpforms.php';

	/**
	 * Premium version main file, relative to the plugins directory.
	 *
	 * @since 4.10.0
	 */
	const BASENAME_PRO = 'wpforms/wpforms.php';

	/**
	 * Where the free version is downloaded from.
	 *
	 * @since 4.10.0
	 */
	const DOWNLOAD_URL = 'https://downloads.wordpress.org/plugin/wpforms-lite.zip';

	/**
	 * Where the premium version is bought.
	 *
	 * @since 4.10.0
	 */
	const UPGRADE_URL = 'https://wpforms.com/?utm_source=WordPress&utm_medium=about&utm_campaign=smtp';

	/**
	 * Whether the plugin's own API is loaded and callable.
	 *
	 * @since 4.10.0
	 *
	 * @return bool
	 */
	public function is_loaded(): bool {

		return function_exists( 'wpforms' );
	}

	/**
	 * Pre-empt the welcome screen WPForms redirects to on activation.
	 *
	 * @since 4.10.0
	 */
	public function before_activation(): void {

		update_option( 'wpforms_activation_redirect', true );
	}

	/**
	 * Drop the onboarding redirect WPForms arms on activation.
	 *
	 * @since 4.10.0
	 */
	public function after_activation(): void {

		delete_transient( 'wpforms_setup_wizard_first_run' );

		// On a first install the Setup Wizard arms its own flag on the next
		// admin request, unless this record of that run is already there.
		add_option( 'wpforms_setup_wizard_initial_version', defined( 'WPFORMS_VERSION' ) ? WPFORMS_VERSION : '', '', false );
	}
}
