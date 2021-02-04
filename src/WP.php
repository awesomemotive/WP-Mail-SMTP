<?php

namespace WPMailSMTP;

/**
 * Class WP provides WordPress shortcuts.
 *
 * @since 1.0.0
 */
class WP {

	/**
	 * The "queue" of notices.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	protected static $admin_notices = array();
	/**
	 * CSS class for a success notice.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const ADMIN_NOTICE_SUCCESS = 'notice-success';
	/**
	 * CSS class for an error notice.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const ADMIN_NOTICE_ERROR = 'notice-error';
	/**
	 * CSS class for an info notice.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const ADMIN_NOTICE_INFO = 'notice-info';
	/**
	 * CSS class for a warning notice.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const ADMIN_NOTICE_WARNING = 'notice-warning';

	/**
	 * True if WP is processing an AJAX call.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public static function is_doing_ajax() {

		if ( function_exists( 'wp_doing_ajax' ) ) {
			return wp_doing_ajax();
		}

		return ( defined( 'DOING_AJAX' ) && DOING_AJAX );
	}

	/**
	 * True if I am in the Admin Panel, not doing AJAX.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public static function in_wp_admin() {

		return ( is_admin() && ! self::is_doing_ajax() );
	}

	/**
	 * Add a notice to the "queue of notices".
	 *
	 * @since 1.0.0
	 * @since 1.5.0 Added `$is_dismissible` param.
	 *
	 * @param string $message        Message text (HTML is OK).
	 * @param string $class          Display class (severity).
	 * @param bool   $is_dismissible Whether the message should be dismissible.
	 */
	public static function add_admin_notice( $message, $class = self::ADMIN_NOTICE_INFO, $is_dismissible = true ) {

		self::$admin_notices[] = array(
			'message'        => $message,
			'class'          => $class,
			'is_dismissible' => (bool) $is_dismissible,
		);
	}

	/**
	 * Display all notices.
	 *
	 * @since 1.0.0
	 * @since 1.5.0 Allow the notice to be dismissible, remove the id attribute, which is not unique.
	 */
	public static function display_admin_notices() {

		foreach ( (array) self::$admin_notices as $notice ) :
			$dismissible = $notice['is_dismissible'] ? 'is-dismissible' : '';
			?>

			<div class="notice wp-mail-smtp-notice <?php echo esc_attr( $notice['class'] ); ?> notice <?php echo esc_attr( $dismissible ); ?>">
				<p>
					<?php echo wp_kses_post( $notice['message'] ); ?>
				</p>
			</div>

			<?php
		endforeach;
	}

	/**
	 * Check whether WP_DEBUG is active.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public static function is_debug() {

		return defined( 'WP_DEBUG' ) && WP_DEBUG;
	}

	/**
	 * Shortcut to global $wpdb.
	 *
	 * @since 1.0.0
	 *
	 * @return \wpdb
	 */
	public static function wpdb() {
		global $wpdb;

		return $wpdb;
	}

	/**
	 * Get the postfix for assets files - ".min" or empty.
	 * ".min" if in production mode.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public static function asset_min() {

		$min = '.min';

		if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {
			$min = '';
		}

		return $min;
	}

	/**
	 * Check whether the string is a JSON or not.
	 *
	 * @since 1.5.0
	 *
	 * @param string $string String we want to test if it's json.
	 *
	 * @return bool
	 */
	public static function is_json( $string ) {

		return is_string( $string ) && is_array( json_decode( $string, true ) ) && ( json_last_error() === JSON_ERROR_NONE ) ? true : false;
	}

	/**
	 * Get the full date format as per WP options.
	 *
	 * @since 1.5.0
	 *
	 * @return string
	 */
	public static function datetime_format() {

		return sprintf( /* translators: %1$s - date, \a\t - specially escaped "at", %2$s - time. */
			esc_html__( '%1$s \a\t %2$s', 'wp-mail-smtp' ),
			get_option( 'date_format' ),
			get_option( 'time_format' )
		);
	}

	/**
	 * Get the full date form as per MySQL format.
	 *
	 * @since 1.5.0
	 *
	 * @return string
	 */
	public static function datetime_mysql_format() {

		return 'Y-m-d H:i:s';
	}

	/**
	 * Sanitize the value, similar to `sanitize_text_field()`, but a bit differently.
	 * It preserves `<` and `>` for non-HTML tags.
	 *
	 * @since 1.5.0
	 *
	 * @param string $value String we want to sanitize.
	 *
	 * @return string
	 */
	public static function sanitize_value( $value ) {

		// Remove HTML tags.
		$filtered = wp_strip_all_tags( $value, false );
		// Remove multi-lines/tabs.
		$filtered = preg_replace( '/[\r\n\t ]+/', ' ', $filtered );
		// Remove whitespaces.
		$filtered = trim( $filtered );

		// Remove octets.
		$found = false;
		while ( preg_match( '/%[a-f0-9]{2}/i', $filtered, $match ) ) {
			$filtered = str_replace( $match[0], '', $filtered );
			$found    = true;
		}

		if ( $found ) {
			// Strip out the whitespace that may now exist after removing the octets.
			$filtered = trim( preg_replace( '/ +/', ' ', $filtered ) );
		}

		return $filtered;
	}

	/**
	 * Get default email address.
	 *
	 * This is the same code as used in WP core for getting the default email address.
	 *
	 * @see https://github.com/WordPress/WordPress/blob/master/wp-includes/pluggable.php#L332
	 *
	 * @since 2.2.0
	 * @since 2.3.0 In WP 5.5 the core code changed and is now using `network_home_url`.
	 *
	 * @return string
	 */
	public static function get_default_email() {

		if ( version_compare( get_bloginfo( 'version' ), '5.5-alpha', '<' ) ) {
			$sitename = strtolower( $_SERVER['SERVER_NAME'] ); // phpcs:ignore
		} else {
			$sitename = wp_parse_url( network_home_url(), PHP_URL_HOST );
		}

		if ( 'www.' === substr( $sitename, 0, 4 ) ) {
			$sitename = substr( $sitename, 4 );
		}

		return 'wordpress@' . $sitename;
	}

	/**
	 * Wrapper for the WP `admin_url` method that should be used in the plugin.
	 *
	 * We can filter into it, to maybe call `network_admin_url` for multisite support.
	 *
	 * @since 2.2.0
	 *
	 * @param string $path   Optional path relative to the admin URL.
	 * @param string $scheme The scheme to use. Default is 'admin', which obeys force_ssl_admin() and is_ssl().
	 *                       'http' or 'https' can be passed to force those schemes.
	 *
	 * @return string Admin URL link with optional path appended.
	 */
	public static function admin_url( $path = '', $scheme = 'admin' ) {

		return apply_filters( 'wp_mail_smtp_admin_url', \admin_url( $path, $scheme ), $path, $scheme );
	}

	/**
	 * Check if the global plugin option in a multisite should be used.
	 * If the global plugin option "multisite" is set and true.
	 *
	 * @since 2.2.0
	 *
	 * @return bool
	 */
	public static function use_global_plugin_settings() {

		if ( ! is_multisite() ) {
			return false;
		}

		$main_site_options = get_blog_option( get_main_site_id(), Options::META_KEY, [] );

		return ! empty( $main_site_options['general']['network_wide'] );
	}

	/**
	 * Returns Jed-formatted localization data.
	 * This code was taken from a function removed from WP core: `wp_get_jed_locale_data`.
	 *
	 * @since 2.6.0
	 *
	 * @param string $domain Translation domain.
	 *
	 * @return array
	 */
	public static function get_jed_locale_data( $domain ) {

		$translations = get_translations_for_domain( $domain );

		$locale = array(
			'' => array(
				'domain' => $domain,
				'lang'   => is_admin() && function_exists( 'get_user_locale' ) ? get_user_locale() : get_locale(),
			),
		);

		if ( ! empty( $translations->headers['Plural-Forms'] ) ) {
			$locale['']['plural_forms'] = $translations->headers['Plural-Forms'];
		}

		foreach ( $translations->entries as $msgid => $entry ) {
			$locale[ $msgid ] = $entry->translations;
		}

		return $locale;
	}
}
