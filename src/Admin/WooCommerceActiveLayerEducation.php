<?php

namespace WPMailSMTP\Admin;

use WPMailSMTP\WP;

/**
 * ActiveLayer education section on the WooCommerce Accounts & Privacy settings tab.
 *
 * Renders a WooCommerce-native card after the account settings block that helps
 * store owners install, activate, and connect the free ActiveLayer anti-spam
 * plugin. Install and activation reuse the shared plugin installer exposed by
 * the wp_mail_smtp_ajax dispatcher (Pages\AboutTab), so no install plumbing is
 * duplicated here.
 *
 * @since 4.9.0
 */
class WooCommerceActiveLayerEducation {

	/**
	 * ActiveLayer plugin basename (folder/file), used for install and activation checks.
	 *
	 * @since 4.9.0
	 */
	const PLUGIN_BASENAME = 'activelayer-anti-spam-spam-protection-for-forms-comments/activelayer-anti-spam-spam-protection-for-forms-comments.php';

	/**
	 * ActiveLayer WordPress.org download URL. Whitelisted in Pages\AboutTab::get_am_plugins().
	 *
	 * @since 4.9.0
	 */
	const DOWNLOAD_URL = 'https://downloads.wordpress.org/plugin/activelayer-anti-spam-spam-protection-for-forms-comments.zip';

	/**
	 * ActiveLayer WordPress.org plugin page (manual fallback).
	 *
	 * @since 4.9.0
	 */
	const WPORG_URL = 'https://wordpress.org/plugins/activelayer-anti-spam-spam-protection-for-forms-comments/';

	/**
	 * ActiveLayer settings page slug, the connect-account CTA target.
	 *
	 * @since 4.9.0
	 */
	const SETTINGS_PAGE = 'activelayer-settings';

	/**
	 * ActiveLayer integrations page slug, the connected-state link target.
	 *
	 * @since 4.9.0
	 */
	const INTEGRATIONS_PAGE = 'activelayer-integrations';

	/**
	 * User meta key persisting the per-user dismissal.
	 *
	 * @since 4.9.0
	 */
	const DISMISS_META = 'wp_mail_smtp_activelayer_wc_education_dismissed';

	/**
	 * Register hooks.
	 *
	 * @since 4.9.0
	 */
	public function hooks() {

		add_action( 'woocommerce_settings_account_registration_options_after', [ $this, 'output_section' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		add_action( 'wp_ajax_wp_mail_smtp_activelayer_wc_dismiss', [ $this, 'ajax_dismiss' ] );
	}

	/**
	 * Whether the ActiveLayer plugin is active.
	 *
	 * @since 4.9.0
	 *
	 * @return bool
	 */
	private function is_activelayer_active() {

		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		return is_plugin_active( self::PLUGIN_BASENAME );
	}

	/**
	 * Whether the ActiveLayer plugin is installed (present, active or not).
	 *
	 * @since 4.9.0
	 *
	 * @return bool
	 */
	private function is_activelayer_installed() {

		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		return array_key_exists( self::PLUGIN_BASENAME, get_plugins() );
	}

	/**
	 * Whether the current user can install plugins on this site.
	 *
	 * @since 4.9.0
	 *
	 * @return bool
	 */
	private function can_install() {

		return current_user_can( 'install_plugins' ) && wp_is_file_mod_allowed( 'wp_mail_smtp_can_install' );
	}

	/**
	 * Whether ActiveLayer has a validated API key connected.
	 *
	 * Mirrors ActiveLayer's own validation checks via cheap option reads,
	 * without booting any ActiveLayer classes.
	 *
	 * @since 4.9.0
	 *
	 * @return bool
	 */
	private function is_api_key_connected() {

		$settings = get_option( 'activelayer_global_settings', [] );
		$api_key  = '';

		if ( is_array( $settings ) && isset( $settings['api_key'] ) && is_string( $settings['api_key'] ) ) {
			$api_key = trim( $settings['api_key'] );
		}

		if ( empty( $api_key ) ) {
			return false;
		}

		$validation = get_option( 'activelayer_api_key_validated', [] );

		return is_array( $validation ) &&
			! empty( $validation['is_valid'] ) &&
			! empty( $validation['key'] ) &&
			$validation['key'] === $api_key;
	}

	/**
	 * Absolute URL of the ActiveLayer settings page (connect-account CTA).
	 *
	 * @since 4.9.0
	 *
	 * @return string
	 */
	private function get_settings_url() {

		return admin_url( 'admin.php?page=' . self::SETTINGS_PAGE );
	}

	/**
	 * Absolute URL of the ActiveLayer integrations page (connected-state link).
	 *
	 * @since 4.9.0
	 *
	 * @return string
	 */
	private function get_integrations_url() {

		return admin_url( 'admin.php?page=' . self::INTEGRATIONS_PAGE );
	}

	/**
	 * Resolve the render state for the current site and user.
	 *
	 * @since 4.9.0
	 *
	 * @return string One of 'install', 'activate', 'goto-url', 'connect', 'connected', or '' to hide.
	 */
	private function get_state() {

		if ( $this->is_activelayer_active() ) {
			return $this->is_api_key_connected() ? 'connected' : 'connect';
		}

		$is_installed = $this->is_activelayer_installed();

		if ( ! $is_installed && $this->can_install() ) {
			return 'install';
		}

		if ( $is_installed && current_user_can( 'activate_plugins' ) ) {
			return 'activate';
		}

		// Admins who cannot run the installer (file mods locked) can still follow a link.
		if ( current_user_can( 'install_plugins' ) ) {
			return 'goto-url';
		}

		// Users who cannot act at all (e.g. shop managers) get no dead-end card.
		return '';
	}

	/**
	 * Whether the section should be displayed for the current user.
	 *
	 * @since 4.9.0
	 *
	 * @return bool
	 */
	private function should_display() {

		/**
		 * Filters whether the ActiveLayer education section is rendered on the
		 * WooCommerce Accounts & Privacy settings tab.
		 *
		 * @since 4.9.0
		 *
		 * @param bool $should_display Whether the section should be displayed.
		 */
		if ( ! apply_filters( 'wp_mail_smtp_admin_woo_commerce_active_layer_education_should_display', true ) ) {
			return false;
		}

		if ( get_user_meta( get_current_user_id(), self::DISMISS_META, true ) ) {
			return false;
		}

		return $this->get_state() !== '';
	}

	/**
	 * Output the section after the WooCommerce account settings block.
	 *
	 * Fires on woocommerce_settings_account_registration_options_after, which
	 * WooCommerce runs after the section's closing table tag, so free-form
	 * markup is safe here.
	 *
	 * @since 4.9.0
	 */
	public function output_section() {

		if ( ! $this->should_display() ) {
			return;
		}

		$state = $this->get_state();
		?>
		<section id="wpms-activelayer-wc" class="wpms-activelayer-wc" aria-labelledby="wpms-activelayer-wc-heading">
			<button type="button" class="wpms-activelayer-wc__dismiss wp-mail-smtp-activelayer-wc-dismiss" aria-label="<?php echo esc_attr__( 'Dismiss this section', 'wp-mail-smtp' ); ?>">
				<span aria-hidden="true">&times;</span>
			</button>
			<div class="wpms-activelayer-wc__icon">
				<img src="<?php echo esc_url( wp_mail_smtp()->assets_url . '/images/activelayer/activelayer.svg' ); ?>" alt="">
			</div>
			<div class="wpms-activelayer-wc__body">
				<h3 id="wpms-activelayer-wc-heading" class="wpms-activelayer-wc__heading">
					<?php echo esc_html__( 'Stop Fake Customer Registrations and Review Spam', 'wp-mail-smtp' ); ?>
				</h3>
				<?php $this->output_state( $state ); ?>
			</div>
		</section>
		<?php
	}

	/**
	 * Output the state-dependent description and call to action.
	 *
	 * @since 4.9.0
	 *
	 * @param string $state Render state.
	 */
	private function output_state( $state ) {

		if ( $state === 'connect' ) {
			?>
			<p class="wpms-activelayer-wc__description">
				<?php echo esc_html__( 'ActiveLayer is active. Connect your free account to start blocking fake registrations and review spam.', 'wp-mail-smtp' ); ?>
			</p>
			<?php
			if ( current_user_can( 'manage_activelayer' ) ) {
				?>
				<button type="button" class="wpms-activelayer-wc__cta wp-mail-smtp-activelayer-button" data-action="goto-settings" data-url="<?php echo esc_url( $this->get_settings_url() ); ?>">
					<?php echo esc_html__( 'Connect Your Free Account', 'wp-mail-smtp' ); ?>
				</button>
				<?php
			}

			return;
		}

		if ( $state === 'connected' ) {
			?>
			<p class="wpms-activelayer-wc__description">
				<?php echo esc_html__( 'ActiveLayer is protecting customer registration and product reviews.', 'wp-mail-smtp' ); ?>
				<?php if ( current_user_can( 'manage_activelayer' ) ) : ?>
					<a href="<?php echo esc_url( $this->get_integrations_url() ); ?>">
						<?php echo esc_html__( 'View ActiveLayer Settings', 'wp-mail-smtp' ); ?>
					</a>
				<?php endif; ?>
			</p>
			<?php

			return;
		}

		if ( $state === 'install' ) {
			$action      = 'install';
			$button_text = esc_html__( 'Install & Activate ActiveLayer', 'wp-mail-smtp' );
			$button_url  = '';
		} elseif ( $state === 'activate' ) {
			$action      = 'activate';
			$button_text = esc_html__( 'Activate ActiveLayer', 'wp-mail-smtp' );
			$button_url  = '';
		} else {
			$action      = 'goto-url';
			$button_text = esc_html__( 'Get ActiveLayer', 'wp-mail-smtp' );
			$button_url  = self::WPORG_URL;
		}
		?>
		<p class="wpms-activelayer-wc__description">
			<?php echo esc_html__( 'Blocks bot signups and review spam on My Account, checkout, and product reviews. No CAPTCHA anywhere on the path to purchase. Free plugin from WordPress.org, with 1,000 free spam checks and no credit card required.', 'wp-mail-smtp' ); ?>
		</p>
		<button type="button" class="wpms-activelayer-wc__cta wp-mail-smtp-activelayer-button" data-action="<?php echo esc_attr( $action ); ?>" data-url="<?php echo esc_url( $button_url ); ?>">
			<?php echo esc_html( $button_text ); ?>
		</button>
		<?php
	}

	/**
	 * Enqueue the section assets on the WooCommerce Accounts & Privacy tab only.
	 *
	 * @since 4.9.0
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_assets( $hook ) {

		if ( $hook !== 'woocommerce_page_wc-settings' ) {
			return;
		}

		// Read-only context detection on a GET request; no nonce applies.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : '';

		if ( $tab !== 'account' || ! $this->should_display() ) {
			return;
		}

		wp_enqueue_style(
			'wp-mail-smtp-activelayer-wc',
			wp_mail_smtp()->assets_url . '/css/smtp-activelayer-wc' . WP::asset_min() . '.css',
			[],
			WPMS_PLUGIN_VER
		);

		wp_enqueue_script(
			'wp-mail-smtp-activelayer-wc',
			wp_mail_smtp()->assets_url . '/js/smtp-activelayer-wc' . WP::asset_min() . '.js',
			[ 'jquery' ],
			WPMS_PLUGIN_VER,
			true
		);

		wp_localize_script(
			'wp-mail-smtp-activelayer-wc',
			'wp_mail_smtp_activelayer_wc',
			[
				'ajax_url'        => admin_url( 'admin-ajax.php' ),
				'nonce'           => wp_create_nonce( 'wp-mail-smtp-admin' ),
				'plugin'          => self::PLUGIN_BASENAME,
				'download_url'    => self::DOWNLOAD_URL,
				'settings_url'    => $this->get_settings_url(),
				'wporg_url'       => self::WPORG_URL,
				'installing'      => esc_html__( 'Installing...', 'wp-mail-smtp' ),
				'activating'      => esc_html__( 'Activating...', 'wp-mail-smtp' ),
				'goto_settings'   => esc_html__( 'Connect Your Free Account', 'wp-mail-smtp' ),
				'get_activelayer' => esc_html__( 'Get ActiveLayer', 'wp-mail-smtp' ),
				'error_install'   => esc_html__( 'Could not install ActiveLayer. Please download it from WordPress.org and install manually.', 'wp-mail-smtp' ),
				'error_activate'  => esc_html__( 'Could not activate ActiveLayer. Please activate it from the Plugins page.', 'wp-mail-smtp' ),
			]
		);
	}

	/**
	 * AJAX: dismiss the section for the current user.
	 *
	 * @since 4.9.0
	 */
	public function ajax_dismiss() {

		if ( check_ajax_referer( 'wp-mail-smtp-admin', 'nonce', false ) === false ) {
			wp_send_json_error( esc_html__( 'Could not dismiss the section. Please reload the page and try again.', 'wp-mail-smtp' ) );
		}

		update_user_meta( get_current_user_id(), self::DISMISS_META, time() );

		wp_send_json_success();
	}
}
