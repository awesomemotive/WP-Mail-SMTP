<?php

namespace WPMailSMTP\PartnerPlugins\Plugins;

use WPMailSMTP\PartnerPlugins\PartnerPlugin;

/**
 * WPConsent.
 *
 * @since 4.10.0
 */
class WPConsent extends PartnerPlugin {

	/**
	 * Catalog slug.
	 *
	 * @since 4.10.0
	 */
	const SLUG = 'wpconsent';

	/**
	 * Product name.
	 *
	 * @since 4.10.0
	 */
	const NAME = 'WPConsent';

	/**
	 * Product name of the premium version.
	 *
	 * @since 4.10.0
	 */
	const NAME_PRO = 'WPConsent Pro';

	/**
	 * Free version main file, relative to the plugins directory.
	 *
	 * @since 4.10.0
	 */
	const BASENAME = 'wpconsent-cookies-banner-privacy-suite/wpconsent.php';

	/**
	 * Premium version main file, relative to the plugins directory.
	 *
	 * @since 4.10.0
	 */
	const BASENAME_PRO = 'wpconsent-premium/wpconsent-premium.php';

	/**
	 * Where the free version is downloaded from.
	 *
	 * @since 4.10.0
	 */
	const DOWNLOAD_URL = 'https://downloads.wordpress.org/plugin/wpconsent-cookies-banner-privacy-suite.zip';

	/**
	 * Main files of plugins that solve the same problem this one does.
	 *
	 * @since 4.10.0
	 */
	const COMPETITORS = [
		'cookie-law-info/cookie-law-info.php',
		'complianz-gdpr/complianz-gpdr.php',
		'complianz-gdpr-premium/complianz-gpdr-premium.php',
		'cookie-notice/cookie-notice.php',
		'gdpr-cookie-compliance/moove-gdpr.php',
		'iubenda-cookie-law-solution/iubenda_cookie_solution.php',
		'real-cookie-banner/index.php',
		'cookiebot/cookiebot.php',
		'uk-cookie-consent/uk-cookie-consent.php',
		'borlabs-cookie/borlabs-cookie.php',
	];

	/**
	 * Whether the plugin's own API is loaded and callable.
	 *
	 * @since 4.10.0
	 *
	 * @return bool
	 */
	public function is_loaded(): bool {

		return function_exists( 'wpconsent' );
	}

	/**
	 * Whether the premium tier is unlocked. WPConsent uses a license key on the
	 * free plugin, so the plugin file says nothing about the tier.
	 *
	 * @since 4.10.0
	 *
	 * @return bool
	 * @noinspection PhpUndefinedFunctionInspection
	 */
	public function is_pro_active(): bool {

		if ( ! function_exists( 'wpconsent' ) ) {
			return false;
		}

		$wpconsent = wpconsent();

		return isset( $wpconsent->license ) && method_exists( $wpconsent->license, 'is_active' ) && $wpconsent->license->is_active();
	}

	/**
	 * Whether the consent banner is switched on, which is what makes the
	 * plugin do anything visible.
	 *
	 * @since 4.10.0
	 *
	 * @return bool
	 * @noinspection PhpUndefinedFunctionInspection
	 */
	public function is_configured(): bool {

		if ( ! $this->is_active() || ! $this->is_loaded() ) {
			return false;
		}

		$wpconsent = wpconsent();

		if ( ! isset( $wpconsent->settings ) ) {
			return false;
		}

		return ! empty( $wpconsent->settings->get_option( 'enable_consent_banner', 0 ) );
	}

	/**
	 * The plugin's own onboarding wizard.
	 *
	 * @since 4.10.0
	 *
	 * @return string
	 */
	public function get_setup_url(): string {

		return admin_url( 'admin.php?page=wpconsent-onboarding' );
	}

	/**
	 * Drop the onboarding redirect WPConsent arms.
	 *
	 * @since 4.10.0
	 */
	public function after_activation(): void {

		delete_transient( 'wpconsent_onboarding_redirect' );

		$activated = get_option( 'wpconsent_activated', [] );

		if ( ! empty( $activated['wpconsent'] ) ) {
			return;
		}

		// On a first install WPConsent arms its onboarding redirect on the next
		// admin request, unless this record of that run is already there. The
		// version is the one its own first run writes, so its upgrade routines
		// still run afterwards.
		$activated['wpconsent'] = time();
		$activated['version']   = '0';

		update_option( 'wpconsent_activated', $activated );
	}
}
