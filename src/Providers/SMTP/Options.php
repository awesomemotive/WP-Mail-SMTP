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
				'logo_url'    => wp_mail_smtp()->assets_url . '/images/providers/smtp.svg',
				'slug'        => 'smtp',
				'title'       => esc_html__( 'Other SMTP', 'wp-mail-smtp' ),
				'description' => sprintf(
					wp_kses(
						/* translators: %s - URL to a related article on WPForms.com. */
						__( 'Use the SMTP details provided by your hosting provider or email service.<br><br>To see recommended settings for the popular services as well as troubleshooting tips, check out our <a href="%s" target="_blank" rel="noopener noreferrer">SMTP documentation</a>.', 'wp-mail-smtp' ),
						array(
							'br' => array(),
							'a'  => array(
								'href'   => array(),
								'rel'    => array(),
								'target' => array(),
							),
						)
					),
					'https://wpmailsmtp.com/docs/how-to-set-up-the-other-smtp-mailer-in-wp-mail-smtp/'
				),
			)
		);
	}
}
