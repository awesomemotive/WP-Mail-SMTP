<?php

namespace WPMailSMTP\SetupChecklist;

use WPMailSMTP\ConnectionInterface;
use WPMailSMTP\TestEmail\TestEmail;
use WPMailSMTP\UsageTracking\UsageTracking;

/**
 * Setup Checklist per-site state.
 *
 * @since 4.10.0
 */
class State {

	/**
	 * Option name storing the checklist state blob.
	 *
	 * @since 4.10.0
	 *
	 * @var string
	 */
	const OPTION = 'wp_mail_smtp_setup_checklist';

	/**
	 * Register hooks.
	 *
	 * @since 4.10.0
	 */
	public function hooks() {

		add_action( 'wp_mail_smtp_test_email_send_after', [ $this, 'record_test_email_result' ] );
		add_action( 'wp_mail_smtp_newsletter_subscribe_after', [ $this, 'set_newsletter_subscribed' ] );
		add_action( 'wp_mail_smtp_settings_import_from_plugin_after', [ $this, 'set_settings_imported' ] );
		add_action( 'wp_mail_smtp_admin_setup_wizard_update_settings', [ $this, 'maybe_flag_wizard_usage_tracking_optin' ] );
		add_action( 'admin_init', [ $this, 'maybe_record_visit' ] );
	}

	/**
	 * Record that a test email went through.
	 *
	 * @since 4.10.0
	 *
	 * @param TestEmail $test_email The test email that just ran.
	 */
	public function record_test_email_result( $test_email ) {

		if ( ! $test_email instanceof TestEmail ) {
			return;
		}

		$connection = $test_email->get_connection();

		if ( $connection instanceof ConnectionInterface && ! $connection->is_primary() ) {
			return;
		}

		if ( $test_email->is_successful() ) {
			$this->set_flag( 'test_email_sent' );
		}
	}

	/**
	 * Flag usage tracking as a wizard opt-in when the wizard saves it enabled.
	 *
	 * @since 4.10.0
	 *
	 * @param array $settings Settings the wizard is saving.
	 */
	public function maybe_flag_wizard_usage_tracking_optin( $settings ) {

		if ( ! is_array( $settings ) || empty( $settings['general'][ UsageTracking::SETTINGS_SLUG ] ) ) {
			return;
		}

		$this->set_usage_tracking_wizard_optin();
	}

	/**
	 * Flag a checklist page as visited when the user opens it.
	 *
	 * @since 4.10.0
	 */
	public function maybe_record_visit() {

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : '';

		if ( $page === 'wp-mail-smtp-tools' && $tab === 'code-snippets' ) {
			$this->set_flag( 'visited_code_snippets' );
		}

		if ( $page === 'wp-mail-smtp' && $tab === 'control' ) {
			$this->set_flag( 'visited_wp_notifications' );
		}
	}

	/**
	 * Whether the checklist has been dismissed on this site.
	 *
	 * @since 4.10.0
	 *
	 * @return bool
	 */
	public function is_dismissed() {

		return ! empty( $this->get_all()['dismissed'] );
	}

	/**
	 * Dismiss the checklist, recording the progress percentage at dismissal.
	 *
	 * @since 4.10.0
	 *
	 * @param int $progress_percent Completion percentage at the moment of dismissal.
	 */
	public function dismiss( $progress_percent ) {

		$state                          = $this->get_all();
		$state['dismissed']             = true;
		$state['progress_at_dismissal'] = max( 0, min( 100, (int) $progress_percent ) );

		$this->update( $state );
	}

	/**
	 * Progress percentage captured when the checklist was dismissed.
	 *
	 * @since 4.10.0
	 *
	 * @return int
	 */
	public function get_progress_at_dismissal() {

		return (int) ( $this->get_all()['progress_at_dismissal'] ?? 0 );
	}

	/**
	 * Whether a test email has been sent successfully at least once.
	 *
	 * @since 4.10.0
	 *
	 * @return bool
	 */
	public function is_test_email_sent() {

		return ! empty( $this->get_all()['test_email_sent'] );
	}

	/**
	 * Flag a test email as sent.
	 *
	 * @since 4.10.0
	 */
	public function set_test_email_sent() {

		$this->set_flag( 'test_email_sent' );
	}

	/**
	 * Whether settings have been imported from another SMTP plugin.
	 *
	 * @since 4.10.0
	 *
	 * @return bool
	 */
	public function is_settings_imported() {

		return ! empty( $this->get_all()['settings_imported'] );
	}

	/**
	 * Flag settings as imported.
	 *
	 * @since 4.10.0
	 */
	public function set_settings_imported() {

		$this->set_flag( 'settings_imported' );
	}

	/**
	 * Whether an email address has been submitted for recommendations.
	 *
	 * @since 4.10.0
	 *
	 * @return bool
	 */
	public function is_newsletter_subscribed() {

		return ! empty( $this->get_all()['newsletter_subscribed'] );
	}

	/**
	 * Flag the recommendations email as submitted.
	 *
	 * @since 4.10.0
	 */
	public function set_newsletter_subscribed() {

		$this->set_flag( 'newsletter_subscribed' );
	}

	/**
	 * Whether usage tracking was opted into from the Setup Wizard.
	 *
	 * @since 4.10.0
	 *
	 * @return bool
	 */
	public function is_usage_tracking_wizard_optin() {

		return ! empty( $this->get_all()['usage_tracking_wizard_optin'] );
	}

	/**
	 * Flag usage tracking as opted into from the Setup Wizard.
	 *
	 * @since 4.10.0
	 */
	public function set_usage_tracking_wizard_optin() {

		$this->set_flag( 'usage_tracking_wizard_optin' );
	}

	/**
	 * Whether a tracked page has been visited.
	 *
	 * @since 4.10.0
	 *
	 * @param string $key Tracked page key, `code_snippets` or `wp_notifications`.
	 *
	 * @return bool
	 */
	public function has_visited( $key ) {

		return ! empty( $this->get_all()[ 'visited_' . $key ] );
	}

	/**
	 * Set a one-way flag, writing only on the first transition.
	 *
	 * @since 4.10.0
	 *
	 * @param string $key State key.
	 */
	private function set_flag( $key ) {

		$state = $this->get_all();

		if ( ! empty( $state[ $key ] ) ) {
			return;
		}

		$state[ $key ] = true;

		$this->update( $state );
	}

	/**
	 * Read the full state blob.
	 *
	 * @since 4.10.0
	 *
	 * @return array
	 */
	private function get_all() {

		$state = get_option( self::OPTION, [] );

		return is_array( $state ) ? $state : [];
	}

	/**
	 * Persist the full state blob.
	 *
	 * @since 4.10.0
	 *
	 * @param array $state State blob.
	 */
	private function update( array $state ) {

		update_option( self::OPTION, $state, false );
	}
}
