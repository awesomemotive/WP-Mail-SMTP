<?php

namespace WPMailSMTP\Admin\SetupWizard;

/**
 * The wizard step a Lite to Pro upgrade started from, kept until Pro is active.
 *
 * @since 4.10.4
 */
class UpgradeReturn {

	/**
	 * Transient holding the step and its wizard.
	 *
	 * @since 4.10.4
	 *
	 * @var string
	 */
	private const TRANSIENT = 'wp_mail_smtp_setup_wizard_upgrade_return';

	/**
	 * How long a stored step stays valid.
	 *
	 * @since 4.10.4
	 *
	 * @var int
	 */
	private const LIFETIME = 30 * DAY_IN_SECONDS;

	/**
	 * Step used when no valid one is given.
	 *
	 * @since 4.10.4
	 *
	 * @var string
	 */
	private const DEFAULT_STEP = '/step/license';

	/**
	 * A wizard route, or the License step for anything else.
	 *
	 * @since 4.10.4
	 *
	 * @param mixed $step Requested step, such as `/step/configure_mailer/gmail`.
	 *
	 * @return string
	 */
	public function get_valid_step( $step ) {

		// The step becomes a URL fragment, so only a plain route survives.
		if ( is_string( $step ) && preg_match( '#^/step/[a-z_]+(/[a-z0-9_]+)?\z#', $step ) ) {
			return $step;
		}

		return self::DEFAULT_STEP;
	}

	/**
	 * The URL that reopens the given wizard at the given step.
	 *
	 * @since 4.10.4
	 *
	 * @param string $step    Valid wizard step.
	 * @param string $variant Wizard the upgrade started in: 'hosted' or 'local'.
	 *
	 * @return string
	 */
	public function get_url( $step, $variant ) {

		return Launcher::get_url( $this->get_valid_variant( $variant ), [ 'upgrade-redirect' => '1' ], $step );
	}

	/**
	 * Store the step to return to and the wizard it belongs to.
	 *
	 * @since 4.10.4
	 *
	 * @param string $step    Valid wizard step.
	 * @param string $variant Wizard the upgrade started in: 'hosted' or 'local'.
	 *
	 * @return void
	 */
	public function remember( $step, $variant ) {

		set_transient(
			self::TRANSIENT,
			[
				'step'    => $step,
				'variant' => $variant,
			],
			self::LIFETIME
		);
	}

	/**
	 * The wizard the stored step belongs to.
	 *
	 * @since 4.10.4
	 *
	 * @return string
	 */
	public function get_variant() {

		$stored = get_transient( self::TRANSIENT );

		return $this->get_valid_variant( is_array( $stored ) && isset( $stored['variant'] ) ? $stored['variant'] : '' );
	}

	/**
	 * The hosted variant, or the bundled one for anything else.
	 *
	 * @since 4.10.4
	 *
	 * @param mixed $variant Requested variant.
	 *
	 * @return string
	 */
	private function get_valid_variant( $variant ) {

		return $variant === Launcher::VARIANT_HOSTED ? Launcher::VARIANT_HOSTED : Launcher::VARIANT_LOCAL;
	}

	/**
	 * The stored step, empty when there is none.
	 *
	 * @since 4.10.4
	 *
	 * @return string
	 */
	public function get_step() {

		$stored = get_transient( self::TRANSIENT );

		if ( empty( $stored ) ) {
			return '';
		}

		if ( ! is_array( $stored ) || empty( $stored['step'] ) || $this->get_valid_step( $stored['step'] ) !== $stored['step'] ) {
			$this->forget();

			return '';
		}

		return $stored['step'];
	}

	/**
	 * Remove the stored step.
	 *
	 * @since 4.10.4
	 *
	 * @return void
	 */
	public function forget() {

		delete_transient( self::TRANSIENT );
	}
}
