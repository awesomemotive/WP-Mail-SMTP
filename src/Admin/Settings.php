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

	/**
	 * @inheritdoc
	 */
	public function display() {
		pvar( get_called_class() );
	}

	/**
	 * @inheritdoc
	 */
	public function process( $data ) {
	}
}
