<?php

namespace WPMailSMTP\Admin\Pages;

use WPMailSMTP\Admin\ConnectionSettings;
use WPMailSMTP\Admin\PageAbstract;
use WPMailSMTP\Admin\SetupWizard;
use WPMailSMTP\Options;
use WPMailSMTP\WP;

/**
 * Class SettingsTab is part of Area, displays general settings of the plugin.
 *
 * @since 1.0.0
 */
class SettingsTab extends PageAbstract {

	/**
	 * Settings constructor.
	 *
	 * @since 1.5.0
	 */
	public function __construct() {
		parent::__construct();

		add_action( 'wp_mail_smtp_admin_pages_settings_license_key', array( __CLASS__, 'display_license_key_field_content' ) );
	}

	/**
	 * @var string Slug of a tab.
	 */
	protected $slug = 'settings';

	/**
	 * @inheritdoc
	 */
	public function get_label() {
		return esc_html__( 'General', 'wp-mail-smtp' );
	}

	/**
	 * @inheritdoc
	 */
	public function get_title() {
		return $this->get_label();
	}

	/**
	 * @inheritdoc
	 */
	public function display() {

		$options = Options::init();
		?>

		<form method="POST" action="" autocomplete="off" class="wp-mail-smtp-connection-settings-form">
			<?php $this->wp_nonce_field(); ?>

			<?php ob_start(); ?>

			<!-- License Section Title -->
			<div class="wp-mail-smtp-setting-row wp-mail-smtp-setting-row-content wp-mail-smtp-clear section-heading" id="wp-mail-smtp-setting-row-license-heading">
				<div class="wp-mail-smtp-setting-field">
					<h2><?php esc_html_e( 'License', 'wp-mail-smtp' ); ?></h2>

					<p class="desc">
						<?php esc_html_e( 'Your license key provides access to updates and support.', 'wp-mail-smtp' ); ?>
					</p>
				</div>
			</div>

			<!-- License Key -->
			<div id="wp-mail-smtp-setting-row-license_key" class="wp-mail-smtp-setting-row wp-mail-smtp-setting-row-license_key wp-mail-smtp-clear">
				<div class="wp-mail-smtp-setting-label">
					<label for="wp-mail-smtp-setting-license_key"><?php esc_html_e( 'License Key', 'wp-mail-smtp' ); ?></label>
				</div>
				<div class="wp-mail-smtp-setting-field">
					<?php do_action( 'wp_mail_smtp_admin_pages_settings_license_key', $options ); ?>
				</div>
			</div>

			<!-- Mail Section Title -->
			<div class="wp-mail-smtp-setting-row wp-mail-smtp-setting-row-content wp-mail-smtp-clear section-heading no-desc">
				<div class="wp-mail-smtp-setting-field">
					<h2><?php esc_html_e( 'Primary Connection', 'wp-mail-smtp' ); ?></h2>
				</div>
			</div>

			<?php if ( ! is_network_admin() ) : ?>
				<!-- Setup Wizard button -->
				<div id="wp-mail-smtp-setting-row-setup-wizard-button" class="wp-mail-smtp-setting-row wp-mail-smtp-setting-row-email wp-mail-smtp-clear">
					<div class="wp-mail-smtp-setting-label">
						<label for="wp-mail-smtp-setting-from_email"><?php esc_html_e( 'Setup Wizard', 'wp-mail-smtp' ); ?></label>
					</div>
					<div class="wp-mail-smtp-setting-field">
						<a href="<?php echo esc_url( SetupWizard::get_site_url() ); ?>" class="wp-mail-smtp-btn wp-mail-smtp-btn-md wp-mail-smtp-btn-blueish">
							<?php esc_html_e( 'Launch Setup Wizard', 'wp-mail-smtp' ); ?>
						</a>

						<p class="desc">
							<?php esc_html_e( 'We\'ll guide you through each step needed to get WP Mail SMTP fully set up on your site.', 'wp-mail-smtp' ); ?>
						</p>
					</div>
				</div>
			<?php endif; ?>

			<?php
			$connection          = wp_mail_smtp()->get_connections_manager()->get_primary_connection();
			$connection_settings = new ConnectionSettings( $connection );

			// Display connection settings.
			$connection_settings->display();
			?>

			<?php $this->display_backup_connection_education(); ?>

			<?php
			$settings_content = apply_filters( 'wp_mail_smtp_admin_settings_tab_display', ob_get_clean() );
			echo $settings_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			?>

			<?php $this->display_save_btn(); ?>

		</form>

		<?php
		$this->display_wpforms();
		$this->display_pro_banner();
	}

	/**
	 * License key text for a Lite version of the plugin.
	 *
	 * @since 1.5.0
	 *
	 * @param Options $options
	 */
	public static function display_license_key_field_content( $options ) {
		?>

		<p><?php esc_html_e( 'You\'re using WP Mail SMTP Lite - no license needed. Enjoy!', 'wp-mail-smtp' ); ?> 🙂</p>

		<p>
			<?php
			printf(
				wp_kses( /* translators: %s - WPMailSMTP.com upgrade URL. */
					__( 'To unlock more features, consider <strong><a href="%s" target="_blank" rel="noopener noreferrer" class="wp-mail-smtp-upgrade-modal">upgrading to PRO</a></strong>.', 'wp-mail-smtp' ),
					array(
						'a'      => array(
							'href'   => array(),
							'class'  => array(),
							'target' => array(),
							'rel'    => array(),
						),
						'strong' => array(),
					)
				),
				esc_url( wp_mail_smtp()->get_upgrade_link( 'general-license-key' ) )
			);
			?>
		</p>

		<p class="desc">
			<?php
			printf(
				wp_kses( /* Translators: %s - discount value $50 */
					__( 'As a valued WP Mail SMTP Lite user you receive <strong>%s off</strong>, automatically applied at checkout!', 'wp-mail-smtp' ),
					array(
						'strong' => array(),
						'br'     => array(),
					)
				),
				'$50'
			);
			?>
		</p>

		<hr>

		<p>
			<?php esc_html_e( 'Already purchased? Simply enter your license key below to connect with WP Mail SMTP Pro!', 'wp-mail-smtp' ); ?>
		</p>

		<p>
			<input type="password" id="wp-mail-smtp-setting-upgrade-license-key" class="wp-mail-smtp-not-form-input" placeholder="<?php esc_attr_e( 'Paste license key here', 'wp-mail-smtp' ); ?>" value="" />
			<button type="button" class="wp-mail-smtp-btn wp-mail-smtp-btn-md wp-mail-smtp-btn-orange" id="wp-mail-smtp-setting-upgrade-license-button">
				<?php esc_attr_e( 'Connect', 'wp-mail-smtp' ); ?>
			</button>
		</p>

		<?php
	}

	/**
	 * Display a WPForms related message.
	 *
	 * @since 1.3.0
	 * @since 1.4.0 Display only to site admins.
	 * @since 1.5.0 Do nothing.
	 */
	protected function display_wpforms() {
		/*
		 * Used to have this check:
		 *
		 * $is_dismissed = get_user_meta( get_current_user_id(), 'wp_mail_smtp_wpforms_dismissed', true );
		 */
	}

	/**
	 * Display WP Mail SMTP Pro upgrade banner.
	 *
	 * @since 1.5.0
	 */
	protected function display_pro_banner() {

		// Display only to site admins. Only site admins can install plugins.
		if ( ! is_super_admin() ) {
			return;
		}

		// Do not display if WP Mail SMTP Pro already installed.
		if ( wp_mail_smtp()->is_pro() ) {
			return;
		}

		$is_dismissed = get_user_meta( get_current_user_id(), 'wp_mail_smtp_pro_banner_dismissed', true );

		// Do not display if user dismissed.
		if ( (bool) $is_dismissed === true ) {
			return;
		}
		?>

		<div id="wp-mail-smtp-pro-banner">

			<span class="wp-mail-smtp-pro-banner-dismiss">
				<button id="wp-mail-smtp-pro-banner-dismiss">
					<span class="dashicons dashicons-dismiss"></span>
				</button>
			</span>

			<h2>
				<?php esc_html_e( 'Get WP Mail SMTP Pro and Unlock all the Powerful Features', 'wp-mail-smtp' ); ?>
			</h2>

			<p>
				<?php esc_html_e( 'Thanks for being a loyal WP Mail SMTP user. Upgrade to WP Mail SMTP Pro to unlock more awesome features and experience why WP Mail SMTP is the most popular SMTP plugin.', 'wp-mail-smtp' ); ?>
			</p>

			<p>
				<?php esc_html_e( 'We know that you will truly love WP Mail SMTP. It\'s used by over 4,000,000 websites.', 'wp-mail-smtp' ); ?>
			</p>

			<p><strong><?php esc_html_e( 'Pro Features:', 'wp-mail-smtp' ); ?></strong></p>

			<div class="benefits">
				<ul>
					<li><?php esc_html_e( 'Email Logging - keep track of every email sent from your site', 'wp-mail-smtp' ); ?></li>
					<li><?php esc_html_e( 'Alerts - get notified when your emails fail (via email, slack or SMS)', 'wp-mail-smtp' ); ?></li>
					<li><?php esc_html_e( 'Backup Connection - send emails even if your primary connection fails', 'wp-mail-smtp' ); ?></li>
					<li><?php esc_html_e( 'Smart Routing - define conditions for your email sending', 'wp-mail-smtp' ); ?></li>
					<li><?php esc_html_e( 'Amazon SES - harness the power of AWS', 'wp-mail-smtp' ); ?></li>
					<li><?php esc_html_e( 'Outlook - send emails using your Outlook or Microsoft 365 account', 'wp-mail-smtp' ); ?></li>
					<li><?php esc_html_e( 'Zoho Mail - use your Zoho Mail account to send emails', 'wp-mail-smtp' ); ?></li>
					<li><?php esc_html_e( 'Multisite Support - network settings for easy management', 'wp-mail-smtp' ); ?></li>
					<li><?php esc_html_e( 'Manage Notifications - control which emails your site sends', 'wp-mail-smtp' ); ?></li>
					<li><?php esc_html_e( 'Access to our world class support team', 'wp-mail-smtp' ); ?></li>
				</ul>
				<ul>
					<li><?php esc_html_e( 'White Glove Setup - sit back and relax while we handle everything for you', 'wp-mail-smtp' ); ?></li>
					<li class="arrow-right"><?php esc_html_e( 'Install & Setup WP Mail SMTP Pro plugin', 'wp-mail-smtp' ); ?></li>
					<li class="arrow-right"><?php esc_html_e( 'Configure SendLayer, SMTP.com or Brevo service', 'wp-mail-smtp' ); ?></li>
					<li class="arrow-right"><?php esc_html_e( 'Set up domain name verification (DNS)', 'wp-mail-smtp' ); ?></li>
					<li class="arrow-right"><?php esc_html_e( 'Test and verify email delivery', 'wp-mail-smtp' ); ?></li>
				</ul>
			</div>

			<p>
				<?php
				printf(
					wp_kses( /* translators: %s - WPMailSMTP.com URL. */
						__( '<a href="%s" target="_blank" rel="noopener noreferrer">Get WP Mail SMTP Pro Today and Unlock all the Powerful Features &raquo;</a>', 'wp-mail-smtp' ),
						array(
							'a'      => array(
								'href'   => array(),
								'target' => array(),
								'rel'    => array(),
							),
							'strong' => array(),
						)
					),
					esc_url( wp_mail_smtp()->get_upgrade_link( 'general-cta' ) )
				);
				?>
			</p>

			<p>
				<?php
				printf(
					wp_kses( /* Translators: %s - discount value $50. */
						__( '<strong>Bonus:</strong> WP Mail SMTP users get <span class="price-off">%s off regular price</span>, automatically applied at checkout.', 'wp-mail-smtp' ),
						array(
							'strong' => array(),
							'span'   => array(
								'class' => array(),
							),
						)
					),
					'$50'
				);
				?>
			</p>

		</div>

		<?php
	}

	/**
	 * Display backup connection education section.
	 *
	 * @since 3.7.0
	 */
	private function display_backup_connection_education() {

		if ( wp_mail_smtp()->is_pro() ) {
			return;
		}

		$upgrade_link_url = wp_mail_smtp()->get_upgrade_link(
			[
				'medium'  => 'Backup Connection Settings',
				'content' => 'Upgrade to WP Mail SMTP Pro Link',
			]
		);
		?>
		<div class="wp-mail-smtp-setting-row wp-mail-smtp-setting-row-content wp-mail-smtp-clear section-heading">
			<div class="wp-mail-smtp-setting-field">
				<h4 class="wp-mail-smtp-product-education__heading">
					<?php esc_html_e( 'Backup Connection', 'wp-mail-smtp' ); ?>
				</h4>
				<p class="wp-mail-smtp-product-education__description">
					<?php
					echo wp_kses(
						sprintf( /* translators: %s - WPMailSMTP.com Upgrade page URL. */
							__( 'Don’t worry about losing emails. Add an additional connection, then set it as your Backup Connection. Emails that fail to send with the Primary Connection will be sent via the selected Backup Connection. <a href="%s" target="_blank" rel="noopener noreferrer">Upgrade to WP Mail SMTP Pro!</a>', 'wp-mail-smtp' ),
							esc_url( $upgrade_link_url )
						),
						[
							'a' => [
								'href'   => [],
								'rel'    => [],
								'target' => [],
							],
						]
					);
					?>
				</p>
			</div>
		</div>
		<div class="wp-mail-smtp-setting-row wp-mail-smtp-clear">
			<div class="wp-mail-smtp-setting-label">
				<label>
					<?php esc_html_e( 'Backup Connection', 'wp-mail-smtp' ); ?>
				</label>
			</div>
			<div class="wp-mail-smtp-setting-field">
				<div class="wp-mail-smtp-connection-selector">
					<label>
						<input type="radio" checked/>
						<span><?php esc_attr_e( 'None', 'wp-mail-smtp' ); ?></span>
					</label>
				</div>
				<p class="desc">
					<?php
					echo wp_kses(
						sprintf( /* translators: %s - Smart routing settings page url. */
							__( 'Once you add an <a href="%s">additional connection</a>, you can select it here.', 'wp-mail-smtp' ),
							add_query_arg(
								[
									'tab' => 'connections',
								],
								wp_mail_smtp()->get_admin()->get_admin_page_url()
							)
						),
						[
							'a' => [
								'href'   => [],
								'target' => [],
								'rel'    => [],
							],
						]
					);
					?>
				</p>
			</div>
		</div>
		<?php
	}

	/**
	 * Process tab form submission ($_POST ).
	 *
	 * @since 1.0.0
	 *
	 * @param array $data Post data specific for the plugin.
	 */
	public function process_post( $data ) {

		$this->check_admin_referer();

		$connection          = wp_mail_smtp()->get_connections_manager()->get_primary_connection();
		$connection_settings = new ConnectionSettings( $connection );

		$old_data = $connection->get_options()->get_all();

		$data = $connection_settings->process( $data, $old_data );

		/**
		 * Filters mail settings before save.
		 *
		 * @since 2.2.1
		 *
		 * @param array $data Settings data.
		 */
		$data = apply_filters( 'wp_mail_smtp_settings_tab_process_post', $data );

		// All the sanitization is done in Options class.
		Options::init()->set( $data, false, false );

		$connection_settings->post_process( $data, $old_data );

		if ( $connection_settings->get_scroll_to() !== false ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotValidated
			wp_safe_redirect( sanitize_text_field( wp_unslash( $_POST['_wp_http_referer'] ) ) . $connection_settings->get_scroll_to() );
			exit;
		}

		WP::add_admin_notice(
			esc_html__( 'Settings were successfully saved.', 'wp-mail-smtp' ),
			WP::ADMIN_NOTICE_SUCCESS
		);
	}
}
