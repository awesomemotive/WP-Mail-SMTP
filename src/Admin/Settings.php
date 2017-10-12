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
	 * @inheritdoc
	 */
	public function get_label() {
		return __( 'Settings', 'wp-mail-smtp' );
	}

	/**
	 * @inheritdoc
	 */
	public function get_title() {
		return $this->get_label();
	}
}
