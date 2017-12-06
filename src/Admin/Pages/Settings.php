<?php

namespace WPMailSMTP\Admin\Pages;

use WPMailSMTP\Admin\PageAbstract;
use WPMailSMTP\Options;
use WPMailSMTP\WP;

/**
 * Class Settings is part of Area, displays general settings of the plugin.
 *
 * @since 1.0.0
 */
class Settings extends PageAbstract {

	/**
	 * @var string Slug of a tab.
	 */
	protected $slug = 'settings';

	/**
	 * @inheritdoc
	 */
	public function get_label() {
		return esc_html__( 'Settings', 'wp-mail-smtp' );
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
		$mailer  = $options->get( 'mail', 'mailer' );
		?>

		<form method="POST" action="">
			<?php $this->wp_nonce_field(); ?>

			<!-- Mail Section Title -->
			<div class="wp-mail-smtp-setting-row wp-mail-smtp-setting-row-content wp-mail-smtp-clear section-heading no-desc" id="wp-mail-smtp-setting-row-email-heading">
				<span class="wp-mail-smtp-setting-field">
					<h2><?php esc_html_e( 'Mail', 'wp-mail-smtp' ); ?></h2>
				</span>
			</div>

			<!-- From Email -->
			<div id="wp-mail-smtp-setting-row-from_email" class="wp-mail-smtp-setting-row wp-mail-smtp-setting-row-email wp-mail-smtp-clear">
				<span class="wp-mail-smtp-setting-label">
					<label for="wp-mail-smtp-setting-from_email"><?php esc_html_e( 'From Email', 'wp-mail-smtp' ); ?></label>
				</span>
				<span class="wp-mail-smtp-setting-field">
					<input name="wp-mail-smtp[mail][from_email]" type="email"
						value="<?php echo esc_attr( $options->get( 'mail', 'from_email' ) ); ?>"
						<?php echo $options->is_const_defined( 'mail', 'from_email' ) ? 'disabled' : ''; ?>
						id="wp-mail-smtp-setting-from_email" spellcheck="false"
					/>
					<p class="desc">
						<?php esc_html_e( 'You can specify the email address that emails should be sent from.', 'wp-mail-smtp' ); ?><br/>
						<?php
						printf(
							/* translators: %s - default email address. */
							esc_html__( 'If you leave this blank, the default one will be used: %s.', 'wp-mail-smtp' ),
							'<code>' . wp_mail_smtp()->get_processor()->get_default_email() . '</code>'
						);
						?>
					</p>
					<p class="desc">
						<?php esc_html_e( 'Please note if you are sending using an email provider (Gmail, Yahoo, Hotmail, Outlook.com, etc) this setting should be your email address for that account.', 'wp-mail-smtp' ); ?>
					</p>
				</span>
			</div>

			<!-- From Name -->
			<div id="wp-mail-smtp-setting-row-from_name" class="wp-mail-smtp-setting-row wp-mail-smtp-setting-row-text wp-mail-smtp-clear">
				<span class="wp-mail-smtp-setting-label">
					<label for="wp-mail-smtp-setting-from_name"><?php esc_html_e( 'From Name', 'wp-mail-smtp' ); ?></label>
				</span>
				<span class="wp-mail-smtp-setting-field">
					<input name="wp-mail-smtp[mail][from_name]" type="text"
						value="<?php echo esc_attr( $options->get( 'mail', 'from_name' ) ); ?>"
						<?php echo $options->is_const_defined( 'mail', 'from_name' ) ? 'disabled' : ''; ?>
						id="wp-mail-smtp-setting-from-name" spellcheck="false"
					/>
					<p class="desc">
						<?php esc_html_e( 'You can specify the name that emails should be sent from.', 'wp-mail-smtp' ); ?><br/>
						<?php
						printf(
							/* translators: %s - WordPress. */
							esc_html__( 'If you leave this blank, the emails will be sent from %s.', 'wp-mail-smtp' ),
							'<code>WordPress</code>'
						);
						?>
					</p>
				</span>
			</div>

			<!-- Mailer -->
			<div id="wp-mail-smtp-setting-row-mailer" class="wp-mail-smtp-setting-row wp-mail-smtp-setting-row-mailer wp-mail-smtp-clear">
				<span class="wp-mail-smtp-setting-label">
					<label for="wp-mail-smtp-setting-mailer"><?php esc_html_e( 'Mailer', 'wp-mail-smtp' ); ?></label>
				</span>
				<span class="wp-mail-smtp-setting-field">
					<div class="wp-mail-smtp-mailers">

						<?php foreach ( wp_mail_smtp()->get_providers()->get_options_all() as $provider ) : ?>

							<div class="wp-mail-smtp-mailer <?php echo $mailer === $provider->get_slug() ? 'active' : ''; ?>">
								<div class="wp-mail-smtp-mailer-image">
									<img src="<?php echo esc_url( $provider->get_logo_url() ); ?>"
										alt="<?php echo esc_attr( $provider->get_title() ); ?>">
								</div>

								<div class="wp-mail-smtp-mailer-text">
									<input id="wp-mail-smtp-setting-mailer-<?php echo esc_attr( $provider->get_slug() ); ?>"
										type="radio" name="wp-mail-smtp[mail][mailer]"
										value="<?php echo esc_attr( $provider->get_slug() ); ?>"
										<?php checked( $provider->get_slug(), $mailer ); ?>
										<?php echo $options->is_const_defined( 'mail', 'mailer' ) ? 'disabled' : ''; ?>
									/>
									<label for="wp-mail-smtp-setting-mailer-<?php echo esc_attr( $provider->get_slug() ); ?>"><?php echo $provider->get_title(); ?></label>
								</div>
							</div>

						<?php endforeach; ?>

					</div>
				</span>
			</div>

			<!-- Return Path -->
			<div id="wp-mail-smtp-setting-row-return_path" class="wp-mail-smtp-setting-row wp-mail-smtp-setting-row-checkbox wp-mail-smtp-clear">
				<span class="wp-mail-smtp-setting-label">
					<label for="wp-mail-smtp-setting-return_path"><?php esc_html_e( 'Return Path', 'wp-mail-smtp' ); ?></label>
				</span>
				<span class="wp-mail-smtp-setting-field">
					<input name="wp-mail-smtp[mail][return_path]" type="checkbox"
						value="true" <?php checked( true, $options->get( 'mail', 'return_path' ) ); ?>
						<?php echo $options->is_const_defined( 'mail', 'return_path' ) ? 'disabled' : ''; ?>
						id="wp-mail-smtp-setting-return_path"
					/>
					<label for="wp-mail-smtp-setting-return_path"><?php esc_html_e( 'Set the return-path to match the From Email', 'wp-mail-smtp' ); ?></label></label>
					<p class="desc">
						<?php esc_html_e( 'Return Path indicates where non-delivery receipts - or bounce messages - are to be sent.', 'wp-mail-smtp' ); ?><br/>
						<?php esc_html_e( 'If unchecked bounce messages may be lost.', 'wp-mail-smtp' ); ?>
					</p>
				</span>
			</div>

			<!-- Mailer Options -->
			<div class="wp-mail-smtp-mailer-options">
				<?php foreach ( wp_mail_smtp()->get_providers()->get_options_all() as $provider ) : ?>

					<div class="wp-mail-smtp-mailer-option wp-mail-smtp-mailer-option-<?php echo esc_attr( $provider->get_slug() ); ?> <?php echo $mailer === $provider->get_slug() ? 'active' : 'hidden'; ?>">

						<!-- Mailer Option Title -->
						<div class="wp-mail-smtp-setting-row wp-mail-smtp-setting-row-content wp-mail-smtp-clear section-heading no-desc" id="wp-mail-smtp-setting-row-email-heading">
							<span class="wp-mail-smtp-setting-field">
								<h2><?php echo $provider->get_title(); ?></h2>
							</span>
						</div>

						<?php $provider->display_options(); ?>
					</div>

				<?php endforeach; ?>

			</div>

			<!-- General Section Title -->
			<div class="wp-mail-smtp-setting-row wp-mail-smtp-setting-row-content wp-mail-smtp-clear section-heading no-desc" id="wp-mail-smtp-setting-row-email-heading">
				<span class="wp-mail-smtp-setting-field">
					<h2><?php esc_html_e( 'General', 'wp-mail-smtp' ); ?></h2>
				</span>
			</div>

			<!-- Hide Announcements -->
			<div id="wp-mail-smtp-setting-row-am_notifications_hidden" class="wp-mail-smtp-setting-row wp-mail-smtp-setting-row-checkbox wp-mail-smtp-clear">
				<span class="wp-mail-smtp-setting-label">
					<label for="wp-mail-smtp-setting-am_notifications_hidden"><?php esc_html_e( 'Hide Announcements', 'wp-mail-smtp' ); ?></label>
				</span>
				<span class="wp-mail-smtp-setting-field">
					<input name="wp-mail-smtp[general][am_notifications_hidden]" type="checkbox"
						value="true" <?php checked( true, $options->get( 'general', 'am_notifications_hidden' ) ); ?>
						id="wp-mail-smtp-setting-am_notifications_hidden"
					/>
					<label for="wp-mail-smtp-setting-am_notifications_hidden"><?php esc_html_e( 'Check this if you would like to hide plugin announcements and update details.', 'wp-mail-smtp' ); ?></label>
				</span>
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

		// All the sanitization is done there.
		$options->set( $data );

		WP::add_admin_notice(
			esc_html__( 'Settings were successfully saved.', 'wp-mail-smtp' ),
			WP::ADMIN_NOTICE_SUCCESS
		);
	}
}
