<?php

namespace WPMailSMTP\Providers\Preflight;

/**
 * A read-only check that establishes whether one mailer configuration could send.
 *
 * @since 4.10.0
 */
interface PreflightInterface {

	/**
	 * Run the check.
	 *
	 * @since 4.10.0
	 *
	 * @param array $config Mailer options keyed by the wizard's field names.
	 *
	 * @return Findings
	 */
	public function run( $config );
}
