<?php

namespace WPMailSMTP\Compatibility\Plugin;

/**
 * WishList Member compatibility plugin.
 *
 * @since 2.8.0
 */
class WishListMember extends PluginAbstract {

	/**
	 * Get plugin name.
	 *
	 * @since 2.8.0
	 *
	 * @return string
	 */
	public static function get_name() {

		return 'WishList Member';
	}

	/**
	 * Get plugin path.
	 *
	 * @since 2.8.0
	 *
	 * @return string
	 */
	public static function get_path() {

		return 'wishlist-member/wpm.php';
	}

	/**
	 * Execute on init action in admin area.
	 *
	 * @since 2.8.0
	 */
	public function load_admin() {

		add_action( 'admin_init', [ $this, 'clear_post' ], 0 );
	}

	/**
	 * Clear $_POST array to prevent Area::process_actions on GET request.
	 *
	 * @since 2.8.0
	 */
	public function clear_post() {

		if ( wp_mail_smtp()->get_admin()->is_admin_page() && isset( $_POST['msg'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			unset( $_POST['msg'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		}
	}
}
