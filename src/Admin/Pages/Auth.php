<?php

namespace WPMailSMTP\Admin\Pages;

use WPMailSMTP\Options;

/**
 * Class Auth
 *
 * @package WPMailSMTP\Admin\Pages
 */
class Auth {

	/**
	 * @var string Slug of a tab.
	 */
	protected $slug = 'auth';

	/**
	 * Launch mailer specific Auth logic.
	 */
	public function process_auth() {

		$options = new Options();
		$auth    = wp_mail_smtp()->get_providers()->get_auth( $options->get( 'mail', 'mailer' ) );

		$auth->process();

		// TODO: remove this when ready.
		exit;
	}

	/**
	 * Return nothing, as we don't need this functionality.
	 */
	public function get_label() {
		return '';
	}

	/**
	 * Return nothing, as we don't need this functionality.
	 */
	public function get_title() {
		return '';
	}

	/**
	 * Do nothing, as we don't need this functionality.
	 */
	public function display() {
	}
}
