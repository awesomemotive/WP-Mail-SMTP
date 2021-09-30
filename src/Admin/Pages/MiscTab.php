<?php

namespace WPMailSMTP\Admin\Pages;

use WPMailSMTP\Admin\Area;
use WPMailSMTP\Admin\PageAbstract;
use WPMailSMTP\Options;
use WPMailSMTP\UsageTracking\UsageTracking;
use WPMailSMTP\Reports\Emails\Summary as SummaryReportEmail;
use WPMailSMTP\Tasks\Reports\SummaryEmailTask as SummaryReportEmailTask;
use WPMailSMTP\WP;

/**
 * Class MiscTab is part of Area, displays different plugin-related settings of the plugin (not related to emails).
 *
 * @since 1.0.0
 */
class MiscTab extends PageAbstract {

	/**
	 * Slug of a tab.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $slug = 'misc';

	/**
	 * Link label of a tab.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_label() {
		return esc_html__( 'Misc', 'wp-mail-smtp' );
	}

	/**
	 * Title of a tab.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_title() {
		return $this->get_label();
	}

	/**
	 * Output HTML of the misc settings.
	 *
	 * @since 1.0.0
	 */
	public function display() {

		$options = new Options();
		?>

		<form method="POST" action="">
			<?php $this->wp_nonce_field(); ?>

			<!-- Section Title -->
			<div class="wp-mail-smtp-setting-row wp-mail-smtp-setting-row-content wp-mail-smtp-clear section-heading no-desc" id="wp-mail-smtp-setting-row-email-heading">
				<div class="wp-mail-smtp-setting-field">
					<h2><?php echo $this->get_title(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h2>
				</div>
			</div>

			<!-- Do not send -->
			<div id="wp-mail-smtp-setting-row-do_not_send" class="wp-mail-smtp-setting-row wp-mail-smtp-setting-row-checkbox wp-mail-smtp-clear">
				<div class="wp-mail-smtp-setting-label">
					<label for="wp-mail-smtp-setting-do_not_send">
						<?php esc_html_e( 'Do Not Send', 'wp-mail-smtp' ); ?>
					</label>
				</div>
				<div class="wp-mail-smtp-setting-field">
					<input name="wp-mail-smtp[general][do_not_send]" type="checkbox" value="true" id="wp-mail-smtp-setting-do_not_send"
						<?php echo $options->is_const_defined( 'general', 'do_not_send' ) ? 'disabled' : ''; ?>
						<?php checked( true, $options->get( 'general', 'do_not_send' ) ); ?>
					>
					<label for="wp-mail-smtp-setting-do_not_send">
						<?php esc_html_e( 'Stop sending all emails.', 'wp-mail-smtp' ); ?>
					</label>
					<p class="desc">
						<?php
						printf(
							wp_kses(
								__( 'Some plugins, like BuddyPress and Events Manager, are using their own email delivery solutions. By default, this option does not block their emails, as those plugins do not use default <code>wp_mail()</code> function to send emails.', 'wp-mail-smtp' ),
								[
									'code' => [],
								]
							)
						);
						?>
						<br>
						<?php esc_html_e( 'You will need to consult with their documentation to switch them to use default WordPress email delivery.', 'wp-mail-smtp' ); ?>
						<br>
						<?php esc_html_e( 'Test emails are allowed to be sent, regardless of this option.', 'wp-mail-smtp' ); ?>
						<br>
						<?php
						if ( $options->is_const_defined( 'general', 'do_not_send' ) ) {
							echo $options->get_const_set_message( 'WPMS_DO_NOT_SEND' ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						} else {
							printf(
								wp_kses( /* translators: %s - The URL to the constants support article. */
									__( 'Please read this <a href="%s" target="_blank" rel="noopener noreferrer">support article</a> if you want to enable this option using constants.', 'wp-mail-smtp' ),
									[
										'a' => [
											'href'   => [],
											'target' => [],
											'rel'    => [],
										],
									]
								),
								'https://wpmailsmtp.com/docs/how-to-secure-smtp-settings-by-using-constants/'
							);
						}
						?>
					</p>
				</div>
			</div>

			<!-- Hide Announcements -->
			<div id="wp-mail-smtp-setting-row-am_notifications_hidden" class="wp-mail-smtp-setting-row wp-mail-smtp-setting-row-checkbox wp-mail-smtp-clear">
				<div class="wp-mail-smtp-setting-label">
					<label for="wp-mail-smtp-setting-am_notifications_hidden">
						<?php esc_html_e( 'Hide Announcements', 'wp-mail-smtp' ); ?>
					</label>
				</div>
				<div class="wp-mail-smtp-setting-field">
					<input name="wp-mail-smtp[general][am_notifications_hidden]" type="checkbox"
						value="true" <?php checked( true, $options->get( 'general', 'am_notifications_hidden' ) ); ?>
						id="wp-mail-smtp-setting-am_notifications_hidden"
					>
					<label for="wp-mail-smtp-setting-am_notifications_hidden">
						<?php esc_html_e( 'Hide plugin announcements and update details.', 'wp-mail-smtp' ); ?>
					</label>
				</div>
			</div>

			<!-- Hide Email Delivery Errors -->
			<div id="wp-mail-smtp-setting-row-email_delivery_errors_hidden"
				class="wp-mail-smtp-setting-row wp-mail-smtp-setting-row-checkbox wp-mail-smtp-clear">
				<div class="wp-mail-smtp-setting-label">
					<label for="wp-mail-smtp-setting-email_delivery_errors_hidden">
						<?php esc_html_e( 'Hide Email Delivery Errors', 'wp-mail-smtp' ); ?>
					</label>
				</div>
				<div class="wp-mail-smtp-setting-field">
					<?php
					$is_hard_disabled = has_filter( 'wp_mail_smtp_admin_is_error_delivery_notice_enabled' ) && ! wp_mail_smtp()->get_admin()->is_error_delivery_notice_enabled();
					?>
					<?php if ( $is_hard_disabled ) : ?>
						<input type="checkbox" disabled checked id="wp-mail-smtp-setting-email_delivery_errors_hidden">
					<?php else : ?>
						<input name="wp-mail-smtp[general][email_delivery_errors_hidden]" type="checkbox" value="true"
							<?php checked( true, $options->get( 'general', 'email_delivery_errors_hidden' ) ); ?>
							id="wp-mail-smtp-setting-email_delivery_errors_hidden">
					<?php endif; ?>

					<label for="wp-mail-smtp-setting-email_delivery_errors_hidden">
						<?php esc_html_e( 'Hide warnings alerting of email delivery errors.', 'wp-mail-smtp' ); ?>
					</label>

					<?php if ( $is_hard_disabled ) : ?>
						<p class="desc">
							<?php
							printf( /* translators: %s - filter that was used to disabled. */
								esc_html__( 'Email Delivery Errors were disabled using a %s filter.', 'wp-mail-smtp' ),
								'<code>wp_mail_smtp_admin_is_error_delivery_notice_enabled</code>'
							);
							?>
						</p>
					<?php else : ?>
						<p class="desc">
							<?php
							echo wp_kses(
								__( '<strong>This is not recommended</strong> and should only be done for staging or development sites.', 'wp-mail-smtp' ),
								[
									'strong' => [],
								]
							);
							?>
						</p>
					<?php endif; ?>
				</div>
			</div>

			<!-- Hide Dashboard Widget -->
			<div id="wp-mail-smtp-setting-row-dashboard_widget_hidden" class="wp-mail-smtp-setting-row wp-mail-smtp-setting-row-checkbox wp-mail-smtp-clear">
				<div class="wp-mail-smtp-setting-label">
					<label for="wp-mail-smtp-setting-dashboard_widget_hidden">
						<?php esc_html_e( 'Hide Dashboard Widget', 'wp-mail-smtp' ); ?>
					</label>
				</div>
				<div class="wp-mail-smtp-setting-field">
					<input name="wp-mail-smtp[general][dashboard_widget_hidden]" type="checkbox"
						   value="true" <?php checked( true, $options->get( 'general', 'dashboard_widget_hidden' ) ); ?>
						   id="wp-mail-smtp-setting-dashboard_widget_hidden"
					>
					<label for="wp-mail-smtp-setting-dashboard_widget_hidden">
						<?php esc_html_e( 'Hide the WP Mail SMTP Dashboard Widget.', 'wp-mail-smtp' ); ?>
					</label>
				</div>
			</div>

			<!-- Uninstall -->
			<div id="wp-mail-smtp-setting-row-uninstall" class="wp-mail-smtp-setting-row wp-mail-smtp-setting-row-checkbox wp-mail-smtp-clear">
				<div class="wp-mail-smtp-setting-label">
					<label for="wp-mail-smtp-setting-uninstall">
						<?php esc_html_e( 'Uninstall WP Mail SMTP', 'wp-mail-smtp' ); ?>
					</label>
				</div>
				<div class="wp-mail-smtp-setting-field">
					<input name="wp-mail-smtp[general][uninstall]" type="checkbox"
						value="true" <?php checked( true, $options->get( 'general', 'uninstall' ) ); ?>
						id="wp-mail-smtp-setting-uninstall">
					<label for="wp-mail-smtp-setting-uninstall">
						<?php esc_html_e( 'Remove ALL WP Mail SMTP data upon plugin deletion. All settings will be unrecoverable.', 'wp-mail-smtp' ); ?>
					</label>
				</div>
			</div>

			<?php if ( apply_filters( 'wp_mail_smtp_admin_pages_misc_tab_show_usage_tracking_setting', true ) ) : ?>
				<!-- Usage Tracking -->
				<div id="wp-mail-smtp-setting-row-usage-tracking" class="wp-mail-smtp-setting-row wp-mail-smtp-setting-row-checkbox wp-mail-smtp-clear">
					<div class="wp-mail-smtp-setting-label">
						<label for="wp-mail-smtp-setting-usage-tracking">
							<?php esc_html_e( 'Allow Usage Tracking', 'wp-mail-smtp' ); ?>
						</label>
					</div>
					<div class="wp-mail-smtp-setting-field">
						<input name="wp-mail-smtp[general][<?php echo esc_attr( UsageTracking::SETTINGS_SLUG ); ?>]" type="checkbox"
							value="true" <?php checked( true, $options->get( 'general', UsageTracking::SETTINGS_SLUG ) ); ?>
							id="wp-mail-smtp-setting-usage-tracking">
						<label for="wp-mail-smtp-setting-usage-tracking">
							<?php esc_html_e( 'By allowing us to track usage data we can better help you because we know with which WordPress configurations, themes and plugins we should test.', 'wp-mail-smtp' ); ?>
						</label>
					</div>
				</div>
			<?php endif; ?>

			<!-- Summary Report Email -->
			<div id="wp-mail-smtp-setting-row-summary-report-email" class="wp-mail-smtp-setting-row wp-mail-smtp-setting-row-checkbox wp-mail-smtp-clear">
				<div class="wp-mail-smtp-setting-label">
					<label for="wp-mail-smtp-setting-summary-report-email">
						<?php esc_html_e( 'Disable Email Summaries', 'wp-mail-smtp' ); ?>
					</label>
				</div>
				<div class="wp-mail-smtp-setting-field">
					<input name="wp-mail-smtp[general][<?php echo esc_attr( SummaryReportEmail::SETTINGS_SLUG ); ?>]" type="checkbox" id="wp-mail-smtp-setting-summary-report-email"
						   value="true" <?php checked( true, SummaryReportEmail::is_disabled() ); ?>
						<?php disabled( $options->is_const_defined( 'general', SummaryReportEmail::SETTINGS_SLUG ) || ( wp_mail_smtp()->is_pro() && empty( Options::init()->get( 'logs', 'enabled' ) ) ) ); ?>>
					<label for="wp-mail-smtp-setting-summary-report-email">
						<?php esc_html_e( 'Disable Email Summaries weekly delivery.', 'wp-mail-smtp' ); ?>
					</label>
					<p class="desc">
						<?php
						if ( wp_mail_smtp()->is_pro() && empty( Options::init()->get( 'logs', 'enabled' ) ) ) {
							echo wp_kses(
								sprintf( /* translators: %s - Email Log settings url. */
									__( 'Please enable <a href="%s">Email Logging</a> first, before this setting can be configured.', 'wp-mail-smtp' ),
									esc_url( wp_mail_smtp()->get_admin()->get_admin_page_url( Area::SLUG . '&tab=logs' ) )
								),
								[
									'a' => [
										'href' => [],
									],
								]
							);
						} else {
							printf(
								'<a href="%1$s" target="_blank">%2$s</a>',
								esc_url( SummaryReportEmail::get_preview_link() ),
								esc_html__( 'View Email Summary Example', 'wp-mail-smtp' )
							);
						}

						if ( $options->is_const_defined( 'general', SummaryReportEmail::SETTINGS_SLUG ) ) {
							echo '<br>' . $options->get_const_set_message( 'WPMS_SUMMARY_REPORT_EMAIL_DISABLED' ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						}
						?>
					</p>
				</div>
			</div>

			<?php $this->display_save_btn(); ?>

		</form>

		<?php
	}

	/**
	 * Process tab form submission ($_POST).
	 *
	 * @since 1.0.0
	 * @since 2.2.0 Fixed checkbox saving and use the correct merge to prevent breaking other 'general' checkboxes.
	 *
	 * @param array $data Tab data specific for the plugin ($_POST).
	 */
	public function process_post( $data ) { // phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh

		$this->check_admin_referer();

		$options = new Options();

		// Unchecked checkboxes doesn't exist in $_POST, so we need to ensure we actually have them in data to save.
		if ( empty( $data['general']['do_not_send'] ) ) {
			$data['general']['do_not_send'] = false;
		}
		if ( empty( $data['general']['am_notifications_hidden'] ) ) {
			$data['general']['am_notifications_hidden'] = false;
		}
		if ( empty( $data['general']['email_delivery_errors_hidden'] ) ) {
			$data['general']['email_delivery_errors_hidden'] = false;
		}
		if ( empty( $data['general']['dashboard_widget_hidden'] ) ) {
			$data['general']['dashboard_widget_hidden'] = false;
		}
		if ( empty( $data['general']['uninstall'] ) ) {
			$data['general']['uninstall'] = false;
		}
		if ( empty( $data['general'][ UsageTracking::SETTINGS_SLUG ] ) ) {
			$data['general'][ UsageTracking::SETTINGS_SLUG ] = false;
		}
		if ( empty( $data['general'][ SummaryReportEmail::SETTINGS_SLUG ] ) ) {
			$data['general'][ SummaryReportEmail::SETTINGS_SLUG ] = false;
		}

		$is_summary_report_email_opt_changed = $options->is_option_changed(
			$options->parse_boolean( $data['general'][ SummaryReportEmail::SETTINGS_SLUG ] ),
			'general',
			SummaryReportEmail::SETTINGS_SLUG
		);

		// If this option was changed, cancel summary report email task.
		if ( $is_summary_report_email_opt_changed ) {
			( new SummaryReportEmailTask() )->cancel();
		}

		// All the sanitization is done there.
		$options->set( $data, false, false );

		WP::add_admin_notice(
			esc_html__( 'Settings were successfully saved.', 'wp-mail-smtp' ),
			WP::ADMIN_NOTICE_SUCCESS
		);
	}
}
