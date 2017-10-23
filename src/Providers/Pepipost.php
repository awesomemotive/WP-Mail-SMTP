<?php

namespace WPMailSMTP\Providers;

/**
 * Class Pepipost
 */
class Pepipost extends ProviderAbstract {

	/**
	 * @inheritdoc
	 */
	public function get_logo_url() {
		return wp_mail_smtp()->plugin_url . '/assets/images/pepipost.png';
	}

	/**
	 * @inheritdoc
	 */
	public function get_slug() {
		return 'pepipost';
	}

	/**
	 * @inheritdoc
	 */
	public function get_title() {
		return __( 'Pepipost', 'wp-mail-smtp' );
	}

	/**
	 * @inheritdoc
	 */
	public function get_description() {
		return '';
	}

}
