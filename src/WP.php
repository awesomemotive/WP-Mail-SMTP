<?php

namespace WPMailSMTP;

/**
 * Class WP provides WordPress shortcuts.
 */
class WP {

	/**
	 * The "queue" of notices.
	 *
	 * @var array
	 */
	protected static $admin_notices = array();
	/**
	 * @var string
	 */
	const ADMIN_NOTICE_SUCCESS = 'notice-success';
	/**
	 * @var string
	 */
	const ADMIN_NOTICE_ERROR = 'notice-error';
	/**
	 * @var string
	 */
	const ADMIN_NOTICE_INFO = 'notice-info';
	/**
	 * @var string
	 */
	const ADMIN_NOTICE_WARNING = 'notice-warning';

	/**
	 * True is WP is processing an AJAX call.
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
	 * @return bool
	 */
	public static function in_wp_admin() {
		return ( is_admin() && ! self::is_doing_ajax() );
	}

	/**
	 * Add a notice to the "queue of notices".
	 *
	 * @param string $message Message text (HTML is OK).
	 * @param string $class Display class (severity).
	 */
	public static function add_admin_notice( $message, $class = self::ADMIN_NOTICE_INFO ) {

		self::$admin_notices[] = array(
			'message' => $message,
			'class'   => $class,
		);
	}

	/**
	 * Display all notices.
	 */
	public static function display_admin_notices() {

		foreach ( (array) self::$admin_notices as $notice ) : ?>

			<div id="message" class="<?php echo esc_attr( $notice['class'] ); ?> notice is-dismissible">
				<p>
					<?php echo $notice['message']; ?>
				</p>
			</div>

			<?php
		endforeach;
	}

	/**
	 * @return bool
	 */
	public static function is_debug() {
		return defined( 'WP_DEBUG' ) && WP_DEBUG;
	}

	/**
	 * Shortcut to global $wpdb.
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
	 * @return string
	 */
	public static function asset_min() {

		$min = '.min';

		if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {
			$min = '';
		}

		return $min;
	}
}
