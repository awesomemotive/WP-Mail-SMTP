<?php

namespace WPMailSMTP\Admin\Pages;

use WPMailSMTP\Admin\PageAbstract;
use WPMailSMTP\Options;

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
						<input name="wp-mail-smtp[mail][from_email]" type="email" value="<?php echo esc_attr( $options->get( 'mail', 'from_email' ) ); ?>" id="wp-mail-smtp-setting-from-email" class="regular-text" spellcheck="false" />
						<p class="description"><?php _e( 'You can specify the email address that emails should be sent from. If you leave this blank, the default email will be used.', 'wp-mail-smtp' ); ?></p>
					</td>
				</tr>
				<!-- From Name -->
				<tr>
					<th scope="row">
						<label for="wp-mail-smtp-setting-from-name"><?php _e( 'From Name', 'wp-mail-smtp' ); ?></label>
					</th>
					<td>
						<input name="wp-mail-smtp[mail][from_name]" type="text" value="<?php echo esc_attr( $options->get( 'mail', 'from_name' ) ); ?>" id="wp-mail-smtp-setting-from-name" class="regular-text" spellcheck="false" />
						<p class="description"><?php _e( 'You can specify the name that emails should be sent from. If you leave this blank, the emails will be sent from WordPress.', 'wp-mail-smtp' ); ?></p>
					</td>
				</tr>
				<!-- Mailer -->
				<tr>
					<th scope="row">
						<label for="wp-mail-smtp-setting-from-name"><?php _e( 'Mailer', 'wp-mail-smtp' ); ?></label>
					</th>
					<td>
						<div class="wp-mail-smtp-mailers">

							<div class="wp-mail-smtp-mailer">
								<div class="wp-mail-smtp-mailer-image <?php echo $mailer === 'mail' ? 'active' : ''; ?>">
									<img src="<?php echo wp_mail_smtp()->plugin_url; ?>/assets/images/php.png" alt="<?php esc_attr_e( 'Other SMTP', 'wp-mail-smtp' ); ?>">
								</div>
								<div class="wp-mail-smtp-mailer-text">
									<input id="mailer_mail" type="radio" name="wp-mail-smtp[mail][mailer]" value="mail" <?php checked( 'mail', $mailer ); ?> />
									<label for="mailer_mail"><?php _e( 'Default (none)', 'wp-mail-smtp' ); ?></label>
								</div>
							</div>

							<?php do_action( 'wp_mail_smtp_admin_settings_mailer', $options ); ?>

							<div class="wp-mail-smtp-mailer">
								<div class="wp-mail-smtp-mailer-image <?php echo $mailer === 'smtp' ? 'active' : ''; ?>">
									<img src="<?php echo wp_mail_smtp()->plugin_url; ?>/assets/images/smtp.png" alt="<?php esc_attr_e( 'Other SMTP', 'wp-mail-smtp' ); ?>">
								</div>
								<div class="wp-mail-smtp-mailer-text">
									<input id="mailer_smtp" type="radio" name="wp-mail-smtp[mail][mailer]" value="smtp" <?php checked( 'smtp', $mailer ); ?> />
									<label for="mailer_smtp"><?php _e( 'Other SMTP', 'wp-mail-smtp' ); ?></label>
								</div>
							</div>

							<?php if ( Options::init()->is_pepipost_active() ) : ?>
								<div class="wp-mail-smtp-mailer">
									<div class="wp-mail-smtp-mailer-image <?php echo $mailer === 'pepipost' ? 'active' : ''; ?>">
										<img src="<?php echo wp_mail_smtp()->plugin_url; ?>/assets/images/smtp.png" alt="<?php esc_attr_e( 'Other SMTP', 'wp-mail-smtp' ); ?>">
									</div>
									<div class="wp-mail-smtp-mailer-text">
										<input id="mailer_pepipost" type="radio" name="wp-mail-smtp[mail][mailer]" value="pepipost" <?php checked( 'pepipost', $mailer ); ?> />
										<label for="mailer_pepipost"><?php _e( 'Pepipost', 'wp-mail-smtp' ); ?></label>
									</div>
								</div>
							<?php endif; ?>
						</div>
					</td>
				</tr>
				<!-- Return Path -->
				<tr>
					<th scope="row">
						<label for="wp-mail-smtp-setting-return-path"><?php _e( 'Return Path', 'wp-mail-smtp' ); ?></label>
					</th>
					<td>
						<input name="wp-mail-smtp[mail][return_path]" type="checkbox" id="wp-mail-smtp-setting-return-path" <?php checked( true, $options->get( 'mail', 'return_path' ) ); ?> />
						<?php _e( 'Set the return-path to match the From Email', 'wp-mail-smtp' ); ?>
						<p class="description"><?php _e( 'Return Path indicates where non-delivery receipts - or bounce messages - are to be sent.', 'wp-mail-smtp' ); ?></p>
					</td>
				</tr>
			</table>

			<div class="wp-mail-smtp-mailer-options">

				<div class="wp-mail-smtp-mailer-options-mail <?php echo $mailer === 'mail' ? 'active' : 'hidden'; ?>">
					<h2><?php _e( 'Default (none)', 'wp-mail-smtp' ); ?></h2>

					<p>
						<?php _e( 'You currently have the native WordPress option selected. Please select an SMTP above to begin setup.', 'wp-mail-smtp' ); ?>
					</p>
				</div>

				<div class="wp-mail-smtp-mailer-options-smtp <?php echo $mailer === 'smtp' ? 'active' : 'hidden'; ?>">
					<h2><?php _e( 'Other SMTP', 'wp-mail-smtp' ); ?></h2>

					<table class="form-table">
						<!-- SMTP Host -->
						<tr>
							<th scope="row">
								<label for="wp-mail-smtp-setting-smtp-host"><?php _e( 'SMTP Host', 'wp-mail-smtp' ); ?></label>
							</th>
							<td>
								<input name="wp-mail-smtp[smtp][host]" type="text" value="<?php echo esc_attr( $options->get( 'smtp', 'host' ) ); ?>" id="wp-mail-smtp-setting-smtp-host" class="regular-text" spellcheck="false" />
							</td>
						</tr>
						<!-- SMTP Port -->
						<tr>
							<th scope="row">
								<label for="wp-mail-smtp-setting-smtp-port"><?php _e( 'SMTP Port', 'wp-mail-smtp' ); ?></label>
							</th>
							<td>
								<input name="wp-mail-smtp[smtp][port]" type="number" value="<?php echo esc_attr( $options->get( 'smtp', 'port' ) ); ?>" id="wp-mail-smtp-setting-smtp-port" class="small-text" spellcheck="false" />
							</td>
						</tr>
						<!-- SMTP Encryption -->
						<tr>
							<th scope="row">
								<label for="wp-mail-smtp-setting-smtp-port"><?php _e( 'Encryption', 'wp-mail-smtp' ); ?></label>
							</th>
							<td>
								<?php $encryption = $options->get( 'smtp', 'encryption' ); ?>

								<div class="wp-mail-smtp-inline-radios">
									<input type="radio" id="wp-mail-smtp-setting-smtp-enc-none" name="wp-mail-smtp[smtp][encryption]" value="none" <?php checked( 'none', $encryption ); ?>>
									<label for="wp-mail-smtp-setting-smtp-enc-none"><?php _e( 'None', 'wp-mail-smtp' ); ?></label>
									<input type="radio" id="wp-mail-smtp-setting-smtp-enc-ssl" name="wp-mail-smtp[smtp][encryption]" value="ssl" <?php checked( 'ssl', $encryption ); ?>>
									<label for="wp-mail-smtp-setting-smtp-enc-ssl"><?php _e( 'SSL', 'wp-mail-smtp' ); ?></label>
									<input type="radio" id="wp-mail-smtp-setting-smtp-enc-tls" name="wp-mail-smtp[smtp][encryption]" value="tls" <?php checked( 'tls', $encryption ); ?>>
									<label for="wp-mail-smtp-setting-smtp-enc-tls"><?php _e( 'TLS', 'wp-mail-smtp' ); ?></label>
								</div>

								<p class="description">
									<?php _e( 'TLS is not the same as STARTTLS. For most servers SSL is the recommended option.', 'wp-mail-smtp' ); ?>
								</p>
							</td>
						</tr>
						<!-- SMTP Authentication -->
						<tr>
							<th scope="row">
								<label for="wp-mail-smtp-setting-smtp-port"><?php _e( 'Authentication', 'wp-mail-smtp' ); ?></label>
							</th>
							<td>
								<?php $auth = $options->get( 'smtp', 'auth' ); ?>

								<div class="wp-mail-smtp-inline-radios">
									<input type="radio" id="wp-mail-smtp-setting-smtp-auth-no" name="wp-mail-smtp[smtp][auth]" value="no" <?php checked( false, $auth ); ?>>
									<label for="wp-mail-smtp-setting-smtp-auth-no"><?php _e( 'No', 'wp-mail-smtp' ); ?></label>
									<input type="radio" id="wp-mail-smtp-setting-smtp-auth-yes" name="wp-mail-smtp[smtp][auth]" value="yes" <?php checked( true, $auth ); ?>>
									<label for="wp-mail-smtp-setting-smtp-auth-yes"><?php _e( 'Yes', 'wp-mail-smtp' ); ?></label>
								</div>
							</td>
						</tr>
						<!-- SMTP Username -->
						<tr>
							<th scope="row">
								<label for="wp-mail-smtp-setting-smtp-user"><?php _e( 'SMTP Username', 'wp-mail-smtp' ); ?></label>
							</th>
							<td>
								<input name="wp-mail-smtp[smtp][user]" type="text" value="<?php echo esc_attr( $options->get( 'smtp', 'user' ) ); ?>" id="wp-mail-smtp-setting-smtp-user" class="regular-text" spellcheck="false" />
							</td>
						</tr>
						<!-- SMTP Password -->
						<tr>
							<th scope="row">
								<label for="wp-mail-smtp-setting-smtp-pass"><?php _e( 'SMTP Password', 'wp-mail-smtp' ); ?></label>
							</th>
							<td>
								<input name="wp-mail-smtp[smtp][pass]" type="text" value="<?php echo esc_attr( $options->get( 'smtp', 'pass' ) ); ?>" id="wp-mail-smtp-setting-smtp-pass" class="regular-text" spellcheck="false" />
							</td>
						</tr>
					</table>
				</div>

			</div>

			<p class="wp-mail-smtp-submit">
				<input type="submit" name="wp-mail-smtp[setting_submit]" class="button-primary" value="<?php esc_attr_e( 'Save Changes', 'wp-mail-smtp' ); ?>"/>
			</p>
		</form>

		<?php
	}

	/**
	 * @inheritdoc
	 */
	public function process( $data ) {

		$this->check_admin_referer();
	}
}
