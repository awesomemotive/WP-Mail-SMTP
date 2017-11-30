<?php

namespace WPMailSMTP\Providers\Gmail;

use WPMailSMTP\Providers\OptionAbstract;

/**
 * Class Option.
 *
 * @since 1.0.0
 */
class Options extends OptionAbstract {

	/**
	 * Mailgun constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		parent::__construct(
			array(
				'logo_url' => wp_mail_smtp()->plugin_url . '/assets/images/gmail.png',
				'slug'     => 'gmail',
				'title'    => esc_html__( 'Gmail', 'wp-mail-smtp' ),
				'php'      => '5.4',
			)
		);
	}

	/**
	 * @inheritdoc
	 */
	public function display_options() {

		// Do not display options if PHP version is not correct.
		if ( ! $this->is_php_correct() ) {
			$this->display_php_warning();

			return;
		}
		?>

		<table class="form-table">

			<!-- Client ID -->
			<tr>
				<th scope="row">
					<label for="wp-mail-smtp-setting-<?php echo esc_attr( $this->get_slug() ); ?>-client-id"><?php esc_html_e( 'Client ID', 'wp-mail-smtp' ); ?></label>
				</th>
				<td>
					<input name="wp-mail-smtp[<?php echo esc_attr( $this->get_slug() ); ?>][client_id]" type="text"
						value="<?php echo esc_attr( $this->options->get( $this->get_slug(), 'client_id' ) ); ?>"
						<?php echo $this->options->is_const_defined( $this->get_slug(), 'client_id' ) ? 'disabled' : ''; ?>
						id="wp-mail-smtp-setting-<?php echo esc_attr( $this->get_slug() ); ?>-client-id" class="regular-text" spellcheck="false"
					/>
				</td>
			</tr>

			<!-- Client Secret -->
			<tr>
				<th scope="row">
					<label for="wp-mail-smtp-setting-<?php echo esc_attr( $this->get_slug() ); ?>-client-secret"><?php esc_html_e( 'Client Secret', 'wp-mail-smtp' ); ?></label>
				</th>
				<td>
					<input name="wp-mail-smtp[<?php echo esc_attr( $this->get_slug() ); ?>][client_secret]" type="text"
						value="<?php echo esc_attr( $this->options->get( $this->get_slug(), 'client_secret' ) ); ?>"
						<?php echo $this->options->is_const_defined( $this->get_slug(), 'client_secret' ) ? 'disabled' : ''; ?>
						id="wp-mail-smtp-setting-<?php echo esc_attr( $this->get_slug() ); ?>-client-secret" class="regular-text" spellcheck="false"
					/>
				</td>
			</tr>

			<!-- Authorized redirect URI -->
			<tr>
				<th scope="row">
					<label for="wp-mail-smtp-setting-<?php echo esc_attr( $this->get_slug() ); ?>-client-redirect"><?php esc_html_e( 'Authorized redirect URI', 'wp-mail-smtp' ); ?></label>
				</th>
				<td>
					<input type="text" readonly="readonly" class="regular-text"
						value="<?php echo esc_attr( Auth::get_plugin_auth_url() ); ?>"
						id="wp-mail-smtp-setting-<?php echo esc_attr( $this->get_slug() ); ?>-client-redirect"
					/>
					<button type="button" class="button wp-mail-smtp-setting-copy"
						title="<?php esc_attr_e( 'Copy URL to clipboard', 'wp-mail-smtp' ); ?>"
						data-source_id="wp-mail-smtp-setting-<?php echo esc_attr( $this->get_slug() ); ?>-client-redirect">
						<span class="dashicons dashicons-admin-page"></span>
					</button>
					<p class="description">
						<?php esc_html_e( 'This is the path on your site that you will be redirected to after you have authenticated with Google.', 'wp-mail-smtp' ); ?>
						<br>
						<?php esc_html_e( 'You need to copy this URL into "Authorized redirect URIs" field for you web application on Google APIs site for your project there.', 'wp-mail-smtp' ); ?>
					</p>
				</td>
			</tr>

			<!-- Auth users button -->
			<?php $auth = new Auth(); ?>
			<?php if ( ! $auth->is_completed() ) : ?>
				<tr>
					<td>&nbsp;</td>
					<td>
						<a href="<?php echo esc_url( $auth->get_google_auth_url() ); ?>" class="button-primary">
							<?php esc_html_e( 'Allow plugin to send emails using your Google account', 'wp-mail-smtp' ); ?>
						</a>
					</td>
				</tr>
			<?php endif; ?>

		</table>

		<?php
	}
}
