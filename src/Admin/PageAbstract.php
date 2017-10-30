<?php

namespace WPMailSMTP\Admin;

/**
 * Class PageAbstract
 */
abstract class PageAbstract implements PageInterface {

	/**
	 * @var string Slug of a subpage.
	 */
	protected $slug;

	/**
	 * @inheritdoc
	 */
	public function get_link() {
		return esc_url(
			add_query_arg(
				'subpage',
				$this->slug,
				admin_url( 'options-general.php?page=' . Area::SLUG )
			)
		);
	}

	/**
	 * Print the nonce field for specific subpage.
	 */
	public function wp_nonce_field() {
		wp_nonce_field( Area::SLUG . '-' . $this->slug );
	}

	/**
	 * Makes sure that a user was referred from another admin page.
	 * To avoid security exploits.
	 */
	public function check_admin_referer() {
		check_admin_referer( Area::SLUG . '-' . $this->slug );
	}
}
