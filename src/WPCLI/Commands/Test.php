<?php

namespace WPMailSMTP\WPCLI\Commands;

use WP_CLI;
use WPMailSMTP\Options;
use WPMailSMTP\TestEmail\TestEmail;

/**
 * Send a test email via the currently configured mailer.
 *
 * @since 4.9.0
 */
class Test {

	/**
	 * Send a test email via the currently configured mailer.
	 *
	 * ## OPTIONS
	 *
	 * <recipient>
	 * : Email address to send the test message to.
	 *
	 * [--plain]
	 * : Send as plain text instead of HTML.
	 *
	 * ## EXAMPLES
	 *
	 *     wp wp-mail-smtp test you@example.com
	 *     wp wp-mail-smtp test you@example.com --plain
	 *
	 * @since 4.9.0
	 *
	 * @param array $args       Positional args.
	 * @param array $assoc_args Associative args.
	 *
	 * @return void
	 */
	public function __invoke( $args, $assoc_args ) { // phpcs:ignore WPForms.PHP.HooksMethod.InvalidPlaceForAddingHooks -- The wp_mail_failed capture hook is paired add/remove and scoped to this single send.

		$recipient = $args[0] ?? null;

		if ( $recipient === null || ! is_email( $recipient ) ) {
			WP_CLI::error( __( 'Pass a valid recipient: wp wp-mail-smtp test <recipient>', 'wp-mail-smtp' ) );
		}

		$mailer = Options::init()->get( 'mail', 'mailer' );

		if ( $mailer === '' || $mailer === 'mail' ) {
			WP_CLI::error( __( 'No mailer is configured. Run `wp wp-mail-smtp setup ...` first.', 'wp-mail-smtp' ) );
		}

		// Capture wp_mail_failed to surface the underlying WP_Error on failure.
		$captured_error = null;
		$capture        = static function ( $wp_error ) use ( &$captured_error ) {
			$captured_error = $wp_error;
		};
		add_action( 'wp_mail_failed', $capture );

		$test = ( new TestEmail() )->as_html( ! isset( $assoc_args['plain'] ) );

		$test->send( $recipient );

		remove_action( 'wp_mail_failed', $capture );

		if ( $test->is_successful() ) {
			WP_CLI::success(
				sprintf(
					/* translators: %1$s is the recipient email address. %2$s is the mailer slug (e.g. smtp, sendgrid). Recipient and mailer slug are not translated. */
					__( 'Test email sent to %1$s via mailer "%2$s".', 'wp-mail-smtp' ),
					$recipient,
					$mailer
				)
			);

			return;
		}

		$reason = $captured_error instanceof \WP_Error
			? $captured_error->get_error_message()
			: __( 'wp_mail() returned false (no further detail available).', 'wp-mail-smtp' );

		WP_CLI::error(
			sprintf(
				/* translators: %s is the underlying error message returned by wp_mail() / the mailer (already localized or quoted verbatim from the mailer response). */
				__( 'Test email failed: %s', 'wp-mail-smtp' ),
				$reason
			)
		);
	}
}
