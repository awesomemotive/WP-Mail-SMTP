<?php

namespace WPMailSMTP\Admin\SetupWizard;

use WPMailSMTP\Options;
use WPMailSMTP\Reports\Emails\Summary as SummaryReportEmail;
use WPMailSMTP\Tasks\Reports\SummaryEmailTask as SummaryReportEmailTask;
use WPMailSMTP\UsageTracking\UsageTracking;

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

		$all      = Options::init()->get_all();
		$settings = $this->filter_allowed( $all );

		// Secrets are write-only: their presence surfaces under `meta`, never their values.
		foreach ( $this->get_secret_keys() as $group => $keys ) {
			foreach ( $keys as $key ) {
				unset( $settings[ $group ][ $key ] );
			}
		}

		return [
			'settings' => $settings,
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
		$settings = $this->prepare_secrets_for_save( $this->filter_allowed( wp_slash( (array) $settings ) ) );

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
	 * Drop untouched (`null` or absent) secrets so the merging save keeps the stored values.
	 *
	 * An untouched SMTP password is cleared instead when the host changes, so a client
	 * cannot send the stored password to a server of its choosing.
	 *
	 * @since 4.10.3
	 *
	 * @param array $settings Settings tree keyed by group.
	 *
	 * @return array
	 */
	public function prepare_secrets_for_save( $settings ) {

		$settings = $this->prepare_constant_smtp_pass( $settings );

		foreach ( $this->get_secret_keys() as $group => $keys ) {
			if ( ! isset( $settings[ $group ] ) || ! is_array( $settings[ $group ] ) ) {
				continue;
			}

			foreach ( $keys as $key ) {
				if ( isset( $settings[ $group ][ $key ] ) ) {
					continue;
				}

				unset( $settings[ $group ][ $key ] );

				if ( $group === 'smtp' && ! $this->is_stored_smtp_host( $settings['smtp'] ) ) {
					$settings[ $group ][ $key ] = '';
				}
			}
		}

		return $settings;
	}

	/**
	 * Fill the untouched (`null` or absent) secrets of a probe config with the stored values.
	 *
	 * The stored SMTP password is only used for the stored host.
	 *
	 * @since 4.10.3
	 *
	 * @param string $mailer Mailer slug.
	 * @param array  $config The mailer's submitted settings group.
	 *
	 * @return array
	 */
	public function fill_stored_secrets( $mailer, $config ) {

		$secret_keys = $this->get_secret_keys();

		if ( empty( $secret_keys[ $mailer ] ) ) {
			return $config;
		}

		foreach ( $secret_keys[ $mailer ] as $key ) {
			if ( isset( $config[ $key ] ) ) {
				continue;
			}

			$config[ $key ] = $mailer === 'smtp' && ! $this->is_stored_smtp_host( $config ) ?
				'' :
				Options::init()->get( $mailer, $key );
		}

		return $config;
	}

	/**
	 * Apply a constant SMTP password: a host change needs it repeated, and the repeat is never stored.
	 *
	 * Clearing a constant password changes nothing, so only knowing it can authorize a new host.
	 *
	 * @since 4.10.3
	 *
	 * @param array $settings Settings tree keyed by group.
	 *
	 * @return array
	 */
	private function prepare_constant_smtp_pass( $settings ) {

		$options = Options::init();

		if ( ! isset( $settings['smtp'] ) || ! $options->is_const_defined( 'smtp', 'pass' ) ) {
			return $settings;
		}

		if ( $this->is_unconfirmed_smtp_host_change( wp_unslash( $settings['smtp'] ) ) ) {
			unset( $settings['smtp']['host'] );
		}

		unset( $settings['smtp']['pass'] );

		return $settings;
	}

	/**
	 * Whether an SMTP group changes the host without repeating the constant password.
	 *
	 * @since 4.10.3
	 *
	 * @param array $smtp Unslashed SMTP settings group.
	 *
	 * @return bool
	 */
	public function is_unconfirmed_smtp_host_change( $smtp ) {

		return $this->is_constant_pass_smtp_host_change( $smtp ) &&
			! hash_equals( (string) Options::init()->get( 'smtp', 'pass' ), (string) ( $smtp['pass'] ?? '' ) );
	}

	/**
	 * Whether an SMTP group changes the host while the password is a constant, confirmed or not.
	 *
	 * @since 4.10.3
	 *
	 * @param array $smtp Unslashed SMTP settings group.
	 *
	 * @return bool
	 */
	public function is_constant_pass_smtp_host_change( $smtp ) {

		return Options::init()->is_const_defined( 'smtp', 'pass' ) && ! $this->is_stored_smtp_host( $smtp );
	}

	/**
	 * Whether an SMTP group targets the stored host; a group without a host counts as a match.
	 *
	 * @since 4.10.3
	 *
	 * @param array $smtp SMTP settings group.
	 *
	 * @return bool
	 */
	private function is_stored_smtp_host( $smtp ) {

		if ( ! isset( $smtp['host'] ) ) {
			return true;
		}

		$submitted = strtolower( trim( wp_unslash( (string) $smtp['host'] ) ) );
		$stored    = strtolower( trim( (string) Options::init()->get( 'smtp', 'host' ) ) );

		return $submitted === $stored;
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
	protected function get_meta( $settings ) {

		return [
			'connections' => $this->get_mailer_connections( $settings ),
			'secrets'     => $this->get_secrets_meta(),
		];
	}

	/**
	 * Report whether each mailer secret is stored, with its length and last four characters as hints.
	 *
	 * The SMTP password and constants get no hints; values under 12 characters get no last characters.
	 *
	 * @since 4.10.3
	 *
	 * @return array
	 */
	private function get_secrets_meta() {

		$options = Options::init();
		$secrets = [];

		foreach ( $this->get_secret_keys() as $group => $keys ) {
			foreach ( $keys as $key ) {
				$value  = (string) $options->get( $group, $key );
				$length = $group !== 'smtp' && ! $options->is_const_defined( $group, $key ) ? mb_strlen( $value ) : 0;

				$secrets[ $group ][ $key ] = [
					'is_set'     => $value !== '',
					'last_chars' => $length >= 12 ? mb_substr( $value, -4 ) : '',
					'length'     => $length,
				];
			}
		}

		return $secrets;
	}

	/**
	 * Report whether each oAuth mailer is authorized, per connection mode.
	 *
	 * The wizard only needs to tell a connected mailer from an unconnected one,
	 * so the raw tokens are withheld and each mode is reported as a single
	 * `is_*_authorized` boolean.
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
	protected function get_mailer_connection_map() {

		return [
			'gmail' => [
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
	 * Tokens are stored either as scalars or as arrays; an array counts as present only
	 * when it is non-empty and none of its parts are empty, matching the client's
	 * "all parts required" check.
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
	 * The secret keys of each mailer group, which the wizard can write but never read back.
	 *
	 * @since 4.10.3
	 *
	 * @return array[]
	 */
	protected function get_secret_keys() {

		return [
			'smtp'         => [ 'pass' ],
			'sendlayer'    => [ 'api_key' ],
			'smtpcom'      => [ 'api_key' ],
			'sendinblue'   => [ 'api_key' ],
			'mailersend'   => [ 'api_key' ],
			'mailgun'      => [ 'api_key' ],
			'mailjet'      => [ 'api_key', 'secret_key' ],
			'resend'       => [ 'api_key' ],
			'sendgrid'     => [ 'api_key' ],
			'smtp2go'      => [ 'api_key' ],
			'sparkpost'    => [ 'api_key' ],
			'postmark'     => [ 'server_api_token' ],
			'mandrill'     => [ 'api_key' ],
			'elasticemail' => [ 'api_key' ],
			'gmail'        => [ 'client_secret' ],
		];
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
	protected function get_allowed_keys() {

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
			'elasticemail' => [ 'api_key' ],
			'gmail'        => [ 'client_id', 'client_secret', 'user_details' ],
			'general'      => [ SummaryReportEmail::SETTINGS_SLUG, UsageTracking::SETTINGS_SLUG ],
		];
	}
}
