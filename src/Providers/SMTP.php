<?php

namespace WPMailSMTP\Providers;

/**
 * Class SMTP
 */
class SMTP extends ProviderAbstract {

	/**
	 * SMTP constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		parent::__construct(
			array(
				'logo_url' => wp_mail_smtp()->plugin_url . '/assets/images/smtp.png',
				'slug'     => 'smtp',
				'title'    => esc_html__( 'Other SMTP', 'wp-mail-smtp' ),
			)
		);
	}
}
