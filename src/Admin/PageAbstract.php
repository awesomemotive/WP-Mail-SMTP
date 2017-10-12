<?php

namespace WPMailSMTP\Admin;

/**
 * Class PageAbstract
 */
abstract class PageAbstract implements PageInterface {

	/**
	 * @var string Slug of a subpage.
	 */
	public $slug;

	/**
	 * Page content.
	 *
	 * @overwrite
	 */
	public function display() {
		echo get_called_class();
	}

	/**
	 * URL to a page.
	 *
	 * @return string
	 */
	public function get_page_link() {
		return esc_url(
			add_query_arg(
				'subpage',
				$this->slug,
				admin_url( 'options-general.php?page=' . Area::SLUG )
			)
		);
	}
}
