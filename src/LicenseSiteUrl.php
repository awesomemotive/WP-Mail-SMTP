<?php

namespace WPMailSMTP;

/**
 * The site URL that identifies this site to WP Mail SMTP services.
 *
 * @since 4.10.0
 */
class LicenseSiteUrl {

	/**
	 * Get the URL to send.
	 *
	 * @since 4.10.0
	 *
	 * @return string
	 */
	public function get() {

		$pinned = Options::init()->get( 'license', 'site_url' );

		// An install that has not pinned a URL keeps sending the computed one, so nothing about
		// what our services see changes until an explicit action pins a value.
		return ! empty( $pinned ) ? $pinned : WP::get_site_url();
	}

	/**
	 * Derive the URL to identify this site by, from the most stable source available.
	 *
	 * @since 4.10.0
	 *
	 * @return string
	 */
	public function derive() {

		// Core answers WP_SITEURL for every site URL read while the constant is defined, and
		// drops that filter on multisite (ms-default-filters.php), where the row is the truth.
		if ( ! is_multisite() && defined( 'WP_SITEURL' ) && ! empty( WP_SITEURL ) ) {
			$url = WP_SITEURL;
		} else {
			$url = $this->get_row();
		}

		if ( empty( $url ) ) {
			$url = WP::get_site_url();
		}

		$url = untrailingslashit( $url );

		// A site moved to HTTPS through an `option_siteurl` filter still has `http` in its row.
		// The Product API dereferences this value, so the scheme has to be one the site answers on.
		if (
			wp_parse_url( $url, PHP_URL_SCHEME ) === 'http' &&
			wp_parse_url( WP::get_site_url(), PHP_URL_SCHEME ) === 'https'
		) {
			$url = set_url_scheme( $url, 'https' );
		}

		// Sanitised here rather than on the way into the option, so the string this sends, the
		// string it pins and the string it compares a page view later are all the same one.
		return esc_url_raw( $url );
	}

	/**
	 * Read the `siteurl` row as it is stored, past every option filter.
	 *
	 * @since 4.10.0
	 *
	 * @return string
	 */
	private function get_row() {

		global $wpdb;

		$site_id  = WP::use_global_plugin_settings() ? get_main_site_id() : 0;
		$switched = ! empty( $site_id ) && is_multisite() && $site_id !== get_current_blog_id();

		if ( $switched ) {
			switch_to_blog( $site_id );
		}

		// Filters on `option_siteurl` are what make every accessor unusable here, and the row
		// itself is the only read they cannot reach.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$url = $wpdb->get_var( $wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s LIMIT 1", 'siteurl' ) );

		if ( $switched ) {
			restore_current_blog();
		}

		return (string) $url;
	}
}
