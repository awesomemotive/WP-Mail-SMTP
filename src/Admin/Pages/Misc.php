<?php

namespace WPMailSMTP\Admin\Pages;

use WPMailSMTP\Admin\PageAbstract;
use WPMailSMTP\Options;
use WPMailSMTP\WP;

/**
 * Class Misc is part of Area, displays different plugin-related settings of the plugin (not related to emails).
 *
 * @since 1.0.0
 */
class Misc extends PageAbstract {
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

			<!-- General Section Title -->
			<div class="wp-mail-smtp-setting-row wp-mail-smtp-setting-row-content wp-mail-smtp-clear section-heading no-desc" id="wp-mail-smtp-setting-row-email-heading">
				<div class="wp-mail-smtp-setting-field">
					<h2><?php esc_html_e( 'General', 'wp-mail-smtp' ); ?></h2>
				</div>
			</div>

			<!-- Hide Announcements -->
			<div id="wp-mail-smtp-setting-row-am_notifications_hidden" class="wp-mail-smtp-setting-row wp-mail-smtp-setting-row-checkbox wp-mail-smtp-clear">
				<div class="wp-mail-smtp-setting-label">
					<label for="wp-mail-smtp-setting-am_notifications_hidden"><?php esc_html_e( 'Hide Announcements', 'wp-mail-smtp' ); ?></label>
				</div>
				<div class="wp-mail-smtp-setting-field">
					<input name="wp-mail-smtp[general][am_notifications_hidden]" type="checkbox"
						value="true" <?php checked( true, $options->get( 'general', 'am_notifications_hidden' ) ); ?>
						id="wp-mail-smtp-setting-am_notifications_hidden"
					>
					<label for="wp-mail-smtp-setting-am_notifications_hidden"><?php esc_html_e( 'Check this if you would like to hide plugin announcements and update details.', 'wp-mail-smtp' ); ?></label>
				</div>
			</div>

			<!-- Uninstall -->
			<div id="wp-mail-smtp-setting-row-uninstall" class="wp-mail-smtp-setting-row wp-mail-smtp-setting-row-checkbox wp-mail-smtp-clear">
				<div class="wp-mail-smtp-setting-label">
					<label for="wp-mail-smtp-setting-uninstall"><?php esc_html_e( 'Uninstall WP Mail SMTP', 'wp-mail-smtp' ); ?></label>
				</div>
				<div class="wp-mail-smtp-setting-field">
					<input name="wp-mail-smtp[general][uninstall]" type="checkbox"
						value="true" <?php checked( true, $options->get( 'general', 'uninstall' ) ); ?>
						id="wp-mail-smtp-setting-uninstall">
					<label for="wp-mail-smtp-setting-uninstall"><?php esc_html_e( 'Check this if you would like to remove ALL WP Mail SMTP data upon plugin deletion. All settings will be unrecoverable.', 'wp-mail-smtp' ); ?></label>
				</div>
			</div>

			<p class="wp-mail-smtp-submit">
				<button type="submit" class="wp-mail-smtp-btn wp-mail-smtp-btn-md wp-mail-smtp-btn-orange"><?php esc_html_e( 'Save Settings', 'wp-mail-smtp' ); ?></button>
			</p>

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
