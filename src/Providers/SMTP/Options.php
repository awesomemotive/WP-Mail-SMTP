<?php

namespace WPMailSMTP\Providers\SMTP;

use WPMailSMTP\Providers\OptionsAbstract;

/**
 * Class SMTP.
 *
 * @since 1.0.0
 */
class Options extends OptionsAbstract {

	/**
	 * SMTP constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		parent::__construct(
			array(
				'logo_url'    => wp_mail_smtp()->plugin_url . '/assets/images/smtp.png',
				'slug'        => 'smtp',
				'title'       => esc_html__( 'Other SMTP', 'wp-mail-smtp' ),
				/* translators: %1$s - opening link tag; %2$s - closing link tag. */
				'description' => sprintf(
					wp_kses(
						__( 'Use the SMTP details provided by your hosting provider or email service.<br><br>To see recommended settings for the popular services as well as troubleshooting tips, check out our %1$sSMTP documentation%2$s.', 'wp-mail-smtp' ),
						array(
							'br' => array(),
							'a'  => array(
								'href'   => array(),
								'rel'    => array(),
								'target' => array(),
							),
						)
					),
					'<a href="https://wpforms.com/docs/how-to-set-up-smtp-using-the-wp-mail-smtp-plugin/" target="_blank" rel="noopener noreferrer">',
					'</a>'
				),
			)
		);
	}
}
