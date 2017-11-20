<?php

namespace WPMailSMTP\Admin;

/**
 * Class PageAbstract
 */
abstract class PageAbstract implements PageInterface {

	/**
	 * @var string Slug of a tab.
	 */
	protected $slug;

	/**
	 * @inheritdoc
	 */
	public function get_link() {
		return esc_url(
			add_query_arg(
				'tab',
				$this->slug,
				admin_url( 'options-general.php?page=' . Area::SLUG )
			)
		);
	}

	/**
	 * Print the nonce field for a specific tab.
	 *
	 * @since 1.0.0
	 */
	public function wp_nonce_field() {
		wp_nonce_field( Area::SLUG . '-' . $this->slug );
	}

	/**
	 * Make sure that a user was referred from plugin admin page.
	 * To avoid security problems.
	 *
	 * @since 1.0.0
	 */
	public function check_admin_referer() {
		check_admin_referer( Area::SLUG . '-' . $this->slug );
	}
}
