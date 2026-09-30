<?php

namespace WPMailSMTP\SettingsImport;

use WPMailSMTP\Helpers\PluginImportDataRetriever;
use WPMailSMTP\Options;

/**
 * Importing settings from other SMTP plugins.
 *
 * @since 4.10.0
 */
class SettingsImport {

	/**
	 * Supported plugins: slug to the option name that holds its settings.
	 *
	 * @since 4.10.0
	 *
	 * @var array
	 */
	private const OPTIONS = [
		'easy-smtp'        => 'swpsmtp_options',
		'post-smtp-mailer' => 'postman_options',
		'smtp-mailer'      => 'smtp_mailer_options',
		'wp-smtp'          => 'wp_smtp_options',
		'fluent-smtp'      => 'fluentmail-settings',
	];

	/**
	 * Detected plugins as slug to display name, in catalog order.
	 *
	 * @since 4.10.0
	 *
	 * @return array
	 */
	public function get_detected_plugins() {

		$names    = $this->get_plugin_names();
		$detected = [];

		foreach ( self::OPTIONS as $slug => $option ) {
			if ( ! empty( get_option( $option ) ) ) {
				$detected[ $slug ] = $names[ $slug ];
			}
		}

		return $detected;
	}

	/**
	 * Detected plugin slugs as a plain list.
	 *
	 * @since 4.10.0
	 *
	 * @return array
	 */
	public function get_detected_plugin_slugs() {

		return array_keys( $this->get_detected_plugins() );
	}

	/**
	 * Import the given plugin's settings into this plugin.
	 *
	 * @since 4.10.0
	 *
	 * @param string $slug Supported plugin slug.
	 *
	 * @return bool Whether anything was imported.
	 */
	public function import_from_plugin( $slug ) {

		if ( ! array_key_exists( $slug, $this->get_detected_plugins() ) ) {
			return false;
		}

		$settings = ( new PluginImportDataRetriever( $slug ) )->get();

		if ( empty( $settings ) ) {
			return false;
		}

		Options::init()->set( $settings, false, false );

		/**
		 * Fires after settings have been imported from another SMTP plugin.
		 *
		 * @since 4.10.0
		 *
		 * @param string $slug Slug of the plugin the settings came from.
		 */
		do_action( 'wp_mail_smtp_settings_import_from_plugin_after', $slug ); // phpcs:ignore WPForms.PHP.ValidateHooks.InvalidHookName -- The namespace and the class are both SettingsImport; the name carries the segment once.

		return true;
	}

	/**
	 * Display names for the supported plugins.
	 *
	 * @since 4.10.0
	 *
	 * @return array
	 */
	private function get_plugin_names() {

		return [
			'easy-smtp'        => __( 'Easy WP SMTP', 'wp-mail-smtp' ),
			'post-smtp-mailer' => __( 'Post SMTP', 'wp-mail-smtp' ),
			'smtp-mailer'      => __( 'SMTP Mailer', 'wp-mail-smtp' ),
			'wp-smtp'          => __( 'WP SMTP', 'wp-mail-smtp' ),
			'fluent-smtp'      => __( 'FluentSMTP', 'wp-mail-smtp' ),
		];
	}
}
