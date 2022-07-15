<?php

namespace WPMailSMTP\Providers\Sendinblue;

use WPMailSMTP\Providers\OptionsAbstract;
use WPMailSMTP\Options as PluginOptions;

/**
 * Class Options.
 *
 * @since 1.6.0
 */
class Options extends OptionsAbstract {

	/**
	 * Mailer slug.
	 *
	 * @since 1.6.0
	 */
	const SLUG = 'sendinblue';

	/**
	 * Options constructor.
	 *
	 * @since 1.6.0
	 * @since 2.3.0 Added supports parameter.
	 */
	public function __construct() {

		$description = sprintf(
			wp_kses( /* translators: %1$s - URL to sendinblue.com site. */
				__( '<strong><a href="%1$s" target="_blank" rel="noopener noreferrer">Sendinblue</a> is one of our recommended mailers.</strong> It\'s a transactional email provider with scalable price plans, so it\'s suitable for any size of business.<br><br>If you\'re just starting out, you can use Sendinblue\'s free plan to send up to 300 emails a day. You don\'t need to use a credit card to try it out. When you\'re ready, you can upgrade to a higher plan to increase your sending limits.', 'wp-mail-smtp' ) .
				'<br><br>' .
				/* translators: %2$s - URL to wpmailsmtp.com doc. */
				__( 'To get started, read our <a href="%2$s" target="_blank" rel="noopener noreferrer">Sendinblue documentation</a>.', 'wp-mail-smtp' ),
				[
					'strong' => true,
					'br'     => true,
					'a'      => [
						'href'   => true,
						'rel'    => true,
						'target' => true,
					],
				]
			),
			'https://wpmailsmtp.com/go/sendinblue/',
			esc_url( wp_mail_smtp()->get_utm_url( 'https://wpmailsmtp.com/docs/how-to-set-up-the-sendinblue-mailer-in-wp-mail-smtp/', 'Sendinblue documentation' ) )
		);

		$api_key = PluginOptions::init()->get( self::SLUG, 'api_key' );

		if ( empty( $api_key ) ) {
			$description .= sprintf(
				'</p><p class="buttonned"><a href="%1$s" target="_blank" rel="noopener noreferrer" class="wp-mail-smtp-btn wp-mail-smtp-btn-md wp-mail-smtp-btn-blueish">%2$s</a></p>',
				'https://wpmailsmtp.com/go/sendinblue/',
				esc_html__( 'Get Sendinblue Now (Free)', 'wp-mail-smtp' )
			);
		}

		$description .= '<p class="wp-mail-smtp-tooltip">' .
			esc_html__( 'Transparency and Disclosure', 'wp-mail-smtp' ) .
			'<span class="wp-mail-smtp-tooltip-text">' .
			esc_html__( 'We believe in full transparency. The Sendinblue links above are tracking links as part of our partnership with Sendinblue. We can recommend just about any SMTP service, but we only recommend products that we believe will add value to our users.', 'wp-mail-smtp' ) .
			'</span></p>';

		parent::__construct(
			[
				'logo_url'    => wp_mail_smtp()->assets_url . '/images/providers/sendinblue.svg',
				'slug'        => self::SLUG,
				'title'       => esc_html__( 'Sendinblue', 'wp-mail-smtp' ),
				'php'         => '5.6',
				'description' => $description,
				'supports'    => [
					'from_email'       => true,
					'from_name'        => true,
					'return_path'      => false,
					'from_email_force' => true,
					'from_name_force'  => true,
				],
				'recommended' => true,
			]
		);
	}

	/**
	 * Output the mailer provider options.
	 *
	 * @since 1.6.0
	 */
	public function display_options() {

		// Do not display options if PHP version is not correct.
		if ( ! $this->is_php_correct() ) {
			$this->display_php_warning();

			return;
		}
		?>

		<!-- API Key -->
		<div id="wp-mail-smtp-setting-row-<?php echo esc_attr( $this->get_slug() ); ?>-client_id"
			class="wp-mail-smtp-setting-row wp-mail-smtp-setting-row-text wp-mail-smtp-clear">
			<div class="wp-mail-smtp-setting-label">
				<label for="wp-mail-smtp-setting-<?php echo esc_attr( $this->get_slug() ); ?>-api_key"><?php esc_html_e( 'API Key', 'wp-mail-smtp' ); ?></label>
			</div>
			<div class="wp-mail-smtp-setting-field">
				<?php if ( $this->options->is_const_defined( $this->get_slug(), 'api_key' ) ) : ?>
					<input type="text" disabled value="****************************************"
						id="wp-mail-smtp-setting-<?php echo esc_attr( $this->get_slug() ); ?>-api_key"
					/>
					<?php $this->display_const_set_message( 'WPMS_SENDINBLUE_API_KEY' ); ?>
				<?php else : ?>
					<input type="password" spellcheck="false"
						name="wp-mail-smtp[<?php echo esc_attr( $this->get_slug() ); ?>][api_key]"
						value="<?php echo esc_attr( $this->options->get( $this->get_slug(), 'api_key' ) ); ?>"
						id="wp-mail-smtp-setting-<?php echo esc_attr( $this->get_slug() ); ?>-api_key"
					/>
				<?php endif; ?>

				<p class="desc">
					<?php
					printf( /* translators: %s - link to get an API Key. */
						esc_html__( 'Follow this link to get an API Key: %s.', 'wp-mail-smtp' ),
						'<a href="https://account.sendinblue.com/advanced/api" target="_blank" rel="noopener noreferrer">' .
						esc_html__( 'Get v3 API Key', 'wp-mail-smtp' ) .
						'</a>'
					);
					?>
				</p>
			</div>
		</div>

		<!-- Sending Domain -->
		<div id="wp-mail-smtp-setting-row-<?php echo esc_attr( $this->get_slug() ); ?>-domain" class="wp-mail-smtp-setting-row wp-mail-smtp-setting-row-text wp-mail-smtp-clear">
			<div class="wp-mail-smtp-setting-label">
				<label for="wp-mail-smtp-setting-<?php echo esc_attr( $this->get_slug() ); ?>-domain"><?php esc_html_e( 'Sending Domain', 'wp-mail-smtp' ); ?></label>
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
						wp_kses(
							/* translators: %s - URL to Sendinblue documentation on wpmailsmtp.com */
							__( 'Please input the sending domain/subdomain you configured in your Sendinblue dashboard. More information can be found in our <a href="%s" target="_blank" rel="noopener noreferrer">Sendinblue documentation</a>.', 'wp-mail-smtp' ),
							[
								'br' => [],
								'a'  => [
									'href'   => [],
									'rel'    => [],
									'target' => [],
								],
							]
						),
						esc_url( wp_mail_smtp()->get_utm_url( 'https://wpmailsmtp.com/docs/how-to-set-up-the-sendinblue-mailer-in-wp-mail-smtp/#setup-smtp', 'Sendinblue documentation' ) )
					);
					?>
				</p>
			</div>
		</div>

		<?php
	}
}
