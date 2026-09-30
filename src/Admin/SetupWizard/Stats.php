<?php

namespace WPMailSMTP\Admin\SetupWizard;

/**
 * Setup Wizard usage stats, shared by both wizard variants.
 *
 * @since 4.10.0
 */
class Stats {

	/**
	 * The WP Option key for storing setup wizard stats.
	 *
	 * @since 4.10.0
	 */
	const OPTION_KEY = 'wp_mail_smtp_setup_wizard_stats';

	/**
	 * Get the Setup Wizard stats.
	 * - launched_time  -> when the Setup Wizard was last launched.
	 * - completed_time -> when the Setup Wizard was last completed.
	 * - was_successful -> if the Setup Wizard was completed successfully.
	 * - mailer         -> mailer slug configured in the Setup Wizard on its last completion attempt.
	 * - type           -> which wizard variant was launched: 'local' or 'hosted'.
	 *
	 * @since 4.10.0
	 *
	 * @return array Always carries every key above, even though a stored record can
	 *               be missing any of them, so callers can index in directly.
	 */
	public static function get() {

		$defaults = [
			'launched_time'  => 0,
			'completed_time' => 0,
			'was_successful' => false,
			'mailer'         => '',
			'type'           => '',
		];

		return array_merge( $defaults, (array) get_option( self::OPTION_KEY, [] ) );
	}

	/**
	 * Update the Setup Wizard stats.
	 *
	 * @since 4.10.0
	 *
	 * @param array $options Take a look at the Stats::get method for the possible array keys.
	 *
	 * @return void
	 */
	public static function update( $options ) {

		update_option( self::OPTION_KEY, array_merge( self::get(), $options ), false );
	}

	/**
	 * Update the completed Setup Wizard stats.
	 *
	 * @since 4.10.0
	 *
	 * @param bool   $was_successful If the Setup Wizard was completed successfully.
	 * @param string $mailer         Mailer slug configured in the Setup Wizard.
	 *
	 * @return void
	 */
	public static function update_completed( $was_successful, $mailer = '' ) {

		self::update(
			[
				'completed_time' => time(),
				'was_successful' => $was_successful,
				'mailer'         => $mailer,
			]
		);
	}
}
