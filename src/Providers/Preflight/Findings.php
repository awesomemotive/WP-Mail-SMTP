<?php

namespace WPMailSMTP\Providers\Preflight;

/**
 * Everything one preflight run established, in the order it was established.
 *
 * @since 4.10.0
 */
class Findings {

	/**
	 * Findings in insertion order.
	 *
	 * @since 4.10.0
	 *
	 * @var Finding[]
	 */
	private $findings = [];

	/**
	 * Start an empty run.
	 *
	 * @since 4.10.0
	 *
	 * @return Findings
	 */
	public static function none() {

		return new self();
	}

	/**
	 * Append a finding.
	 *
	 * @since 4.10.0
	 *
	 * @param Finding $finding Finding to append.
	 */
	public function add( Finding $finding ) {

		$this->findings[] = $finding;
	}

	/**
	 * Get every finding.
	 *
	 * @since 4.10.0
	 *
	 * @return Finding[]
	 */
	public function all() {

		return $this->findings;
	}

	/**
	 * Whether the run established nothing to report.
	 *
	 * @since 4.10.0
	 *
	 * @return bool
	 */
	public function is_ok() {

		return empty( $this->findings );
	}

	/**
	 * Whether any finding gates the wizard's Continue action.
	 *
	 * @since 4.10.0
	 *
	 * @return bool
	 */
	public function has_blocking() {

		foreach ( $this->findings as $finding ) {
			if ( $finding->is_blocking() ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Whether the run reached no verdict.
	 *
	 * One inconclusive conjunct means the run as a whole reached no verdict.
	 *
	 * @since 4.10.0
	 *
	 * @return bool
	 */
	public function is_inconclusive() {

		foreach ( $this->findings as $finding ) {
			if ( $finding->get_severity() === Finding::INCONCLUSIVE ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get the wire shape.
	 *
	 * @since 4.10.0
	 *
	 * @return array
	 */
	public function to_array() {

		return array_map(
			function ( Finding $finding ) {
				return $finding->to_array();
			},
			$this->findings
		);
	}
}
