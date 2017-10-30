<?php

namespace WPMailSMTP\Providers;

/**
 * Interface ProviderInterface, shared between all current and future providers.
 * Defines required methods across all providers.
 */
interface ProviderInterface {

	/**
	 * Get the mailer provider slug.
	 * Already escaped.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_slug();

	/**
	 * Get the mailer provider title (or name).
	 * Already escaped.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_title();

	/**
	 * Get the mailer provider description.
	 * Already escaped.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_description();

	/**
	 * Get the mailer provider logo URL.
	 * Already escaped.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_logo_url();

	/**
	 * Output the mailer provider options.
	 *
	 * @since 1.0.0
	 */
	public function display_options();
}
