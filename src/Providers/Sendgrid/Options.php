<?php

namespace WPMailSMTP\Providers\Sendgrid;

use WPMailSMTP\Providers\OptionsAbstract;

/**
 * Class Option.
 *
 * @since 1.0.0
 */
class Options extends OptionsAbstract {

	/**
	 * Options constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		parent::__construct(
			array(
				'logo_url'    => wp_mail_smtp()->plugin_url . '/assets/images/sendgrid.svg',
				'slug'        => 'sendgrid',
				'title'       => esc_html__( 'SendGrid', 'wp-mail-smtp' ),
				'description' => sprintf(
					wp_kses(
						/* translators: %1$s - opening link tag; %2$s - closing link tag; %3$s - opening link tag; %4$s - closing link tag. */
						__( '%1$sSendGrid%2$s is one of the leading transactional email services, sending over 35 billion emails every month. They provide users 100 free emails per day.<br><br>Read our %3$sSendGrid documentation%4$s to learn how to set up SendGrid and improve your email deliverability.', 'wp-mail-smtp' ),
						array(
							'br' => array(),
							'a'  => array(
								'href'   => array(),
								'rel'    => array(),
								'target' => array(),
							),
						)
					),
					'<a href="https://sendgrid.com" target="_blank" rel="noopener noreferrer">',
					'</a>',
					'<a href="https://wpmailsmtp.com/docs/how-to-set-up-the-sendgrid-mailer-in-wp-mail-smtp/" target="_blank" rel="noopener noreferrer">',
					'</a>'
				),
			)
		);
	}

	/**
	 * @inheritdoc
	 */
	public function display_options() {
		?>

		<!-- API Key -->
		<div id="wp-mail-smtp-setting-row-<?php echo esc_attr( $this->get_slug() ); ?>-api_key" class="wp-mail-smtp-setting-row wp-mail-smtp-setting-row-text wp-mail-smtp-clear">
			<div class="wp-mail-smtp-setting-label">
				<label for="wp-mail-smtp-setting-<?php echo esc_attr( $this->get_slug() ); ?>-api_key"><?php esc_html_e( 'API Key', 'wp-mail-smtp' ); ?></label>
			</div>
			<div class="wp-mail-smtp-setting-field">
				<?php if ( $this->options->is_const_defined( $this->get_slug(), 'api_key' ) ) : ?>
					<input type="text" disabled value="****************************************"
						id="wp-mail-smtp-setting-<?php echo esc_attr( $this->get_slug() ); ?>-api_key"
					/>
					<?php $this->display_const_set_message( 'WPMS_SENDGRID_API_KEY' ); ?>
				<?php else : ?>
					<input type="password" spellcheck="false"
						name="wp-mail-smtp[<?php echo esc_attr( $this->get_slug() ); ?>][api_key]"
						value="<?php echo esc_attr( $this->options->get( $this->get_slug(), 'api_key' ) ); ?>"
						id="wp-mail-smtp-setting-<?php echo esc_attr( $this->get_slug() ); ?>-api_key"
					/>
				<?php endif; ?>
				<p class="desc">
					<?php
					printf(
						/* translators: %s - API key link. */
						esc_html__( 'Follow this link to get an API Key from SendGrid: %s.', 'wp-mail-smtp' ),
						'<a href="https://app.sendgrid.com/settings/api_keys" target="_blank" rel="noopener noreferrer">' .
						esc_html__( 'Create API Key', 'wp-mail-smtp' ) .
						'</a>'
					);
					?>
					<br/>
					<?php
					printf(
						/* translators: %s - SendGrid access level. */
						esc_html__( 'To send emails you will need only a %s access level for this API key.', 'wp-mail-smtp' ),
						'<code>Mail Send</code>'
					);
					?>
				</p>
			</div>
		</div>

		<?php
	}
}
