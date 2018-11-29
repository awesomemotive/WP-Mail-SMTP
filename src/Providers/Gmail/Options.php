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
	 * Mailgun constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		parent::__construct(
			array(
				'logo_url'    => wp_mail_smtp()->plugin_url . '/assets/images/gmail.png',
				'slug'        => 'gmail',
				'title'       => esc_html__( 'Gmail', 'wp-mail-smtp' ),
				'description' => sprintf(
					wp_kses(
						/* translators: %1$s - opening link tag; %2$s - closing link tag. */
						__( 'Send emails using your Gmail or G Suite (formerly Google Apps) account, all while keeping your login credentials safe. Other Google SMTP methods require enabling less secure apps in your account and entering your password. However, this integration uses the Google API to improve email delivery issues while keeping your site secure.<br><br>Read our %1$sGmail documentation%2$s to learn how to configure Gmail or G Suite.', 'wp-mail-smtp' ),
						array(
							'br' => array(),
							'a'  => array(
								'href'   => array(),
								'rel'    => array(),
								'target' => array(),
							),
						)
					),
					'<a href="https://wpforms.com/how-to-securely-send-wordpress-emails-using-gmail-smtp/" target="_blank" rel="noopener noreferrer">',
					'</a>'
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
				<input name="wp-mail-smtp[<?php echo esc_attr( $this->get_slug() ); ?>][client_secret]" type="text"
					value="<?php echo esc_attr( $this->options->get( $this->get_slug(), 'client_secret' ) ); ?>"
					<?php echo $this->options->is_const_defined( $this->get_slug(), 'client_secret' ) ? 'disabled' : ''; ?>
					id="wp-mail-smtp-setting-<?php echo esc_attr( $this->get_slug() ); ?>-client_secret" spellcheck="false"
				/>
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
					<?php esc_html_e( 'This is the path on your site that you will be redirected to after you have authenticated with Google.', 'wp-mail-smtp' ); ?>
					<br>
					<?php esc_html_e( 'You need to copy this URL into "Authorized redirect URIs" field for you web application on Google APIs site for your project there.', 'wp-mail-smtp' ); ?>
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
		$this->process_gmail_remove();

		$auth = new Auth();
		?>

		<script>
			var wp_mail_smtp = window.wp_mail_smtp || {};
			wp_mail_smtp.text_gmail_remove = "<?php esc_html_e( 'Are you sure you want to reset the current Gmail connection? You will need to immediately create a new one to be able to send emails.', 'wp-mail-smtp' ); ?>";
		</script>

		<?php if ( $auth->is_clients_saved() ) : ?>

			<?php if ( $auth->is_auth_required() ) : ?>

				<a href="<?php echo esc_url( $auth->get_google_auth_url() ); ?>" class="wp-mail-smtp-btn wp-mail-smtp-btn-md wp-mail-smtp-btn-orange">
					<?php esc_html_e( 'Allow plugin to send emails using your Google account', 'wp-mail-smtp' ); ?>
				</a>
				<p class="desc">
					<?php esc_html_e( 'Click the button above to confirm authorization.', 'wp-mail-smtp' ); ?>
				</p>

			<?php else : ?>

				<a href="<?php echo esc_url( wp_nonce_url( wp_mail_smtp()->get_admin()->get_admin_page_url(), 'gmail_remove', 'gmail_remove_nonce' ) ); ?>#wp-mail-smtp-setting-row-gmail-authorize" class="wp-mail-smtp-btn wp-mail-smtp-btn-md wp-mail-smtp-btn-red" id="wp-mail-smtp-gmail-remove">
					<?php esc_html_e( 'Remove Connection', 'wp-mail-smtp' ); ?>
				</a>
				<p class="desc">
					<?php esc_html_e( 'Removing the connection will give you an ability to redo the connection or link to another Google account.', 'wp-mail-smtp' ); ?>
				</p>

			<?php endif; ?>

		<?php else : ?>

			<p>
				<?php esc_html_e( 'To setup Gmail integration properly you should save Client ID and Client Secret.', 'wp-mail-smtp' ); ?>
			</p>

		<?php
		endif;
	}

	/**
	 * Remove Gmail connection.
	 *
	 * @since 1.3.0
	 */
	public function process_gmail_remove() {

		if ( ! is_super_admin() ) {
			return;
		}

		if (
			! isset( $_GET['gmail_remove_nonce'] ) ||
			! wp_verify_nonce( $_GET['gmail_remove_nonce'], 'gmail_remove' )
		) {
			return;
		}

		$options = new \WPMailSMTP\Options();
		$old_opt = $options->get_all();

		if ( $options->get( 'mail', 'mailer' ) === 'gmail' ) {
			foreach ( $old_opt['gmail'] as $key => $value ) {
				if ( ! in_array( $key, array( 'client_id', 'client_secret' ), true ) ) {
					unset( $old_opt['gmail'][ $key ] );
				}
			}
		}

		$options->set( $old_opt );
	}
}
