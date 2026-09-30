<?php
/**
 * Polyfills for functions missing on the plugin's supported-but-older WordPress and PHP versions.
 *
 * Must stay in the global namespace so the backfilled functions are reachable by the same
 * unqualified names their callers use. Required from wp-mail-smtp.php before the plugin boots.
 *
 * @since 4.9.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! function_exists( 'wp_is_serving_rest_request' ) ) {
	/**
	 * Backfill of WP 6.5's wp_is_serving_rest_request() for older WordPress.
	 *
	 * Action Scheduler 4.0+ calls this function unconditionally.
	 *
	 * @since 4.9.0
	 *
	 * @return bool
	 */
	function wp_is_serving_rest_request() {

		return defined( 'REST_REQUEST' ) && REST_REQUEST;
	}
}

if ( ! function_exists( 'wp_admin_notice' ) ) {
	/**
	 * Backfill of WP 6.4's wp_admin_notice() for older WordPress.
	 *
	 * Action Scheduler 4.1+ prints its admin notices with this function.
	 *
	 * @since 4.10.0
	 *
	 * @param string $message The notice message.
	 * @param array  $args    Optional. Keys: type, dismissible, id, additional_classes, paragraph_wrap.
	 */
	function wp_admin_notice( $message, $args = [] ) {

		$args = wp_parse_args(
			$args,
			[
				'type'               => '',
				'dismissible'        => false,
				'id'                 => '',
				'additional_classes' => '',
				'paragraph_wrap'     => true,
			]
		);

		$classes = [ 'notice' ];

		if ( ! empty( $args['type'] ) ) {
			$classes[] = 'notice-' . $args['type'];
		}

		if ( $args['dismissible'] ) {
			$classes[] = 'is-dismissible';
		}

		if ( ! empty( $args['additional_classes'] ) ) {
			$classes[] = $args['additional_classes'];
		}

		if ( $args['paragraph_wrap'] ) {
			$message = '<p>' . $message . '</p>';
		}

		printf(
			'<div%1$s class="%2$s">%3$s</div>',
			! empty( $args['id'] ) ? ' id="' . esc_attr( $args['id'] ) . '"' : '',
			esc_attr( implode( ' ', $classes ) ),
			wp_kses_post( $message )
		);
	}
}
