<?php

namespace WPMailSMTP\Providers;

/**
 * Class SMTP
 */
class SMTP extends ProviderAbstract {

	/**
	 * Pepipost constructor.
	 */
	public function __construct() {

		parent::__construct(
			array(
				'logo_url' => wp_mail_smtp()->plugin_url . '/assets/images/smtp.png',
				'slug'     => 'smtp',
				'title'    => __( 'Other SMTP', 'wp-mail-smtp' ),
			)
		);
	}
}
