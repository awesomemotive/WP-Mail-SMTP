<?php

namespace WPMailSMTP\Admin;

/**
 * Class WPMS_Admin_Test is part of WPMS_Admin_Area, displays email testing page of the plugin.
 */
class Test extends PageAbstract {

	/**
	 * @var string Slug of a subpage.
	 */
	public $slug = 'test';

	/**
	 * WPMS_Admin constructor.
	 */
	public function __construct() {
	}

	/**
	 * Page title.
	 *
	 * @return string
	 */
	public function get_page_title() {
		return __( 'Email Test', 'wp-mail-smtp' );
	}
}
