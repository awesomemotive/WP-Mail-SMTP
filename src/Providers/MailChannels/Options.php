<?php

namespace WPMailSMTP\Providers\MailChannels;

use WPMailSMTP\ConnectionInterface;
use WPMailSMTP\Helpers\UI;
use WPMailSMTP\Providers\OptionsAbstract;

/**
 * MailChannels provider options.
 *
 * @since 4.10.0
 */
class Options extends OptionsAbstract {

	const SLUG = 'mailchannels';

	/**
	 * Options constructor.
	 *
	 * @since 4.10.0
	 *
	 * @param ConnectionInterface $connection The Connection object.
	 */
	public function __construct( $connection = null ) {

		if ( is_null( $connection ) ) {
			$connection = wp_mail_smtp()->get_connections_manager()->get_primary_connection();
		}

		$description = sprintf(
			wp_kses(
				/* translators: %1$s - MailChannels site URL; %2$s - Email API documentation URL. */
				__( '<a href="%1$s" target="_blank" rel="noopener noreferrer">MailChannels</a> sends WordPress email directly through its HTTPS Email API, without relying on a local mail server.<br><br>Review the <a href="%2$s" target="_blank" rel="noopener noreferrer">MailChannels Email API documentation</a> before configuring your sender domain.', 'wp-mail-smtp' ),
				[
					'br' => true,
					'a'  => [
						'href'   => true,
						'rel'    => true,
						'target' => true,
					],
				]
			),
			'https://www.mailchannels.com/email-api/',
			'https://docs.mailchannels.net/email-api'
		);

		parent::__construct(
			[
				'logo_url'    => wp_mail_smtp()->assets_url . '/images/providers/mailchannels.svg',
				'slug'        => self::SLUG,
				'title'       => esc_html__( 'MailChannels', 'wp-mail-smtp' ),
				'php'         => '7.4',
				'description' => $description,
				'supports'    => [
					'from_email'       => true,
					'from_name'        => true,
					'return_path'      => true,
					'from_email_force' => true,
					'from_name_force'  => true,
				],
			],
			$connection
		);
	}

	/**
	 * Output MailChannels settings.
	 *
	 * @since 4.10.0
	 */
	public function display_options() {

		$slug             = $this->get_slug();
		$key_is_constant  = $this->connection_options->is_const_defined( $slug, 'api_key' );
		$mode_is_constant = $this->connection_options->is_const_defined( $slug, 'send_mode' );
		$key_constant     = defined( 'WPMS_ON' ) && WPMS_ON && defined( 'WPMS_MAILCHANNELS_API_KEY' ) && WPMS_MAILCHANNELS_API_KEY ? 'WPMS_MAILCHANNELS_API_KEY' : 'MAILCHANNELS_API_KEY';
		?>

		<!-- API Key -->
		<div id="wp-mail-smtp-setting-row-<?php echo esc_attr( $slug ); ?>-api_key" class="wp-mail-smtp-setting-row wp-mail-smtp-setting-row-text wp-mail-smtp-clear">
			<div class="wp-mail-smtp-setting-label">
				<label for="wp-mail-smtp-setting-<?php echo esc_attr( $slug ); ?>-api_key"><?php esc_html_e( 'API Key', 'wp-mail-smtp' ); ?></label>
			</div>
			<div class="wp-mail-smtp-setting-field">
				<?php if ( $key_is_constant ) : ?>
					<input type="text" disabled value="****************************************" id="wp-mail-smtp-setting-<?php echo esc_attr( $slug ); ?>-api_key" />
					<?php $this->display_const_set_message( $key_constant ); ?>
				<?php else : ?>
					<?php
					UI::hidden_password_field(
						[
							'name'       => "wp-mail-smtp[{$slug}][api_key]",
							'id'         => "wp-mail-smtp-setting-{$slug}-api_key",
							'value'      => $this->connection_options->get( $slug, 'api_key' ),
							'clear_text' => esc_html__( 'Remove API Key', 'wp-mail-smtp' ),
						]
					);
					?>
				<?php endif; ?>
				<p class="desc">
					<?php
					printf(
						/* translators: %s - link to MailChannels Console. */
						esc_html__( 'Create or copy an Email API key in the MailChannels Console: %s.', 'wp-mail-smtp' ),
						'<a href="https://console.mailchannels.net/" target="_blank" rel="noopener noreferrer">' . esc_html__( 'API Keys', 'wp-mail-smtp' ) . '</a>'
					);
					?>
				</p>
			</div>
		</div>

		<!-- Submission Mode -->
		<div id="wp-mail-smtp-setting-row-<?php echo esc_attr( $slug ); ?>-send_mode" class="wp-mail-smtp-setting-row wp-mail-smtp-setting-row-radio wp-mail-smtp-clear">
			<div class="wp-mail-smtp-setting-label">
				<label><?php esc_html_e( 'Submission Mode', 'wp-mail-smtp' ); ?></label>
			</div>
			<div class="wp-mail-smtp-setting-field">
				<label for="wp-mail-smtp-setting-<?php echo esc_attr( $slug ); ?>-send_mode-direct">
					<input type="radio" id="wp-mail-smtp-setting-<?php echo esc_attr( $slug ); ?>-send_mode-direct" name="wp-mail-smtp[<?php echo esc_attr( $slug ); ?>][send_mode]" value="direct" <?php disabled( $mode_is_constant ); ?> <?php checked( 'direct', $this->connection_options->get( $slug, 'send_mode' ) ); ?> />
					<?php esc_html_e( 'Direct', 'wp-mail-smtp' ); ?>
				</label>
				<label for="wp-mail-smtp-setting-<?php echo esc_attr( $slug ); ?>-send_mode-queued">
					<input type="radio" id="wp-mail-smtp-setting-<?php echo esc_attr( $slug ); ?>-send_mode-queued" name="wp-mail-smtp[<?php echo esc_attr( $slug ); ?>][send_mode]" value="queued" <?php disabled( $mode_is_constant ); ?> <?php checked( 'queued', $this->connection_options->get( $slug, 'send_mode' ) ); ?> />
					<?php esc_html_e( 'Queued', 'wp-mail-smtp' ); ?>
				</label>
				<p class="desc"><?php esc_html_e( 'Direct mode waits for per-message acceptance results. Queued mode returns after MailChannels accepts the request. Neither status is final delivery.', 'wp-mail-smtp' ); ?></p>
				<?php if ( $mode_is_constant ) : ?>
					<?php $this->display_const_set_message( 'WPMS_MAILCHANNELS_SEND_MODE' ); ?>
				<?php endif; ?>
			</div>
		</div>

		<?php
	}
}
