<?php

namespace WPMailSMTP\Providers\Preflight;

use InvalidArgumentException;

/**
 * One thing a preflight established. Carries no English: the client keys its copy on the code.
 *
 * @since 4.10.0
 */
class Finding {

	/**
	 * Positive evidence the input is wrong or unusable.
	 *
	 * @since 4.10.0
	 *
	 * @var string
	 */
	const ERROR = 'error';

	/**
	 * A real, persistent, degraded-but-working state.
	 *
	 * @since 4.10.0
	 *
	 * @var string
	 */
	const WARNING = 'warning';

	/**
	 * The run reached no verdict about the configuration. Blocks a Connected state
	 * without attributing anything to the user.
	 *
	 * @since 4.10.0
	 *
	 * @var string
	 */
	const INCONCLUSIVE = 'inconclusive';

	/**
	 * Enumerated code.
	 *
	 * @since 4.10.0
	 *
	 * @var string
	 */
	private $code;

	/**
	 * Wizard field name, a group of them, or null when no single field is at fault.
	 *
	 * @since 4.10.0
	 *
	 * @var string|string[]|null
	 */
	private $field;

	/**
	 * One of the severity constants.
	 *
	 * @since 4.10.0
	 *
	 * @var string
	 */
	private $severity;

	/**
	 * Finding constructor.
	 *
	 * @since 4.10.0
	 *
	 * @param string               $code     Enumerated code.
	 * @param string|string[]|null $field    Field, field group, or null.
	 * @param string               $severity One of the severity constants.
	 *
	 * @throws InvalidArgumentException When the severity is not one of the constants.
	 */
	public function __construct( $code, $field, $severity ) {

		if ( ! in_array( $severity, [ self::ERROR, self::WARNING, self::INCONCLUSIVE ], true ) ) {
			throw new InvalidArgumentException( 'Unknown preflight severity: ' . $severity ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}

		$this->code     = $code;
		$this->field    = $field;
		$this->severity = $severity;
	}

	/**
	 * Get the enumerated code.
	 *
	 * @since 4.10.0
	 *
	 * @return string
	 */
	public function get_code() {

		return $this->code;
	}

	/**
	 * Get the field, field group, or null.
	 *
	 * @since 4.10.0
	 *
	 * @return string|string[]|null
	 */
	public function get_field() {

		return $this->field;
	}

	/**
	 * Get the severity.
	 *
	 * @since 4.10.0
	 *
	 * @return string
	 */
	public function get_severity() {

		return $this->severity;
	}

	/**
	 * Whether this finding gates the wizard's Continue action.
	 *
	 * @since 4.10.0
	 *
	 * @return bool
	 */
	public function is_blocking() {

		return $this->severity === self::ERROR;
	}

	/**
	 * Get the wire shape.
	 *
	 * @since 4.10.0
	 *
	 * @return array
	 */
	public function to_array() {

		return [
			'code'     => $this->code,
			'field'    => $this->field,
			'severity' => $this->severity,
		];
	}
}
