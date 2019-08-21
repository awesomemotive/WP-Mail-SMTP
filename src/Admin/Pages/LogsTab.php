<?php

namespace WPMailSMTP\Admin\Pages;

use WPMailSMTP\Admin\Area;
use WPMailSMTP\Admin\PageAbstract;

/**
 * Class LogsTab is a placeholder for Lite users and redirects them to Email Log page.
 *
 * @since 1.6.0
 */
class LogsTab extends PageAbstract {

	/**
	 * Part of the slug of a tab.
	 *
	 * @since 1.6.0
	 *
	 * @var string
	 */
	protected $slug = 'logs';

	/**
	 * @inheritdoc
	 *
	 * @since 1.6.0
	 */
	public function get_label() {

		return esc_html__( 'Email Log', 'wp-mail-smtp' );
	}

	/**
	 * @inheritdoc
	 *
	 * @since 1.6.0
	 */
	public function get_title() {

		return $this->get_label();
	}

	/**
	 * Custom URL for this tab, redirects to Email Log page.
	 *
	 * @since 1.6.0
	 *
	 * @return string
	 */
	public function get_link() {

		return wp_mail_smtp()->get_admin()->get_admin_page_url( Area::SLUG . '-' . $this->slug );
	}

	/**
	 * Not used as we are simply redirecting users.
	 *
	 * @since 1.6.0
	 */
	public function display() {
	}

	/**
	 * Not used as we are simply redirecting users.
	 *
	 * @since 1.6.0
	 *
	 * @param array $data
	 */
	public function process_post( $data ) {
	}
}
