<?php

namespace WPMailSMTP\Providers;

use WPMailSMTP\Options;

/**
 * Abstract Class ProviderAbstract to contain common providers functionality.
 *
 * @since 1.0.0
 */
abstract class OptionsAbstract implements OptionsInterface {

	/**
	 * @var string
	 */
	private $logo_url = '';
	/**
	 * @var string
	 */
	private $slug = '';
	/**
	 * @var string
	 */
	private $title = '';
	/**
	 * @var string
	 */
	private $description = '';
	/**
	 * @var string
	 */
	private $php = WPMS_PHP_VER;
	/**
	 * @var Options
	 */
	protected $options;

	/**
	 * ProviderAbstract constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param array $params
	 */
	public function __construct( $params ) {

		if (
			empty( $params['slug'] ) ||
			empty( $params['title'] )
		) {
			return;
		}

		$this->slug  = sanitize_key( $params['slug'] );
		$this->title = sanitize_text_field( $params['title'] );

		if ( ! empty( $params['description'] ) ) {
			$this->description = wp_kses( $params['description'],
				array(
					'br' => array(),
					'a'  => array(
						'href'   => array(),
						'rel'    => array(),
						'target' => array(),
					),
				)
			);
		}

		if ( ! empty( $params['php'] ) ) {
			$this->php = sanitize_text_field( $params['php'] );
		}

		if ( ! empty( $params['logo_url'] ) ) {
			$this->logo_url = esc_url_raw( $params['logo_url'] );
		}

		$this->options = new Options();
	}

	/**
	 * @inheritdoc
	 */
	public function get_logo_url() {
		return apply_filters( 'wp_mail_smtp_providers_provider_get_logo_url', $this->logo_url, $this );
	}

	/**
	 * @inheritdoc
	 */
	public function get_slug() {
		return apply_filters( 'wp_mail_smtp_providers_provider_get_slug', $this->slug, $this );
	}

	/**
	 * @inheritdoc
	 */
	public function get_title() {
		return apply_filters( 'wp_mail_smtp_providers_provider_get_title', $this->title, $this );
	}

	/**
	 * @inheritdoc
	 */
	public function get_description() {
		return apply_filters( 'wp_mail_smtp_providers_provider_get_description', $this->description, $this );
	}

	/**
	 * @inheritdoc
	 */
	public function get_php_version() {
		return apply_filters( 'wp_mail_smtp_providers_provider_get_php_version', $this->php, $this );
	}

	/**
	 * @inheritdoc
	 */
	public function display_options() {
		?>

		<!-- SMTP Host -->
		<div id="wp-mail-smtp-setting-row-<?php echo esc_attr( $this->get_slug() ); ?>-host" class="wp-mail-smtp-setting-row wp-mail-smtp-setting-row-text wp-mail-smtp-clear">
			<div class="wp-mail-smtp-setting-label">
				<label for="wp-mail-smtp-setting-<?php echo esc_attr( $this->get_slug() ); ?>-host"><?php esc_html_e( 'SMTP Host', 'wp-mail-smtp' ); ?></label>
			</div>
			<div class="wp-mail-smtp-setting-field">
				<input name="wp-mail-smtp[<?php echo esc_attr( $this->get_slug() ); ?>][host]" type="text"
					value="<?php echo esc_attr( $this->options->get( $this->get_slug(), 'host' ) ); ?>"
					<?php echo $this->options->is_const_defined( $this->get_slug(), 'host' ) ? 'disabled' : ''; ?>
					id="wp-mail-smtp-setting-<?php echo esc_attr( $this->get_slug() ); ?>-host" spellcheck="false"
				/>
			</div>
		</div>

		<!-- SMTP Encryption -->
		<div id="wp-mail-smtp-setting-row-<?php echo esc_attr( $this->get_slug() ); ?>-encryption" class="wp-mail-smtp-setting-row wp-mail-smtp-setting-row-radio wp-mail-smtp-clear">
			<div class="wp-mail-smtp-setting-label">
				<label><?php esc_html_e( 'Encryption', 'wp-mail-smtp' ); ?></label>
			</div>
			<div class="wp-mail-smtp-setting-field">

				<label for="wp-mail-smtp-setting-<?php echo esc_attr( $this->get_slug() ); ?>-enc-none">
					<input type="radio" id="wp-mail-smtp-setting-<?php echo esc_attr( $this->get_slug() ); ?>-enc-none"
						name="wp-mail-smtp[<?php echo esc_attr( $this->get_slug() ); ?>][encryption]" value="none"
						<?php echo $this->options->is_const_defined( $this->get_slug(), 'encryption' ) ? 'disabled' : ''; ?>
						<?php checked( 'none', $this->options->get( $this->get_slug(), 'encryption' ) ); ?>
					/>
					<?php esc_html_e( 'None', 'wp-mail-smtp' ); ?>
				</label>

				<label for="wp-mail-smtp-setting-<?php echo esc_attr( $this->get_slug() ); ?>-enc-ssl">
					<input type="radio" id="wp-mail-smtp-setting-<?php echo esc_attr( $this->get_slug() ); ?>-enc-ssl"
						name="wp-mail-smtp[<?php echo esc_attr( $this->get_slug() ); ?>][encryption]" value="ssl"
						<?php echo $this->options->is_const_defined( $this->get_slug(), 'encryption' ) ? 'disabled' : ''; ?>
						<?php checked( 'ssl', $this->options->get( $this->get_slug(), 'encryption' ) ); ?>
					/>
					<?php esc_html_e( 'SSL', 'wp-mail-smtp' ); ?>
				</label>

				<label for="wp-mail-smtp-setting-<?php echo esc_attr( $this->get_slug() ); ?>-enc-tls">
					<input type="radio" id="wp-mail-smtp-setting-<?php echo esc_attr( $this->get_slug() ); ?>-enc-tls"
						name="wp-mail-smtp[<?php echo esc_attr( $this->get_slug() ); ?>][encryption]" value="tls"
						<?php echo $this->options->is_const_defined( $this->get_slug(), 'encryption' ) ? 'disabled' : ''; ?>
						<?php checked( 'tls', $this->options->get( $this->get_slug(), 'encryption' ) ); ?>
					/>
					<?php esc_html_e( 'TLS', 'wp-mail-smtp' ); ?>
				</label>

				<p class="desc">
					<?php esc_html_e( 'For most servers TLS is the recommended option. If your SMTP provider offers both SSL and TLS options, we recommend using TLS.', 'wp-mail-smtp' ); ?>
				</p>
			</div>
		</div>

		<!-- SMTP Port -->
		<div id="wp-mail-smtp-setting-row-<?php echo esc_attr( $this->get_slug() ); ?>-port" class="wp-mail-smtp-setting-row wp-mail-smtp-setting-row-number wp-mail-smtp-clear">
			<div class="wp-mail-smtp-setting-label">
				<label for="wp-mail-smtp-setting-<?php echo esc_attr( $this->get_slug() ); ?>-port"><?php esc_html_e( 'SMTP Port', 'wp-mail-smtp' ); ?></label>
			</div>
			<div class="wp-mail-smtp-setting-field">
				<input name="wp-mail-smtp[<?php echo esc_attr( $this->get_slug() ); ?>][port]" type="number"
					value="<?php echo esc_attr( $this->options->get( $this->get_slug(), 'port' ) ); ?>"
					<?php echo $this->options->is_const_defined( $this->get_slug(), 'port' ) ? 'disabled' : ''; ?>
					id="wp-mail-smtp-setting-<?php echo esc_attr( $this->get_slug() ); ?>-port" class="small-text" spellcheck="false"
				/>
			</div>
		</div>

		<!-- PHPMailer SMTPAutoTLS -->
		<div id="wp-mail-smtp-setting-row-<?php echo esc_attr( $this->get_slug() ); ?>-autotls" class="wp-mail-smtp-setting-row wp-mail-smtp-setting-row-checkbox-toggle wp-mail-smtp-clear <?php echo $this->options->is_const_defined( $this->get_slug(), 'encryption' ) || 'tls' === $this->options->get( $this->get_slug(), 'encryption' ) ? 'inactive' : ''; ?>">
			<div class="wp-mail-smtp-setting-label">
				<label for="wp-mail-smtp-setting-<?php echo esc_attr( $this->get_slug() ); ?>-autotls"><?php esc_html_e( 'Auto TLS', 'wp-mail-smtp' ); ?></label>
			</div>
			<div class="wp-mail-smtp-setting-field">
				<label for="wp-mail-smtp-setting-<?php echo esc_attr( $this->get_slug() ); ?>-autotls">
					<input type="checkbox" id="wp-mail-smtp-setting-<?php echo esc_attr( $this->get_slug() ); ?>-autotls"
						name="wp-mail-smtp[<?php echo esc_attr( $this->get_slug() ); ?>][autotls]" value="yes"
						<?php echo $this->options->is_const_defined( $this->get_slug(), 'autotls' ) ? 'disabled' : ''; ?>
						<?php checked( true, (bool) $this->options->get( $this->get_slug(), 'autotls' ) ); ?>
					/>
					<span class="wp-mail-smtp-setting-toggle-switch"></span>
					<span class="wp-mail-smtp-setting-toggle-checked-label"><?php esc_html_e( 'On', 'wp-mail-smtp' ); ?></span>
					<span class="wp-mail-smtp-setting-toggle-unchecked-label"><?php esc_html_e( 'Off', 'wp-mail-smtp' ); ?></span>
				</label>
				<p class="desc">
					<?php esc_html_e( 'By default TLS encryption is automatically used if the server supports it, which is recommended. In some cases, due to server misconfigurations, this can cause issues and may need to be disabled.', 'wp-mail-smtp' ); ?>
				</p>
			</div>
		</div>

		<!-- SMTP Authentication -->
		<div id="wp-mail-smtp-setting-row-<?php echo esc_attr( $this->get_slug() ); ?>-auth" class="wp-mail-smtp-setting-row wp-mail-smtp-setting-row-checkbox-toggle wp-mail-smtp-clear">
			<div class="wp-mail-smtp-setting-label">
				<label for="wp-mail-smtp-setting-<?php echo esc_attr( $this->get_slug() ); ?>-auth"><?php esc_html_e( 'Authentication', 'wp-mail-smtp' ); ?></label>
			</div>
			<div class="wp-mail-smtp-setting-field">
				<label for="wp-mail-smtp-setting-<?php echo esc_attr( $this->get_slug() ); ?>-auth">
					<input type="checkbox" id="wp-mail-smtp-setting-<?php echo esc_attr( $this->get_slug() ); ?>-auth"
						name="wp-mail-smtp[<?php echo esc_attr( $this->get_slug() ); ?>][auth]" value="yes"
						<?php echo $this->options->is_const_defined( $this->get_slug(), 'auth' ) ? 'disabled' : ''; ?>
						<?php checked( true, (bool) $this->options->get( $this->get_slug(), 'auth' ) ); ?>
					/>
					<span class="wp-mail-smtp-setting-toggle-switch"></span>
					<span class="wp-mail-smtp-setting-toggle-checked-label"><?php esc_html_e( 'On', 'wp-mail-smtp' ); ?></span>
					<span class="wp-mail-smtp-setting-toggle-unchecked-label"><?php esc_html_e( 'Off', 'wp-mail-smtp' ); ?></span>
				</label>
			</div>
		</div>

		<!-- SMTP Username -->
		<div id="wp-mail-smtp-setting-row-<?php echo esc_attr( $this->get_slug() ); ?>-user" class="wp-mail-smtp-setting-row wp-mail-smtp-setting-row-text wp-mail-smtp-clear <?php echo ! $this->options->is_const_defined( $this->get_slug(), 'auth' ) && ! $this->options->get( $this->get_slug(), 'auth' ) ? 'inactive' : ''; ?>">
			<div class="wp-mail-smtp-setting-label">
				<label for="wp-mail-smtp-setting-<?php echo esc_attr( $this->get_slug() ); ?>-user"><?php esc_html_e( 'SMTP Username', 'wp-mail-smtp' ); ?></label>
			</div>
			<div class="wp-mail-smtp-setting-field">
				<input name="wp-mail-smtp[<?php echo esc_attr( $this->get_slug() ); ?>][user]" type="text"
					value="<?php echo esc_attr( $this->options->get( $this->get_slug(), 'user' ) ); ?>"
					<?php echo $this->options->is_const_defined( $this->get_slug(), 'user' ) ? 'disabled' : ''; ?>
					id="wp-mail-smtp-setting-<?php echo esc_attr( $this->get_slug() ); ?>-user" spellcheck="false" autocomplete="new-password"
				/>
			</div>
		</div>

		<!-- SMTP Password -->
		<div id="wp-mail-smtp-setting-row-<?php echo esc_attr( $this->get_slug() ); ?>-pass" class="wp-mail-smtp-setting-row wp-mail-smtp-setting-row-password wp-mail-smtp-clear <?php echo ! $this->options->is_const_defined( $this->get_slug(), 'auth' ) && ! $this->options->get( $this->get_slug(), 'auth' ) ? 'inactive' : ''; ?>">
			<div class="wp-mail-smtp-setting-label">
				<label for="wp-mail-smtp-setting-<?php echo esc_attr( $this->get_slug() ); ?>-pass"><?php esc_html_e( 'SMTP Password', 'wp-mail-smtp' ); ?></label>
			</div>
			<div class="wp-mail-smtp-setting-field">
				<?php if ( $this->options->is_const_defined( $this->get_slug(), 'pass' ) ) : ?>
					<input type="text" value="*************" disabled id="wp-mail-smtp-setting-<?php echo esc_attr( $this->get_slug() ); ?>-pass"/>
				<?php else : ?>
					<input name="wp-mail-smtp[<?php echo esc_attr( $this->get_slug() ); ?>][pass]" type="password"
						value="<?php echo esc_attr( $this->options->get( $this->get_slug(), 'pass' ) ); ?>"
						id="wp-mail-smtp-setting-<?php echo esc_attr( $this->get_slug() ); ?>-pass" spellcheck="false" autocomplete="new-password"
					/>
					<p class="desc">
						<?php
						printf(
							/* translators: %s - wp-config.php. */
							esc_html__( 'The password is stored in plain text. We highly recommend you setup your password in your WordPress configuration file for improved security; to do this add the lines below to your %s file.', 'wp-mail-smtp' ),
							'<code>wp-config.php</code>'
						);
						?>
					</p>
					<pre>
						define( 'WPMS_ON', true );
						define( 'WPMS_SMTP_PASS', 'your_password' );
					</pre>
				<?php endif; ?>
			</div>
		</div>

		<?php
	}

	/**
	 * Check whether we can use this provider based on the PHP version.
	 * Valid for those, that use SDK.
	 *
	 * @return bool
	 */
	protected function is_php_correct() {
		return version_compare( phpversion(), $this->php, '>=' );
	}

	/**
	 * Display a helpful message to those users, that are using an outdated version of PHP,
	 * which is not supported by the currently selected Provider.
	 */
	protected function display_php_warning() {
		?>

		<blockquote>
			<?php
			printf(
				/* translators: %1$s - Provider name; %2$s - PHP version required by Provider; %3$s - current PHP version. */
				esc_html__( '%1$s requires PHP %2$s to work and does not support your current PHP version %3$s. Please contact your host and request a PHP upgrade to the latest one.', 'wp-mail-smtp' ),
				$this->title,
				$this->php,
				phpversion()
			)
			?>
			<br>
			<?php esc_html_e( 'Meanwhile you can switch to the "Other SMTP" Mailer option.', 'wp-mail-smtp' ); ?>
		</blockquote>

		<?php
	}
}
