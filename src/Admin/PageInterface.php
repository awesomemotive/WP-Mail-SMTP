<?php

namespace WPMailSMTP\Admin;

/**
 * Class PageInterface defines what should be in each page class.
 */
interface PageInterface {

	/**
	 * URL to a subpage.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_link();

	/**
	 * Title of a subpage.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_title();

	/**
	 * Link label of a subpage.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_label();

	/**
	 * Subpage content.
	 *
	 * @since 1.0.0
	 */
	public function display();

	/**
	 * Process subpage form submission.
	 *
	 * @since 1.0.0
	 *
	 * @param array $data Raw $_POST data specific for the plugin.
	 */
	public function process( $data );
}
