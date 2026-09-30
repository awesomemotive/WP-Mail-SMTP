<?php

namespace WPMailSMTP\Admin\Dashboard;

/**
 * Dashboard shared helpers.
 *
 * @since 4.10.0
 */
class Helpers {

	/**
	 * Get the current user's meta value guaranteed to be an array.
	 *
	 * @since 4.10.0
	 *
	 * @param string $key Meta key.
	 *
	 * @return array
	 */
	public static function get_user_meta_array( string $key ): array {

		$value = get_user_meta( get_current_user_id(), $key, true );

		return is_array( $value ) ? $value : [];
	}

	/**
	 * Update the current user's meta with an array value.
	 *
	 * @since 4.10.0
	 *
	 * @param string $key   Meta key.
	 * @param array  $value Meta value.
	 */
	public static function update_user_meta_array( string $key, array $value ): void {

		update_user_meta( get_current_user_id(), $key, $value );
	}

	/**
	 * Whether the request asks for a forced cache recompute via `force-check=1`, read
	 * from GET on a page load and from POST on the date-range AJAX call.
	 *
	 * @since 4.10.0
	 *
	 * @return bool
	 */
	public static function is_force_refresh(): bool {

		// No nonce by design, as with core's own `force-check`: the flag only re-derives
		// data the user is already looking at, and the capability check below gates it.
		// phpcs:disable WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing
		$force_check = isset( $_GET['force-check'] )
			? absint( $_GET['force-check'] )
			: absint( $_POST['force-check'] ?? 0 );
		// phpcs:enable WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing

		return $force_check === 1 && current_user_can( wp_mail_smtp()->get_capability_manage_options() );
	}
}
