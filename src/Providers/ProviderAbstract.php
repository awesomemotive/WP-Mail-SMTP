<?php

namespace WPMailSMTP\Providers;

use WPMailSMTP\Options;

/**
 * Abstract Class ProviderAbstract to contain common providers functionality.
 */
abstract class ProviderAbstract implements ProviderInterface {

	/**
	 * @inheritdoc
	 */
	public function display_options() {

		$options = new Options();
		?>

		<table class="form-table">
			<!-- SMTP Host -->
			<tr>
				<th scope="row">
					<label for="wp-mail-smtp-setting-smtp-host"><?php _e( 'SMTP Host', 'wp-mail-smtp' ); ?></label>
				</th>
				<td>
					<input name="wp-mail-smtp[smtp][host]" type="text"
					       value="<?php echo esc_attr( $options->get( 'smtp', 'host' ) ); ?>"
						<?php echo $options->is_const_defined( 'smtp', 'host' ) ? 'disabled' : ''; ?>
						   id="wp-mail-smtp-setting-smtp-host" class="regular-text" spellcheck="false"
					/>
				</td>
			</tr>
			<!-- SMTP Port -->
			<tr>
				<th scope="row">
					<label for="wp-mail-smtp-setting-smtp-port"><?php _e( 'SMTP Port', 'wp-mail-smtp' ); ?></label>
				</th>
				<td>
					<input name="wp-mail-smtp[smtp][port]" type="number"
					       value="<?php echo esc_attr( $options->get( 'smtp', 'port' ) ); ?>"
						<?php echo $options->is_const_defined( 'smtp', 'port' ) ? 'disabled' : ''; ?>
						   id="wp-mail-smtp-setting-smtp-port" class="small-text" spellcheck="false"
					/>
				</td>
			</tr>
			<!-- SMTP Encryption -->
			<tr>
				<th scope="row">
					<label for="wp-mail-smtp-setting-smtp-port"><?php _e( 'Encryption', 'wp-mail-smtp' ); ?></label>
				</th>
				<td>
					<div class="wp-mail-smtp-inline-radios">
						<input type="radio" id="wp-mail-smtp-setting-smtp-enc-none"
						       name="wp-mail-smtp[smtp][encryption]" value="none"
							<?php echo $options->is_const_defined( 'smtp', 'encryption' ) ? 'disabled' : ''; ?>
							<?php checked( 'none', $options->get( 'smtp', 'encryption' ) ); ?>
						/>
						<label for="wp-mail-smtp-setting-smtp-enc-none"><?php _e( 'None', 'wp-mail-smtp' ); ?></label>

						<input type="radio" id="wp-mail-smtp-setting-smtp-enc-ssl"
						       name="wp-mail-smtp[smtp][encryption]" value="ssl"
							<?php echo $options->is_const_defined( 'smtp', 'encryption' ) ? 'disabled' : ''; ?>
							<?php checked( 'ssl', $options->get( 'smtp', 'encryption' ) ); ?>
						/>
						<label for="wp-mail-smtp-setting-smtp-enc-ssl"><?php _e( 'SSL', 'wp-mail-smtp' ); ?></label>

						<input type="radio" id="wp-mail-smtp-setting-smtp-enc-tls"
						       name="wp-mail-smtp[smtp][encryption]" value="tls"
							<?php echo $options->is_const_defined( 'smtp', 'encryption' ) ? 'disabled' : ''; ?>
							<?php checked( 'tls', $options->get( 'smtp', 'encryption' ) ); ?>
						/>
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
					<div class="wp-mail-smtp-inline-radios">
						<input type="radio" id="wp-mail-smtp-setting-smtp-auth-no"
						       name="wp-mail-smtp[smtp][auth]" value="no"
							<?php echo $options->is_const_defined( 'smtp', 'auth' ) ? 'disabled' : ''; ?>
							<?php checked( false, $options->get( 'smtp', 'auth' ) ); ?>
						/>
						<label for="wp-mail-smtp-setting-smtp-auth-no"><?php _e( 'No', 'wp-mail-smtp' ); ?></label>

						<input type="radio" id="wp-mail-smtp-setting-smtp-auth-yes"
						       name="wp-mail-smtp[smtp][auth]" value="yes"
							<?php echo $options->is_const_defined( 'smtp', 'auth' ) ? 'disabled' : ''; ?>
							<?php checked( true, $options->get( 'smtp', 'auth' ) ); ?>
						/>
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
					<input name="wp-mail-smtp[smtp][user]" type="text"
					       value="<?php echo esc_attr( $options->get( 'smtp', 'user' ) ); ?>"
						<?php echo $options->is_const_defined( 'smtp', 'user' ) ? 'disabled' : ''; ?>
						   id="wp-mail-smtp-setting-smtp-user" class="regular-text" spellcheck="false"
					/>
				</td>
			</tr>
			<!-- SMTP Password -->
			<tr>
				<th scope="row">
					<label for="wp-mail-smtp-setting-smtp-pass"><?php _e( 'SMTP Password', 'wp-mail-smtp' ); ?></label>
				</th>
				<td>
					<?php if ( $options->is_const_defined( 'smtp', 'pass' ) ) : ?>
						<input type="text" value="*************" disabled id="wp-mail-smtp-setting-smtp-pass" class="regular-text"/>
					<?php else : ?>
						<input name="wp-mail-smtp[smtp][pass]" type="text"
						       value="<?php echo esc_attr( $options->get( 'smtp', 'pass' ) ); ?>"
						       id="wp-mail-smtp-setting-smtp-pass" class="regular-text" spellcheck="false"
						/>
					<?php endif; ?>
				</td>
			</tr>
		</table>

		<?php
	}
}
