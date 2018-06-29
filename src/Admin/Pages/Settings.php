<?php

namespace WPMailSMTP\Admin\Pages;

use WPMailSMTP\Admin\PageAbstract;
use WPMailSMTP\Debug;
use WPMailSMTP\Options;
use WPMailSMTP\WP;

/**
 * Class Settings is part of Area, displays general settings of the plugin.
 *
 * @since 1.0.0
 */
class Settings extends PageAbstract {

	/**
	 * @var string Slug of a tab.
	 */
	protected $slug = 'settings';

	/**
	 * @inheritdoc
	 */
	public function get_label() {
		return esc_html__( 'Settings', 'wp-mail-smtp' );
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
		?>

		<form method="POST" action="" autocomplete="off">
			<?php $this->wp_nonce_field(); ?>

			<!-- Mail Section Title -->
			<div class="wp-mail-smtp-setting-row wp-mail-smtp-setting-row-content wp-mail-smtp-clear section-heading no-desc" id="wp-mail-smtp-setting-row-email-heading">
				<div class="wp-mail-smtp-setting-field">
					<h2><?php esc_html_e( 'Mail', 'wp-mail-smtp' ); ?></h2>
				</div>
			</div>

			<!-- From Email -->
			<div id="wp-mail-smtp-setting-row-from_email" class="wp-mail-smtp-setting-row wp-mail-smtp-setting-row-email wp-mail-smtp-clear">
				<div class="wp-mail-smtp-setting-label">
					<label for="wp-mail-smtp-setting-from_email"><?php esc_html_e( 'From Email', 'wp-mail-smtp' ); ?></label>
				</div>
				<div class="wp-mail-smtp-setting-field">
					<input name="wp-mail-smtp[mail][from_email]" type="email"
						value="<?php echo esc_attr( $options->get( 'mail', 'from_email' ) ); ?>"
						<?php echo $options->is_const_defined( 'mail', 'from_email' ) ? 'disabled' : ''; ?>
						id="wp-mail-smtp-setting-from_email" spellcheck="false"
						placeholder="<?php echo esc_attr( wp_mail_smtp()->get_processor()->get_default_email() ); ?>">

					<p class="desc">
						<?php esc_html_e( 'The email address which emails are sent from.', 'wp-mail-smtp' ); ?><br/>
						<?php esc_html_e( 'If you using an email provider (Gmail, Yahoo, Outlook.com, etc) this should be your email address for that account.', 'wp-mail-smtp' ); ?>
					</p>

					<p class="desc">
						<?php esc_html_e( 'Please note that other plugins can change this, to prevent this use the setting below.', 'wp-mail-smtp' ); ?>
					</p>

					<hr class="wp-mail-smtp-setting-mid-row-sep">

					<input name="wp-mail-smtp[mail][from_email_force]" type="checkbox"
						value="true" <?php checked( true, (bool) $options->get( 'mail', 'from_email_force' ) ); ?>
						<?php echo $options->is_const_defined( 'mail', 'from_email_force' ) ? 'disabled' : ''; ?>
						id="wp-mail-smtp-setting-from_email_force">

					<label for="wp-mail-smtp-setting-from_email_force">
						<?php esc_html_e( 'Force From Email', 'wp-mail-smtp' ); ?>
					</label>

					<p class="desc">
						<?php esc_html_e( 'If checked, the From Email setting above will be used for all emails, ignoring values set by other plugins.', 'wp-mail-smtp' ); ?>
					</p>
				</div>
			</div>

			<!-- From Name -->
			<div id="wp-mail-smtp-setting-row-from_name" class="wp-mail-smtp-setting-row wp-mail-smtp-setting-row-text wp-mail-smtp-clear">
				<div class="wp-mail-smtp-setting-label">
					<label for="wp-mail-smtp-setting-from_name"><?php esc_html_e( 'From Name', 'wp-mail-smtp' ); ?></label>
				</div>
				<div class="wp-mail-smtp-setting-field">
					<input name="wp-mail-smtp[mail][from_name]" type="text"
						value="<?php echo esc_attr( $options->get( 'mail', 'from_name' ) ); ?>"
						<?php echo $options->is_const_defined( 'mail', 'from_name' ) ? 'disabled' : ''; ?>
						id="wp-mail-smtp-setting-from_name" spellcheck="false"
						placeholder="<?php echo esc_attr( wp_mail_smtp()->get_processor()->get_default_name() ); ?>">

					<p class="desc">
						<?php esc_html_e( 'The name which emails are sent from.', 'wp-mail-smtp' ); ?>
					</p>

					<hr class="wp-mail-smtp-setting-mid-row-sep">

					<input name="wp-mail-smtp[mail][from_name_force]" type="checkbox"
						value="true" <?php checked( true, (bool) $options->get( 'mail', 'from_name_force' ) ); ?>
						<?php echo $options->is_const_defined( 'mail', 'from_name_force' ) ? 'disabled' : ''; ?>
						id="wp-mail-smtp-setting-from_name_force">

					<label for="wp-mail-smtp-setting-from_name_force">
						<?php esc_html_e( 'Force From Name', 'wp-mail-smtp' ); ?>
					</label>

					<p class="desc">
						<?php esc_html_e( 'If checked, the From Name setting above will be used for all emails, ignoring values set by other plugins.', 'wp-mail-smtp' ); ?>
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

							<div class="wp-mail-smtp-mailer <?php echo $mailer === $provider->get_slug() ? 'active' : ''; ?>">
								<div class="wp-mail-smtp-mailer-image">
									<img src="<?php echo esc_url( $provider->get_logo_url() ); ?>"
										alt="<?php echo esc_attr( $provider->get_title() ); ?>">
								</div>

								<div class="wp-mail-smtp-mailer-text">
									<input id="wp-mail-smtp-setting-mailer-<?php echo esc_attr( $provider->get_slug() ); ?>"
										type="radio" name="wp-mail-smtp[mail][mailer]"
										value="<?php echo esc_attr( $provider->get_slug() ); ?>"
										<?php checked( $provider->get_slug(), $mailer ); ?>
										<?php echo $options->is_const_defined( 'mail', 'mailer' ) ? 'disabled' : ''; ?>
									/>
									<label for="wp-mail-smtp-setting-mailer-<?php echo esc_attr( $provider->get_slug() ); ?>"><?php echo $provider->get_title(); ?></label>
								</div>
							</div>

						<?php endforeach; ?>

					</div>
				</div>
			</div>

			<!-- Return Path -->
			<div id="wp-mail-smtp-setting-row-return_path" class="wp-mail-smtp-setting-row wp-mail-smtp-setting-row-checkbox wp-mail-smtp-clear">
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
						<?php esc_html_e( 'If unchecked bounce messages may be lost.', 'wp-mail-smtp' ); ?>
					</p>
				</div>
			</div>

			<!-- Mailer Options -->
			<div class="wp-mail-smtp-mailer-options">
				<?php foreach ( wp_mail_smtp()->get_providers()->get_options_all() as $provider ) : ?>

					<div class="wp-mail-smtp-mailer-option wp-mail-smtp-mailer-option-<?php echo esc_attr( $provider->get_slug() ); ?> <?php echo $mailer === $provider->get_slug() ? 'active' : 'hidden'; ?>">

						<!-- Mailer Option Title -->
						<?php $provider_desc = $provider->get_description(); ?>
						<div class="wp-mail-smtp-setting-row wp-mail-smtp-setting-row-content wp-mail-smtp-clear section-heading <?php echo empty( $provider_desc ) ? 'no-desc' : ''; ?>" id="wp-mail-smtp-setting-row-email-heading">
							<div class="wp-mail-smtp-setting-field">
								<h2><?php echo $provider->get_title(); ?></h2>
								<?php if ( ! empty( $provider_desc ) ) : ?>
									<p class="desc"><?php echo $provider_desc; ?></p>
								<?php endif; ?>
							</div>
						</div>

						<?php $provider->display_options(); ?>
					</div>

				<?php endforeach; ?>

			</div>

			<p class="wp-mail-smtp-submit">
				<button type="submit" class="wp-mail-smtp-btn wp-mail-smtp-btn-md wp-mail-smtp-btn-orange"><?php esc_html_e( 'Save Settings', 'wp-mail-smtp' ); ?></button>
			</p>

		</form>

		<?php
		$this->display_wpforms();
	}

	/**
	 * Display a WPForms-related message.
	 *
	 * @since 1.3.0
	 */
	protected function display_wpforms() {

		// Do not display if WPForms Pro already installed.
		if ( class_exists( 'WPForms_Pro', false ) ) {
			return;
		}

		$is_dismissed = get_user_meta( get_current_user_id(), 'wp_mail_smtp_wpforms_dismissed', true );

		// Do not display if user dismissed.
		if ( (bool) $is_dismissed === true ) {
			return;
		}
		?>

		<div id="wp-mail-smtp-wpforms">

			<span class="wp-mail-smtp-wpforms-dismiss">
				<button id="wp-mail-smtp-wpforms-dismiss">
					<span class="dashicons dashicons-dismiss"></span>
				</button>
			</span>

			<h2>
				<?php esc_html_e( 'Get WPForms Pro and Support WP Mail SMTP', 'wp-mail-smtp' ); ?>
			</h2>

			<p>
				<?php esc_html_e( 'WP Mail SMTP is a free plugin, and the team behind WPForms maintains it to give back to the WordPress community.', 'wp-mail-smtp' ); ?>
			</p>

			<p>
				<?php
				printf(
					wp_kses(
						/* translators: %s - WPForms.com URL. */
						__( 'Please consider supporting us by <a href="%s" target="_blank" rel="noopener noreferrer">purchasing a WPForms Pro license</a>. Aside from getting access to the best drag & drop WordPress form builder plugin, your purchase will help us continue to maintain and add new features to the WP Mail SMTP plugin while keeping this SMTP plugin free for the larger WordPress community.', 'wp-mail-smtp' ),
						array(
							'a' => array(
								'href'   => array(),
								'target' => array(),
								'rel'    => array(),
							),
						)
					),
					'https://wpforms.com/?discount=THANKYOU&utm_source=WordPress&utm_medium=settings-cta&utm_campaign=smtpplugin'
				);
				?>
			</p>

			<p>
				<?php
				printf(
					/* translators: %s - link to WP.org repo and 5 HTML encoded stars as a label. */
					esc_html__( 'We know that you will truly love WPForms. It has over 2000+ five star ratings (%s) and is active on over 1 million websites.', 'wp-mail-smtp' ),
					'<a href="https://wordpress.org/support/plugin/wpforms-lite/reviews/?filter=5" target="_blank" rel="noopener noreferrer" class="stars"><span class="dashicons dashicons-star-filled"></span><span class="dashicons dashicons-star-filled"></span><span class="dashicons dashicons-star-filled"></span><span class="dashicons dashicons-star-filled"></span><span class="dashicons dashicons-star-filled"></span></a>'
				);
				?>
			</p>

			<p><strong><?php esc_html_e( 'Other Benefits:', 'wp-mail-smtp' ); ?></strong></p>

			<div class="benefits">
				<ul>
					<li><?php esc_html_e( 'Access to premium support for WP Mail SMTP', 'wp-mail-smtp' ); ?></li>
					<li><?php esc_html_e( 'Get the best drag & drop form builder', 'wp-mail-smtp' ); ?></li>
					<li><?php esc_html_e( 'All form features like file upload, pagination, etc', 'wp-mail-smtp' ); ?></li>
					<li><?php esc_html_e( 'Create surveys & polls with the surveys addon', 'wp-mail-smtp' ); ?></li>
				</ul>
				<ul>
					<li><?php esc_html_e( 'No future ads inside WP Mail SMTP admin', 'wp-mail-smtp' ); ?></li>
					<li><?php esc_html_e( 'Pre-made form templates and smart conditional logic', 'wp-mail-smtp' ); ?></li>
					<li><?php esc_html_e( '500+ integrations with different marketing & payment services', 'wp-mail-smtp' ); ?></li>
					<li><?php esc_html_e( 'Collect signatures, geo-location data, and more', 'wp-mail-smtp' ); ?></li>
				</ul>
			</div>

			<p>
				<?php
				printf(
					wp_kses(
						/* translators: %1$s - WPForms.com URL, %2$s - percents off. */
						__( '<a href="%1$s" target="_blank" rel="noopener noreferrer">Get WPForms Pro and support WP Mail SMTP</a> - use coupon "<strong>thankyou</strong>" to save %2$s off your purchase.', 'wp-mail-smtp' ),
						array(
							'a'      => array(
								'href'   => array(),
								'target' => array(),
								'rel'    => array(),
							),
							'strong' => array(),
						)
					),
					'https://wpforms.com/?discount=THANKYOU&utm_source=WordPress&utm_medium=settings-cta&utm_campaign=smtpplugin',
					'20%'
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

		// Remove all debug messages when switching mailers.
		if ( $old_opt['mail']['mailer'] !== $data['mail']['mailer'] ) {
			Debug::clear();
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

		// New gmail clients data will be added from new $data.
		$to_save = Options::array_merge_recursive( $old_opt, $data );

		// All the sanitization is done in Options class.
		$options->set( $to_save );

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
