<?php

namespace WPMailSMTP\Providers\Mailtrap;

use WPMailSMTP\ConnectionInterface;
use WPMailSMTP\Helpers\UI;
use WPMailSMTP\Providers\OptionsAbstract;

/**
 * Class Options.
 *
 */
class Options extends OptionsAbstract {

	/**
	 * Mailer slug.
	 *
	 * @var string
	 */
	const SLUG = 'mailtrap';

	/**
	 * Options constructor.
	 *
	 * @param ConnectionInterface $connection The Connection object.
	 */
	public function __construct( $connection = null ) {

		if ( is_null( $connection ) ) {
			$connection = wp_mail_smtp()->get_connections_manager()->get_primary_connection();
		}

		$description = sprintf(
			wp_kses(
			/* translators: %1$s - URL to mailtrap.io; %2$s - URL to Mailtrap documentation on mailtrap.io. */
				__( '<strong><a href="%1$s" target="_blank" rel="noopener noreferrer">Mailtrap</a></strong> is an Email Delivery Platform designed for product companies with high sending volumes. <br>Send transactional emails with RESTful API or SMTP or marketing emails with Campaigns, and test your emails with Email Sandbox before sending them.', 'wp-mail-smtp' ),
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
			esc_url( wp_mail_smtp()->get_utm_url( 'https://mailtrap.io/blog/wordpress-send-email/', [ 'source' => 'wpmailsmtpplugin', 'medium' => 'WordPress', 'content' => isset( $_GET['page'] ) && $_GET['page'] === 'wp-mail-smtp-setup-wizard' ? 'Setup Wizard - Mailer Description' : 'Plugin Settings - Mailer Description' ] ) ),
			// phpcs:ignore WordPress.Arrays.ArrayDeclarationSpacing.AssociativeArrayFound
			esc_url( wp_mail_smtp()->get_utm_url( 'https://wpmailsmtp.com/docs/how-to-set-up-the-mailtrap-mailer-in-wp-mail-smtp/', 'SendLayer Documentation' ) )
		);

		$mailer_options = $connection->get_options()->get_group( self::SLUG );

		if ( empty( $mailer_options['api_key'] ) ) {
			$description .= sprintf(
				'</p><p class="buttonned"><a href="%1$s" target="_blank" rel="noopener noreferrer" class="wp-mail-smtp-btn wp-mail-smtp-btn-md wp-mail-smtp-btn-blueish">%2$s</a></p>',
				// phpcs:ignore WordPress.Arrays.ArrayDeclarationSpacing.AssociativeArrayFound
				esc_url( wp_mail_smtp()->get_utm_url( 'https://mailtrap.io/blog/wordpress-send-email/', [ 'source' => 'wpmailsmtpplugin', 'medium' => 'WordPress', 'content' => 'Plugin Settings - Mailer Button' ] ) ),
				esc_html__( 'Get Started with Mailtrap', 'wp-mail-smtp' )
			);
		}

		parent::__construct(
			[
				'logo_url'    => wp_mail_smtp()->assets_url . '/images/providers/mailtrap.svg',
				'slug'        => self::SLUG,
				'title'       => esc_html__( 'Mailtrap', 'wp-mail-smtp' ),
				'description' => $description,
				'recommended' => true,
				'supports'    => [
					'from_email'       => true,
					'from_name'        => true,
					'return_path'      => false,
					'from_email_force' => true,
					'from_name_force'  => true,
				],
			],
			$connection
		);
	}

	/**
	 * Output the mailer provider options.
	 * 
	 */
	public function display_options() {

		// phpcs:ignore WordPress.Arrays.ArrayDeclarationSpacing.AssociativeArrayFound, WordPress.Security.NonceVerification.Recommended
		$get_api_key_url = wp_mail_smtp()->get_utm_url( 'https://help.mailtrap.io/article/103-api-tokens', [ 'source' => 'wpmailsmtpplugin', 'medium' => 'WordPress', 'content' => 'Plugin Settings - Get API Key' ] );
		?>

		<!-- API Key -->
		<div id="wp-mail-smtp-setting-row-<?php echo esc_attr( $this->get_slug() ); ?>-api_key" class="wp-mail-smtp-setting-row wp-mail-smtp-setting-row-text wp-mail-smtp-clear">
			<div class="wp-mail-smtp-setting-label">
				<label for="wp-mail-smtp-setting-<?php echo esc_attr( $this->get_slug() ); ?>-api_key"><?php esc_html_e( 'API Key', 'wp-mail-smtp' ); ?></label>
			</div>
			<div class="wp-mail-smtp-setting-field">
				<?php if ( $this->connection_options->is_const_defined( $this->get_slug(), 'api_key' ) ) : ?>
					<input type="text" disabled value="****************************************"
						id="wp-mail-smtp-setting-<?php echo esc_attr( $this->get_slug() ); ?>-api_key"
					/>
					<?php $this->display_const_set_message( 'WPMS_MAILTRAP_API_KEY' ); ?>
				<?php else : ?>
					<?php
					$slug  = $this->get_slug();
					$value = $this->connection_options->get( $this->get_slug(), 'api_key' );

					UI::hidden_password_field(
						[
							'name'       => "wp-mail-smtp[{$slug}][api_key]",
							'id'         => "wp-mail-smtp-setting-{$slug}-api_key",
							'value'      => $value,
							'clear_text' => esc_html__( 'Remove API Key', 'wp-mail-smtp' ),
						]
					);
					?>
				<?php endif; ?>
				<p class="desc">
					<?php
					printf(
						/* translators: %s - API key link. */
						esc_html__( 'Follow this link to get an API Key from Mailtrap: %s.', 'wp-mail-smtp' ),
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
