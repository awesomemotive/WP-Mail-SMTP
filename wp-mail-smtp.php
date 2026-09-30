<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use WPMailSMTP\Helpers\Templates;

require_once __DIR__ . '/polyfills.php';

/**
 * Autoloader. We need it being separate and not using Composer autoloader because of the Gmail libs,
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

	/*
	 * Backward-compatibility aliases for classes relocated to a new namespace.
	 * Resolved here (not eagerly) so the target loads only when legacy code
	 * references the old name.
	 */
	$aliases = [
		'WPMailSMTP\Admin\SetupWizard' => 'WPMailSMTP\Admin\SetupWizard\Local',
	];

	if ( isset( $aliases[ $class ] ) ) {
		class_alias( $aliases[ $class ], $class );

		return;
	}

	/*
	 * This folder can be both "wp-mail-smtp" and "wp-mail-smtp-pro".
	 */
	$plugin_dir = basename( __DIR__ );

	// Default directory for all code is plugin's /src/.
	$base_dir = plugin_dir_path( __DIR__ ) . '/' . $plugin_dir . '/src/';

	// Get the relative class name.
	$relative_class = substr( $class, strlen( $plugin_space ) + 1 );

	// A map, not a derivation rule: the namespace doesn't encode the vendor/package directory.
	$vendor_dirs = [
		'Vendor\ProductApi\\' => 'vendor_prefixed/awesomemotive/wpforms-product-api-client/src/',
	];

	foreach ( $vendor_dirs as $vendor_namespace => $vendor_dir ) {
		if ( strpos( $relative_class, $vendor_namespace ) === 0 ) {
			$base_dir       = plugin_dir_path( __DIR__ ) . '/' . $plugin_dir . '/' . $vendor_dir;
			$relative_class = substr( $relative_class, strlen( $vendor_namespace ) );

			break;
		}
	}

	// Prepare a path to a file.
	$file = wp_normalize_path( $base_dir . $relative_class . '.php' );

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

/**
 * Render a plugin template and return its markup.
 *
 * @since 4.10.0
 *
 * @param string $template Path relative to the templates directory, without extension.
 * @param array  $args     Variables the template consumes.
 * @param bool   $extract  Expose each key of $args as its own variable.
 *
 * @return string
 */
function wp_mail_smtp_render( $template, $args = [], $extract = false ) {

	return Templates::get_html( $template, $args, $extract );
}

wp_mail_smtp();
