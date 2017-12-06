<?php

namespace WPMailSMTP\Providers\Sendgrid;

use WPMailSMTP\Providers\OptionAbstract;

/**
 * Class Option.
 *
 * @since 1.0.0
 */
class Options extends OptionAbstract {

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

		<!-- API Key -->
		<div id="wp-mail-smtp-setting-row-<?php echo esc_attr( $this->get_slug() ); ?>-apli_key" class="wp-mail-smtp-setting-row wp-mail-smtp-setting-row-text wp-mail-smtp-clear">
			<span class="wp-mail-smtp-setting-label">
				<label for="wp-mail-smtp-setting-<?php echo esc_attr( $this->get_slug() ); ?>-apli_key"><?php esc_html_e( 'API Key', 'wp-mail-smtp' ); ?></label>
			</span>
			<span class="wp-mail-smtp-setting-field">
				<input name="wp-mail-smtp[<?php echo esc_attr( $this->get_slug() ); ?>][apli_key]" type="text"
					value="<?php echo esc_attr( $this->options->get( $this->get_slug(), 'apli_key' ) ); ?>"
					<?php echo $this->options->is_const_defined( $this->get_slug(), 'apli_key' ) ? 'disabled' : ''; ?>
					id="wp-mail-smtp-setting-<?php echo esc_attr( $this->get_slug() ); ?>-apli_key" spellcheck="false"
				/>
				<p class="desc">
					<?php
					printf(
						/* translators: %s - API key link. */
						esc_html__( 'Follow this link to get an API Key from Sendgrid: %s.', 'wp-mail-smtp' ),
						'<a href="https://app.sendgrid.com/settings/api_keys" target="_blank">' .
						esc_html__( 'Create API Key', 'wp-mail-smtp' ) .
						'</a>'
					);
					?>
					<br/>
					<?php
					printf(
						/* translators: %s - Sendgrid access level. */
						esc_html__( 'To send emails you will need only a %s access level for this API key.', 'wp-mail-smtp' ),
						'<code>Mail Send</code>'
					);
					?>
				</p>
			</span>
		</div>

		<?php
	}
}
