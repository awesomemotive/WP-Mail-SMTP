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

			<h2><?php esc_html_e( 'Mail', 'wp-mail-smtp' ); ?></h2>

			<table class="form-table">

				<!-- From Email -->
				<tr>
					<th scope="row">
						<label for="wp-mail-smtp-setting-from-email"><?php esc_html_e( 'From Email', 'wp-mail-smtp' ); ?></label>
					</th>
					<td>
						<input name="wp-mail-smtp[mail][from_email]" type="email"
							value="<?php echo esc_attr( $options->get( 'mail', 'from_email' ) ); ?>"
							<?php echo $options->is_const_defined( 'mail', 'from_email' ) ? 'disabled' : ''; ?>
							id="wp-mail-smtp-setting-from-email" class="regular-text" spellcheck="false"
						/>

						<p class="description">
							<?php esc_html_e( 'You can specify the email address that emails should be sent from.', 'wp-mail-smtp' ); ?><br/>
							<?php
							printf(
								/* translators: %s - default email address. */
								esc_html__( 'If you leave this blank, the default one will be used: %s.', 'wp-mail-smtp' ),
								'<code>' . wp_mail_smtp()->get_processor()->get_default_email() . '</code>'
							);
							?>
						</p>
					</td>
				</tr>

				<!-- From Name -->
				<tr>
					<th scope="row">
						<label for="wp-mail-smtp-setting-from-name"><?php esc_html_e( 'From Name', 'wp-mail-smtp' ); ?></label>
					</th>
					<td>
						<input name="wp-mail-smtp[mail][from_name]" type="text"
							value="<?php echo esc_attr( $options->get( 'mail', 'from_name' ) ); ?>"
							<?php echo $options->is_const_defined( 'mail', 'from_name' ) ? 'disabled' : ''; ?>
							id="wp-mail-smtp-setting-from-name" class="regular-text" spellcheck="false"
						/>

						<p class="description">
							<?php esc_html_e( 'You can specify the name that emails should be sent from.', 'wp-mail-smtp' ); ?><br/>
							<?php
							printf(
								/* translators: %s - WordPress. */
								esc_html__( 'If you leave this blank, the emails will be sent from %s.', 'wp-mail-smtp' ),
								'<code>WordPress</code>'
							);
							?>
						</p>
					</td>
				</tr>

				<!-- Mailer -->
				<tr>
					<th scope="row">
						<label for="wp-mail-smtp-setting-from-name"><?php esc_html_e( 'Mailer', 'wp-mail-smtp' ); ?></label>
					</th>
					<td>
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
					</td>
				</tr>

				<!-- Return Path -->
				<tr>
					<th scope="row">
						<label for="wp-mail-smtp-setting-return-path"><?php esc_html_e( 'Return Path', 'wp-mail-smtp' ); ?></label>
					</th>
					<td>
						<input name="wp-mail-smtp[mail][return_path]" type="checkbox"
							<?php checked( true, $options->get( 'mail', 'return_path' ) ); ?>
							<?php echo $options->is_const_defined( 'mail', 'return_path' ) ? 'disabled' : ''; ?>
							id="wp-mail-smtp-setting-return-path"
						/>
						<label for="wp-mail-smtp-setting-return-path"><?php esc_html_e( 'Set the return-path to match the From Email', 'wp-mail-smtp' ); ?></label>

						<p class="description">
							<?php esc_html_e( 'Return Path indicates where non-delivery receipts - or bounce messages - are to be sent.', 'wp-mail-smtp' ); ?><br/>
							<?php esc_html_e( 'It won\'t be set if unchecked, thus bounce messages may be lost.', 'wp-mail-smtp' ); ?>
						</p>
					</td>
				</tr>

			</table>

			<!-- Mailer Options -->
			<div class="wp-mail-smtp-mailer-options">
				<?php foreach ( wp_mail_smtp()->get_providers()->get_options_all() as $provider ) : ?>

					<div class="wp-mail-smtp-mailer-option wp-mail-smtp-mailer-option-<?php echo esc_attr( $provider->get_slug() ); ?> <?php echo $mailer === $provider->get_slug() ? 'active' : 'hidden'; ?>">
						<h2><?php echo $provider->get_title(); ?></h2>

						<?php $provider->display_options(); ?>
					</div>

				<?php endforeach; ?>

			</div>

			<h2><?php esc_html_e( 'General', 'wp-mail-smtp' ); ?></h2>

			<table class="form-table">

				<!-- Hide Announcements -->
				<tr valign="top">
					<th scope="row">
						<label for="wp-mail-smtp-setting-am-notifications-hidden">
							<?php esc_html_e( 'Hide Announcements', 'wp-mail-smtp' ); ?>
						</label>
					</th>
					<td>
						<label for="wp-mail-smtp-setting-am-notifications-hidden">
							<input name="wp-mail-smtp[general][am_notifications_hidden]" type="checkbox" id="wp-mail-smtp-setting-am-notifications-hidden"
								value="true" <?php checked( true, $options->get( 'general', 'am_notifications_hidden' ) ); ?> />
							<?php esc_html_e( 'Check this if you would like to hide plugin announcements and update details.', 'wp-mail-smtp' ); ?>
						</label>
					</td>
				</tr>

			</table>

			<p class="wp-mail-smtp-submit">
				<button type="submit" class="button-primary"><?php esc_html_e( 'Save Changes', 'wp-mail-smtp' ); ?></button>
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
