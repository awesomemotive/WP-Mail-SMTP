<?php

namespace WPMailSMTP\Admin\Pages;

use WPMailSMTP\Admin\PageAbstract;

/**
 * Class AlertsTab is a placeholder for Pro alerts feature.
 * Displays product education.
 *
 * @since 3.5.0
 */
class AlertsTab extends PageAbstract {

	/**
	 * Part of the slug of a tab.
	 *
	 * @since 3.5.0
	 *
	 * @var string
	 */
	protected $slug = 'alerts';

	/**
	 * Tab priority.
	 *
	 * @since 3.5.0
	 *
	 * @var int
	 */
	protected $priority = 20;

	/**
	 * Link label of a tab.
	 *
	 * @since 3.5.0
	 *
	 * @return string
	 */
	public function get_label() {

		return esc_html__( 'Alerts', 'wp-mail-smtp' );
	}

	/**
	 * Title of a tab.
	 *
	 * @since 3.5.0
	 *
	 * @return string
	 */
	public function get_title() {

		return $this->get_label();
	}

	/**
	 * Output HTML of the alerts settings preview.
	 *
	 * @since 3.5.0
	 */
	public function display() {

		$upgrade_link_url = wp_mail_smtp()->get_upgrade_link(
			[
				'medium'  => 'Alerts Settings',
				'content' => 'Upgrade to WP Mail SMTP Pro Link',
			]
		);

		$upgrade_button_url = wp_mail_smtp()->get_upgrade_link(
			[
				'medium'  => 'Alerts Settings',
				'content' => 'Upgrade to WP Mail SMTP Pro Button',
			]
		);

		?>
		<div class="wp-mail-smtp-product-education">
			<div class="wp-mail-smtp-product-education__row">
				<h4 class="wp-mail-smtp-product-education__heading">
					<?php esc_html_e( 'Alerts', 'wp-mail-smtp' ); ?>
				</h4>
				<p class="wp-mail-smtp-product-education__description">
					<?php
					echo wp_kses(
						sprintf( /* translators: %s - WPMailSMTP.com Upgrade page URL. */
							__( 'Configure at least one of these integrations to receive notifications when email fails to send from your site. Alert notifications will contain the following important data: email subject, email Send To address, the error message, and helpful links to help you fix the issue. <a href="%s" target="_blank" rel="noopener noreferrer">Upgrade to WP Mail SMTP Pro!</a>', 'wp-mail-smtp' ),
							esc_url( $upgrade_link_url )
						),
						[
							'a' => [
								'href'   => [],
								'rel'    => [],
								'target' => [],
							],
						]
					);
					?>
				</p>
			</div>

			<div class="wp-mail-smtp-product-education__row wp-mail-smtp-product-education__row--inactive">
				<div class="wp-mail-smtp-setting-row wp-mail-smtp-setting-row-alert">
					<div class="wp-mail-smtp-setting-row wp-mail-smtp-setting-row-content section-heading">
						<div class="wp-mail-smtp-setting-field">
							<h3><?php esc_html_e( 'Email', 'wp-mail-smtp' ); ?></h3>
							<p class="desc"><?php esc_html_e( 'Enter the email addresses (3 max) you’d like to use to receive alerts when email sending fails. Read our documentation on setting up email alerts.', 'wp-mail-smtp' ); ?></p>
						</div>
					</div>
					<div class="wp-mail-smtp-setting-row wp-mail-smtp-setting-row-checkbox-toggle">
						<div class="wp-mail-smtp-setting-label">
							<label><?php esc_html_e( 'Email Alerts', 'wp-mail-smtp' ); ?></label>
						</div>
						<div class="wp-mail-smtp-setting-field">
							<label>
								<span class="wp-mail-smtp-setting-toggle-switch"></span>
								<span class="wp-mail-smtp-setting-toggle-checked-label"><?php esc_html_e( 'On', 'wp-mail-smtp' ); ?></span>
								<span class="wp-mail-smtp-setting-toggle-unchecked-label"><?php esc_html_e( 'Off', 'wp-mail-smtp' ); ?></span>
							</label>
						</div>
					</div>
					<div class="wp-mail-smtp-setting-row wp-mail-smtp-setting-row-alert-options">
						<div class="wp-mail-smtp-setting-row wp-mail-smtp-setting-row-alert-connection-options">
							<div class="wp-mail-smtp-setting-row wp-mail-smtp-setting-row-text">
								<div class="wp-mail-smtp-setting-label">
									<label><?php esc_html_e( 'Send To', 'wp-mail-smtp' ); ?></label>
								</div>
								<div class="wp-mail-smtp-setting-field"><input type="text"></div>
							</div>
						</div>
					</div>
				</div>

				<div class="wp-mail-smtp-setting-row wp-mail-smtp-setting-row-alert">
					<div class="wp-mail-smtp-setting-row wp-mail-smtp-setting-row-content section-heading">
						<div class="wp-mail-smtp-setting-field">
							<h3><?php esc_html_e( 'Slack', 'wp-mail-smtp' ); ?></h3>
							<p class="desc"><?php esc_html_e( 'Paste in the Slack webhook URL you’d like to use to receive alerts when email sending fails. Read our documentation on setting up Slack alerts.', 'wp-mail-smtp' ); ?></p>
						</div>
					</div>
					<div class="wp-mail-smtp-setting-row wp-mail-smtp-setting-row-checkbox-toggle">
						<div class="wp-mail-smtp-setting-label">
							<label><?php esc_html_e( 'Slack Alerts', 'wp-mail-smtp' ); ?></label>
						</div>
						<div class="wp-mail-smtp-setting-field">
							<label>
								<span class="wp-mail-smtp-setting-toggle-switch"></span>
								<span class="wp-mail-smtp-setting-toggle-checked-label"><?php esc_html_e( 'On', 'wp-mail-smtp' ); ?></span>
								<span class="wp-mail-smtp-setting-toggle-unchecked-label"><?php esc_html_e( 'Off', 'wp-mail-smtp' ); ?></span>
							</label>
						</div>
					</div>
					<div class="wp-mail-smtp-setting-row wp-mail-smtp-setting-row-alert-options">
						<div class="wp-mail-smtp-setting-row wp-mail-smtp-setting-row-alert-connection-options">
							<div class="wp-mail-smtp-setting-row wp-mail-smtp-setting-row-text">
								<div class="wp-mail-smtp-setting-label">
									<label><?php esc_html_e( 'Webhook URL', 'wp-mail-smtp' ); ?></label>
								</div>
								<div class="wp-mail-smtp-setting-field"><input type="text"></div>
							</div>
						</div>
					</div>
				</div>

				<div class="wp-mail-smtp-setting-row wp-mail-smtp-setting-row-alert">
					<div class="wp-mail-smtp-setting-row wp-mail-smtp-setting-row-content section-heading">
						<div class="wp-mail-smtp-setting-field">
							<h3><?php esc_html_e( 'SMS via Twilio', 'wp-mail-smtp' ); ?></h3>
							<p class="desc"><?php esc_html_e( 'To receive SMS alerts, you’ll need a Twilio account. Read our documentation to learn how to set up Twilio SMS, then enter your connection details below.', 'wp-mail-smtp' ); ?></p>
						</div>
					</div>
					<div class="wp-mail-smtp-setting-row wp-mail-smtp-setting-row-checkbox-toggle">
						<div class="wp-mail-smtp-setting-label">
							<label><?php esc_html_e( 'SMS via Twilio Alerts', 'wp-mail-smtp' ); ?></label>
						</div>
						<div class="wp-mail-smtp-setting-field">
							<label>
								<span class="wp-mail-smtp-setting-toggle-switch"></span>
								<span class="wp-mail-smtp-setting-toggle-checked-label"><?php esc_html_e( 'On', 'wp-mail-smtp' ); ?></span>
								<span class="wp-mail-smtp-setting-toggle-unchecked-label"><?php esc_html_e( 'Off', 'wp-mail-smtp' ); ?></span>
							</label>
						</div>
					</div>
					<div class="wp-mail-smtp-setting-row wp-mail-smtp-setting-row-alert-options">
						<div class="wp-mail-smtp-setting-row wp-mail-smtp-setting-row-alert-connection-options">
							<div class="wp-mail-smtp-setting-row wp-mail-smtp-setting-row-text">
								<div class="wp-mail-smtp-setting-label">
									<label><?php esc_html_e( 'Twilio Account ID', 'wp-mail-smtp' ); ?></label>
								</div>
								<div class="wp-mail-smtp-setting-field"><input type="text"></div>
							</div>
							<div class="wp-mail-smtp-setting-row wp-mail-smtp-setting-row-text">
								<div class="wp-mail-smtp-setting-label">
									<label><?php esc_html_e( 'Twilio Auth Token', 'wp-mail-smtp' ); ?></label>
								</div>
								<div class="wp-mail-smtp-setting-field"><input type="text"></div>
							</div>
							<div class="wp-mail-smtp-setting-row wp-mail-smtp-setting-row-text">
								<div class="wp-mail-smtp-setting-label">
									<label><?php esc_html_e( 'From Phone Number', 'wp-mail-smtp' ); ?></label>
								</div>
								<div class="wp-mail-smtp-setting-field"><input type="text"></div>
							</div>
							<div class="wp-mail-smtp-setting-row wp-mail-smtp-setting-row-text">
								<div class="wp-mail-smtp-setting-label">
									<label><?php esc_html_e( 'To Phone Number', 'wp-mail-smtp' ); ?></label>
								</div>
								<div class="wp-mail-smtp-setting-field"><input type="text"></div>
							</div>
						</div>
					</div>
				</div>

				<div class="wp-mail-smtp-setting-row wp-mail-smtp-setting-row-alert">
					<div class="wp-mail-smtp-setting-row wp-mail-smtp-setting-row-content section-heading">
						<div class="wp-mail-smtp-setting-field">
							<h3><?php esc_html_e( 'Webhook', 'wp-mail-smtp' ); ?></h3>
							<p class="desc"><?php esc_html_e( 'Paste in the webhook URL you’d like to use to receive alerts when email sending fails. Read our documentation on setting up webhook alerts.', 'wp-mail-smtp' ); ?></p>
						</div>
					</div>
					<div class="wp-mail-smtp-setting-row wp-mail-smtp-setting-row-checkbox-toggle">
						<div class="wp-mail-smtp-setting-label">
							<label><?php esc_html_e( 'Webhook Alerts', 'wp-mail-smtp' ); ?></label>
						</div>
						<div class="wp-mail-smtp-setting-field">
							<label>
								<span class="wp-mail-smtp-setting-toggle-switch"></span>
								<span class="wp-mail-smtp-setting-toggle-checked-label"><?php esc_html_e( 'On', 'wp-mail-smtp' ); ?></span>
								<span class="wp-mail-smtp-setting-toggle-unchecked-label"><?php esc_html_e( 'Off', 'wp-mail-smtp' ); ?></span>
							</label>
						</div>
					</div>
					<div class="wp-mail-smtp-setting-row wp-mail-smtp-setting-row-alert-options">
						<div class="wp-mail-smtp-setting-row wp-mail-smtp-setting-row-alert-connection-options">
							<div class="wp-mail-smtp-setting-row wp-mail-smtp-setting-row-text">
								<div class="wp-mail-smtp-setting-label">
									<label><?php esc_html_e( 'Webhook URL', 'wp-mail-smtp' ); ?></label>
								</div>
								<div class="wp-mail-smtp-setting-field"><input type="text"></div>
							</div>
						</div>
					</div>
				</div>
			</div>

			<a href="<?php echo esc_url( $upgrade_button_url ); ?>" target="_blank" rel="noopener noreferrer" class="wp-mail-smtp-btn wp-mail-smtp-btn-upgrade wp-mail-smtp-btn-orange">
				<?php esc_html_e( 'Upgrade to WP Mail SMTP Pro', 'wp-mail-smtp' ); ?>
			</a>
		</div>
		<?php
	}
}
