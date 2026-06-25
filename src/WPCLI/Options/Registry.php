<?php

namespace WPMailSMTP\WPCLI\Options;

/**
 * Defines all Lite WP-CLI configuration args. Pro extends via the
 * `wp_mail_smtp_wpcli_options_registry_get_args` filter.
 *
 * @since 4.9.0
 */
class Registry {

	/**
	 * Get the full arg list (Lite + filter contributions).
	 *
	 * Each arg shape:
	 *   - flag         (string) Operator-facing dotted flag, e.g. `smtp.host`.
	 *   - storage_path (string|null) Override for where the value lands in the
	 *                  options array. Defaults to the flag. Supports dotted
	 *                  notation for nesting, e.g. `alert_slack.connections.0.webhook_url`.
	 *   - type         (string) string|int|bool|email|enum.
	 *   - enum         (array|null) For type=enum.
	 *   - enum_storage_map (array|null) For enum types: map of operator-facing
	 *                  value => storage value, applied on write and reversed on
	 *                  read. Keys not in the map pass through unchanged.
	 *   - required     (bool) Unconditionally required.
	 *   - required_if  (array|null) Map of flag => value pairs (AND semantics).
	 *   - sensitive    (bool) Masked in `option list`; accepts --flag-file / env var.
	 *   - env_var      (string|null) Explicit override; else derived as WPMS_<FLAG_UPPER>.
	 *   - description  (string) Help text.
	 *
	 * @since 4.9.0
	 *
	 * @return array
	 */
	public function get_args() {

		$args = array_merge(
			$this->mail_args(),
			$this->general_args(),
			$this->smtp_args(),
			$this->sendgrid_args(),
			$this->mailgun_args(),
			$this->postmark_args(),
			$this->sendlayer_args(),
			$this->resend_args(),
			$this->smtpcom_args(),
			$this->smtp2go_args(),
			$this->sparkpost_args(),
			$this->mailjet_args(),
			$this->mailersend_args(),
			$this->sendinblue_args(),
			$this->elasticemail_args(),
			$this->amazonses_args(),
			$this->mandrill_args()
		);

		/**
		 * Filter the WP-CLI arg registry. Pro hooks here to register
		 * its own flags (logs, alerts, additional connections, etc.).
		 *
		 * @since 4.9.0
		 *
		 * @param array $args List of arg definitions.
		 */
		return apply_filters( 'wp_mail_smtp_wpcli_options_registry_get_args', $args ); // phpcs:ignore WPForms.PHP.ValidateHooks.InvalidHookName
	}

	/**
	 * Look up a single arg by its dotted flag.
	 *
	 * @since 4.9.0
	 *
	 * @param string $flag Dotted flag, e.g. `smtp.host`.
	 *
	 * @return array|null
	 */
	public function find( $flag ) {

		foreach ( $this->get_args() as $arg ) {
			if ( $arg['flag'] === $flag ) {
				return $arg;
			}
		}

		return null;
	}

	/**
	 * The non-OAuth mailers selectable via `mail.mailer`.
	 *
	 * @since 4.9.0
	 *
	 * @return array
	 */
	public static function supported_mailers() {

		return [
			'mail',
			'smtp',
			'sendgrid',
			'mailgun',
			'postmark',
			'sendlayer',
			'resend',
			'smtpcom',
			'smtp2go',
			'sparkpost',
			'mailjet',
			'mailersend',
			'brevo',
			'elasticemail',
			'amazonses',
			'mandrill',
		];
	}

	/**
	 * Args common to every mailer (`mail.*`).
	 *
	 * @since 4.9.0
	 *
	 * @return array
	 */
	private function mail_args() {

		return [
			[
				'flag'        => 'mail.from_email',
				'type'        => 'email',
				'required'    => true,
				'description' => __( 'From email address.', 'wp-mail-smtp' ),
			],
			[
				'flag'        => 'mail.from_name',
				'type'        => 'string',
				'required'    => true,
				'description' => __( 'From name.', 'wp-mail-smtp' ),
			],
			[
				'flag'             => 'mail.mailer',
				'type'             => 'enum',
				'enum'             => self::supported_mailers(),
				'enum_storage_map' => [ 'brevo' => 'sendinblue' ],
				'required'         => true,
				'description'      => sprintf(
					/* translators: %s is a comma-separated list of supported mailer slugs (e.g. "mail, smtp, sendgrid"). The slugs themselves are not translated. */
					__( 'Mailer to use. One of: %s.', 'wp-mail-smtp' ),
					implode( ', ', self::supported_mailers() )
				),
			],
			[
				'flag'        => 'mail.return_path',
				'type'        => 'bool',
				'description' => __( 'Set Return-Path to match From email.', 'wp-mail-smtp' ),
			],
			[
				'flag'        => 'mail.from_email_force',
				'type'        => 'bool',
				'description' => __( 'Force From email on every outgoing message.', 'wp-mail-smtp' ),
			],
			[
				'flag'        => 'mail.from_name_force',
				'type'        => 'bool',
				'description' => __( 'Force From name on every outgoing message.', 'wp-mail-smtp' ),
			],
		];
	}

	/**
	 * General plugin settings args (`general.*`).
	 *
	 * @since 4.9.0
	 *
	 * @return array
	 */
	private function general_args() {

		return [
			[
				'flag'        => 'general.do_not_send',
				'type'        => 'bool',
				'description' => __( 'Disable all outgoing email.', 'wp-mail-smtp' ),
			],
			[
				'flag'         => 'general.hide_am_notifications',
				'storage_path' => 'general.am_notifications_hidden',
				'type'         => 'bool',
				'description'  => __( 'Hide Awesome Motive product notifications.', 'wp-mail-smtp' ),
			],
		];
	}

	/**
	 * Other SMTP mailer args (`smtp.*`).
	 *
	 * @since 4.9.0
	 *
	 * @return array
	 */
	private function smtp_args() {

		$req      = [ 'mail.mailer' => 'smtp' ];
		$req_auth = [
			'mail.mailer' => 'smtp',
			'smtp.auth'   => true,
		];

		return [
			[
				'flag'        => 'smtp.host',
				'type'        => 'string',
				'required_if' => $req,
				'description' => __( 'SMTP server hostname.', 'wp-mail-smtp' ),
			],
			[
				'flag'        => 'smtp.port',
				'type'        => 'int',
				'required_if' => $req,
				'description' => __( 'SMTP server port (e.g. 25, 465, 587).', 'wp-mail-smtp' ),
			],
			[
				'flag'        => 'smtp.encryption',
				'type'        => 'enum',
				'enum'        => [ 'none', 'ssl', 'tls' ],
				'description' => __( 'Encryption: none, ssl, or tls.', 'wp-mail-smtp' ),
			],
			[
				'flag'        => 'smtp.autotls',
				'type'        => 'bool',
				'description' => __( 'Auto TLS when supported by server.', 'wp-mail-smtp' ),
			],
			[
				'flag'        => 'smtp.auth',
				'type'        => 'bool',
				'description' => __( 'Whether the SMTP server requires authentication.', 'wp-mail-smtp' ),
			],
			[
				'flag'        => 'smtp.user',
				'type'        => 'string',
				'required_if' => $req_auth,
				'description' => __( 'SMTP username.', 'wp-mail-smtp' ),
			],
			[
				'flag'        => 'smtp.pass',
				'type'        => 'string',
				'required_if' => $req_auth,
				'sensitive'   => true,
				'description' => __( 'SMTP password.', 'wp-mail-smtp' ),
			],
		];
	}

	/**
	 * SendGrid mailer args (`sendgrid.*`).
	 *
	 * @since 4.9.0
	 *
	 * @return array
	 */
	private function sendgrid_args() {

		$req = [ 'mail.mailer' => 'sendgrid' ];

		return [
			[
				'flag'        => 'sendgrid.api_key',
				'type'        => 'string',
				'required_if' => $req,
				'sensitive'   => true,
				'description' => __( 'SendGrid API key.', 'wp-mail-smtp' ),
			],
			[
				'flag'        => 'sendgrid.domain',
				'type'        => 'string',
				'description' => __( 'Optional sending domain.', 'wp-mail-smtp' ),
			],
		];
	}

	/**
	 * Mailgun mailer args (`mailgun.*`).
	 *
	 * @since 4.9.0
	 *
	 * @return array
	 */
	private function mailgun_args() {

		$req = [ 'mail.mailer' => 'mailgun' ];

		return [
			[
				'flag'        => 'mailgun.api_key',
				'type'        => 'string',
				'required_if' => $req,
				'sensitive'   => true,
				'description' => __( 'Mailgun API key.', 'wp-mail-smtp' ),
			],
			[
				'flag'        => 'mailgun.domain',
				'type'        => 'string',
				'required_if' => $req,
				'description' => __( 'Mailgun sending domain.', 'wp-mail-smtp' ),
			],
			[
				'flag'        => 'mailgun.region',
				'type'        => 'enum',
				'enum'        => [ 'US', 'EU' ],
				'description' => __( 'Mailgun region (US or EU).', 'wp-mail-smtp' ),
			],
		];
	}

	/**
	 * Postmark mailer args (`postmark.*`).
	 *
	 * @since 4.9.0
	 *
	 * @return array
	 */
	private function postmark_args() {

		$req = [ 'mail.mailer' => 'postmark' ];

		return [
			[
				'flag'         => 'postmark.api_key',
				'storage_path' => 'postmark.server_api_token',
				'type'         => 'string',
				'required_if'  => $req,
				'sensitive'    => true,
				'description'  => __( 'Postmark server API token.', 'wp-mail-smtp' ),
			],
			[
				'flag'        => 'postmark.message_stream',
				'type'        => 'string',
				'description' => __( 'Optional Postmark message stream.', 'wp-mail-smtp' ),
			],
		];
	}

	/**
	 * SendLayer mailer args (`sendlayer.*`).
	 *
	 * @since 4.9.0
	 *
	 * @return array
	 */
	private function sendlayer_args() {

		$req = [ 'mail.mailer' => 'sendlayer' ];

		return [
			[
				'flag'        => 'sendlayer.api_key',
				'type'        => 'string',
				'required_if' => $req,
				'sensitive'   => true,
				'description' => __( 'SendLayer API key.', 'wp-mail-smtp' ),
			],
		];
	}

	/**
	 * Resend mailer args (`resend.*`).
	 *
	 * @since 4.9.0
	 *
	 * @return array
	 */
	private function resend_args() {

		$req = [ 'mail.mailer' => 'resend' ];

		return [
			[
				'flag'        => 'resend.api_key',
				'type'        => 'string',
				'required_if' => $req,
				'sensitive'   => true,
				'description' => __( 'Resend API key.', 'wp-mail-smtp' ),
			],
		];
	}

	/**
	 * SMTP.com mailer args (`smtpcom.*`).
	 *
	 * @since 4.9.0
	 *
	 * @return array
	 */
	private function smtpcom_args() {

		$req = [ 'mail.mailer' => 'smtpcom' ];

		return [
			[
				'flag'        => 'smtpcom.api_key',
				'type'        => 'string',
				'required_if' => $req,
				'sensitive'   => true,
				'description' => __( 'SMTP.com API key.', 'wp-mail-smtp' ),
			],
			[
				'flag'        => 'smtpcom.channel',
				'type'        => 'string',
				'required_if' => $req,
				'description' => __( 'SMTP.com sender channel name.', 'wp-mail-smtp' ),
			],
		];
	}

	/**
	 * SMTP2GO mailer args (`smtp2go.*`).
	 *
	 * @since 4.9.0
	 *
	 * @return array
	 */
	private function smtp2go_args() {

		$req = [ 'mail.mailer' => 'smtp2go' ];

		return [
			[
				'flag'        => 'smtp2go.api_key',
				'type'        => 'string',
				'required_if' => $req,
				'sensitive'   => true,
				'description' => __( 'SMTP2GO API key.', 'wp-mail-smtp' ),
			],
		];
	}

	/**
	 * SparkPost mailer args (`sparkpost.*`).
	 *
	 * @since 4.9.0
	 *
	 * @return array
	 */
	private function sparkpost_args() {

		$req = [ 'mail.mailer' => 'sparkpost' ];

		return [
			[
				'flag'        => 'sparkpost.api_key',
				'type'        => 'string',
				'required_if' => $req,
				'sensitive'   => true,
				'description' => __( 'SparkPost API key.', 'wp-mail-smtp' ),
			],
			[
				'flag'        => 'sparkpost.region',
				'type'        => 'enum',
				'enum'        => [ 'US', 'EU' ],
				'description' => __( 'SparkPost region (US or EU).', 'wp-mail-smtp' ),
			],
		];
	}

	/**
	 * Mailjet mailer args (`mailjet.*`).
	 *
	 * @since 4.9.0
	 *
	 * @return array
	 */
	private function mailjet_args() {

		$req = [ 'mail.mailer' => 'mailjet' ];

		return [
			[
				'flag'        => 'mailjet.api_key',
				'type'        => 'string',
				'required_if' => $req,
				'sensitive'   => true,
				'description' => __( 'Mailjet API key.', 'wp-mail-smtp' ),
			],
			[
				'flag'        => 'mailjet.secret_key',
				'type'        => 'string',
				'required_if' => $req,
				'sensitive'   => true,
				'description' => __( 'Mailjet secret key.', 'wp-mail-smtp' ),
			],
		];
	}

	/**
	 * MailerSend mailer args (`mailersend.*`).
	 *
	 * @since 4.9.0
	 *
	 * @return array
	 */
	private function mailersend_args() {

		$req = [ 'mail.mailer' => 'mailersend' ];

		return [
			[
				'flag'        => 'mailersend.api_key',
				'type'        => 'string',
				'required_if' => $req,
				'sensitive'   => true,
				'description' => __( 'MailerSend API key.', 'wp-mail-smtp' ),
			],
			[
				'flag'         => 'mailersend.pro_plan',
				'storage_path' => 'mailersend.has_pro_plan',
				'type'         => 'bool',
				'description'  => __( 'Set if your MailerSend account is on a paid plan.', 'wp-mail-smtp' ),
			],
		];
	}

	/**
	 * Brevo (formerly Sendinblue) mailer args (`brevo.*`).
	 *
	 * @since 4.9.0
	 *
	 * @return array
	 */
	private function sendinblue_args() {

		$req = [ 'mail.mailer' => 'brevo' ];

		return [
			[
				'flag'         => 'brevo.api_key',
				'storage_path' => 'sendinblue.api_key',
				'type'         => 'string',
				'required_if'  => $req,
				'sensitive'    => true,
				'description'  => __( 'Brevo (formerly Sendinblue) API key.', 'wp-mail-smtp' ),
			],
			[
				'flag'         => 'brevo.domain',
				'storage_path' => 'sendinblue.domain',
				'type'         => 'string',
				'description'  => __( 'Optional Brevo sending domain.', 'wp-mail-smtp' ),
			],
		];
	}

	/**
	 * Elastic Email mailer args (`elasticemail.*`).
	 *
	 * @since 4.9.0
	 *
	 * @return array
	 */
	private function elasticemail_args() {

		$req = [ 'mail.mailer' => 'elasticemail' ];

		return [
			[
				'flag'        => 'elasticemail.api_key',
				'type'        => 'string',
				'required_if' => $req,
				'sensitive'   => true,
				'description' => __( 'Elastic Email API key.', 'wp-mail-smtp' ),
			],
		];
	}

	/**
	 * Amazon SES mailer args (`amazonses.*`).
	 *
	 * @since 4.9.0
	 *
	 * @return array
	 */
	private function amazonses_args() {

		$req = [ 'mail.mailer' => 'amazonses' ];

		return [
			[
				'flag'         => 'amazonses.access_key_id',
				'storage_path' => 'amazonses.client_id',
				'type'         => 'string',
				'required_if'  => $req,
				'sensitive'    => true,
				'description'  => __( 'AWS access key ID.', 'wp-mail-smtp' ),
			],
			[
				'flag'         => 'amazonses.secret_access_key',
				'storage_path' => 'amazonses.client_secret',
				'type'         => 'string',
				'required_if'  => $req,
				'sensitive'    => true,
				'description'  => __( 'AWS secret access key.', 'wp-mail-smtp' ),
			],
			[
				'flag'        => 'amazonses.region',
				'type'        => 'string',
				'required_if' => $req,
				'description' => __( 'AWS region (e.g. us-east-1).', 'wp-mail-smtp' ),
			],
		];
	}

	/**
	 * Mandrill mailer args (`mandrill.*`).
	 *
	 * @since 4.9.0
	 *
	 * @return array
	 */
	private function mandrill_args() {

		$req = [ 'mail.mailer' => 'mandrill' ];

		return [
			[
				'flag'        => 'mandrill.api_key',
				'type'        => 'string',
				'required_if' => $req,
				'sensitive'   => true,
				'description' => __( 'Mandrill API key.', 'wp-mail-smtp' ),
			],
		];
	}
}
