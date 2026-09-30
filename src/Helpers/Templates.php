<?php

namespace WPMailSMTP\Helpers;

/**
 * Locate and render plugin templates.
 *
 * @since 4.10.0
 */
class Templates {

	/**
	 * Absolute path to a template, or an empty string when it does not exist.
	 *
	 * @since 4.10.0
	 *
	 * @param string $template_name Path relative to the plugin's templates directory, with extension.
	 *
	 * @return string
	 */
	public static function locate( $template_name ) {

		$template_name = ltrim( $template_name, '/' );

		if ( $template_name === '' || strpos( $template_name, '..' ) !== false ) {
			return '';
		}

		$path = WPMS_PLUGIN_DIR . 'templates/' . $template_name;

		return is_readable( $path ) ? $path : '';
	}

	/**
	 * Render a template and return its markup.
	 *
	 * @since 4.10.0
	 *
	 * @param string $template_name Path relative to the plugin's templates directory, without extension.
	 * @param array  $args          Variables the template consumes.
	 * @param bool   $extract       Expose each key of $args as its own variable.
	 *
	 * @return string
	 */
	public static function get_html( $template_name, $args = [], $extract = false ) {

		$located = self::locate( $template_name . '.php' );

		if ( $located === '' ) {
			return '';
		}

		if ( $extract ) {
			// phpcs:ignore WordPress.PHP.DontExtract.extract_extract -- The template contract is named variables, matching the ported source.
			extract( $args, EXTR_SKIP );
		}

		ob_start();

		require $located;

		return (string) ob_get_clean();
	}
}
