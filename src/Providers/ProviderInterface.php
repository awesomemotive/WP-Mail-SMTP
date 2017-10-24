<?php

namespace WPMailSMTP\Providers;

/**
 * Interface ProviderInterface, shared between all current and future providers.
 * Defines required methods across all providers.
 */
interface ProviderInterface {

	/**
	 * Get the mailer provider slug.
	 *
	 * @return string
	 */
	public function get_slug();

	/**
	 * Get the mailer provider title (or name).
	 *
	 * @return string
	 */
	public function get_title();

	/**
	 * Get the mailer provider description.
	 *
	 * @return string
	 */
	public function get_description();

	/**
	 * Get the mailer provider logo URL.
	 *
	 * @return string
	 */
	public function get_logo_url();

	/**
	 * Output the mailer provider options.
	 */
	public function display_options();
}
