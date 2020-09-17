<?php

namespace WPMailSMTP\Providers\SMTPcom;

use WPMailSMTP\Options as PluginOptions;
use WPMailSMTP\Providers\OptionsAbstract;

/**
 * Class Options.
 *
 * @since 2.0.0
 */
class Options extends OptionsAbstract {

	/**
	 * Mailer slug.
	 *
	 * @since 2.0.0
	 */
	const SLUG = 'smtpcom';

	/**
	 * Options constructor.
	 *
	 * @since 2.0.0
	 * @since 2.3.0 Added supports parameter.
	 */
	public function __construct() {

		$allowed_kses_html = array(
			'strong' => array(),
			'br'     => array(),
			'a'      => array(
				'href'   => array(),
				'rel'    => array(),
				'target' => array(),
			),
		);

		$description  = sprintf(
			wp_kses( /* translators: %s - URL to smtp.com site. */
				__( '<strong><a href="%s" target="_blank" rel="noopener noreferrer">SMTP.com</a> is a recommended transactional email service.</strong> With a 22 years of track record of reliable email delivery, SMTP.com is a premiere solution for WordPress developers and website owners. SMTP.com has been around for almost as long as email itself. Their super simple integration interface makes it easy to get started while a powerful API and robust documentation make it a preferred choice among developers. Start a 30-day free trial with 50,000 emails.', 'wp-mail-smtp' ),
				$allowed_kses_html
			),
			'https://wpmailsmtp.com/go/smtp/'
		);
		$description .= '<br><br>';
		$description .= sprintf(
			wp_kses( /* translators: %s - URL to wpmailsmtp.com doc page for stmp.com. */
				__( 'Read our <a href="%s" target="_blank" rel="noopener noreferrer">SMTP.com documentation</a> to learn how to configure SMTP.com and improve your email deliverability.', 'wp-mail-smtp' ),
				$allowed_kses_html
			),
			'https://wpmailsmtp.com/docs/how-to-set-up-the-smtp-com-mailer-in-wp-mail-smtp'
		);

		$mailer_options = PluginOptions::init()->get_group( self::SLUG );

		if ( empty( $mailer_options['api_key'] ) && empty( $mailer_options['channel'] ) ) {
			$description .= '</p><p class="buttonned"><a href="https://wpmailsmtp.com/go/smtp/" target="_blank" rel="noopener noreferrer" class="wp-mail-smtp-btn wp-mail-smtp-btn-md wp-mail-smtp-btn-blueish">' .
											esc_html__( 'Get Started with SMTP.com', 'wp-mail-smtp' ) .
											'</a></p>';
		}

		parent::__construct(
			[
				'logo_url'    => wp_mail_smtp()->assets_url . '/images/providers/smtp-com.svg',
				'slug'        => self::SLUG,
				'title'       => esc_html__( 'SMTP.com', 'wp-mail-smtp' ),
				'description' => $description,
				'recommended' => true,
				'supports'    => [
					'from_email'       => true,
					'from_name'        => true,
					'return_path'      => false,
					'from_email_force' => true,
					'from_name_force'  => true,
				],
			]
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
					<?php $this->display_const_set_message( 'WPMS_SMTPCOM_API_KEY' ); ?>
				<?php else : ?>
					<input type="password" spellcheck="false"
						name="wp-mail-smtp[<?php echo esc_attr( $this->get_slug() ); ?>][api_key]"
						value="<?php echo esc_attr( $this->options->get( $this->get_slug(), 'api_key' ) ); ?>"
						id="wp-mail-smtp-setting-<?php echo esc_attr( $this->get_slug() ); ?>-api_key"
					/>
				<?php endif; ?>
				<p class="desc">
					<?php
					printf( /* translators: %s - API key link. */
						esc_html__( 'Follow this link to get an API Key from SMTP.com: %s.', 'wp-mail-smtp' ),
						'<a href="https://my.smtp.com/settings/api" target="_blank" rel="noopener noreferrer">' .
						esc_html__( 'Get API Key', 'wp-mail-smtp' ) .
						'</a>'
					);
					?>
				</p>
			</div>
		</div>

		<!-- Channel/Sender -->
		<div id="wp-mail-smtp-setting-row-<?php echo esc_attr( $this->get_slug() ); ?>-channel" class="wp-mail-smtp-setting-row wp-mail-smtp-setting-row-text wp-mail-smtp-clear">
			<div class="wp-mail-smtp-setting-label">
				<label for="wp-mail-smtp-setting-<?php echo esc_attr( $this->get_slug() ); ?>-channel"><?php esc_html_e( 'Sender Name', 'wp-mail-smtp' ); ?></label>
			</div>
			<div class="wp-mail-smtp-setting-field">
				<input name="wp-mail-smtp[<?php echo esc_attr( $this->get_slug() ); ?>][channel]" type="text"
					value="<?php echo esc_attr( $this->options->get( $this->get_slug(), 'channel' ) ); ?>"
					<?php echo $this->options->is_const_defined( $this->get_slug(), 'channel' ) ? 'disabled' : ''; ?>
					id="wp-mail-smtp-setting-<?php echo esc_attr( $this->get_slug() ); ?>-channel" spellcheck="false"
				/>
				<?php
				if ( $this->options->is_const_defined( $this->get_slug(), 'channel' ) ) {
					$this->display_const_set_message( 'WPMS_SMTPCOM_CHANNEL' );
				}
				?>
				<p class="desc">
					<?php
					printf( /* translators: %s - Channel/Sender Name link for smtp.com documentation. */
						esc_html__( 'Follow this link to get a Sender Name from SMTP.com: %s.', 'wp-mail-smtp' ),
						'<a href="https://my.smtp.com/senders/" target="_blank" rel="noopener noreferrer">' .
						esc_html__( 'Get Sender Name', 'wp-mail-smtp' ) .
						'</a>'
					);
					?>
				</p>
			</div>
		</div>

		<?php
	}
}
