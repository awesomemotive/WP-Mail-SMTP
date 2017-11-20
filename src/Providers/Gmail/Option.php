<?php

namespace WPMailSMTP\Providers\Gmail;

use WPMailSMTP\Providers\OptionAbstract;

/**
 * Class Option
 *
 * @package WPMailSMTP\Providers\Mailgun
 */
class Option extends OptionAbstract {

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
			)
		);
	}

	/**
	 * @inheritdoc
	 */
	public function display_options() {
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

		</table>

		<?php
	}
}
