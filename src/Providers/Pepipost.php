<?php

namespace WPMailSMTP\Providers;

/**
 * Class Pepipost
 */
class Pepipost extends ProviderAbstract {

	/**
	 * Pepipost constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		parent::__construct(
			array(
				'logo_url' => wp_mail_smtp()->plugin_url . '/assets/images/pepipost.png',
				'slug'     => 'pepipost',
				'title'    => esc_html__( 'Pepipost', 'wp-mail-smtp' ),
			)
		);
	}
}
