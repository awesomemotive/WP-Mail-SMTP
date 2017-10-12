<?php

namespace WPMailSMTP\Admin;

/**
 * Class PageInterface defines what should be in each page class.
 */
interface PageInterface {

	/**
	 * Subpage content.
	 */
	public function display();

	/**
	 * URL to a subpage.
	 *
	 * @return string
	 */
	public function get_link();

	/**
	 * Title of a subpage.
	 *
	 * @return string
	 */
	public function get_title();

	/**
	 * Link label of a subpage.
	 *
	 * @return string
	 */
	public function get_label();
}
