<?php

namespace WPMailSMTP\Integrations\EmailDetective;

use WPMailSMTP\Options;

/**
 * The client_meta payload sent when an Email Detective test is created.
 *
 * @since 4.10.0
 */
class Meta {

	/**
	 * Collect the metadata for the current site.
	 *
	 * @since 4.10.0
	 *
	 * @return array
	 */
	public static function collect() {

		return [
			'mailer'         => (string) Options::init()->get( 'mail', 'mailer' ),
			'plugin'         => 'wp-mail-smtp',
			'plugin_version' => WPMS_PLUGIN_VER,
			'license_type'   => self::license_type(),
		];
	}

	/**
	 * The license tier.
	 *
	 * @since 4.10.0
	 *
	 * @return string
	 */
	private static function license_type() {

		if ( ! wp_mail_smtp()->is_pro() ) {
			return 'lite';
		}

		// Not Core::get_license_type(): it reports an unregistered Pro build as 'lite'.
		$key  = wp_mail_smtp()->get_license_key();
		$type = strtolower( (string) Options::init()->get( 'license', 'type' ) );

		if ( $key === '' || $type === '' ) {
			return 'paid';
		}

		return $type;
	}
}
