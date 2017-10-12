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
	 * @inheritdoc
	 */
	public function display() {
		echo get_called_class();
	}

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
}
