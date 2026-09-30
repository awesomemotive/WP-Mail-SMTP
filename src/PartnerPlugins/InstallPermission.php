<?php

namespace WPMailSMTP\PartnerPlugins;

use WP_Error;

/**
 * Whether the current user may make a partner plugin active on the site it describes.
 *
 * @since 4.10.0
 */
class InstallPermission {

	/**
	 * Whether the request may go ahead, or why it may not.
	 *
	 * @since 4.10.0
	 *
	 * @param PartnerPlugin $plugin Partner the request is about.
	 *
	 * @return true|WP_Error
	 */
	public function check( PartnerPlugin $plugin ) {

		if ( $plugin->get_install_state() === PartnerPlugin::STATE_NOT_INSTALLED ) {
			if ( ! current_user_can( 'install_plugins' ) ) {
				return new WP_Error(
					'wp_mail_smtp_partner_cannot_install',
					esc_html__( 'Your account does not have permission to install plugins on this site.', 'wp-mail-smtp' ),
					$plugin->get_wporg_url()
				);
			}
		} elseif ( ! current_user_can( 'activate_plugins' ) ) {
			return new WP_Error(
				'wp_mail_smtp_partner_cannot_activate',
				esc_html__( 'Your account does not have permission to activate plugins on this site. The plugin is already installed, so an administrator only needs to switch it on.', 'wp-mail-smtp' )
			);
		}

		// Reaching past the site serving the request is the network administrator's to do.
		if (
			is_multisite() &&
			$plugin->get_site_id() !== get_current_blog_id() &&
			! current_user_can( 'manage_network_plugins' )
		) {
			return new WP_Error(
				'wp_mail_smtp_partner_cannot_manage_network',
				esc_html__( 'Only a network administrator can install plugins for the sites on this network.', 'wp-mail-smtp' )
			);
		}

		return true;
	}

	/**
	 * A refusal in the shape the client's error modal reads.
	 *
	 * @since 4.10.0
	 *
	 * @param WP_Error $refusal Refusal from {@see self::check()}.
	 *
	 * @return array
	 */
	public function get_refusal_payload( WP_Error $refusal ): array {

		return [
			'message'    => $refusal->get_error_message(),
			'manual_url' => (string) $refusal->get_error_data(),
		];
	}
}
