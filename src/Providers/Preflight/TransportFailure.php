<?php

namespace WPMailSMTP\Providers\Preflight;

use RuntimeException;

/**
 * Unwinds a run when a response carries no verdict about the configuration.
 *
 * @since 4.10.0
 */
class TransportFailure extends RuntimeException {

	/**
	 * What the gate made of the response.
	 *
	 * @since 4.10.0
	 *
	 * @var Finding
	 */
	private $finding;

	/**
	 * TransportFailure constructor.
	 *
	 * @since 4.10.0
	 *
	 * @param Finding $finding What the gate made of the response.
	 */
	public function __construct( Finding $finding ) {

		parent::__construct( $finding->get_code() );

		$this->finding = $finding;
	}

	/**
	 * Get the finding that ended the run.
	 *
	 * @since 4.10.0
	 *
	 * @return Finding
	 */
	public function get_finding() {

		return $this->finding;
	}
}
