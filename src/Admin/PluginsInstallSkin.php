<?php

namespace WPMailSMTP\Admin;

use Automatic_Upgrader_Skin;
use Language_Pack_Upgrader;
use WP_Error;

/**
 * WordPress class extended for on-the-fly plugin installations.
 *
 * @since 1.5.0
 * @since 1.7.1 Removed feedback() method override to be compatible with PHP5.3+ and WP5.3.
 * @since 3.11.0 Updated to extend Automatic_Upgrader_Skin.
 * @since 4.10.1 Notes a failure for the caller instead of answering the request with it.
 * @since 4.10.5 Keeps the parent's header() and footer(), which buffer anything printed during the run.
 */
class PluginsInstallSkin extends Automatic_Upgrader_Skin {

	/**
	 * The failure the upgrader reported, when it reported one.
	 *
	 * @since 4.10.1
	 *
	 * @var WP_Error|null
	 */
	private $failure = null;

	/**
	 * Note a failure, leaving the reply to the caller.
	 *
	 * @since 1.5.0
	 * @since 4.10.1 Notes the failure instead of answering the request with it.
	 *
	 * @param string|WP_Error $errors Errors from the install process.
	 */
	public function error( $errors ) {

		// Core reuses this skin for the translation downloads that follow an install, and their failure is not the plugin's.
		if ( is_wp_error( $errors ) && ! $this->upgrader instanceof Language_Pack_Upgrader ) {
			$this->failure = $errors;
		}
	}

	/**
	 * Whether the upgrader reported a failure.
	 *
	 * @since 4.10.1
	 *
	 * @return bool
	 */
	public function has_failure(): bool {

		return $this->failure !== null;
	}

	/**
	 * The failure the upgrader reported, when it reported one.
	 *
	 * @since 4.10.1
	 *
	 * @return WP_Error|null
	 */
	public function get_failure(): ?WP_Error {

		return $this->failure;
	}

	/**
	 * Empty out JavaScript output that calls function to decrement the update counts.
	 *
	 * @since 1.5.0
	 *
	 * @param string $type Type of update count to decrement.
	 */
	public function decrement_update_count( $type ) {
	}
}

