<?php

namespace WPMailSMTP\Providers\Sendlayer;

use WPMailSMTP\Options as PluginOptions;
use WPMailSMTP\Providers\OptionsAbstract;

/**
 * Class Options.
 *
 * @since 3.4.0
 */
class Options extends OptionsAbstract {

	/**
	 * Mailer slug.
	 *
	 * @since 3.4.0
	 *
	 * @var string
	 */
	const SLUG = 'sendlayer';

	/**
	 * Options constructor.
	 *
	 * @since 3.4.0
	 */
	public function __construct() {

		$description = sprintf(
			wp_kses(
			/* translators: %1$s - URL to sendlayer.com; %2$s - URL to SendLayer documentation on wpmailsmtp.com. */
				__( '<strong><a href="%1$s" target="_blank" rel="noopener noreferrer">SendLayer</a> is our #1 recommended mailer.</strong> Its affordable pricing and simple setup make it the perfect choice for WordPress sites. SendLayer will authenticate your outgoing emails to make sure they always hit customers’ inboxes, and it has detailed documentation to help you authorize your domain.<br><br>You can send hundreds of emails for free when you sign up for a trial.<br><br>To get started, read our <a href="%2$s" target="_blank" rel="noopener noreferrer">SendLayer documentation</a>.', 'wp-mail-smtp' ),
				[
					'strong' => [],
					'br'     => [],
					'a'      => [
						'href'   => [],
						'rel'    => [],
						'target' => [],
					],
				]
			),
			// phpcs:ignore WordPress.Arrays.ArrayDeclarationSpacing.AssociativeArrayFound, WordPress.Security.NonceVerification.Recommended
			esc_url( wp_mail_smtp()->get_utm_url( 'https://sendlayer.com/wp-mail-smtp/', [ 'source' => 'wpmailsmtpplugin', 'medium' => 'WordPress', 'content' => isset( $_GET['page'] ) && $_GET['page'] === 'wp-mail-smtp-setup-wizard' ? 'Setup Wizard - Mailer Description' : 'Plugin Settings - Mailer Description' ] ) ),
			// phpcs:ignore WordPress.Arrays.ArrayDeclarationSpacing.AssociativeArrayFound
			esc_url( wp_mail_smtp()->get_utm_url( 'https://wpmailsmtp.com/docs/how-to-set-up-the-sendlayer-mailer-in-wp-mail-smtp/', 'SendLayer Documentation' ) )
		);

		$mailer_options = PluginOptions::init()->get_group( self::SLUG );

		if ( empty( $mailer_options['api_key'] ) ) {
			$description .= sprintf(
				'</p><p class="buttonned"><a href="%1$s" target="_blank" rel="noopener noreferrer" class="wp-mail-smtp-btn wp-mail-smtp-btn-md wp-mail-smtp-btn-blueish">%2$s</a></p>',
				// phpcs:ignore WordPress.Arrays.ArrayDeclarationSpacing.AssociativeArrayFound
				esc_url( wp_mail_smtp()->get_utm_url( 'https://sendlayer.com/wp-mail-smtp/', [ 'source' => 'wpmailsmtpplugin', 'medium' => 'WordPress', 'content' => 'Plugin Settings - Mailer Button' ] ) ),
				esc_html__( 'Get Started with SendLayer', 'wp-mail-smtp' )
			);
		}

		parent::__construct(
			[
				'logo_url'    => wp_mail_smtp()->assets_url . '/images/providers/sendlayer.svg',
				'slug'        => self::SLUG,
				'title'       => esc_html__( 'SendLayer', 'wp-mail-smtp' ),
				'description' => $description,
				'recommended' => true,
			]
		);
	}

	/**
	 * Output the mailer provider options.
	 *
	 * @since 3.4.0
	 */
	public function display_options() {

		// phpcs:ignore WordPress.Arrays.ArrayDeclarationSpacing.AssociativeArrayFound, WordPress.Security.NonceVerification.Recommended
		$get_api_key_url = wp_mail_smtp()->get_utm_url( 'https://app.sendlayer.com/settings/api/', [ 'source' => 'wpmailsmtpplugin', 'medium' => 'WordPress', 'content' => 'Plugin Settings - Get API Key' ] );
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
					<?php $this->display_const_set_message( 'WPMS_SENDLAYER_API_KEY' ); ?>
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
						esc_html__( 'Follow this link to get an API Key from SendLayer: %s.', 'wp-mail-smtp' ),
						'<a href="' . esc_url( $get_api_key_url ) . '" target="_blank" rel="noopener noreferrer">' .
						esc_html__( 'Get API Key', 'wp-mail-smtp' ) .
						'</a>'
					);
					?>
				</p>
			</div>
		</div>
		<?php
	}
}
