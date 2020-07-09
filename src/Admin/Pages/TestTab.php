<?php

namespace WPMailSMTP\Admin\Pages;

use WPMailSMTP\Conflicts;
use WPMailSMTP\Debug;
use WPMailSMTP\MailCatcherInterface;
use WPMailSMTP\Options;
use WPMailSMTP\WP;
use WPMailSMTP\Admin\PageAbstract;

/**
 * Class TestTab is part of Area, displays email testing page of the plugin.
 *
 * @since 1.0.0
 */
class TestTab extends PageAbstract {

	/**
	 * @var string Slug of a tab.
	 */
	protected $slug = 'test';

	/**
	 * Mailer debug error data.
	 *
	 * @since 1.3.0
	 *
	 * @var array
	 */
	private $debug = array();

	/**
	 * @inheritdoc
	 */
	public function get_label() {
		return esc_html__( 'Email Test', 'wp-mail-smtp' );
	}

	/**
	 * @inheritdoc
	 */
	public function get_title() {
		return $this->get_label();
	}

	/**
	 * @inheritdoc
	 */
	public function display() {
		?>

		<form method="POST" action="">
			<?php $this->wp_nonce_field(); ?>

			<!-- Test Email Section Title -->
			<div class="wp-mail-smtp-setting-row wp-mail-smtp-setting-row-content wp-mail-smtp-clear section-heading no-desc" id="wp-mail-smtp-setting-row-email-heading">
				<div class="wp-mail-smtp-setting-field">
					<h2><?php esc_html_e( 'Send a Test Email', 'wp-mail-smtp' ); ?></h2>
				</div>
			</div>

			<!-- Test Email -->
			<div id="wp-mail-smtp-setting-row-test_email" class="wp-mail-smtp-setting-row wp-mail-smtp-setting-row-email wp-mail-smtp-clear">
				<div class="wp-mail-smtp-setting-label">
					<label for="wp-mail-smtp-setting-test_email"><?php esc_html_e( 'Send To', 'wp-mail-smtp' ); ?></label>
				</div>
				<div class="wp-mail-smtp-setting-field">
					<input name="wp-mail-smtp[test][email]" value="<?php echo esc_attr( wp_get_current_user()->user_email ); ?>"
						type="email" id="wp-mail-smtp-setting-test_email" spellcheck="false" required>
					<p class="desc">
						<?php esc_html_e( 'Enter email address where test email will be sent.', 'wp-mail-smtp' ); ?>
					</p>
				</div>
			</div>

			<!-- HTML/Plain -->
			<div id="wp-mail-smtp-setting-row-test_email_html" class="wp-mail-smtp-setting-row wp-mail-smtp-setting-row-checkbox-toggle wp-mail-smtp-clear">
				<div class="wp-mail-smtp-setting-label">
					<label for="wp-mail-smtp-setting-test_email_html"><?php esc_html_e( 'HTML', 'wp-mail-smtp' ); ?></label>
				</div>
				<div class="wp-mail-smtp-setting-field">
					<label for="wp-mail-smtp-setting-test_email_html">
						<input type="checkbox" id="wp-mail-smtp-setting-test_email_html" name="wp-mail-smtp[test][html]" value="yes" checked />
						<span class="wp-mail-smtp-setting-toggle-switch"></span>
						<span class="wp-mail-smtp-setting-toggle-checked-label"><?php esc_html_e( 'On', 'wp-mail-smtp' ); ?></span>
						<span class="wp-mail-smtp-setting-toggle-unchecked-label"><?php esc_html_e( 'Off', 'wp-mail-smtp' ); ?></span>
					</label>
					<p class="desc">
						<?php esc_html_e( 'Send this email in HTML or in plain text format.', 'wp-mail-smtp' ); ?>
					</p>
				</div>
			</div>

			<p class="wp-mail-smtp-submit">
				<?php
				$btn       = 'wp-mail-smtp-btn-orange';
				$disabled  = '';
				$help_text = '';

				$mailer = wp_mail_smtp()->get_providers()->get_mailer(
					Options::init()->get( 'mail', 'mailer' ),
					wp_mail_smtp()->get_processor()->get_phpmailer()
				);

				if ( ! $mailer || ! $mailer->is_mailer_complete() ) {
					$btn      = 'wp-mail-smtp-btn-red';
					$disabled = 'disabled';

					$help_text = '<span class="help-text"><strong>' . esc_html__( 'You cannot send an email. Mailer is not properly configured. Please check your settings.', 'wp-mail-smtp' ) . '</strong></span>';
				}
				?>
				<button type="submit" class="wp-mail-smtp-btn wp-mail-smtp-btn-md <?php echo esc_attr( $btn ); ?>" <?php echo esc_attr( $disabled ); ?>>
					<?php esc_html_e( 'Send Email', 'wp-mail-smtp' ); ?>
				</button>
				<?php echo $help_text; ?>
			</p>
		</form>

		<?php
		$this->display_debug_details();
	}

	/**
	 * @inheritdoc
	 */
	public function process_post( $data ) {

		$this->check_admin_referer();

		if ( ! empty( $data['test']['email'] ) ) {
			$data['test']['email'] = filter_var( $data['test']['email'], FILTER_VALIDATE_EMAIL );
		}

		$is_html = true;
		if ( empty( $data['test']['html'] ) ) {
			$is_html = false;
		}

		if ( empty( $data['test']['email'] ) ) {
			WP::add_admin_notice(
				esc_html__( 'Test failed. Please use a valid email address and try to resend the test email.', 'wp-mail-smtp' ),
				WP::ADMIN_NOTICE_WARNING
			);
			return;
		}

		$phpmailer = wp_mail_smtp()->get_processor()->get_phpmailer();

		// Set SMTPDebug level, default is 3 (commands + data + connection status).
		$phpmailer->SMTPDebug = apply_filters( 'wp_mail_smtp_admin_test_email_smtp_debug', 3 );

		/* translators: %s - email address a test email will be sent to. */
		$subject = 'WP Mail SMTP: ' . sprintf( esc_html__( 'Test email to %s', 'wp-mail-smtp' ), $data['test']['email'] );

		if ( $is_html ) {
			add_filter( 'wp_mail_content_type', array( __CLASS__, 'set_test_html_content_type' ) );

			/* translators: %s - email address a test email will be sent to. */
			$subject = 'WP Mail SMTP: HTML ' . sprintf( esc_html__( 'Test email to %s', 'wp-mail-smtp' ), $data['test']['email'] );
		}

		// Start output buffering to grab smtp debugging output.
		ob_start();

		// Send the test mail.
		$result = wp_mail(
			$data['test']['email'],
			$subject,
			$this->get_email_message( $is_html ),
			array(
				'X-Mailer-Type:WPMailSMTP/Admin/Test',
			)
		);

		$smtp_debug = ob_get_clean();

		if ( $is_html ) {
			remove_filter( 'wp_mail_content_type', array( __NAMESPACE__, 'set_test_html_content_type' ) );
		}

		/*
		 * Notify a user about the results.
		 */
		if ( $result ) {
			$result_message = esc_html__( 'Test plain text email was sent successfully!', 'wp-mail-smtp' );
			if ( $is_html ) {
				$result_message = sprintf(
					/* translators: %s - "HTML" in bold. */
					esc_html__( 'Test %s email was sent successfully! Please check your inbox to make sure it is delivered.', 'wp-mail-smtp' ),
					'<strong>HTML</strong>'
				);
			}
			WP::add_admin_notice(
				$result_message,
				WP::ADMIN_NOTICE_SUCCESS
			);
		} else {
			// Grab the smtp debugging output.
			$this->debug['smtp_debug'] = $smtp_debug;
			$this->debug['smtp_error'] = wp_strip_all_tags( $phpmailer->ErrorInfo );
			$this->debug['error_log']  = $this->get_debug_messages( $phpmailer, $smtp_debug );
		}
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
		$message = $this->get_email_message_text();

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
			<title>WP Mail SMTP Test Email</title>
			<style type="text/css">@media only screen and (max-width: 599px) {table.body .container {width: 95% !important;}.header {padding: 15px 15px 12px 15px !important;}.header img {width: 200px !important;height: auto !important;}.content, .aside {padding: 30px 40px 20px 40px !important;}}</style>
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
								<img src="<?php echo esc_url( wp_mail_smtp()->plugin_url . '/assets/images/email/wp-mail-smtp' . ( wp_mail_smtp()->is_white_labeled() ? '-whitelabel' : '' ) . '.png' ); ?>" width="250" alt="WP Mail SMTP Logo" style="outline: none; text-decoration: none; max-width: 100%; clear: both; -ms-interpolation-mode: bicubic; display: inline-block !important; width: 250px;">
							</td>
						</tr>
						<!-- Content -->
						<tr style="padding: 0; vertical-align: top; text-align: left;">
							<td align="left" valign="top" class="content" style="word-wrap: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; mso-table-lspace: 0pt; mso-table-rspace: 0pt; -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; color: #444; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-weight: normal; margin: 0; Margin: 0; text-align: left; font-size: 14px; mso-line-height-rule: exactly; line-height: 140%; background-color: #ffffff; padding: 60px 75px 45px 75px; border-right: 1px solid #ddd; border-bottom: 1px solid #ddd; border-left: 1px solid #ddd; border-top: 3px solid #809eb0;">
								<div class="success" style="text-align: center;">
									<p class="check" style="-ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; color: #444; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-weight: normal; padding: 0; font-size: 14px; mso-line-height-rule: exactly; line-height: 140%; margin: 0 auto 16px auto; Margin: 0 auto 16px auto; text-align: center;">
										<img src="<?php echo esc_url( wp_mail_smtp()->plugin_url . '/assets/images/email/icon-check.png' ); ?>" width="70" alt="Success" style="outline: none; text-decoration: none; max-width: 100%; clear: both; -ms-interpolation-mode: bicubic; display: block; margin: 0 auto 0 auto; Margin: 0 auto 0 auto; width: 50px;">
									</p>
									<p class="text-extra-large text-center congrats" style="-ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; color: #444; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-weight: normal; padding: 0; mso-line-height-rule: exactly; line-height: 140%; font-size: 20px; text-align: center; margin: 0 0 20px 0; Margin: 0 0 20px 0;">
										Congrats, test email was sent successfully!
									</p>
									<p class="text-large" style="-ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; color: #444; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-weight: normal; padding: 0; text-align: left; mso-line-height-rule: exactly; line-height: 140%; margin: 0 0 15px 0; Margin: 0 0 15px 0; font-size: 16px;">
										Thank you for trying out WP Mail SMTP. We're on a mission to make sure that your emails actually get delivered.
									</p>
									<?php if ( ! wp_mail_smtp()->is_pro() ) : ?>
										<p class="text-large" style="-ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; color: #444; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-weight: normal; padding: 0; text-align: left; mso-line-height-rule: exactly; line-height: 140%; margin: 0 0 15px 0; Margin: 0 0 15px 0; font-size: 16px;">
											If you find this free plugin useful, please consider giving WP Mail SMTP Pro a try!
										</p>
									<?php endif; ?>
									<p class="signature" style="-ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; color: #444; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-weight: normal; padding: 0; font-size: 14px; mso-line-height-rule: exactly; line-height: 140%; text-align: left; margin: 20px 0 0 0; Margin: 20px 0 0 0;">
										<img src="<?php echo esc_url( wp_mail_smtp()->plugin_url . '/assets/images/email/signature.png' ); ?>" width="180" alt="Signature" style="outline: none; text-decoration: none; max-width: 100%; clear: both; -ms-interpolation-mode: bicubic; width: 180px; display: block; margin: 0 0 0 0; Margin: 0 0 0 0;">
									</p>
									<p style="-ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; color: #444; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-weight: normal; padding: 0; text-align: left; font-size: 14px; mso-line-height-rule: exactly; line-height: 140%; margin: 0 0 15px 0; Margin: 0 0 15px 0;">
										Jared Atchison<br>Lead Developer, WP Mail SMTP
									</p>
								</div>
							</td>
						</tr>
						<!-- Aside -->
						<?php if ( ! wp_mail_smtp()->is_pro() ) : ?>
							<tr style="padding: 0; vertical-align: top; text-align: left;">
								<td align="left" valign="top" class="aside upsell-mi" style="word-wrap: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; mso-table-lspace: 0pt; mso-table-rspace: 0pt; -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; color: #444; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-weight: normal; margin: 0; Margin: 0; font-size: 14px; mso-line-height-rule: exactly; line-height: 140%; background-color: #f8f8f8; border-top: 1px solid #dddddd; border-right: 1px solid #dddddd; border-bottom: 1px solid #dddddd; border-left: 1px solid #dddddd; text-align: center !important; padding: 30px 75px 25px 75px;">
									<h6 style="padding: 0; color: #444444; word-wrap: normal; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-weight: bold; mso-line-height-rule: exactly; line-height: 130%; font-size: 18px; text-align: center; margin: 0 0 15px 0; Margin: 0 0 15px 0;">
										Unlock More Features with WP Mail SMTP Pro
									</h6>
									<p class="text-large" style="-ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; color: #444; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-weight: normal; padding: 0; mso-line-height-rule: exactly; line-height: 140%; margin: 0 0 15px 0; Margin: 0 0 15px 0; font-size: 16px; text-align: center;">
										Email Logs and Notification Controls<br>
										Amazon SES / Outlook.com / Office 365 integrations<br>
										Access to our world class support team
									</p>
									<p class="text-large last" style="-ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; color: #444; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-weight: normal; padding: 0; mso-line-height-rule: exactly; line-height: 140%; font-size: 13px; text-align: center; margin: 0 0 0 0; Margin: 0 0 0 0;">
										WP Mail SMTP users get <span style="font-weight:700;color:#218900;">$50 off</span>, automatically applied at checkout
									</p>
									<center style="width: 100%;">
										<table class="button large expanded orange" style="border-collapse: collapse; border-spacing: 0; padding: 0; vertical-align: top; text-align: left; mso-table-lspace: 0pt; mso-table-rspace: 0pt; -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; color: #e27730; width: 100% !important;">
											<tr style="padding: 0; vertical-align: top; text-align: left;">
												<td class="button-inner" style="word-wrap: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; mso-table-lspace: 0pt; mso-table-rspace: 0pt; -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; color: #444; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-weight: normal; margin: 0; Margin: 0; text-align: left; font-size: 14px; mso-line-height-rule: exactly; line-height: 100%; padding: 20px 0 20px 0;">
													<table style="border-collapse: collapse; border-spacing: 0; padding: 0; vertical-align: top; text-align: left; mso-table-lspace: 0pt; mso-table-rspace: 0pt; -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; width: 100% !important;">
														<tr style="padding: 0; vertical-align: top; text-align: left;">
															<td style="word-wrap: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; mso-table-lspace: 0pt; mso-table-rspace: 0pt; -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-weight: normal; padding: 0; margin: 0; Margin: 0; font-size: 14px; text-align: center; color: #ffffff; background: #e27730; border: 1px solid #c45e1b; border-bottom: 3px solid #c45e1b; mso-line-height-rule: exactly; line-height: 100%;">
																<a href="<?php echo esc_url( wp_mail_smtp()->get_upgrade_link( 'email-test' ) ); ?>" style="-ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; margin: 0; Margin: 0; font-family: Helvetica, Arial, sans-serif; font-weight: bold; color: #ffffff; text-decoration: none; display: inline-block; border: 0 solid #c45e1b; mso-line-height-rule: exactly; line-height: 100%; padding: 14px 20px 12px 20px; font-size: 20px; text-align: center; width: 100%; padding-left: 0; padding-right: 0;">
																	Upgrade to WP Mail SMTP Pro Today
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
	 *
	 * @return string
	 */
	private function get_email_message_text() {

		// phpcs:disable
		if ( wp_mail_smtp()->is_pro() ) {
			// WP Mail SMTP Pro paid installed.
			$message =
'Congrats, test email was sent successfully!

Thank you for trying out WP Mail SMTP. We are on a mission to make sure your emails actually get delivered.

- Jared Atchison
Lead Developer, WP Mail SMTP';
		} else {
			// Free WP Mail SMTP is installed.
			$message =
'Congrats, test email was sent successfully!

Thank you for trying out WP Mail SMTP. We are on a mission to make sure your emails actually get delivered.

If you find this free plugin useful, please consider giving WP Mail SMTP Pro a try!

https://wpmailsmtp.com/lite-upgrade/

Unlock More Features with WP Mail SMTP Pro:

+ Email Logs and Notification Controls
+ Amazon SES / Outlook.com / Office 365 integrations
+ Access to our world class support team

- Jared Atchison
Lead Developer, WP Mail SMTP';
		}
		// phpcs:enable

		return $message;
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

	/**
	 * Prepare debug information, that will help users to identify the error.
	 *
	 * @since 1.0.0
	 *
	 * @param MailCatcherInterface $phpmailer  The MailCatcher object.
	 * @param string               $smtp_debug The SMTP debug message.
	 *
	 * @return string
	 */
	protected function get_debug_messages( $phpmailer, $smtp_debug ) {

		$options   = new Options();
		$conflicts = new Conflicts();

		$this->debug['mailer'] = $options->get( 'mail', 'mailer' );

		/*
		 * Versions Debug.
		 */

		$versions_text = '<strong>Versions:</strong><br>';

		$versions_text .= '<strong>WordPress:</strong> ' . get_bloginfo( 'version' ) . '<br>';
		$versions_text .= '<strong>WordPress MS:</strong> ' . ( is_multisite() ? 'Yes' : 'No' ) . '<br>';
		$versions_text .= '<strong>PHP:</strong> ' . PHP_VERSION . '<br>';
		$versions_text .= '<strong>WP Mail SMTP:</strong> ' . WPMS_PLUGIN_VER . '<br>';

		/*
		 * Mailer Debug.
		 */

		$mailer_text = '<strong>Params:</strong><br>';

		$mailer_text .= '<strong>Mailer:</strong> ' . $this->debug['mailer'] . '<br>';
		$mailer_text .= '<strong>Constants:</strong> ' . ( $options->is_const_enabled() ? 'Yes' : 'No' ) . '<br>';
		if ( $conflicts->is_detected() ) {
			$mailer_text .= '<strong>Conflicts:</strong> ' . esc_html( $conflicts->get_conflict_name() ) . '<br>';
		}

		// Display different debug info based on the mailer.
		$mailer = wp_mail_smtp()->get_providers()->get_mailer( $this->debug['mailer'], $phpmailer );

		if ( $mailer ) {
			$mailer_text .= $mailer->get_debug_info();
		}

		$phpmailer_error = $phpmailer->ErrorInfo; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

		// Append any PHPMailer errors to the mailer debug (except SMTP mailer, which has the full error output below).
		if (
			! empty( $phpmailer_error ) &&
			! $options->is_mailer_smtp()
		) {
			$mailer_text .= '<br><br><strong>PHPMailer Debug:</strong><br>' .
			                wp_strip_all_tags( $phpmailer_error ) .
			                '<br>';
		}

		/*
		 * General Debug.
		 */

		$debug_text = implode( '<br>', Debug::get() );
		Debug::clear();
		if ( ! empty( $debug_text ) ) {
			$debug_text = '<br><strong>Debug:</strong><br>' . $debug_text . '<br>';
		}

		/*
		 * SMTP Debug.
		 */

		$smtp_text = '';
		if ( $options->is_mailer_smtp() ) {
			$smtp_text = '<strong>SMTP Debug:</strong><br>';
			if ( ! empty( $smtp_debug ) ) {
				$smtp_text .= '<pre>' . $smtp_debug . '</pre>';
			} else {
				$smtp_text .= '[empty]';
			}
		}

		$errors = apply_filters(
			'wp_mail_smtp_admin_test_get_debug_messages',
			array(
				$versions_text,
				$mailer_text,
				$debug_text,
				$smtp_text,
			)
		);

		return '<pre>' . implode( '<br>', array_filter( $errors ) ) . '</pre>';
	}

	/**
	 * Returns debug information for detection, processing, and display.
	 *
	 * @since 1.3.0
	 *
	 * @return array
	 */
	protected function get_debug_details() {

		$options         = new Options();
		$smtp_host       = $options->get( 'smtp', 'host' );
		$smtp_port       = $options->get( 'smtp', 'port' );
		$smtp_encryption = $options->get( 'smtp', 'encryption' );

		$details = [
			// [any] - cURL error 60/77.
			[
				'mailer'      => 'any',
				'errors'      => [
					[ 'cURL error 60' ],
					[ 'cURL error 77' ],
				],
				'description' => [
					'<strong>' . esc_html__( 'SSL certificate issue.', 'wp-mail-smtp' ) . '</strong>',
					esc_html__( 'This means your web server cannot reliably make secure connections (make requests to HTTPS sites).', 'wp-mail-smtp' ),
					esc_html__( 'Typically this error is returned when web server is not configured properly.', 'wp-mail-smtp' ),
				],
				'steps'       => [
					esc_html__( 'Contact your web hosting provider and inform them your site has an issue with SSL certificates.', 'wp-mail-smtp' ),
					esc_html__( 'The exact error you can provide them is in the Error log, available at the bottom of this page.', 'wp-mail-smtp' ),
					esc_html__( 'Ask them to resolve the issue then try again.', 'wp-mail-smtp' ),
				],
			],
			// [any] - cURL error 6/7.
			[
				'mailer'      => 'any',
				'errors'      => [
					[ 'cURL error 6' ],
					[ 'cURL error 7' ],
				],
				'description' => [
					'<strong>' . esc_html__( 'Could not connect to host.', 'wp-mail-smtp' ) . '</strong>',
					! empty( $smtp_host )
						? sprintf( /* translators: %s - SMTP host address. */
							esc_html__( 'This means your web server was unable to connect to %s.', 'wp-mail-smtp' ),
							$smtp_host
						)
						: esc_html__( 'This means your web server was unable to connect to the host server.', 'wp-mail-smtp' ),
					esc_html__( 'Typically this error is returned your web server is blocking the connections or the SMTP host denying the request.', 'wp-mail-smtp' ),
				],
				'steps'       => [
					sprintf( /* translators: %s - SMTP host address. */
						esc_html__( 'Contact your web hosting provider and ask them to verify your server can connect to %s. Additionally, ask them if a firewall or security policy may be preventing the connection.', 'wp-mail-smtp' ),
						$smtp_host
					),
					esc_html__( 'If using "Other SMTP" Mailer, triple check your SMTP settings including host address, email, and password.', 'wp-mail-smtp' ),
					esc_html__( 'If using "Other SMTP" Mailer, contact your SMTP host to confirm they are accepting outside connections with the settings you have configured (address, username, port, security, etc).', 'wp-mail-smtp' ),
				],
			],
			// [any] - cURL error XX (other).
			[
				'mailer'      => 'any',
				'errors'      => [
					[ 'cURL error' ],
				],
				'description' => [
					'<strong>' . esc_html__( 'Could not connect to your host.', 'wp-mail-smtp' ) . '</strong>',
					! empty( $smtp_host )
						? sprintf( /* translators: %s - SMTP host address. */
							esc_html__( 'This means your web server was unable to connect to %s.', 'wp-mail-smtp' ),
							$smtp_host
						)
						: esc_html__( 'This means your web server was unable to connect to the host server.', 'wp-mail-smtp' ),
					esc_html__( 'Typically this error is returned when web server is not configured properly.', 'wp-mail-smtp' ),
				],
				'steps'       => [
					esc_html__( 'Contact your web hosting provider and inform them you are having issues making outbound connections.', 'wp-mail-smtp' ),
					esc_html__( 'The exact error you can provide them is in the Error log, available at the bottom of this page.', 'wp-mail-smtp' ),
					esc_html__( 'Ask them to resolve the issue then try again.', 'wp-mail-smtp' ),
				],
			],
			// [smtp] - SMTP Error: Count not authenticate.
			[
				'mailer'      => 'smtp',
				'errors'      => [
					[ 'SMTP Error: Could not authenticate.' ],
				],
				'description' => [
					'<strong>' . esc_html__( 'Could not authenticate your SMTP account.', 'wp-mail-smtp' ) . '</strong>',
					esc_html__( 'This means we were able to connect to your SMTP host, but were not able to proceed using the email/password in the settings.', 'wp-mail-smtp' ),
					esc_html__( 'Typically this error is returned when the email or password is not correct or is not what the SMTP host is expecting.', 'wp-mail-smtp' ),
				],
				'steps'       => [
					esc_html__( 'Triple check your SMTP settings including host address, email, and password. If you have recently reset your password you will need to update the settings.', 'wp-mail-smtp' ),
					esc_html__( 'Contact your SMTP host to confirm you are using the correct username and password.', 'wp-mail-smtp' ),
					esc_html__( 'Verify with your SMTP host that your account has permissions to send emails using outside connections.', 'wp-mail-smtp' ),
				],
			],
			// [smtp] - Sending bulk email, hitting rate limit.
			[
				'mailer'      => 'smtp',
				'errors'      => [
					[ 'We do not authorize the use of this system to transport unsolicited' ],
				],
				'description' => [
					'<strong>' . esc_html__( 'Error due to unsolicited and/or bulk e-mail.', 'wp-mail-smtp' ) . '</strong>',
					esc_html__( 'This means the connection to your SMTP host was made successfully, but the host rejected the email.', 'wp-mail-smtp' ),
					esc_html__( 'Typically this error is returned when you are sending too many e-mails or e-mails that have been identified as spam.', 'wp-mail-smtp' ),
				],
				'steps'       => [
					esc_html__( 'Check the emails that are sending are sending individually. Example: email is not sending to 30 recipients. You can install any WordPress e-mail logging plugin to do that.', 'wp-mail-smtp' ),
					esc_html__( 'Contact your SMTP host to ask about sending/rate limits.', 'wp-mail-smtp' ),
					esc_html__( 'Verify with them your SMTP account is in good standing and your account has not been flagged.', 'wp-mail-smtp' ),
				],
			],
			// [smtp] - Unauthenticated senders not allowed.
			[
				'mailer'      => 'smtp',
				'errors'      => [
					[ 'Unauthenticated senders not allowed' ],
				],
				'description' => [
					'<strong>' . esc_html__( 'Unauthenticated senders are not allowed.', 'wp-mail-smtp' ) . '</strong>',
					esc_html__( 'This means the connection to your SMTP host was made successfully, but you should enable Authentication and provide correct Username and Password.', 'wp-mail-smtp' ),
				],
				'steps'       => [
					esc_html__( 'Go to WP Mail SMTP plugin Settings page.', 'wp-mail-smtp' ),
					esc_html__( 'Enable Authentication', 'wp-mail-smtp' ),
					esc_html__( 'Enter correct SMTP Username (usually this is an email address) and Password in the appropriate fields.', 'wp-mail-smtp' ),
				],
			],
			// [smtp] - certificate verify failed.
			// Has to be defined before "SMTP connect() failed" error, since this is a more specific error,
			// which contains the "SMTP connect() failed" error message as well.
			[
				'mailer'      => 'smtp',
				'errors'      => [
					[ 'certificate verify failed' ],
				],
				'description' => [
					'<strong>' . esc_html__( 'Misconfigured server certificate.', 'wp-mail-smtp' ) . '</strong>',
					esc_html__( 'This means OpenSSL on your server isn\'t able to verify the host certificate.', 'wp-mail-smtp' ),
					esc_html__( 'There are a few reasons why this is happening. It could be that the host certificate is misconfigured, or this server\'s OpenSSL is using an outdated CA bundle.', 'wp-mail-smtp' ),
				],
				'steps'       => [
					esc_html__( 'Verify that the host\'s SSL certificate is valid.', 'wp-mail-smtp' ),
					sprintf(
						wp_kses( /* translators: %s - URL to the PHP openssl manual */
							__( 'Contact your hosting support, show them the "full Error Log for debugging" below and share this <a href="%s" target="_blank" rel="noopener noreferrer">link</a> with them.', 'wp-mail-smtp' ),
							[
								'a' => [
									'href'   => [],
									'target' => [],
									'rel'    => [],
								],
							]
						),
						'https://www.php.net/manual/en/migration56.openssl.php'
					),
				],
			],
			// [smtp] - SMTP connect() failed.
			[
				'mailer'      => 'smtp',
				'errors'      => [
					[ 'SMTP connect() failed' ],
				],
				'description' => [
					'<strong>' . esc_html__( 'Could not connect to the SMTP host.', 'wp-mail-smtp' ) . '</strong>',
					! empty( $smtp_host )
						? sprintf( /* translators: %s - SMTP host address. */
							esc_html__( 'This means your web server was unable to connect to %s.', 'wp-mail-smtp' ),
							$smtp_host
						)
						: esc_html__( 'This means your web server was unable to connect to the host server.', 'wp-mail-smtp' ),
					esc_html__( 'Typically this error is returned for one of the following reasons:', 'wp-mail-smtp' ),
					'-' . esc_html__( 'SMTP settings are incorrect (wrong port, security setting, incorrect host).', 'wp-mail-smtp' ) . '<br>' .
					'-' . esc_html__( 'Your web server is blocking the connection.', 'wp-mail-smtp' ) . '<br>' .
					'-' . esc_html__( 'Your SMTP host is rejecting the connection.', 'wp-mail-smtp' ),
				],
				'steps'       => [
					esc_html__( 'Triple check your SMTP settings including host address, email, and password, port, and security.', 'wp-mail-smtp' ),
					sprintf(
						wp_kses( /* translators: %1$s - SMTP host address, %2$s - SMTP port, %3$s - SMTP encryption. */
							__( 'Contact your web hosting provider and ask them to verify your server can connect to %1$s on port %2$s using %3$s encryption. Additionally, ask them if a firewall or security policy may be preventing the connection - many shared hosts block certain ports.<br><strong>Note: this is the most common cause of this issue.</strong>', 'wp-mail-smtp' ),
							[
								'a'      => [
									'href'   => [],
									'rel'    => [],
									'target' => [],
								],
								'strong' => [],
								'br'     => [],
							]
						),
						$smtp_host,
						$smtp_port,
						'none' === $smtp_encryption ? esc_html__( 'no', 'wp-mail-smtp' ) : $smtp_encryption
					),
					esc_html__( 'Contact your SMTP host to confirm you are using the correct username and password.', 'wp-mail-smtp' ),
					esc_html__( 'Verify with your SMTP host that your account has permissions to send emails using outside connections.', 'wp-mail-smtp' ),
				],
			],
			// [mailgun] - Please activate your Mailgun account.
			[
				'mailer'      => 'mailgun',
				'errors'      => [
					[ 'Please activate your Mailgun account' ],
				],
				'description' => [
					'<strong>' . esc_html__( 'Mailgun failed.', 'wp-mail-smtp' ) . '</strong>',
					esc_html__( 'It seems that you forgot to activate your Mailgun account.', 'wp-mail-smtp' ),
				],
				'steps'       => [
					esc_html__( 'Check your inbox you used to create a Mailgun account. Click the activation link in an email from Mailgun.', 'wp-mail-smtp' ),
					esc_html__( 'If you do not see activation email, go to your Mailgun control panel and resend the activation email.', 'wp-mail-smtp' ),
				],
			],
			// [mailgun] - Forbidden.
			[
				'mailer'      => 'mailgun',
				'errors'      => [
					[ 'Forbidden' ],
				],
				'description' => [
					'<strong>' . esc_html__( 'Mailgun failed.', 'wp-mail-smtp' ) . '</strong>',
					esc_html__( 'Typically this error is because there is an issue with your Mailgun settings, in many cases the API key.', 'wp-mail-smtp' ),
				],
				'steps'       => [
					esc_html__( 'Verify your API key is correct.', 'wp-mail-smtp' ),
					esc_html__( 'Go to your Mailgun account and view your API key.', 'wp-mail-smtp' ),
					esc_html__( 'Note that the API key includes the "key" prefix, so make sure that it is in the WP Mail SMTP Mailgun API setting.', 'wp-mail-smtp' ),
				],
			],
			// [mailgun] - Free accounts are for test purposes only.
			[
				'mailer'      => 'mailgun',
				'errors'      => [
					[ 'Free accounts are for test purposes only' ],
				],
				'description' => [
					'<strong>' . esc_html__( 'Mailgun failed.', 'wp-mail-smtp' ) . '</strong>',
					esc_html__( 'Your Mailgun account does not have access to send emails.', 'wp-mail-smtp' ),
					esc_html__( 'Typically this error is because you have not set up and/or complete domain name verification for your Mailgun account.', 'wp-mail-smtp' ),
				],
				'steps'       => [
					sprintf(
						wp_kses( /* translators: %s - Mailgun documentation URL. */
							__( 'Go to our how-to guide for setting up <a href="%s" target="_blank" rel="noopener noreferrer">Mailgun with WP Mail SMTP</a>.', 'wp-mail-smtp' ),
							[
								'a' => [
									'href'   => [],
									'rel'    => [],
									'target' => [],
								],
							]
						),
						'https://wpmailsmtp.com/docs/how-to-set-up-the-mailgun-mailer-in-wp-mail-smtp/'
					),
					esc_html__( 'Complete the steps in section "2. Verify Your Domain".', 'wp-mail-smtp' ),
				],
			],
			// [gmail] - 401: Login Required.
			[
				'mailer'      => 'gmail',
				'errors'      => [
					[ '401', 'Login Required' ],
				],
				'description' => [
					'<strong>' . esc_html__( 'Google API Error.', 'wp-mail-smtp' ) . '</strong>',
					esc_html__( 'You have not properly configured Gmail mailer.', 'wp-mail-smtp' ),
					esc_html__( 'Make sure that you have clicked the "Allow plugin to send emails using your Google account" button under Gmail settings.', 'wp-mail-smtp' ),
				],
				'steps'       => [
					esc_html__( 'Go to plugin Settings page and click the "Allow plugin to send emails using your Google account" button.', 'wp-mail-smtp' ),
					esc_html__( 'After the click you should be redirected to a Gmail authorization screen, where you will be asked a permission to send emails on your behalf.', 'wp-mail-smtp' ),
					esc_html__( 'Please click "Agree", if you see that button. If not - you will need to enable less secure apps first:', 'wp-mail-smtp' )
					. '<ul>'
						. '<li>' .
							sprintf(
								wp_kses( /* translators: %s - Google support article URL. */
									__( 'if you are using regular Gmail account, please <a href="%s" target="_blank" rel="noopener noreferrer">read this article</a> to proceed.', 'wp-mail-smtp' ),
									[
										'a' => [
											'href'   => [],
											'target' => [],
											'rel'    => [],
										],
									]
								),
								'https://support.google.com/accounts/answer/6010255?hl=en'
							)
						. '</li>'
						. '<li>' .
							sprintf(
								wp_kses( /* translators: %s - Google support article URL. */
									__( 'if you are using G Suite, please <a href="%s" target="_blank" rel="noopener noreferrer">read this article</a> to proceed.', 'wp-mail-smtp' ),
									[
										'a' => [
											'href'   => [],
											'target' => [],
											'rel'    => [],
										],
									]
								),
								'https://support.google.com/cloudidentity/answer/6260879?hl=en'
							)
						. '</li>'
					. '</ul>',
				],
			],
			// [gmail] - 400: Recipient address required.
			[
				'mailer'      => 'gmail',
				'errors'      => [
					[ '400', 'Recipient address required' ],
				],
				'description' => [
					'<strong>' . esc_html__( 'Google API Error.', 'wp-mail-smtp' ) . '</strong>',
					esc_html__( 'Typically this error is because address the email was sent to is invalid or was empty.', 'wp-mail-smtp' ),
				],
				'steps'       => [
					esc_html__( 'Check the "Send To" email address used and confirm it is a valid email and was not empty.', 'wp-mail-smtp' ),
					sprintf( /* translators: 1 - correct email address example. 2 - incorrect email address example. */
						esc_html__( 'It should be something like this: %1$s. These are incorrect values: %2$s.', 'wp-mail-smtp' ),
						'<code>info@example.com</code>',
						'<code>info@localhost</code>, <code>info@192.168.1.1</code>'
					),
					esc_html__( 'Make sure that the generated email has a TO header, useful when you are responsible for email creation.', 'wp-mail-smtp' ),
				],
			],
			// [gmail] - Token has been expired or revoked.
			[
				'mailer'      => 'gmail',
				'errors'      => [
					[ 'invalid_grant', 'Token has been expired or revoked' ],
				],
				'description' => [
					'<strong>' . esc_html__( 'Google API Error.', 'wp-mail-smtp' ) . '</strong>',
					esc_html__( 'Unfortunately, this error can be due to many different reasons.', 'wp-mail-smtp' ),
				],
				'steps'       => [
					sprintf(
						wp_kses( /* translators: %s - Blog article URL. */
							__( 'Please <a href="%s" target="_blank" rel="noopener noreferrer">read this article</a> to learn more about what can cause this error and how it can be resolved.', 'wp-mail-smtp' ),
							[
								'a' => [
									'href'   => [],
									'target' => [],
									'rel'    => [],
								],
							]
						),
						'https://blog.timekit.io/google-oauth-invalid-grant-nightmare-and-how-to-fix-it-9f4efaf1da35'
					),
				],
			],
			// [gmail] - Code was already redeemed.
			[
				'mailer'      => 'gmail',
				'errors'      => [
					[ 'invalid_grant', 'Code was already redeemed' ],
				],
				'description' => [
					'<strong>' . esc_html__( 'Google API Error.', 'wp-mail-smtp' ) . '</strong>',
					esc_html__( 'Authentication code that Google returned to you has already been used on your previous auth attempt.', 'wp-mail-smtp' ),
				],
				'steps'       => [
					esc_html__( 'Make sure that you are not trying to manually clean up the plugin options to retry the "Allow..." step.', 'wp-mail-smtp' ),
					esc_html__( 'Reinstall the plugin with clean plugin data turned on on Misc page. This will remove all the plugin options and you will be safe to retry.', 'wp-mail-smtp' ),
					esc_html__( 'Make sure there is no aggressive caching on site admin area pages or try to clean cache between attempts.', 'wp-mail-smtp' ),
				],
			],
			// [gmail] - 400: Mail service not enabled.
			[
				'mailer'      => 'gmail',
				'errors'      => [
					[ '400', 'Mail service not enabled' ],
				],
				'description' => [
					'<strong>' . esc_html__( 'Google API Error.', 'wp-mail-smtp' ) . '</strong>',
					esc_html__( 'There are various reasons for that, please review the steps below.', 'wp-mail-smtp' ),
				],
				'steps'       => [
					sprintf(
						wp_kses( /* translators: %s - Google G Suite Admin area URL. */
							__( 'Make sure that your G Suite trial period has not expired. You can check the status <a href="%s" target="_blank" rel="noopener noreferrer">here</a>.', 'wp-mail-smtp' ),
							[
								'a' => [
									'href'   => [],
									'rel'    => [],
									'target' => [],
								],
							]
						),
						'https://admin.google.com'
					),
					sprintf(
						wp_kses( /* translators: %s - Google G Suite Admin area URL. */
							__( 'Make sure that Gmail app in your G Suite is actually enabled. You can check that in Apps list in <a href="%s" target="_blank" rel="noopener noreferrer">G Suite Admin</a> area.', 'wp-mail-smtp' ),
							[
								'a' => [
									'href'   => [],
									'rel'    => [],
									'target' => [],
								],
							]
						),
						'https://admin.google.com'
					),
					sprintf(
						wp_kses( /* translators: %s - Google Developers Console URL. */
							__( 'Make sure that you have Gmail API enabled, and you can do that <a href="%s" target="_blank" rel="noopener noreferrer">here</a>.', 'wp-mail-smtp' ),
							[
								'a' => [
									'href'   => [],
									'rel'    => [],
									'target' => [],
								],
							]
						),
						'https://console.developers.google.com/'
					),
				],
			],
			// [gmail] - 403: Project X is not found and cannot be used for API calls.
			[
				'mailer'      => 'gmail',
				'errors'      => [
					[ '403', 'is not found and cannot be used for API calls' ],
				],
				'description' => [
					'<strong>' . esc_html__( 'Google API Error.', 'wp-mail-smtp' ) . '</strong>',
				],
				'steps'       => [
					esc_html__( 'Make sure that the used Client ID/Secret correspond to a proper project that has Gmail API enabled.', 'wp-mail-smtp' ),
					sprintf(
						wp_kses( /* translators: %s - Gmail documentation URL. */
							esc_html__( 'Please follow our <a href="%s" target="_blank" rel="noopener noreferrer">Gmail tutorial</a> to be sure that all the correct project and data is applied.', 'wp-mail-smtp' ),
							[
								'a' => [
									'href'   => [],
									'rel'    => [],
									'target' => [],
								],
							]
						),
						'https://wpmailsmtp.com/docs/how-to-set-up-the-gmail-mailer-in-wp-mail-smtp/'
					),
				],
			],
			// [gmail] - The OAuth client was disabled.
			[
				'mailer'      => 'gmail',
				'errors'      => [
					[ 'disabled_client', 'The OAuth client was disabled' ],
				],
				'description' => [
					'<strong>' . esc_html__( 'Google API Error.', 'wp-mail-smtp' ) . '</strong>',
					esc_html__( 'You may have added a new API to a project', 'wp-mail-smtp' ),
				],
				'steps'       => [
					esc_html__( 'Make sure that the used Client ID/Secret correspond to a proper project that has Gmail API enabled.', 'wp-mail-smtp' ),
					esc_html__( 'Try to use a separate project for your emails, so the project has only 1 Gmail API in it enabled. You will need to remove the old project and create a new one from scratch.', 'wp-mail-smtp' ),
				],
			],
			// [SMTP.com] - The "channel - not found" issue.
			[
				'mailer'      => 'smtpcom',
				'errors'      => [
					[ 'channel - not found' ],
				],
				'description' => [
					'<strong>' . esc_html__( 'SMTP.com API Error.', 'wp-mail-smtp' ) . '</strong>',
					esc_html__( 'Your Sender Name option is incorrect.', 'wp-mail-smtp' ),
				],
				'steps'       => [
					esc_html__( 'Please make sure you entered an accurate Sender Name in WP Mail SMTP plugin settings.', 'wp-mail-smtp' ),
				],
			],
			// [gmail] - GuzzleHttp requires cURL, the allow_url_fopen ini setting, or a custom HTTP handler.
			[
				'mailer'      => 'gmail',
				'errors'      => [
					[ 'GuzzleHttp requires cURL, the allow_url_fopen ini setting, or a custom HTTP handler' ],
				],
				'description' => [
					'<strong>' . esc_html__( 'GuzzleHttp requirements.', 'wp-mail-smtp' ) . '</strong>',
					esc_html__( 'GuzzleHttp requires cURL, the allow_url_fopen ini setting, or a custom HTTP handler.', 'wp-mail-smtp' ),
				],
				'steps'       => [
					esc_html__( 'Edit your php.ini file on your hosting server.', 'wp-mail-smtp' ),
					esc_html__( '(Recommended) Enable PHP extension: cURL, by adding "extension=curl" to the php.ini file (without the quotation marks) OR', 'wp-mail-smtp' ),
					esc_html__( '(If cURL can\'t be enabled on your hosting server) Enable PHP setting: allow_url_fopen, by adding "allow_url_fopen = On" to the php.ini file (without the quotation marks)', 'wp-mail-smtp' ),
					esc_html__( 'If you don\'t know how to do the above we strongly suggest contacting your hosting support and provide them the "full Error Log for debugging" below and these steps. They should be able to fix this issue for you.', 'wp-mail-smtp' ),
				],
			],
		];

		/**
		 * [any] - PHP 7.4.x and PCRE library issues.
		 *
		 * @see https://wordpress.org/support/topic/cant-send-emails-using-php-7-4/
		 */
		if (
			version_compare( phpversion(), '7.4', '>=' ) &&
			defined( 'PCRE_VERSION' ) &&
			version_compare( PCRE_VERSION, '10.0', '>' ) &&
			version_compare( PCRE_VERSION, '10.32', '<=' )
		) {
			$details[] = [
				'mailer'      => 'any',
				'errors'      => [
					[ 'Invalid address:  (setFrom)' ],
				],
				'description' => [
					'<strong>' . esc_html__( 'PCRE library issue', 'wp-mail-smtp' ) . '</strong>',
					esc_html__( 'It looks like your server is running PHP version 7.4.x with an outdated PCRE library (libpcre2) that has a known issue with email address validation.', 'wp-mail-smtp' ),
					esc_html__( 'There is a known issue with PHP version 7.4.x, when using libpcre2 library version lower than 10.33.', 'wp-mail-smtp' ),
				],
				'steps'       => [
					esc_html__( 'Contact your web hosting provider and inform them you are having issues with libpcre2 library on PHP 7.4.', 'wp-mail-smtp' ),
					esc_html__( 'They should be able to resolve this issue for you.', 'wp-mail-smtp' ),
					esc_html__( 'For a quick fix, until your web hosting resolves this, you can downgrade to PHP version 7.3 on your server.', 'wp-mail-smtp' ),
				],
			];
		}

		// Error detection logic.
		foreach ( $details as $data ) {

			// Check for appropriate mailer.
			if ( 'any' !== $data['mailer'] && $this->debug['mailer'] !== $data['mailer'] ) {
				continue;
			}

			$match = false;

			// Attempt to detect errors.
			foreach ( $data['errors'] as $error_group ) {
				foreach ( $error_group as $error_code => $error_message ) {
					$match = ( false !== strpos( $this->debug['error_log'], $error_message ) || false !== strpos( $this->debug['error_log'], $error_message ) );
					if ( ! $match ) {
						break;
					}
				}
				if ( $match ) {
					break;
				}
			}

			if ( $match ) {
				return $data;
			}
		}

		// Return defaults.
		return [
			'description' => [
				'<strong>' . esc_html__( 'An issue was detected.', 'wp-mail-smtp' ) . '</strong>',
				esc_html__( 'This means your test email was unable to be sent.', 'wp-mail-smtp' ),
				esc_html__( 'Typically this error is returned for one of the following reasons:', 'wp-mail-smtp' ),
				'- ' . esc_html__( 'Plugin settings are incorrect (wrong SMTP settings, invalid Mailer configuration, etc).', 'wp-mail-smtp' ) . '<br>' .
				'- ' . esc_html__( 'Your web server is blocking the connection.', 'wp-mail-smtp' ) . '<br>' .
				'- ' . esc_html__( 'Your host is rejecting the connection.', 'wp-mail-smtp' ),
			],
			'steps'       => [
				esc_html__( 'Triple check the plugin settings, consider reconfiguring to make sure everything is correct (eg bad copy and paste).', 'wp-mail-smtp' ),
				wp_kses(
					__( 'Contact your web hosting provider and ask them to verify your server can make outside connections. Additionally, ask them if a firewall or security policy may be preventing the connection - many shared hosts block certain ports.<br><strong>Note: this is the most common cause of this issue.</strong>', 'wp-mail-smtp' ),
					[
						'strong' => [],
						'br'     => [],
					]
				),
				esc_html__( 'Try using a different mailer.', 'wp-mail-smtp' ),
			],
		];
	}

	/**
	 * Displays all the various error and debug details.
	 *
	 * @since 1.3.0
	 */
	protected function display_debug_details() {

		if ( empty( $this->debug ) ) {
			return;
		}

		$debug = $this->get_debug_details();
		?>
		<div id="message" class="notice-error notice-inline">
			<p><strong><?php esc_html_e( 'There was a problem while sending the test email.', 'wp-mail-smtp' ); ?></strong></p>
		</div>

		<div id="wp-mail-smtp-debug">
			<?php
			foreach ( $debug['description'] as $description ) {
				echo '<p>' . $description . '</p>';
			}
			?>

			<h2><?php esc_html_e( 'Recommended next steps:', 'wp-mail-smtp' ); ?></h2>

			<ol>
				<?php
				foreach ( $debug['steps'] as $step ) {
					echo '<li>' . $step . '</li>';
				}
				?>
			</ol>

			<h2><?php esc_html_e( 'Need support?', 'wp-mail-smtp' ); ?></h2>

			<?php if ( wp_mail_smtp()->is_pro() ) : ?>

				<p>
					<?php
					printf(
						wp_kses( /* translators: %s - WPMailSMTP.com account area link. */
							__( 'As a WP Mail SMTP Pro user you have access to WP Mail SMTP priority support. Please log in to your WPMailSMTP.com account and <a href="%s" target="_blank" rel="noopener noreferrer">submit a support ticket</a>.', 'wp-mail-smtp' ),
							array(
								'a' => array(
									'href'   => array(),
									'rel'    => array(),
									'target' => array(),
								),
							)
						),
						'https://wpmailsmtp.com/account/support/'
					);
					?>
				</p>

			<?php else : ?>

				<p>
					<?php esc_html_e( 'WP Mail SMTP is a free plugin, and the team behind WPForms maintains it to give back to the WordPress community.', 'wp-mail-smtp' ); ?>
				</p>

				<p>
					<?php
					printf(
						wp_kses( /* translators: %s - WPMailSMTP.com URL. */
							__( 'To access our world class support, please <a href="%s" target="_blank" rel="noopener noreferrer">upgrade to WP Mail SMTP Pro</a>. Along with getting expert support, you will also get Notification controls, Email Logging, and integrations for Amazon SES, Office 365, and Outlook.com.', 'wp-mail-smtp' ),
							array(
								'a' => array(
									'href'   => array(),
									'target' => array(),
									'rel'    => array(),
								),
							)
						),
						esc_url( wp_mail_smtp()->get_upgrade_link( 'email-test-fail' ) )
					)
					?>
				</p>

				<p>
					<?php esc_html_e( 'Additionally, you can take advantage of our White Glove Setup. Sit back and relax while we handle everything for you! If you simply don\'t have time or maybe you feel a bit in over your head - we got you covered.', 'wp-mail-smtp' ); ?>
				</p>

				<p>
					<?php
					echo wp_kses(
						__( 'As a valued WP Mail SMTP user, you will get <span class="price-off">$50 off regular pricing</span>, automatically applied at checkout!', 'wp-mail-smtp' ),
						array(
							'span' => array(
								'class' => array(),
							),
						)
					);
					?>
				</p>

				<p>
					<?php
					printf(
						wp_kses( /* translators: %1$s - WP Mail SMTP support policy URL, %2$s - WP Mail SMTP support forum URL, %3$s - WPMailSMTP.com URL. */
							__( 'Alternatively, we also offer <a href="%1$s" target="_blank" rel="noopener noreferrer">limited support</a> on the WordPress.org support forums. You can <a href="%2$s" target="_blank" rel="noopener noreferrer">create a support thread</a> there, but please understand that free support is not guaranteed and is limited to simple issues. If you have an urgent or complex issue, then please consider <a href="%3$s" target="_blank" rel="noopener noreferrer">upgrading to WP Mail SMTP Pro</a> to access our priority support ticket system.', 'wp-mail-smtp' ),
							array(
								'a' => array(
									'href'   => array(),
									'rel'    => array(),
									'target' => array(),
								),
							)
						),
						'https://wordpress.org/support/topic/wp-mail-smtp-support-policy/',
						'https://wordpress.org/support/plugin/wp-mail-smtp/',
						esc_url( wp_mail_smtp()->get_upgrade_link( 'email-test-fail' ) )
					);
					?>
				</p>

			<?php endif; ?>

			<p>
				<a href="#" class="error-log-toggle">
					<span class="dashicons dashicons-arrow-right-alt2"></span>
					<strong><?php esc_html_e( 'Click here to view the full Error Log for debugging', 'wp-mail-smtp' ); ?></strong>
				</a>
			</p>

			<div class="error-log">
				<?php echo $this->debug['error_log']; ?>
			</div>

			<p class="error-log-note">
				<em><?php esc_html_e( 'Please copy only the content of the error debug message above, identified with an orange left border, into the support forum topic if you experience any issues.', 'wp-mail-smtp' ); ?></em>
			</p>
		</div>
		<?php
	}
}
