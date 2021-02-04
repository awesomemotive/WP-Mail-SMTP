<?php

namespace WPMailSMTP\Providers\Mailgun;

use WPMailSMTP\Providers\OptionsAbstract;

/**
 * Class Option.
 *
 * @since 1.0.0
 */
class Options extends OptionsAbstract {

	/**
	 * Mailgun constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		parent::__construct(
			array(
				'logo_url'    => wp_mail_smtp()->assets_url . '/images/providers/mailgun.svg',
				'slug'        => 'mailgun',
				'title'       => esc_html__( 'Mailgun', 'wp-mail-smtp' ),
				'description' => sprintf(
					wp_kses(
						/* translators: %1$s - URL to mailgun.com; %2$s - URL to Mailgun documentation on wpmailsmtp.com */
						__( '<a href="%1$s" target="_blank" rel="noopener noreferrer">Mailgun</a> is one of the leading transactional email services trusted by over 150,000+ businesses. They provide 5,000 free emails per month for 3 months.<br><br>Read our <a href="%2$s" target="_blank" rel="noopener noreferrer">Mailgun documentation</a> to learn how to configure Mailgun and improve your email deliverability.', 'wp-mail-smtp' ),
						array(
							'br' => array(),
							'a'  => array(
								'href'   => array(),
								'rel'    => array(),
								'target' => array(),
							),
						)
					),
					'https://www.mailgun.com',
					'https://wpmailsmtp.com/docs/how-to-set-up-the-mailgun-mailer-in-wp-mail-smtp/'
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
				<label for="wp-mail-smtp-setting-<?php echo esc_attr( $this->get_slug() ); ?>-api_key"><?php esc_html_e( 'Private API Key', 'wp-mail-smtp' ); ?></label>
			</div>
			<div class="wp-mail-smtp-setting-field">
				<?php if ( $this->options->is_const_defined( $this->get_slug(), 'api_key' ) ) : ?>
					<input type="text" disabled value="****************************************"
						id="wp-mail-smtp-setting-<?php echo esc_attr( $this->get_slug() ); ?>-api_key"
					/>
					<?php $this->display_const_set_message( 'WPMS_MAILGUN_API_KEY' ); ?>
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
						esc_html__( 'Follow this link to get an API Key from Mailgun: %s.', 'wp-mail-smtp' ),
						'<a href="https://app.mailgun.com/app/account/security/api_keys" target="_blank" rel="noopener noreferrer">' .
						esc_html__( 'Get a Private API Key', 'wp-mail-smtp' ) .
						'</a>'
					);
					?>
				</p>
			</div>
		</div>

		<!-- Domain -->
		<div id="wp-mail-smtp-setting-row-<?php echo esc_attr( $this->get_slug() ); ?>-domain" class="wp-mail-smtp-setting-row wp-mail-smtp-setting-row-text wp-mail-smtp-clear">
			<div class="wp-mail-smtp-setting-label">
				<label for="wp-mail-smtp-setting-<?php echo esc_attr( $this->get_slug() ); ?>-domain"><?php esc_html_e( 'Domain Name', 'wp-mail-smtp' ); ?></label>
			</div>
			<div class="wp-mail-smtp-setting-field">
				<input name="wp-mail-smtp[<?php echo esc_attr( $this->get_slug() ); ?>][domain]" type="text"
					value="<?php echo esc_attr( $this->options->get( $this->get_slug(), 'domain' ) ); ?>"
					<?php echo $this->options->is_const_defined( $this->get_slug(), 'domain' ) ? 'disabled' : ''; ?>
					id="wp-mail-smtp-setting-<?php echo esc_attr( $this->get_slug() ); ?>-domain" spellcheck="false"
				/>
				<p class="desc">
					<?php
					printf(
						/* translators: %s - Domain Name link. */
						esc_html__( 'Follow this link to get a Domain Name from Mailgun: %s.', 'wp-mail-smtp' ),
						'<a href="https://app.mailgun.com/app/domains" target="_blank" rel="noopener noreferrer">' .
						esc_html__( 'Get a Domain Name', 'wp-mail-smtp' ) .
						'</a>'
					);
					?>
				</p>
			</div>
		</div>

		<!-- Region -->
		<div id="wp-mail-smtp-setting-row-<?php echo esc_attr( $this->get_slug() ); ?>-region" class="wp-mail-smtp-setting-row wp-mail-smtp-setting-row-radio wp-mail-smtp-clear">
			<div class="wp-mail-smtp-setting-label">
				<label><?php esc_html_e( 'Region', 'wp-mail-smtp' ); ?></label>
			</div>
			<div class="wp-mail-smtp-setting-field">

				<label for="wp-mail-smtp-setting-<?php echo esc_attr( $this->get_slug() ); ?>-region-us">
					<input type="radio" id="wp-mail-smtp-setting-<?php echo esc_attr( $this->get_slug() ); ?>-region-us"
						name="wp-mail-smtp[<?php echo esc_attr( $this->get_slug() ); ?>][region]" value="US"
						<?php echo $this->options->is_const_defined( $this->get_slug(), 'region' ) ? 'disabled' : ''; ?>
						<?php checked( 'US', $this->options->get( $this->get_slug(), 'region' ) ); ?>
					/>
					<?php esc_html_e( 'US', 'wp-mail-smtp' ); ?>
				</label>

				<label for="wp-mail-smtp-setting-<?php echo esc_attr( $this->get_slug() ); ?>-region-eu">
					<input type="radio" id="wp-mail-smtp-setting-<?php echo esc_attr( $this->get_slug() ); ?>-region-eu"
						name="wp-mail-smtp[<?php echo esc_attr( $this->get_slug() ); ?>][region]" value="EU"
						<?php echo $this->options->is_const_defined( $this->get_slug(), 'region' ) ? 'disabled' : ''; ?>
						<?php checked( 'EU', $this->options->get( $this->get_slug(), 'region' ) ); ?>
					/>
					<?php esc_html_e( 'EU', 'wp-mail-smtp' ); ?>
				</label>

				<p class="desc">
					<?php esc_html_e( 'Define which endpoint you want to use for sending messages.', 'wp-mail-smtp' ); ?><br>
					<?php esc_html_e( 'If you are operating under EU laws, you may be required to use EU region.', 'wp-mail-smtp' ); ?>
					<?php
					printf(
						wp_kses(
							/* translators: %s - URL to Mailgun.com page. */
							__( '<a href="%s" rel="" target="_blank">More information</a> on Mailgun.com.', 'wp-mail-smtp' ),
							array(
								'a' => array(
									'href'   => array(),
									'rel'    => array(),
									'target' => array(),
								),
							)
						),
						'https://www.mailgun.com/regions'
					);
					?>
				</p>
			</div>
		</div>

		<?php
	}
}
