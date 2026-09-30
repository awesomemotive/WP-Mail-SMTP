<?php

namespace WPMailSMTP\Admin\SetupWizard;

use WPMailSMTP\Options;
use WPMailSMTP\Reports\Emails\Summary as SummaryReportEmail;
use WPMailSMTP\Tasks\Reports\SummaryEmailTask as SummaryReportEmailTask;

/**
 * Bridges the stored plugin options and the setup wizard client.
 *
 * Projects `Options` into the client-safe payload the wizard reads, and gates
 * incoming saves down to the same allowlist so the REST controller never hands
 * the browser (or accepts back) anything outside what the wizard actually uses.
 *
 * @since 4.10.0
 */
class Settings {

	/**
	 * Assemble the wizard hydration payload.
	 *
	 * Splits into two buckets: `settings`, the writable options the client binds
	 * and saves back, and `meta`, read-only data the client never persists.
	 *
	 * @since 4.10.0
	 *
	 * @return array
	 */
	public function get_payload() {

		$all = Options::init()->get_all();

		return [
			'settings' => $this->filter_allowed( $all ),
			'meta'     => $this->get_meta( $all ),
		];
	}

	/**
	 * Persist an incoming settings tree from the wizard client.
	 *
	 * Values are stored slashed because `Options::get()` runs `stripslashes()`
	 * over every string it reads back, so anything saved unslashed loses its
	 * backslashes on the next read (SMTP passwords and API keys can carry them).
	 *
	 * @since 4.10.0
	 *
	 * @param array $settings Settings tree keyed by group.
	 *
	 * @return void
	 */
	public function update( $settings ) {

		// Persist only the paths the wizard is allowed to write.
		$settings = $this->filter_allowed( wp_slash( (array) $settings ) );

		// Cancel the summary report email task if the summary report email was disabled.
		if (
			! SummaryReportEmail::is_disabled() &&
			isset( $settings['general'][ SummaryReportEmail::SETTINGS_SLUG ] ) &&
			$settings['general'][ SummaryReportEmail::SETTINGS_SLUG ] === true
		) {
			( new SummaryReportEmailTask() )->cancel();
		}

		/**
		 * Before updating settings in Setup Wizard.
		 *
		 * @since 3.3.0
		 *
		 * @param array $settings Settings being saved.
		 */
		do_action( 'wp_mail_smtp_admin_setup_wizard_update_settings', $settings ); // phpcs:ignore WPForms.PHP.ValidateHooks.InvalidHookName -- Public hook name, kept for backwards compatibility.

		// Merged rather than replaced: each wizard step posts only the groups it owns,
		// so a replacing write would wipe every group the step left out.
		Options::init()->set( $settings, false, false );
	}

	/**
	 * Reduce an options tree to the group/key paths the wizard client uses.
	 *
	 * `Options::get_all()` returns the entire stored options blob, including secrets
	 * the wizard never displays (the alert channel connection credentials) and
	 * unrelated general/debug flags, so the client gets only the paths it binds.
	 *
	 * The same filter gates incoming saves, so a crafted request can persist
	 * only the writable paths and never arbitrary option keys.
	 *
	 * oAuth tokens are absent here; they surface as presence booleans under `meta`.
	 *
	 * @since 4.10.0
	 *
	 * @param array $settings Options tree keyed by group.
	 *
	 * @return array
	 */
	public function filter_allowed( $settings ) {

		$filtered = [];

		foreach ( $this->get_allowed_keys() as $group => $keys ) {
			if ( ! isset( $settings[ $group ] ) || ! is_array( $settings[ $group ] ) ) {
				continue;
			}

			foreach ( $keys as $key ) {
				if ( array_key_exists( $key, $settings[ $group ] ) ) {
					$filtered[ $group ][ $key ] = $settings[ $group ][ $key ];
				}
			}
		}

		return $filtered;
	}

	/**
	 * Build the read-only `meta` bucket the client reads but never saves.
	 *
	 * @since 4.10.0
	 *
	 * @param array $settings Full options tree from Options::get_all().
	 *
	 * @return array
	 */
	private function get_meta( $settings ) {

		return [
			'connections' => $this->get_mailer_connections( $settings ),
			'license'     => [
				'is_valid' => $this->is_license_valid( $settings ),
			],
		];
	}

	/**
	 * Whether a valid, active license is stored.
	 *
	 * The wizard gates the one-click mailers on licensing but never needs the
	 * key itself, so the key stays server-side and only this boolean is exposed.
	 *
	 * @since 4.10.0
	 *
	 * @param array $settings Full options tree from Options::get_all().
	 *
	 * @return bool
	 */
	private function is_license_valid( $settings ) {

		$license = isset( $settings['license'] ) ? $settings['license'] : [];

		return ! empty( $license['key'] ) &&
			empty( $license['is_expired'] ) &&
			empty( $license['is_disabled'] ) &&
			empty( $license['is_invalid'] ) &&
			empty( $license['is_limit_reached'] );
	}

	/**
	 * Report whether each oAuth mailer is authorized, per connection mode.
	 *
	 * The wizard only needs to tell a connected mailer from an unconnected one,
	 * so the raw tokens are withheld and each mode is reported as a single
	 * `is_*_authorized` boolean. gmail/outlook expose both the custom-app and
	 * one-click modes because the client switches between them live.
	 *
	 * @since 4.10.0
	 *
	 * @param array $settings Full options tree from Options::get_all().
	 *
	 * @return array
	 */
	private function get_mailer_connections( $settings ) {

		$connections = [];

		foreach ( $this->get_mailer_connection_map() as $mailer => $modes ) {
			foreach ( $modes as $flag => $keys ) {
				$connections[ $mailer ][ $flag ] = $this->has_all_tokens( $settings, $mailer, $keys );
			}
		}

		return $connections;
	}

	/**
	 * The token paths that must all be present for each connection mode.
	 *
	 * @since 4.10.0
	 *
	 * @return array[]
	 */
	private function get_mailer_connection_map() {

		return [
			'gmail'   => [
				'is_authorized'                 => [ 'access_token', 'refresh_token' ],
				'is_one_click_setup_authorized' => [ 'one_click_setup_credentials' ],
			],
			'outlook' => [
				'is_authorized'                 => [ 'access_token', 'refresh_token' ],
				'is_one_click_setup_authorized' => [ 'one_click_setup_credentials' ],
			],
			'zoho'    => [
				'is_authorized' => [ 'access_token', 'refresh_token' ],
			],
		];
	}

	/**
	 * Whether every listed token in a mailer group counts as present.
	 *
	 * @since 4.10.0
	 *
	 * @param array  $settings Full options tree from Options::get_all().
	 * @param string $mailer   Mailer group slug.
	 * @param array  $keys     Token keys that must all be present.
	 *
	 * @return bool
	 */
	private function has_all_tokens( $settings, $mailer, $keys ) {

		foreach ( $keys as $key ) {
			$value = isset( $settings[ $mailer ][ $key ] ) ? $settings[ $mailer ][ $key ] : null;

			if ( ! $this->is_present( $value ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Whether a stored credential value counts as present.
	 *
	 * Tokens are stored either as scalars or as arrays (e.g. the one-click setup
	 * credentials); an array counts as present only when it is non-empty and none
	 * of its parts are empty, matching the client's "all parts required" check.
	 *
	 * @since 4.10.0
	 *
	 * @param mixed $value Stored credential value.
	 *
	 * @return bool
	 */
	private function is_present( $value ) {

		if ( is_array( $value ) ) {
			if ( empty( $value ) ) {
				return false;
			}

			foreach ( $value as $part ) {
				if ( empty( $part ) ) {
					return false;
				}
			}

			return true;
		}

		return ! empty( $value );
	}

	/**
	 * The option group/key paths the wizard client reads and writes.
	 *
	 * Mirrors the wizard store's default settings state: every key the app binds
	 * or exposes through a getter must appear here, or it will be missing after
	 * hydration.
	 *
	 * @since 4.10.0
	 *
	 * @return array[]
	 */
	private function get_allowed_keys() {

		return [
			'mail'         => [ 'mailer', 'from_email', 'from_name', 'return_path', 'from_email_force', 'from_name_force' ],
			'smtp'         => [ 'host', 'port', 'encryption', 'autotls', 'auth', 'user', 'pass' ],
			'sendlayer'    => [ 'api_key', 'quick_connect', 'is_shared_domain', 'sender_domain' ],
			'smtpcom'      => [ 'api_key', 'channel' ],
			'sendinblue'   => [ 'api_key', 'domain' ],
			'mailersend'   => [ 'api_key', 'has_pro_plan' ],
			'mailgun'      => [ 'api_key', 'domain', 'region' ],
			'mailjet'      => [ 'api_key', 'secret_key' ],
			'resend'       => [ 'api_key' ],
			'sendgrid'     => [ 'api_key', 'domain' ],
			'smtp2go'      => [ 'api_key' ],
			'sparkpost'    => [ 'api_key', 'region' ],
			'postmark'     => [ 'server_api_token', 'message_stream' ],
			'mandrill'     => [ 'api_key' ],
			'amazonses'    => [ 'client_id', 'client_secret', 'region' ],
			'elasticemail' => [ 'api_key' ],
			'gmail'        => [ 'client_id', 'client_secret', 'user_details', 'one_click_setup_enabled', 'one_click_setup_user_details' ],
			'outlook'      => [ 'client_id', 'client_secret', 'user_details', 'one_click_setup_enabled', 'one_click_setup_user_details' ],
			'zoho'         => [ 'client_id', 'client_secret', 'domain', 'user_details' ],
			'logs'         => [ 'enabled', 'log_email_content', 'save_attachments', 'open_email_tracking', 'click_link_tracking' ],
			'general'      => [ SummaryReportEmail::SETTINGS_SLUG ],
			'alert_email'  => [ 'enabled', 'connections' ],
		];
	}
}
