<?php

namespace WPMailSMTP\WPCLI\Options;

/**
 * Renders the shared `## CONFIGURATION FLAGS` section that each WP-CLI
 * command appends to its own longdesc.
 *
 * @since 4.9.0
 */
class Help {

	/**
	 * Logical (not alphabetical) group order; mailers follow
	 * `supported_mailers()` order, Pro groups follow their merge order.
	 *
	 * @since 4.9.0
	 *
	 * @var string[]
	 */
	private static $group_order = [
		'mail',
		'general',
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
		'logs',
		'rate_limit',
		'control',
		'alert',
	];

	/**
	 * Render the `## CONFIGURATION FLAGS` section: every registered arg
	 * enumerated under its top-level flag-segment heading.
	 *
	 * @since 4.9.0
	 *
	 * @param Registry $registry Source of args (Lite + Pro via filter).
	 *
	 * @return string
	 */
	public static function configuration_flags( Registry $registry ) { // phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh

		$grouped = [];

		foreach ( $registry->get_args() as $arg ) {
			$group               = explode( '.', $arg['flag'], 2 )[0];
			$grouped[ $group ][] = $arg;
		}

		// Order known groups first, then anything else in registry order.
		$ordered = [];

		foreach ( self::$group_order as $group ) {
			if ( isset( $grouped[ $group ] ) ) {
				$ordered[ $group ] = $grouped[ $group ];

				unset( $grouped[ $group ] );
			}
		}

		foreach ( $grouped as $group => $args ) {
			$ordered[ $group ] = $args;
		}

		$lines = [];

		$lines[] = '## ' . __( 'CONFIGURATION FLAGS', 'wp-mail-smtp' );
		$lines[] = '';
		$lines[] = __( 'These flags accept their value as a literal (--flag=value), from a file (--flag-file=<path>), or from an environment variable (WPMS_<UPPER>).', 'wp-mail-smtp' );
		$lines[] = '';

		foreach ( $ordered as $group => $args ) {
			$lines[] = self::group_label( $group ) . ':';
			$lines[] = '';

			foreach ( $args as $arg ) {
				$lines[] = '[--' . $arg['flag'] . '=' . self::value_shape( $arg ) . ']';
				$lines[] = ': ' . self::description( $arg );
				$lines[] = '';
			}
		}

		return rtrim( implode( "\n", $lines ) ) . "\n";
	}

	/**
	 * The `<value-shape>` placeholder for an arg's `--flag=...` form.
	 *
	 * Enum shapes stay `<enum>`; the allowed values are listed in the description.
	 *
	 * @since 4.9.0
	 *
	 * @param array $arg Registry arg.
	 *
	 * @return string
	 */
	private static function value_shape( array $arg ) {

		$shapes = [
			'int'   => '<int>',
			'bool'  => '<bool>',
			'email' => '<email>',
			'enum'  => '<enum>',
		];

		return $shapes[ $arg['type'] ] ?? '<string>';
	}

	/**
	 * Assemble the description line: registry description, then any
	 * required / required_if / sensitive notes appended in that order.
	 *
	 * @since 4.9.0
	 *
	 * @param array $arg Registry arg.
	 *
	 * @return string
	 */
	private static function description( array $arg ) {

		$parts = [ wp_strip_all_tags( $arg['description'] ) ];

		if ( ! empty( $arg['required'] ) ) {
			$parts[] = __( 'Required.', 'wp-mail-smtp' );
		}

		if ( ! empty( $arg['required_if'] ) && is_array( $arg['required_if'] ) ) {
			$conditions = [];

			foreach ( $arg['required_if'] as $flag => $value ) {
				$conditions[] = '--' . $flag . '=' . self::format_required_if_value( $value );
			}

			$parts[] = sprintf(
				/* translators: %s is a list of "--flag=value" conditions joined by " and " (e.g. "--mail.mailer=smtp and --smtp.auth=true"). Flags and values are not translated. */
				__( 'Required when %s.', 'wp-mail-smtp' ),
				implode( ' ' . __( 'and', 'wp-mail-smtp' ) . ' ', $conditions )
			);
		}

		if ( ! empty( $arg['sensitive'] ) ) {
			$env_var = ! empty( $arg['env_var'] )
				? $arg['env_var']
				: 'WPMS_' . strtoupper( str_replace( '.', '_', $arg['flag'] ) );
			$parts[] = sprintf(
				/* translators: %1$s is the dotted CLI flag (e.g. smtp.pass). %2$s is the matching environment variable name (e.g. WPMS_SMTP_PASS). Flag and env var name are not translated. */
				__( 'Sensitive: also accepts --%1$s-file=<path> or env var %2$s.', 'wp-mail-smtp' ),
				$arg['flag'],
				$env_var
			);
		}

		return implode( ' ', $parts );
	}

	/**
	 * Map a group slug to its section-header label.
	 *
	 * Brand names stay literal (untranslated); generic labels are translatable;
	 * unknown groups fall back to `ucfirst`.
	 *
	 * @since 4.9.0
	 *
	 * @param string $group Group slug.
	 *
	 * @return string
	 */
	private static function group_label( $group ) {

		$labels = [
			'mail'         => __( 'Mail', 'wp-mail-smtp' ),
			'general'      => __( 'General', 'wp-mail-smtp' ),
			'smtp'         => 'SMTP',
			'sendgrid'     => 'SendGrid',
			'mailgun'      => 'Mailgun',
			'postmark'     => 'Postmark',
			'sendlayer'    => 'SendLayer',
			'resend'       => 'Resend',
			'smtpcom'      => 'SMTP.com',
			'smtp2go'      => 'SMTP2GO',
			'sparkpost'    => 'SparkPost',
			'mailjet'      => 'Mailjet',
			'mailersend'   => 'MailerSend',
			'brevo'        => 'Brevo',
			'elasticemail' => 'Elastic Email',
			'amazonses'    => 'Amazon SES',
			'mandrill'     => 'Mandrill',
			'logs'         => __( 'Email Logs', 'wp-mail-smtp' ),
			'rate_limit'   => __( 'Rate Limiting', 'wp-mail-smtp' ),
			'control'      => __( 'Email Controls', 'wp-mail-smtp' ),
			'alert'        => __( 'Alerts', 'wp-mail-smtp' ),
		];

		return $labels[ $group ] ?? ucfirst( $group );
	}

	/**
	 * Render a `required_if` value for display. Booleans become the literal
	 * strings `true`/`false`; everything else passes through as-is. Matches
	 * how operators type the value on the command line.
	 *
	 * @since 4.9.0
	 *
	 * @param mixed $value Condition value.
	 *
	 * @return string
	 */
	private static function format_required_if_value( $value ) {

		if ( is_bool( $value ) ) {
			return $value ? 'true' : 'false';
		}

		return (string) $value;
	}
}
