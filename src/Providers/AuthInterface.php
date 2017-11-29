<?php

namespace WPMailSMTP\Providers;

/**
 * Interface AuthInterface
 *
 * @package WPMailSMTP\Providers
 */
interface AuthInterface {

	/**
	 * Do something for this Auth implementation.
	 */
	public function process();

}
