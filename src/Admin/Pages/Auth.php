<?php

namespace WPMailSMTP\Admin\Pages;

use WPMailSMTP\Options;
use WPMailSMTP\Providers\AuthAbstract;

/**
 * Class Auth.
 *
 * @since 1.0.0
 */
class Auth {

	/**
	 * @var string Slug of a tab.
	 */
	protected $slug = 'auth';

	/**
	 * Launch mailer specific Auth logic.
	 *
	 * @since 1.0.0
	 */
	public function process_auth() {

		$auth = wp_mail_smtp()->get_providers()->get_auth( Options::init()->get( 'mail', 'mailer' ) );

		if ( $auth && $auth instanceof AuthAbstract ) {
			$auth->process();
		}
	}

	/**
	 * Return nothing, as we don't need this functionality.
	 *
	 * @since 1.0.0
	 */
	public function get_label() {
		return '';
	}

	/**
	 * Return nothing, as we don't need this functionality.
	 *
	 * @since 1.0.0
	 */
	public function get_title() {
		return '';
	}

	/**
	 * Do nothing, as we don't need this functionality.
	 *
	 * @since 1.0.0
	 */
	public function display() {
	}
}
