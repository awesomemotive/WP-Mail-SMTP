<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Autoloader. We needs it being separate and not using Composer autoloader because of the Gmail libs,
 * which are huge and not needed for most users.
 * Inspired by PSR-4 examples: https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-4-autoloader-examples.md
 *
 * @since 1.0.0
 *
 * @param string $class The fully-qualified class name.
 */
spl_autoload_register( function ( $class ) {

	list( $plugin_space ) = explode( '\\', $class );
	if ( $plugin_space !== 'WPMailSMTP' ) {
		return;
	}

	$plugin_folder = 'wp-mail-smtp';

	// Default directory for all code is plugin's /src/.
	$base_dir = plugin_dir_path( __DIR__ ) . '/' . $plugin_folder . '/src/';

	// Get the relative class name.
	$relative_class = substr( $class, strlen( $plugin_space ) + 1 );

	/**
	 * Normalize a filesystem path.
	 * Copy of the `wp_normalize_path()` from WordPress 3.9.
	 *
	 * @since 1.2.0
	 *
	 * @param string $path
	 *
	 * @return string
	 */
	$normalize = function( $path ) {
		$path = str_replace( '\\', '/', $path );
		$path = preg_replace( '|(?<=.)/+|', '/', $path );
		if ( ':' === substr( $path, 1, 1 ) ) {
			$path = ucfirst( $path );
		}
		return $path;
	};

	// Prepare a path to a file.
	$file = $normalize( $base_dir . $relative_class . '.php' );

	// If the file exists, require it.
	if ( is_readable( $file ) ) {
		/** @noinspection PhpIncludeInspection */
		require_once $file;
	}
} );

/**
 * Global function-holder. Works similar to a singleton's instance().
 *
 * @since 1.0.0
 *
 * @return WPMailSMTP\Core
 */
function wp_mail_smtp() {
	/**
	 * @var \WPMailSMTP\Core
	 */
	static $core;

	if ( ! isset( $core ) ) {
		$core = new \WPMailSMTP\Core();
	}

	return $core;
}

wp_mail_smtp();
