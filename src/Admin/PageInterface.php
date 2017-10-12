<?php

namespace WPMailSMTP\Admin;

/**
 * Class PageInterface defines what should be in each page class.
 */
interface PageInterface {

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

	/**
	 * Subpage content.
	 */
	public function display();

	/**
	 * Process subpage form submission.
	 *
	 * @param array $data $_POST data specific for the plugin.
	 */
	public function process( $data );
}
