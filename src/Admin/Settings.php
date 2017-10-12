<?php

namespace WPMailSMTP\Admin;

/**
 * Class Settings is part of Area, displays general settings of the plugin.
 */
class Settings extends PageAbstract {

	/**
	 * @var string Slug of a subpage.
	 */
	public $slug = 'settings';

	/**
	 * Settings constructor.
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
