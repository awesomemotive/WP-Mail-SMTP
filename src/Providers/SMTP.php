<?php

namespace WPMailSMTP\Providers;

/**
 * Class SMTP
 */
class SMTP extends ProviderAbstract {

	/**
	 * @inheritdoc
	 */
	public function get_logo_url() {
		return wp_mail_smtp()->plugin_url . '/assets/images/smtp.png';
	}

	/**
	 * @inheritdoc
	 */
	public function get_slug() {
		return 'smtp';
	}

	/**
	 * @inheritdoc
	 */
	public function get_title() {
		return __( 'Other SMTP', 'wp-mail-smtp' );
	}

	/**
	 * @inheritdoc
	 */
	public function get_description() {
		return '';
	}

}
