<?php

namespace WPMailSMTP\WPCLI\Commands;

use WP_CLI;
use WPMailSMTP\Options;
use WPMailSMTP\WPCLI\Options\Help;
use WPMailSMTP\WPCLI\Options\Registry;
use WPMailSMTP\WPCLI\Options\Writer;

/**
 * Configure WP Mail SMTP from the command line.
 *
 * @since 4.9.0
 */
class Setup {

	/**
	 * One-line summary passed to WP_CLI::add_command() as `shortdesc`.
	 *
	 * Required because passing an explicit `longdesc` makes WP-CLI ignore the class docblock.
	 *
	 * @since 4.9.0
	 *
	 * @return string
	 */
	public static function shortdesc() {

		return __( 'Configure WP Mail SMTP from the command line.', 'wp-mail-smtp' );
	}

	/**
	 * Build the longdesc passed to WP_CLI::add_command().
	 *
	 * @since 4.9.0
	 *
	 * @param Registry $registry Provides the configuration-flags enumeration.
	 *
	 * @return string
	 */
	public static function help( Registry $registry ) {

		$flags = Help::configuration_flags( $registry );

		$force_desc = __( 'Skip the refusal that fires when the plugin is already configured. Does NOT wipe existing settings — only flags you pass are written.', 'wp-mail-smtp' );

		return <<<HELP
## OPTIONS

[--force]
: {$force_desc}

## EXAMPLES

    wp wp-mail-smtp setup --mail.from_email=noreply@example.com --mail.from_name="Example" \\
        --mail.mailer=smtp --smtp.host=mail.example.com --smtp.port=587 \\
        --smtp.encryption=tls --smtp.auth=1 --smtp.user=foo \\
        --smtp.pass-file=/run/secret/smtp_pass

    wp wp-mail-smtp setup --mail.from_email=noreply@example.com --mail.from_name="Example" \\
        --mail.mailer=sendgrid --sendgrid.api_key=\$SG_KEY

{$flags}
HELP;
	}

	/**
	 * Execute the `wp wp-mail-smtp setup` command.
	 *
	 * @since 4.9.0
	 *
	 * @param array $args       Positional args.
	 * @param array $assoc_args Associative args.
	 *
	 * @return void
	 */
	public function __invoke( $args, $assoc_args ) {

		$force = isset( $assoc_args['force'] );

		unset( $assoc_args['force'] );

		$writer = new Writer( new Registry() );

		$current_mailer = Options::init()->get( 'mail', 'mailer' );

		if ( ! $force && $current_mailer !== '' && $current_mailer !== 'mail' ) {
			WP_CLI::error(
				sprintf(
					/* translators: %s is the currently configured mailer slug (e.g. smtp, sendgrid). The slug is not translated. */
					__( "WP Mail SMTP is already configured (mailer: %s).\nUse `wp wp-mail-smtp option set <group>.<key> <value>` to change individual settings, or pass --force to override this check.", 'wp-mail-smtp' ),
					$current_mailer
				)
			);
		}

		$resolved = $writer->resolve( $assoc_args );

		if ( empty( $resolved ) ) {
			WP_CLI::error( __( 'No configuration flags provided. Pass at least --mail.from_email, --mail.from_name, and --mail.mailer (plus the credentials for that mailer).', 'wp-mail-smtp' ) );
		}

		$writer->validate( $resolved );
		$result = $writer->write( $resolved );

		// Nothing stored means every flag was shadowed by a wp-config constant.
		if ( empty( $result['written'] ) ) {
			WP_CLI::error( __( 'No settings were stored — every flag passed was shadowed by a wp-config constant. Remove the constants and re-run, or pass flags for non-shadowed settings.', 'wp-mail-smtp' ) );
		}

		// Skip the admin Setup Wizard redirect, mirroring wizard completion.
		update_option( 'wp_mail_smtp_activation_prevent_redirect', true );

		$mailer = $resolved['mail.mailer'] ?? Options::init()->get( 'mail', 'mailer' );

		WP_CLI::success(
			sprintf(
				/* translators: %s is the configured mailer slug (e.g. smtp, sendgrid). The slug is not translated. */
				__( 'Configured WP Mail SMTP (mailer: %s). Run `wp wp-mail-smtp test <recipient>` to verify.', 'wp-mail-smtp' ),
				$mailer
			)
		);
	}
}
