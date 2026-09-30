<?php

namespace WPMailSMTP\Providers\Preflight;

/**
 * Shared behaviour for every preflight, whether it speaks HTTP or SMTP.
 *
 * @since 4.10.0
 */
abstract class PreflightAbstract implements PreflightInterface {

	/**
	 * Seconds per operation. Ten rather than five because a rejected SMTP AUTH on a
	 * tarpitting host measured 5.9s, and reporting that as a timeout would name the
	 * wrong field.
	 *
	 * @since 4.10.0
	 *
	 * @var int
	 */
	const TIMEOUT = 10;

	/**
	 * Build an error finding.
	 *
	 * @since 4.10.0
	 *
	 * @param string               $code  Enumerated code.
	 * @param string|string[]|null $field Field or field group at fault.
	 *
	 * @return Finding
	 */
	protected function error( $code, $field = null ) {

		return new Finding( $code, $field, Finding::ERROR );
	}

	/**
	 * Build a warning finding.
	 *
	 * A field on a non-error finding is a focus hint, not an assertion that the value is wrong.
	 *
	 * @since 4.10.0
	 *
	 * @param string               $code  Enumerated code.
	 * @param string|string[]|null $field Field the warning concerns.
	 *
	 * @return Finding
	 */
	protected function warning( $code, $field = null ) {

		return new Finding( $code, $field, Finding::WARNING );
	}

	/**
	 * Build an inconclusive finding.
	 *
	 * A field on a non-error finding is a focus hint, not an assertion that the value is wrong.
	 *
	 * @since 4.10.0
	 *
	 * @param string               $code  Enumerated code.
	 * @param string|string[]|null $field Field the run could reach no verdict about.
	 *
	 * @return Finding
	 */
	protected function inconclusive( $code, $field = null ) {

		return new Finding( $code, $field, Finding::INCONCLUSIVE );
	}

	/**
	 * The verdict for a configuration that left nothing to probe.
	 *
	 * @since 4.10.0
	 *
	 * @return Findings
	 */
	protected function incomplete_config() {

		$findings = Findings::none();

		$findings->add( $this->inconclusive( Code::CONFIG_INCOMPLETE ) );

		return $findings;
	}

	/**
	 * Read the fields this check sends, as the save path will store them.
	 *
	 * Each check knows its own fields, so each one casts and checks its own. `Options` stores
	 * every credential field but the SMTP password through `sanitize_text_field()`, and a probe
	 * reading the submitted bytes instead would test a value the site is never going to send.
	 *
	 * A field the wizard's form will not submit empty, arriving empty, is a request no client of
	 * this check produces, so the run stops rather than asking a provider about a value it was
	 * never given.
	 *
	 * @since 4.10.0
	 *
	 * @param array $config Mailer options as submitted.
	 *
	 * @return array|null Null when a value leaves nothing to probe.
	 */
	abstract protected function sanitize_config( $config );
}
