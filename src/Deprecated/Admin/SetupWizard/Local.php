<?php

namespace WPMailSMTP\Deprecated\Admin\SetupWizard;

use WPMailSMTP\Admin\SetupWizard\Launcher;
use WPMailSMTP\Admin\SetupWizard\Stats;

/**
 * Deprecated WPMailSMTP\Admin\SetupWizard\Local methods, kept for backward
 * compatibility with the class when it was WPMailSMTP\Admin\SetupWizard.
 *
 * Methods a caller reads a value from forward to their new owner. The ones that
 * were only ever hook callbacks are inert: the Launcher owns those hooks now,
 * and running one outside its hook renders a page or consumes the activation
 * transient.
 *
 * @since 4.10.0
 */
trait Local {

	/**
	 * Get the URL of the Setup Wizard page.
	 *
	 * @since      2.6.0
	 * @deprecated {VERSION}
	 *
	 * @return string
	 */
	public static function get_site_url() {

		_deprecated_function( __METHOD__, '4.10.0', '\WPMailSMTP\Admin\SetupWizard\Local::get_base_url' );

		return self::get_base_url();
	}

	/**
	 * Checks if the Wizard should be loaded in current context.
	 *
	 * @since      2.6.0
	 * @deprecated {VERSION}
	 *
	 * @return void
	 */
	public function maybe_load_wizard() {

		_deprecated_function( __METHOD__, '4.10.0', '\WPMailSMTP\Admin\SetupWizard\Launcher::maybe_load' );
	}

	/**
	 * Maybe redirect to the setup wizard after plugin activation on a new install.
	 *
	 * @since      2.6.0
	 * @deprecated {VERSION}
	 *
	 * @return void
	 */
	public function maybe_redirect_after_activation() {

		_deprecated_function( __METHOD__, '4.10.0', '\WPMailSMTP\Admin\SetupWizard\Launcher::maybe_redirect_after_activation' );
	}

	/**
	 * Register page through WordPress's hooks.
	 *
	 * @since      2.6.0
	 * @deprecated {VERSION}
	 *
	 * @return void
	 */
	public function add_dashboard_page() {

		_deprecated_function( __METHOD__, '4.10.0', '\WPMailSMTP\Admin\SetupWizard\Launcher::add_dashboard_page' );
	}

	/**
	 * Check if the Setup Wizard should load.
	 *
	 * @since      2.6.0
	 * @deprecated {VERSION}
	 *
	 * @return bool
	 */
	public function should_setup_wizard_load() {

		_deprecated_function( __METHOD__, '4.10.0', '\WPMailSMTP\Admin\SetupWizard\Launcher::should_setup_wizard_load' );

		return ( new Launcher() )->should_setup_wizard_load();
	}

	/**
	 * Get the Setup Wizard stats.
	 *
	 * @since      3.1.0
	 * @deprecated {VERSION}
	 *
	 * @return array
	 */
	public static function get_stats() {

		_deprecated_function( __METHOD__, '4.10.0', '\WPMailSMTP\Admin\SetupWizard\Stats::get' );

		return Stats::get();
	}

	/**
	 * Update the Setup Wizard stats.
	 *
	 * @since      3.1.0
	 * @deprecated {VERSION}
	 *
	 * @param array $options Take a look at the Stats::get method for the possible array keys.
	 *
	 * @return void
	 */
	public static function update_stats( $options ) {

		_deprecated_function( __METHOD__, '4.10.0', '\WPMailSMTP\Admin\SetupWizard\Stats::update' );

		Stats::update( $options );
	}
}
