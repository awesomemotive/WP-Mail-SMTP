<?php

namespace WPMailSMTP\Providers;

/**
 * Interface AuthInterface
 *
 * @since 1.0.0
 *
 * @package WPMailSMTP\Providers
 */
interface AuthInterface {

	/**
	 * Do something for this Auth implementation.
	 *
	 * @since 1.0.0
	 */
	public function process();

}
