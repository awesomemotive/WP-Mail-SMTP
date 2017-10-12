<?php

namespace WPMailSMTP\Admin;

/**
 * Class Test is part of Area, displays email testing page of the plugin.
 */
class Test extends PageAbstract {

	/**
	 * @var string Slug of a subpage.
	 */
	public $slug = 'test';

	/**
	 * Test constructor.
	 */
	public function __construct() {
	}

	/**
	 * @inheritdoc
	 */
	public function get_label() {
		return __( 'Email Test', 'wp-mail-smtp' );
	}

	/**
	 * @inheritdoc
	 */
	public function get_title() {
		return __( 'Send a Test Email', 'wp-mail-smtp' );
	}
}
