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
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const ADMIN_NOTICE_SUCCESS = 'notice-success';
	/**
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const ADMIN_NOTICE_ERROR = 'notice-error';
	/**
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const ADMIN_NOTICE_INFO = 'notice-info';
	/**
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const ADMIN_NOTICE_WARNING = 'notice-warning';

	/**
	 * True is WP is processing an AJAX call.
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
					<?php echo $notice['message']; ?>
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
	 * @param string $string
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

		return sprintf(
			/* translators: %1$s - date, \a\t - specially escaped "at", %2$s - time. */
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
	 * Sanitize the value, similar to sanitize_text_field(), but a bit differently.
	 * It preserves `<` and `>` for non-HTML tags.
	 *
	 * @since 1.5.0
	 *
	 * @param string $value
	 *
	 * @return mixed|string|string[]|null
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
}
