<?php

namespace WPMailSMTP\Admin\Pages;

use WPMailSMTP\Admin\PageAbstract;
use WPMailSMTP\Options;
use WPMailSMTP\WP;

/**
 * Class MiscTab is part of Area, displays different plugin-related settings of the plugin (not related to emails).
 *
 * @since 1.0.0
 */
class MiscTab extends PageAbstract {
	/**
	 * @var string Slug of a tab.
	 */
	protected $slug = 'misc';

	/**
	 * @inheritdoc
	 */
	public function get_label() {
		return esc_html__( 'Misc', 'wp-mail-smtp' );
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

		$options = new Options();
		?>

		<form method="POST" action="">
			<?php $this->wp_nonce_field(); ?>

			<!-- Section Title -->
			<div class="wp-mail-smtp-setting-row wp-mail-smtp-setting-row-content wp-mail-smtp-clear section-heading no-desc" id="wp-mail-smtp-setting-row-email-heading">
				<div class="wp-mail-smtp-setting-field">
					<h2><?php echo $this->get_title(); ?></h2>
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
					<input name="wp-mail-smtp[general][do_not_send]" type="checkbox"
						value="true" <?php checked( true, $options->get( 'general', 'do_not_send' ) ); ?>
						id="wp-mail-smtp-setting-do_not_send"
					>
					<label for="wp-mail-smtp-setting-do_not_send">
						<?php esc_html_e( 'Check this if you would like to stop sending all emails.', 'wp-mail-smtp' ); ?>
					</label>
					<p class="desc">
						<?php
						printf(
							wp_kses(
								__( 'Some plugins, like BuddyPress and Events Manager, are using own email delivery solutions. By default, this option does not block their emails, as those plugins do not use default <code>wp_mail()</code> function to send emails.', 'wp-mail-smtp' ),
								array(
									'code' => array(),
								)
							)
						);
						?>
						<br>
						<?php esc_html_e( 'You will need to consult with their documentation to switch them to use default WordPress email delivery.', 'wp-mail-smtp' ); ?>
						<br>
						<?php esc_html_e( 'Test emails are allowed to be sent, regardless of this option.', 'wp-mail-smtp' ); ?>
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
						<?php esc_html_e( 'Check this if you would like to hide plugin announcements and update details.', 'wp-mail-smtp' ); ?>
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
						<?php esc_html_e( 'Check this if you would like to hide warnings alerting of email delivery errors.', 'wp-mail-smtp' ); ?>
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
								array(
									'strong' => true,
								)
							);
							?>
						</p>
					<?php endif; ?>
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
						<?php esc_html_e( 'Check this if you would like to remove ALL WP Mail SMTP data upon plugin deletion. All settings will be unrecoverable.', 'wp-mail-smtp' ); ?>
					</label>
				</div>
			</div>

			<?php $this->display_save_btn(); ?>

		</form>

		<?php
	}

	/**
	 * @inheritdoc
	 */
	public function process_post( $data ) {

		$this->check_admin_referer();

		$options = new Options();

		// Unchecked checkboxes doesn't exist in $_POST, so we need to ensure we actually have them in data to save.
		if ( empty( $data['general']['am_notifications_hidden'] ) ) {
			$data['general']['am_notifications_hidden'] = false;
		}
		if ( empty( $data['general']['uninstall'] ) ) {
			$data['general']['uninstall'] = false;
		}

		$to_save = array_merge( $options->get_all(), $data );

		// All the sanitization is done there.
		$options->set( $to_save );

		WP::add_admin_notice(
			esc_html__( 'Settings were successfully saved.', 'wp-mail-smtp' ),
			WP::ADMIN_NOTICE_SUCCESS
		);
	}
}
