<?php

namespace WPMailSMTP\Providers;

/**
 * Interface ProviderInterface, shared between all current and future providers.
 * Defines required methods across all providers.
 */
interface ProviderInterface {

	/**
	 * @return string
	 */
	public function get_slug();

	/**
	 * @return string
	 */
	public function get_title();

	/**
	 * @return string
	 */
	public function get_description();

	/**
	 * @return string
	 */
	public function get_logo_url();

	public function display_options();
}
