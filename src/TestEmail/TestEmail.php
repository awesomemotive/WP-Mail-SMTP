<?php

namespace WPMailSMTP\TestEmail;

use WPMailSMTP\Admin\DomainChecker;
use WPMailSMTP\ConnectionInterface;

/**
 * Class TestEmail.
 *
 * Sends a test email on behalf of any caller (admin test page, setup wizard,
 * future contexts) and exposes the result + diagnostics.
 *
 * @since 4.9.0
 */
class TestEmail {

	/**
	 * Test email sending failed.
	 *
	 * @since 3.0.0
	 *
	 * @const int
	 */
	const FAILED = 0;

	/**
	 * Test email sent successfully.
	 *
	 * @since 3.0.0
	 *
	 * @const int
	 */
	const SUCCESS = 1;

	/**
	 * Test email domain check failed.
	 *
	 * @since 3.0.0
	 *
	 * @const int
	 */
	const FAILED_DOMAIN_CHECK = 2;

	/**
	 * X-Mailer-Type header value for the admin Email Test page.
	 *
	 * @since 4.9.0
	 *
	 * @const string
	 */
	const CONTEXT_ADMIN_TEST = 'WPMailSMTP/Admin/Test';

	/**
	 * X-Mailer-Type header value for the Setup Wizard configuration check.
	 *
	 * @since 4.9.0
	 *
	 * @const string
	 */
	const CONTEXT_SETUP_WIZARD = 'WPMailSMTP/Admin/SetupWizard/Test';

	/**
	 * Caller context. Drives the X-Mailer-Type header read by MailCatcherTrait,
	 * Pro/SmartRouting and Pro/Emails/Logs/Email.
	 *
	 * @since 4.9.0
	 *
	 * @var string
	 */
	private $context = self::CONTEXT_ADMIN_TEST;

	/**
	 * Test email connection. Defaults to the primary connection when not set.
	 *
	 * @since 4.9.0
	 *
	 * @var ConnectionInterface|null
	 */
	private $connection;

	/**
	 * Whether to send as HTML (true) or plain text (false).
	 *
	 * @since 4.9.0
	 *
	 * @var bool
	 */
	private $is_html = true;

	/**
	 * Whether to run the DomainChecker after a successful send.
	 *
	 * @since 4.9.0
	 *
	 * @var bool
	 */
	private $run_domain_check = false;

	/**
	 * Test email result.
	 *
	 * @since 3.0.0
	 *
	 * @var int|null
	 */
	private $result = null;

	/**
	 * Domain Checker API object, populated when domain check is enabled and the send succeeded.
	 *
	 * @since 2.6.0
	 *
	 * @var DomainChecker|null
	 */
	private $domain_checker;

	/**
	 * Set the connection used to send the test email.
	 *
	 * @since 4.9.0
	 *
	 * @param ConnectionInterface $connection Connection object.
	 *
	 * @return self
	 */
	public function with_connection( ConnectionInterface $connection ) {

		$this->connection = $connection;

		return $this;
	}

	/**
	 * Set the caller context (drives the X-Mailer-Type header).
	 *
	 * @since 4.9.0
	 *
	 * @param string $context One of the CONTEXT_* constants.
	 *
	 * @return self
	 */
	public function with_context( $context ) {

		$this->context = (string) $context;

		return $this;
	}

	/**
	 * Toggle HTML vs plain text body.
	 *
	 * @since 4.9.0
	 *
	 * @param bool $is_html True for HTML, false for plain text.
	 *
	 * @return self
	 */
	public function as_html( $is_html ) {

		$this->is_html = (bool) $is_html;

		return $this;
	}

	/**
	 * Toggle DomainChecker invocation after a successful send.
	 *
	 * @since 4.9.0
	 *
	 * @param bool $run True to run the domain check, false to skip.
	 *
	 * @return self
	 */
	public function with_domain_check( $run ) {

		$this->run_domain_check = (bool) $run;

		return $this;
	}

	/**
	 * Whether the send succeeded (with or without domain-check issues).
	 *
	 * @since 4.9.0
	 *
	 * @return bool
	 */
	public function is_successful() {

		return $this->result === self::SUCCESS || $this->result === self::FAILED_DOMAIN_CHECK;
	}

	/**
	 * Get the raw result constant.
	 *
	 * @since 4.9.0
	 *
	 * @return int|null
	 */
	public function get_result() {

		return $this->result;
	}

	/**
	 * Get the context this test email was sent in.
	 *
	 * @since 4.10.0
	 *
	 * @return string
	 */
	public function get_context() {

		return $this->context;
	}

	/**
	 * Whether this test email was sent as HTML.
	 *
	 * @since 4.10.0
	 *
	 * @return bool
	 */
	public function is_html() {

		return (bool) $this->is_html;
	}

	/**
	 * Get the DomainChecker instance populated after a successful send (when enabled).
	 *
	 * @since 4.9.0
	 *
	 * @return DomainChecker|null
	 */
	public function get_domain_checker() {

		return $this->domain_checker;
	}

	/**
	 * Get the connection used for the send.
	 *
	 * @since 4.9.0
	 *
	 * @return ConnectionInterface|null
	 */
	public function get_connection() {

		return $this->connection;
	}

	/**
	 * Send the test email.
	 *
	 * Lifted from TestTab::process_post() with the form-data plumbing replaced by
	 * fluent setters; the wp_mail() / ob_start / DomainChecker block stays identical.
	 *
	 * @since 4.9.0
	 *
	 * @param string $recipient Recipient email address.
	 *
	 * @return int|null Result constant (SUCCESS, FAILED, FAILED_DOMAIN_CHECK), or null when validation failed.
	 */
	public function send( $recipient ) { // phpcs:ignore WPForms.PHP.HooksMethod.InvalidPlaceForAddingHooks -- Paired add_filter/remove_filter scope the HTML content-type to this single test send only; moving them to hooks() would change behavior.

		$recipient = filter_var( wp_unslash( $recipient ), FILTER_VALIDATE_EMAIL );

		if ( empty( $recipient ) ) {
			return null;
		}

		if ( ! $this->connection ) {
			$this->connection = wp_mail_smtp()->get_connections_manager()->get_primary_connection();
		}

		$phpmailer = wp_mail_smtp()->get_processor()->get_phpmailer();

		$subject = sprintf(
			/* translators: %1$s - plugin name (WP Mail SMTP), %2$s - email address a test email will be sent to. */
			__( '%1$s: Test email to %2$s', 'wp-mail-smtp' ),
			'WP Mail SMTP',
			$recipient
		);
		$headers = [ 'X-Mailer-Type:' . $this->context ];

		if ( $this->is_html ) {
			add_filter( 'wp_mail_content_type', [ __CLASS__, 'set_test_html_content_type' ] );

			$subject = sprintf(
				/* translators: %1$s - plugin name (WP Mail SMTP), %2$s - email address a test email will be sent to. */
				__( '%1$s: HTML test email to %2$s', 'wp-mail-smtp' ),
				'WP Mail SMTP',
				$recipient
			);
			$headers[] = 'Content-Type: text/html';
		}

		// Send the test mail.
		$result = wp_mail(
			$recipient,
			$subject,
			$this->get_email_message( $this->is_html ),
			$headers
		);

		if ( $this->is_html ) {
			remove_filter( 'wp_mail_content_type', [ __CLASS__, 'set_test_html_content_type' ] );
		}

		/*
		 * Notify a user about the results.
		 */
		if ( $result ) {
			if ( $this->run_domain_check ) {
				$connection_options = $this->connection->get_options();
				$mailer             = $connection_options->get( 'mail', 'mailer' );
				$email              = $connection_options->get( 'mail', 'from_email' );
				$domain             = '';

				// Add the optional sending domain parameter.
				if ( in_array( $mailer, [ 'mailgun', 'sendinblue', 'sendgrid' ], true ) ) {
					$domain = $connection_options->get( $mailer, 'domain' );
				}

				$this->domain_checker = new DomainChecker( $mailer, $email, $domain, $this->connection );

				$this->result = $this->domain_checker->no_issues() ? self::SUCCESS : self::FAILED_DOMAIN_CHECK;
			} else {
				$this->result = self::SUCCESS;
			}
		} else {
			$this->result = self::FAILED;
		}

		/**
		 * Fires after a test email has been sent and its result resolved.
		 *
		 * @since 4.10.0
		 *
		 * @param TestEmail $test_email The test email instance, carrying the result.
		 */
		do_action( 'wp_mail_smtp_test_email_send_after', $this ); // phpcs:ignore WPForms.PHP.ValidateHooks.InvalidHookName -- Hook name drops the repeated namespace/class segment per project convention.

		return $this->result;
	}

	/**
	 * Get the email message that should be sent.
	 *
	 * @since 1.4.0
	 *
	 * @param bool $is_html Whether to send an HTML email or plain text.
	 *
	 * @return string
	 */
	private function get_email_message( $is_html = true ) {

		// Default plain text version of the email.
		$message = self::get_email_message_text();

		if ( $is_html ) {
			$message = $this->get_email_message_html();
		}

		return $message;
	}

	/**
	 * Get the HTML prepared message for test email.
	 *
	 * @since 1.4.0
	 *
	 * @return string
	 */
	private function get_email_message_html() {

		ob_start();
		?>
		<!doctype html>
		<html lang="en">
		<head>
			<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
			<meta http-equiv="X-UA-Compatible" content="IE=edge">
			<meta name="viewport" content="width=device-width">
			<title><?php esc_html_e( 'WP Mail SMTP Test Email', 'wp-mail-smtp' ); ?></title>
			<style type="text/css">@media only screen and (max-width: 599px) {table.body .container {width: 95% !important;}.header {padding: 15px 15px 12px 15px !important;}.header img {width: 200px !important;height: auto !important;}.content, .aside {padding: 30px 40px 20px 40px !important;}}</style>
			<?php
			/**
			 * Fires in the HTML test email head.
			 *
			 * @since 3.10.0
			 */
			do_action( 'wp_mail_smtp_admin_pages_test_tab_get_email_message_html_head' ); // phpcs:ignore WPForms.PHP.ValidateHooks.InvalidHookName -- Public hook name preserved for backward compatibility after move from TestTab to TestEmail.
			?>
		</head>
		<body style="height: 100% !important; width: 100% !important; min-width: 100%; -moz-box-sizing: border-box; -webkit-box-sizing: border-box; box-sizing: border-box; -webkit-font-smoothing: antialiased !important; -moz-osx-font-smoothing: grayscale !important; -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; color: #444; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-weight: normal; padding: 0; margin: 0; Margin: 0; font-size: 14px; mso-line-height-rule: exactly; line-height: 140%; background-color: #f1f1f1; text-align: center;">
		<table border="0" cellpadding="0" cellspacing="0" width="100%" height="100%" class="body" style="border-collapse: collapse; border-spacing: 0; vertical-align: top; mso-table-lspace: 0pt; mso-table-rspace: 0pt; -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; height: 100% !important; width: 100% !important; min-width: 100%; -moz-box-sizing: border-box; -webkit-box-sizing: border-box; box-sizing: border-box; -webkit-font-smoothing: antialiased !important; -moz-osx-font-smoothing: grayscale !important; background-color: #f1f1f1; color: #444; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-weight: normal; padding: 0; margin: 0; Margin: 0; text-align: left; font-size: 14px; mso-line-height-rule: exactly; line-height: 140%;">
			<tr style="padding: 0; vertical-align: top; text-align: left;">
				<td align="center" valign="top" class="body-inner wp-mail-smtp" style="word-wrap: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; mso-table-lspace: 0pt; mso-table-rspace: 0pt; -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; color: #444; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-weight: normal; padding: 0; margin: 0; Margin: 0; font-size: 14px; mso-line-height-rule: exactly; line-height: 140%; text-align: center;">
					<!-- Container -->
					<table border="0" cellpadding="0" cellspacing="0" class="container" style="border-collapse: collapse; border-spacing: 0; padding: 0; vertical-align: top; mso-table-lspace: 0pt; mso-table-rspace: 0pt; -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; width: 600px; margin: 0 auto 30px auto; Margin: 0 auto 30px auto; text-align: inherit;">
						<!-- Header -->
						<tr style="padding: 0; vertical-align: top; text-align: left;">
							<td align="center" valign="middle" class="header" style="word-wrap: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; mso-table-lspace: 0pt; mso-table-rspace: 0pt; -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; color: #444; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-weight: normal; margin: 0; Margin: 0; font-size: 14px; mso-line-height-rule: exactly; line-height: 140%; text-align: center; padding: 30px 30px 22px 30px;">
								<img src="<?php echo esc_url( wp_mail_smtp()->plugin_url . '/assets/images/email/wp-mail-smtp' . ( wp_mail_smtp()->is_white_labeled() ? '-whitelabel' : '' ) . '.png' ); ?>" width="250" alt="<?php esc_attr_e( 'WP Mail SMTP Logo', 'wp-mail-smtp' ); ?>" style="outline: none; text-decoration: none; max-width: 100%; clear: both; -ms-interpolation-mode: bicubic; display: inline-block !important; width: 250px;">
							</td>
						</tr>
						<!-- Content -->
						<tr style="padding: 0; vertical-align: top; text-align: left;">
							<td align="left" valign="top" style="word-wrap: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; mso-table-lspace: 0pt; mso-table-rspace: 0pt; -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; color: #444; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-weight: normal; margin: 0; Margin: 0; text-align: left; font-size: 14px; mso-line-height-rule: exactly; line-height: 140%; background-color: #ffffff; padding: 0; border-right: 1px solid #ddd; border-bottom: 1px solid #ddd; border-left: 1px solid #ddd; border-top: 3px solid #809eb0;">
								<table border="0" cellpadding="0" cellspacing="0" style="border-collapse: collapse; border-spacing: 0; padding: 0; vertical-align: top; mso-table-lspace: 0pt; mso-table-rspace: 0pt; -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; margin: 0; Margin: 0; text-align: inherit;">
									<tr style="padding: 0; vertical-align: top; text-align: left;">
										<td class="content" style="padding: 60px 75px 45px 75px;">
											<div class="success" style="text-align: center;">
												<p class="check" style="-ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; color: #444; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-weight: normal; padding: 0; font-size: 14px; mso-line-height-rule: exactly; line-height: 140%; margin: 0 auto 16px auto; Margin: 0 auto 16px auto; text-align: center;">
													<img src="<?php echo esc_url( wp_mail_smtp()->plugin_url . '/assets/images/email/icon-check.png' ); ?>" width="70" alt="<?php esc_attr_e( 'Success', 'wp-mail-smtp' ); ?>" style="outline: none; text-decoration: none; max-width: 100%; clear: both; -ms-interpolation-mode: bicubic; display: block; margin: 0 auto 0 auto; Margin: 0 auto 0 auto; width: 50px;">
												</p>
												<p class="text-extra-large text-center congrats" style="-ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; color: #444; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-weight: normal; padding: 0; mso-line-height-rule: exactly; line-height: 140%; font-size: 20px; text-align: center; margin: 0 0 20px 0; Margin: 0 0 20px 0;">
													<?php esc_html_e( 'Congrats, test email was sent successfully!', 'wp-mail-smtp' ); ?>
												</p>
												<p class="text-large" style="-ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; color: #444; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-weight: normal; padding: 0; text-align: left; mso-line-height-rule: exactly; line-height: 140%; margin: 0 0 15px 0; Margin: 0 0 15px 0; font-size: 16px;">
													<?php esc_html_e( 'Thank you for trying out WP Mail SMTP. We\'re on a mission to make sure that your emails actually get delivered.', 'wp-mail-smtp' ); ?>
												</p>
												<?php if ( ! wp_mail_smtp()->is_pro() ) : ?>
													<p class="text-large" style="-ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; color: #444; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-weight: normal; padding: 0; text-align: left; mso-line-height-rule: exactly; line-height: 140%; margin: 0 0 15px 0; Margin: 0 0 15px 0; font-size: 16px;">
														<?php esc_html_e( 'If you find this free plugin useful, please consider giving WP Mail SMTP Pro a try!', 'wp-mail-smtp' ); ?>
													</p>
												<?php endif; ?>
												<p class="signature" style="-ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; color: #444; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-weight: normal; padding: 0; font-size: 14px; mso-line-height-rule: exactly; line-height: 140%; text-align: left; margin: 20px 0 0 0; Margin: 20px 0 0 0;">
													<img src="<?php echo esc_url( wp_mail_smtp()->plugin_url . '/assets/images/email/signature.png' ); ?>" width="180" alt="<?php esc_attr_e( 'Signature', 'wp-mail-smtp' ); ?>" style="outline: none; text-decoration: none; max-width: 100%; clear: both; -ms-interpolation-mode: bicubic; width: 180px; display: block; margin: 0 0 0 0; Margin: 0 0 0 0;">
												</p>
												<p style="-ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; color: #444; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-weight: normal; padding: 0; text-align: left; font-size: 14px; mso-line-height-rule: exactly; line-height: 140%; margin: 0 0 15px 0; Margin: 0 0 15px 0;">
													Syed Balkhi<br>
													<?php
													echo esc_html(
														sprintf(
															/* translators: %s - plugin name (WP Mail SMTP). */
															__( 'Co-Founder, %s', 'wp-mail-smtp' ),
															'WP Mail SMTP'
														)
													);
													?>
												</p>
											</div>
										</td>
									</tr>
									<!-- Aside -->
									<?php if ( ! wp_mail_smtp()->is_pro() ) : ?>
										<tr style="padding: 0; vertical-align: top; text-align: left;">
											<td align="left" valign="top" class="aside upsell-mi" style="word-wrap: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; mso-table-lspace: 0pt; mso-table-rspace: 0pt; -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; color: #444; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-weight: normal; margin: 0; Margin: 0; font-size: 14px; mso-line-height-rule: exactly; line-height: 140%; background-color: #f8f8f8; border-top: 1px solid #dddddd; text-align: center !important; padding: 30px 75px 25px 75px;">
												<h6 style="padding: 0; color: #444444; word-wrap: normal; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-weight: bold; mso-line-height-rule: exactly; line-height: 130%; font-size: 18px; text-align: center; margin: 0 0 15px 0; Margin: 0 0 15px 0;">
													<?php esc_html_e( 'Unlock Powerful Features with WP Mail SMTP Pro', 'wp-mail-smtp' ); ?>
												</h6>
												<p class="text-large" style="-ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; color: #444; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-weight: normal; padding: 0; mso-line-height-rule: exactly; line-height: 140%; margin: 0 0 15px 0; Margin: 0 0 15px 0; font-size: 16px; text-align: center;">
													<?php esc_html_e( 'Email Logging with Email Resending', 'wp-mail-smtp' ); ?><br>
													<?php esc_html_e( 'Open & Click Tracking', 'wp-mail-smtp' ); ?><br>
													<?php esc_html_e( 'Email Reports with Weekly Summary', 'wp-mail-smtp' ); ?><br>
													<?php esc_html_e( 'Backup Mailer', 'wp-mail-smtp' ); ?><br>
													<?php esc_html_e( 'Failed Email Alerts via Email, Slack, and SMS', 'wp-mail-smtp' ); ?><br>
													<?php esc_html_e( 'World-Class Support', 'wp-mail-smtp' ); ?>
												</p>
												<p class="text-large last" style="-ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; color: #444; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-weight: normal; padding: 0; mso-line-height-rule: exactly; line-height: 140%; font-size: 13px; text-align: center; margin: 0 0 0 0; Margin: 0 0 0 0;">
													<?php
													echo wp_kses(
														sprintf(
															/* translators: %s - discount amount, e.g. "$50 off". */
															esc_html__( 'WP Mail SMTP users get %s, automatically applied at checkout', 'wp-mail-smtp' ),
															'<span style="font-weight:700;color:#218900;">' . esc_html__( '$50 off', 'wp-mail-smtp' ) . '</span>'
														),
														[ 'span' => [ 'style' => [] ] ]
													);
													?>
												</p>
												<center style="width: 100%;">
													<table class="button large expanded orange" style="border-collapse: collapse; border-spacing: 0; padding: 0; vertical-align: top; text-align: left; mso-table-lspace: 0pt; mso-table-rspace: 0pt; -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; color: #e27730; width: 100% !important;">
														<tr style="padding: 0; vertical-align: top; text-align: left;">
															<td class="button-inner" style="word-wrap: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; mso-table-lspace: 0pt; mso-table-rspace: 0pt; -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; color: #444; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-weight: normal; margin: 0; Margin: 0; text-align: left; font-size: 14px; mso-line-height-rule: exactly; line-height: 100%; padding: 20px 0 20px 0;">
																<table style="border-collapse: collapse; border-spacing: 0; padding: 0; vertical-align: top; text-align: left; mso-table-lspace: 0pt; mso-table-rspace: 0pt; -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; width: 100% !important;">
																	<tr style="padding: 0; vertical-align: top; text-align: left;">
																		<td style="word-wrap: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; mso-table-lspace: 0pt; mso-table-rspace: 0pt; -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-weight: normal; padding: 0; margin: 0; Margin: 0; font-size: 14px; text-align: center; color: #ffffff; background: #e27730; border: 1px solid #c45e1b; border-bottom: 3px solid #c45e1b; mso-line-height-rule: exactly; line-height: 100%;">
																			<a href="<?php echo esc_url( wp_mail_smtp()->get_upgrade_link( [ 'medium' => 'email-test', 'content' => 'Upgrade to Pro Today' ] ) ); // phpcs:ignore WordPress.Arrays.ArrayDeclarationSpacing.AssociativeArrayFound ?>" style="-ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; margin: 0; Margin: 0; font-family: Helvetica, Arial, sans-serif; font-weight: bold; color: #ffffff; text-decoration: none; display: inline-block; border: 0 solid #c45e1b; mso-line-height-rule: exactly; line-height: 100%; padding: 14px 20px 12px 20px; font-size: 20px; text-align: center; width: 100%; padding-left: 0; padding-right: 0;">
																				<?php esc_html_e( 'Upgrade to Pro Today', 'wp-mail-smtp' ); ?>
																			</a>
																		</td>
																	</tr>
																</table>
															</td>
														</tr>
													</table>
												</center>
											</td>
										</tr>
									<?php endif; ?>
									<?php
									/**
									 * Fires in the HTML test email footer.
									 *
									 * @since 3.10.0
									 */
									do_action( 'wp_mail_smtp_admin_pages_test_tab_get_email_message_html_footer' ); // phpcs:ignore WPForms.PHP.ValidateHooks.InvalidHookName -- Public hook name preserved for backward compatibility after move from TestTab to TestEmail.
									?>
								</table>
							</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>
		</body>
		</html>

		<?php
		$message = ob_get_clean();

		return $message;
	}

	/**
	 * Get the plain text prepared message for test email.
	 *
	 * @since 1.4.0
	 * @since 1.5.0 Display an upsell to WP Mail SMTP Pro if free version installed.
	 * @since 2.6.0 Change visibility, so it can be used elsewhere.
	 *
	 * @return string
	 */
	public static function get_email_message_text() {

		$intro = __( 'Congrats, test email was sent successfully!', 'wp-mail-smtp' ) . "\n\n" .
			__( 'Thank you for trying out WP Mail SMTP. We are on a mission to make sure your emails actually get delivered.', 'wp-mail-smtp' ) . "\n\n";

		$signature = '- Syed Balkhi' . "\n" .
			sprintf(
				/* translators: %s - plugin name (WP Mail SMTP). */
				__( 'Co-Founder, %s', 'wp-mail-smtp' ),
				'WP Mail SMTP'
			);

		if ( wp_mail_smtp()->is_pro() ) {
			// WP Mail SMTP Pro paid installed.
			return $intro . $signature;
		}

		// Free WP Mail SMTP is installed.
		$features = [
			__( 'Log all emails and resend failed emails from your email log', 'wp-mail-smtp' ),
			__( 'Track opens and clicks to measure the engagement', 'wp-mail-smtp' ),
			__( 'Get email reports with a weekly summary of your email activity', 'wp-mail-smtp' ),
			__( 'Use a backup mailer if your mail service goes down', 'wp-mail-smtp' ),
			__( 'Get notified of failed emails via email, Slack, or SMS', 'wp-mail-smtp' ),
			__( 'Get help from our world-class support team', 'wp-mail-smtp' ),
		];

		return $intro .
			__( 'If you find this free plugin useful, please consider giving WP Mail SMTP Pro a try!', 'wp-mail-smtp' ) . "\n\n" .
			'https://wpmailsmtp.com/lite-upgrade/' . "\n\n" .
			__( 'Unlock These Powerful Features with WP Mail SMTP Pro:', 'wp-mail-smtp' ) . "\n\n" .
			'+ ' . implode( "\n+ ", $features ) . "\n\n" .
			$signature;
	}

	/**
	 * Set the HTML content type for a test email.
	 *
	 * @since 1.4.0
	 *
	 * @return string
	 */
	public static function set_test_html_content_type() {

		return 'text/html';
	}
}
