<?php

namespace WPMailSMTP\Deprecated\Admin\SetupWizard;

use WP_Error;

/**
 * Deprecated WPMailSMTP\Admin\SetupWizard\RestApi methods, kept for backward compatibility.
 *
 * @since 4.10.4
 */
trait RestApi {

	/**
	 * REST endpoint for installing a partner plugin.
	 *
	 * @since      4.6.0
	 * @deprecated {VERSION}
	 *
	 * @param WP_REST_Request $request Current request.
	 *
	 * @return WP_Error
	 */
	public function install_plugin( $request ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found

		_deprecated_function( __METHOD__, '4.10.4' );

		return $this->get_plugin_installs_removed_error();
	}

	/**
	 * REST endpoint for getting all partner's plugin information.
	 *
	 * @since      4.10.0
	 * @deprecated {VERSION}
	 *
	 * @return WP_Error
	 */
	public function get_partner_plugins_info() {

		_deprecated_function( __METHOD__, '4.10.4' );

		return $this->get_plugin_installs_removed_error();
	}

	/**
	 * The error the removed plugin install endpoints answer with.
	 *
	 * @since 4.10.4
	 *
	 * @return WP_Error
	 */
	private function get_plugin_installs_removed_error() {

		return new WP_Error(
			'wp_mail_smtp_setup_wizard_plugin_installs_removed',
			esc_html__( 'The Setup Wizard no longer installs plugins.', 'wp-mail-smtp' ),
			[ 'status' => 410 ]
		);
	}
}
