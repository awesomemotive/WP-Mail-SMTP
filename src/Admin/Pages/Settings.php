<?php

namespace WPMailSMTP\Admin\Pages;

use WPMailSMTP\Admin\PageAbstract;
use WPMailSMTP\Options;
use WPMailSMTP\WP;

/**
 * Class Settings is part of Area, displays general settings of the plugin.
 */
class Settings extends PageAbstract {

	/**
	 * @var string Slug of a subpage.
	 */
	protected $slug = 'settings';

	/**
	 * @inheritdoc
	 */
	public function get_label() {
		return __( 'Settings', 'wp-mail-smtp' );
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

			<table class="form-table">

				<!-- From Email -->
				<tr>
					<th scope="row">
						<label for="wp-mail-smtp-setting-from-email"><?php _e( 'From Email', 'wp-mail-smtp' ); ?></label>
					</th>
					<td>
						<input name="wp-mail-smtp[mail][from_email]" type="email"
							value="<?php echo esc_attr( $options->get( 'mail', 'from_email' ) ); ?>"
							<?php echo $options->is_const_defined( 'mail', 'from_email' ) ? 'disabled' : ''; ?>
							id="wp-mail-smtp-setting-from-email" class="regular-text" spellcheck="false"
						/>

						<p class="description">
							<?php
							printf(
								/* translators: %s - default email address. */
								__( 'You can specify the email address that emails should be sent from. If you leave this blank, the default one will be used: %s', 'wp-mail-smtp' ),
								'<code>' . wp_mail_smtp()->get_processor()->get_default_email() . '</code>'
							);
							?>
						</p>
					</td>
				</tr>

				<!-- From Name -->
				<tr>
					<th scope="row">
						<label for="wp-mail-smtp-setting-from-name"><?php _e( 'From Name', 'wp-mail-smtp' ); ?></label>
					</th>
					<td>
						<input name="wp-mail-smtp[mail][from_name]" type="text"
							value="<?php echo esc_attr( $options->get( 'mail', 'from_name' ) ); ?>"
							<?php echo $options->is_const_defined( 'mail', 'from_name' ) ? 'disabled' : ''; ?>
							id="wp-mail-smtp-setting-from-name" class="regular-text" spellcheck="false"
						/>

						<p class="description">
							<?php
							printf(
								/* translators: %s - WordPress. */
								__( 'You can specify the name that emails should be sent from. If you leave this blank, the emails will be sent from %s.', 'wp-mail-smtp' ),
								'<code>WordPress</code>'
							);
							?>
						</p>
					</td>
				</tr>

				<!-- Mailer -->
				<tr>
					<th scope="row">
						<label for="wp-mail-smtp-setting-from-name"><?php _e( 'Mailer', 'wp-mail-smtp' ); ?></label>
					</th>
					<td>
						<div class="wp-mail-smtp-mailers">

							<?php foreach ( wp_mail_smtp()->get_admin()->get_providers() as $provider ) : ?>

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
						<label for="wp-mail-smtp-setting-return-path"><?php _e( 'Return Path', 'wp-mail-smtp' ); ?></label>
					</th>
					<td>
						<input name="wp-mail-smtp[mail][return_path]" type="checkbox"
							<?php checked( true, $options->get( 'mail', 'return_path' ) ); ?>
							<?php echo $options->is_const_defined( 'mail', 'return_path' ) ? 'disabled' : ''; ?>
							id="wp-mail-smtp-setting-return-path"
						/>
						<label for="wp-mail-smtp-setting-return-path"><?php _e( 'Set the return-path to match the From Email', 'wp-mail-smtp' ); ?></label>

						<p class="description">
							<?php _e( 'Return Path indicates where non-delivery receipts - or bounce messages - are to be sent.', 'wp-mail-smtp' ); ?>
						</p>
					</td>
				</tr>
			</table>

			<div class="wp-mail-smtp-mailer-options">

				<?php foreach ( wp_mail_smtp()->get_admin()->get_providers() as $provider ) : ?>

					<div class="wp-mail-smtp-mailer-option wp-mail-smtp-mailer-option-<?php echo esc_attr( $provider->get_slug() ); ?> <?php echo $mailer === $provider->get_slug() ? 'active' : 'hidden'; ?>">
						<h2><?php echo $provider->get_title(); ?></h2>

						<?php $provider->display_options(); ?>
					</div>

				<?php endforeach; ?>

			</div>

			<p class="wp-mail-smtp-submit">
				<button type="submit" class="button-primary"><?php _e( 'Save Changes', 'wp-mail-smtp' ); ?></button>
			</p>
		</form>

		<?php
	}

	/**
	 * @inheritdoc
	 */
	public function process( $data ) {

		$this->check_admin_referer();

		$options = new Options();

		// All the sanitization is done there.
		$options->set( $data );

		WP::add_admin_notice(
			__( 'Setting were successfully saved.', 'wp-mail-smtp' ),
			WP::ADMIN_NOTICE_SUCCESS
		);
	}
}
