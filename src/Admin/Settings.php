<?php

namespace WPMailSMTP\Admin;

/**
 * Class WPMS_Admin_Settings is part of WPMS_Admin_Area, displays general settings of the plugin.
 */
class Settings extends PageAbstract {

	/**
	 * @var string Slug of a subpage.
	 */
	public $slug = 'settings';

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
		return __( 'Settings', 'wp-mail-smtp' );
	}
}
