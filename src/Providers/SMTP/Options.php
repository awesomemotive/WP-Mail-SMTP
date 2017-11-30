<?php

namespace WPMailSMTP\Providers\SMTP;

use WPMailSMTP\Providers\OptionAbstract;

/**
 * Class SMTP.
 *
 * @since 1.0.0
 */
class Options extends OptionAbstract {

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
