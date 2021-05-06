<?php

namespace WPMailSMTP\Admin\Pages;

use WPMailSMTP\Admin\PageAbstract;
use WPMailSMTP\Admin\SetupWizard;
use WPMailSMTP\Debug;
use WPMailSMTP\Options;
use WPMailSMTP\Providers\Gmail\Auth;
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

		$options = new Options();
		$mailer  = $options->get( 'mail', 'mailer' );

		$disabled_email = in_array( $mailer, [ 'outlook', 'zoho' ], true ) ? 'disabled' : '';
		$disabled_name  = 'outlook' === $mailer ? 'disabled' : '';

		if ( empty( $mailer ) ) {
			$mailer = 'mail';
		}

		$mailer_supported_settings = wp_mail_smtp()->get_providers()->get_options( $mailer )->get_supports();
		?>

		<form method="POST" action="" autocomplete="off">
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
			<div class="wp-mail-smtp-setting-row wp-mail-smtp-setting-row-content wp-mail-smtp-clear section-heading no-desc" id="wp-mail-smtp-setting-row-email-heading">
				<div class="wp-mail-smtp-setting-field">
					<h2><?php esc_html_e( 'Mail', 'wp-mail-smtp' ); ?></h2>
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

			<!-- From Email -->
			<div id="wp-mail-smtp-setting-row-from_email" class="wp-mail-smtp-setting-row wp-mail-smtp-setting-row-email wp-mail-smtp-clear">
				<div class="wp-mail-smtp-setting-label">
					<label for="wp-mail-smtp-setting-from_email"><?php esc_html_e( 'From Email', 'wp-mail-smtp' ); ?></label>
				</div>
				<div class="wp-mail-smtp-setting-field">
					<div class="js-wp-mail-smtp-setting-from_email" style="display: <?php echo empty( $mailer_supported_settings['from_email'] ) ? 'none' : 'block'; ?>;">
						<?php if ( 'gmail' !== $mailer ) : ?>
							<input name="wp-mail-smtp[mail][from_email]" type="email"
								value="<?php echo esc_attr( $options->get( 'mail', 'from_email' ) ); ?>"
								<?php echo $options->is_const_defined( 'mail', 'from_email' ) || ! empty( $disabled_email ) ? 'disabled' : ''; ?>
								id="wp-mail-smtp-setting-from_email" spellcheck="false"
								placeholder="<?php echo esc_attr( wp_mail_smtp()->get_processor()->get_default_email() ); ?>">
						<?php else : ?>
							<?php
							// Gmail mailer From Email selector.
							$gmail_auth    = new Auth();
							$gmail_aliases = $gmail_auth->is_clients_saved() ? $gmail_auth->get_user_possible_send_from_addresses() : [];
							?>

							<?php if ( empty( $gmail_aliases ) ) : ?>
								<select name="wp-mail-smtp[mail][from_email]" id="wp-mail-smtp-setting-from_email" disabled>
									<option value=""><?php esc_html_e( 'Please first authorize the Gmail mailer below', 'wp-mail-smtp' ); ?></option>
								</select>
							<?php else : ?>
								<select name="wp-mail-smtp[mail][from_email]" id="wp-mail-smtp-setting-from_email">
									<?php foreach ( $gmail_aliases as $gmail_email_address ) : ?>
										<option value="<?php echo esc_attr( $gmail_email_address ); ?>" <?php selected( $options->get( 'mail', 'from_email' ), $gmail_email_address ); ?>><?php echo esc_html( $gmail_email_address ); ?></option>
									<?php endforeach; ?>
								</select>
							<?php endif; ?>

						<?php endif; ?>

						<?php if ( ! in_array( $mailer, [ 'gmail', 'outlook', 'zoho' ], true ) ) : ?>
							<p class="desc">
								<?php esc_html_e( 'The email address that emails are sent from.', 'wp-mail-smtp' ); ?><br/>
								<?php esc_html_e( 'If you\'re using an email provider (Yahoo, Outlook.com, etc) this should be your email address for that account.', 'wp-mail-smtp' ); ?>
							</p>
							<p class="desc">
								<?php esc_html_e( 'Please note that other plugins can change this, to prevent this use the setting below.', 'wp-mail-smtp' ); ?>
							</p>
						<?php endif; ?>
					</div>

					<hr class="wp-mail-smtp-setting-mid-row-sep" style="display: <?php echo ( ! empty( $mailer_supported_settings['from_email'] ) && ! empty( $mailer_supported_settings['from_email_force'] ) ) ? 'block' : 'none'; ?>;">

					<div class="js-wp-mail-smtp-setting-from_email_force" style="display: <?php echo empty( $mailer_supported_settings['from_email_force'] ) ? 'none' : 'block'; ?>;">
						<input name="wp-mail-smtp[mail][from_email_force]" type="checkbox"
							value="true" <?php checked( true, (bool) $options->get( 'mail', 'from_email_force' ) ); ?>
							<?php echo $options->is_const_defined( 'mail', 'from_email_force' ) || ! empty( $disabled_email ) ? 'disabled' : ''; ?>
							id="wp-mail-smtp-setting-from_email_force">

						<label for="wp-mail-smtp-setting-from_email_force">
							<?php esc_html_e( 'Force From Email', 'wp-mail-smtp' ); ?>
						</label>

						<?php if ( ! empty( $disabled_email ) ) : ?>
							<p class="desc">
								<?php esc_html_e( 'Current provider will automatically force From Email to be the email address that you use to set up the connection below.', 'wp-mail-smtp' ); ?>
							</p>
						<?php else : ?>
							<p class="desc">
								<?php esc_html_e( 'If checked, the From Email setting above will be used for all emails, ignoring values set by other plugins.', 'wp-mail-smtp' ); ?>
							</p>
						<?php endif; ?>
					</div>
				</div>
			</div>

			<!-- From Name -->
			<div id="wp-mail-smtp-setting-row-from_name" class="wp-mail-smtp-setting-row wp-mail-smtp-setting-row-text wp-mail-smtp-clear">
				<div class="wp-mail-smtp-setting-label">
					<label for="wp-mail-smtp-setting-from_name"><?php esc_html_e( 'From Name', 'wp-mail-smtp' ); ?></label>
				</div>
				<div class="wp-mail-smtp-setting-field">
					<div class="js-wp-mail-smtp-setting-from_name" style="display: <?php echo empty( $mailer_supported_settings['from_name'] ) ? 'none' : 'block'; ?>;">
						<input name="wp-mail-smtp[mail][from_name]" type="text"
							value="<?php echo esc_attr( $options->get( 'mail', 'from_name' ) ); ?>"
							<?php echo $options->is_const_defined( 'mail', 'from_name' ) || ! empty( $disabled_name ) ? 'disabled' : ''; ?>
							id="wp-mail-smtp-setting-from_name" spellcheck="false"
							placeholder="<?php echo esc_attr( wp_mail_smtp()->get_processor()->get_default_name() ); ?>">

						<?php if ( empty( $disabled_name ) ) : ?>
							<p class="desc">
								<?php esc_html_e( 'The name that emails are sent from.', 'wp-mail-smtp' ); ?>
							</p>
						<?php endif; ?>
					</div>

					<hr class="wp-mail-smtp-setting-mid-row-sep" style="display: <?php echo ( ! empty( $mailer_supported_settings['from_name'] ) && ! empty( $mailer_supported_settings['from_name_force'] ) ) ? 'block' : 'none'; ?>;">

					<div class="js-wp-mail-smtp-setting-from_name_force" style="display: <?php echo empty( $mailer_supported_settings['from_name_force'] ) ? 'none' : 'block'; ?>;">
						<input name="wp-mail-smtp[mail][from_name_force]" type="checkbox"
							value="true" <?php checked( true, (bool) $options->get( 'mail', 'from_name_force' ) ); ?>
							<?php echo $options->is_const_defined( 'mail', 'from_name_force' ) || ! empty( $disabled_name ) ? 'disabled' : ''; ?>
							id="wp-mail-smtp-setting-from_name_force">

						<label for="wp-mail-smtp-setting-from_name_force">
							<?php esc_html_e( 'Force From Name', 'wp-mail-smtp' ); ?>
						</label>

						<?php if ( ! empty( $disabled_name ) ) : ?>
							<p class="desc">
								<?php esc_html_e( 'Current provider doesn\'t support setting and forcing From Name. Emails will be sent on behalf of the account name used to setup the connection below.', 'wp-mail-smtp' ); ?>
							</p>
						<?php else : ?>
							<p class="desc">
								<?php esc_html_e( 'If checked, the From Name setting above will be used for all emails, ignoring values set by other plugins.', 'wp-mail-smtp' ); ?>
							</p>
						<?php endif; ?>
					</div>
				</div>
			</div>

			<!-- Return Path -->
			<div id="wp-mail-smtp-setting-row-return_path" class="wp-mail-smtp-setting-row wp-mail-smtp-setting-row-checkbox wp-mail-smtp-clear js-wp-mail-smtp-setting-return_path" style="display: <?php echo empty( $mailer_supported_settings['return_path'] ) ? 'none' : 'block'; ?>;">
				<div class="wp-mail-smtp-setting-label">
					<label for="wp-mail-smtp-setting-return_path"><?php esc_html_e( 'Return Path', 'wp-mail-smtp' ); ?></label>
				</div>
				<div class="wp-mail-smtp-setting-field">
					<input name="wp-mail-smtp[mail][return_path]" type="checkbox"
					       value="true" <?php checked( true, (bool) $options->get( 'mail', 'return_path' ) ); ?>
						<?php echo $options->is_const_defined( 'mail', 'return_path' ) ? 'disabled' : ''; ?>
						   id="wp-mail-smtp-setting-return_path">

					<label for="wp-mail-smtp-setting-return_path">
						<?php esc_html_e( 'Set the return-path to match the From Email', 'wp-mail-smtp' ); ?>
					</label>

					<p class="desc">
						<?php esc_html_e( 'Return Path indicates where non-delivery receipts - or bounce messages - are to be sent.', 'wp-mail-smtp' ); ?><br/>
						<?php esc_html_e( 'If unchecked, bounce messages may be lost.', 'wp-mail-smtp' ); ?>
					</p>
				</div>
			</div>

			<!-- Mailer -->
			<div id="wp-mail-smtp-setting-row-mailer" class="wp-mail-smtp-setting-row wp-mail-smtp-setting-row-mailer wp-mail-smtp-clear">
				<div class="wp-mail-smtp-setting-label">
					<label for="wp-mail-smtp-setting-mailer"><?php esc_html_e( 'Mailer', 'wp-mail-smtp' ); ?></label>
				</div>
				<div class="wp-mail-smtp-setting-field">
					<div class="wp-mail-smtp-mailers">

						<?php foreach ( wp_mail_smtp()->get_providers()->get_options_all() as $provider ) : ?>

							<div class="wp-mail-smtp-mailer wp-mail-smtp-mailer-<?php echo esc_attr( $provider->get_slug() ); ?> <?php echo $mailer === $provider->get_slug() ? 'active' : ''; ?>">

								<div class="wp-mail-smtp-mailer-image <?php echo $provider->is_recommended() ? 'is-recommended' : ''; ?>">
									<img src="<?php echo esc_url( $provider->get_logo_url() ); ?>"
										alt="<?php echo esc_attr( $provider->get_title() ); ?>">
								</div>

								<div class="wp-mail-smtp-mailer-text">
									<?php if ( $provider->is_disabled() ) : ?>
										<input type="radio" name="wp-mail-smtp[mail][mailer]" disabled
											class="js-wp-mail-smtp-setting-mailer-radio-input educate"
											id="wp-mail-smtp-setting-mailer-<?php echo esc_attr( $provider->get_slug() ); ?>"
											value="<?php echo esc_attr( $provider->get_slug() ); ?>"
										/>
									<?php else : ?>
										<input id="wp-mail-smtp-setting-mailer-<?php echo esc_attr( $provider->get_slug() ); ?>"
											type="radio" name="wp-mail-smtp[mail][mailer]"
											value="<?php echo esc_attr( $provider->get_slug() ); ?>"
											class="js-wp-mail-smtp-setting-mailer-radio-input<?php echo $provider->is_disabled() ? ' educate' : ''; ?>"
											<?php checked( $provider->get_slug(), $mailer ); ?>
											<?php echo $options->is_const_defined( 'mail', 'mailer' ) || $provider->is_disabled() ? 'disabled' : ''; ?>
										/>
									<?php endif; ?>
									<label for="wp-mail-smtp-setting-mailer-<?php echo esc_attr( $provider->get_slug() ); ?>">
										<?php echo esc_html( $provider->get_title() ); ?>
									</label>
								</div>
							</div>

						<?php endforeach; ?>
					</div>

					<!-- Suggest a mailer -->
					<div class="wp-mail-smtp-suggest-new-mailer">
						<p class="desc">
							<?php esc_html_e( 'Don\'t see what you\'re looking for?', 'wp-mail-smtp' ); ?>
							<a href="https://wpmailsmtp.com/suggest-a-mailer" target="_blank" rel="noopener noreferrer">
								<?php esc_html_e( 'Suggest a Mailer', 'wp-mail-smtp' ); ?>
							</a>
						</p>
					</div>
				</div>
			</div>

			<!-- Mailer Options -->
			<div class="wp-mail-smtp-mailer-options">
				<?php foreach ( wp_mail_smtp()->get_providers()->get_options_all() as $provider ) : ?>
					<?php $provider_desc = $provider->get_description(); ?>
					<div class="wp-mail-smtp-mailer-option wp-mail-smtp-mailer-option-<?php echo esc_attr( $provider->get_slug() ); ?> <?php echo $mailer === $provider->get_slug() ? 'active' : 'hidden'; ?>">

						<!-- Mailer Title/Notice/Description -->
						<div class="wp-mail-smtp-setting-row wp-mail-smtp-setting-row-content wp-mail-smtp-clear section-heading <?php echo empty( $provider_desc ) ? 'no-desc' : ''; ?>" id="wp-mail-smtp-setting-row-email-heading">
							<div class="wp-mail-smtp-setting-field">
								<?php if ( $provider->is_disabled() ) : ?>
									<?php $provider->display_options(); ?>
								<?php else : ?>
									<h2><?php echo $provider->get_title(); ?></h2>
									<?php
									$provider_edu_notice = $provider->get_notice( 'educational' );
									$is_dismissed        = (bool) get_user_meta( get_current_user_id(), "wp_mail_smtp_notice_educational_for_{$provider->get_slug()}_dismissed", true );
									if ( ! empty( $provider_edu_notice ) && ! $is_dismissed ) :
										?>
										<p class="inline-notice inline-edu-notice"
											data-notice="educational"
											data-mailer="<?php echo esc_attr( $provider->get_slug() ); ?>">
											<a href="#" title="<?php esc_attr_e( 'Dismiss this notice', 'wp-mail-smtp' ); ?>"
												class="wp-mail-smtp-mailer-notice-dismiss js-wp-mail-smtp-mailer-notice-dismiss">
												<span class="dashicons dashicons-dismiss"></span>
											</a>

											<?php echo $provider_edu_notice; ?>
										</p>
									<?php endif; ?>

									<?php if ( ! empty( $provider_desc ) ) : ?>
										<p class="desc"><?php echo $provider_desc; ?></p>
									<?php endif; ?>
								<?php endif; ?>
							</div>
						</div>

						<?php $provider->display_options(); ?>
					</div>

				<?php endforeach; ?>

			</div>

			<?php
			$settings_content = apply_filters( 'wp_mail_smtp_admin_settings_tab_display', ob_get_clean() );
			echo $settings_content; // phpcs:ignore
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
					__( 'To unlock more features consider <strong><a href="%s" target="_blank" rel="noopener noreferrer" class="wp-mail-smtp-upgrade-modal">upgrading to PRO</a></strong>.', 'wp-mail-smtp' ),
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
				<?php esc_html_e( 'We know that you will truly love WP Mail SMTP. It\'s used by over 2,000,000 websites.', 'wp-mail-smtp' ); ?>
			</p>

			<p><strong><?php esc_html_e( 'Pro Features:', 'wp-mail-smtp' ); ?></strong></p>

			<div class="benefits">
				<ul>
					<li><?php esc_html_e( 'Manage Notifications - control which emails your site sends', 'wp-mail-smtp' ); ?></li>
					<li><?php esc_html_e( 'Email Logging - keep track of every email sent from your site', 'wp-mail-smtp' ); ?></li>
					<li><?php esc_html_e( 'Multisite Support - Network settings for easy management', 'wp-mail-smtp' ); ?></li>
					<li><?php esc_html_e( 'Office 365 - send emails using your Office 365 account', 'wp-mail-smtp' ); ?></li>
					<li><?php esc_html_e( 'Amazon SES - harness the power of AWS', 'wp-mail-smtp' ); ?></li>
					<li><?php esc_html_e( 'Outlook.com - send emails using your Outlook.com account', 'wp-mail-smtp' ); ?></li>
					<li><?php esc_html_e( 'Zoho Mail - use your Zoho Mail account', 'wp-mail-smtp' ); ?></li>
					<li><?php esc_html_e( 'Access to our world class support team', 'wp-mail-smtp' ); ?></li>
				</ul>
				<ul>
					<li><?php esc_html_e( 'White Glove Setup - sit back and relax while we handle everything for you', 'wp-mail-smtp' ); ?></li>
					<li class="arrow-right"><?php esc_html_e( 'Install WP Mail SMTP Pro plugin', 'wp-mail-smtp' ); ?></li>
					<li class="arrow-right"><?php esc_html_e( 'Set up domain name verification (DNS)', 'wp-mail-smtp' ); ?></li>
					<li class="arrow-right"><?php esc_html_e( 'Configure SMTP.com or Mailgun service', 'wp-mail-smtp' ); ?></li>
					<li class="arrow-right"><?php esc_html_e( 'Set up WP Mail SMTP Pro plugin', 'wp-mail-smtp' ); ?></li>
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
	 * @inheritdoc
	 */
	public function process_post( $data ) {

		$this->check_admin_referer();

		$options = new Options();
		$old_opt = $options->get_all();

		// When checkbox is unchecked - it's not submitted at all, so we need to define its default false value.
		if ( ! isset( $data['mail']['from_email_force'] ) ) {
			$data['mail']['from_email_force'] = false;
		}
		if ( ! isset( $data['mail']['from_name_force'] ) ) {
			$data['mail']['from_name_force'] = false;
		}
		if ( ! isset( $data['mail']['return_path'] ) ) {
			$data['mail']['return_path'] = false;
		}
		if ( ! isset( $data['smtp']['autotls'] ) ) {
			$data['smtp']['autotls'] = false;
		}
		if ( ! isset( $data['smtp']['auth'] ) ) {
			$data['smtp']['auth'] = false;
		}

		// When switching mailers.
		if (
			! empty( $old_opt['mail']['mailer'] ) &&
			! empty( $data['mail']['mailer'] ) &&
			$old_opt['mail']['mailer'] !== $data['mail']['mailer']
		) {

			// Remove all debug messages when switching mailers.
			Debug::clear();

			// Save correct from email address if Zoho or Outlook mailers are already configured.
			if (
				in_array( $data['mail']['mailer'], [ 'zoho', 'outlook' ], true ) &&
				! empty( $old_opt[ $data['mail']['mailer'] ]['user_details']['email'] )
			) {
				$data['mail']['from_email'] = $old_opt[ $data['mail']['mailer'] ]['user_details']['email'];
			}
		}

		$to_redirect = false;

		// Old and new Gmail client id/secret values are different - we need to invalidate tokens and scroll to Auth button.
		if (
			$options->get( 'mail', 'mailer' ) === 'gmail' &&
			! empty( $data['gmail']['client_id'] ) &&
			! empty( $data['gmail']['client_secret'] ) &&
			(
				$options->get( 'gmail', 'client_id' ) !== $data['gmail']['client_id'] ||
				$options->get( 'gmail', 'client_secret' ) !== $data['gmail']['client_secret']
			)
		) {
			unset( $old_opt['gmail'] );

			if (
				! empty( $data['gmail']['client_id'] ) &&
				! empty( $data['gmail']['client_secret'] )
			) {
				$to_redirect = true;
			}
		}

		$data = apply_filters( 'wp_mail_smtp_settings_tab_process_post', $data );

		// All the sanitization is done in Options class.
		$options->set( $data, false, false );

		if ( $to_redirect ) {
			wp_redirect( $_POST['_wp_http_referer'] . '#wp-mail-smtp-setting-row-gmail-authorize' );
			exit;
		}

		WP::add_admin_notice(
			esc_html__( 'Settings were successfully saved.', 'wp-mail-smtp' ),
			WP::ADMIN_NOTICE_SUCCESS
		);
	}
}
