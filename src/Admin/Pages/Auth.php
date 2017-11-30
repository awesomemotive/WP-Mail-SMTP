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

		$auth = wp_mail_smtp()->get_providers()->get_auth( Options::init()->get( 'mail', 'mailer' ) );

		$auth->process();
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
