<?php

namespace WPMailSMTP\Providers\Gmail;

use WPMailSMTP\Providers\OptionsAbstract;

/**
 * Class Option.
 *
 * @since 1.0.0
 */
class Options extends OptionsAbstract {

	/**
	 * Mailer slug.
	 *
	 * @since 1.5.0
	 */
	const SLUG = 'gmail';

	/**
	 * Gmail Options constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		parent::__construct(
			array(
				'logo_url'    => wp_mail_smtp()->assets_url . '/images/providers/google.svg',
				'slug'        => self::SLUG,
				'title'       => esc_html__( 'Gmail', 'wp-mail-smtp' ),
				'description' => sprintf(
					wp_kses( /* translators: %s - URL to our Gmail doc. */
						__( 'Send emails using your Gmail or G Suite (formerly Google Apps) account, all while keeping your login credentials safe. Other Google SMTP methods require enabling less secure apps in your account and entering your password. However, this integration uses the Google API to improve email delivery issues while keeping your site secure.<br><br>Read our <a href="%s" target="_blank" rel="noopener noreferrer">Gmail documentation</a> to learn how to configure Gmail or G Suite.', 'wp-mail-smtp' ),
						array(
							'br' => array(),
							'a'  => array(
								'href'   => array(),
								'rel'    => array(),
								'target' => array(),
							),
						)
					),
					'https://wpmailsmtp.com/docs/how-to-set-up-the-gmail-mailer-in-wp-mail-smtp/'
				),
				'notices'     => array(
					'educational' => esc_html__( 'The Gmail mailer works well for sites that send low numbers of emails. However, Gmail\'s API has rate limitations and a number of additional restrictions that can lead to challenges during setup. If you expect to send a high volume of emails, or if you find that your web host is not compatible with the Gmail API restrictions, then we recommend considering a different mailer option.', 'wp-mail-smtp' ),
				),
				'php'         => '5.5',
			)
		);
	}

	/**
	 * @inheritdoc
	 */
	public function display_options() {

		// Do not display options if PHP version is not correct.
		if ( ! $this->is_php_correct() ) {
			$this->display_php_warning();

			return;
		}
		?>

		<!-- Client ID -->
		<div id="wp-mail-smtp-setting-row-<?php echo esc_attr( $this->get_slug() ); ?>-client_id"
			class="wp-mail-smtp-setting-row wp-mail-smtp-setting-row-text wp-mail-smtp-clear">
			<div class="wp-mail-smtp-setting-label">
				<label for="wp-mail-smtp-setting-<?php echo esc_attr( $this->get_slug() ); ?>-client_id"><?php esc_html_e( 'Client ID', 'wp-mail-smtp' ); ?></label>
			</div>
			<div class="wp-mail-smtp-setting-field">
				<input name="wp-mail-smtp[<?php echo esc_attr( $this->get_slug() ); ?>][client_id]" type="text"
					value="<?php echo esc_attr( $this->options->get( $this->get_slug(), 'client_id' ) ); ?>"
					<?php echo $this->options->is_const_defined( $this->get_slug(), 'client_id' ) ? 'disabled' : ''; ?>
					id="wp-mail-smtp-setting-<?php echo esc_attr( $this->get_slug() ); ?>-client_id" spellcheck="false"
				/>
			</div>
		</div>

		<!-- Client Secret -->
		<div id="wp-mail-smtp-setting-row-<?php echo esc_attr( $this->get_slug() ); ?>-client_secret"
			class="wp-mail-smtp-setting-row wp-mail-smtp-setting-row-text wp-mail-smtp-clear">
			<div class="wp-mail-smtp-setting-label">
				<label for="wp-mail-smtp-setting-<?php echo esc_attr( $this->get_slug() ); ?>-client_secret"><?php esc_html_e( 'Client Secret', 'wp-mail-smtp' ); ?></label>
			</div>
			<div class="wp-mail-smtp-setting-field">
				<?php if ( $this->options->is_const_defined( $this->get_slug(), 'client_secret' ) ) : ?>
					<input type="text" disabled value="****************************************"
						id="wp-mail-smtp-setting-<?php echo esc_attr( $this->get_slug() ); ?>-client_secret"
					/>
					<?php $this->display_const_set_message( 'WPMS_GMAIL_CLIENT_SECRET' ); ?>
				<?php else : ?>
					<input type="password" spellcheck="false"
						name="wp-mail-smtp[<?php echo esc_attr( $this->get_slug() ); ?>][client_secret]"
						value="<?php echo esc_attr( $this->options->get( $this->get_slug(), 'client_secret' ) ); ?>"
						id="wp-mail-smtp-setting-<?php echo esc_attr( $this->get_slug() ); ?>-client_secret"
					/>
				<?php endif; ?>
			</div>
		</div>

		<!-- Authorized redirect URI -->
		<div id="wp-mail-smtp-setting-row-<?php echo esc_attr( $this->get_slug() ); ?>-client_redirect"
			class="wp-mail-smtp-setting-row wp-mail-smtp-setting-row-text wp-mail-smtp-clear">
			<div class="wp-mail-smtp-setting-label">
				<label for="wp-mail-smtp-setting-<?php echo esc_attr( $this->get_slug() ); ?>-client_redirect"><?php esc_html_e( 'Authorized redirect URI', 'wp-mail-smtp' ); ?></label>
			</div>
			<div class="wp-mail-smtp-setting-field">
				<input type="text" readonly="readonly"
					value="<?php echo esc_attr( Auth::get_plugin_auth_url() ); ?>"
					id="wp-mail-smtp-setting-<?php echo esc_attr( $this->get_slug() ); ?>-client_redirect"
				/>
				<button type="button" class="wp-mail-smtp-btn wp-mail-smtp-btn-md wp-mail-smtp-btn-light-grey wp-mail-smtp-setting-copy"
					title="<?php esc_attr_e( 'Copy URL to clipboard', 'wp-mail-smtp' ); ?>"
					data-source_id="wp-mail-smtp-setting-<?php echo esc_attr( $this->get_slug() ); ?>-client_redirect">
					<span class="dashicons dashicons-admin-page"></span>
				</button>
				<p class="desc">
					<?php esc_html_e( 'Please copy this URL into the "Authorized redirect URIs" field of your Google web application.', 'wp-mail-smtp' ); ?>
				</p>
			</div>
		</div>

		<!-- Auth users button -->
		<div id="wp-mail-smtp-setting-row-<?php echo esc_attr( $this->get_slug() ); ?>-authorize"
			class="wp-mail-smtp-setting-row wp-mail-smtp-setting-row-text wp-mail-smtp-clear">
			<div class="wp-mail-smtp-setting-label">
				<label><?php esc_html_e( 'Authorization', 'wp-mail-smtp' ); ?></label>
			</div>
			<div class="wp-mail-smtp-setting-field">
				<?php $this->display_auth_setting_action(); ?>
			</div>
		</div>

		<?php
	}

	/**
	 * Display either an "Allow..." or "Remove..." button.
	 *
	 * @since 1.3.0
	 */
	protected function display_auth_setting_action() {

		// Do the processing on the fly, as having ajax here is too complicated.
		$this->process_provider_remove();

		$auth = new Auth();
		?>

		<?php if ( $auth->is_clients_saved() ) : ?>

			<?php if ( $auth->is_auth_required() ) : ?>

				<a href="<?php echo esc_url( $auth->get_auth_url() ); ?>" class="wp-mail-smtp-btn wp-mail-smtp-btn-md wp-mail-smtp-btn-orange">
					<?php esc_html_e( 'Allow plugin to send emails using your Google account', 'wp-mail-smtp' ); ?>
				</a>
				<p class="desc">
					<?php esc_html_e( 'Click the button above to confirm authorization.', 'wp-mail-smtp' ); ?>
				</p>

			<?php else : ?>

				<a href="<?php echo esc_url( wp_nonce_url( wp_mail_smtp()->get_admin()->get_admin_page_url(), 'gmail_remove', 'gmail_remove_nonce' ) ); ?>#wp-mail-smtp-setting-row-<?php echo esc_attr( $this->get_slug() ); ?>-authorize" class="wp-mail-smtp-btn wp-mail-smtp-btn-md wp-mail-smtp-btn-red js-wp-mail-smtp-provider-remove">
					<?php esc_html_e( 'Remove Connection', 'wp-mail-smtp' ); ?>
				</a>
				<span class="connected-as">
					<?php
					$user = $auth->get_user_info();

					if ( ! empty( $user['email'] ) ) {
						printf(
							/* translators: %s - email address, as received from Google API. */
							esc_html__( 'Connected as %s', 'wp-mail-smtp' ),
							'<code>' . esc_html( $user['email'] ) . '</code>'
						);
					}
					?>
				</span>
				<p class="desc">
					<?php
					printf(
						wp_kses( /* translators: %s - URL to Google Gmail alias documentation page. */
							__( 'If you want to use a different From Email address you can set-up a Google email alias. <a href="%s" target="_blank" rel="noopener noreferrer">Follow these instructions</a> and then select the From Email at the top of this page.', 'wp-mail-smtp' ),
							[
								'a' => [
									'href'   => [],
									'rel'    => [],
									'target' => [],
								],
							]
						),
						'https://support.google.com/a/answer/33327'
					);
					?>
				</p>
				<p class="desc">
					<?php esc_html_e( 'Removing the connection will give you an ability to redo the connection or link to another Google account.', 'wp-mail-smtp' ); ?>
				</p>

			<?php endif; ?>

		<?php else : ?>

			<p class="inline-notice inline-error">
				<?php esc_html_e( 'You need to save settings with Client ID and Client Secret before you can proceed.', 'wp-mail-smtp' ); ?>
			</p>

		<?php
		endif;
	}

	/**
	 * Remove Provider connection.
	 *
	 * @since 1.3.0
	 */
	public function process_provider_remove() {

		if ( ! is_super_admin() ) {
			return;
		}

		if (
			! isset( $_GET['gmail_remove_nonce'] ) ||
			! wp_verify_nonce( $_GET['gmail_remove_nonce'], 'gmail_remove' ) // phpcs:ignore
		) {
			return;
		}

		$options = new \WPMailSMTP\Options();

		if ( $options->get( 'mail', 'mailer' ) !== $this->get_slug() ) {
			return;
		}

		$old_opt = $options->get_all();

		foreach ( $old_opt[ $this->get_slug() ] as $key => $value ) {
			// Unset everything except Client ID and Secret.
			if ( ! in_array( $key, array( 'client_id', 'client_secret' ), true ) ) {
				unset( $old_opt[ $this->get_slug() ][ $key ] );
			}
		}

		$options->set( $old_opt );
	}
}
