<?php

namespace WPMailSMTP\Providers;

/**
 * Class Sendgrid
 */
class Sendgrid extends ProviderAbstract {

	/**
	 * Sendgrid constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		parent::__construct(
			array(
				'logo_url' => wp_mail_smtp()->plugin_url . '/assets/images/sendgrid.png',
				'slug'     => 'sendgrid',
				'title'    => esc_html__( 'Sendgrid', 'wp-mail-smtp' ),
			)
		);
	}

	/**
	 * @inheritdoc
	 */
	public function display_options() {
		?>

		<table class="form-table">

			<!-- API Key -->
			<tr>
				<th scope="row">
					<label for="wp-mail-smtp-setting-<?php echo esc_attr( $this->get_slug() ); ?>-api-key"><?php esc_html_e( 'API Key', 'wp-mail-smtp' ); ?></label>
				</th>
				<td>
					<input name="wp-mail-smtp[<?php echo esc_attr( $this->get_slug() ); ?>][api_key]" type="text"
						value="<?php echo esc_attr( $this->options->get( $this->get_slug(), 'api_key' ) ); ?>"
						<?php echo $this->options->is_const_defined( $this->get_slug(), 'api_key' ) ? 'disabled' : ''; ?>
						id="wp-mail-smtp-setting-<?php echo esc_attr( $this->get_slug() ); ?>-api-key" class="regular-text" spellcheck="false"
					/>

					<p class="description">
						<?php
						printf(
							/* translators: %s - API key link. */
							esc_html__( 'Follow this link to get an API Key from Sendgrid: %s.', 'wp-mail-smtp' ),
							'<a href="https://app.sendgrid.com/settings/api_keys" target="_blank">' .
							esc_html__( 'Create API Key', 'wp-mail-smtp' ) .
							'</a>'
						);
						?>
					</p>
					<p class="description">
						<?php
						printf(
							/* translators: %s - Sendgrid access level. */
							esc_html__( 'You will need only a %s access level for this API key.', 'wp-mail-smtp' ),
							'<code>Mail Send</code>'
						);
						?>
					</p>
				</td>
			</tr>

		</table>

		<?php
	}
}
