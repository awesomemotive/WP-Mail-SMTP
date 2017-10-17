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
}
