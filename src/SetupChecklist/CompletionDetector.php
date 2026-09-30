<?php

namespace WPMailSMTP\SetupChecklist;

use WPMailSMTP\DomainCheckState;
use WPMailSMTP\Options;
use WPMailSMTP\PartnerPlugins\Catalog;
use WPMailSMTP\SettingsImport\SettingsImport;
use WPMailSMTP\UsageTracking\UsageTracking;

/**
 * Setup Checklist completion detector.
 *
 * @since 4.10.0
 */
class CompletionDetector {

	/**
	 * Per-site state store.
	 *
	 * @since 4.10.0
	 *
	 * @var State
	 */
	protected $state;

	/**
	 * The settings-import domain: which plugins have importable settings.
	 *
	 * @since 4.10.0
	 *
	 * @var SettingsImport
	 */
	protected $settings_import;

	/**
	 * Constructor.
	 *
	 * @since 4.10.0
	 *
	 * @param State          $state           Per-site state store.
	 * @param SettingsImport $settings_import Settings-import domain.
	 */
	public function __construct( State $state, SettingsImport $settings_import ) {

		$this->state           = $state;
		$this->settings_import = $settings_import;
	}

	/**
	 * Whether the given checklist item is complete.
	 *
	 * @since 4.10.0
	 *
	 * @param string $item_id Checklist item ID from {@see Config}.
	 *
	 * @return bool
	 */
	public function is_complete( $item_id ) {

		$checks = $this->get_checks();

		if ( ! isset( $checks[ $item_id ] ) || ! is_callable( $checks[ $item_id ] ) ) {
			return false;
		}

		return (bool) call_user_func( $checks[ $item_id ] );
	}

	/**
	 * Whether the given checklist item applies to this site.
	 *
	 * @since 4.10.0
	 *
	 * @param string $item_id Checklist item ID from {@see Config}.
	 *
	 * @return bool
	 */
	public function is_applicable( $item_id ) {

		$conditions = $this->get_conditions();

		if ( isset( $conditions[ $item_id ] ) && is_callable( $conditions[ $item_id ] ) ) {
			return (bool) call_user_func( $conditions[ $item_id ] );
		}

		return isset( $this->get_checks()[ $item_id ] );
	}

	/**
	 * Completion callbacks keyed by item ID.
	 *
	 * @since 4.10.0
	 *
	 * @return array
	 */
	protected function get_checks() {

		$checks = [
			'setup_mailer'          => [ $this, 'is_mailer_configured' ],
			'import_settings'       => [ $this->state, 'is_settings_imported' ],
			'test_email'            => [ $this, 'is_test_email_sent' ],
			'domain_setup'          => [ $this, 'is_domain_setup_successful' ],
			'wpvibe'                => [ $this, 'is_wpvibe_configured' ],
			'code_snippets'         => [ $this, 'is_code_snippets_visited' ],
			'wpconsent'             => [ $this, 'is_wpconsent_configured' ],
			'smart_recommendations' => [ $this->state, 'is_newsletter_subscribed' ],
			'usage_tracking'        => [ $this, 'is_usage_tracking_enabled' ],
		];

		/**
		 * Filter the checklist completion callbacks.
		 *
		 * @since 4.10.0
		 *
		 * @param array $checks Callbacks keyed by checklist item ID.
		 */
		return (array) apply_filters( 'wp_mail_smtp_setup_checklist_completion_detector_get_checks', $checks );
	}

	/**
	 * Applicability callbacks keyed by item ID, for conditional items only.
	 *
	 * @since 4.10.0
	 *
	 * @return array
	 */
	protected function get_conditions() {

		$conditions = [
			'import_settings' => [ $this, 'can_import_settings' ],
			'domain_setup'    => [ DomainCheckState::class, 'has_issues_history' ],
			'usage_tracking'  => [ $this, 'can_opt_into_usage_tracking' ],
		];

		/**
		 * Filter the checklist applicability callbacks.
		 *
		 * @since 4.10.0
		 *
		 * @param array $conditions Callbacks keyed by checklist item ID.
		 */
		return (array) apply_filters( 'wp_mail_smtp_setup_checklist_completion_detector_get_conditions', $conditions );
	}

	/**
	 * Whether a test email has gone through on a configured mailer.
	 *
	 * @since 4.10.0
	 *
	 * @return bool
	 */
	protected function is_test_email_sent() {

		// The flag is written by any successful send on the primary connection, and the
		// Default (PHP) mailer reports itself complete, so the flag alone means little.
		return $this->is_mailer_configured() && $this->state->is_test_email_sent();
	}

	/**
	 * Whether the primary connection has a real mailer, set up completely.
	 *
	 * @since 4.10.0
	 *
	 * @return bool
	 */
	protected function is_mailer_configured() {

		$connection = wp_mail_smtp()->get_connections_manager()->get_primary_connection();

		if ( in_array( $connection->get_mailer_slug(), [ '', 'mail' ], true ) ) {
			return false;
		}

		$mailer = $connection->get_mailer();

		return ! empty( $mailer ) && $mailer->is_mailer_complete();
	}

	/**
	 * Whether the import item has anything to offer.
	 *
	 * @since 4.10.0
	 *
	 * @return bool
	 */
	protected function can_import_settings() {

		if ( $this->state->is_settings_imported() ) {
			return true;
		}

		if ( empty( $this->settings_import->get_detected_plugins() ) ) {
			return false;
		}

		// Importing overwrites the mailer and its credentials, so it is not offered
		// to a site that has already been set up.
		return ! $this->is_plugin_configured();
	}

	/**
	 * Whether this site has been set up already, by any measure.
	 *
	 * @since 4.10.0
	 *
	 * @return bool
	 */
	protected function is_plugin_configured() {

		if ( $this->is_mailer_configured() ) {
			return true;
		}

		$options = $this->get_options();

		foreach ( $this->get_option_defaults() as $group => $keys ) {
			foreach ( $keys as $key => $default ) {
				// Loose, so a stored '1' still matches a boolean default.
				if ( $options->get( $group, $key ) != $default ) { // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison, Universal.Operators.StrictComparisons.LooseNotEqual
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * The options store to read settings from.
	 *
	 * @since 4.10.0
	 *
	 * @return Options
	 */
	protected function get_options() {

		return Options::init();
	}

	/**
	 * The values this plugin writes on activation, minus the two whose defaults
	 * track the site's own admin email and name: those drift on their own, so a
	 * difference there says nothing about whether the user configured anything.
	 *
	 * @since 4.10.0
	 *
	 * @return array
	 */
	protected function get_option_defaults() {

		$defaults = Options::get_defaults();

		unset( $defaults['mail']['from_email'], $defaults['mail']['from_name'] );

		return $defaults;
	}

	/**
	 * Whether the last domain check came back successful.
	 *
	 * @since 4.10.0
	 *
	 * @return bool
	 */
	protected function is_domain_setup_successful() {

		return DomainCheckState::get_state() === DomainCheckState::CLEAN;
	}

	/**
	 * Whether the WPVibe plugin is active and set up.
	 *
	 * @since 4.10.0
	 *
	 * @return bool
	 */
	protected function is_wpvibe_configured() {

		return $this->is_recommended_plugin_configured( 'wpvibe' );
	}

	/**
	 * Whether the WPConsent plugin is active and set up.
	 *
	 * @since 4.10.0
	 *
	 * @return bool
	 */
	protected function is_wpconsent_configured() {

		return $this->is_recommended_plugin_configured( 'wpconsent' );
	}

	/**
	 * Whether the Code Snippets page has been opened.
	 *
	 * @since 4.10.0
	 *
	 * @return bool
	 */
	protected function is_code_snippets_visited() {

		return $this->state->has_visited( 'code_snippets' );
	}

	/**
	 * Whether usage tracking is switched on.
	 *
	 * @since 4.10.0
	 *
	 * @return bool
	 */
	protected function is_usage_tracking_enabled() {

		return ( new UsageTracking() )->is_enabled();
	}

	/**
	 * Whether the usage-tracking item should be offered at all.
	 *
	 * @since 4.10.0
	 *
	 * @return bool
	 */
	protected function can_opt_into_usage_tracking() {

		if ( wp_mail_smtp()->is_pro() ) {
			return false;
		}

		return ! $this->state->is_usage_tracking_wizard_optin();
	}

	/**
	 * Whether a partner plugin is active and set up.
	 *
	 * @since 4.10.0
	 *
	 * @param string $slug Catalog slug.
	 *
	 * @return bool
	 */
	protected function is_recommended_plugin_configured( $slug ) {

		$plugin = ( new Catalog() )->get( $slug );

		return $plugin !== null && $plugin->is_configured();
	}
}
