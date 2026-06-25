<?php

namespace WPMailSMTP\WPCLI\Commands;

use WP_CLI\Dispatcher\CommandNamespace;

/**
 * Configure WP Mail SMTP from the command line.
 *
 * Use one of the subcommands listed below. See `wp help wp-mail-smtp <subcommand>`
 * for detailed help on each one.
 *
 * ## EXAMPLES
 *
 *     wp wp-mail-smtp setup --mail.from_email=noreply@example.com --mail.from_name="Example" \
 *         --mail.mailer=smtp --smtp.host=mail.example.com --smtp.port=587 \
 *         --smtp.encryption=tls --smtp.auth=1 --smtp.user=foo --smtp.pass-file=/run/secret/smtp_pass
 *
 *     wp wp-mail-smtp option get mail.from_email
 *
 *     wp wp-mail-smtp test you@example.com
 *
 * @since 4.9.0
 */
class Manage extends CommandNamespace {

}
